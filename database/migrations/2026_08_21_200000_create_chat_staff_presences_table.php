<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_staff_presences')) {
            return;
        }

        Schema::create('chat_staff_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['online', 'away', 'offline'])->default('offline');
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_assigned_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_seen_at'], 'chat_presence_status_seen_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_staff_presences');
    }
};
