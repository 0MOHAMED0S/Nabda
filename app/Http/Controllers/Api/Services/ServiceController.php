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
    /**
     * جلب جميع الأقسام
     */
    public function index()
    {
        try {
            // 1. تحسين الأداء: جلب الحقول المطلوبة فقط وإلغاء الحاجة لدالة map()
            $categories = Category::select('id', 'name')->get();

            // 2. تطبيق معايير RESTful: الاستجابة بـ 200 ومصفوفة فارغة عند عدم وجود بيانات
            if ($categories->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد أقسام متاحة حالياً.',
                    'data'    => []
                ], 200);
            }

            // 3. حالة النجاح
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الأقسام بنجاح.',
                'data'    => $categories
            ], 200);

        } catch (Exception $e) {
            // 4. تسجيل الخطأ الفني في السيرفر أمنياً
            Log::error('API Categories Index Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب الأقسام.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * جلب جميع الخدمات مباشرة
     */
    public function allServices()
    {
        try {
            // 1. تحسين الأداء العالي:
            // - select: نجلب حقول الخدمة المطلوبة فقط.
            // - with('category:id,name'): نجلب حقلي الـ id والـ name فقط من جدول الأقسام المرتبط لتخفيف الحمل.
            $services = Service::with('category:id,name')
                ->select('id', 'title', 'description', 'image', 'category_id', 'created_at')
                ->latest()
                ->get();

            // 2. التحقق من القائمة الفارغة
            if ($services->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد خدمات متاحة حالياً.',
                    'data'    => []
                ], 200);
            }

            // 3. إعادة التشكيل (Mapping) لتنسيق رابط الصورة واستخراج اسم القسم
            $data = $services->map(function ($service) {
                return [
                    'id'            => $service->id,
                    'title'         => $service->title,
                    'description'   => $service->description,
                    'image_url'     => $service->image ? asset('storage/' . $service->image) : null,
                    'category_name' => $service->category ? $service->category->name : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الخدمات بنجاح.',
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            Log::error('API All Services Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب الخدمات.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * API: جلب جميع الحالات التابعة لخدمة معينة (بناءً على service_id)
     */
/**
     * API: جلب جميع الحالات التابعة لخدمة معينة (بناءً على service_id)
     */
    public function getServiceCases(Request $request, $serviceId): JsonResponse
    {
        try {
            // 1. التأكد من وجود الخدمة المطلوبة وجلب تفاصيلها مع تصنيفها لعنوان الصفحة
            $service = \App\Models\Service::with('category')->find($serviceId);

            if (!$service) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الخدمة غير موجودة.',
                    'data'    => []
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. جلب الحالات المرتبطة بهذه الخدمة (مع التأكد أن المؤسسة التابعة لها نشطة ومعتمدة)
            $cases = FoundationCase::with(['foundation:id,name,logo', 'service.category'])
                ->where('service_id', $serviceId)
                ->where('status', 'active')
                ->whereHas('foundation', function($q) {
                    // شرط إضافي لضمان عدم جلب حالات لمؤسسات تم إيقافها
                    $q->where('status', 'active')->where('approval_status', 'approved');
                })
                ->withSum(['donations as collected_amount' => function ($query) {
                    $query->where('status', 'completed');
                }], 'amount')
                ->orderBy('priority', 'asc') // الحالات العاجلة أولاً
                ->orderBy('created_at', 'desc')
                ->get(); // جلب دفعة واحدة بدون Pagination

            // 3. تهيئة البيانات وتنسيقها للواجهة (Frontend)
            $cases->transform(function ($case) {
                $collected  = $case->collected_amount ?? 0;
                $target     = $case->target_amount;

                // حساب النسبة المئوية
                $percentage = ($case->goal_type === 'financial' && $target > 0)
                    ? min(100, round(($collected / $target) * 100))
                    : 0;

                // استخراج رابط الصورة الكامل بأمان
                $firstImage = (is_array($case->images) && count($case->images) > 0) ? $case->images[0] : null;
                $imageUrl = !empty($firstImage) && !str_starts_with($firstImage, 'http')
                    ? asset('storage/' . $firstImage)
                    : $firstImage;

                $logoUrl = ($case->foundation && $case->foundation->logo) && !str_starts_with($case->foundation->logo, 'http')
                    ? asset('storage/' . $case->foundation->logo)
                    : ($case->foundation->logo ?? null);

                // تحديد ما إذا كانت الحالة عاجلة
                $isUrgent = in_array(strtolower($case->priority), ['urgent', 'عاجل', 'high', 'عالية']);

                // استخراج بيانات الخدمة والتصنيف بأمان
                $serviceCategory = ($case->service && $case->service->category) ? $case->service->category->name : 'غير مصنف';
                $serviceTitle    = $case->service ? $case->service->title : 'خدمة عامة';

                return [
                    'id'                    => $case->id,
                    'title'                 => $case->title,
                    'short_description'     => \Illuminate\Support\Str::limit($case->main_description, 70, '...'),

                    // 🎯 الحقول المطلوبة للخدمة والتصنيف داخل كل حالة
                    'service_id'            => $case->service_id,
                    'service_name'          => $serviceTitle,
                    'category_name'         => $serviceCategory,
                    'category'              => $serviceCategory, // تركتها أيضاً لعدم كسر أي كود قديم في الفرونت إند

                    // شارات وتنسيقات جاهزة للطباعة
                    'is_urgent'             => $isUrgent,
                    'urgency_badge'         => $isUrgent ? 'عاجلة' : 'غير عاجلة',
                    'currency'              => 'جنيه',

                    // الأرقام لشرائط التقدم
                    'target_amount'         => $target,
                    'collected_amount'      => $collected,
                    'completion_percentage' => $percentage,

                    // الصور والمؤسسة
                    'image_url'             => $imageUrl,
                    'foundation_name'       => $case->foundation->name ?? '',
                    'foundation_logo_url'   => $logoUrl,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب الحالات التابعة للخدمة بنجاح.',

                // 🎯 إضافة معلومات الخدمة العامة لاستخدامها في عنوان الصفحة (Page Header)
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

public function show($id): JsonResponse
    {
        try {
            // 1. جلب الخدمة مع تصنيفها (Category)
            $service = Service::with('category')->find($id);

            if (!$service) {
                return response()->json([
                    'status'  => false,
                    'message' => 'الخدمة المطلوبة غير موجودة.',
                    'data'    => null
                ], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // 2. 🎯 إحصائية إضافية: جلب عدد الحالات "النشطة" المرتبطة بهذه الخدمة
            $activeCasesCount = FoundationCase::where('service_id', $id)
                                              ->where('status', 'active')
                                              ->count();

            // 3. استخراج رابط الصورة بأمان (تم تصحيح startsWith هنا ✅)
            $imageUrl = !empty($service->image) && !\Illuminate\Support\Str::startsWith($service->image, ['http://', 'https://'])
                ? asset('storage/' . $service->image)
                : ($service->image ?? null);

            // 4. تشكيل الاستجابة النهائية
            $data = [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image_url'          => $imageUrl,

                // بيانات التصنيف
                'category_id'        => $service->category_id,
                'category_name'      => $service->category->name ?? 'غير مصنف',

                // إحصائيات وتواريخ
                'active_cases_count' => $activeCasesCount,
                'created_at'         => $service->created_at ? $service->created_at->format('Y-m-d') : null,
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل الخدمة بنجاح.',
                'data'    => $data
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            Log::error("API Get Service Details Error (ID: {$id}): " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني أثناء جلب بيانات الخدمة.',
                'data'    => null
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
}
