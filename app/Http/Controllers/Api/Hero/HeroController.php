<?php

namespace App\Http\Controllers\Api\Hero;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use Exception;
use Illuminate\Support\Facades\Log;

class HeroController extends Controller
{
    /**
     * API: جلب بيانات الواجهة الرئيسية (الهيرو)
     * (Public Endpoint)
     */
    public function index()
    {
        try {
            // 1. تحسين الأداء: جلب الحقول المطلوبة فقط بدلاً من جلب كل شيء (Select specific columns)
            $hero = HeroSection::select('id', 'title', 'description', 'video')->first();

            // 2. حالة عدم وجود بيانات (404)
            if (!$hero) {
                return response()->json([
                    'status'  => false,
                    'message' => 'بيانات الواجهة الرئيسية غير متوفرة حالياً.',
                    'data'    => null
                ], 404);
            }

            // 3. حالة النجاح (200) - مع تصحيح حقل الفيديو بدلاً من الصورة
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الواجهة الرئيسية بنجاح.',
                'data'    => [
                    'id'          => $hero->id,
                    'title'       => $hero->title,
                    'description' => $hero->description,
                    // تغيير المفتاح ليكون video_url واستدعاء حقل video
                    'video_url'   => $hero->video ? asset('storage/' . $hero->video) : null,
                ]
            ], 200);
        } catch (Exception $e) {
            // 4. تسجيل الخطأ الفني في الـ Log بوضوح
            Log::error('API Get Hero Section Error: ' . $e->getMessage());

            // 5. إرجاع رسالة خطأ موحدة لا تكشف تفاصيل السيرفر للمستخدم (500)
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء معالجة الطلب.',
                'data'    => null
            ], 500);
        }
    }
}
