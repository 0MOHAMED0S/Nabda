<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_teams', function (Blueprint $table) {
            $table->id();
            // ربط العضو بالمؤسسة (بمجرد حذف المؤسسة يحذف فريقها)
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

            $table->string('name'); // الاسم الكامل
            $table->string('position'); // المنصب (مثال: المدير التنفيذي)
            $table->string('phone'); // رقم الهاتف (اختياري/مطلوب حسب رغبتك، سنجعله اختياري في الداتابيز ومطلوب في الـ API)
            $table->string('image'); // مسار الصورة الشخصية
            $table->enum('status', ['active', 'archived'])->default('active'); // الحالة (نشط/مؤرشف)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_teams');
    }
};
