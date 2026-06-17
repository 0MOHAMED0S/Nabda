<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Volunteer;
use App\Models\VolunteerReport;
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
public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // 🎯 التعديل هنا: جلب جميع المتطوعين في النظام (بدون تقييدهم بفرص المؤسسة)
            // إذا أردت عرض المتطوعين المعتمدين فقط يمكنك تغييرها إلى: Volunteer::where('status', 'approved');
            $query = \App\Models\Volunteer::query();

            // ==========================================
            // 1. حساب الإحصائيات العلوية (المربعات الأربعة)
            // ==========================================
            $totalVolunteers = (clone $query)->count();
            $activeVolunteers = (clone $query)->where('status', 'active')->count();

            // حساب إجمالي الساعات (من التقارير المعتمدة التابعة لفرص هذه المؤسسة فقط)
            $totalHours = (clone $query)->withSum(['reports' => function($q) use ($foundationId) {
                $q->where('status', 'approved')
                  ->whereHas('opportunity', function($opp) use ($foundationId) {
                      $opp->where('foundation_id', $foundationId);
                  });
            }], 'hours')->get()->sum('reports_sum_hours') ?? 0;

            // حساب المتوسط
            $avgHours = $totalVolunteers > 0 ? round($totalHours / $totalVolunteers, 1) : 0;

            $stats = [
                'total_volunteers'  => $totalVolunteers,
                'active_volunteers' => $activeVolunteers,
                'total_hours'       => $totalHours,
                'average_hours'     => $avgHours,
            ];

            // ==========================================
            // 2. الفلترة والبحث (Search)
            // ==========================================
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // ==========================================
            // 3. جلب بيانات الجدول دفعة واحدة (بدون Pagination)
            // ==========================================
            $volunteers = $query->withCount(['opportunities as activities_count' => function ($q) use ($foundationId) {
                                    // عدد الأنشطة مع هذه المؤسسة
                                    $q->where('foundation_id', $foundationId)
                                      ->whereIn('opportunity_volunteer.status', ['accepted', 'attended']);
                                }])
                                ->withSum(['reports as total_hours' => function($q) use ($foundationId) {
                                    // ساعات المتطوع مع هذه المؤسسة
                                    $q->where('status', 'approved')
                                      ->whereHas('opportunity', function($opp) use ($foundationId) {
                                          $opp->where('foundation_id', $foundationId);
                                      });
                                }], 'hours')
                                ->withMax(['reports as last_activity_date' => function($q) use ($foundationId) {
                                    // تاريخ آخر نشاط مع هذه المؤسسة
                                    $q->where('status', 'approved')
                                      ->whereHas('opportunity', function($opp) use ($foundationId) {
                                          $opp->where('foundation_id', $foundationId);
                                      });
                                }], 'created_at')
                                ->orderBy('created_at', 'desc')
                                ->get();

            // ==========================================
            // 4. تهيئة البيانات والتنظيف للواجهة (Frontend)
            // ==========================================
            $volunteers->transform(function ($volunteer) {
                $data = $volunteer->toArray();

                // 1. رابط الصورة الشخصية
                if (!empty($data['avatar']) && !str_starts_with($data['avatar'], 'http')) {
                    $data['avatar_url'] = asset('storage/' . $data['avatar']);
                } else {
                    $data['avatar_url'] = $data['avatar'] ?? null;
                }
                unset($data['avatar']);

                // 2. الساعات والأنشطة
                $data['total_hours'] = $data['total_hours'] ?? 0;
                $data['activities_count'] = $data['activities_count'] ?? 0;

                // 3. تاريخ آخر نشاط
                $data['last_activity_date'] = $data['last_activity_date']
                    ? \Carbon\Carbon::parse($data['last_activity_date'])->format('Y-m-d')
                    : 'لم يشارك بعد';

                // 4. استخراج المجال (طبي، تعليم، إغاثة...)
                if (isset($data['volunteer_fields']) && is_array($data['volunteer_fields']) && count($data['volunteer_fields']) > 0) {
                    $data['primary_field'] = $data['volunteer_fields'][0];
                } else {
                    $data['primary_field'] = 'عام';
                }

                // 🎯 استخراج المحافظة الأساسية للسهولة في العرض (اختياري، يسهل على الفرونت إند)
                if (isset($data['governorates']) && is_array($data['governorates']) && count($data['governorates']) > 0) {
                    $data['primary_governorate'] = $data['governorates'][0];
                } else {
                    $data['primary_governorate'] = 'غير محدد';
                }

                // 5. ترجمة الحالة لتلوين الـ Badge في الفرونت إند
                $data['status_ar'] = 'نشط';

                // 🎯 التعديل هنا: قمنا بإزالة $data['governorates'] من دالة unset لكي يتم إرجاعها في الاستجابة (Response)
                // تنظيف المصفوفة من البيانات الكبيرة غير المستخدمة في الجدول
                unset($data['volunteer_fields'], $data['national_id_front'], $data['national_id_back']);

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

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Volunteers Index Error: " . $e->getMessage());
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

    /**
     * API: عرض الملف الشخصي التفصيلي لمتطوع محدد (بدون Pagination)
     */

public function show(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // 1. جلب بيانات المتطوع الأساسية
            $volunteer = \App\Models\Volunteer::find($id);

            if (!$volunteer) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المتطوع غير موجود.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. جلب كافة التقارير المعتمدة للمتطوع (الأنشطة السابقة التي تم تقييمها)
            $approvedReports = \App\Models\VolunteerReport::where('volunteer_id', $id)
                ->where('status', 'approved')
                ->with(['opportunity:id,title,foundation_id,date', 'opportunity.foundation:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            // ==========================================
            // 3. حساب الإحصائيات السريعة
            // ==========================================
            $totalHours = $approvedReports->sum('hours');

            // حساب إجمالي عدد الأنشطة التي تم قبوله فيها أو حضرها (سواء رفع تقرير أم لا)
            $activitiesCount = $volunteer->opportunities()
                                         ->whereIn('opportunity_volunteer.status', ['accepted', 'attended'])
                                         ->count();

            $avgHours = $activitiesCount > 0 ? round($totalHours / $activitiesCount, 1) : 0;

            $lastActivity = $approvedReports->first();
            $lastActivityDate = $lastActivity ? \Carbon\Carbon::parse($lastActivity->created_at)->format('Y/m/d') : 'لم يشارك بعد';

            // حساب التقييمات
            $ratedReports = $approvedReports->whereNotNull('rating');
            $averageRating = $ratedReports->count() > 0 ? round($ratedReports->avg('rating'), 1) : 0;
            $reviewsCount = $ratedReports->count();

            // ==========================================
            // 4. تحديد الشارات والتكريمات بذكاء (Gamification)
            // ==========================================
            $badges = [];
            if ($totalHours >= 100) {
                $badges[] = ['name' => '100 ساعة تطوع', 'icon' => 'time', 'color' => 'green'];
            }
            if ($averageRating >= 4.5) {
                $badges[] = ['name' => 'المتطوع الذهبي', 'icon' => 'medal', 'color' => 'gold'];
            }
            if ($activitiesCount >= 10) {
                $badges[] = ['name' => 'قدوة حسنة', 'icon' => 'star', 'color' => 'purple'];
            }

            // ==========================================
            // 5. تجهيز قائمة الأنشطة السابقة (من التقارير المعتمدة لتشمل التقييم ورسالة الشكر)
            // ==========================================
            $activitiesHistory = $approvedReports->map(function($report) {
                return [
                    'id'              => $report->id,
                    'activity_name'   => $report->opportunity->title ?? 'غير محدد',
                    'foundation_name' => $report->opportunity->foundation->name ?? 'غير محدد',
                    'date'            => \Carbon\Carbon::parse($report->created_at)->format('Y/m/d'),
                    'hours'           => $report->hours,
                    'rating'          => $report->rating,
                    'feedback'        => $report->feedback_message,
                    'status'          => 'مكتمل'
                ];
            });

            // ==========================================
            // 6. تهيئة البيانات الشخصية (معلومات الاتصال والمجالات)
            // ==========================================
            $avatarUrl = !empty($volunteer->avatar) && !str_starts_with($volunteer->avatar, 'http')
                ? asset('storage/' . $volunteer->avatar)
                : ($volunteer->avatar ?? null);

            // 🎯 التعديل هنا: جلب العنوان الفعلي من قاعدة البيانات
            $address = $volunteer->address ?? 'غير محدد';

            // 🎯 التعديل هنا: جلب المحافظات وإرجاعها كمصفوفة
            $governorates = $volunteer->governorates ?? [];
            if (!is_array($governorates)) {
                $governorates = json_decode($governorates, true) ?? [];
            }

            // استخراج مجالات التطوع (كالمهارات: تدريس، تواصل، صبر...)
            $fields = $volunteer->volunteer_fields ?? [];
            if (!is_array($fields)) {
                $fields = json_decode($fields, true) ?? [];
            }

            // ==========================================
            // 7. الرد النهائي المطابق للشاشة
            // ==========================================
            $responseData = [
                'personal_info' => [
                    'id'           => $volunteer->id,
                    'name'         => $volunteer->name,
                    'avatar_url'   => $avatarUrl,
                    'email'        => $volunteer->email,
                    'phone'        => $volunteer->phone,
                    'address'      => $address,      // 👈 تم تصحيح العنوان
                    'governorates' => $governorates, // 👈 تمت إضافة المحافظات
                    'join_date'    => \Carbon\Carbon::parse($volunteer->created_at)->translatedFormat('Y/m/d'),
                    'status'       => $volunteer->status,
                    'status_ar'    => $volunteer->status === 'active' ? 'نشط' : 'موقوف',
                    'fields'       => $fields,
                ],
                'quick_stats' => [
                    'total_hours'      => $totalHours,
                    'activities_count' => $activitiesCount,
                    'average_hours'    => $avgHours,
                    'last_activity'    => $lastActivityDate,
                ],
                'ratings_and_badges' => [
                    'average_rating' => $averageRating,
                    'reviews_count'  => $reviewsCount,
                    'badges'         => $badges,
                ],
                'previous_activities' => $activitiesHistory
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الملف الشخصي للمتطوع بنجاح.',
                'data'    => $responseData
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Volunteer Profile Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات المتطوع.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * API: عرض تفاصيل نشاط تطوعي محدد (تقرير منتهي ومعتمد)
     */
    public function showActivityDetails(Request $request, $reportId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // 1. جلب التقرير مع كافة علاقاته (المتطوع، الفرصة، والمؤسسة)
            // والتأكد من أن هذا النشاط يخص هذه المؤسسة
            $report = VolunteerReport::with(['volunteer', 'opportunity.foundation'])
                ->where('id', $reportId)
                ->whereHas('opportunity', function ($q) use ($foundationId) {
                    $q->where('foundation_id', $foundationId);
                })
                ->first();

            if (!$report) {
                return response()->json([
                    'status'  => false,
                    'message' => 'النشاط غير موجود أو لا تملك صلاحية الوصول إليه.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $opportunity = $report->opportunity;
            $volunteer = $report->volunteer;
            $foundation = $opportunity->foundation;

            // ==========================================
            // 2. تهيئة الروابط الكاملة (Full URLs)
            // ==========================================
            // صورة المتطوع
            $avatarUrl = !empty($volunteer->avatar) && !str_starts_with($volunteer->avatar, 'http')
                ? asset('storage/' . $volunteer->avatar)
                : ($volunteer->avatar ?? null);

            // صور النشاط المرفقة بالتقرير
            $activityImages = [];
            if (is_array($report->images)) {
                $activityImages = array_map(function ($img) {
                    return !str_starts_with($img, 'http') ? asset('storage/' . $img) : $img;
                }, $report->images);
            }

            // ==========================================
            // 3. تنسيق الوقت والتاريخ (ليطابق الصورة)
            // ==========================================
            $formattedDate = Carbon::parse($opportunity->date)->translatedFormat('d F Y'); // مثال: 20 فبراير 2026

            // تنسيق الوقت (مثال: 10:00 ص - 02:00 م)
            $startTime = Carbon::parse($opportunity->start_time)->translatedFormat('h:i a');
            $endTime = Carbon::parse($opportunity->end_time)->translatedFormat('h:i a');
            $timeString = "{$startTime} - {$endTime}";

            // ==========================================
            // 4. بناء الرد النهائي مقسماً حسب كروت الشاشة (Cards)
            // ==========================================
            $responseData = [
                // الهيدر العلوي
                'header' => [
                    'title'           => $opportunity->title,
                    'foundation_name' => $foundation->name ?? 'غير محدد',
                    'date'            => $formattedDate,
                    'hours'           => $report->hours,
                    'status'          => 'مكتمل'
                ],

                // كارت: المتطوع
                'volunteer' => [
                    'id'         => $volunteer->id,
                    'name'       => $volunteer->name,
                    'avatar_url' => $avatarUrl,
                    'rating'     => $report->rating // التقييم الذي حصل عليه في هذا النشاط
                ],

                // كارت: وصف النشاط (يأتي من الفرصة الأساسية)
                'activity_description' => $opportunity->description,

                // كارت: صور النشاط (تأتي من صور التقرير المرفوعة)
                'activity_images' => $activityImages,

                // كارت: إنجازات النشاط (يأتي من ملخص التقرير الذي كتبه المتطوع)
                // (نقوم بتحويل النص إلى مصفوفة إذا كان مفصولاً بأسطر جديدة ليعرض كقائمة ✓)
                'achievements' => array_filter(array_map('trim', explode("\n", $report->summary))),

                // كارت: معلومات النشاط
                'activity_info' => [
                    'date'              => $formattedDate,
                    'time'              => $timeString,
                    'duration_hours'    => $report->hours,
                    'location'          => $opportunity->location,
                    'participants_count'=> $opportunity->required_volunteers // أو عدد المسجلين الفعليين
                ],

                // كارت: المؤسسة المنظمة
                'foundation_info' => [
                    'name'           => $foundation->name ?? 'غير محدد',
                    'contact_person' => $opportunity->contact_person, // المنسق
                    'contact_phone'  => $opportunity->contact_phone
                ],

                // كارت: التقييم
                'evaluation' => [
                    'rating'   => $report->rating,
                    'feedback' => $report->feedback_message // ملاحظات المؤسسة
                ],

                // كارت: متطلبات النشاط
                'requirements' => is_array($opportunity->requirements)
                                    ? $opportunity->requirements
                                    : json_decode($opportunity->requirements, true) ?? [],
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل النشاط بنجاح.',
                'data'    => $responseData
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Activity Details Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب تفاصيل النشاط.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
