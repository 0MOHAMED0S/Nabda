<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_opportunities', function (Blueprint $table) {
            $table->id();

            // الربط بالمؤسسة صاحبة الفرصة
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

            // 1. البيانات الأساسية
            $table->string('title'); // عنوان الفرصة
            $table->string('category')->nullable(); // التصنيف
            $table->text('description'); // وصف الفرصة

            // 2. الموقع والمواعيد
            $table->string('location'); // الموقع
            $table->date('date'); // التاريخ
            $table->time('start_time'); // من ساعة
            $table->time('end_time'); // إلى ساعة
            $table->integer('total_hours')->default(0); // إجمالي الساعات (يُحسب تلقائياً)

            // 3. المتطلبات والعدد
            $table->integer('required_volunteers'); // عدد المتطوعين المطلوب
            $table->json('requirements')->nullable(); // المتطلبات (مصفوفة)

            // 4. معلومات الاتصال
            $table->string('contact_person'); // اسم المسؤول
            $table->string('contact_phone'); // رقم الهاتف

            // الحالة
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active'); // نشط / مكتمل / ملغى

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_opportunities');
    }
};
