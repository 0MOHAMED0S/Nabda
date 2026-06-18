<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Mail\ContactMessageReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactMessageController extends Controller
{
    /**
     * عرض جميع الرسائل في لوحة التحكم مع الإحصائيات والفلترة
     */
    public function index(Request $request): View
    {
        try {
            // 1. حساب الإحصائيات العلوية بذكاء (مستقلة عن فلتر البحث)
            $stats = [
                'total'  => ContactMessage::count(),
                'unread' => ContactMessage::where('is_read', false)->count(),
                'read'   => ContactMessage::where('is_read', true)->count(),
            ];

            // 2. بناء الاستعلام مع الفلاتر باستخدام طريقة when الاحترافية
            $messages = ContactMessage::query()
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    $isRead = $request->status === 'read';
                    $q->where('is_read', $isRead);
                })
                ->latest() // بديل احترافي لـ orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();

            // 3. إرجاع الواجهة
            return view('admin.contact_messages.index', compact('messages', 'stats'));

        } catch (Exception $e) {
            Log::error("Admin Contact Messages Index Error: " . $e->getMessage());
            abort(500, 'حدث خطأ تقني أثناء جلب الرسائل.');
        }
    }

    /**
     * جلب تفاصيل الرسالة وتحديث حالتها إلى مقروءة (تُستخدم للنافذة المنبثقة Modal)
     */
    public function show(ContactMessage $message): JsonResponse
    {
        try {
            // تحديث حالة القراءة إذا لم تكن مقروءة مسبقاً فقط
            if (!$message->is_read) {
                $message->update(['is_read' => true]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الرسالة بنجاح.',
                'data'    => $message
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("Admin Contact Message Show Error ID {$message->id}: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء فتح الرسالة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * إرسال رد للمستخدم عبر البريد الإلكتروني وحفظه في النظام
     */
    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        // 1. التحقق من المدخلات مع رسائل خطأ واضحة باللغة العربية
        $request->validate([
            'reply_subject' => 'required|string|max:255',
            'reply_body'    => 'required|string',
        ], [
            'reply_subject.required' => 'يرجى كتابة موضوع الرد.',
            'reply_body.required'    => 'يرجى كتابة نص الرسالة قبل الإرسال.',
        ]);

        try {
            // 2. إرسال البريد الإلكتروني الفعلي للمستخدم
            Mail::to($message->email)->send(
                new ContactMessageReply($request->reply_subject, $request->reply_body)
            );

            // 3. تحديث بيانات الرسالة في قاعدة البيانات لتوثيق الرد
            $message->update([
                'reply_subject' => $request->reply_subject,
                'reply_body'    => $request->reply_body,
                'is_read'       => true,
                'replied_at'    => now(),
            ]);

            return back()->with('success', 'تم الرد على الرسالة وإرسال البريد الإلكتروني بنجاح.');

        } catch (Exception $e) {
            Log::error("Admin Contact Reply Error ID {$message->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء محاولة إرسال الرد عبر البريد الإلكتروني.');
        }
    }

    /**
     * حذف الرسالة نهائياً من النظام
     */
    public function destroy(ContactMessage $message): RedirectResponse
    {
        try {
            $message->delete();
            return back()->with('success', 'تم حذف الرسالة بنجاح.');
        } catch (Exception $e) {
            Log::error("Admin Contact Message Delete Error ID {$message->id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء محاولة حذف الرسالة.');
        }
    }
}
