<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('provider', 50)->default('vnpay');
            $table->string('txn_ref', 100)->unique();
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_type', 20);
            $table->string('bank_code', 50)->nullable();
            $table->string('transaction_no', 100)->nullable();
            $table->string('response_code', 20)->nullable();
            $table->string('transaction_status', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
