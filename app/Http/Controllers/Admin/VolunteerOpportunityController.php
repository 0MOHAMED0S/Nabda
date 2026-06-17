<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class VolunteerOpportunityController extends Controller
{
    /**
     * عرض قائمة الفرص التطوعية مع الفلترة المتقدمة
     */
    public function index(Request $request)
    {
        try {
            // جلب المؤسسات المعتمدة لعرضها في قائمة الفلتر
            $foundations = Foundation::where('approval_status', 'approved')->get();

            // بناء الاستعلام وجلب العلاقات
            $query = VolunteerOpportunity::with(['foundation'])
                // 1. حساب عدد المتطوعين الفعليين (المقبولين أو الذين حضروا)
                ->withCount(['volunteers as accepted_volunteers_count' => function ($q) {
                    $q->whereIn('opportunity_volunteer.status', ['accepted', 'attended']);
                }])
                // 2. جلب أحدث المتطوعين المقبولين لعرضهم في المودال (مع جلب بياناتهم)
                ->with(['volunteers' => function ($q) {
                    $q->whereIn('opportunity_volunteer.status', ['accepted', 'attended'])
                      ->latest('opportunity_volunteer.created_at')
                      ->take(10);
                }]);

            // ==========================================
            // 🎯 تطبيق الفلاتر (Filters)
            // ==========================================

            if ($request->filled('search')) {
                $query->where('title', 'like', '%' . $request->search . '%')
                      ->orWhere('location', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('foundation_id') && $request->foundation_id !== 'all') {
                $query->where('foundation_id', $request->foundation_id);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('category') && $request->category !== 'all') {
                $query->where('category', $request->category);
            }

            // جلب البيانات مع الترقيم
            $opportunities = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();

            // حساب نسبة اكتمال العدد المطلوب قبل الإرسال للواجهة
            $opportunities->getCollection()->transform(function ($opp) {
                $required = $opp->required_volunteers > 0 ? $opp->required_volunteers : 1; // تجنب القسمة على صفر
                $opp->calculated_percentage = min(100, round(($opp->accepted_volunteers_count / $required) * 100));

                return $opp;
            });

            return view('admin.opportunities.index', compact('opportunities', 'foundations'));

        } catch (Exception $e) {
            Log::error("Admin Volunteer Opportunities Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب الفرص التطوعية.');
        }
    }
}
