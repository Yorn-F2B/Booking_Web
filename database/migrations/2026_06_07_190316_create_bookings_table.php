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

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('room_category_id')
                ->constrained('room_categories');

            $table->date('check_in_date');
            $table->date('check_out_date');

            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();

            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);

            $table->integer('room_quantity')->default(1);

            $table->boolean('prefer_adjacent_rooms')->default(false);

            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);

            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
                'refunded',
            ])->default('unpaid');

            $table->enum('status', [
                'pending',
                'confirmed',
                'checked_in',
                'checked_out',
                'cancelled',
            ])->default('pending');

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