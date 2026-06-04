<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\FoundationRating;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Log;

class FoundationRatingController extends Controller
{
    /**
     * API: إضافة تقييم لمؤسسة (متاح للزوار والمستخدمين المسجلين)
     */
    public function store(Request $request, $foundationId): JsonResponse
    {
        try {
            // 1. التأكد من وجود المؤسسة
            $foundation = Foundation::find($foundationId);

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
            $rating = FoundationRating::create([
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
        } catch (Exception $e) {
            Log::error("API Public Foundation Rating Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء إرسال التقييم.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: جلب جميع التقييمات الخاصة بالمؤسسة
     */
public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $foundation = $request->user();

            // 🛡️ حماية الصلاحيات: التأكد أن المستخدم الحالي هو "مؤسسة"
            if (!$foundation instanceof \App\Models\Foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة. هذا الحساب لا يملك صلاحيات مؤسسة.'
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }

            // جلب التقييمات ترتيباً من الأحدث للأقدم
            // 🎯 أضفنا with('user') لتسريع جلب البيانات وعدم إرهاق قاعدة البيانات
            $ratings = \App\Models\FoundationRating::with('user')
                ->where('foundation_id', $foundation->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($rating) {

                    // 🎯 جلب المستخدم من العلاقة مباشرة
                    $user = $rating->user;

                    // 🎯 معالجة صورة المستخدم أو وضع صورة افتراضية
                    $avatarUrl = null;

                    if ($user && $user->avatar) {
                        $avatarUrl = filter_var($user->avatar, FILTER_VALIDATE_URL)
                            ? $user->avatar
                            : asset('storage/' . $user->avatar);
                    } else {
                        // يمكنك استخدام رابط لصورة افتراضية (Placeholder) إذا أردت
                        // $avatarUrl = asset('assets/images/default-avatar.png');
                    }

                    // 🎯 تنسيق البيانات لتكون جاهزة وعملية للفرونت إند
                    return [
                        'id'          => $rating->id,
                        'name'        => $rating->name ?? 'فاعل خير',
                        'avatar_url'  => $avatarUrl,
                        'rating'      => $rating->rating,
                        'message'     => $rating->message,
                        'is_approved' => (bool) $rating->is_approved,
                        'date'        => $rating->created_at->format('Y-m-d'),
                        'time_ago'    => $rating->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب قائمة التقييمات بنجاح.',
                'data'    => $ratings
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Get Ratings Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب التقييمات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: تحديث حالة التقييم (إظهار/إخفاء)
     */
    public function updateStatus(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $foundation = $request->user();

            // 🛡️ حماية الصلاحيات
            if (!$foundation instanceof \App\Models\Foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة.'
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }

            // التأكد أن التقييم موجود ويخص هذه المؤسسة تحديداً
            $rating = \App\Models\FoundationRating::where('id', $id)
                ->where('foundation_id', $foundation->id)
                ->first();

            if (!$rating) {
                return response()->json([
                    'status'  => false,
                    'message' => 'التقييم غير موجود أو لا تملك صلاحية تعديله.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تحديث الحالة (Toggle): نعكس الحالة الحالية مباشرة
            $newState = !$rating->is_approved;

            $rating->update([
                'is_approved' => $newState
            ]);

            // صياغة رسالة نجاح ديناميكية بناءً على الحالة الجديدة
            $statusMsg = $newState ? 'الموافقة على التقييم وإظهاره' : 'إخفاء التقييم';

            return response()->json([
                'status'  => true,
                'message' => "تم {$statusMsg} بنجاح.",
                'data'    => [
                    'id'          => $rating->id,
                    'is_approved' => (bool) $rating->is_approved
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Update Rating Status Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تحديث حالة التقييم.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
