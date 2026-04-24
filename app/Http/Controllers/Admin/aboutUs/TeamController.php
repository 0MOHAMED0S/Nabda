<?php

namespace App\Http\Controllers\Admin\AboutUs; // تم تصحيح الـ Namespace (حرف A كبير)

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class TeamController extends Controller
{
    /**
     * عرض قائمة فريق العمل
     */
    public function index()
    {
        try {
            // جلب الأعضاء مرتبين حسب حقل الترتيب (order) لسهولة التحكم في الواجهة
            $members = TeamMember::orderBy('order', 'asc')->get();
            return view('admin.aboutUs.team.index', compact('members'));

        } catch (Exception $e) {
            Log::error("Team Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب بيانات فريق العمل. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * إضافة عضو جديد للفريق
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل عربية مخصصة
        $request->validate([
            'name'      => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'image'     => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'name.required'      => 'يرجى إدخال اسم العضو.',
            'name.string'        => 'يجب أن يكون الاسم نصاً صالحاً.',
            'job_title.required' => 'يرجى إدخال المسمى الوظيفي (مثل: مطور ويب، مدير تنفيذي).',
            'image.required'     => 'الصورة الشخصية مطلوبة لإضافة العضو.',
            'image.image'        => 'الملف المرفق يجب أن يكون صورة صحيحة.',
            'image.mimes'        => 'صيغ الصور المدعومة: jpeg, png, jpg, webp.',
            'image.max'          => 'يجب ألا يتجاوز حجم الصورة 2 ميجابايت.',
        ]);

        try {
            // 2. رفع الصورة وحفظ المسار
            $path = $request->file('image')->store('team', 'public');

            // 3. إنشاء السجل
            TeamMember::create([
                'name'      => $request->name,
                'job_title' => $request->job_title,
                'image'     => $path
            ]);

            return back()->with('success', 'تم إضافة عضو جديد لفريق العمل بنجاح.');

        } catch (Exception $e) {
            Log::error("Team Store Error: " . $e->getMessage());
            return back()->with('error', 'فشل في إضافة العضو. حدث خطأ تقني يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات عضو حالي
     */
    public function update(Request $request, $id)
    {
        // 1. جلب العضو للتأكد من وجوده
        $teamMember = TeamMember::findOrFail($id);

        // 2. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation
        try {
            $request->validate([
                'name'      => 'required|string|max:255',
                'job_title' => 'required|string|max:255',
                'image'     => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ], [
                'name.required'      => 'يرجى إدخال اسم العضو.',
                'job_title.required' => 'يرجى إدخال المسمى الوظيفي.',
                'image.image'        => 'الملف المرفق يجب أن يكون صورة صحيحة.',
                'image.mimes'        => 'صيغ الصور المدعومة: jpeg, png, jpg, webp.',
                'image.max'          => 'يجب ألا يتجاوز حجم الصورة 2 ميجابايت.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لإعادة فتح نافذة التعديل تلقائياً في الواجهة
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $teamMember->id);
        }

        try {
            // 3. تجهيز البيانات للتحديث (باستخدام only للحماية)
            $data = $request->only(['name', 'job_title']);

            // 4. معالجة تحديث الصورة
            if ($request->hasFile('image') && $request->file('image')->isValid()) {

                // حذف الصورة القديمة بأمان (داخل try/catch لضمان عدم توقف الكود إذا كانت مفقودة)
                try {
                    if (!empty($teamMember->image) && Storage::disk('public')->exists($teamMember->image)) {
                        Storage::disk('public')->delete($teamMember->image);
                    }
                } catch (Exception $fileError) {
                    Log::warning("Failed to delete old team member image (ID: $teamMember->id): " . $fileError->getMessage());
                }

                // رفع الصورة الجديدة
                $data['image'] = $request->file('image')->store('team', 'public');
            }

            // 5. التحديث النهائي
            $teamMember->update($data);

            return back()->with('success', 'تم تحديث بيانات العضو بنجاح.');

        } catch (Exception $e) {
            Log::error("Team Update Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء تحديث البيانات. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حذف عضو من الفريق
     */
    public function destroy($id)
    {
        try {
            $teamMember = TeamMember::findOrFail($id);

            // حذف الصورة المرتبطة بأمان قبل حذف السجل
            try {
                if (!empty($teamMember->image) && Storage::disk('public')->exists($teamMember->image)) {
                    Storage::disk('public')->delete($teamMember->image);
                }
            } catch (Exception $fileError) {
                Log::warning("Failed to delete team member image during deletion (ID: $id): " . $fileError->getMessage());
            }

            // حذف السجل من قاعدة البيانات
            $teamMember->delete();

            return back()->with('success', 'تم حذف العضو من النظام بنجاح.');

        } catch (Exception $e) {
            Log::error("Team Delete Error (ID: $id): " . $e->getMessage());
            return back()->with('error', 'فشل الحذف، حدث خطأ تقني. يرجى المحاولة لاحقاً.');
        }
    }
}
