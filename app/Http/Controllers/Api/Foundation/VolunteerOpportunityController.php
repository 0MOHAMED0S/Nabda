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
            'selected_volunteers.array'    => 'صيغة اختيار المتطوعين غير صحيحة.',
            'selected_volunteers.*.exists' => 'أحد المتطوعين المختارين غير موجود في النظام.',
        ];
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;
            $baseQuery = VolunteerOpportunity::where('foundation_id', $foundationId);

            $stats = [
                'total'     => (clone $baseQuery)->count(),
                'active'    => (clone $baseQuery)->where('status', 'active')->count(),
                'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
                'archived'  => (clone $baseQuery)->where('status', 'cancelled')->count(),
            ];

            $opportunities = VolunteerOpportunity::where('foundation_id', $foundationId)
                ->with([
                    'foundation:id,name,logo',
                    'volunteers:id,name,email,phone,avatar,governorates'
                ])
                ->withCount(['volunteers as accepted_volunteers_count' => function ($query) {
                    $query->where('opportunity_volunteer.status', 'accepted');
                }])
                ->orderBy('date', 'desc')
                ->paginate(10);

            $opportunities->getCollection()->transform(function ($opportunity) {
                if ($opportunity->foundation) {
                    $opportunity->foundation->logo_url = $opportunity->foundation->logo
                        ? asset('storage/' . $opportunity->foundation->logo)
                        : null;
                    $opportunity->foundation->makeHidden(['logo']);
                }

                if ($opportunity->volunteers) {
                    $opportunity->volunteers->each(function ($volunteer) {
                        $volunteer->avatar_url = $volunteer->avatar
                            ? asset('storage/' . $volunteer->avatar)
                            : null;
                        $volunteer->makeHidden(['avatar', 'pivot']);
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

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $foundationId = $request->user()->id;

            $opportunity = VolunteerOpportunity::where('id', $id)
                ->where('foundation_id', $foundationId)
                ->with([
                    'foundation',
                    'volunteers'
                ])
                ->withCount(['volunteers as accepted_volunteers_count' => function ($query) {
                    $query->where('opportunity_volunteer.status', 'accepted');
                }])
                ->first();

            if (!$opportunity) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الفرصة التطوعية غير موجودة أو لا تملك صلاحية عرضها.'
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            if ($opportunity->foundation) {
                $foundationFiles = [
                    'logo',
                    'cover_image',
                    'license_image',
                    'commercial_register',
                    'tax_card',
                    'accreditation_letter',
                    'headquarters_image'
                ];

                foreach ($foundationFiles as $field) {
                    if (!empty($opportunity->foundation->$field)) {
                        $urlField = $field . '_url';
                        $opportunity->foundation->$urlField = str_starts_with($opportunity->foundation->$field, 'http')
                            ? $opportunity->foundation->$field
                            : asset('storage/' . $opportunity->foundation->$field);
                        $opportunity->foundation->makeHidden([$field]);
                    }
                }
            }

            if ($opportunity->volunteers) {
                $opportunity->volunteers->each(function ($volunteer) {
                    $volunteerFiles = ['avatar', 'national_id_front', 'national_id_back'];

                    foreach ($volunteerFiles as $field) {
                        if (!empty($volunteer->$field)) {
                            $urlField = $field . '_url';
                            $volunteer->$urlField = str_starts_with($volunteer->$field, 'http')
                                ? $volunteer->$field
                                : asset('storage/' . $volunteer->$field);
                            $volunteer->makeHidden([$field]);
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

            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);
            $data['total_hours'] = $start->diffInHours($end) > 0 ? $start->diffInHours($end) : 1;

            $opportunity = VolunteerOpportunity::create($data);
            if ($request->filled('selected_volunteers') && is_array($request->selected_volunteers)) {
                $opportunity->volunteers()->attach($request->selected_volunteers, ['status' => 'invited']);
            }

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
            'selected_volunteers'   => 'nullable|array',
            'selected_volunteers.*' => 'integer|exists:volunteers,id',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $data = $request->all();

            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);
            $data['total_hours'] = $start->diffInHours($end) > 0 ? $start->diffInHours($end) : 1;

            $opportunity->update($data);

            if ($request->has('selected_volunteers') && is_array($request->selected_volunteers)) {

                $existingStatuses = $opportunity->volunteers()->pluck('opportunity_volunteer.status', 'volunteers.id')->toArray();

                $syncData = [];
                foreach ($request->selected_volunteers as $vId) {
                    $currentStatus = $existingStatuses[$vId] ?? 'invited';
                    $syncData[$vId] = ['status' => $currentStatus];
                }

                $opportunity->volunteers()->sync($syncData);
            }

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
