<?php

namespace Database\Seeders;

use App\Models\Ticker;
use Illuminate\Database\Seeder;

class TickerSeeder extends Seeder
{
    public function run(): void
    {
        $phrases = [
            'معاً نصنع فرقاً... ونمنح الأمل لمن يحتاجه',
            'تبرعك اليوم ينقذ حياة غداً',
            'كن جزءاً من التغيير وساهم في رسم البسمة',
            'نبضة خير.. منصتكم الموثوقة للعمل الإنساني',
            'أكثر من ١٠٠ مؤسسة خيرية بانتظار دعمكم',
        ];

        foreach ($phrases as $index => $phrase) {
            Ticker::create([
                'content' => $phrase,
                'is_active' => true,
                'order' => $index,
            ]);
        }
    }
}
