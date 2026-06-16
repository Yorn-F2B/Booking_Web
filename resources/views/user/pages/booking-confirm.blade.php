@extends('layouts.user')

@section('title', 'Xác nhận đặt phòng')

@section('content')

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                Xác nhận đặt phòng
            </h1>

            <p class="text-muted mb-0">
                Kiểm tra thông tin cá nhân và thông tin đặt phòng trước khi hoàn tất.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    Vui lòng kiểm tra lại thông tin bên dưới.
                </div>
            @endif

            <form action="{{ route('bookings.store') }}" method="POST">
                @csrf

                <input type="hidden" name="room_category_id" value="{{ $bookingData['room_category_id'] }}">
                <input type="hidden" name="check_in_date" value="{{ $bookingData['check_in_date'] }}">
                <input type="hidden" name="check_out_date" value="{{ $bookingData['check_out_date'] }}">
                <input type="hidden" name="adult_count" value="{{ $bookingData['adult_count'] }}">
                <input type="hidden" name="child_count" value="{{ $bookingData['child_count'] ?? 0 }}">
                <input type="hidden" name="note" value="{{ $bookingData['note'] ?? '' }}">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Thông tin khách hàng
                                </h2>

                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Họ
                                        </label>
                                        <input type="text" name="last_name" class="form-control"
                                            value="{{ old('last_name', $customer->last_name ?? '') }}" required>
                                        @error('last_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Tên
                                        </label>
                                        <input type="text" name="first_name" class="form-control"
                                            value="{{ old('first_name', $customer->first_name ?? '') }}" required>
                                        @error('first_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Số điện thoại
                                        </label>
                                        <input type="text" name="phone" class="form-control"
                                            value="{{ old('phone', $customer->phone ?? '') }}" required>
                                        @error('phone')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            CCCD
                                        </label>
                                        <input type="text" name="cccd" class="form-control"
                                            value="{{ old('cccd', $customer->cccd ?? '') }}">
                                        @error('cccd')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Email
                                        </label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $customer->email ?? auth()->user()->email) }}">
                                        @error('email')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Địa chỉ
                                        </label>
                                        <textarea name="address" rows="3"
                                            class="form-control">{{ old('address', $customer->address ?? '') }}</textarea>
                                        @error('address')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Dịch vụ đặt thêm
                                </h2>

                                <p class="text-muted small mb-3">
                                    Chọn dịch vụ cần đặt trước. Nếu cần thêm dịch vụ trong thời gian lưu trú, vui lòng gọi
                                    lễ tân để được hỗ trợ.
                                </p>

                                @if ($services->count() > 0)

                                    <div class="row g-2 align-items-end mb-3">

                                        <div class="col-md-6">
                                            <label class="form-label small">Chọn dịch vụ</label>
                                            <select id="serviceSelect" class="form-select">
                                                <option value="">-- Chọn dịch vụ --</option>

                                                @foreach ($services as $service)
                                                    <option value="{{ $service->id }}" data-name="{{ $service->name }}"
                                                        data-price="{{ $service->price }}" data-unit="{{ $service->unit }}"
                                                        data-type="{{ $service->type }}">
                                                        {{ $service->name }}
                                                        -
                                                        {{ number_format($service->price, 0, ',', '.') }}đ / {{ $service->unit }}
                                                        -
                                                        {{ $service->type == 'minibar' ? 'Minibar' : 'Dịch vụ' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label small">Số lượng</label>
                                            <input type="number" id="serviceQuantity" class="form-control" value="1" min="1">
                                        </div>

                                        <div class="col-md-3">
                                            <button type="button" id="addServiceButton" class="btn btn-primary w-100">
                                                Thêm
                                            </button>
                                        </div>

                                        <div class="col-md-12">
                                            <label class="form-label small">Ghi chú</label>
                                            <input type="text" id="serviceNote" class="form-control"
                                                placeholder="Ví dụ: chuẩn bị trước khi nhận phòng">
                                        </div>

                                    </div>

                                    <div id="selectedServiceEmptyBox" class="alert alert-light border mb-0">
                                        Chưa chọn dịch vụ đặt thêm.
                                    </div>

                                    <div id="selectedServiceTableBox" class="table-responsive d-none">

                                        <table class="table table-sm align-middle mb-0">

                                            <thead class="table-light">
                                                <tr>
                                                    <th>Dịch vụ đã chọn</th>
                                                    <th>Loại</th>
                                                    <th>Đơn giá</th>
                                                    <th style="width: 110px;">Số lượng</th>
                                                    <th>Thành tiền</th>
                                                    <th>Ghi chú</th>
                                                    <th></th>
                                                </tr>
                                            </thead>

                                            <tbody id="selectedServiceTableBody"></tbody>

                                        </table>

                                    </div>

                                    <div id="selectedServiceInputs"></div>

                                @else

                                    <div class="alert alert-light border mb-0">
                                        Hiện chưa có dịch vụ đặt thêm.
                                    </div>

                                @endif

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Ghi chú đặt phòng
                                </h2>

                                <p class="mb-0 text-muted">
                                    {{ $bookingData['note'] ?? 'Không có ghi chú.' }}
                                </p>

                            </div>
                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card border-0 shadow-sm sticky-top" style="top: 90px;">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Thông tin booking
                                </h2>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Hạng phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ $roomCategory->name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Nhận phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ date('d/m/Y', strtotime($bookingData['check_in_date'])) }}
                                    </div>
                                    <div class="small text-muted">
                                        Nhận phòng từ 14:00 đến 15:00
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Trả phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ date('d/m/Y', strtotime($bookingData['check_out_date'])) }}
                                    </div>
                                    <div class="small text-muted">
                                        Trả phòng trước 11:00
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số đêm
                                    </div>
                                    <div class="fw-bold">
                                        {{ $nightCount }} đêm
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số khách
                                    </div>
                                    <div class="fw-bold">
                                        {{ $bookingData['adult_count'] }} người lớn,
                                        {{ $bookingData['child_count'] ?? 0 }} trẻ em
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số phòng
                                    </div>
                                    <div class="fw-bold">
                                        1 phòng
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Dịch vụ đặt thêm
                                    </div>
                                    <div class="fw-bold text-danger" id="selectedServiceTotalText">
                                        0đ
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold">
                                        Tạm tính
                                    </span>

                                    <span class="fw-bold text-primary fs-5" id="finalEstimatedTotalText"
                                        data-room-total="{{ $estimatedTotal }}">
                                        {{ number_format($estimatedTotal, 0, ',', '.') }}đ
                                    </span>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bx bx-check-circle me-1"></i>
                                    Xác nhận đặt phòng
                                </button>

                                <a href="{{ route('rooms.show', $roomCategory->id) }}"
                                    class="btn btn-outline-secondary w-100 mt-2">
                                    Quay lại
                                </a>

                                <p class="small text-muted mt-3 mb-0">
                                    Nếu cần đặt nhiều phòng hoặc khách đoàn, vui lòng liên hệ hotline/lễ tân để được hỗ trợ.
                                </p>

                            </div>
                        </div>

                    </div>

                </div>

            </form>

        </div>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceQuantity = document.getElementById('serviceQuantity');
            const serviceNote = document.getElementById('serviceNote');
            const addServiceButton = document.getElementById('addServiceButton');

            const selectedServiceEmptyBox = document.getElementById('selectedServiceEmptyBox');
            const selectedServiceTableBox = document.getElementById('selectedServiceTableBox');
            const selectedServiceTableBody = document.getElementById('selectedServiceTableBody');
            const selectedServiceInputs = document.getElementById('selectedServiceInputs');

            const selectedServiceTotalText = document.getElementById('selectedServiceTotalText');
            const finalEstimatedTotalText = document.getElementById('finalEstimatedTotalText');

            const selectedServices = new Map();

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            function getTypeLabel(type) {
                if (type === 'minibar') {
                    return 'Minibar';
                }

                return 'Dịch vụ';
            }

            function getRoomTotal() {
                if (!finalEstimatedTotalText) {
                    return 0;
                }

                return parseFloat(finalEstimatedTotalText.dataset.roomTotal || 0);
            }

            function renderSelectedServices() {
                if (!selectedServiceTableBody || !selectedServiceInputs) {
                    return;
                }

                selectedServiceTableBody.innerHTML = '';
                selectedServiceInputs.innerHTML = '';

                let serviceTotal = 0;
                let index = 0;

                selectedServices.forEach(function (service, serviceId) {
                    const total = service.price * service.quantity;
                    serviceTotal += total;

                    const row = document.createElement('tr');

                    row.innerHTML = `
                            <td class="fw-bold">${service.name}</td>
                            <td>
                                <span class="badge ${service.type === 'minibar' ? 'bg-warning text-dark' : 'bg-primary'}">
                                    ${getTypeLabel(service.type)}
                                </span>
                            </td>
                            <td>${formatMoney(service.price)} / ${service.unit}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm selected-service-quantity"
                                    value="${service.quantity}" min="1" data-service-id="${serviceId}">
                            </td>
                            <td class="fw-bold text-danger">${formatMoney(total)}</td>
                            <td>
                                <input type="text" class="form-control form-control-sm selected-service-note"
                                    value="${service.note}" data-service-id="${serviceId}" placeholder="Ghi chú">
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-service-button"
                                    data-service-id="${serviceId}">
                                    Xóa
                                </button>
                            </td>
                        `;

                    selectedServiceTableBody.appendChild(row);

                    selectedServiceInputs.insertAdjacentHTML('beforeend', `
                            <input type="hidden" name="services[${index}][service_id]" value="${serviceId}">
                            <input type="hidden" name="services[${index}][quantity]" value="${service.quantity}">
                            <input type="hidden" name="services[${index}][note]" value="${service.note}">
                        `);

                    index++;
                });

                if (selectedServiceEmptyBox && selectedServiceTableBox) {
                    if (selectedServices.size > 0) {
                        selectedServiceEmptyBox.classList.add('d-none');
                        selectedServiceTableBox.classList.remove('d-none');
                    } else {
                        selectedServiceEmptyBox.classList.remove('d-none');
                        selectedServiceTableBox.classList.add('d-none');
                    }
                }

                if (selectedServiceTotalText) {
                    selectedServiceTotalText.innerText = formatMoney(serviceTotal);
                }

                if (finalEstimatedTotalText) {
                    finalEstimatedTotalText.innerText = formatMoney(getRoomTotal() + serviceTotal);
                }
            }

            function addSelectedService() {
                if (!serviceSelect || !serviceQuantity) {
                    return;
                }

                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

                if (!selectedOption || !selectedOption.value) {
                    alert('Vui lòng chọn dịch vụ.');
                    return;
                }

                const serviceId = selectedOption.value;
                const quantity = Math.max(1, parseInt(serviceQuantity.value || 1));
                const note = serviceNote ? serviceNote.value.trim() : '';

                if (selectedServices.has(serviceId)) {
                    const currentService = selectedServices.get(serviceId);

                    currentService.quantity += quantity;

                    if (note !== '') {
                        currentService.note = currentService.note
                            ? currentService.note + '; ' + note
                            : note;
                    }

                    selectedServices.set(serviceId, currentService);
                } else {
                    selectedServices.set(serviceId, {
                        name: selectedOption.dataset.name || selectedOption.text,
                        price: parseFloat(selectedOption.dataset.price || 0),
                        unit: selectedOption.dataset.unit || '',
                        type: selectedOption.dataset.type || 'service',
                        quantity: quantity,
                        note: note,
                    });
                }

                serviceSelect.value = '';
                serviceQuantity.value = 1;

                if (serviceNote) {
                    serviceNote.value = '';
                }

                renderSelectedServices();
            }

            if (addServiceButton) {
                addServiceButton.addEventListener('click', addSelectedService);
            }

            if (selectedServiceTableBody) {
                selectedServiceTableBody.addEventListener('click', function (event) {
                    const button = event.target.closest('.remove-service-button');

                    if (!button) {
                        return;
                    }

                    selectedServices.delete(button.dataset.serviceId);
                    renderSelectedServices();
                });

                selectedServiceTableBody.addEventListener('input', function (event) {
                    const quantityInput = event.target.closest('.selected-service-quantity');
                    const noteInput = event.target.closest('.selected-service-note');

                    if (quantityInput) {
                        const service = selectedServices.get(quantityInput.dataset.serviceId);

                        if (service) {
                            service.quantity = Math.max(1, parseInt(quantityInput.value || 1));
                            selectedServices.set(quantityInput.dataset.serviceId, service);
                            renderSelectedServices();
                        }
                    }

                    if (noteInput) {
                        const service = selectedServices.get(noteInput.dataset.serviceId);

                        if (service) {
                            service.note = noteInput.value;
                            selectedServices.set(noteInput.dataset.serviceId, service);
                            renderSelectedServices();
                        }
                    }
                });
            }

            renderSelectedServices();
        });
    </script>

@endsection