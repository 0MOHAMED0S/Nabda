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
    public function index(Request $request)
    {
        try {
            $query = Article::select('id', 'type', 'main_title', 'second_title', 'description', 'published_date', 'image');
            if ($request->has('type') && in_array($request->type, ['article', 'news'])) {
                $query->where('type', $request->type);
            }
            $articles = $query->orderBy('published_date', 'desc')->get();

            if ($articles->isEmpty()) {
                return response()->json([
                    'status'  => true,
                    'message' => 'لا توجد مقالات أو أخبار حالياً.',
                    'data'    => []
                ], 200);
            }

            $data = $articles->map(function ($article) {
                return [
                    'id'             => $article->id,
                    'type'           => $article->type,
                    'main_title'     => $article->main_title,
                    'second_title'   => $article->second_title,
                    'short_desc'     => Str::limit($article->description, 100),
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

    public function show($id)
    {
        try {
            $article = Article::select('id', 'type', 'main_title', 'second_title', 'description', 'published_date', 'image')
                ->where('id', $id)
                ->first();
            if (!$article) {
                return response()->json([
                    'status'  => false,
                    'message' => 'المحتوى المطلوب غير موجود أو تم حذفه.',
                    'data'    => null
                ], 404);
            }
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
