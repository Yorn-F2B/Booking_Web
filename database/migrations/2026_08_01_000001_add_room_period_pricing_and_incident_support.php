<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_room_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_room_id')->constrained('booking_rooms')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms');
            // Khoảng nửa mở [start_date, end_date): end_date là ngày trả/đổi phòng.
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_per_night', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['booking_room_id', 'start_date']);
        });

        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->decimal('support_amount', 12, 2)->default(0)->after('surcharge_reason');
            $table->string('support_reason')->nullable()->after('support_amount');
        });

        // promotion_type hiện đã phục vụ phân loại nghiệp vụ; trường này chỉ quy định phạm vi áp mã.
        Schema::table('promotions', function (Blueprint $table) {
            $table->enum('coupon_scope', ['normal', 'incident'])->default('normal')->after('promotion_type');
        });

        // Tạo một period cho dữ liệu cũ để chuyển đổi không làm đổi số tiền lịch sử.
        DB::table('booking_rooms')->orderBy('id')->chunkById(200, function ($rooms) {
            foreach ($rooms as $room) {
                $booking = DB::table('bookings')->where('id', $room->booking_id)->first();
                if (!$booking) continue;
                $start = substr((string) ($booking->check_in_at ?: $booking->check_in_date), 0, 10);
                $end = substr((string) ($booking->check_out_at ?: $booking->check_out_date), 0, 10);
                if ($start && $end && $end > $start) {
                    DB::table('booking_room_periods')->insert([
                        'booking_room_id' => $room->id, 'room_id' => $room->room_id,
                        'start_date' => $start, 'end_date' => $end,
                        'price_per_night' => $room->price_at_booking,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotions', fn (Blueprint $table) => $table->dropColumn('coupon_scope'));
        Schema::table('booking_rooms', fn (Blueprint $table) => $table->dropColumn(['support_amount', 'support_reason']));
        Schema::dropIfExists('booking_room_periods');
    }
};
