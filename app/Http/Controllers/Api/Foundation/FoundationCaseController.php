<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationCaseController extends Controller
{

    private function validationMessages()
    {
        return [
            'title.required'               => 'عنوان الحالة مطلوب.',
            'service_id.required'          => 'يرجى اختيار الخدمة المرتبطة بهذه الحالة.',
            'service_id.exists'            => 'الخدمة المختارة غير صالحة أو غير موجودة.',

            'main_description.required'    => 'الوصف الرئيسي للحالة مطلوب.',

            'beneficiary_name.required'    => 'اسم المستفيد مطلوب.',
            'beneficiary_age.required'     => 'عمر المستفيد مطلوب.',
            'beneficiary_age.integer'      => 'عمر المستفيد يجب أن يكون رقماً صحيحاً.',
            'beneficiary_address.required' => 'عنوان المستفيد مطلوب.',

            'priority.required'            => 'يرجى تحديد مستوى الأولوية (عاجلة أم عادية).',
            'end_date.required'            => 'تاريخ انتهاء الحملة مطلوب.',
            'end_date.after'               => 'تاريخ انتهاء الحملة يجب أن يكون تاريخاً مستقبلياً.',

            'goal_type.required'           => 'يرجى تحديد نوع الهدف (مالي أو عيني).',
            'goal_type.in'                 => 'نوع الهدف غير صحيح.',

            'target_amount.required'       => 'المبلغ المطلوب إجباري لأن نوع الهدف (مالي).',
            'target_amount.required_if'    => 'المبلغ المطلوب إجباري عندما تختار أن نوع الهدف (مالي).',
            'target_amount.prohibited'     => 'عذراً، لا يمكنك إدخال مبلغ مالي لأن نوع الهدف (عيني).',
            'target_amount.prohibited_if'  => 'عذراً، لا يمكنك إدخال مبلغ مالي لأن نوع الهدف الذي اخترته هو (عيني).',
            'target_amount.numeric'        => 'المبلغ المطلوب يجب أن يكون رقماً.',
            'target_amount.min'            => 'المبلغ المطلوب يجب أن يكون أكبر من الصفر.',

            'images.array'                 => 'صور الحالة يجب أن تكون مصفوفة.',
            'documents.array'              => 'المستندات يجب أن تكون مصفوفة.',
            'images.*.image'               => 'يجب أن تكون المرفقات صوراً صالحة.',
            'documents.*.mimes'            => 'صيغ المستندات المدعومة: pdf, doc, docx, xls, xlsx.',
        ];
    }

    public function index(Request $request)
    {
        try {
            $cases = $request->user()->cases()
                ->with('service.category') // 👈 جلب الخدمة والتصنيف معاً
                ->withCount(['donations as donors_count' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('created_at', 'desc')
                ->get();

            $statistics = [
                'total_cases'     => $cases->count(),
                'active_cases'    => $cases->where('status', 'active')->count(),
                'completed_cases' => $cases->where('status', 'completed')->count(),
                'stopped_cases'   => $cases->where('status', 'cancelled')->count(),
            ];

            $formattedCases = $cases->map(function ($case) {
                // 🎯 التعديل هنا: نعتبر الحالة مالية إذا كانت 'financial' أو 'both'
                $isFinancial = in_array($case->goal_type, ['financial', 'both']);

                $collected = $isFinancial ? ($case->collected_amount ?? 0) : ($case->donors_count ?? 0);
                $percentage = null;

                if ($isFinancial && $case->target_amount > 0) {
                    $percentage = min(100, round(($collected / $case->target_amount) * 100, 1));
                } elseif (!$isFinancial && $case->status === 'completed') {
                    $percentage = 100;
                }

                // تحويل جميع بيانات الحالة الأصلية إلى مصفوفة
                $caseData = $case->toArray();

                // 2. 🎯 تجهيز بيانات الخدمة وتصنيفها
                if ($case->service) {
                    $caseData['campaign_type']    = $case->service->title; // للحفاظ على توافق الفرونت إند
                    $caseData['service_name']     = $case->service->title;
                    $caseData['service_date']     = $case->service->created_at ? $case->service->created_at->format('Y-m-d') : null;

                    // استخراج اسم التصنيف من العلاقة المتداخلة
                    $caseData['service_category'] = $case->service->category ? $case->service->category->name : 'غير مصنف';
                } else {
                    $caseData['campaign_type']    = 'خدمة عامة';
                    $caseData['service_name']     = 'غير محدد';
                    $caseData['service_date']     = null;
                    $caseData['service_category'] = 'غير مصنف';
                }

                // 3. تحويل مسارات الملفات إلى روابط كاملة (Full URLs)
                if (isset($caseData['images']) && is_array($caseData['images'])) {
                    $caseData['images'] = array_map(function ($img) {
                        return \Illuminate\Support\Str::startsWith($img, ['http://', 'https://']) ? $img : asset('storage/' . $img);
                    }, $caseData['images']);
                }

                if (isset($caseData['documents']) && is_array($caseData['documents'])) {
                    $caseData['documents'] = array_map(function ($doc) {
                        return \Illuminate\Support\Str::startsWith($doc, ['http://', 'https://']) ? $doc : asset('storage/' . $doc);
                    }, $caseData['documents']);
                }

                if (!empty($caseData['video'])) {
                    $caseData['video'] = \Illuminate\Support\Str::startsWith($caseData['video'], ['http://', 'https://'])
                        ? $caseData['video']
                        : asset('storage/' . $caseData['video']);
                }

                // 4. إضافة الحسابات الإضافية للواجهة
                $caseData['target_amount']         = $isFinancial ? $case->target_amount : 'كمية (عيني)';
                $caseData['collected_amount']      = $collected;
                $caseData['completion_percentage'] = $percentage;
                $caseData['donors_count']          = $case->donors_count ?? 0;

                // إضافة صورة مصغرة للجدول برابط كامل
                $caseData['thumbnail'] = (is_array($case->images) && count($case->images) > 0)
                    ? (\Illuminate\Support\Str::startsWith($case->images[0], ['http://', 'https://']) ? $case->images[0] : asset('storage/' . $case->images[0]))
                    : null;

                // تنظيف العلاقة المضافة حتى لا تتكرر البيانات ككائن كامل داخل الرد
                unset($caseData['service']);

                return $caseData;
            });

            return response()->json([
                'status'     => true,
                'message'    => $cases->isEmpty() ? 'لا توجد حالات مضافة حالياً.' : 'تم جلب بيانات الحالات بنجاح.',
                'statistics' => $statistics,
                'data'       => $formattedCases
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Cases Index Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب البيانات.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'                  => 'required|string|max:255',
            'service_id'             => 'required|exists:services,id',
            'main_description'       => 'required|string',
            'additional_description' => 'nullable|string',
            'beneficiary_name'       => 'required|string|max:255',
            'beneficiary_age'        => 'required|integer|min:0|max:150',
            'beneficiary_address'    => 'required|string|max:500',
            'priority'               => 'required|in:urgent,normal',
            'end_date'               => 'required|date|after:today',
            'goal_type'              => 'required|in:financial,in-kind,both',
            'target_amount'          => 'required_if:goal_type,financial,both|prohibited_if:goal_type,in-kind|numeric|min:1',
            'images'                 => 'nullable|array',
            'images.*'               => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'documents'              => 'nullable|array',
            'documents.*'            => 'file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'video'                  => 'nullable|file|mimes:mp4,avi,mov,webm|max:51200',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'تعذر الحفظ لوجود أخطاء في البيانات.',
                'errors'  => $validator->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $data = $request->only(['title', 'service_id', 'main_description', 'additional_description', 'beneficiary_name', 'beneficiary_age', 'beneficiary_address', 'priority', 'end_date', 'goal_type', 'target_amount']);
            if ($data['goal_type'] === 'in-kind') {
                $data['target_amount'] = null;
            }

            if ($request->hasFile('images')) {
                $paths = [];
                foreach ($request->file('images') as $img) $paths[] = $img->store('foundations/cases/images', 'public');
                $data['images'] = $paths;
            }
            if ($request->hasFile('documents')) {
                $paths = [];
                foreach ($request->file('documents') as $doc) $paths[] = $doc->store('foundations/cases/documents', 'public');
                $data['documents'] = $paths;
            }
            if ($request->hasFile('video')) {
                $data['video'] = $request->file('video')->store('foundations/cases/videos', 'public');
            }

            $case = $request->user()->cases()->create($data);
            $caseData = $case->toArray();
            $caseData['campaign_type'] = $case->service ? $case->service->title : 'خدمة عامة';
            unset($caseData['service']); // تنظيف لعدم تكرار البيانات

            if (isset($caseData['images']) && is_array($caseData['images'])) {
                $caseData['images'] = array_map(fn($img) => asset('storage/' . $img), $caseData['images']);
            }

            if (isset($caseData['documents']) && is_array($caseData['documents'])) {
                $caseData['documents'] = array_map(fn($doc) => asset('storage/' . $doc), $caseData['documents']);
            }

            if (!empty($caseData['video'])) {
                $caseData['video'] = asset('storage/' . $caseData['video']);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم إضافة الحالة بنجاح.',
                'data'    => $caseData
            ], 201, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Case Store Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء حفظ الحالة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $case = $request->user()->cases()
                ->with('service.category') // 👈 جلب الخدمة وتصنيفها
                ->withCount(['donations as donors_count' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->find($id);

            if (!$case) {
                return response()->json(['status' => false, 'message' => 'الحالة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $isFinancial = in_array($case->goal_type, ['financial', 'both']);

            $collected = $isFinancial ? ($case->collected_amount ?? 0) : ($case->donors_count ?? 0);
            $percentage = null;

            if ($isFinancial && $case->target_amount > 0) {
                $percentage = min(100, round(($collected / $case->target_amount) * 100, 1));
            } elseif (!$isFinancial && $case->status === 'completed') {
                $percentage = 100;
            }
            $caseData = $case->toArray();

            if ($case->service) {
                $caseData['campaign_type']    = $case->service->title; // للحفاظ على التوافق
                $caseData['service_name']     = $case->service->title;
                $caseData['service_date']     = $case->service->created_at ? $case->service->created_at->format('Y-m-d') : null;
                $caseData['service_category'] = $case->service->category ? $case->service->category->name : 'غير مصنف';
            } else {
                $caseData['campaign_type']    = 'خدمة عامة';
                $caseData['service_name']     = 'غير محدد';
                $caseData['service_date']     = null;
                $caseData['service_category'] = 'غير مصنف';
            }

            if (isset($caseData['images']) && is_array($caseData['images'])) {
                $caseData['images'] = array_map(function ($img) {
                    return \Illuminate\Support\Str::startsWith($img, ['http://', 'https://']) ? $img : asset('storage/' . $img);
                }, $caseData['images']);
            }

            if (isset($caseData['documents']) && is_array($caseData['documents'])) {
                $caseData['documents'] = array_map(function ($doc) {
                    return \Illuminate\Support\Str::startsWith($doc, ['http://', 'https://']) ? $doc : asset('storage/' . $doc);
                }, $caseData['documents']);
            }

            if (!empty($caseData['video'])) {
                $caseData['video'] = \Illuminate\Support\Str::startsWith($caseData['video'], ['http://', 'https://'])
                    ? $caseData['video']
                    : asset('storage/' . $caseData['video']);
            }

            $caseData['target_amount']         = $isFinancial ? $case->target_amount : 'كمية (عيني)';
            $caseData['collected_amount']      = $collected;
            $caseData['completion_percentage'] = $percentage;
            $caseData['donors_count']          = $case->donors_count ?? 0;

            unset($caseData['service']);

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الحالة بنجاح.',
                'data'    => $caseData
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Case Show Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالة.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function update(Request $request, $id)
    {
        $case = $request->user()->cases()->find($id);
        if (!$case) return response()->json(['status' => false, 'message' => 'الحالة غير موجودة أو تم حذفها.'], 404, [], JSON_UNESCAPED_UNICODE);

        $currentGoalType = $request->input('goal_type', $case->goal_type);
        $rules = [
            'title'                  => 'sometimes|required|string|max:255',
            'service_id'             => 'sometimes|required|exists:services,id',

            'main_description'       => 'sometimes|required|string',
            'additional_description' => 'sometimes|nullable|string',
            'beneficiary_name'       => 'sometimes|required|string|max:255',
            'beneficiary_age'        => 'sometimes|required|integer|min:0',
            'beneficiary_address'    => 'sometimes|required|string|max:500',
            'priority'               => 'sometimes|required|in:urgent,normal',
            'end_date'               => 'sometimes|required|date',
            'goal_type'              => 'sometimes|required|in:financial,in-kind,both',
            'images'                 => 'sometimes|nullable|array',
            'images.*'               => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'documents'              => 'sometimes|nullable|array',
            'documents.*'            => 'file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'video'                  => 'sometimes|nullable|file|mimes:mp4,avi,mov,webm|max:51200',
        ];

        if (in_array($currentGoalType, ['financial', 'both'])) {
            if ($request->has('goal_type')) {
                $rules['target_amount'] = 'required|numeric|min:1';
            } else {
                $rules['target_amount'] = 'sometimes|required|numeric|min:1';
            }
        } else {
            $rules['target_amount'] = 'prohibited';
        }

        $validator = Validator::make($request->all(), $rules, $this->validationMessages());

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'تعذر التحديث لوجود أخطاء في البيانات.', 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $data = $request->only([
                'title',
                'service_id',
                'main_description',
                'additional_description',
                'beneficiary_name',
                'beneficiary_age',
                'beneficiary_address',
                'priority',
                'end_date',
                'goal_type',
                'target_amount',
                'status'
            ]);

            $finalGoalType = $data['goal_type'] ?? $case->goal_type;
            if ($finalGoalType === 'in-kind') {
                $data['target_amount'] = null;
            }

            if ($request->hasFile('images')) {
                $paths = $case->images ?? [];
                foreach ($request->file('images') as $img) $paths[] = $img->store('foundations/cases/images', 'public');
                $data['images'] = $paths;
            }
            if ($request->hasFile('documents')) {
                $paths = $case->documents ?? [];
                foreach ($request->file('documents') as $doc) $paths[] = $doc->store('foundations/cases/documents', 'public');
                $data['documents'] = $paths;
            }
            if ($request->hasFile('video')) {
                if ($case->video && Storage::disk('public')->exists($case->video)) Storage::disk('public')->delete($case->video);
                $data['video'] = $request->file('video')->store('foundations/cases/videos', 'public');
            }

            if (!empty($data)) {
                $case->update($data);
            }

            $case->load('service');
            $caseData = $case->toArray();

            $caseData['campaign_type'] = $case->service ? $case->service->title : 'خدمة عامة';
            unset($caseData['service']); // تنظيف

            if (isset($caseData['images']) && is_array($caseData['images'])) {
                $caseData['images'] = array_map(fn($img) => asset('storage/' . $img), $caseData['images']);
            }

            if (isset($caseData['documents']) && is_array($caseData['documents'])) {
                $caseData['documents'] = array_map(fn($doc) => asset('storage/' . $doc), $caseData['documents']);
            }

            if (!empty($caseData['video'])) {
                $caseData['video'] = asset('storage/' . $caseData['video']);
            }

            return response()->json(['status' => true, 'message' => 'تم تحديث بيانات الحالة بنجاح.', 'data' => $caseData], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Foundation Case Update Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء التحديث.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $case = $request->user()->cases()->find($id);
            if (!$case) return response()->json(['status' => false, 'message' => 'الحالة غير موجودة.'], 404);

            if (is_array($case->images)) foreach ($case->images as $img) if (Storage::disk('public')->exists($img)) Storage::disk('public')->delete($img);
            if (is_array($case->documents)) foreach ($case->documents as $doc) if (Storage::disk('public')->exists($doc)) Storage::disk('public')->delete($doc);
            if ($case->video && Storage::disk('public')->exists($case->video)) Storage::disk('public')->delete($case->video);

            $case->delete();
            return response()->json(['status' => true, 'message' => 'تم حذف الحالة وجميع مرفقاتها بنجاح.'], 200);
        } catch (Exception $e) {
            Log::error("API Foundation Case Delete Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء محاولة الحذف.'], 500);
        }
    }

    public function deleteFile(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'file_type' => 'required|in:image,document,video',
        ], [
            'file_path.required' => 'مسار الملف مطلوب.',
            'file_type.required' => 'نوع الملف مطلوب.',
            'file_type.in'       => 'نوع الملف يجب أن يكون: image, document, أو video.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'أخطاء في البيانات.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $case = $request->user()->cases()->find($id);

            if (!$case) {
                return response()->json(['status' => false, 'message' => 'الحالة غير موجودة.'], 404);
            }

            $path = $request->file_path;
            if (str_contains($path, '/storage/')) {
                $path = explode('/storage/', $path)[1];
            }

            $type = $request->file_type;
            if ($type === 'video') {
                if ($case->video === $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    $case->update(['video' => null]);
                    return response()->json(['status' => true, 'message' => 'تم حذف الفيديو بنجاح.']);
                }
            } else {
                $column = $type === 'image' ? 'images' : 'documents';
                $files = $case->{$column} ?? [];

                $index = array_search($path, $files);

                if ($index !== false) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    unset($files[$index]);
                    $updatedFiles = array_values($files);
                    $case->update([$column => $updatedFiles]);

                    return response()->json([
                        'status'  => true,
                        'message' => 'تم حذف الملف بنجاح.',
                        'data'    => $updatedFiles
                    ], 200);
                }
            }

            return response()->json(['status' => false, 'message' => 'الملف المطلوب غير موجود في هذه الحالة.'], 404);
        } catch (Exception $e) {
            Log::error("API Foundation Case Delete File Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء محاولة حذف الملف.'], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,completed,cancelled,archived',
        ], [
            'status.required' => 'يرجى تحديد حالة الحملة الجديدة.',
            'status.in'       => 'الحالة المحددة غير صالحة (يجب أن تكون active, completed, cancelled, أو archived).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'أخطاء في البيانات.',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $case = $request->user()->cases()->find($id);

            if (!$case) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الحالة غير موجودة أو تم حذفها.'
                ], 404);
            }

            $case->update([
                'status' => $request->status
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث حالة الحملة بنجاح.',
                'data'    => $case
            ], 200);
        } catch (Exception $e) {
            Log::error("API Foundation Case Update Status Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء تحديث حالة الحملة.'
            ], 500);
        }
    }
}
