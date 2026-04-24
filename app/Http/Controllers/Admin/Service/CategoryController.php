<?php

namespace App\Http\Controllers\Admin\Service; // تأكدنا من أن حرف S كبير (PSR-4)

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class CategoryController extends Controller
{
    /**
     * عرض قائمة الأقسام
     */
    public function index()
    {
        try {
            // جلب الأقسام من الأحدث للأقدم
            $categories = Category::latest()->get();
            return view('admin.service.categories.index', compact('categories'));

        } catch (Exception $e) {
            Log::error("Category Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب بيانات الأقسام. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة قسم جديد
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل مخصصة وتفعيل منع التكرار (unique)
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ], [
            'name.required' => 'يرجى إدخال اسم القسم.',
            'name.string'   => 'اسم القسم يجب أن يكون نصاً صالحاً.',
            'name.max'      => 'اسم القسم طويل جداً (الحد الأقصى 255 حرف).',
            'name.unique'   => 'هذا القسم موجود بالفعل، يرجى اختيار اسم آخر.',
        ]);

        try {
            // 2. استخدام only لحماية قاعدة البيانات وتخزين القسم
            Category::create($request->only(['name']));

            return back()->with('success', 'تمت إضافة القسم الجديد بنجاح.');

        } catch (Exception $e) {
            Log::error("Category Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في إضافة القسم، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات قسم موجود
     */
    public function update(Request $request, $id)
    {
        // 1. التأكد من وجود القسم (يعيد 404 تلقائياً إذا لم يكن موجوداً)
        $category = Category::findOrFail($id);

        // 2. التحقق من البيانات داخل try/catch مع استثناء القسم الحالي من شرط التكرار
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $id,
            ], [
                'name.required' => 'يرجى إدخال اسم القسم.',
                'name.string'   => 'اسم القسم يجب أن يكون نصاً صالحاً.',
                'name.max'      => 'اسم القسم طويل جداً.',
                'name.unique'   => 'يوجد قسم آخر يحمل هذا الاسم بالفعل.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لضمان بقاء الـ Modal مفتوحاً في الـ Blade مع عرض الأخطاء
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $id);
        }

        try {
            // 3. التحديث بالحقول المحددة والآمنة فقط
            $category->update($request->only(['name']));

            return back()->with('success', 'تم تحديث بيانات القسم بنجاح.');

        } catch (Exception $e) {
            Log::error("Category Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء التحديث. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * حذف قسم
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);

            // ملاحظة هامة: بما أنك تستخدم cascadeOnDelete في الـ Migration،
            // سيتم حذف جميع الخدمات (Services) المرتبطة بهذا القسم تلقائياً من قاعدة البيانات.
            $category->delete();

            return back()->with('success', 'تم حذف القسم بنجاح.');

        } catch (Exception $e) {
            Log::error("Category Delete Error: " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، قد يكون هناك مشكلة في قاعدة البيانات. يرجى المحاولة لاحقاً.');
        }
    }
}
