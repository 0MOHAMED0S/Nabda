<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'foundation_id', 'case_id', 'user_id',
        'donation_type', 'donor_name', 'donor_phone', 'donor_address',
        'amount', 'payment_method', 'payment_status',
        'item_category', 'item_description', 'item_condition',
        'delivery_method', 'pickup_time', 'donation_image', 'status'
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    // العلاقات
    public function foundation() {
        return $this->belongsTo(Foundation::class);
    }

    public function foundationCase() {
        return $this->belongsTo(FoundationCase::class, 'case_id');
    }

    // إذا كان لديك جدول users للمتبرعين
    public function user() {
        return $this->belongsTo(User::class);
    }
}
