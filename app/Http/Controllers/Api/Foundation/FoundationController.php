<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\FoundationCase;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon; // 🎯 ضروري جداً لحساب التواريخ والأيام المتبقية
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoundationController extends Controller
{
    /**
     * API: جلب جميع المؤسسات المعتمدة والنشطة (العرض المختصر للقائمة)
     */
    public function index()
    {
        try {
            $foundations = Foundation::where('approval_status', 'approved')
                ->where('status', 'active')
                ->select([
                    'id',
                    'name',
                    'about_desc_1',
                    'type',
                    'logo',
                    'cover_image',
                    'main_address',
                    'contact_email',
                    'contact_phone',
                    'license_number',
                    'supervising_authority',
                    'approval_status'
                ])
                ->withCount(['cases as active_cases_count' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            if ($foundations->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد مؤسسات معتمدة ونشطة حالياً.',
                    'data'    => []
                ], 200);
            }

            $data = $foundations->map(function ($foundation) {
                return [
                    'id'                    => $foundation->id,
                    'name'                  => $foundation->name,
                    'short_description'     => $foundation->about_desc_1 ? Str::limit($foundation->about_desc_1, 80, '...') : '',
                    'type'                  => $foundation->type,
                    'main_address'          => $foundation->main_address,
                    'contact_email'         => $foundation->contact_email,
                    'contact_phone'         => $foundation->contact_phone,
                    'license_number'        => $foundation->license_number,
                    'supervising_authority' => $foundation->supervising_authority,
                    'approval_status'       => $foundation->approval_status,
                    'active_cases_count'    => $foundation->active_cases_count ?? 0,
                    'logo_url'              => $foundation->logo ? asset('storage/' . $foundation->logo) : null,
                    'cover_image_url'       => $foundation->cover_image ? asset('storage/' . $foundation->cover_image) : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب قائمة المؤسسات بنجاح.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            Log::error("API Get Public Foundations Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات المؤسسات.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * API: جلب كافة تفاصيل مؤسسة واحدة معتمدة (العرض الشامل للبروفايل)
     */
public function show($id): JsonResponse
    {
        try {
            // 1. جلب المؤسسة مع العلاقات المباشرة (الفريق، الأسئلة الشائعة، الأهداف، الفروع)
            $foundation = Foundation::with([
                'teamMembers' => function ($query) {
                    $query->where('status', 'active');
                },
                'faqs' => function ($query) {
                    $query->where('status', 'published');
                },
                'goals',
                'branches'
            ])
                ->where('approval_status', 'approved')
                ->where('status', 'active')
                ->find($id);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة أو غير معتمدة حالياً.',
                    'data'    => null
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. إخفاء البيانات الحساسة
            $foundation->makeHidden([
                'password',
                'remember_token',
                'license_image',
                'commercial_register',
                'tax_card',
                'accreditation_letter',
                'logo',
                'cover_image',
                'headquarters_image'
            ]);

            $data = $foundation->toArray();

            // 3. تهيئة الروابط الكاملة للصور الأساسية
            $data['logo_url']               = $foundation->logo ? asset('storage/' . $foundation->logo) : null;
            $data['cover_image_url']        = $foundation->cover_image ? asset('storage/' . $foundation->cover_image) : null;
            $data['headquarters_image_url'] = $foundation->headquarters_image ? asset('storage/' . $foundation->headquarters_image) : null;

            // تهيئة صور فريق العمل
            if (isset($data['team_members'])) {
                foreach ($data['team_members'] as &$member) {
                    $member['image_url'] = !empty($member['image']) && !str_starts_with($member['image'], 'http')
                        ? asset('storage/' . $member['image'])
                        : ($member['image'] ?? null);
                    unset($member['image']);
                }
            }

            // ==========================================
            // 4. قسم "الأثر بالأرقام" (Impact Stats)
            // ==========================================
            // عدد الحالات
            $casesCount = \App\Models\FoundationCase::where('foundation_id', $id)->count();

            // عدد الحملات / الفرص التطوعية
            $campaignsCount = \App\Models\VolunteerOpportunity::where('foundation_id', $id)->count();

            // إجمالي المساعدات (التبرعات المكتملة)
            $totalDonations = \App\Models\Donation::where('foundation_id', $id)->where('status', 'completed')->sum('amount');

            // عدد المتطوعين الفريدين المقبولين في فرص هذه المؤسسة
            $opportunitiesIds = \App\Models\VolunteerOpportunity::where('foundation_id', $id)->pluck('id');
            $volunteersCount = \Illuminate\Support\Facades\DB::table('opportunity_volunteer')
                ->whereIn('volunteer_opportunity_id', $opportunitiesIds)
                ->whereIn('status', ['accepted', 'attended'])
                ->distinct('volunteer_id')
                ->count('volunteer_id');

            $data['impact_stats'] = [
                'cases_count'      => $casesCount,
                'campaigns_count'  => $campaignsCount,
                'volunteers_count' => $volunteersCount,
                'total_donations'  => $totalDonations,
            ];

            // ==========================================
            // 5. قسم "آراء المتبرعين والمشاركين" (Ratings)
            // ==========================================
            // جلب التقييمات المرتبطة بهذه المؤسسة (مفترض وجود موديل FoundationRating)
            // واستدعاء صورة المستخدم إن وجدت
            $ratings = \App\Models\FoundationRating::where('foundation_id', $id)
                ->where('is_approved', true) // 🎯 عرض التقييمات الموافق عليها فقط
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($rating) {
                    // إذا كان التقييم مسجلاً باسم مستخدم في النظام، نجلب صورته، وإلا نرسل null ليضع الفرونت إند صورة افتراضية
                    $user = $rating->user_id ? \App\Models\User::find($rating->user_id) : null;

                    // 🎯 معالجة صورة المستخدم سواء كانت محلية أو من جوجل
                    $avatarUrl = null;
                    if ($user && $user->avatar) {
                        $avatarUrl = filter_var($user->avatar, FILTER_VALIDATE_URL)
                            ? $user->avatar
                            : asset('storage/' . $user->avatar);
                    }

                    return [
                        'id'         => $rating->id,
                        'name'       => $rating->name ?? 'فاعل خير',
                        'avatar_url' => $avatarUrl,
                        'rating'     => $rating->rating,
                        'message'    => $rating->message,
                        'date'       => \Carbon\Carbon::parse($rating->created_at)->translatedFormat('d F Y')
                    ];
                });

            $averageRating = $ratings->count() > 0 ? round($ratings->avg('rating'), 1) : 0;

            $data['reviews'] = [
                'average_rating' => $averageRating,
                'total_reviews'  => $ratings->count(),
                'list'           => $ratings
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل المؤسسة بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Foundation Public Details Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات المؤسسة.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: جلب جميع الحالات النشطة التابعة لمؤسسة معينة (مخصص لقائمة الحالات داخل بروفايل المؤسسة)
     */
public function getFoundationCases(Request $request, $id): JsonResponse
    {
        try {
            // 1. التأكد من وجود المؤسسة واعتمادها
            $foundation = Foundation::where('status', 'active')->where('approval_status', 'approved')->find($id);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة أو غير معتمدة حالياً.',
                    'data'    => null
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. 🎯 جلب الحالات مع حساب التبرعات وجلب علاقة المؤسسة + (الخدمة وتصنيفها)
            $cases = FoundationCase::with(['foundation:id,name,logo', 'service.category'])
                ->where('foundation_id', $id)
                ->where('status', 'active')
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('priority', 'asc') // الحالات العاجلة أولاً
                ->orderBy('created_at', 'desc')
                ->get(); // جلب دفعة واحدة بدون Pagination

            // 3. تهيئة البيانات وتنسيقها للواجهة (Frontend)
            $cases->transform(function ($case) {
                $collected  = $case->collected_amount ?? 0;
                $target     = $case->target_amount;

                // حساب النسبة المئوية
                $percentage = ($case->goal_type === 'financial' && $target > 0)
                    ? min(100, round(($collected / $target) * 100))
                    : 0;

                // استخراج رابط الصورة الكامل بأمان
                $firstImage = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;
                $imageUrl = !empty($firstImage) && !str_starts_with($firstImage, 'http')
                    ? asset('storage/' . $firstImage)
                    : $firstImage;

                $logoUrl = ($case->foundation && $case->foundation->logo) && !str_starts_with($case->foundation->logo, 'http')
                    ? asset('storage/' . $case->foundation->logo)
                    : ($case->foundation->logo ?? null);

                // تحديد ما إذا كانت الحالة عاجلة
                $isUrgent = in_array(strtolower($case->priority), ['urgent', 'عاجل', 'high', 'عالية']);

                // 🎯 استخراج بيانات الخدمة والتصنيف بأمان
                $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'غير مصنف';
                $serviceTitle    = $case->service ? $case->service->title : 'خدمة عامة';

                return [
                    'id'                    => $case->id,
                    'title'                 => $case->title,
                    'short_description'     => \Illuminate\Support\Str::limit($case->main_description, 70, '...'),

                    // 🎯 تعويض campaign_type المحذوف باسم التصنيف (مثال: كفالة، إسكان)
                    'category'              => $serviceCategory,
                    'service_name'          => $serviceTitle, // إضافة اسم الخدمة كمعلومة إضافية

                    // شارات وتنسيقات جاهزة للطباعة
                    'is_urgent'             => $isUrgent,
                    'urgency_badge'         => $isUrgent ? 'عاجلة' : 'غير عاجلة',
                    'currency'              => 'جنيه',

                    // الأرقام لشرائط التقدم
                    'target_amount'         => $target,
                    'collected_amount'      => $collected,
                    'completion_percentage' => $percentage,

                    // الصور والمؤسسة
                    'image_url'             => $imageUrl,
                    'foundation_name'       => $case->foundation->name ?? '',
                    'foundation_logo_url'   => $logoUrl,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الحالات بنجاح.',
                'data'    => $cases
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Foundation Cases Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     * API: جلب التفاصيل الكاملة لحالة واحدة بعينها (لصفحة التبرع للحالة - الصور والمستندات والقصة)
     */
public function getCaseDetails($caseId): JsonResponse
    {
        try {
            // 1. استعلام فائق السرعة يجلب الحالة، المؤسسة، التحديثات، إجماليات التبرعات، والخدمة وتصنيفها
            $case = FoundationCase::with([
                'foundation:id,name,logo',
                'updates',
                'service.category' // 🎯 جلب علاقة الخدمة وتصنيفها
            ])
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->withCount(['donations as donors_count' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->where('status', 'active')
                ->find($caseId);

            if (!$case) {
                return response()->json([
                    'status'  => false,
                    'message' => 'هذه الحالة غير موجودة أو تم إغلاقها.',
                    'data'    => null
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. العمليات الحسابية
            $collected  = $case->collected_amount ?? 0;
            $target     = $case->target_amount;
            $percentage = ($case->goal_type === 'financial' && $target > 0) ? min(100, round(($collected / $target) * 100)) : 0;

            // 3. حساب الوقت المتبقي وتاريخ الانتهاء
            $remainingDays = 0;
            $endDateFormatted = null;
            if ($case->end_date) {
                $endDate = Carbon::parse($case->end_date);
                $remainingDays = max(0, now()->diffInDays($endDate, false));
                $endDateFormatted = $endDate->format('d/m');
            }

            // 4. جلب أحدث المتبرعين للقائمة الجانبية (15 متبرع)
            $recentDonors = $case->donations()
                ->where('status', 'completed')
                ->latest()
                ->take(15)
                ->get(['id', 'donor_name', 'amount', 'created_at'])
                ->map(function ($donor) {
                    return [
                        'name'     => $donor->donor_name ?? 'فاعل خير',
                        'amount'   => $donor->amount,
                        'time_ago' => $donor->created_at->diffForHumans(),
                    ];
                });

            // 5. تهيئة صور الحالة
            $images = is_array($case->images) ? array_map(fn($img) => asset('storage/' . $img), $case->images) : [];
            $mainImage = count($images) > 0 ? $images[0] : null;

            // 6. تهيئة المستندات المرفقة (ملفات الـ PDF)
            $formattedDocuments = [];
            if (is_array($case->documents)) {
                foreach ($case->documents as $doc) {
                    $formattedDocuments[] = asset('storage/' . $doc);
                }
            }

            // 7. تهيئة تحديثات الحالة (التايم لاين)
            $formattedUpdates = [];
            if ($case->updates) {
                $formattedUpdates = $case->updates->map(function ($update) {
                    return [
                        'id'          => $update->id,
                        'title'       => $update->title ?? 'تحديث جديد',
                        'description' => $update->content ?? $update->description ?? '',
                        'date'        => Carbon::parse($update->update_date ?? $update->created_at)->translatedFormat('l, d F Y')
                    ];
                });
            }

            // 🎯 8. استخراج بيانات الخدمة والتصنيف بأمان
            $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'غير مصنف';
            $serviceTitle    = $case->service ? $case->service->title : 'خدمة عامة';

            // 9. تشكيل الاستجابة النهائية
            $data = [
                'id'                    => $case->id,
                'case_number'           => '#' . $case->id,
                'title'                 => $case->title,
                'location'              => $case->beneficiary_address ?? 'غير محدد',

                // 🎯 تعويض campaign_type المحذوف باسم التصنيف للحفاظ على التوافق مع الواجهة
                'category'              => $serviceCategory,

                // 🎯 إضافة بيانات الخدمة والتصنيف كحقول مستقلة
                'service_name'          => $serviceTitle,
                'service_category'      => $serviceCategory,

                'is_active'             => $case->status === 'active',
                'is_verified'           => true,

                'foundation_name'       => $case->foundation->name ?? 'مؤسسة غير معروفة',
                'foundation_logo_url'   => ($case->foundation && $case->foundation->logo) ? asset('storage/' . $case->foundation->logo) : null,

                'age'                   => $case->beneficiary_age ?? 'غير محدد',
                'target_amount'         => $target,
                'collected_amount'      => $collected,
                'completion_percentage' => $percentage,

                'remaining_days'        => ceil($remainingDays),
                'end_date'              => $endDateFormatted,

                'total_donors_count'    => $case->donors_count ?? 0,
                'recent_donors'         => $recentDonors,

                'story'                 => $case->main_description,
                'additional_note'       => $case->additional_description,

                'main_image'            => $mainImage,
                'gallery'               => $images,

                'attachments'           => [
                    'document_files'    => $formattedDocuments,
                    'video_url'         => $case->video ? (Str::startsWith($case->video, ['http://', 'https://']) ? $case->video : asset('storage/' . $case->video)) : null,
                ],

                'updates'               => $formattedUpdates
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الحالة بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            Log::error("API Get Public Case Details Error (ID: {$caseId}): " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالة.',
                'data'    => null
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }


/**
     * API: جلب خدمات / حملات المؤسسة (بدون شريط التقدم المالي - لتبويب الخدمات) - بدون Pagination
     */
public function getFoundationServices(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            // 1. التأكد من وجود المؤسسة واعتمادها
            $foundation = \App\Models\Foundation::where('status', 'active')->where('approval_status', 'approved')->find($id);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة أو غير معتمدة حالياً.',
                    'data'    => []
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. 🎯 جلب أرقام الخدمات المرتبطة بحالات المؤسسة النشطة بشكل فريد
            $serviceIds = \App\Models\FoundationCase::where('foundation_id', $id)
                ->where('status', 'active')
                ->whereNotNull('service_id')
                ->distinct()
                ->pluck('service_id');

            // 3. جلب بيانات هذه الخدمات مع التصنيفات التابعة لها وحساب عدد الحالات
            $services = \App\Models\Service::with('category')
                ->whereIn('id', $serviceIds)
                // 🎯 إضافة حساب عدد الحالات النشطة التابعة لهذه المؤسسة وهذه الخدمة
                ->withCount(['foundationCases as cases_count' => function ($query) use ($id) {
                    $query->where('foundation_id', $id)
                          ->where('status', 'active');
                }])
                ->orderBy('created_at', 'desc')
                ->get(); // بدون Pagination

            // 4. تنسيق البيانات لتتطابق مع تصميم كارت الخدمة في الفرونت إند
            $services->transform(function ($service) {
                // استخراج رابط الصورة الكامل بأمان (الصورة في موديل الخدمة نص string)
                $imageUrl = !empty($service->image) && !str_starts_with($service->image, 'http')
                    ? asset('storage/' . $service->image)
                    : ($service->image ?? null);

                return [
                    'id'          => $service->id,
                    'title'       => $service->title,
                    // قص الوصف الطويل ليكون مناسباً لحجم الكارت
                    'description' => \Illuminate\Support\Str::limit($service->description, 80, '...'),
                    // 🎯 جلب اسم التصنيف من العلاقة، وإذا لم يوجد نكتب 'عام'
                    'category'    => $service->category->name ?? 'عام',
                    'image_url'   => $imageUrl,
                    // 🎯 إضافة عدد الحالات هنا
                    'cases_count' => $service->cases_count ?? 0,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الخدمات بنجاح.',
                'data'    => $services
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Foundation Services Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الخدمات.',
                'data'    => []
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

public function getContactDetails(Request $request, $id): JsonResponse
    {
        try {
            // 1. جلب المؤسسة مع فروعها
            $foundation = Foundation::with('branches')
                ->where('status', 'active')
                ->where('approval_status', 'approved')
                ->find($id);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة أو غير معتمدة حالياً.',
                    'data'    => null
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. تهيئة بيانات الفروع (معالجة حقل coordinates)
            $branches = $foundation->branches->map(function ($branch) {

                // 🎯 فصل حقل الإحداثيات إلى خط عرض وطول
                $lat = null;
                $lng = null;

                if (!empty($branch->coordinates)) {
                    // افتراض أن الإحداثيات محفوظة مفصولة بفاصلة مثل: "30.0444,31.2357"
                    $coords = explode(',', $branch->coordinates);
                    if (count($coords) >= 2) {
                        $lat = trim($coords[0]);
                        $lng = trim($coords[1]);
                    }
                }

                return [
                    'id'        => $branch->id,
                    'name'      => $branch->name, // مثال: فرع القاهرة
                    'address'   => $branch->address,
                    'phone'     => $branch->phone,
                    'email'     => $branch->email, // 🎯 أضفنا الإيميل لأنه موجود في الموديل لديك

                    // إحداثيات الخريطة (Leaflet / OpenStreetMap)
                    'latitude'  => $lat,
                    'longitude' => $lng,

                    // نرسل الحقل الأصلي احتياطياً
                    'coordinates' => $branch->coordinates
                ];
            });

            // 3. هيكلة الرد ليتطابق مع كروت الشاشة (Cards)
            $data = [
                'contact_info' => [
                    'phone'         => $foundation->phone ?? 'غير متوفر',
                    'email'         => $foundation->email ?? 'غير متوفر',
                    'working_hours' => $foundation->working_hours ?? 'الاثنين - السبت: 9:00 ص - 5:00 م',
                ],
                'branches' => $branches
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب معلومات الاتصال بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Foundation Contact Details Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الاتصال.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     * API: جلب جميع الحالات المرتبطة بخدمة معينة داخل مؤسسة محددة
     */
    public function getFoundationServiceCases(Request $request, $foundationId, $serviceId): JsonResponse
    {
        try {
            // 1. التأكد من وجود المؤسسة واعتمادها
            $foundation = Foundation::where('status', 'active')->where('approval_status', 'approved')->find($foundationId);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة أو غير معتمدة حالياً.',
                    'data'    => [] // إرجاع مصفوفة فارغة لتجنب أخطاء الواجهة
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. جلب الحالات المطابقة لشرطي المؤسسة والخدمة معاً
            $cases = FoundationCase::with(['foundation:id,name,logo', 'service.category'])
                ->where('foundation_id', $foundationId)
                ->where('service_id', $serviceId) // 🎯 فلترة إضافية برقم الخدمة
                ->where('status', 'active')
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('priority', 'asc') // الحالات العاجلة أولاً
                ->orderBy('created_at', 'desc')
                ->get(); // بدون Pagination (يمكنك استخدام paginate إن كانت الحالات كثيرة جداً)

            // 3. تهيئة البيانات وتنسيقها للواجهة (Frontend)
            $cases->transform(function ($case) {
                $collected  = $case->collected_amount ?? 0;
                $target     = $case->target_amount;

                // حساب النسبة المئوية
                $percentage = ($case->goal_type === 'financial' && $target > 0)
                    ? min(100, round(($collected / $target) * 100))
                    : 0;

                // استخراج رابط الصورة الكامل بأمان
                $firstImage = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;
                $imageUrl = !empty($firstImage) && !\Illuminate\Support\Str::startsWith($firstImage, ['http://', 'https://'])
                    ? asset('storage/' . $firstImage)
                    : $firstImage;

                $logoUrl = ($case->foundation && $case->foundation->logo) && !\Illuminate\Support\Str::startsWith($case->foundation->logo, ['http://', 'https://'])
                    ? asset('storage/' . $case->foundation->logo)
                    : ($case->foundation->logo ?? null);

                // تحديد ما إذا كانت الحالة عاجلة
                $isUrgent = in_array(strtolower($case->priority), ['urgent', 'عاجل', 'high', 'عالية']);

                // استخراج بيانات الخدمة والتصنيف بأمان
                $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'غير مصنف';
                $serviceTitle    = $case->service ? $case->service->title : 'خدمة عامة';

                return [
                    'id'                    => $case->id,
                    'foundation_id'         => $case->foundation_id,
                    'service_id'            => $case->service_id,
                    'title'                 => $case->title,
                    'short_description'     => \Illuminate\Support\Str::limit($case->main_description, 70, '...'),

                    'category'              => $serviceCategory,
                    'service_name'          => $serviceTitle,

                    // شارات وتنسيقات جاهزة للطباعة
                    'is_urgent'             => $isUrgent,
                    'urgency_badge'         => $isUrgent ? 'عاجلة' : 'غير عاجلة',
                    'currency'              => 'جنيه',

                    // الأرقام لشرائط التقدم
                    'target_amount'         => $target,
                    'collected_amount'      => $collected,
                    'completion_percentage' => $percentage,

                    // الصور والمؤسسة
                    'image_url'             => $imageUrl,
                    'foundation_name'       => $case->foundation->name ?? '',
                    'foundation_logo_url'   => $logoUrl,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب حالات الخدمة التابعة للمؤسسة بنجاح.',
                'data'    => $cases
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Foundation Service Cases Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب الحالات.',
                'data'    => []
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
