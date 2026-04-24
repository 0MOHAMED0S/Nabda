<?php

namespace Database\Seeders;

use App\Models\AboutVision;
use Illuminate\Database\Seeder;

class AboutVisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $visions = [
            [
                'title' => 'مهمتنا',
                'description' => 'نسعى لتسهيل التبرع وطلب المساعدة باستخدام التكنولوجيا مع ضمان الشفافية والأمان وسرعة الوصول للمحتاجين.',
                'image' => 'visions/vision.webp', // يمكنك تغيير الصورة لاحقاً من لوحة التحكم
            ],
            [
                'title' => 'رؤيتنا',
                'description' => 'أن نكون المنصة الخيرية الرقمية الرائدة في العالم العربي، ونموذجاً يحتذى به في العمل الخيري الذكي والمستدام.',
                'image' => 'visions/vision.webp',
            ],
            [
                'title' => 'قيمنا', // بناءً على الأيقونة الزرقاء في أسفل الصورة
                'description' => 'نلتزم بالشفافية والمصداقية في كل خطوة، ونعمل بروح الفريق الواحد لتعزيز التكافل وبناء مجتمع مترابط.',
                'image' => 'visions/vision.webp',
            ],
        ];

        foreach ($visions as $vision) {
            AboutVision::create($vision);
        }
    }
}
