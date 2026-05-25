<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id', 'volunteer_opportunity_id', 'hours', 'summary', 'images','status', 'rating', 'feedback_message'
    ];

    protected function casts(): array
    {
        return [
            'hours'  => 'float',
            'images' => 'array', // تحويل مسارات الصور إلى مصفوفة تلقائياً
        ];
    }

    /**
     * علاقة التقرير بالفرصة التطوعية
     */
    public function opportunity()
    {
        return $this->belongsTo(VolunteerOpportunity::class, 'volunteer_opportunity_id');
    }

    /**
     * علاقة التقرير بالمتطوع صاحب التقرير
     */
    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class, 'volunteer_id');
    }
}
