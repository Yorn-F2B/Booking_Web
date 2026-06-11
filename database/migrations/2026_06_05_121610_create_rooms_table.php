<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();

                $table->string('room_number', 20)->unique();

                $table->foreignId('room_category_id')
                    ->constrained('room_categories')
                    ->cascadeOnDelete();

                $table->integer('floor_number')->nullable();

                $table->enum('status', [
                    'available',
                    'reserved',
                    'occupied',
                    'cleaning',
                    'maintenance'
                ])->default('available');

                $table->text('note')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};