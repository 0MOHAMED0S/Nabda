<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class VolunteerHoursController extends Controller
{
    /**
     * API: جلب سجل الساعات التطوعية بالكامل (الإحصائيات، التوزيع، والجدول التفصيلي) - بدون Pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // 1. جلب جميع التقارير المعتمدة (لأن الساعات لا تحسب إلا بعد الموافقة)
            $approvedReports = VolunteerReport::where('volunteer_id', $volunteerId)
                ->where('status', 'approved')
                ->with(['opportunity:id,title,date,foundation_id', 'opportunity.foundation:id,name'])
                ->get();

            // ==========================================
            // القسم الأول: الإحصائيات العلوية
            // ==========================================
            $totalHours = $approvedReports->sum('hours');

            // حساب عدد المؤسسات الشريكة (الفريدة) التي تطوع معها
            $uniqueFoundationsCount = $approvedReports->pluck('opportunity.foundation_id')->unique()->count();

            // حساب نقاط التأثير (بناءً على صورتك: 108 ساعة = 1080 نقطة، أي الضرب في 10)
            $impactPoints = $totalHours * 10;

            $stats = [
                'total_hours'        => $totalHours,
                'foundations_count'  => $uniqueFoundationsCount,
                'impact_points'      => $impactPoints,
            ];

            // ==========================================
            // القسم الثاني: توزيع المجهود (النسب المئوية)
            // ==========================================
            $effortDistribution = [];

            if ($totalHours > 0) {
                // تجميع التقارير حسب اسم المؤسسة
                $groupedByFoundation = $approvedReports->groupBy(function ($report) {
                    return $report->opportunity->foundation->name ?? 'مؤسسة غير معروفة';
                });

                foreach ($groupedByFoundation as $foundationName => $reports) {
                    $foundationHours = $reports->sum('hours');
                    $percentage = round(($foundationHours / $totalHours) * 100);

                    $effortDistribution[] = [
                        'foundation_name' => $foundationName,
                        'hours'           => $foundationHours,
                        'percentage'      => $percentage . '%' // جاهزة للفرونت إند للرسم
                    ];
                }

                // ترتيب التوزيع من الأعلى نسبة للأقل
                usort($effortDistribution, function($a, $b) {
                    return $b['hours'] <=> $a['hours'];
                });
            }

            // ==========================================
            // القسم الثالث: سجل الأنشطة التفصيلي (الجدول)
            // ==========================================
            // نجلب البيانات مرة أخرى بدون Pagination للجدول السفلي
            $detailedActivities = VolunteerReport::where('volunteer_id', $volunteerId)
                ->where('status', 'approved')
                ->with(['opportunity:id,title,date,foundation_id', 'opportunity.foundation:id,name'])
                ->orderBy('created_at', 'desc')
                ->get(); // 👈 تم استبدال paginate(10) بـ get()

            // تهيئة وتنسيق بيانات الجدول لتكون نظيفة جداً للواجهة
            $detailedActivities->transform(function ($report) { // 👈 تم استخدام transform مباشرة
                return [
                    'id'              => $report->id,
                    'activity_name'   => $report->opportunity->title ?? 'غير محدد',
                    'foundation_name' => $report->opportunity->foundation->name ?? 'غير محدد',
                    // استخدام تاريخ التقرير
                    'date'            => Carbon::parse($report->created_at)->translatedFormat('d F Y'), // مثال: 15 فبراير 2026
                    'hours'           => $report->hours,
                ];
            });

            // ==========================================
            // إرجاع الرد النهائي الشامل
            // ==========================================
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب سجل الساعات التطوعية بنجاح.',
                'data'    => [
                    'stats'               => $stats,
                    'effort_distribution' => $effortDistribution,
                    'detailed_record'     => $detailedActivities
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Hours Record Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب سجل الساعات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
