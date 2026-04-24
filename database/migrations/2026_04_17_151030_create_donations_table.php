<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // 1. وجهة التبرع (Relations)
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            // حالة التبرع (إذا كان null فهذا يعني التبرع للمؤسسة بشكل عام)
            $table->foreignId('case_id')->nullable()->constrained('foundation_cases')->nullOnDelete();
            // المستخدم المتبرع (إذا كان null فهذا يعني أنه زائر / فاعل خير)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // 2. نوع التبرع
            $table->enum('donation_type', ['financial', 'in-kind']);

            // 3. بيانات المتبرع (للزوار أو للتوصيل)
            $table->string('donor_name')->default('فاعل خير');
            $table->string('donor_phone')->nullable();
            $table->text('donor_address')->nullable(); // مطلوب في حالة الاستلام من المنزل

            // 4. التفاصيل المالية (للتبرع المالي)
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('payment_method')->nullable(); // طريقة الدفع (مثال: fake, visa, fawry)
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');

            // 5. التفاصيل العينية (للتبرع العيني)
            $table->string('item_category')->nullable(); // نوع الصنف (ملابس، أدوية، الخ)
            $table->text('item_description')->nullable(); // وصف تفصيلي
            $table->string('item_condition')->nullable(); // حالة التبرع (جديد، مستعمل)
            $table->enum('delivery_method', ['home_pickup', 'branch_dropoff', 'collection_point'])->nullable(); // طريقة التسليم
            $table->dateTime('pickup_time')->nullable(); // الوقت المناسب للاستلام
            $table->string('donation_image')->nullable(); // صورة التبرع العيني (اختياري)

            // 6. حالة الطلب بشكل عام
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
