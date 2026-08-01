<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'late_arrival_confirmed_at')) {
                $table->timestamp('late_arrival_confirmed_at')->nullable()->after('late_arrival_policy');
            }
            if (!Schema::hasColumn('bookings', 'late_arrival_confirmed_by')) {
                $table->unsignedBigInteger('late_arrival_confirmed_by')->nullable()->after('late_arrival_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('bookings', 'late_arrival_confirmed_by')) $columns[] = 'late_arrival_confirmed_by';
            if (Schema::hasColumn('bookings', 'late_arrival_confirmed_at')) $columns[] = 'late_arrival_confirmed_at';
            if ($columns) $table->dropColumn($columns);
        });
    }
};
