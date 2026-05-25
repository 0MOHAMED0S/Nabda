<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('volunteer_id')->constrained('volunteers')->cascadeOnDelete();
            $table->foreignId('volunteer_opportunity_id')->constrained('volunteer_opportunities')->cascadeOnDelete();

            $table->decimal('hours', 5, 2); // الساعات المدخلة (ويمكن للمؤسسة تعديلها)
            $table->text('summary');
            $table->json('images')->nullable();

            // 🎯 الحقول الجديدة لدعم شاشة التقييم
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); // حالة التقرير
            $table->integer('rating')->nullable(); // التقييم بالنجوم (1 إلى 5)
            $table->text('feedback_message')->nullable(); // رسالة المؤسسة للمتطوع

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_reports');
    }
};
