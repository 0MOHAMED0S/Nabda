<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Exception;

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
}
