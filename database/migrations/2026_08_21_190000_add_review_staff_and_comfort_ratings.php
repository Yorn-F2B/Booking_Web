<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_reviews')) {
            return;
        }

        Schema::table('hotel_reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_reviews', 'staff_rating')) {
                $table->unsignedTinyInteger('staff_rating')->nullable()->after('location_rating');
            }
            if (!Schema::hasColumn('hotel_reviews', 'comfort_rating')) {
                $table->unsignedTinyInteger('comfort_rating')->nullable()->after('service_rating');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hotel_reviews')) {
            return;
        }

        Schema::table('hotel_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_reviews', 'comfort_rating')) {
                $table->dropColumn('comfort_rating');
            }
            if (Schema::hasColumn('hotel_reviews', 'staff_rating')) {
                $table->dropColumn('staff_rating');
            }
        });
    }
};
