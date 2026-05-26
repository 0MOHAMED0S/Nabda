<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationRating extends Model
{
    use HasFactory;
    protected $fillable = ['foundation_id', 'user_id', 'rating', 'name', 'message'];
}
