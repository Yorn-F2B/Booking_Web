@extends('layouts.user')

@section('title', 'Chi tiết đơn phòng')

@section('content')

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                Chi tiết đơn phòng
            </h1>

            <p class="text-muted mb-0">
                Theo dõi thông tin đặt phòng, trạng thái xác nhận và phòng được gán.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            <div class="mb-4">
                <a href="{{ route('home') }}#bookings" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>
                    Quay lại trang chủ
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin.</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="settings-section mb-4">

                        <div class="d-flex justify-content-between align-items-start mb-3">

                            <div>
                                <h2 class="h5 fw-bold mb-1">
                                    {{ $booking->booking_code }}
                                </h2>

                                <p class="text-muted small mb-0">
                                    {{ $booking->roomCategory->name ?? 'Không xác định' }}
                                </p>
                            </div>

                            <div>
                                @if ($booking->status == 'pending')
                                    <span class="badge text-bg-warning">Chờ xác nhận</span>
                                @elseif ($booking->status == 'confirmed')
                                    <span class="badge text-bg-primary">Đã xác nhận</span>
                                @elseif ($booking->status == 'checked_in')
                                    <span class="badge text-bg-info">Đã nhận phòng</span>
                                @elseif ($booking->status == 'checked_out')
                                    <span class="badge text-bg-success">Đã trả phòng</span>
                                @else
                                    <span class="badge text-bg-danger">Đã hủy</span>
                                @endif
                            </div>

                        </div>

                        <div class="table-responsive">

                            <table class="table table-bordered align-middle mb-0">

                                <tbody>

                                    <tr>
                                        <th width="220">Mã booking</th>
                                        <td>{{ $booking->booking_code }}</td>
                                    </tr>

                                    <tr>
                                        <th>Hạng phòng</th>
                                        <td>{{ $booking->roomCategory->name ?? 'Không xác định' }}</td>
                                    </tr>

                                    <tr>
                                        <th>Ngày nhận phòng</th>
                                        <td>
                                            {{ date('d/m/Y', strtotime($booking->check_in_date)) }}
                                            <div class="small text-muted">Nhận phòng linh hoạt 13:00–14:00 nếu phòng đã sẵn sàng</div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Ngày trả phòng</th>
                                        <td>
                                            {{ date('d/m/Y', strtotime($booking->check_out_date)) }}
                                            <div class="small text-muted">Trả phòng trước 12:00</div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Số người lớn</th>
                                        <td>{{ $booking->adult_count }}</td>
                                    </tr>

                                    <tr>
                                        <th>Số trẻ em</th>
                                        <td>{{ $booking->child_count }}</td>
                                    </tr>

                                    <tr>
                                        <th>Số phòng đặt</th>
                                        <td>{{ $booking->room_quantity }}</td>
                                    </tr>

                                    <tr>
                                        <th>Yêu cầu phòng gần nhau</th>
                                        <td>{{ $booking->prefer_adjacent_rooms ? 'Có' : 'Không' }}</td>
                                    </tr>

                                    <tr>
                                        <th>Tổng tiền tạm tính</th>
                                        <td>{{ number_format($booking->estimated_total, 0, ',', '.') }}đ</td>
                                    </tr>

                                    <tr>
                                        <th>Tiền cọc</th>
                                        <td>{{ number_format($booking->deposit_amount, 0, ',', '.') }}đ</td>
                                    </tr>

                                    <tr>
                                        <th>Trạng thái thanh toán</th>
                                        <td>
                                            @if ($booking->payment_status == 'unpaid')
                                                Chưa thanh toán
                                            @elseif ($booking->payment_status == 'partial')
                                                Đã cọc / thanh toán một phần
                                            @elseif ($booking->payment_status == 'paid')
                                                Đã thanh toán
                                            @else
                                                Đã hoàn tiền
                                            @endif
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Ghi chú</th>
                                        <td>{{ $booking->note ?? 'Không có ghi chú' }}</td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    @php
                        $canCustomerAddService = in_array($booking->status, ['pending', 'confirmed', 'checked_in'])
                            && !in_array($booking->payment_status, ['paid', 'refunded']);
                    @endphp

                    <div class="settings-section mb-4">

                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">
                                    Tự thêm dịch vụ
                                </h3>
                                <p class="text-muted small mb-0">
                                    Chọn thêm dịch vụ cần dùng, khách sạn sẽ ghi nhận trực tiếp trên đơn phòng này.
                                </p>
                            </div>

                            @if ($canCustomerAddService)
                                <span class="badge text-bg-success">Đang mở</span>
                            @else
                                <span class="badge text-bg-secondary">Đã khóa</span>
                            @endif
                        </div>

                        @if ($canCustomerAddService)
                            @if (($availableServices ?? collect())->count() > 0)
                                <form action="{{ route('bookings.services.store', $booking->id) }}" method="POST" id="customerAddServiceForm">
                                    @csrf

                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small">Dịch vụ</label>
                                            <select name="service_id" id="customerServiceSelect" class="form-select" required>
                                                <option value="">-- Chọn dịch vụ --</option>
                                                @foreach ($availableServices as $service)
                                                    <option value="{{ $service->id }}"
                                                        data-name="{{ $service->name }}"
                                                        data-price="{{ $service->price }}"
                                                        data-unit="{{ $service->unit }}"
                                                        data-type="{{ $service->type }}">
                                                        {{ $service->name }} - {{ number_format((float) $service->price, 0, ',', '.') }}đ / {{ $service->unit }}
                                                        @if ($service->type == 'minibar')
                                                            - Minibar
                                                        @else
                                                            - Dịch vụ
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label small">Số lượng</label>
                                            <input type="number" name="quantity" id="customerServiceQuantity" class="form-control" value="1" min="1" max="50" required>
                                        </div>

                                        <div class="col-md-5">
                                            <label class="form-label small">Ghi chú</label>
                                            <input type="text" name="note" class="form-control" placeholder="Ví dụ: giao lên phòng sau 19:00">
                                        </div>

                                        <div class="col-md-8">
                                            <div class="alert alert-light border mb-0 small" id="customerServicePreview">
                                                Chọn dịch vụ để xem tạm tính.
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bx bx-plus-circle me-1"></i>
                                                Thêm dịch vụ
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-light border mb-0">
                                    Hiện chưa có dịch vụ nào đang mở bán.
                                </div>
                            @endif
                        @else
                            <div class="alert alert-light border mb-0 small">
                                Chỉ có thể tự thêm dịch vụ khi đơn còn ở trạng thái chờ xác nhận, đã xác nhận hoặc đang lưu trú,
                                và đơn chưa thanh toán hoàn tất.
                            </div>
                        @endif

                    </div>

                    @if ($booking->serviceItems->count() > 0)
                        <div class="settings-section mb-4">

                            <h3 class="h6 fw-bold mb-3">
                                Dịch vụ / phụ thu phát sinh
                            </h3>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tên khoản thu</th>
                                            <th>Loại</th>
                                            <th>Đơn giá</th>
                                            <th>Số lượng</th>
                                            <th>Thực dùng</th>
                                            <th>Trạng thái</th>
                                            <th>Thành tiền</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($booking->serviceItems as $item)
                                            <tr>
                                                <td>{{ $item->name }}</td>

                                                <td>
                                                    @if ($item->type == 'service')
                                                        <span class="badge text-bg-primary">Dịch vụ</span>
                                                    @elseif ($item->type == 'minibar')
                                                        <span class="badge text-bg-warning">Minibar</span>
                                                    @elseif ($item->type == 'damage_fee')
                                                        <span class="badge text-bg-danger">Hư hại</span>
                                                    @elseif ($item->type == 'occupancy_fee')
                                                        <span class="badge text-bg-info">Phụ thu số người</span>
                                                    @elseif ($item->type == 'policy_violation_fee')
                                                        <span class="badge text-bg-dark">Vi phạm nội quy</span>
                                                    @else
                                                        <span class="badge text-bg-secondary">{{ $item->type }}</span>
                                                    @endif
                                                </td>

                                                <td>{{ number_format((float) $item->unit_price, 0, ',', '.') }}đ</td>

                                                <td>{{ $item->quantity }}</td>

                                                <td>
                                                    @if ($item->type == 'minibar')
                                                        {{ $item->used_quantity ?? 0 }}/{{ $item->quantity }}
                                                    @else
                                                        {{ $item->used_quantity ?? $item->quantity }}
                                                    @endif
                                                </td>

                                                <td>
                                                    @if (($item->billing_status ?? null) == 'confirmed')
                                                        <span class="badge text-bg-success">Đã tính</span>
                                                    @elseif (($item->billing_status ?? null) == 'pending')
                                                        <span class="badge text-bg-warning">Chờ xác nhận</span>
                                                    @elseif (($item->billing_status ?? null) == 'unused')
                                                        <span class="badge text-bg-secondary">Không dùng</span>
                                                    @elseif (($item->billing_status ?? null) == 'cancelled')
                                                        <span class="badge text-bg-danger">Đã hủy</span>
                                                    @else
                                                        <span class="badge text-bg-light">---</span>
                                                    @endif
                                                </td>

                                                <td class="fw-bold text-danger">
                                                    {{ number_format((float) $item->total, 0, ',', '.') }}đ
                                                </td>

                                                <td>{{ $item->note ?: '---' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    @endif

                </div>

                <div class="col-lg-4">

                    <div class="settings-section mb-4">

                        <h3 class="h6 fw-bold mb-3">
                            Phòng đã được gán
                        </h3>

                        @forelse ($booking->bookingRooms as $bookingRoom)

                            <div class="border rounded p-3 mb-2">
                                <div class="fw-bold">
                                    Phòng {{ $bookingRoom->room->room_number ?? 'Không xác định' }}
                                </div>

                                <div class="small text-muted">
                                    Tầng {{ $bookingRoom->room->floor_number ?? '---' }}
                                </div>
                            </div>

                        @empty

                            <div class="alert alert-warning mb-0">
                                Khách sạn chưa gán phòng cụ thể cho đơn này.
                            </div>

                        @endforelse

                    </div>


                    @if (in_array($booking->status, ['pending', 'confirmed']))

                        <div class="settings-section">

                            <h3 class="h6 fw-bold mb-3">
                                Thao tác
                            </h3>

                            <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST"
                                onsubmit="return confirm('Bạn có chắc muốn hủy đơn đặt phòng này không?')">

                                @csrf

                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bx bx-x-circle me-1"></i>
                                    Hủy đơn đặt phòng
                                </button>

                            </form>

                        </div>

                    @endif

                </div>

            </div>

        </div>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const serviceSelect = document.getElementById('customerServiceSelect');
            const quantityInput = document.getElementById('customerServiceQuantity');
            const previewBox = document.getElementById('customerServicePreview');

            if (!serviceSelect || !quantityInput || !previewBox) {
                return;
            }

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            function updatePreview() {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const quantity = Math.max(1, parseInt(quantityInput.value || 1));

                if (!selectedOption || !selectedOption.value) {
                    previewBox.innerHTML = 'Chọn dịch vụ để xem tạm tính.';
                    return;
                }

                const price = parseFloat(selectedOption.dataset.price || 0);
                const unit = selectedOption.dataset.unit || '';
                const type = selectedOption.dataset.type || 'service';
                const total = price * quantity;

                if (type === 'minibar') {
                    previewBox.innerHTML = '<strong>' + selectedOption.dataset.name + '</strong> x ' + quantity
                        + ' · Đơn giá ' + formatMoney(price) + ' / ' + unit
                        + '<br><span class="text-muted">Minibar sẽ được ghi nhận và xác nhận số lượng thực dùng khi trả phòng.</span>';
                    return;
                }

                previewBox.innerHTML = '<strong>' + selectedOption.dataset.name + '</strong> x ' + quantity
                    + ' · Tạm tính thêm <strong class="text-danger">' + formatMoney(total) + '</strong>';
            }

            serviceSelect.addEventListener('change', updatePreview);
            quantityInput.addEventListener('input', updatePreview);
            updatePreview();
        });
    </script>

@endsection