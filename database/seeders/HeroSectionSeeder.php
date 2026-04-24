<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HeroSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\HeroSection::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'معاً نصنع فرقاً... ونمنح الأمل لمن يحتاجه',
                'description' => 'نبضة خير هي منصة شاملة تجمع بين المتبرعين، المؤسسات، والمتطوعين في مكان واحد لدعم الحالات الإنسانية وتغيير حياة آلاف الأسر بلمسة خير.',
                'video' => 'hero/videos/hero.webp',
            ]
        );
    }
}
