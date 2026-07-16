@php
    $customerName = $invoice->resolved_customer_name;
    $customerName = trim((string) $customerName) !== '' ? $customerName : 'Quý khách';

    $viewInvoiceUrl = route('bookings.invoice', $booking);
    $printInvoiceUrl = route('bookings.invoice.print', $booking);
    $resolvedCheckInDate = $invoice->resolved_check_in_date;
    $resolvedCheckOutDate = $invoice->resolved_check_out_date;
    $resolvedActualCheckIn = $invoice->resolved_actual_check_in;
    $resolvedActualCheckOut = $invoice->resolved_actual_check_out;
@endphp

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hóa đơn booking</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#0f172a;color:#ffffff;padding:22px 24px;">
                            <div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#d4af37;font-weight:700;">MCuong Hotel</div>
                            <div style="font-size:24px;font-weight:800;margin-top:6px;">Hóa đơn thanh toán của quý khách</div>
                            <div style="font-size:14px;color:#cbd5e1;margin-top:6px;">Mã hóa đơn: <strong>{{ $invoice->invoice_code }}</strong></div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">Xin chào <strong>{{ $customerName }}</strong>,</p>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                                Booking của quý khách đã hoàn tất check-out. MCuong Hotel gửi kèm thông tin hóa đơn để quý khách tiện đối chiếu và lưu trữ.
                            </p>

                            <div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:18px;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="padding:12px 14px;background:#f8fafc;color:#64748b;font-size:13px;">Mã booking</td>
                                        <td style="padding:12px 14px;background:#f8fafc;text-align:right;font-weight:700;">{{ $booking->booking_code }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Khách hàng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $invoice->resolved_customer_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $invoice->resolved_room_numbers }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Ngày xuất hóa đơn</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $invoice->issued_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Nhận phòng dự kiến</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $resolvedCheckInDate ? \Carbon\Carbon::parse($resolvedCheckInDate)->format('d/m/Y') : '---' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Trả phòng dự kiến</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $resolvedCheckOutDate ? \Carbon\Carbon::parse($resolvedCheckOutDate)->format('d/m/Y') : '---' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Check-in thực tế</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $resolvedActualCheckIn ? \Carbon\Carbon::parse($resolvedActualCheckIn)->format('d/m/Y H:i') : '---' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Check-out thực tế</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ $resolvedActualCheckOut ? \Carbon\Carbon::parse($resolvedActualCheckOut)->format('d/m/Y H:i') : '---' }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:18px;">
                                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;">Tiền phòng</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:700;">{{ number_format($invoice->resolved_room_charge, 0, ',', '.') }}đ</td>
                                    </tr>
                                    @if ($invoice->resolved_service_charge > 0)
                                        <tr>
                                            <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Dịch vụ</td>
                                            <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ number_format($invoice->resolved_service_charge, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif
                                    @if ($invoice->resolved_inspection_charge > 0)
                                        <tr>
                                            <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Minibar / hư hại đã duyệt</td>
                                            <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;">{{ number_format($invoice->resolved_inspection_charge, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif
                                    @if ($invoice->resolved_discount_amount > 0)
                                        <tr>
                                            <td style="padding:12px 14px;color:#16a34a;font-size:13px;border-top:1px solid #eef2f7;">Khuyến mãi</td>
                                            <td style="padding:12px 14px;text-align:right;font-weight:700;border-top:1px solid #eef2f7;color:#16a34a;">-{{ number_format($invoice->resolved_discount_amount, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td style="padding:12px 14px;color:#111827;font-size:14px;border-top:1px solid #eef2f7;font-weight:800;">Tổng cuối</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:900;border-top:1px solid #eef2f7;font-size:18px;">{{ number_format($invoice->resolved_final_total, 0, ',', '.') }}đ</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 14px;color:#64748b;font-size:13px;border-top:1px solid #eef2f7;">Đã thanh toán</td>
                                        <td style="padding:12px 14px;text-align:right;font-weight:800;border-top:1px solid #eef2f7;">{{ number_format($invoice->resolved_total_paid, 0, ',', '.') }}đ</td>
                                    </tr>
                                    @if ($invoice->resolved_remaining_amount > 0)
                                        <tr>
                                            <td style="padding:12px 14px;color:#b91c1c;font-size:14px;border-top:1px solid #eef2f7;font-weight:700;">Còn thiếu</td>
                                            <td style="padding:12px 14px;text-align:right;font-weight:900;border-top:1px solid #eef2f7;color:#b91c1c;">{{ number_format($invoice->resolved_remaining_amount, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif
                                    @if ($invoice->resolved_overpayment_amount > 0)
                                        <tr>
                                            <td style="padding:12px 14px;color:#166534;font-size:14px;border-top:1px solid #eef2f7;font-weight:700;">Trả dư</td>
                                            <td style="padding:12px 14px;text-align:right;font-weight:900;border-top:1px solid #eef2f7;color:#166534;">{{ number_format($invoice->resolved_overpayment_amount, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div style="margin-bottom:18px;">
                                <a href="{{ $viewInvoiceUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;margin-right:8px;">
                                    Xem hóa đơn trên website
                                </a>
                                <a href="{{ $printInvoiceUrl }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:700;">
                                    In hóa đơn
                                </a>
                            </div>

                            <div style="font-size:14px;line-height:1.7;color:#475569;">
                                Nếu quý khách cần hỗ trợ thêm về hóa đơn hoặc lưu trú, vui lòng liên hệ MCuong Hotel để được hỗ trợ nhanh nhất.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;color:#64748b;font-size:12px;line-height:1.6;">
                            Email này được gửi tự động từ hệ thống MCuong Hotel. Quý khách có thể đăng nhập vào website để xem lại hóa đơn bất cứ lúc nào.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
