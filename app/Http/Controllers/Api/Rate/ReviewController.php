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
    public function index()
    {
        try {
            $reviews = Review::with('user:id,avatar') // نفترض أن صورة المستخدم موجودة في حقل avatar
                ->select('id', 'user_id', 'name', 'rating', 'message', 'created_at')
                ->where('is_approved', true)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($reviews->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد تقييمات معتمدة حتى الآن.',
                    'data'    => []
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $data = $reviews->map(function ($review) {
                $userImage = null;
                if ($review->user && $review->user->avatar) {
                    $userImage = str_starts_with($review->user->avatar, 'http')
                        ? $review->user->avatar
                        : asset('storage/' . $review->user->avatar);
                }

                return [
                    'id'      => $review->id,
                    'name'    => $review->name,
                    'rating'  => $review->rating,
                    'message' => $review->message,
                    'image'   => $userImage,
                    'date'    => $review->created_at
                        ? $review->created_at->locale('ar')->diffForHumans()
                        : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب التقييمات بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API Reviews Index Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب التقييمات.',
                'data'    => []
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function store(Request $request)
    {
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
            $userId = null;
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();
                if ($user instanceof \App\Models\User) {
                    $userId = $user->id;
                }
            }

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
