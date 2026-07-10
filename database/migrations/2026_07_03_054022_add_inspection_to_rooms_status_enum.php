<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM(
            'available',
            'reserved',
            'occupied',
            'cleaning',
            'maintenance',
            'inspection'
        ) NOT NULL DEFAULT 'available'");
    }

    public function down(): void
    {
        // Chuyển các phòng đang inspection về available trước khi thu hẹp enum
        DB::statement("UPDATE rooms SET status = 'available' WHERE status = 'inspection'");

        DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM(
            'available',
            'reserved',
            'occupied',
            'cleaning',
            'maintenance'
        ) NOT NULL DEFAULT 'available'");
    }
};
