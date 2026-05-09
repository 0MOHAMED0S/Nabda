<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_volunteer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_opportunity_id')->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->foreignId('volunteer_id')->constrained('volunteers')->cascadeOnDelete();

            // حالة طلب التطوع (مقبول، مرفوض، قيد الانتظار)
            $table->enum('status', ['pending', 'accepted', 'rejected', 'attended'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_volunteer');
    }
};
