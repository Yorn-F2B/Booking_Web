<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_code')->unique();
                $table->foreignId('booking_id')->constrained()->onDelete('cascade');
                $table->string('customer_name');
                $table->text('room_numbers');
                $table->date('check_in_date');
                $table->date('check_out_date');
                $table->dateTime('actual_check_in')->nullable();
                $table->dateTime('actual_check_out')->nullable();
                $table->decimal('room_charge', 15, 2)->default(0);
                $table->decimal('service_charge', 15, 2)->default(0);
                $table->decimal('minibar_charge', 15, 2)->default(0);
                $table->decimal('extra_charge', 15, 2)->default(0);
                $table->decimal('damage_fee', 15, 2)->default(0);
                $table->decimal('deposit_amount', 15, 2)->default(0);
                $table->decimal('remaining_amount', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('printed_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
