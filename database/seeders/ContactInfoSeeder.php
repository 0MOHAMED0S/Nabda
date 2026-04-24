<?php

namespace Database\Seeders;

use App\Models\ContactInfo;
use Illuminate\Database\Seeder;

class ContactInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // استخدام updateOrCreate لضمان وجود سجل واحد فقط دائماً بالمعرف رقم 1
        ContactInfo::updateOrCreate(
            ['id' => 1], // البحث عن السجل رقم 1
            [
                'phone' => '+965 12345678',
                'email' => 'info@nabda-khair.com',
                'working_hours' => 'الأحد - الخميس: 8:00 ص - 4:00 م',
            ]
        );
    }
}
