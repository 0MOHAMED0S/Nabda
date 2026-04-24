<?php

namespace App\Http\Controllers\Admin\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class FoundationController extends Controller
{
    /**
     * عرض قائمة المؤسسات مع الإحصائيات
     */
    public function index()
    {
        try {
            $foundations = Foundation::orderBy('created_at', 'desc')->paginate(15);

            $stats = [
                'total'    => Foundation::count(),
                'approved' => Foundation::where('approval_status', 'approved')->count(),
                'pending'  => Foundation::where('approval_status', 'pending')->count(),
                'rejected' => Foundation::where('approval_status', 'rejected')->count(),
            ];

            // تم تصحيح اسم العرض هنا ليكون admin.foundations.index
            return view('admin.foundations.index', compact('foundations', 'stats'));
        } catch (Exception $e) {
            Log::error("Foundation Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات المؤسسات.');
        }
    }

    /**
     * تحديث بيانات المؤسسة وحالة الاعتماد
     */
    public function update(Request $request, Foundation $foundation)
    {
        try {
            $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|max:255|unique:foundations,email,' . $foundation->id,
                'phone'                 => 'required|string|max:20|unique:foundations,phone,' . $foundation->id,
                'type'                  => 'required|string|max:100',
                'approval_status'       => 'required|in:pending,approved,rejected',
                'status'                => 'required|in:active,inactive',
                'license_number'        => 'required|string|max:255',
                'supervising_authority' => 'required|string|max:255',
            ], [
                'name.required'       => 'اسم المؤسسة مطلوب.',
                'email.unique'        => 'هذا البريد الإلكتروني مستخدم مسبقاً.',
                'phone.unique'        => 'هذا الهاتف مستخدم مسبقاً.',
                'approval_status.in'  => 'حالة الاعتماد غير صالحة.',
                'status.in'           => 'حالة الحساب غير صالحة.',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $foundation->id);
        }

        try {
            $foundation->update($request->only([
                'name',
                'email',
                'phone',
                'type',
                'approval_status',
                'status',
                'license_number',
                'supervising_authority'
            ]));

            return back()->with('success', 'تم تحديث بيانات واعتماد المؤسسة بنجاح.');
        } catch (Exception $e) {
            Log::error("Foundation Update Error ID {$foundation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث البيانات.');
        }
    }

    /**
     * رفض طلب المؤسسة (إيقاف وعدم اعتماد)
     */
    public function reject(Foundation $foundation)
    {
        try {
            $foundation->update([
                'approval_status' => 'rejected',
                'status'          => 'inactive'
            ]);

            return back()->with('success', 'تم رفض طلب المؤسسة وإيقاف حسابها بنجاح.');
        } catch (Exception $e) {
            Log::error("Foundation Reject Error ID {$foundation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء محاولة رفض المؤسسة.');
        }
    }

    /**
     * حذف المؤسسة نهائياً مع ملفاتها
     */
    public function destroy(Foundation $foundation)
    {
        try {
            $files = [
                $foundation->license_image,
                $foundation->commercial_register,
                $foundation->tax_card,
                $foundation->accreditation_letter,
                $foundation->headquarters_image,
                $foundation->logo
            ];

            foreach ($files as $file) {
                if (!empty($file) && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            $foundation->delete();

            return back()->with('success', 'تم حذف المؤسسة وجميع مستنداتها بنجاح.');
        } catch (Exception $e) {
            Log::error("Foundation Delete Error ID {$foundation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المؤسسة.');
        }
    }

    /**
     * عرض قائمة المؤسسات المعتمدة فقط (المقبولة)
     */
    public function approvedIndex()
    {
        try {
            // جلب المؤسسات المعتمدة فقط
            $foundations = Foundation::where('approval_status', 'approved')
                                     ->orderBy('updated_at', 'desc')
                                     ->paginate(15);

            $stats = [
                'total_approved' => Foundation::where('approval_status', 'approved')->count(),
                'active'         => Foundation::where('approval_status', 'approved')->where('status', 'active')->count(),
                'inactive'       => Foundation::where('approval_status', 'approved')->where('status', 'inactive')->count(),
            ];

            return view('admin.foundations.approve', compact('foundations', 'stats'));
        } catch (Exception $e) {
            Log::error("Foundation Approved Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات المؤسسات المعتمدة.');
        }
    }
}
