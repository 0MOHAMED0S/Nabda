<?php

namespace App\Http\Controllers\Admin\Foundation;

use App\Http\Controllers\Controller;
use App\Mail\FoundationStatusUpdated;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Mail;

class FoundationController extends Controller
{
    /**
     * عرض قائمة المؤسسات مع الإحصائيات
     */
    public function index()
    {
        try {
            $foundations = Foundation::orderBy('created_at', 'desc')->paginate(15);

            $stats = [
                'total'    => Foundation::count(),
                'approved' => Foundation::where('approval_status', 'approved')->count(),
                'pending'  => Foundation::where('approval_status', 'pending')->count(),
                'rejected' => Foundation::where('approval_status', 'rejected')->count(),
            ];

            // تم تصحيح اسم العرض هنا ليكون admin.foundations.index
            return view('admin.foundations.index', compact('foundations', 'stats'));
        } catch (Exception $e) {
            Log::error("Foundation Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب بيانات المؤسسات.');
        }
    }

    /**
     * تحديث بيانات المؤسسة وحالة الاعتماد
     */
    public function update(Request $request, Foundation $foundation)
    {
        try {
            $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|max:255|unique:foundations,email,' . $foundation->id,
                'phone'                 => 'required|string|max:20|unique:foundations,phone,' . $foundation->id,
                'type'                  => 'required|string|max:100',
                'approval_status'       => 'required|in:pending,approved,rejected',
                'status'                => 'required|in:active,inactive',
                'license_number'        => 'required|string|max:255',
                'supervising_authority' => 'required|string|max:255',
            ], [
                'name.required'       => 'اسم المؤسسة مطلوب.',
                'email.unique'        => 'هذا البريد الإلكتروني مستخدم مسبقاً.',
                'phone.unique'        => 'هذا الهاتف مستخدم مسبقاً.',
                'approval_status.in'  => 'حالة الاعتماد غير صالحة.',
                'status.in'           => 'حالة الحساب غير صالحة.',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with('edit_id', $foundation->id);
        }

        try {
            $foundation->update($request->only([
                'name',
                'email',
                'phone',
                'type',
                'approval_status',
                'status',
                'license_number',
                'supervising_authority'
            ]));

            // 🎯 إضافة إرسال الإيميل هنا
            // نتحقق أولاً مما إذا كانت "حالة الاعتماد" قد تغيرت فعلياً في هذا التحديث لتجنب الإرسال المزعج
            if ($foundation->wasChanged('approval_status') && !empty($foundation->email)) {
                Mail::to($foundation->email)->send(new FoundationStatusUpdated($foundation));
            }

            // 🎯 تم استبدال send() بـ queue() لإرسال الإيميل في الخلفية وعدم تعطيل النظام
            // if ($foundation->wasChanged('approval_status') && !empty($foundation->email)) {
            //     Mail::to($foundation->email)->queue(new FoundationStatusUpdated($foundation));
            // }

            return back()->with('success', 'تم تحديث بيانات واعتماد المؤسسة بنجاح.');
        } catch (Exception $e) {
            Log::error("Foundation Update Error ID {$foundation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث البيانات.');
        }
    }

    /**
     * رفض طلب المؤسسة (إيقاف وعدم اعتماد)
     */
    public function reject(Foundation $foundation)
    {
        try {
            $foundation->update([
                'approval_status' => 'rejected',
                'status'          => 'inactive'
            ]);

            return back()->with('success', 'تم رفض طلب المؤسسة وإيقاف حسابها بنجاح.');
        } catch (Exception $e) {
            Log::error("Foundation Reject Error ID {$foundation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء محاولة رفض المؤسسة.');
        }
    }

    /**
     * حذف المؤسسة نهائياً مع ملفاتها
     */
    public function destroy(Foundation $foundation)
    {
        try {
            $files = [
                $foundation->license_image,
                $foundation->commercial_register,
                $foundation->tax_card,
                $foundation->accreditation_letter,
                $foundation->headquarters_image,
                $foundation->logo
            ];

            foreach ($files as $file) {
                if (!empty($file) && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            $foundation->delete();

            return back()->with('success', 'تم حذف المؤسسة وجميع مستنداتها بنجاح.');
        } catch (Exception $e) {
            Log::error("Foundation Delete Error ID {$foundation->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المؤسسة.');
        }
    }

/**
     * عرض قائمة المؤسسات المعتمدة فقط (المقبولة) مع إحصائيات نشاطها
     */
    public function approvedIndex()
    {
        try {
            // جلب المؤسسات المعتمدة مع إحصائيات تفصيلية (نشاط المؤسسة)
            $foundations = \App\Models\Foundation::where('approval_status', 'approved')
                ->withCount([
                    // 1. عدد الحالات النشطة التابعة للمؤسسة
                    'cases as active_cases_count' => function ($query) {
                        $query->where('status', 'active');
                    },
                    // 2. إجمالي عدد الحالات (كل الحالات)
                    'cases as total_cases_count',

                    // 3. عدد الفرص التطوعية المتاحة حالياً
                    'opportunities as active_opportunities_count' => function ($query) {
                        $query->where('status', 'open');
                    },

                    // 4. عدد عمليات التبرع المكتملة لصالح هذه المؤسسة
                    'donations as completed_donations_count' => function ($query) {
                        $query->where('status', 'completed');
                    }
                ])
                ->withSum([
                    // 5. إجمالي المبالغ المالية التي تم جمعها لهذه المؤسسة
                    'donations as total_collected_amount' => function ($query) {
                        $query->where('status', 'completed')
                              ->where('donation_type', 'financial');
                    }
                ], 'amount')
                ->orderBy('updated_at', 'desc')
                ->paginate(15);

            // الإحصائيات العلوية الخاصة بالصفحة
            $stats = [
                'total_approved' => \App\Models\Foundation::where('approval_status', 'approved')->count(),
                'active'         => \App\Models\Foundation::where('approval_status', 'approved')->where('status', 'active')->count(),
                'inactive'       => \App\Models\Foundation::where('approval_status', 'approved')->where('status', 'inactive')->count(),
            ];

            return view('admin.foundations.approve', compact('foundations', 'stats'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Foundation Approved Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء جلب بيانات المؤسسات المعتمدة.');
        }
    }

    /**
     * إضافة مؤسسة جديدة من قبل الإدارة (معتمدة ونشطة تلقائياً)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                // البيانات الأساسية
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|string|email|max:255|unique:foundations',
                'phone'                 => 'required|string|max:20|unique:foundations',
                'type'                  => 'required|string|max:100',

                // بيانات الترخيص
                'license_number'        => 'required|string|max:255',
                'supervising_authority' => 'required|string|max:255',

                // الملفات والمستندات
                'license_image'         => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
                'commercial_register'   => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
                'tax_card'              => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
                'accreditation_letter'  => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',

                // الصور وكلمة المرور
                'headquarters_image'    => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'logo'                  => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'password'              => 'required|string|min:8|confirmed',
            ], [
                'name.required'         => 'اسم المؤسسة مطلوب.',
                'email.unique'          => 'هذا البريد الإلكتروني مسجل مسبقاً.',
                'phone.unique'          => 'رقم الهاتف مسجل مسبقاً.',
                'password.confirmed'    => 'تأكيد كلمة المرور غير متطابق.',
                '*.required'            => 'هذا الحقل أو الملف مطلوب.',
                '*.file'                => 'الملف المرفق غير صالح.',
                '*.mimes'               => 'الصيغ المدعومة للملفات هي: jpeg, png, jpg, pdf.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // نمرر form_type لكي يفتح الـ Modal الخاص بالإضافة تلقائياً في حال وجود خطأ
            return back()->withErrors($e->validator)->withInput()->with('form_type', 'create');
        }

        try {
            // تخزين الملفات والصور
            $license_image        = $request->file('license_image')->store('foundations/documents', 'public');
            $commercial_register  = $request->file('commercial_register')->store('foundations/documents', 'public');
            $tax_card             = $request->file('tax_card')->store('foundations/documents', 'public');
            $accreditation_letter = $request->file('accreditation_letter')->store('foundations/documents', 'public');
            $headquarters_image   = $request->file('headquarters_image')->store('foundations/images', 'public');
            $logo                 = $request->file('logo')->store('foundations/logos', 'public');

            // إنشاء المؤسسة (معتمدة ونشطة فوراً 🎯)
            Foundation::create([
                'name'                  => $request->name,
                'email'                 => $request->email,
                'phone'                 => $request->phone,
                'type'                  => $request->type,
                'license_number'        => $request->license_number,
                'supervising_authority' => $request->supervising_authority,
                'password'              => \Illuminate\Support\Facades\Hash::make($request->password),
                'license_image'         => $license_image,
                'commercial_register'   => $commercial_register,
                'tax_card'              => $tax_card,
                'accreditation_letter'  => $accreditation_letter,
                'headquarters_image'    => $headquarters_image,
                'logo'                  => $logo,
                'approval_status'       => 'approved', // 🎯 معتمدة تلقائياً
                'status'                => 'active',   // 🎯 حساب نشط
            ]);

            return back()->with('success', 'تم إضافة المؤسسة واعتمادها بنجاح.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Admin Foundation Create Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء إضافة المؤسسة.');
        }
    }
}
