<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use App\Models\Foundation; // 🎯 Required for foundation validation
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class VolunteerAuthController extends Controller
{
/**
     * API: تسجيل حساب متطوع جديد (يذهب لقائمة الانتظار Pending)
     */
    public function register(Request $request): JsonResponse
    {
        // 1. Initial Strict Validation
        $validator = Validator::make($request->all(), [
            // Step 1: Basic Info
            'name'              => 'required|string|min:2|max:255',
            'email'             => 'required|string|email|max:255|unique:volunteers,email',
            'phone'             => 'required|string|max:20|unique:volunteers,phone',
            'address'           => 'required|string|max:500',

            // Step 2: Volunteer Details
            'volunteer_type'    => 'required|in:general,affiliated',

            // 🎯 الاحترافية هنا:
            // 1. exclude_if: إذا كان التطوع (general)، استبعد هذا الحقل تماماً من الفحص.
            // 2. required_if: إذا كان التطوع (affiliated)، اجعله إجبارياً وتأكد من وجوده في جدول المؤسسات.
            'foundation_id'     => 'exclude_if:volunteer_type,general|required_if:volunteer_type,affiliated|exists:foundations,id',

            'volunteer_fields'  => 'nullable|array',
            'volunteer_fields.*'=> 'string|max:100', // Validate array items
            'governorates'      => 'nullable|array',
            'governorates.*'    => 'string|max:100', // Validate array items
            'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            // Step 3: Sensitive Info
            'national_id'       => 'required|string|size:14|unique:volunteers,national_id',
            'national_id_front' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'national_id_back'  => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'password'          => 'required|string|min:8|confirmed',
        ], [
            'foundation_id.required_if' => 'يرجى اختيار المؤسسة بما أنك اخترت التطوع الدائم مع مؤسسة محددة.',
            'foundation_id.exists'      => 'المؤسسة التي قمت باختيارها غير صالحة.',
            'national_id.size'          => 'الرقم القومي يجب أن يتكون من 14 رقماً.',
            'national_id.unique'        => 'هذا الرقم القومي مسجل لدينا مسبقاً.',
            'national_id_front.required'=> 'صورة الوجه الأمامي للبطاقة مطلوبة.',
            'national_id_back.required' => 'صورة الوجه الخلفي للبطاقة مطلوبة.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة البيانات المدخلة وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 🎯 2. Deep Validation: Check if the foundation is active and approved (فقط للمنضمين لمؤسسة)
        if ($request->volunteer_type === 'affiliated') {
            $foundation = Foundation::where('id', $request->foundation_id)
                ->where('status', 'active')
                ->where('approval_status', 'approved')
                ->first();

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة المختارة غير موجودة، أو غير معتمدة حالياً لاستقبال متطوعين.',
                    'errors'  => ['foundation_id' => ['المؤسسة غير متاحة.']]
                ], 422);
            }
        }

        // File path trackers for cleanup on failure
        $avatarPath = null;
        $idFrontPath = null;
        $idBackPath = null;

        try {
            DB::beginTransaction();

            // 3. Secure File Uploads
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('volunteers/avatars', 'public');
            }
            $idFrontPath = $request->file('national_id_front')->store('volunteers/national_ids', 'public');
            $idBackPath  = $request->file('national_id_back')->store('volunteers/national_ids', 'public');

            // 4. Create Volunteer Record
            $volunteer = Volunteer::create([
                'name'              => trim($request->name),
                'email'             => strtolower(trim($request->email)),
                'phone'             => trim($request->phone),
                'address'           => trim($request->address),

                'volunteer_type'    => $request->volunteer_type,
                // حماية إضافية: نضع null إجبارياً إذا كان التطوع عام، حتى لو تسرب الـ ID من الفرونت إند
                'foundation_id'     => $request->volunteer_type === 'affiliated' ? $request->foundation_id : null,

                'volunteer_fields'  => $request->volunteer_fields,
                'governorates'      => $request->governorates,
                'avatar'            => $avatarPath,

                'national_id'       => $request->national_id,
                'national_id_front' => $idFrontPath,
                'national_id_back'  => $idBackPath,
                'password'          => Hash::make($request->password),
                'status'            => 'pending', // Requires admin approval
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال طلب التطوع بنجاح! سيتم مراجعة بياناتك والبطاقة الشخصية من قبل الإدارة، وسنعلمك فور التفعيل لتتمكن من تسجيل الدخول.',
                'data'    => [
                    'id'    => $volunteer->id,
                    'name'  => $volunteer->name,
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            // 🛡️ Orphan File Cleanup: Delete files if DB insert fails
            foreach ([$avatarPath, $idFrontPath, $idBackPath] as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('API Volunteer Register Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء تسجيل الحساب. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    /**
     * API: تسجيل دخول المتطوع (مع فحص حالة الحساب الصارمة)
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Strict Input Validation
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email|max:255',
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
            // 2. Sanitization
            $email = strtolower(trim($request->email));

            // 3. Retrieve Volunteer
            $volunteer = Volunteer::where('email', $email)->first();

            // 4. Security Check: Unified Error for Enumeration Prevention
            if (!$volunteer || !Hash::check($request->password, $volunteer->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                ], 401);
            }

            // 🎯 5. Strict Business Logic Check: Is the account approved?
            if ($volunteer->status === 'pending') {
                return response()->json([
                    'status'  => false,
                    'message' => 'حسابك لا يزال قيد المراجعة من قبل الإدارة. يرجى الانتظار حتى يتم التحقق من بياناتك وبطاقتك الشخصية.'
                ], 403);
            }

            if ($volunteer->status === 'rejected') {
                return response()->json([
                    'status'  => false,
                    'message' => 'نعتذر، لقد تم رفض طلب التطوع الخاص بك من قبل الإدارة.'
                ], 403);
            }

            // 🎯 6. Associated Foundation Check (If Affiliated)
            // If the volunteer is tied to a specific foundation, ensure that foundation hasn't been banned/suspended.
            if ($volunteer->volunteer_type === 'affiliated' && $volunteer->foundation_id) {
                $foundation = Foundation::find($volunteer->foundation_id);
                if (!$foundation || $foundation->status !== 'active' || $foundation->approval_status !== 'approved') {
                     return response()->json([
                        'status'  => false,
                        'message' => 'تم إيقاف حساب المؤسسة التي تتطوع معها مؤقتاً. يرجى مراجعة الإدارة.'
                    ], 403);
                }
            }

            // 7. Issue Token
            $token = $volunteer->createToken('VolunteerAccess')->plainTextToken;

            // 8. Prepare Output Data (Hide highly sensitive fields)
            $volunteer->avatar_url = $volunteer->avatar ? asset('storage/' . $volunteer->avatar) : null;
            $volunteer->makeHidden(['national_id_front', 'national_id_back', 'avatar']);

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل الدخول بنجاح.',
                'data'    => [
                    'volunteer' => $volunteer,
                    'token'     => $token
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('API Volunteer Login Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تسجيل الدخول.',
            ], 500);
        }
    }

    /**
     * API: تسجيل خروج المتطوع وإلغاء التوكن
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // 🛡️ Privilege Escalation Prevention: Ensure token belongs to a Volunteer
            if ($user instanceof Volunteer) {
                // Destroy the current token
                $user->currentAccessToken()->delete();

                return response()->json([
                    'status'  => true,
                    'message' => 'تم تسجيل الخروج بنجاح.'
                ], 200);
            }

            return response()->json([
                'status'  => false,
                'message' => 'عملية غير مصرح بها. نوع الحساب غير مطابق.'
            ], 403);

        } catch (Exception $e) {
            Log::error('API Volunteer Logout Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تسجيل الخروج.'
            ], 500);
        }
    }
}
