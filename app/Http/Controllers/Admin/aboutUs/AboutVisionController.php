<?php

namespace App\Http\Controllers\Admin\AboutUs; // تأكد من أن حرف A كبير

use App\Http\Controllers\Controller;
use App\Models\AboutVision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class AboutVisionController extends Controller
{
    /**
     * عرض قائمة الرؤية والمهمة
     */
    public function index()
    {
        try {
            $visions = AboutVision::orderBy('id', 'asc')->get();
            return view('admin.aboutUs.about_visions.index', compact('visions'));

        } catch (Exception $e) {
            Log::error("About Vision Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب البيانات. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة عنصر جديد (رؤية / مهمة)
     */
    public function store(Request $request)
    {
        // 1. تحقق من البيانات مع رسائل مخصصة
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048', // SVG مهم هنا للأيقونات
        ], [
            'title.required'       => 'يرجى إدخال عنوان الرؤية أو المهمة.',
            'title.string'         => 'يجب أن يكون العنوان نصاً صالحاً.',
            'title.max'            => 'يجب ألا يتجاوز العنوان 255 حرفاً.',
            'description.required' => 'يرجى إدخال الوصف التفصيلي.',
            'image.required'       => 'الصورة/الأيقونة مطلوبة.',
            'image.image'          => 'يجب أن يكون الملف المرفق صورة.',
            'image.mimes'          => 'صيغ الصور المدعومة هي: jpeg, png, jpg, svg, webp.',
            'image.max'            => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ]);

        try {
            // 2. رفع الصورة وحفظ المسار
            $path = $request->file('image')->store('visions', 'public');

            // 3. إنشاء السجل
            AboutVision::create([
                'title'       => $request->title,
                'description' => $request->description,
                'image'       => $path
            ]);

            return back()->with('success', 'تمت إضافة العنصر بنجاح.');

        } catch (Exception $e) {
            Log::error("About Vision Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في الإضافة، حدث خطأ تقني. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث عنصر موجود
     */
    public function update(Request $request, $id)
    {
        // 1. جلب العنصر للتحقق من وجوده (سيظهر خطأ 404 تلقائياً إذا لم يكن موجوداً)
        $vision = AboutVision::findOrFail($id);

        // 2. التحقق من البيانات (نضيف id لجلسة التعديل لإعادة فتح المودل في حالة الخطأ)
        try {
            $request->validate([
                'title'       => 'required|string|max:255',
                'description' => 'required|string',
                'image'       => 'nullable|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
            ], [
                'title.required'       => 'يرجى إدخال العنوان.',
                'title.string'         => 'يجب أن يكون العنوان نصاً صالحاً.',
                'title.max'            => 'يجب ألا يتجاوز العنوان 255 حرفاً.',
                'description.required' => 'يرجى إدخال الوصف.',
                'image.image'          => 'يجب أن يكون الملف المرفق صورة.',
                'image.mimes'          => 'صيغ الصور المدعومة هي: jpeg, png, jpg, svg, webp.',
                'image.max'            => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // إذا فشل التحقق، نعيد التوجيه مع أخذ الـ edit_id في الاعتبار لفتح نافذة التعديل الصحيحة
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $vision->id);
        }

        try {
            // 3. تجهيز البيانات للتحديث
            $data = $request->only(['title', 'description']);

            // 4. معالجة تحديث الصورة (إذا تم رفع صورة جديدة)
            if ($request->hasFile('image') && $request->file('image')->isValid()) {

                // حذف الصورة القديمة بأمان
                try {
                    if (!empty($vision->image) && Storage::disk('public')->exists($vision->image)) {
                        Storage::disk('public')->delete($vision->image);
                    }
                } catch (Exception $fileError) {
                    Log::warning("Failed to delete old vision image (ID: $vision->id): " . $fileError->getMessage());
                }

                // حفظ الصورة الجديدة
                $data['image'] = $request->file('image')->store('visions', 'public');
            }

            // 5. التحديث النهائي
            $vision->update($data);

            return back()->with('success', 'تم تحديث البيانات بنجاح.');

        } catch (Exception $e) {
            Log::error("About Vision Update Error (ID: $id): " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء التحديث. يرجى التأكد من البيانات والمحاولة مرة أخرى.');
        }
    }

    /**
     * حذف عنصر مع الصورة التابعة له
     */
    public function destroy($id)
    {
        try {
            $vision = AboutVision::findOrFail($id);

            // حذف الصورة المرتبطة من السيرفر قبل حذف السجل من قاعدة البيانات
            try {
                if (!empty($vision->image) && Storage::disk('public')->exists($vision->image)) {
                    Storage::disk('public')->delete($vision->image);
                }
            } catch (Exception $fileError) {
                // نسجل التحذير ونكمل حذف السجل (لا نوقف العملية إذا لم يجد الصورة)
                Log::warning("Failed to delete vision image during record deletion (ID: $id): " . $fileError->getMessage());
            }

            // حذف السجل
            $vision->delete();

            return back()->with('success', 'تم الحذف بنجاح.');

        } catch (Exception $e) {
            Log::error("About Vision Delete Error (ID: $id): " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.');
        }
    }
}
