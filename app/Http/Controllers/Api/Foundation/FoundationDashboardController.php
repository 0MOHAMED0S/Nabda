<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use App\Models\VolunteerReport;
use App\Models\Donation;
use App\Models\FoundationCase; // استخدام موديل الحالات الخاص بك
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class FoundationDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $foundation = $request->user();
            $foundationId = $foundation->id;

            $reportsQuery = VolunteerReport::whereHas('opportunity', function ($q) use ($foundationId) {
                $q->where('foundation_id', $foundationId);
            })->where('status', 'approved')->whereNotNull('rating');

            $averageRating = $reportsQuery->avg('rating') ? round($reportsQuery->avg('rating'), 1) : 0;
            $ratingsCount  = $reportsQuery->count();

            $headerInfo = [
                'name'           => $foundation->name,
                'is_verified'    => $foundation->is_verified ?? true,
                'member_since'   => Carbon::parse($foundation->created_at)->translatedFormat('F Y'),
                'average_rating' => $averageRating,
                'ratings_count'  => $ratingsCount,
            ];

            $totalDonations = Donation::where('foundation_id', $foundationId)->where('status', 'completed')->sum('amount');
            $donorsCount    = Donation::where('foundation_id', $foundationId)->distinct('user_id')->count('user_id');

            $opportunitiesIds = VolunteerOpportunity::where('foundation_id', $foundationId)->pluck('id');
            $totalVolunteersApps = DB::table('opportunity_volunteer')->whereIn('volunteer_opportunity_id', $opportunitiesIds)->count();
            $acceptedVolunteers  = DB::table('opportunity_volunteer')->whereIn('volunteer_opportunity_id', $opportunitiesIds)->whereIn('status', ['accepted', 'attended'])->count();

            $activeCases    = FoundationCase::where('foundation_id', $foundationId)->where('status', 'active')->count();
            $completedCases = FoundationCase::where('foundation_id', $foundationId)->where('status', 'completed')->count();

            $stats = [
                'total_donations'     => $totalDonations,
                'donors_count'        => $donorsCount,
                'accepted_volunteers' => $acceptedVolunteers,
                'total_volunteers'    => $totalVolunteersApps,
                'active_cases'        => $activeCases,
                'completed_cases'     => $completedCases,
            ];

            $newVolunteerRequests = DB::table('opportunity_volunteer')->whereIn('volunteer_opportunity_id', $opportunitiesIds)->where('status', 'applied')->count();
            $pendingDonations     = Donation::where('foundation_id', $foundationId)->where('status', 'pending')->count();

            $activeCasesWithDonations = FoundationCase::where('foundation_id', $foundationId)
                ->where('status', 'active')
                ->withSum(['donations' => function($q) {
                    $q->where('status', 'completed');
                }], 'amount')
                ->get();

            $nearCompletionCases = $activeCasesWithDonations->filter(function ($case) {
                if ($case->target_amount > 0) {
                    $percentage = (($case->donations_sum_amount ?? 0) / $case->target_amount) * 100;
                    return $percentage >= 85;
                }
                return false;
            })->count();

            $alerts = [
                'new_volunteer_requests' => $newVolunteerRequests,
                'pending_donations'      => $pendingDonations,
                'near_completion_cases'  => $nearCompletionCases,
                'expiring_documents'     => 0,
            ];

            $latestCases = FoundationCase::where('foundation_id', $foundationId)
                ->withSum(['donations' => function($q) {
                    $q->where('status', 'completed');
                }], 'amount') // جلب مجموع التبرعات لحساب النسبة
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->get()
                ->map(function ($case) {
                    $collected = $case->donations_sum_amount ?? 0;
                    $percentage = $case->target_amount > 0 ? round(($collected / $case->target_amount) * 100) : 0;
                    $firstImage = is_array($case->images) && count($case->images) > 0 ? $case->images[0] : null;
                    $imageUrl = !empty($firstImage) && !str_starts_with($firstImage, 'http') ? asset('storage/' . $firstImage) : $firstImage;

                    $remainingDays = $case->end_date ? Carbon::parse($case->end_date)->diffInDays(now()) : 0;

                    return [
                        'id'                    => $case->id,
                        'title'                 => $case->title,
                        'category'              => $case->campaign_type, // تم التصحيح لـ campaign_type
                        'is_urgent'             => $case->priority === 'عاجل' || $case->priority === 'urgent', // تم التصحيح لمعرفة الأولوية
                        'image_url'             => $imageUrl,
                        'completion_percentage' => min(100, $percentage), // لا تتجاوز 100%
                        'remaining_days'        => $remainingDays,
                        'beneficiaries'         => 1, // حيث أن الموديل يحتوي على اسم مستفيد واحد
                        'status'                => $case->status,
                    ];
                });

            $latestDonations = Donation::where('foundation_id', $foundationId)
                ->with('user:id,name,phone')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($donation) {
                    return [
                        'id'             => $donation->id,
                        'donor_name'     => $donation->user->name ?? 'فاعل خير',
                        'donor_phone'    => $donation->user->phone ?? 'غير متوفر',
                        'amount'         => $donation->amount,
                        'type'           => $donation->type,
                        'payment_method' => $donation->payment_method,
                        'date'           => Carbon::parse($donation->created_at)->format('Y-m-d'),
                        'status'         => $donation->status,
                    ];
                });

            $latestVolunteers = VolunteerOpportunity::where('foundation_id', $foundationId)
                ->with(['volunteers' => function ($query) {
                    $query->orderBy('opportunity_volunteer.created_at', 'desc')->take(4);
                }])
                ->get()
                ->pluck('volunteers')
                ->flatten()
                ->sortByDesc('pivot.created_at')
                ->take(4)
                ->values()
                ->map(function ($volunteer) {
                    $opportunityTitle = VolunteerOpportunity::find($volunteer->pivot->volunteer_opportunity_id)->title ?? 'غير محدد';
                    return [
                        'volunteer_id'     => $volunteer->id,
                        'opportunity_id'   => $volunteer->pivot->volunteer_opportunity_id,
                        'volunteer_name'   => $volunteer->name,
                        'volunteer_phone'  => $volunteer->phone,
                        'opportunity_name' => $opportunityTitle,
                        'applied_at'       => Carbon::parse($volunteer->pivot->created_at)->format('Y-m-d'),
                        'status'           => $volunteer->pivot->status,
                    ];
                });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات لوحة تحكم المؤسسة بنجاح.',
                'data'    => [
                    'header_info'       => $headerInfo,
                    'stats'             => $stats,
                    'alerts'            => $alerts,
                    'latest_cases'      => $latestCases,
                    'latest_donations'  => $latestDonations,
                    'latest_volunteers' => $latestVolunteers,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Dashboard Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات لوحة التحكم.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
