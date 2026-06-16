<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Foundation;
use App\Models\Volunteer;
use App\Models\Donation;
// use App\Models\FoundationCase; // قم بإزالة التعليق إذا أردت جلب الحالات
use Illuminate\Support\Facades\Log;
use Exception;

class DashboardController extends Controller
{
    /**
     * عرض الصفحة الرئيسية للإدارة مع الإحصائيات الشاملة
     */
public function index(\Illuminate\Http\Request $request)
    {
        try {
            // 1. التقاط الفلتر من الرابط (الافتراضي هو 'all')
            $range = $request->query('range', 'all');
            $queryDate = null;

            // 2. تحديد تاريخ البداية بناءً على الفلتر المختار
            if ($range === 'today') {
                $queryDate = now()->startOfDay();
            } elseif ($range === 'week') {
                $queryDate = now()->startOfWeek();
            } elseif ($range === 'month') {
                $queryDate = now()->startOfMonth();
            } elseif ($range === 'year') {
                $queryDate = now()->startOfYear();
            }

            // 3. دالة مساعدة (Closure) لتطبيق فلتر التاريخ
            $applyDateFilter = function($query) use ($queryDate) {
                if ($queryDate) {
                    $query->where('created_at', '>=', $queryDate);
                }
                return $query;
            };

            // 4. تجميع الإحصائيات مع تطبيق الفلتر
            $stats = [
                // --- إحصائيات المجتمع ---
                'users_total'          => \App\Models\User::where($applyDateFilter)->count(),
                'foundations_approved' => \App\Models\Foundation::where('approval_status', 'approved')->where($applyDateFilter)->count(),
                'foundations_pending'  => \App\Models\Foundation::where('approval_status', 'pending')->count(),
                'volunteers_approved'  => \App\Models\Volunteer::where('status', 'approved')->where($applyDateFilter)->count(),
                'volunteers_pending'   => \App\Models\Volunteer::where('status', 'pending')->count(),

                // --- 🎯 إحصائيات التبرعات بكافة أنواعها ---

                // التبرعات المالية (قيمة وعدد)
                'financial_amount' => \App\Models\Donation::where('donation_type', 'financial')
                                        ->where('status', 'completed')
                                        ->where($applyDateFilter)
                                        ->sum('amount'),
                'financial_count'  => \App\Models\Donation::where('donation_type', 'financial')
                                        ->where('status', 'completed')
                                        ->where($applyDateFilter)
                                        ->count(),

                // التبرعات العينية (عدد العمليات المكتملة)
                // التبرعات العينية (أي تبرع نوعه لا يساوي financial)
'inkind_count'     => \App\Models\Donation::where('donation_type', '!=', 'financial')
                        ->where('status', 'completed')
                        ->where($applyDateFilter)
                        ->count(),

                // التبرعات المعلقة (سواء مالي بانتظار التأكيد أو عيني بانتظار الاستلام)
                // لا نطبق عليها الفلتر الزمني لأنها مهام يجب إنجازها بغض النظر عن متى طُلبت
                'donations_pending' => \App\Models\Donation::where('status', 'pending')->count(),
            ];

            return view('admin.index', compact('stats', 'range'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Admin Dashboard Stats Error: " . $e->getMessage());
            return view('admin.index', ['stats' => [], 'range' => 'all'])->with('error', 'حدث خطأ تقني أثناء جلب إحصائيات لوحة التحكم.');
        }
    }
}
