<?php

namespace App\Http\Controllers\Api\Hero;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use Exception;
use Illuminate\Support\Facades\Log;

class HeroController extends Controller
{
    public function index()
    {
        try {
            $hero = HeroSection::select('id', 'title', 'description', 'video')->first();
            if (!$hero) {
                return response()->json([
                    'status'  => false,
                    'message' => 'بيانات الواجهة الرئيسية غير متوفرة حالياً.',
                    'data'    => null
                ], 404);
            }
            return response()->json([
                'status'  => true,
                'message' => 'تم جلب بيانات الواجهة الرئيسية بنجاح.',
                'data'    => [
                    'id'          => $hero->id,
                    'title'       => $hero->title,
                    'description' => $hero->description,
                    'video_url'   => $hero->video ? asset('storage/' . $hero->video) : null,
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('API Get Hero Section Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء معالجة الطلب.',
                'data'    => null
            ], 500);
        }
    }
}
