<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_service_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services');

            $table->string('name');
            $table->string('type');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_service_items');
    }
};
