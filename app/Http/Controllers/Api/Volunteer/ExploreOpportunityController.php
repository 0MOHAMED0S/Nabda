<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Validator;

class ExploreOpportunityController extends Controller
{
    /**
     * API: عرض جميع الفرص التطوعية المتاحة (مع حالة المتطوع الحالي)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // جلب الفرص النشطة فقط (التي يمكن التقديم عليها)
            $opportunities = VolunteerOpportunity::where('status', 'active')
                ->with(['foundation:id,name,logo']) // جلب اسم ولوجو المؤسسة
                // 🎯 السحر هنا: نجلب علاقة المتطوع الحالي بهذه الفرصة فقط لنعرف حالته
                ->with(['volunteers' => function ($query) use ($volunteerId) {
                    $query->where('volunteer_id', $volunteerId)
                          ->select('volunteers.id'); // لا نحتاج كل بيانات المتطوع، فقط الـ ID والحالة من الـ Pivot
                }])
                ->orderBy('date', 'asc') // ترتيب بالأقرب تاريخاً
                ->paginate(9); // 9 لكي تظهر كشبكة متناسقة (3 في كل صف) كما في الصورة

            // 🧹 تهيئة البيانات لتكون سهلة جداً على مطور الواجهة (Frontend)
            $opportunities->getCollection()->transform(function ($opportunity) {
                // إعداد رابط لوجو المؤسسة
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                // 🎯 استخراج حالة المتطوع الحالي في هذه الفرصة
                $application = $opportunity->volunteers->first();

                // إضافة حقل جديد للـ JSON يخبر الواجهة بحالة التقديم:
                // null (لم يتقدم), pending (في انتظار الموافقة), accepted (مقبول), rejected (مرفوض)
                $opportunity->user_application_status = $application ? $application->pivot->status : null;

                // تنظيف المصفوفة من العلاقة الثقيلة لأننا أخذنا الخلاصة
                unset($opportunity->volunteers);

                return $opportunity;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الفرص التطوعية بنجاح.',
                'data'    => $opportunities
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Fetch Opportunities Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب الفرص.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: عرض تفاصيل فرصة تطوعية واحدة (مع الروابط الكاملة وحالة المتطوع)
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // جلب الفرصة بالـ ID مع بيانات المؤسسة وعلاقة المتطوع الحالي بها
            $opportunity = VolunteerOpportunity::with(['foundation:id,name,logo'])
                ->with(['volunteers' => function ($query) use ($volunteerId) {
                    $query->where('volunteer_id', $volunteerId)
                          ->select('volunteers.id');
                }])
                ->where('id', $id)
                ->first();

            if (!$opportunity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرصة غير موجودة.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 🧹 تهيئة رابط المؤسسة (Full URL)
            if ($opportunity->foundation) {
                $opportunity->foundation->logo_url = $opportunity->foundation->logo
                    ? asset('storage/' . $opportunity->foundation->logo)
                    : null;
                $opportunity->foundation->makeHidden(['logo']);
            }

            // 🎯 استخراج حالة المتطوع الحالي في هذه الفرصة
            $application = $opportunity->volunteers->first();
            $opportunity->user_application_status = $application ? $application->pivot->status : null;

            // تنظيف البيانات
            unset($opportunity->volunteers);

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الفرصة بنجاح.',
                'data'    => $opportunity
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Show Opportunity Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب تفاصيل الفرصة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: التقديم على فرصة تطوعية
     */
    public function apply(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // التأكد أن الفرصة موجودة ونشطة
            $opportunity = VolunteerOpportunity::where('id', $id)->where('status', 'active')->first();

            if (!$opportunity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'هذه الفرصة غير موجودة أو لم تعد متاحة للتقديم.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // التأكد من أن المتطوع لم يتقدم مسبقاً
            $alreadyApplied = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->exists();

            if ($alreadyApplied) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لقد قمت بالتقديم على هذه الفرصة مسبقاً.'
                ], 400, [], JSON_UNESCAPED_UNICODE); // 400 Bad Request
            }

            // 🎯 تسجيل المتطوع في الفرصة بحالة (قيد الانتظار)
            $opportunity->volunteers()->attach($volunteerId, ['status' => 'pending']);

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال طلب التقديم بنجاح. أنت الآن في انتظار موافقة المؤسسة.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Apply Opportunity Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء التقديم على الفرصة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: إلغاء التقديم (الانسحاب من الفرصة)
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;
            $opportunity = VolunteerOpportunity::find($id);

            if (!$opportunity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرصة غير موجودة.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // التأكد من أن المتطوع مسجل بالفعل في هذه الفرصة
            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application) {
                return response()->json([
                    'status'  => false,
                    'message' => 'أنت غير مسجل في هذه الفرصة لتتمكن من إلغائها.'
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 حذف تسجيل المتطوع من الفرصة (Detach)
            $opportunity->volunteers()->detach($volunteerId);

            return response()->json([
                'status'  => true,
                'message' => 'تم إلغاء التقديم والانسحاب من الفرصة بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Cancel Opportunity Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء إلغاء التقديم.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: عرض المهام التطوعية الخاصة بالمتطوع (سجل التقديمات)
     * يدعم الفلترة: ?status=pending, accepted, attended, rejected
     */
    public function myTasks(Request $request): JsonResponse
    {
        try {
            $volunteer = $request->user();

            // استقبال معامل الفلترة من الرابط (إن وجد)
            $statusFilter = $request->query('status');

            // 1. بناء الاستعلام بناءً على علاقة المتطوع بالفرص
            $query = $volunteer->opportunities()
                ->with(['foundation:id,name,logo']) // جلب بيانات المؤسسة
                ->orderBy('opportunity_volunteer.created_at', 'desc'); // الأحدث تقديماً أولاً

            // 2. تطبيق الفلتر بناءً على التبويبات في الواجهة
            // مقبولة = accepted | قيد المراجعة = pending | مكتملة = attended | ملغاة = rejected
            if ($statusFilter && in_array($statusFilter, ['pending', 'accepted', 'attended', 'rejected'])) {
                $query->wherePivot('status', $statusFilter);
            }

            // 3. جلب البيانات مع التقسيم لصفحات
            $tasks = $query->paginate(10);

            // 4. تهيئة البيانات (روابط كاملة + تنظيف الـ JSON)
            $tasks->getCollection()->transform(function ($opportunity) {
                // إعداد رابط لوجو المؤسسة
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                // 🎯 استخراج حالة التقديم وتاريخه من جدول الـ Pivot مباشرة
                $opportunity->application_status = $opportunity->pivot->status;
                $opportunity->applied_at         = $opportunity->pivot->created_at;

                // إخفاء كائن الـ pivot المعقد لتنظيف الرد
                $opportunity->makeHidden(['pivot']);

                return $opportunity;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب مهامك التطوعية بنجاح.',
                'data'    => $tasks
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Fetch My Tasks Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب المهام.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: رفع تقرير إتمام مهمة (ساعات العمل والصور)
     */
    public function submitReport(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // 1. التأكد أن الفرصة موجودة ونشطة
            $opportunity = VolunteerOpportunity::where('id', $id)->where('status', 'active')->first();

            if (!$opportunity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرصة غير موجودة أو لم تعد نشطة.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. التأكد أن المتطوع مسجل في هذه الفرصة وحالته "مقبول" (لا يمكنه رفع تقرير وهو قيد المراجعة)
            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'accepted') {
                return response()->json([
                    'status'  => false,
                    'message' => 'عذراً، يجب أن تكون مقبولاً في هذه الفرصة لتتمكن من رفع تقرير الإنجاز.'
                ], 403, [], JSON_UNESCAPED_UNICODE); // 403 Forbidden
            }

            // 3. التحقق من صحة بيانات التقرير المدخلة
            $validator = Validator::make($request->all(), [
                'hours'    => 'required|numeric|min:0.5|max:24',
                'summary'  => 'required|string|min:5|max:2000',
                'images'   => 'sometimes|array|max:5', // بحد أقصى 5 صور للتقرير الواحد
                'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // حجم الصورة بحد أقصى 5 ميجا
            ], [
                'hours.required'   => 'يرجى إدخال عدد الساعات التي تطوعت بها.',
                'hours.numeric'    => 'عدد الساعات يجب أن يكون رقماً.',
                'hours.max'        => 'عدد الساعات لا يمكن أن يتجاوز 24 ساعة في التقرير الواحد.',
                'summary.required' => 'يرجى كتابة ملخص سريع لما قمت بإنجازه.',
                'images.array'     => 'صيغة الصور المرفقة غير صحيحة.',
                'images.*.image'   => 'أحد الملفات المرفقة ليس صورة صالحة.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'يرجى مراجعة الأخطاء في النموذج.',
                    'errors'  => $validator->errors()
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            // 4. معالجة وحفظ الصور (إذا تم إرفاقها)
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('volunteer_reports/images', 'public');
                    $imagePaths[] = $path;
                }
            }

            // 5. حفظ التقرير في قاعدة البيانات
            $report = \App\Models\VolunteerReport::create([
                'volunteer_id'             => $volunteerId,
                'volunteer_opportunity_id' => $opportunity->id,
                'hours'                    => $request->hours,
                'summary'                  => $request->summary,
                'images'                   => !empty($imagePaths) ? $imagePaths : null,
            ]);

            // (اختياري): إذا كنت تريد تغيير حالة المتطوع إلى "مكتملة (attended)" بعد أول تقرير،
            // يمكنك فك التعليق عن هذا السطر:
            // $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'attended']);

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال تقرير إتمام المهمة بنجاح. شكراً لجهودك!',
                'data'    => $report
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Submit Report Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء رفع التقرير. يرجى المحاولة لاحقاً.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
