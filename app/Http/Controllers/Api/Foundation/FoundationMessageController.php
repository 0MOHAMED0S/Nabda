<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\FoundationMessage;
use App\Mail\FoundationMessageReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class FoundationMessageController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            $query = FoundationMessage::where('foundation_id', $foundationId);
            $totalMessages  = (clone $query)->count();
            $unreadMessages = (clone $query)->where('is_read', false)->count();
            $readMessages   = (clone $query)->where('is_read', true)->count();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            }
            if ($request->filled('subject') && $request->subject !== 'الكل') {
                $query->where('subject', $request->subject);
            }
            if ($request->filled('status')) {
                if ($request->status === 'read') $query->where('is_read', true);
                if ($request->status === 'unread') $query->where('is_read', false);
            }

            $messages = $query->orderBy('created_at', 'desc')->get();
            $messages->transform(function ($msg) {
                return [
                    'id'         => $msg->id,
                    'name'       => $msg->name,
                    'email'      => $msg->email,
                    'subject'    => $msg->subject,
                    'message'    => \Illuminate\Support\Str::limit($msg->message, 50, '...'),
                    'is_read'    => $msg->is_read,
                    'is_replied' => !is_null($msg->replied_at),
                    'date'       => \Carbon\Carbon::parse($msg->created_at)->translatedFormat('Y/m/d'),
                    'avatar_ltr' => mb_substr($msg->name, 0, 1, "UTF-8"),
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => [
                    'stats' => [
                        'total'  => $totalMessages,
                        'unread' => $unreadMessages,
                        'read'   => $readMessages,
                    ],
                    'messages' => $messages
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Foundation Messages Index Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'خطأ في السيرفر'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            // 🎯 التعديل هنا: استخدام id بدلاً من foundation_id
            $foundationId = $request->user()->id;

            $message = FoundationMessage::where('foundation_id', $foundationId)->findOrFail($id);

            // جعلها مقروءة بمجرد الفتح
            if (!$message->is_read) {
                $message->update(['is_read' => true]);
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'id'            => $message->id,
                    'name'          => $message->name,
                    'email'         => $message->email,
                    'subject'       => $message->subject,
                    'message'       => $message->message,
                    'date'          => \Carbon\Carbon::parse($message->created_at)->translatedFormat('Y/m/d'),
                    'is_read'       => $message->is_read,
                    'is_replied'    => !is_null($message->replied_at),
                    'reply_subject' => $message->reply_subject,
                    'reply_body'    => $message->reply_body,
                    'replied_at'    => $message->replied_at ? \Carbon\Carbon::parse($message->replied_at)->format('Y-m-d') : null,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'الرسالة غير موجودة.'], 404);
        }
    }

    public function reply(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reply_subject' => 'required|string|max:255',
            'reply_body'    => 'required|string',
        ]);

        try {
            $foundationId = $request->user()->id;

            $message = FoundationMessage::with('foundation')->where('foundation_id', $foundationId)->findOrFail($id);
            if (!empty($message->email)) {
                Mail::to($message->email)->send(new FoundationMessageReply(
                    $request->reply_subject,
                    $request->reply_body,
                    $message->foundation->name ?? 'مؤسسة خيرية'
                ));
            }

            $message->update([
                'reply_subject' => $request->reply_subject,
                'reply_body'    => $request->reply_body,
                'is_read'       => true,
                'replied_at'    => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم حفظ الرد وإرساله للمستخدم بنجاح.',
                'data'    => $message
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Foundation Reply Error ID {$id}: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء الإرسال.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function toggleRead(Request $request, $id): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;
            $message = FoundationMessage::where('foundation_id', $foundationId)->findOrFail($id);
            if (!$message->is_read) {
                $message->update(['is_read' => true]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديد الرسالة كمقروءة بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mark Message as Read Error ID {$id}: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أو أن الرسالة غير موجودة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;
            $message = FoundationMessage::where('foundation_id', $foundationId)->findOrFail($id);
            $message->delete();
            return response()->json([
                'status'  => true,
                'message' => 'تم حذف الرسالة وردودها بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Delete Message Error ID {$id}: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء محاولة الحذف أو أن الرسالة غير موجودة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
