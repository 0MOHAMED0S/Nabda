<?php

namespace App\Http\Controllers\Api\Article;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // 🎯 تم استيراد الكلاس بشكل نظيف
use Exception;

class ArticleController extends Controller
{
    /**
     * API: جلب قائمة المقالات والأخبار (مع إمكانية الفلترة بالنوع)
     */
    public function index(Request $request)
    {
        try {
            // 1. تحسين الأداء: تحديد الحقول المطلوبة فقط من قاعدة البيانات
            $query = Article::select('id', 'type', 'main_title', 'second_title', 'description', 'published_date', 'image');

            // 2. فلترة ذكية: إذا أرسل الـ Frontend نوع معين (?type=news أو ?type=article)
            if ($request->has('type') && in_array($request->type, ['article', 'news'])) {
                $query->where('type', $request->type);
            }

            // 3. الترتيب وجلب البيانات
            $articles = $query->orderBy('published_date', 'desc')->get();

            // 4. تصحيح معايير RESTful: إرجاع 200 مع مصفوفة فارغة بدلاً من 404
            if ($articles->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد مقالات أو أخبار حالياً.',
                    'data'    => []
                ], 200);
            }

            // 5. إعادة تشكيل البيانات لتهيئة الروابط والتاريخ والوصف المختصر
            $data = $articles->map(function ($article) {
                return [
                    'id'             => $article->id,
                    'type'           => $article->type,
                    'main_title'     => $article->main_title,
                    'second_title'   => $article->second_title,
                    'short_desc'     => Str::limit($article->description, 100),
                    // برمجة دفاعية: التأكد من وجود تاريخ لتجنب الـ Null Exception
                    'published_date' => $article->published_date ? $article->published_date->format('Y-m-d') : null,
                    'image_url'      => $article->image ? asset('storage/' . $article->image) : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب القائمة بنجاح.',
                'data'    => $data,
            ], 200);

        } catch (Exception $e) {
            Log::error('API Articles Index Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء جلب البيانات.',
                'data'    => []
            ], 500);
        }
    }

    /**
     * API: جلب تفاصيل مقال أو خبر معين بواسطة الـ ID
     */
    public function show($id)
    {
        try {
            // 1. الأداء: جلب الحقول المطلوبة للمقال المُراد فقط
            $article = Article::select('id', 'type', 'main_title', 'second_title', 'description', 'published_date', 'image')
                ->where('id', $id)
                ->first();

            // 2. التحقق من وجود المقال (هنا نستخدم 404 لأننا نبحث عن شيء محدد)
            if (!$article) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المحتوى المطلوب غير موجود أو تم حذفه.',
                    'data'    => null
                ], 404);
            }

            // 3. تجهيز بيانات المقال بالكامل
            $data = [
                'id'             => $article->id,
                'type'           => $article->type,
                'main_title'     => $article->main_title,
                'second_title'   => $article->second_title,
                'description'    => $article->description, // الوصف كاملاً
                'published_date' => $article->published_date ? $article->published_date->format('Y-m-d') : null,
                'image_url'      => $article->image ? asset('storage/' . $article->image) : null,
            ];

            return response()->json([
                'status'  => true,
                'message' => 'تم جلب تفاصيل المحتوى بنجاح.',
                'data'    => $data
            ], 200);

        } catch (Exception $e) {
            Log::error('API Articles Show Error (ID ' . $id . '): ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'حدث خطأ تقني في الخادم أثناء عرض التفاصيل.',
                'data'    => null
            ], 500);
        }
    }
}
