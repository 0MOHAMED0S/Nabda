<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ZakatCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ZakatConditionController extends Controller
{
    /**
     * عرض قائمة شروط/بطاقات الزكاة
     */
    public function index()
    {
        try {
            $conditions = ZakatCondition::orderBy('order', 'asc')->get();
            return view('admin.zakat.index', compact('conditions'));
        } catch (Exception $e) {
            Log::error("Zakat Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء تحميل بيانات شروط الزكاة. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة شرط زكاة جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'icon'        => 'required|string',
        ], [
            'title.required'       => 'يرجى إدخال عنوان الشرط أو البطاقة.',
            'title.string'         => 'عنوان البطاقة يجب أن يكون نصاً صالحاً.',
            'title.max'            => 'عنوان البطاقة يجب ألا يتجاوز 255 حرفاً.',
            'description.required' => 'يرجى كتابة وصف مختصر يوضح الشرط.',
            'icon.required'        => 'يرجى اختيار أيقونة للبطاقة.',
        ]);

        try {
            ZakatCondition::create($request->only(['title', 'description', 'icon']));
            return back()->with('success', 'تم إضافة شرط جديد بنجاح إلى القائمة.');
        } catch (Exception $e) {
            Log::error("Zakat Store Error: " . $e->getMessage());
            return back()->with('error', 'عذراً، فشل إضافة الشرط الجديد بسبب خطأ تقني. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات شرط زكاة موجود
     */
    public function update(Request $request, ZakatCondition $zakatCondition)
    {
        // 1. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation
        try {
            $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'icon'        => 'required|string',
            ], [
                'title.required'       => 'العنوان مطلوب لتحديث البيانات.',
                'title.string'         => 'عنوان البطاقة يجب أن يكون نصاً صالحاً.',
                'title.max'            => 'عنوان البطاقة يجب ألا يتجاوز 255 حرفاً.',
                'description.required' => 'الوصف مطلوب لتحديث البيانات.',
                'icon.required'        => 'الأيقونة مطلوبة.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لإعادة فتح الـ Modal الخاص بالتعديل تلقائياً في الـ Blade
            return back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('edit_id', $zakatCondition->id);
        }

        try {
            // 2. تحديث البيانات باستخدام الحقول الآمنة فقط
            $zakatCondition->update($request->only(['title', 'description', 'icon']));

            return back()->with('success', 'تم تحديث بيانات الشرط بنجاح.');
        } catch (Exception $e) {
            Log::error("Zakat Update Error: " . $e->getMessage());
            return back()->with('error', 'فشل تحديث البيانات، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حذف شرط زكاة
     */
    public function destroy(ZakatCondition $zakatCondition)
    {
        try {
            $zakatCondition->delete();
            return back()->with('success', 'تم حذف البطاقة بنجاح من النظام.');
        } catch (Exception $e) {
            Log::error("Zakat Delete Error: " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، قد يكون العنصر مرتبطاً ببيانات أخرى في النظام.');
        }
    }
}
