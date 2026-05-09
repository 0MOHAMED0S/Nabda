<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class VolunteerPasswordController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية
     */
    private function validationMessages()
    {
        return [
            'current_password.required' => 'يرجى إدخال كلمة المرور الحالية.',
            'new_password.required'     => 'يرجى إدخال كلمة المرور الجديدة.',
            'new_password.min'          => 'كلمة المرور الجديدة يجب ألا تقل عن 8 رموز للحفاظ على أمان حسابك.',
            'new_password.confirmed'    => 'تأكيد كلمة المرور غير متطابق.',
            'new_password.different'    => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن كلمة المرور الحالية.',
        ];
    }

    /**
     * API: تغيير كلمة المرور للمتطوع
     */
    public function update(Request $request): JsonResponse
    {
        $volunteer = $request->user();

        // 1. التحقق من المدخلات (Validation)
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed|different:current_password',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات الإدخال غير صالحة. يرجى مراجعة الأخطاء.',
                'errors'  => $validator->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            // 2. التحقق من صحة كلمة المرور الحالية
            if (!Hash::check($request->current_password, $volunteer->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'كلمة المرور الحالية التي أدخلتها غير صحيحة.',
                ], 400, [], JSON_UNESCAPED_UNICODE); // 400 Bad Request
            }

            // 3. تحديث كلمة المرور (مع التشفير Hash)
            $volunteer->update([
                'password' => Hash::make($request->new_password)
            ]);

            /* * خطوة أمنية إضافية (اختيارية):
             * يمكنك إلغاء جميع التوكنز القديمة وتسجيل الخروج من كل الأجهزة الأخرى
             * للإبقاء على الجلسة الحالية فقط. (أزل علامة التعليق إذا أردت تفعيلها)
             */
            // $volunteer->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم تغيير كلمة المرور بنجاح. يرجى استخدام الكلمة الجديدة في المرات القادمة.',
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Change Password Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء تغيير كلمة المرور. يرجى المحاولة لاحقاً.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
