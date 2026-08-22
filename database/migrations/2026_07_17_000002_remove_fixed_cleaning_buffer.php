<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Older SQL dumps already contain this column, while a clean migration
        // chain did not create it before this migration. Keep both paths safe.
        if (!Schema::hasColumn('bookings', 'cleaning_buffer_minutes')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->integer('cleaning_buffer_minutes')->default(0)->after('check_out_date');
            });
        } else {
            DB::statement('ALTER TABLE bookings MODIFY cleaning_buffer_minutes INT NOT NULL DEFAULT 0');
        }

        DB::table('bookings')
            ->where('cleaning_buffer_minutes', 60)
            ->update(['cleaning_buffer_minutes' => 0]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'cleaning_buffer_minutes')) {
            DB::statement('ALTER TABLE bookings MODIFY cleaning_buffer_minutes INT NOT NULL DEFAULT 60');
        }
    }
};
