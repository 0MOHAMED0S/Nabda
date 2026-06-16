<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'city',              // 🎯 تمت الإضافة
        'charity_interests', // 🎯 تمت الإضافة
        'password',
        'google_id',
        'title',             // 🎯 تمت الإضافة
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'charity_interests' => 'array', // 🎯 السحر هنا: يحول الـ JSON في الداتابيز لمصفوفة برمجية والعكس تلقائياً
        ];
    }

    /**
     * علاقة المستخدم مع تبرعاته
     */
    public function donations()
    {
        return $this->hasMany(\App\Models\Donation::class, 'user_id');
    }
}
