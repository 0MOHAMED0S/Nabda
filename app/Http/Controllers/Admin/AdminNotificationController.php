<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminNotificationController extends Controller
{
    /**
     * دالة مساعدة لجلب الإدمن المسجل دخوله بذكاء
     * (تدعم الـ Guard الافتراضي أو المخصص للإدمن)
     */
    private function getAdmin()
    {
        // إذا كنت تستخدم Guard اسمه admin، سيتم التقاطه، وإلا سيأخذ الافتراضي
        return auth('admin')->check() ? auth('admin')->user() : auth()->user();
    }

    /**
     * عرض صفحة جميع الإشعارات للإدمن
     */
    public function index()
    {
        try {
            $admin = $this->getAdmin();

            if (!$admin) {
                return redirect()->route('login')->with('error', 'انتهت الجلسة، يرجى تسجيل الدخول مجدداً.');
            }

            // جلب الإشعارات الخاصة بالمدير الحالي (الأحدث أولاً)
            $notifications = $admin->notifications()->paginate(15);

            // جلب عدد الإشعارات غير المقروءة
            $unreadCount = $admin->unreadNotifications()->count();

            // 🎯 السطر الذي تم تصحيحه هنا: إرجاع صفحة الإشعارات بدلاً من المؤسسات
            return view('admin.notifications.index', compact('notifications', 'unreadCount'));
        } catch (Exception $e) {
            Log::error("Admin Notifications Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء جلب الإشعارات.');
        }
    }

    /**
     * تحديد إشعار معين كمقروء
     */
    public function markAsRead($id)
    {
        try {
            $admin = $this->getAdmin();
            $notification = $admin->notifications()->findOrFail($id);

            $notification->markAsRead();

            return back()->with('success', 'تم تحديد الإشعار كمقروء.');
        } catch (Exception $e) {
            Log::error("Admin Mark Notification Read Error ID {$id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث حالة الإشعار.');
        }
    }

    /**
     * تحديد جميع الإشعارات كمقروءة دفعة واحدة
     */
    public function markAllAsRead()
    {
        try {
            $admin = $this->getAdmin();
            $admin->unreadNotifications->markAsRead();

            return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
        } catch (Exception $e) {
            Log::error("Admin Mark All Notifications Read Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث الإشعارات.');
        }
    }

    /**
     * حذف إشعار معين
     */
    public function destroy($id)
    {
        try {
            $admin = $this->getAdmin();
            $notification = $admin->notifications()->findOrFail($id);

            $notification->delete();

            return back()->with('success', 'تم حذف الإشعار بنجاح.');
        } catch (Exception $e) {
            Log::error("Admin Delete Notification Error ID {$id}: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف الإشعار.');
        }
    }
}
