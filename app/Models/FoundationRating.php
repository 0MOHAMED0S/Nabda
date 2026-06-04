<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationRating extends Model
{
    use HasFactory;
    protected $fillable = ['foundation_id', 'user_id', 'rating', 'name', 'message', 'is_approved'];

    // أضف هذه الدالة داخل موديل FoundationRating
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
