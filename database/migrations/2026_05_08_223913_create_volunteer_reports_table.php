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

            $table->decimal('hours', 5, 2); // كم ساعة تطوعت اليوم؟ (يدعم الكسور مثل 4.5 ساعات)
            $table->text('summary'); // ماذا أنجزت؟
            $table->json('images')->nullable(); // صور التوثيق (مصفوفة مسارات الصور)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_reports');
    }
};
