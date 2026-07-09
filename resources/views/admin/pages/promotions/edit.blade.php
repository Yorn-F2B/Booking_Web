@extends('layouts.admin')

@section('title', 'Sửa mã ưu đãi')

@section('content')
    <style>
        .promotion-form-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
            margin-bottom: 18px;
        }

        .promotion-form-card h5 {
            font-weight: 800;
            margin-bottom: 14px;
        }

        .promotion-help {
            color: #64748b;
            font-size: 13px;
        }

        .promotion-switch-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .promotion-switch-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            background: #f8fafc;
        }

        @media (max-width: 767px) {
            .promotion-switch-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.promotions.index') }}">Mã ưu đãi</a> /
                Sửa mã
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Sửa mã ưu đãi</h2>
                    <p>Cập nhật điều kiện, quyền dùng và thời gian hiệu lực</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.promotions.show', $promotion->id) }}" class="btn btn-outline-primary">
                        Xem chi tiết
                    </a>

                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Không thể cập nhật mã:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.promotions.update', $promotion->id) }}" method="POST" id="promotionForm">
                @csrf
                @method('PUT')
@once
    <style>
        .flatpickr-calendar {
            font-family: inherit;
            border-radius: 14px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month input.cur-year {
            font-weight: 800;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.selected:hover {
            background: #d4af37;
            border-color: #d4af37;
        }

        .flatpickr-time input,
        .flatpickr-time .flatpickr-am-pm {
            font-weight: 800;
        }

        .promotion-service-offer-row,
        .promotion-room-upgrade-row {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
            padding: 14px;
            margin-bottom: 12px;
        }

        .promotion-service-offer-row:last-child,
        .promotion-room-upgrade-row:last-child {
            margin-bottom: 0;
        }
    </style>
@endonce

@php
    $serviceOfferRows = old('service_offers');

    if ($serviceOfferRows === null) {
        $serviceOfferRows = $promotion->exists
            ? $promotion->serviceOffers->map(function ($offer) {
                return [
                    'service_id' => $offer->service_id,
                    'discount_type' => $offer->discount_type,
                    'discount_value' => $offer->discount_value,
                    'quantity' => $offer->quantity,
                    'auto_add_service' => $offer->auto_add_service ? 1 : 0,
                    'note' => $offer->note,
                ];
            })->values()->all()
            : [];
    }

    if (!is_array($serviceOfferRows) || count($serviceOfferRows) === 0) {
        $serviceOfferRows = [[
            'service_id' => '',
            'discount_type' => 'percent',
            'discount_value' => 100,
            'quantity' => 1,
            'auto_add_service' => 1,
            'note' => '',
        ]];
    }


    $roomUpgradeOfferRows = old('room_upgrade_offers');

    if ($roomUpgradeOfferRows === null) {
        $roomUpgradeOfferRows = $promotion->exists
            ? $promotion->roomUpgradeOffers->map(function ($offer) {
                return [
                    'enabled' => 1,
                    'upgrade_kind' => $offer->upgrade_kind,
                    'from_room_category_id' => $offer->from_room_category_id,
                    'to_room_category_id' => $offer->to_room_category_id,
                    'cover_type' => $offer->cover_type,
                    'cover_value' => $offer->cover_value,
                    'max_cover_amount' => $offer->max_cover_amount,
                    'auto_apply_on_upgrade' => $offer->auto_apply_on_upgrade ? 1 : 0,
                    'note' => $offer->note,
                ];
            })->values()->all()
            : [];
    }

    if (!is_array($roomUpgradeOfferRows) || count($roomUpgradeOfferRows) === 0) {
        $roomUpgradeOfferRows = [[
            'enabled' => 0,
            'upgrade_kind' => old('promotion_type', $promotion->promotion_type) === 'support_discount' ? 'incident_support' : 'paid_upsell',
            'from_room_category_id' => '',
            'to_room_category_id' => '',
            'cover_type' => 'percent_difference',
            'cover_value' => 20,
            'max_cover_amount' => '',
            'auto_apply_on_upgrade' => 0,
            'note' => '',
        ]];
    }
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="promotion-form-card">
            <h5>Thông tin mã</h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mã code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control text-uppercase"
                        value="{{ old('code', $promotion->code) }}" placeholder="VD: WELCOME10" required>
                    <div class="promotion-help mt-1">Chỉ dùng chữ, số, dấu - hoặc _.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Tên mã <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $promotion->name) }}" placeholder="VD: Ưu đãi khách mới" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Loại mã <span class="text-danger">*</span></label>
                    <select name="promotion_type" id="promotionType" class="form-select" required>
                        @foreach ($promotionTypes as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}"
                                @selected(old('promotion_type', $promotion->promotion_type) == $typeValue)>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </select>
                    <div class="promotion-help mt-1" id="promotionTypeHelp"></div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $promotion->status) == 'active')>
                            Hoạt động
                        </option>
                        <option value="inactive" @selected(old('status', $promotion->status) == 'inactive')>
                            Tạm ẩn
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" rows="3" class="form-control"
                        placeholder="Ghi chú nội bộ hoặc mô tả ngắn cho mã">{{ old('description', $promotion->description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="promotion-form-card">
            <h5>Giá trị giảm tiền</h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Kiểu giảm tiền <span class="text-danger">*</span></label>
                    <select name="discount_type" id="discountType" class="form-select" required>
                        @foreach ($discountTypes as $discountValue => $discountLabel)
                            <option value="{{ $discountValue }}"
                                @selected(old('discount_type', $promotion->discount_type) == $discountValue)>
                                {{ $discountLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Giá trị giảm tiền <span class="text-danger">*</span></label>
                    <input type="number" name="discount_value" id="discountValue" class="form-control"
                        value="{{ old('discount_value', $promotion->discount_value) }}" min="0" step="0.01" required>
                    <div class="promotion-help mt-1" id="discountValueHelp"></div>
                </div>

                <div class="col-md-4" id="maxDiscountBox">
                    <label class="form-label">Giảm tối đa</label>
                    <input type="number" name="max_discount_amount" class="form-control"
                        value="{{ old('max_discount_amount', $promotion->max_discount_amount) }}"
                        min="0" step="1000" placeholder="VD: 200000">
                    <div class="promotion-help mt-1">Chỉ dùng khi giảm theo %.</div>
                </div>
            </div>
        </div>



        <div class="promotion-form-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <h5 class="mb-1">Ưu đãi dịch vụ đi kèm</h5>
                    <div class="promotion-help">
                        Dùng cho mã kiểu giảm tiền phòng + tặng/giảm dịch vụ cụ thể. Ví dụ: giảm 200.000đ và tặng buffet sáng 100%.
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary" id="addServiceOfferButton">
                    Thêm dịch vụ
                </button>
            </div>

            @if (($services ?? collect())->count() > 0)
                <div id="serviceOfferRows" data-next-index="{{ count($serviceOfferRows) }}">
                    @foreach ($serviceOfferRows as $offerIndex => $offerRow)
                        <div class="promotion-service-offer-row" data-service-offer-row>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Dịch vụ ưu đãi</label>
                                    <select name="service_offers[{{ $offerIndex }}][service_id]" class="form-select">
                                        <option value="">-- Không chọn --</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->id }}" @selected((string) ($offerRow['service_id'] ?? '') === (string) $service->id)>
                                                {{ $service->name }} - {{ number_format((float) $service->price, 0, ',', '.') }}đ/{{ $service->unit }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Kiểu ưu đãi</label>
                                    <select name="service_offers[{{ $offerIndex }}][discount_type]" class="form-select service-offer-discount-type">
                                        @foreach ($discountTypes as $discountValue => $discountLabel)
                                            <option value="{{ $discountValue }}" @selected(($offerRow['discount_type'] ?? 'percent') === $discountValue)>
                                                {{ $discountLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Giá trị</label>
                                    <input type="number" name="service_offers[{{ $offerIndex }}][discount_value]"
                                        class="form-control" min="0" step="0.01"
                                        value="{{ $offerRow['discount_value'] ?? 100 }}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Số lượng</label>
                                    <input type="number" name="service_offers[{{ $offerIndex }}][quantity]"
                                        class="form-control" min="1"
                                        value="{{ $offerRow['quantity'] ?? 1 }}">
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label">Ghi chú dịch vụ ưu đãi</label>
                                    <input type="text" name="service_offers[{{ $offerIndex }}][note]" class="form-control"
                                        value="{{ $offerRow['note'] ?? '' }}"
                                        placeholder="VD: Tặng buffet sáng cho khách đổi hạng phòng">
                                </div>

                                <div class="col-md-3 d-flex align-items-end">
                                    <label class="promotion-switch-item w-100 mb-0">
                                        <input type="checkbox" name="service_offers[{{ $offerIndex }}][auto_add_service]" value="1"
                                            @checked((int) ($offerRow['auto_add_service'] ?? 1) === 1)>
                                        <span class="fw-semibold d-block">Tự thêm vào booking</span>
                                        <span class="promotion-help">Nếu khách chưa chọn dịch vụ này.</span>
                                    </label>
                                </div>

                                <div class="col-md-2 d-flex align-items-end justify-content-end">
                                    <button type="button" class="btn btn-outline-danger remove-service-offer-button">
                                        Xóa dòng
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template id="serviceOfferTemplate">
                    <div class="promotion-service-offer-row" data-service-offer-row>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Dịch vụ ưu đãi</label>
                                <select name="service_offers[__INDEX__][service_id]" class="form-select">
                                    <option value="">-- Không chọn --</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">
                                            {{ $service->name }} - {{ number_format((float) $service->price, 0, ',', '.') }}đ/{{ $service->unit }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Kiểu ưu đãi</label>
                                <select name="service_offers[__INDEX__][discount_type]" class="form-select service-offer-discount-type">
                                    @foreach ($discountTypes as $discountValue => $discountLabel)
                                        <option value="{{ $discountValue }}" @selected($discountValue === 'percent')>
                                            {{ $discountLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Giá trị</label>
                                <input type="number" name="service_offers[__INDEX__][discount_value]"
                                    class="form-control" min="0" step="0.01" value="100">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Số lượng</label>
                                <input type="number" name="service_offers[__INDEX__][quantity]"
                                    class="form-control" min="1" value="1">
                            </div>

                            <div class="col-md-7">
                                <label class="form-label">Ghi chú dịch vụ ưu đãi</label>
                                <input type="text" name="service_offers[__INDEX__][note]" class="form-control"
                                    placeholder="VD: Tặng buffet sáng cho khách đổi hạng phòng">
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <label class="promotion-switch-item w-100 mb-0">
                                    <input type="checkbox" name="service_offers[__INDEX__][auto_add_service]" value="1" checked>
                                    <span class="fw-semibold d-block">Tự thêm vào booking</span>
                                    <span class="promotion-help">Nếu khách chưa chọn dịch vụ này.</span>
                                </label>
                            </div>

                            <div class="col-md-2 d-flex align-items-end justify-content-end">
                                <button type="button" class="btn btn-outline-danger remove-service-offer-button">
                                    Xóa dòng
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            @else
                <div class="alert alert-light border mb-0">
                    Chưa có dịch vụ hoạt động để gắn vào mã ưu đãi. Hãy tạo dịch vụ trước nếu muốn tặng/giảm dịch vụ.
                </div>
            @endif
        </div>


        <div class="promotion-form-card">
            <h5>Ưu đãi nâng hạng phòng</h5>
            <div class="promotion-help mb-3">
                Bật phần này khi mã dùng cho đổi/nâng hạng phòng. Mã hỗ trợ sự cố phải là <strong>Mã hỗ trợ</strong>; mã kích thích khách đặt hạng cao hơn phải là <strong>Mã điều kiện</strong>.
            </div>

            @foreach ($roomUpgradeOfferRows as $upgradeIndex => $upgradeRow)
                <div class="promotion-room-upgrade-row" data-room-upgrade-row>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="room_upgrade_offers[{{ $upgradeIndex }}][enabled]" value="1"
                            class="form-check-input room-upgrade-enabled" id="roomUpgradeEnabled{{ $upgradeIndex }}"
                            @checked((int) ($upgradeRow['enabled'] ?? 0) === 1)>
                        <label class="form-check-label fw-bold" for="roomUpgradeEnabled{{ $upgradeIndex }}">
                            Mã này có quyền lợi nâng hạng phòng
                        </label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kiểu nâng hạng</label>
                            <select name="room_upgrade_offers[{{ $upgradeIndex }}][upgrade_kind]" class="form-select room-upgrade-kind">
                                <option value="incident_support" @selected(($upgradeRow['upgrade_kind'] ?? '') === 'incident_support')>
                                    Hỗ trợ do sự cố - khách không trả thêm
                                </option>
                                <option value="paid_upsell" @selected(($upgradeRow['upgrade_kind'] ?? 'paid_upsell') === 'paid_upsell')>
                                    Mã điều kiện upsell - khách trả phần còn lại
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Từ hạng</label>
                            <select name="room_upgrade_offers[{{ $upgradeIndex }}][from_room_category_id]" class="form-select">
                                <option value="">Tất cả hạng cũ</option>
                                @foreach (($roomCategories ?? collect()) as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($upgradeRow['from_room_category_id'] ?? '') === (string) $category->id)>
                                        {{ $category->name }} - {{ number_format((float) $category->price, 0, ',', '.') }}đ
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sang hạng</label>
                            <select name="room_upgrade_offers[{{ $upgradeIndex }}][to_room_category_id]" class="form-select">
                                <option value="">Tất cả hạng mới</option>
                                @foreach (($roomCategories ?? collect()) as $category)
                                    <option value="{{ $category->id }}" @selected((string) ($upgradeRow['to_room_category_id'] ?? '') === (string) $category->id)>
                                        {{ $category->name }} - {{ number_format((float) $category->price, 0, ',', '.') }}đ
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cách chịu/giảm tiền chênh</label>
                            <select name="room_upgrade_offers[{{ $upgradeIndex }}][cover_type]" class="form-select room-upgrade-cover-type">
                                <option value="full_difference" @selected(($upgradeRow['cover_type'] ?? '') === 'full_difference')>
                                    Chịu toàn bộ tiền chênh
                                </option>
                                <option value="percent_difference" @selected(($upgradeRow['cover_type'] ?? 'percent_difference') === 'percent_difference')>
                                    Giảm % tiền chênh
                                </option>
                                <option value="fixed_amount" @selected(($upgradeRow['cover_type'] ?? '') === 'fixed_amount')>
                                    Giảm số tiền cố định
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Giá trị</label>
                            <input type="number" name="room_upgrade_offers[{{ $upgradeIndex }}][cover_value]"
                                class="form-control room-upgrade-cover-value" min="0" step="0.01"
                                value="{{ $upgradeRow['cover_value'] ?? 20 }}">
                            <div class="promotion-help mt-1">Với %: nhập 20 = giảm 20% phần chênh.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Giới hạn tối đa</label>
                            <input type="number" name="room_upgrade_offers[{{ $upgradeIndex }}][max_cover_amount]"
                                class="form-control" min="0" step="1000"
                                value="{{ $upgradeRow['max_cover_amount'] ?? '' }}" placeholder="Bỏ trống nếu không giới hạn">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Ghi chú nâng hạng</label>
                            <input type="text" name="room_upgrade_offers[{{ $upgradeIndex }}][note]" class="form-control"
                                value="{{ $upgradeRow['note'] ?? '' }}"
                                placeholder="VD: dùng khi phòng lỗi, hết phòng cùng hạng hoặc khuyến khích lên hạng cao hơn">
                        </div>

                        <div class="col-md-4 d-flex align-items-end">
                            <label class="promotion-switch-item w-100 mb-0">
                                <input type="checkbox" name="room_upgrade_offers[{{ $upgradeIndex }}][auto_apply_on_upgrade]" value="1"
                                    @checked((int) ($upgradeRow['auto_apply_on_upgrade'] ?? 0) === 1)>
                                <span class="fw-semibold d-block">Tự gợi ý khi đổi hạng</span>
                                <span class="promotion-help">Dùng để lễ tân dễ chọn mã phù hợp.</span>
                            </label>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="promotion-form-card">
            <h5>Thời gian áp dụng</h5>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bắt đầu cho nhập mã</label>
                    <input type="text" name="valid_from" class="form-control js-vn-datetime"
                        value="{{ old('valid_from', $promotion->valid_from ? $promotion->valid_from->format('d/m/Y H:i') : '') }}"
                        placeholder="dd/mm/yyyy hh:mm" autocomplete="off">
                    <div class="promotion-help mt-1">Ví dụ: 21/06/2026 14:00. Dùng giờ 24h.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Kết thúc cho nhập mã</label>
                    <input type="text" name="valid_to" class="form-control js-vn-datetime"
                        value="{{ old('valid_to', $promotion->valid_to ? $promotion->valid_to->format('d/m/Y H:i') : '') }}"
                        placeholder="dd/mm/yyyy hh:mm" autocomplete="off">
                    <div class="promotion-help mt-1">Để trống nếu không giới hạn thời gian nhập mã.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày lưu trú bắt đầu được áp dụng</label>
                    <input type="text" name="stay_from" class="form-control js-vn-date"
                        value="{{ old('stay_from', $promotion->stay_from ? $promotion->stay_from->format('d/m/Y') : '') }}"
                        placeholder="dd/mm/yyyy" autocomplete="off">
                    <div class="promotion-help mt-1">Dùng cho mã sự kiện/lễ, mùa cao điểm, dịp cụ thể.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ngày lưu trú cuối cùng được áp dụng</label>
                    <input type="text" name="stay_to" class="form-control js-vn-date"
                        value="{{ old('stay_to', $promotion->stay_to ? $promotion->stay_to->format('d/m/Y') : '') }}"
                        placeholder="dd/mm/yyyy" autocomplete="off">
                    <div class="promotion-help mt-1">Để trống nếu không giới hạn ngày lưu trú.</div>
                </div>
            </div>
        </div>

        <div class="promotion-form-card">
            <h5>Điều kiện booking</h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Đơn từ</label>
                    <input type="number" name="min_booking_amount" class="form-control"
                        value="{{ old('min_booking_amount', $promotion->min_booking_amount ?? 0) }}" min="0" step="1000">
                    <div class="promotion-help mt-1">0 = không yêu cầu.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Số đêm tối thiểu</label>
                    <input type="number" name="min_nights" class="form-control"
                        value="{{ old('min_nights', $promotion->min_nights ?? 0) }}" min="0">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Số phòng tối thiểu</label>
                    <input type="number" name="min_rooms" class="form-control"
                        value="{{ old('min_rooms', $promotion->min_rooms ?? 0) }}" min="0">
                </div>
            </div>
        </div>

        <div class="promotion-form-card">
            <h5>Điều kiện khách hàng</h5>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Đã hoàn thành tối thiểu</label>
                    <input type="number" name="min_completed_bookings" class="form-control"
                        value="{{ old('min_completed_bookings', $promotion->min_completed_bookings ?? 0) }}" min="0">
                    <div class="promotion-help mt-1">Số đơn đã hoàn tất.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Đã chi tiêu tối thiểu</label>
                    <input type="number" name="min_total_spent" class="form-control"
                        value="{{ old('min_total_spent', $promotion->min_total_spent ?? 0) }}" min="0" step="1000">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Mỗi khách dùng tối đa</label>
                    <input type="number" name="per_customer_limit" class="form-control"
                        value="{{ old('per_customer_limit', $promotion->per_customer_limit) }}" min="1"
                        placeholder="Bỏ trống = không giới hạn">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="promotion-form-card">
            <h5>Quyền sử dụng</h5>

            <div class="promotion-switch-grid">
                <label class="promotion-switch-item">
                    <input type="checkbox" name="is_public" id="isPublic" value="1"
                        @checked(old('is_public', $promotion->is_public ?? true))>
                    <span class="fw-semibold d-block">Hiện cho user</span>
                    <span class="promotion-help">User có thể nhìn thấy mã nếu đủ điều kiện.</span>
                </label>

                <label class="promotion-switch-item">
                    <input type="checkbox" name="user_can_apply" id="userCanApply" value="1"
                        @checked(old('user_can_apply', $promotion->user_can_apply ?? true))>
                    <span class="fw-semibold d-block">User tự áp dụng</span>
                    <span class="promotion-help">Cho khách tự chọn ở trang xác nhận.</span>
                </label>

                <label class="promotion-switch-item">
                    <input type="checkbox" name="admin_can_apply" id="adminCanApply" value="1"
                        @checked(old('admin_can_apply', $promotion->admin_can_apply ?? true))>
                    <span class="fw-semibold d-block">Admin áp dụng</span>
                    <span class="promotion-help">Cho lễ tân/admin chọn khi tạo hoặc hỗ trợ booking.</span>
                </label>

                <label class="promotion-switch-item">
                    <input type="checkbox" name="requires_note" id="requiresNote" value="1"
                        @checked(old('requires_note', $promotion->requires_note ?? false))>
                    <span class="fw-semibold d-block">Bắt buộc nhập lý do</span>
                    <span class="promotion-help">Nên bật cho mã hỗ trợ khách.</span>
                </label>

                <label class="promotion-switch-item">
                    <input type="checkbox" name="is_stackable" id="isStackable" value="1"
                        @checked(old('is_stackable', $promotion->is_stackable ?? true))>
                    <span class="fw-semibold d-block">Cho dùng chung</span>
                    <span class="promotion-help">Có thể chọn chung với mã khác.</span>
                </label>
            </div>
        </div>

        <div class="promotion-form-card">
            <h5>Giới hạn lượt dùng</h5>

            <div class="mb-3">
                <label class="form-label">Tổng lượt dùng toàn hệ thống</label>
                <input type="number" name="usage_limit" class="form-control"
                    value="{{ old('usage_limit', $promotion->usage_limit) }}" min="1"
                    placeholder="Bỏ trống = không giới hạn">
            </div>

            @if ($promotion->exists)
                <div class="alert alert-light border mb-0">
                    <div class="fw-semibold">Đã dùng</div>
                    <div class="fs-5 fw-bold">{{ (int) $promotion->used_count }} lượt</div>
                    <div class="promotion-help">
                        Chỉ số này tự tăng khi mã được áp vào booking, không nên sửa tay.
                    </div>
                </div>
            @endif
        </div>

        <div class="promotion-form-card">
            <h5>Gợi ý cấu hình</h5>

            <div class="promotion-help lh-lg">
                <strong>Mã hỗ trợ</strong>: không hiện cho user, chỉ admin áp dụng, bắt buộc nhập lý do.
                <br>
                <strong>Mã điều kiện</strong>: nên nhập ít nhất một điều kiện như đơn từ, số đêm, số phòng hoặc số đơn đã hoàn thành.
                <br>
                <strong>Mã sự kiện</strong>: nên nhập cả thời gian nhập mã và thời gian lưu trú áp dụng.
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-gold">
                Cập nhật mã ưu đãi
            </button>

            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
                Hủy
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const promotionType = document.getElementById('promotionType');
        const discountType = document.getElementById('discountType');
        const discountValueHelp = document.getElementById('discountValueHelp');
        const maxDiscountBox = document.getElementById('maxDiscountBox');
        const promotionTypeHelp = document.getElementById('promotionTypeHelp');

        const isPublic = document.getElementById('isPublic');
        const userCanApply = document.getElementById('userCanApply');
        const adminCanApply = document.getElementById('adminCanApply');
        const requiresNote = document.getElementById('requiresNote');

        function initPromotionFlatpickr() {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            const vnLocale = flatpickr.l10ns && flatpickr.l10ns.vn
                ? flatpickr.l10ns.vn
                : 'vn';

            flatpickr('.js-vn-datetime', {
                locale: vnLocale,
                enableTime: true,
                time_24hr: true,
                dateFormat: 'd/m/Y H:i',
                allowInput: true,
                minuteIncrement: 5,
                disableMobile: true,
            });

            flatpickr('.js-vn-date', {
                locale: vnLocale,
                dateFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true,
            });
        }

        function updateDiscountUI() {
            if (discountType.value === 'percent') {
                maxDiscountBox.classList.remove('d-none');
                discountValueHelp.textContent = 'Nhập phần trăm giảm, ví dụ 10 = giảm 10%.';
            } else {
                maxDiscountBox.classList.add('d-none');
                discountValueHelp.textContent = 'Nhập số tiền giảm trực tiếp, ví dụ 100000.';
            }
        }

        function updatePromotionTypeUI() {
            if (promotionType.value === 'support_discount') {
                promotionTypeHelp.textContent = 'Mã hỗ trợ chỉ dành cho admin/lễ tân xử lý sự cố, bắt buộc nhập lý do.';
                isPublic.checked = false;
                userCanApply.checked = false;
                adminCanApply.checked = true;
                requiresNote.checked = true;

                isPublic.disabled = true;
                userCanApply.disabled = true;
                adminCanApply.disabled = true;
                requiresNote.disabled = true;
            } else {
                const messages = {
                    normal_discount: 'Mã thường dành cho khách hoặc admin áp dụng như ưu đãi cơ bản.',
                    event_discount: 'Mã sự kiện dùng cho dịp lễ, ngày đặc biệt hoặc giai đoạn được chỉ định.',
                    conditional_discount: 'Mã điều kiện chỉ dùng khi booking/khách đạt yêu cầu tối thiểu.'
                };

                promotionTypeHelp.textContent = messages[promotionType.value] || '';

                isPublic.disabled = false;
                userCanApply.disabled = false;
                adminCanApply.disabled = false;
                requiresNote.disabled = false;
            }
        }


        const serviceOfferRows = document.getElementById('serviceOfferRows');
        const serviceOfferTemplate = document.getElementById('serviceOfferTemplate');
        const addServiceOfferButton = document.getElementById('addServiceOfferButton');
        let serviceOfferIndex = Number(serviceOfferRows?.dataset.nextIndex || 0);

        function refreshServiceOfferRemoveButtons() {
            if (!serviceOfferRows) {
                return;
            }

            const rows = serviceOfferRows.querySelectorAll('[data-service-offer-row]');
            rows.forEach(function (row) {
                const button = row.querySelector('.remove-service-offer-button');
                if (button) {
                    button.disabled = rows.length <= 1;
                }
            });
        }

        if (addServiceOfferButton && serviceOfferTemplate && serviceOfferRows) {
            addServiceOfferButton.addEventListener('click', function () {
                const html = serviceOfferTemplate.innerHTML.replaceAll('__INDEX__', serviceOfferIndex);
                serviceOfferRows.insertAdjacentHTML('beforeend', html);
                serviceOfferIndex++;
                refreshServiceOfferRemoveButtons();
            });
        }

        if (serviceOfferRows) {
            serviceOfferRows.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-service-offer-button');
                if (!button) {
                    return;
                }

                const rows = serviceOfferRows.querySelectorAll('[data-service-offer-row]');
                if (rows.length <= 1) {
                    return;
                }

                button.closest('[data-service-offer-row]').remove();
                refreshServiceOfferRemoveButtons();
            });
        }

        refreshServiceOfferRemoveButtons();



        function updateRoomUpgradeRows() {
            document.querySelectorAll('[data-room-upgrade-row]').forEach(function (row) {
                const enabled = row.querySelector('.room-upgrade-enabled');
                const kind = row.querySelector('.room-upgrade-kind');
                const coverType = row.querySelector('.room-upgrade-cover-type');
                const coverValue = row.querySelector('.room-upgrade-cover-value');

                if (!enabled || !kind || !coverType || !coverValue) {
                    return;
                }

                if (kind.value === 'incident_support') {
                    coverType.value = 'full_difference';
                    coverValue.value = 100;
                    coverType.disabled = true;
                    coverValue.readOnly = true;
                } else {
                    coverType.disabled = false;
                    coverValue.readOnly = false;
                }
            });
        }

        document.querySelectorAll('.room-upgrade-kind').forEach(function (select) {
            select.addEventListener('change', updateRoomUpgradeRows);
        });

        const promotionForm = document.getElementById('promotionForm');

        if (promotionForm) {
            promotionForm.addEventListener('submit', function () {
                isPublic.disabled = false;
                userCanApply.disabled = false;
                adminCanApply.disabled = false;
                requiresNote.disabled = false;
            });
        }

        discountType.addEventListener('change', updateDiscountUI);
        promotionType.addEventListener('change', updatePromotionTypeUI);

        initPromotionFlatpickr();
        updateDiscountUI();
        updatePromotionTypeUI();
        updateRoomUpgradeRows();
    });
</script>

</form>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
@endsection
