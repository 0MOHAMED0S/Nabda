<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User; // 🎯 تأكد من استيراد موديل المستخدم
use Symfony\Component\HttpFoundation\Response;

class IsUser
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

        // 2. 🛡️ حماية الجدار الناري: التأكد من أن صاحب التوكن هو "مستخدم عادي"
        // (وليس مؤسسة Foundation أو متطوع Volunteer)
        if (!$request->user() instanceof User) {
            return response()->json([
                'status'  => false,
                'message' => 'صلاحيات مرفوضة. هذا المسار مخصص للمستخدمين (المتبرعين) فقط.'
            ], 403); // 403 Forbidden
        }

        return $next($request);
    }
}
