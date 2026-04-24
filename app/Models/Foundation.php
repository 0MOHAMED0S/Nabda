<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Foundation extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $guard = 'foundation';

protected $fillable = [
        // البيانات الأساسية
        'name',
        'email',
        'phone',
        'type',
        'password',
        'logo',

        // بيانات الترخيص
        'license_number',
        'supervising_authority',
        'license_image',
        'commercial_register',
        'tax_card',
        'accreditation_letter',
        'headquarters_image',

        // بيانات بروفايل المؤسسة (الجديدة)
        'cover_image',
        'about_desc_1',
        'about_desc_2',
        'vision',
        'mission',
        'core_mission',
        'contact_email',
        'contact_phone',
        'main_address',
        'website_url',
        'foundation_date',
        'working_hours',

        // حالات الحساب
        'approval_status',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'foundation_date' => 'date',
    ];

    // أضف هذه الدالة داخل كلاس Foundation
    public function faqs()
    {
        return $this->hasMany(FoundationFaq::class);
    }
    public function teamMembers()
    {
        return $this->hasMany(FoundationTeam::class);
    }
    public function goals()
    {
        return $this->hasMany(FoundationGoal::class);
    }
    public function branches()
    {
        return $this->hasMany(FoundationBranch::class);
    }
    public function cases()
    {
        return $this->hasMany(FoundationCase::class);
    }
}
