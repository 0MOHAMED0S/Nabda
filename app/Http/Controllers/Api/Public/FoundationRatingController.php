<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\FoundationRating;
use App\Models\User; // أو Foundation حسب الموديل الخاص بالمؤسسات لديك
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationRatingController extends Controller
{
    /**
     * API: إضافة تقييم لمؤسسة (متاح للزوار والمستخدمين المسجلين)
     */
public function store(Request $request, $foundationId): JsonResponse
    {
        try {
            // 1. التأكد من وجود المؤسسة
            $foundation = \App\Models\Foundation::find($foundationId);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. التحقق من صحة البيانات بناءً على قيود الواجهة (الصورة)
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'rating'  => 'required|integer|min:1|max:5',
                'name'    => 'nullable|string|max:255',
                'message' => 'nullable|string|max:200', // بحد أقصى 200 حرف كما في التصميم
            ], [
                'rating.required' => 'يرجى تحديد التقييم بالنجوم.',
                'message.max'     => 'الرسالة يجب ألا تتجاوز 200 حرف.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'يرجى مراجعة البيانات المدخلة.',
                    'errors'  => $validator->errors()
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            // 3. التحقق مما إذا كان المستخدم مسجلاً للدخول أم زائر (Guest)
            $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;

            // 4. حفظ التقييم في قاعدة البيانات
            $rating = \App\Models\FoundationRating::create([
                'foundation_id' => $foundationId,
                'user_id'       => $userId,
                'rating'        => $request->rating,
                // إذا ترك حقل الاسم فارغاً، نعطيه القيمة الافتراضية "فاعل خير" كما هو مطلوب بالصورة
                'name'          => $request->filled('name') ? $request->name : 'فاعل خير',
                'message'       => $request->message,
            ]);

            // 🔔 5. إرسال إشعار للمؤسسة بالتقييم الجديد
            if ($foundation) {
                // رسم نجوم لجمالية الإشعار بناءً على التقييم
                $stars = str_repeat('⭐', $rating->rating);

                $foundation->notify(new \App\Notifications\GeneralNotification(
                    'تقييم جديد 🌟',
                    "تلقيت تقييماً جديداً بـ {$rating->rating} نجوم {$stars} من {$rating->name}.",
                    'info'
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال تقييمك بنجاح. شكراً لمساهمتك في تحسين جودة الخدمة!',
                'data'    => $rating
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Public Foundation Rating Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء إرسال التقييم.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
