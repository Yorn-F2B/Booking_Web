<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->constrained('rooms');

            $table->integer('adult_count')->default(1);
            $table->integer('child_count')->default(0);

            $table->decimal('price_at_booking', 12, 2)->default(0);

            $table->decimal('surcharge', 12, 2)->default(0);
            $table->string('surcharge_reason')->nullable();

            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_rooms');
    }
};