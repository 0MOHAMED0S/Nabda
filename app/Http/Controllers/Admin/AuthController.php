<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin()
    {
        // إذا كان المدير مسجلاً للدخول بالفعل، قم بتحويله للوحة التحكم
        if (Auth::guard('admin')->check()) {
            return redirect()->intended('/admin/dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * معالجة طلب تسجيل الدخول
     */
    public function login(Request $request)
    {
        // 1. التحقق من صحة البيانات المدخلة
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'البريد الإلكتروني مطلوب.',
            'email.email'       => 'يرجى إدخال صيغة بريد إلكتروني صحيحة.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        try {
            $credentials = $request->only('email', 'password');
            $remember = $request->has('remember'); // التحقق من خيار "تذكرني"

            // 2. محاولة تسجيل الدخول باستخدام الجارد المخصص للإدارة
            if (Auth::guard('admin')->attempt($credentials, $remember)) {

                // 3. حماية أمنية: تجديد الجلسة لمنع هجمات (Session Fixation)
                $request->session()->regenerate();

                // 4. التحويل إلى لوحة التحكم مع رسالة ترحيب
                return redirect()->intended('/admin/dashboard')
                    ->with('success', 'مرحباً بك مجدداً في لوحة التحكم.');
            }

            // في حال كانت البيانات خاطئة
            return back()->withErrors([
                'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ])->onlyInput('email', 'remember'); // إعادة البريد الإلكتروني المدخل للحقل لتسهيل التعديل

        } catch (Exception $e) {
            // تسجيل الخطأ في السيرفر
            Log::error('Admin Login Error: ' . $e->getMessage());

            return back()->with('error', 'حدث خطأ فني أثناء محاولة تسجيل الدخول، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        try {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/admin/login')->with('success', 'تم تسجيل الخروج بنجاح، نراك لاحقاً.');
        } catch (Exception $e) {
            Log::error('Admin Logout Error: ' . $e->getMessage());
            return redirect('/admin/dashboard')->with('error', 'حدث خطأ أثناء محاولة تسجيل الخروج.');
        }
    }


    public function profile()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.auth.profile', compact('admin'));
    }

    /**
     * تحديث البيانات الأساسية (الاسم والبريد)
     */
    public function updateProfile(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
        ], [
            'name.required'  => 'يرجى إدخال الاسم.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique'   => 'هذا البريد الإلكتروني مستخدم بالفعل.',
        ]);

        try {
            $admin->update($request->only('name', 'email'));
            return back()->with('success', 'تم تحديث بيانات الملف الشخصي بنجاح.');
        } catch (Exception $e) {
            Log::error('Update Profile Error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث البيانات.');
        }
    }

    /**
     * تحديث كلمة المرور
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password:admin',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required'         => 'يرجى إدخال كلمة المرور الحالية.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required'                 => 'يرجى إدخال كلمة المرور الجديدة.',
            'password.confirmed'                => 'تأكيد كلمة المرور غير متطابق.',
            'password.min'                      => 'كلمة المرور الجديدة يجب ألا تقل عن 8 رموز.',
        ]);

        try {
            $admin = Auth::guard('admin')->user();
            $admin->update([
                'password' => Hash::make($request->password)
            ]);

            return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
        } catch (Exception $e) {
            Log::error('Update Password Error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تغيير كلمة المرور.');
        }
    }
}
