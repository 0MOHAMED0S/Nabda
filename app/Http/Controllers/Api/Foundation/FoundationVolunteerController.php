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
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            $query = \App\Models\Volunteer::query();

            $totalVolunteers = (clone $query)->count();
            $activeVolunteers = (clone $query)->where('status', 'active')->count();

            $totalHours = (clone $query)->withSum(['reports' => function ($q) use ($foundationId) {
                $q->where('status', 'approved')
                    ->whereHas('opportunity', function ($opp) use ($foundationId) {
                        $opp->where('foundation_id', $foundationId);
                    });
            }], 'hours')->get()->sum('reports_sum_hours') ?? 0;

            $avgHours = $totalVolunteers > 0 ? round($totalHours / $totalVolunteers, 1) : 0;

            $stats = [
                'total_volunteers'  => $totalVolunteers,
                'active_volunteers' => $activeVolunteers,
                'total_hours'       => $totalHours,
                'average_hours'     => $avgHours,
            ];

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $volunteers = $query->withCount(['opportunities as activities_count' => function ($q) use ($foundationId) {
                $q->where('foundation_id', $foundationId)
                    ->whereIn('opportunity_volunteer.status', ['accepted', 'attended']);
            }])
                ->withSum(['reports as total_hours' => function ($q) use ($foundationId) {
                    $q->where('status', 'approved')
                        ->whereHas('opportunity', function ($opp) use ($foundationId) {
                            $opp->where('foundation_id', $foundationId);
                        });
                }], 'hours')
                ->withMax(['reports as last_activity_date' => function ($q) use ($foundationId) {
                    $q->where('status', 'approved')
                        ->whereHas('opportunity', function ($opp) use ($foundationId) {
                            $opp->where('foundation_id', $foundationId);
                        });
                }], 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $volunteers->transform(function ($volunteer) {
                $data = $volunteer->toArray();

                if (!empty($data['avatar']) && !str_starts_with($data['avatar'], 'http')) {
                    $data['avatar_url'] = asset('storage/' . $data['avatar']);
                } else {
                    $data['avatar_url'] = $data['avatar'] ?? null;
                }
                unset($data['avatar']);

                $data['total_hours'] = $data['total_hours'] ?? 0;
                $data['activities_count'] = $data['activities_count'] ?? 0;

                $data['last_activity_date'] = $data['last_activity_date']
                    ? \Carbon\Carbon::parse($data['last_activity_date'])->format('Y-m-d')
                    : 'لم يشارك بعد';

                if (isset($data['volunteer_fields']) && is_array($data['volunteer_fields']) && count($data['volunteer_fields']) > 0) {
                    $data['primary_field'] = $data['volunteer_fields'][0];
                } else {
                    $data['primary_field'] = 'عام';
                }
                if (isset($data['governorates']) && is_array($data['governorates']) && count($data['governorates']) > 0) {
                    $data['primary_governorate'] = $data['governorates'][0];
                } else {
                    $data['primary_governorate'] = 'غير محدد';
                }
                $data['status_ar'] = 'نشط';

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
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        try {
            $volunteer = Volunteer::where('id', $id)
                ->where('foundation_id', $request->user()->id)
                ->first();

            if (!$volunteer) {
                return response()->json(['status' => false, 'message' => 'المتطوع غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

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
    public function show(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            $foundationId = $request->user()->id;
            $volunteer = \App\Models\Volunteer::find($id);

            if (!$volunteer) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المتطوع غير موجود.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }
            $approvedReports = \App\Models\VolunteerReport::where('volunteer_id', $id)
                ->where('status', 'approved')
                ->with(['opportunity:id,title,foundation_id,date', 'opportunity.foundation:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            $totalHours = $approvedReports->sum('hours');

            $activitiesCount = $volunteer->opportunities()
                ->whereIn('opportunity_volunteer.status', ['accepted', 'attended'])
                ->count();

            $avgHours = $activitiesCount > 0 ? round($totalHours / $activitiesCount, 1) : 0;

            $lastActivity = $approvedReports->first();
            $lastActivityDate = $lastActivity ? \Carbon\Carbon::parse($lastActivity->created_at)->format('Y/m/d') : 'لم يشارك بعد';

            $ratedReports = $approvedReports->whereNotNull('rating');
            $averageRating = $ratedReports->count() > 0 ? round($ratedReports->avg('rating'), 1) : 0;
            $reviewsCount = $ratedReports->count();

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

            $activitiesHistory = $approvedReports->map(function ($report) {
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

            $avatarUrl = !empty($volunteer->avatar) && !str_starts_with($volunteer->avatar, 'http')
                ? asset('storage/' . $volunteer->avatar)
                : ($volunteer->avatar ?? null);

            $address = $volunteer->address ?? 'غير محدد';

            $governorates = $volunteer->governorates ?? [];
            if (!is_array($governorates)) {
                $governorates = json_decode($governorates, true) ?? [];
            }

            $fields = $volunteer->volunteer_fields ?? [];
            if (!is_array($fields)) {
                $fields = json_decode($fields, true) ?? [];
            }

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

    public function showActivityDetails(Request $request, $reportId): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

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

            $avatarUrl = !empty($volunteer->avatar) && !str_starts_with($volunteer->avatar, 'http')
                ? asset('storage/' . $volunteer->avatar)
                : ($volunteer->avatar ?? null);

            $activityImages = [];
            if (is_array($report->images)) {
                $activityImages = array_map(function ($img) {
                    return !str_starts_with($img, 'http') ? asset('storage/' . $img) : $img;
                }, $report->images);
            }

            $formattedDate = Carbon::parse($opportunity->date)->translatedFormat('d F Y'); // مثال: 20 فبراير 2026

            $startTime = Carbon::parse($opportunity->start_time)->translatedFormat('h:i a');
            $endTime = Carbon::parse($opportunity->end_time)->translatedFormat('h:i a');
            $timeString = "{$startTime} - {$endTime}";

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
                    'rating'     => $report->rating
                ],

                'activity_description' => $opportunity->description,

                'activity_images' => $activityImages,

                'achievements' => array_filter(array_map('trim', explode("\n", $report->summary))),

                'activity_info' => [
                    'date'              => $formattedDate,
                    'time'              => $timeString,
                    'duration_hours'    => $report->hours,
                    'location'          => $opportunity->location,
                    'participants_count' => $opportunity->required_volunteers // أو عدد المسجلين الفعليين
                ],

                'foundation_info' => [
                    'name'           => $foundation->name ?? 'غير محدد',
                    'contact_person' => $opportunity->contact_person, // المنسق
                    'contact_phone'  => $opportunity->contact_phone
                ],

                'evaluation' => [
                    'rating'   => $report->rating,
                    'feedback' => $report->feedback_message // ملاحظات المؤسسة
                ],

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
