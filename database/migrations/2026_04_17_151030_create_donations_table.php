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

            // 1. وجهة التبرع
            $table->foreignId('foundation_id')->constrained('foundations')->cascadeOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('foundation_cases')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // 2. نوع التبرع
            $table->enum('donation_type', ['financial', 'in-kind']);

            // 3. بيانات المتبرع
            $table->string('donor_name')->default('فاعل خير');
            $table->string('donor_email')->nullable(); // 🎯 مهم جداً لـ Paymob
            $table->string('donor_phone')->nullable();
            $table->text('donor_address')->nullable();

            // 4. التفاصيل المالية
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');

            // 🎯 5. إضافات بوابات الدفع (Paymob)
            $table->string('paymob_order_id')->nullable();       // رقم الطلب في بيموب
            $table->string('paymob_transaction_id')->nullable(); // رقم العملية الناجحة

            // 6. التفاصيل العينية
            $table->string('item_category')->nullable();
            $table->text('item_description')->nullable();
            $table->string('item_condition')->nullable();
            $table->enum('delivery_method', ['home_pickup', 'branch_dropoff', 'collection_point'])->nullable();
            $table->dateTime('pickup_time')->nullable();
            $table->string('donation_image')->nullable();

            // 7. حالة الطلب بشكل عام
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
