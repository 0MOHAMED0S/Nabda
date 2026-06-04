<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('foundation_ratings', function (Blueprint $table) {
        $table->id();

        // 🎯 التعديل هنا: توجيه العلاقة إلى جدول foundations
        $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

        $table->unsignedBigInteger('user_id')->nullable();
        $table->integer('rating');
        $table->string('name')->default('فاعل خير');
        $table->string('message', 200)->nullable();
        $table->boolean('is_approved')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foundation_ratings');
    }
};
