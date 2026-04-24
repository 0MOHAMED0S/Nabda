<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة (Mass Assignment)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'answer',
        'order',
        'is_active',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً
     * * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    /**
     * Scope لجلب الأسئلة المفعلة فقط مرتبة حسب حقل الترتيب
     * يمكن استخدامه في الموقع (Front-end) هكذا: Faq::active()->get();
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order', 'asc');
    }
}
