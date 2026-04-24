<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\FoundationBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationBranchController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية
     */
    private $validationMessages = [
        'name.required'        => 'اسم الفرع مطلوب ولا يمكن تركه فارغاً.',
        'name.string'          => 'يجب أن يكون اسم الفرع نصاً.',
        'name.max'             => 'يجب ألا يتجاوز طول اسم الفرع 255 حرفاً.',

        'phone.required'       => 'رقم هاتف الفرع مطلوب للتواصل.',
        'phone.string'         => 'رقم الهاتف يجب أن يكون نصاً.',
        'phone.max'            => 'رقم الهاتف أطول من المسموح به.',

        'address.required'     => 'العنوان الكامل للفرع مطلوب.',
        'address.string'       => 'يجب أن يكون العنوان نصاً.',

        'email.email'          => 'صيغة البريد الإلكتروني غير صحيحة.',
        'email.max'            => 'البريد الإلكتروني طويل جداً.',

        'coordinates.required' => 'إحداثيات الموقع مطلوبة لظهور الفرع على الخريطة.',
        'coordinates.string'   => 'الإحداثيات يجب أن تكون نصاً (مثال: 30.0444,31.2357).',
    ];

    /**
     * API: جلب جميع فروع المؤسسة الحالية
     */
    public function index(Request $request)
    {
        try {
            $branches = $request->user()->branches()->orderBy('created_at', 'desc')->get();

            if ($branches->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد فروع مضافة حالياً. ابدأ بإضافة فروع مؤسستك.',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الفروع بنجاح.',
                'data'    => $branches
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Branches Index Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب البيانات.'
            ], 500);
        }
    }

    /**
     * API: إضافة فرع جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:50',
            'address'     => 'required|string',
            'email'       => 'nullable|email|max:255', // اختياري
            'coordinates' => 'required|string|max:255',
        ], $this->validationMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة البيانات المدخلة وإصلاح الأخطاء.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // يتم إنشاء الفرع وربطه مباشرة بالمؤسسة صاحبة التوكن
            $branch = $request->user()->branches()->create($request->only([
                'name', 'phone', 'address', 'email', 'coordinates'
            ]));

            return response()->json([
                'status'  => true,
                'message' => 'تمت إضافة الفرع بنجاح.',
                'data'    => $branch
            ], 201);

        } catch (Exception $e) {
            Log::error("API Foundation Branch Store Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ غير متوقع أثناء حفظ بيانات الفرع.'
            ], 500);
        }
    }

    /**
     * API: عرض تفاصيل فرع محدد
     */
    public function show(Request $request, $id)
    {
        try {
            $branch = $request->user()->branches()->find($id);

            if (!$branch) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لم يتم العثور على الفرع المطلوب، أو أنه لا يتبع لمؤسستك.'
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الفرع بنجاح.',
                'data'    => $branch
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Branch Show Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الفرع.'
            ], 500);
        }
    }

    /**
     * API: تعديل فرع موجود (يدعم التحديث الجزئي)
     */
    public function update(Request $request, $id)
    {
        // استخدام sometimes لدعم التحديث الجزئي (Partial Update)
        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:255',
            'phone'       => 'sometimes|required|string|max:50',
            'address'     => 'sometimes|required|string',
            'email'       => 'sometimes|nullable|email|max:255',
            'coordinates' => 'sometimes|required|string|max:255',
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
            $branch = $request->user()->branches()->find($id);

            if (!$branch) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرع المراد تحديثه غير موجود.'
                ], 404);
            }

            $branch->update($request->only([
                'name', 'phone', 'address', 'email', 'coordinates'
            ]));

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث بيانات الفرع بنجاح.',
                'data'    => $branch
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Branch Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء التحديث.'
            ], 500);
        }
    }

    /**
     * API: حذف فرع
     */
    public function destroy(Request $request, $id)
    {
        try {
            $branch = $request->user()->branches()->find($id);

            if (!$branch) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرع المراد حذفه غير موجود بالفعل.'
                ], 404);
            }

            $branch->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف الفرع نهائياً بنجاح.'
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Branch Delete Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء محاولة الحذف.'
            ], 500);
        }
    }
}
