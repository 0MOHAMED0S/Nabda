<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foundations', function (Blueprint $table) {
            $table->id();

            // ----------------------------------------------------
            // 1. البيانات الأساسية للتسجيل (Required)
            // ----------------------------------------------------
            $table->string('name');
            $table->string('email')->unique(); // إيميل تسجيل الدخول
            $table->string('phone')->unique(); // هاتف التسجيل
            $table->string('type'); // نوع المؤسسة
            $table->string('password');
            $table->string('logo'); // لوجو المؤسسة (يُطلب عند التسجيل)

            // ----------------------------------------------------
            // 2. معلومات الترخيص والتوثيق (Required for Approval)
            // ----------------------------------------------------
            $table->string('license_number');
            $table->string('supervising_authority'); // الجهة المشرفة
            $table->string('license_image'); // صورة الترخيص
            $table->string('commercial_register'); // السجل التجاري
            $table->string('tax_card'); // البطاقة الضريبية
            $table->string('accreditation_letter'); // خطاب اعتماد رسمي
            $table->string('headquarters_image'); // صورة مقر المؤسسة

            // ----------------------------------------------------
            // 3. محتوى بروفايل المؤسسة (Nullable - يُضاف لاحقاً من لوحة التحكم)
            // ----------------------------------------------------
            $table->string('cover_image')->nullable(); // صورة الغلاف (Cover)

            // من نحن (About)
            $table->text('about_desc_1')->nullable(); // الوصف الأول
            $table->text('about_desc_2')->nullable(); // الوصف الثاني

            // الرؤية، الرسالة، والمهمة الأساسية
            $table->text('vision')->nullable(); // الرؤية
            $table->text('mission')->nullable(); // الرسالة
            $table->text('core_mission')->nullable(); // المهمة الأساسية

            // معلومات الاتصال العامة (لظهورها للجمهور)
            $table->string('contact_email')->nullable(); // بريد التواصل العام
            $table->string('contact_phone')->nullable(); // هاتف التواصل العام
            $table->text('main_address')->nullable(); // العنوان الرئيسي
            $table->string('website_url')->nullable(); // الموقع الإلكتروني
            $table->date('foundation_date')->nullable(); // تاريخ التأسيس
            $table->string('working_hours')->nullable(); // ساعات العمل

            // ----------------------------------------------------
            // 4. حالات الحساب والاعتماد
            // ----------------------------------------------------
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundations');
    }
};
