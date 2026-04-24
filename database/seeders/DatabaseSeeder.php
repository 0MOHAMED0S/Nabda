<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            HeroSectionSeeder::class,
            TickerSeeder::class,
            ZakatConditionSeeder::class,
            AboutUsSeeder::class,
            AboutHistorySeeder::class,
            CategorySeeder::class,
            ServiceSeeder::class,
            AboutVisionSeeder::class,
            AboutGoal2Seeder::class,
            AboutGoalSeeder::class,
            TeamMemberSeeder::class,
            FaqSeeder::class,
            ContactInfoSeeder::class,
            BranchSeeder::class
        ]);
    }
}
