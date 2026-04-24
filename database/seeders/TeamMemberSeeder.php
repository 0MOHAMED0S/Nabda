<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            [
                'name' => 'محمد عاشور',
                'job_title' => 'مدير التطبيق',
                'image' => 'team/team.webp',
                'order' => 1,
            ],
            [
                'name' => 'أحمد علي',
                'job_title' => 'مطور برمجيات',
                'image' => 'team/team.webp',
                'order' => 2,
            ],
            [
                'name' => 'سارة محمود',
                'job_title' => 'مصممة واجهات UI/UX',
                'image' => 'team/team.webp',
                'order' => 3,
            ],
            [
                'name' => 'ياسين حسن',
                'job_title' => 'مسؤول العلاقات العامة',
                'image' => 'team/team.webp',
                'order' => 4,
            ],
            [
                'name' => 'ليلى إبراهيم',
                'job_title' => 'منسق ميداني',
                'image' => 'team/team.webp',
                'order' => 5,
            ],
        ];

        foreach ($members as $member) {
            TeamMember::create($member);
        }
    }
}
