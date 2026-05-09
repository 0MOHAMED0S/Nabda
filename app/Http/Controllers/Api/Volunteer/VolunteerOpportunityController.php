<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class VolunteerOpportunityController extends Controller
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
}
