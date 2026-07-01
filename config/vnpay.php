<?php

return [
    'tmn_code' => env('VNPAY_TMN_CODE'),
    'hash_secret' => env('VNPAY_HASH_SECRET'),

    'payment_url' => env(
        'VNPAY_PAYMENT_URL',
        'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'
    ),

    'return_url' => env('VNPAY_RETURN_URL'),
    'ipn_url' => env('VNPAY_IPN_URL'),

    'version' => '2.1.0',
    'command' => 'pay',
    'curr_code' => 'VND',
    'locale' => 'vn',
    'order_type' => 'other',

    // Phiên trên cổng VNPay sau khi khách bấm link email.
    'expire_minutes' => env('VNPAY_EXPIRE_MINUTES', 30),

    // Link yêu cầu thanh toán gửi qua email. Khách bấm lại trong thời gian này sẽ được tạo phiên VNPay mới.
    'admin_request_expire_minutes' => env('VNPAY_ADMIN_REQUEST_EXPIRE_MINUTES', 1440),
];