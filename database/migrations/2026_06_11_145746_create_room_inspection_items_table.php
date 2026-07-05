<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_inspection_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_inspection_id')
                ->constrained('room_inspections')
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->string('name', 150);

            $table->string('unit', 50)->nullable();

            $table->decimal('price', 12, 2)->default(0);

            $table->integer('quantity')->default(1);

            $table->decimal('total', 12, 2)->default(0);

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->text('admin_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_inspection_items');
    }
};