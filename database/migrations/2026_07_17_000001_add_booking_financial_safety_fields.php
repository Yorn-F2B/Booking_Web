<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  DB::statement("ALTER TABLE services MODIFY COLUMN type ENUM('service','minibar','minibar_order','damage_fee','occupancy_fee','policy_violation_fee','early_checkin_fee','late_checkout_fee','extension_fee','extra_guest_fee','manual_fee') NOT NULL DEFAULT 'service'");
  Schema::table('bookings', function (Blueprint $table) {
   if (!Schema::hasColumn('bookings','payment_expires_at')) $table->dateTime('payment_expires_at')->nullable()->after('deposit_amount');
   if (!Schema::hasColumn('bookings','final_total')) $table->decimal('final_total',12,2)->nullable()->after('estimated_total');
  });
  Schema::create('customer_credits', function (Blueprint $table) {
   $table->id(); $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
   $table->foreignId('source_booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
   $table->decimal('original_amount',12,2); $table->decimal('remaining_amount',12,2);
   $table->dateTime('expires_at'); $table->enum('status',['active','used','expired'])->default('active');
   $table->text('note')->nullable(); $table->timestamps();
   $table->index(['customer_id','status','expires_at']);
  });
 }
 public function down(): void { Schema::dropIfExists('customer_credits'); Schema::table('bookings', fn(Blueprint $t) => $t->dropColumn(['payment_expires_at','final_total'])); }
};
