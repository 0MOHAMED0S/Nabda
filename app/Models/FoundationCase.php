<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'title', 'campaign_type', 'main_description', 'additional_description',
        'beneficiary_name', 'beneficiary_age', 'beneficiary_address', 'priority',
        'end_date', 'goal_type', 'target_amount',
        'images', 'documents', 'video', 'status'
    ];

    protected $casts = [
        'images'    => 'array',
        'documents' => 'array',
        'end_date'  => 'date',
    ];

    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }
    public function donations()
    {
        return $this->hasMany(Donation::class, 'case_id');
    }
}
