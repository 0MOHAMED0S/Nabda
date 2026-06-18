<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    /**
     * عرض قائمة جميع المستخدمين مع الإحصائيات (التبرعات، الحالات، النقاط)
     */
public function index(Request $request)
    {
        try {
            // 🎯 جلب المستخدمين مع حساب عدد التبرعات المكتملة، وعدد الحالات الفريدة المدعومة
            $users = User::withCount([
                'donations as completed_donations_count' => function ($query) {
                    $query->where('status', 'completed');
                },
                'donations as helped_cases_count' => function ($query) {
                    // نجلب عدد الـ case_id الفريدة (التي لم تتكرر)
                    $query->where('status', 'completed')
                          ->whereNotNull('case_id')
                          ->select(DB::raw('count(distinct case_id)'));
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString(); // 🎯 إضافة هذا السطر تضمن عمل الترقيم بشكل سليم حتى لو كان هناك بحث أو فلاتر في الرابط

            $stats = [
                'total' => User::count(),
            ];

            return view('admin.users.index', compact('users', 'stats'));
        } catch (Exception $e) {
            Log::error("Admin User Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات المستخدمين.');
        }
    }

    /**
     * إضافة مستخدم جديد
     */
    public function store(Request $request)
    {
        try {
            // 1. قواعد التحقق مطابقة للـ API
            $request->validate([
                'title'    => 'nullable|string|max:100',
                'name'     => 'required|string|min:2|max:255',
                'email'    => 'required|string|email|max:255|unique:users,email',
                'phone'    => 'nullable|string|max:20|unique:users,phone',
                'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'name.required'      => 'يرجى إدخال الاسم الكامل.',
                'email.required'     => 'البريد الإلكتروني مطلوب.',
                'email.unique'       => 'هذا البريد الإلكتروني مسجل مسبقاً.',
                'phone.unique'       => 'رقم الهاتف مسجل مسبقاً.',
                'password.required'  => 'كلمة المرور مطلوبة.',
                'password.min'       => 'كلمة المرور يجب أن تتكون من 8 أحرف على الأقل.',
                'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
                'avatar.image'       => 'يجب أن يكون الملف المرفق صورة صالحة.',
                'avatar.max'         => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with('form_type', 'create');
        }

        $avatarPath = null;

        try {
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('users/avatars', 'public');
            }

            User::create([
                'title'    => $request->title,
                'name'     => trim($request->name),
                'email'    => strtolower(trim($request->email)),
                'phone'    => $request->phone ? trim($request->phone) : null,
                'password' => Hash::make($request->password),
                'avatar'   => $avatarPath,
            ]);

            return back()->with('success', 'تم إنشاء حساب المستخدم بنجاح.');
        } catch (Exception $e) {
            if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }
            Log::error("Admin User Create Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء إضافة المستخدم.');
        }
    }

    /**
     * تحديث بيانات المستخدم
     */
    public function update(Request $request, User $user)
    {
        try {
            $request->validate([
                'title'    => 'nullable|string|max:100',
                'name'     => 'required|string|min:2|max:255',
                'email'    => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'phone'    => 'nullable|string|max:20|unique:users,phone,' . $user->id,
                'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'password' => 'nullable|string|min:8|confirmed',
            ], [
                'name.required'      => 'يرجى إدخال الاسم الكامل.',
                'email.required'     => 'البريد الإلكتروني مطلوب.',
                'email.unique'       => 'هذا البريد الإلكتروني مسجل مسبقاً.',
                'phone.unique'       => 'رقم الهاتف مسجل مسبقاً.',
                'password.min'       => 'كلمة المرور يجب أن تتكون من 8 أحرف على الأقل.',
                'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
                'avatar.image'       => 'يجب أن يكون الملف المرفق صورة صالحة.',
                'avatar.max'         => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $user->id);
        }

        try {
            $data = $request->only(['title', 'name', 'email', 'phone']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar && !str_starts_with($user->avatar, 'http') && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('users/avatars', 'public');
            }

            $user->update($data);

            return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
        } catch (Exception $e) {
            Log::error("Admin User Update Error ID {$user->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء تحديث المستخدم.');
        }
    }

    /**
     * حذف المستخدم نهائياً
     */
    public function destroy(User $user)
    {
        try {
            if ($user->avatar && !str_starts_with($user->avatar, 'http') && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->delete();

            return back()->with('success', 'تم حذف المستخدم بنجاح.');
        } catch (Exception $e) {
            Log::error("Admin User Delete Error ID {$user->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء محاولة حذف المستخدم.');
        }
    }
}
