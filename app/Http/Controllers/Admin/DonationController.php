<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class DonationController extends Controller
{
    /**
     * عرض قائمة التبرعات مع الفلترة والإحصائيات
     */
    public function index(Request $request)
    {
        try {
            // 1. جلب المؤسسات لفلتر البحث
            $foundations = Foundation::where('approval_status', 'approved')->get();

            // 2. بناء الاستعلام الأساسي مع العلاقات
            $query = Donation::with(['foundation', 'foundationCase', 'user']);

            // ==========================================
            // 🎯 تطبيق الفلاتر (Filters)
            // ==========================================

            // البحث برقم الطلب، اسم المتبرع، أو الهاتف
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('donor_name', 'like', "%{$search}%")
                      ->orWhere('donor_phone', 'like', "%{$search}%")
                      ->orWhere('paymob_order_id', 'like', "%{$search}%");
                });
            }

            if ($request->filled('foundation_id') && $request->foundation_id !== 'all') {
                $query->where('foundation_id', $request->foundation_id);
            }

            if ($request->filled('donation_type') && $request->donation_type !== 'all') {
                $query->where('donation_type', $request->donation_type);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // ==========================================
            // 📊 الإحصائيات العلوية السريعة
            // ==========================================
            $stats = [
                'total_financial' => (clone $query)->where('donation_type', 'financial')->where('status', 'completed')->sum('amount'),
                'total_inkind'    => (clone $query)->where('donation_type', '!=', 'financial')->where('status', 'completed')->count(),
                'pending_count'   => (clone $query)->where('status', 'pending')->count(),
            ];

            // 3. جلب البيانات مع الترقيم
            $donations = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

            return view('admin.donations.index', compact('donations', 'foundations', 'stats'));

        } catch (Exception $e) {
            Log::error("Admin Donations Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب بيانات التبرعات.');
        }
    }

    /**
     * تحديث حالة التبرع (مفيد جداً للتبرعات العينية عند استلامها)
     */
    public function updateStatus(Request $request, Donation $donation)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,completed,failed,cancelled'
            ]);

            $donation->update(['status' => $request->status]);

            return back()->with('success', 'تم تحديث حالة التبرع بنجاح.');
        } catch (Exception $e) {
            Log::error("Admin Donation Update Status Error ID {$donation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث الحالة.');
        }
    }
}
