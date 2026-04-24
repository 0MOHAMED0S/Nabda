<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

            // 1. البيانات الأساسية
            $table->string('title');
            $table->string('campaign_type');
            $table->text('main_description');
            $table->text('additional_description')->nullable();
            $table->string('beneficiary_name');
            $table->integer('beneficiary_age');
            $table->string('beneficiary_address');
            $table->enum('priority', ['urgent', 'normal'])->default('normal');

            // 2. المعلومات المالية
            $table->date('end_date');
            $table->enum('goal_type', ['financial', 'in-kind'])->default('financial');
            $table->decimal('target_amount', 15, 2)->nullable();

            // 3. المرفقات (JSON لدعم التعدد)
            $table->json('images')->nullable();
            $table->json('documents')->nullable();
            $table->string('video')->nullable();

            $table->enum('status', ['active', 'completed', 'cancelled', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_cases');
    }
};
