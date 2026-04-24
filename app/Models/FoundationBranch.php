<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'name',
        'phone',
        'address',
        'email',
        'coordinates',
    ];

    // العلاقة مع المؤسسة
    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }
}
