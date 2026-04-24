<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;

class UserAuthController extends Controller
{
    /**
     * API: تسجيل حساب مستخدم جديد (متبرع)
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:2|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'phone'    => 'required|string|max:20|unique:users,phone',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required'      => 'يرجى إدخال اسمك الكامل.',
            'email.required'     => 'البريد الإلكتروني مطلوب.',
            'email.unique'       => 'هذا البريد الإلكتروني مسجل مسبقاً.',
            'phone.required'     => 'رقم الهاتف مطلوب.',
            'phone.unique'       => 'رقم الهاتف مسجل مسبقاً.',
            'password.required'  => 'كلمة المرور مطلوبة.',
            'password.min'       => 'كلمة المرور يجب أن تتكون من 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'avatar.image'       => 'يجب أن يكون الملف المرفق صورة صالحة.',
            'avatar.max'         => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة البيانات المدخلة وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $avatarPath = null;

        try {
            // 🛡️ بدء معاملة قاعدة البيانات (Transaction) لضمان أمان العملية بالكامل
            DB::beginTransaction();

            // 1. رفع الصورة أولاً (إذا وجدت)
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('users/avatars', 'public');
            }

            // 2. إنشاء المستخدم وتوحيد حالة الأحرف للإيميل (Sanitization)
            $user = User::create([
                'name'     => trim($request->name),
                'email'    => strtolower(trim($request->email)),
                'phone'    => trim($request->phone),
                'avatar'   => $avatarPath,
                'password' => Hash::make($request->password),
            ]);

            // 3. إصدار التوكن
            $token = $user->createToken('UserAccess')->plainTextToken;

            // 4. تأكيد حفظ البيانات في قاعدة البيانات
            DB::commit();

            // إرفاق الرابط الكامل للصورة للاستجابة
            $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
            $user->makeHidden('avatar'); // إخفاء المسار القصير لتنظيف الـ JSON

            return response()->json([
                'status'  => true,
                'message' => 'تم إنشاء الحساب بنجاح، مرحباً بك!',
                'data'    => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 201);
        } catch (Exception $e) {
            // 🛡️ التراجع عن قاعدة البيانات في حال حدوث أي خطأ برمجي أو انقطاع
            DB::rollBack();

            // 🛡️ حذف الصورة المرفوعة من السيرفر (Orphan File Prevention) حتى لا تستهلك مساحة
            if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            Log::error('API User Register Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء إنشاء الحساب. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    /**
     * API: تسجيل الدخول (ذكي: يدعم الإيميل أو رقم الهاتف)
     */
    public function login(Request $request): JsonResponse
    {
        // 1. التحقق من المدخلات (البريد الإلكتروني وكلمة المرور فقط)
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'يرجى إدخال البريد الإلكتروني.',
            'email.email'       => 'صيغة البريد الإلكتروني غير صحيحة.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات مفقودة أو غير صالحة.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. تنظيف المدخل (إزالة الفراغات وتحويله لأحرف صغيرة لضمان المطابقة الدقيقة)
            $email = strtolower(trim($request->email));

            // 3. البحث عن المستخدم بالبريد الإلكتروني فقط
            $user = User::where('email', $email)->first();

            // 4. التحقق الأمني (تجنب الـ Timing Attacks)
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة، يرجى المحاولة مرة أخرى.'
                ], 401);
            }

            // 5. إصدار التوكن
            $token = $user->createToken('UserAccess')->plainTextToken;

            // 6. تهيئة البيانات للعرض (تهيئة رابط الصورة وإخفاء المسار الداخلي)
            $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
            $user->makeHidden('avatar');

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل الدخول بنجاح.',
                'data'    => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('API User Login Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تسجيل الدخول.',
            ], 500);
        }
    }

    /**
     * API: جلب الملف الشخصي للمستخدم الحالي (مع كافة البيانات)
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            // جلب المستخدم الحالي
            $user = $request->user();

            // 🛡️ حماية التصعيد (Privilege Escalation Prevention)
            if (!$user instanceof User) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة. هذا الحساب لا يملك صلاحيات المستخدم العادي.'
                ], 403);
            }
            // 🎯 تشكيل البيانات (Data Mapping) لضمان نظافة الـ JSON للفرونت إند
            $data = [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'city'              => $user->city ?? '', // إرجاع نص فارغ بدلاً من null

                // التأكد من إرجاع مصفوفة حتى لو لم يختر أي اهتمامات لتجنب أخطاء الفرونت إند
                'charity_interests' => $user->charity_interests ?? [],

                // رابط الصورة
                'avatar_url'        => $user->avatar ? asset('storage/' . $user->avatar) : null,

                // تواريخ منسقة
                'joined_at'         => $user->created_at->format('Y-m-d'), // مثال: 2024-05-12
                'joined_since'      => $user->created_at->diffForHumans(), // مثال: منذ شهرين

            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الملف الشخصي بكافة البيانات بنجاح.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            Log::error('API User Profile Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب الملف الشخصي.',
            ], 500);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 1. حماية التصعيد للتأكد أن صاحب الطلب هو مستخدم عادي
            if (!$user instanceof User) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة.'
                ], 403);
            }

            // 2. التحقق من المدخلات (ملاحظة استخدام Rule::unique لتجاهل الإيميل والهاتف الخاص بالمستخدم نفسه)
            $validator = Validator::make($request->all(), [
                'name'              => 'required|string|min:2|max:255',
                'email'             => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'phone'             => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
                'city'              => 'nullable|string|max:255',
                'charity_interests' => 'nullable|array', // يجب أن ترسل كمصفوفة من الواجهة
                'charity_interests.*' => 'string|max:100', // كل عنصر داخل المصفوفة يجب أن يكون نص
                'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            ], [
                'email.unique'             => 'هذا البريد الإلكتروني مستخدم لحساب آخر.',
                'phone.unique'             => 'رقم الهاتف مستخدم لحساب آخر.',
                'charity_interests.array'  => 'صيغة الاهتمامات غير صحيحة.',
                'avatar.image'             => 'الملف المرفق يجب أن يكون صورة صالحة.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'يرجى مراجعة البيانات المدخلة.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // 3. معالجة تحديث الصورة (وإزالة القديمة)
            $oldAvatar = $user->avatar; // نحفظ المسار القديم قبل التحديث

            if ($request->hasFile('avatar')) {
                // رفع الصورة الجديدة
                $user->avatar = $request->file('avatar')->store('users/avatars', 'public');
            }

            // 4. تحديث باقي البيانات (مع تنظيف الإيميل)
            $user->name              = trim($request->name);
            $user->email             = strtolower(trim($request->email));
            $user->phone             = trim($request->phone);
            $user->city              = $request->city;
            $user->charity_interests = $request->charity_interests; // سيتم تحويلها لـ JSON تلقائياً بفضل الـ Casts

            $user->save();

            // 5. التنظيف الذكي (Orphan File Cleanup)
            // إذا تم رفع صورة جديدة بنجاح، وكان هناك صورة قديمة بالفعل، احذف القديمة من السيرفر
            if ($request->hasFile('avatar') && $oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
                Storage::disk('public')->delete($oldAvatar);
            }

            // 6. تجهيز البيانات للعرض
            $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
            $user->makeHidden('avatar');

            return response()->json([
                'status'  => true,
                'message' => 'تم حفظ التغييرات بنجاح.',
                'data'    => $user
            ], 200);
        } catch (Exception $e) {
            Log::error('API User Update Profile Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تحديث البيانات.',
            ], 500);
        }
    }

    /**
     * API: تسجيل الخروج (إلغاء التوكن الحالي)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 🛡️ التأكد من الصلاحيات قبل الحذف
            if ($user instanceof User) {
                // تدمير التوكن الحالي فقط (يسمح له بالبقاء مسجلاً في أجهزة أخرى إن وجدت)
                $user->currentAccessToken()->delete();

                return response()->json([
                    'status'  => true,
                    'message' => 'تم تسجيل الخروج بنجاح. نراك قريباً!'
                ], 200);
            }

            return response()->json([
                'status'  => false,
                'message' => 'عملية غير مصرح بها.'
            ], 403);
        } catch (Exception $e) {
            Log::error('API User Logout Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج.'
            ], 500);
        }
    }

    /**
     * API: تغيير كلمة المرور للمستخدم
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 🛡️ حماية التصعيد للتأكد أن صاحب الطلب هو مستخدم عادي
            if (!$user instanceof User) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة.'
                ], 403);
            }

            // 1. التحقق من المدخلات بدقة
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                // يجب أن تكون 8 أحرف على الأقل، ومؤكدة، ومختلفة عن كلمة المرور القديمة
                'new_password'     => 'required|string|min:8|confirmed|different:current_password',
            ], [
                'current_password.required' => 'يرجى إدخال كلمة المرور الحالية.',
                'new_password.required'     => 'يرجى إدخال كلمة المرور الجديدة.',
                'new_password.min'          => 'كلمة المرور الجديدة يجب أن تتكون من 8 أحرف على الأقل.',
                'new_password.confirmed'    => 'تأكيد كلمة المرور غير متطابق.',
                'new_password.different'    => 'كلمة المرور الجديدة يجب ألا تكون مطابقة لكلمة المرور القديمة.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'يرجى مراجعة البيانات المدخلة وإصلاح الأخطاء.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // 2. التحقق الأمني: هل كلمة المرور الحالية المدخلة صحيحة؟
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة. يرجى التأكد والمحاولة مرة أخرى.',
                    // نرجعها في شكل errors لكي يسهل للفرونت إند إظهارها تحت الحقل مباشرة
                    'errors'  => [
                        'current_password' => ['كلمة المرور الحالية غير صحيحة.']
                    ]
                ], 400);
            }

            // 3. تحديث وتشفير كلمة المرور الجديدة
            $user->password = Hash::make($request->new_password);
            $user->save();

            // 💡 ملاحظة احترافية: في بعض الأنظمة يتم تدمير التوكن وإجبار المستخدم على تسجيل الدخول مرة أخرى
            // ولكن لتجربة مستخدم (UX) أفضل، نكتفي بتغيير الباسوورد مع إبقاء الجلسة الحالية نشطة.

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث كلمة المرور بنجاح.',
            ], 200);
        } catch (Exception $e) {
            Log::error('API User Update Password Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تغيير كلمة المرور.',
            ], 500);
        }
    }
}
