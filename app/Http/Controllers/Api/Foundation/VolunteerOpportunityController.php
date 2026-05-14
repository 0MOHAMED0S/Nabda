<?php

namespace App\Http\Controllers\Api\Foundation;

use App\Http\Controllers\Controller;
use App\Models\VolunteerOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Exception;

class VolunteerOpportunityController extends Controller
{
    private function validationMessages()
    {
        return [
            'title.required'               => 'عنوان الفرصة مطلوب.',
            'category.required'            => 'تصنيف الفرصة مطلوب.',
            'description.required'         => 'وصف الفرصة مطلوب.',
            'location.required'            => 'موقع الفرصة مطلوب.',
            'date.required'                => 'تاريخ الفرصة مطلوب.',
            'date.after_or_equal'          => 'تاريخ الفرصة يجب أن يكون اليوم أو في المستقبل.',
            'start_time.required'          => 'وقت بداية الفرصة مطلوب.',
            'end_time.required'            => 'وقت نهاية الفرصة مطلوب.',
            'end_time.after'               => 'وقت النهاية يجب أن يكون بعد وقت البداية.',
            'required_volunteers.required' => 'العدد المطلوب للمتطوعين إجباري.',
            'required_volunteers.min'      => 'يجب طلب متطوع واحد على الأقل.',
            'requirements.required'        => 'متطلبات الفرصة مطلوبة.',
            'requirements.array'           => 'المتطلبات يجب أن تكون في صيغة قائمة.',
            'contact_person.required'      => 'اسم مسؤول الاتصال مطلوب.',
            'contact_phone.required'       => 'رقم هاتف التواصل مطلوب.',
            'status.required'              => 'حالة الفرصة مطلوبة.',
            'status.in'                    => 'حالة الفرصة غير صالحة.',

            // 🎯 رسائل اختيار المتطوعين
            'selected_volunteers.array'    => 'صيغة اختيار المتطوعين غير صحيحة.',
            'selected_volunteers.*.exists' => 'أحد المتطوعين المختارين غير موجود في النظام.',
        ];
    }


/**
     * API: جلب قائمة فرص التطوع الخاصة بالمؤسسة (مع الإحصائيات والتفاصيل الكاملة)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // 1. حساب الإحصائيات العلوية (Stats) كما في تصميم الواجهة
            $baseQuery = VolunteerOpportunity::where('foundation_id', $foundationId);

            $stats = [
                'total'     => (clone $baseQuery)->count(), // إجمالي الفرص
                'active'    => (clone $baseQuery)->where('status', 'active')->count(), // فرص نشطة
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(), // مكتملة
                'archived'  => (clone $baseQuery)->where('status', 'cancelled')->count(), // مؤرشفة (ملغاة)
            ];

            // 2. جلب الفرص مع كافة العلاقات والتفاصيل المطلوبة
            $opportunities = VolunteerOpportunity::where('foundation_id', $foundationId)
                ->with([
                    'foundation:id,name,logo', // جلب بيانات المؤسسة
                    // 🎯 تم إضافة 'governorates' هنا لجلب محافظات المتطوع
                    'volunteers:id,name,email,phone,avatar,governorates'
                ])
                ->withCount(['volunteers as accepted_volunteers_count' => function ($query) {
                    // حساب عدد المقبولين فقط لعرضها في عمود (المطلوب / المسجل)
                    $query->where('opportunity_volunteer.status', 'accepted');
                }])
                ->orderBy('date', 'desc')
                ->paginate(10);

            // 3. تهيئة الروابط الكاملة للصور (Full URLs) وإخفاء المسارات المحلية
            $opportunities->getCollection()->transform(function ($opportunity) {

                // إضافة الرابط الكامل للوجو المؤسسة (إن وُجد)
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                // إضافة الرابط الكامل لصور المتطوعين (إن وُجدوا)
                if ($opportunity->volunteers) {
                    $opportunity->volunteers->each(function ($volunteer) {
                        $volunteer->avatar_url = $volunteer->avatar
                            ? asset('storage/' . $volunteer->avatar)
                            : null;
                        $volunteer->makeHidden(['avatar', 'pivot']); // يمكنك إزالة 'pivot' إذا كنت تريد عرض حالة المتطوع داخل الفرصة
                    });
                }

                return $opportunity;
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الفرص والإحصائيات بنجاح.',
                'data'    => [
                    'stats'         => $stats,
                    'opportunities' => $opportunities
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Fetch Opportunities Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء جلب البيانات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: عرض تفاصيل فرصة تطوعية واحدة (مع كافة التفاصيل والروابط الكاملة)
     */
/**
     * API: عرض تفاصيل فرصة تطوعية واحدة (مع كافة التفاصيل والروابط الكاملة لكل الملفات)
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            // جلب الفرصة مع العلاقات بالكامل وحساب عدد المقبولين
            $opportunity = VolunteerOpportunity::where('id', $id)
                ->where('foundation_id', $foundationId)
                ->with([
                    'foundation', // 👈 جلب كل بيانات المؤسسة
                    'volunteers'  // 👈 جلب كل بيانات المتطوعين مع الـ Pivot الخاص بهم
                ])
                ->withCount(['volunteers as accepted_volunteers_count' => function ($query) {
                    // حساب المقبولين فقط
                    $query->where('opportunity_volunteer.status', 'accepted');
                }])
                ->first();

            // التحقق من وجود الفرصة
            if (!$opportunity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرصة التطوعية غير موجودة أو لا تملك صلاحية عرضها.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 🎯 تهيئة الروابط الكاملة للصور والملفات (Full URLs) وإخفاء المسارات المحلية

            // 1. ملفات وصور المؤسسة بالكامل
            if ($opportunity->foundation) {
                $foundationFiles = [
                    'logo', 'cover_image', 'license_image', 'commercial_register',
                    'tax_card', 'accreditation_letter', 'headquarters_image'
                ];

                foreach ($foundationFiles as $field) {
                    if (!empty($opportunity->foundation->$field)) {
                        $urlField = $field . '_url'; // إنشاء اسم حقل جديد (مثال: logo_url)

                        $opportunity->foundation->$urlField = str_starts_with($opportunity->foundation->$field, 'http')
                            ? $opportunity->foundation->$field
                            : asset('storage/' . $opportunity->foundation->$field);

                        $opportunity->foundation->makeHidden([$field]); // إخفاء المسار المحلي القديم
                    }
                }
            }

            // 2. ملفات وصور المتطوعين بالكامل
            if ($opportunity->volunteers) {
                $opportunity->volunteers->each(function ($volunteer) {
                    $volunteerFiles = ['avatar', 'national_id_front', 'national_id_back'];

                    foreach ($volunteerFiles as $field) {
                        if (!empty($volunteer->$field)) {
                            $urlField = $field . '_url'; // إنشاء اسم حقل جديد (مثال: avatar_url)

                            $volunteer->$urlField = str_starts_with($volunteer->$field, 'http')
                                ? $volunteer->$field
                                : asset('storage/' . $volunteer->$field);

                            $volunteer->makeHidden([$field]); // إخفاء المسار المحلي القديم
                        }
                    }
                });
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الفرصة بنجاح.',
                'data'    => $opportunity
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Show Opportunity Error ID {$id}: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الفرصة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     * API: إنشاء فرصة تطوعية جديدة (مع إمكانية دعوة متطوعين)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'                 => 'required|string|max:255',
            'category'              => 'required|string|max:255',
            'description'           => 'required|string',
            'location'              => 'required|string|max:255',
            'date'                  => 'required|date|after_or_equal:today',
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i|after:start_time',
            'required_volunteers'   => 'required|integer|min:1',
            'requirements'          => 'required|array|min:1',
            'contact_person'        => 'required|string|max:255',
            'contact_phone'         => 'required|string|max:20',

            // 🎯 إضافة حقل اختيار المتطوعين (اختياري)
            'selected_volunteers'   => 'nullable|array',
            'selected_volunteers.*' => 'integer|exists:volunteers,id',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى مراجعة الأخطاء.',
                'errors'  => $validator->errors()
            ], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $data = $request->all();
            $data['foundation_id'] = $request->user()->id;

            // حساب إجمالي الساعات
            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);
            $data['total_hours'] = $start->diffInHours($end) > 0 ? $start->diffInHours($end) : 1;

            $opportunity = VolunteerOpportunity::create($data);

            // 🎯 ربط المتطوعين المختارين بالفرصة مع جعل حالتهم المبدئية 'pending' (قيد الانتظار / تم إرسال دعوة)
            if ($request->filled('selected_volunteers') && is_array($request->selected_volunteers)) {
                $opportunity->volunteers()->attach($request->selected_volunteers, ['status' => 'pending']);
            }

            // تحميل بيانات المتطوعين المختارين لإرجاعها في الرد
            $opportunity->load('volunteers:id,name,email,phone');

            return response()->json([
                'status'  => true,
                'message' => 'تم إنشاء الفرصة التطوعية بنجاح.',
                'data'    => $opportunity
            ], 201, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Create Opportunity Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ أثناء حفظ الفرصة.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: تحديث فرصة تطوعية (مع إمكانية إضافة متطوعين جدد للدعوة)
     */
/**
     * API: تحديث فرصة تطوعية
     */
    public function update(Request $request, $id): JsonResponse
    {
        $opportunity = VolunteerOpportunity::where('id', $id)
            ->where('foundation_id', $request->user()->id)
            ->first();

        if (!$opportunity) {
            return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $validator = Validator::make($request->all(), [
            'title'                 => 'required|string|max:255',
            'category'              => 'required|string|max:255',
            'description'           => 'required|string',
            'location'              => 'required|string|max:255',
            'date'                  => 'required|date',
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i|after:start_time',
            'required_volunteers'   => 'required|integer|min:1',
            'requirements'          => 'required|array|min:1',
            'contact_person'        => 'required|string|max:255',
            'contact_phone'         => 'required|string|max:20',
            'status'                => 'nullable|in:active,completed,cancelled',

            // 🎯 حقل المتطوعين في التحديث
            'selected_volunteers'   => 'nullable|array',
            'selected_volunteers.*' => 'integer|exists:volunteers,id',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $data = $request->all();

            // إعادة حساب الساعات إذا تم تغيير الوقت
            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);
            $data['total_hours'] = $start->diffInHours($end) > 0 ? $start->diffInHours($end) : 1;

            $opportunity->update($data);

            // 🎯 تحديث قائمة المتطوعين المدعوين بذكاء (مع دعم الحذف والإضافة)
            if ($request->has('selected_volunteers') && is_array($request->selected_volunteers)) {

                // 1. جلب حالات المتطوعين المرتبطين حالياً (للحفاظ على حالتهم من الضياع)
                // النتيجة ستكون مصفوفة مثل: [1 => 'accepted', 3 => 'pending']
                $existingStatuses = $opportunity->volunteers()->pluck('opportunity_volunteer.status', 'volunteers.id')->toArray();

                // 2. تجهيز البيانات الجديدة للـ Sync
                $syncData = [];
                foreach ($request->selected_volunteers as $vId) {
                    // إذا كان المتطوع موجوداً مسبقاً، نحتفظ بحالته. إذا كان جديداً نعطيه 'pending'
                    $currentStatus = $existingStatuses[$vId] ?? 'pending';
                    $syncData[$vId] = ['status' => $currentStatus];
                }

                // 3. استخدام دالة sync القوية
                // - ستضيف المتطوعين الجدد
                // - ستحافظ على القدامى بحالتهم (accepted, rejected... الخ)
                // - ستحذف (Detach) أي متطوع تم إزالة الـ ID الخاص به
                $opportunity->volunteers()->sync($syncData);
            }

            // تحميل المتطوعين المحدثين لإرجاعهم في الرد
            $opportunity->load('volunteers:id,name,email,phone');

            return response()->json([
                'status'  => true,
                'message' => 'تم تحديث الفرصة بنجاح.',
                'data'    => $opportunity
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Update Opportunity Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء التحديث.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * API: حذف فرصة تطوعية
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $opportunity = VolunteerOpportunity::where('id', $id)
                ->where('foundation_id', $request->user()->id)
                ->first();

            if (!$opportunity) {
                return response()->json(['status' => false, 'message' => 'الفرصة غير موجودة.'], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $opportunity->delete();

            return response()->json([
                'status'  => true,
                'message' => 'تم حذف الفرصة التطوعية بنجاح.'
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Delete Opportunity Error: " . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء الحذف.'], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }


}
