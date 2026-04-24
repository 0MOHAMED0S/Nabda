<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationProfileController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية الشاملة لكل الحالات
     */
    private function validationMessages()
    {
        return [
            // رسائل الاسم والنوع
            'name.required'             => 'اسم المؤسسة مطلوب ولا يمكن تركه فارغاً.',
            'name.string'               => 'يجب أن يكون اسم المؤسسة نصاً.',
            'name.min'                  => 'اسم المؤسسة يجب ألا يقل عن 3 أحرف.',
            'name.max'                  => 'اسم المؤسسة يجب ألا يتجاوز 255 حرفاً.',
            'type.required'             => 'نوع المؤسسة مطلوب.',
            'type.string'               => 'نوع المؤسسة يجب أن يكون نصاً.',
            'type.max'                  => 'نوع المؤسسة يجب ألا يتجاوز 100 حرف.',

            // رسائل الهاتف
            'phone.required'            => 'رقم الهاتف الأساسي مطلوب.',
            'phone.regex'               => 'صيغة رقم الهاتف الأساسي غير صالحة. يرجى إدخال أرقام صحيحة.',
            'phone.min'                 => 'رقم الهاتف الأساسي قصير جداً.',
            'phone.max'                 => 'رقم الهاتف الأساسي طويل جداً.',
            'phone.unique'              => 'رقم الهاتف هذا مستخدم بالفعل ومسجل لمؤسسة أخرى.',

            'contact_phone.regex'       => 'صيغة هاتف التواصل الإضافي غير صالحة.',
            'contact_phone.min'         => 'هاتف التواصل الإضافي قصير جداً.',
            'contact_phone.max'         => 'هاتف التواصل الإضافي طويل جداً.',

            // رسائل البريد الإلكتروني
            'email.required'            => 'البريد الإلكتروني الأساسي مطلوب.',
            'email.email'               => 'صيغة البريد الإلكتروني الأساسي غير صحيحة.',
            'email.unique'              => 'البريد الإلكتروني الأساسي مسجل بالفعل لمؤسسة أخرى.',
            'email.max'                 => 'البريد الإلكتروني الأساسي طويل جداً.',

            'contact_email.email'       => 'صيغة بريد التواصل الإضافي غير صحيحة.',
            'contact_email.max'         => 'بريد التواصل الإضافي طويل جداً.',

            // رسائل الروابط والتواريخ
            'website_url.url'           => 'رابط الموقع الإلكتروني غير صالح. تأكد من كتابته بصيغة صحيحة (مثال: https://example.com).',
            'website_url.max'           => 'رابط الموقع الإلكتروني طويل جداً.',
            'foundation_date.date'      => 'صيغة تاريخ التأسيس غير صالحة.',
            'foundation_date.before_or_equal' => 'تاريخ التأسيس لا يمكن أن يكون في المستقبل.',

            // رسائل النصوص الوصفية والطويلة
            'about_desc_1.max'          => 'الوصف الأول طويل جداً (الحد الأقصى 5000 حرف).',
            'about_desc_2.max'          => 'الوصف الثاني طويل جداً (الحد الأقصى 5000 حرف).',
            'vision.max'                => 'نص الرؤية طويل جداً (الحد الأقصى 2000 حرف).',
            'mission.max'               => 'نص الرسالة طويل جداً (الحد الأقصى 2000 حرف).',
            'core_mission.max'          => 'نص المهمة الأساسية طويل جداً (الحد الأقصى 2000 حرف).',
            'main_address.max'          => 'العنوان الرئيسي طويل جداً.',
            'working_hours.max'         => 'نص ساعات العمل طويل جداً.',

            // رسائل الصور (اللوجو والغلاف)
            'logo.image'                => 'الملف المرفق للوجو يجب أن يكون صورة.',
            'logo.mimes'                => 'صيغ الصور المدعومة للوجو هي: jpeg, png, jpg, webp.',
            'logo.max'                  => 'حجم اللوجو يجب ألا يتجاوز 2 ميجابايت.',
            'logo.dimensions'           => 'أبعاد اللوجو صغيرة جداً (الحد الأدنى 100x100 بيكسل).',

            'cover_image.image'         => 'الملف المرفق للغلاف يجب أن يكون صورة.',
            'cover_image.mimes'         => 'صيغ الصور المدعومة للغلاف هي: jpeg, png, jpg, webp.',
            'cover_image.max'           => 'حجم صورة الغلاف يجب ألا يتجاوز 5 ميجابايت.',
            'cover_image.dimensions'    => 'أبعاد الغلاف صغيرة جداً (الحد الأدنى 400x200 بيكسل).',
        ];
    }

    /**
     * API: عرض بيانات الملف الشخصي للمؤسسة الحالية
     */
    public function show(Request $request)
    {
        try {
            // جلب بيانات المؤسسة التي قامت بتسجيل الدخول حالياً
            $foundation = $request->user();

            // جلب جميع البيانات المرتبطة (العلاقات) وإرفاقها مع كائن المؤسسة
            $foundation->load([
                'teamMembers', // فريق العمل
                'faqs',        // الأسئلة الشائعة
                'goals',       // الأهداف
                'branches'     // الفروع
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الملف الشخصي مع كافة التفاصيل بنجاح.',
                'data'    => $foundation
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Profile Show Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الملف الشخصي.'
            ], 500);
        }
    }

    /**
     * API: تحديث بيانات الملف الشخصي للمؤسسة بصرامة
     */
    public function update(Request $request)
    {
        $foundation = $request->user();

        // قواعد تحقق صارمة جداً (Hard Validation)
        $validator = Validator::make($request->all(), [
            // البيانات الأساسية
            'name'            => 'sometimes|required|string|min:3|max:255',
            'phone'           => 'sometimes|required|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:20|unique:foundations,phone,' . $foundation->id,
            'email'           => 'sometimes|required|email:rfc,dns|max:255|unique:foundations,email,' . $foundation->id,
            'type'            => 'sometimes|required|string|min:2|max:100',

            // بيانات البروفايل المتقدمة
            'about_desc_1'    => 'sometimes|nullable|string|max:5000',
            'about_desc_2'    => 'sometimes|nullable|string|max:5000',
            'vision'          => 'sometimes|nullable|string|max:2000',
            'mission'         => 'sometimes|nullable|string|max:2000',
            'core_mission'    => 'sometimes|nullable|string|max:2000',

            // معلومات الاتصال المتقدمة
            'contact_email'   => 'sometimes|nullable|email:rfc,dns|max:255',
            'contact_phone'   => 'sometimes|nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:50',
            'main_address'    => 'sometimes|nullable|string|min:5|max:1000',
            'website_url'     => 'sometimes|nullable|url|max:255',
            'foundation_date' => 'sometimes|nullable|date|before_or_equal:today',
            'working_hours'   => 'sometimes|nullable|string|max:255',

            // الصور (مع التحقق من الأبعاد للحفاظ على جودة التصميم)
            'logo'            => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048|dimensions:min_width=100,min_height=100',
            'cover_image'     => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120|dimensions:min_width=400,min_height=200',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر التحديث لوجود أخطاء في البيانات المدخلة. يرجى مراجعتها.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // التأكد من أن المستخدم أرسل بيانات فعلية لمنع استهلاك الموارد بطلبات فارغة
        if (empty($request->all()) && !$request->hasFile('logo') && !$request->hasFile('cover_image')) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم إرسال أي بيانات جديدة ليتم تحديثها.'
            ], 400); // 400 Bad Request
        }

        try {
            // استخراج الحقول النصية فقط (استبعاد أي حقول ضارة إن وجدت)
            $data = $request->only([
                'name', 'phone', 'email', 'type',
                'about_desc_1', 'about_desc_2', 'vision', 'mission', 'core_mission',
                'contact_email', 'contact_phone', 'main_address', 'website_url',
                'foundation_date', 'working_hours'
            ]);

            // 1. معالجة وتحديث اللوجو
            if ($request->hasFile('logo')) {
                if ($foundation->logo && Storage::disk('public')->exists($foundation->logo)) {
                    Storage::disk('public')->delete($foundation->logo);
                }
                $data['logo'] = $request->file('logo')->store('foundations/logos', 'public');
            }

            // 2. معالجة وتحديث صورة الغلاف
            if ($request->hasFile('cover_image')) {
                if ($foundation->cover_image && Storage::disk('public')->exists($foundation->cover_image)) {
                    Storage::disk('public')->delete($foundation->cover_image);
                }
                $data['cover_image'] = $request->file('cover_image')->store('foundations/covers', 'public');
            }

            // التحديث النهائي في الداتابيز
            if (!empty($data)) {
                $foundation->update($data);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات الملف الشخصي بنجاح.',
                'data'    => $foundation->fresh() // إرجاع البيانات المحدثة لتنعكس فوراً في الواجهة
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Profile Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء حفظ البيانات. يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }
}
