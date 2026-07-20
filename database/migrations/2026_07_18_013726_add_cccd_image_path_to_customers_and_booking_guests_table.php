<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('cccd_image_path')->nullable()->after('cccd');
        });

        Schema::table('booking_guests', function (Blueprint $table) {
            $table->string('cccd_image_path')->nullable()->after('cccd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('cccd_image_path');
        });

        Schema::table('booking_guests', function (Blueprint $table) {
            $table->dropColumn('cccd_image_path');
        });
    }
};
