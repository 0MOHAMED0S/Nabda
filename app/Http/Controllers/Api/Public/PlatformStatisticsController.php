<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\FoundationCase;
use App\Models\Donation;
use App\Models\User; // 👈 لا تنسَ استدعاء موديل المستخدمين
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class PlatformStatisticsController extends Controller
{
    /**
     * API: جلب الإحصائيات العامة للمنصة (بأرقام حقيقية من قاعدة البيانات)
     */
    public function index(): JsonResponse
    {
        try {
            // 1. حساب المؤسسات الشريكة (النشطة والمعتمدة فعلياً فقط)
            $foundationsCount = Foundation::where('status', 'active')
                ->where('approval_status', 'approved')
                ->count();

            // 2. حساب الحالات المنجزة (مكتملة فعلياً)
            $completedCasesCount = FoundationCase::where('status', 'completed')->count();

            // 3. إجمالي التبرعات (مجموع المبالغ المتبرع بها بنجاح)
            $totalDonationsAmount = Donation::where('status', 'completed')->sum('amount');

            // 4. المتطوعون (إجمالي عدد المستخدمين المسجلين في المنصة)
            // إذا كان لديك حقل يحدد المتطوع، يمكنك تغييره إلى: User::where('role', 'volunteer')->count();
            $volunteersCount = User::count();

            // 5. عدد المستفيدين (إجمالي عدد الحالات التي تم إضافتها للمنصة سواء مكتملة أو نشطة)
            $beneficiariesCount = FoundationCase::count();

            // 6. هيكلة الاستجابة لتتطابق تماماً مع الكروت الـ 4 في الواجهة الأمامية
            $data = [
                // الكارت الأزرق العلوي (المتطوعون)
                'volunteers' => [
                    'count'       => number_format($volunteersCount),
                    'label'       => 'متطوعًا',
                    'description' => 'يساهمون يوميًا في التحقق الميداني والمتابعة.'
                ],

                // الكارت الرمادي العلوي (المستفيدون)
                'beneficiaries' => [
                    'count'       => number_format($beneficiariesCount) . '+', // إضافة علامة + كشكل جمالي للرقم الحقيقي
                    'label'       => 'عدد المستفيدين',
                    'description' => 'المستفيدون الذين حصلوا على الدعم المستمر من المنصة.'
                ],

                // الكارت الرمادي السفلي (المؤسسات)
                'foundations' => [
                    'count'       => number_format($foundationsCount),
                    'label'       => 'المؤسسات المستمرة',
                    'description' => 'المؤسسات المعتمدة والعاملة معنا لتفعيل الأثر الحقيقي.'
                ],

                // الكارت الأزرق السفلي (الحالات المنجزة)
                'completed_cases' => [
                    'count'       => number_format($completedCasesCount),
                    'label'       => 'الحالات المنجزة',
                    'description' => 'التي تم مساعدتها من خلال مساهماتكم.'
                ],

                // بيانات إضافية لاستخدامها في النصوص الجانبية (مثل إجمالي التبرعات)
                'general_info' => [
                    'total_donations_amount' => $totalDonationsAmount,
                ]
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب إحصائيات المنصة بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Platform Statistics Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب إحصائيات المنصة.',
                'data'    => null
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
