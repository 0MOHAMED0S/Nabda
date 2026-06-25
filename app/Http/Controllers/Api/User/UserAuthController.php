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
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'    => 'nullable|string|max:100', // 🎯 تمت إضافة اللقب
            'name'     => 'required|string|min:2|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'phone'    => 'nullable|string|max:20|unique:users,phone',
            'avatar'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required'      => 'يرجى إدخال اسمك الكامل.',
            'email.required'     => 'البريد الإلكتروني مطلوب.',
            'email.unique'       => 'هذا البريد الإلكتروني مسجل مسبقاً.',
            'phone.nullable'     => 'رقم الهاتف اختياري.',
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
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        $avatarPath = null;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('users/avatars', 'public');
            }

            $user = \App\Models\User::create([
                'title'    => $request->title, // 🎯 حفظ اللقب
                'name'     => trim($request->name),
                'email'    => strtolower(trim($request->email)),
                'phone'    => trim($request->phone),
                'avatar'   => $avatarPath,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);

            $token = $user->createToken('UserAccess')->plainTextToken;

            \Illuminate\Support\Facades\DB::commit();

            $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;
            $user->makeHidden('avatar');

            return response()->json([
                'status'  => true,
                'message' => 'تم إنشاء الحساب بنجاح، مرحباً بك!',
                'data'    => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 201, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            if ($avatarPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($avatarPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($avatarPath);
            }

            \Illuminate\Support\Facades\Log::error('API User Register Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء إنشاء الحساب. يرجى المحاولة لاحقاً.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function login(Request $request): JsonResponse
    {
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
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $email = strtolower(trim($request->email));
            $user = User::where('email', $email)->first();
            if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة، يرجى المحاولة مرة أخرى.'
                ], 401, [], JSON_UNESCAPED_UNICODE);
            }

            $token = $user->createToken('UserAccess')->plainTextToken;
            if ($user->avatar) {
                $user->avatar_url = filter_var($user->avatar, FILTER_VALIDATE_URL) ? $user->avatar : asset('storage/' . $user->avatar);
            } else {
                $user->avatar_url = null;
            }
            $user->makeHidden('avatar');

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل الدخول بنجاح.',
                'data'    => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API User Login Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تسجيل الدخول.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user instanceof User) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة. هذا الحساب لا يملك صلاحيات المستخدم العادي.'
                ], 403);
            }

            $avatarUrl = null;
            if ($user->avatar) {
                if (filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                    $avatarUrl = $user->avatar;
                } else {
                    $avatarUrl = asset('storage/' . $user->avatar);
                }
            }

            $data = [
                'id'                => $user->id,
                'title'             => $user->title ?? null, // إضافة حقل اللقب
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'city'              => $user->city ?? null, // إرجاع null بدلاً من نص فارغ

                'charity_interests' => $user->charity_interests ?? [],
                'avatar_url'        => $avatarUrl,
                'joined_at'         => $user->created_at->format('Y-m-d'), // مثال: 2024-05-12
                'joined_since'      => $user->created_at->diffForHumans(), // مثال: منذ شهرين

            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الملف الشخصي بكافة البيانات بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API User Profile Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب الملف الشخصي.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user instanceof User) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة.'
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'title'             => 'nullable|string|max:100', // 🎯 إضافة التحقق من اللقب
                'name'              => 'required|string|min:2|max:255',
                'email'             => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
                'phone'             => ['nullable', 'string', 'max:20', \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($user->id)],
                'city'              => 'nullable|string|max:255',
                'charity_interests' => 'nullable|array',
                'charity_interests.*' => 'string|max:100',
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
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $oldAvatar = $user->avatar; // نحفظ المسار القديم قبل التحديث

            if ($request->hasFile('avatar')) {
                $user->avatar = $request->file('avatar')->store('users/avatars', 'public');
            }

            $user->title             = $request->title; // 🎯 تحديث اللقب
            $user->name              = trim($request->name);
            $user->email             = strtolower(trim($request->email));
            $user->phone             = trim($request->phone);
            $user->city              = $request->city;
            $user->charity_interests = $request->charity_interests;

            $user->save();

            if ($request->hasFile('avatar') && $oldAvatar && !filter_var($oldAvatar, FILTER_VALIDATE_URL) && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldAvatar)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldAvatar);
            }

            if ($user->avatar) {
                $user->avatar_url = filter_var($user->avatar, FILTER_VALIDATE_URL) ? $user->avatar : asset('storage/' . $user->avatar);
            } else {
                $user->avatar_url = null;
            }

            $user->makeHidden('avatar');

            return response()->json([
                'status'  => true,
                'message' => 'تم حفظ التغييرات بنجاح.',
                'data'    => $user
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API User Update Profile Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تحديث البيانات.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user instanceof User) {
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

    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user instanceof User) {
                return response()->json([
                    'status'  => false,
                    'message' => 'صلاحيات مرفوضة.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
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

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة. يرجى التأكد والمحاولة مرة أخرى.',
                    'errors'  => [
                        'current_password' => ['كلمة المرور الحالية غير صحيحة.']
                    ]
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

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
