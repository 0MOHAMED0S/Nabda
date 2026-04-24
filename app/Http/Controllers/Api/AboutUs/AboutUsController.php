<?php

namespace App\Http\Controllers\Api\AboutUs;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use App\Models\AboutVision;
use App\Models\AboutGoal;
use App\Models\AboutGoal2;
use App\Models\AboutHistory;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Log;
use Exception;

class AboutUsController extends Controller
{
    /**
     * 1. جلب البيانات الأساسية (من نحن)
     */
    public function getAboutInfo()
    {
        try {
            // تحسين الأداء: جلب الحقول المطلوبة فقط
            $aboutUs = AboutUs::select('id', 'title', 'description1', 'description2', 'video_url')->first();

            if (!$aboutUs) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد بيانات متاحة حالياً.',
                    'data'    => null
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب المعلومات الأساسية بنجاح.',
                'data'    => $aboutUs
            ], 200);

        } catch (Exception $e) {
            Log::error('API getAboutInfo Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب المعلومات الأساسية.', 'data' => null], 500);
        }
    }

    /**
     * 2. جلب الرؤية والمهمة
     */
    public function getVisions()
    {
        try {
            // استخدام select لتخفيف الضغط على الـ RAM، واستخدام map فقط لتنسيق رابط الصورة
            $visions = AboutVision::select('id', 'title', 'description', 'image')->get();

            $data = $visions->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'title'       => $item->title,
                    'description' => $item->description,
                    'image_url'   => $item->image ? asset('storage/' . $item->image) : null,
                ];
            });

            return response()->json(['status' => true, 'message' => 'تم جلب الرؤية والمهمة.', 'data' => $data], 200);

        } catch (Exception $e) {
            Log::error('API getVisions Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب الرؤية والمهمة.', 'data' => []], 500);
        }
    }

    /**
     * 3. جلب الأهداف (القسم الأول)
     */
    public function getGoalsPart1()
    {
        try {
            // 🎯 احترافية: الاستغناء التام عن map() لأننا نختار الحقول الدقيقة مباشرة من قاعدة البيانات
            $data = AboutGoal::select('id', 'title', 'description')->get();

            return response()->json(['status' => true, 'message' => 'تم جلب الأهداف.', 'data' => $data], 200);

        } catch (Exception $e) {
            Log::error('API getGoalsPart1 Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب الأهداف.', 'data' => []], 500);
        }
    }

    /**
     * 4. جلب الأهداف (القسم الثاني)
     */
    public function getGoalsPart2()
    {
        try {
            // 🎯 احترافية: جلب مباشر وسريع للبيانات المطلوبة فقط
            $data = AboutGoal2::select('id', 'title', 'description')->get();

            return response()->json(['status' => true, 'message' => 'تم جلب الأهداف الإضافية.', 'data' => $data], 200);

        } catch (Exception $e) {
            Log::error('API getGoalsPart2 Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب الأهداف.', 'data' => []], 500);
        }
    }

    /**
     * 5. جلب مسيرة المنصة (التاريخ)
     */
    public function getHistories()
    {
        try {
            // 🎯 الترتيب زمنياً الأقدم فالأحدث (لأنها قصة مسيرة) بدون استخدام الـ map
            $data = AboutHistory::select('id', 'title', 'description')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json(['status' => true, 'message' => 'تم جلب مسيرة المنصة.', 'data' => $data], 200);

        } catch (Exception $e) {
            Log::error('API getHistories Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب مسيرة المنصة.', 'data' => []], 500);
        }
    }

    /**
     * 6. جلب فريق العمل
     */
    public function getTeam()
    {
        try {
            // جلب الحقول المحددة فقط وتنسيق الصورة
            $team = TeamMember::select('id', 'name', 'job_title', 'order', 'image')
                ->orderBy('order', 'asc')
                ->get();

            $data = $team->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'job_title'   => $item->job_title,
                    'order'       => $item->order,
                    'image_url'   => $item->image ? asset('storage/' . $item->image) : null,
                ];
            });

            return response()->json(['status' => true, 'message' => 'تم جلب أعضاء الفريق.', 'data' => $data], 200);

        } catch (Exception $e) {
            Log::error('API getTeam Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'حدث خطأ تقني أثناء جلب فريق العمل.', 'data' => []], 500);
        }
    }
}
