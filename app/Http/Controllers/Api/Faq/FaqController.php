<?php

namespace App\Http\Controllers\Api\Faq;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Support\Facades\Log;
use Exception;

class FaqController extends Controller
{
    /**
     * API: جلب قائمة الأسئلة الشائعة المفعلة
     * (Public Endpoint)
     */
    public function index()
    {
        try {
            // 1. تحسين الأداء (Performance):
            // - استخدام الـ Scope (active) لجلب المفعل فقط.
            // - استخدام select لجلب الحقول المطلوبة فقط لتوفير الذاكرة (بدون الحاجة لـ map).
            $faqs = Faq::active()
                ->select('id', 'question', 'answer')
                ->get();

            // 2. تطبيق معايير RESTful: إرجاع 200 مع مصفوفة فارغة بدلاً من 404
            if ($faqs->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد أسئلة شائعة متاحة حالياً.',
                    'data'    => []
                ], 200);
            }

            // 3. حالة النجاح ووجود بيانات
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الأسئلة الشائعة بنجاح.',
                'data'    => $faqs
            ], 200);

        } catch (Exception $e) {
            // 4. تسجيل الخطأ الفني أمنياً في السيرفر
            Log::error('API FAQ Index Error: ' . $e->getMessage());

            // 5. إرجاع رسالة خطأ موحدة بدون كشف تفاصيل السيرفر للمستخدم
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب الأسئلة الشائعة.',
                'data'    => [] // حماية للـ Frontend من الأخطاء
            ], 500);
        }
    }
}
