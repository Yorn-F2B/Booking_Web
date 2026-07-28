<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'check_in_at')) {
                $table->dateTime('check_in_at')->nullable()->after('check_out_date');
            }
            if (!Schema::hasColumn('bookings', 'check_out_at')) {
                $table->dateTime('check_out_at')->nullable()->after('check_in_at');
            }
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::table('bookings')->update([
                'check_in_at' => DB::raw("check_in_date || ' 14:00:00'"),
                'check_out_at' => DB::raw("check_out_date || ' 12:00:00'"),
            ]);
        } else {
            DB::table('bookings')->update([
                'check_in_at' => DB::raw("CONCAT(check_in_date, ' 14:00:00')"),
                'check_out_at' => DB::raw("CONCAT(check_out_date, ' 12:00:00')"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['check_in_at', 'check_out_at']);
        });
    }
};