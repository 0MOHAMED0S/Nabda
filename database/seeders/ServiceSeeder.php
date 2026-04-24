<?php

namespace Database\Seeders;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::create([
            'category_id' => 1, // صحة
            'title' => 'حملة دعم العمليات الجراحية',
            'description' => 'إحدى الحملات الأكثر شعبية للمساعدة للحالات الصحية الأمراض الجراحية',
            'image' => 'services/service.webp' // يفضل رفع صورة يدوياً بعد التشغيل
        ]);
        Service::create([
            'category_id' => 2, // تعليم
            'title' => 'حملة دعم الحقيبة المدرسية',
            'description' => 'دعم الطلاب المحتاجين بالحقائب واللوازم المدرسية الأساسية',
            'image' => 'services/service.webp'
        ]);
    }
}
