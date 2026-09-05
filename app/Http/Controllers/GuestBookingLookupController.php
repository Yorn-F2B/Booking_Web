<?php

namespace App\Http\Controllers;

use App\Mail\GuestBookingCancelledMail;
use App\Mail\GuestBookingOtpMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Services\BookingCancellationService;
use App\Services\BookingFinancialService;
use App\Services\RoomSelectionFallbackService;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuestBookingLookupController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const VERIFIED_TTL_MINUTES = 30;
    private const MAX_OTP_ATTEMPTS = 5;

    public function index(Request $request)
    {
        return view('guest-bookings.lookup', [
            'bookingCode' => old('booking_code', (string) $request->query('booking_code', '')),
            'email' => old('email', (string) $request->query('email', '')),
        ]);
    }

    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'booking_code' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ], [
            'booking_code.required' => 'Vui lòng nhập mã booking.',
            'email.required' => 'Vui lòng nhập email đã dùng khi đặt phòng.',
            'email.email' => 'Email không đúng định dạng.',
        ]);

        $bookingCode = strtoupper(trim($data['booking_code']));
        $email = Str::lower(trim($data['email']));
        $booking = $this->findGuestBooking($bookingCode, $email);

        if (!$booking) {
            return back()->withInput()->with('error', 'Không tìm thấy booking khớp với mã booking và email đã nhập.');
        }

        $otp = (string) random_int(100000, 999999);
        $lookupKey = $this->lookupKey($bookingCode, $email);

        Cache::put($this->otpCacheKey($lookupKey), [
            'hash' => hash('sha256', $otp),
            'attempts' => 0,
            'booking_id' => $booking->id,
            'email' => $email,
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        try {
            app(\App\Services\EmailDeliveryService::class)->sendOrFail($email, new GuestBookingOtpMail($booking, $otp, self::OTP_TTL_MINUTES), 'guest_booking_lookup_otp', $booking);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => null,
                'action' => 'guest_lookup_otp_sent',
                'description' => 'Đã gửi OTP tra cứu booking đến email ' . $this->maskEmail($email) . '.',
            ]);
        } catch (\Throwable $e) {
            Cache::forget($this->otpCacheKey($lookupKey));
            Log::warning('Không gửi được OTP tra cứu booking.', [
                'booking_id' => $booking->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Chưa gửi được mã xác thực. Vui lòng thử lại hoặc liên hệ lễ tân.');
        }

        return redirect()->route('guest-bookings.verify-form')->with([
            'guest_lookup_code' => $bookingCode,
            'guest_lookup_email' => $email,
            'success' => 'Mã OTP đã được gửi tới ' . $this->maskEmail($email) . '. Mã có hiệu lực trong ' . self::OTP_TTL_MINUTES . ' phút.',
        ]);
    }

    public function verifyForm(Request $request)
    {
        $bookingCode = (string) ($request->session()->get('guest_lookup_code') ?: old('booking_code'));
        $email = (string) ($request->session()->get('guest_lookup_email') ?: old('email'));

        if ($bookingCode === '' || $email === '') {
            return redirect()->route('guest-bookings.index')->with('error', 'Vui lòng tra cứu booking trước khi nhập OTP.');
        }

        return view('guest-bookings.verify', compact('bookingCode', 'email'));
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'booking_code' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP gồm đúng 6 chữ số.',
        ]);

        $bookingCode = strtoupper(trim($data['booking_code']));
        $email = Str::lower(trim($data['email']));
        $lookupKey = $this->lookupKey($bookingCode, $email);
        $cacheKey = $this->otpCacheKey($lookupKey);
        $payload = Cache::get($cacheKey);

        if (!$payload) {
            return redirect()->route('guest-bookings.index')->with('error', 'Mã OTP đã hết hạn. Vui lòng tra cứu và gửi mã mới.');
        }

        $attempts = (int) ($payload['attempts'] ?? 0) + 1;
        if (!hash_equals((string) $payload['hash'], hash('sha256', (string) $data['otp']))) {
            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                Cache::forget($cacheKey);
                return redirect()->route('guest-bookings.index')->with('error', 'Bạn đã nhập sai OTP quá nhiều lần. Vui lòng gửi mã mới.');
            }

            $payload['attempts'] = $attempts;
            Cache::put($cacheKey, $payload, now()->addMinutes(self::OTP_TTL_MINUTES));

            return back()->withInput($request->except('otp'))->with('error', 'Mã OTP không đúng. Bạn còn ' . (self::MAX_OTP_ATTEMPTS - $attempts) . ' lần thử.');
        }

        $booking = $this->findGuestBooking($bookingCode, $email);
        if (!$booking || (int) $booking->id !== (int) ($payload['booking_id'] ?? 0)) {
            Cache::forget($cacheKey);
            return redirect()->route('guest-bookings.index')->with('error', 'Thông tin booking đã thay đổi. Vui lòng tra cứu lại.');
        }

        Cache::forget($cacheKey);
        $accessToken = Str::random(64);
        Cache::put($this->accessCacheKey($accessToken), [
            'booking_id' => $booking->id,
            'email' => $email,
        ], now()->addMinutes(self::VERIFIED_TTL_MINUTES));

        return redirect()->route('guest-bookings.show', ['token' => $accessToken]);
    }

    public function show(Request $request, string $token, BookingFinancialService $financials)
    {
        [$booking, $email] = $this->resolveAccess($token);

        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
        ]);

        $paidAmount = (float) $booking->payments()->where('status', 'success')->sum('amount');
        $policy = $financials->cancellationPolicy($booking);
        $cancelLimitAt = $this->cancelLimitAt($booking);
        $canCancel = in_array($booking->status, ['pending', 'confirmed'], true)
            && !$booking->actual_check_in
            && (!$cancelLimitAt || now('Asia/Ho_Chi_Minh')->lt($cancelLimitAt));

        return view('guest-bookings.show', compact(
            'booking',
            'paidAmount',
            'policy',
            'canCancel',
            'cancelLimitAt',
            'token',
            'email'
        ));
    }

    public function respondToRoomSelectionFallback(
        Request $request,
        string $token,
        RoomSelectionFallbackService $fallbackService
    ) {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline'],
        ], [
            'decision.required' => 'Vui lòng chọn Đồng ý hoặc Từ chối phòng dự phòng.',
            'decision.in' => 'Phản hồi phòng dự phòng không hợp lệ.',
        ]);

        [$booking] = $this->resolveAccess($token);

        try {
            $result = $fallbackService->respond(
                $booking,
                (string) $data['decision'],
                null,
                'Khách xác nhận qua trang tra cứu OTP'
            );
        } catch (\Throwable $e) {
            Log::warning('Khách vãng lai phản hồi phòng dự phòng thất bại.', [
                'booking_id' => $booking->id,
                'decision' => $data['decision'],
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Chưa thể ghi nhận phản hồi phòng dự phòng: ' . $e->getMessage());
        }

        $freshBooking = $booking->fresh();
        Realtime::booking(
            $freshBooking,
            ($result['accepted'] ?? false) ? 'manual_room_fallback_accepted' : 'manual_room_fallback_declined'
        );

        if ($result['accepted'] ?? false) {
            return back()->with('success', 'Đã xác nhận sử dụng phòng dự phòng. Booking tiếp tục giữ nguyên và không thu phí đảm bảo yêu cầu phòng.');
        }

        $refundDue = (float) ($result['refund_due'] ?? 0);

        return back()->with(
            'success',
            $refundDue > 0
                ? 'Đã hủy booking. Khách sạn phải hoàn lại toàn bộ ' . number_format($refundDue, 0, ',', '.') . 'đ đã thanh toán.'
                : 'Đã hủy booking. Booking chưa phát sinh khoản thanh toán nên không có tiền cần hoàn.'
        );
    }

    public function cancel(
        Request $request,
        string $token,
        BookingFinancialService $financials,
        BookingCancellationService $cancellations
    ) {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'confirm_forfeit' => ['accepted'],
        ], [
            'reason.required' => 'Vui lòng cho biết lý do hủy booking.',
            'confirm_forfeit.accepted' => 'Bạn phải xác nhận đã hiểu tiền đã thanh toán sẽ không được hoàn lại.',
        ]);

        [$booking, $email] = $this->resolveAccess($token);
        $cancelLimitAt = $this->cancelLimitAt($booking);

        if (!in_array($booking->status, ['pending', 'confirmed'], true) || $booking->actual_check_in) {
            return back()->with('error', 'Booking không còn ở trạng thái có thể hủy.');
        }

        if ($booking->room_selection_mode === 'manual' && $booking->room_selection_status === 'awaiting_guest') {
            return back()->with('error', 'Khách sạn chưa đáp ứng được yêu cầu phòng. Vui lòng dùng lựa chọn Đồng ý hoặc Từ chối phòng dự phòng để được áp dụng đúng chính sách hoàn tiền.');
        }

        if ($cancelLimitAt && now('Asia/Ho_Chi_Minh')->greaterThanOrEqualTo($cancelLimitAt)) {
            return back()->with('error', 'Booking đã quá giờ tự hủy. Vui lòng liên hệ lễ tân để được hỗ trợ.');
        }

        $policy = $financials->cancellationPolicy($booking);
        $paidAmount = (float) $booking->payments()->where('status', 'success')->sum('amount');
        $reason = trim((string) $request->input('reason'));

        try {
            $cancellations->cancel(
                $booking,
                $policy,
                null,
                'guest_cancelled_via_lookup',
                'Khách xác nhận hủy qua trang tra cứu OTP'
            );

            DB::table('booking_logs')->insert([
                'booking_id' => $booking->id,
                'user_id' => null,
                'action' => 'guest_cancellation_reason',
                'description' => 'Lý do khách cung cấp: ' . $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::forget($this->accessCacheKey($token));
            Realtime::booking($booking->fresh(), 'cancelled');

            try {
                app(\App\Services\EmailDeliveryService::class)->sendOrFail($email, new GuestBookingCancelledMail($booking->fresh(), $paidAmount, $reason), 'booking_cancelled', $booking);
            } catch (\Throwable $mailError) {
                Log::warning('Không gửi được email xác nhận hủy booking.', [
                    'booking_id' => $booking->id,
                    'email' => $email,
                    'error' => $mailError->getMessage(),
                ]);
            }

            return redirect()->route('guest-bookings.cancelled', ['booking_code' => $booking->booking_code]);
        } catch (\Throwable $e) {
            Log::warning('Hủy booking qua tra cứu thất bại.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Chưa thể hủy booking. Vui lòng tải lại trang hoặc liên hệ lễ tân.');
        }
    }

    public function cancelled(Request $request)
    {
        return view('guest-bookings.cancelled', [
            'bookingCode' => (string) $request->query('booking_code'),
        ]);
    }

    private function findGuestBooking(string $bookingCode, string $email): ?Booking
    {
        return Booking::query()
            ->with('customer')
            ->whereRaw('UPPER(booking_code) = ?', [strtoupper($bookingCode)])
            ->where(function ($query) use ($email) {
                $query->whereRaw('LOWER(customer_email_snapshot) = ?', [$email])
                    ->orWhereHas('customer', function ($customerQuery) use ($email) {
                        $customerQuery->whereRaw('LOWER(email) = ?', [$email]);
                    });
            })
            ->first();
    }

    private function resolveAccess(string $token): array
    {
        $payload = Cache::get($this->accessCacheKey($token));
        if (!$payload) {
            abort(419, 'Phiên xác thực booking đã hết hạn. Vui lòng tra cứu lại.');
        }

        $booking = Booking::with('customer')->find($payload['booking_id'] ?? 0);
        $email = Str::lower((string) ($payload['email'] ?? ''));

        if (!$booking || Str::lower((string) $booking->booked_customer_email) !== $email) {
            Cache::forget($this->accessCacheKey($token));
            abort(403);
        }

        Cache::put($this->accessCacheKey($token), $payload, now()->addMinutes(self::VERIFIED_TTL_MINUTES));

        return [$booking, $email];
    }

    private function cancelLimitAt(Booking $booking)
    {
        $checkInAt = $booking->check_in_at?->copy()->timezone('Asia/Ho_Chi_Minh');
        if (!$checkInAt) {
            return null;
        }

        // Đồng bộ với chính sách booking đã snapshot tại thời điểm tạo đơn.
        return $booking->booking_type === 'hourly'
            ? $checkInAt->copy()->addMinutes($booking->hourlyCancelGraceMinutes())
            : \Carbon\Carbon::parse($booking->check_in_date . ' ' . $booking->directCancelCutoffTime(), 'Asia/Ho_Chi_Minh');
    }

    private function lookupKey(string $bookingCode, string $email): string
    {
        return hash('sha256', strtoupper($bookingCode) . '|' . Str::lower($email));
    }

    private function otpCacheKey(string $lookupKey): string
    {
        return 'guest-booking-otp:' . $lookupKey;
    }

    private function accessCacheKey(string $token): string
    {
        return 'guest-booking-access:' . hash('sha256', $token);
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));
        return $visible . str_repeat('*', max(3, mb_strlen($name) - mb_strlen($visible))) . '@' . $domain;
    }
}
