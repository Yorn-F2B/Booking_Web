@extends('layouts.admin')

@section('title', 'Chi tiết mã ưu đãi')

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

        $customerName = function ($booking) {
            $name = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? ''));
            return $name !== '' ? $name : 'Khách hàng';
        };
    @endphp

    <style>
        .promotion-detail-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
            margin-bottom: 18px;
        }

        .promotion-detail-card h5 {
            font-weight: 800;
            margin-bottom: 14px;
        }

        .promotion-code-large {
            display: inline-flex;
            border-radius: 999px;
            padding: 8px 14px;
            background: #111827;
            color: #fff;
            font-weight: 900;
            letter-spacing: 0.05em;
        }

        .promotion-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .promotion-stat-box {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }

        .promotion-stat-box span {
            display: block;
            color: #64748b;
            font-size: 13px;
        }

        .promotion-stat-box strong {
            display: block;
            margin-top: 4px;
            font-size: 20px;
        }

        .promotion-info-row {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .promotion-info-row:last-child {
            border-bottom: 0;
        }

        .promotion-info-label {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 991px) {
            .promotion-stat-grid {
                grid-template-columns: 1fr 1fr;
            }

            .promotion-info-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.promotions.index') }}">Mã ưu đãi</a> /
                Chi tiết
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Chi tiết mã ưu đãi</h2>
                    <p>Theo dõi điều kiện áp dụng và lịch sử sử dụng mã</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.promotions.edit', $promotion->id) }}" class="btn btn-gold">
                        <i class="bx bx-edit me-1"></i>
                        Chỉnh sửa
                    </a>

                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>
            </div>
<div class="promotion-detail-card">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="promotion-code-large mb-2">{{ $promotion->code }}</div>
                        <h4 class="mb-1">{{ $promotion->name }}</h4>
                        <p class="text-muted mb-0">{{ $promotion->description ?: 'Chưa có mô tả.' }}</p>
                    </div>

                    <div class="text-end">
                        <span class="badge {{ $statusClasses[$promotion->status] ?? 'bg-secondary' }}">
                            {{ $statusLabels[$promotion->status] ?? $promotion->status }}
                        </span>

                        <form action="{{ route('admin.promotions.toggle-status', $promotion->id) }}"
                            method="POST" class="mt-2">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                {{ $promotion->status == 'active' ? 'Tạm ẩn mã' : 'Bật mã' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="promotion-stat-grid mb-3">
                <div class="promotion-stat-box">
                    <span>Loại mã</span>
                    <strong>{{ $promotion->type_label }}</strong>
                </div>

                <div class="promotion-stat-box">
                    <span>Giá trị giảm</span>
                    <strong>{{ $promotion->discount_label }}</strong>
                </div>

                <div class="promotion-stat-box">
                    <span>Đã dùng</span>
                    <strong>
                        {{ (int) $promotion->used_count }}
                        @if ($promotion->usage_limit)
                            / {{ (int) $promotion->usage_limit }}
                        @endif
                    </strong>
                </div>

                <div class="promotion-stat-box">
                    <span>Tổng đã giảm</span>
                    <strong>{{ number_format((float) $totalDiscount, 0, ',', '.') }}đ</strong>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="promotion-detail-card">
                        <h5>Điều kiện áp dụng</h5>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Thời gian nhập mã</div>
                            <div>
                                {{ $promotion->valid_from ? $promotion->valid_from->format('d/m/Y H:i') : 'Không giới hạn' }}
                                →
                                {{ $promotion->valid_to ? $promotion->valid_to->format('d/m/Y H:i') : 'Không giới hạn' }}
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Ngày lưu trú áp dụng</div>
                            <div>
                                {{ $promotion->stay_from ? $promotion->stay_from->format('d/m/Y') : 'Không giới hạn' }}
                                →
                                {{ $promotion->stay_to ? $promotion->stay_to->format('d/m/Y') : 'Không giới hạn' }}
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Đơn tối thiểu</div>
                            <div>{{ number_format((float) $promotion->min_booking_amount, 0, ',', '.') }}đ</div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Số đêm / số phòng</div>
                            <div>
                                Từ {{ (int) $promotion->min_nights }} đêm,
                                từ {{ (int) $promotion->min_rooms }} phòng
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Điều kiện khách</div>
                            <div>
                                Hoàn thành từ {{ (int) $promotion->min_completed_bookings }} đơn,
                                chi tiêu từ {{ number_format((float) $promotion->min_total_spent, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Giới hạn mỗi khách</div>
                            <div>
                                {{ $promotion->per_customer_limit ? (int) $promotion->per_customer_limit . ' lần' : 'Không giới hạn' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="promotion-detail-card">
                        <h5>Quyền sử dụng</h5>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Hiện cho user</div>
                            <div>
                                @if ($promotion->is_public)
                                    <span class="badge bg-primary">Có</span>
                                @else
                                    <span class="badge bg-secondary">Không</span>
                                @endif
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">User tự áp dụng</div>
                            <div>
                                @if ($promotion->user_can_apply)
                                    <span class="badge bg-primary">Có</span>
                                @else
                                    <span class="badge bg-secondary">Không</span>
                                @endif
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Admin áp dụng</div>
                            <div>
                                @if ($promotion->admin_can_apply)
                                    <span class="badge bg-dark">Có</span>
                                @else
                                    <span class="badge bg-secondary">Không</span>
                                @endif
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Bắt buộc nhập lý do</div>
                            <div>
                                @if ($promotion->requires_note)
                                    <span class="badge bg-warning text-dark">Có</span>
                                @else
                                    <span class="badge bg-secondary">Không</span>
                                @endif
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Cho dùng chung</div>
                            <div>
                                @if ($promotion->is_stackable)
                                    <span class="badge bg-success">Có</span>
                                @else
                                    <span class="badge bg-danger">Không</span>
                                @endif
                            </div>
                        </div>

                        <div class="promotion-info-row">
                            <div class="promotion-info-label">Người tạo</div>
                            <div>{{ $promotion->creator->name ?? 'Không xác định' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="promotion-detail-card">
                <h5>Ưu đãi dịch vụ đi kèm</h5>

                @if ($promotion->serviceOffers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dịch vụ</th>
                                    <th>Giá trị ưu đãi</th>
                                    <th>Số lượng</th>
                                    <th>Tự thêm vào booking</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($promotion->serviceOffers as $offer)
                                    <tr>
                                        <td class="fw-bold">{{ $offer->service->name ?? 'Dịch vụ đã xóa' }}</td>
                                        <td>
                                            @if ($offer->discount_type === \App\Models\Promotion::DISCOUNT_PERCENT)
                                                {{ rtrim(rtrim(number_format((float) $offer->discount_value, 2, ',', '.'), '0'), ',') }}%
                                            @else
                                                {{ number_format((float) $offer->discount_value, 0, ',', '.') }}đ / đơn vị
                                            @endif
                                        </td>
                                        <td>{{ (int) $offer->quantity }}</td>
                                        <td>{{ $offer->auto_add_service ? 'Có' : 'Không' }}</td>
                                        <td class="text-muted small">{{ $offer->note ?: '---' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-light border mb-0">
                        Mã này hiện chỉ giảm tiền, chưa gắn ưu đãi dịch vụ.
                    </div>
                @endif
            </div>

            <div class="promotion-detail-card">
                <h5>Lịch sử sử dụng mã</h5>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Thời gian</th>
                                <th>Booking</th>
                                <th>Khách</th>
                                <th>Người áp dụng</th>
                                <th>Kênh</th>
                                <th class="text-end">Giảm tiền</th>
                                <th class="text-end">Ưu đãi dịch vụ</th>
                                <th class="text-end">Tổng ưu đãi</th>
                                <th>Lý do / ghi chú</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($usages as $usage)
                                <tr>
                                    <td>{{ $usage->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if ($usage->booking)
                                            <a href="{{ route('admin.bookings.show', $usage->booking->id) }}">
                                                {{ $usage->booking->booking_code }}
                                            </a>
                                        @else
                                            <span class="text-muted">Booking đã xóa</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($usage->booking)
                                            {{ $customerName($usage->booking) }}
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td>{{ $usage->user->name ?? ($usage->applied_channel == 'user' ? 'Khách tự áp dụng' : 'Không xác định') }}</td>
                                    <td>
                                        @if ($usage->applied_channel == 'admin')
                                            <span class="badge bg-dark">Admin</span>
                                        @else
                                            <span class="badge bg-primary">User</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        -{{ number_format((float) ($usage->money_discount_amount ?? $usage->discount_amount), 0, ',', '.') }}đ
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        -{{ number_format((float) ($usage->service_discount_amount ?? 0), 0, ',', '.') }}đ
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        -{{ number_format((float) $usage->discount_amount, 0, ',', '.') }}đ
                                    </td>
                                    <td>{{ $usage->note ?: '---' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        Mã này chưa được áp dụng cho booking nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $usages->links() }}
                </div>
            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
@endsection
