<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\FoundationGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationGoalController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية
     */
    private $validationMessages = [
        'title.required'       => 'عنوان الهدف مطلوب ولا يمكن تركه فارغاً.',
        'title.string'         => 'يجب أن يكون العنوان نصاً.',
        'title.max'            => 'يجب ألا يتجاوز طول العنوان 255 حرفاً.',
        'description.required' => 'وصف الهدف مطلوب.',
        'description.string'   => 'يجب أن يكون الوصف نصاً.',
    ];

    /**
     * API: جلب جميع الأهداف الخاصة بالمؤسسة الحالية
     */
    public function index(Request $request)
    {
        try {
            $goals = $request->user()->goals()->orderBy('created_at', 'desc')->get();

            if ($goals->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد أهداف مضافة حالياً. ابدأ بإضافة أهداف مؤسستك.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب أهداف المؤسسة بنجاح.',
                'data'    => $goals
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Goals Index Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب البيانات.'
            ], 500);
        }
    }

    /**
     * API: إضافة هدف جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة البيانات المدخلة وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // يتم إنشاء الهدف وربطه مباشرة بالمؤسسة صاحبة التوكن
            $goal = $request->user()->goals()->create($request->only(['title', 'description']));

            return response()->json([
                'status'  => true,
                'message' => 'تمت إضافة الهدف بنجاح.',
                'data'    => $goal
            ], 201);

        } catch (Exception $e) {
            Log::error("API Foundation Goal Store Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ غير متوقع أثناء حفظ الهدف.'
            ], 500);
        }
    }

    /**
     * API: عرض تفاصيل هدف محدد
     */
    public function show(Request $request, $id)
    {
        try {
            $goal = $request->user()->goals()->find($id);

            if (!$goal) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لم يتم العثور على الهدف المطلوب، أو أنك لا تملك صلاحية الوصول إليه.'
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الهدف بنجاح.',
                'data'    => $goal
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Goal Show Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب الهدف.'
            ], 500);
        }
    }

    /**
     * API: تعديل هدف موجود (يدعم التحديث الجزئي)
     */
    public function update(Request $request, $id)
    {
        // استخدام sometimes لدعم التحديث الجزئي
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات التحديث غير صالحة.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // منع الطلبات الفارغة
        if (empty($request->all())) {
            return response()->json([
                'status'  => false,
                'message' => 'لم يتم إرسال أي بيانات جديدة ليتم تحديثها.'
            ], 400);
        }

        try {
            $goal = $request->user()->goals()->find($id);

            if (!$goal) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الهدف المراد تحديثه غير موجود.'
                ], 404);
            }

            $goal->update($request->only(['title', 'description']));

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث الهدف بنجاح.',
                'data'    => $goal
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Goal Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء التحديث.'
            ], 500);
        }
    }

    /**
     * API: حذف هدف
     */
    public function destroy(Request $request, $id)
    {
        try {
            $goal = $request->user()->goals()->find($id);

            if (!$goal) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الهدف المراد حذفه غير موجود بالفعل.'
                ], 404);
            }

            $goal->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف الهدف نهائياً بنجاح.'
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Goal Delete Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء محاولة الحذف.'
            ], 500);
        }
    }
}
