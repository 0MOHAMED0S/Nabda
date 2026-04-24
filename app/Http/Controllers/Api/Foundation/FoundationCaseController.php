<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\FoundationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class FoundationCaseController extends Controller
{
    /**
     * مصفوفة رسائل التحقق باللغة العربية الشاملة لكل الحالات
     */
    private function validationMessages()
    {
        return [
            'title.required'               => 'عنوان الحالة مطلوب.',
            'campaign_type.required'       => 'يرجى اختيار نوع الحملة.',
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

            // 🎯 رسائل التحقق الذكية للمبلغ ونوع الهدف
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

    /** 1. جلب كل الحالات */
/** 1. جلب كل الحالات (مع الإحصائيات وكل تفاصيل الحالة) */
    public function index(Request $request)
    {
        try {
            $cases = $request->user()->cases()
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
                $isFinancial = $case->goal_type === 'financial';
                $collected = $isFinancial ? ($case->collected_amount ?? 0) : ($case->donors_count ?? 0);
                $percentage = null;

                if ($isFinancial && $case->target_amount > 0) {
                    $percentage = min(100, round(($collected / $case->target_amount) * 100, 1));
                } elseif (!$isFinancial && $case->status === 'completed') {
                    $percentage = 100;
                }

                // تحويل جميع بيانات الحالة الأصلية إلى مصفوفة
                $caseData = $case->toArray();

                // إضافة الحسابات الإضافية للواجهة
                $caseData['target_amount']         = $isFinancial ? $case->target_amount : 'كمية (عيني)';
                $caseData['collected_amount']      = $collected;
                $caseData['completion_percentage'] = $percentage;
                $caseData['donors_count']          = $case->donors_count ?? 0;

                // إضافة صورة مصغرة للجدول (اختياري للفرونت إند)
                $caseData['thumbnail'] = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;

                return $caseData;
            });

            return response()->json([
                'status'     => true,
                'message'    => $cases->isEmpty() ? 'لا توجد حالات مضافة حالياً.' : 'تم جلب بيانات الحالات بنجاح.',
                'statistics' => $statistics,
                'data'       => $formattedCases
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Cases Index Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب البيانات.'], 500);
        }
    }

    /** 2. إضافة حالة جديدة */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'                  => 'required|string|max:255',
            'campaign_type'          => 'required|string|max:255',
            'main_description'       => 'required|string',
            'additional_description' => 'nullable|string',
            'beneficiary_name'       => 'required|string|max:255',
            'beneficiary_age'        => 'required|integer|min:0|max:150',
            'beneficiary_address'    => 'required|string|max:500',
            'priority'               => 'required|in:urgent,normal',
            'end_date'               => 'required|date|after:today',
            'goal_type'              => 'required|in:financial,in-kind',
            'target_amount'          => 'required_if:goal_type,financial|prohibited_if:goal_type,in-kind|numeric|min:1',
            'images'                 => 'nullable|array',
            'images.*'               => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'documents'              => 'nullable|array',
            'documents.*'            => 'file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'video'                  => 'nullable|file|mimes:mp4,avi,mov|max:51200',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'تعذر الحفظ لوجود أخطاء في البيانات.', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->only(['title', 'campaign_type', 'main_description', 'additional_description', 'beneficiary_name', 'beneficiary_age', 'beneficiary_address', 'priority', 'end_date', 'goal_type', 'target_amount']);

            if ($data['goal_type'] === 'in-kind') $data['target_amount'] = null;

            if ($request->hasFile('images')) {
                $paths = []; foreach ($request->file('images') as $img) $paths[] = $img->store('foundations/cases/images', 'public');
                $data['images'] = $paths;
            }
            if ($request->hasFile('documents')) {
                $paths = []; foreach ($request->file('documents') as $doc) $paths[] = $doc->store('foundations/cases/documents', 'public');
                $data['documents'] = $paths;
            }
            if ($request->hasFile('video')) {
                $data['video'] = $request->file('video')->store('foundations/cases/videos', 'public');
            }

            $case = $request->user()->cases()->create($data);
            return response()->json(['status' => true, 'message' => 'تم إضافة الحالة بنجاح.', 'data' => $case], 201);
        } catch (Exception $e) {
            Log::error("API Foundation Case Store Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء حفظ الحالة.'], 500);
        }
    }

    /** 3. عرض حالة واحدة */
/** 3. عرض حالة واحدة مع حساباتها */
    public function show(Request $request, $id)
    {
        try {
            // جلب الحالة مع حساب التبرعات الخاصة بها
            $case = $request->user()->cases()
                ->withCount(['donations as donors_count' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->find($id);

            if (!$case) {
                return response()->json(['status' => false, 'message' => 'الحالة غير موجودة.'], 404);
            }

            $isFinancial = $case->goal_type === 'financial';
            $collected = $isFinancial ? ($case->collected_amount ?? 0) : ($case->donors_count ?? 0);
            $percentage = null;

            if ($isFinancial && $case->target_amount > 0) {
                $percentage = min(100, round(($collected / $case->target_amount) * 100, 1));
            } elseif (!$isFinancial && $case->status === 'completed') {
                $percentage = 100;
            }

            // تجهيز البيانات النهائية
            $caseData = $case->toArray();
            $caseData['target_amount']         = $isFinancial ? $case->target_amount : 'كمية (عيني)';
            $caseData['collected_amount']      = $collected;
            $caseData['completion_percentage'] = $percentage;
            $caseData['donors_count']          = $case->donors_count ?? 0;

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الحالة بنجاح.',
                'data'    => $caseData
            ], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Case Show Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالة.'], 500);
        }
    }

    /** 4. تحديث حالة (المُحسّنة والمحمية) */
    public function update(Request $request, $id)
    {
        $case = $request->user()->cases()->find($id);
        if (!$case) return response()->json(['status' => false, 'message' => 'الحالة غير موجودة أو تم حذفها.'], 404);

        // معرفة نوع الهدف النهائي (سواء أرسله الآن في الطلب أو الموجود مسبقاً في الداتابيز)
        $currentGoalType = $request->input('goal_type', $case->goal_type);

        // القواعد الأساسية
        $rules = [
            'title'                  => 'sometimes|required|string|max:255',
            'campaign_type'          => 'sometimes|required|string|max:255',
            'main_description'       => 'sometimes|required|string',
            'additional_description' => 'sometimes|nullable|string',
            'beneficiary_name'       => 'sometimes|required|string|max:255',
            'beneficiary_age'        => 'sometimes|required|integer|min:0',
            'beneficiary_address'    => 'sometimes|required|string|max:500',
            'priority'               => 'sometimes|required|in:urgent,normal',
            'end_date'               => 'sometimes|required|date',
            'goal_type'              => 'sometimes|required|in:financial,in-kind',

            // 🛡️ تم إصلاح الخطأ: إضافة التحقق من محتوى المصفوفات
            'images'                 => 'sometimes|nullable|array',
            'images.*'               => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'documents'              => 'sometimes|nullable|array',
            'documents.*'            => 'file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'video'                  => 'sometimes|nullable|file|mimes:mp4,avi,mov|max:51200',
        ];

        // 🎯 🛡️ القواعد الديناميكية لـ target_amount (إصلاح الثغرة المنطقية)
        if ($currentGoalType === 'financial') {
            if ($request->has('goal_type')) {
                // إذا أرسل المستخدم تعديلاً صريحاً على "نوع الهدف" ليصبح مالي، فهو مجبر على تمرير المبلغ
                $rules['target_amount'] = 'required|numeric|min:1';
            } else {
                // إذا كان الهدف مالي مسبقاً، وهو يُعدل شيئاً آخر (كالاسم)، فلا نجبره على تمرير المبلغ مجدداً، ولكن إن مرره يجب أن يكون صالحاً
                $rules['target_amount'] = 'sometimes|required|numeric|min:1';
            }
        } else {
            // إذا كان الهدف النهائي "عيني"، نمنع تماماً تمرير أي مبلغ
            $rules['target_amount'] = 'prohibited';
        }

        $validator = Validator::make($request->all(), $rules, $this->validationMessages());

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'تعذر التحديث لوجود أخطاء في البيانات.', 'errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->only([
                'title', 'campaign_type', 'main_description', 'additional_description',
                'beneficiary_name', 'beneficiary_age', 'beneficiary_address', 'priority',
                'end_date', 'goal_type', 'target_amount', 'status'
            ]);

            // 🛡️ تنظيف البيانات: التأكد من تصفير المبلغ في حال كان الهدف النهائي عيني
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

            if (!empty($data)) $case->update($data);
            return response()->json(['status' => true, 'message' => 'تم تحديث بيانات الحالة بنجاح.', 'data' => $case], 200);

        } catch (Exception $e) {
            Log::error("API Foundation Case Update Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء التحديث.'], 500);
        }
    }

    /** 5. حذف حالة بالكامل مع ملفاتها */
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

    /** 6. حذف ملف محدد (صورة/مستند/فيديو) */
/** 6. حذف ملف محدد (صورة/مستند/فيديو) */
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

            // 🎯 التصحيح الجوهري: تنظيف المسار (Path Cleaning)
            $path = $request->file_path;

            // إذا كان المسار يحتوي على الرابط الكامل أو كلمة /storage/ نقوم باقتطاعها
            if (str_contains($path, '/storage/')) {
                // نأخذ ما بعد كلمة /storage/ ليتطابق مع ما في قاعدة البيانات
                $path = explode('/storage/', $path)[1];
            }

            $type = $request->file_type;

            // أ) معالجة الفيديو
            if ($type === 'video') {
                if ($case->video === $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    $case->update(['video' => null]);
                    return response()->json(['status' => true, 'message' => 'تم حذف الفيديو بنجاح.']);
                }
            }
            // ب) معالجة الصور والمستندات (المصفوفات)
            else {
                $column = $type === 'image' ? 'images' : 'documents';
                $files = $case->{$column} ?? [];

                $index = array_search($path, $files);

                if ($index !== false) {
                    // حذف الملف من السيرفر
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }

                    // حذف المسار من المصفوفة
                    unset($files[$index]);

                    // إعادة ترتيب مفاتيح المصفوفة (ضروري جداً)
                    $updatedFiles = array_values($files);

                    // حفظ التعديل في الداتابيز
                    $case->update([$column => $updatedFiles]);

                    return response()->json([
                        'status'  => true,
                        'message' => 'تم حذف الملف بنجاح.',
                        'data'    => $updatedFiles
                    ], 200);
                }
            }

            // إذا لم يتم العثور على التطابق بعد التنظيف
            return response()->json(['status' => false, 'message' => 'الملف المطلوب غير موجود في هذه الحالة.'], 404);

        } catch (Exception $e) {
            Log::error("API Foundation Case Delete File Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء محاولة حذف الملف.'], 500);
        }
    }

    /** * 7. تحديث حالة الحملة فقط (مستقلة)
     */
    public function updateStatus(Request $request, $id)
    {
        // 1. التحقق من صحة الحالة المدخلة
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
            // 2. البحث عن الحالة (مع التأكد أنها تتبع للمؤسسة المسجلة الدخول)
            $case = $request->user()->cases()->find($id);

            if (!$case) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الحالة غير موجودة أو تم حذفها.'
                ], 404);
            }

            // 3. تحديث الحالة
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
