<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // مسح الجدول لتجنب التكرار عند تشغيل الـ Seeder مرة أخرى
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('articles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $articles = [
            [
                'type'           => 'article',
                'image'          => 'articles/sample1.jpg', // تأكد من وجود المجلد في storage/app/public/articles
                'main_title'     => 'نبضة خير تطلق حملة دعم جديدة لإنقاذ مرضى القلب',
                'second_title'   => 'مبادرة إنسانية تهدف لتوفير العلاج العاجل لمرضى القلب',
                'description'    => 'أعلنت منصة نبضة خير عن إطلاق حملة إنسانية جديدة لدعم مرضى القلب غير القادرين على تحمل تكاليف العمليات الجراحية. تهدف الحملة إلى جمع التبرعات اللازمة لتغطية الفحوصات الطبية والأدوية الأساسية بالتعاون مع مجموعة من المستشفيات المتخصصة.',
                'published_date' => '2023-11-24',
            ],
            [
                'type'           => 'news',
                'image'          => 'articles/sample2.jpg',
                'main_title'     => 'توسيع نطاق المساعدات في المحافظات الحدودية',
                'second_title'   => 'خطة استراتيجية للوصول إلى الأسر الأكثر احتياجاً',
                'description'    => 'بدأت المنصة في تنفيذ خطة توسعية شاملة لتشمل القرى والمناطق النائية في المحافظات الحدودية، وذلك لضمان وصول الدعم الغذائي والطبي لمستحقيه بأسرع وقت ممكن.',
                'published_date' => '2024-01-10',
            ],
            [
                'type'           => 'article',
                'image'          => 'articles/sample3.jpg',
                'main_title'     => 'كيف تساهم التكنولوجيا في تسهيل العمل العمل الخيري؟',
                'second_title'   => 'رؤية نبضة خير للتحول الرقمي في التبرعات',
                'description'    => 'استعرضت إدارة المنصة دور الحلول الذكية والآمنة في تعزيز الشفافية وبناء جسور الثقة بين المتبرع والمستفيد، موكدة أن التحول الرقمي هو الركيزة الأساسية لمستقبل العمل الإنساني.',
                'published_date' => '2024-02-15',
            ]
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
