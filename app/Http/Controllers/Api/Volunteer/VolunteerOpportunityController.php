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
     * API: عرض جميع الفرص التطوعية المتاحة (مع حالة المتطوع الحالي) بدون Pagination
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
                ->get(); // 👈 تم استبدال paginate(9) بـ get()

            // 🧹 تهيئة البيانات لتكون سهلة جداً على مطور الواجهة (Frontend)
            // نستخدم transform مباشرة على المجموعة (Collection) المرجعة من get()
            $opportunities->transform(function ($opportunity) {
                // إعداد رابط لوجو المؤسسة (Full URL)
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                // 🎯 استخراج حالة المتطوع الحالي في هذه الفرصة
                $application = $opportunity->volunteers->first();

                // إضافة حقل جديد للـ JSON يخبر الواجهة بحالة التقديم (المنطق الجديد):
                // null (لم يتقدم)
                // applied (قدم بنفسه - في انتظار الموافقة)
                // invited (المؤسسة دعته - يجب أن يقبل أو يرفض)
                // accepted (مقبول)
                // rejected (مرفوض)
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
     * API: التقديم على فرصة تطوعية (مبادرة من المتطوع)
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

            // التأكد من أن المتطوع لم يتقدم مسبقاً (سواء قدم بنفسه أو تمت دعوته)
            $alreadyApplied = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->exists();

            if ($alreadyApplied) {
                return response()->json([
                    'status'  => false,
                    'message' => 'أنت مسجل بالفعل في هذه الفرصة أو لديك دعوة معلقة.'
                ], 400, [], JSON_UNESCAPED_UNICODE); // 400 Bad Request
            }

            // 🎯 تسجيل المتطوع في الفرصة بحالة (applied) لأنه هو من بادر بالتقديم
            $opportunity->volunteers()->attach($volunteerId, ['status' => 'applied']);

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
     * API: قبول دعوة التطوع (عندما ترسل المؤسسة دعوة للمتطوع)
     */
    public function acceptInvitation(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;
            $opportunity = VolunteerOpportunity::find($id);

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'invited') {
                return response()->json([
                    'status'  => false,
                    'message' => 'لا توجد دعوة معلقة لك لهذه الفرصة.'
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // تحويل الحالة من invited إلى accepted
            $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'accepted']);

            return response()->json([
                'status'  => true,
                'message' => 'تم قبول الدعوة بنجاح! يسعدنا انضمامك للفرصة.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Accept Invitation Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء قبول الدعوة.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: رفض دعوة التطوع
     */
    public function rejectInvitation(Request $request, $id): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;
            $opportunity = VolunteerOpportunity::find($id);

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'invited') {
                return response()->json([
                    'status'  => false,
                    'message' => 'لا توجد دعوة معلقة لك لهذه الفرصة.'
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // تحويل الحالة من invited إلى rejected
            $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'rejected']);

            return response()->json([
                'status'  => true,
                'message' => 'تم رفض الدعوة.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

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

            // 🎯 حذف تسجيل المتطوع من الفرصة كلياً (Detach)
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
