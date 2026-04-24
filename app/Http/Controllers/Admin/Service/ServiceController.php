<?php

namespace App\Http\Controllers\Admin\Service; // تأكد من أن حرف S كبير (PSR-4)

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ServiceController extends Controller
{
    /**
     * عرض قائمة الخدمات
     */
    public function index()
    {
        try {
            // جلب الخدمات مع الأقسام (Eager Loading) وتمرير الأقسام للـ Select Box
            $services = Service::with('category')->latest()->get();
            $categories = Category::all();

            return view('admin.service.services.index', compact('services', 'categories'));

        } catch (Exception $e) {
            Log::error("Service Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات الخدمات. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة خدمة جديدة
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل عربية مخصصة
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ], [
            'category_id.required' => 'يرجى اختيار القسم.',
            'category_id.exists'   => 'القسم المختار غير صالح أو تم حذفه.',
            'title.required'       => 'يرجى إدخال عنوان الخدمة.',
            'description.required' => 'يرجى إدخال تفاصيل وصف الخدمة.',
            'image.required'       => 'صورة الخدمة مطلوبة.',
            'image.image'          => 'الملف المرفق يجب أن يكون صورة.',
            'image.mimes'          => 'صيغ الصور المدعومة: jpeg, png, jpg, webp.',
            'image.max'            => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.'
        ]);

        try {
            // 2. تجميع البيانات بشكل آمن باستخدام only
            $data = $request->only(['category_id', 'title', 'description']);

            // 3. رفع الصورة وحفظ المسار
            $data['image'] = $request->file('image')->store('services', 'public');

            // 4. إنشاء السجل
            Service::create($data);

            return back()->with('success', 'تمت إضافة الخدمة بنجاح.');

        } catch (Exception $e) {
            Log::error("Service Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في الإضافة، حدث خطأ تقني. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات خدمة
     */
    public function update(Request $request, $id)
    {
        // 1. جلب الخدمة (يُعيد 404 تلقائياً إذا لم تكن موجودة)
        $service = Service::findOrFail($id);

        // 2. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id',
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
            ], [
                'category_id.required' => 'يرجى اختيار القسم.',
                'category_id.exists'   => 'القسم المختار غير صالح.',
                'title.required'       => 'يرجى إدخال عنوان الخدمة.',
                'description.required' => 'يرجى إدخال تفاصيل الوصف.',
                'image.image'          => 'الملف المرفق يجب أن يكون صورة.',
                'image.mimes'          => 'صيغ الصور المدعومة: jpeg, png, jpg, webp.',
                'image.max'            => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.'
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لإعادة فتح نافذة التعديل تلقائياً في الواجهة عند الخطأ
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $service->id);
        }

        try {
            // 3. تجهيز البيانات للتحديث بشكل آمن
            $data = $request->only(['category_id', 'title', 'description']);

            // 4. معالجة تحديث الصورة
            if ($request->hasFile('image') && $request->file('image')->isValid()) {

                // حذف الصورة القديمة بأمان (منع الكود من التوقف إذا كانت الصورة مفقودة من السيرفر)
                try {
                    if (!empty($service->image) && Storage::disk('public')->exists($service->image)) {
                        Storage::disk('public')->delete($service->image);
                    }
                } catch (Exception $fileError) {
                    Log::warning("Failed to delete old service image (ID: $service->id): " . $fileError->getMessage());
                }

                // رفع الصورة الجديدة
                $data['image'] = $request->file('image')->store('services', 'public');
            }

            // 5. التحديث النهائي
            $service->update($data);

            return back()->with('success', 'تم تحديث بيانات الخدمة بنجاح.');

        } catch (Exception $e) {
            Log::error("Service Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء التحديث. يرجى المحاولة مرة أخرى.');
        }
    }

    /**
     * حذف خدمة
     */
    public function destroy($id)
    {
        try {
            $service = Service::findOrFail($id);

            // حذف الصورة المرتبطة بأمان قبل حذف السجل
            try {
                if (!empty($service->image) && Storage::disk('public')->exists($service->image)) {
                    Storage::disk('public')->delete($service->image);
                }
            } catch (Exception $fileError) {
                Log::warning("Failed to delete service image during deletion (ID: $id): " . $fileError->getMessage());
            }

            // حذف السجل
            $service->delete();

            return back()->with('success', 'تم حذف الخدمة بنجاح.');

        } catch (Exception $e) {
            Log::error("Service Delete Error (ID: $id): " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، حدث خطأ تقني غير متوقع. يرجى المحاولة لاحقاً.');
        }
    }
}
