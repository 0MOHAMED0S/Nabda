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

            // 🎯 تم تحديث الحالات لتتوافق مع المنطق الجديد (مقدم بنفسه، مدعو، مقبول، مرفوض، حضر)
            $table->enum('status', ['applied', 'invited', 'accepted', 'rejected', 'attended'])->default('applied');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_volunteer');
    }
};
