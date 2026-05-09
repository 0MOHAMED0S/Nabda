<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class VolunteerProfileController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية الشاملة لكل الحالات
     */
    private function validationMessages()
    {
        return [
            // البيانات الشخصية
            'name.required'             => 'الاسم الكامل مطلوب ولا يمكن تركه فارغاً.',
            'name.string'               => 'يجب أن يكون الاسم نصاً.',
            'name.min'                  => 'الاسم يجب ألا يقل عن حرفين.',
            'name.max'                  => 'الاسم يجب ألا يتجاوز 255 حرفاً.',

            'email.required'            => 'البريد الإلكتروني مطلوب.',
            'email.email'               => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'              => 'هذا البريد الإلكتروني مسجل بالفعل لمتطوع آخر.',

            'phone.required'            => 'رقم الهاتف مطلوب.',
            'phone.regex'               => 'صيغة رقم الهاتف غير صالحة. يرجى إدخال أرقام صحيحة.',
            'phone.unique'              => 'رقم الهاتف هذا مستخدم بالفعل.',

            'address.required'          => 'العنوان السكني مطلوب.',
            'address.max'               => 'العنوان طويل جداً (الحد الأقصى 500 حرف).',

            // بيانات التطوع
            'volunteer_type.required'   => 'يرجى تحديد نوع التطوع.',
            'volunteer_type.in'         => 'نوع التطوع المحدد غير صحيح.',

            'foundation_id.prohibited_if' => 'لا يمكنك اختيار مؤسسة محددة عندما يكون نوع التطوع "عام".',
            'foundation_id.required_if' => 'يرجى اختيار المؤسسة بما أنك اخترت التطوع الدائم.',
            'foundation_id.exists'      => 'المؤسسة المختارة غير موجودة.',

            'volunteer_fields.array'    => 'صيغة مجالات التطوع غير صحيحة.',
            'governorates.array'        => 'صيغة المحافظات المتاحة غير صحيحة.',

            // الصورة الشخصية
            'avatar.image'              => 'الملف المرفق يجب أن يكون صورة.',
            'avatar.mimes'              => 'صيغ الصور المدعومة هي: jpeg, png, jpg, webp.',
            'avatar.max'                => 'حجم الصورة الشخصية يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }

    /**
     * API: عرض بيانات الملف الشخصي للمتطوع الحالي
     */
/**
     * API: عرض بيانات الملف الشخصي للمتطوع الحالي (مع الروابط الكاملة)
     */
    public function show(Request $request): JsonResponse
    {
        try {
            // جلب بيانات المتطوع الذي قام بتسجيل الدخول حالياً
            $volunteer = $request->user();

            // جلب بيانات المؤسسة (إذا كان متطوعاً تابعاً لمؤسسة)
            if ($volunteer->volunteer_type === 'affiliated') {
                $volunteer->load('foundation:id,name,logo');

                // تهيئة الرابط الكامل للوجو المؤسسة (إذا كانت العلاقة محملة والمؤسسة موجودة)
                if ($volunteer->relationLoaded('foundation') && $volunteer->foundation) {
                    $volunteer->foundation->logo_url = $volunteer->foundation->logo
                        ? asset('storage/' . $volunteer->foundation->logo)
                        : null;

                    // إخفاء المسار المحلي القديم للوجو
                    $volunteer->foundation->makeHidden(['logo']);
                }
            }

            // 🎯 تهيئة الروابط الكاملة لجميع صور ومرفقات المتطوع
            $volunteer->avatar_url = $volunteer->avatar
                ? asset('storage/' . $volunteer->avatar)
                : null;

            $volunteer->national_id_front_url = $volunteer->national_id_front
                ? asset('storage/' . $volunteer->national_id_front)
                : null;

            $volunteer->national_id_back_url = $volunteer->national_id_back
                ? asset('storage/' . $volunteer->national_id_back)
                : null;

            // 🧹 تنظيف الـ JSON: إخفاء المسارات المحلية القديمة لأننا أرسلنا الروابط الكاملة بدلاً منها
            $volunteer->makeHidden([
                'avatar',
                'national_id_front',
                'national_id_back'
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الملف الشخصي بنجاح.',
                'data'    => $volunteer
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Profile Show Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الملف الشخصي.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: تحديث بيانات الملف الشخصي للمتطوع بصرامة
     */
    public function update(Request $request): JsonResponse
    {
        $volunteer = $request->user();

        // 1. قواعد تحقق صارمة جداً (Hard Validation)
        $validator = Validator::make($request->all(), [
            // البيانات الشخصية
            'name'             => 'sometimes|required|string|min:2|max:255',
            'email'            => 'sometimes|required|email|max:255|unique:volunteers,email,' . $volunteer->id,
            'phone'            => 'sometimes|required|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:20|unique:volunteers,phone,' . $volunteer->id,
            'address'          => 'sometimes|required|string|max:500',

            // بيانات التطوع (متطابقة مع الشروط التي وضعناها في الـ Register)
            'volunteer_type'   => 'sometimes|required|in:general,affiliated',
            'foundation_id'    => 'prohibited_if:volunteer_type,general|required_if:volunteer_type,affiliated|nullable|integer|exists:foundations,id',

            'volunteer_fields' => 'sometimes|nullable|array',
            'volunteer_fields.*'=> 'string|max:100', // التحقق من داخل المصفوفة

            'governorates'     => 'sometimes|nullable|array',
            'governorates.*'   => 'string|max:100', // التحقق من داخل المصفوفة

            // الصورة الشخصية
            'avatar'           => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            /*
             * ⚠️ ملاحظة أمنية:
             * لا نسمح للمتطوع بتحديث (الرقم القومي، وصور البطاقة، وحالة الحساب) من خلال هذا الـ API.
             * إذا أراد تعديل الرقم القومي، يجب أن يكون هناك مسار مخصص يعيد حسابه لحالة (Pending) لمراجعته من الإدارة.
             */
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر التحديث لوجود أخطاء في البيانات المدخلة. يرجى مراجعتها.',
                'errors'  => $validator->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        // التأكد من أن المستخدم أرسل بيانات فعلية
        if (empty($request->all()) && !$request->hasFile('avatar')) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم إرسال أي بيانات جديدة ليتم تحديثها.'
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            // 2. استخراج الحقول المسموح بتحديثها فقط
            $data = $request->only([
                'name', 'email', 'phone', 'address',
                'volunteer_type', 'foundation_id', 'volunteer_fields', 'governorates'
            ]);

            // 🎯 السحر المنطقي: إذا قام بتغيير نوع تطوعه إلى "عام"، نقوم بحذف المؤسسة المرتبطة به
            if (isset($data['volunteer_type']) && $data['volunteer_type'] === 'general') {
                $data['foundation_id'] = null;
            }

            // 3. معالجة وتحديث الصورة الشخصية (Avatar)
            if ($request->hasFile('avatar')) {
                // مسح الصورة القديمة من السيرفر إذا كانت موجودة
                if ($volunteer->avatar && Storage::disk('public')->exists($volunteer->avatar)) {
                    Storage::disk('public')->delete($volunteer->avatar);
                }
                // حفظ الصورة الجديدة
                $data['avatar'] = $request->file('avatar')->store('volunteers/avatars', 'public');
            }

            // 4. التحديث النهائي في قاعدة البيانات
            if (!empty($data)) {
                $volunteer->update($data);
            }

            // 5. تهيئة البيانات المحدثة للإرجاع
            $volunteer->makeHidden(['national_id_front', 'national_id_back']);
            $volunteer->avatar_url = $volunteer->avatar ? asset('storage/' . $volunteer->avatar) : null;

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات الملف الشخصي بنجاح.',
                'data'    => $volunteer
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Profile Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني غير متوقع أثناء حفظ البيانات. يرجى المحاولة لاحقاً.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
