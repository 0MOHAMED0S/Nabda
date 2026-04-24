<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_goals', function (Blueprint $table) {
            $table->id();
            // ربط الهدف بالمؤسسة (بمجرد حذف المؤسسة تحذف أهدافها)
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

            $table->string('title'); // عنوان الهدف (مثال: دعم التعليم)
            $table->text('description'); // وصف الهدف

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_goals');
    }
};
