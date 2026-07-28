<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE services MODIFY type ENUM('service','minibar','damage_fee','extra_guest_fee') NOT NULL DEFAULT 'service'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE services MODIFY type ENUM('service','minibar','damage_fee') NOT NULL DEFAULT 'service'");
        }
    }
};
