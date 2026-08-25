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
use App\Services\HotelPolicyService;
use App\Services\RoomReservationStatusService;
use App\Mail\RoomSelectionResultMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingRoomController extends Controller
{
    public function resolveManualRoomSelection(
        Request $request,
        Booking $booking,
        BookingRepricingService $repricingService
    ) {
        $this->guardCanAccessBooking($booking);

        if ($booking->room_selection_mode !== 'manual') {
            return back()->with('error', 'Booking này không có yêu cầu chọn phòng thủ công.');
        }

        if ($booking->room_selection_status !== 'pending') {
            return back()->with('error', 'Yêu cầu chọn phòng này đã được xử lý trước đó.');
        }

        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Chỉ xử lý yêu cầu chọn phòng trước khi khách check-in.');
        }

        $data = $request->validate([
            'decision' => 'required|in:fulfilled,unfulfilled',
            'selected_room_ids' => 'nullable|required_if:decision,fulfilled|array',
            'selected_room_ids.*' => 'integer|distinct|exists:rooms,id',
            'handling_note' => 'nullable|required_if:decision,unfulfilled|string|max:1000',
        ], [
            'selected_room_ids.required_if' => 'Vui lòng chọn phòng khi xác nhận đáp ứng yêu cầu.',
            'selected_room_ids.*.distinct' => 'Danh sách phòng bị trùng.',
            'handling_note.required_if' => 'Vui lòng ghi lý do khi không thể đáp ứng yêu cầu của khách.',
        ]);

        $selectedRoomIds = collect($data['selected_room_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($data['decision'] === 'fulfilled' && $selectedRoomIds->count() !== (int) $booking->room_quantity) {
            return back()->with('error', 'Phải chọn đúng ' . $booking->room_quantity . ' phòng cho booking.');
        }

        $result = null;

        try {
            DB::transaction(function () use (
                $booking,
                $data,
                $selectedRoomIds,
                $repricingService,
                &$result
            ) {
                $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

                if ($lockedBooking->room_selection_mode !== 'manual'
                    || $lockedBooking->room_selection_status !== 'pending') {
                    throw new \RuntimeException('Yêu cầu chọn phòng vừa được người khác xử lý.');
                }

                if (!in_array($lockedBooking->status, ['pending', 'confirmed'], true)) {
                    throw new \RuntimeException('Booking vừa thay đổi trạng thái nên không thể chọn phòng lúc này.');
                }

                $lockedBooking->load(['bookingRooms.room.category', 'roomCategory', 'serviceItems', 'payments']);

                if ($data['decision'] === 'fulfilled') {
                    $existingRows = BookingRoom::query()
                        ->where('booking_id', $lockedBooking->id)
                        ->with('room')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $roomIdsToLock = $selectedRoomIds
                        ->merge($existingRows->pluck('room_id'))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    Room::whereIn('id', $roomIdsToLock)->lockForUpdate()->get();

                    $selectedRooms = Room::query()
                        ->whereIn('id', $selectedRoomIds->all())
                        ->where('room_category_id', $lockedBooking->room_category_id)
                        ->availableForPeriod(
                            $lockedBooking->check_in_at,
                            $lockedBooking->check_out_at,
                            $lockedBooking->id
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    if ($selectedRooms->count() !== (int) $lockedBooking->room_quantity) {
                        throw new \RuntimeException('Có phòng vừa bị giữ/đặt hoặc không còn đúng hạng. Vui lòng tải lại và chọn lại.');
                    }

                    foreach ($selectedRoomIds as $index => $roomId) {
                        $bookingRoom = $existingRows->get($index);
                        $newRoom = $selectedRooms->get($roomId);

                        if (!$bookingRoom || !$newRoom) {
                            throw new \RuntimeException('Không tìm thấy dòng phòng cần cập nhật.');
                        }

                        $oldRoom = $bookingRoom->room;
                        if ((int) $bookingRoom->room_id !== (int) $newRoom->id) {
                            $bookingRoom->update(['room_id' => $newRoom->id]);

                            BookingRoomChange::create([
                                'booking_id' => $lockedBooking->id,
                                'booking_room_id' => $bookingRoom->id,
                                'old_room_id' => $oldRoom?->id,
                                'new_room_id' => $newRoom->id,
                                'old_room_category_id' => $oldRoom?->room_category_id,
                                'new_room_category_id' => $newRoom->room_category_id,
                                'old_room_price' => (float) $bookingRoom->price_at_booking,
                                'new_room_price' => (float) $bookingRoom->price_at_booking,
                                'night_count' => max(1, Carbon::parse($lockedBooking->check_in_date)->diffInDays(Carbon::parse($lockedBooking->check_out_date))),
                                'price_difference_total' => 0,
                                'change_source' => 'front_desk',
                                'reason' => 'Lễ tân chọn phòng theo yêu cầu của khách.',
                                'changed_by' => Auth::id(),
                            ]);

                            // Đổi phòng trước check-in chỉ đổi lịch gán trong booking_rooms;
                            // không đụng trạng thái vận hành hiện tại của phòng cũ.
                        }

                        // Phòng mới được giữ theo lịch booking, không đổi room.status trước check-in.
                    }

                    $unitFee = max(0, (float) app(HotelPolicyService::class)
                        ->forBooking($lockedBooking, 'booking.manual_room_selection_fee', 50000));
                    $fee = round($unitFee * max(1, (int) $lockedBooking->room_quantity), 0);

                    $lockedBooking->forceFill([
                        'room_selection_status' => 'fulfilled',
                        'room_selection_fee' => $fee,
                        'room_selection_handled_by' => Auth::id(),
                        'room_selection_handled_at' => now('Asia/Ho_Chi_Minh'),
                        'room_selection_handling_note' => trim((string) ($data['handling_note'] ?? '')),
                    ])->save();

                    // Đồng bộ lại Đã đặt/Trống cho cả phòng dự phòng cũ và phòng khách được chọn.
                    // Service chỉ đụng available/reserved nên không thể ghi đè occupied/cleaning/inspection/maintenance.
                    app(RoomReservationStatusService::class)->syncRoomIds($roomIdsToLock);

                    $lockedBooking->refresh()->load(['bookingRooms.room.category', 'serviceItems', 'payments']);
                    $oneNightRoomTotal = (float) $lockedBooking->bookingRooms->sum('price_at_booking');
                    $preview = $repricingService->preview(
                        $lockedBooking,
                        Carbon::parse($lockedBooking->check_in_at, 'Asia/Ho_Chi_Minh'),
                        Carbon::parse($lockedBooking->check_out_at, 'Asia/Ho_Chi_Minh'),
                        $oneNightRoomTotal
                    );
                    $repricingService->apply($lockedBooking, $preview);

                    $roomNumbers = Room::whereIn('id', $selectedRoomIds->all())
                        ->orderBy('room_number')
                        ->pluck('room_number')
                        ->implode(', ');

                    $this->addBookingLog(
                        $lockedBooking,
                        'manual_room_selection_fulfilled',
                        'Đã đáp ứng yêu cầu chọn phòng thủ công. Phòng được chọn: '
                        . $roomNumbers . '. Phí đảm bảo yêu cầu phòng: '
                        . number_format($fee, 0, ',', '.') . 'đ.'
                        . (!empty($data['handling_note']) ? ' Ghi chú: ' . $data['handling_note'] : '')
                    );

                    $result = ['fulfilled' => true, 'room_numbers' => $roomNumbers, 'fee' => $fee];
                } else {
                    $lockedBooking->forceFill([
                        'room_selection_status' => 'awaiting_guest',
                        'room_selection_fee' => 0,
                        'room_selection_handled_by' => Auth::id(),
                        'room_selection_handled_at' => now('Asia/Ho_Chi_Minh'),
                        'room_selection_handling_note' => trim((string) ($data['handling_note'] ?? '')),
                        'room_selection_guest_decided_at' => null,
                    ])->save();

                    $fallbackRoomNumbers = $lockedBooking->bookingRooms
                        ->pluck('room.room_number')
                        ->filter()
                        ->values()
                        ->implode(', ');

                    $this->addBookingLog(
                        $lockedBooking,
                        'manual_room_selection_awaiting_guest',
                        'Không thể đáp ứng yêu cầu chọn phòng thủ công. Đã chuyển sang chờ khách xác nhận phòng dự phòng; không thu phí. Lý do: '
                        . trim((string) ($data['handling_note'] ?? ''))
                    );

                    $result = ['fulfilled' => false, 'room_numbers' => $fallbackRoomNumbers, 'fee' => 0];
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không thể xử lý yêu cầu chọn phòng: ' . $e->getMessage());
        }

        $booking->refresh()->load(['customer', 'bookingRooms.room']);

        try {
            if ($booking->booked_customer_email) {
                Mail::to($booking->booked_customer_email)->send(new RoomSelectionResultMail(
                    $booking,
                    (bool) ($result['fulfilled'] ?? false),
                    (string) ($data['handling_note'] ?? '')
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email kết quả chọn phòng.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        Realtime::booking($booking, 'manual_room_selection_resolved', false);

        return back()->with(
            'success',
            ($result['fulfilled'] ?? false)
                ? 'Đã chọn phòng theo yêu cầu, tính lại tổng tiền và gửi email cho khách.'
                : 'Đã ghi nhận không thể đáp ứng. Phòng dự phòng vẫn được giữ, không thu phí và khách đã được yêu cầu xác nhận Đồng ý/Từ chối.'
        );
    }

    public function markRoomSelectionRefundCompleted(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->refund_status !== 'pending' || (float) ($booking->refund_due_amount ?? 0) <= 0) {
            return back()->with('error', 'Booking này không có khoản hoàn cọc đang chờ xử lý.');
        }

        if ($booking->room_selection_status !== 'fallback_declined' || $booking->status !== 'cancelled') {
            return back()->with('error', 'Chỉ xác nhận hoàn tiền cho booking đã hủy do khách từ chối phòng dự phòng.');
        }

        $data = $request->validate([
            'refund_note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($booking, $data) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->refund_status !== 'pending' || (float) $locked->refund_due_amount <= 0) {
                throw new \RuntimeException('Khoản hoàn tiền vừa được người khác xử lý.');
            }

            $locked->forceFill([
                'refund_status' => 'completed',
                'refund_processed_at' => now('Asia/Ho_Chi_Minh'),
                'refund_processed_by' => Auth::id(),
            ])->save();

            $this->addBookingLog(
                $locked,
                'manual_room_refund_completed',
                'Đã xác nhận hoàn lại ' . number_format((float) $locked->refund_due_amount, 0, ',', '.')
                . 'đ cho khách do khách sạn không đáp ứng yêu cầu phòng.'
                . (!empty($data['refund_note']) ? ' Ghi chú: ' . trim((string) $data['refund_note']) : '')
            );
        });

        Realtime::booking($booking->fresh(), 'manual_room_refund_completed', false);

        return back()->with('success', 'Đã đánh dấu khoản hoàn cọc là hoàn tất.');
    }

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

                    // Không giải phóng room.status khi đổi lịch gán trước check-in;
                    // phòng cũ có thể đang occupied/cleaning cho nghiệp vụ hiện tại.
                }

                // booking_rooms là nguồn giữ phòng theo thời gian; không đổi room.status
                // trước check-in để tránh ghi đè trạng thái vận hành hiện tại.

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

            app(RoomReservationStatusService::class)->syncRoomIds($roomIdsToLock);

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
            'room_assignment_mode' => 'required|in:auto,manual',
            'new_room_id' => 'nullable|required_if:room_assignment_mode,manual|exists:rooms,id|different:old_room_id',
            'old_room_new_status' => 'nullable|in:available,cleaning,maintenance',
            'change_reason' => 'required|string|max:255',
            'confirm_operation' => 'nullable|boolean',
            'operation_token' => 'required_if:confirm_operation,1|nullable|string|uuid',
        ], [
            'new_room_id.required_if' => 'Vui lòng chọn phòng cụ thể khi dùng chế độ chọn thủ công.',
        ]);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true)) {
            return back()->with('error', 'Trạng thái booking hiện tại không cho phép đổi phòng.');
        }

        $booking->load('bookingRooms.room.category');
        $bookingRoom = $booking->bookingRooms->firstWhere('room_id', (int) $data['old_room_id']);
        if (!$bookingRoom || !$bookingRoom->room) {
            return back()->with('error', 'Phòng cần đổi không thuộc booking này.');
        }

        if ($booking->status === 'checked_in') {
            if (empty($data['old_room_new_status'])) {
                return back()->with('error', 'Vui lòng chọn trạng thái xử lý cho phòng khách vừa chuyển đi.');
            }
            if ($data['old_room_new_status'] === 'available') {
                return back()->with('error', 'Phòng khách vừa chuyển đi phải chuyển sang Cần dọn hoặc Bảo trì, không được mở bán ngay.');
            }
        } else {
            // Trước check-in, room.status là trạng thái vận hành hiện tại và không được thay đổi.
            $data['old_room_new_status'] = 'available';
        }

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking chưa có thời gian check-in/check-out nên không thể kiểm tra phòng theo thời gian.');
        }

        // Ở lượt xác nhận phải dùng đúng dữ liệu đã preview, không tin hidden field bị sửa tay.
        if ($request->boolean('confirm_operation')) {
            $preview = session('booking_room_operation_preview');
            if (!is_array($preview)
                || (int) ($preview['booking_id'] ?? 0) !== (int) $booking->id
                || (string) ($preview['operation'] ?? '') !== 'change_same_rank_room'
                || !hash_equals((string) ($preview['token'] ?? ''), (string) $data['operation_token'])) {
                return back()->with('error', 'Bản xem trước đổi phòng đã hết hiệu lực hoặc bị thay thế. Hãy xem trước lại trước khi xác nhận.');
            }

            $previewPayload = is_array($preview['payload'] ?? null) ? $preview['payload'] : [];
            foreach (['old_room_id', 'room_assignment_mode', 'new_room_id', 'old_room_new_status', 'change_reason'] as $key) {
                if (array_key_exists($key, $previewPayload)) {
                    $data[$key] = $previewPayload[$key];
                }
            }

            $bookingRoom = $booking->bookingRooms->firstWhere('room_id', (int) $data['old_room_id']);
            if (!$bookingRoom || !$bookingRoom->room) {
                return back()->with('error', 'Phòng cần đổi không còn thuộc booking này.');
            }
        }

        if (!$request->boolean('confirm_operation') && ($data['room_assignment_mode'] ?? 'auto') === 'auto') {
            // Không cho request craft ép một phòng cụ thể trong chế độ hệ thống tự chọn.
            $data['new_room_id'] = null;
        }

        $oldRoom = $bookingRoom->room;
        $assignedRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $candidateQuery = Room::query()
            ->where('room_category_id', $oldRoom->room_category_id)
            ->whereNotIn('id', $assignedRoomIds)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id);

        if ($booking->status === 'checked_in') {
            $candidateQuery->where('status', 'available');
        }

        if (($data['room_assignment_mode'] ?? 'auto') === 'auto' && empty($data['new_room_id'])) {
            $autoRoom = (clone $candidateQuery)->inRandomOrder()->first();
            if (!$autoRoom) {
                return back()->with('error', 'Không còn phòng cùng hạng phù hợp trong thời gian booking.');
            }
            $data['new_room_id'] = (int) $autoRoom->id;
        }

        $newRoom = (clone $candidateQuery)
            ->whereKey((int) $data['new_room_id'])
            ->first();

        if (!$newRoom) {
            return back()->with(
                'error',
                'Phòng thay thế không hợp lệ: có thể sai hạng, đang bận/được giữ, hoặc đã thuộc chính booking này.'
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
            $manualChangeSelectionFee = $financial->manualRoomChangeSelectionFee(
                $booking,
                (string) ($data['room_assignment_mode'] ?? 'auto'),
                1
            );

            $snapshot = [
                'room_quantity' => $booking->bookingRooms->count(),
                'night_count' => $nightCount,
                'room_total' => round($roomTotal, 0),
                'service_total' => round($serviceTotal, 0),
                'inspection_total' => round($inspectionTotal, 0),
                'manual_room_selection_fee' => round((float) ($booking->room_selection_fee ?? 0), 0),
                'discount_total' => round((float) $booking->discount_amount, 0),
                'total' => round($total, 0),
                'required_deposit' => round($requiredDeposit, 0),
                'paid_total' => round($paidTotal, 0),
                'deposit_shortfall' => max(0, round($requiredDeposit - $paidTotal, 0)),
                'remaining' => max(0, round($total - $paidTotal, 0)),
            ];

            $afterSnapshot = $snapshot;
            if ($manualChangeSelectionFee > 0) {
                $afterSnapshot['manual_room_selection_fee'] = round(
                    (float) $snapshot['manual_room_selection_fee'] + $manualChangeSelectionFee,
                    0
                );
                $afterSnapshot['total'] = round((float) $snapshot['total'] + $manualChangeSelectionFee, 0);
                $afterSnapshot['required_deposit'] = round(
                    (float) $snapshot['required_deposit']
                    + ($manualChangeSelectionFee * app(HotelPolicyService::class)->depositRate($booking)),
                    0
                );
                $afterSnapshot['deposit_shortfall'] = max(0, round((float) $afterSnapshot['required_deposit'] - $paidTotal, 0));
                $afterSnapshot['remaining'] = max(0, round((float) $afterSnapshot['total'] - $paidTotal, 0));
            }

            $modeLabel = ($data['room_assignment_mode'] ?? 'auto') === 'manual'
                ? 'Lễ tân chọn thủ công'
                : 'Hệ thống tự chọn';
            $statusNote = $booking->status === 'checked_in'
                ? ' Sau khi xác nhận, phòng cũ chuyển sang ' . ['cleaning' => 'Cần dọn', 'maintenance' => 'Bảo trì'][$data['old_room_new_status']] . '.'
                : ' Booking chưa check-in nên trạng thái vận hành hiện tại của phòng cũ/mới không bị thay đổi.';

            session()->put('booking_room_operation_preview', [
                'booking_id' => (int) $booking->id,
                'operation' => 'change_same_rank_room',
                'token' => (string) \Illuminate\Support\Str::uuid(),
                'title' => 'Xem trước đổi phòng cùng hạng',
                'message' => $modeLabel . ': phòng ' . $oldRoom->room_number
                    . ' → phòng ' . $newRoom->room_number
                    . ' (cùng hạng ' . ($oldRoom->category?->name ?? '---') . '). '
                    . 'Giá phòng, dịch vụ và mã ưu đãi không thay đổi.'
                    . ($manualChangeSelectionFee > 0
                        ? ' Khách đang lưu trú và chọn chính xác phòng nên phát sinh phí đảm bảo yêu cầu phòng +'
                            . number_format($manualChangeSelectionFee, 0, ',', '.') . 'đ.'
                        : ' Không phát sinh phí chọn phòng vì hệ thống tự chọn hoặc booking chưa ở.')
                    . $statusNote,
                'action_url' => route('admin.bookings.change-room', $booking->id),
                'http_method' => 'POST',
                'payload' => collect($data)
                    ->except(['confirm_operation', 'operation_token', '_token', '_method'])
                    ->all(),
                'before' => $snapshot,
                'after' => $afterSnapshot,
                'promotion_changes' => [],
                'service_changes' => [],
            ]);
            session()->flashInput($data);

            return redirect(route('admin.bookings.show', $booking) . '#room-operation-preview');
        }

        $operationToken = (string) $data['operation_token'];
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

            $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                ->where('room_id', (int) $data['old_room_id'])
                ->lockForUpdate()
                ->first();
            if (!$bookingRoom) {
                throw new \RuntimeException('Phòng cần đổi không còn thuộc booking này.');
            }

            $currentAssignedIds = BookingRoom::where('booking_id', $booking->id)
                ->pluck('room_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();
            if (in_array((int) $data['new_room_id'], $currentAssignedIds, true)) {
                throw new \RuntimeException('Phòng mới đã thuộc booking hiện tại; không được dùng một phòng thay thế cho hai dòng phòng.');
            }

            Room::whereIn('id', [(int) $data['old_room_id'], (int) $data['new_room_id']])
                ->lockForUpdate()
                ->get();

            $oldRoom = Room::whereKey((int) $data['old_room_id'])->firstOrFail();
            $newRoomQuery = Room::query()
                ->whereKey((int) $data['new_room_id'])
                ->where('room_category_id', $oldRoom->room_category_id)
                ->whereNotIn('id', $currentAssignedIds)
                ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id);
            if ($booking->status === 'checked_in') {
                $newRoomQuery->where('status', 'available');
            }
            $newRoom = $newRoomQuery->lockForUpdate()->first();

            if (!$newRoom) {
                throw new \RuntimeException('Phòng mới vừa được booking khác giữ, sai hạng hoặc không còn sẵn sàng. Vui lòng xem trước lại.');
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

            if ($booking->status === 'checked_in') {
                $oldStatus = $data['old_room_new_status'];
                if (!in_array($oldStatus, ['cleaning', 'maintenance'], true)) {
                    throw new \RuntimeException('Phòng cũ sau khi khách chuyển phải là Cần dọn hoặc Bảo trì.');
                }
                $oldRoom->update([
                    'status' => $oldStatus,
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);
                $newRoom->update([
                    'status' => 'occupied',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);
            } else {
                app(RoomReservationStatusService::class)->syncRoomIds([$oldRoom->id, $newRoom->id]);
            }

            $financial = app(BookingFinancialService::class);
            $manualChangeSelectionFee = $financial->addManualRoomChangeSelectionFee(
                $booking,
                (string) ($data['room_assignment_mode'] ?? 'auto'),
                1
            );
            if ($manualChangeSelectionFee > 0) {
                $this->repriceCurrentBooking($booking);
                $booking->refresh();
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->update([
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Đổi từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number . '. Lý do: ' . $data['change_reason'],
            ]);

            $statusLog = $booking->status === 'checked_in'
                ? '. Trạng thái phòng cũ: ' . $data['old_room_new_status']
                : '. Booking chưa check-in: giữ nguyên trạng thái vận hành của phòng';
            $this->addBookingLog(
                $booking,
                'change_room',
                'Đổi từ phòng ' . $oldRoom->room_number
                . ' sang phòng ' . $newRoom->room_number
                . '. Chế độ: ' . (($data['room_assignment_mode'] ?? 'auto') === 'manual' ? 'chọn thủ công' : 'hệ thống tự chọn')
                . '. Lý do: ' . $data['change_reason']
                . ($manualChangeSelectionFee > 0
                    ? '. Phí chọn phòng thủ công: +' . number_format($manualChangeSelectionFee, 0, ',', '.') . 'đ'
                    : '. Phí chọn phòng: 0đ')
                . $statusLog
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

    private function repriceCurrentBooking(Booking $booking): void
    {
        $booking->refresh()->load([
            'bookingRooms.room.category',
            'bookingPromotions.promotion.serviceOffers.service',
            'bookingPromotions.promotion.roomUpgradeOffers',
            'bookingPromotions.serviceOffers',
            'bookingPromotions.roomUpgradeOffers.offer',
            'serviceItems.service',
            'payments',
            'customer',
            'guests',
            'roomChanges',
        ]);

        $oneNightRoomTotal = (float) $booking->bookingRooms->sum(
            fn (BookingRoom $room) => (float) $room->price_at_booking
        );

        $repricing = app(BookingRepricingService::class);
        $preview = $repricing->preview(
            $booking,
            Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
            Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh'),
            $oneNightRoomTotal
        );
        $repricing->apply($booking, $preview);
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