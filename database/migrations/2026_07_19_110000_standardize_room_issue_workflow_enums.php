<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('room_issue_requests')) {
            return;
        }

        Schema::table('room_issue_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('room_issue_requests', 'resolution_type')) {
                $table->enum('resolution_type', ['same_category', 'upgrade_category', 'no_room'])->nullable()->after('status');
            }
            if (!Schema::hasColumn('room_issue_requests', 'repair_status')) {
                $table->enum('repair_status', ['waiting', 'completed'])->nullable()->after('resolution_type');
            }
            if (!Schema::hasColumn('room_issue_requests', 'repair_completed_by')) {
                $table->foreignId('repair_completed_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('room_issue_requests', 'repair_completed_at')) {
                $table->timestamp('repair_completed_at')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('room_issue_requests', 'repair_note')) {
                $table->text('repair_note')->nullable()->after('repair_completed_at');
            }
        });

        // Mở rộng ENUM trước để dữ liệu cũ có thể được chuyển mà không bị lỗi 1265.
        DB::statement("ALTER TABLE room_issue_requests MODIFY resolution_type ENUM('room_change','category_upgrade','same_category','upgrade_category','no_room') NULL");
        DB::statement("ALTER TABLE room_issue_requests MODIFY repair_status ENUM('pending','waiting','completed') NULL");
        DB::statement("UPDATE room_issue_requests SET resolution_type='same_category' WHERE resolution_type='room_change'");
        DB::statement("UPDATE room_issue_requests SET resolution_type='upgrade_category' WHERE resolution_type='category_upgrade'");
        DB::statement("UPDATE room_issue_requests SET repair_status='waiting' WHERE repair_status='pending'");
        DB::statement("ALTER TABLE room_issue_requests MODIFY resolution_type ENUM('same_category','upgrade_category','no_room') NULL");
        DB::statement("ALTER TABLE room_issue_requests MODIFY repair_status ENUM('waiting','completed') NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('room_issue_requests')) {
            return;
        }
        DB::statement("ALTER TABLE room_issue_requests MODIFY resolution_type ENUM('room_change','category_upgrade','upgrade_category','no_room') NULL");
        DB::statement("ALTER TABLE room_issue_requests MODIFY repair_status ENUM('pending','waiting','completed') NULL");
    }
};
