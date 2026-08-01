@extends('layouts.admin')

@section('title', 'Danh sách dịch vụ')

@section('content')
    @php
        $typeLabels = $typeLabels ?? \App\Models\Service::typeLabels();
        $groupLabels = $groupLabels ?? \App\Models\Service::groupLabels();
        $billingRuleLabels = $billingRuleLabels ?? \App\Models\Service::billingRuleLabels();

        $typeBadgeClasses = [
            'service' => 'bg-primary',
            'minibar' => 'bg-warning text-dark',
            'minibar_order' => 'bg-info text-dark',
            'damage_fee' => 'bg-danger',
            'occupancy_fee' => 'bg-info text-dark',
            'policy_violation_fee' => 'bg-dark',
        ];

        $groupBadgeClasses = [
            'general' => 'bg-secondary',
            'food_drink' => 'bg-success',
            'vehicle' => 'bg-dark',
            'laundry' => 'bg-info text-dark',
            'transport' => 'bg-primary',
            'wellness' => 'bg-danger',
            'room_support' => 'bg-warning text-dark',
            'other' => 'bg-secondary',
        ];
    @endphp

    <style>
        .service-filter-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(3, 1fr) auto;
            gap: 10px;
        }

        .service-muted {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 991px) {
            .service-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Dịch vụ
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Danh sách dịch vụ</h2>
                    <p>Quản lý dịch vụ, minibar, phí phát sinh và nhóm xe cộ/gửi xe</p>
                </div>

                <a href="{{ route('services.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm dịch vụ
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="settings-section mb-3">
                <form method="GET" action="{{ route('services.index') }}" class="service-filter-grid">
                    <input type="text" name="keyword" class="form-control"
                        value="{{ request('keyword') }}" placeholder="Tìm tên, mô tả, đơn vị...">

                    <select name="type" class="form-select">
                        <option value="">Tất cả loại</option>
                        @foreach ($typeLabels as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}" {{ request('type') == $typeValue ? 'selected' : '' }}>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </select>

                    <select name="service_group" class="form-select">
                        <option value="">Tất cả nhóm</option>
                        @foreach ($groupLabels as $groupValue => $groupLabel)
                            <option value="{{ $groupValue }}" {{ request('service_group') == $groupValue ? 'selected' : '' }}>
                                {{ $groupLabel }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tạm ẩn</option>
                    </select>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Lọc</button>
                        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên dịch vụ</th>
                                <th>Loại</th>
                                <th>Nhóm</th>
                                <th>Giá</th>
                                <th>Đơn vị</th>
                                <th>Cách tính</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td>{{ $service->id }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $service->name }}</div>
                                        @if ($service->description)
                                            <div class="service-muted">
                                                {{ \Illuminate\Support\Str::limit($service->description, 80) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $typeBadgeClasses[$service->type] ?? 'bg-secondary' }}">
                                            {{ $service->type_label }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge {{ $groupBadgeClasses[$service->service_group] ?? 'bg-secondary' }}">
                                            {{ $service->group_label }}
                                        </span>
                                    </td>

                                    <td>{{ number_format((float) $service->price, 0, ',', '.') }}đ</td>

                                    <td>{{ $service->unit }}</td>

                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $service->billing_rule_label }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($service->status == 'active')
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-secondary">Tạm ẩn</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('services.show', $service->id) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="{{ route('services.edit', $service->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="{{ route('services.destroy', $service->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa dịch vụ này không?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        Chưa có dịch vụ nào
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                <div class="mt-3">
                    {{ $services->links() }}
                </div>
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
@endsection
