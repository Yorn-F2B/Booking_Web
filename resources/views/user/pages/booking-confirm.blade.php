@extends('layouts.user')

@section('title', 'Xác nhận đặt phòng')

@section('content')

    @php
        $policyService = app(\App\Services\HotelPolicyService::class);
        $minBookingAge = max(0, (int) $policyService->get('booking.min_age', 18));
        $depositPercent = max(0, min(100, (float) $policyService->depositPercentForRooms((int) ($bookingData['room_quantity'] ?? 1))));
        $depositRate = $depositPercent / 100;
        $standardCheckInTime = (string) $policyService->get('stay.standard_check_in_time', '14:00');
        $standardCheckOutTime = (string) $policyService->get('stay.standard_check_out_time', '12:00');
        $earlyCheckInFreeFrom = (string) $policyService->get('stay.early_checkin_free_from', '12:00');
        $manualRoomSelectionFee = max(0, (float) $policyService->get('booking.manual_room_selection_fee', 50000));
    @endphp

    <style>
        .promotion-list {
            display: grid;
            gap: 10px;
        }

        .promotion-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
            background: #fff;
            transition: 0.15s ease;
        }

        .promotion-card:hover {
            border-color: #c7a14a;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .promotion-code {
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .promotion-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .promotion-collapsible {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f9fafb;
            overflow: hidden;
        }

        .promotion-collapsible > summary {
            list-style: none;
            cursor: pointer;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .promotion-collapsible > summary::-webkit-details-marker {
            display: none;
        }

        .promotion-collapsible .promotion-collapsible-body {
            border-top: 1px solid #e5e7eb;
            padding: 14px;
            background: #ffffff;
        }

        .promotion-selected-hint {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

    </style>

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
            @include('user.partials.account-restriction')
@if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">
                        Vui lòng kiểm tra lại thông tin bên dưới.
                    </div>

                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bookings.store') }}" method="POST">
                @csrf

                <input type="hidden" name="room_category_id" value="{{ $bookingData['room_category_id'] }}">
                <input type="hidden" name="check_in_date" value="{{ $bookingData['check_in_date'] }}">
                <input type="hidden" name="check_out_date" value="{{ $bookingData['check_out_date'] }}">
                <input type="hidden" name="adult_count" value="{{ $bookingData['adult_count'] }}">
                <input type="hidden" name="child_count" value="{{ $bookingData['child_count'] ?? 0 }}">
                <input type="hidden" name="baby_count" value="{{ $bookingData['baby_count'] ?? 0 }}">
                <input type="hidden" name="room_quantity" value="{{ $bookingData['room_quantity'] ?? 1 }}">
                <input type="hidden" name="note" value="{{ $bookingData['note'] ?? '' }}">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div><h2 class="h5 fw-bold mb-1">Thông tin lưu trú</h2><div class="small text-muted">Có thể chỉnh ngay tại bước xác nhận; hệ thống sẽ kiểm tra lại tồn phòng và tính lại giá trước khi đặt.</div></div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="recheckBookingOption"><i class="bx bx-refresh me-1"></i>Cập nhật phương án</button>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Nhận phòng</label><input id="editBookingCheckIn" type="date" class="form-control" value="{{ $bookingData['check_in_date'] }}"></div>
                            <div class="col-md-3"><label class="form-label">Trả phòng</label><input id="editBookingCheckOut" type="date" class="form-control" value="{{ $bookingData['check_out_date'] }}"></div>
                            <div class="col-md-2"><label class="form-label">Người lớn</label><input id="editBookingAdults" type="number" min="1" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_guests',60) }}" class="form-control" value="{{ $bookingData['adult_count'] }}"></div>
                            <div class="col-md-2"><label class="form-label">Trẻ em</label><input id="editBookingChildren" type="number" min="0" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_guests',60) }}" class="form-control" value="{{ $bookingData['child_count'] ?? 0 }}"></div>
                            <div class="col-md-2"><label class="form-label">Em bé</label><input id="editBookingBabies" type="number" min="0" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_guests',60) }}" class="form-control" value="{{ $bookingData['baby_count'] ?? 0 }}"></div>
                            <div class="col-md-2"><label class="form-label">Số phòng</label><input id="editBookingRooms" type="number" min="1" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_rooms',30) }}" class="form-control" value="{{ $bookingData['room_quantity'] ?? 1 }}"></div>
                        </div>
                    </div>
                </div>

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
                                            value="{{ old('phone', $customer->phone ?? '') }}" inputmode="numeric" maxlength="10" required>
                                        @error('phone')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <button type="button" id="bookingCccdButton" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('bookingCccdImage').click()"><i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh</button>
                                                <input type="file" id="bookingCccdImage" class="d-none js-cccd-image" accept="image/*"
                                                    data-button="#bookingCccdButton" data-status="#bookingCccdStatus"
                                                    data-target-cccd="input[name='cccd']" data-target-first-name="input[name='first_name']"
                                                    data-target-last-name="input[name='last_name']" data-target-birthday="input[name='birthday']" data-target-gender="input[name='gender']" data-target-address="textarea[name='address']"
                                                    data-required-fields="cccd,full_name,birthday,gender,address" data-confirm-apply="1">
                                                <small id="bookingCccdStatus" class="text-muted">Quét và kiểm tra đúng CCCD của người đứng tên booking.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            CCCD <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="cccd" class="form-control"
                                            value="{{ old('cccd', $customer->cccd ?? '') }}" inputmode="numeric" maxlength="12" required>
                                        @error('cccd')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Ngày sinh <span class="text-danger">*</span>
                                        </label>
                                        @php
                                            $bookingBirthday = old(
                                                'birthday',
                                                $customer?->birthday
                                                    ? \Carbon\Carbon::parse($customer->birthday)->format('Y-m-d')
                                                    : ''
                                            );
                                        @endphp
                                        <input type="date" name="birthday" class="form-control"
                                            value="{{ $bookingBirthday }}"
                                            min="1900-01-01"
                                            max="{{ now('Asia/Ho_Chi_Minh')->subYears($minBookingAge)->toDateString() }}"
                                            required autocomplete="bday">
                                        <div class="form-text">Người đứng tên booking phải đủ {{ $minBookingAge }} tuổi tại ngày đặt phòng.</div>
                                        @error('birthday')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <input type="hidden" name="gender" value="{{ old('gender', $customer->gender ?? '') }}">

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $customer->email ?? auth()->user()->email) }}" required>
                                        @error('email')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Địa chỉ <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="address" rows="3"
                                            class="form-control" required>{{ old('address', $customer->address ?? '') }}</textarea>
                                        @error('address')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h2 class="h5 fw-bold mb-2">Cách khách sạn phân phòng</h2>
                                <p class="text-muted small mb-3">
                                    Hệ thống giữ đủ số phòng theo booking để tránh hết phòng. Nếu chọn theo yêu cầu, lễ tân sẽ đọc nội dung và chọn lại các phòng nếu đáp ứng được.
                                </p>

                                <div class="d-grid gap-2">
                                    <label class="border rounded-3 p-3 d-flex gap-2 align-items-start">
                                        <input class="form-check-input mt-1" type="radio" name="room_selection_mode" value="automatic"
                                            @checked(old('room_selection_mode', 'automatic') === 'automatic')>
                                        <span>
                                            <strong>Hệ thống tự chọn phòng</strong>
                                            <span class="d-block text-muted small">Miễn phí. Hệ thống tự chọn đủ số phòng đang khả dụng cho booking.</span>
                                        </span>
                                    </label>

                                    <label class="border rounded-3 p-3 d-flex gap-2 align-items-start">
                                        <input class="form-check-input mt-1" type="radio" name="room_selection_mode" value="manual"
                                            @checked(old('room_selection_mode') === 'manual')>
                                        <span>
                                            <strong>Chọn phòng theo yêu cầu</strong>
                                            <span class="d-block text-muted small">
                                                Lễ tân chọn thủ công theo nội dung khách ghi. Chỉ khi đáp ứng được mới thu
                                                <strong>{{ number_format($manualRoomSelectionFee, 0, ',', '.') }}đ/phòng</strong>.
                                            </span>
                                        </span>
                                    </label>
                                </div>

                                <div id="manualRoomRequestBox" class="mt-3 {{ old('room_selection_mode') === 'manual' ? '' : 'd-none' }}">
                                    <label for="roomSelectionRequest" class="form-label fw-semibold">Yêu cầu phòng</label>
                                    <textarea id="roomSelectionRequest" name="room_selection_request" rows="3"
                                        class="form-control @error('room_selection_request') is-invalid @enderror"
                                        placeholder="Ví dụ: muốn tầng cao, yên tĩnh, xa thang máy; nếu còn thì ưu tiên phòng 605...">{{ old('room_selection_request') }}</textarea>
                                    @error('room_selection_request')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Đây là yêu cầu ưu tiên, không phải cam kết cho tới khi lễ tân xác nhận phòng.</div>
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
                                                        data-type="{{ $service->type }}"
                                                        data-billing-rule="{{ $service->billing_rule ?: \App\Models\Service::BILLING_ONCE }}"
                                                        data-group="{{ $service->service_group ?? 'general' }}">
                                                        {{ $service->name }}
                                                        -
                                                        {{ number_format($service->price, 0, ',', '.') }}đ / {{ $service->unit }}
                                                        -
                                                        {{ $service->type === 'minibar_order' ? 'Minibar gọi thêm' : ($service->group_label ?? 'Dịch vụ') }}
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

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Mã ưu đãi có thể áp dụng
                                </h2>

                                <p class="text-muted small mb-3">
                                    Mỗi booking được chọn tối đa <strong>1 mã thường</strong>, <strong>1 mã sự kiện</strong> và <strong>1 mã điều kiện</strong>.
                                    Mã ghi “chỉ dùng một mình” không thể chọn cùng mã khác. Mã hỗ trợ chỉ do khách sạn áp dụng.
                                </p>

                                @php
                                    $eligiblePromotionCodes = ($availablePromotions ?? collect())->pluck('code')->map(fn ($code) => (string) $code)->all();
                                    $promotionCatalog = $promotionCatalog ?? ($availablePromotions ?? collect());
                                @endphp
                                @if (($promotionCatalog ?? collect())->count() > 0)
                                    <details class="promotion-collapsible" {{ !empty(old('promotion_codes', [])) ? 'open' : '' }}>
                                        <summary>
                                            <span>
                                                Có <span id="eligiblePromotionCount">{{ count($eligiblePromotionCodes) }}</span> mã phù hợp
                                                <span class="promotion-selected-hint d-block" id="selectedPromotionCountText">
                                                    Chưa chọn mã nào
                                                </span>
                                            </span>
                                            <span class="badge text-bg-light border">Bấm để xem / chọn</span>
                                        </summary>

                                        <div class="promotion-collapsible-body">
                                            <div class="promotion-list">
                                                @foreach ($promotionCatalog as $promotion)
                                                    @php
                                                        $promotionTypeLabel = match ($promotion->promotion_type) {
                                                            'normal_discount' => 'Mã thường',
                                                            'event_discount' => 'Mã sự kiện',
                                                            'conditional_discount' => 'Mã điều kiện',
                                                            default => 'Mã ưu đãi',
                                                        };

                                                        $promotionBadgeClass = match ($promotion->promotion_type) {
                                                            'normal_discount' => 'bg-primary',
                                                            'event_discount' => 'bg-success',
                                                            'conditional_discount' => 'bg-warning text-dark',
                                                            default => 'bg-secondary',
                                                        };

                                                        $promotionDiscountText = $promotion->discount_type == 'percent'
                                                            ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                            : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';

                                                        if ($promotion->discount_type == 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                            $promotionDiscountText .= ' - tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                        }

                                                        $promotionServiceOffersJson = $promotion->serviceOffers->map(function ($offer) {
                                                            return [
                                                                'service_id' => $offer->service_id,
                                                                'service_name' => $offer->service->name ?? 'Dịch vụ',
                                                                'service_unit' => $offer->service->unit ?? '',
                                                                'service_price' => (float) ($offer->service->price ?? 0),
                                                                'service_type' => $offer->service->type ?? 'service',
                                                                'service_billing_rule' => $offer->service->billing_rule ?? \App\Models\Service::BILLING_ONCE,
                                                                'discount_type' => $offer->discount_type,
                                                                'discount_value' => (float) $offer->discount_value,
                                                                'quantity' => (int) $offer->quantity,
                                                                'auto_add_service' => (bool) $offer->auto_add_service,
                                                            ];
                                                        })->values()->toArray();
                                                        $promotionInitiallyEligible = in_array((string) $promotion->code, $eligiblePromotionCodes, true);
                                                    @endphp

                                                    <label class="promotion-card mb-0 {{ $promotionInitiallyEligible ? '' : 'd-none' }}" data-promotion-card data-code="{{ $promotion->code }}">
                                                        <div class="form-check">
                                                            <input type="checkbox"
                                                                name="promotion_codes[]"
                                                                value="{{ $promotion->code }}"
                                                                class="form-check-input promotion-check"
                                                                data-code="{{ $promotion->code }}"
                                                                data-type="{{ $promotion->promotion_type }}"
                                                                data-stackable="{{ $promotion->is_stackable ? 1 : 0 }}"
                                                                data-discount-type="{{ $promotion->discount_type }}"
                                                                data-discount-value="{{ (float) $promotion->discount_value }}"
                                                                data-max-discount="{{ (float) $promotion->max_discount_amount }}"
                                                                data-service-offers='@json($promotionServiceOffersJson)'
                                                                @disabled(!$promotionInitiallyEligible)
                                                                @checked($promotionInitiallyEligible && in_array($promotion->code, old('promotion_codes', [])))>

                                                            <div class="ms-1">
                                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                                    <div>
                                                                        <div class="promotion-code">{{ $promotion->code }}</div>
                                                                        <div class="fw-semibold">{{ $promotion->name }}</div>
                                                                    </div>
                                                                    <span class="badge {{ $promotionBadgeClass }}">{{ $promotionTypeLabel }}</span>
                                                                </div>

                                                                <div class="promotion-meta mt-1">
                                                                    Giảm {{ $promotionDiscountText }}
                                                                    @if ((float) $promotion->min_booking_amount > 0)
                                                                        · Đơn từ {{ number_format((float) $promotion->min_booking_amount, 0, ',', '.') }}đ
                                                                    @endif
                                                                    @if ((int) $promotion->min_nights > 0)
                                                                        · Từ {{ (int) $promotion->min_nights }} đêm
                                                                    @endif
                                                                    · {{ $promotion->is_stackable ? 'Có thể dùng cùng nhóm mã khác' : 'Chỉ dùng một mình' }}
                                                                </div>

                                                                @if ($promotion->serviceOffers->count() > 0)
                                                                    <div class="promotion-meta mt-1 text-success">
                                                                        Dịch vụ ưu đãi:
                                                                        {{ $promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </details>
                                @else
                                    <div class="alert alert-light border mb-0" id="promotionEmptyState">
                                        Hiện chưa có mã ưu đãi phù hợp với đơn này.
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

                                <div class="alert alert-info small mb-3">
                                    Giờ nhận phòng linh hoạt <strong>{{ $earlyCheckInFreeFrom }} - {{ $standardCheckInTime }}</strong> nếu phòng sẵn sàng <br>
                                    Giờ trả phòng <strong>{{ $standardCheckOutTime }}</strong>
                                </div>

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
                                        Nhận phòng linh hoạt {{ $earlyCheckInFreeFrom }}–{{ $standardCheckInTime }} nếu phòng đã sẵn sàng
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
                                        Trả phòng trước {{ $standardCheckOutTime }}
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
                                        {{ $bookingData['child_count'] ?? 0 }} trẻ em,
                                        {{ $bookingData['baby_count'] ?? 0 }} em bé
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ (int) ($bookingData['room_quantity'] ?? 1) }} phòng
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

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Mã ưu đãi
                                    </div>
                                    <div class="fw-bold text-success" id="selectedPromotionDiscountText">
                                        -0đ
                                    </div>
                                    <div class="small text-muted" id="selectedPromotionBreakdownText">
                                        Chưa áp dụng ưu đãi.
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

                                <div class="border rounded-3 p-3 mb-3 bg-light">
                                    <div class="fw-bold mb-2">
                                        Chọn hình thức thanh toán
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_type"
                                            id="paymentDeposit30" value="deposit_30" checked>
                                        <label class="form-check-label" for="paymentDeposit30">
                                            Cọc {{ rtrim(rtrim(number_format($depositPercent, 2, '.', ''), '0'), '.') }}%
                                            <strong id="depositAmountPreview">
                                                {{ number_format(round($estimatedTotal * $depositRate), 0, ',', '.') }}đ
                                            </strong>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" id="finalBookingSubmit" class="btn btn-primary w-100 py-2">
                                    <i class="bx bx-check-circle me-1"></i>
                                    Thanh toán
                                </button>

                                <a href="{{ route('rooms', [
        'check_in_date' => $bookingData['check_in_date'],
        'check_out_date' => $bookingData['check_out_date'],
        'adult_count' => $bookingData['adult_count'],
        'child_count' => $bookingData['child_count'] ?? 0,
        'baby_count' => $bookingData['baby_count'] ?? 0,
        'room_category_id' => $roomCategory->id,
    ]) }}" class="btn btn-outline-secondary w-100 mt-2">
                                    Quay lại danh sách phòng
                                </a>

                                <p class="small text-muted mt-3 mb-0">
                                    Booking nhiều phòng đã được hỗ trợ trực tuyến. Hệ thống sẽ kiểm tra lại tồn phòng, sức chứa và mức cọc trước khi tạo đơn.
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
            const roomSelectionModeInputs = Array.from(document.querySelectorAll('input[name="room_selection_mode"]'));
            const manualRoomRequestBox = document.getElementById('manualRoomRequestBox');
            const roomSelectionRequest = document.getElementById('roomSelectionRequest');

            function syncRoomSelectionMode() {
                const selected = roomSelectionModeInputs.find((input) => input.checked)?.value || 'automatic';
                const isManual = selected === 'manual';
                manualRoomRequestBox?.classList.toggle('d-none', !isManual);
                if (roomSelectionRequest) {
                    roomSelectionRequest.required = isManual;
                }
            }

            roomSelectionModeInputs.forEach((input) => input.addEventListener('change', syncRoomSelectionMode));
            syncRoomSelectionMode();

            const serviceSelect = document.getElementById('serviceSelect');
            const serviceQuantity = document.getElementById('serviceQuantity');
            const serviceNote = document.getElementById('serviceNote');
            const addServiceButton = document.getElementById('addServiceButton');

            const selectedServiceEmptyBox = document.getElementById('selectedServiceEmptyBox');
            const selectedServiceTableBox = document.getElementById('selectedServiceTableBox');
            const selectedServiceTableBody = document.getElementById('selectedServiceTableBody');
            const selectedServiceInputs = document.getElementById('selectedServiceInputs');

            const selectedServiceTotalText = document.getElementById('selectedServiceTotalText');
            const selectedPromotionDiscountText = document.getElementById('selectedPromotionDiscountText');
            const selectedPromotionBreakdownText = document.getElementById('selectedPromotionBreakdownText');
            const finalEstimatedTotalText = document.getElementById('finalEstimatedTotalText');
            const depositAmountPreview = document.getElementById('depositAmountPreview');
            const promotionChecks = document.querySelectorAll('.promotion-check');
            const fullAmountPreview = document.getElementById('fullAmountPreview');

            const selectedServices = new Map();
            const bookingNightCount = Math.max(1, {{ (int) $nightCount }});
            const bookingRoomCount = Math.max(1, {{ (int) ($bookingData['room_quantity'] ?? 1) }});
            const bookingGuestCount = Math.max(1, {{ (int) $bookingData['adult_count'] + (int) ($bookingData['child_count'] ?? 0) + (int) ($bookingData['baby_count'] ?? 0) }});

            function serviceMultiplier(billingRule) {
                if (billingRule === 'per_night') return bookingNightCount;
                if (billingRule === 'per_room') return bookingRoomCount;
                if (billingRule === 'per_room_per_night') return bookingRoomCount * bookingNightCount;
                if (billingRule === 'per_guest') return bookingGuestCount;
                if (billingRule === 'per_guest_per_night') return bookingGuestCount * bookingNightCount;
                return 1;
            }

            function billedServiceQuantity(service) {
                return Math.max(1, parseInt(service.quantity || 1)) * serviceMultiplier(service.billingRule || 'once');
            }

            function serviceLineTotal(service) {
                return Math.round(Math.max(0, Number(service.price || 0)) * billedServiceQuantity(service));
            }

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            function getTypeLabel(type) {
                if (type === 'minibar_order') {
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

            function getSelectedServiceQuantity(serviceId) {
                const key = String(serviceId);
                return selectedServices.has(key)
                    ? billedServiceQuantity(selectedServices.get(key))
                    : 0;
            }

            function parseServiceOffers(checkbox) {
                try {
                    return JSON.parse(checkbox.dataset.serviceOffers || '[]');
                } catch (error) {
                    return [];
                }
            }

            function calculatePromotionTotals(roomTotal, serviceTotal) {
                let autoServiceTotal = 0;
                let serviceDiscount = 0;
                const autoServiceNames = [];

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    parseServiceOffers(checkbox).forEach(function (offer) {
                        const price = parseFloat(offer.service_price || 0);
                        const billingRule = offer.service_billing_rule || 'once';
                        const offerQuantity = Math.max(1, parseInt(offer.quantity || 1));
                        let applicableQuantity = Math.min(offerQuantity, getSelectedServiceQuantity(offer.service_id));
                        const missingQuantity = Math.max(0, offerQuantity - applicableQuantity);

                        if (missingQuantity > 0 && offer.auto_add_service) {
                            autoServiceTotal += price * missingQuantity * serviceMultiplier(billingRule);
                            applicableQuantity += missingQuantity;
                            autoServiceNames.push((offer.service_name || 'Dịch vụ') + ' x' + missingQuantity);
                        }

                        if (applicableQuantity <= 0 || price <= 0) {
                            return;
                        }

                        const originalAmount = price * applicableQuantity;
                        let discountAmount = 0;

                        if (offer.discount_type === 'percent') {
                            discountAmount = Math.round(originalAmount * parseFloat(offer.discount_value || 0) / 100);
                        } else {
                            discountAmount = parseFloat(offer.discount_value || 0) * applicableQuantity;
                        }

                        serviceDiscount += Math.min(Math.max(0, discountAmount), originalAmount);
                    });
                });

                const subtotal = roomTotal + serviceTotal + autoServiceTotal;
                let moneyDiscount = 0;

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    const discountType = checkbox.dataset.discountType;
                    const discountValue = parseFloat(checkbox.dataset.discountValue || 0);
                    const maxDiscount = parseFloat(checkbox.dataset.maxDiscount || 0);
                    let amount = 0;

                    if (discountType === 'percent') {
                        amount = Math.round(subtotal * discountValue / 100);

                        if (maxDiscount > 0) {
                            amount = Math.min(amount, maxDiscount);
                        }
                    } else {
                        amount = discountValue;
                    }

                    moneyDiscount += Math.max(0, amount);
                });

                const effectiveServiceDiscount = Math.min(serviceDiscount, subtotal);
                const effectiveMoneyDiscount = Math.min(moneyDiscount, Math.max(0, subtotal - effectiveServiceDiscount));
                const totalDiscount = effectiveServiceDiscount + effectiveMoneyDiscount;

                return {
                    subtotal: subtotal,
                    autoServiceTotal: autoServiceTotal,
                    autoServiceNames: autoServiceNames,
                    moneyDiscount: effectiveMoneyDiscount,
                    serviceDiscount: effectiveServiceDiscount,
                    totalDiscount: totalDiscount,
                    finalTotal: Math.max(0, subtotal - totalDiscount),
                };
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
                    const total = serviceLineTotal(service);
                    serviceTotal += total;

                    const row = document.createElement('tr');

                    row.innerHTML = `
                                                    <td class="fw-bold">${service.name}</td>
                                                    <td>
                                                        <span class="badge ${service.type === 'minibar_order' ? 'bg-warning text-dark' : 'bg-primary'}">
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
                    const totals = calculatePromotionTotals(getRoomTotal(), serviceTotal);
                    const finalTotal = totals.finalTotal;

                    if (selectedPromotionDiscountText) {
                        selectedPromotionDiscountText.innerText = '-' + formatMoney(totals.totalDiscount);
                    }

                    if (selectedPromotionBreakdownText) {
                        const parts = [];

                        if (totals.moneyDiscount > 0) {
                            parts.push('Giảm tiền ' + formatMoney(totals.moneyDiscount));
                        }

                        if (totals.serviceDiscount > 0) {
                            parts.push('Ưu đãi dịch vụ ' + formatMoney(totals.serviceDiscount));
                        }

                        if (totals.autoServiceTotal > 0) {
                            parts.push('Tự thêm ' + totals.autoServiceNames.join(', '));
                        }

                        selectedPromotionBreakdownText.innerText = parts.length > 0
                            ? parts.join(' · ')
                            : 'Chưa áp dụng ưu đãi.';
                    }

                    finalEstimatedTotalText.innerText = formatMoney(finalTotal);

                    if (depositAmountPreview) {
                        const roomTotal = getRoomTotal();
                        const roomDiscountForDeposit = Math.min(roomTotal, totals.moneyDiscount);
                        const requiredDeposit = Math.round(Math.max(0, roomTotal - roomDiscountForDeposit) * {{ json_encode($depositRate) }});
                        depositAmountPreview.innerText = formatMoney(requiredDeposit);
                    }

                    if (fullAmountPreview) {
                        fullAmountPreview.innerText = formatMoney(finalTotal);
                    }
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
                        billingRule: selectedOption.dataset.billingRule || 'once',
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

            function promotionTypeLabel(type) {
                if (type === 'normal_discount') return 'mã thường';
                if (type === 'event_discount') return 'mã sự kiện';
                if (type === 'conditional_discount') return 'mã điều kiện';
                return 'mã ưu đãi';
            }

            function enforceUserPromotionSelection(changedCheckbox) {
                if (!changedCheckbox.checked) return true;

                const selected = Array.from(document.querySelectorAll('.promotion-check:checked'));
                if (changedCheckbox.dataset.stackable === '0' && selected.length > 1) {
                    changedCheckbox.checked = false;
                    alert('Mã ' + (changedCheckbox.dataset.code || '') + ' chỉ được dùng một mình.');
                    return false;
                }

                const selectedSolo = selected.find(item => item !== changedCheckbox && item.dataset.stackable === '0');
                if (selectedSolo) {
                    changedCheckbox.checked = false;
                    alert('Mã ' + (selectedSolo.dataset.code || '') + ' đang được chọn và chỉ được dùng một mình.');
                    return false;
                }

                const type = changedCheckbox.dataset.type || '';
                if (['normal_discount', 'event_discount', 'conditional_discount'].includes(type)) {
                    const sameType = selected.filter(item => item.dataset.type === type);
                    if (sameType.length > 1) {
                        changedCheckbox.checked = false;
                        alert('Mỗi booking chỉ được chọn tối đa 1 ' + promotionTypeLabel(type) + '.');
                        return false;
                    }
                }

                return true;
            }

            promotionChecks.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    enforceUserPromotionSelection(checkbox);
                    renderSelectedServices();
                });
            });

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


            function updateSelectedPromotionCountText() {
                const text = document.getElementById('selectedPromotionCountText');
                if (!text) {
                    return;
                }

                const selected = Array.from(document.querySelectorAll('.promotion-check:checked'))
                    .map(function (checkbox) {
                        return checkbox.dataset.code || checkbox.value;
                    });

                text.innerText = selected.length > 0
                    ? 'Đang chọn: ' + selected.join(', ')
                    : 'Chưa chọn mã nào';
            }

            updateSelectedPromotionCountText();
            document.querySelectorAll('.promotion-check').forEach(function (checkbox) {
                checkbox.addEventListener('change', updateSelectedPromotionCountText);
            });

            let promotionRefreshTimer = null;
            async function refreshPromotionEligibility() {
                const cccdInput = document.querySelector('input[name="cccd"]');
                const csrf = document.querySelector('input[name="_token"]')?.value;
                if (!csrf) return;

                const body = new URLSearchParams({
                    _token: csrf,
                    room_category_id: @json($bookingData['room_category_id']),
                    check_in_date: document.querySelector('input[name="check_in_date"]')?.value || @json($bookingData['check_in_date']),
                    check_out_date: document.querySelector('input[name="check_out_date"]')?.value || @json($bookingData['check_out_date']),
                    adult_count: document.querySelector('input[name="adult_count"]')?.value || @json($bookingData['adult_count']),
                    child_count: document.querySelector('input[name="child_count"]')?.value || @json($bookingData['child_count'] ?? 0),
                    baby_count: document.querySelector('input[name="baby_count"]')?.value || @json($bookingData['baby_count'] ?? 0),
                    room_quantity: document.querySelector('input[name="room_quantity"]')?.value || @json($bookingData['room_quantity'] ?? 1),
                    customer_cccd: (cccdInput?.value || '').replace(/\D/g, '')
                });

                try {
                    const response = await fetch(@json(route('bookings.eligible-promotions')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: body.toString()
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    const eligible = new Set((payload.codes || []).map(String));
                    document.querySelectorAll('[data-promotion-card]').forEach(function (card) {
                        const code = String(card.dataset.code || '');
                        const checkbox = card.querySelector('.promotion-check');
                        const allowed = eligible.has(code);
                        card.classList.toggle('d-none', !allowed);
                        if (checkbox) {
                            checkbox.disabled = !allowed;
                            if (!allowed && checkbox.checked) checkbox.checked = false;
                        }
                    });
                    const count = document.getElementById('eligiblePromotionCount');
                    if (count) count.textContent = String(eligible.size);
                    updateSelectedPromotionCountText();
                    renderSelectedServices();
                } catch (error) {
                    console.debug('Không thể làm mới mã ưu đãi theo CCCD.', error);
                }
            }

            const cccdPromotionInput = document.querySelector('input[name="cccd"]');
            cccdPromotionInput?.addEventListener('input', function () {
                clearTimeout(promotionRefreshTimer);
                promotionRefreshTimer = setTimeout(refreshPromotionEligibility, 450);
            });

        });
    </script>

@include('partials.cccd-scanner-script')
<script>
const bookingOptionInputs = ['editBookingCheckIn','editBookingCheckOut','editBookingAdults','editBookingChildren','editBookingBabies','editBookingRooms']
    .map(id => document.getElementById(id)).filter(Boolean);
const finalBookingSubmit = document.getElementById('finalBookingSubmit');
const initialBookingOption = bookingOptionInputs.map(input => input.value).join('|');
function syncBookingOptionDirtyState() {
    const dirty = bookingOptionInputs.map(input => input.value).join('|') !== initialBookingOption;
    if (finalBookingSubmit) {
        finalBookingSubmit.disabled = dirty;
        finalBookingSubmit.title = dirty ? 'Hãy bấm Cập nhật phương án để kiểm tra lại tồn phòng và giá.' : '';
    }
    document.getElementById('recheckBookingOption')?.classList.toggle('btn-warning', dirty);
}
bookingOptionInputs.forEach(input => input.addEventListener('input', syncBookingOptionDirtyState));
syncBookingOptionDirtyState();

document.getElementById('recheckBookingOption')?.addEventListener('click', function () {
    const params = new URLSearchParams({
        room_category_id: @json($bookingData['room_category_id']),
        check_in_date: document.getElementById('editBookingCheckIn').value,
        check_out_date: document.getElementById('editBookingCheckOut').value,
        adult_count: document.getElementById('editBookingAdults').value,
        child_count: document.getElementById('editBookingChildren').value,
        baby_count: document.getElementById('editBookingBabies').value,
        room_quantity: document.getElementById('editBookingRooms').value,
        note: @json($bookingData['note'] ?? '')
    });
    window.location.href = @json(route('bookings.confirm')) + '?' + params.toString();
});
</script>

@endsection
