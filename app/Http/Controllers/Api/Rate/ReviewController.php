<?php

namespace App\Http\Controllers\Api\Rate;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class ReviewController extends Controller
{
    /**
     * API: جلب جميع التقييمات المعتمدة لعرضها في الموقع
     * (Public Endpoint)
     */
    public function index()
    {
        try {
            // 1. تحسين الأداء: جلب الحقول المطلوبة فقط للواجهة الأمامية
            $reviews = Review::select('id', 'name', 'rating', 'message', 'created_at')
                             ->where('is_approved', true)
                             ->orderBy('created_at', 'desc')
                             ->get();

            // 2. تطبيق معايير RESTful: الاستجابة بـ 200 مع مصفوفة فارغة
            if ($reviews->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد تقييمات معتمدة حتى الآن.',
                    'data'    => []
                ], 200);
            }

            // 3. تنسيق التاريخ ليظهر بشكل احترافي في الواجهة (مثال: منذ يومين، أو تاريخ عادي)
            $data = $reviews->map(function ($review) {
                return [
                    'id'      => $review->id,
                    'name'    => $review->name,
                    'rating'  => $review->rating,
                    'message' => $review->message,
                    // diffForHumans تعطي شكل جميل مثل "منذ 3 أيام" بدلاً من التاريخ الجاف
                    'date'    => $review->created_at ? $review->created_at->diffForHumans() : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب التقييمات بنجاح.',
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            // تسجيل الخطأ أمنياً
            Log::error('API Reviews Index Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب التقييمات.',
                'data'    => [] // حماية للـ Frontend
            ], 500);
        }
    }

    /**
     * API: استقبال تقييم جديد من المستخدم أو الزائر
     */
    public function store(Request $request)
    {
        // 1. تعزيز قواعد التحقق (Hard Validation)
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|min:2|max:255',
            'rating'  => 'required|integer|min:1|max:5',
            'message' => 'required|string|min:5|max:1000',
        ], [
            'name.required'    => 'يرجى إدخال اسمك.',
            'name.min'         => 'الاسم المدخل قصير جداً.',
            'rating.required'  => 'التقييم بالنجوم مطلوب.',
            'rating.integer'   => 'قيمة التقييم غير صالحة.',
            'rating.min'       => 'الحد الأدنى للتقييم هو نجمة واحدة.',
            'rating.max'       => 'الحد الأقصى للتقييم هو 5 نجوم.',
            'message.required' => 'يرجى كتابة رأيك في الخدمة.',
            'message.min'      => 'رسالة التقييم قصيرة جداً.',
            'message.max'      => 'رسالتك تتجاوز الحد الأقصى المسموح به (1000 حرف).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة البيانات المدخلة.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. الحماية الجوهرية للـ Token (لمنع خطأ Foreign Key Constraint)
            $userId = null;
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();
                // نتأكد 100% أن صاحب التوكن هو مستخدم عادي وليس أدمن أو مؤسسة تختبر النظام
                if ($user instanceof \App\Models\User) {
                    $userId = $user->id;
                }
            }

            // 3. حفظ البيانات
            $review = Review::create([
                'user_id'     => $userId,
                'name'        => $request->name,
                'rating'      => $request->rating,
                'message'     => $request->message,
                'is_approved' => false, // يحتاج موافقة الإدارة أولاً
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'شكراً لتقييمك! تم استلام رسالتك بنجاح وهي قيد المراجعة الآن.',
                'data'    => $review
            ], 201);

        } catch (Exception $e) {
            Log::error('API Reviews Store Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء إرسال التقييم. يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }
}
