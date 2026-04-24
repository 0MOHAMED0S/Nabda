<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بتعبئتها
     */
    protected $fillable = [
        // 1. وجهة التبرع
        'foundation_id', 'case_id', 'user_id',

        // 2. نوع التبرع
        'donation_type',

        // 3. بيانات المتبرع
        'donor_name', 'donor_email', 'donor_phone', 'donor_address', // 🎯 تمت إضافة donor_email هنا

        // 4. التفاصيل المالية (شاملة حقول Paymob)
        'amount', 'payment_method', 'payment_status',
        'paymob_order_id', 'paymob_transaction_id', // 🎯 تمت إضافة حقول تتبع Paymob هنا

        // 5. التفاصيل العينية
        'item_category', 'item_description', 'item_condition',
        'delivery_method', 'pickup_time', 'donation_image',

        // 6. حالة الطلب العامة
        'status'
    ];

    /**
     * التحويلات التلقائية للحقول
     */
    protected $casts = [
        'pickup_time' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    // ==========================================
    // العلاقات (Relationships)
    // ==========================================

    /**
     * التبرع يتبع لمؤسسة معينة
     */
    public function foundation() {
        return $this->belongsTo(Foundation::class);
    }

    /**
     * التبرع قد يتبع لحالة محددة (اختياري)
     */
    public function foundationCase() {
        return $this->belongsTo(FoundationCase::class, 'case_id');
    }

    /**
     * التبرع قد يكون من مستخدم مسجل (اختياري، قد يكون المتبرع زائر)
     */
    public function user() {
        return $this->belongsTo(User::class);
    }
}
