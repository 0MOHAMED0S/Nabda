<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\FoundationCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class PublicCaseController extends Controller
{
    /**
     * API 1: جلب جميع الحالات النشطة (لصفحة الحالات الرئيسية - الكروت) - بدون Pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // 1. جلب جميع الحالات النشطة مع علاقاتها الأساسية وإجمالي التبرعات
            $cases = FoundationCase::with(['foundation:id,name,logo', 'service.category'])
                ->where('status', 'active')
                ->whereHas('foundation', function ($q) {
                    $q->where('status', 'active')->where('approval_status', 'approved'); // ضمان أن المؤسسة معتمدة
                })
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('priority', 'asc') // الحالات العاجلة أولاً
                ->orderBy('created_at', 'desc')
                ->get(); // 👈 تم استخدام get بدلاً من paginate

            // 2. تنسيق البيانات لتتطابق مع تصميم "كارت الحالة" في الصورة الأولى
            $cases->transform(function ($case) { // 👈 تم إزالة getCollection()
                $collected  = $case->collected_amount ?? 0;
                $target     = $case->target_amount;

                // حساب النسبة المئوية
                $percentage = ($case->goal_type === 'financial' && $target > 0)
                    ? min(100, round(($collected / $target) * 100))
                    : 0;

                // استخراج رابط الصورة الكامل بأمان
                $firstImage = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;
                $imageUrl = !empty($firstImage) && !Str::startsWith($firstImage, ['http://', 'https://'])
                    ? asset('storage/' . $firstImage)
                    : $firstImage;

                $logoUrl = ($case->foundation && $case->foundation->logo) && !Str::startsWith($case->foundation->logo, ['http://', 'https://'])
                    ? asset('storage/' . $case->foundation->logo)
                    : ($case->foundation->logo ?? null);

                // تحديد ما إذا كانت الحالة عاجلة لطباعة شارة (عاجلة)
                $isUrgent = in_array(strtolower($case->priority), ['urgent', 'عاجل', 'high', 'عالية']);

                // استخراج بيانات التصنيف والخدمة
                $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'عام';

                return [
                    'id'                    => $case->id,
                    'foundation_id'         => $case->foundation_id, // 🎯 تمت إضافة رقم المؤسسة
                    'title'                 => $case->title,
                    'short_description'     => Str::limit($case->main_description, 70, '...'),

                    'category'              => $serviceCategory,

                    // شارات وتنسيقات
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

        } catch (Exception $e) {
            Log::error("API Get All Public Cases Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API 2: عرض تفاصيل الحالة بالكامل (للشاشة الفردية - الصورة الثانية)
     */
    public function show($id): JsonResponse
    {
        try {
            // 1. استعلام شامل يجلب الحالة بكل تفاصيلها (المؤسسة، التحديثات، الخدمة، التبرعات)
            $case = FoundationCase::with([
                'foundation:id,name,logo',
                'updates',
                'service.category'
            ])
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->withCount(['donations as donors_count' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->where('status', 'active')
                ->find($id);

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

            // 3. حساب الوقت المتبقي (لشريط الأيام)
            $remainingDays = 0;
            $endDateFormatted = null;
            if ($case->end_date) {
                $endDate = Carbon::parse($case->end_date);
                $remainingDays = max(0, now()->diffInDays($endDate, false));
                $endDateFormatted = $endDate->format('d/m');
            }

            // 4. جلب أحدث المتبرعين للقائمة الجانبية
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

            // 5. تهيئة صور ومستندات الحالة
            $images = is_array($case->images) ? array_map(fn($img) => asset('storage/' . $img), $case->images) : [];
            $mainImage = count($images) > 0 ? $images[0] : null;

            $formattedDocuments = [];
            if (is_array($case->documents)) {
                foreach ($case->documents as $doc) {
                    $formattedDocuments[] = asset('storage/' . $doc);
                }
            }

            // 6. تهيئة تحديثات الحالة (التايم لاين)
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

            // 7. استخراج بيانات الخدمة والتصنيف بأمان
            $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'غير مصنف';
            $serviceTitle    = $case->service ? $case->service->title : 'خدمة عامة';

            // 8. تشكيل الاستجابة النهائية
            $data = [
                'id'                    => $case->id,
                'foundation_id'         => $case->foundation_id, // 🎯 تمت إضافة رقم المؤسسة
                'case_number'           => '#' . $case->id,
                'title'                 => $case->title,
                'location'              => $case->beneficiary_address ?? 'غير محدد',

                'category'              => $serviceCategory,
                'service_name'          => $serviceTitle,
                'service_category'      => $serviceCategory,

                'is_active'             => $case->status === 'active',
                'is_verified'           => true, // شارة "موثق"

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
            Log::error("API Get Public Case Details Error (ID: {$id}): " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالة.',
                'data'    => null
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
