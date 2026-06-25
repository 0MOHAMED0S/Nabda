<?php

namespace App\Http\Controllers\Api\Faq;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Support\Facades\Log;
use Exception;

class FaqController extends Controller
{

    public function index()
    {
        try {
            $faqs = Faq::active()
                ->select('id', 'question', 'answer')
                ->get();
            if ($faqs->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد أسئلة شائعة متاحة حالياً.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الأسئلة الشائعة بنجاح.',
                'data'    => $faqs
            ], 200);

        } catch (Exception $e) {
            Log::error('API FAQ Index Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب الأسئلة الشائعة.',
                'data'    => [] // حماية للـ Frontend من الأخطاء
            ], 500);
        }
    }
}
