@extends('layouts.admin')

@section('title', 'Mã ưu đãi')

@section('content')
    @php
        $statusLabels = [
            'active' => 'Hoạt động',
            'inactive' => 'Tạm ẩn',
        ];

        $statusClasses = [
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
        ];
    @endphp

    <style>
        .promotion-admin-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
        }

        .promotion-filter-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(4, 1fr) auto;
            gap: 10px;
        }

        .promotion-code-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            background: #111827;
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.04em;
            font-size: 12px;
        }

        .promotion-condition-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .promotion-condition-pill {
            display: inline-flex;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 4px 8px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
        }

        .promotion-muted {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 991px) {
            .promotion-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Mã ưu đãi
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Mã ưu đãi</h2>
                    <p>Quản lý mã thường, mã sự kiện, mã hỗ trợ và mã điều kiện</p>
                </div>

                <a href="{{ route('admin.promotions.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm mã
                </a>
            </div>

<div class="promotion-admin-card mb-3">
                <form action="{{ route('admin.promotions.index') }}" method="GET">
                    <div class="promotion-filter-grid">
                        <input type="text" name="keyword" class="form-control"
                            value="{{ request('keyword') }}"
                            placeholder="Tìm mã, tên mã hoặc mô tả">

                        <select name="promotion_type" class="form-select">
                            <option value="">Tất cả loại mã</option>
                            @foreach ($promotionTypes as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected(request('promotion_type') == $typeValue)>
                                    {{ $typeLabel }}
                                </option>
                            @endforeach
                        </select>

                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" @selected(request('status') == 'active')>Hoạt động</option>
                            <option value="inactive" @selected(request('status') == 'inactive')>Tạm ẩn</option>
                        </select>

                        <select name="visibility" class="form-select">
                            <option value="">Tất cả quyền dùng</option>
                            <option value="user" @selected(request('visibility') == 'user')>User tự dùng</option>
                            <option value="admin" @selected(request('visibility') == 'admin')>Admin áp dụng</option>
                            <option value="support" @selected(request('visibility') == 'support')>Mã hỗ trợ</option>
                            <option value="hidden_user" @selected(request('visibility') == 'hidden_user')>Không hiện user</option>
                        </select>

                        <select name="valid_state" class="form-select">
                            <option value="">Tất cả hiệu lực</option>
                            <option value="active_now" @selected(request('valid_state') == 'active_now')>Đang hiệu lực</option>
                            <option value="upcoming" @selected(request('valid_state') == 'upcoming')>Sắp diễn ra</option>
                            <option value="expired" @selected(request('valid_state') == 'expired')>Đã hết hạn</option>
                        </select>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary text-nowrap">
                                Lọc
                            </button>
                            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary text-nowrap">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="promotion-admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã</th>
                                <th>Loại / giảm</th>
                                <th>Điều kiện chính</th>
                                <th>Hiệu lực</th>
                                <th>Lượt dùng</th>
                                <th>Quyền dùng</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($promotions as $promotion)
                                @php
                                    $conditionPills = [];

                                    if ((float) $promotion->min_booking_amount > 0) {
                                        $conditionPills[] = 'Đơn từ ' . number_format((float) $promotion->min_booking_amount, 0, ',', '.') . 'đ';
                                    }

                                    if ((int) $promotion->min_nights > 0) {
                                        $conditionPills[] = 'Từ ' . (int) $promotion->min_nights . ' đêm';
                                    }

                                    if ((int) $promotion->min_rooms > 0) {
                                        $conditionPills[] = 'Từ ' . (int) $promotion->min_rooms . ' phòng';
                                    }

                                    if ((int) $promotion->min_completed_bookings > 0) {
                                        $conditionPills[] = 'Khách đã hoàn thành ' . (int) $promotion->min_completed_bookings . ' đơn';
                                    }

                                    if ((float) $promotion->min_total_spent > 0) {
                                        $conditionPills[] = 'Đã chi tiêu từ ' . number_format((float) $promotion->min_total_spent, 0, ',', '.') . 'đ';
                                    }

                                    $usageText = $promotion->usage_limit
                                        ? ((int) $promotion->used_count . '/' . (int) $promotion->usage_limit)
                                        : ((int) $promotion->used_count . ' lượt');

                                    $validText = 'Không giới hạn';
                                    if ($promotion->valid_from || $promotion->valid_to) {
                                        $validText = ($promotion->valid_from ? $promotion->valid_from->format('d/m/Y H:i') : '---')
                                            . ' → '
                                            . ($promotion->valid_to ? $promotion->valid_to->format('d/m/Y H:i') : '---');
                                    }
                                @endphp

                                <tr>
                                    <td>
                                        <div class="promotion-code-badge">{{ $promotion->code }}</div>
                                        <div class="promotion-muted mt-1">{{ $promotion->name }}</div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $promotion->type_label }}</div>
                                        <div class="promotion-muted">Giảm tiền: {{ $promotion->discount_label }}</div>
                                        @if ($promotion->serviceOffers->count() > 0)
                                            <div class="promotion-muted mt-1">
                                                Dịch vụ:
                                                {{ $promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ') }}
                                            </div>
                                        @endif
                                        @if ($promotion->roomUpgradeOffers->count() > 0)
                                            <div class="promotion-muted mt-1 text-success">
                                                Nâng hạng:
                                                {{ $promotion->roomUpgradeOffers->map(fn ($offer) => $offer->kind_label . ' - ' . $offer->cover_label)->implode(' · ') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td style="min-width: 240px;">
                                        @if (count($conditionPills) > 0)
                                            <div class="promotion-condition-list">
                                                @foreach ($conditionPills as $conditionPill)
                                                    <span class="promotion-condition-pill">{{ $conditionPill }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="promotion-muted">Không có điều kiện đặc biệt</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="small">{{ $validText }}</div>
                                        @if ($promotion->stay_from || $promotion->stay_to)
                                            <div class="promotion-muted">
                                                Lưu trú:
                                                {{ $promotion->stay_from ? $promotion->stay_from->format('d/m/Y') : '---' }}
                                                →
                                                {{ $promotion->stay_to ? $promotion->stay_to->format('d/m/Y') : '---' }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $usageText }}</div>
                                        <div class="promotion-muted">
                                            Tổng ưu đãi:
                                            {{ number_format((float) ($promotion->total_discount_used ?? 0), 0, ',', '.') }}đ
                                        </div>
                                        @if ((float) ($promotion->total_room_upgrade_discount_used ?? 0) > 0)
                                            <div class="promotion-muted">
                                                Nâng hạng: {{ number_format((float) $promotion->total_room_upgrade_discount_used, 0, ',', '.') }}đ
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($promotion->user_can_apply && $promotion->is_public)
                                            <span class="badge bg-primary">User</span>
                                        @endif

                                        @if ($promotion->admin_can_apply)
                                            <span class="badge bg-dark">Admin</span>
                                        @endif

                                        @if ($promotion->requires_note)
                                            <span class="badge bg-warning text-dark">Cần lý do</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $statusClasses[$promotion->status] ?? 'bg-secondary' }}">
                                            {{ $statusLabels[$promotion->status] ?? $promotion->status }}
                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.promotions.show', $promotion->id) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="{{ route('admin.promotions.edit', $promotion->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="{{ route('admin.promotions.toggle-status', $promotion->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                {{ $promotion->status == 'active' ? 'Ẩn' : 'Bật' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.promotions.destroy', $promotion->id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Xác nhận xóa hoặc tạm ẩn mã này?')">
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
                                    <td colspan="8" class="text-center text-muted">
                                        Chưa có mã ưu đãi nào
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $promotions->links() }}
                </div>
            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
@endsection
