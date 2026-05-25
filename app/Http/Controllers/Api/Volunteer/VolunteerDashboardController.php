<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use App\Models\VolunteerReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class VolunteerDashboardController extends Controller
{
    /**
     * API: جلب بيانات لوحة التحكم الرئيسية للمتطوع (الإحصائيات، المستويات، أحدث الفرص، ومهامي)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $volunteer = $request->user();
            $volunteerId = $volunteer->id;

            // 1. جلب التقارير المعتمدة للمتطوع (لحساب الإحصائيات)
            $approvedReports = VolunteerReport::where('volunteer_id', $volunteerId)
                ->where('status', 'approved')
                ->with(['opportunity:id,category,foundation_id'])
                ->get();

            // ==========================================
            // القسم الأول: المربعات العلوية (الإحصائيات)
            // ==========================================
            $totalHours = $approvedReports->sum('hours');
            $completedTasks = $approvedReports->count();
            $foundationsCount = $approvedReports->pluck('opportunity.foundation_id')->unique()->count();
            $averageRating = $approvedReports->avg('rating') ? round($approvedReports->avg('rating'), 1) : 0;

            $stats = [
                'completed_tasks'   => $completedTasks,
                'total_hours'       => $totalHours,
                'foundations_count' => $foundationsCount,
                'average_rating'    => $averageRating,
            ];

            // ==========================================
            // القسم الثاني: نظام النقاط والمستويات (Gamification)
            // ==========================================
            // حساب النقاط (كل ساعة = 4 نقاط كمثال حسب صورتك، أو يمكنك جعلها 10 كما في الكود السابق)
            $points = $totalHours * 10;

            // تحديد المستوى برمجياً (مبتدئ -> نشط -> متميز -> خبير)
            $currentLevel = 'مبتدئ';
            $nextLevel = 'نشط';
            $nextLevelTargetHours = 50; // يحتاج 50 ساعة ليصل لـ "نشط"

            if ($totalHours >= 50 && $totalHours < 140) {
                $currentLevel = 'نشط';
                $nextLevel = 'متميز';
                $nextLevelTargetHours = 140; // يحتاج 140 ساعة ليصل لـ "متميز" كما في صورتك
            } elseif ($totalHours >= 140 && $totalHours < 300) {
                $currentLevel = 'متميز';
                $nextLevel = 'خبير';
                $nextLevelTargetHours = 300;
            } elseif ($totalHours >= 300) {
                $currentLevel = 'خبير';
                $nextLevel = 'سفير الخير';
                $nextLevelTargetHours = $totalHours; // وصل للحد الأقصى
            }

            $hoursLeft = max(0, $nextLevelTargetHours - $totalHours);
            $progressPercentage = $nextLevelTargetHours > 0 ? min(100, round(($totalHours / $nextLevelTargetHours) * 100)) : 100;

            $gamification = [
                'points'              => $points,
                'current_level'       => $currentLevel,
                'next_level'          => $nextLevel,
                'hours_left'          => $hoursLeft,
                'progress_percentage' => $progressPercentage // نسبة مئوية لملء شريط التقدم الأزرق
            ];

            // ==========================================
            // القسم الثالث: توزيع الساعات (تصنيف المجهود) للرسم البياني الدائري
            // ==========================================
            $hoursDistribution = [];
            if ($totalHours > 0) {
                // تجميع بناءً على (مجال / تصنيف) الفرصة
                $groupedByCategory = $approvedReports->groupBy(function ($report) {
                    return $report->opportunity->category ?? 'أخرى';
                });

                foreach ($groupedByCategory as $category => $reports) {
                    $catHours = $reports->sum('hours');
                    $hoursDistribution[] = [
                        'category'   => $category, // مثال: مساعدات طبية
                        'percentage' => round(($catHours / $totalHours) * 100),
                    ];
                }

                // ترتيب النسب من الأكبر للأصغر
                usort($hoursDistribution, fn($a, $b) => $b['percentage'] <=> $a['percentage']);
            }

            // ==========================================
            // القسم الرابع: أحدث فرص التطوع (التي لم يقدم عليها بعد)
            // ==========================================
            $latestOpportunities = VolunteerOpportunity::where('status', 'active')
                ->whereDoesntHave('volunteers', function ($q) use ($volunteerId) {
                    $q->where('volunteer_id', $volunteerId); // استبعاد الفرص التي سجل فيها
                })
                ->with(['foundation:id,name,logo'])
                ->orderBy('created_at', 'desc')
                ->take(3) // جلب أحدث 3 فرص فقط
                ->get();

            $latestOpportunities->transform(function ($opp) {
                if ($opp->foundation) {
                    $opp->foundation->logo_url = !empty($opp->foundation->logo) && !str_starts_with($opp->foundation->logo, 'http')
                        ? asset('storage/' . $opp->foundation->logo)
                        : $opp->foundation->logo;
                    $opp->foundation->makeHidden(['logo']);
                }
                return [
                    'id'              => $opp->id,
                    'title'           => $opp->title,
                    'foundation_name' => $opp->foundation->name ?? 'غير محدد',
                    'location'        => $opp->location,
                    'logo_url'        => $opp->foundation->logo_url ?? null
                ];
            });

            // ==========================================
            // القسم الخامس: مهامي التطوعية الحالية (آخر المهام)
            // ==========================================
            $myRecentTasks = $volunteer->opportunities()
                ->with(['foundation:id,name'])
                ->orderBy('opportunity_volunteer.updated_at', 'desc')
                ->take(3) // جلب آخر 3 مهام للملخص السريع
                ->get();

            $myRecentTasks->transform(function ($task) {
                return [
                    'id'              => $task->id,
                    'title'           => $task->title,
                    'foundation_name' => $task->foundation->name ?? 'غير محدد',
                    'date'            => Carbon::parse($task->date)->translatedFormat('d F Y'),
                    'status'          => $task->pivot->status, // applied, accepted, attended...
                    'status_ar'       => $this->translateStatus($task->pivot->status) // دالة مساعدة لترجمة الحالة للواجهة
                ];
            });

            // ==========================================
            // إرجاع الرد النهائي الشامل
            // ==========================================
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات لوحة التحكم بنجاح.',
                'data'    => [
                    'volunteer_name'       => explode(' ', trim($volunteer->name))[0], // الاسم الأول فقط (مثال: أحمد)
                    'stats'                => $stats,
                    'gamification'         => $gamification,
                    'hours_distribution'   => $hoursDistribution,
                    'latest_opportunities' => $latestOpportunities,
                    'my_recent_tasks'      => $myRecentTasks,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Dashboard Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب بيانات لوحة التحكم.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * دالة مساعدة لترجمة حالة المهمة ليعرضها الفرونت إند مباشرة
     */
    private function translateStatus($status)
    {
        return match ($status) {
            'applied'  => 'جاري',
            'invited'  => 'دعوة معلقة',
            'accepted' => 'مؤكد',
            'attended' => 'مكتملة',
            'rejected' => 'مرفوض',
            default    => 'غير محدد',
        };
    }
}
