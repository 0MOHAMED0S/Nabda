<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Carbon::setLocale('ar');
            $completedDonations = Donation::with('foundationCase')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->get();

            $totalDonationsCount = $completedDonations->count();
            $totalAmount         = $completedDonations->where('donation_type', 'financial')->sum('amount');

            $supportedCasesCount       = $completedDonations->whereNotNull('case_id')->unique('case_id')->count();
            $supportedFoundationsCount = $completedDonations->whereNotNull('foundation_id')->unique('foundation_id')->count();

            $points = $totalDonationsCount * 10;
            $badge  = $this->calculateBadge($points);

            $recentDonations = Donation::with(['foundation', 'foundationCase'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($donation) {
                    $isFinancial = $donation->donation_type === 'financial';

                    $statusAr = match ($donation->status) {
                        'completed'  => 'مكتمل',
                        'pending'    => 'قيد المراجعة',
                        'processing' => 'جاري',
                        'cancelled'  => 'ملغي',
                        default      => 'غير محدد'
                    };

                    return [
                        'id'              => $donation->id,
                        'foundation_id'   => $donation->foundation_id, // 🎯 تمت إضافة معرف المؤسسة
                        'case_id'         => $donation->case_id,       // 🎯 تمت إضافة معرف الحالة
                        'foundation_name' => $donation->foundation ? $donation->foundation->name : 'جمعية خيرية',

                        'type'            => $donation->donation_type,
                        'type_ar'         => $isFinancial ? 'مالي' : 'عيني',

                        'amount_or_item'  => $isFinancial
                                                ? number_format($donation->amount) . ' ج.م'
                                                : ($donation->item_category ?? 'مواد عينية'),

                        'description'     => $isFinancial
                                                ? 'تبرع نقدي لصالح ' . ($donation->foundationCase ? $donation->foundationCase->title : 'عام')
                                                : ($donation->item_description ?? 'تبرع عيني'),

                        'method'          => $this->translateMethod($donation),

                        'status_en'       => $donation->status, // 🎯 حالة التبرع بالإنجليزية
                        'status_ar'       => $statusAr,         // 🎯 حالة التبرع بالعربية

                        'date'            => $donation->created_at->translatedFormat('d M Y'), // 🎯 تم التحديث لتشمل السنة ليكون التاريخ دقيقاً
                    ];
                });

            $distribution = $this->calculateDonationDistribution($completedDonations);

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات لوحة التحكم بنجاح.',
                'data'    => [
                    'user_name' => explode(' ', $user->name)[0] ?? 'مرحباً',
                    'stats' => [
                        'total_donations' => $totalDonationsCount,
                        'total_amount'    => $totalAmount,
                        'supported_cases' => $supportedCasesCount,
                        'foundations'     => $supportedFoundationsCount,
                    ],
                    'points_system' => [
                        'total_points' => $points,
                        'badge'        => $badge,
                    ],
                    'distribution'     => $distribution,
                    'recent_donations' => $recentDonations,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            Log::error('API User Dashboard Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب بيانات لوحة التحكم.',
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    private function calculateBadge(int $points): string
    {
        if ($points >= 200) return 'متبرع ماسي';
        if ($points >= 100) return 'متبرع ذهبي';
        if ($points >= 50)  return 'متبرع فضي';

        return 'متبرع مبادر';
    }

    private function translateMethod($donation): string
    {
        if ($donation->donation_type === 'financial') {
            return match($donation->payment_method) {
                'paymob'        => 'بطاقة ائتمان',
                'bank_transfer' => 'تحويل بنكي',
                'cash'          => 'نقدي',
                default         => 'بطاقة ائتمان'
            };
        }

        return match($donation->delivery_method) {
            'home_pickup'      => 'توصيل للمقر',
            'branch_dropoff'   => 'تسليم يدوي',
            'collection_point' => 'نقطة تجميع',
            default            => 'تسليم يدوي'
        };
    }

    private function calculateDonationDistribution($donations): array
    {
        $total = $donations->count();
        if ($total === 0) return [];

        $categories = [];

        foreach ($donations as $donation) {
            $catName = ($donation->foundationCase && $donation->foundationCase->category)
                        ? $donation->foundationCase->category
                        : 'أخرى';

            if (!isset($categories[$catName])) {
                $categories[$catName] = 0;
            }
            $categories[$catName]++;
        }

        $distribution = [];
        foreach ($categories as $name => $count) {
            $distribution[] = [
                'name'       => $name,
                'percentage' => round(($count / $total) * 100),
            ];
        }

        usort($distribution, function($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        return $distribution;
    }
}
