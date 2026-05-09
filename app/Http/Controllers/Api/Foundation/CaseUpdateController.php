<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\CaseUpdate;
use App\Models\FoundationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Exception;

class CaseUpdateController extends Controller
{
    private function validationMessages()
    {
        return [
            'title.required'       => 'عنوان التحديث مطلوب.',
            'title.max'            => 'عنوان التحديث يجب ألا يتجاوز 255 حرفاً.',
            'update_date.required' => 'تاريخ التحديث مطلوب.',
            'update_date.date'     => 'صيغة تاريخ التحديث غير صحيحة.',
            'description.required' => 'وصف التحديث مطلوب.',
        ];
    }

    /**
     * API: جلب جميع التحديثات الخاصة بحالة معينة
     */
    public function index(Request $request, $caseId): JsonResponse
    {
        try {
            // التأكد من أن الحالة موجودة وتخص المؤسسة الحالية (حماية أمنية)
            $case = FoundationCase::where('id', $caseId)
                ->where('foundation_id', $request->user()->id)
                ->first();

            if (!$case) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الحالة غير موجودة أو لا تملك صلاحية عرضها.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // جلب التحديثات مرتبة بالأحدث تاريخاً
            $updates = CaseUpdate::where('foundation_case_id', $case->id)
                ->orderBy('update_date', 'desc')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تحديثات الحالة بنجاح.',
                'data'    => $updates
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Fetch Case Updates Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب التحديثات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: إضافة تحديث جديد لحالة معينة
     */
    public function store(Request $request, $caseId): JsonResponse
    {
        try {
            // التأكد من أن الحالة موجودة وتخص المؤسسة الحالية
            $case = FoundationCase::where('id', $caseId)
                ->where('foundation_id', $request->user()->id)
                ->first();

            if (!$case) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الحالة غير موجودة أو لا تملك صلاحية تعديلها.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'update_date' => 'required|date',
                'description' => 'required|string',
            ], $this->validationMessages());

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $update = CaseUpdate::create([
                'foundation_case_id' => $case->id,
                'title'              => $request->title,
                'update_date'        => $request->update_date,
                'description'        => $request->description,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم إضافة تحديث الحالة بنجاح.',
                'data'    => $update
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Create Case Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء حفظ التحديث.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: تعديل تحديث سابق
     */
    public function update(Request $request, $updateId): JsonResponse
    {
        try {
            // جلب التحديث والتأكد أن الحالة المرتبطة به تخص المؤسسة الحالية
            $update = CaseUpdate::whereHas('case', function ($query) use ($request) {
                $query->where('foundation_id', $request->user()->id);
            })->where('id', $updateId)->first();

            if (!$update) {
                return response()->json([
                    'status'  => false,
                    'message' => 'التحديث غير موجود أو لا تملك صلاحية تعديله.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $validator = Validator::make($request->all(), [
                'title'       => 'required|string|max:255',
                'update_date' => 'required|date',
                'description' => 'required|string',
            ], $this->validationMessages());

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
            }

            $update->update($request->only(['title', 'update_date', 'description']));

            return response()->json([
                'status'  => true,
                'message' => 'تم تعديل التحديث بنجاح.',
                'data'    => $update
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Edit Case Update Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء التعديل.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: حذف تحديث
     */
    public function destroy(Request $request, $updateId): JsonResponse
    {
        try {
            // جلب التحديث أمنياً عبر التحقق من ملكية المؤسسة
            $update = CaseUpdate::whereHas('case', function ($query) use ($request) {
                $query->where('foundation_id', $request->user()->id);
            })->where('id', $updateId)->first();

            if (!$update) {
                return response()->json(['status' => false, 'message' => 'التحديث غير موجود.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $update->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف التحديث بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Delete Case Update Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء الحذف.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
