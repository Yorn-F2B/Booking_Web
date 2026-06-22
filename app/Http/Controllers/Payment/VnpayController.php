<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VnpayController extends Controller
{
    public function create(Request $request, Booking $booking, VnpayService $vnpayService)
    {
        $this->ensureCustomerCanPay($booking);

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Booking này đã thanh toán đủ.');
        }

        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Booking này không còn ở trạng thái có thể thanh toán online.');
        }

        if (!$this->hasAvailableRoomForBooking($booking)) {
            return back()->with('error', 'Hạng phòng này hiện đã hết phòng trống trong thời gian bạn chọn. Vui lòng hủy đơn hiện tại và chọn ngày khác hoặc hạng phòng khác.');
        }

        $data = $request->validate([
            'payment_type' => 'required|in:deposit_30,full_100',
        ]);

        $amount = $this->calculatePaymentAmount($booking, $data['payment_type']);

        if ($amount <= 0) {
            return back()->with('error', 'Số tiền cần thanh toán không hợp lệ.');
        }

        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'vnpay',
            'txn_ref' => $this->generateTxnRef($booking),
            'amount' => $amount,
            'status' => 'pending',
            'payment_type' => $data['payment_type'],
        ]);

        return redirect()->away(
            $vnpayService->createPaymentUrl($booking, $payment, $request)
        );
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
            return redirect()
                ->route('bookings.show', $booking)
                ->with('success', 'Thanh toán VNPay thành công. Booking đã được xác nhận và gán phòng.');
        }

        if ($result === 'paid_no_room') {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Thanh toán đã được ghi nhận nhưng hệ thống không còn phòng trống để tự động gán. Vui lòng liên hệ lễ tân để được xử lý.');
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('error', 'Thanh toán VNPay không thành công. Bạn có thể thanh toán lại nếu vẫn muốn đặt phòng.');
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

        $this->processPaymentResult($payment, $params);

        return response()->json([
            'RspCode' => '00',
            'Message' => 'Confirm Success',
        ]);
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

            $responseCode = $params['vnp_ResponseCode'] ?? null;
            $transactionStatus = $params['vnp_TransactionStatus'] ?? null;

            $isSuccess = $responseCode === '00' && $transactionStatus === '00';

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

                return 'failed';
            }

            $newDepositAmount = min(
                (float) $booking->estimated_total,
                (float) $booking->deposit_amount + (float) $payment->amount
            );

            $booking->deposit_amount = $newDepositAmount;
            $booking->payment_status = $newDepositAmount >= (float) $booking->estimated_total
                ? 'paid'
                : 'partial';

            $roomAssigned = $this->assignRoomAfterSuccessfulPayment($booking);

            if (!$roomAssigned) {
                $booking->status = 'pending';
                $booking->note = trim(
                    ($booking->note ? $booking->note . "\n" : '')
                    . 'Đã ghi nhận thanh toán VNPay nhưng hệ thống không còn phòng trống để tự động gán. Cần lễ tân xử lý thủ công.'
                );
                $booking->save();

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'vnpay_payment_success_no_room',
                    'description' => 'Thanh toán VNPay thành công '
                        . number_format((float) $payment->amount, 0, ',', '.')
                        . 'đ nhưng không còn phòng trống để tự động gán.',
                ]);

                return 'paid_no_room';
            }

            $booking->status = 'confirmed';
            $booking->save();

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'vnpay_payment_success',
                'description' => 'Thanh toán VNPay thành công: '
                    . number_format((float) $payment->amount, 0, ',', '.')
                    . 'đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: '
                    . $booking->payment_status
                    . '.',
            ]);

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

    private function calculatePaymentAmount(Booking $booking, string $paymentType): float
    {
        $estimatedTotal = (float) $booking->estimated_total;
        $currentPaid = (float) $booking->deposit_amount;
        $remaining = max(0, $estimatedTotal - $currentPaid);

        if ($paymentType === 'deposit_30') {
            $depositTarget = round($estimatedTotal * 0.3, 0);

            return max(0, min($depositTarget - $currentPaid, $remaining));
        }

        return $remaining;
    }

    private function hasAvailableRoomForBooking(Booking $booking): bool
    {
        if ($booking->bookingRooms()->exists()) {
            return true;
        }

        return Room::where('room_category_id', $booking->room_category_id)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->exists();
    }

    private function assignRoomAfterSuccessfulPayment(Booking $booking): bool
    {
        if ($booking->bookingRooms()->exists()) {
            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room && $bookingRoom->room->status === 'available') {
                    $bookingRoom->room->update([
                        'status' => 'reserved',
                    ]);
                }
            }

            return true;
        }

        $room = Room::where('room_category_id', $booking->room_category_id)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->orderBy('floor_number')
            ->orderBy('room_number')
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

        if ($room->status === 'available') {
            $room->update([
                'status' => 'reserved',
            ]);
        }

        return true;
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
}