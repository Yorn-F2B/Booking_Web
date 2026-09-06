<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CustomerRequestFormMail;
use App\Models\Booking;
use App\Models\CustomerRequest;
use App\Models\BookingServiceItem;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Support\Realtime;
use App\Services\StayPricingPolicyService;
use App\Services\BookingRepricingService;
use App\Services\BookingFinancialService;

class CustomerRequestController extends Controller
{
    public function index(Request $request)
    {
        $items = CustomerRequest::with(['booking', 'reviewer'])
            ->where('type', 'late_arrival')
            ->whereHas('booking', fn ($query) => $query->visibleToOperationsUser(Auth::user()))
            ->latest('id');

        if ($request->filled('booking')) {
            $items->where('booking_id', $request->integer('booking'));
        }
        if ($request->filled('status')) {
            $items->where('status', $request->status);
        }

        return view('admin.pages.customer-requests.index', [
            'items' => $items->paginate(20)->withQueryString(),
        ]);
    }

    public function show(CustomerRequest $customerRequest)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        $customerRequest->load(['booking.bookingRooms.room', 'attachments', 'reviewer']);

        $details = (array) ($customerRequest->details ?? []);
        $pageVersion = max(1, (int) ($details['version'] ?? 1));
        $acknowledgedVersion = (int) ($details['admin_acknowledged_version'] ?? 0);
        $hasUnseenUpdate = $acknowledgedVersion < $pageVersion;

