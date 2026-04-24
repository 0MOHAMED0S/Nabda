<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\FoundationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DonationController extends Controller
{
    private function validationMessages()
    {
        return [
            'foundation_id.required'       => 'يجب تحديد المؤسسة المراد التبرع لها.',
            'foundation_id.integer'        => 'معرف المؤسسة يجب أن يكون رقماً.',
            'foundation_id.exists'         => 'المؤسسة المحددة غير موجودة في نظامنا.',
            'case_id.integer'              => 'معرف الحالة يجب أن يكون رقماً.',
            'case_id.exists'               => 'الحالة المحددة غير موجودة، أو أنها لا تتبع لهذه المؤسسة.',
            'donation_type.required'       => 'يرجى تحديد نوع التبرع (مالي أو عيني).',
            'donation_type.in'             => 'نوع التبرع المدخل غير صحيح (يجب أن يكون financial أو in-kind).',
            'donor_email.required_if'      => 'البريد الإلكتروني مطلوب لإتمام عملية الدفع الإلكتروني.',
            'donor_email.email'            => 'صيغة البريد الإلكتروني غير صحيحة.',
            'amount.required_if'           => 'حقل المبلغ إجباري لأنك اخترت التبرع المالي.',
            'amount.prohibited_if'         => 'عذراً، لا يمكنك إرسال مبلغ مالي لأن نوع التبرع (عيني).',
            'item_category.required_if'    => 'يرجى تحديد نوع الصنف (مثال: ملابس، أدوية) للتبرع العيني.',
            'item_category.prohibited_if'  => 'لا يمكنك إرسال صنف تبرع لأن نوع التبرع مالي.',
            'delivery_method.required_if'  => 'يرجى تحديد طريقة تسليم التبرع العيني.',
            // ... (باقي رسائل الخطأ الخاصة بك كما هي)
        ];
    }

    /**
     * API: إنشاء طلب تبرع جديد (يولد رابط الدفع للمالي، ويحفظ العيني)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'foundation_id'    => 'required|integer|exists:foundations,id',
            'case_id'          => [
                'nullable', 'integer',
                Rule::exists('foundation_cases', 'id')->where(function ($query) use ($request) {
                    $query->where('foundation_id', $request->foundation_id);
                }),
            ],
            'donation_type'    => 'required|string|in:financial,in-kind',

            'donor_name'       => 'nullable|string|min:2|max:255',
            'donor_phone'      => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:20',
            'donor_email'      => 'required_if:donation_type,financial|nullable|email|max:255', // 🎯 مطلوب لـ Paymob

            'amount'           => 'required_if:donation_type,financial|prohibited_if:donation_type,in-kind|nullable|numeric|min:5|max:1000000',

            'item_category'    => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|string|max:255',
            'item_description' => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|string|min:3|max:1000',
            'item_condition'   => 'nullable|string|max:255',
            'delivery_method'  => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|in:home_pickup,branch_dropoff,collection_point',
            'donor_address'    => 'required_if:delivery_method,home_pickup|nullable|string|min:5|max:1000',
            'pickup_time'      => 'required_if:delivery_method,home_pickup,collection_point|nullable|date|after:now',
            'donation_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], $this->validationMessages());

        // التحقق من توافق الحالة مع نوع التبرع
        $validator->after(function ($validator) use ($request) {
            if ($request->filled('case_id') && $request->filled('donation_type') && empty($validator->errors()->get('case_id'))) {
                $case = FoundationCase::find($request->case_id);
                if ($case && $case->status !== 'active') {
                    $validator->errors()->add('case_id', 'عذراً، هذه الحالة مغلقة أو مكتملة ولا تقبل تبرعات جديدة.');
                }
                if ($case && $case->goal_type !== $request->donation_type) {
                    $goalNameAr = $case->goal_type === 'financial' ? 'مالية' : 'عينية';
                    $validator->errors()->add('donation_type', "عذراً، هذه الحالة تقبل التبرعات الـ ({$goalNameAr}) فقط.");
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

            // 1. معالجة هوية المتبرع
            if (auth('sanctum')->check() && auth('sanctum')->user() instanceof \App\Models\User) {
                $user = auth('sanctum')->user();
                $data['user_id']     = $user->id;
                $data['donor_name']  = $request->donor_name ?? $user->name;
                $data['donor_phone'] = $request->donor_phone ?? $user->phone;
                $data['donor_email'] = $request->donor_email ?? $user->email;
            } else {
                $data['user_id']     = null;
                $data['donor_name']  = $request->donor_name ?? 'فاعل خير';
                $data['donor_email'] = $request->donor_email ?? 'dummy@nabdatkhair.com';
            }

            // 2. رفع الصورة العينية
            if ($request->hasFile('donation_image')) {
                $data['donation_image'] = $request->file('donation_image')->store('donations/images', 'public');
            }

            // 3. ضبط الحالة الافتراضية
            $data['payment_status'] = 'pending';
            $data['status']         = 'pending';
            $data['payment_method'] = $data['donation_type'] === 'financial' ? 'paymob' : null;

            // حفظ التبرع مبدئياً
            $donation = Donation::create($data);

            // 🎯 4. مسار التبرع المالي (Paymob Integration)
            if ($donation->donation_type === 'financial') {
                $amountInCents = $donation->amount * 100;

                // أ. الحصول على Auth Token
                $authResponse = Http::post('https://accept.paymob.com/api/auth/tokens', [
                    'api_key' => env('PAYMOB_API_KEY')
                ]);
                $authToken = $authResponse->json()['token'];

                // ب. تسجيل الطلب
                $orderResponse = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
                    'auth_token'      => $authToken,
                    'delivery_needed' => 'false',
                    'amount_cents'    => $amountInCents,
                    'currency'        => 'EGP',
                    'merchant_order_id' => $donation->id . '_' . time(),
                ]);
                $paymobOrderId = $orderResponse->json()['id'];

                // تحديث رقم الطلب في الداتابيز
                $donation->update(['paymob_order_id' => $paymobOrderId]);

                // ج. الحصول على مفتاح الدفع
                $paymentKeyResponse = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
                    'auth_token'     => $authToken,
                    'amount_cents'   => $amountInCents,
                    'expiration'     => 3600,
                    'order_id'       => $paymobOrderId,
                    'billing_data'   => [
                        "apartment" => "NA", "email" => $donation->donor_email, "floor" => "NA",
                        "first_name" => $donation->donor_name, "street" => "NA", "building" => "NA",
                        "phone_number" => $donation->donor_phone ?? "01000000000", "shipping_method" => "NA",
                        "postal_code" => "NA", "city" => "NA", "country" => "EG", "last_name" => "NA",
                        "state" => "NA"
                    ],
                    'currency'       => 'EGP',
                    'integration_id' => env('PAYMOB_INTEGRATION_ID')
                ]);

                $paymentToken = $paymentKeyResponse->json()['token'];
                $iframeLink = "https://accept.paymob.com/api/acceptance/iframes/" . env('PAYMOB_IFRAME_ID') . "?payment_token=" . $paymentToken;

                return response()->json([
                    'status'  => true,
                    'message' => 'تم إنشاء طلب التبرع. يرجى إكمال عملية الدفع.',
                    'data'    => [
                        'donation_id' => $donation->id,
                        'payment_url' => $iframeLink // الفرونت إند يفتح هذا الرابط للمستخدم
                    ]
                ], 201);
            }

            // 🎯 5. مسار التبرع العيني
            return response()->json([
                'status'  => true,
                'message' => 'تم تسجيل تبرعكم العيني بنجاح. سيتم التواصل معكم قريباً لترتيب الاستلام.',
                'data'    => $donation
            ], 201);

        } catch (Exception $e) {
            Log::error("API Make Donation Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء معالجة الطلب. يرجى المحاولة لاحقاً.'
            ], 500);
        }
    }

    /**
     * API: استقبال رد بيموب وتأكيد الدفع (Webhook)
     * هنا نقوم بإغلاق الحالة إذا اكتمل المبلغ!
     */
    public function paymobCallback(Request $request)
    {
        $data = $request->all();

        // 1. التحقق من الـ HMAC للأمان
        $hmacString = $data['amount_cents'] . $data['created_at'] . $data['currency'] .
                      $data['error_occured'] . $data['has_parent_transaction'] . $data['id'] .
                      $data['integration_id'] . $data['is_3d_secure'] . $data['is_auth'] .
                      $data['is_capture'] . $data['is_refunded'] . $data['is_standalone_payment'] .
                      $data['is_voided'] . $data['order']['id'] . $data['owner'] .
                      $data['pending'] . $data['source_data']['pan'] . $data['source_data']['sub_type'] .
                      $data['source_data']['type'] . $data['success'];

        $calculatedHmac = hash_hmac('sha512', $hmacString, env('PAYMOB_HMAC'));

        if ($calculatedHmac !== $request->hmac) {
            Log::warning('Paymob HMAC Check Failed!');
            return response()->json(['message' => 'HMAC validation failed'], 403);
        }

        $orderId = $data['order']['id'];
        $donation = Donation::where('paymob_order_id', $orderId)->first();

        if ($donation && $donation->status === 'pending') {
            if ($data['success'] === 'true' || $data['success'] === true) {
                // 2. تحديث التبرع إلى ناجح
                $donation->update([
                    'payment_status'        => 'paid',
                    'status'                => 'completed',
                    'paymob_transaction_id' => $data['id']
                ]);

                // 🎯 3. التحديث الذكي: إغلاق الحالة إذا اكتمل المبلغ
                if ($donation->case_id) {
                    $case = FoundationCase::find($donation->case_id);
                    if ($case && $case->goal_type === 'financial' && $case->target_amount > 0) {
                        $totalCollected = $case->donations()->where('status', 'completed')->sum('amount');
                        if ($totalCollected >= $case->target_amount) {
                            $case->update(['status' => 'completed']);
                        }
                    }
                }

            } else {
                // فشل الدفع
                $donation->update(['payment_status' => 'failed', 'status' => 'cancelled']);
            }
        }

        return response()->json(['message' => 'Processed'], 200);
    }
}
