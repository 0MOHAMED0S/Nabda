<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\FoundationFaq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationFaqController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية (مُعاد استخدامها)
     */
    private $validationMessages = [
        'question.required' => 'حقل السؤال مطلوب ولا يمكن تركه فارغاً.',
        'question.string'   => 'يجب أن يكون السؤال نصاً.',
        'question.max'      => 'يجب ألا يتجاوز طول السؤال 255 حرفاً.',
        'answer.required'   => 'حقل الإجابة مطلوب.',
        'answer.string'     => 'يجب أن تكون الإجابة نصاً.',
        'status.required'   => 'يرجى تحديد حالة السؤال.',
        'status.in'         => 'حالة السؤال المدخلة غير صحيحة (يجب أن تكون: published أو archived).',
    ];

    /**
     * API: جلب جميع الأسئلة الشائعة الخاصة بالمؤسسة
     */
    public function index(Request $request)
    {
        try {
            // جلب الأسئلة المرتبطة بالمؤسسة التي سجلت الدخول حالياً
            $faqs = $request->user()->faqs()->orderBy('created_at', 'desc')->get();

            // حالة: إذا لم تقم المؤسسة بإضافة أي أسئلة بعد
            if ($faqs->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد أسئلة شائعة مضافة حتى الآن.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الأسئلة الشائعة بنجاح.',
                'data'    => $faqs
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation FAQs Index Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب البيانات.'
            ], 500);
        }
    }

    /**
     * API: إضافة سؤال جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:255',
            'answer'   => 'required|string',
            'status'   => 'required|in:published,archived',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة البيانات المدخلة وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // إنشاء السؤال وربطه تلقائياً بالمؤسسة (بفضل العلاقة faqs)
            $faq = $request->user()->faqs()->create($request->only(['question', 'answer', 'status']));

            return response()->json([
                'status'  => true,
                'message' => 'تمت إضافة السؤال بنجاح.',
                'data'    => $faq
            ], 201);

        } catch (Exception $e) {
            Log::error("API Foundation FAQ Store Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ غير متوقع أثناء حفظ السؤال.'
            ], 500);
        }
    }

    /**
     * API: عرض تفاصيل سؤال محدد
     */
    public function show(Request $request, $id)
    {
        try {
            $faq = $request->user()->faqs()->find($id);

            // التحقق من وجود السؤال وأنه يتبع للمؤسسة
            if (!$faq) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لم يتم العثور على السؤال المطلوب، أو أنك لا تملك صلاحية الوصول إليه.'
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل السؤال بنجاح.',
                'data'    => $faq
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation FAQ Show Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب السؤال.'
            ], 500);
        }
    }

    /**
     * API: تعديل سؤال موجود (يدعم التحديث الجزئي - Partial Update)
     */
    public function update(Request $request, $id)
    {
        // استخدام 'sometimes' تعني: "تحقق من هذا الحقل فقط إذا تم إرساله في الـ Request"
        // هذا يسمح للفرونت إند بتحديث الـ status فقط دون الحاجة لإرسال السؤال والإجابة مرة أخرى
        $validator = Validator::make($request->all(), [
            'question' => 'sometimes|required|string|max:255',
            'answer'   => 'sometimes|required|string',
            'status'   => 'sometimes|required|in:published,archived',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات التحديث غير صالحة.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // التأكد من أن المستخدم أرسل بيانات لتحديثها فعلياً
        if (empty($request->all())) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم إرسال أي بيانات جديدة ليتم تحديثها.'
            ], 400); // 400 Bad Request
        }

        try {
            $faq = $request->user()->faqs()->find($id);

            if (!$faq) {
                return response()->json([
                    'status'  => false,
                    'message' => 'السؤال المراد تحديثه غير موجود.'
                ], 404);
            }

            // تحديث الحقول التي تم تمريرها فقط
            $faq->update($request->only(['question', 'answer', 'status']));

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث السؤال بنجاح.',
                'data'    => $faq
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation FAQ Update Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء التحديث.'
            ], 500);
        }
    }

    /**
     * API: حذف سؤال
     */
    public function destroy(Request $request, $id)
    {
        try {
            $faq = $request->user()->faqs()->find($id);

            if (!$faq) {
                return response()->json([
                    'status'  => false,
                    'message' => 'السؤال المراد حذفه غير موجود بالفعل.'
                ], 404);
            }

            $faq->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف السؤال نهائياً بنجاح.'
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation FAQ Delete Error: " . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء محاولة الحذف.'
            ], 500);
        }
    }
}
