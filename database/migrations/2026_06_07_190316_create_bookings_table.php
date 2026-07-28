<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('room_category_id')->constrained('room_categories');

            $table->string('booking_type', 20)->default('overnight'); // overnight, hourly
            $table->string('booking_mode', 20)->default('advance'); // advance, walk_in
            $table->string('booking_source', 20)->default('reception'); // user_online, reception

            $table->date('check_in_date');
            $table->date('check_out_date');

            $table->integer('cleaning_buffer_minutes')->default(60);
            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();

            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);
            $table->integer('room_quantity')->default(1);
            $table->boolean('prefer_adjacent_rooms')->default(false);

            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);

            $table->decimal('late_arrival_fee', 12, 2)->default(0);
            $table->decimal('late_arrival_hours', 5, 2)->nullable();
            $table->string('late_arrival_policy', 255)->nullable();

            $table->string('payment_status', 20)->default('unpaid'); // unpaid, partial, paid, refunded
            $table->string('status', 20)->default('pending'); // pending, confirmed, checked_in, inspection_requested, checked_out, completed, cancelled

            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};