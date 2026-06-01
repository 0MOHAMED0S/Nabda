<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\FoundationMessage;
use App\Models\Foundation; // 🎯 استدعاء موديل المؤسسة
use App\Mail\NewFoundationMessageAlert; // 🎯 استدعاء كلاس الإيميل الجديد
use Illuminate\Support\Facades\Mail; // 🎯 استدعاء واجهة البريد
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactFoundationController extends Controller
{
    /**
     * إرسال رسالة تواصل للمؤسسة
     */
    public function store(Request $request, $foundationId): JsonResponse
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        try {
            // 1. جلب بيانات المؤسسة للتحقق من وجودها وللحصول على بريدها الإلكتروني
            $foundation = \App\Models\Foundation::findOrFail($foundationId);

            // 2. إنشاء الرسالة وحفظها في قاعدة البيانات
            $newMessage = \App\Models\FoundationMessage::create([
                'foundation_id' => $foundationId,
                'user_id'       => auth('sanctum')->check() ? auth('sanctum')->id() : null,
                'name'          => $request->name,
                'email'         => $request->email,
                'subject'       => $request->subject,
                'message'       => $request->message,
            ]);

            // 3. 🎯 إرسال الإشعار للمؤسسة عبر البريد في الخلفية (Queue)
            if (!empty($foundation->email)) {
                \Illuminate\Support\Facades\Mail::to($foundation->email)->send(new \App\Mail\NewFoundationMessageAlert($newMessage));
            }

            // 🔔 4. إرسال إشعار داخل النظام (Database Notification) للمؤسسة
            if ($foundation) {
                $foundation->notify(new \App\Notifications\GeneralNotification(
                    'رسالة تواصل جديدة ✉️',
                    "تلقيت رسالة تواصل جديدة من {$request->name} بعنوان: '{$request->subject}'.",
                    'info'
                ));
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم إرسال رسالتك للمؤسسة بنجاح.'
            ], 201, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Send Foundation Message Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء إرسال الرسالة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
