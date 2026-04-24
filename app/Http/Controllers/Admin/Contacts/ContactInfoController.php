<?php

namespace App\Http\Controllers\Admin\Contacts;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactInfoController extends Controller
{
    /**
     * عرض صفحة تعديل معلومات التواصل
     */
    public function edit()
    {
        try {
            // جلب السجل الأول أو إنشاء كائن جديد فارغ إذا لم يكن هناك بيانات مسبقة
            $contact = ContactInfo::first() ?? new ContactInfo();

            return view('admin.contacts.contact_info.index', compact('contact'));

        } catch (Exception $e) {
            Log::error("Contact Info Edit Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات التواصل. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث وحفظ معلومات التواصل
     */
    public function update(Request $request)
    {
        // 1. التحقق من صحة البيانات مع رسائل خطأ دقيقة وشاملة
        $request->validate([
            'phone'         => 'required|string|max:20',
            'email'         => 'required|email|max:255',
            'working_hours' => 'required|string|max:255',
        ], [
            'phone.required'         => 'يرجى إدخال رقم الهاتف.',
            'phone.string'           => 'رقم الهاتف يجب أن يكون صالحاً.',
            'phone.max'              => 'رقم الهاتف يجب ألا يتجاوز 20 حرفاً/رقماً.',

            'email.required'         => 'يرجى إدخال البريد الإلكتروني.',
            'email.email'            => 'يرجى إدخال عنوان بريد إلكتروني صحيح.',
            'email.max'              => 'البريد الإلكتروني طويل جداً.',

            'working_hours.required' => 'يرجى إدخال ساعات العمل.',
            'working_hours.string'   => 'ساعات العمل يجب أن تكون نصاً صالحاً.',
            'working_hours.max'      => 'نص ساعات العمل يجب ألا يتجاوز 255 حرفاً.',
        ]);

        try {
            // 2. التحديث أو الإنشاء باستخدام الحقول الآمنة فقط (Security Mass Assignment)
            ContactInfo::updateOrCreate(
                ['id' => 1], // نعتمد على السجل الأول دائماً لأنها معلومات تواصل وحيدة للموقع
                $request->only(['phone', 'email', 'working_hours'])
            );

            return back()->with('success', 'تم تحديث معلومات التواصل بنجاح.');

        } catch (Exception $e) {
            Log::error("Contact Info Update Error: " . $e->getMessage());

            // إرجاع المستخدم مع البيانات التي أدخلها (withInput) لتجنب إعادة كتابتها
            return back()->withInput()->with('error', 'حدث خطأ تقني أثناء التحديث. يرجى المحاولة لاحقاً.');
        }
    }
}
