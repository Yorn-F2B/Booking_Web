@extends('layouts.admin')

@section('title', 'Chi tiết nhân viên')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('staffs.index') }}">Admin</a> /
                <a href="{{ route('staffs.index') }}">Nhân viên</a> /
                Chi tiết #{{ $staff->id }}
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Chi tiết nhân viên</h2>
                    <p>Mã #{{ $staff->id }} — {{ $staff->full_name }}</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('staffs.edit', $staff->id) }}" class="btn btn-gold">
                        <i class="bx bx-edit me-1"></i>Sửa
                    </a>

                    <a href="{{ route('staffs.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Danh sách
                    </a>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="settings-section text-center">

                        @if ($staff->avatar)
                            <img src="{{ asset('storage/' . $staff->avatar) }}" alt="{{ $staff->full_name }}"
                                class="avatar-lg mb-3">
                        @else
                            <div class="avatar-lg mb-3 d-inline-flex align-items-center justify-content-center bg-light">
                                <i class="bx bx-user fs-1"></i>
                            </div>
                        @endif

                        <h3 class="h5 fw-bold mb-1">{{ $staff->full_name }}</h3>

                        <p class="text-muted small mb-2">
                            {{ $staff->position ?? 'Chưa có chức vụ' }}
                        </p>

                        @if ($staff->work_status === 'working')
                            <span class="badge bg-success">Đang làm việc</span>
                        @elseif ($staff->work_status === 'temporary_leave')
                            <span class="badge bg-warning text-dark">Nghỉ tạm</span>
                        @else
                            <span class="badge bg-secondary">Đã nghỉ</span>
                        @endif

                    </div>
                </div>

                <div class="col-lg-8">

                    <div class="settings-section">
                        <h3 class="settings-section-title">
                            <i class="bx bx-user"></i> Thông tin cá nhân
                        </h3>

                        <dl class="row mb-0 small">
                            <dt class="col-sm-4 text-muted">Họ tên</dt>
                            <dd class="col-sm-8">{{ $staff->full_name }}</dd>

                            <dt class="col-sm-4 text-muted">Email đăng nhập</dt>
                            <dd class="col-sm-8">{{ $staff->user->email ?? 'Chưa có tài khoản' }}</dd>

                            <dt class="col-sm-4 text-muted">SĐT</dt>
                            <dd class="col-sm-8">{{ $staff->phone ?? '-' }}</dd>

                            <dt class="col-sm-4 text-muted">CCCD</dt>
                            <dd class="col-sm-8">{{ $staff->cccd ?? '-' }}</dd>

                            <dt class="col-sm-4 text-muted">Ngày sinh</dt>
                            <dd class="col-sm-8">
                                {{ $staff->birthday ? date('d/m/Y', strtotime($staff->birthday)) : '-' }}
                            </dd>

                            <dt class="col-sm-4 text-muted">Giới tính</dt>
                            <dd class="col-sm-8">
                                @if ($staff->gender === 'male')
                                    Nam
                                @elseif ($staff->gender === 'female')
                                    Nữ
                                @elseif ($staff->gender === 'other')
                                    Khác
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Địa chỉ</dt>
                            <dd class="col-sm-8">{{ $staff->address ?? '-' }}</dd>
                        </dl>
                    </div>

                    <div class="settings-section mt-4">
                        <h3 class="settings-section-title">
                            <i class="bx bx-briefcase"></i> Công việc
                        </h3>

                        <dl class="row mb-0 small">
                            <dt class="col-sm-4 text-muted">Chức vụ</dt>
                            <dd class="col-sm-8">{{ $staff->position ?? '-' }}</dd>

                            <dt class="col-sm-4 text-muted">Lương</dt>
                            <dd class="col-sm-8">
                                {{ number_format($staff->salary ?? 0, 0, ',', '.') }} ₫
                            </dd>

                            <dt class="col-sm-4 text-muted">Ngày vào làm</dt>
                            <dd class="col-sm-8">
                                {{ $staff->hire_date ? date('d/m/Y', strtotime($staff->hire_date)) : '-' }}
                            </dd>

                            <dt class="col-sm-4 text-muted">Trạng thái</dt>
                            <dd class="col-sm-8">
                                @if ($staff->work_status === 'working')
                                    Đang làm việc
                                @elseif ($staff->work_status === 'temporary_leave')
                                    Nghỉ tạm
                                @else
                                    Đã nghỉ
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">User ID</dt>
                            <dd class="col-sm-8">{{ $staff->user_id ?? '-' }}</dd>
                        </dl>
                    </div>

                </div>
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>
@endsection