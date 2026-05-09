<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerOpportunity extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id', 'title', 'category', 'description',
        'location', 'date', 'start_time', 'end_time', 'total_hours',
        'required_volunteers', 'requirements',
        'contact_person', 'contact_phone', 'status'
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'requirements' => 'array', // تحويل المتطلبات تلقائياً لمصفوفة
        ];
    }

    // علاقة الفرصة بالمؤسسة
    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }

    // علاقة الفرصة بالمتطوعين المسجلين فيها
    public function volunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'opportunity_volunteer')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
