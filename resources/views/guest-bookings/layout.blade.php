<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tra cứu booking') - MCuong Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/project-date-picker.css') }}?v={{ filemtime(public_path('assets/css/project-date-picker.css')) }}">
    <style>
        *{box-sizing:border-box} body{margin:0;background:#f4f7fb;color:#10213a;font-family:Arial,Helvetica,sans-serif}.wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px}.card{width:min(720px,100%);background:#fff;border:1px solid #dfe7f2;border-radius:22px;box-shadow:0 18px 55px rgba(15,35,65,.12);overflow:hidden}.head{background:#0b1f3a;color:#fff;padding:24px 28px}.brand{color:#f1c75b;font-size:13px;font-weight:800;letter-spacing:.09em;text-transform:uppercase}.head h1{margin:7px 0 4px;font-size:27px}.head p{margin:0;color:#c9d5e6;line-height:1.55}.body{padding:26px 28px}.field{margin-bottom:17px}.field label{display:block;font-weight:750;margin-bottom:7px}.input,.select,.textarea{width:100%;border:1px solid #cbd7e6;border-radius:12px;padding:13px 14px;font-size:16px;outline:none;background:#fff}.input:focus,.textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}.textarea{min-height:105px;resize:vertical}.btn{display:inline-flex;justify-content:center;align-items:center;border:0;border-radius:12px;padding:13px 18px;font-weight:800;font-size:15px;cursor:pointer;text-decoration:none}.btn-primary{background:#1d5fe9;color:#fff}.btn-danger{background:#c62828;color:#fff}.btn-light{background:#edf2f8;color:#15304f}.actions{display:flex;gap:10px;flex-wrap:wrap}.alert{padding:13px 15px;border-radius:12px;margin-bottom:18px;line-height:1.5}.alert-error{background:#fff1f1;border:1px solid #ffc9c9;color:#8c1d1d}.alert-success{background:#edfff4;border:1px solid #b7efcd;color:#17623a}.muted{color:#64748b;line-height:1.6}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.info{border:1px solid #e3eaf3;border-radius:14px;padding:15px}.info small{display:block;color:#718096;margin-bottom:5px}.info strong{font-size:16px}.warning{background:#fff8e8;border:1px solid #f4d48a;color:#704d00;padding:15px;border-radius:14px;line-height:1.6}.checkbox{display:flex;gap:10px;align-items:flex-start;margin:16px 0}.checkbox input{margin-top:4px}.footer{padding:0 28px 24px;color:#7a8798;font-size:13px}@media(max-width:640px){.wrap{padding:12px}.head,.body{padding:21px}.grid{grid-template-columns:1fr}.actions .btn{width:100%}}
    </style>
</head>
<body>
@include('partials.flash-toasts')
<div class="wrap"><div class="card">
    <div class="head"><div class="brand">MCuong Hotel</div><h1>@yield('heading')</h1><p>@yield('subheading')</p></div>
    <div class="body">
        @yield('content')
    </div>
    <div class="footer">Dữ liệu chỉ được hiển thị sau khi xác thực email đã dùng khi đặt phòng.</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
<script src="{{ asset('assets/js/project-date-picker.js') }}?v={{ filemtime(public_path('assets/js/project-date-picker.js')) }}"></script>
</body>
</html>
