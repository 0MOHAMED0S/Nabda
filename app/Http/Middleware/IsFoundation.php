<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsFoundation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // التحقق من أن الطلب قادم من مستخدم مسجل الدخول عبر حرس "المؤسسات"
        if (!Auth::guard('foundation')->check()) {
            return response()->json([
                'status'  => false,
                'message' => 'صلاحيات مرفوضة. يرجى تسجيل الدخول بحساب "مؤسسة" للوصول إلى هذه البيانات.'
            ], 401); // 401 Unauthorized
        }

        // إخبار لارافيل أننا نستخدم هذا الـ Guard في بقية دورة حياة الطلب (Request Lifecycle)
        // هذا مفيد جداً حتى يعمل دالة auth()->user() بشكل صحيح وتجلب بيانات المؤسسة
        Auth::shouldUse('foundation');

        return $next($request);
    }
}
