<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('booking_rooms', 'baby_count')) {
            Schema::table('booking_rooms', function (Blueprint $table) {
                $table->unsignedInteger('baby_count')->default(0)->after('child_count');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('booking_rooms', 'baby_count')) {
            Schema::table('booking_rooms', function (Blueprint $table) {
                $table->dropColumn('baby_count');
            });
        }
    }
};
