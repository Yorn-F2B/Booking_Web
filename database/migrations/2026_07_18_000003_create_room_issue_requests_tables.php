<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_issue_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_room_id')->constrained('rooms');
            $table->foreignId('current_room_category_id')->constrained('room_categories');
            $table->foreignId('approved_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('approved_room_category_id')->nullable()->constrained('room_categories')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('issue_description');
            $table->enum('status', ['pending', 'approved', 'repair_only', 'rejected'])->default('pending');
            $table->enum('resolution_type', ['same_category', 'upgrade_category', 'no_room'])->nullable();
            $table->enum('repair_status', ['waiting', 'completed'])->nullable();
            $table->foreignId('repair_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('price_difference_per_night', 15, 2)->default(0);
            $table->json('promotion_codes')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('repair_completed_at')->nullable();
            $table->text('repair_note')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });

        Schema::create('room_issue_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_issue_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_issue_attachments');
        Schema::dropIfExists('room_issue_requests');
    }
};
