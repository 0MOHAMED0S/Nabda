<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Volunteer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'volunteer'; // تحديد الحرس

    protected $fillable = [
        'name', 'email', 'phone', 'address',
        'volunteer_type', 'foundation_id', 'volunteer_fields', 'governorates', 'avatar',
        'national_id', 'national_id_front', 'national_id_back',
        'password', 'status'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'         => 'hashed',
            'volunteer_fields' => 'array', // تحويل الـ JSON لمصفوفة تلقائياً
            'governorates'     => 'array', // تحويل الـ JSON لمصفوفة تلقائياً
        ];
    }

    // علاقة المتطوع بالمؤسسة (إذا كان تطوعه دائماً مع مؤسسة محددة)
    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }
}
