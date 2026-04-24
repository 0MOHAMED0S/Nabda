<?php

namespace Database\Seeders;

use App\Models\ZakatCondition;
use Illuminate\Database\Seeder;

class ZakatConditionSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            [
                'title' => 'بلوغ النصاب',
                'description' => 'أن يبلغ المال الحد الأدنى المقرر شرعاً (ما يعادل قيمة 85 جراماً من الذهب تقريباً).',
                'icon' => 'fa-solid fa-scale-balanced',
                'order' => 1
            ],
            [
                'title' => 'مرور عام هجري',
                'description' => 'أن يمر على المال عام هجري كامل (354 يوماً) منذ أن بلغ النصاب.',
                'icon' => 'fa-solid fa-calendar-day',
                'order' => 2
            ],
            [
                'title' => 'الملكية الكاملة',
                'description' => 'أن يكون المال مملوكاً لصاحبه وقابلاً للتصرف.',
                'icon' => 'fa-solid fa-user-check',
                'order' => 3
            ],
        ];

        foreach ($conditions as $condition) {
            ZakatCondition::create($condition);
        }
    }
}
