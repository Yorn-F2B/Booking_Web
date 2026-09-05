<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bookings', 'refund_processed_note')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->text('refund_processed_note')->nullable()->after('refund_processed_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'refund_processed_note')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('refund_processed_note');
            });
        }
    }
};
