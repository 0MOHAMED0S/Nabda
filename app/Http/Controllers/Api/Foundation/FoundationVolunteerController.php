<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class FoundationVolunteerController extends Controller
{
    /**
     * API: جلب قائمة المتطوعين مع الإحصائيات والبحث (تطابق الشاشة تماماً)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // جلب المتطوعين التابعين لهذه المؤسسة
            $query = Volunteer::where('foundation_id', $foundationId);

            // --- 1. حساب الإحصائيات العلوية بذكاء ---
            $totalVolunteers = (clone $query)->count();
            $activeVolunteers = (clone $query)->where('status', 'active')->count();

            // حساب إجمالي ساعات كل المتطوعين (عبر تجميع ساعات تقاريرهم)
            $totalHours = (clone $query)->withSum('reports', 'hours')->get()->sum('reports_sum_hours');

            // حساب المتوسط
            $avgHours = $totalVolunteers > 0 ? round($totalHours / $totalVolunteers, 1) : 0;

            $stats = [
                'total_volunteers'  => $totalVolunteers,
                'active_volunteers' => $activeVolunteers,
                'total_hours'       => $totalHours,
                'average_hours'     => $avgHours,
            ];

            // --- 2. الفلترة والبحث (Search) ---
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // --- 3. جلب بيانات الجدول مع الحسابات (بدون تقسيم/Pagination حسب طلبك السابق) ---
            $volunteers = $query->withCount(['opportunities as activities_count' => function ($q) {
                                    // حساب عدد الأنشطة التي تم قبول المتطوع فيها أو حضرها
                                    $q->whereIn('opportunity_volunteer.status', ['accepted', 'attended']);
                                }])
                                ->withSum('reports as total_hours', 'hours') // إجمالي ساعات المتطوع
                                ->withMax('reports as last_activity_date', 'created_at') // تاريخ آخر نشاط/تقرير
                                ->orderBy('created_at', 'desc')
                                ->get();

            // --- 4. تهيئة البيانات (Full URLs) والتنظيف ---
            $volunteers->transform(function ($volunteer) {
                $data = $volunteer->toArray();

                // رابط الصورة الشخصية الكامل
                if (!empty($data['avatar'])) {
                    $data['avatar_url'] = asset('storage/' . $data['avatar']);
                } else {
                    $data['avatar_url'] = null; // أو رابط لصورة افتراضية
                }
                unset($data['avatar']); // إخفاء المسار المحلي

                // تنسيق الأرقام والتواريخ
                $data['total_hours'] = $data['total_hours'] ?? 0;

                $data['last_activity_date'] = $data['last_activity_date']
                    ? Carbon::parse($data['last_activity_date'])->format('Y-m-d')
                    : 'لم يشارك بعد';

                // استخراج المجال الأول (لعمود "المجال" في الجدول)
                if (isset($data['volunteer_fields']) && is_array($data['volunteer_fields']) && count($data['volunteer_fields']) > 0) {
                    $data['primary_field'] = $data['volunteer_fields'][0];
                } else {
                    $data['primary_field'] = 'عام';
                }

                // إخفاء الحقول غير الضرورية للجدول لتخفيف حجم الـ JSON
                unset($data['volunteer_fields'], $data['governorates'], $data['national_id_front'], $data['national_id_back']);

                return $data;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات المتطوعين بنجاح.',
                'data'    => [
                    'stats'      => $stats,
                    'volunteers' => $volunteers
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Volunteers Index Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب البيانات.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: تبديل حالة المتطوع (تنشيط / إيقاف) - للزر الموجود في عمود الإجراءات
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $volunteer = Volunteer::where('id', $id)
                                  ->where('foundation_id', $request->user()->id)
                                  ->first();

            if (!$volunteer) {
                return response()->json(['status' => false, 'message' => 'المتطوع غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // التبديل بين الحالتين
            $newStatus = $volunteer->status === 'active' ? 'inactive' : 'active';
            $volunteer->update(['status' => $newStatus]);

            $statusAr = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'status'  => true,
                'message' => "تم تغيير حالة المتطوع إلى ($statusAr) بنجاح.",
                'data'    => ['id' => $volunteer->id, 'status' => $newStatus]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Toggle Volunteer Status Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
