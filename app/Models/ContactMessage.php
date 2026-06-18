<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'subject', 'message',
        'reply_subject', 'reply_body', 'is_read', 'replied_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'replied_at' => 'datetime',
    ];

    /**
     * إذا كان المرسل مستخدم مسجل
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
