<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\FoundationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Exception;

class DonationController extends Controller
{
    /**
     * مصفوفة رسائل التحقق الشاملة لجميع الحالات (All Cases Messages)
     */
    private function validationMessages()
    {
        return [
            // المؤسسة والحالة
            'foundation_id.required'       => 'يجب تحديد المؤسسة المراد التبرع لها.',
            'foundation_id.integer'        => 'معرف المؤسسة يجب أن يكون رقماً.',
            'foundation_id.exists'         => 'المؤسسة المحددة غير موجودة في نظامنا.',
            'case_id.integer'              => 'معرف الحالة يجب أن يكون رقماً.',
            'case_id.exists'               => 'الحالة المحددة غير موجودة، أو أنها لا تتبع لهذه المؤسسة.',

            // نوع التبرع
            'donation_type.required'       => 'يرجى تحديد نوع التبرع (مالي أو عيني).',
            'donation_type.in'             => 'نوع التبرع المدخل غير صحيح (يجب أن يكون financial أو in-kind).',

            // بيانات المتبرع
            'donor_name.min'               => 'الاسم قصير جداً.',
            'donor_name.max'               => 'الاسم طويل جداً.',
            'donor_phone.regex'            => 'صيغة رقم الهاتف غير صالحة. يرجى إدخال أرقام صحيحة.',
            'donor_phone.min'              => 'رقم الهاتف قصير جداً.',
            'donor_phone.max'              => 'رقم الهاتف طويل جداً.',

            // التبرع المالي
            'amount.required_if'           => 'حقل المبلغ إجباري لأنك اخترت التبرع المالي.',
            'amount.prohibited_if'         => 'عذراً، لا يمكنك إرسال مبلغ مالي لأن نوع التبرع (عيني).',
            'amount.numeric'               => 'يجب أن يكون المبلغ المالي رقماً.',
            'amount.min'                   => 'الحد الأدنى للتبرع هو 5.',
            'amount.max'                   => 'المبلغ المدخل يتجاوز الحد المسموح به للعملية الواحدة.',

            // التبرع العيني
            'item_category.required_if'    => 'يرجى تحديد نوع الصنف (مثال: ملابس، أدوية) للتبرع العيني.',
            'item_category.prohibited_if'  => 'لا يمكنك إرسال صنف تبرع لأن نوع التبرع مالي.',
            'item_description.required_if' => 'يرجى كتابة وصف تفصيلي للتبرع العيني.',
            'item_description.prohibited_if'=> 'لا يمكنك إرسال وصف عيني لأن نوع التبرع مالي.',
            'item_condition.max'           => 'حالة التبرع المدخلة طويلة جداً.',
            'delivery_method.required_if'  => 'يرجى تحديد طريقة تسليم التبرع العيني.',
            'delivery_method.in'           => 'طريقة التسليم غير صحيحة.',
            'delivery_method.prohibited_if'=> 'لا يتم تحديد طريقة تسليم للتبرعات المالية.',

            // الاستلام والتوصيل
            'donor_address.required_if'    => 'العنوان التفصيلي مطلوب لأنك اخترت الاستلام من المنزل.',
            'donor_address.min'            => 'العنوان التفصيلي قصير جداً.',
            'pickup_time.required_if'      => 'يرجى تحديد الوقت المناسب للاستلام.',
            'pickup_time.date'             => 'صيغة الوقت والتاريخ غير صالحة.',
            'pickup_time.after'            => 'وقت الاستلام يجب أن يكون في المستقبل.',

            // المرفقات
            'donation_image.image'         => 'المرفق يجب أن يكون صورة صالحة.',
            'donation_image.mimes'         => 'صيغ الصور المدعومة: jpeg, png, jpg, webp.',
            'donation_image.max'           => 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }

    /**
     * API: إنشاء طلب تبرع جديد (متاح للجميع مع حماية صارمة)
     */
/**
     * API: إنشاء طلب تبرع جديد (متاح للجميع مع حماية صارمة وتحديث ذكي للحالة)
     */
    public function store(Request $request)
    {
        // 1. القواعد الصارمة (Hard Validation Rules)
        $validator = Validator::make($request->all(), [
            'foundation_id'    => 'required|integer|exists:foundations,id',

            // 🛡️ حماية متقدمة: التأكد أن الحالة موجودة وتتبع لنفس المؤسسة المرسلة
            'case_id'          => [
                'nullable',
                'integer',
                Rule::exists('foundation_cases', 'id')->where(function ($query) use ($request) {
                    $query->where('foundation_id', $request->foundation_id);
                }),
            ],

            'donation_type'    => 'required|string|in:financial,in-kind',

            'donor_name'       => 'nullable|string|min:2|max:255',
            'donor_phone'      => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:20',

            // 🎯 المنع التبادلي للمبالغ والأصناف
            'amount'           => 'required_if:donation_type,financial|prohibited_if:donation_type,in-kind|nullable|numeric|min:5|max:1000000',

            'item_category'    => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|string|max:255',
            'item_description' => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|string|min:3|max:1000',
            'item_condition'   => 'nullable|string|max:255',
            'delivery_method'  => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|in:home_pickup,branch_dropoff,collection_point',

            'donor_address'    => 'required_if:delivery_method,home_pickup|nullable|string|min:5|max:1000',
            'pickup_time'      => 'required_if:delivery_method,home_pickup,collection_point|nullable|date|after:now',

            'donation_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], $this->validationMessages());

        // 2. التحقق الذكي من تطابق نوع التبرع مع نوع الحالة
        $validator->after(function ($validator) use ($request) {
            if ($request->filled('case_id') && $request->filled('donation_type')) {
                if (empty($validator->errors()->get('case_id'))) {
                    $case = FoundationCase::find($request->case_id);

                    if ($case && $case->status !== 'active') {
                        $validator->errors()->add('case_id', 'عذراً، هذه الحالة مغلقة أو مكتملة ولا تقبل تبرعات جديدة.');
                    }

                    if ($case && $case->goal_type !== $request->donation_type) {
                        $goalNameAr = $case->goal_type === 'financial' ? 'مالية' : 'عينية';
                        $validator->errors()->add(
                            'donation_type',
                            "عذراً، هذه الحالة تقبل التبرعات الـ ({$goalNameAr}) فقط."
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر إتمام الطلب لوجود أخطاء في البيانات.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except('donation_image');

            // 3. تحديد هوية المتبرع وتجنب خطأ تعارض الـ Foreign Key
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();
                if ($user instanceof \App\Models\User) {
                    $data['user_id']     = $user->id;
                    $data['donor_name']  = $request->donor_name ?? $user->name;
                    $data['donor_phone'] = $request->donor_phone ?? $user->phone;
                } else {
                    $data['user_id']     = null;
                    $data['donor_name']  = $request->donor_name ?? 'فاعل خير';
                }
            } else {
                $data['user_id']     = null;
                $data['donor_name']  = $request->donor_name ?? 'فاعل خير';
            }

            // 4. رفع الصورة (إن وُجدت)
            if ($request->hasFile('donation_image')) {
                $data['donation_image'] = $request->file('donation_image')->store('donations/images', 'public');
            }

            // 5. معالجة عمليات الدفع وحالة الطلب
            if ($data['donation_type'] === 'financial') {
                $data['payment_method'] = 'fake_gateway';
                $data['payment_status'] = 'paid';
                $data['status']         = 'completed';
            } else {
                $data['payment_status'] = 'pending';
                $data['status']         = 'pending';
            }

            // 6. حفظ التبرع في الداتابيز
            $donation = Donation::create($data);

            // 🎯 7. [الإضافة الجديدة] التحقق التلقائي وتحديث حالة الحملة إذا اكتملت!
            if ($donation->case_id && $donation->donation_type === 'financial' && $donation->status === 'completed') {

                $case = FoundationCase::find($donation->case_id);

                if ($case && $case->goal_type === 'financial' && $case->target_amount > 0) {

                    // حساب إجمالي التبرعات الناجحة لهذه الحالة
                    $totalCollected = $case->donations()->where('status', 'completed')->sum('amount');

                    // إذا كان المجموع يغطي أو يتجاوز المبلغ المطلوب، نقوم بإغلاق الحالة
                    if ($totalCollected >= $case->target_amount) {
                        $case->update(['status' => 'completed']);
                    }
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل تبرعكم بنجاح. جزاكم الله خيراً وجعله في ميزان حسناتكم.',
                'data'    => $donation
            ], 201);

        } catch (Exception $e) {
            Log::error("API Make Donation Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء معالجة التبرع. يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }
}
