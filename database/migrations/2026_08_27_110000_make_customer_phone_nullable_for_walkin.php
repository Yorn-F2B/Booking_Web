<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Không tự đổi về NOT NULL vì có thể đã phát sinh khách walk-in không có điện thoại.
    }
};
