<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: occupancy now uses adult_count and child_count only.
    }

    public function down(): void
    {
        // No-op.
    }
};
