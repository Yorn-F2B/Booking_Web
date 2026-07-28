<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY status ENUM('pending','confirmed','checked_in','inspection_requested','checked_out','completed','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['status', 'check_in_at', 'check_out_at', 'payment_expires_at'], 'idx_bookings_availability');
        });

        Schema::table('room_inspections', function (Blueprint $table) {
            $table->unique(['booking_id', 'room_id'], 'uq_room_inspections_booking_room');
        });

        Schema::table('booking_payments', function (Blueprint $table) {
            $table->unique('txn_ref', 'uq_booking_payments_txn_ref');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', fn (Blueprint $table) => $table->dropUnique('uq_booking_payments_txn_ref'));
        Schema::table('room_inspections', fn (Blueprint $table) => $table->dropUnique('uq_room_inspections_booking_room'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropIndex('idx_bookings_availability'));
    }
};
