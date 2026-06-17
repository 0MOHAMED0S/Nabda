<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoundationCase;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationCaseController extends Controller
{
    /**
     * عرض قائمة الحالات مع الفلترة المتقدمة
     */
    public function index(Request $request)
    {
        try {
            // جلب المؤسسات المعتمدة لعرضها في قائمة الفلتر
            $foundations = Foundation::where('approval_status', 'approved')->get();

            // بناء الاستعلام مع العلاقات الأساسية وحساب التبرعات
            $query = FoundationCase::with(['foundation', 'service.category'])
                ->withCount(['donations as donors_count' => function ($q) {
                    $q->where('status', 'completed');
                }])
                ->withSum(['donations as collected_amount' => function ($q) {
                    $q->where('status', 'completed');
                }], 'amount')
                // 🎯 جلب آخر 10 تبرعات مكتملة لعرضها في تفاصيل المودال
                ->with(['donations' => function ($q) {
                    $q->where('status', 'completed')->with('user')->latest()->take(10);
                }]);

            // ==========================================
            // 🎯 تطبيق الفلاتر (Filters)
            // ==========================================

            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('beneficiary_name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('foundation_id') && $request->foundation_id !== 'all') {
                $query->where('foundation_id', $request->foundation_id);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('goal_type') && $request->goal_type !== 'all') {
                $query->where('goal_type', $request->goal_type);
            }

            // جلب البيانات مع الترقيم
            $cases = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

            // حساب النسب المئوية قبل إرسالها للواجهة
            $cases->getCollection()->transform(function ($case) {
                $isFinancial = in_array($case->goal_type, ['financial', 'both']);
                $collected = $isFinancial ? ($case->collected_amount ?? 0) : ($case->donors_count ?? 0);
                $percentage = 0;

                if ($isFinancial && $case->target_amount > 0) {
                    $percentage = min(100, round(($collected / $case->target_amount) * 100, 1));
                } elseif (!$isFinancial && $case->status === 'completed') {
                    $percentage = 100;
                }

                $case->calculated_percentage = $percentage;
                $case->calculated_collected = $collected;

                return $case;
            });

            return view('admin.cases.index', compact('cases', 'foundations'));

        } catch (Exception $e) {
            Log::error("Admin Foundation Cases Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب الحالات.');
        }
    }
}
