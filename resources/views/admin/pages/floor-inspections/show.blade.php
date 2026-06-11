@extends('layouts.admin')

@section('title', 'Kiểm tra phòng')

@section('content')

    @php
        $statusLabels = [
            'pending' => 'Chờ kiểm tra',
            'reported' => 'Đã báo cáo - chờ admin duyệt',
            'confirmed' => 'Admin đã duyệt',
            'rejected' => 'Admin từ chối',
        ];

        $statusClasses = [
            'pending' => 'bg-warning text-dark',
            'reported' => 'bg-info',
            'confirmed' => 'bg-success',
            'rejected' => 'bg-danger',
        ];

        $customerName = trim(($roomInspection->booking->customer->last_name ?? '') . ' ' . ($roomInspection->booking->customer->first_name ?? ''));

        $oldItemMap = $roomInspection->items->keyBy('service_id');
    @endphp

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.floor-inspections.index') }}">Phòng cần kiểm tra</a> /
                Chi tiết kiểm tra
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Kiểm tra phòng {{ $roomInspection->room->room_number ?? '---' }}</h2>
                    <p>Quản lý tầng ghi nhận và cập nhật tình trạng phòng trước khi check-out</p>
                </div>

                <a href="{{ route('admin.floor-inspections.index') }}" class="btn btn-outline-secondary">
                    Quay lại
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
                    Vui lòng kiểm tra lại thông tin kiểm tra phòng.
                </div>
            @endif

            <div class="row g-4">

                <div class="col-lg-4">

                    <div class="settings-section h-100">

                        <h5 class="fw-bold mb-3">
                            Thông tin phiếu kiểm tra
                        </h5>

                        <div class="mb-3">
                            <div class="text-muted small">Trạng thái</div>
                            <span class="badge {{ $statusClasses[$roomInspection->status] ?? 'bg-secondary' }}">
                                {{ $statusLabels[$roomInspection->status] ?? $roomInspection->status }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Mã booking</div>
                            <strong>{{ $roomInspection->booking->booking_code ?? '---' }}</strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Khách hàng</div>
                            <strong>{{ $customerName ?: 'Chưa có tên' }}</strong>
                            <div class="text-muted small">
                                {{ $roomInspection->booking->customer->phone ?? '---' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Phòng</div>
                            <strong>Phòng {{ $roomInspection->room->room_number ?? '---' }}</strong>
                            <div class="text-muted small">
                                Tầng {{ $roomInspection->room->floor_number ?? '---' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Hạng phòng</div>
                            <strong>{{ $roomInspection->booking->roomCategory->name ?? '---' }}</strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Check-in</div>
                            {{ $roomInspection->booking->check_in_date ? date('d/m/Y', strtotime($roomInspection->booking->check_in_date)) : '---' }}
                        </div>

                        <div class="mb-0">
                            <div class="text-muted small">Check-out dự kiến</div>
                            {{ $roomInspection->booking->check_out_date ? date('d/m/Y', strtotime($roomInspection->booking->check_out_date)) : '---' }}
                        </div>

                    </div>

                </div>

                <div class="col-lg-8">

                    <div class="settings-section">

                        <h5 class="fw-bold mb-3">
                            Kết quả kiểm tra phòng
                        </h5>

                        @if ($roomInspection->status == 'confirmed')

                            <div class="alert alert-success">
                                Phiếu này đã được admin duyệt nên quản lý tầng không thể chỉnh sửa nữa.
                            </div>

                        @endif

                        <form action="{{ route('admin.floor-inspections.report', $roomInspection->id) }}" method="POST">

                            @csrf

                            <fieldset {{ $roomInspection->status == 'confirmed' ? 'disabled' : '' }}>

                                <div class="mb-4">

                                    <label class="form-label fw-semibold">
                                        Phòng có hư hại không?
                                    </label>

                                    <select name="has_damage" id="hasDamage" class="form-select" required>
                                        <option value="0" @selected(!$roomInspection->has_damage)>
                                            Không có hư hại
                                        </option>

                                        <option value="1" @selected($roomInspection->has_damage)>
                                            Có hư hại
                                        </option>
                                    </select>

                                </div>

                                <div id="damageWrapper" style="display:none;">

                                    <label class="form-label fw-semibold mb-3">
                                        Chọn hạng mục hư hại
                                    </label>

                                    @if ($damageServices->count() > 0)

                                        <div class="table-responsive">

                                            <table class="table table-bordered align-middle">

                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 60px;">Chọn</th>
                                                        <th>Hư hại</th>
                                                        <th>Đơn giá</th>
                                                        <th>Đơn vị</th>
                                                        <th style="width: 120px;">Số lượng</th>
                                                        <th>Tạm tính</th>
                                                    </tr>
                                                </thead>

                                                <tbody>

                                                    @foreach ($damageServices as $damageService)

                                                        @php
                                                            $oldItem = $oldItemMap[$damageService->id] ?? null;
                                                            $isChecked = $oldItem ? true : false;
                                                            $quantity = $oldItem ? $oldItem->quantity : 1;
                                                        @endphp

                                                        <tr>
                                                            <td>
                                                                <input type="checkbox"
                                                                    name="damage_service_ids[]"
                                                                    value="{{ $damageService->id }}"
                                                                    class="form-check-input damage-checkbox"
                                                                    @checked($isChecked)>
                                                            </td>

                                                            <td>
                                                                <strong>{{ $damageService->name }}</strong>

                                                                @if ($damageService->description)
                                                                    <div class="text-muted small">
                                                                        {{ $damageService->description }}
                                                                    </div>
                                                                @endif
                                                            </td>

                                                            <td>
                                                                {{ number_format((float) $damageService->price, 0, ',', '.') }}đ
                                                            </td>

                                                            <td>
                                                                {{ $damageService->unit }}
                                                            </td>

                                                            <td>
                                                                <input type="number"
                                                                    name="damage_quantities[{{ $damageService->id }}]"
                                                                    id="damageQuantity{{ $damageService->id }}"
                                                                    class="form-control form-control-sm damage-quantity"
                                                                    value="{{ $quantity }}"
                                                                    min="1"
                                                                    data-price="{{ (float) $damageService->price }}"
                                                                    {{ $isChecked ? '' : 'disabled' }}>
                                                            </td>

                                                            <td>
                                                                <span id="damageTotal{{ $damageService->id }}">
                                                                    0đ
                                                                </span>
                                                            </td>
                                                        </tr>

                                                    @endforeach

                                                </tbody>

                                            </table>

                                        </div>

                                        <div class="text-end mt-3">
                                            <h5>
                                                Tổng hư hại:
                                                <span id="grandDamageTotal">0đ</span>
                                            </h5>
                                        </div>

                                    @else

                                        <div class="alert alert-warning">
                                            Chưa có hạng mục phí hư hại nào. Vui lòng thêm trong phần Dịch vụ với loại "Phí hư hại".
                                        </div>

                                    @endif

                                </div>

                                <div class="mt-4">

                                    <label class="form-label fw-semibold">
                                        Ghi chú kiểm tra
                                    </label>

                                    <textarea name="inspection_note"
                                        rows="4"
                                        class="form-control"
                                        placeholder="Mô tả tình trạng phòng...">{{ old('inspection_note', $roomInspection->inspection_note) }}</textarea>

                                </div>

                                @if ($roomInspection->status == 'rejected' && $roomInspection->admin_note)
                                    <div class="alert alert-danger mt-3">
                                        <strong>Lý do admin từ chối:</strong>
                                        <br>
                                        {{ $roomInspection->admin_note }}
                                    </div>
                                @endif

                                <button type="submit" class="btn btn-primary mt-4">
                                    Gửi / Cập nhật kết quả kiểm tra
                                </button>

                            </fieldset>

                        </form>

                    </div>

                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

    <script>
        const hasDamage = document.getElementById('hasDamage');
        const damageWrapper = document.getElementById('damageWrapper');

        function formatMoney(number) {
            return new Intl.NumberFormat('vi-VN').format(number).replaceAll(',', '.') + 'đ';
        }

        function toggleDamageWrapper() {
            if (!hasDamage || !damageWrapper) {
                return;
            }

            damageWrapper.style.display = hasDamage.value === '1' ? 'block' : 'none';

            if (hasDamage.value !== '1') {
                document.querySelectorAll('.damage-checkbox').forEach(function (checkbox) {
                    checkbox.checked = false;
                });

                document.querySelectorAll('.damage-quantity').forEach(function (input) {
                    input.disabled = true;
                    input.value = 1;
                });
            }

            calculateDamageTotal();
        }

        function calculateDamageTotal() {
            let grandTotal = 0;

            document.querySelectorAll('.damage-checkbox').forEach(function (checkbox) {
                const serviceId = checkbox.value;
                const quantityInput = document.getElementById('damageQuantity' + serviceId);
                const totalElement = document.getElementById('damageTotal' + serviceId);

                if (!quantityInput || !totalElement) {
                    return;
                }

                const quantity = Math.max(1, parseInt(quantityInput.value || 1));
                const price = parseFloat(quantityInput.dataset.price || 0);

                let lineTotal = 0;

                if (checkbox.checked) {
                    lineTotal = price * quantity;
                    grandTotal += lineTotal;
                }

                totalElement.innerText = formatMoney(lineTotal);
            });

            const grandDamageTotal = document.getElementById('grandDamageTotal');

            if (grandDamageTotal) {
                grandDamageTotal.innerText = formatMoney(grandTotal);
            }
        }

        document.querySelectorAll('.damage-checkbox').forEach(function (checkbox) {
            const serviceId = checkbox.value;
            const quantityInput = document.getElementById('damageQuantity' + serviceId);

            if (quantityInput) {
                quantityInput.disabled = !checkbox.checked;
            }

            checkbox.addEventListener('change', function () {
                if (quantityInput) {
                    quantityInput.disabled = !this.checked;

                    if (this.checked && (!quantityInput.value || quantityInput.value < 1)) {
                        quantityInput.value = 1;
                    }
                }

                calculateDamageTotal();
            });
        });

        document.querySelectorAll('.damage-quantity').forEach(function (input) {
            input.addEventListener('input', calculateDamageTotal);
            input.addEventListener('change', calculateDamageTotal);
        });

        if (hasDamage) {
            hasDamage.addEventListener('change', toggleDamageWrapper);
        }

        toggleDamageWrapper();
    </script>

@endsection