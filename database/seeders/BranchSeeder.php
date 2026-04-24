<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // مسح البيانات لتجنب التكرار
        DB::table('branches')->truncate();

        $branches = [
            [
                'name'    => 'فرع القاهرة',
                'address' => 'مدينة نصر - الحي السابع',
                'phone'   => '+20 100 111 2222',
                'lat'     => 30.059488,  // إحداثيات مدينة نصر تقريبية
                'lng'     => 31.348315,
            ],
            [
                'name'    => 'فرع الفيوم',
                'address' => 'الفيوم - شارع البحر',
                'phone'   => '+20 133 444 5555',
                'lat'     => 29.309948,  // إحداثيات الفيوم تقريبية
                'lng'     => 30.841804,
            ],
            [
                'name'    => 'فرع بني سويف',
                'address' => 'بني سويف - شارع البحر',
                'phone'   => '+20 133 444 5555',
                'lat'     => 28.109900,  // إحداثيات بني سويف تقريبية
                'lng'     => 30.750300,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}
