<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_updates', function (Blueprint $table) {
            $table->id();

            // الربط بجدول الحالات
            $table->foreignId('foundation_case_id')->constrained('foundation_cases')->cascadeOnDelete();

            // بيانات التحديث بناءً على الصورة
            $table->string('title'); // عنوان التحديث
            $table->date('update_date'); // تاريخ التحديث
            $table->text('description'); // وصف التحديث

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_updates');
    }
};
