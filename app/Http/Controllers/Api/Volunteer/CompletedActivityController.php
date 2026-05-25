<?php

namespace App\Http\Controllers\Api\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\VolunteerReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class CompletedActivityController extends Controller
{
/**
     * API: جلب الأنشطة المنتهية (التقارير المعتمدة) الخاصة بالمتطوع مع الإحصائيات (بدون Pagination)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $volunteerId = $request->user()->id;

            // 1. بناء الاستعلام الأساسي: نجلب التقارير الخاصة بالمتطوع والتي تم تقييمها والموافقة عليها
            $query = VolunteerReport::where('volunteer_id', $volunteerId)
                ->where('status', 'approved')
                ->with(['opportunity.foundation:id,name,logo']); // جلب تفاصيل الفرصة والمؤسسة التابعة لها

            // 2. حساب الإحصائيات العلوية (المطابقة للصورة)
            $stats = [
                'total_hours'      => (clone $query)->sum('hours'), // إجمالي الساعات المعتمدة
                'total_activities' => (clone $query)->count(),      // عدد الأنشطة المنتهية
                'average_rating'   => round((clone $query)->avg('rating') ?? 0, 1) // متوسط التقييم (من 5)
            ];

            // 3. تطبيق الفلترة والبحث (حقل البحث في الواجهة: بالعنوان أو المؤسسة أو الموقع)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('opportunity', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhereHas('foundation', function ($f) use ($search) {
                          $f->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // 4. جلب البيانات دفعة واحدة (بدون Pagination) لترتيبها من الأحدث للأقدم
            $completedActivities = $query->orderBy('updated_at', 'desc')->get(); // 👈 استبدال paginate بـ get

            // 5. تهيئة البيانات (Full URLs) وإخفاء المسارات المحلية
            $completedActivities->transform(function ($report) { // 👈 استخدام transform مباشرة

                // تحويل لوجو المؤسسة إلى رابط كامل
                if ($report->opportunity && $report->opportunity->foundation) {
                    $foundation = $report->opportunity->foundation;
                    $foundation->logo_url = !empty($foundation->logo) && !str_starts_with($foundation->logo, 'http')
                        ? asset('storage/' . $foundation->logo)
                        : $foundation->logo;
                    $foundation->makeHidden(['logo']);
                }

                // تحويل الصور المرفقة في التقرير إلى روابط كاملة
                if (is_array($report->images)) {
                    $report->images = array_map(function ($img) {
                        return !str_starts_with($img, 'http') ? asset('storage/' . $img) : $img;
                    }, $report->images);
                }

                return $report;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الأنشطة المنتهية بنجاح.',
                'data'    => [
                    'stats'      => $stats,
                    'activities' => $completedActivities
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Volunteer Completed Activities Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب الأنشطة المنتهية.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
