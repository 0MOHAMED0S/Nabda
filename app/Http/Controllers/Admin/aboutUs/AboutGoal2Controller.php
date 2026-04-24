<?php

namespace App\Http\Controllers\Admin\AboutUs; // تم تصحيح حرف A ليكون كبيراً لمطابقة معايير (PSR-4)

use App\Http\Controllers\Controller;
use App\Models\AboutGoal2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class AboutGoal2Controller extends Controller
{
    /**
     * عرض قائمة الأهداف (القسم الثاني)
     */
    public function index()
    {
        try {
            // جلب الأهداف مرتبة حسب الأقدم (ID) ليحافظ الترتيب على شكله في الموقع
            $goals = AboutGoal2::orderBy('id', 'asc')->get();
            return view('admin.aboutUs.aboutgoal2.index', compact('goals'));

        } catch (Exception $e) {
            Log::error("About Goal 2 Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات الأهداف. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة هدف جديد
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل مخصصة باللغة العربية
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
        ], [
            'title.required'       => 'يرجى إدخال عنوان الهدف.',
            'title.string'         => 'يجب أن يكون العنوان نصاً صالحاً.',
            'title.max'            => 'يجب ألا يتجاوز العنوان 255 حرفاً.',
            'description.required' => 'يرجى كتابة وصف للهدف.',
            'description.string'   => 'يجب أن يكون الوصف نصاً صالحاً.',
        ]);

        try {
            // 2. استخدام only لحماية قاعدة البيانات من تمرير أي حقول غير مرغوب فيها
            AboutGoal2::create($request->only(['title', 'description']));

            return back()->with('success', 'تم إضافة الهدف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Goal 2 Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في إضافة الهدف، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات هدف موجود
     */
    public function update(Request $request, $id)
    {
        // 1. التحقق من وجود الهدف (يعيد صفحة 404 تلقائياً إذا لم يكن موجوداً)
        $goal = AboutGoal2::findOrFail($id);

        // 2. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation
        try {
            $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
            ], [
                'title.required'       => 'يرجى إدخال عنوان الهدف.',
                'title.string'         => 'يجب أن يكون العنوان نصاً صالحاً.',
                'title.max'            => 'يجب ألا يتجاوز العنوان 255 حرفاً.',
                'description.required' => 'يرجى كتابة وصف للهدف.',
                'description.string'   => 'يجب أن يكون الوصف نصاً صالحاً.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لإعادة فتح الـ Modal الخاص بالتعديل تلقائياً بداخل الـ Blade
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $goal->id);
        }

        try {
            // 3. التحديث باستخدام الحقول الآمنة المحددة فقط
            $goal->update($request->only(['title', 'description']));

            return back()->with('success', 'تم تحديث بيانات الهدف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Goal 2 Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء تحديث البيانات. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * حذف هدف
     */
    public function destroy($id)
    {
        try {
            AboutGoal2::findOrFail($id)->delete();
            return back()->with('success', 'تم حذف الهدف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Goal 2 Delete Error: " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.');
        }
    }
}
