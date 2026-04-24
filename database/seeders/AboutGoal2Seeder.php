<?php

namespace Database\Seeders;

use App\Models\AboutGoal2; // تأكد من أن اسم الموديل مطابق (غالباً AboutGoal2)
use Illuminate\Database\Seeder;

class AboutGoal2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $goals = [
            [
                'title' => 'تسهيل الوصول للحالات الإنسانية',
                'description' => 'تجميع الحالات الموثوقة في منصة واحدة لسهولة الوصول والدعم.',
            ],
            [
                'title' => 'تعزيز الشفافية في العمل الخيري',
                'description' => 'عرض بيانات وتقارير واضحة لكل حالة وتبرع.',
            ],
            [
                'title' => 'تمكين المؤسسات الخيرية رقمياً',
                'description' => 'توفير أدوات ذكية لإدارة الحالات والحملات بكفاءة.',
            ],
            [
                'title' => 'دعم ثقافة التطوع والمشاركة المجتمعية',
                'description' => 'خلق فرص تطوع حقيقية ومؤثرة داخل المجتمع.',
            ],
        ];

        foreach ($goals as $goal) {
            AboutGoal2::create($goal);
        }
    }
}
