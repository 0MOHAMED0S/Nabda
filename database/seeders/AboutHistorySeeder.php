<?php

namespace Database\Seeders;

use App\Models\AboutHistory;
use Illuminate\Database\Seeder;

class AboutHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $histories = [
            [
                'title' => 'أكتوبر ٢٠٢٥ - بداية الفكرة',
                'description' => 'ولدت فكرة نبضة خير من حاجة حقيقية لمنصة تجمع العمل الخيري في مكان واحد، وتوفر الشفافية وسهولة الوصول للحالات الإنسانية الموثوقة.',
            ],
            [
                'title' => 'ديسمبر ٢٠٢٥ - التخطيط والتصميم',
                'description' => 'تم بناء الرؤية، تحديد الأهداف، وتصميم الهيكل الكامل للمنصة وتجربة المستخدم.',
            ],
        ];

        foreach ($histories as $history) {
            AboutHistory::create($history);
        }
    }
}
