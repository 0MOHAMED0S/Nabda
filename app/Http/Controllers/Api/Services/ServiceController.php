<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FoundationCase;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::select('id', 'name')->get();
            if ($categories->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد أقسام متاحة حالياً.',
                    'data'    => []
                ], 200);
            }
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الأقسام بنجاح.',
                'data'    => $categories
            ], 200);
        } catch (Exception $e) {
            Log::error('API Categories Index Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب الأقسام.',
                'data'    => []
            ], 500);
        }
    }

    public function allServices()
    {
        try {
            $services = Service::select('id', 'title', 'description', 'image', 'category_id', 'created_at')
                ->with('category:id,name')
                ->withCount('foundationCases') // 🎯 إضافة عداد الحالات
                ->latest()
                ->get();

            if ($services->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد خدمات متاحة حالياً.',
                    'data'    => []
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

            $data = $services->map(function ($service) {
                return [
                    'id'            => $service->id,
                    'title'         => $service->title,
                    'description'   => $service->description,
                    'image_url'     => $service->image ? asset('storage/' . $service->image) : null,
                    'category_name' => $service->category ? $service->category->name : null,
                    'cases_count'   => $service->foundation_cases_count, // 🎯 إرجاع عدد الحالات في الاستجابة
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الخدمات بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('API All Services Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب الخدمات.',
                'data'    => []
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function getServiceCases(Request $request, $serviceId): JsonResponse
    {
        try {
            $service = \App\Models\Service::with('category')->find($serviceId);

            if (!$service) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الخدمة غير موجودة.',
                    'data'    => []
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $cases = FoundationCase::with(['foundation:id,name,logo', 'service.category'])
                ->where('service_id', $serviceId)
                ->where('status', 'active')
                ->whereHas('foundation', function ($q) {
                    $q->where('status', 'active')->where('approval_status', 'approved');
                })
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('priority', 'asc') // الحالات العاجلة أولاً
                ->orderBy('created_at', 'desc')
                ->get(); // جلب دفعة واحدة بدون Pagination

            $cases->transform(function ($case) {
                $collected  = $case->collected_amount ?? 0;
                $target     = $case->target_amount;

                $percentage = ($case->goal_type === 'financial' && $target > 0)
                    ? min(100, round(($collected / $target) * 100))
                    : 0;

                $firstImage = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;
                $imageUrl = !empty($firstImage) && !str_starts_with($firstImage, 'http')
                    ? asset('storage/' . $firstImage)
                    : $firstImage;

                $logoUrl = ($case->foundation && $case->foundation->logo) && !str_starts_with($case->foundation->logo, 'http')
                    ? asset('storage/' . $case->foundation->logo)
                    : ($case->foundation->logo ?? null);

                $isUrgent = in_array(strtolower($case->priority), ['urgent', 'عاجل', 'high', 'عالية']);

                $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'غير مصنف';
                $serviceTitle    = $case->service ? $case->service->title : 'خدمة عامة';

                return [
                    'id'                    => $case->id,
                    'title'                 => $case->title,
                    'short_description'     => \Illuminate\Support\Str::limit($case->main_description, 70, '...'),

                    'service_id'            => $case->service_id,
                    'service_name'          => $serviceTitle,
                    'category_name'         => $serviceCategory,
                    'category'              => $serviceCategory, // تركتها أيضاً لعدم كسر أي كود قديم في الفرونت إند

                    'is_urgent'             => $isUrgent,
                    'urgency_badge'         => $isUrgent ? 'عاجلة' : 'غير عاجلة',
                    'currency'              => 'جنيه',

                    'target_amount'         => $target,
                    'collected_amount'      => $collected,
                    'completion_percentage' => $percentage,

                    'image_url'             => $imageUrl,
                    'foundation_name'       => $case->foundation->name ?? '',
                    'foundation_logo_url'   => $logoUrl,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الحالات التابعة للخدمة بنجاح.',

                'service_info' => [
                    'id'            => $service->id,
                    'name'          => $service->title,
                    'category_name' => $service->category->name ?? 'غير مصنف',
                ],

                'data'    => $cases
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Service Cases Error: " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الحالات.'
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        try {
            $service = \App\Models\Service::with('category')
                ->withCount(['foundationCases as active_cases_count' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->find($id);

            if (!$service) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الخدمة المطلوبة غير موجودة.',
                    'data'    => null
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            $imageUrl = !empty($service->image) && !\Illuminate\Support\Str::startsWith($service->image, ['http://', 'https://'])
                ? asset('storage/' . $service->image)
                : ($service->image ?? null);

            $data = [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image_url'          => $imageUrl,

                'category_id'        => $service->category_id,
                'category_name'      => $service->category->name ?? 'غير مصنف',

                'active_cases_count' => $service->active_cases_count ?? 0, // 🎯 تم جلبها مباشرة من الاستعلام
                'created_at'         => $service->created_at ? $service->created_at->format('Y-m-d') : null,
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الخدمة بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("API Get Service Details Error (ID: {$id}): " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الخدمة.',
                'data'    => null
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
