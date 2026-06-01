<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * جلب جميع الإشعارات (بدون Pagination)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // جلب المستخدم الحالي (أياً كان نوعه: إدمن، مؤسسة، مستخدم، متطوع)
            $user = $request->user();

            // تعيين لغة التواريخ للعربية (لإظهار: منذ ساعتين، منذ يومين)
            Carbon::setLocale('ar');

            // جلب الإشعارات مرتبة من الأحدث للأقدم
            $notifications = $user->notifications;

            // حساب عدد الإشعارات غير المقروءة
            $unreadCount = $user->unreadNotifications->count();

            // تنسيق البيانات لتتطابق مع تصميم الفرونت إند
            $formattedNotifications = $notifications->map(function ($notification) {
                return [
                    'id'         => $notification->id,
                    'title'      => $notification->data['title'] ?? 'إشعار جديد',
                    'body'       => $notification->data['body'] ?? '',
                    'type'       => $notification->data['type'] ?? 'info', // success, info, warning
                    'is_read'    => !is_null($notification->read_at),
                    'time_ago'   => $notification->created_at->diffForHumans(), // مثال: "منذ ساعتين"
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الإشعارات بنجاح.',
                'data'    => [
                    'unread_count'  => $unreadCount,
                    'notifications' => $formattedNotifications,
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Notifications Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500);
        }
    }

    /**
     * تحديد إشعار واحد كمقروء
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $notification = $request->user()->notifications()->findOrFail($id);
            $notification->markAsRead();

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديد الإشعار كمقروء.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'الإشعار غير موجود.'], 404);
        }
    }

    /**
     * تحديد كل الإشعارات كمقروءة (زر: تعيين الكل كمقروء)
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $request->user()->unreadNotifications->markAsRead();

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديد جميع الإشعارات كمقروءة.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني.'], 500);
        }
    }

    /**
     * حذف إشعار
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $notification = $request->user()->notifications()->findOrFail($id);
            $notification->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف الإشعار بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'الإشعار غير موجود.'], 404);
        }
    }
}
