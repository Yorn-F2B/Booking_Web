@php
    $customerName = $booking->booked_customer_name !== ''
        ? $booking->booked_customer_name
        : 'Quý khách';

    $checkInText = $booking->check_in_at
        ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
        : ($booking->check_in_date ? date('d/m/Y', strtotime($booking->check_in_date)) : '---');

    $checkOutText = $booking->check_out_at
        ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
        : ($booking->check_out_date ? date('d/m/Y', strtotime($booking->check_out_date)) : '---');

    $statusLabels = [
        'pending' => 'Chờ xác nhận / chờ thanh toán',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đã nhận phòng',
        'inspection_requested' => 'Chờ kiểm tra phòng',
        'checked_out' => 'Đã trả phòng',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
        'canceled' => 'Đã hủy',
        'no_show' => 'No-show',
    ];

    $paymentLabels = [
        'unpaid' => 'Chưa thanh toán',
        'partial' => 'Đã cọc / thanh toán một phần',
        'paid' => 'Đã thanh toán đủ',
        'refunded' => 'Đã hoàn tiền',
    ];

    $roomNumbers = $booking->bookingRooms
        ->pluck('room.room_number')
        ->filter()
        ->values()
        ->implode(', ');

    $totalAmount = (float) ($booking->estimated_total ?? 0);
    $paidAmount = (float) $booking->payments->where('status', 'success')->sum('amount');
    $remainingAmount = max(0, $totalAmount - $paidAmount);

    $latestPendingPayment = null;

    if ($booking->status === 'pending' && $booking->payment_status === 'unpaid') {
        $latestPendingPayment = $booking->payments
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();
    }

    $latestSuccessPayment = $booking->payments
        ->where('status', 'success')
        ->sortByDesc('paid_at')
        ->first();

    $bookingTypeText = $booking->booking_type === 'hourly' ? 'Theo giờ' : 'Qua đêm';
    $bookingModeText = $booking->booking_mode === 'walk_in' ? 'Ở ngay' : 'Đặt trước';
    $isPaymentSuccessMail = ($source ?? '') === 'payment_success';
    $emailTitle = $isPaymentSuccessMail ? 'Xác nhận thanh toán và đặt phòng' : 'Xác nhận đặt phòng';
