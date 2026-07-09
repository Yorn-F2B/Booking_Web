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
        Schema::create('room_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('price', 12, 2);
            $table->integer('max_people');
            $table->decimal('area', 6, 2)->nullable();
            $table->integer('bed_count')->default(1);
            $table->string('bed_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_categories');
    }
};
