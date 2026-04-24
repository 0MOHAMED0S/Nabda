<?php

namespace App\Http\Controllers\Api\Hero;

use App\Http\Controllers\Controller;
use App\Models\Ticker;
use Exception;
use Illuminate\Support\Facades\Log;

class TickerController extends Controller
{
    /**
     * API: جلب بيانات الشريط المتحرك (أحدث الأخبار أو التنبيهات)
     * (Public Endpoint)
     */
    public function index()
    {
        try {
            // 1. الأداء والأمان: جلب الحقول المطلوبة، للأخبار (المفعلة فقط)، ومرتبة بشكل صحيح
            $tickers = Ticker::select('id', 'content')
                ->where('is_active', true) // 🎯 التعديل الأهم: منع ظهور الأخبار الموقوفة للجمهور
                ->orderBy('order', 'asc')  // 🎯 ترتيب الأخبار حسب ما حددته الإدارة
                ->orderBy('created_at', 'desc') // الترتيب الثانوي حسب الأحدث
                ->get();

            // 2. حالة القائمة الفارغة (RESTful Best Practice: 200 OK with empty array)
            if ($tickers->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد تنبيهات أو أخبار في الشريط حالياً.',
                    'data'    => []
                ], 200);
            }

            // 3. حالة النجاح ووجود بيانات
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الشريط المتحرك بنجاح.',
                'data'    => $tickers
            ], 200);

        } catch (Exception $e) {
            // 4. تسجيل الخطأ الفني أمنياً في السيرفر
            Log::error('API Get Tickers Error: ' . $e->getMessage());

            // 5. إرجاع رسالة خطأ موحدة بدون كشف تفاصيل السيرفر للمستخدم
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب بيانات الشريط.',
                'data'    => null
            ], 500);
        }
    }
}
