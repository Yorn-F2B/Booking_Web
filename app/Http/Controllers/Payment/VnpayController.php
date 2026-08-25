<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Mail\AdminVnpayPaymentRequestMail;
use App\Mail\BookingCreatedMail;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Services\VnpayService;
use App\Services\BookingFinancialService;
use App\Services\PendingPaymentRequestService;
use App\Services\HotelPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\Realtime;

class VnpayController extends Controller
{
    public function create(Request $request, Booking $booking, VnpayService $vnpayService)
    {
        $this->ensureCustomerCanPay($booking);

        $data = $request->validate([
            'payment_type' => 'required|in:deposit_30',
        ]);

        $payment = DB::transaction(function () use ($booking, $data, $vnpayService) {
            // Khóa booking để hai tab không thể cùng lúc tạo hai link/giao dịch
            // pending cho cùng một mục đích.
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $this->ensureCustomerCanPay($lockedBooking);

            if ($lockedBooking->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'payment_type' => 'Booking này đã thanh toán đủ.',
                ]);
            }

            if (!in_array($lockedBooking->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Booking này không còn ở trạng thái có thể thanh toán online.',
                ]);
            }

            if (!$this->hasAvailableRoomForBooking($lockedBooking)) {
                throw ValidationException::withMessages([
                    'payment_type' => 'Hạng phòng này hiện đã hết phòng trống trong thời gian bạn chọn. Vui lòng liên hệ khách sạn để được hỗ trợ.',
                ]);
            }

            app(PendingPaymentRequestService::class)->expire(
                $lockedBooking->id,
                'created_new_vnpay_request_same_purpose',
                $data['payment_type']
            );

            $financials = app(BookingFinancialService::class);
            $financials->refreshPaymentStatus($lockedBooking);
            $lockedBooking->refresh();

            $amount = $this->calculatePaymentAmount($lockedBooking, $data['payment_type']);
            if ($amount <= 0) {
                $lockedBooking->update(['payment_expires_at' => null]);
                return null;
            }

            $requestExpiresAt = now('Asia/Ho_Chi_Minh')->addMinutes($vnpayService->expireMinutes());
            $lockedBooking->update(['payment_expires_at' => $requestExpiresAt]);

