<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Volunteer; // 🎯 تأكد من استيراد موديل المتطوع
use Symfony\Component\HttpFoundation\Response;

class IsVolunteer
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. التأكد من وجود توكن صالح لتسجيل الدخول
        if (!auth('sanctum')->check()) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بالدخول. يرجى تسجيل الدخول أولاً.'
            ], 401);
        }

        // 2. 🛡️ حماية الجدار الناري: التأكد من أن صاحب التوكن هو "متطوع"
        // (وليس مؤسسة Foundation أو مستخدم عادي User)
        if (!$request->user() instanceof Volunteer) {
            return response()->json([
                'status'  => false,
                'message' => 'صلاحيات مرفوضة. هذا المسار مخصص للمتطوعين فقط.'
            ], 403); // 403 Forbidden
        }

        // 3. 🎯 حماية إضافية (اختيارية ولكن يُنصح بها): التأكد من أن حساب المتطوع نشط (غير موقوف)
        // (لقد تأكدنا من ذلك في الـ Login، ولكن ماذا لو تم إيقافه بعد تسجيل الدخول؟)
        // يمكنك إزالة هذا الجزء إذا كنت تريد السماح للمتطوع المرفوض/المعلق بتسجيل الخروج فقط.
        $volunteer = $request->user();
        if ($volunteer->status === 'pending' || $volunteer->status === 'rejected') {
             // استثناء مسار تسجيل الخروج من هذا الشرط حتى يتمكن من الخروج إذا أراد
             if (!$request->is('api/volunteer/logout')) {
                 return response()->json([
                     'status'  => false,
                     'message' => 'حسابك غير مفعل حالياً. يرجى مراجعة الإدارة.'
                 ], 403);
             }
        }

        return $next($request);
    }
}
