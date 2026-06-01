<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\VolunteerReport;
use App\Models\VolunteerOpportunity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class OpportunityReportEvaluationController extends Controller
{
    /**
     * API: جلب جميع التقارير المرفوعة لفرصة تطوعية محددة
     */
    public function index(Request $request, $opportunityId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // التأكد أن الفرصة تخص هذه المؤسسة
            $opportunity = VolunteerOpportunity::where('id', $opportunityId)
                ->where('foundation_id', $foundationId)
                ->first();

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة أو لا تملك صلاحية الوصول.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // جلب التقارير مع بيانات المتطوع المرتبطة بها
            $reports = VolunteerReport::where('volunteer_opportunity_id', $opportunityId)
                ->with(['volunteer:id,name,email,phone,avatar'])
                ->orderBy('created_at', 'desc')
                ->get();

            // 🎯 تحويل الروابط إلى (Full URLs) للصور الرمزية وصور التقارير
            $reports->transform(function ($report) {
                // 1. صورة المتطوع
                if ($report->volunteer) {
                    $report->volunteer->avatar_url = !empty($report->volunteer->avatar) && !str_starts_with($report->volunteer->avatar, 'http')
                        ? asset('storage/' . $report->volunteer->avatar)
                        : $report->volunteer->avatar;
                    $report->volunteer->makeHidden(['avatar']);
                }

                // 2. صور التقرير (مصفوفة الصور)
                if (is_array($report->images)) {
                    $report->images = array_map(function ($img) {
                        return !str_starts_with($img, 'http') ? asset('storage/' . $img) : $img;
                    }, $report->images);
                }

                return $report;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تقارير المتطوعين بنجاح.',
                'data'    => $reports
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Fetch Reports Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب التقارير.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

/**
     * API: تقييم التقرير (الموافقة/الرفض، تعديل الساعات، التقييم، وإرسال رسالة)
     */
    public function evaluate(Request $request, $reportId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // جلب التقرير والتأكد من أنه يخص فرصة تابعة لهذه المؤسسة
            $report = VolunteerReport::where('id', $reportId)
                ->whereHas('opportunity', function ($query) use ($foundationId) {
                    $query->where('foundation_id', $foundationId);
                })->first();

            if (!$report) {
                return response()->json(['status' => false, 'message' => 'التقرير غير موجود أو لا تملك صلاحية الوصول إليه.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // التحقق من المدخلات بناءً على الصورة (الموافقة/الرفض، الساعات المستحقة، التقييم، الرسالة)
            $validator = Validator::make($request->all(), [
                'status'  => 'required|in:approved,rejected', // موافقة أو رفض
                'hours'   => 'required|numeric|min:0|max:24', // الساعات المستحقة (يمكن للمؤسسة تعديلها)
                'rating'  => 'nullable|integer|min:1|max:5', // تقييم المتطوع
                'message' => 'nullable|string|max:1000', // رسالة للمتطوع
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تحديث بيانات التقرير
            $report->update([
                'status'           => $request->status,
                'hours'            => $request->hours,
                'rating'           => $request->rating,
                'feedback_message' => $request->message,
            ]);

            // 💡 خطوة إضافية ذكية: إذا تمت الموافقة على التقرير، نجعل حالة المتطوع "حضر" (attended)
            if ($request->status === 'approved') {
                $report->opportunity->volunteers()->updateExistingPivot($report->volunteer_id, ['status' => 'attended']);
            }

            // 🔔 إضافة الإشعار: إرسال تنبيه للمتطوع بنتيجة تقييم تقريره
            // (نبحث في موديل المستخدمين أو المتطوعين حسب هيكلة نظامك)
            $volunteerToNotify = \App\Models\User::find($report->volunteer_id) ?? \App\Models\Volunteer::find($report->volunteer_id);
            if ($volunteerToNotify) {
                $statusAr = $request->status === 'approved' ? 'قبول' : 'رفض';
                $icon = $request->status === 'approved' ? 'success' : 'warning';
                $emoji = $request->status === 'approved' ? '✅' : '❌';

                $volunteerToNotify->notify(new \App\Notifications\GeneralNotification(
                    "تقييم تقرير التطوع {$emoji}",
                    "تم {$statusAr} تقريرك الخاص بفرصة التطوع: '{$report->opportunity->title}'.",
                    $icon
                ));
            }

            // تحويل الصور لروابط كاملة قبل إرجاع الرد
            $reportData = $report->toArray();
            if (is_array($reportData['images'])) {
                $reportData['images'] = array_map(fn($img) => asset('storage/' . $img), $reportData['images']);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم تقييم تقرير المتطوع بنجاح.',
                'data'    => $reportData
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Evaluate Report Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء التقييم.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
