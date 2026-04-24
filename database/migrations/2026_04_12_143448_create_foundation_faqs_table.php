<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_faqs', function (Blueprint $table) {
            $table->id();
            // ربط السؤال بالمؤسسة (بمجرد حذف المؤسسة تحذف أسئلتها)
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();

            $table->string('question'); // السؤال
            $table->text('answer'); // الإجابة
            $table->enum('status', ['published', 'archived'])->default('published'); // الحالة (منشور/مؤرشف)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_faqs');
    }
};
