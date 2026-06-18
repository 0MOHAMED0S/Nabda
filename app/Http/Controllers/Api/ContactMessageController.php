<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactMessageController extends Controller
{
    /**
     * استقبال وحفظ رسالة "اتصل بنا" من الفرونت إند
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 1. قواعد التحقق (Validation Rules) والرسائل المخصصة باللغة العربية
            $validator = Validator::make($request->all(), [
                'name'    => 'required|string|min:3|max:100',
                'email'   => 'required|email|max:150',
                'subject' => 'required|string|min:3|max:150',
                'message' => 'required|string|min:10|max:250',
            ], [
                // 🎯 رسائل حقل الاسم
                'name.required' => 'حقل الاسم بالكامل مطلوب.',
                'name.string'   => 'الاسم يجب أن يكون نصاً.',
                'name.min'      => 'الاسم يجب ألا يقل عن 3 أحرف.',
                'name.max'      => 'الاسم يجب ألا يتجاوز 100 حرف.',

                // 🎯 رسائل حقل البريد الإلكتروني
                'email.required'=> 'حقل البريد الإلكتروني مطلوب.',
                'email.email'   => 'يرجى إدخال عنوان بريد إلكتروني صحيح (مثال: example@email.com).',
                'email.max'     => 'عنوان البريد الإلكتروني طويل جداً.',

                // 🎯 رسائل حقل الموضوع
                'subject.required' => 'يرجى اختيار أو كتابة موضوع الرسالة.',
                'subject.min'      => 'موضوع الرسالة قصير جداً.',
                'subject.max'      => 'موضوع الرسالة يجب ألا يتجاوز 150 حرفاً.',

                // 🎯 رسائل حقل الرسالة (استناداً إلى الحد الأقصى المطلوب 250 حرف)
                'message.required' => 'حقل الرسالة مطلوب، كيف يمكننا مساعدتك؟',
                'message.string'   => 'محتوى الرسالة يجب أن يكون نصاً صالحاً.',
                'message.min'      => 'الرسالة قصيرة جداً، يرجى كتابة تفاصيل أكثر (10 أحرف على الأقل).',
                'message.max'      => 'عذراً، محتوى الرسالة يجب ألا يتجاوز 250 حرفاً كحد أقصى.',
            ]);

            // 2. التحقق من وجود أخطاء وإرجاعها بشكل احترافي للفرونت إند
            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'يرجى مراجعة البيانات المدخلة وتصحيح الأخطاء.',
                    'errors'  => $validator->errors()
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            // 3. التحقق مما إذا كان المستخدم مسجلاً الدخول (لالتقاط الـ ID الخاص به)
            $userId = auth('sanctum')->check() ? auth('sanctum')->id() : null;

            // 4. حفظ الرسالة في قاعدة البيانات
            ContactMessage::create([
                'user_id' => $userId,
                'name'    => $request->name,
                'email'   => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
            ]);

            // 5. إرجاع استجابة النجاح
            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال رسالتك بنجاح. فريقنا سيقوم بالرد عليك في أقرب وقت ممكن.'
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            // تسجيل الخطأ في ملفات الـ Log لسهولة التتبع من قبل الإدارة
            Log::error("API Contact Us Send Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'عذراً، حدث خطأ تقني غير متوقع أثناء إرسال رسالتك. يرجى المحاولة مرة أخرى لاحقاً.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
