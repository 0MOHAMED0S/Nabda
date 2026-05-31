<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id',
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'reply_subject',
        'reply_body',
        'is_read',
        'replied_at'
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function foundation()
    {
        return $this->belongsTo(Foundation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
