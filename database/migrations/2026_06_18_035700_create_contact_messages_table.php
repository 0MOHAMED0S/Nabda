<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            // user_id اختياري لكي يتمكن الزوار من الإرسال
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // بيانات المرسل والرسالة
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');

            // بيانات الرد (من الإدمن)
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
        Schema::dropIfExists('contact_messages');
    }
};
