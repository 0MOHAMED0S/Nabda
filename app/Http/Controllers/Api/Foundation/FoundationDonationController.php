<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class FoundationDonationController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;
            $query = Donation::where('foundation_id', $foundationId);

            $stats = [
                'total_donations'   => (clone $query)->count(),
                'cash_donations'    => (clone $query)->where('donation_type', 'financial')->count(),
                'inkind_donations'  => (clone $query)->where('donation_type', 'in-kind')->count(),
                'total_amount'      => (clone $query)->where('donation_type', 'financial')
                                                     ->where('status', 'completed')
                                                     ->sum('amount'),
            ];

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('donor_name', 'like', "%{$search}%")
                      ->orWhere('donor_phone', 'like', "%{$search}%")
                      ->orWhere('paymob_order_id', 'like', "%{$search}%");
                });
            }

            if ($request->filled('type')) {
                $query->where('donation_type', $request->type); // financial أو in-kind
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('method')) {
                $method = $request->method;
                $query->where(function($q) use ($method) {
                    $q->where('payment_method', $method)
                      ->orWhere('delivery_method', $method);
                });
            }

            $donations = $query->with('foundationCase:id,title') // جلب اسم الحالة إن وُجدت
                               ->orderBy('created_at', 'desc')
                               ->get();

            $donations->transform(function ($donation) {
                if ($donation->donation_image) {
                    $donation->donation_image_url = asset('storage/' . $donation->donation_image);
                    $donation->makeHidden(['donation_image']);
                }
                return $donation;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب التبرعات بنجاح.',
                'data'    => [
                    'stats'     => $stats,
                    'donations' => $donations
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Donations Index Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function report(Request $request): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;
            $baseQuery = Donation::where('foundation_id', $foundationId);

            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            $cashQuery = (clone $baseQuery)->where('donation_type', 'financial');
            $totalCashAmount = (clone $cashQuery)->where('status', 'completed')->sum('amount');
            $cashCount = $cashQuery->count();

            $cashStats = [
                'count'           => $cashCount,
                'total_amount'    => $totalCashAmount,
                'average_amount'  => $cashCount > 0 ? round($totalCashAmount / $cashCount, 2) : 0,
                'methods'         => [
                    'credit_card' => (clone $cashQuery)->where('payment_method', 'credit_card')->count(),
                    'bank_transfer'=> (clone $cashQuery)->where('payment_method', 'bank_transfer')->count(),
                    'e_wallet'    => (clone $cashQuery)->where('payment_method', 'wallet')->count(),
                ]
            ];

            $inkindQuery = (clone $baseQuery)->where('donation_type', 'in-kind');

            $inkindStats = [
                'count'      => $inkindQuery->count(),
                'status'     => [
                    'completed_or_received' => (clone $inkindQuery)->whereIn('status', ['completed', 'received'])->count(),
                    'pending'               => (clone $inkindQuery)->where('status', 'pending')->count(),
                ],
                'categories' => (clone $inkindQuery)
                                    ->selectRaw('item_category, COUNT(*) as count')
                                    ->groupBy('item_category')
                                    ->pluck('count', 'item_category')
                                    ->toArray()
            ];

            $monthlyQuery = (clone $baseQuery)->whereMonth('created_at', $currentMonth)
                                              ->whereYear('created_at', $currentYear);
            $monthlyStats = [
                'total_count'    => $monthlyQuery->count(),
                'total_amount'   => (clone $monthlyQuery)->where('donation_type', 'financial')->where('status', 'completed')->sum('amount'),
                'inkind_count'   => (clone $monthlyQuery)->where('donation_type', 'in-kind')->count(),
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب التقرير الرقابي بنجاح.',
                'data'    => [
                    'cash_donations'   => $cashStats,
                    'inkind_donations' => $inkindStats,
                    'monthly_report'   => $monthlyStats,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Foundation Donations Report Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب التقرير.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $donation = Donation::where('id', $id)
                                ->where('foundation_id', $request->user()->id)
                                ->with('foundationCase:id,title')
                                ->first();

            if (!$donation) {
                return response()->json(['status' => false, 'message' => 'التبرع غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            if ($donation->donation_image) {
                $donation->donation_image_url = asset('storage/' . $donation->donation_image);
                $donation->makeHidden(['donation_image']);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل التبرع بنجاح.',
                'data'    => $donation
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Show Donation Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $donation = Donation::where('id', $id)->where('foundation_id', $request->user()->id)->first();

            if (!$donation) {
                return response()->json(['status' => false, 'message' => 'التبرع غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:pending,received,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $donation->update(['status' => $request->status]);

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث حالة التبرع بنجاح.',
                'data'    => $donation
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Update Donation Status Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $donation = Donation::where('id', $id)->where('foundation_id', $request->user()->id)->first();

            if (!$donation) {
                return response()->json(['status' => false, 'message' => 'التبرع غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $donation->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف التبرع بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Delete Donation Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
