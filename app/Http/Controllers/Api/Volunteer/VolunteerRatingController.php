<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class VolunteerRatingController extends Controller
{
    /**
     * API: جلب تقييمات المتطوع وآراء المؤسسات (الإحصائيات العلوية + الكروت) بدون Pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // 1. الاستعلام الأساسي: جلب التقارير التي تم تقييمها فقط (تحتوي على rating)
            $baseQuery = VolunteerReport::where('volunteer_id', $volunteerId)
                ->whereNotNull('rating')
                ->where('status', 'approved');

            // 2. حساب الإحصائيات العلوية (متوسط التقييم وعدد التقييمات)
            $totalRatingsCount = (clone $baseQuery)->count();
            $averageRating     = $totalRatingsCount > 0 ? round((clone $baseQuery)->avg('rating'), 1) : 0;

            $stats = [
                'average_rating' => $averageRating,
                'total_reviews'  => $totalRatingsCount,
            ];

            // 3. جلب كروت التقييمات التفصيلية مع بيانات الفرصة والمؤسسة (دفعة واحدة)
            $reviews = $baseQuery->with([
                    'opportunity:id,title,foundation_id',
                    'opportunity.foundation:id,name'
                ])
                ->orderBy('updated_at', 'desc') // الأحدث أولاً بناءً على تاريخ التقييم
                ->get(); // 👈 استبدال paginate(9) بـ get()

            // 4. تنسيق وتهيئة البيانات لتكون نظيفة جداً للواجهة (Frontend)
            $reviews->transform(function ($report) { // 👈 استخدام transform مباشرة
                return [
                    'id'              => $report->id,
                    'foundation_name' => $report->opportunity->foundation->name ?? 'غير محدد',
                    'activity_title'  => $report->opportunity->title ?? 'غير محدد',
                    'rating'          => $report->rating,
                    'feedback'        => $report->feedback_message ?? 'لا توجد رسالة.',
                    // تنسيق التاريخ ليطابق الشاشة (مثال: 12 يناير 2026)
                    'date'            => Carbon::parse($report->updated_at)->translatedFormat('d F Y'),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب التقييمات بنجاح.',
                'data'    => [
                    'stats'   => $stats,
                    'reviews' => $reviews
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Fetch Ratings Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب التقييمات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
