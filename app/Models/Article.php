<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = ['type', 'image', 'main_title', 'second_title', 'description', 'published_date'];

    protected $casts = [
        'published_date' => 'date',
    ];
}
