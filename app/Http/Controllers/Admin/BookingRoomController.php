<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Support\Realtime;
use App\Services\BookingRepricingService;
use App\Services\BookingFinancialService;

class BookingRoomController extends Controller
{
    public function assignRooms(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Chỉ có thể đổi phòng khi booking chưa check-in.');
        }

        $data = $request->validate([
            'room_ids' => 'required|array',
            'room_ids.*' => 'exists:rooms,id',
        ]);

        if (count($data['room_ids']) != $booking->room_quantity) {
            return back()->with('error', 'Bạn phải chọn đúng ' . $booking->room_quantity . ' phòng cho booking này.');
        }

        $booking->load('bookingRooms.room', 'roomCategory');

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking chưa có thời gian nhận/trả phòng nên không thể kiểm tra phòng trống.');
        }

        $selectedRoomIds = collect($data['room_ids'])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (count($selectedRoomIds) !== count($data['room_ids'])) {
            return back()->with('error', 'Không được chọn trùng phòng trong cùng một booking.');
        }

        $validRoomCount = Room::whereIn('id', $selectedRoomIds)
            ->where('room_category_id', $booking->room_category_id)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->count();

        if ($validRoomCount != $booking->room_quantity) {
            return back()->with(
                'error',
                'Danh sách phòng chọn không hợp lệ. Có phòng đã bị đặt trùng thời gian, đang trong thời gian dọn phòng hoặc không thuộc đúng hạng phòng.'
            );
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
                throw new \RuntimeException('Booking vừa thay đổi trạng thái nên không thể gán/đổi phòng lúc này.');
            }

            $existingRows = BookingRoom::query()
                ->where('booking_id', $booking->id)
                ->with('room')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Khóa cả phòng cũ và phòng mới, sau đó kiểm tra lại tồn kho ngay
            // trong transaction để tránh hai lễ tân cùng gán một phòng.
            $roomIdsToLock = collect($selectedRoomIds)
                ->merge($existingRows->pluck('room_id'))
                ->filter()
                ->unique()
                ->values()
                ->all();
            Room::whereIn('id', $roomIdsToLock)->lockForUpdate()->get();

            $selectedRooms = Room::whereIn('id', $selectedRoomIds)
                ->where('room_category_id', $booking->room_category_id)
                ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($selectedRooms->count() !== $booking->room_quantity) {
                throw new \RuntimeException('Có phòng vừa được booking khác giữ. Vui lòng tải lại danh sách phòng và chọn lại.');
            }

            // Không xóa booking_rooms vì mã và dịch vụ theo phòng đang gắn vào ID
            // của từng dòng này. Chỉ cập nhật room_id để giữ nguyên toàn bộ liên kết.
            foreach ($selectedRoomIds as $index => $roomId) {
                $bookingRoom = $existingRows->get($index);
                $newRoom = $selectedRooms->get($roomId);
                if (!$newRoom) {
                    throw new \RuntimeException('Không tìm thấy phòng đã chọn.');
                }

                if (!$bookingRoom) {
                    $bookingRoom = BookingRoom::create([
                        'booking_id' => $booking->id,
                        'room_id' => $newRoom->id,
                        'adult_count' => 0,
                        'child_count' => 0,
                        'price_at_booking' => $booking->roomCategory->price ?? 0,
                        'surcharge' => 0,
                        'surcharge_reason' => null,
                        'created_at' => now(),
                    ]);
                } elseif ((int) $bookingRoom->room_id !== (int) $newRoom->id) {
                    $oldRoom = $bookingRoom->room;
                    $bookingRoom->update(['room_id' => $newRoom->id]);

                    BookingRoomChange::create([
                        'booking_id' => $booking->id,
                        'booking_room_id' => $bookingRoom->id,
                        'old_room_id' => $oldRoom?->id,
                        'new_room_id' => $newRoom->id,
                        'old_room_category_id' => $oldRoom?->room_category_id,
                        'new_room_category_id' => $newRoom->room_category_id,
                        'old_room_price' => (float) $bookingRoom->price_at_booking,
                        'new_room_price' => (float) $bookingRoom->price_at_booking,
                        'night_count' => max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date))),
                        'price_difference_total' => 0,
                        'change_source' => 'front_desk',
                        'reason' => 'Gán/đổi phòng trước check-in.',
                        'changed_by' => Auth::id(),
                    ]);

                    if ($oldRoom && !in_array((int) $oldRoom->id, $selectedRoomIds, true)) {
                        $oldRoom->update([
                            'status' => 'available',
                            'status_from' => null,
                            'status_until' => null,
                        ]);
                    }
                }

                $newRoom->update([
                    'status' => 'reserved',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);

            }

            // Gán được phòng không đồng nghĩa đã đủ cọc. Chỉ xác nhận booking
            // khi ledger thanh toán đã đạt mức cọc bắt buộc; nếu chưa thì giữ pending.
            $financials = app(\App\Services\BookingFinancialService::class);
            $financials->refreshPaymentStatus($booking->fresh());
            $booking->refresh();
            if (
                $financials->paidTotal($booking) + 0.01 >= $financials->requiredDeposit($booking)
                && $booking->status === 'pending'
            ) {
                $booking->update(['status' => 'confirmed']);
            }

            $newRoomNumbers = Room::whereIn('id', $selectedRoomIds)->orderBy('room_number')
                ->pluck('room_number')
                ->implode(', ');

            $this->addBookingLog(
                $booking,
                'assign_rooms',
                'Gán/cập nhật phòng cho booking: ' . $newRoomNumbers . '.'
            );

            DB::commit();

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with('success', 'Đổi phòng cho booking thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi đổi phòng: ' . $e->getMessage());
        }
    }

    public function changeRoom(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'old_room_id' => 'required|exists:rooms,id',
            'new_room_id' => 'required|exists:rooms,id|different:old_room_id',
            'old_room_new_status' => 'required|in:available,cleaning,maintenance',
            'change_reason' => 'required|string|max:255',
            'confirm_operation' => 'nullable|boolean',
            'operation_token' => 'required_if:confirm_operation,1|nullable|string|uuid',
        ]);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Trạng thái booking hiện tại không cho phép đổi phòng.');
        }

        if ($booking->status === 'checked_in' && $data['old_room_new_status'] === 'available') {
            return back()->with('error', 'Phòng khách vừa chuyển đi phải chuyển sang Cần dọn hoặc Bảo trì, không được mở bán ngay.');
        }

        $bookingRoom = BookingRoom::where('booking_id', $booking->id)
            ->where('room_id', $data['old_room_id'])
            ->first();

        if (!$bookingRoom) {
            return back()->with('error', 'Phòng cần đổi không thuộc booking này.');
        }

        $checkInAt = $booking->check_in_at;
        $checkOutAt = $booking->check_out_at;

        if (!$checkInAt || !$checkOutAt) {
            return back()->with('error', 'Booking chưa có thời gian check-in/check-out nên không thể kiểm tra phòng theo thời gian.');
        }

        $oldRoom = Room::findOrFail($data['old_room_id']);

        $newRoom = Room::where('id', $data['new_room_id'])
            ->where('room_category_id', $oldRoom->room_category_id)
            ->availableForPeriod($checkInAt, $checkOutAt, $booking->id)
            ->first();

        if (!$newRoom) {
            return back()->with(
                'error',
                'Không thể đổi phòng. Trong khoảng thời gian '
                . Carbon::parse($checkInAt)->format('d/m/Y H:i')
                . ' → '
                . Carbon::parse($checkOutAt)->format('d/m/Y H:i')
                . ', phòng thay thế đã được đặt hoặc không còn sẵn sàng.'
            );
        }

        // Mọi điều chỉnh phòng đều đi qua bước xem trước. Không thay đổi DB ở lượt đầu.
        if (!$request->boolean('confirm_operation')) {
            $booking->load([
                'bookingRooms.room.category',
                'serviceItems',
                'roomInspections.items',
                'payments',
            ]);

            $financial = app(BookingFinancialService::class);
            $nightCount = max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date)));
            $roomTotal = (float) $booking->bookingRooms->sum(
                fn (BookingRoom $room) => ((float) $room->price_at_booking * $nightCount) + (float) $room->surcharge
            );
            $serviceTotal = (float) $booking->serviceItems
                ->where('billing_status', 'confirmed')
                ->sum('total');
            $inspectionTotal = (float) $booking->roomInspections
                ->flatMap->items
                ->where('status', 'approved')
                ->sum('total');
            $paidTotal = $financial->paidTotal($booking);
            $total = $financial->currentTotal($booking);
            $requiredDeposit = $financial->requiredDeposit($booking);

            $snapshot = [
                'room_quantity' => $booking->bookingRooms->count(),
                'night_count' => $nightCount,
                'room_total' => round($roomTotal, 0),
                'service_total' => round($serviceTotal, 0),
                'inspection_total' => round($inspectionTotal, 0),
                'discount_total' => round((float) $booking->discount_amount, 0),
                'total' => round($total, 0),
                'required_deposit' => round($requiredDeposit, 0),
                'paid_total' => round($paidTotal, 0),
                'deposit_shortfall' => max(0, round($requiredDeposit - $paidTotal, 0)),
                'remaining' => max(0, round($total - $paidTotal, 0)),
            ];

            session()->put('booking_room_operation_preview', [
                'booking_id' => (int) $booking->id,
                'operation' => 'change_same_rank_room',
                'token' => (string) \Illuminate\Support\Str::uuid(),
                'title' => 'Xem trước đổi phòng cùng hạng',
                'message' => 'Phòng ' . $oldRoom->room_number
                    . ' → phòng ' . $newRoom->room_number
                    . ' (cùng hạng ' . ($oldRoom->category?->name ?? '---') . '). '
                    . 'Giá phòng, dịch vụ, mã ưu đãi và số tiền đã thanh toán không thay đổi. '
                    . 'Sau khi xác nhận, phòng cũ chuyển sang trạng thái '
                    . ['available' => 'Trống', 'cleaning' => 'Cần dọn', 'maintenance' => 'Bảo trì'][$data['old_room_new_status']]
                    . '.',
                'action_url' => route('admin.bookings.change-room', $booking->id),
                'http_method' => 'POST',
                'payload' => collect($data)
                    ->except(['confirm_operation', 'operation_token', '_token', '_method'])
                    ->all(),
                'before' => $snapshot,
                'after' => $snapshot,
                'promotion_changes' => [],
                'service_changes' => [],
            ]);
            session()->flashInput($data);

            return redirect(route('admin.bookings.show', $booking) . '#room-operation-preview');
        }

        $operationToken = (string) $data['operation_token'];
        $preview = session('booking_room_operation_preview');
        if (!is_array($preview)
            || (int) ($preview['booking_id'] ?? 0) !== (int) $booking->id
            || (string) ($preview['operation'] ?? '') !== 'change_same_rank_room'
            || !hash_equals((string) ($preview['token'] ?? ''), $operationToken)) {
            return back()->with('error', 'Bản xem trước đổi phòng đã hết hiệu lực hoặc bị thay thế. Hãy xem trước lại trước khi xác nhận.');
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true)) {
                throw new \RuntimeException('Booking vừa thay đổi trạng thái nên không thể đổi phòng lúc này.');
            }

            if (BookingLog::where('booking_id', $booking->id)
                ->where('description', 'like', '%[room-op:' . $operationToken . ']%')
                ->exists()) {
                DB::commit();
                session()->forget('booking_room_operation_preview');
                return back()->with('info', 'Thao tác đổi phòng này đã được xử lý trước đó; hệ thống không thực hiện lặp lần hai.');
            }

            if ($booking->status === 'checked_in' && $data['old_room_new_status'] === 'available') {
                throw new \RuntimeException('Phòng khách vừa chuyển đi phải được dọn hoặc bảo trì trước khi mở bán lại.');
            }

            $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                ->where('room_id', $data['old_room_id'])
                ->lockForUpdate()
                ->first();
            if (!$bookingRoom) {
                throw new \RuntimeException('Phòng cần đổi không còn thuộc booking này.');
            }

            Room::whereIn('id', [(int) $data['old_room_id'], (int) $data['new_room_id']])
                ->lockForUpdate()
                ->get();

            $oldRoom = Room::whereKey($data['old_room_id'])->firstOrFail();
            $newRoom = Room::where('id', $data['new_room_id'])
                ->where('room_category_id', $oldRoom->room_category_id)
                ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
                ->lockForUpdate()
                ->first();

            if (!$newRoom) {
                throw new \RuntimeException('Phòng mới vừa được booking khác giữ hoặc không còn hợp lệ. Vui lòng chọn lại.');
            }

            $bookingRoom->update([
                'room_id' => $newRoom->id,
                'surcharge_reason' => 'Đổi từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number . '. Lý do: ' . $data['change_reason'],
            ]);

            BookingRoomChange::create([
                'booking_id' => $booking->id,
                'booking_room_id' => $bookingRoom->id,
                'old_room_id' => $oldRoom->id,
                'new_room_id' => $newRoom->id,
                'old_room_category_id' => $oldRoom->room_category_id,
                'new_room_category_id' => $newRoom->room_category_id,
                'old_room_price' => (float) $bookingRoom->price_at_booking,
                'new_room_price' => (float) $bookingRoom->price_at_booking,
                'night_count' => max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date))),
                'price_difference_total' => 0,
                'change_source' => 'front_desk',
                'reason' => $data['change_reason'],
                'changed_by' => Auth::id(),
            ]);

            $oldStatus = $data['old_room_new_status'];
            $oldRoom->update([
                'status' => $oldStatus,
                'status_from' => $oldStatus === 'available' ? null : now('Asia/Ho_Chi_Minh'),
                'status_until' => null,
            ]);

            $newRoom->update([
                'status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved',
                'status_from' => now('Asia/Ho_Chi_Minh'),
                'status_until' => null,
            ]);

            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Đổi từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number . '. Lý do: ' . $data['change_reason'],
            ]);

            $this->addBookingLog(
                $booking,
                'change_room',
                'Đổi từ phòng '
                . $oldRoom->room_number
                . ' sang phòng '
                . $newRoom->room_number
                . '. Lý do: '
                . $data['change_reason']
                . '. Trạng thái phòng cũ: '
                . $data['old_room_new_status']
                . '. [room-op:' . $operationToken . ']'
            );

            session()->forget('booking_room_operation_preview');
            DB::commit();

            return back()->with('success', 'Đổi phòng thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi đổi phòng: ' . $e->getMessage());
        }
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        abort_unless($booking->canBeHandledBy($user), 403, 'Booking này đang được phân cho lễ tân khác hoặc bạn không có quyền xử lý.');
    }

    private function addBookingLog(Booking $booking, string $action, string $description): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}