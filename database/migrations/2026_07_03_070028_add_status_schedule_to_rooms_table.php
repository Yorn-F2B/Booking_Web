<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dateTime('status_from')->nullable()->after('status')->comment('Thời điểm bắt đầu trạng thái hiện tại');
            $table->dateTime('status_until')->nullable()->after('status_from')->comment('Thời điểm kết thúc trạng thái hiện tại');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['status_from', 'status_until']);
        });
    }
};
