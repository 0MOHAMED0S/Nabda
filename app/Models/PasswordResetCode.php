<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    // لا نحتاج للـ updated_at في هذا الجدول
    public $timestamps = false;

    protected $fillable = [
        'email', 'code', 'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
