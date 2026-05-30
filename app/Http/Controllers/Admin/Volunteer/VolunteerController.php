<?php

namespace App\Http\Controllers\Admin\Volunteer;

use App\Http\Controllers\Controller;
use App\Mail\VolunteerStatusUpdated;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Mail;

class VolunteerController extends Controller
{
    /**
     * عرض قائمة جميع المتطوعين مع الإحصائيات
     */
    public function index()
    {
        try {
            $volunteers = Volunteer::orderBy('created_at', 'desc')->paginate(15);

            $stats = [
                'total'    => Volunteer::count(),
                'approved' => Volunteer::where('status', 'approved')->count(),
                'pending'  => Volunteer::where('status', 'pending')->count(),
                'rejected' => Volunteer::where('status', 'rejected')->count(),
            ];

            return view('admin.volunteers.index', compact('volunteers', 'stats'));
        } catch (Exception $e) {
            Log::error("Volunteer Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات المتطوعين.');
        }
    }

    /**
     * تحديث بيانات المتطوع وحالة الاعتماد
     */
public function update(Request $request, Volunteer $volunteer)
    {
        try {
            $request->validate([
                'name'           => 'required|string|max:255',
                'email'          => 'required|email|max:255|unique:volunteers,email,' . $volunteer->id,
                'phone'          => 'required|string|max:20|unique:volunteers,phone,' . $volunteer->id,
                'national_id'    => 'required|string|size:14|unique:volunteers,national_id,' . $volunteer->id,
                'volunteer_type' => 'required|in:general,affiliated',
                'status'         => 'required|in:pending,approved,rejected', // حالة مراجعة الإدارة
                'address'        => 'required|string|max:500',
            ], [
                'name.required'        => 'اسم المتطوع مطلوب.',
                'email.unique'         => 'هذا البريد الإلكتروني مستخدم مسبقاً.',
                'phone.unique'         => 'هذا الهاتف مستخدم مسبقاً.',
                'national_id.unique'   => 'الرقم القومي مستخدم مسبقاً.',
                'national_id.size'     => 'الرقم القومي يجب أن يتكون من 14 رقماً.',
                'status.in'            => 'حالة الحساب غير صالحة.',
                'volunteer_type.in'    => 'نوع التطوع غير صالح.',
            ]);
        } catch (ValidationException $e) {
            // إرجاع edit_id لكي يفتح الـ Modal الخاص بالتعديل تلقائياً في الفرونت إند
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $volunteer->id);
        }

        try {
            $volunteer->update($request->only([
                'name',
                'email',
                'phone',
                'national_id',
                'volunteer_type',
                'status',
                'address'
            ]));

            // 🎯 إضافة إرسال الإيميل في الخلفية
            // نتحقق أولاً مما إذا كانت حالة التطوع (status) قد تغيرت في هذا التعديل
            if ($volunteer->wasChanged('status') && !empty($volunteer->email)) {
                Mail::to($volunteer->email)->send(new VolunteerStatusUpdated($volunteer));
            }

            return back()->with('success', 'تم تحديث بيانات وحالة المتطوع بنجاح.');
        } catch (Exception $e) {
            Log::error("Volunteer Update Error ID {$volunteer->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث بيانات المتطوع.');
        }
    }

    /**
     * رفض طلب المتطوع (تغيير الحالة إلى مرفوض)
     */
    public function reject(Volunteer $volunteer)
    {
        try {
            $volunteer->update([
                'status' => 'rejected'
            ]);

            // 🎯 إرسال إيميل الرفض للمتطوع في الخلفية لتجنب بطء السيرفر
            if (!empty($volunteer->email)) {
                Mail::to($volunteer->email)->send(new VolunteerStatusUpdated($volunteer));
            }

            return back()->with('success', 'تم رفض طلب التطوع بنجاح.');
        } catch (Exception $e) {
            Log::error("Volunteer Reject Error ID {$volunteer->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء محاولة رفض المتطوع.');
        }
    }

    /**
     * حذف المتطوع نهائياً مع صور هويته
     */
    public function destroy(Volunteer $volunteer)
    {
        try {
            // تجميع مسارات الملفات الحساسة الخاصة بالمتطوع
            $files = [
                $volunteer->avatar,
                $volunteer->national_id_front,
                $volunteer->national_id_back
            ];

            // حذف الملفات من السيرفر لتوفير المساحة وللحماية الأمنية
            foreach ($files as $file) {
                if (!empty($file) && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            // حذف السجل من قاعدة البيانات
            $volunteer->delete();

            return back()->with('success', 'تم حذف المتطوع وجميع مرفقاته بشكل نهائي.');
        } catch (Exception $e) {
            Log::error("Volunteer Delete Error ID {$volunteer->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء محاولة حذف المتطوع.');
        }
    }

    /**
     * عرض قائمة المتطوعين المعتمدين فقط (المقبولين)
     */
    public function approvedIndex()
    {
        try {
            // جلب المتطوعين المقبولين فقط
            $volunteers = Volunteer::where('status', 'approved')
                ->orderBy('updated_at', 'desc')
                ->paginate(15);

            // إحصائيات خاصة بالمعتمدين
            $stats = [
                'total_approved' => Volunteer::where('status', 'approved')->count(),
                'general'        => Volunteer::where('status', 'approved')->where('volunteer_type', 'general')->count(),
                'affiliated'     => Volunteer::where('status', 'approved')->where('volunteer_type', 'affiliated')->count(),
            ];

            return view('admin.volunteers.approve', compact('volunteers', 'stats'));
        } catch (Exception $e) {
            Log::error("Volunteer Approved Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات المتطوعين المعتمدين.');
        }
    }
}