@endphp

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $emailTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:28px 12px;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#0a1931;color:#ffffff;padding:22px 24px;border-bottom:3px solid #d4af37;">
                            <div style="font-size:13px;color:#e4c765;font-weight:700;">MCuong Hotel</div>
                            <div style="font-size:23px;font-weight:800;margin-top:6px;">{{ $emailTitle }}</div>
                            <div style="font-size:13px;color:#c5cfdd;margin-top:6px;">Mã booking: <strong>{{ $booking->booking_code }}</strong></div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">Xin chào <strong>{{ $customerName }}</strong>,</p>

                            @if ($isPaymentSuccessMail)
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                                    MCuong Hotel đã ghi nhận thanh toán của quý khách. Booking đã được cập nhật trạng thái thanh toán và gán phòng nếu còn phòng phù hợp.
                                </p>
                            @else
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                                    MCuong Hotel đã ghi nhận thông tin đặt phòng của quý khách. Vui lòng kiểm tra lại thông tin bên dưới.
                                </p>
                            @endif

                            <div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:18px;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="padding:12px 14px;background:#f8fafc;color:#64748b;font-size:13px;">Mã booking</td>
                                        <td style="padding:12px 14px;background:#f8fafc;text-align:right;font-weight:700;">{{ $booking->booking_code }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Trạng thái booking</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $statusLabels[$booking->status] ?? $booking->status }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Trạng thái thanh toán</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $paymentLabels[$booking->payment_status] ?? $booking->payment_status }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Loại đặt phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $bookingModeText }} · {{ $bookingTypeText }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Hạng phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $booking->roomCategory->name ?? '---' }}</td>
                                    </tr>
                                    @if ($roomNumbers)
                                        <tr>
                                            <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Phòng dự kiến</td>
                                            <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $roomNumbers }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Nhận phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $checkInText }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Trả phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $checkOutText }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Số khách / số phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $booking->adult_count }} NL / {{ $booking->child_count }} TE · {{ $booking->room_quantity }} phòng</td>
                                    </tr>
                                </table>
                            </div>

                            @if ($booking->serviceItems->count() > 0)
                                <div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:18px;">
                                    <div style="padding:12px 14px;background:#f8fafc;font-weight:700;">Dịch vụ / ưu đãi đi kèm</div>
                                    @foreach ($booking->serviceItems as $item)
                                        <div style="padding:11px 14px;border-top:1px solid #eef2f7;font-size:14px;line-height:1.5;">
                                            <strong>{{ $item->name }}</strong>
                                            <span style="color:#64748b;">× {{ $item->quantity }}</span>
                                            <span style="float:right;font-weight:700;">{{ number_format((float) $item->total, 0, ',', '.') }}đ</span>
                                            @if ($item->note)
                                                <div style="color:#64748b;font-size:12px;margin-top:4px;">{{ $item->note }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:18px;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;">Tổng tiền sau ưu đãi</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:800;">{{ number_format($totalAmount, 0, ',', '.') }}đ</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Đã thanh toán / đã cọc</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:800;border-top:1px solid #eef2f7;">{{ number_format($paidAmount, 0, ',', '.') }}đ</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#b91c1c;font-size:14px;border-top:1px solid #eef2f7;font-weight:700;">Còn lại cần thanh toán</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:900;border-top:1px solid #eef2f7;color:#b91c1c;font-size:18px;">{{ number_format($remainingAmount, 0, ',', '.') }}đ</td>
                                    </tr>
                                </table>
                            </div>

                            @if ($paidAmount > 0)
                                <div style="background:#ecfdf5;border:1px solid #bbf7d0;border-radius:14px;padding:14px 16px;margin-bottom:18px;color:#14532d;">
                                    <div style="font-weight:800;margin-bottom:4px;">Đã ghi nhận thanh toán</div>
                                    <div style="font-size:14px;line-height:1.6;">
                                        Số tiền đã thanh toán/cọc: <strong>{{ number_format($paidAmount, 0, ',', '.') }}đ</strong><br>
                                        Còn lại cần thanh toán: <strong>{{ number_format($remainingAmount, 0, ',', '.') }}đ</strong>
                                        @if ($latestSuccessPayment)
                                            <br>Mã giao dịch VNPay: <strong>{{ $latestSuccessPayment->txn_ref }}</strong>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($latestPendingPayment)
                                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:14px 16px;margin-bottom:18px;color:#7c2d12;">
                                    <div style="font-weight:800;margin-bottom:4px;">Đang có yêu cầu thanh toán chờ xử lý</div>
                                    <div style="font-size:14px;line-height:1.6;">
                                        Số tiền cần thanh toán: <strong>{{ number_format((float) $latestPendingPayment->amount, 0, ',', '.') }}đ</strong><br>
                                        Mã giao dịch: <strong>{{ $latestPendingPayment->txn_ref }}</strong><br>
                                        Booking được xác nhận sau khi thanh toán thành công.
                                    </div>
                                </div>
                            @elseif ($remainingAmount > 0)
                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:14px 16px;margin-bottom:18px;color:#1e3a8a;">
                                    <div style="font-weight:800;margin-bottom:4px;">Thông tin thanh toán</div>
                                    <div style="font-size:14px;line-height:1.6;">
                                        Quý khách vui lòng thanh toán phần còn lại theo hướng dẫn của lễ tân khi nhận/trả phòng.
                                    </div>
                                </div>
                            @endif

                            @if ($booking->customer && $booking->customer->user_id === null && $booking->booked_customer_email)
                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:14px;padding:16px;margin-bottom:18px;color:#1e3a8a;">
                                    <div style="font-weight:800;margin-bottom:7px;">Quản lý booking vãng lai</div>
                                    <div style="font-size:14px;line-height:1.6;margin-bottom:12px;">
                                        Quý khách có thể dùng mã booking và email này để tra cứu hoặc tự hủy booking. Khi hủy, toàn bộ tiền đã thanh toán sẽ không được hoàn lại và không được bảo lưu.
                                    </div>
                                    <a href="{{ route('guest-bookings.index', ['booking_code' => $booking->booking_code, 'email' => $booking->booked_customer_email]) }}" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;font-weight:800;padding:11px 16px;border-radius:10px;">Tra cứu / hủy booking</a>
                                </div>
                            @endif

                            <div style="font-size:14px;line-height:1.7;color:#475569;">
                                Khi nhận phòng, quý khách vui lòng mang theo CCCD/hộ chiếu để đối chiếu thông tin lưu trú.
                                Nếu thông tin có sai lệch, vui lòng liên hệ MCuong Hotel để được hỗ trợ.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;color:#64748b;font-size:12px;line-height:1.6;">
                            MCuong Hotel
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
