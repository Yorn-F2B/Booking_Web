<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('operational_notifications')) Schema::create('operational_notifications', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); $t->string('role',50)->nullable();
            $t->string('title'); $t->text('message'); $t->string('type',30)->default('info'); $t->text('target_url')->nullable();
            $t->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete(); $t->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $t->timestamp('read_at')->nullable(); $t->json('meta')->nullable(); $t->timestamps(); $t->index(['user_id','read_at']); $t->index(['role','read_at']);
        });
        if (!Schema::hasTable('email_delivery_logs')) Schema::create('email_delivery_logs', function(Blueprint $t){
            $t->id(); $t->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete(); $t->string('recipient',255); $t->string('mail_type',100);
            $t->string('subject')->nullable(); $t->enum('status',['pending','sent','failed'])->default('pending'); $t->unsignedInteger('attempts')->default(1);
            $t->timestamp('sent_at')->nullable(); $t->timestamp('failed_at')->nullable(); $t->text('error_message')->nullable(); $t->json('meta')->nullable(); $t->timestamps();
            $t->index(['status','created_at']); $t->index(['booking_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('email_delivery_logs'); Schema::dropIfExists('operational_notifications'); }
};
