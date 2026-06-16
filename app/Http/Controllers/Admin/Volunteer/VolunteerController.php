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

    /**
     * إضافة متطوع جديد من قبل الإدارة (معتمد وتلقائي التفعيل)
     */
    public function store(Request $request)
    {
        try {
            // 1. قواعد التحقق (نفس القواعد الصارمة المطلوبة)
            $request->validate([
                // البيانات الشخصية
                'name'              => 'required|string|min:2|max:255',
                'email'             => 'required|string|email|max:255|unique:volunteers,email',
                'phone'             => 'required|string|max:20|unique:volunteers,phone',
                'address'           => 'required|string|max:500',

                // بيانات التطوع
                'volunteer_type'    => 'required|in:general,affiliated',
                'foundation_id'     => 'prohibited_if:volunteer_type,general|required_if:volunteer_type,affiliated|nullable|integer|exists:foundations,id',
                'volunteer_fields'  => 'nullable|array',
                'volunteer_fields.*'=> 'string|max:100',
                'governorates'      => 'nullable|array',
                'governorates.*'    => 'string|max:100',

                // البيانات الحساسة والمرفقات
                'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'national_id'       => 'required|string|size:14|unique:volunteers,national_id',
                'national_id_front' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'national_id_back'  => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
                'password'          => 'required|string|min:8|confirmed',
            ], [
                'foundation_id.prohibited_if' => 'لا يمكنك اختيار مؤسسة محددة عندما يكون نوع التطوع "عام".',
                'foundation_id.required_if'   => 'يرجى اختيار المؤسسة بما أنك اخترت التطوع التابع.',
                'national_id.size'            => 'الرقم القومي يجب أن يتكون من 14 رقماً.',
                'national_id.unique'          => 'هذا الرقم القومي مسجل لدينا مسبقاً.',
                'email.unique'                => 'هذا البريد الإلكتروني مسجل مسبقاً.',
                'phone.unique'                => 'رقم الهاتف مسجل مسبقاً.',
                'national_id_front.required'  => 'صورة الوجه الأمامي للبطاقة مطلوبة.',
                'national_id_back.required'   => 'صورة الوجه الخلفي للبطاقة مطلوبة.',
                'password.confirmed'          => 'تأكيد كلمة المرور غير متطابق.',
                '*.required'                  => 'هذا الحقل أو الملف مطلوب.',
                '*.image'                     => 'الملف يجب أن يكون صورة صحيحة.',
            ]);
        } catch (ValidationException $e) {
            // نمرر form_type لكي يفتح الـ Modal الخاص بالإضافة تلقائياً في الواجهة في حال وجود أخطاء
            return back()->withErrors($e->validator)->withInput()->with('form_type', 'create');
        }

        // متغيرات لتتبع مسارات الملفات من أجل التنظيف الذكي
        $avatarPath  = null;
        $idFrontPath = null;
        $idBackPath  = null;

        try {
            // 2. رفع الملفات بشكل آمن
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('volunteers/avatars', 'public');
            }
            $idFrontPath = $request->file('national_id_front')->store('volunteers/national_ids', 'public');
            $idBackPath  = $request->file('national_id_back')->store('volunteers/national_ids', 'public');

            // 3. إنشاء سجل المتطوع (معتمد فوراً لأن الإدمن هو من يضيفه)
            $volunteer = Volunteer::create([
                'name'              => trim($request->name),
                'email'             => strtolower(trim($request->email)),
                'phone'             => trim($request->phone),
                'address'           => trim($request->address),

                'volunteer_type'    => $request->volunteer_type,
                'foundation_id'     => $request->volunteer_type === 'affiliated' ? $request->foundation_id : null,

                'volunteer_fields'  => $request->volunteer_fields,
                'governorates'      => $request->governorates,
                'avatar'            => $avatarPath,

                'national_id'       => $request->national_id,
                'national_id_front' => $idFrontPath,
                'national_id_back'  => $idBackPath,
                'password'          => \Illuminate\Support\Facades\Hash::make($request->password),

                'status'            => 'approved', // 🎯 معتمد ومقبول فوراً
            ]);

            // 4. إرسال إشعار للمؤسسة (إذا كان تابعاً لها)
            if ($volunteer->foundation_id) {
                $foundationToNotify = \App\Models\Foundation::find($volunteer->foundation_id);
                if ($foundationToNotify) {
                    $foundationToNotify->notify(new \App\Notifications\GeneralNotification(
                        'متطوع جديد 🤝',
                        "تم إضافة المتطوع {$volunteer->name} وربطه بمؤسستكم من قبل الإدارة.",
                        'success'
                    ));
                }
            }

            return back()->with('success', 'تم إضافة المتطوع واعتماده بنجاح.');

        } catch (Exception $e) {
            // 🛡️ التنظيف الذكي: إذا فشل الحفظ في قاعدة البيانات لأي سبب تقني، نمسح الصور التي تم رفعها
            foreach ([$avatarPath, $idFrontPath, $idBackPath] as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error("Admin Volunteer Create Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني غير متوقع أثناء إضافة المتطوع.');
        }
    }
}
