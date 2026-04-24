<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class FaqController extends Controller
{
    /**
     * عرض قائمة الأسئلة الشائعة مرتبة
     */
    public function index()
    {
        try {
            // جلب الأسئلة مرتبة حسب حقل الترتيب (order)
            $faqs = Faq::orderBy('order', 'asc')->get();
            return view('admin.faqs.index', compact('faqs'));

        } catch (Exception $e) {
            Log::error("Faq Index Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحميل بيانات الأسئلة الشائعة. يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * حفظ سؤال جديد في قاعدة البيانات
     */
    public function store(Request $request)
    {
        // 1. التحقق من البيانات
        $request->validate([
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string',
            'order'     => 'nullable|integer|min:1',
        ], [
            'question.required' => 'يجب إدخال نص السؤال.',
            'question.string'   => 'نص السؤال يجب أن يكون صالحاً.',
            'question.max'      => 'نص السؤال طويل جداً، الحد الأقصى 255 حرفاً.',
            'answer.required'   => 'يجب إدخال الإجابة الخاصة بالسؤال.',
            'answer.string'     => 'نص الإجابة يجب أن يكون صالحاً.',
            'order.integer'     => 'قيمة الترتيب يجب أن تكون رقماً صحيحاً.',
            'order.min'         => 'أقل قيمة للترتيب هي 1.',
        ]);

        try {
            $data = $request->only(['question', 'answer']);
            $data['is_active'] = $request->has('is_active');

            // --- خوارزمية الترتيب الذكي (الإضافة) ---
            $requestedOrder = $request->input('order');
            $maxOrder = Faq::max('order') ?? 0;

            if ($requestedOrder) {
                // إذا أدخل المستخدم ترتيباً، نقوم بدفع كل العناصر التي تساويه أو تكبره خطوة للأمام
                Faq::where('order', '>=', $requestedOrder)->increment('order');
                $data['order'] = $requestedOrder;
            } else {
                // إذا تركه فارغاً، نضعه في نهاية القائمة
                $data['order'] = $maxOrder + 1;
            }

            Faq::create($data);

            return back()->with('success', 'تم إضافة السؤال الشائع بنجاح.');

        } catch (Exception $e) {
            Log::error("Faq Store Error: " . $e->getMessage());
            return back()->with('error', 'حدث خطأ تقني أثناء الحفظ، يرجى المحاولة لاحقاً.');
        }
    }

    /**
     * تحديث بيانات سؤال موجود
     */
    public function update(Request $request, Faq $faq)
    {
        try {
            $request->validate([
                'question'  => 'required|string|max:255',
                'answer'    => 'required|string',
                'order'     => 'nullable|integer|min:1',
            ], [
                'question.required' => 'حقل السؤال مطلوب لتعديل البيانات.',
                'question.max'      => 'نص السؤال طويل جداً.',
                'answer.required'   => 'حقل الإجابة مطلوب لتعديل البيانات.',
                'order.integer'     => 'قيمة الترتيب يجب أن تكون رقماً صحيحاً.',
                'order.min'         => 'أقل قيمة للترتيب هي 1.',
            ]);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->withInput()
                ->with('edit_id', $faq->id);
        }

        try {
            $data = $request->only(['question', 'answer']);
            $data['is_active'] = $request->has('is_active');

            // --- خوارزمية الترتيب الذكي (التحديث) ---
            $newOrder = $request->input('order');
            $oldOrder = $faq->order;

            if ($newOrder && $newOrder != $oldOrder) {
                if ($newOrder < $oldOrder) {
                    // إذا قام بنقل السؤال للأعلى (مثال: من 5 إلى 2) -> ننزل الباقي للأسفل
                    Faq::whereBetween('order', [$newOrder, $oldOrder - 1])->increment('order');
                } else {
                    // إذا قام بنقل السؤال للأسفل (مثال: من 2 إلى 5) -> نرفع الباقي للأعلى
                    Faq::whereBetween('order', [$oldOrder + 1, $newOrder])->decrement('order');
                }
                $data['order'] = $newOrder;
            } elseif (!$newOrder) {
                // إذا ترك الحقل فارغاً، نحافظ على ترتيبه القديم
                $data['order'] = $oldOrder;
            }

            $faq->update($data);

            return back()->with('success', 'تم تحديث بيانات السؤال بنجاح.');

        } catch (Exception $e) {
            Log::error("Faq Update Error ID {$faq->id}: " . $e->getMessage());
            return back()->with('error', 'تعذر تحديث البيانات حالياً، حاول مرة أخرى.');
        }
    }

    /**
     * حذف سؤال نهائياً
     */
    public function destroy(Faq $faq)
    {
        try {
            $deletedOrder = $faq->order;

            $faq->delete();

            // --- خوارزمية الترتيب الذكي (الحذف) ---
            // نقوم بسحب كل الأسئلة التي كانت تحت هذا السؤال درجة واحدة للأعلى لسد الفراغ
            Faq::where('order', '>', $deletedOrder)->decrement('order');

            return back()->with('success', 'تم حذف السؤال نهائياً من النظام وإعادة ترتيب القائمة.');

        } catch (Exception $e) {
            Log::error("Faq Delete Error ID {$faq->id}: " . $e->getMessage());
            return back()->with('error', 'فشلت عملية الحذف، قد يكون العنصر مرتبطاً ببيانات أخرى.');
        }
    }
}
