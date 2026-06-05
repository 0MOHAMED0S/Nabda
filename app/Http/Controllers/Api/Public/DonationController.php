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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

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
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'foundation_id'    => 'required|integer|exists:foundations,id',
            'case_id'          => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('foundation_cases', 'id')->where(function ($query) use ($request) {
                    $query->where('foundation_id', $request->foundation_id);
                }),
            ],
            'donation_type'    => 'required|string|in:financial,in-kind',
            'donor_name'       => 'nullable|string|min:2|max:255',
            'donor_phone'      => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:20',
            'donor_email'      => 'required_if:donation_type,financial|nullable|email|max:255',
            'amount'           => 'required_if:donation_type,financial|prohibited_if:donation_type,in-kind|nullable|numeric|min:5|max:1000000',
            'item_category'    => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|string|max:255',
            'item_description' => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|string|min:3|max:1000',
            'item_condition'   => 'nullable|string|max:255',
            'delivery_method'  => 'required_if:donation_type,in-kind|prohibited_if:donation_type,financial|nullable|in:home_pickup,branch_dropoff,collection_point',
            'donor_address'    => 'required_if:delivery_method,home_pickup|nullable|string|min:5|max:1000',
            'pickup_time'      => 'required_if:delivery_method,home_pickup,collection_point|nullable|date|after:now',
            'donation_image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->filled('case_id') && $request->filled('donation_type') && empty($validator->errors()->get('case_id'))) {
                $case = \App\Models\FoundationCase::find($request->case_id);
                if ($case && $case->status !== 'active') {
                    $validator->errors()->add('case_id', 'عذراً، هذه الحالة مغلقة أو مكتملة ولا تقبل تبرعات جديدة.');
                }
                if ($case && $case->goal_type !== 'both' && $case->goal_type !== $request->donation_type) {
                    $goalNameAr = $case->goal_type === 'financial' ? 'مالية' : 'عينية';
                    $validator->errors()->add('donation_type', "عذراً، هذه الحالة تقبل التبرعات الـ ({$goalNameAr}) فقط.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'تعذر إتمام الطلب.', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->except('donation_image');

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

            if ($request->hasFile('donation_image')) {
                $data['donation_image'] = $request->file('donation_image')->store('donations/images', 'public');
            }

            $data['payment_status'] = 'pending';
            $data['status']         = 'pending';
            $data['payment_method'] = $data['donation_type'] === 'financial' ? 'paymob' : null;

            $donation = \App\Models\Donation::create($data);

            if ($donation->donation_type === 'financial') {
                $amountInCents = $donation->amount * 100;
                $authResponse = \Illuminate\Support\Facades\Http::post('https://accept.paymob.com/api/auth/tokens', ['api_key' => env('PAYMOB_API_KEY')]);
                $authToken = $authResponse->json()['token'];

                $orderResponse = \Illuminate\Support\Facades\Http::post('https://accept.paymob.com/api/ecommerce/orders', [
                    'auth_token'      => $authToken,
                    'delivery_needed' => 'false',
                    'amount_cents' => $amountInCents,
                    'currency'        => 'EGP',
                    'merchant_order_id' => $donation->id . '_' . time(),
                ]);
                $paymobOrderId = $orderResponse->json()['id'];
                $donation->update(['paymob_order_id' => $paymobOrderId]);

                $paymentKeyResponse = \Illuminate\Support\Facades\Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
                    'auth_token' => $authToken,
                    'amount_cents' => $amountInCents,
                    'expiration' => 3600,
                    'order_id' => $paymobOrderId,
                    'billing_data' => [
                        "apartment" => "NA",
                        "email" => $donation->donor_email,
                        "floor" => "NA",
                        "first_name" => $donation->donor_name,
                        "street" => "NA",
                        "building" => "NA",
                        "phone_number" => $donation->donor_phone ?? "01000000000",
                        "shipping_method" => "NA",
                        "postal_code" => "NA",
                        "city" => "NA",
                        "country" => "EG",
                        "last_name" => "NA",
                        "state" => "NA"
                    ],
                    'currency' => 'EGP',
                    'integration_id' => env('PAYMOB_INTEGRATION_ID')
                ]);

                $paymentToken = $paymentKeyResponse->json()['token'];
                $iframeLink = "https://accept.paymob.com/api/acceptance/iframes/" . env('PAYMOB_IFRAME_ID') . "?payment_token=" . $paymentToken;

                return response()->json([
                    'status' => true,
                    'message' => 'تم إنشاء طلب التبرع.',
                    'data' => ['donation_id' => $donation->id, 'payment_url' => $iframeLink]
                ], 201);
            }

            // 🎯 إشعارات التبرع العيني
            $foundation = \App\Models\Foundation::find($donation->foundation_id);
            if ($foundation) {
                $foundation->notify(new \App\Notifications\GeneralNotification(
                    'تبرع عيني جديد 🎁',
                    "تم تسجيل تبرع عيني جديد من {$donation->donor_name}، يرجى مراجعة التفاصيل.",
                    'info'
                ));
            }

            if ($donation->user_id) {
                $user = \App\Models\User::find($donation->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\GeneralNotification(
                        'تم تسجيل تبرعك',
                        'شكراً لك! تم تسجيل تبرعك العيني وسنتواصل معك قريباً لترتيب الاستلام.',
                        'success'
                    ));
                }
            }

            return response()->json(['status' => true, 'message' => 'تم تسجيل تبرعكم العيني بنجاح.', 'data' => $donation], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Make Donation Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500);
        }
    }

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
            \Illuminate\Support\Facades\Log::warning('Paymob HMAC Check Failed!');
            return response()->json(['message' => 'HMAC validation failed'], 403);
        }

        $orderId = $data['order']['id'];
        $donation = \App\Models\Donation::where('paymob_order_id', $orderId)->first();

        if ($donation && $donation->status === 'pending') {
            if ($data['success'] === 'true' || $data['success'] === true) {
                // 2. تحديث التبرع إلى ناجح
                $donation->update([
                    'payment_status'        => 'paid',
                    'status'                => 'completed',
                    'paymob_transaction_id' => $data['id']
                ]);

                // 🎯 جلب بيانات الحالة (إذا وجدت) والمؤسسة والمستخدم
                $case = $donation->case_id ? \App\Models\FoundationCase::find($donation->case_id) : null;
                $foundation = \App\Models\Foundation::find($donation->foundation_id);
                $user = $donation->user_id ? \App\Models\User::find($donation->user_id) : null;

                // 🔔 إشعار 1: للمستخدم (نص مطابق للصورة تماماً)
                if ($user) {
                    $caseName = $case ? "لحالة '{$case->title}' " : "للمؤسسة ";
                    $user->notify(new \App\Notifications\GeneralNotification(
                        'تم استلام تبرعك بنجاح',
                        "شكراً لك! تبرعك {$caseName}تم بنجاح ووصل إلى المستفيد.",
                        'success'
                    ));
                }

                // 🔔 إشعار 2: للمؤسسة (تأكيد استلام الأموال)
                if ($foundation) {
                    $foundation->notify(new \App\Notifications\GeneralNotification(
                        'تبرع مالي جديد 💰',
                        "تم استلام تبرع مالي بقيمة {$donation->amount} جنيه من {$donation->donor_name} بنجاح.",
                        'success'
                    ));
                }

                // 🎯 3. التحديث الذكي: إغلاق الحالة إذا اكتمل المبلغ
                if ($case && in_array($case->goal_type, ['financial', 'both']) && $case->target_amount > 0) {
                    $totalCollected = $case->donations()->where('status', 'completed')->sum('amount');

                    if ($totalCollected >= $case->target_amount) {
                        $case->update(['status' => 'completed']);

                        // 🔔 إشعار 3: للمؤسسة (اكتمال الحالة)
                        if ($foundation) {
                            $foundation->notify(new \App\Notifications\GeneralNotification(
                                'اكتملت الحالة! 🎉',
                                "تهانينا! تم جمع المبلغ المطلوب بالكامل لحالة '{$case->title}'.",
                                'success'
                            ));
                        }
                    }
                }
            } else {
                // فشل الدفع
                $donation->update(['payment_status' => 'failed', 'status' => 'cancelled']);

                // 🔔 إشعار الفشل للمستخدم
                if ($donation->user_id) {
                    $user = \App\Models\User::find($donation->user_id);
                    if ($user) {
                        $user->notify(new \App\Notifications\GeneralNotification(
                            'فشل عملية الدفع',
                            'عذراً، لم نتمكن من إتمام عملية الدفع الخاصة بتبرعك. يرجى المحاولة مرة أخرى.',
                            'warning'
                        ));
                    }
                }
            }
        }

        return response()->json(['message' => 'Processed'], 200);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // 1. حساب الإحصائيات العلوية (المربعات الثلاثة)
            // إجمالي التبرعات المالية المكتملة
            $totalAmount = Donation::where('user_id', $userId)
                ->where('donation_type', 'financial')
                ->where('status', 'completed')
                ->sum('amount');

            // عدد العمليات (التبرعات الكلية)
            $totalOperations = Donation::where('user_id', $userId)->count();

            // جلب تاريخ آخر تبرع لحساب "منذ يومين" مثلاً
            $lastDonation = Donation::where('user_id', $userId)->latest()->first();
            $lastDonationTime = $lastDonation ? $lastDonation->created_at->diffForHumans() : 'لا يوجد تفاعل بعد';

            // أكبر تبرع مالي
            $largestDonationRecord = Donation::with('foundation')
                ->where('user_id', $userId)
                ->where('donation_type', 'financial')
                ->where('status', 'completed')
                ->orderByDesc('amount')
                ->first();

            $largestDonationAmount = $largestDonationRecord ? $largestDonationRecord->amount : 0;
            $largestDonationFoundation = ($largestDonationRecord && $largestDonationRecord->foundation)
                ? $largestDonationRecord->foundation->name
                : 'غير محدد';

            $stats = [
                'total_amount'                => $totalAmount,
                'total_operations'            => $totalOperations,
                'last_donation_time'          => $lastDonationTime,
                'largest_donation_amount'     => $largestDonationAmount,
                'largest_donation_foundation' => 'لجمعية ' . $largestDonationFoundation,
            ];

            // 2. بناء الاستعلام الأساسي للجدول
            $query = Donation::with(['foundation:id,name', 'foundationCase:id,title'])
                ->where('user_id', $userId);

            // -- الفلترة (بحث بالاسم، نوع التبرع، أو التاريخ) --
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('donor_name', 'like', "%{$search}%")
                        ->orWhereHas('foundation', function ($fQ) use ($search) {
                            $fQ->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('type') && in_array($request->type, ['financial', 'in-kind'])) {
                $query->where('donation_type', $request->type);
            }

            // 3. جلب البيانات دفعة واحدة (بدون Pagination)
            $donations = $query->orderBy('created_at', 'desc')->get();

            // 4. تنسيق البيانات لتتطابق مع تصميم الجدول في الصورة
            $donations->transform(function ($donation) {

                // تحديد التفاصيل بناءً على نوع التبرع
                $isFinancial = $donation->donation_type === 'financial';

                // عنوان التفاصيل (مثال: 500 ج.م أو مواد غذائية)
                $detailsTitle = $isFinancial
                    ? number_format($donation->amount) . ' ج.م'
                    : ($donation->item_category ?? 'مواد عينية');

                // وصف التفاصيل الفرعي
                if ($isFinancial) {
                    $targetName = $donation->foundationCase ? $donation->foundationCase->title : ($donation->foundation->name ?? 'مؤسسة عامة');
                    $detailsSubtitle = "تبرع نقدي لصالح " . $targetName;
                } else {
                    $detailsSubtitle = $donation->item_description ?? 'بدون تفاصيل إضافية';
                }

                // طريقة الدفع / التسليم
                $method = $isFinancial
                    ? ($donation->payment_method ?? 'غير محدد')
                    : ($donation->delivery_method ?? 'غير محدد');

                // ترجمة الحالة لتلوين الشارة
                $statusAr = match ($donation->status) {
                    'completed' => 'مكتمل',
                    'pending'   => 'قيد المراجعة',
                    'processing' => 'جاري', // تظهر في الصورة كـ "جاري"
                    'cancelled' => 'ملغي',
                    default     => 'غير محدد'
                };

                return [
                    'id'               => $donation->id,
                    'foundation_name'  => $donation->foundation->name ?? 'مؤسسة محذوفة',
                    'donor_name'       => $donation->donor_name ?? 'فاعل خير',

                    'type_en'          => $donation->donation_type, // للفرونت إند لمعرفة الأيقونة
                    'type_ar'          => $isFinancial ? 'مالي' : 'عيني',

                    'details_title'    => $detailsTitle,
                    'details_subtitle' => $detailsSubtitle,

                    'method'           => $method, // مثال: بطاقة ائتمان / توصيل للمقر

                    'date'             => Carbon::parse($donation->created_at)->translatedFormat('d F Y'), // مثال: 20 أكتوبر 2023

                    'status_en'        => $donation->status,
                    'status_ar'        => $statusAr,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب سجل التبرعات بنجاح.',
                'data'    => [
                    'stats'     => $stats,
                    'donations' => $donations // مصفوفة عادية تحتوي على التبرعات مباشرة
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            Log::error("API User Donations Index Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب سجل التبرعات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: جلب تفاصيل تبرع محدد (إيصال التبرع)
     */
public function show(Request $request, $id): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // 1. جلب التبرع مع العلاقات (المؤسسة والحالة)
            $donation = Donation::with(['foundation', 'foundationCase'])
                ->where('id', $id)
                ->where('user_id', $userId) // التأكد أن التبرع يخص المستخدم الحالي
                ->first();

            if (!$donation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'التبرع غير موجود أو لا تملك صلاحية الوصول إليه.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. حساب المبالغ المالية (بناءً على الصورة: يتم إضافة 1% كرسوم خدمة)
            $isFinancial = $donation->donation_type === 'financial';
            $baseAmount  = $isFinancial ? $donation->amount : 0;
            $feeAmount   = $isFinancial ? ($baseAmount * 0.01) : 0; // 1% رسوم خدمة افتراضية
            $totalAmount = $baseAmount + $feeAmount;

            // 3. ترجمة حالة التبرع
            $statusAr = match ($donation->status) {
                'completed'  => 'مكتمل',
                'pending'    => 'قيد المراجعة',
                'processing' => 'جاري',
                'cancelled'  => 'ملغي',
                default      => 'غير محدد'
            };

            // 4. ترجمة طريقة الدفع / التسليم
            if ($isFinancial) {
                $methodAr = match ($donation->payment_method) {
                    'paymob'        => 'بطاقة ائتمانية',
                    'bank_transfer' => 'تحويل بنكي',
                    'cash'          => 'دفع نقدي',
                    default         => 'بطاقة ائتمانية'
                };
            } else {
                $methodAr = match ($donation->delivery_method) {
                    'home_pickup'      => 'توصيل للمقر',
                    'branch_dropoff'   => 'تسليم يدوي',
                    'collection_point' => 'نقطة تجميع',
                    default            => 'تسليم يدوي'
                };
            }

            // 5. توليد رقم مرجعي مميز للإيصال (مثال: REF-0001234)
            $referenceNumber = 'REF-' . str_pad($donation->id, 7, '0', STR_PAD_LEFT);

            // 6. هيكلة البيانات لتطابق كارت "إيصال التبرع" في الفرونت إند تماماً
            $receiptData = [
                'id'               => $donation->id,
                'case_id'          => $donation->case_id, // 🎯 تمت إضافة معرف الحالة
                'foundation_id'    => $donation->foundation_id, // 🎯 تمت إضافة معرف المؤسسة
                'reference_number' => $referenceNumber,
                'status_en'        => $donation->status,
                'status_ar'        => $statusAr,
                'donation_type'    => $donation->donation_type,

                // بيانات المنصة العلوية
                'platform' => [
                    'name'    => 'نبضة خير',
                    'license' => '12345678',
                ],

                // القسم الرمادي (التفاصيل المالية - تُعرض كأصفار إذا كان التبرع عينياً)
                'financials' => [
                    'base_amount'  => number_format($baseAmount, 0),
                    'fee_amount'   => number_format($feeAmount, 0),
                    'total_amount' => number_format($totalAmount, 0),
                    'currency'     => 'ج.م',
                ],

                // 🎯 إضافة كافة التفاصيل إذا كان التبرع عينياً (In-Kind)
                'in_kind_details' => !$isFinancial ? [
                    'category'    => $donation->item_category ?? 'غير محدد',
                    'description' => $donation->item_description ?? 'لا يوجد وصف',
                    'condition'   => $donation->item_condition ?? 'غير محدد',
                    'address'     => $donation->donor_address ?? 'غير محدد',
                    'pickup_time' => $donation->pickup_time ? \Carbon\Carbon::parse($donation->pickup_time)->translatedFormat('d F Y, h:i A') : 'غير محدد',
                    'image_url'   => $donation->donation_image ? asset('storage/' . $donation->donation_image) : null,
                ] : null,

                'payment_method' => $methodAr, // تُستخدم كطريقة الدفع أو التسليم

                // تفاصيل المتبرع والجهة
                'donor' => [
                    'name'  => $donation->donor_name ?? 'فاعل خير',
                    'phone' => $donation->donor_phone ?? 'غير متوفر',
                ],
                'recipient_name' => $donation->foundation->name ?? 'مؤسسة عامة',
                'date'           => \Carbon\Carbon::parse($donation->created_at)->translatedFormat('d F Y'),

                // المشروع والحملة
                'project_name'   => $donation->foundationCase ? $donation->foundationCase->title : 'تبرع عام',
                // التأكد بأمان من وجود الحالة قبل محاولة قراءة التصنيف
                'campaign_name'  => $donation->foundationCase ? ($donation->foundationCase->category ?? 'حملة عامة') : 'حملة عامة',
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الإيصال بنجاح.',
                'data'    => $receiptData
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API User Donation Details Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب تفاصيل الإيصال.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
