<?php

namespace App\Http\Controllers\Admin\Articles; // يفضل استخدام حروف كبيرة (PSR-4)

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ArticleController extends Controller
{
    /**
     * عرض قائمة المقالات والأخبار
     */
    public function index()
    {
        try {
            $articles = Article::orderBy('published_date', 'desc')->paginate(15);
            return view('admin.articles.index', compact('articles'));

        } catch (Exception $e) {
            Log::error("Article Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب البيانات. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حفظ مقال أو خبر جديد
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل مخصصة
        $request->validate([
            'type'           => 'required|in:article,news',
            'image'          => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'main_title'     => 'required|string|max:255',
            'second_title'   => 'nullable|string|max:255',
            'description'    => 'required|string',
            'published_date' => 'required|date',
        ], [
            'type.required'           => 'يرجى تحديد نوع المحتوى (مقال أو خبر).',
            'type.in'                 => 'نوع المحتوى المختار غير صالح.',
            'image.required'          => 'صورة الغلاف مطلوبة.',
            'image.image'             => 'الملف المرفق يجب أن يكون صورة.',
            'image.mimes'             => 'صيغ الصور المقبولة: jpeg, png, jpg, webp.',
            'image.max'               => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
            'main_title.required'     => 'العنوان الرئيسي مطلوب.',
            'main_title.max'          => 'العنوان الرئيسي يجب ألا يتجاوز 255 حرفاً.',
            'description.required'    => 'محتوى الوصف مطلوب.',
            'published_date.required' => 'تاريخ النشر مطلوب.',
            'published_date.date'     => 'تاريخ النشر غير صالح.',
        ]);

        try {
            // 2. استخدام only لتجميع البيانات بأمان
            $data = $request->only(['type', 'main_title', 'second_title', 'description', 'published_date']);

            // 3. رفع الصورة وحفظ المسار
            $data['image'] = $request->file('image')->store('articles', 'public');

            // 4. إنشاء السجل
            Article::create($data);

            // تخصيص رسالة النجاح حسب النوع
            $typeLabel = $request->type == 'news' ? 'الخبر' : 'المقال';
            return back()->with('success', "تم إضافة {$typeLabel} بنجاح.");

        } catch (Exception $e) {
            Log::error("Article Store Error: " . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ تقني أثناء الحفظ، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات مقال موجود
     */
    public function update(Request $request, Article $article)
    {
        // 1. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation لفتح المودال
        try {
            $request->validate([
                'type'           => 'required|in:article,news',
                'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'main_title'     => 'required|string|max:255',
                'second_title'   => 'nullable|string|max:255',
                'description'    => 'required|string',
                'published_date' => 'required|date',
            ], [
                'type.required'           => 'يرجى تحديد نوع المحتوى.',
                'type.in'                 => 'نوع المحتوى غير صالح.',
                'image.image'             => 'الملف المرفق يجب أن يكون صورة.',
                'image.mimes'             => 'صيغ الصور المقبولة: jpeg, png, jpg, webp.',
                'image.max'               => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
                'main_title.required'     => 'العنوان الرئيسي مطلوب.',
                'main_title.max'          => 'العنوان الرئيسي يجب ألا يتجاوز 255 حرفاً.',
                'description.required'    => 'محتوى الوصف مطلوب.',
                'published_date.required' => 'تاريخ النشر مطلوب.',
                'published_date.date'     => 'تاريخ النشر غير صالح.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لضمان بقاء المودال مفتوحاً
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $article->id);
        }

        try {
            // 2. تجميع البيانات بأمان
            $data = $request->only(['type', 'main_title', 'second_title', 'description', 'published_date']);

            // 3. معالجة تحديث الصورة
            if ($request->hasFile('image') && $request->file('image')->isValid()) {

                // حذف الصورة القديمة بأمان
                try {
                    if (!empty($article->image) && Storage::disk('public')->exists($article->image)) {
                        Storage::disk('public')->delete($article->image);
                    }
                } catch (Exception $fileError) {
                    Log::warning("Failed to delete old article image (ID: {$article->id}): " . $fileError->getMessage());
                }

                // رفع الصورة الجديدة
                $data['image'] = $request->file('image')->store('articles', 'public');
            }

            // 4. التحديث النهائي
            $article->update($data);

            return back()->with('success', 'تم تحديث البيانات بنجاح.');

        } catch (Exception $e) {
            Log::error("Article Update Error ID {$article->id}: " . $e->getMessage());
            return back()->withInput()->with('error', 'فشل التحديث تقنياً، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حذف المقال نهائياً مع صورته
     */
    public function destroy(Article $article)
    {
        try {
            // حذف الصورة المرفقة بأمان
            try {
                if (!empty($article->image) && Storage::disk('public')->exists($article->image)) {
                    Storage::disk('public')->delete($article->image);
                }
            } catch (Exception $fileError) {
                Log::warning("Failed to delete article image during record deletion (ID: {$article->id}): " . $fileError->getMessage());
            }

            // حذف السجل من قاعدة البيانات
            $article->delete();

            return back()->with('success', 'تم حذف المحتوى بنجاح.');

        } catch (Exception $e) {
            Log::error("Article Delete Error ID {$article->id}: " . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف حالياً بسبب خطأ فني.');
        }
    }
}
