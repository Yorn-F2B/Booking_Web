<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notificationTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fb;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
                <tr>
                    <td style="padding:22px 26px;background:#0b1f3a;color:#ffffff;">
                        <div style="font-size:20px;font-weight:700;">MCuong Hotel</div>
                        <div style="margin-top:4px;font-size:13px;opacity:.8;">Thông tin về booking và dịch vụ của bạn</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 26px;">
                        <h1 style="margin:0 0 14px;font-size:22px;line-height:1.35;color:#111827;">{{ $notificationTitle }}</h1>
                        <p style="margin:0;font-size:15px;line-height:1.7;color:#4b5563;">{{ $notificationMessage }}</p>

                        @if($targetUrl)
                            <p style="margin:24px 0 0;">
                                <a href="{{ $targetUrl }}" style="display:inline-block;padding:11px 18px;border-radius:9px;background:#c99a25;color:#ffffff;text-decoration:none;font-weight:700;">Xem chi tiết</a>
                            </p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 26px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                        Đây là email tự động từ MCuong Hotel. Nếu cần hỗ trợ, vui lòng liên hệ lễ tân.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
