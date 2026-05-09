<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_case_id', 'title', 'update_date', 'description'
    ];

    protected function casts(): array
    {
        return [
            'update_date' => 'date',
        ];
    }

    // علاقة التحديث بالحالة
    public function case()
    {
        return $this->belongsTo(FoundationCase::class, 'foundation_case_id');
    }
}
