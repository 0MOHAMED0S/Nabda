<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id', 'volunteer_opportunity_id', 'hours', 'summary', 'images'
    ];

    protected function casts(): array
    {
        return [
            'hours'  => 'float',
            'images' => 'array', // تحويل مسارات الصور إلى مصفوفة تلقائياً
        ];
    }
}
