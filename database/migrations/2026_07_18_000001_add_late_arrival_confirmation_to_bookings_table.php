<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $hasPolicy = Schema::hasColumn('bookings', 'late_arrival_policy');
        $hasConfirmedAt = Schema::hasColumn('bookings', 'late_arrival_confirmed_at');
        $hasConfirmedBy = Schema::hasColumn('bookings', 'late_arrival_confirmed_by');

        Schema::table('bookings', function (Blueprint $table) use ($hasPolicy, $hasConfirmedAt, $hasConfirmedBy) {
            if (!$hasConfirmedAt) {
                $column = $table->timestamp('late_arrival_confirmed_at')->nullable();
                if ($hasPolicy) {
                    $column->after('late_arrival_policy');
                }
            }

            if (!$hasConfirmedBy) {
                $column = $table->unsignedBigInteger('late_arrival_confirmed_by')->nullable();
                $column->after('late_arrival_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('bookings', 'late_arrival_confirmed_by') ? 'late_arrival_confirmed_by' : null,
            Schema::hasColumn('bookings', 'late_arrival_confirmed_at') ? 'late_arrival_confirmed_at' : null,
        ]));

        if ($columns !== []) {
            Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
