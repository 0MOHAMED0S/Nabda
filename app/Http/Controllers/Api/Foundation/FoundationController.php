<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\FoundationCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon; // 🎯 ضروري جداً لحساب التواريخ والأيام المتبقية
use Exception;

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
    public function show($id)
    {
        try {
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
                ], 404);
            }

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

            $data['logo_url']               = $foundation->logo ? asset('storage/' . $foundation->logo) : null;
            $data['cover_image_url']        = $foundation->cover_image ? asset('storage/' . $foundation->cover_image) : null;
            $data['headquarters_image_url'] = $foundation->headquarters_image ? asset('storage/' . $foundation->headquarters_image) : null;

            if (isset($data['team_members'])) {
                foreach ($data['team_members'] as &$member) {
                    $member['image_url'] = !empty($member['image']) ? asset('storage/' . $member['image']) : null;
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل المؤسسة بنجاح.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            Log::error("API Get Foundation Public Details Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات المؤسسة.',
            ], 500);
        }
    }

    /**
     * API: جلب جميع الحالات النشطة التابعة لمؤسسة معينة (مخصص لقائمة الحالات داخل بروفايل المؤسسة)
     */
    public function getFoundationCases($id)
    {
        try {
            $foundation = Foundation::where('status', 'active')->where('approval_status', 'approved')->find($id);

            if (!$foundation) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المؤسسة غير موجودة أو غير معتمدة حالياً.',
                    'data'    => []
                ], 404);
            }

            $cases = FoundationCase::with('foundation:id,name,logo')
                ->where('foundation_id', $id)
                ->where('status', 'active')
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('priority', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($cases->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد حالات إنسانية نشطة حالياً لهذه المؤسسة.',
                    'data'    => []
                ], 200);
            }

            $data = $cases->map(function ($case) {
                $collected  = $case->collected_amount ?? 0;
                $target     = $case->target_amount;
                $percentage = ($case->goal_type === 'financial' && $target > 0) ? min(100, round(($collected / $target) * 100)) : 0;
                $firstImage = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;

                return [
                    'id'                    => $case->id,
                    'title'                 => $case->title,
                    'short_description'     => Str::limit($case->main_description, 60, '...'),
                    'campaign_type'         => $case->campaign_type,
                    'priority'              => $case->priority,
                    'is_urgent'             => $case->priority === 'urgent',
                    'target_amount'         => $target,
                    'collected_amount'      => $collected,
                    'completion_percentage' => $percentage,
                    'image_url'             => $firstImage ? asset('storage/' . $firstImage) : null,
                    'foundation_name'       => $case->foundation->name ?? '',
                    'foundation_logo_url'   => ($case->foundation && $case->foundation->logo) ? asset('storage/' . $case->foundation->logo) : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الحالات بنجاح.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            Log::error("API Get Foundation Cases Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالات.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * API: جلب التفاصيل الكاملة لحالة واحدة بعينها (لصفحة التبرع للحالة - الصور والمستندات والقصة)
     */
    public function getCaseDetails($caseId)
    {
        try {
            // 1. استعلام فائق السرعة يجلب الحالة، بيانات المؤسسة، وإجماليات التبرعات
            $case = FoundationCase::with('foundation:id,name,logo')
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
                ], 404);
            }

            // 2. العمليات الحسابية
            $collected  = $case->collected_amount ?? 0;
            $target     = $case->target_amount;
            $percentage = ($case->goal_type === 'financial' && $target > 0) ? min(100, round(($collected / $target) * 100)) : 0;

            // 3. حساب الوقت المتبقي
            $remainingDays = 0;
            $endDateFormatted = null;
            if ($case->end_date) {
                $endDate = Carbon::parse($case->end_date);
                $remainingDays = max(0, now()->diffInDays($endDate, false));
                $endDateFormatted = $endDate->format('d/m/Y');
            }

            // 4. جلب أحدث 5 متبرعين للقائمة الجانبية
            $recentDonors = $case->donations()
                ->where('status', 'completed')
                ->latest()
                ->take(5)
                ->get(['id', 'donor_name', 'amount', 'created_at'])
                ->map(function ($donor) {
                    return [
                        'name'     => $donor->donor_name ?? 'فاعل خير',
                        'amount'   => $donor->amount,
                        'time_ago' => $donor->created_at->diffForHumans(),
                    ];
                });

            // 5. تهيئة الصور
            $images = is_array($case->images) ? array_map(fn($img) => asset('storage/' . $img), $case->images) : [];
            $mainImage = count($images) > 0 ? $images[0] : null;

            // 🎯 6. التصحيح: تهيئة المستندات المرفقة (فك الـ JSON array وتحويلها لروابط)
            $formattedDocuments = [];
            if (is_array($case->documents)) {
                foreach ($case->documents as $doc) {
                    $formattedDocuments[] = asset('storage/' . $doc);
                }
            }

            // 7. تشكيل الاستجابة النهائية
            $data = [
                'id'                    => $case->id,
                'title'                 => $case->title,

                // تم التصحيح: جلب العنوان الفعلي من الموديل
                'location'              => $case->beneficiary_address ?? 'غير محدد',

                'campaign_type'         => $case->campaign_type,
                'foundation_name'       => $case->foundation->name ?? 'مؤسسة غير معروفة',
                'foundation_logo_url'   => ($case->foundation && $case->foundation->logo) ? asset('storage/' . $case->foundation->logo) : null,
                'is_active'             => $case->status === 'active',
                'is_verified'           => true,
                'target_amount'         => $target,
                'collected_amount'      => $collected,
                'completion_percentage' => $percentage,
                'remaining_days'        => ceil($remainingDays),
                'end_date'              => $endDateFormatted,
                'total_donors_count'    => $case->donors_count ?? 0,
                'recent_donors'         => $recentDonors,
                'story'                 => $case->main_description,
                'additional_note'       => $case->additional_description, // تم إضافته ليعرض أسفل القصة
                'main_image'            => $mainImage,
                'gallery'               => $images,

                // 🎯 تم التصحيح: الاعتماد على حقول الموديل الفعلي
                'attachments'           => [
                    // سيرجع مصفوفة من روابط ملفات الـ PDF المرفقة في الحقل documents
                    'document_files'    => $formattedDocuments,

                    // إذا كان الفيديو مرفوعاً سيرجع الرابط، أو يرجع رابط يوتيوب إذا كان نصياً
                    'video_url'         => $case->video ? (Str::startsWith($case->video, ['http://', 'https://']) ? $case->video : asset('storage/' . $case->video)) : null,
                ]
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الحالة بنجاح.',
                'data'    => $data
            ], 200);
        } catch (Exception $e) {
            Log::error("API Get Public Case Details Error (ID: {$caseId}): " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالة.',
                'data'    => null
            ], 500);
        }
    }
}
