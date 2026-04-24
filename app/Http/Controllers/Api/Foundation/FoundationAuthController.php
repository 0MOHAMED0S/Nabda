<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationAuthController extends Controller
{
    /**
     * API: تسجيل مؤسسة جديدة مع رفع المستندات
     */
/**
     * API: تسجيل حساب مؤسسة جديد (طلب التحاق)
     */
    public function register(Request $request)
    {
        // 1. التحقق من المدخلات مع رسائل خطأ مفصلة باللغة العربية
        $validator = Validator::make($request->all(), [
            // الخطوة 1
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:foundations',
            'phone'                 => 'required|string|max:20|unique:foundations',
            'type'                  => 'required|string|max:100',

            // الخطوة 2 (ملفات)
            'license_number'        => 'required|string|max:255',
            'supervising_authority' => 'required|string|max:255',
            'license_image'         => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'commercial_register'   => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'tax_card'              => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'accreditation_letter'  => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',

            // الخطوة 3 (صور وباسوورد)
            'headquarters_image'    => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'logo'                  => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'password'              => 'required|string|min:8|confirmed',
        ], [
            // رسائل الخطوة 1
            'name.required'                  => 'اسم المؤسسة مطلوب.',
            'email.required'                 => 'البريد الإلكتروني مطلوب.',
            'email.email'                    => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'                   => 'هذا البريد الإلكتروني مسجل مسبقاً لدينا.',
            'phone.required'                 => 'رقم الهاتف مطلوب.',
            'phone.unique'                   => 'رقم الهاتف مسجل مسبقاً لدينا.',
            'type.required'                  => 'يرجى تحديد نوع المؤسسة.',

            // رسائل الخطوة 2
            'license_number.required'        => 'رقم الترخيص الرسمي مطلوب.',
            'supervising_authority.required' => 'الجهة المشرفة مطلوبة.',
            'license_image.required'         => 'صورة الترخيص مطلوبة.',
            'commercial_register.required'   => 'صورة السجل التجاري مطلوبة.',
            'tax_card.required'              => 'صورة البطاقة الضريبية مطلوبة.',
            'accreditation_letter.required'  => 'خطاب الاعتماد الرسمي مطلوب.',

            // رسائل الخطوة 3
            'headquarters_image.required'    => 'صورة مقر المؤسسة مطلوبة.',
            'logo.required'                  => 'لوجو المؤسسة مطلوب.',
            'password.required'              => 'كلمة المرور مطلوبة.',
            'password.min'                   => 'كلمة المرور يجب ألا تقل عن 8 رموز.',
            'password.confirmed'             => 'تأكيد كلمة المرور غير متطابق.',

            // رسائل عامة للملفات
            '*.file'                         => 'الملف المرفق غير صالح.',
            '*.image'                        => 'الملف يجب أن يكون صورة صحيحة.',
            '*.mimes'                        => 'الصيغ المدعومة للملفات هي: jpeg, png, jpg, pdf.',
            '*.max'                          => 'حجم الملف يتجاوز الحد المسموح به.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة الحقول وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. تخزين الملفات في مجلد storage/app/public/foundations
            $license_image         = $request->file('license_image')->store('foundations/documents', 'public');
            $commercial_register   = $request->file('commercial_register')->store('foundations/documents', 'public');
            $tax_card              = $request->file('tax_card')->store('foundations/documents', 'public');
            $accreditation_letter  = $request->file('accreditation_letter')->store('foundations/documents', 'public');
            $headquarters_image    = $request->file('headquarters_image')->store('foundations/images', 'public');
            $logo                  = $request->file('logo')->store('foundations/logos', 'public');

            // 3. إنشاء حساب المؤسسة (طلب التحاق)
            $foundation = Foundation::create([
                'name'                  => $request->name,
                'email'                 => $request->email,
                'phone'                 => $request->phone,
                'type'                  => $request->type,
                'license_number'        => $request->license_number,
                'supervising_authority' => $request->supervising_authority,
                'password'              => Hash::make($request->password),
                'license_image'         => $license_image,
                'commercial_register'   => $commercial_register,
                'tax_card'              => $tax_card,
                'accreditation_letter'  => $accreditation_letter,
                'headquarters_image'    => $headquarters_image,
                'logo'                  => $logo,
                'approval_status'       => 'pending', // قيد المراجعة
                'status'                => 'active',  // الحساب نشط كمستخدم، لكن ينتظر الاعتماد
            ]);

            // 🎯 تم إزالة إصدار التوكن نهائياً من هنا

            // 4. إرجاع استجابة تفيد بنجاح إرسال الطلب فقط
            return response()->json([
                'status'  => true,
                'message' => 'تم استلام طلب التسجيل بنجاح. يرجى الانتظار لحين مراجعة واعتماد الإدارة لبياناتكم، وسيتم إبلاغكم فور الموافقة لتتمكنوا من تسجيل الدخول.',
                // يمكننا إرجاع بعض البيانات الأساسية لتأكيد العملية للفرونت إند (بدون باسوورد أو بيانات حساسة)
                'data'    => [
                    'id'    => $foundation->id,
                    'name'  => $foundation->name,
                    'email' => $foundation->email,
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error("Foundation Registration API Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ غير متوقع في الخادم أثناء التسجيل. يرجى المحاولة لاحقاً.',
                'data'    => null
            ], 500);
        }
    }

    /**
     * API: تسجيل الدخول (Login) للمؤسسة
     */
    public function login(Request $request)
    {
        // 1. التحقق من المدخلات
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'يرجى إدخال البريد الإلكتروني.',
            'email.email'       => 'صيغة البريد الإلكتروني المدخلة غير صحيحة.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات الدخول غير مكتملة أو غير صالحة.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $foundation = Foundation::where('email', $request->email)->first();

            // 2. التحقق من وجود الحساب وصحة كلمة المرور
            if (!$foundation || !Hash::check($request->password, $foundation->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'
                ], 401);
            }

            // 3. فحص حالة الاعتماد (approval_status) بدلاً من is_approved
            if ($foundation->approval_status === 'pending') {
                return response()->json([
                    'status'  => false,
                    'message' => 'حساب المؤسسة لا يزال قيد المراجعة من قبل الإدارة. يرجى الانتظار لحين الاعتماد.'
                ], 403);
            }

            if ($foundation->approval_status === 'rejected') {
                return response()->json([
                    'status'  => false,
                    'message' => 'نأسف، لقد تم رفض طلب اعتماد مؤسستكم من قبل الإدارة.'
                ], 403);
            }

            // 4. فحص حالة التفعيل (status) للإيقاف الإداري المؤقت
            if ($foundation->status === 'inactive') {
                return response()->json([
                    'status'  => false,
                    'message' => 'تم إيقاف هذا الحساب من قبل الإدارة. يرجى التواصل مع الدعم الفني لحل المشكلة.'
                ], 403);
            }

            // 5. تسجيل الدخول ناجح وإصدار Token
            $token = $foundation->createToken('FoundationAccess')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل الدخول بنجاح.',
                'data'    => [
                    'foundation' => $foundation,
                    'token'      => $token
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error("Foundation Login API Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء محاولة تسجيل الدخول. يرجى المحاولة لاحقاً.',
            ], 500);
        }
    }

    /**
     * API: تسجيل الخروج وإبطال التوكن
     */
    public function logout(Request $request)
    {
        try {
            // حذف التوكن الذي تم استخدامه في الطلب الحالي
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل الخروج بنجاح.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج.'
            ], 500);
        }
    }
}
