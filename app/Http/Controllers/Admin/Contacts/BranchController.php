<?php

namespace App\Http\Controllers\Admin\Contacts;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class BranchController extends Controller
{
    /**
     * عرض قائمة الفروع
     */
    public function index()
    {
        try {
            $branches = Branch::orderBy('id', 'asc')->get();
            return view('admin.contacts.branches.index', compact('branches'));

        } catch (Exception $e) {
            Log::error("Branch Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات الفروع. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حفظ فرع جديد
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات مع رسائل مخصصة
        $request->validate([
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'lat'     => 'nullable|numeric|between:-90,90',
            'lng'     => 'nullable|numeric|between:-180,180',
        ], [
            'name.required'    => 'يرجى إدخال اسم الفرع.',
            'name.string'      => 'اسم الفرع يجب أن يكون نصاً صالحاً.',
            'name.max'         => 'اسم الفرع طويل جداً (الحد الأقصى 255 حرف).',

            'address.required' => 'يرجى إدخال عنوان الفرع بالتفصيل.',
            'address.string'   => 'عنوان الفرع يجب أن يكون نصاً صالحاً.',

            'phone.required'   => 'يرجى إدخال رقم هاتف الفرع.',
            'phone.max'        => 'رقم الهاتف طويل جداً (الحد الأقصى 20 رقم).',

            'lat.numeric'      => 'خط العرض (Latitude) يجب أن يكون رقماً.',
            'lat.between'      => 'خط العرض يجب أن يكون بين -90 و 90.',

            'lng.numeric'      => 'خط الطول (Longitude) يجب أن يكون رقماً.',
            'lng.between'      => 'خط الطول يجب أن يكون بين -180 و 180.',
        ]);

        try {
            // 2. تجميع البيانات بشكل آمن
            $data = $request->only(['name', 'address', 'phone', 'lat', 'lng']);

            // 3. إنشاء السجل
            Branch::create($data);

            return back()->with('success', 'تم إضافة الفرع بنجاح.');

        } catch (Exception $e) {
            Log::error("Branch Store Error: " . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ تقني أثناء حفظ بيانات الفرع. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات الفرع
     */
    public function update(Request $request, Branch $branch)
    {
        // 1. التحقق من البيانات داخل try/catch لالتقاط أخطاء الـ Validation
        try {
            $request->validate([
                'name'    => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'phone'   => 'required|string|max:20',
                'lat'     => 'nullable|numeric|between:-90,90',
                'lng'     => 'nullable|numeric|between:-180,180',
            ], [
                'name.required'    => 'حقل اسم الفرع مطلوب لتحديث البيانات.',
                'address.required' => 'حقل العنوان مطلوب لتحديث البيانات.',
                'phone.required'   => 'حقل الهاتف مطلوب لتحديث البيانات.',
                'lat.numeric'      => 'خط العرض يجب أن يكون رقماً.',
                'lng.numeric'      => 'خط الطول يجب أن يكون رقماً.',
            ]);
        } catch (ValidationException $e) {
            // إرسال edit_id لضمان بقاء الـ Edit Modal مفتوحاً في Alpine.js عند وجود خطأ
            return back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('edit_id', $branch->id);
        }

        try {
            // 2. تجميع وتحديث البيانات بشكل آمن
            $data = $request->only(['name', 'address', 'phone', 'lat', 'lng']);

            $branch->update($data);

            return back()->with('success', 'تم تحديث بيانات الفرع بنجاح.');

        } catch (Exception $e) {
            Log::error("Branch Update Error ID {$branch->id}: " . $e->getMessage());
            return back()->withInput()->with('error', 'فشل تحديث بيانات الفرع بسبب خطأ تقني.');
        }
    }

    /**
     * حذف الفرع نهائياً
     */
    public function destroy(Branch $branch)
    {
        try {
            $branch->delete();

            return back()->with('success', 'تم حذف الفرع نهائياً من النظام.');

        } catch (Exception $e) {
            Log::error("Branch Delete Error ID {$branch->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء محاولة حذف الفرع.');
        }
    }
}
