<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticker extends Model
{
    protected $fillable = ['content', 'is_active', 'order'];
}
