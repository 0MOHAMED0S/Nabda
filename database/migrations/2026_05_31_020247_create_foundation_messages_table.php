<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('foundation_id')->constrained()->cascadeOnDelete();
            // user_id اختياري حتى يتمكن الزوار (غير المسجلين) من الإرسال أيضاً
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // بيانات المرسل والرسالة
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');

            // بيانات الرد (تُحفظ عند قيام المؤسسة بالرد)
            $table->string('reply_subject')->nullable();
            $table->text('reply_body')->nullable();

            // حالة الرسالة
            $table->boolean('is_read')->default(false);
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_messages');
    }
};
