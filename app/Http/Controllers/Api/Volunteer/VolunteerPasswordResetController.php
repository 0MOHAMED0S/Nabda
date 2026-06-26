<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use App\Models\PasswordResetCode;
use App\Mail\VolunteerPasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class VolunteerPasswordResetController extends Controller
{
    /**
     * 1. إرسال رمز الـ OTP إلى البريد الإلكتروني الخاص بالمتطوع
     */
    public function sendCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:volunteers,email', // 🎯 التحقق من جدول volunteers
        ], [
            'email.required' => 'يرجى إدخال البريد الإلكتروني.',
            'email.exists'   => 'هذا البريد الإلكتروني غير مسجل لدينا في سجلات المتطوعين.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'بيانات غير صالحة.', 'errors' => $validator->errors()], 422);
        }

        try {
            $volunteer = Volunteer::where('email', $request->email)->first();

            // فحص إضافي: التأكد من أن حساب المتطوع ليس موقوفاً نهائياً أو مرفوضاً
            if ($volunteer->status === 'rejected') {
                return response()->json(['status' => false, 'message' => 'نعتذر، هذا الحساب موقوف أو تم رفضه ولا يمكن استعادة كلمة المرور.'], 403);
            }

            // توليد رمز عشوائي من 6 أرقام
            $code = sprintf("%06d", mt_rand(1, 999999));

            // حذف أي رموز سابقة لنفس الإيميل
            PasswordResetCode::where('email', $volunteer->email)->delete();

            // حفظ الرمز الجديد
            PasswordResetCode::create([
                'email'      => $volunteer->email,
                'code'       => $code,
                'created_at' => Carbon::now()
            ]);

            // إرسال الإيميل
            Mail::to($volunteer->email)->send(new VolunteerPasswordResetMail($code, $volunteer->name));

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني بنجاح.'
            ], 200);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Volunteer Send Reset Code Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء محاولة إرسال الرمز. يرجى المحاولة لاحقاً.'], 500);
        }
    }

    /**
     * 2. التحقق من صحة الرمز المدخل
     */
    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:volunteers,email',
            'code'  => 'required|string|min:6|max:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'يرجى إدخال البريد الإلكتروني والرمز المكون من 6 أرقام.', 'errors' => $validator->errors()], 422);
        }

        $resetRecord = PasswordResetCode::where('email', $request->email)->first();

        // المطابقة المباشرة للنص
        if (!$resetRecord || $request->code !== $resetRecord->code) {
            return response()->json(['status' => false, 'message' => 'الرمز المدخل غير صحيح.'], 400);
        }

        // التحقق من صلاحية الرمز (15 دقيقة)
        if (Carbon::now()->diffInMinutes($resetRecord->created_at) > 15) {
            $resetRecord->delete(); // مسح الرمز المنتهي
            return response()->json(['status' => false, 'message' => 'صلاحية الرمز قد انتهت. يرجى طلب رمز جديد.'], 400);
        }

        return response()->json([
            'status'  => true,
            'message' => 'الرمز صحيح. يمكنك الآن تعيين كلمة المرور الجديدة.'
        ], 200);
    }

    /**
     * 3. تعيين كلمة المرور الجديدة للمتطوع
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:volunteers,email',
            'code'     => 'required|string|min:6|max:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required'  => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.min'       => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'بيانات غير صالحة.', 'errors' => $validator->errors()], 422);
        }

        $resetRecord = PasswordResetCode::where('email', $request->email)->first();

        // فحص الأمان مرة أخيرة قبل تغيير الباسورد
        if (!$resetRecord || $request->code !== $resetRecord->code) {
            return response()->json(['status' => false, 'message' => 'الرمز المدخل غير صحيح أو انتهت صلاحيته.'], 400);
        }

        try {
            $volunteer = Volunteer::where('email', $request->email)->first();

            // تغيير كلمة المرور للمتطوع (مشفرة)
            $volunteer->update([
                'password' => Hash::make($request->password)
            ]);

            // حذف الرمز لمنع استخدامه مرة أخرى
            $resetRecord->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.'
            ], 200);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Volunteer Password Reset Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء تغيير كلمة المرور.'], 500);
        }
    }
}
