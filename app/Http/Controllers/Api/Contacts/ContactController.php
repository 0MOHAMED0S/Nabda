<?php

namespace App\Http\Controllers\Api\Contacts;

use App\Http\Controllers\Controller;
use App\Models\ContactInfo;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactController extends Controller
{
    /**
     * 1. جلب معلومات التواصل الأساسية
     * (Public Endpoint)
     */
    public function getContactInfo()
    {
        try {
            // تحسين الأداء: جلب الحقول المطلوبة فقط لأول سجل
            $contactInfo = ContactInfo::select('id', 'phone', 'email', 'working_hours')->first();

            // إذا لم يتم تهيئة البيانات بعد من لوحة التحكم (نستخدم 200 مع Null لأنها ليست قائمة بل كيان واحد غير موجود بعد)
            if (!$contactInfo) {
                return response()->json([
                    'status'  => true,
                    'message' => 'معلومات التواصل غير متوفرة حالياً.',
                    'data'    => null
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب معلومات التواصل بنجاح.',
                'data'    => $contactInfo // نرسل الـ Object مباشرة لأننا استخدمنا select
            ], 200);

        } catch (Exception $e) {
            Log::error('API Contact Info Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب معلومات التواصل.',
                'data'    => null
            ], 500);
        }
    }

    /**
     * 2. جلب قائمة الفروع ومواقعها على الخريطة
     * (Public Endpoint)
     */
    public function getBranches()
    {
        try {
            // تحسين الأداء: جلب الحقول المطلوبة فقط بدون دوال إضافية (Map)
            // ونضيف Casting سريع داخل الـ Select (اختياري) أو نعتمد على الكاستينج في الموديل
            $branches = Branch::select('id', 'name', 'address', 'phone', 'lat', 'lng')
                ->orderBy('id', 'asc') // ترتيب ثابت
                ->get();

            // تصحيح RESTful: القائمة الفارغة ترجع 200
            if ($branches->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد فروع مسجلة حالياً.',
                    'data'    => []
                ], 200);
            }

            // لضمان أن الإحداثيات تعود كـ Float (إذا لم يكن هناك Casting في موديل Branch)
            $data = $branches->map(function ($branch) {
                return [
                    'id'      => $branch->id,
                    'name'    => $branch->name,
                    'address' => $branch->address,
                    'phone'   => $branch->phone,
                    'lat'     => (float) $branch->lat,
                    'lng'     => (float) $branch->lng,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الفروع بنجاح.',
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            Log::error('API Branches Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب بيانات الفروع.',
                'data'    => []
            ], 500);
        }
    }
}
