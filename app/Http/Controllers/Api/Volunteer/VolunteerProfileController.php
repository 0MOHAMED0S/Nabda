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
public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $volunteer = $request->user();

            // 1. قواعد التحقق الذكية (Smart Validation)
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name'           => 'sometimes|required|string|min:3|max:255',
                'phone'          => 'sometimes|required|string|max:20|unique:users,phone,' . $volunteer->id,

                // 🎯 التحقق من نوع التطوع والمؤسسة
                'volunteer_type' => 'sometimes|required|in:general,affiliated',
                'foundation_id'  => [
                    'required_if:volunteer_type,affiliated', // مطلوب إجبارياً إذا كان تابعاً
                    'prohibited_if:volunteer_type,general',  // ممنوع إرساله تماماً إذا كان عام
                    'nullable',
                    'exists:foundations,id'
                ],

                // 🎯 إضافة التحقق من الصورة
                'avatar'         => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:5120',
            ], [
                'foundation_id.required_if'   => 'يرجى اختيار المؤسسة التابع لها.',
                'foundation_id.prohibited_if' => 'لا يمكنك اختيار مؤسسة لأن نوع التطوع "عام".',
                'avatar.image'                => 'يجب أن يكون الملف المرفق صورة صالحة.',
                'avatar.max'                  => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'يرجى مراجعة البيانات المدخلة.',
                    'errors'  => $validator->errors()
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. تحديث البيانات الأساسية
            if ($request->has('name')) $volunteer->name = $request->name;
            if ($request->has('phone')) $volunteer->phone = $request->phone;

            // 3. 🎯 المعالجة الذكية لنوع التطوع والمؤسسة
            if ($request->has('volunteer_type')) {
                $volunteer->volunteer_type = $request->volunteer_type;

                if ($request->volunteer_type === 'general') {
                    // إذا اختار "عام"، نقوم بمسح الـ foundation_id من قاعدة البيانات فوراً للأمان
                    $volunteer->foundation_id = null;
                } elseif ($request->volunteer_type === 'affiliated' && $request->has('foundation_id')) {
                    // إذا اختار "تابع"، نحفظ رقم المؤسسة
                    $volunteer->foundation_id = $request->foundation_id;
                }
            }

            // 4. 🎯 معالجة وتحديث الصورة الشخصية (Avatar)
            if ($request->hasFile('avatar')) {
                // مسح الصورة القديمة من السيرفر إذا كانت موجودة ولا تخص روابط خارجية (مثل جوجل)
                if ($volunteer->avatar && !filter_var($volunteer->avatar, FILTER_VALIDATE_URL) && \Illuminate\Support\Facades\Storage::disk('public')->exists($volunteer->avatar)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($volunteer->avatar);
                }

                // رفع الصورة الجديدة وحفظ مسارها
                $volunteer->avatar = $request->file('avatar')->store('volunteers/avatars', 'public');
            }

            $volunteer->save();

            // تجهيز رابط الصورة الكامل في الاستجابة (اختياري لسهولة عرضها في الفرونت إند)
            if ($volunteer->avatar) {
                $volunteer->avatar_url = filter_var($volunteer->avatar, FILTER_VALIDATE_URL)
                    ? $volunteer->avatar
                    : asset('storage/' . $volunteer->avatar);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث البيانات بنجاح.',
                'data'    => $volunteer
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Volunteer Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تحديث البيانات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
