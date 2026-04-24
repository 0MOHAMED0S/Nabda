<?php

namespace Database\Seeders;

use App\Models\AboutGoal;
use Illuminate\Database\Seeder;

class AboutGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $goals = [
            [
                'title' => 'تنظيم العمل الخيري الرقمي',
                'description' => 'تجميع المبادرات والحالات والمؤسسات في منصة واحدة منظمة وسهلة الوصول.',
            ],
            [
                'title' => 'تسهيل وصول الدعم للمحتاجين',
                'description' => 'ربط المتبرعين بالحالات الإنسانية الحقيقية بسرعة وسهولة.',
            ],
            [
                'title' => 'تعزيز الشفافية والمصداقية',
                'description' => 'ضمان وضوح مسار التبرعات وتتبع الأثر الحقيقي لكل مساهمة.',
            ],
            [
                'title' => 'تمكين المؤسسات الخيرية',
                'description' => 'توفير أدوات تقنية لإدارة الحملات والحالات بكفاءة واحترافية.',
            ],
            [
                'title' => 'دعم المتطوعين وتنظيم جهودهم',
                'description' => 'تسهيل المشاركة المجتمعية وتنظيم العمل الميداني بشكل فعّال.',
            ],
            [
                'title' => 'نشر ثقافة العطاء والعمل الإنساني',
                'description' => 'تعزيز قيم التعاون والتكافل الاجتماعي في المجتمع.',
            ],
        ];

        foreach ($goals as $goal) {
            AboutGoal::create($goal);
        }
    }
}