            return BookingPayment::create([
                'booking_id' => $lockedBooking->id,
                'provider' => 'vnpay',
                'txn_ref' => $this->generateTxnRef($lockedBooking),
                'amount' => $amount,
                'status' => 'pending',
                'payment_type' => $data['payment_type'],
                'raw_response' => [
                    'request_expires_at' => $requestExpiresAt->toDateTimeString(),
                    'request_expire_minutes' => $vnpayService->expireMinutes(),
                ],
            ]);
        });

        if (!$payment) {
            return redirect()->route('bookings.show', $booking)
                ->with('success', 'Booking đã đủ mức cọc ' . $this->depositPercentLabel($booking) . ' cần thiết, không cần tạo thêm giao dịch.');
        }

        $freshBooking = Booking::findOrFail($booking->id);

        return redirect()->away(
            $vnpayService->createPaymentUrl($freshBooking, $payment, $request)
        );
    }

    public function adminCreate(Request $request, Booking $booking, VnpayService $vnpayService)
    {
        $data = $request->validate([
            'payment_type' => 'required|in:deposit_30,custom',
            'customer_email' => 'required|email|max:150',
        ], [
            'payment_type.required' => 'Vui lòng chọn hình thức thanh toán VNPay.',
            'payment_type.in' => 'Hình thức thanh toán VNPay không hợp lệ.',
            'customer_email.required' => 'Vui lòng nhập email khách để gửi yêu cầu thanh toán VNPay.',
            'customer_email.email' => 'Email khách không hợp lệ.',
        ]);

        try {
            $result = DB::transaction(function () use ($booking, $data, $vnpayService) {
                $lockedBooking = Booking::with(['customer', 'roomCategory', 'bookingRooms.room'])
                    ->whereKey($booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                app(BookingFinancialService::class)->refreshPaymentStatus($lockedBooking);
                $lockedBooking->refresh();

                if ($lockedBooking->payment_status === 'paid') {
                    throw ValidationException::withMessages([
                        'payment_type' => 'Booking này đã thanh toán đủ.',
                    ]);
                }

                if (!in_array($lockedBooking->status, ['pending', 'confirmed', 'checked_in', 'inspection_requested'], true)) {
                    throw ValidationException::withMessages([
                        'payment_type' => 'Booking đã kết thúc hoặc không còn ở trạng thái có thể tạo thanh toán VNPay.',
                    ]);
                }

                app(PendingPaymentRequestService::class)->expire(
                    $lockedBooking->id,
                    'created_new_admin_vnpay_request_same_purpose',
                    $data['payment_type']
                );

                $amount = $this->calculatePaymentAmount($lockedBooking, $data['payment_type'], true);
                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'payment_type' => 'Số tiền cần thanh toán không hợp lệ hoặc booking đã đủ tiền cọc/đã thanh toán đủ.',
                    ]);
                }

                $payment = BookingPayment::create([
                    'booking_id' => $lockedBooking->id,
                    'provider' => 'admin_vnpay',
                    'txn_ref' => $this->generateTxnRef($lockedBooking),
                    'amount' => $amount,
                    'status' => 'pending',
                    'payment_type' => $data['payment_type'],
                    'raw_response' => [
                        'source' => 'admin_email_request',
                        'customer_email' => $data['customer_email'],
                        'staff_id' => Auth::id(),
                    ],
                ]);

                $requestExpiresAt = now('Asia/Ho_Chi_Minh')->addMinutes($vnpayService->adminRequestExpireMinutes());
                $paymentRequestUrl = route('payment.vnpay.admin-request', [
                    'payment' => $payment->id,
                    'token' => $vnpayService->paymentRequestToken($payment),
                ]);

                $rawResponse = $payment->raw_response ?? [];
                $rawResponse['payment_request_url'] = $paymentRequestUrl;
                $rawResponse['request_expires_at'] = $requestExpiresAt->toDateTimeString();
                $rawResponse['request_expire_minutes'] = $vnpayService->adminRequestExpireMinutes();
                $payment->update(['raw_response' => $rawResponse]);

                return [
                    'booking' => $lockedBooking,
                    'payment' => $payment,
                    'amount' => $amount,
                    'request_expires_at' => $requestExpiresAt,
                    'payment_request_url' => $paymentRequestUrl,
                    'payment_purpose' => $data['payment_type'] === 'deposit_30'
                        ? 'cọc ' . $this->depositPercentLabel($lockedBooking)
                        : 'thanh toán số tiền còn lại',
                ];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể tạo yêu cầu thanh toán VNPay: ' . $e->getMessage());
        }

        $booking = $result['booking'];
        $payment = $result['payment'];
        $amount = $result['amount'];
        $requestExpiresAt = $result['request_expires_at'];
        $paymentRequestUrl = $result['payment_request_url'];
        $paymentPurpose = $result['payment_purpose'];

        try {
            Mail::to($data['customer_email'])->send(
                new AdminVnpayPaymentRequestMail($booking, $payment, $paymentRequestUrl, $requestExpiresAt)
            );

            $rawResponse = $payment->fresh()->raw_response ?? [];
            $rawResponse['email_sent_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
            $payment->update(['raw_response' => $rawResponse]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'admin_vnpay_email_sent',
                'description' => 'Lễ tân gửi yêu cầu thanh toán VNPay qua email '
                    . $data['customer_email'] . ': '
                    . number_format((float) $amount, 0, ',', '.') . 'đ ('
                    . $paymentPurpose . '). Mã giao dịch: ' . $payment->txn_ref
                    . '. Link yêu cầu có hiệu lực đến ' . $requestExpiresAt->format('d/m/Y H:i') . '.',
            ]);

            return back()
                ->with('success', 'Đã gửi email yêu cầu thanh toán VNPay cho khách. Số tiền: ' . number_format((float) $amount, 0, ',', '.') . 'đ. Link email có hiệu lực đến ' . $requestExpiresAt->format('d/m/Y H:i') . '.')
                ->with('admin_vnpay_payment_url', $paymentRequestUrl);
        } catch (\Throwable $e) {
            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'admin_vnpay_email_failed',
                'description' => 'Đã tạo mã thanh toán VNPay ' . $payment->txn_ref
                    . ' nhưng gửi email thất bại: ' . $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Đã tạo yêu cầu thanh toán VNPay nhưng gửi email thất bại: ' . $e->getMessage() . '. Có thể copy link bên dưới gửi thủ công cho khách.')
                ->with('admin_vnpay_payment_url', $paymentRequestUrl);
        }
    }

    public function payRequest(Request $request, BookingPayment $payment, VnpayService $vnpayService)
    {
        if ($payment->provider !== 'admin_vnpay') {
            return redirect()
                ->route('home')
                ->with('error', 'Yêu cầu thanh toán không hợp lệ.');
        }

        if (!$vnpayService->verifyPaymentRequestToken($payment, (string) $request->query('token'))) {
            return redirect()
                ->route('home')
                ->with('error', 'Link thanh toán không hợp lệ hoặc đã bị thay thế. Vui lòng liên hệ lễ tân để nhận link mới.');
        }

        $payment->loadMissing(['booking.customer', 'booking.roomCategory', 'booking.bookingRooms.room']);
        $booking = $payment->booking;

        if (!$booking) {
            return redirect()
                ->route('home')
                ->with('error', 'Không tìm thấy booking của yêu cầu thanh toán.');
        }

        if ($payment->status === 'success') {
            return redirect()
                ->route('home')
                ->with('success', 'Giao dịch này đã được thanh toán thành công trước đó. Cảm ơn quý khách.');
        }

        if ($payment->status !== 'pending') {
            return redirect()
                ->route('home')
                ->with('error', 'Yêu cầu thanh toán này không còn hiệu lực. Vui lòng liên hệ lễ tân để nhận link mới.');
        }

        $rawResponse = $payment->raw_response ?? [];
        $requestExpiresAt = !empty($rawResponse['request_expires_at'] ?? null)
            ? \Carbon\Carbon::parse($rawResponse['request_expires_at'], 'Asia/Ho_Chi_Minh')
            : $payment->created_at?->copy()->timezone('Asia/Ho_Chi_Minh')->addMinutes($vnpayService->adminRequestExpireMinutes());

        if ($requestExpiresAt && now('Asia/Ho_Chi_Minh')->greaterThan($requestExpiresAt)) {
            $this->markPaymentRequestFailed($payment, 'EXPIRED', 'Yêu cầu thanh toán VNPay đã hết hạn.');

            return redirect()
                ->route('home')
                ->with('error', 'Link thanh toán đã hết hạn. Vui lòng liên hệ lễ tân để nhận link mới.');
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in', 'inspection_requested'], true)) {
            $this->markPaymentRequestFailed($payment, 'BOOKING_CLOSED', 'Booking đã kết thúc hoặc không còn hiệu lực thanh toán VNPay.');

            return redirect()
                ->route('home')
                ->with('error', 'Booking này đã kết thúc hoặc không còn hiệu lực thanh toán.');
        }

        if ($booking->payment_status === 'paid') {
            $this->markPaymentRequestFailed($payment, 'ALREADY_PAID', 'Booking đã thanh toán đủ trước khi khách mở link VNPay.');

            return redirect()
                ->route('home')
                ->with('success', 'Booking này đã được thanh toán đủ. Cảm ơn quý khách.');
        }

        $expectedAmount = $this->calculatePaymentAmount($booking, $payment->payment_type, true);

        if ($expectedAmount <= 0 || abs(round($expectedAmount, 0) - round((float) $payment->amount, 0)) >= 1) {
            $this->markPaymentRequestFailed($payment, 'AMOUNT_CHANGED', 'Số tiền booking đã thay đổi. Cần tạo lại yêu cầu thanh toán VNPay.');

            return redirect()
                ->route('home')
                ->with('error', 'Số tiền booking đã thay đổi. Vui lòng liên hệ lễ tân để nhận link thanh toán mới.');
        }

        $gatewayExpireMinutes = $vnpayService->expireMinutes();
        $paymentUrl = $vnpayService->createPaymentUrl($booking, $payment, $request, $gatewayExpireMinutes);

        $rawResponse = $payment->raw_response ?? [];
        $rawResponse['last_opened_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
        $rawResponse['last_gateway_expires_at'] = now('Asia/Ho_Chi_Minh')->addMinutes($gatewayExpireMinutes)->toDateTimeString();
        $rawResponse['last_customer_ip'] = $request->ip();
        $payment->update([
            'raw_response' => $rawResponse,
        ]);

        return redirect()->away($paymentUrl);
    }

    public function return(Request $request, VnpayService $vnpayService)
    {
        $params = $request->query();

        if (!$vnpayService->verifySignature($params)) {
            return redirect()
                ->route('home')
                ->with('error', 'Kết quả thanh toán VNPay không hợp lệ hoặc sai chữ ký.');
        }

        $payment = BookingPayment::where('txn_ref', $params['vnp_TxnRef'] ?? null)
            ->first();

        if (!$payment) {
            return redirect()
                ->route('home')
                ->with('error', 'Không tìm thấy giao dịch thanh toán.');
        }

        $result = $this->processPaymentResult($payment, $params);

        $booking = $payment->booking;

        if ($result === 'success') {
            $emailMessage = $this->sendBookingPaymentSuccessEmail($payment);

            return $this->redirectAfterPaymentReturn(
                $payment,
                $booking,
                'success',
                'Thanh toán VNPay thành công. Booking đã được cập nhật thanh toán.' . ($emailMessage ? ' ' . $emailMessage : '')
            );
        }

        if ($result === 'paid_no_room') {
            return $this->redirectAfterPaymentReturn(
                $payment,
                $booking,
                'error',
                'Thanh toán đã được ghi nhận nhưng hệ thống không còn phòng trống để tự động gán. Vui lòng liên hệ lễ tân để được xử lý.'
            );
        }

        return $this->redirectAfterPaymentReturn(
            $payment,
            $booking,
            'error',
            'Thanh toán VNPay không thành công. Có thể tạo lại giao dịch nếu khách vẫn muốn thanh toán online.'
        );
    }

    public function ipn(Request $request, VnpayService $vnpayService)
    {
        $params = $request->query();

        if (!$vnpayService->verifySignature($params)) {
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature',
            ]);
        }

        $payment = BookingPayment::where('txn_ref', $params['vnp_TxnRef'] ?? null)
            ->first();

        if (!$payment) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Order not found',
            ]);
        }

        $result = $this->processPaymentResult($payment, $params);

        if ($result === 'success') {
            $this->sendBookingPaymentSuccessEmail($payment);
        }

        return response()->json([
            'RspCode' => '00',
            'Message' => 'Confirm Success',
        ]);
    }

    private function sendBookingPaymentSuccessEmail(BookingPayment $payment): string
    {
        $payment = $payment->fresh();

        if (!$payment || $payment->status !== 'success') {
            return '';
        }

        $rawResponse = $payment->raw_response ?? [];

        if (!empty($rawResponse['booking_confirm_email_sent_at'])) {
            return 'Email xác nhận booking đã được gửi trước đó.';
        }

        $booking = Booking::with([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
        ])->find($payment->booking_id);

        if (!$booking) {
            return '';
        }

        $email = $booking->customer->email ?? null;

        if (!$email) {
            $rawResponse['booking_confirm_email_skipped_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
            $rawResponse['booking_confirm_email_skip_reason'] = 'missing_customer_email';
            $payment->update(['raw_response' => $rawResponse]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'booking_email_skipped_after_payment',
                'description' => 'Thanh toán VNPay thành công nhưng không gửi email xác nhận booking vì khách chưa có email.',
            ]);

            return 'Khách chưa có email nên chưa gửi email xác nhận booking.';
        }

        try {
            Mail::to($email)->send(new BookingCreatedMail($booking, 'payment_success'));

            $rawResponse['booking_confirm_email_sent_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
            $rawResponse['booking_confirm_email_to'] = $email;
            $payment->update(['raw_response' => $rawResponse]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'booking_email_sent_after_payment',
                'description' => 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến ' . $email . '.',
            ]);

            return 'Đã gửi email xác nhận booking đến ' . $email . '.';
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email xác nhận booking sau thanh toán VNPay.', [
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $rawResponse['booking_confirm_email_failed_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
            $rawResponse['booking_confirm_email_error'] = $e->getMessage();
            $payment->update(['raw_response' => $rawResponse]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'booking_email_failed_after_payment',
                'description' => 'Thanh toán VNPay thành công nhưng chưa gửi được email xác nhận booking đến ' . $email . ': ' . $e->getMessage(),
            ]);

            return 'Thanh toán đã thành công nhưng chưa gửi được email xác nhận: ' . $e->getMessage();
        }
    }

    private function redirectAfterPaymentReturn(BookingPayment $payment, Booking $booking, string $flashKey, string $message)
    {
        if ($this->isAdminPayment($payment)) {
            if ($this->currentUserCanOpenAdminBooking()) {
                return redirect()
                    ->route('admin.bookings.show', $booking)
                    ->with($flashKey, $message);
            }

            return redirect()
                ->route('home')
                ->with($flashKey, $message . ' Cảm ơn quý khách đã thanh toán.');
        }

        if (Auth::check()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with($flashKey, $message);
        }

        return redirect()
            ->route('home')
            ->with($flashKey, $message);
    }

    private function currentUserCanOpenAdminBooking(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role ?? null, ['super_admin', 'manager', 'receptionist', 'receptionist_lead'], true);
    }

    private function processPaymentResult(BookingPayment $payment, array $params): string
    {
        return DB::transaction(function () use ($payment, $params) {
            $payment = BookingPayment::where('id', $payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $booking = Booking::where('id', $payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'success') {
                return $booking->bookingRooms()->exists() ? 'success' : 'paid_no_room';
            }

            // Link/giao dịch đã bị thay thế, hết hạn hoặc đóng do khách chuyển
            // sang thanh toán tại quầy tuyệt đối không được sống lại khi VNPay
            // trả callback muộn.
            if ($payment->status !== 'pending') {
                return 'failed';
            }

            $responseCode = $params['vnp_ResponseCode'] ?? null;
            $transactionStatus = $params['vnp_TransactionStatus'] ?? null;
            $receivedAmount = (int) ($params['vnp_Amount'] ?? 0);
            $expectedAmount = (int) round((float) $payment->amount * 100);
            $amountMatches = $receivedAmount === $expectedAmount;
            $bookingCanReceivePayment = in_array(
                $booking->status,
                ['pending', 'confirmed', 'checked_in', 'inspection_requested'],
                true
            );

            $rawResponse = $payment->raw_response ?? [];
            $requestExpiresAt = !empty($rawResponse['request_expires_at'] ?? null)
                ? \Carbon\Carbon::parse($rawResponse['request_expires_at'], 'Asia/Ho_Chi_Minh')
                : $booking->payment_expires_at;
            $notExpired = !$requestExpiresAt || now('Asia/Ho_Chi_Minh')->lte($requestExpiresAt);

            $isSuccess = $responseCode === '00'
                && $transactionStatus === '00'
                && $amountMatches
                && $bookingCanReceivePayment
                && $notExpired;

            $payment->update([
                'status' => $isSuccess ? 'success' : 'failed',
                'bank_code' => $params['vnp_BankCode'] ?? null,
                'transaction_no' => $params['vnp_TransactionNo'] ?? null,
                'response_code' => $responseCode,
                'transaction_status' => $transactionStatus,
                'paid_at' => $isSuccess ? now('Asia/Ho_Chi_Minh') : null,
                'raw_response' => $params,
            ]);

            if (!$isSuccess) {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'vnpay_payment_failed',
                    'description' => 'Thanh toán VNPay không thành công. Mã phản hồi: ' . ($responseCode ?? 'không rõ') . '.',
                ]);

                $bookingId = $booking->id;

                DB::afterCommit(function () use ($bookingId) {
                    Realtime::booking($bookingId, 'payment_failed');
                });

                return 'failed';
            }

            $payableTotal = $this->calculatePayableTotal($booking);
            $paidTotal = (float) BookingPayment::where('booking_id', $booking->id)
                ->where('status', 'success')
                ->sum('amount');
            if ($payment->payment_type === 'deposit_30' && (float) $booking->deposit_amount <= 0) {
                $depositTarget = app(BookingFinancialService::class)->requiredDeposit($booking);
                $booking->deposit_amount = min((float) $payment->amount, $depositTarget);
            }
            $booking->payment_status = $paidTotal + 0.01 >= $payableTotal ? 'paid' : 'partial';
            if (array_key_exists('overpayment_amount', $booking->getAttributes())) {
                $booking->overpayment_amount = max(0, round($paidTotal - $payableTotal, 0));
            }
            $booking->payment_expires_at = null;

            $roomAssigned = $this->assignRoomAfterSuccessfulPayment($booking);

            if (!$roomAssigned) {
                $booking->status = 'pending';
                $booking->note = trim(
                    ($booking->note ? $booking->note . "\n" : '')
                    . 'Đã ghi nhận thanh toán VNPay nhưng hệ thống không còn phòng trống để tự động gán. Cần lễ tân xử lý thủ công.'
                );
                $booking->save();
                $bookingObserverBroadcastedNoRoomPayment = $booking->wasChanged();

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'vnpay_payment_success_no_room',
                    'description' => 'Thanh toán VNPay thành công '
                        . number_format((float) $payment->amount, 0, ',', '.')
                        . 'đ nhưng không còn phòng trống để tự động gán.',
                ]);

                $bookingId = $booking->id;

                if (!$bookingObserverBroadcastedNoRoomPayment) {
                    DB::afterCommit(function () use ($bookingId) {
                        Realtime::booking($bookingId, 'payment_success_no_room');
                    });
                }

                return 'paid_no_room';
            }

            if ($booking->status === 'pending') {
                $booking->status = 'confirmed';
            }

            $booking->save();
            $bookingObserverBroadcastedPayment = $booking->wasChanged();

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'vnpay_payment_success',
                'description' => 'Thanh toán VNPay thành công: '
                    . number_format((float) $payment->amount, 0, ',', '.')
                    . 'đ. Trạng thái thanh toán: '
                    . $booking->payment_status
                    . ($this->isAdminPayment($payment) ? '. Giao dịch tạo từ admin.' : '. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý.')
                    . '.',
            ]);

            $bookingId = $booking->id;

            if (!$bookingObserverBroadcastedPayment) {
                DB::afterCommit(function () use ($bookingId) {
                    Realtime::booking($bookingId, 'payment_updated');
                });
            }

            return 'success';
        });
    }

    private function ensureCustomerCanPay(Booking $booking): void
    {
        $customer = $booking->customer;

        if (!$customer || $customer->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function calculatePaymentAmount(Booking $booking, string $paymentType, bool $forAdmin = false): float
    {
        $financialService = app(BookingFinancialService::class);
        $payableTotal = $financialService->currentTotal($booking);
        $currentPaid = $financialService->paidTotal($booking);
        $remaining = max(0, $payableTotal - $currentPaid);

        if ($paymentType === 'deposit_30') {
            // Chỉ thu phần còn thiếu để đạt mức cọc theo policy snapshot sau khi
            // booking đổi ngày/hạng. Dịch vụ/phụ thu không làm tăng mức cọc.
            $depositTarget = $financialService->requiredDeposit($booking);

            return max(0, min($depositTarget - $currentPaid, $remaining));
        }

        return $remaining;
    }

    private function calculatePayableTotal(Booking $booking): float
    {
        return app(BookingFinancialService::class)->currentTotal($booking);
    }

    private function hasAvailableRoomForBooking(Booking $booking): bool
    {
        if ($booking->bookingRooms()->exists()) {
            return true;
        }

        return Room::where('room_category_id', $booking->room_category_id)
            ->bookableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->exists();
    }

    private function assignRoomAfterSuccessfulPayment(Booking $booking): bool
    {
        if ($booking->bookingRooms()->exists()) {
            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                $room = $bookingRoom->room;
                if (!$room) {
                    continue;
                }

                app(\App\Services\RoomPreparationService::class)
                    ->flagPriorityIfNeeded($booking, $room, 'xác nhận sau thanh toán VNPay');

                // Thanh toán chỉ xác nhận lịch giữ phòng trong booking_rooms; không đổi
                // room.status trước check-in vì phòng có thể đang phục vụ khách khác.
            }

            return true;
        }

        $room = Room::where('room_category_id', $booking->room_category_id)
            ->bookableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->lockForUpdate()
            ->first();

        if (!$room) {
            return false;
        }

        $booking->loadMissing('roomCategory');

        BookingRoom::create([
            'booking_id' => $booking->id,
            'room_id' => $room->id,
            'adult_count' => $booking->adult_count,
            'child_count' => $booking->child_count ?? 0,
            'price_at_booking' => (float) ($booking->roomCategory->price ?? 0),
            'surcharge' => 0,
            'surcharge_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(\App\Services\RoomPreparationService::class)
            ->flagPriorityIfNeeded($booking, $room, 'gán phòng sau thanh toán VNPAY');

        // Không đổi room.status khi mới gán phòng cho booking chưa check-in.
        // Xung đột lịch được kiểm soát bởi booking_rooms + bookableForPeriod().

        return true;
    }


    private function markPaymentRequestFailed(BookingPayment $payment, string $responseCode, string $description): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $rawResponse = $payment->raw_response ?? [];
        $rawResponse['closed_reason'] = $responseCode;
        $rawResponse['closed_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
        $rawResponse['closed_description'] = $description;

        $payment->update([
            'status' => 'failed',
            'response_code' => $responseCode,
            'transaction_status' => $responseCode,
            'raw_response' => $rawResponse,
        ]);

        BookingLog::create([
            'booking_id' => $payment->booking_id,
            'user_id' => Auth::id(),
            'action' => 'admin_vnpay_request_closed',
            'description' => $description . ' Mã giao dịch: ' . $payment->txn_ref . '.',
        ]);
    }

    private function isAdminPayment(BookingPayment $payment): bool
    {
        return in_array($payment->provider, ['admin_vnpay'], true);
    }

    private function generateTxnRef(Booking $booking): string
    {
        do {
            $txnRef = $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }

    private function depositPercentLabel(Booking $booking): string
    {
        $percent = (float) app(HotelPolicyService::class)->forBooking($booking, 'payment.deposit_percent', 30);
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }

}