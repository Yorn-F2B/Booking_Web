<!doctype html>
<html lang="vi">
<body style="margin:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1e293b">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
    <tr><td style="padding:22px 26px;background:#0a1931;color:#fff;border-bottom:3px solid #d4af37">
        <div style="color:#e4c765;font-size:13px;font-weight:700">MCuong Hotel</div>
        <h1 style="margin:6px 0 0;font-size:22px">Xác thực booking</h1>
    </td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 18px;line-height:1.6">Mã xác thực cho booking <strong>{{ $booking->booking_code }}</strong>:</p>
        <div style="font-size:32px;letter-spacing:9px;font-weight:800;text-align:center;padding:18px;background:#faf6e9;border:1px solid #eadb9d;border-radius:8px;color:#0a1931">{{ $otp }}</div>
        <p style="margin:18px 0 0;color:#66736f;font-size:13px;line-height:1.6">Mã có hiệu lực trong {{ $expiresInMinutes }} phút. Không chia sẻ mã này với người khác.</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
