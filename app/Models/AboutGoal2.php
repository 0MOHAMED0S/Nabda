<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutGoal2 extends Model
{
    use HasFactory;

    protected $table = 'about_goal2s';

    protected $fillable = [
        'title',
        'description',
    ];
}
