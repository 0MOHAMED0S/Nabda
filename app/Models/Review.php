<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'name', 'rating', 'message', 'is_approved'];
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->orderBy('created_at', 'desc');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
