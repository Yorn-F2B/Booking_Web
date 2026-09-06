<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomInspection;
use Illuminate\Support\Facades\DB;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\BookingServiceItem;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Services\BookingFinancialService;
use App\Services\BookingOccupancyFeeService;
use App\Services\BookingRepricingService;
use App\Services\StayPricingPolicyService;
use App\Services\RoomReservationStatusService;
use Illuminate\Support\Facades\Auth;
use App\Support\Realtime;
use Carbon\Carbon;

class BookingLifecycleController extends Controller
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function checkIn(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);


        $data = $request->validate([
            'check_in_cccd' => ['nullable', 'string', 'max:50'],
            'cccd_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'scanned_full_name' => ['nullable', 'string', 'max:255'],
            'scanned_birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'scanned_gender' => ['nullable', 'in:male,female,other'],
            'scanned_address' => ['nullable', 'string', 'max:1000'],
            'guest_nationality' => ['nullable', 'string', 'max:100'],

            'actual_adult_count' => ['required', 'integer', 'min:1'],
            'actual_child_count' => ['required', 'integer', 'min:0'],
            'actual_guest_confirmed' => ['accepted'],

            'over_capacity_action' => 'nullable|in:extra_fee',

            'extra_service_ids' => 'nullable|array',
            'extra_service_ids.*' => 'nullable|exists:services,id',
            'extra_booking_room_ids' => 'nullable|array',
            'extra_booking_room_ids.*' => 'nullable|exists:booking_rooms,id',

            'early_check_in_action' => 'nullable|in:accept_fee',
        ], [
            'scanned_birthday.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại.',
            'actual_adult_count.required' => 'Vui lòng nhập số người lớn thực tế đến nhận phòng.',
            'actual_adult_count.min' => 'Phải có ít nhất 1 người lớn thực tế đến nhận phòng.',
            'actual_child_count.required' => 'Vui lòng nhập số trẻ em thực tế.',
            'actual_guest_confirmed.accepted' => 'Vui lòng xác nhận đã đối chiếu số khách thực tế trước khi check-in.',
        ]);

        $actualCheckInAt = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
            'customer',
        ]);

        $requiredRoomCount = max(1, (int) ($booking->room_quantity ?? 1));
        $assignedRoomIds = $booking->bookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id);

        if ($booking->bookingRooms->count() !== $requiredRoomCount
            || $assignedRoomIds->unique()->count() !== $requiredRoomCount) {
            return back()->with('error', 'Chưa thể check-in: booking phải được gán đủ đúng '
                . $requiredRoomCount . ' phòng khác nhau trước khi nhận phòng.');
        }

        if ($booking->room_selection_mode === 'manual'
            && !in_array($booking->room_selection_status, ['fulfilled', 'fallback_accepted'], true)) {
            if ($booking->room_selection_status === 'awaiting_guest') {
                return back()->with('error', 'Khách đang được yêu cầu xác nhận phòng dự phòng. Chưa thể check-in cho tới khi khách đồng ý phương án.');
            }

            return back()->with('error', 'Booking có yêu cầu chọn phòng cụ thể nhưng yêu cầu chưa được xử lý hoàn tất. Hãy xử lý yêu cầu phòng trước khi check-in.');
        }

        $booking->load(['guests.bookingRoom.room.category', 'guests.guardian']);

        // Khi check-in, lễ tân xác nhận tổng số khách thực tế đến. booking_guests vẫn chỉ
        // lưu hồ sơ người đại diện; số khách vận hành được phân tự động vào booking_rooms.
        $actualAdultCount = max(0, (int) $data['actual_adult_count']);
        $actualChildCount = max(0, (int) $data['actual_child_count']);

        if ($actualAdultCount < $requiredRoomCount) {
            return back()->withInput()->with('error', 'Chưa thể check-in: ' . $requiredRoomCount
                . ' phòng cần tối thiểu ' . $requiredRoomCount
                . ' người lớn thực tế để mỗi phòng có một người đại diện.');
        }

        // Mỗi phòng cần tối thiểu một người lớn đại diện có hồ sơ và được gán đúng phòng.
        foreach ($booking->bookingRooms as $bookingRoom) {
            $roomRepresentative = $booking->guests
                ->where('booking_room_id', $bookingRoom->id)
                ->first(fn ($guest) => $guest->guest_type === 'adult');
            if (!$roomRepresentative) {
                return back()->withInput()->with('error', 'Chưa thể check-in: phòng '
                    . ($bookingRoom->room?->room_number ?? '---')
                    . ' cần ít nhất một người lớn đại diện có hồ sơ/CCCD.');
            }
        }

        // Chỉ booking nhiều phòng mới cần thêm một đại diện chung cho cả đoàn.
        // Người này đồng thời là đại diện của một trong các phòng, không tạo thêm hồ sơ riêng.
        if ($requiredRoomCount > 1 && $booking->guests->where('is_booking_representative', true)->count() !== 1) {
            return back()->withInput()->with('error', 'Chưa thể check-in: booking nhiều phòng cần chọn đúng một người đại diện cả đoàn trong các đại diện phòng.');
        }

        // Đối chiếu theo TỔNG sức chứa các phòng đã gán, đúng với bước xác nhận khách thực tế.
        // Không bắt lễ tân phân từng khách vào từng phòng chỉ để tính phụ thu.
        $currentAdultCapacity = $booking->bookingRooms->sum(
            fn ($bookingRoom) => max(0, (int) ($bookingRoom->room?->category?->adult_capacity ?? 0))
        );
        $currentChildCapacity = $booking->bookingRooms->sum(
            fn ($bookingRoom) => max(0, (int) ($bookingRoom->room?->category?->child_capacity ?? 0))
        );

        $adultOverCapacity = max(0, $actualAdultCount - $currentAdultCapacity);
        $minorOverCapacity = max(0, $actualChildCount - $currentChildCapacity);
        $isOverCapacity = $adultOverCapacity > 0 || $minorOverCapacity > 0;

        if ($isOverCapacity && ($data['over_capacity_action'] ?? null) !== 'extra_fee') {
            $parts = [];
            if ($adultOverCapacity > 0) {
                $parts[] = 'vượt ' . $adultOverCapacity . ' người lớn';
            }
            if ($minorOverCapacity > 0) {
                $parts[] = 'vượt ' . $minorOverCapacity . ' trẻ em';
            }

            return back()->withInput()->with(
                'error',
                'Số khách thực tế ' . implode(' và ', $parts)
                . ' so với tổng sức chứa các phòng. Hãy thêm/đổi phòng, đổi hạng hoặc chọn thu phụ phí vượt sức chứa rồi mới check-in.'
            );
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== 'confirmed') {
                throw new \Exception('Chỉ có thể nhận phòng với booking đã xác nhận.');
            }

            // Khóa cả dòng phân phòng và phòng vật lý để thao tác đổi phòng/checkout
            // đồng thời không thể thay đổi trạng thái ngay giữa lúc check-in.
            $lockedBookingRooms = BookingRoom::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->get();
            $requiredRoomCount = max(1, (int) ($booking->room_quantity ?? 1));
            $lockedRoomIds = $lockedBookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

            if ($lockedBookingRooms->count() !== $requiredRoomCount || $lockedRoomIds->count() !== $requiredRoomCount) {
                throw new \Exception('Booking không còn được gán đủ đúng ' . $requiredRoomCount . ' phòng khác nhau. Vui lòng kiểm tra lại phân phòng.');
            }

            $lockedRooms = Room::query()
                ->whereIn('id', $lockedRoomIds->all())
                ->lockForUpdate()
                ->get();

            if ($lockedRooms->count() !== $requiredRoomCount) {
                throw new \Exception('Có phòng đã gán không còn tồn tại. Vui lòng kiểm tra lại dữ liệu phân phòng.');
            }

            $bookableAssignedCount = Room::query()
                ->whereIn('id', $lockedRoomIds->all())
                ->bookableForPeriod(
                    $booking->check_in_at,
                    $booking->check_out_at,
                    $booking->id,
                    $booking->cleaning_buffer_minutes
                )
                ->count();

            if ($bookableAssignedCount !== $requiredRoomCount) {
                throw new \Exception('Ít nhất một phòng đã gán vừa phát sinh xung đột lịch/giữ phòng. Không được check-in cho tới khi phân phòng lại an toàn.');
            }

            $booking->unsetRelation('bookingRooms');
            $booking->load(['bookingRooms.room.category', 'roomCategory']);

            $financialService = app(BookingFinancialService::class);
            $currentTotal = $financialService->currentTotal($booking);
            $paidTotal = $financialService->paidTotal($booking);
            $requiredDeposit = $financialService->requiredDeposit($booking);

            if ($currentTotal > 0 && $paidTotal + 0.01 < $requiredDeposit) {
                throw new \Exception('Booking chưa thanh toán đủ mức cọc đã chốt khi đặt phòng. Đã thanh toán ' . number_format($paidTotal, 0, ',', '.') . 'đ, yêu cầu tối thiểu ' . number_format($requiredDeposit, 0, ',', '.') . 'đ.');
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $actionNote = '';

            if ($booking->payment_status === 'unpaid') {
                $actionNote .= ' Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.';
            }

            // Chốt số khách thực tế tại thời điểm nhận phòng. Từ đây booking và
            // booking_rooms phản ánh số người đang lưu trú, còn BookingLog giữ lịch sử số đã đặt.
            $bookedSnapshot = [
                'adult' => (int) $booking->adult_count,
                'child' => (int) $booking->child_count,
            ];
            $booking->adult_count = $actualAdultCount;
            $booking->child_count = $actualChildCount;
            $booking->save();

            app(\App\Services\BookingRoomOccupancyAllocator::class)
                ->rebalanceBookingAllowOverflow($booking, $actualAdultCount, $actualChildCount);
            $booking->load(['bookingRooms.room.category']);

            $actionNote .= ' Khách thực tế: ' . $actualAdultCount . ' người lớn, '
                . $actualChildCount . ' trẻ em'
                . ' (đã đặt: ' . $bookedSnapshot['adult'] . ' NL, ' . $bookedSnapshot['child'] . ' TE).';

            if ($isOverCapacity && ($data['over_capacity_action'] ?? null) === 'extra_fee') {
                $actionNote .= ' ' . $this->handleExtraGuestFees(
                    $booking,
                    $data,
                    $actualAdultCount,
                    $actualChildCount
                );
            }

            $earlyCheckInNote = $this->handleEarlyCheckInFee($booking, $data, $actualCheckInAt);

            if ($earlyCheckInNote !== '') {
                $actionNote .= ' ' . $earlyCheckInNote;
            }

            $lateArrivalNote = $this->handleLateArrivalFee($booking, $data);

            if ($lateArrivalNote !== '') {
                $actionNote .= ' ' . $lateArrivalNote;
            }

            $roomReadyNote = $this->prepareRoomsForCheckIn($booking, $actualCheckInAt);

            if ($roomReadyNote !== '') {
                $actionNote .= ' ' . $roomReadyNote;
            }

            $booking->load([
                'bookingRooms.room.category',
                'roomCategory',
            ]);

            $booking->status = 'checked_in';
            $booking->actual_check_in = $actualCheckInAt;

            $booking->note = $oldNote
                . $actualCheckInAt->format('d/m/Y H:i')
                . ' - Check-in hoàn tất. Đã xác nhận người đại diện cho từng phòng'
                . ($requiredRoomCount > 1 ? ' và đại diện cả đoàn' : '')
                . '. ' . trim($actionNote);

            $booking->save();

            // Phụ thu vượt sức chứa là dịch vụ đã xác nhận theo từng phòng; tính lại tổng booking ngay sau khi lưu.
            $this->repriceCurrentBooking($booking);

            // Chỉ người đại diện từng phòng được lưu; khi check-in đồng bộ trạng thái và giờ nhận cho các hồ sơ đại diện.
            $booking->guests()->update([
                'status' => 'checked_in',
                'actual_check_in_at' => $actualCheckInAt,
                'updated_by' => Auth::id(),
            ]);

            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update(['status' => 'occupied']);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $bookingRoom->room->id,
                        'user_id' => Auth::id(),
                        'action_type' => 'check_in',
                        'action_time' => now(),
                        'note' => 'Phòng đã check-in cho booking #' . $booking->booking_code . '.',
                    ]);
                }
            }

            $this->addBookingLog(
                $booking,
                'check_in',
                'Xác nhận check-in. Đã đủ người đại diện cho từng phòng'
                . ($requiredRoomCount > 1 ? ' và đại diện cả đoàn' : '')
                . '. ' . trim($actionNote)
            );

            DB::commit();

            Realtime::booking($booking, 'checked_in');

            return back()->with('success', 'Check-in thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $message = $e->getMessage();

            // Đây không phải lỗi nghiệp vụ: request đầu tiên chỉ là bước lấy/xác nhận
            // báo giá check-in sớm. Nếu JavaScript bị lỗi hoặc chưa kịp chặn submit,
            // backend vẫn phải quay lại đúng màn hình xác nhận thay vì hiện toast đỏ.
            if (str_contains($message, 'Vui lòng xác nhận khách đồng ý phụ thu trước khi check-in.')) {
                return back()
                    ->withInput()
                    ->with('early_checkin_confirmation_required', true);
            }

            return back()->with('error', 'Có lỗi khi check-in: ' . $message);
        }
    }


    public function previewExtendStay(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ booking đang ở mới được kiểm tra gia hạn lưu trú.');
        }

        try {
            [$oldCheckOutAt, $newCheckOutAt] = $this->getExtendStayTimesFromRequest($request, $booking);

            $booking->load([
                'bookingRooms.room.category',
                'roomCategory',
                'customer',
                'serviceItems',
            ]);

            $analysis = $this->analyzeExtendStay($booking, $oldCheckOutAt, $newCheckOutAt);

            // Chỉ tính/hiển thị tiền khi phương án gia hạn thực sự khả thi.
            // Nếu đã bị chặn bởi xung đột phòng thì bảng so sánh tiền chỉ gây hiểu nhầm
            // rằng lễ tân vẫn có thể xác nhận gia hạn.
            $repricingPreview = null;
            if (($analysis['status'] ?? null) !== 'blocked') {
                $repricingPreview = $this->previewExtensionRepricing(
                    $booking,
                    $oldCheckOutAt,
                    $newCheckOutAt
                );
            }

            session()->put('extend_stay_preview.' . $booking->id, [
                'status' => $analysis['status'],
                'title' => $analysis['title'],
                'message' => $analysis['message'],
                'booking_type' => $booking->booking_type === 'hourly' ? 'Gói giờ' : 'Qua đêm',
                'old_check_out_date' => $oldCheckOutAt->format('Y-m-d'),
                'old_check_out_time' => $oldCheckOutAt->format('H:i'),
                'new_check_out_date' => $newCheckOutAt->format('Y-m-d'),
                'new_check_out_time' => $newCheckOutAt->format('H:i'),
                'old_check_out_text' => $oldCheckOutAt->format('d/m/Y H:i'),
                'new_check_out_text' => $newCheckOutAt->format('d/m/Y H:i'),
                'period_text' => $oldCheckOutAt->format('d/m/Y H:i') . ' → ' . $newCheckOutAt->format('d/m/Y H:i'),
                'fee_amount' => $analysis['fee_amount'],
                'fee_text' => number_format($analysis['fee_amount'], 0, ',', '.') . 'đ',
                'policy_text' => $analysis['policy_text'],
                'conflicts' => $analysis['conflicts_for_view'],
                'replacements' => $analysis['replacements_for_view'],
                'repricing' => $repricingPreview,
            ]);

            return redirect(route('admin.bookings.show', $booking) . '#extend-stay-preview');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['new_check_out_date' => $e->getMessage()]);
        }
    }

    public function discardExtendStayPreview(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        session()->forget('extend_stay_preview.' . $booking->id);
        session()->forget('_old_input');

        return redirect()->route('admin.bookings.show', $booking->id);
    }

    public function extendStay(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ booking đang ở mới được gia hạn lưu trú.');
        }

        try {
            // Kiểm tra dữ liệu đầu vào trước để trả lỗi form rõ ràng. Thời gian cũ sẽ
            // được đọc lại sau khi khóa booking nhằm tránh hai lễ tân gia hạn đồng thời.
            [, $requestedNewCheckOutAt] = $this->getExtendStayTimesFromRequest($request, $booking);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with([
                    'bookingRooms.room.category',
                    'roomCategory',
                    'customer',
                    'serviceItems.service',
                    'bookingPromotions.promotion.serviceOffers.service',
                    'bookingPromotions.promotion.roomUpgradeOffers',
                    'bookingPromotions.serviceOffers',
                    'bookingPromotions.roomUpgradeOffers.offer',
                    'payments',
                ])
                ->firstOrFail();

            if ($booking->status !== 'checked_in') {
                throw new \Exception('Booking đã được xử lý bởi yêu cầu khác và không còn ở trạng thái đang ở.');
            }

            if (!$booking->check_out_at) {
                throw new \Exception('Booking chưa có thời gian trả phòng hiện tại nên không thể gia hạn.');
            }

            $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
            $newCheckOutAt = $requestedNewCheckOutAt->copy();

            if ($newCheckOutAt->lessThanOrEqualTo($oldCheckOutAt)) {
                throw new \Exception(
                    'Thời gian trả phòng của booking vừa thay đổi. Giờ trả phòng mới phải sau '
                    . $oldCheckOutAt->format('d/m/Y H:i') . '.'
                );
            }

            // Khóa các dòng gán phòng hiện tại trước khi xét xung đột/phương án đổi phòng.
            BookingRoom::where('booking_id', $booking->id)->lockForUpdate()->get();

            $analysis = $this->analyzeExtendStay($booking, $oldCheckOutAt, $newCheckOutAt);
            if ($analysis['status'] === 'blocked') {
                throw new \Exception($analysis['message']);
            }

            // Khóa cả các phòng thay thế đã chọn rồi phân tích lại. Nếu một request khác
            // vừa lấy phòng trong lúc chờ lock, lần phân tích thứ hai sẽ loại phòng đó.
            $replacementIds = collect($analysis['replacement_plans'])
                ->map(fn (array $plan) => (int) ($plan['new_room']->id ?? 0))
                ->filter()
                ->unique()
                ->values()
                ->all();
            if ($replacementIds !== []) {
                Room::whereIn('id', $replacementIds)->lockForUpdate()->get();
                $booking->load('bookingRooms.room.category');
                $analysis = $this->analyzeExtendStay($booking, $oldCheckOutAt, $newCheckOutAt);
                if ($analysis['status'] === 'blocked') {
                    throw new \Exception($analysis['message']);
                }
            }

            $repricingPreview = $this->previewExtensionRepricing($booking, $oldCheckOutAt, $newCheckOutAt);
            $usesRepricing = $repricingPreview !== null;
            $extraRoomTotal = (float) $analysis['fee_amount'];
            $extendPolicyText = $analysis['policy_text'];
            $roomChangeMessages = [];

            foreach ($analysis['replacement_plans'] as $plan) {
                /** @var BookingRoom $bookingRoom */
                $bookingRoom = BookingRoom::whereKey($plan['booking_room']->id)->lockForUpdate()->firstOrFail();
                /** @var Room $oldRoom */
                $oldRoom = Room::whereKey($plan['old_room']->id)->lockForUpdate()->with('category')->firstOrFail();
                /** @var Room $newRoom */
                $newRoom = Room::whereKey($plan['new_room']->id)->lockForUpdate()->with('category')->firstOrFail();

                if ($newRoom->status !== 'available') {
                    throw new \Exception('Phòng thay thế ' . $newRoom->room_number . ' vừa không còn sẵn sàng. Vui lòng kiểm tra lại phương án gia hạn.');
                }

                $bookingRoom->update([
                    'room_id' => $newRoom->id,
                    'surcharge_reason' => trim(
                        ($bookingRoom->surcharge_reason ? $bookingRoom->surcharge_reason . ' | ' : '')
                        . 'Chuyển từ phòng '
                        . $oldRoom->room_number
                        . ' sang phòng '
                        . $newRoom->room_number
                        . ' để gia hạn do phòng cũ có booking kế tiếp.'
                    ),
                ]);

                // Đây là chuyển phòng trong khi khách vẫn tiếp tục lưu trú, không phải
                // quy trình check-out. Phòng cũ vừa có khách rời đi phải qua Cleaning
                // trước khi có thể phục vụ booking kế tiếp; không được nhảy sang Inspection.
                $oldRoom->update([
                    'status' => 'cleaning',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);

                $newRoom->update([
                    'status' => 'occupied',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);

                $roomChangeMessages[] = 'Chuyển phòng '
                    . $oldRoom->room_number
                    . ' → '
                    . $newRoom->room_number
                    . ' cùng hạng '
                    . ($newRoom->category->name ?? $oldRoom->category->name ?? '')
                    . '.';
            }

            // Qua đêm và gói giờ đều được BookingRepricingService tính lại khi thời
            // lượng lưu trú thay đổi. Chỉ trường hợp qua đêm nhưng kéo giờ trả trong
            // cùng ngày mới là phụ thu check-out/gia hạn theo chính sách riêng.
            if ($extraRoomTotal > 0 && !$usesRepricing) {
                $extendStayService = Service::firstOrCreate(
                    [
                        'name' => 'Phụ thu gia hạn lưu trú',
                        'type' => 'extension_fee',
                    ],
                    [
                        'service_group' => 'other',
                        'price' => 0,
                        'unit' => 'lần',
                        'billing_rule' => Service::BILLING_ONCE,
                        'description' => 'Phụ thu khi khách gia hạn thêm giờ hoặc check-out muộn.',
                        'status' => 'active',
                    ]
                );

                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $extendStayService->id,
                    'name' => 'Phụ thu gia hạn lưu trú',
                    'type' => 'extension_fee',
                    'billing_rule_snapshot' => Service::BILLING_ONCE,
                    'unit_price' => $extraRoomTotal,
                    'base_quantity' => 1,
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'nights_snapshot' => 1,
                    'rooms_snapshot' => max(1, (int) $booking->room_quantity),
                    'people_snapshot' => max(1, (int) $booking->adult_count + (int) $booking->child_count),
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now('Asia/Ho_Chi_Minh'),
                    'confirm_note' => $extendPolicyText,
                    'total' => $extraRoomTotal,
                    'note' => 'Gia hạn từ ' . $oldCheckOutAt->format('d/m/Y H:i')
                        . ' đến ' . $newCheckOutAt->format('d/m/Y H:i')
                        . '. ' . $extendPolicyText,
                ]);
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $roomChangeText = count($roomChangeMessages) > 0
                ? ' ' . implode(' ', $roomChangeMessages)
                : '';

            $booking->check_out_date = $newCheckOutAt->toDateString();
            $booking->check_out_at = $newCheckOutAt;
            if (!$usesRepricing) {
                $booking->subtotal_amount = (float) $booking->subtotal_amount + $extraRoomTotal;
                $booking->estimated_total = (float) $booking->estimated_total + $extraRoomTotal;
            }
            $booking->note = $oldNote
                . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . ' - Gia hạn lưu trú từ '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' đến '
                . $newCheckOutAt->format('d/m/Y H:i')
                . '. '
                . $extendPolicyText
                . $roomChangeText
                . ' Phần tiền tăng: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.';
            $booking->save();

            $financialText = '';
            if ($usesRepricing && $repricingPreview) {
                app(BookingRepricingService::class)->apply($booking, $repricingPreview);
                $booking->refresh();

                $serviceChanges = collect($repricingPreview['service_preview']['lines'] ?? [])
                    ->filter(fn (array $item) => !empty($item['will_reprice']) || !empty($item['will_remove']))
                    ->map(fn (array $item) => ($item['name'] ?? 'Dịch vụ')
                        . ': ' . number_format((float) ($item['old_total'] ?? 0), 0, ',', '.')
                        . 'đ → ' . number_format((float) ($item['new_total'] ?? 0), 0, ',', '.') . 'đ')
                    ->implode('; ');
                $removedPromotions = collect($repricingPreview['promotion_preview']['removed'] ?? [])
                    ->map(fn (array $item) => ($item['code'] ?? '---') . ': ' . ($item['reason'] ?? 'không còn đủ điều kiện'))
                    ->implode('; ');

                $financialText = ' Tổng đơn: '
                    . number_format((float) $repricingPreview['old']['total'], 0, ',', '.')
                    . 'đ → '
                    . number_format((float) $repricingPreview['new']['total'], 0, ',', '.')
                    . 'đ. Còn phải thu: '
                    . number_format((float) $repricingPreview['new']['remaining'], 0, ',', '.')
                    . 'đ. Khách đang trả dư: '
                    . number_format((float) $repricingPreview['new']['overpayment'], 0, ',', '.')
                    . 'đ.'
                    . ($serviceChanges !== '' ? ' Dịch vụ tính lại: ' . $serviceChanges . '.' : '')
                    . ($removedPromotions !== '' ? ' Mã bị gỡ: ' . $removedPromotions . '.' : '');
            } else {
                $booking->unsetRelation('serviceItems');
                app(BookingFinancialService::class)->refreshPaymentStatus($booking);
                $booking->refresh();
            }

            $this->addBookingLog(
                $booking,
                'extend_stay',
                'Gia hạn lưu trú từ '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' đến '
                . $newCheckOutAt->format('d/m/Y H:i')
                . '. '
                . $extendPolicyText
                . $roomChangeText
                . ' Phần tiền phòng/phụ thu tăng: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.'
                . $financialText
            );

            DB::commit();

            // Bản xem trước chỉ có hiệu lực cho đúng booking và đúng lần gia hạn.
            // Xóa ngay sau khi giao dịch thành công để không còn hiện lại hoặc
            // vô tình xuất hiện khi mở một booking khác trong cùng phiên đăng nhập.
            session()->forget('extend_stay_preview.' . $booking->id);

            $successMessage = 'Gia hạn lưu trú thành công. '
                . $extendPolicyText
                . ' Phần tiền tăng: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.';

            if (count($roomChangeMessages) > 0) {
                $successMessage .= ' Hệ thống đã chuyển phòng cùng hạng: ' . implode(' ', $roomChangeMessages);
            }

            return back()->with('success', $successMessage);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gia hạn lưu trú: ' . $e->getMessage());
        }
    }

    private function previewExtensionRepricing(
        Booking $booking,
        \Carbon\Carbon $oldCheckOutAt,
        \Carbon\Carbon $newCheckOutAt
    ): ?array {
        // Booking qua đêm chỉ kéo giờ trả trong cùng ngày dùng biểu phí trả muộn
        // riêng. Mọi trường hợp làm thay đổi số đêm hoặc thời lượng gói giờ phải
        // tính lại toàn bộ giá phòng/dịch vụ/khuyến mãi để không cộng phí hai lần.
        if (
            $booking->booking_type !== 'hourly'
            && $newCheckOutAt->toDateString() === $oldCheckOutAt->toDateString()
        ) {
            return null;
        }

        $booking->loadMissing([
            'bookingRooms',
            'serviceItems.service',
            'bookingPromotions.promotion.serviceOffers.service',
            'bookingPromotions.promotion.roomUpgradeOffers',
            'bookingPromotions.serviceOffers',
            'bookingPromotions.roomUpgradeOffers.offer',
            'payments',
            'customer',
        ]);

        $oneNightRoomTotal = (float) $booking->bookingRooms->sum(
            fn ($bookingRoom) => (float) $bookingRoom->price_at_booking
        );
        if ($oneNightRoomTotal <= 0) {
            $oneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0)
                * max(1, (int) $booking->room_quantity);
        }

        return app(BookingRepricingService::class)->preview(
            $booking,
            \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
            $newCheckOutAt,
            $oneNightRoomTotal,
            null
        );
    }

    private function getExtendStayTimesFromRequest(Request $request, Booking $booking): array
    {
        $data = $request->validate([
            'new_check_out_date' => 'required|date',
            'new_check_out_time' => 'required|date_format:H:i',
        ], [
            'new_check_out_date.required' => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out_date.date' => 'Ngày trả phòng mới không hợp lệ.',
            'new_check_out_time.required' => 'Vui lòng chọn giờ trả phòng mới.',
            'new_check_out_time.date_format' => 'Giờ trả phòng mới phải theo định dạng 24 giờ, ví dụ 14:00 hoặc 17:30.',
        ]);

        if (!$booking->check_out_at) {
            throw new \Exception('Booking chưa có thời gian trả phòng hiện tại nên không thể gia hạn.');
        }

        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $newCheckOutAt = \Carbon\Carbon::parse(
            $data['new_check_out_date'] . ' ' . $data['new_check_out_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        if ($newCheckOutAt->toDateString() < $oldCheckOutAt->toDateString()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'new_check_out_date' => 'Ngày trả phòng mới phải sau thời gian trả hiện tại ' . $oldCheckOutAt->format('d/m/Y H:i') . '.',
            ]);
        }

        if ($newCheckOutAt->lessThanOrEqualTo($oldCheckOutAt)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'new_check_out_time' => 'Thời gian trả phòng mới phải sau ' . $oldCheckOutAt->format('d/m/Y H:i') . '. Không được giữ nguyên hoặc chọn thời gian sớm hơn.',
            ]);
        }

        return [$oldCheckOutAt, $newCheckOutAt];
    }

    private function analyzeExtendStay(Booking $booking, \Carbon\Carbon $oldCheckOutAt, \Carbon\Carbon $newCheckOutAt): array
    {
        if ($booking->bookingRooms->count() == 0) {
            throw new \Exception('Booking này chưa có phòng nên không thể gia hạn.');
        }

        $feeResult = $this->calculateExtendStayFee($booking, $oldCheckOutAt, $newCheckOutAt);
        $replacementPlans = [];
        $usedReplacementRoomIds = [];
        $conflictMessages = [];
        $conflictsForView = [];
        $replacementsForView = [];
        $blockedMessages = [];

        $newCheckOutAtWithCleaning = $newCheckOutAt
            ->copy()
            ->addMinutes($booking->cleaning_buffer_minutes ?? 0);
        // Khi phải đổi phòng để gia hạn, code thực hiện đổi ngay tại thời điểm xác
        // nhận. Vì vậy phòng thay thế phải trống từ hiện tại, không chỉ từ giờ trả cũ.
        $replacementNeededFrom = now('Asia/Ho_Chi_Minh');

        foreach ($booking->bookingRooms as $bookingRoom) {
            if (!$bookingRoom->room) {
                throw new \Exception('Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.');
            }

            $conflictBookingRoom = $this->findConflictBookingRoom(
                $bookingRoom->room_id,
                $booking->id,
                $oldCheckOutAt,
                $newCheckOutAtWithCleaning
            );

            if (!$conflictBookingRoom) {
                continue;
            }

            $conflictBooking = $conflictBookingRoom->booking;
            $oldRoom = $bookingRoom->room;
            $roomNumber = $oldRoom->room_number ?? ('ID ' . $bookingRoom->room_id);
            $categoryId = (int) ($oldRoom->room_category_id ?? $booking->room_category_id);
            $categoryName = $oldRoom->category->name ?? $booking->roomCategory->name ?? 'không xác định';

            $conflictText = 'Phòng ' . $roomNumber
                . ' đã có booking '
                . ($conflictBooking->booking_code ?? '')
                . ' của ' . $this->getCustomerNameFromBooking($conflictBooking)
                . ' từ '
                . \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . ' đến '
                . \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . '.';

            $conflictMessages[] = $conflictText;
            $conflictsForView[] = [
                'room_number' => $roomNumber,
                'category_name' => $categoryName,
                'booking_code' => $conflictBooking->booking_code ?? '',
                'customer_name' => $this->getCustomerNameFromBooking($conflictBooking),
                'check_in_text' => \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'check_out_text' => \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            ];

            $replacementRoom = $this->findSameCategoryReplacementRoom(
                $categoryId,
                $booking->id,
                $replacementNeededFrom,
                $newCheckOutAtWithCleaning,
                array_merge(
                    $booking->bookingRooms->pluck('room_id')->toArray(),
                    $usedReplacementRoomIds
                )
            );

            if (!$replacementRoom) {
                $blockedMessages[] = 'Phòng ' . $roomNumber
                    . ' bị vướng booking kế tiếp trong thời gian gia hạn. Hiện tại không còn phòng sẵn sàng cùng hạng '
                    . $categoryName
                    . ' để chuyển khách ngay.';
                continue;
            }

            $replacementPlans[] = [
                'booking_room' => $bookingRoom,
                'old_room' => $oldRoom,
                'new_room' => $replacementRoom,
                'conflict_booking' => $conflictBooking,
            ];

            $usedReplacementRoomIds[] = $replacementRoom->id;
            $replacementsForView[] = [
                'old_room_number' => $roomNumber,
                'new_room_number' => $replacementRoom->room_number,
                'category_name' => $replacementRoom->category->name ?? $categoryName,
            ];
        }

        if (count($blockedMessages) > 0) {
            return [
                'status' => 'blocked',
                'title' => 'Không thể gia hạn',
                'message' => implode(' ', $blockedMessages) . ' Khách cần trả phòng đúng hạn hoặc tạo booking mới ở phòng/hạng khác.',
                'fee_amount' => $feeResult['amount'],
                'policy_text' => $feeResult['policy_text'],
                'replacement_plans' => [],
                'conflicts_for_view' => $conflictsForView,
                'replacements_for_view' => $replacementsForView,
            ];
        }

        if (count($replacementPlans) > 0) {
            return [
                'status' => 'need_room_change',
                'title' => 'Có thể gia hạn nhưng phải chuyển phòng',
                'message' => implode(' ', $conflictMessages) . ' Hệ thống tìm được phòng cùng hạng đang sẵn sàng để chuyển khách ngay trước khi gia hạn.',
                'fee_amount' => $feeResult['amount'],
                'policy_text' => $feeResult['policy_text'],
                'replacement_plans' => $replacementPlans,
                'conflicts_for_view' => $conflictsForView,
                'replacements_for_view' => $replacementsForView,
            ];
        }

        return [
            'status' => 'available',
            'title' => 'Có thể gia hạn',
            'message' => 'Không có booking nào giao thời gian trong khung giờ gia hạn. Có thể gia hạn trên phòng hiện tại.',
            'fee_amount' => $feeResult['amount'],
            'policy_text' => $feeResult['policy_text'],
            'replacement_plans' => [],
            'conflicts_for_view' => [],
            'replacements_for_view' => [],
        ];
    }

    private function findConflictBookingRoom(
        int $roomId,
        int $currentBookingId,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to
    ) {
        return BookingRoom::where('room_id', $roomId)
            ->where('booking_id', '!=', $currentBookingId)
            ->whereHas('booking', function ($query) use ($from, $to) {
                $query->activeForOperations()
                    ->where('check_in_at', '<', $to)
                    ->where('check_out_at', '>', $from);
            })
            ->with(['booking.customer', 'room'])
            ->orderBy(
                Booking::select('check_in_at')
                    ->whereColumn('bookings.id', 'booking_rooms.booking_id')
                    ->limit(1)
            )
            ->first();
    }

    private function findSameCategoryReplacementRoom(
        int $roomCategoryId,
        int $currentBookingId,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
        array $excludeRoomIds = []
    ) {
        return Room::where('room_category_id', $roomCategoryId)
            ->where('status', 'available')
            ->when(count($excludeRoomIds) > 0, function ($query) use ($excludeRoomIds) {
                $query->whereNotIn('id', $excludeRoomIds);
            })
            ->bookableForPeriod($from, $to, $currentBookingId)
            ->with('category')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->first();
    }

    private function calculateExtendStayFee(Booking $booking, \Carbon\Carbon $oldCheckOutAt, \Carbon\Carbon $newCheckOutAt): array
    {
        $oneNightTotal = $booking->bookingRooms->sum(function ($bookingRoom) {
            return (float) $bookingRoom->price_at_booking;
        });

        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        if ($booking->booking_type === 'hourly') {
            $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
            $oldMinutes = max(1, $checkInAt->diffInMinutes($oldCheckOutAt));
            $newMinutes = max(1, $checkInAt->diffInMinutes($newCheckOutAt));
            $pricingPolicy = app(StayPricingPolicyService::class);
            $oldPricing = $pricingPolicy->shortStay($oneNightTotal, 1, $oldMinutes, $booking);
            $newPricing = $pricingPolicy->shortStay($oneNightTotal, 1, $newMinutes, $booking);
            $extraRoomTotal = max(0, round((float) $newPricing['amount'] - (float) $oldPricing['amount'], 0));

            return [
                'amount' => $extraRoomTotal,
                'policy_text' => 'Booking theo giờ, thời lượng thay đổi từ '
                    . round($oldMinutes / 60, 2) . ' giờ lên ' . round($newMinutes / 60, 2)
                    . ' giờ. Tiền phòng được tính lại theo chính sách gói giờ: '
                    . number_format((float) $oldPricing['amount'], 0, ',', '.') . 'đ → '
                    . number_format((float) $newPricing['amount'], 0, ',', '.') . 'đ.',
            ];
        }

        if ($oldCheckOutAt->toDateString() === $newCheckOutAt->toDateString()) {
            $latePolicy = app(StayPricingPolicyService::class)->lateCheckOut($newCheckOutAt, $oneNightTotal, $booking);
            $extraRoomTotal = $latePolicy['amount'];
            $extendPolicyText = $latePolicy['policy_text'];
        } else {
            $extraNights = max(
                1,
                $oldCheckOutAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
            );

            $extraRoomTotal = $oneNightTotal * $extraNights;
            $extendPolicyText = 'Booking qua đêm, gia hạn thêm ' . $extraNights . ' đêm.';
        }

        return [
            'amount' => round($extraRoomTotal, 0),
            'policy_text' => $extendPolicyText,
        ];
    }

    private function getCustomerNameFromBooking(?Booking $booking): string
    {
        if (!$booking || !$booking->customer) {
            return 'khách mới';
        }

        $customerName = trim(
            ($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')
        );

        return $customerName !== '' ? $customerName : 'khách mới';
    }

    public function addRoomToBooking(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ booking đã xác nhận hoặc đang ở mới được thêm phòng.');
        }

        if ($booking->roomInspections()->whereIn('status', ['pending', 'reported'])->exists()) {
            return back()->with('error', 'Booking đã yêu cầu kiểm tra phòng, không thể thêm phòng nữa.');
        }

        $data = $request->validate([
            'additional_room_category_id' => 'required|exists:room_categories,id',
            'additional_room_quantity' => 'required|integer|min:1',
            'room_assignment_mode' => 'nullable|in:auto,manual',
            'target_room_ids' => 'nullable|array',
            'target_room_ids.*' => 'integer|distinct|exists:rooms,id',
            'prefer_near_current_rooms' => 'nullable|boolean',
            'add_room_reason' => 'nullable|string|max:1000',
            'confirm_operation' => 'nullable|boolean',
            'operation_token' => 'required_if:confirm_operation,1|nullable|string|uuid',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

        if (!$request->boolean('confirm_operation')) {
            return $this->previewBookingRoomOperation(
                $booking,
                'add_room',
                $data,
                fn () => $this->handleAddRoomToBooking($booking, $data, null, null),
                route('admin.bookings.add-room-to-booking', $booking->id)
            );
        }

        DB::beginTransaction();

        try {
            $booking = $this->lockBookingForRoomOperation($booking);
            $operationToken = (string) $data['operation_token'];
            if ($this->roomOperationAlreadyApplied($booking, $operationToken)) {
                DB::commit();
                return back()->with('info', 'Thao tác này đã được xử lý trước đó; hệ thống không thực hiện lặp lần hai.');
            }
            $this->assertRoomOperationPreviewToken($booking, 'add_room', $operationToken);
            $message = $this->handleAddRoomToBooking($booking, $data, null, null);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->note = $oldNote . now()->format('d/m/Y H:i') . ' - ' . $message;
            $booking->save();

            $this->addBookingLog($booking, 'add_room_to_booking', $message . ' [room-op:' . $operationToken . ']');
            session()->forget('booking_room_operation_preview');

            DB::commit();

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể thêm phòng: ' . $e->getMessage());
        }
    }

    public function changeOneRoomCategory(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ booking đã xác nhận hoặc đang ở mới được đổi hạng 1 phòng.');
        }

        if ($booking->roomInspections()->whereIn('status', ['pending', 'reported'])->exists()) {
            return back()->with('error', 'Booking đã yêu cầu kiểm tra phòng, không thể đổi hạng phòng nữa.');
        }

        $data = $request->validate([
            'booking_room_id' => 'required|exists:booking_rooms,id',
            'new_room_category_id' => 'required|exists:room_categories,id',
            'room_assignment_mode' => 'required|in:auto,manual',
            'target_room_ids' => 'nullable|required_if:room_assignment_mode,manual|array',
            'target_room_ids.*' => 'integer|distinct|exists:rooms,id',
            'change_category_reason' => 'nullable|string|max:1000',
            'confirm_operation' => 'nullable|boolean',
            'operation_token' => 'required_if:confirm_operation,1|nullable|string|uuid',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

        if (!$request->boolean('confirm_operation') && ($data['room_assignment_mode'] ?? 'auto') === 'auto') {
            // Chế độ tự chọn phải thực sự do hệ thống chọn; bỏ mọi room id craft từ request đầu.
            unset($data['target_room_ids']);
        }

        if (!$request->boolean('confirm_operation')) {
            return $this->previewBookingRoomOperation(
                $booking,
                'change_one_room',
                $data,
                function () use ($booking, &$data) {
                    return $this->handleChangeOneRoomCategory($booking, $data);
                },
                route('admin.bookings.change-one-room-category', $booking->id)
            );
        }

        DB::beginTransaction();

        try {
            $booking = $this->lockBookingForRoomOperation($booking);
            $operationToken = (string) $data['operation_token'];
            if ($this->roomOperationAlreadyApplied($booking, $operationToken)) {
                DB::commit();
                return back()->with('info', 'Thao tác này đã được xử lý trước đó; hệ thống không thực hiện lặp lần hai.');
            }
            $this->assertRoomOperationPreviewToken($booking, 'change_one_room', $operationToken);
            $previewPayload = session('booking_room_operation_preview.payload', []);
            foreach (['booking_room_id', 'new_room_category_id', 'room_assignment_mode', 'target_room_ids', 'change_category_reason'] as $key) {
                if (array_key_exists($key, $previewPayload)) {
                    $data[$key] = $previewPayload[$key];
                }
            }
            $message = $this->handleChangeOneRoomCategory($booking, $data);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->note = $oldNote . now()->format('d/m/Y H:i') . ' - ' . $message;
            $booking->save();

            $this->addBookingLog($booking, 'change_one_room_category', $message . ' [room-op:' . $operationToken . ']');
            session()->forget('booking_room_operation_preview');

            DB::commit();

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể đổi hạng 1 phòng: ' . $e->getMessage());
        }
    }

    public function changeAllRoomCategory(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ booking đã xác nhận hoặc đang ở mới được đổi toàn bộ hạng phòng.');
        }

        if ($booking->roomInspections()->whereIn('status', ['pending', 'reported'])->exists()) {
            return back()->with('error', 'Booking đã yêu cầu kiểm tra phòng, không thể đổi toàn bộ hạng phòng nữa.');
        }

        $data = $request->validate([
            'new_room_category_id' => 'required|exists:room_categories,id',
            'room_assignment_mode' => 'required|in:auto,manual',
            'target_room_ids' => 'nullable|required_if:room_assignment_mode,manual|array',
            'target_room_ids.*' => 'integer|distinct|exists:rooms,id',
            'change_category_reason' => 'nullable|string|max:1000',
            'confirm_operation' => 'nullable|boolean',
            'operation_token' => 'required_if:confirm_operation,1|nullable|string|uuid',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

        if (!$request->boolean('confirm_operation') && ($data['room_assignment_mode'] ?? 'auto') === 'auto') {
            // Chế độ tự chọn phải thực sự do hệ thống chọn; bỏ mọi room id craft từ request đầu.
            unset($data['target_room_ids']);
        }

        if (!$request->boolean('confirm_operation')) {
            return $this->previewBookingRoomOperation(
                $booking,
                'change_all_rooms',
                $data,
                function () use ($booking, &$data) {
                    return $this->handleChangeRoomCategory($booking, $data, null, null);
                },
                route('admin.bookings.change-all-room-category', $booking->id)
            );
        }

        DB::beginTransaction();

        try {
            $booking = $this->lockBookingForRoomOperation($booking);
            $operationToken = (string) $data['operation_token'];
            if ($this->roomOperationAlreadyApplied($booking, $operationToken)) {
                DB::commit();
                return back()->with('info', 'Thao tác này đã được xử lý trước đó; hệ thống không thực hiện lặp lần hai.');
            }
            $this->assertRoomOperationPreviewToken($booking, 'change_all_rooms', $operationToken);
            $previewPayload = session('booking_room_operation_preview.payload', []);
            foreach (['new_room_category_id', 'room_assignment_mode', 'target_room_ids', 'change_category_reason'] as $key) {
                if (array_key_exists($key, $previewPayload)) {
                    $data[$key] = $previewPayload[$key];
                }
            }
            $message = $this->handleChangeRoomCategory($booking, $data, null, null);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->note = $oldNote . now()->format('d/m/Y H:i') . ' - ' . $message;
            $booking->save();

            $this->addBookingLog($booking, 'change_all_room_category', $message . ' [room-op:' . $operationToken . ']');
            session()->forget('booking_room_operation_preview');

            DB::commit();

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể đổi toàn bộ hạng phòng: ' . $e->getMessage());
        }
    }

    public function discardRoomOperationPreview(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        session()->forget('booking_room_operation_preview');
        session()->forget('_old_input');

        return redirect()->route('admin.bookings.show', $booking->id);
    }

    private function roomOperationAlreadyApplied(Booking $booking, string $operationToken): bool
    {
        return BookingLog::query()
            ->where('booking_id', $booking->id)
            ->where('description', 'like', '%[room-op:' . $operationToken . ']%')
            ->exists();
    }

    private function assertRoomOperationPreviewToken(Booking $booking, string $operation, string $operationToken): void
    {
        $preview = session('booking_room_operation_preview');

        if (!is_array($preview)
            || (int) ($preview['booking_id'] ?? 0) !== (int) $booking->id
            || (string) ($preview['operation'] ?? '') !== $operation
            || !hash_equals((string) ($preview['token'] ?? ''), $operationToken)) {
            throw new \RuntimeException('Bản xem trước đã hết hiệu lực hoặc đã được thay bằng thao tác khác. Hãy xem trước lại trước khi xác nhận.');
        }
    }

    private function lockBookingForRoomOperation(Booking $booking): Booking
    {
        $locked = Booking::whereKey($booking->id)
            ->lockForUpdate()
            ->with(['bookingRooms.room.category', 'roomCategory'])
            ->firstOrFail();

        if (!in_array($locked->status, ['confirmed', 'checked_in'], true)) {
            throw new \Exception('Booking vừa thay đổi trạng thái. Chỉ booking đã xác nhận hoặc đang ở mới được sửa phòng.');
        }

        if ($locked->roomInspections()->whereIn('status', ['pending', 'reported'])->exists()) {
            throw new \Exception('Booking đã yêu cầu kiểm tra phòng, không thể thay đổi danh sách/hạng phòng nữa.');
        }

        if ($locked->status === 'checked_in' && $locked->check_out_at && now('Asia/Ho_Chi_Minh')->greaterThanOrEqualTo($locked->check_out_at)) {
            throw new \Exception('Booking đã đến hoặc quá giờ trả phòng. Hãy gia hạn thời gian lưu trú trước khi thêm hoặc đổi phòng.');
        }

        BookingRoom::where('booking_id', $locked->id)->lockForUpdate()->get();

        return $locked;
    }

    private function handleExtraGuestFees(
        Booking $booking,
        array $data,
        int $actualAdultCount,
        int $actualChildCount
    ) {
        $serviceIds = $data['extra_service_ids'] ?? [];

        $booking->loadMissing(['bookingRooms.room.category']);

        $adultCapacity = (int) $booking->bookingRooms->sum(
            fn ($bookingRoom) => max(0, (int) ($bookingRoom->room?->category?->adult_capacity ?? 0))
        );
        $minorCapacity = (int) $booking->bookingRooms->sum(
            fn ($bookingRoom) => max(0, (int) ($bookingRoom->room?->category?->child_capacity ?? 0))
        );

        $adultOver = max(0, $actualAdultCount - $adultCapacity);
        $childOver = max(0, $actualChildCount - $minorCapacity);

        $required = [
            'adult' => $adultOver,
            'child' => $childOver,
        ];
        $labels = [
            'adult' => 'người lớn',
            'child' => 'trẻ em',
        ];

        $validRows = [];
        foreach ($required as $guestType => $quantity) {
            if ($quantity <= 0) {
                continue;
            }

            $serviceId = $serviceIds[$guestType] ?? null;
            if (empty($serviceId)) {
                throw new \Exception('Vui lòng chọn loại phụ thu cho ' . $labels[$guestType] . ' vượt sức chứa.');
            }

            $service = Service::whereKey($serviceId)
                ->where('type', 'occupancy_fee')
                ->where('status', 'active')
                ->first();
            if (!$service) {
                throw new \Exception('Khoản phụ thu cho ' . $labels[$guestType] . ' không hợp lệ hoặc đã bị ẩn.');
            }

            $validRows[] = compact('guestType', 'quantity', 'service');
        }

        if ($validRows === []) {
            throw new \Exception('Không xác định được khoản phụ thu vượt sức chứa.');
        }

        $totalExtraFee = 0;
        $feeDescriptions = [];
        foreach ($validRows as $row) {
            /** @var \App\Models\Service $service */
            $service = $row['service'];
            $unitPrice = (float) $service->price;
            $quantity = (int) $row['quantity'];
            $guestType = (string) $row['guestType'];
            $total = $unitPrice * $quantity;
            $systemMarker = '[capacity_type:' . $guestType . ']';

            BookingServiceItem::create([
                'booking_id' => $booking->id,
                'scope' => 'booking',
                'booking_room_id' => null,
                'room_id_snapshot' => null,
                'source_type' => 'checkin_capacity_fee',
                'service_id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'billing_rule_snapshot' => 'once',
                'unit_price' => $unitPrice,
                'base_quantity' => $quantity,
                'nights_snapshot' => 1,
                'rooms_snapshot' => max(1, $booking->bookingRooms->count()),
                'people_snapshot' => $quantity,
                'quantity' => $quantity,
                'billing_status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'total' => $total,
                'note' => $systemMarker . ' Phụ thu vượt tổng sức chứa booking cho ' . $labels[$guestType],
            ]);

            $totalExtraFee += $total;
            $feeDescriptions[] = $service->name . ' x ' . $quantity . ': '
                . number_format($total, 0, ',', '.') . 'đ';
        }

        return 'Đã ghi phụ thu vượt tổng sức chứa: ' . implode('; ', $feeDescriptions)
            . '. Tổng phụ thu: ' . number_format($totalExtraFee, 0, ',', '.') . 'đ.';
    }

    private function handleAddRoomToBooking(
        Booking $booking,
        array &$data,
        ?int $actualAdultCount = null,
        ?int $actualChildCount = null
    ) {
        if (empty($data['additional_room_category_id'])) {
            throw new \Exception('Vui lòng chọn hạng phòng cần thêm.');
        }

        $quantity = (int) ($data['additional_room_quantity'] ?? 1);

        // Phòng phát sinh được phép thêm cả trước và sau check-in nếu còn inventory.
        // Không ép số người lớn phải >= số phòng: khách có thể giữ thêm phòng trước
        // cho người đi cùng đến sau. Phòng mới khởi tạo occupancy 0/0 và số khách
        // của booking chỉ thay đổi khi lễ tân cập nhật khách thực tế/chủ động thêm khách.

        $category = RoomCategory::where('status', 'active')
            ->find($data['additional_room_category_id']);

        if (!$category) {
            throw new \Exception('Hạng phòng cần thêm không hợp lệ.');
        }

        $currentAdultCapacity = $booking->bookingRooms->reduce(function ($total, $bookingRoom) {
            return $total + ($bookingRoom->room->category->adult_capacity ?? 0);
        }, 0);

        $currentChildCapacity = $booking->bookingRooms->reduce(function ($total, $bookingRoom) {
            return $total + ($bookingRoom->room->category->child_capacity ?? 0);
        }, 0);

        $newAdultCapacity = $currentAdultCapacity + ($category->adult_capacity * $quantity);
        $newChildCapacity = $currentChildCapacity + ($category->child_capacity * $quantity);

        if (
            $actualAdultCount !== null
            && $actualChildCount !== null
            && ($actualAdultCount > $newAdultCapacity || $actualChildCount > $newChildCapacity)
        ) {
            throw new \Exception('Số phòng thêm vẫn chưa đủ sức chứa cho số khách của booking.');
        }

        $rooms = $this->findAvailableRoomsForCheckIn(
            $category->id,
            $quantity,
            $booking->check_in_at,
            $booking->check_out_at,
            $data['prefer_near_current_rooms'] ?? false,
            $booking,
            (string) ($data['room_assignment_mode'] ?? 'auto'),
            collect($data['target_room_ids'] ?? [])->map(fn ($id) => (int) $id)->all()
        );

        if ($rooms->count() < $quantity) {
            throw new \Exception('Không còn đủ phòng trống thuộc hạng phòng đã chọn.');
        }

        $nightCount = $this->getNightCount($booking);
        $affectedRoomIds = [];

        foreach ($rooms as $room) {
            $affectedRoomIds[] = (int) $room->id;
            // Chặn phòng trùng ở tầng ghi dữ liệu, kể cả khi cache quan hệ hoặc
            // truy vấn chọn phòng bị sai trong tương lai.
            $alreadyAssigned = BookingRoom::query()
                ->where('booking_id', $booking->id)
                ->where('room_id', $room->id)
                ->exists();

            if ($alreadyAssigned) {
                throw new \Exception(
                    'Phòng ' . ($room->room_number ?? $room->id)
                    . ' đã có trong booking, không thể thêm trùng.'
                );
            }

            $newRoomPrice = (float) $category->price;
            $historyAdjustment = 0.0;
            $historyNote = null;

            // Nếu khách đã ở rồi mới thêm phòng, phòng mới chỉ được tính từ thời
            // điểm phát sinh. Không được reprice ngược các đêm/giờ trước đó.
            if ($booking->status === 'checked_in' && $booking->check_in_at && $booking->check_out_at) {
                $nowAtHotel = now('Asia/Ho_Chi_Minh');

                if ($booking->booking_type === 'hourly') {
                    $totalMinutes = max(1, Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
                        ->diffInMinutes(Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')));
                    $remainingMinutes = max(1, $nowAtHotel->diffInMinutes(
                        Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh'),
                        false
                    ));
                    $remainingMinutes = max(1, min($totalMinutes, $remainingMinutes));
                    $pricingPolicy = app(StayPricingPolicyService::class);
                    $fullCharge = (float) $pricingPolicy->shortStay($newRoomPrice, 1, $totalMinutes, $booking)['amount'];
                    $remainingCharge = (float) $pricingPolicy->shortStay($newRoomPrice, 1, $remainingMinutes, $booking)['amount'];
                    $historyAdjustment = round($remainingCharge - $fullCharge, 0);
                    $historyNote = 'Phòng phát sinh giữa ca; chỉ tính phần thời gian còn lại.';
                } else {
                    $checkInDay = Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->startOfDay();
                    $checkOutDay = Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();
                    $effectiveDay = $nowAtHotel->copy()->startOfDay()->min($checkOutDay);
                    $elapsedNights = $effectiveDay->gt($checkInDay)
                        ? min($nightCount, (int) $checkInDay->diffInDays($effectiveDay))
                        : 0;
                    $historyAdjustment = round(-$newRoomPrice * $elapsedNights, 0);
                    if ($elapsedNights > 0) {
                        $historyNote = 'Phòng phát sinh giữa kỳ; không tính ' . $elapsedNights . ' đêm đã qua.';
                    }
                }
            }

            $reason = trim((string) ($data['add_room_reason'] ?? 'Khách phát sinh nhu cầu thêm phòng.'));
            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'adult_count' => 0,
                'child_count' => 0,
                'price_at_booking' => $newRoomPrice,
                'surcharge' => $historyAdjustment,
                'surcharge_reason' => substr(trim($reason . ($historyNote ? ' | ' . $historyNote : '')), 0, 255),
                'created_at' => now(),
            ]);
            app(\App\Services\RoomPreparationService::class)
                ->flagPriorityIfNeeded($booking, $room, 'lễ tân thêm phòng vào booking');

            if ($booking->status === 'checked_in' && $room->status === 'available') {
                $room->update([
                    'status' => 'occupied',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);
            }
            // Nếu phòng đang dọn, vẫn được gán vào booking nhưng giữ nguyên trạng thái
            // cleaning. RoomPreparationService ở trên sẽ tạo yêu cầu dọn ưu tiên; chỉ
            // khi buồng phòng hoàn tất thì phòng mới được phép nhận khách.

        }

        if ($booking->status !== 'checked_in') {
            app(RoomReservationStatusService::class)->syncRoomIds($affectedRoomIds);
        }

        $booking->room_quantity += $quantity;
        $booking->save();

        // Không rebalance occupancy khi THÊM phòng ở bất kỳ trạng thái nào.
        // Mục tiêu của thao tác này là bán/giữ thêm inventory, không phải thay đổi
        // số khách hay tự chuyển khách hiện có sang phòng mới. Phòng mới giữ 0/0
        // cho đến khi lễ tân cập nhật khách/đại diện theo nhu cầu thực tế.

        $this->repriceCurrentBooking($booking);

        return 'Đã thêm '
            . $quantity
            . ' phòng hạng '
            . $category->name
            . ' vào booking. Lý do: '
            . ($data['add_room_reason'] ?? 'Khách phát sinh nhu cầu thêm phòng.');
    }

    private function handleChangeRoomCategory(
        Booking $booking,
        array &$data,
        ?int $actualAdultCount = null,
        ?int $actualChildCount = null
    ) {
        if (empty($data['new_room_category_id'])) {
            throw new \Exception('Vui lòng chọn hạng phòng mới.');
        }

        $newCategory = RoomCategory::where('status', 'active')->find($data['new_room_category_id']);
        if (!$newCategory) {
            throw new \Exception('Hạng phòng mới không hợp lệ.');
        }

        $booking->loadMissing('bookingRooms.room.category');
        app(\App\Services\BookingRoomOccupancyAllocator::class)->synchronizeBooking($booking);
        $booking->load('bookingRooms.room.category');
        $roomQuantity = max(1, $booking->bookingRooms->count());
        $newAdultCapacity = (int) $newCategory->adult_capacity * $roomQuantity;
        $newChildCapacity = (int) $newCategory->child_capacity * $roomQuantity;
        $bookingAdults = max(0, (int) $booking->adult_count);
        $bookingChildren = max(0, (int) $booking->child_count);

        if ($bookingAdults > $newAdultCapacity || $bookingChildren > $newChildCapacity) {
            throw new \Exception('Hạng phòng mới không đủ sức chứa cho số khách hiện tại. Vui lòng chọn hạng khác hoặc thêm phòng.');
        }
        foreach ($booking->bookingRooms as $existingRoom) {
            if ((int) $existingRoom->adult_count > (int) $newCategory->adult_capacity
                || (int) $existingRoom->child_count > (int) $newCategory->child_capacity) {
                throw new \Exception('Phân bổ khách hiện tại của phòng '
                    . ($existingRoom->room?->room_number ?? ('#' . $existingRoom->id))
                    . ' vượt sức chứa của hạng mới. Hãy phân lại khách hoặc chọn hạng phù hợp hơn.');
            }
        }

        $newRooms = $this->resolveCategoryChangeRooms(
            $booking,
            (int) $newCategory->id,
            $roomQuantity,
            $data
        );
        if ($newRooms->count() < $roomQuantity) {
            throw new \Exception('Không còn đủ phòng trống thuộc hạng phòng mới trong thời gian booking.');
        }

        $nightCount = $this->getNightCount($booking);
        $oldBookingRooms = $booking->bookingRooms->sortBy('id')->values();
        $newRooms = $newRooms->values();
        $difference = 0;
        $changeDescriptions = [];
        $affectedRoomIds = [];

        foreach ($oldBookingRooms as $index => $bookingRoom) {
            $oldRoom = $bookingRoom->room;
            $newRoom = $newRooms->get($index);
            if (!$oldRoom || !$newRoom) {
                throw new \Exception('Không thể ghép đủ phòng cũ và phòng mới.');
            }

            $affectedRoomIds[] = (int) $oldRoom->id;
            $affectedRoomIds[] = (int) $newRoom->id;

            $oldCategory = $oldRoom->category;
            $oldPrice = (float) $bookingRoom->price_at_booking;
            $newPrice = (float) $newCategory->price;
            $changePricing = $this->calculateRoomChangePricing($booking, $bookingRoom, $newPrice);
            $roomDifference = $changePricing['price_difference_total'];
            $difference += $roomDifference;

            $reason = $data['change_category_reason'] ?? 'Lễ tân đổi toàn bộ booking sang hạng khác.';
            $bookingRoom->update([
                'room_id' => $newRoom->id,
                'price_at_booking' => $newPrice,
                'surcharge' => $changePricing['new_surcharge'],
                'surcharge_reason' => $this->mergeRoomSurchargeReason(
                    $bookingRoom->surcharge_reason,
                    $reason,
                    $changePricing['history_note']
                ),
            ]);

            if ($booking->status === 'checked_in') {
                $oldRoomNextStatus = $oldRoom->status === 'maintenance' ? 'maintenance' : 'cleaning';
                $oldRoom->update([
                    'status' => $oldRoomNextStatus,
                    'status_from' => $oldRoomNextStatus === 'maintenance'
                        ? ($oldRoom->status_from ?: now('Asia/Ho_Chi_Minh'))
                        : now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);
                $newRoom->update([
                    'status' => 'occupied',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);
            }
            // Với booking confirmed nhưng chưa check-in, chỉ thay lịch gán phòng.
            // Không đổi room.status để không ghi đè trạng thái vận hành hiện tại.

            BookingRoomChange::create([
                'booking_id' => $booking->id,
                'booking_room_id' => $bookingRoom->id,
                'old_room_id' => $oldRoom->id,
                'new_room_id' => $newRoom->id,
                'old_room_category_id' => $oldRoom->room_category_id,
                'new_room_category_id' => $newCategory->id,
                'old_room_price' => $oldPrice,
                'new_room_price' => $newPrice,
                'night_count' => $changePricing['charged_nights'],
                'price_difference_total' => $roomDifference,
                'change_source' => 'front_desk',
                'reason' => $data['change_category_reason'] ?? null,
                'changed_by' => Auth::id(),
            ]);

            $changeDescriptions[] = 'phòng ' . $oldRoom->room_number . ' → ' . $newRoom->room_number;
        }

        if ($booking->status !== 'checked_in') {
            app(RoomReservationStatusService::class)->syncRoomIds($affectedRoomIds);
        }

        $booking->room_category_id = $newCategory->id;
        $booking->save();

        $manualChangeSelectionFee = app(BookingFinancialService::class)->addManualRoomChangeSelectionFee(
            $booking,
            (string) ($data['room_assignment_mode'] ?? 'auto'),
            $roomQuantity
        );
        $this->repriceCurrentBooking($booking);

        return 'Đã đổi toàn bộ sang hạng ' . $newCategory->name
            . ' (' . implode(', ', $changeDescriptions) . '). Hệ thống đã cập nhật giá phòng và ghi lịch sử nâng/đổi hạng. '
            . 'Tiền chênh: ' . number_format($difference, 0, ',', '.') . 'đ. '
            . ($manualChangeSelectionFee > 0
                ? 'Phí chọn thủ công ' . $roomQuantity . ' phòng: +' . number_format($manualChangeSelectionFee, 0, ',', '.') . 'đ. '
                : 'Phí chọn phòng: 0đ (hệ thống tự chọn hoặc booking chưa ở). ')
            . 'Lễ tân có thể vào mục Mã ưu đãi / hỗ trợ khách để áp mã sau nếu cần. Lý do: '
            . ($data['change_category_reason'] ?? 'Khách yêu cầu đổi toàn bộ hạng phòng.');
    }

    private function handleChangeOneRoomCategory(Booking $booking, array &$data)
    {
        $bookingRoom = BookingRoom::where('booking_id', $booking->id)
            ->where('id', $data['booking_room_id'])
            ->with('room.category')
            ->first();

        if (!$bookingRoom || !$bookingRoom->room) {
            throw new \Exception('Phòng cần đổi không tồn tại trong booking này.');
        }

        $oldRoom = $bookingRoom->room;
        $oldCategory = $oldRoom->category;
        $newCategory = RoomCategory::where('status', 'active')->find($data['new_room_category_id']);
        if (!$newCategory) {
            throw new \Exception('Hạng phòng mới không hợp lệ.');
        }

        app(\App\Services\BookingRoomOccupancyAllocator::class)->synchronizeBooking($booking);
        $bookingRoom->refresh();
        if ((int) $bookingRoom->adult_count > (int) $newCategory->adult_capacity
            || (int) $bookingRoom->child_count > (int) $newCategory->child_capacity) {
            throw new \Exception('Hạng phòng mới không đủ sức chứa cho số khách đang được phân ở phòng '
                . $oldRoom->room_number . '. Hãy phân lại khách hoặc chọn hạng khác.');
        }

        $newRoom = $this->resolveCategoryChangeRooms(
            $booking,
            (int) $newCategory->id,
            1,
            $data
        )->first();
        if (!$newRoom) {
            throw new \Exception('Không còn phòng trống thuộc hạng phòng mới trong thời gian booking.');
        }

        $oldPrice = (float) $bookingRoom->price_at_booking;
        $newPrice = (float) $newCategory->price;
        $changePricing = $this->calculateRoomChangePricing($booking, $bookingRoom, $newPrice);
        $difference = $changePricing['price_difference_total'];

        $reason = $data['change_category_reason'] ?? 'Lễ tân đổi một phòng sang hạng khác.';
        $bookingRoom->update([
            'room_id' => $newRoom->id,
            'price_at_booking' => $newPrice,
            'surcharge' => $changePricing['new_surcharge'],
            'surcharge_reason' => $this->mergeRoomSurchargeReason(
                $bookingRoom->surcharge_reason,
                $reason,
                $changePricing['history_note']
            ),
        ]);
        if ($booking->status === 'checked_in') {
            $oldRoomNextStatus = $oldRoom->status === 'maintenance' ? 'maintenance' : 'cleaning';
            $oldRoom->update([
                'status' => $oldRoomNextStatus,
                'status_from' => $oldRoomNextStatus === 'maintenance'
                    ? ($oldRoom->status_from ?: now('Asia/Ho_Chi_Minh'))
                    : now('Asia/Ho_Chi_Minh'),
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

        BookingRoomChange::create([
            'booking_id' => $booking->id,
            'booking_room_id' => $bookingRoom->id,
            'old_room_id' => $oldRoom->id,
            'new_room_id' => $newRoom->id,
            'old_room_category_id' => $oldRoom->room_category_id,
            'new_room_category_id' => $newCategory->id,
            'old_room_price' => $oldPrice,
            'new_room_price' => $newPrice,
            'night_count' => $changePricing['charged_nights'],
            'price_difference_total' => $difference,
            'change_source' => 'front_desk',
            'reason' => $data['change_category_reason'] ?? null,
            'changed_by' => Auth::id(),
        ]);

        if ((int) $booking->room_quantity === 1) {
            $booking->room_category_id = $newCategory->id;
        }

        $booking->save();
        $manualChangeSelectionFee = app(BookingFinancialService::class)->addManualRoomChangeSelectionFee(
            $booking,
            (string) ($data['room_assignment_mode'] ?? 'auto'),
            1
        );
        $this->repriceCurrentBooking($booking);

        return 'Đã đổi phòng ' . $oldRoom->room_number . ' (' . ($oldCategory?->name ?? '---') . ') sang phòng '
            . $newRoom->room_number . ' (' . $newCategory->name . '). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. '
            . 'Tiền chênh: ' . number_format($difference, 0, ',', '.') . 'đ. '
            . ($manualChangeSelectionFee > 0
                ? 'Phí chọn phòng thủ công: +' . number_format($manualChangeSelectionFee, 0, ',', '.') . 'đ. '
                : 'Phí chọn phòng: 0đ (hệ thống tự chọn hoặc booking chưa ở). ')
            . 'Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: '
            . ($data['change_category_reason'] ?? 'Khách yêu cầu đổi phòng.');
    }


    /**
     * Khi đổi hạng giữa thời gian lưu trú, chỉ phần đêm chưa sử dụng được tính
     * theo giá phòng mới. Phần các đêm đã ở được giữ nguyên bằng một khoản điều
     * chỉnh trên booking_rooms.surcharge, tránh việc ghi đè giá mới cho cả kỳ.
     */
    private function calculateRoomChangePricing(Booking $booking, BookingRoom $bookingRoom, float $newPrice): array
    {
        $oldPrice = (float) $bookingRoom->price_at_booking;
        $existingSurcharge = (float) $bookingRoom->surcharge;

        if ($booking->booking_type === 'hourly' && $booking->check_in_at && $booking->check_out_at) {
            $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
            $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
            $totalMinutes = max(1, $checkInAt->diffInMinutes($checkOutAt));
            $pricingPolicy = app(StayPricingPolicyService::class);
            $oldFull = (float) $pricingPolicy->shortStay($oldPrice, 1, $totalMinutes, $booking)['amount'];
            $newFull = (float) $pricingPolicy->shortStay($newPrice, 1, $totalMinutes, $booking)['amount'];

            if ($booking->status === 'checked_in') {
                $nowAtHotel = now('Asia/Ho_Chi_Minh');
                $remainingMinutes = max(0, $nowAtHotel->diffInMinutes($checkOutAt, false));
                $remainingMinutes = min($totalMinutes, $remainingMinutes);

                if ($remainingMinutes > 0) {
                    $oldRemaining = (float) $pricingPolicy->shortStay($oldPrice, 1, $remainingMinutes, $booking)['amount'];
                    $newRemaining = (float) $pricingPolicy->shortStay($newPrice, 1, $remainingMinutes, $booking)['amount'];
                    $priceDifferenceTotal = round($newRemaining - $oldRemaining, 0);
                } else {
                    $priceDifferenceTotal = 0.0;
                }

                // Sau khi price_at_booking đổi sang giá mới, currentTotal sẽ tính lại cả
                // phiên bằng giá mới. Khoản surcharge này triệt phần lịch sử để chỉ phần
                // thời lượng còn lại chịu giá mới.
                $fullDifference = $newFull - $oldFull;
                $historyAdjustment = $priceDifferenceTotal - $fullDifference;
                $newSurcharge = round($existingSurcharge + $historyAdjustment, 0);

                return [
                    'total_nights' => 1,
                    'elapsed_nights' => 0,
                    'charged_nights' => $remainingMinutes > 0 ? 1 : 0,
                    'new_surcharge' => $newSurcharge,
                    'price_difference_total' => $priceDifferenceTotal,
                    'history_note' => $remainingMinutes > 0
                        ? 'Booking theo giờ: giữ nguyên phần thời lượng đã sử dụng; giá hạng mới chỉ áp dụng cho phần thời gian còn lại.'
                        : 'Booking theo giờ đã hết thời lượng; đổi hạng không hồi tố tiền phòng đã sử dụng.',
                ];
            }

            return [
                'total_nights' => 1,
                'elapsed_nights' => 0,
                'charged_nights' => 1,
                'new_surcharge' => $existingSurcharge,
                'price_difference_total' => round($newFull - $oldFull, 0),
                'history_note' => null,
            ];
        }

        $totalNights = max(1, $this->getNightCount($booking));
        $elapsedNights = 0;

        if ($booking->status === 'checked_in' && $booking->check_in_at && $booking->check_out_at) {
            $checkInDay = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->startOfDay();
            $checkOutDay = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();
            $effectiveDay = \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->startOfDay();

            if ($effectiveDay->gt($checkOutDay)) {
                $effectiveDay = $checkOutDay->copy();
            }

            if ($effectiveDay->gt($checkInDay)) {
                $elapsedNights = (int) $checkInDay->diffInDays($effectiveDay);
                $elapsedNights = min($totalNights, max(0, $elapsedNights));
            }
        }

        $chargedNights = max(0, $totalNights - $elapsedNights);
        $priceDeltaPerNight = $newPrice - $oldPrice;
        $historyAdjustment = $priceDeltaPerNight * $elapsedNights;
        $newSurcharge = round($existingSurcharge - $historyAdjustment, 0);
        $priceDifferenceTotal = round($priceDeltaPerNight * $chargedNights, 0);

        return [
            'total_nights' => $totalNights,
            'elapsed_nights' => $elapsedNights,
            'charged_nights' => $chargedNights,
            'new_surcharge' => $newSurcharge,
            'price_difference_total' => $priceDifferenceTotal,
            'history_note' => $elapsedNights > 0
                ? 'Giữ giá cũ cho ' . $elapsedNights . ' đêm đã sử dụng; giá mới chỉ áp dụng ' . $chargedNights . ' đêm còn lại.'
                : null,
        ];
    }

    private function mergeRoomSurchargeReason(?string $current, string $reason, ?string $historyNote): string
    {
        $parts = array_values(array_filter([
            trim((string) $current),
            trim($reason),
            trim((string) $historyNote),
        ], fn ($part) => $part !== ''));

        return substr(implode(' | ', $parts), 0, 255);
    }


    private function previewBookingRoomOperation(
        Booking $booking,
        string $operation,
        array &$payload,
        \Closure $mutator,
        string $actionUrl
    ) {
        DB::beginTransaction();

        try {
            $before = $this->bookingRoomOperationSnapshot($booking->fresh());
            $message = $mutator();

            $afterBooking = Booking::findOrFail($booking->id);
            $after = $this->bookingRoomOperationSnapshot($afterBooking);

            $preview = [
                'booking_id' => (int) $booking->id,
                'operation' => $operation,
                'token' => (string) \Illuminate\Support\Str::uuid(),
                'title' => match ($operation) {
                    'add_room' => 'Xem trước thêm phòng',
                    'change_one_room' => 'Xem trước đổi hạng 1 phòng',
                    'change_all_rooms' => 'Xem trước đổi toàn bộ hạng phòng',
                    default => 'Xem trước thay đổi phòng',
                },
                'message' => $message,
                'action_url' => $actionUrl,
                'payload' => collect($payload)
                    ->except(['confirm_operation', 'operation_token', '_token', '_method'])
                    ->all(),
                'before' => $before,
                'after' => $after,
                'promotion_changes' => $this->comparePromotionSnapshots(
                    $before['promotions'],
                    $after['promotions']
                ),
                'service_changes' => $this->compareServiceSnapshots(
                    $before['services'],
                    $after['services']
                ),
            ];

            DB::rollBack();

            session()->put('booking_room_operation_preview', $preview);
            session()->flashInput($payload);

            return redirect(route('admin.bookings.show', $booking) . '#room-operation-preview');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Không thể xem trước thay đổi: ' . $e->getMessage());
        }
    }

    private function bookingRoomOperationSnapshot(Booking $booking): array
    {
        $booking->load([
            'bookingRooms.room.category',
            'bookingPromotions.bookingRoom.room',
            'serviceItems.bookingRoom.room',
            'roomInspections.items',
            'payments',
        ]);

        $financial = app(BookingFinancialService::class);
        $nightCount = $this->getNightCount($booking);
        $roomTotal = (float) $booking->bookingRooms->sum(
            fn (BookingRoom $room) => ((float) $room->price_at_booking * $nightCount)
                + (float) $room->surcharge
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

        return [
            'room_quantity' => $booking->bookingRooms->count(),
            'night_count' => $nightCount,
            'rooms' => $booking->bookingRooms->map(fn (BookingRoom $room) => [
                'booking_room_id' => (int) $room->id,
                'room_number' => $room->room?->room_number ?? '---',
                'category_name' => $room->room?->category?->name ?? '---',
                'price_per_night' => (float) $room->price_at_booking,
                'total' => ((float) $room->price_at_booking * $nightCount) + (float) $room->surcharge,
            ])->values()->all(),
            'room_total' => round($roomTotal, 0),
            'service_total' => round($serviceTotal, 0),
            'inspection_total' => round($inspectionTotal, 0),
            'discount_total' => round((float) $booking->discount_amount, 0),
            'total' => round($total, 0),
            'required_deposit' => round($requiredDeposit, 0),
            'paid_total' => round($paidTotal, 0),
            'deposit_shortfall' => max(0, round($requiredDeposit - $paidTotal, 0)),
            'remaining' => max(0, round($total - $paidTotal, 0)),
            'overpayment' => max(0, round($paidTotal - $total, 0)),
            'promotions' => $booking->bookingPromotions->map(fn ($usage) => [
                'key' => ($usage->scope ?? 'booking') . ':'
                    . ((int) ($usage->booking_room_id ?? 0)) . ':'
                    . strtoupper((string) $usage->code_snapshot),
                'code' => $usage->code_snapshot,
                'scope' => $usage->scope ?? 'booking',
                'room_number' => $usage->bookingRoom?->room?->room_number,
                'discount' => (float) $usage->discount_amount,
            ])->values()->all(),
            'services' => $booking->serviceItems
                ->where('billing_status', 'confirmed')
                ->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'name' => $item->name,
                    'scope' => $item->scope ?? 'booking',
                    'room_number' => $item->bookingRoom?->room?->room_number,
                    'total' => (float) $item->total,
                ])->values()->all(),
        ];
    }

    private function comparePromotionSnapshots(array $before, array $after): array
    {
        $beforeByKey = collect($before)->keyBy('key');
        $afterByKey = collect($after)->keyBy('key');
        $changes = [];

        foreach ($beforeByKey as $key => $item) {
            if (!$afterByKey->has($key)) {
                $changes[] = array_merge($item, ['status' => 'removed', 'old_discount' => $item['discount'], 'new_discount' => 0]);
                continue;
            }

            $new = $afterByKey->get($key);
            $changes[] = array_merge($new, [
                'status' => abs((float) $new['discount'] - (float) $item['discount']) > 0.01 ? 'recalculated' : 'kept',
                'old_discount' => (float) $item['discount'],
                'new_discount' => (float) $new['discount'],
            ]);
        }

        foreach ($afterByKey as $key => $item) {
            if (!$beforeByKey->has($key)) {
                $changes[] = array_merge($item, ['status' => 'added', 'old_discount' => 0, 'new_discount' => $item['discount']]);
            }
        }

        return array_values($changes);
    }

    private function compareServiceSnapshots(array $before, array $after): array
    {
        $beforeById = collect($before)->keyBy('id');
        $afterById = collect($after)->keyBy('id');
        $changes = [];

        foreach ($beforeById as $id => $item) {
            if (!$afterById->has($id)) {
                $changes[] = array_merge($item, ['status' => 'removed', 'old_total' => $item['total'], 'new_total' => 0]);
                continue;
            }

            $new = $afterById->get($id);
            if (abs((float) $new['total'] - (float) $item['total']) > 0.01) {
                $changes[] = array_merge($new, [
                    'status' => 'recalculated',
                    'old_total' => (float) $item['total'],
                    'new_total' => (float) $new['total'],
                ]);
            }
        }

        foreach ($afterById as $id => $item) {
            if (!$beforeById->has($id)) {
                $changes[] = array_merge($item, ['status' => 'added', 'old_total' => 0, 'new_total' => $item['total']]);
            }
        }

        return array_values($changes);
    }


    /**
     * Tính lại toàn bộ booking từ các dòng phòng hiện tại. Bộ tính dùng lại cho
     * thêm phòng, đổi một phòng, đổi toàn bộ hạng và các thay đổi lịch lưu trú.
     * Mã/dịch vụ theo phòng được xét đúng booking_room_id; mã toàn booking được
     * kiểm tra lại sau khi toàn bộ giá phòng đã thay đổi.
     */
    private function repriceCurrentBooking(Booking $booking): array
    {
        app(BookingOccupancyFeeService::class)->reconcile($booking->refresh());

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

        $preview = app(BookingRepricingService::class)->preview(
            $booking,
            \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
            \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh'),
            $oneNightRoomTotal
        );

        app(BookingRepricingService::class)->apply($booking, $preview);

        return $preview;
    }


    private function resolveCategoryChangeRooms(
        Booking $booking,
        int $roomCategoryId,
        int $quantity,
        array &$data
    ) {
        $quantity = max(1, $quantity);
        $mode = (string) ($data['room_assignment_mode'] ?? 'auto');
        if (!in_array($mode, ['auto', 'manual'], true)) {
            throw new \Exception('Cách chọn phòng không hợp lệ.');
        }

        $booking->loadMissing('bookingRooms.room');
        $assignedRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $query = Room::query()
            ->where('room_category_id', $roomCategoryId)
            ->whereNotIn('id', $assignedRoomIds)
            ->bookableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id);

        if ($booking->status === 'checked_in') {
            $query->where('status', 'available');
        }

        $rawIds = collect($data['target_room_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($rawIds->isNotEmpty()) {
            if ($rawIds->unique()->count() !== $rawIds->count()) {
                throw new \Exception('Không được chọn trùng phòng.');
            }
            if ($rawIds->count() !== $quantity) {
                throw new \Exception('Phải chọn đúng ' . $quantity . ' phòng cần chuyển.');
            }

            $roomsById = (clone $query)
                ->whereIn('id', $rawIds->all())
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($room) => (int) $room->id);

            if ($roomsById->count() !== $quantity) {
                throw new \Exception('Có phòng chọn tay sai hạng, đang bận/được giữ hoặc đã thuộc booking hiện tại.');
            }

            return $rawIds->map(fn ($id) => $roomsById->get((int) $id))->values();
        }

        if ($mode === 'manual') {
            throw new \Exception('Vui lòng chọn đúng ' . $quantity . ' phòng cụ thể.');
        }

        $rooms = (clone $query)
            ->inRandomOrder()
            ->lockForUpdate()
            ->take($quantity)
            ->get();

        if ($rooms->count() !== $quantity) {
            throw new \Exception('Không còn đủ phòng trống thuộc hạng phòng mới trong thời gian booking.');
        }

        // Ghi lại lựa chọn tự động vào payload preview để bước xác nhận dùng đúng
        // chính các phòng lễ tân vừa xem, không random lại phòng khác.
        $data['target_room_ids'] = $rooms->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        return $rooms->values();
    }

    private function findAvailableRoomsForCheckIn(
        int $roomCategoryId,
        int $quantity,
        $checkInAt,
        $checkOutAt,
        bool $preferNearCurrentRooms = false,
        ?Booking $booking = null,
        string $assignmentMode = 'auto',
        array $targetRoomIds = []
    ) {
        $quantity = max(1, $quantity);
        if (!in_array($assignmentMode, ['auto', 'manual'], true)) {
            throw new \Exception('Cách chọn phòng không hợp lệ.');
        }

        $assignedRoomIds = $booking
            ? $booking->bookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all()
            : [];

        // Luồng thêm phòng chỉ được phép lấy phòng Sẵn sàng hoặc Đang dọn.
        // Không tính phòng Đã đặt/Đang ở/Bảo trì/Chờ kiểm tra dù chúng có thể rảnh
        // ở một thời điểm khác. Cleaning được phép chọn để lễ tân giữ trước và hệ
        // thống tạo việc dọn ưu tiên.
        $query = Room::query()
            ->where('room_category_id', $roomCategoryId)
            ->when($assignedRoomIds !== [], fn ($q) => $q->whereNotIn('id', $assignedRoomIds))
            ->availableForPeriod(
                $checkInAt,
                $checkOutAt,
                $booking?->id,
                (int) ($booking?->cleaning_buffer_minutes ?? 0),
                ['available', 'cleaning'],
                true
            );

        $selectedIds = collect($targetRoomIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($assignmentMode === 'manual') {
            if ($selectedIds->count() !== $quantity) {
                throw new \Exception('Vui lòng chọn đúng ' . $quantity . ' phòng cụ thể.');
            }

            $rooms = (clone $query)
                ->whereIn('id', $selectedIds->all())
                ->lockForUpdate()
                ->get()
                ->keyBy(fn ($room) => (int) $room->id);

            if ($rooms->count() !== $quantity) {
                throw new \Exception('Có phòng đã chọn không còn hợp lệ: chỉ phòng Sẵn sàng hoặc Đang dọn và không xung đột booking mới được dùng.');
            }

            return $selectedIds->map(fn ($id) => $rooms->get((int) $id))->values();
        }

        // Hệ thống luôn ưu tiên phòng sẵn sàng trước. Chỉ khi không đủ mới lấy
        // phòng đang dọn; các phòng cleaning được chọn sẽ phát sinh task dọn nhanh.
        $query->orderByRaw("CASE rooms.status WHEN 'available' THEN 0 WHEN 'cleaning' THEN 1 ELSE 9 END");

        if ($preferNearCurrentRooms && $booking) {
            $currentFloors = $booking->bookingRooms
                ->pluck('room.floor_number')
                ->filter()
                ->unique()
                ->values();

            if ($currentFloors->isNotEmpty()) {
                $query->orderByRaw('ABS(floor_number - ?) ASC', [(int) $currentFloors->first()]);
            }
        }

        return $query
            ->lockForUpdate()
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->take($quantity)
            ->get();
    }

    private function getNightCount(Booking $booking)
    {
        return max(
            1,
            (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400
        );
    }


    public function changeStayDates(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Chỉ booking chờ xác nhận hoặc đã xác nhận mới được đổi ngày lưu trú.');
        }

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking chưa có đủ thời gian nhận/trả phòng nên không thể đổi ngày.');
        }

        $data = $request->validate([
            'new_check_in_date' => 'required|date',
            'new_check_in_time' => 'nullable|date_format:H:i',
            'new_check_out_date' => 'required|date',
            'new_check_out_time' => 'nullable|date_format:H:i',
            'replacement_room_category_id' => 'nullable|integer|exists:room_categories,id',
            'confirm_reprice' => 'nullable|boolean',
        ], [
            'new_check_in_date.required' => 'Vui lòng chọn ngày nhận phòng mới.',
            'new_check_in_date.date' => 'Ngày nhận phòng mới không hợp lệ.',
            'new_check_in_time.date_format' => 'Giờ nhận phòng mới phải theo định dạng 24 giờ, ví dụ 07:00 hoặc 14:00.',
            'new_check_out_date.required' => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out_date.date' => 'Ngày trả phòng mới không hợp lệ.',
            'new_check_out_time.date_format' => 'Giờ trả phòng mới phải theo định dạng 24 giờ, ví dụ 12:00 hoặc 14:00.',
            'replacement_room_category_id.exists' => 'Hạng phòng thay thế không tồn tại.',
        ]);

        $oldCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        $newCheckInTime = $data['new_check_in_time'] ?? $oldCheckInAt->format('H:i');
        $newCheckOutTime = $data['new_check_out_time'] ?? $oldCheckOutAt->format('H:i');

        $newCheckInAt = \Carbon\Carbon::parse(
            $data['new_check_in_date'] . ' ' . $newCheckInTime . ':00',
            'Asia/Ho_Chi_Minh'
        );
        $newCheckOutAt = \Carbon\Carbon::parse(
            $data['new_check_out_date'] . ' ' . $newCheckOutTime . ':00',
            'Asia/Ho_Chi_Minh'
        );
        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

        if ($newCheckInAt->toDateString() < $nowVn->toDateString()) {
            return back()->withInput()->with('error', 'Ngày nhận phòng mới không được trước ngày hiện tại.');
        }

        if (
            $booking->booking_type !== 'hourly'
            && $newCheckOutAt->toDateString() <= $newCheckInAt->toDateString()
        ) {
            return back()->withInput()->with(
                'error',
                'Booking qua đêm phải có ngày trả phòng mới sau ngày nhận phòng mới ít nhất 1 ngày.'
            );
        }

        // Khi lễ tân chủ động chuyển booking từ một ngày tương lai về hôm nay,
        // đây là một lịch nhận phòng mới có hiệu lực ngay trong ngày. Không được lấy
        // giờ G của hôm nay để quy kết khách đến muộn/no-show chỉ vì thời điểm đổi lịch
        // đã qua giờ G. Với trường hợp này, mốc nhận phòng mới tối thiểu là thời điểm đổi lịch.
        $isRescheduledToToday = $booking->booking_type !== 'hourly'
            && $oldCheckInAt->toDateString() > $nowVn->toDateString()
            && $newCheckInAt->toDateString() === $nowVn->toDateString();

        if ($isRescheduledToToday && $newCheckInAt->lessThan($nowVn)) {
            $newCheckInAt = $nowVn->copy()->startOfMinute();
        }

        if ($newCheckOutAt->lessThanOrEqualTo($newCheckInAt)) {
            return back()->withInput()->with('error', 'Thời gian trả phòng mới phải sau thời gian nhận phòng mới.');
        }

        if ($newCheckOutAt->lessThanOrEqualTo($nowVn)) {
            return back()->withInput()->with('error', 'Thời gian trả phòng mới phải sau thời điểm hiện tại.');
        }

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
        ]);

        if ($booking->bookingRooms->count() === 0) {
            return back()->withInput()->with(
                'error',
                'Booking này chưa được gán phòng nên không thể đổi ngày lưu trú tự động.'
            );
        }

        $oldBookingRooms = $booking->bookingRooms->sortBy('id')->values();
        $roomQuantity = $oldBookingRooms->count();
        $currentRoomIds = $oldBookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->values()->toArray();
        $cleaningBufferMinutes = (int) ($booking->cleaning_buffer_minutes ?? 0);
        $newCheckOutWithCleaning = $newCheckOutAt->copy()->addMinutes($cleaningBufferMinutes);

        $oldNightCount = max(
            1,
            $oldCheckInAt->copy()->startOfDay()->diffInDays($oldCheckOutAt->copy()->startOfDay())
        );
        $newNightCount = max(
            1,
            $newCheckInAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
        );

        $oldOneNightRoomTotal = (float) $oldBookingRooms->sum(function ($bookingRoom) {
            return (float) $bookingRoom->price_at_booking;
        });

        if ($oldOneNightRoomTotal <= 0) {
            $oldOneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, $roomQuantity);
        }

        $selectedCategory = null;
        $targetRoomPlan = [];
        $roomChangeMessages = [];
        $selectedCategoryId = !empty($data['replacement_room_category_id'])
            ? (int) $data['replacement_room_category_id']
            : null;

        if ($selectedCategoryId) {
            $selectedCategory = RoomCategory::where('status', 'active')->find($selectedCategoryId);

            if (!$selectedCategory) {
                return back()->withInput()->with('error', 'Hạng phòng thay thế đã bị ẩn hoặc không còn hoạt động.');
            }

            $selectedAdultCapacity = (int) $selectedCategory->adult_capacity * $roomQuantity;
            $selectedChildCapacity = (int) $selectedCategory->child_capacity * $roomQuantity;
            if (
                $selectedAdultCapacity < (int) ($booking->adult_count ?? 0)
                || $selectedChildCapacity < ((int) ($booking->child_count ?? 0))
            ) {
                return $this->redirectWithStayDateCategoryOptions(
                    $request,
                    $booking,
                    $newCheckInAt,
                    $newCheckOutAt,
                    'Hạng ' . $selectedCategory->name
                        . ' không đủ sức chứa cho số khách của booking. Hệ thống đã lọc lại các hạng phù hợp.'
                );
            }

            $candidateRooms = $this->findAvailableRoomsForChangedStayDates(
                $selectedCategory->id,
                $roomQuantity,
                $newCheckInAt,
                $newCheckOutAt,
                $booking,
                []
            );

            if ($candidateRooms->count() < $roomQuantity) {
                return $this->redirectWithStayDateCategoryOptions(
                    $request,
                    $booking,
                    $newCheckInAt,
                    $newCheckOutAt,
                    'Hạng ' . $selectedCategory->name
                        . ' vừa được chọn không còn đủ '
                        . $roomQuantity
                        . ' phòng trong khung giờ mới. Hệ thống đã cập nhật lại danh sách hạng còn phòng.'
                );
            }

            foreach ($oldBookingRooms as $index => $bookingRoom) {
                $newRoom = $candidateRooms->get($index);

                if (!$newRoom) {
                    return back()->withInput()->with('error', 'Không thể ghép đủ phòng mới cho booking.');
                }

                $targetRoomPlan[] = [
                    'booking_room' => $bookingRoom,
                    'old_room' => $bookingRoom->room,
                    'new_room' => $newRoom,
                    'new_price' => (float) $selectedCategory->price,
                    'reason' => 'Đổi sang hạng '
                        . $selectedCategory->name
                        . ' để phù hợp với lịch lưu trú mới '
                        . $newCheckInAt->format('d/m/Y H:i')
                        . ' → '
                        . $newCheckOutAt->format('d/m/Y H:i')
                        . '.',
                ];
            }
        } else {
            $usedReplacementRoomIds = [];

            foreach ($oldBookingRooms as $bookingRoom) {
                $room = $bookingRoom->room;

                if (!$room) {
                    return back()->withInput()->with(
                        'error',
                        'Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.'
                    );
                }

                $conflictBookingRoom = $this->findConflictBookingRoom(
                    $room->id,
                    $booking->id,
                    $newCheckInAt,
                    $newCheckOutWithCleaning
                );

                if (!$conflictBookingRoom) {
                    $targetRoomPlan[] = [
                        'booking_room' => $bookingRoom,
                        'old_room' => $room,
                        'new_room' => $room,
                        'new_price' => (float) $bookingRoom->price_at_booking,
                        'reason' => null,
                    ];
                    continue;
                }

                $replacementRoom = $this->findAvailableRoomsForChangedStayDates(
                    (int) $room->room_category_id,
                    1,
                    $newCheckInAt,
                    $newCheckOutAt,
                    $booking,
                    array_merge($currentRoomIds, $usedReplacementRoomIds)
                )->first();

                if (!$replacementRoom) {
                    $conflictBooking = $conflictBookingRoom->booking;
                    $conflictText = 'Phòng '
                        . ($room->room_number ?? '---')
                        . ' bị trùng với booking '
                        . ($conflictBooking->booking_code ?? '')
                        . ' từ '
                        . \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ' đến '
                        . \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ', đồng thời không còn đủ phòng cùng hạng để tự đổi.';

                    return $this->redirectWithStayDateCategoryOptions(
                        $request,
                        $booking,
                        $newCheckInAt,
                        $newCheckOutAt,
                        $conflictText
                    );
                }

                $usedReplacementRoomIds[] = (int) $replacementRoom->id;
                $targetRoomPlan[] = [
                    'booking_room' => $bookingRoom,
                    'old_room' => $room,
                    'new_room' => $replacementRoom,
                    'new_price' => (float) $bookingRoom->price_at_booking,
                    'reason' => 'Đổi cùng hạng do phòng cũ bị trùng lịch khi sửa ngày nhận/trả.',
                ];
            }
        }

        $newOneNightRoomTotal = $selectedCategory
            ? ((float) $selectedCategory->price * $roomQuantity)
            : (float) collect($targetRoomPlan)->sum(fn ($plan) => (float) $plan['new_price']);

        $roomDelta = round(
            ($newOneNightRoomTotal * $newNightCount)
            - ($oldOneNightRoomTotal * $oldNightCount),
            0
        );

        $repricingPreview = app(BookingRepricingService::class)->preview(
            $booking,
            $newCheckInAt,
            $newCheckOutAt,
            $newOneNightRoomTotal,
            $selectedCategory ? (float) $selectedCategory->price : null
        );

        if (!$request->boolean('confirm_reprice')) {
            session()->put('stay_date_reprice_preview', array_merge($repricingPreview, [
                    'booking_id' => (int) $booking->id,
                    'new_check_in_date' => $newCheckInAt->toDateString(),
                    'new_check_in_time' => $newCheckInAt->format('H:i'),
                    'new_check_out_date' => $newCheckOutAt->toDateString(),
                    'new_check_out_time' => $newCheckOutAt->format('H:i'),
                    'replacement_room_category_id' => $selectedCategory?->id,
                    'target_category_name' => $selectedCategory?->name ?? $booking->roomCategory?->name,
                    'room_delta' => $roomDelta,
                ]));
            session()->flashInput($request->all());

            return redirect(route('admin.bookings.show', $booking) . '#stay-date-reprice-preview');
        }

        DB::beginTransaction();

        try {
            $targetRoomIds = collect($targetRoomPlan)
                ->pluck('new_room.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($targetRoomIds->count() !== $roomQuantity) {
                throw new \Exception('Danh sách phòng mới bị trùng. Vui lòng kiểm tra lại phương án.');
            }

            $stillAvailableRoomIds = Room::whereIn('id', $targetRoomIds->all())
                ->bookableForPeriod(
                    $newCheckInAt,
                    $newCheckOutAt,
                    $booking->id,
                    $cleaningBufferMinutes
                )
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($stillAvailableRoomIds->count() !== $targetRoomIds->count()) {
                throw new \Exception(
                    'Có phòng trong phương án vừa được booking khác sử dụng. Hãy kiểm tra lại lịch để hệ thống chọn phương án mới.'
                );
            }

            foreach ($targetRoomPlan as $plan) {
                /** @var \App\Models\BookingRoom $bookingRoom */
                $bookingRoom = $plan['booking_room'];
                /** @var \App\Models\Room $oldRoom */
                $oldRoom = $plan['old_room'];
                /** @var \App\Models\Room $newRoom */
                $newRoom = $plan['new_room'];
                $newPrice = (float) $plan['new_price'];

                if ((int) $oldRoom->id === (int) $newRoom->id) {
                    continue;
                }

                $oldPrice = (float) $bookingRoom->price_at_booking;
                $oldCategoryId = (int) $oldRoom->room_category_id;
                $newCategoryId = (int) $newRoom->room_category_id;
                $changeDifference = ($newPrice * $newNightCount) - ($oldPrice * $oldNightCount);

                $bookingRoom->update([
                    'room_id' => $newRoom->id,
                    'price_at_booking' => $newPrice,
                    'surcharge_reason' => trim(
                        ($bookingRoom->surcharge_reason ? $bookingRoom->surcharge_reason . ' | ' : '')
                        . ($plan['reason'] ?? 'Đổi phòng để phù hợp với lịch lưu trú mới.')
                    ),
                ]);

                // Đổi lịch lưu trú trước check-in không thay đổi trạng thái vận hành
                // hiện tại của phòng cũ hoặc phòng mới.

                BookingRoomChange::create([
                    'booking_id' => $booking->id,
                    'booking_room_id' => $bookingRoom->id,
                    'old_room_id' => $oldRoom->id,
                    'new_room_id' => $newRoom->id,
                    'old_room_category_id' => $oldCategoryId,
                    'new_room_category_id' => $newCategoryId,
                    'old_room_price' => $oldPrice,
                    'new_room_price' => $newPrice,
                    'night_count' => $newNightCount,
                    'price_difference_total' => $changeDifference,
                    'change_source' => 'front_desk',
                    'reason' => $plan['reason'] ?? 'Đổi phòng khi sửa ngày nhận/trả.',
                    'changed_by' => Auth::id(),
                ]);

                $roomChangeMessages[] = 'Phòng '
                    . ($oldRoom->room_number ?? '---')
                    . ' → '
                    . ($newRoom->room_number ?? '---')
                    . (
                        $oldCategoryId !== $newCategoryId
                            ? ' (' . ($oldRoom->category->name ?? 'hạng cũ')
                                . ' → '
                                . ($newRoom->category->name ?? $selectedCategory?->name ?? 'hạng mới')
                                . ')'
                            : ' cùng hạng'
                    )
                    . '.';
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $roomChangeText = count($roomChangeMessages) > 0
                ? ' ' . implode(' ', $roomChangeMessages)
                : '';

            $booking->check_in_date = $newCheckInAt->toDateString();
            $booking->check_out_date = $newCheckOutAt->toDateString();
            $booking->check_in_at = $newCheckInAt;
            $booking->check_out_at = $newCheckOutAt;
            if ($selectedCategory) {
                $booking->room_category_id = $selectedCategory->id;
            }
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = null;
            $booking->late_arrival_confirmed_at = $isRescheduledToToday ? $nowVn : null;
            $booking->late_arrival_confirmed_by = $isRescheduledToToday ? Auth::id() : null;
            $booking->late_arrival_policy = $isRescheduledToToday
                ? Booking::RESCHEDULED_AFTER_G_POLICY_PREFIX
                    . ' Lễ tân chuyển đơn từ ngày tương lai về hôm nay lúc '
                    . $nowVn->format('d/m/Y H:i')
                    . '. Lịch nhận phòng đã được tái lập cho hôm nay; không áp dụng giờ G/đến muộn cho lần check-in này.'
                : null;
            $booking->note = $oldNote
                . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . ' - Đổi ngày lưu trú từ '
                . $oldCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' sang '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . ($selectedCategory ? ' và đổi toàn bộ sang hạng ' . $selectedCategory->name . '.' : '.')
                . ' Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText;

            $booking->save();

            app(BookingRepricingService::class)->apply($booking, $repricingPreview);
            $booking->refresh();

            $removedPromotionText = collect($repricingPreview['promotion_preview']['removed'] ?? [])
                ->map(fn (array $item) => ($item['code'] ?? '---') . ': ' . ($item['reason'] ?? 'không còn đủ điều kiện'))
                ->implode('; ');
            $serviceRepriceText = collect($repricingPreview['service_preview']['lines'] ?? [])
                ->filter(fn (array $item) => !empty($item['will_reprice']) || !empty($item['will_remove']))
                ->map(function (array $item) {
                    return ($item['name'] ?? 'Dịch vụ')
                        . ': '
                        . number_format((float) ($item['old_total'] ?? 0), 0, ',', '.')
                        . 'đ → '
                        . number_format((float) ($item['new_total'] ?? 0), 0, ',', '.')
                        . 'đ';
                })
                ->implode('; ');

            $logDescription = 'Đổi ngày lưu trú từ '
                . $oldCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' sang '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . ($selectedCategory ? '; đổi toàn bộ sang hạng ' . $selectedCategory->name : '')
                . '. Số đêm: '
                . $repricingPreview['old']['night_count']
                . ' → '
                . $repricingPreview['new']['night_count']
                . '. Tiền phòng: '
                . number_format((float) $repricingPreview['old']['room_total'], 0, ',', '.')
                . 'đ → '
                . number_format((float) $repricingPreview['new']['room_total'], 0, ',', '.')
                . 'đ. Tổng đơn: '
                . number_format((float) $repricingPreview['old']['total'], 0, ',', '.')
                . 'đ → '
                . number_format((float) $repricingPreview['new']['total'], 0, ',', '.')
                . 'đ. Đã thanh toán: '
                . number_format((float) $repricingPreview['paid_total'], 0, ',', '.')
                . 'đ. Còn phải thu: '
                . number_format((float) $repricingPreview['new']['remaining'], 0, ',', '.')
                . 'đ. Khách đang trả dư: '
                . number_format((float) $repricingPreview['new']['overpayment'], 0, ',', '.')
                . 'đ. Mức cọc mới: '
                . number_format((float) $repricingPreview['new']['required_deposit'], 0, ',', '.')
                . 'đ.'
                . ($removedPromotionText !== '' ? ' Mã bị gỡ: ' . $removedPromotionText . '.' : '')
                . ($serviceRepriceText !== '' ? ' Dịch vụ tính lại: ' . $serviceRepriceText . '.' : '')
                . $roomChangeText;

            $this->addBookingLog($booking, 'change_stay_dates', $logDescription);

            DB::commit();

            return back()->with(
                'success',
                'Đã đổi ngày lưu trú thành công'
                . ($selectedCategory ? ' và chuyển booking sang hạng ' . $selectedCategory->name : '')
                . '. Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            if (str_contains($e->getMessage(), 'phương án vừa được booking khác sử dụng')) {
                return $this->redirectWithStayDateCategoryOptions(
                    $request,
                    $booking,
                    $newCheckInAt,
                    $newCheckOutAt,
                    $e->getMessage()
                );
            }

            return back()->withInput()->with('error', 'Có lỗi khi đổi ngày lưu trú: ' . $e->getMessage());
        }
    }

    public function discardStayDatePreview(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        session()->forget('stay_date_reprice_preview');
        session()->forget('_old_input');

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Đã bỏ bản xem trước.');
    }

    private function findAvailableRoomsForChangedStayDates(
        int $roomCategoryId,
        int $quantity,
        \Carbon\Carbon $newCheckInAt,
        \Carbon\Carbon $newCheckOutAt,
        Booking $booking,
        array $excludeRoomIds = []
    ) {
        $query = Room::where('room_category_id', $roomCategoryId)
            ->bookableForPeriod(
                $newCheckInAt,
                $newCheckOutAt,
                $booking->id,
                (int) ($booking->cleaning_buffer_minutes ?? 0)
            );

        if (count($excludeRoomIds) > 0) {
            $query->whereNotIn('id', array_values(array_unique(array_map('intval', $excludeRoomIds))));
        }

        return $query
            ->with('category')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->take($quantity)
            ->get();
    }

    private function redirectWithStayDateCategoryOptions(
        Request $request,
        Booking $booking,
        \Carbon\Carbon $newCheckInAt,
        \Carbon\Carbon $newCheckOutAt,
        string $reason
    ) {
        $booking->loadMissing(['bookingRooms.room.category', 'roomCategory']);

        $roomQuantity = max(1, $booking->bookingRooms->count());
        $newNightCount = max(
            1,
            $newCheckInAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
        );
        $oldCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $oldNightCount = max(
            1,
            $oldCheckInAt->copy()->startOfDay()->diffInDays($oldCheckOutAt->copy()->startOfDay())
        );
        $oldOneNightRoomTotal = (float) $booking->bookingRooms->sum(
            fn ($bookingRoom) => (float) $bookingRoom->price_at_booking
        );

        if ($oldOneNightRoomTotal <= 0) {
            $oldOneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0) * $roomQuantity;
        }

        $oldRoomTotal = $oldOneNightRoomTotal * $oldNightCount;
        $options = [];

        $categories = RoomCategory::where('status', 'active')
            ->orderBy('price')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $totalAdultCapacity = (int) $category->adult_capacity * $roomQuantity;
            $totalChildCapacity = (int) $category->child_capacity * $roomQuantity;

            if (
                $totalAdultCapacity < (int) ($booking->adult_count ?? 0)
                || $totalChildCapacity < ((int) ($booking->child_count ?? 0))
            ) {
                continue;
            }

            $rooms = $this->findAvailableRoomsForChangedStayDates(
                (int) $category->id,
                $roomQuantity,
                $newCheckInAt,
                $newCheckOutAt,
                $booking,
                []
            );

            if ($rooms->count() < $roomQuantity) {
                continue;
            }

            $newRoomTotal = (float) $category->price * $roomQuantity * $newNightCount;
            $difference = round($newRoomTotal - $oldRoomTotal, 0);

            $options[] = [
                'category_id' => (int) $category->id,
                'category_name' => $category->name,
                'price_per_room_per_night' => (float) $category->price,
                'price_text' => number_format((float) $category->price, 0, ',', '.') . 'đ/phòng/đêm',
                'room_quantity' => $roomQuantity,
                'room_numbers' => $rooms->pluck('room_number')->implode(', '),
                'adult_capacity' => $totalAdultCapacity,
                'child_capacity' => $totalChildCapacity,
                'new_room_total' => $newRoomTotal,
                'new_room_total_text' => number_format($newRoomTotal, 0, ',', '.') . 'đ',
                'difference' => $difference,
                'difference_text' => ($difference > 0 ? '+' : '') . number_format($difference, 0, ',', '.') . 'đ',
                'is_current_category' => (int) $category->id === (int) $booking->room_category_id,
            ];
        }

        if (count($options) === 0) {
            return back()->withInput($request->all())->with(
                'error',
                $reason
                . ' Trong khung '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . ', hiện không có hạng phòng nào còn đủ '
                . $roomQuantity
                . ' phòng. Hãy đổi ngày, giảm số phòng hoặc tạo booking khác.'
            );
        }

        return back()
            ->withInput($request->all())
            ->with('stay_date_category_options', [
                'booking_id' => (int) $booking->id,
                'reason' => $reason,
                'new_check_in_date' => $newCheckInAt->toDateString(),
                'new_check_in_time' => $newCheckInAt->format('H:i'),
                'new_check_out_date' => $newCheckOutAt->toDateString(),
                'new_check_out_time' => $newCheckOutAt->format('H:i'),
                'period_text' => $newCheckInAt->format('d/m/Y H:i')
                    . ' → '
                    . $newCheckOutAt->format('d/m/Y H:i'),
                'room_quantity' => $roomQuantity,
                'options' => $options,
            ])
            ->with(
                'warning',
                $reason
                . ' Hãy chọn một hạng còn đủ phòng ngay bên dưới để đổi hạng theo đúng lịch mới.'
            );
    }


    public function requestInspection(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with('bookingRooms.room')
                ->firstOrFail();

            if ($booking->status !== 'checked_in') {
                throw new \Exception('Chỉ có thể yêu cầu kiểm tra khi khách đang ở và chưa gửi yêu cầu kiểm tra trước đó.');
            }

            if ($booking->bookingRooms->isEmpty()) {
                throw new \Exception('Booking chưa được gán phòng nên không thể tạo phiếu kiểm tra.');
            }

            foreach ($booking->bookingRooms as $bookingRoom) {
                if (!$bookingRoom->room) {
                    continue;
                }

                $bookingRoom->room->update([
                    'status' => 'inspection',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);

                \App\Models\RoomActionLog::create([
                    'room_id'     => $bookingRoom->room->id,
                    'user_id'     => Auth::id(),
                    'action_type' => 'status_change',
                    'action_time' => now(),
                    'note'        => 'Yêu cầu kiểm tra phòng trước check-out từ booking #' . $booking->booking_code,
                ]);

                RoomInspection::firstOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'room_id'    => $bookingRoom->room_id,
                        'status'     => 'pending',
                    ],
                    [
                        'has_damage' => false,
                        'damage_total' => 0,
                        'workflow_stage' => \App\Models\RoomInspection::STAGE_HOUSEKEEPING_REPORT,
                        'version' => 0,
                    ]
                );
            }


            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'status' => 'inspection_requested',
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Đã yêu cầu kiểm tra phòng trước khi check-out.',
            ]);

            $roomNumbers = $booking->bookingRooms
                ->pluck('room.room_number')
                ->filter()
                ->implode(', ');

            $this->addBookingLog(
                $booking,
                'request_inspection',
                'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: ' . $roomNumbers . '.'
            );

            DB::commit();

            return back()->with('success', 'Đã tạo phiếu kiểm tra và chuyển phòng sang trạng thái chờ kiểm tra.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi yêu cầu kiểm tra phòng: ' . $e->getMessage());
        }
    }

    public function requestPriorityCleaning(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ gửi yêu cầu dọn ưu tiên cho booking đã xác nhận nhưng khách chưa check-in.');
        }

        if (!$booking->check_in_at) {
            return back()->with('error', 'Booking này chưa có giờ nhận phòng dự kiến.');
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $priorityStartTime = (string) $booking->policyValue('stay.priority_cleaning_start_time', '12:00') . ':00';
        $standardCheckInTime = $booking->standardCheckInTime();
        $priorityStartAt = $checkInAt->copy()->setTimeFromTimeString($priorityStartTime);

        if (!$nowVn->isSameDay($checkInAt)) {
            return back()->with('error', 'Chỉ gửi yêu cầu dọn ưu tiên trong đúng ngày nhận phòng.');
        }

        if ($nowVn->lessThan($priorityStartAt)) {
            return back()->with('error', 'Chỉ gửi yêu cầu dọn ưu tiên từ ' . $priorityStartAt->format('H:i') . ' ngày nhận phòng.');
        }

        $booking->load('bookingRooms.room');

        $roomsNeedPriority = $booking->bookingRooms->filter(function ($bookingRoom) {
            $status = $bookingRoom->room->status ?? null;

            return in_array($status, ['inspection', 'cleaning']);
        });

        if ($roomsNeedPriority->count() == 0) {
            return back()->with(
                'error',
                'Không có phòng nào đang chờ kiểm tra hoặc cần dọn. Nếu phòng đã sẵn sàng, lễ tân có thể check-in cho khách.'
            );
        }

        DB::beginTransaction();

        try {
            $roomMessages = [];

            foreach ($roomsNeedPriority as $bookingRoom) {
                $room = $bookingRoom->room;

                if (!$room) {
                    continue;
                }

                $oldRoomNote = $room->note ? $room->note . "\n" : '';

                $room->update([
                    'note' => $oldRoomNote
                        . $nowVn->format('d/m/Y H:i')
                        . ' - ƯU TIÊN DỌN NHANH cho booking '
                        . $booking->booking_code
                        . '. Khách đã đến trong khung chuẩn bị phòng sớm ' . $priorityStartAt->format('H:i') . '–' . substr($standardCheckInTime, 0, 5) . ', cần chuẩn bị phòng sớm nếu có thể.',
                ]);

                $roomMessages[] = 'Phòng '
                    . $room->room_number
                    . ' đang '
                    . mb_strtolower($this->getRoomStatusLabel($room->status));
            }

            $oldBookingNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'note' => $oldBookingNote
                    . $nowVn->format('d/m/Y H:i')
                    . ' - Lễ tân gửi yêu cầu buồng phòng ưu tiên dọn nhanh. '
                    . implode(', ', $roomMessages)
                    . '.',
            ]);

            $this->addBookingLog(
                $booking,
                'priority_cleaning',
                'Gửi yêu cầu buồng phòng ưu tiên dọn nhanh từ ' . $priorityStartAt->format('H:i') . '–' . substr($standardCheckInTime, 0, 5) . '. '
                . implode(', ', $roomMessages)
                . '.'
            );

            DB::commit();

            return back()->with('success', 'Đã gửi yêu cầu dọn ưu tiên cho buồng phòng. Khi phòng sẵn sàng, lễ tân có thể check-in.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gửi yêu cầu dọn ưu tiên: ' . $e->getMessage());
        }
    }

    public function addCheckoutFee(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'inspection_requested') {
            return back()->with('error', 'Chỉ có thể thêm phí chốt tại bước chờ check-out.');
        }

        $data = $request->validate([
            'checkout_extra_name' => 'required|string|max:150',
            'checkout_extra_amount' => 'required|numeric|min:1000',
            'checkout_extra_note' => 'nullable|string|max:1000',
        ], [
            'checkout_extra_name.required' => 'Vui lòng nhập tên khoản phí phát sinh.',
            'checkout_extra_amount.required' => 'Vui lòng nhập số tiền phí phát sinh.',
            'checkout_extra_amount.numeric' => 'Số tiền phí phát sinh không hợp lệ.',
            'checkout_extra_amount.min' => 'Số tiền phí phát sinh tối thiểu là 1.000đ.',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with(['roomInspections', 'serviceItems'])
                ->firstOrFail();

            if ($booking->status !== 'inspection_requested') {
                throw new \Exception('Booking đã được xử lý hoặc không còn ở bước chờ check-out.');
            }

            if ($booking->roomInspections->contains(fn ($inspection) => $inspection->status !== 'confirmed')) {
                throw new \Exception('Chưa thể thêm phí chốt vì vẫn còn phiếu kiểm tra phòng chưa hoàn tất đối chiếu với khách.');
            }

            $feeName = trim((string) $data['checkout_extra_name']);
            $feeAmount = (float) $data['checkout_extra_amount'];
            $feeNote = trim((string) ($data['checkout_extra_note'] ?? ''));

            $manualFeeService = Service::firstOrCreate(
                [
                    'name' => $feeName,
                    'type' => 'manual_fee',
                ],
                [
                    'service_group' => 'other',
                    'price' => 0,
                    'unit' => 'lần',
                    'description' => 'Khoản phí phát sinh được lễ tân ghi nhận trước khi check-out.',
                    'status' => 'active',
                ]
            );

            BookingServiceItem::create([
                'booking_id' => $booking->id,
                'service_id' => $manualFeeService->id,
                'name' => $feeName,
                'type' => 'manual_fee',
                'unit_price' => $feeAmount,
                'quantity' => 1,
                'used_quantity' => 1,
                'billing_status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now('Asia/Ho_Chi_Minh'),
                'confirm_note' => $feeNote !== '' ? $feeNote : null,
                'total' => $feeAmount,
                'note' => $feeNote !== '' ? $feeNote : 'Phí phát sinh được thêm trước khi check-out.',
            ]);

            // Đồng bộ ngay các cột tổng tiền lưu trên booking. Nếu chỉ thêm service item
            // mà không reprice thì currentTotal() đúng nhưng estimated/subtotal trong DB
            // vẫn là số cũ, khiến màn hình/đối soát dùng hai nguồn tiền khác nhau.
            $this->repriceCurrentBooking($booking);
            $booking->refresh();

            $this->addBookingLog(
                $booking,
                'checkout_fee_added',
                'Thêm phí phát sinh trước check-out: ' . $feeName . ' - '
                . number_format($feeAmount, 0, ',', '.') . 'đ'
                . ($feeNote !== '' ? '. Ghi chú: ' . $feeNote : '')
            );

            DB::commit();

            Realtime::booking($booking->id, 'checkout_fee_added');

            return back()->with(
                'success',
                'Đã thêm phí “' . $feeName . '” ' . number_format($feeAmount, 0, ',', '.')
                . 'đ. Tổng cần thanh toán và số còn lại đã được cập nhật.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể thêm phí phát sinh: ' . $e->getMessage());
        }
    }

    public function checkOut(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'inspection_requested') {
            return back()->with('error', 'Chỉ có thể check-out sau khi đã yêu cầu và hoàn tất kiểm tra phòng.');
        }

        $data = $request->validate([
            'checkout_late_fee_confirm' => 'nullable|in:1',
        ]);

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
            'roomInspections.items',
            'roomInspections.room',
            'serviceItems',
        ]);

        $assignedRoomCount = $booking->bookingRooms->pluck('room_id')->filter()->unique()->count();
        $inspectionRoomCount = $booking->roomInspections->pluck('room_id')->filter()->unique()->count();

        if ($assignedRoomCount === 0 || $inspectionRoomCount < $assignedRoomCount) {
            return back()->with(
                'error',
                'Không thể check-out vì chưa có đủ phiếu kiểm tra cho tất cả phòng đã gán.'
            );
        }

        $notCompletedInspectionCount = $booking->roomInspections
            ->filter(function ($inspection) {
                return ($inspection->status ?? null) !== 'confirmed'
                    && ($inspection->workflow_stage ?? null) !== RoomInspection::STAGE_COMPLETED;
            })
            ->count();

        if ($notCompletedInspectionCount > 0) {
            return back()->with(
                'error',
                'Không thể check-out vì vẫn còn '
                . $notCompletedInspectionCount
                . ' phiếu kiểm tra chưa hoàn tất đối chiếu với khách.'
            );
        }

        $actualCheckOutAt = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $lateCheckout = $this->calculateCheckoutLateFee($booking, $actualCheckOutAt);

        if ($lateCheckout['amount'] > 0 && ($data['checkout_late_fee_confirm'] ?? null) !== '1') {
            return back()->with(
                'error',
                'Booking trả phòng muộn, phát sinh phụ thu '
                . number_format($lateCheckout['amount'], 0, ',', '.')
                . 'đ. Vui lòng tick xác nhận khách đã chấp nhận phụ thu trước khi check-out.'
            );
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with(['bookingRooms.room.category', 'roomCategory', 'roomInspections.items', 'roomInspections.room', 'serviceItems'])
                ->firstOrFail();

            if ($booking->status !== 'inspection_requested') {
                throw new \Exception('Booking đã được xử lý bởi yêu cầu khác hoặc chưa ở trạng thái chờ check-out.');
            }

            $assignedRoomIds = $booking->bookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $inspectionRoomIds = $booking->roomInspections->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
            if ($assignedRoomIds->isEmpty() || $assignedRoomIds->diff($inspectionRoomIds)->isNotEmpty()) {
                throw new \Exception('Không thể check-out vì chưa có đủ phiếu kiểm tra cho tất cả phòng đã gán.');
            }

            $stillIncomplete = $booking->roomInspections->filter(function ($inspection) {
                return ($inspection->status ?? null) !== 'confirmed'
                    && ($inspection->workflow_stage ?? null) !== RoomInspection::STAGE_COMPLETED;
            });
            if ($stillIncomplete->isNotEmpty()) {
                throw new \Exception('Vẫn còn ' . $stillIncomplete->count() . ' phiếu kiểm tra chưa hoàn tất đối chiếu với khách.');
            }

            // Self-heal dữ liệu cũ: có phiên bản đã vào workflow completed nhưng status
            // hoặc trạng thái vật lý phòng vẫn kẹt ở bước inspection. Khi hai bên đã
            // thống nhất thì cho phép checkout và đồng bộ lại trước khi chốt booking.
            $inspectionCompletedAt = now('Asia/Ho_Chi_Minh');
            foreach ($booking->roomInspections as $inspection) {
                if (($inspection->workflow_stage ?? null) === RoomInspection::STAGE_COMPLETED
                    && $inspection->status !== 'confirmed') {
                    $inspection->update([
                        'status' => 'confirmed',
                        'confirmed_by' => $inspection->confirmed_by ?: Auth::id(),
                        'confirmed_at' => $inspection->confirmed_at ?: $inspectionCompletedAt,
                    ]);
                }

                if (($inspection->status === 'confirmed' || ($inspection->workflow_stage ?? null) === RoomInspection::STAGE_COMPLETED)
                    && $inspection->room
                    && $inspection->room->status === 'inspection'
                    && $assignedRoomIds->contains((int) $inspection->room_id)) {
                    $inspection->room->update([
                        'status' => 'occupied',
                        'status_from' => $inspectionCompletedAt,
                        'status_until' => null,
                    ]);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $inspection->room_id,
                        'user_id' => Auth::id(),
                        'action_type' => 'inspection_completed',
                        'action_time' => $inspectionCompletedAt,
                        'note' => 'Đồng bộ phiếu kiểm tra đã hoàn tất của booking #' . $booking->booking_code
                            . '. Phòng trở lại trạng thái đang ở trước khi check-out.',
                    ]);
                }
            }

            // Khởi tạo đầy đủ các thành phần tiền dùng khi tổng hợp và ghi log check-out.
            $roomBaseTotal = $this->getCheckoutRoomBaseTotal($booking);
            $serviceItemTotal = (float) $booking->serviceItems
                ->where('billing_status', 'confirmed')
                ->sum(function ($item) {
                    return (float) $item->total;
                });
            $approvedInspectionTotal = $this->getApprovedInspectionTotal($booking);

            $feeMessages = [];

            if ($lateCheckout['amount'] > 0) {
                $lateCheckoutService = Service::firstOrCreate(
                    [
                        'name' => 'Phụ thu check-out muộn',
                        'type' => 'late_checkout_fee',
                    ],
                    [
                        'service_group' => 'other',
                        'price' => 0,
                        'unit' => 'lần',
                        'description' => 'Phụ thu khi khách trả phòng muộn so với giờ check-out trên booking.',
                        'status' => 'active',
                    ]
                );

                BookingServiceItem::updateOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'name' => 'Phụ thu check-out muộn',
                        'type' => 'late_checkout_fee',
                    ],
                    [
                        'service_id' => $lateCheckoutService->id,
                        'unit_price' => $lateCheckout['required_total'] ?? $lateCheckout['amount'],
                        'quantity' => 1,
                        'used_quantity' => 1,
                        'billing_status' => 'confirmed',
                        'confirmed_by' => Auth::id(),
                        'confirmed_at' => $actualCheckOutAt,
                        'confirm_note' => $lateCheckout['policy_text'],
                        'total' => $lateCheckout['required_total'] ?? $lateCheckout['amount'],
                        'note' => $lateCheckout['note'],
                    ]
                );

                $feeMessages[] = 'Phụ thu check-out muộn: '
                    . number_format($lateCheckout['required_total'] ?? $lateCheckout['amount'], 0, ',', '.')
                    . 'đ'
                    . (($lateCheckout['existing_total'] ?? 0) > 0
                        ? ' (cập nhật thêm ' . number_format($lateCheckout['amount'], 0, ',', '.') . 'đ theo giờ trả thực tế)'
                        : '')
                    . '. '
                    . $lateCheckout['policy_text'];
            }

            // Chốt lại một nguồn tiền duy nhất trước khi kiểm tra đã thu đủ hay chưa.
            // Việc này cũng đồng bộ các phí vừa phát sinh (manual/late checkout) vào
            // subtotal, estimated_total, trạng thái thanh toán và overpayment.
            $this->repriceCurrentBooking($booking);
            $booking->refresh();
            $booking->load(['bookingRooms.room.category', 'roomCategory', 'roomInspections.items', 'serviceItems']);
            $financialService = app(BookingFinancialService::class);
            $finalTotal = $financialService->currentTotal($booking);

            $paidBeforeCheckout = (float) $booking->payments()->where('status', 'success')->sum('amount');
            $remainingTotal = max(0, $finalTotal - $paidBeforeCheckout);

            // Nếu vừa phát sinh phụ thu check-out muộn thì phải lưu khoản phí trước khi
            // yêu cầu lễ tân thu nốt tiền. Không throw để rollback khoản phí vừa tạo,
            // nếu không lần bấm check-out sau sẽ lại rơi vào đúng vòng lặp cũ.
            if ($remainingTotal > 0.01) {
                $booking->forceFill([
                    'estimated_total' => $finalTotal,
                ])->save();
                $financialService->refreshPaymentStatus($booking);

                if ($lateCheckout['amount'] > 0) {
                    $this->addBookingLog(
                        $booking,
                        'late_checkout_fee_recorded',
                        'Đã ghi nhận phụ thu check-out muộn '
                        . number_format($lateCheckout['amount'], 0, ',', '.')
                        . 'đ trước khi hoàn tất thanh toán.'
                    );
                }

                DB::commit();
                Realtime::booking($booking->id, 'checkout_payment_required');

                $prefix = $lateCheckout['amount'] > 0
                    ? 'Đã ghi nhận phụ thu check-out muộn vào booking. '
                    : '';

                return back()->with(
                    'error',
                    $prefix
                    . 'Booking còn '
                    . number_format($remainingTotal, 0, ',', '.')
                    . 'đ chưa thanh toán trên hệ thống. Vui lòng ghi nhận khoản thu ở mục Thanh toán, sau đó bấm Check-out lại.'
                );
            }

            // Phụ thu vừa được thêm ở trên nên lấy lại tổng dịch vụ để log không bị thiếu.
            $booking->load('serviceItems');
            $serviceItemTotal = (float) $booking->serviceItems
                ->where('billing_status', 'confirmed')
                ->sum(fn ($item) => (float) $item->total);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $feeText = count($feeMessages) > 0
                ? ' Phí phát sinh: ' . implode(' ', $feeMessages)
                : ' Không phát sinh phụ thu check-out.';

            $paymentText = ' Khách đã thanh toán đủ trên hệ thống trước khi check-out.';

            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => $actualCheckOutAt,
                'payment_status' => 'paid',
                'final_total' => $finalTotal,
                'estimated_total' => $finalTotal,
                'note' => $oldNote
                    . $actualCheckOutAt->format('d/m/Y H:i')
                    . ' - Check-out thực tế. Tổng phải thu: '
                    . number_format($finalTotal, 0, ',', '.')
                    . 'đ. Đã thu trước check-out: '
                    . number_format($paidBeforeCheckout, 0, ',', '.')
                    . 'đ. Còn lại khi check-out: '
                    . number_format($remainingTotal, 0, ',', '.')
                    . 'đ.'
                    . $paymentText
                    . $feeText,
            ]);

            // Booking đã kết thúc thì toàn bộ khách đang lưu trú cũng phải được
            // đóng trạng thái và lịch sử phòng cùng thời điểm. Nếu bỏ bước này,
            // danh sách khách lưu trú vẫn hiển thị khách đã check-out.
            $guestIds = $booking->guests()->pluck('id');

            if ($guestIds->isNotEmpty()) {
                $booking->guests()
                    ->where('status', 'checked_in')
                    ->update([
                        'status' => 'checked_out',
                        'actual_check_out_at' => $actualCheckOutAt,
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);

                \App\Models\BookingGuestRoomHistory::whereIn('booking_guest_id', $guestIds)
                    ->whereNull('ended_at')
                    ->update([
                        'ended_at' => $actualCheckOutAt,
                        'updated_at' => now(),
                    ]);
            }

            $priorityCleaningWindowMinutes = max(1, (int) $booking->policyValue('stay.priority_cleaning_window_minutes', 120));

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $room = $bookingRoom->room;
                    $nextBookingRoom = \App\Models\BookingRoom::query()
                        ->with('booking')
                        ->where('room_id', $room->id)
                        ->where('booking_id', '!=', $booking->id)
                        ->whereHas('booking', function ($query) use ($actualCheckOutAt, $priorityCleaningWindowMinutes) {
                            $query->where(function ($active) {
                                $active->where('status', 'confirmed')
                                    ->orWhere(function ($pending) {
                                        $pending->where('status', 'pending')
                                            ->where(function ($validHold) {
                                                $validHold->where('payment_expires_at', '>', now('Asia/Ho_Chi_Minh'))
                                                    ->orWhereHas('payments', fn ($payment) => $payment->where('status', 'success'));
                                            });
                                    });
                            })
                                ->where('check_in_at', '>=', $actualCheckOutAt)
                                ->where('check_in_at', '<=', $actualCheckOutAt->copy()->addMinutes($priorityCleaningWindowMinutes));
                        })
                        ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                        ->orderBy('bookings.check_in_at')
                        ->select('booking_rooms.*')
                        ->first();

                    $urgentText = '';
                    if ($nextBookingRoom?->booking) {
                        $nextCheckIn = \Carbon\Carbon::parse($nextBookingRoom->booking->check_in_at, 'Asia/Ho_Chi_Minh');
                        $urgentText = ' DỌN GẤP: phòng có khách tiếp theo dự kiến nhận lúc '
                            . $nextCheckIn->format('H:i d/m/Y')
                            . ' (đơn ' . $nextBookingRoom->booking->booking_code . '). Lễ tân cần báo khách có thể phải chờ đến khi buồng phòng xác nhận đã dọn xong.';
                    }

                    $room->update([
                        'status' => 'cleaning',
                        'status_from' => $actualCheckOutAt,
                        'status_until' => null,
                        'note' => trim(($room->note ? $room->note . "\n" : '')
                            . $actualCheckOutAt->format('d/m/Y H:i')
                            . ' - Phòng vừa trả, cần dọn.' . $urgentText),
                    ]);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $room->id,
                        'user_id' => Auth::id(),
                        'action_type' => $urgentText ? 'priority_cleaning' : 'check_out',
                        'action_time' => now(),
                        'note' => 'Khách trả phòng từ booking #' . $booking->booking_code
                            . '. Chuyển sang trạng thái dọn dẹp.' . $urgentText,
                    ]);
                }
            }

            $roomNumbers = $booking->bookingRooms
                ->pluck('room.room_number')
                ->filter()
                ->implode(', ');

            $this->addBookingLog(
                $booking,
                'check_out',
                'Xác nhận check-out lúc '
                . $actualCheckOutAt->format('d/m/Y H:i')
                . '. Phòng chuyển sang cần dọn: '
                . $roomNumbers
                . '. Tiền phòng: '
                . number_format($roomBaseTotal, 0, ',', '.')
                . 'đ. Dịch vụ/phụ thu: '
                . number_format($serviceItemTotal, 0, ',', '.')
                . 'đ. Minibar/hư hại duyệt: '
                . number_format($approvedInspectionTotal, 0, ',', '.')
                . 'đ. Tổng phải thu: '
                . number_format($finalTotal, 0, ',', '.')
                . 'đ. Đã thu trước check-out: '
                . number_format($paidBeforeCheckout, 0, ',', '.')
                . 'đ. Còn lại khi check-out: '
                . number_format($remainingTotal, 0, ',', '.')
                . 'đ.'
                . $paymentText
                . $feeText
            );

            DB::commit();

            Realtime::booking($booking->id, 'checked_out');

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with(
                    'success',
                    'Check-out thành công. Tổng phải thu '
                    . number_format($finalTotal, 0, ',', '.')
                    . 'đ. '
                    . 'Booking đã thanh toán đủ trên hệ thống. '
                    . 'Phòng đã chuyển sang trạng thái cần dọn.'
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi khi check-out: ' . $e->getMessage());
        }
    }

    private function getCheckoutPaymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'cash' => 'tiền mặt tại quầy',
            'bank_transfer' => 'chuyển khoản tại quầy',
            default => 'không xác định',
        };
    }

    private function generateCheckoutPaymentTxnRef(Booking $booking, string $method): string
    {
        $prefix = $method === 'cash' ? 'CASHOUT' : 'BANKOUT';

        do {
            $txnRef = $prefix
                . $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(\Illuminate\Support\Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }


    private function calculateCheckoutLateFee(Booking $booking, ?\Carbon\Carbon $actualCheckOutAt = null): array
    {
        if (!$booking->check_out_at) {
            return [
                'amount' => 0,
                'required_total' => 0,
                'existing_total' => 0,
                'late_minutes' => 0,
                'late_hours' => 0,
                'policy_text' => 'Booking chưa có giờ check-out dự kiến.',
                'note' => '',
            ];
        }

        $actualCheckOutAt = $actualCheckOutAt ?: \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $plannedCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        if ($actualCheckOutAt->lessThanOrEqualTo($plannedCheckOutAt)) {
            return [
                'amount' => 0,
                'required_total' => 0,
                'existing_total' => 0,
                'late_minutes' => 0,
                'late_hours' => 0,
                'policy_text' => 'Khách trả phòng đúng hạn, không phụ thu check-out muộn.',
                'note' => '',
            ];
        }

        $lateMinutes = $plannedCheckOutAt->diffInMinutes($actualCheckOutAt);
        $lateHours = round($lateMinutes / 60, 2);

        $existingLateCheckoutFee = BookingServiceItem::where('booking_id', $booking->id)
            ->where('type', 'late_checkout_fee')
            ->whereNotIn('billing_status', ['unused', 'cancelled'])
            ->first();
        $existingTotal = max(0, (float) ($existingLateCheckoutFee?->total ?? 0));

        $oneNightTotal = (float) $booking->bookingRooms->sum('price_at_booking');
        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $pricing = app(StayPricingPolicyService::class);

        if ($booking->booking_type === 'hourly') {
            $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
            $totalMinutes = $checkInAt->diffInMinutes($actualCheckOutAt);
            $nightPrice = (float) ($booking->roomCategory->price ?? 0);
            $quantity = max(1, (int) $booking->room_quantity);

            $overnightThresholdHours = max(1, (int) $booking->policyValue('stay.short_stay_to_overnight_hours', 12));
            $overnightThresholdMinutes = $overnightThresholdHours * 60;
            $isLongStay = $totalMinutes > $overnightThresholdMinutes;
            $newTotal = $isLongStay
                ? $pricing->longStay($checkInAt, $actualCheckOutAt, $nightPrice, $quantity, $booking)['total_amount']
                : $pricing->shortStay($nightPrice, $quantity, $totalMinutes, $booking)['amount'];

            $currentRoomTotal = max(0, $this->getCheckoutRoomBaseTotal($booking));
            // getCheckoutRoomBaseTotal() đã loại service item khỏi tiền phòng, nên
            // chênh lệch này chính là toàn bộ phụ thu cần có tại giờ trả thực tế.
            $requiredTotal = max(0, round($newTotal - $currentRoomTotal, 0));
            $policyText = $isLongStay
                ? 'Tổng thời gian sau khi trả muộn vượt ' . $overnightThresholdHours . ' giờ, hệ thống tự chuyển sang chính sách qua đêm và chỉ thu phần chênh lệch.'
                : 'Booking theo giờ được tính lại theo tổng thời gian thực tế và chỉ thu phần chênh lệch.';
        } else {
            $extraDays = max(0, $plannedCheckOutAt->copy()->startOfDay()->diffInDays($actualCheckOutAt->copy()->startOfDay()));
            $dayPolicy = $pricing->lateCheckOut($actualCheckOutAt, $oneNightTotal, $booking);
            $requiredTotal = round(($extraDays * $oneNightTotal) + $dayPolicy['amount'], 0);
            $policyText = ($extraDays > 0 ? 'Trả sang thêm ' . $extraDays . ' ngày, tính thêm ' . $extraDays . ' đêm. ' : '')
                . $dayPolicy['policy_text'];
        }

        $additionalAmount = max(0, round($requiredTotal - $existingTotal, 0));

        return [
            // amount là phần cần tăng thêm ở lần bấm checkout hiện tại.
            'amount' => $additionalAmount,
            'required_total' => $requiredTotal,
            'existing_total' => $existingTotal,
            'late_minutes' => $lateMinutes,
            'late_hours' => $lateHours,
            'policy_text' => $policyText,
            'note' => 'Giờ check-out dự kiến: ' . $plannedCheckOutAt->format('d/m/Y H:i')
                . '. Giờ check-out thực tế: ' . $actualCheckOutAt->format('d/m/Y H:i')
                . '. ' . $policyText,
        ];
    }
    private function getCheckoutRoomBaseTotal(Booking $booking): float
    {
        $booking->loadMissing([
            'bookingRooms.room.category',
            'roomCategory',
            'serviceItems',
        ]);

        $serviceItemTotal = $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->sum(function ($item) {
                return (float) $item->total;
            });

        if ($booking->booking_type === 'hourly') {
            $roomTotal = max(0, (float) $booking->estimated_total - $serviceItemTotal);

            if ($roomTotal > 0) {
                return round($roomTotal, 0);
            }

            return round((float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity), 0);
        }

        $nightCount = $this->getNightCount($booking);
        $roomTotal = $booking->bookingRooms->sum(function ($bookingRoom) use ($nightCount) {
            return (float) $bookingRoom->price_at_booking * $nightCount;
        });

        if ($roomTotal > 0) {
            return round($roomTotal, 0);
        }

        return round(max(0, (float) $booking->estimated_total - $serviceItemTotal), 0);
    }

    private function getApprovedInspectionTotal(Booking $booking): float
    {
        $booking->loadMissing('roomInspections.items');

        return (float) $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum(function ($item) {
                return (float) $item->total;
            });
    }


    private function guardCheckInArrivalTime(Booking $booking, \Carbon\Carbon $actualCheckInAt): void
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return;
        }

        $plannedCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $plannedCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        if ($actualCheckInAt->greaterThanOrEqualTo($plannedCheckOutAt)) {
            throw new \Exception(
                'Booking đã quá thời gian lưu trú '
                . $plannedCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $plannedCheckOutAt->format('d/m/Y H:i')
                . '. Không được check-in booking cũ. Vui lòng hủy/no-show và tạo booking mới nếu khách vẫn muốn ở.'
            );
        }

        if (
            $booking->booking_type !== 'hourly'
            && $actualCheckInAt->toDateString() < $plannedCheckInAt->toDateString()
        ) {
            throw new \Exception(
                'Khách đến trước ngày booking. Booking bắt đầu lúc '
                . $plannedCheckInAt->format('d/m/Y H:i')
                . ', hiện tại là '
                . $actualCheckInAt->format('d/m/Y H:i')
                . '. Đây không phải check-in sớm trong cùng ngày. Vui lòng dùng chức năng Đổi ngày lưu trú hoặc tạo booking mới trước khi check-in.'
            );
        }

        if ($booking->booking_type === 'hourly' && $actualCheckInAt->lessThan($plannedCheckInAt)) {
            throw new \Exception(
                'Booking theo giờ bắt đầu lúc ' . $plannedCheckInAt->format('d/m/Y H:i')
                . ', hiện tại là ' . $actualCheckInAt->format('d/m/Y H:i')
                . '. Không được check-in sớm vì sẽ làm thời gian ở thực tế dài hơn gói đã tính tiền. Hãy đổi thời gian lưu trú và tính lại giá/tồn phòng nếu khách muốn vào sớm.'
            );
        }

        // Booking đã được lễ tân chủ động chuyển từ ngày tương lai về hôm nay
        // thì lịch nhận phòng của hôm nay là lịch mới vừa được xác lập. Không áp dụng
        // giờ G/no-show hay một khoảng ân hạn giả tạo cho lần check-in này.
        if ($booking->isRescheduledAfterCutoff()) {
            return;
        }

        // Walk-in/ở ngay và booking theo giờ không thuộc chính sách giờ G/no-show.
        if (!$booking->usesLateArrivalNoShowPolicy()) {
            return;
        }

        if ($actualCheckInAt->greaterThan($plannedCheckInAt)) {
            $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);

            if ($holdLimitAt && $actualCheckInAt->greaterThanOrEqualTo($holdLimitAt)) {
                throw new \Exception(
                    'Khách đã quá hạn giữ phòng '
                    . $holdLimitAt->format('d/m/Y H:i')
                    . '. Vui lòng xử lý đến muộn/no-show trước khi nhận phòng.'
                );
            }
        }
    }

    private function getBookingNightCountFromTimes(Booking $booking): int
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return $this->getNightCount($booking);
        }

        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->startOfDay();
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();

        return max(1, $checkInAt->diffInDays($checkOutAt));
    }

    private function isBookingFullyPaid(Booking $booking): bool
    {
        $estimatedTotal = (float) $booking->estimated_total;
        $depositAmount = (float) $booking->deposit_amount;

        return $booking->payment_status === 'paid'
            || ($estimatedTotal > 0 && $depositAmount >= $estimatedTotal);
    }

    private function getLateArrivalHoldLimitAt(Booking $booking): ?\Carbon\Carbon
    {
        return $booking->lateArrivalHoldLimitAt();
    }


    private function handleLateArrivalFee(Booking $booking, array $data): string
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return '';
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        // Chỉ booking đặt trước thật sự mới được tính phụ thu/giữ phòng sau giờ G.
        // Walk-in, ở ngay qua đêm, theo giờ và đơn vừa đổi ngày đều xử lý như check-in bình thường.
        if (!$booking->usesLateArrivalNoShowPolicy()) {
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = null;

            if ($booking->isRescheduledAfterCutoff()) {
                $rescheduledAt = $booking->rescheduledAfterCutoffAt();
                $policy = 'Đơn được lễ tân chuyển từ ngày tương lai về hôm nay'
                    . ($rescheduledAt ? ' lúc ' . $rescheduledAt->format('d/m/Y H:i') : '')
                    . '. Lịch nhận phòng đã được tái lập cho hôm nay; không coi là khách đến muộn, không áp dụng giờ G và không phát sinh phụ thu đến muộn.';
                $booking->late_arrival_policy = Booking::RESCHEDULED_AFTER_G_POLICY_PREFIX . ' ' . $policy;
                return $policy;
            }

            $booking->late_arrival_policy = null;
            return '';
        }

        if ($nowVn->lessThanOrEqualTo($checkInAt)) {
            return '';
        }

        if ($nowVn->greaterThanOrEqualTo($checkOutAt)) {
            throw new \Exception('Không thể check-in vì booking đã quá thời gian lưu trú. Vui lòng xử lý no-show và tạo booking mới nếu khách vẫn muốn ở.');
        }

        $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);
        if ($holdLimitAt && $nowVn->greaterThanOrEqualTo($holdLimitAt)) {
            throw new \Exception('Khách đã quá giờ G ' . $holdLimitAt->format('H:i d/m/Y') . '. Booking phải được hủy no-show và mở bán lại phòng.');
        }

        $lateHours = round($checkInAt->diffInMinutes($nowVn) / 60, 2);
        $cutoffAt = $checkInAt->copy()->setTimeFromTimeString($booking->lateArrivalCutoffTime());

        if ($nowVn->lessThanOrEqualTo($cutoffAt)) {
            $policy = 'Khách check-in trong khoảng từ ' . substr($booking->standardCheckInTime(), 0, 5) . ' đến giờ G ' . $cutoffAt->format('H:i') . '. Không phụ thu đến muộn.';
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_policy = $policy;
            return $policy;
        }

        $existingFee = BookingServiceItem::where('booking_id', $booking->id)
            ->where('type', 'late_arrival_fee')
            ->where('billing_status', 'confirmed')
            ->first();

        if (!$booking->late_arrival_confirmed_at || !$existingFee) {
            throw new \Exception('Khách đến sau giờ G nhưng chưa được lễ tân xác nhận giữ phòng và ghi nhận phụ thu.');
        }

        $policy = 'Khách check-in sau giờ G theo xác nhận đến muộn. Phụ thu đã ghi nhận: '
            . number_format((float) $existingFee->total, 0, ',', '.') . 'đ. Hạn giữ phòng: '
            . ($holdLimitAt ? $holdLimitAt->format('H:i d/m/Y') : '---') . '.';

        $booking->late_arrival_fee = (float) $existingFee->total;
        $booking->late_arrival_policy = $policy;

        return $policy;
    }


    public function confirmLateArrival(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Chỉ xác nhận khách đến muộn cho booking đã xác nhận và chưa check-in.');
        }

        $data = $request->validate([
            'expected_arrival_date' => ['required', 'date_format:Y-m-d'],
            'expected_arrival_time' => ['required', 'date_format:H:i'],
        ], [
            'expected_arrival_date.required' => 'Vui lòng chọn ngày khách dự kiến đến.',
            'expected_arrival_date.date_format' => 'Ngày khách dự kiến đến không hợp lệ.',
            'expected_arrival_time.required' => 'Vui lòng chọn giờ khách dự kiến đến.',
            'expected_arrival_time.date_format' => 'Giờ khách dự kiến đến không hợp lệ.',
        ]);

        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = $booking->check_in_at ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh') : null;
        $checkOutAt = $booking->check_out_at ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh') : null;

        if (!$checkInAt || !$checkOutAt || !$booking->usesLateArrivalNoShowPolicy()) {
            return back()->with('error', 'Chức năng giữ phòng sau giờ G chỉ áp dụng cho booking đặt trước qua đêm thuộc diện khách đến muộn.');
        }

        [$holdHour, $holdMinute] = array_map('intval', explode(':', $booking->lateArrivalCutoffTime()));
        $cutoffAt = $checkInAt->copy()->setTime($holdHour, $holdMinute, 0);
        $expectedArrivalAt = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['expected_arrival_date'] . ' ' . $data['expected_arrival_time'],
            'Asia/Ho_Chi_Minh'
        );

        if ($now->lt($checkInAt)) {
            return back()->with('error', 'Chỉ xác nhận khách đến muộn từ giờ nhận phòng dự kiến trở đi.');
        }

        if ($expectedArrivalAt->lessThanOrEqualTo($cutoffAt)) {
            return back()->with('error', 'Giờ dự kiến đến phải sau giờ G ' . $cutoffAt->format('H:i d/m/Y') . '. Từ ' . substr($booking->standardCheckInTime(), 0, 5) . ' đến giờ G không phát sinh phụ thu.');
        }

        if ($expectedArrivalAt->greaterThanOrEqualTo($checkOutAt)) {
            return back()->with('error', 'Giờ dự kiến đến phải trước giờ trả phòng của booking.');
        }

        $booking->loadMissing(['bookingRooms', 'roomCategory']);
        $oneNightTotal = (float) $booking->bookingRooms->sum('price_at_booking');
        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $latePolicy = app(StayPricingPolicyService::class)->lateArrival($expectedArrivalAt, $oneNightTotal, $cutoffAt, $booking);
        $feeAmount = (float) $latePolicy['amount'];
        $hoursAfterCutoff = (float) $latePolicy['hours_after_cutoff'];
        $extendedHoldAt = $expectedArrivalAt->copy()->addMinutes($booking->lateArrivalGraceMinutes())->min($checkOutAt);

        DB::transaction(function () use ($booking, $now, $expectedArrivalAt, $extendedHoldAt, $latePolicy, $feeAmount, $hoursAfterCutoff) {
            $service = Service::query()
                ->where('name', 'Phụ thu khách đến muộn')
                ->orWhere('type', 'policy_violation_fee')
                ->orderByRaw("CASE WHEN name = 'Phụ thu khách đến muộn' THEN 0 ELSE 1 END")
                ->first();

            if (!$service) {
                throw new \RuntimeException('Chưa cấu hình dịch vụ Phụ thu khách đến muộn trong danh mục dịch vụ.');
            }

            BookingServiceItem::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'type' => 'late_arrival_fee',
                ],
                [
                    'service_id' => $service->id,
                    'name' => 'Phụ thu khách đến muộn',
                    'unit_price' => $feeAmount,
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => $now,
                    'confirm_note' => $latePolicy['policy_text'],
                    'total' => $feeAmount,
                    'note' => 'Giờ G: ' . Carbon::parse($booking->check_in_date . ' ' . $booking->lateArrivalCutoffTime(), self::TIMEZONE)->format('d/m/Y H:i')
                        . '. Khách dự kiến đến: ' . $expectedArrivalAt->format('d/m/Y H:i')
                        . '. Giữ phòng đến: ' . $extendedHoldAt->format('d/m/Y H:i') . '.',
                ]
            );

            $booking->update([
                'late_arrival_fee' => $feeAmount,
                'late_arrival_hours' => $hoursAfterCutoff,
                'late_arrival_confirmed_at' => $now,
                'late_arrival_confirmed_by' => Auth::id(),
                'late_arrival_policy' => 'Khách xác nhận đến sau giờ G. Dự kiến đến '
                    . $expectedArrivalAt->format('d/m/Y H:i') . '; giữ phòng đến '
                    . $extendedHoldAt->format('d/m/Y H:i') . '. '
                    . $latePolicy['policy_text'] . ' Phụ thu: '
                    . number_format($feeAmount, 0, ',', '.') . 'đ.',
            ]);
        });

        // Phụ thu đến muộn là một BookingServiceItem đã xác nhận nên phải reprice
        // toàn booking ngay. Trước đây chỉ currentTotal() được tăng còn các cột
        // subtotal/estimated_total trong DB vẫn giữ số cũ.
        $this->repriceCurrentBooking($booking->fresh());
        $booking->refresh();
        $currentTotal = app(BookingFinancialService::class)->currentTotal($booking);
        $booking->update(['final_total' => $currentTotal]);

        $this->addBookingLog(
            $booking,
            'late_arrival_hold_extended',
            'Lễ tân xác nhận khách sẽ đến sau giờ G. Giờ G: ' . $cutoffAt->format('d/m/Y H:i')
            . '. Giờ khách dự kiến đến: ' . $expectedArrivalAt->format('d/m/Y H:i')
            . '. Phòng được giữ tiếp đến: ' . $extendedHoldAt->format('d/m/Y H:i')
            . '. Chính sách: ' . $latePolicy['policy_text']
            . ' Phụ thu khách đến muộn: ' . number_format($feeAmount, 0, ',', '.') . 'đ.'
            . ' Tổng booking sau phụ thu: ' . number_format($currentTotal, 0, ',', '.') . 'đ.'
        );

        return back()->with('success', 'Đã giữ phòng đến ' . $extendedHoldAt->format('H:i d/m/Y')
            . ' và ghi nhận phụ thu ' . number_format($feeAmount, 0, ',', '.') . 'đ.');
    }

    public function cancelLateArrival(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Chỉ có thể hủy no-show với booking đã xác nhận nhưng khách chưa check-in.');
        }

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking chưa có đủ thời gian nhận/trả phòng dự kiến.');
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $isRescheduled = $booking->isRescheduledAfterCutoff();

        if ($isRescheduled) {
            return back()->with('error', 'Booking vừa được chuyển từ ngày tương lai về hôm nay nên không áp dụng giờ G/no-show cho lần nhận phòng này.');
        }

        if (!$booking->usesLateArrivalNoShowPolicy()) {
            return back()->with('error', 'Booking này không thuộc diện xử lý no-show.');
        }

        if ($nowVn->lt($checkInAt)) {
            return back()->with('error', 'Chưa tới giờ nhận phòng nên chưa thể xử lý no-show.');
        }
        if ($nowVn->gte($checkOutAt)) {
            return back()->with('error', 'Booking đã qua giờ trả phòng. Vui lòng xử lý theo quy trình đơn quá hạn.');
        }

        $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);

        $policy = app(\App\Services\BookingFinancialService::class)->cancellationPolicy($booking);

        try {
            app(\App\Services\BookingCancellationService::class)->cancel(
                $booking,
                $policy,
                Auth::id(),
                'receptionist_no_show_cancelled',
                'Lễ tân'
            );
            $booking->refresh()->update([
                'late_arrival_policy' => 'Lễ tân xác nhận khách không đến và hủy no-show lúc ' . $nowVn->format('d/m/Y H:i')
                    . '. Hạn giữ phòng: ' . ($holdLimitAt ? $holdLimitAt->format('H:i d/m/Y') : 'không xác định') . '.',
            ]);

            return back()->with('success', 'Đã hủy no-show, giải phóng phòng và xử lý tiền theo chính sách của booking.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi khi hủy no-show: ' . $e->getMessage());
        }
    }


    private function prepareRoomsForCheckIn(Booking $booking, \Carbon\Carbon $actualCheckInAt): string
    {
        $booking->loadMissing('bookingRooms.room');

        foreach ($booking->bookingRooms as $bookingRoom) {
            $room = $bookingRoom->room;

            if (!$room) {
                throw new \Exception('Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.');
            }

            $status = $room->status ?? null;
            if (!in_array($status, ['available', 'reserved'], true)) {
                throw new \Exception(
                    'Chưa thể check-in vì phòng ' . ($room->room_number ?? '---')
                    . ' đang ' . mb_strtolower($this->getRoomStatusLabel($status)) . '. '
                    . 'Không tự đổi phòng âm thầm khi check-in. Hãy hoàn tất dọn/kiểm tra/sửa phòng hoặc dùng chức năng quản lý phòng để đổi phòng rõ ràng trước khi nhận khách.'
                );
            }
        }

        return '';
    }
    private function handleEarlyCheckInFee(
        Booking $booking,
        array $data,
        \Carbon\Carbon $actualCheckInAt
    ): string {
        if ($booking->booking_type === 'hourly') {
            return '';
        }

        if (!$booking->check_in_at) {
            return '';
        }

        $plannedCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $standardCheckInAt = \Carbon\Carbon::parse($plannedCheckInAt->toDateString() . ' ' . $booking->standardCheckInTime(), 'Asia/Ho_Chi_Minh');

        if ($actualCheckInAt->toDateString() < $plannedCheckInAt->toDateString()) {
            throw new \Exception(
                'Khách đến trước ngày booking '
                . $plannedCheckInAt->format('d/m/Y')
                . '. Đây là đổi ngày lưu trú hoặc tạo booking mới, không phải check-in sớm trong cùng ngày.'
            );
        }

        if (!$actualCheckInAt->isSameDay($plannedCheckInAt) || $actualCheckInAt->greaterThanOrEqualTo($standardCheckInAt)) {
            return '';
        }

        $policy = $this->calculateEarlyCheckInFee($booking, $actualCheckInAt);

        $earlyFeeAlreadyIncluded = $booking->serviceItems()
            ->where('billing_status', 'confirmed')
            ->where(function ($query) {
                $query->where('type', 'early_checkin_fee')
                    ->orWhere(function ($policyFee) {
                        $policyFee->where('type', 'policy_violation_fee')
                            ->where(function ($text) {
                                $text->where('name', 'like', '%nhận phòng%')
                                    ->orWhere('note', 'like', '%Check-in%')
                                    ->orWhere('note', 'like', '%nhận phòng sớm%');
                            });
                    });
            })
            ->exists();

        if ($earlyFeeAlreadyIncluded) {
            return 'Phụ thu check-in sớm đã được tính khi tạo booking; không thu trùng lần nữa.';
        }

        if ($policy['amount'] > 0 && ($data['early_check_in_action'] ?? null) !== 'accept_fee') {
            throw new \Exception(
                'Khách check-in sớm lúc '
                . $actualCheckInAt->format('H:i')
                . ', phát sinh phụ thu '
                . number_format($policy['amount'], 0, ',', '.')
                . 'đ. Vui lòng xác nhận khách đồng ý phụ thu trước khi check-in.'
            );
        }

        if ($policy['amount'] > 0) {
            $earlyCheckInService = Service::firstOrCreate(
                [
                    'name' => 'Phụ thu check-in sớm',
                    'type' => 'early_checkin_fee',
                ],
                [
                    'service_group' => 'other',
                    'price' => 0,
                    'unit' => 'lần',
                    'description' => 'Phụ thu khi khách nhận phòng sớm trước giờ check-in chuẩn.',
                    'status' => 'active',
                ]
            );

            BookingServiceItem::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'name' => 'Phụ thu check-in sớm',
                    'type' => 'early_checkin_fee',
                ],
                [
                    'service_id' => $earlyCheckInService->id,
                    'unit_price' => $policy['amount'],
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => $actualCheckInAt,
                    'confirm_note' => $policy['policy_text'],
                    'total' => $policy['amount'],
                    'note' => 'Check-in sớm lúc '
                        . $actualCheckInAt->format('d/m/Y H:i')
                        . '. Đến sớm '
                        . $policy['duration_text']
                        . '. '
                        . $policy['policy_text'],
                ]
            );

            $booking->estimated_total = (float) $booking->estimated_total + $policy['amount'];
        }

        return 'Check-in sớm trong cùng ngày lúc '
            . $actualCheckInAt->format('d/m/Y H:i')
            . ', sớm hơn giờ chuẩn '
            . $policy['duration_text']
            . '. '
            . $policy['policy_text']
            . ' Phụ thu: '
            . number_format($policy['amount'], 0, ',', '.')
            . 'đ.';
    }
    private function calculateEarlyCheckInFee(Booking $booking, \Carbon\Carbon $actualCheckInAt): array
    {
        $plannedCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $standardCheckInAt = \Carbon\Carbon::parse($plannedCheckInAt->toDateString() . ' ' . $booking->standardCheckInTime(), 'Asia/Ho_Chi_Minh');
        $earlyMinutes = max(0, $actualCheckInAt->diffInMinutes($standardCheckInAt));
        $earlyHoursOnly = intdiv($earlyMinutes, 60);
        $earlyRemainMinutes = $earlyMinutes % 60;
        $durationText = $earlyHoursOnly . ' giờ'
            . ($earlyRemainMinutes > 0 ? ' ' . $earlyRemainMinutes . ' phút' : '');

        $basePrice = $booking->bookingRooms->sum(fn ($bookingRoom) => (float) $bookingRoom->price_at_booking);
        if ($basePrice <= 0) {
            $basePrice = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $policy = app(StayPricingPolicyService::class)->earlyCheckIn($actualCheckInAt, $basePrice, $booking);

        return [
            'percent' => $policy['percent'],
            'base_price' => $basePrice,
            'amount' => $policy['amount'],
            'policy_text' => $policy['policy_text'],
            'early_minutes' => $earlyMinutes,
            'duration_text' => $durationText,
        ];
    }




    private function getRoomStatusLabel(?string $status): string
    {
        return [
            'available' => 'Trống',
            'reserved' => 'Đã giữ',
            'occupied' => 'Đang ở',
            'cleaning' => 'Cần dọn',
            'inspection' => 'Chờ kiểm tra',
            'maintenance' => 'Bảo trì',
        ][$status] ?? 'Chưa rõ trạng thái';
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
