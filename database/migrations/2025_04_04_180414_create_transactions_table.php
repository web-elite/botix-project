<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // اطلاعات کاربر و پلن
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();

            // اطلاعات پرداخت
            $table->unsignedBigInteger('amount'); // به ریال
            $table->string('user_subscription_id')->default('new'); // مثلاً zibal، nextpay، ...
            $table->string('gateway')->default('zibal'); // مثلاً zibal، nextpay، ...
            $table->string('ref_id')->nullable(); // trackId از درگاه
            $table->string('ref_number')->nullable(); // شماره مرجع درگاه
            $table->string('card_number')->nullable(); // شماره کارت پرداخت‌کننده

            // وضعیت و زمان‌ها
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            // توضیحات اضافی
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

