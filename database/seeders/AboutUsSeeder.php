<?php

namespace Database\Seeders;

use App\Models\AboutUs;
use Illuminate\Database\Seeder;

class AboutUsSeeder extends Seeder
{
    public function run(): void
    {
        AboutUs::updateOrCreate(['id' => 1], [
            'title' => 'من نحن ؟',
            'description1' => 'نبضة خير هي منصة رقمية تجمع المتبرعين، المؤسسات الخيرية، والمتطوعين في مكان واحد، بهدف تسهيل الوصول إلى الحالات الإنسانية ودعم المحتاجين بأعلى مستوى من الشفافية والموثوقية.',
            'description2' => 'نؤمن أن الخير يبدأ بنبضة، وأن كل مساهمة مهما كانت صغيرة قادرة على إحداث فرق حقيقي في حياة الآخرين.',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' // رابط يوتيوب افتراضي
        ]);
    }
}
