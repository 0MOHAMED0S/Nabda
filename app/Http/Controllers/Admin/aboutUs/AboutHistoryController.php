<?php

namespace App\Http\Controllers\Admin\AboutUs; // تم تصحيح حرف A ليكون كبيراً (PSR-4)

use App\Http\Controllers\Controller;
use App\Models\AboutHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class AboutHistoryController extends Controller
{
    /**
     * عرض قائمة مسيرة المنصة (المحطات التاريخية)
     */
    public function index()
    {
        try {
            // عرض المحطات التاريخية بنفس ترتيب إضافتها (ID)
            $histories = AboutHistory::orderBy('id', 'asc')->get();
            return view('admin.aboutUs.history.index', compact('histories'));

        } catch (Exception $e) {
            Log::error("About History Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات مسيرة المنصة. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة محطة تاريخية جديدة
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل عربية مخصصة
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
        ], [
            'title.required'       => 'يرجى إدخال عنوان المحطة (مثل: عام التأسيس).',
            'title.string'         => 'يجب أن يكون العنوان نصاً صالحاً.',
            'title.max'            => 'يجب ألا يتجاوز العنوان 255 حرفاً.',
            'description.required' => 'يرجى كتابة تفاصيل وأحداث هذه المحطة.',
            'description.string'   => 'يجب أن يكون الوصف نصاً صالحاً.',
        ]);

        try {
            // 2. إنشاء السجل باستخدام only للحماية الأمنية
            AboutHistory::create($request->only(['title', 'description']));

            return back()->with('success', 'تم إضافة المحطة التاريخية بنجاح.');

        } catch (Exception $e) {
            Log::error("About History Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في الإضافة، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات محطة تاريخية
     */
    public function update(Request $request, $id)
    {
        // 1. جلب المحطة (يعيد 404 إذا لم تكن موجودة)
        $history = AboutHistory::findOrFail($id);

        // 2. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation
        try {
            $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
            ], [
                'title.required'       => 'يرجى إدخال عنوان المحطة.',
                'title.string'         => 'يجب أن يكون العنوان نصاً صالحاً.',
                'title.max'            => 'يجب ألا يتجاوز العنوان 255 حرفاً.',
                'description.required' => 'يرجى كتابة تفاصيل المحطة.',
                'description.string'   => 'يجب أن يكون الوصف نصاً صالحاً.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لإعادة فتح نافذة التعديل (Modal) تلقائياً للمستخدم ليرى الخطأ
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $history->id);
        }

        try {
            // 3. التحديث بالحقول الآمنة فقط
            $history->update($request->only(['title', 'description']));

            return back()->with('success', 'تم تحديث بيانات المحطة بنجاح.');

        } catch (Exception $e) {
            Log::error("About History Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء التحديث. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * حذف محطة تاريخية
     */
    public function destroy($id)
    {
        try {
            AboutHistory::findOrFail($id)->delete();
            return back()->with('success', 'تم حذف المحطة بنجاح من مسيرة المنصة.');

        } catch (Exception $e) {
            Log::error("About History Delete Error: " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.');
        }
    }
}
