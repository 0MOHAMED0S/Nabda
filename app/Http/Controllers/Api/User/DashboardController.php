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
    /**
     * جلب بيانات لوحة التحكم الرئيسية للمستخدم
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Carbon::setLocale('ar'); // لتعريب التواريخ إذا لزم الأمر

            // 1. الكروت العلوية (الإحصائيات الأساسية)
            // نعتمد على التبرعات المكتملة (completed) في الحسابات
            $completedDonations = Donation::where('user_id', $user->id)
                ->where('status', 'completed')
                ->get();

            $totalDonationsCount = $completedDonations->count();

            $totalAmount = $completedDonations->where('donation_type', 'financial')->sum('amount');

            // حساب عدد الحالات الفريدة التي تبرع لها
            $supportedCasesCount = $completedDonations->whereNotNull('case_id')->unique('case_id')->count();

            // حساب عدد الجمعيات الفريدة التي تبرع لها
            $supportedFoundationsCount = $completedDonations->unique('foundation_id')->count();

            // 2. نظام النقاط (كما هو موضح بالصورة: كل تبرع = 10 نقاط)
            $points = $totalDonationsCount * 10;

            // تحديد رتبة المتبرع بناءً على النقاط (يمكنك تعديل الأرقام حسب نظامك)
            $badge = 'متبرع مبادر';
            if ($points >= 200) {
                $badge = 'متبرع ماسي';
            } elseif ($points >= 100) {
                $badge = 'متبرع ذهبي';
            } elseif ($points >= 50) {
                $badge = 'متبرع فضي';
            }

            // 3. جدول آخر التبرعات (نجلب أحدث 5 تبرعات بغض النظر عن حالتها ليرى نشاطه الأخير)
            $recentDonations = Donation::with(['foundation', 'case'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($donation) {
                    return [
                        'id'              => $donation->id,
                        'foundation_name' => $donation->foundation ? $donation->foundation->name : 'جمعية خيرية',
                        'type'            => $donation->donation_type, // financial or in-kind
                        'type_ar'         => $donation->donation_type === 'financial' ? 'مالي' : 'عيني',

                        // التفاصيل (المبلغ أو اسم المادة العينية)
                        'amount_or_item'  => $donation->donation_type === 'financial'
                                                ? number_format($donation->amount) . ' ج.م'
                                                : ($donation->item_category ?? 'مواد عينية'),

                        // الوصف الفرعي (نوع المشروع أو وصف العيني)
                        'description'     => $donation->donation_type === 'financial'
                                                ? 'تبرع نقدي لصالح ' . ($donation->case ? $donation->case->title : 'عام')
                                                : ($donation->item_description ?? 'تبرع عيني'),

                        // طريقة الدفع أو التسليم (ترجمة الحالات لتعرض في الـ UI)
                        'method'          => $this->translateMethod($donation),

                        'date'            => $donation->created_at->translatedFormat('d M'), // مثال: 20 أكتوبر
                    ];
                });

            // 4. الشارت (توزيع التبرعات)
            // سنقوم بتجميع التبرعات حسب تصنيف الحالة أو وضعها كـ "أخرى"
            $distribution = $this->calculateDonationDistribution($completedDonations);

            // تجميع البيانات للفرونت إند
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات لوحة التحكم بنجاح.',
                'data'    => [
                    'user_name' => explode(' ', $user->name)[0], // إرجاع الاسم الأول للترحيب "مرحباً Ahmed"
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

    /**
     * دالة مساعدة لترجمة طريقة الدفع أو التسليم للعربية
     */
    private function translateMethod($donation): string
    {
        if ($donation->donation_type === 'financial') {
            return match($donation->payment_method) {
                'paymob' => 'بطاقة ائتمان',
                'bank_transfer' => 'تحويل بنكي',
                'cash' => 'نقدي',
                default => 'بطاقة ائتمان'
            };
        } else {
            return match($donation->delivery_method) {
                'home_pickup' => 'توصيل للمقر', // أو مندوب للمنزل
                'branch_dropoff' => 'تسليم يدوي',
                'collection_point' => 'نقطة تجميع',
                default => 'تسليم يدوي'
            };
        }
    }

    /**
     * دالة مساعدة لحساب النسب المئوية لتوزيع التبرعات (للشارت)
     */
    private function calculateDonationDistribution($donations): array
    {
        $total = $donations->count();
        if ($total === 0) return [];

        $categories = [];

        // إذا كان لديك حقل category في موديل FoundationCase، نستخدمه.
        // هنا نفترض وجوده، أو نستخدم فئات افتراضية
        foreach ($donations as $donation) {
            $catName = $donation->case && $donation->case->category
                        ? $donation->case->category
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

        // ترتيب التوزيع من النسبة الأكبر للأصغر
        usort($distribution, function($a, $b) {
            return $b['percentage'] <=> $a['percentage'];
        });

        return $distribution;
    }
}
