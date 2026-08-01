<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_reviews', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_category_id')->nullable()->constrained()->nullOnDelete();
            
            $table->tinyInteger('rating')->default(5);
            $table->tinyInteger('cleanliness_rating')->nullable();
            $table->tinyInteger('service_rating')->nullable();
            $table->tinyInteger('location_rating')->nullable();
            $table->tinyInteger('value_rating')->nullable();
            
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            
            $table->enum('status', ['pending', 'approved', 'hidden'])->default('pending');
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable();
            $table->string('hidden_reason')->nullable();
            
            $table->text('admin_reply')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_reviews');
    }
};
