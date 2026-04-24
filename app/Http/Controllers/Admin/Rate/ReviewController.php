<?php

namespace App\Http\Controllers\Admin\Rate;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Exception;

class ReviewController extends Controller
{
public function index()
{
    $reviews = Review::orderBy('is_approved', 'asc')
                     ->orderBy('created_at', 'desc')
                     ->paginate(15);

    // حساب الإحصائيات
    $stats = [
        'total'    => Review::count(),
        'approved' => Review::where('is_approved', true)->count(),
        'pending'  => Review::where('is_approved', false)->count(),
    ];

    return view('admin.reviews.index', compact('reviews', 'stats'));
}

    public function toggleApprove(Review $review)
    {
        try {
            $review->update([
                'is_approved' => !$review->is_approved
            ]);

            $msg = $review->is_approved ? 'تم اعتماد التقييم بنجاح.' : 'تم إخفاء التقييم من الموقع.';
            return back()->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', 'فشلت العملية، حاول مرة أخرى.');
        }
    }

    public function destroy(Review $review)
    {
        try {
            $review->delete();
            return back()->with('success', 'تم حذف التقييم نهائياً.');
        } catch (Exception $e) {
            return back()->with('error', 'تعذر الحذف حالياً.');
        }
    }
}
