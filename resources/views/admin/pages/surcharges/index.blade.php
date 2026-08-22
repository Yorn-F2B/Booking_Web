@extends('layouts.admin')

@section('title', 'Phụ thu / phí phát sinh')

@section('content')
@php
    $badgeClasses = [
        'damage_fee' => 'bg-danger',
        'occupancy_fee' => 'bg-info text-dark',
        'extra_guest_fee' => 'bg-info text-dark',
        'early_checkin_fee' => 'bg-warning text-dark',
        'late_checkout_fee' => 'bg-warning text-dark',
        'extension_fee' => 'bg-primary',
        'policy_violation_fee' => 'bg-dark',
        'manual_fee' => 'bg-secondary',
    ];
@endphp
<style>
    .surcharge-filter-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr auto;gap:10px}
    .surcharge-muted{color:#64748b;font-size:13px}
    @media(max-width:991px){.surcharge-filter-grid{grid-template-columns:1fr}}
</style>
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Phụ thu / phí phát sinh</p>
        <div class="admin-page-head">
            <div>
                <h2>Phụ thu / phí phát sinh</h2>
                <p>Quản lý hư hại, quá số người, check-in sớm, check-out muộn, gia hạn, vi phạm chính sách và phí thủ công.</p>
            </div>
            <a href="{{ route('surcharges.create') }}" class="btn btn-gold"><i class="bx bx-plus me-1"></i>Thêm khoản phí</a>
        </div>

        <div class="settings-section mb-3">
            <form method="GET" action="{{ route('surcharges.index') }}" class="surcharge-filter-grid">
                <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="Tìm tên, mô tả, đơn vị...">
                <select name="type" class="form-select">
                    <option value="">Tất cả loại phí</option>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Ngừng hoạt động</option>
                </select>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Lọc</button>
                    <a href="{{ route('surcharges.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Tên khoản phí</th><th>Loại</th><th>Mức mặc định</th><th>Đơn vị</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                    <tbody>
                    @forelse($surcharges as $surcharge)
                        <tr>
                            <td>{{ $surcharge->id }}</td>
                            <td><div class="fw-semibold">{{ $surcharge->name }}</div>@if($surcharge->description)<div class="surcharge-muted">{{ \Illuminate\Support\Str::limit($surcharge->description, 90) }}</div>@endif</td>
                            <td><span class="badge {{ $badgeClasses[$surcharge->type] ?? 'bg-secondary' }}">{{ $surcharge->type_label }}</span></td>
                            <td>{{ number_format((float)$surcharge->price, 0, ',', '.') }}đ</td>
                            <td>{{ $surcharge->unit }}</td>
                            <td>@if($surcharge->status === 'active')<span class="badge bg-success">Hoạt động</span>@else<span class="badge bg-secondary">Ngừng hoạt động</span>@endif</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('surcharges.show', $surcharge) }}" class="btn btn-sm btn-outline-secondary">Xem</a>
                                <a href="{{ route('surcharges.edit', $surcharge) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form method="POST" action="{{ route('surcharges.destroy', $surcharge) }}" class="d-inline" onsubmit="return confirm('Xóa khoản phí này? Nếu đã có lịch sử, hệ thống chỉ ngừng hoạt động để giữ dữ liệu cũ.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Chưa có phụ thu / phí phát sinh phù hợp.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $surcharges->links() }}</div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>
@endsection
