<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'baby_count')) {
                $table->unsignedInteger('baby_count')->default(0)->after('child_count');
            }
        });

        Schema::table('room_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('room_inspections', 'minibar_total')) {
                $table->decimal('minibar_total', 15, 2)->default(0)->after('damage_total');
            }
            if (!Schema::hasColumn('room_inspections', 'approved_total')) {
                $table->decimal('approved_total', 15, 2)->default(0)->after('minibar_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('room_inspections', function (Blueprint $table) {
            $table->dropColumn(['minibar_total', 'approved_total']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('baby_count');
        });
    }
};
