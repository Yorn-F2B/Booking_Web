<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;

class VnpayService
{
    public function createPaymentUrl(Booking $booking, BookingPayment $payment, Request $request): string
    {
        $paymentUrl = trim((string) config('vnpay.payment_url'));
        $tmnCode = trim((string) config('vnpay.tmn_code'));
        $hashSecret = trim((string) config('vnpay.hash_secret'));

        if (!$paymentUrl || !$tmnCode || !$hashSecret) {
            throw new \Exception('Thiếu cấu hình VNPay. Vui lòng kiểm tra VNPAY_TMN_CODE, VNPAY_HASH_SECRET, VNPAY_PAYMENT_URL.');
        }

        $returnUrl = config('vnpay.return_url') ?: route('payment.vnpay.return');

        $inputData = [
            'vnp_Version' => config('vnpay.version', '2.1.0'),
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (int) round((float) $payment->amount * 100),
            'vnp_Command' => config('vnpay.command', 'pay'),
            'vnp_CreateDate' => now('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_CurrCode' => config('vnpay.curr_code', 'VND'),
            'vnp_IpAddr' => $request->ip() ?: '127.0.0.1',
            'vnp_Locale' => config('vnpay.locale', 'vn'),
            'vnp_OrderInfo' => 'Thanh toan booking ' . $booking->booking_code,
            'vnp_OrderType' => config('vnpay.order_type', 'other'),
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_TxnRef' => $payment->txn_ref,
            'vnp_ExpireDate' => now('Asia/Ho_Chi_Minh')->addMinutes(15)->format('YmdHis'),
        ];

        ksort($inputData);

        $hashData = '';
        $query = '';
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }

            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        $secureHash = hash_hmac('sha512', $hashData, $hashSecret);

        return $paymentUrl . '?' . $query . 'vnp_SecureHash=' . $secureHash;
    }

    public function verifySignature(array $params): bool
    {
        $hashSecret = trim((string) config('vnpay.hash_secret'));

        if (!$hashSecret || empty($params['vnp_SecureHash'])) {
            return false;
        }

        $secureHash = $params['vnp_SecureHash'];

        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);

        ksort($params);

        $hashData = '';
        $i = 0;

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
        }

        $calculatedHash = hash_hmac('sha512', $hashData, $hashSecret);

        return hash_equals($calculatedHash, $secureHash);
    }
}