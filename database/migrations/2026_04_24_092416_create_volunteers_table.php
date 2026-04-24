<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();

            // الخطوة 1: البيانات الأساسية
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('address'); // العنوان

            // الخطوة 2: تفاصيل التطوع
            $table->enum('volunteer_type', ['general', 'affiliated']); // general = عام, affiliated = تابع لمؤسسة
            $table->foreignId('foundation_id')->nullable()->constrained('foundations')->nullOnDelete(); // في حال اختار مؤسسة محددة
            $table->json('volunteer_fields')->nullable(); // مجالات التطوع
            $table->json('governorates')->nullable(); // المحافظات المتاحة
            $table->string('avatar')->nullable(); // صورة شخصية (اختياري)

            // الخطوة 3: البيانات الرسمية والحساسة
            $table->string('national_id')->unique(); // الرقم القومي
            $table->string('national_id_front'); // صورة الوجه الأمامي
            $table->string('national_id_back'); // صورة الوجه الخلفي
            $table->string('password');

            // حالة الحساب
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); // يحتاج مراجعة الإدارة

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteers');
    }
};
