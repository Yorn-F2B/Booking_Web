<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Thuế suất VAT (%)
    |--------------------------------------------------------------------------
    |
    | Giá hiển thị trên hệ thống đã bao gồm VAT. Khi đặt giá trị > 0, phần
    | chi tiết thanh toán sẽ tách riêng dòng "Trong đó thuế VAT" (tính ngược
    | từ tổng đã gồm thuế). Để 0 nếu chỉ muốn hiển thị ghi chú "đã gồm VAT".
    |
    */
    'vat_rate' => (float) env('BOOKING_VAT_RATE', 0),
];
