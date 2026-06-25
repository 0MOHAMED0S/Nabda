<?php

namespace App\Http\Controllers\Api\Hero;

use App\Http\Controllers\Controller;
use App\Models\Ticker;
use Exception;
use Illuminate\Support\Facades\Log;

class TickerController extends Controller
{
    public function index()
    {
        try {
            $tickers = Ticker::select('id', 'content')
                ->where('is_active', true) // 🎯 التعديل الأهم: منع ظهور الأخبار الموقوفة للجمهور
                ->orderBy('order', 'asc')  // 🎯 ترتيب الأخبار حسب ما حددته الإدارة
                ->orderBy('created_at', 'desc') // الترتيب الثانوي حسب الأحدث
                ->get();

            if ($tickers->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد تنبيهات أو أخبار في الشريط حالياً.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الشريط المتحرك بنجاح.',
                'data'    => $tickers
            ], 200);
        } catch (Exception $e) {
            Log::error('API Get Tickers Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب بيانات الشريط.',
                'data'    => null
            ], 500);
        }
    }
}