        return view('admin.pages.customer-requests.show', compact(
            'customerRequest', 'pageVersion', 'acknowledgedVersion', 'hasUnseenUpdate'
        ));
    }

    public function approve(Request $request, CustomerRequest $customerRequest)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        abort_unless($customerRequest->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'version' => ['required', 'integer', 'min:1'],
        ]);
        $this->guardLatestAcknowledged($customerRequest, (int) $data['version']);

        $booking = $customerRequest->booking()->with(['bookingRooms', 'roomCategory'])->firstOrFail();
        if (!in_array($booking->status, ['pending', 'confirmed'], true) || $booking->actual_check_in) {
            return back()->with('error', 'Booking không còn ở trạng thái có thể duyệt yêu cầu đến muộn.');
        }
        if (!$booking->usesLateArrivalNoShowPolicy()) {
            return back()->with('error', 'Booking này không áp dụng giờ G/đến muộn (ví dụ đơn ở ngay, theo giờ hoặc vừa đổi lịch từ ngày tương lai về hôm nay).');
        }

        $timezone = 'Asia/Ho_Chi_Minh';
        $cutoff = Carbon::parse($booking->check_in_date . ' ' . $booking->lateArrivalCutoffTime(), $timezone);
        $arrival = Carbon::parse($customerRequest->expected_arrival_at, $timezone);
        $checkOut = Carbon::parse($booking->check_out_at, $timezone);

        if ($arrival->lessThanOrEqualTo($cutoff)) {
            return back()->with('error', 'Giờ khách dự kiến đến phải sau giờ G ' . $cutoff->format('H:i d/m/Y') . '.');
        }
        if ($arrival->greaterThanOrEqualTo($checkOut)) {
            return back()->with('error', 'Giờ khách dự kiến đến phải trước giờ trả phòng của booking.');
        }

        $oneNightTotal = (float) $booking->bookingRooms->sum('price_at_booking');
        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $latePolicy = app(StayPricingPolicyService::class)->lateArrival($arrival, $oneNightTotal, $cutoff, $booking);
        $feeAmount = (float) ($latePolicy['amount'] ?? 0);
        $hoursAfterCutoff = (float) ($latePolicy['hours_after_cutoff'] ?? 0);
        $holdUntil = $arrival->copy()->addMinutes($booking->lateArrivalGraceMinutes())->min($checkOut);
        $now = now($timezone);

        try {
            DB::transaction(function () use ($customerRequest, $booking, $data, $arrival, $cutoff, $holdUntil, $latePolicy, $feeAmount, $hoursAfterCutoff, $now, $oneNightTotal) {
                $service = Service::query()
                    ->where('name', 'Phụ thu khách đến muộn')
                    ->orWhere('type', 'policy_violation_fee')
                    ->orderByRaw("CASE WHEN name = 'Phụ thu khách đến muộn' THEN 0 ELSE 1 END")
                    ->first();

                if (!$service) {
                    throw new \RuntimeException('Chưa cấu hình dịch vụ Phụ thu khách đến muộn trong danh mục dịch vụ.');
                }

                BookingServiceItem::updateOrCreate(
                    ['booking_id' => $booking->id, 'type' => 'late_arrival_fee'],
                    [
                        'service_id' => $service->id,
                        'name' => 'Phụ thu khách đến muộn',
                        'unit_price' => $feeAmount,
                        'quantity' => 1,
                        'used_quantity' => 1,
                        'billing_status' => 'confirmed',
                        'confirmed_by' => Auth::id(),
                        'confirmed_at' => $now,
                        'confirm_note' => (string) ($latePolicy['policy_text'] ?? ''),
                        'total' => $feeAmount,
                        'note' => 'Giờ G: ' . $cutoff->format('d/m/Y H:i')
                            . '. Khách dự kiến đến: ' . $arrival->format('d/m/Y H:i')
                            . '. Giữ phòng đến: ' . $holdUntil->format('d/m/Y H:i') . '.',
                    ]
                );

                $booking->update([
                    'late_arrival_fee' => $feeAmount,
                    'late_arrival_hours' => $hoursAfterCutoff,
                    'late_arrival_confirmed_at' => $now,
                    'late_arrival_confirmed_by' => Auth::id(),
                    'late_arrival_policy' => '[LATE_ARRIVAL_REQUEST_APPROVED] Khách dự kiến đến '
                        . $arrival->format('d/m/Y H:i') . '; giữ phòng đến '
                        . $holdUntil->format('d/m/Y H:i') . '. '
                        . (string) ($latePolicy['policy_text'] ?? '')
                        . ' Phụ thu: ' . number_format($feeAmount, 0, ',', '.') . 'đ.',
                ]);

                $customerRequest->update([
                    'status' => 'approved',
                    'admin_note' => $data['admin_note'] ?? null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => $now,
                ]);

                // Đồng bộ phụ thu vào tổng booking ngay khi duyệt. Nếu không làm bước này,
                // check-in sau giờ G nhìn thấy "đã duyệt" nhưng lại thiếu khoản phí confirmed.
                $freshBooking = $booking->fresh(['bookingRooms.room.category', 'roomCategory', 'serviceItems.service', 'bookingPromotions', 'payments', 'customer', 'guests', 'roomInspections.items']);
                $preview = app(BookingRepricingService::class)->preview(
                    $freshBooking,
                    Carbon::parse($freshBooking->check_in_at, 'Asia/Ho_Chi_Minh'),
                    Carbon::parse($freshBooking->check_out_at, 'Asia/Ho_Chi_Minh'),
                    $oneNightTotal,
                    (float) ($freshBooking->roomCategory->price ?? 0)
                );
                app(BookingRepricingService::class)->apply($freshBooking, $preview);

                $freshBooking->refresh();
                $freshBooking->update([
                    'final_total' => app(BookingFinancialService::class)->currentTotal($freshBooking),
                ]);

                $freshBooking->logs()->create([
                    'user_id' => Auth::id(),
                    'action' => 'late_arrival_request_approved',
                    'description' => 'Đã duyệt yêu cầu đến sau giờ G. Dự kiến đến: '
                        . $arrival->format('d/m/Y H:i') . '. Giữ phòng đến: '
                        . $holdUntil->format('d/m/Y H:i') . '. Phụ thu: '
                        . number_format($feeAmount, 0, ',', '.') . 'đ.',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể duyệt yêu cầu đến muộn: ' . $e->getMessage());
        }

        Realtime::booking($booking->fresh(), 'late_arrival_approved', false);

        return back()->with('success', 'Đã duyệt yêu cầu đến sau giờ G, giữ phòng đến '
            . $holdUntil->format('H:i d/m/Y') . ' và ghi nhận phụ thu '
            . number_format($feeAmount, 0, ',', '.') . 'đ.');
    }

    public function reject(Request $request, CustomerRequest $customerRequest)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        abort_unless($customerRequest->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
            'version' => ['required', 'integer', 'min:1'],
        ]);
        $this->guardLatestAcknowledged($customerRequest, (int) $data['version']);
        $customerRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu đến sau giờ G.');
    }

    public function receptionistNote(Request $request, CustomerRequest $customerRequest)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        $data = $request->validate(['receptionist_note' => ['required', 'string', 'max:2000']]);
        $customerRequest->update(['receptionist_note' => $data['receptionist_note']]);

        return back()->with('success', 'Đã lưu ghi chú của lễ tân.');
    }

    public function sendGuestForm(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);
        abort_unless(in_array($booking->status, ['pending', 'confirmed'], true), 422, 'Booking hiện không thể gửi form đến muộn.');
        if (!$booking->usesLateArrivalNoShowPolicy()) {
            return back()->with('error', 'Booking này không áp dụng giờ G/đến muộn nên không cần gửi form đến muộn.');
        }

        $hasPending = $booking->customerRequests()
            ->where('type', 'late_arrival')
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return back()->with('error', 'Booking đang có yêu cầu đến sau giờ G chưa xử lý. Không thể gửi thêm form về email.');
        }

        $data = $request->validate(['email' => ['required', 'email']]);
        $formExpireMinutes = max(5, (int) app(\App\Services\HotelPolicyService::class)
            ->forBooking($booking, 'stay.late_arrival_form_expire_minutes', 1440));
        $expires = now()->addMinutes($formExpireMinutes);
        $url = URL::temporarySignedRoute(
            'guest-customer-requests.form',
            $expires,
            ['booking' => $booking->id]
        );

        app(\App\Services\EmailDeliveryService::class)->sendOrFail($data['email'], new CustomerRequestFormMail($booking, $url, $expires, 'late_arrival'), 'late_arrival_form', $booking);

        return back()->with('success', 'Đã gửi form báo đến sau giờ G cho khách. Form dùng được cho cả khách có tài khoản và khách vãng lai.');
    }

    public function acknowledge(Request $request, CustomerRequest $customerRequest)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        abort_unless($customerRequest->status === 'pending', 422, 'Yêu cầu này đã được xử lý.');

        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        $customerRequest->refresh();
        $details = (array) ($customerRequest->details ?? []);
        $currentVersion = max(1, (int) ($details['version'] ?? 1));
        if ((int) $data['version'] !== $currentVersion) {
            return back()->with('error', 'Khách vừa cập nhật yêu cầu. Hãy tải dữ liệu mới nhất rồi xác nhận lại.');
        }

        $details['admin_acknowledged_version'] = $currentVersion;
        $details['admin_acknowledged_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
        $customerRequest->update(['details' => $details]);

        return back()->with('success', 'Đã cập nhật và xác nhận đang xem phiên bản mới nhất. Bây giờ có thể duyệt hoặc từ chối.');
    }

    public function updates(CustomerRequest $customerRequest)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        $details = (array) ($customerRequest->details ?? []);

        return response()->json([
            'current_version' => max(1, (int) ($details['version'] ?? 1)),
            'acknowledged_version' => (int) ($details['admin_acknowledged_version'] ?? 0),
            'updated_at' => optional($customerRequest->updated_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s'),
        ]);
    }

    private function guardLatestAcknowledged(CustomerRequest $customerRequest, int $submittedVersion): void
    {
        $customerRequest->refresh();
        $details = (array) ($customerRequest->details ?? []);
        $currentVersion = max(1, (int) ($details['version'] ?? 1));
        $acknowledgedVersion = (int) ($details['admin_acknowledged_version'] ?? 0);

        abort_if($submittedVersion !== $currentVersion || $acknowledgedVersion !== $currentVersion, 422,
            'Yêu cầu đã có dữ liệu mới hoặc chưa được cập nhật. Hãy bấm “Cập nhật dữ liệu mới nhất” trước khi xử lý.');
    }

    public function attachment(CustomerRequest $customerRequest, $attachment)
    {
        abort_unless($customerRequest->type === 'late_arrival', 404);
        $this->guardCanAccessBooking($customerRequest->booking);
        $file = $customerRequest->attachments()->findOrFail($attachment);
        $path = storage_path('app/public/' . ltrim((string) $file->file_path, '/'));
        abort_unless(is_file($path), 404, 'Tệp đính kèm không còn tồn tại.');

        return response()->file($path);
    }

    private function guardCanAccessBooking(?Booking $booking): void
    {
        abort_unless($booking && $booking->canBeHandledBy(Auth::user()), 403, 'Bạn không được phân công xử lý booking này.');
    }
}
