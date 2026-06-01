<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class OpportunityApplicationController extends Controller
{
    /**
     * API: عرض طلبات التطوع والمتطوعين المسجلين لفرصة محددة (تطابق الصورة تماماً)
     */
    public function index(Request $request, $opportunityId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // جلب الفرصة والتأكد أنها تخص المؤسسة الحالية
            $opportunity = VolunteerOpportunity::where('id', $opportunityId)
                ->where('foundation_id', $foundationId)
                ->with(['volunteers:id,name,email,phone,avatar'])
                ->first();

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة أو لا تملك صلاحية الوصول.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تهيئة بيانات المتطوعين وفصلهم إلى قائمتين (طلبات جديدة / مسجلين)
            $volunteers = $opportunity->volunteers->map(function ($volunteer) {
                // تحويل مسار الصورة لرابط كامل
                $volunteer->avatar_url = !empty($volunteer->avatar) && !str_starts_with($volunteer->avatar, 'http')
                    ? asset('storage/' . $volunteer->avatar)
                    : $volunteer->avatar;

                // استخراج الحالة وتاريخ الطلب
                $volunteer->application_status = $volunteer->pivot->status;
                $volunteer->applied_at         = $volunteer->pivot->created_at->format('Y-m-d');

                $volunteer->makeHidden(['avatar', 'pivot']);
                return $volunteer;
            });

            // 1. الطلبات الجديدة (applied) التي تنتظر الموافقة
            $newRequests = $volunteers->where('application_status', 'applied')->values();

            // 2. المتطوعون المسجلون (المقبولون أو الذين حضروا بالفعل)
            $registered = $volunteers->whereIn('application_status', ['accepted', 'attended'])->values();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب طلبات التطوع بنجاح.',
                'data'    => [
                    'opportunity_info' => [
                        'required_volunteers' => $opportunity->required_volunteers,
                        'registered_count'    => $registered->count(),
                        'is_full'             => $registered->count() >= $opportunity->required_volunteers,
                    ],
                    'new_requests'          => $newRequests, // لعمود "طلبات التطوع الجديدة"
                    'registered_volunteers' => $registered,  // لعمود "المتطوعون المسجلون"
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Fetch Applications Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب الطلبات.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

/**
     * API: قبول طلب متطوع
     */
    public function accept(Request $request, $opportunityId, $volunteerId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            $opportunity = VolunteerOpportunity::where('id', $opportunityId)->where('foundation_id', $foundationId)->first();

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 🛑 التحقق الذكي: هل اكتمل العدد المطلوب للفرصة؟
            $acceptedCount = $opportunity->volunteers()->whereIn('opportunity_volunteer.status', ['accepted', 'attended'])->count();

            if ($acceptedCount >= $opportunity->required_volunteers) {
                return response()->json([
                    'status'  => false,
                    'message' => 'عذراً، تم اكتمال العدد المطلوب لهذه الفرصة ولا يمكن قبول المزيد.'
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // التأكد أن المتطوع قدم بالفعل وحالته 'applied'
            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'applied') {
                return response()->json(['status' => false, 'message' => 'طلب التقديم غير صالح أو تم الرد عليه مسبقاً.'], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تحديث الحالة إلى "مقبول"
            $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'accepted']);

            // 🔔 إضافة الإشعار: إرسال تنبيه للمتطوع بقبوله في الفرصة
            if ($application) {
                $application->notify(new \App\Notifications\GeneralNotification(
                    'تم قبول طلب التطوع 🎉',
                    "تهانينا! تم قبول طلبك للانضمام إلى الفرصة التطوعية: '{$opportunity->title}'.",
                    'success'
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم قبول المتطوع بنجاح وإضافته لقائمة المسجلين.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Accept Application Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء قبول الطلب.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

/**
     * API: رفض طلب متطوع
     */
    public function reject(Request $request, $opportunityId, $volunteerId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            $opportunity = VolunteerOpportunity::where('id', $opportunityId)->where('foundation_id', $foundationId)->first();

            if (!$opportunity) return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);

            $application = $opportunity->volunteers()->where('volunteer_id', $volunteerId)->first();

            if (!$application || $application->pivot->status !== 'applied') {
                return response()->json(['status' => false, 'message' => 'طلب التقديم غير صالح أو تم الرد عليه مسبقاً.'], 400, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تحديث الحالة إلى "مرفوض"
            $opportunity->volunteers()->updateExistingPivot($volunteerId, ['status' => 'rejected']);

            // 🔔 إضافة الإشعار: إرسال تنبيه للمتطوع بالاعتذار عن قبوله
            if ($application) {
                $application->notify(new \App\Notifications\GeneralNotification(
                    'حالة طلب التطوع 📬',
                    "نعتذر منك، لم يتم قبول طلبك للانضمام إلى الفرصة التطوعية: '{$opportunity->title}'. نتمنى لك التوفيق في فرص تطوعية أخرى.",
                    'info'
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم رفض طلب التطوع.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Reject Application Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء رفض الطلب.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
