<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationPasswordController extends Controller
{

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

    public function update(Request $request)
    {
        $foundation = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed|different:current_password',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات الإدخال غير صالحة. يرجى مراجعة الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            if (!Hash::check($request->current_password, $foundation->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'كلمة المرور الحالية التي أدخلتها غير صحيحة.',
                ], 400); // 400 Bad Request
            }

            $foundation->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم تغيير كلمة المرور بنجاح. يرجى استخدام الكلمة الجديدة في المرات القادمة.',
            ], 200);
        } catch (Exception $e) {
            Log::error("API Foundation Change Password Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء تغيير كلمة المرور. يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }
}
