<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomActionLog;
use App\Models\RoomCategory;
use App\Services\RoomReservationStatusService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index(Request $request)
    {
        $now = now(self::TIMEZONE);
        Room::whereIn('status', ['cleaning', 'inspection', 'maintenance'])
            ->whereNotNull('status_until')
            ->where('status_until', '<=', $now)
            ->update(['status' => 'available', 'status_from' => null, 'status_until' => null]);

        // Tự-heal trạng thái Đã đặt/Trống từ lịch booking đang còn hiệu lực.
        // Chỉ đụng available/reserved, tuyệt đối không ghi đè occupied/cleaning/inspection/maintenance.
        app(RoomReservationStatusService::class)->syncAll();

        $today = $now->copy()->startOfDay();

        // Mặc định hiển thị xuyên suốt toàn bộ lịch sử sử dụng phòng:
        // từ booking sớm nhất đến booking xa nhất trong tương lai.
        $earliestBookingAt = Booking::query()->min('check_in_at');
        $latestPlannedBookingAt = Booking::query()->max('check_out_at');
        $latestActualBookingAt = Booking::query()->whereNotNull('actual_check_out')->max('actual_check_out');
        $latestBookingAt = collect([$latestPlannedBookingAt, $latestActualBookingAt])
            ->filter()
            ->max();
        $defaultStart = $earliestBookingAt
            ? Carbon::parse($earliestBookingAt, self::TIMEZONE)->startOfDay()
            : $today->copy();
        $defaultEnd = $latestBookingAt
            ? Carbon::parse($latestBookingAt, self::TIMEZONE)->startOfDay()
            : $today->copy()->addDays(6);

        if ($defaultStart->gt($today)) {
            $defaultStart = $today->copy();
        }
        if ($defaultEnd->lt($today)) {
            $defaultEnd = $today->copy();
        }

        $startDate = $this->safeDate($request->input('start_date'), $defaultStart);
        $endDate = $this->safeDate($request->input('end_date'), $defaultEnd);

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $roomQuery = Room::with('category');
        if ($request->filled('room_number')) {
            $roomQuery->where('room_number', 'like', '%' . trim($request->room_number) . '%');
        }
        if ($request->filled('floor_number')) {
            $roomQuery->where('floor_number', $request->floor_number);
        }
        if ($request->filled('room_category_id')) {
            $roomQuery->where('room_category_id', $request->room_category_id);
        }

        $rooms = $roomQuery
            ->orderByDesc('floor_number')
            ->orderByRaw('CAST(room_number AS UNSIGNED) ASC')
            ->orderBy('room_number')
            ->get();

        $dates = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn (Carbon $date) => $date->copy());

        $bookings = Booking::with(['customer', 'bookingRooms'])
            ->whereIn('status', [
                'pending', 'confirmed', 'checked_in', 'inspection_requested',
                'checked_out', 'completed',
            ])
            ->where(function ($period) use ($startDate, $endDate, $now) {
                $period->where(function ($planned) use ($startDate, $endDate) {
                    $planned->whereDate('check_in_date', '<=', $endDate->toDateString())
                        ->whereDate('check_out_date', '>=', $startDate->toDateString());
                })->orWhere(function ($activeLate) use ($endDate, $now) {
                    // Khách chưa trả dù đã quá giờ dự kiến vẫn phải tiếp tục chiếm lịch
                    // đến thời điểm hiện tại, kể cả đã sang ngày mới.
                    $activeLate->whereIn('status', ['checked_in', 'inspection_requested'])
                        ->whereDate('check_in_date', '<=', $endDate->toDateString())
                        ->where('check_out_at', '<', $now);
                })->orWhere(function ($completedActual) use ($startDate, $endDate) {
                    // Lịch sử trả muộn phải kéo tới actual_check_out chứ không dừng ở
                    // check_out_date dự kiến.
                    $completedActual->whereIn('status', ['checked_out', 'completed'])
                        ->whereNotNull('actual_check_out')
                        ->whereDate('check_in_date', '<=', $endDate->toDateString())
                        ->whereDate('actual_check_out', '>=', $startDate->toDateString());
                });
            })
            ->get();

        $lateCheckoutBookingMap = [];
        $activeLateBookings = Booking::with(['customer', 'bookingRooms'])
            ->whereIn('status', ['checked_in', 'inspection_requested'])
            ->whereNotNull('check_out_at')
            ->where('check_out_at', '<', $now)
            ->get();
        foreach ($activeLateBookings as $lateBooking) {
            foreach ($lateBooking->bookingRooms as $bookingRoom) {
                $lateCheckoutBookingMap[(int) $bookingRoom->room_id] = $lateBooking;
            }
        }

        $bookingMap = [];
        foreach ($bookings as $booking) {
            foreach ($booking->bookingRooms as $bookingRoom) {
                $bookingMap[$bookingRoom->room_id][] = $booking;
            }
        }

        $timeline = [];
        foreach ($rooms as $room) {
            foreach ($dates as $date) {
                $timeline[$room->id][$date->toDateString()] = $this->buildCell(
                    $room,
                    $date,
                    $bookingMap[$room->id] ?? [],
                    $now
                );
            }
        }

        if ($request->filled('timeline_status')) {
            $wantedStatus = $request->timeline_status;
            $rooms = $rooms->filter(function (Room $room) use ($timeline, $wantedStatus) {
                return collect($timeline[$room->id] ?? [])->contains(
                    fn (array $cell) => $cell['status'] === $wantedStatus
                );
            })->values();
        }

        $roomCategories = RoomCategory::orderBy('name')->get();
        $activeCategories = $roomCategories->where('status', 'active')->values();
        $floors = Room::query()->whereNotNull('floor_number')->distinct()->orderByDesc('floor_number')->pluck('floor_number');

        $summary = [
            'total' => $rooms->count(),
            'available' => 0,
            'reserved' => 0,
            'occupied' => 0,
            'late_checkout' => 0,
            'inspection' => 0,
            'cleaning' => 0,
            'maintenance' => 0,
        ];
        $summaryDate = $today->betweenIncluded($startDate, $endDate)
            ? $today->toDateString()
            : $startDate->toDateString();
        foreach ($rooms as $room) {
            $status = $timeline[$room->id][$summaryDate]['status'] ?? 'available';
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return view('admin.pages.rooms.index', compact(
            'rooms', 'dates', 'timeline', 'summary', 'summaryDate',
            'startDate', 'endDate', 'today', 'now', 'defaultStart', 'defaultEnd',
            'roomCategories', 'activeCategories', 'floors', 'lateCheckoutBookingMap'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_number' => 'required|max:20|unique:rooms,room_number',
            'room_category_id' => ['required', Rule::exists('room_categories', 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'floor_number' => 'nullable|integer|min:0',
            'status' => 'required|in:available,cleaning,inspection,maintenance',
            'note' => 'nullable|string|max:1000',
        ]);

        Room::create($data);

        return redirect()->route('admin.rooms.index', ['tab' => 'catalog'])
            ->with('success', 'Thêm phòng thành công.');
    }

    public function show(Room $room)
    {
        $room->load(['category', 'bookingRooms.booking.customer']);
        return view('admin.pages.rooms.show', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'room_number' => 'required|max:20|unique:rooms,room_number,' . $room->id,
            'room_category_id' => ['required', Rule::exists('room_categories', 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'floor_number' => 'nullable|integer|min:0',
            'status' => 'required|in:available,reserved,occupied,cleaning,inspection,maintenance',
            'note' => 'nullable|string|max:1000',
        ]);

        if ((int) $data['room_category_id'] !== (int) $room->room_category_id) {
            $hasActiveBooking = $room->bookingRooms()
                ->whereHas('booking', fn ($query) => $query->activeForOperations())
                ->exists();

            if ($hasActiveBooking) {
                return back()->withInput()->with('error', 'Phòng đang thuộc một booking hoạt động nên chưa thể đổi hạng phòng. Hãy kết thúc/hủy booking hoặc đổi phòng bằng nghiệp vụ booking trước.');
            }
        }

        if (in_array($room->status, ['reserved', 'occupied'], true) && $data['status'] !== $room->status) {
            $data['status'] = $room->status;
        } elseif (!in_array($room->status, ['reserved', 'occupied'], true)
            && in_array($data['status'], ['reserved', 'occupied'], true)) {
            return back()->withInput()->with('error', 'Trạng thái Đã giữ/Đang ở do hệ thống booking quản lý, không thể đặt thủ công.');
        }

        // Trạng thái sửa thủ công không được giữ status_until cũ. Nếu một phòng
        // từng có thời hạn bảo trì/dọn đã hết, status_until cũ sẽ khiến index
        // vừa redirect xong tự đổi phòng về "Sẵn sàng", tạo cảm giác sửa không lưu.
        if (!in_array($room->status, ['reserved', 'occupied'], true)) {
            if (in_array($data['status'], ['cleaning', 'inspection', 'maintenance'], true)) {
                $data['status_from'] = now(self::TIMEZONE);
                $data['status_until'] = null;
            } elseif ($data['status'] === 'available') {
                $data['status_from'] = null;
                $data['status_until'] = null;
            }
        }

        $room->update($data);
        $room->refresh();

        return redirect()->route('admin.rooms.index', [
            'tab' => 'catalog',
            'updated_room' => $room->id,
        ])->with('success', 'Đã cập nhật phòng ' . $room->room_number . '.');
    }

    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate([
            'status' => 'required|in:available,reserved,occupied,cleaning,inspection,maintenance',
            'status_from' => 'nullable|string',
            'status_until' => 'nullable|string',
            'note' => 'nullable|string|max:500',
        ]);

        if (in_array($room->status, ['reserved', 'occupied'], true) && $data['status'] !== $room->status) {
            return back()->with('error', 'Phòng đang được giữ/đang có khách. Trạng thái này phải được thay đổi qua nghiệp vụ booking, không đổi thủ công.');
        }

        if (!in_array($room->status, ['reserved', 'occupied'], true)
            && in_array($data['status'], ['reserved', 'occupied'], true)) {
            return back()->with('error', 'Trạng thái Đã giữ/Đang ở do hệ thống booking quản lý, không thể đặt thủ công.');
        }

        $statusFromInput = trim((string) ($data['status_from'] ?? ''));
        $statusUntilInput = trim((string) ($data['status_until'] ?? ''));
        $statusFrom = $this->parseVietnameseDateTime($statusFromInput ?: null);
        $statusUntil = $this->parseVietnameseDateTime($statusUntilInput ?: null);

        if ($statusFromInput !== '' && !$statusFrom) {
            throw ValidationException::withMessages(['status_from' => 'Thời gian bắt đầu phải đúng định dạng ngày/tháng/năm giờ:phút.']);
        }
        if ($statusUntilInput !== '' && !$statusUntil) {
            throw ValidationException::withMessages(['status_until' => 'Thời gian kết thúc phải đúng định dạng ngày/tháng/năm giờ:phút.']);
        }
        if ($statusFrom && $statusUntil && Carbon::parse($statusUntil, self::TIMEZONE)->lte(Carbon::parse($statusFrom, self::TIMEZONE))) {
            throw ValidationException::withMessages(['status_until' => 'Thời gian kết thúc phải sau thời gian bắt đầu.']);
        }
        if ($data['status'] === 'available') {
            $statusFrom = $statusUntil = null;
        }

        $oldStatus = $room->status;
        $room->update([
            'status' => $data['status'],
            'status_from' => $statusFrom,
            'status_until' => $statusUntil,
            'note' => $data['note'] ?? $room->note,
        ]);

        if ($oldStatus !== $data['status']) {
            RoomActionLog::create([
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'action_type' => 'status_change',
                'action_time' => now(),
                'note' => "Chuyển trạng thái từ {$oldStatus} sang {$data['status']}" .
                    (!empty($data['note']) ? '. Lý do: ' . $data['note'] : ''),
            ]);
        }

        return back()->with('success', 'Cập nhật trạng thái phòng thành công.');
    }

    public function destroy(Room $room)
    {
        if ($room->bookingRooms()->exists()) {
            return back()->with(
                'error',
                'Phòng đã có lịch sử booking nên không thể xóa vật lý. Hãy chuyển phòng sang Bảo trì nếu không muốn tiếp tục sử dụng.'
            );
        }

        $room->delete();
        return redirect()->route('admin.rooms.index', ['tab' => 'catalog'])
            ->with('success', 'Xóa phòng thành công.');
    }

    private function buildCell(Room $room, Carbon $date, array $bookings, Carbon $now): array
    {
        $today = $now->copy()->startOfDay();
        $dateStart = $date->copy()->startOfDay();
        $dateEnd = $date->copy()->endOfDay();

        $matched = collect($bookings)->filter(function (Booking $booking) use ($dateStart, $dateEnd, $now) {
            $plannedCheckIn = $booking->check_in_at
                ? Carbon::parse($booking->check_in_at, self::TIMEZONE)
                : Carbon::parse($booking->check_in_date . ' ' . $booking->standardCheckInTime(), self::TIMEZONE);
            $plannedCheckOut = $booking->check_out_at
                ? Carbon::parse($booking->check_out_at, self::TIMEZONE)
                : Carbon::parse($booking->check_out_date . ' ' . $booking->standardCheckOutTime(), self::TIMEZONE);

            // Lịch sử phòng phải phản ánh thời gian khách thực tế đã sử dụng phòng.
            // Booking đã trả sớm không được tiếp tục chiếm các ô đến ngày trả dự kiến.
            $checkIn = $booking->actual_check_in
                ? Carbon::parse($booking->actual_check_in, self::TIMEZONE)
                : $plannedCheckIn;
            if (in_array($booking->status, ['checked_out', 'completed'], true) && $booking->actual_check_out) {
                $checkOut = Carbon::parse($booking->actual_check_out, self::TIMEZONE);
            } elseif (in_array($booking->status, ['checked_in', 'inspection_requested'], true)
                && $now->greaterThan($plannedCheckOut)) {
                // Chưa trả phòng nhưng đã quá giờ: lịch vẫn bị chiếm tới hiện tại.
                $checkOut = $now->copy();
            } else {
                $checkOut = $plannedCheckOut;
            }

            return $checkIn->lte($dateEnd) && $checkOut->gt($dateStart);
        })->sortBy(fn (Booking $booking) => $booking->actual_check_in ?? $booking->check_in_at ?? $booking->check_in_date);

        $status = 'available';
        $mainBooking = null;

        foreach (['checked_in', 'inspection_requested', 'confirmed', 'pending', 'completed', 'checked_out'] as $priority) {
            $candidate = $matched->firstWhere('status', $priority);
            if ($candidate) {
                $mainBooking = $candidate;
                $status = match ($priority) {
                    'checked_in' => 'occupied',
                    'inspection_requested' => 'inspection',
                    'confirmed', 'pending' => 'reserved',
                    default => 'available',
                };
                if ($candidate->isLateCheckout($now)) {
                    $plannedLateStart = Carbon::parse($candidate->check_out_at, self::TIMEZONE);
                    $lateEnd = in_array($candidate->status, ['checked_out', 'completed'], true) && $candidate->actual_check_out
                        ? Carbon::parse($candidate->actual_check_out, self::TIMEZONE)
                        : $now;

                    // Chỉ tô màu trả muộn cho ô ngày thực sự giao với khoảng
                    // sau giờ checkout dự kiến; không nhuộm tím toàn bộ kỳ lưu trú.
                    if ($dateEnd->gte($plannedLateStart) && $dateStart->lt($lateEnd)) {
                        $status = 'late_checkout';
                    }
                }
                break;
            }
        }

        if (in_array($room->status, ['maintenance', 'cleaning', 'inspection'], true)) {
            $from = $room->status_from ? Carbon::parse($room->status_from, self::TIMEZONE) : $today->copy()->startOfDay();
            $until = $room->status_until ? Carbon::parse($room->status_until, self::TIMEZONE) : null;
            $physicalStatusApplies = $dateEnd->gte($from) && (!$until || $dateStart->lt($until));
            if ($physicalStatusApplies && $status !== 'late_checkout') {
                $status = $room->status;
                $mainBooking = null;
            }
        }

        return [
            'status' => $status,
            'booking' => $mainBooking,
            'bookings' => $matched->values(),
        ];
    }

    private function safeDate(?string $value, Carbon $fallback): Carbon
    {
        if (!$value) {
            return $fallback->copy();
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value, self::TIMEZONE)->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function parseVietnameseDateTime(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::createFromFormat('d/m/Y H:i', $value, self::TIMEZONE)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}