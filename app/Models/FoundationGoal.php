<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'title',
        'description',
    ];

    // العلاقة مع المؤسسة
    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }
}
