<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'cleaning_buffer_minutes')) {
            DB::statement('ALTER TABLE bookings MODIFY cleaning_buffer_minutes INT NOT NULL DEFAULT 0');
            DB::table('bookings')->where('cleaning_buffer_minutes', 60)->update(['cleaning_buffer_minutes' => 0]);
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE bookings MODIFY cleaning_buffer_minutes INT NOT NULL DEFAULT 60');
    }
};
