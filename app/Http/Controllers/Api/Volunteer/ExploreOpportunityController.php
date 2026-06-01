<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class ExploreOpportunityController extends Controller
{
    /**
     * API: عرض جميع الفرص التطوعية المتاحة (مع حالة المتطوع الحالي)
     */
/**
     * API: عرض جميع الفرص التطوعية المتاحة (مع حالة المتطوع الحالي) بدون Pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // جلب الفرص النشطة فقط (التي يمكن التقديم عليها)
            $opportunities = VolunteerOpportunity::where('status', 'active')
                ->with(['foundation:id,name,logo'])
                ->with(['volunteers' => function ($query) use ($volunteerId) {
                    $query->where('volunteer_id', $volunteerId)
                    ->select('volunteers.id');
                }])
                ->orderBy('date', 'asc')
                ->get(); // 👈 تم استبدال paginate(9) بـ get()

            // 🧹 استخدام transform مباشرة على الـ Collection
            $opportunities->transform(function ($opportunity) {
                // إعداد رابط لوجو المؤسسة (Full URL)
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                // 🎯 استخراج حالة المتطوع الحالي (applied, invited, accepted, rejected, attended)
                $application = $opportunity->volunteers->first();
                $opportunity->user_application_status = $application ? $application->pivot->status : null;

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
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب الفرص.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: عرض تفاصيل فرصة تطوعية واحدة (مع الروابط الكاملة وحالة المتطوع)
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            $opportunity = VolunteerOpportunity::with(['foundation:id,name,logo'])
                ->with(['volunteers' => function ($query) use ($volunteerId) {
                    $query->where('volunteer_id', $volunteerId)
                          ->select('volunteers.id');
                }])
                ->where('id', $id)
                ->first();

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 🧹 تهيئة رابط المؤسسة (Full URL)
            if ($opportunity->foundation) {
                $opportunity->foundation->logo_url = $opportunity->foundation->logo
                    ? asset('storage/' . $opportunity->foundation->logo)
                    : null;
                $opportunity->foundation->makeHidden(['logo']);
            }

            // 🎯 استخراج حالة المتطوع الحالي
            $application = $opportunity->volunteers->first();
            $opportunity->user_application_status = $application ? $application->pivot->status : null;

            unset($opportunity->volunteers);

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الفرصة بنجاح.',
                'data'    => $opportunity
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Show Opportunity Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب تفاصيل الفرصة.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: التقديم على فرصة تطوعية (مبادرة شخصية)
     */
public function apply(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            $opportunity = VolunteerOpportunity::where('id', $id)->where('status', 'active')->first();

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'هذه الفرصة غير موجودة أو لم تعد متاحة للتقديم.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $alreadyApplied = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->exists();

            if ($alreadyApplied) {
                return response()->json(['status' => false, 'message' => 'لقد قمت بالتقديم على هذه الفرصة أو لديك دعوة مسبقاً.'], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تم التعديل إلى 'applied' لأن المتطوع بادر بالتقديم
            $opportunity->volunteers()->attach($volunteerId, ['status' => 'applied']);

            // 🔔 إضافة الإشعار: إرسال تنبيه للمؤسسة صاحبة الفرصة
            if ($opportunity->foundation) {
                $volunteerName = $request->user()->name ?? 'متطوع';
                $opportunity->foundation->notify(new \App\Notifications\GeneralNotification(
                    'طلب تقديم جديد على فرصة تطوعية 🙋‍♂️',
                    "قام المتطوع {$volunteerName} بتقديم طلب للانضمام إلى الفرصة: '{$opportunity->title}'. يرجى مراجعة الطلب.",
                    'info'
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال طلب التقديم بنجاح. أنت الآن في انتظار موافقة المؤسسة.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Apply Opportunity Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء التقديم على الفرصة.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 🎯 جديد API: قبول دعوة التطوع (عندما تدعو المؤسسة المتطوع)
     */
public function acceptInvitation(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;
            $opportunity = VolunteerOpportunity::find($id);

            if (!$opportunity) return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'invited') {
                return response()->json(['status' => false, 'message' => 'لا توجد دعوة معلقة لك لهذه الفرصة.'], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'accepted']);

            // 🔔 إضافة الإشعار: إرسال تنبيه للمؤسسة بقبول المتطوع للدعوة
            if ($opportunity->foundation) {
                $volunteerName = $request->user()->name ?? 'متطوع';
                $opportunity->foundation->notify(new \App\Notifications\GeneralNotification(
                    'قبول دعوة تطوع ✅',
                    "يسعدنا إخبارك أن المتطوع {$volunteerName} قد وافق على دعوتك وانضم إلى الفرصة: '{$opportunity->title}'.",
                    'success'
                ));
            }

            return response()->json(['status' => true, 'message' => 'تم قبول الدعوة بنجاح! يسعدنا انضمامك.'], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Accept Invitation Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء قبول الدعوة.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 🎯 جديد API: رفض دعوة التطوع
     */
public function rejectInvitation(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;
            $opportunity = VolunteerOpportunity::find($id);

            if (!$opportunity) return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'invited') {
                return response()->json(['status' => false, 'message' => 'لا توجد دعوة معلقة لك لهذه الفرصة.'], 400, [], JSON_UNESCAPED_UNICODE);
            }

            $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'rejected']);

            // 🔔 إضافة الإشعار: إرسال تنبيه للمؤسسة باعتذار المتطوع عن الدعوة
            if ($opportunity->foundation) {
                $volunteerName = $request->user()->name ?? 'متطوع';
                $opportunity->foundation->notify(new \App\Notifications\GeneralNotification(
                    'اعتذار عن دعوة تطوع ❌',
                    "نأسف لإخبارك أن المتطوع {$volunteerName} قد اعتذر عن قبول الدعوة للانضمام إلى الفرصة: '{$opportunity->title}'.",
                    'warning'
                ));
            }

            return response()->json(['status' => true, 'message' => 'تم رفض الدعوة.'], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Reject Invitation Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء رفض الدعوة.'], 500, [], JSON_UNESCAPED_UNICODE);
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

            if (!$opportunity) return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application) return response()->json(['status' => false, 'message' => 'أنت غير مسجل في هذه الفرصة.'], 400, [], JSON_UNESCAPED_UNICODE);

            $opportunity->volunteers()->detach($volunteerId);

            // 🔔 إضافة الإشعار: إرسال تنبيه للمؤسسة بانسحاب المتطوع
            if ($opportunity->foundation) {
                $volunteerName = $request->user()->name ?? 'متطوع';
                $opportunity->foundation->notify(new \App\Notifications\GeneralNotification(
                    'انسحاب متطوع 🛑',
                    "قام المتطوع {$volunteerName} بإلغاء تقديمه والانسحاب من الفرصة: '{$opportunity->title}'.",
                    'warning'
                ));
            }

            return response()->json(['status' => true, 'message' => 'تم إلغاء التقديم والانسحاب بنجاح.'], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Cancel Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء إلغاء التقديم.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: عرض المهام التطوعية الخاصة بالمتطوع (سجل التقديمات)
     */
    public function myTasks(Request $request): JsonResponse
    {
        try {
            $volunteer = $request->user();
            $statusFilter = $request->query('status');

            $query = $volunteer->opportunities()
                ->with(['foundation:id,name,logo'])
                ->orderBy('opportunity_volunteer.created_at', 'desc');

            // 🎯 تم تحديث مصفوفة الفلاتر لتشمل الحالات الجديدة
            if ($statusFilter && in_array($statusFilter, ['applied', 'invited', 'accepted', 'attended', 'rejected'])) {
                $query->wherePivot('status', $statusFilter);
            }

            $tasks = $query->paginate(10);

            $tasks->getCollection()->transform(function ($opportunity) {
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                $opportunity->application_status = $opportunity->pivot->status;
                $opportunity->applied_at         = $opportunity->pivot->created_at;
                $opportunity->makeHidden(['pivot']);

                return $opportunity;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب مهامك التطوعية بنجاح.',
                'data'    => $tasks
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer My Tasks Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء جلب المهام.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: رفع تقرير إتمام مهمة (ساعات العمل والصور)
     */
public function submitReport(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;
            $opportunity = VolunteerOpportunity::where('id', $id)->where('status', 'active')->first();

            if (!$opportunity) return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة أو نشطة.'], 404, [], JSON_UNESCAPED_UNICODE);

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            // يُسمح برفع التقرير فقط لمن تم قبوله أو حضر بالفعل
            if (!$application || !in_array($application->pivot->status, ['accepted', 'attended'])) {
                return response()->json(['status' => false, 'message' => 'يجب أن تكون مقبولاً لتتمكن من رفع التقرير.'], 403, [], JSON_UNESCAPED_UNICODE);
            }

            $validator = Validator::make($request->all(), [
                'hours'    => 'required|numeric|min:0.5|max:24',
                'summary'  => 'required|string|min:5|max:2000',
                'images'   => 'sometimes|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'أخطاء في الإدخال.', 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = $image->store('volunteer_reports/images', 'public');
                }
            }

            $report = \App\Models\VolunteerReport::create([
                'volunteer_id'             => $volunteerId,
                'volunteer_opportunity_id' => $opportunity->id,
                'hours'                    => $request->hours,
                'summary'                  => $request->summary,
                'images'                   => !empty($imagePaths) ? $imagePaths : null,
            ]);

            // 🎯 تحويل مسارات صور التقرير لروابط كاملة (Full URLs) عند إرجاعها
            $reportData = $report->toArray();
            if (!empty($reportData['images'])) {
                $reportData['images'] = array_map(fn($img) => asset('storage/' . $img), $reportData['images']);
            }

            // 🔔 إضافة الإشعار: إرسال تنبيه للمؤسسة باستلام تقرير جديد من المتطوع
            if ($opportunity->foundation) {
                $volunteerName = $request->user()->name ?? 'متطوع';
                $opportunity->foundation->notify(new \App\Notifications\GeneralNotification(
                    'تقرير تطوع جديد 📝',
                    "قام المتطوع {$volunteerName} برفع تقرير جديد لفرصة التطوع: '{$opportunity->title}'. يرجى مراجعته.",
                    'success'
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال تقرير إتمام المهمة بنجاح.',
                'data'    => $reportData
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Submit Report Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء الرفع.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
