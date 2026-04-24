<?php

namespace App\Http\Controllers\Api\Zakat;

use App\Http\Controllers\Controller;
use App\Models\ZakatCondition;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZakatController extends Controller
{
    /**
     * 1. جلب قائمة شروط الزكاة
     * (Public Endpoint)
     */
    public function getConditions()
    {
        try {
            // تحسين الأداء: جلب الحقول المطلوبة فقط مباشرة (لا حاجة لـ map)
            $conditions = ZakatCondition::ordered()
                ->select('id', 'title', 'description', 'icon', 'order')
                ->get();

            // تصحيح RESTful: إرجاع 200 مع مصفوفة فارغة
            if ($conditions->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد شروط زكاة مضافة حالياً.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب شروط الزكاة بنجاح.',
                'data'    => $conditions
            ], 200);

        } catch (Exception $e) {
            Log::error('API Zakat Conditions Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب شروط الزكاة.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * 2. جلب أسعار الذهب المحدثة (مربوط بـ Cache و API خارجي)
     * (Public Endpoint)
     */
    public function getGoldPrices()
    {
        try {
            // استخدام Cache لـ 12 ساعة لتخفيف الضغط وتوفير التكلفة (43200 ثانية)
            $prices = Cache::remember('gold_prices_egp', 43200, function () {

                $apiKey = env('GOLD_API_KEY');

                // 🛡️ برمجة دفاعية: التأكد من وجود المفتاح قبل إرسال الطلب
                if (empty($apiKey)) {
                    throw new Exception('مفتاح API الذهب (GOLD_API_KEY) غير متوفر في إعدادات الخادم.');
                }

                // 🛡️ إضافة timeout(10) لحماية السيرفر من التعليق (Hanging) إذا كان مزود الذهب معطلاً
                $response = Http::withHeaders([
                    'x-access-token' => $apiKey
                ])->timeout(10)->get('https://www.goldapi.io/api/XAU/EGP');

                if ($response->successful()) {
                    $data = $response->json();

                    $pricePerOunce24k = $data['price'];
                    // 1 أونصة = 31.1034768 جرام
                    $pricePerGram24k = $pricePerOunce24k / 31.1034768;

                    return [
                        'currency' => 'EGP',
                        'date'     => now()->format('Y-m-d H:i:s'),
                        'karat_24' => round($pricePerGram24k, 2),
                        'karat_21' => round($pricePerGram24k * (21 / 24), 2),
                        'karat_18' => round($pricePerGram24k * (18 / 24), 2),
                    ];
                }

                // سيقوم هذا الخطأ بمنع الكاش من حفظ بيانات فارغة وسينتقل فوراً للـ catch
                throw new Exception('فشل في جلب البيانات من مزود الذهب الخارجي. كود الرد: ' . $response->status());
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب أسعار الذهب المحدثة بنجاح.',
                'data'    => $prices
            ], 200);

        } catch (Exception $e) {
            Log::error('API Gold Price Error: ' . $e->getMessage());

            // إرجاع رسالة خطأ آمنة للـ Frontend مع data: null لتجنب الأعطال
            return response()->json([
                'status'  => false,
                'message' => 'عذراً، لم نتمكن من تحديث أسعار الذهب حالياً، يرجى المحاولة لاحقاً.',
                'data'    => null
            ], 500);
        }
    }
}
