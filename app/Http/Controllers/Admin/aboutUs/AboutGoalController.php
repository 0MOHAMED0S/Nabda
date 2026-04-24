<?php

namespace App\Http\Controllers\Admin\AboutUs; // تم تصحيح حرف A ليكون كبيراً (PSR-4)

use App\Http\Controllers\Controller;
use App\Models\AboutGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class AboutGoalController extends Controller
{
    /**
     * عرض قائمة الأهداف
     */
    public function index()
    {
        try {
            // جلب الأهداف مرتبة حسب الأقدم ليحافظ الترتيب على شكله
            $goals = AboutGoal::orderBy('id', 'asc')->get();
            return view('admin.aboutUs.aboutgoal1.index', compact('goals'));

        } catch (Exception $e) {
            Log::error("About Goals Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات أهداف المنصة. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة هدف جديد
     */
    public function store(Request $request)
    {
        // 1. التحقق من صحة البيانات مع رسائل عربية مخصصة
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
            // 2. استخدام only لحماية قاعدة البيانات (Mass Assignment Protection)
            AboutGoal::create($request->only(['title', 'description']));

            return back()->with('success', 'تم إضافة الهدف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Goal Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في إضافة الهدف، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث هدف موجود
     */
    public function update(Request $request, $id)
    {
        // 1. جلب الهدف (سيظهر خطأ 404 تلقائياً إذا لم يكن موجوداً)
        $goal = AboutGoal::findOrFail($id);

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
            // إرسال edit_id لإعادة فتح الـ Modal الخاص بالتعديل تلقائياً في الـ Blade
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $goal->id);
        }

        try {
            // 3. التحديث باستخدام الحقول المحددة فقط
            $goal->update($request->only(['title', 'description']));

            return back()->with('success', 'تم تحديث بيانات الهدف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Goal Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء تحديث البيانات. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * حذف هدف
     */
    public function destroy($id)
    {
        try {
            AboutGoal::findOrFail($id)->delete();
            return back()->with('success', 'تم حذف الهدف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Goal Delete Error: " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.');
        }
    }
}
