<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_branches', function (Blueprint $table) {
            $table->id();
            // ربط الفرع بالمؤسسة
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

            $table->string('name'); // اسم الفرع (مثال: فرع القاهرة)
            $table->string('phone'); // الهاتف
            $table->text('address'); // العنوان الكامل
            $table->string('email')->nullable(); // البريد الإلكتروني (اختياري)
            $table->string('coordinates'); // إحداثيات الموقع (خط الطول والعرض)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_branches');
    }
};
