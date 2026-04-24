<?php

namespace App\Http\Controllers\Admin\AboutUs; // تم تعديل aboutUs إلى AboutUs (حرف كبير) لتطابق معايير PSR-4

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AboutUsController extends Controller
{
    /**
     * عرض صفحة تعديل بيانات (من نحن)
     */
    public function index()
    {
        try {
            // جلب السجل الأول (الذي تم إنشاؤه عبر الـ Seeder) أو إنشاء نسخة فارغة للعرض
            $about = AboutUs::first() ?? new AboutUs();

            return view('admin.aboutUs.about_us.index', compact('about'));

        } catch (Exception $e) {
            // تسجيل الخطأ وحماية المستخدم من رؤية شاشة الخطأ التقنية
            Log::error("About Us Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات صفحة "من نحن". يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حفظ وتحديث بيانات (من نحن)
     */
    public function update(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل خطأ شاملة لجميع الحالات
        $request->validate([
            'title'        => 'required|string|max:255',
            'description1' => 'required|string',
            'description2' => 'nullable|string',
            'video_url'    => 'required|url',
        ], [
            'title.required'        => 'يرجى إدخال العنوان الرئيسي للقسم.',
            'title.string'          => 'يجب أن يكون العنوان نصاً صالحاً.',
            'title.max'             => 'يجب ألا يتجاوز العنوان 255 حرفاً.',

            'description1.required' => 'الفقرة الأولى من الوصف مطلوبة.',
            'description1.string'   => 'يجب أن تكون الفقرة الأولى نصاً صالحاً.',

            'description2.string'   => 'يجب أن تكون الفقرة الثانية نصاً صالحاً.',

            'video_url.required'    => 'رابط الفيديو الترويجي مطلوب.',
            'video_url.url'         => 'يرجى إدخال رابط إلكتروني صحيح (مثال: https://youtube.com/...).',
        ]);

        try {
            // 2. تحديث السجل أو إنشائه باستخدام دالة (updateOrCreate) الاحترافية والمختصرة
            // 3. استخدام only بدلاً من all لحماية قواعد البيانات
            AboutUs::updateOrCreate(
                ['id' => 1], // نبحث عن السجل رقم 1 (لأننا نحتاج سجلاً واحداً فقط لهذا القسم)
                $request->only(['title', 'description1', 'description2', 'video_url']) // البيانات المسموح بتحديثها
            );

            return back()->with('success', 'تم تحديث بيانات قسم "من نحن" بنجاح.');

        } catch (Exception $e) {
            // تسجيل الخطأ التقني لمدير النظام
            Log::error("About Us Update Error: " . $e->getMessage());

            // إعادة رسالة خطأ أنيقة للمستخدم
            return back()->with('error', 'عذراً، حدث خطأ تقني أثناء الحفظ. يرجى التأكد من البيانات والمحاولة مرة أخرى.');
        }
    }
}
