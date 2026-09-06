@extends('layouts.user')

@section('title', 'Chỉnh sửa đơn trước thanh toán')

@section('content')
@php
    $selectedPromotionCodes = collect(old('promotion_codes', $selectedPromotionCodes ?? []))
        ->map(fn ($code) => strtoupper((string) $code));
    $selectedServices = collect($selectedServices ?? []);
    $currentTotal = (float) ($booking->estimated_total ?? 0);
    $promotionSelectionRules = $promotionSelectionRules ?? [];
    $serviceGroupLabels = $serviceGroupLabels ?? \App\Models\Service::groupLabels();
@endphp

<style>
    .prepay-edit-shell{max-width:1240px;margin:0 auto;padding:28px 16px 48px}
    .prepay-edit-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:22px}
    .prepay-edit-head h1{font-size:30px;font-weight:800;color:#0b1d38;margin:0 0 6px}
    .prepay-edit-head p{margin:0;color:#667085;max-width:780px}
    .prepay-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:22px;align-items:start}
    .prepay-card{background:#fff;border:1px solid #e5eaf0;border-radius:16px;box-shadow:0 8px 24px rgba(15,23,42,.05);padding:22px;margin-bottom:18px}
    .prepay-card h2{font-size:18px;font-weight:800;color:#10233f;margin:0 0 16px}
    .prepay-section-note{font-size:13px;color:#788397;margin-top:-8px;margin-bottom:16px}
    .prepay-label{font-weight:700;font-size:13px;color:#344054;margin-bottom:7px}
    .prepay-summary{position:sticky;top:88px}.prepay-summary .amount{font-size:28px;font-weight:900;color:#0b1d38}
    .prepay-summary-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px dashed #e4e8ee;font-size:13px}.prepay-summary-row:last-child{border-bottom:0}
    .prepay-warning{border-radius:12px;background:#fff8e6;border:1px solid #f5d88d;color:#78570b;padding:12px 14px;font-size:13px}
    .prepay-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}.prepay-actions .btn{min-height:44px;font-weight:700}
    .prepay-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:#edf5ff;color:#24558f;font-size:12px;font-weight:800}

    .prepay-picker{border:1px solid #e5eaf0;border-radius:14px;background:#fbfcfe;overflow:hidden}
    .prepay-picker>summary{list-style:none;cursor:pointer;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:14px;background:#fff;font-weight:800;color:#132846}
    .prepay-picker>summary::-webkit-details-marker{display:none}.prepay-picker>summary::after{content:'›';font-size:24px;line-height:1;transform:rotate(90deg);transition:.18s ease;color:#8090a5}.prepay-picker[open]>summary::after{transform:rotate(-90deg)}
    .prepay-picker-summary{display:flex;align-items:center;gap:10px;min-width:0}.prepay-picker-summary small{font-weight:600;color:#7b8798;display:block;margin-top:2px}
    .prepay-count{display:inline-grid;place-items:center;min-width:28px;height:28px;border-radius:999px;background:#edf5ff;color:#24558f;font-size:12px;font-weight:900}
    .prepay-picker-body{border-top:1px solid #e5eaf0;padding:14px;background:#fff}
    .prepay-toolbar{display:grid;grid-template-columns:minmax(0,1fr) 190px auto;gap:9px;margin-bottom:12px}.prepay-toolbar .form-control,.prepay-toolbar .form-select{min-height:40px}
    .prepay-picker-list{display:grid;gap:8px;max-height:390px;overflow:auto;padding-right:4px;overscroll-behavior:contain}
    .prepay-choice{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;border:1px solid #e5eaf0;border-radius:12px;padding:11px 12px;background:#fbfcfe;transition:.15s ease}
    .prepay-choice:hover{border-color:#b9cae1;background:#fff}.prepay-choice.is-selected{border-color:#8eb5ee;background:#f3f8ff}.prepay-choice.is-disabled{opacity:.58;background:#f5f6f8}.prepay-choice.is-disabled:hover{border-color:#e5eaf0;background:#f5f6f8}.prepay-choice[hidden]{display:none!important}
    .prepay-choice-main{display:flex;align-items:flex-start;gap:10px;min-width:0}.prepay-choice-main .form-check-input{margin-top:4px;flex:0 0 auto}
    .prepay-choice-title{font-weight:800;color:#132846;overflow-wrap:anywhere}.prepay-choice-meta{font-size:12px;color:#667085;margin-top:3px;line-height:1.45}
    .prepay-choice-side{display:flex;align-items:center;gap:8px}.prepay-choice-side .service-qty{width:82px}.prepay-choice-side label{font-size:11px;color:#778397;font-weight:700}
    .prepay-type-badge{display:inline-flex;padding:3px 7px;border-radius:999px;background:#eef2f7;color:#556274;font-size:10px;font-weight:800;margin-left:6px;vertical-align:1px}
    .prepay-empty-filter{padding:24px 12px;text-align:center;color:#7a8799;font-size:13px;border:1px dashed #d8e0ea;border-radius:12px}
    .prepay-rule-box{font-size:12px;line-height:1.55;color:#5c6778;background:#f8fafc;border:1px solid #e5eaf0;border-radius:10px;padding:10px 12px;margin-bottom:12px}

    @media(max-width:991px){.prepay-grid{grid-template-columns:1fr}.prepay-summary{position:static}.prepay-edit-head{flex-direction:column}}
    @media(max-width:640px){.prepay-toolbar{grid-template-columns:1fr}.prepay-choice{grid-template-columns:1fr}.prepay-choice-side{justify-content:flex-end}}
</style>

<div class="prepay-edit-shell">
    <div class="prepay-edit-head">
        <div>
            <div class="prepay-badge mb-2"><i class="bx bx-edit-alt"></i> Đơn {{ $booking->booking_code }}</div>
            <h1>Chỉnh sửa trước khi thanh toán</h1>
            <p>Bạn có thể sửa ngày ở, hạng phòng, thông tin liên hệ, dịch vụ và mã ưu đãi. Link VNPay cũ sẽ hết hiệu lực sau khi lưu.</p>
        </div>
        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Quay lại đơn</a>
    </div>

    <form method="POST" action="{{ route('bookings.update-before-payment', $booking) }}" id="prePaymentEditForm">
        @csrf
        @method('PATCH')

        <div class="prepay-grid">
            <div>
                <section class="prepay-card">
                    <h2>1. Phòng và thời gian lưu trú</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="prepay-label" for="room_category_id">Hạng phòng</label>
                            <select class="form-select @error('room_category_id') is-invalid @enderror" id="room_category_id" name="room_category_id" required>
                                @foreach($roomCategories as $category)
                                    <option value="{{ $category->id }}" data-price="{{ (float) $category->price }}" @selected((int) old('room_category_id', $booking->room_category_id) === (int) $category->id)>
                                        {{ $category->name }} · {{ number_format((float) $category->price, 0, ',', '.') }}đ/đêm
                                    </option>
                                @endforeach
                            </select>
                            @error('room_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2"><label class="prepay-label" for="adult_count">Người lớn</label><input type="number" min="1" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_guests', 60) }}" class="form-control @error('adult_count') is-invalid @enderror" id="adult_count" name="adult_count" value="{{ old('adult_count', $booking->adult_count) }}" required>@error('adult_count')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-2"><label class="prepay-label" for="child_count">Trẻ em</label><input type="number" min="0" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_guests', 60) }}" class="form-control @error('child_count') is-invalid @enderror" id="child_count" name="child_count" value="{{ old('child_count', $booking->child_count ?? 0) }}">@error('child_count')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-2"><label class="prepay-label" for="room_quantity">Số phòng</label><input type="number" min="1" max="{{ (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_rooms', 30) }}" class="form-control @error('room_quantity') is-invalid @enderror" id="room_quantity" name="room_quantity" value="{{ old('room_quantity', max(1, (int) $booking->room_quantity)) }}" required>@error('room_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="prepay-label" for="check_in_date">Ngày nhận phòng</label><input type="date" class="form-control @error('check_in_date') is-invalid @enderror" id="check_in_date" name="check_in_date" value="{{ old('check_in_date', $booking->check_in_date) }}" required>@error('check_in_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="prepay-label" for="check_out_date">Ngày trả phòng</label><input type="date" class="form-control @error('check_out_date') is-invalid @enderror" id="check_out_date" name="check_out_date" value="{{ old('check_out_date', $booking->check_out_date) }}" required>@error('check_out_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                </section>

                <section class="prepay-card">
                    <h2>2. Người đứng tên và liên hệ</h2>
                    <p class="prepay-section-note">CCCD đang đứng tên booking không được đổi sang người khác khi đơn còn hoạt động; các thông tin nhập nhầm khác vẫn có thể sửa.</p>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="prepay-label">Họ</label><input class="form-control" name="last_name" value="{{ old('last_name', $customer->last_name) }}" required></div>
                        <div class="col-md-6"><label class="prepay-label">Tên</label><input class="form-control" name="first_name" value="{{ old('first_name', $customer->first_name) }}" required></div>
                        <div class="col-md-6"><label class="prepay-label">Số điện thoại</label><input class="form-control" name="phone" value="{{ old('phone', $customer->phone) }}" required></div>
                        <div class="col-md-6"><label class="prepay-label">Email</label><input type="email" class="form-control" name="email" value="{{ old('email', $customer->email) }}" required></div>
                        <div class="col-md-4"><label class="prepay-label">CCCD</label><input class="form-control" name="cccd" value="{{ old('cccd', $customer->cccd) }}" readonly></div>
                        <div class="col-md-4"><label class="prepay-label">Ngày sinh</label><input type="date" class="form-control" name="birthday" value="{{ old('birthday', $customer->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('Y-m-d') : '') }}" required></div>
                        <div class="col-md-4"><label class="prepay-label">Giới tính</label><select class="form-select" name="gender" required><option value="male" @selected(old('gender',$customer->gender)==='male')>Nam</option><option value="female" @selected(old('gender',$customer->gender)==='female')>Nữ</option><option value="other" @selected(old('gender',$customer->gender)==='other')>Khác</option></select></div>
                        <div class="col-12"><label class="prepay-label">Địa chỉ</label><textarea class="form-control" rows="2" name="address" required>{{ old('address', $customer->address) }}</textarea></div>
                        <div class="col-12"><label class="prepay-label">Ghi chú booking</label><textarea class="form-control" rows="3" name="note">{{ old('note', $booking->note) }}</textarea></div>
                    </div>
                </section>

                <section class="prepay-card">
                    <h2>3. Dịch vụ</h2>
                    <p class="prepay-section-note">Danh sách được thu gọn để không kéo dài trang khi khách sạn có nhiều dịch vụ. Mở danh sách, tìm kiếm hoặc lọc nhóm khi cần.</p>
                    <details class="prepay-picker" id="servicePicker" @if($selectedServices->isNotEmpty()) open @endif>
                        <summary>
                            <span class="prepay-picker-summary"><span class="prepay-count" id="serviceSelectedBadge">0</span><span>Chọn dịch vụ<small id="servicePickerHint">Chưa chọn dịch vụ</small></span></span>
                        </summary>
                        <div class="prepay-picker-body">
                            <div class="prepay-toolbar">
                                <input type="search" class="form-control" id="serviceSearch" placeholder="Tìm tên dịch vụ..." autocomplete="off">
                                <select class="form-select" id="serviceGroupFilter">
                                    <option value="">Tất cả nhóm</option>
                                    @foreach($serviceGroupLabels as $group => $label)<option value="{{ $group }}">{{ $label }}</option>@endforeach
                                </select>
                                <label class="btn btn-outline-secondary d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input m-0" id="serviceSelectedOnly"> Đã chọn</label>
                            </div>
                            <div class="prepay-picker-list" id="serviceChoiceList">
                                @forelse($services as $index => $service)
                                    @php
                                        $selectedItem = $selectedServices->get($service->id);
                                        $checked = old("services.$index.service_id") !== null
                                            ? (int) old("services.$index.service_id") === (int) $service->id
                                            : (bool) $selectedItem;
                                        $qty = old("services.$index.quantity", $selectedItem?->base_quantity ?? 1);
                                    @endphp
                                    <label class="prepay-choice service-choice {{ $checked ? 'is-selected' : '' }}" data-select-card data-name="{{ mb_strtolower($service->name) }}" data-group="{{ $service->service_group }}">
                                        <span class="prepay-choice-main">
                                            <input class="form-check-input service-check" type="checkbox" name="services[{{ $index }}][service_id]" value="{{ $service->id }}" data-price="{{ (float) $service->price }}" @checked($checked)>
                                            <span><span class="prepay-choice-title">{{ $service->name }}</span><span class="prepay-type-badge">{{ $service->group_label }}</span><span class="prepay-choice-meta d-block">{{ number_format((float) $service->price, 0, ',', '.') }}đ · {{ $service->billing_rule_label }}</span></span>
                                        </span>
                                        <span class="prepay-choice-side"><label>Số lượng</label><input type="number" min="1" max="50" class="form-control form-control-sm service-qty" name="services[{{ $index }}][quantity]" value="{{ $qty }}" @disabled(!$checked)></span>
                                    </label>
                                @empty
                                    <div class="prepay-empty-filter">Hiện chưa có dịch vụ đang mở bán.</div>
                                @endforelse
                            </div>
                            <div class="prepay-empty-filter mt-2 d-none" id="serviceFilterEmpty">Không có dịch vụ phù hợp bộ lọc.</div>
                        </div>
                    </details>
                </section>

                <section class="prepay-card">
                    <h2>4. Mã ưu đãi</h2>
                    <p class="prepay-section-note">Danh sách được thu gọn và có tìm kiếm. Giới hạn chọn mã ở đây dùng cùng quy tắc với backend, nên không thể chọn vượt số lượng của từng loại.</p>
                    <div class="prepay-rule-box">
                        @foreach($promotionSelectionRules as $type => $rule)
                            @if($type !== \App\Models\Promotion::TYPE_SUPPORT && $rule['limit'] !== null)
                                <span class="me-3"><strong>{{ $rule['label'] }}:</strong> tối đa {{ $rule['limit'] }}</span>
                            @endif
                        @endforeach
                        <span>Mã ghi “chỉ dùng một mình” không thể đi cùng mã khác.</span>
                    </div>
                    <details class="prepay-picker" id="promotionPicker" @if($selectedPromotionCodes->isNotEmpty()) open @endif>
                        <summary>
                            <span class="prepay-picker-summary"><span class="prepay-count" id="promoSelectedBadge">{{ $selectedPromotionCodes->count() }}</span><span>Chọn mã ưu đãi<small id="promotionPickerHint">{{ $selectedPromotionCodes->isNotEmpty() ? 'Đang chọn: '.$selectedPromotionCodes->implode(', ') : 'Chưa chọn mã nào' }}</small></span></span>
                        </summary>
                        <div class="prepay-picker-body">
                            <div class="prepay-toolbar">
                                <input type="search" class="form-control" id="promotionSearch" placeholder="Tìm mã hoặc tên ưu đãi..." autocomplete="off">
                                <select class="form-select" id="promotionTypeFilter"><option value="">Tất cả loại mã</option>@foreach($promotionSelectionRules as $type => $rule)@if($type !== \App\Models\Promotion::TYPE_SUPPORT)<option value="{{ $type }}">{{ $rule['label'] }}</option>@endif @endforeach</select>
                                <label class="btn btn-outline-secondary d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input m-0" id="promotionSelectedOnly"> Đã chọn</label>
                            </div>
                            <div class="prepay-picker-list" id="promotionChoiceList">
                                @forelse($availablePromotions as $promotion)
                                    @php
                                        $promoChecked = $selectedPromotionCodes->contains(strtoupper((string) $promotion->code));
                                        $typeRule = $promotionSelectionRules[$promotion->promotion_type] ?? ['label' => $promotion->type_label, 'limit' => null];
                                    @endphp
                                    <label class="prepay-choice promo-choice {{ $promoChecked ? 'is-selected' : '' }}" data-select-card data-name="{{ mb_strtolower($promotion->code.' '.$promotion->name) }}" data-type="{{ $promotion->promotion_type }}">
                                        <span class="prepay-choice-main">
                                            <input class="form-check-input promo-check" type="checkbox" name="promotion_codes[]" value="{{ $promotion->code }}" data-code="{{ $promotion->code }}" data-type="{{ $promotion->promotion_type }}" data-type-limit="{{ $typeRule['limit'] ?? '' }}" data-stackable="{{ $promotion->is_stackable ? 1 : 0 }}" @checked($promoChecked)>
                                            <span><span class="prepay-choice-title">{{ $promotion->code }}</span><span class="prepay-type-badge">{{ $typeRule['label'] }}</span><span class="prepay-choice-meta d-block"><strong>{{ $promotion->name }}</strong> · {{ $promotion->discount_label }}@if((float)$promotion->min_booking_amount>0) · Đơn từ {{ number_format((float)$promotion->min_booking_amount,0,',','.') }}đ @endif · {{ $promotion->is_stackable ? 'Có thể kết hợp' : 'Chỉ dùng một mình' }}</span></span>
                                        </span>
                                    </label>
                                @empty
                                    <div class="prepay-empty-filter">Hiện không có mã ưu đãi công khai đang hoạt động.</div>
                                @endforelse
                            </div>
                            <div class="prepay-empty-filter mt-2 d-none" id="promotionFilterEmpty">Không có mã phù hợp bộ lọc.</div>
                        </div>
                    </details>
                </section>
            </div>

            <aside class="prepay-summary">
                <div class="prepay-card">
                    <div class="small text-muted mb-1">Tổng hiện tại</div>
                    <div class="amount">{{ number_format($currentTotal, 0, ',', '.') }}đ</div>
                    <div class="prepay-summary-row"><span>Hạng phòng</span><strong id="summaryRoom">{{ $booking->roomCategory->name ?? '---' }}</strong></div>
                    <div class="prepay-summary-row"><span>Số phòng</span><strong>{{ max(1, (int) $booking->room_quantity) }}</strong></div>
                    <div class="prepay-summary-row"><span>Số đêm</span><strong id="summaryNights">{{ max(1, \Carbon\Carbon::parse($booking->check_in_date)->diffInDays(\Carbon\Carbon::parse($booking->check_out_date))) }}</strong></div>
                    <div class="prepay-summary-row"><span>Dịch vụ đã chọn</span><strong id="summaryServices">0</strong></div>
                    <div class="prepay-summary-row"><span>Mã ưu đãi</span><strong id="summaryPromos">{{ $selectedPromotionCodes->count() }}</strong></div>
                    <div class="prepay-warning mt-3"><i class="bx bx-info-circle me-1"></i>Số tiền chính xác được backend tính lại khi bấm Lưu. Link VNPay cũ sẽ không còn dùng được sau khi đơn thay đổi.</div>
                    <div class="prepay-actions"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bx bx-save me-1"></i>Lưu và tính lại</button><a class="btn btn-outline-secondary" href="{{ route('bookings.show', $booking) }}">Bỏ qua</a></div>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roomSelect = document.getElementById('room_category_id');
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');

    function addOneStayDate(dateValue) {
        if (!dateValue) return '';
        const date = new Date(`${dateValue}T00:00:00`);
        if (Number.isNaN(date.getTime())) return '';
        date.setDate(date.getDate() + 1);
        const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 10);
    }

    function syncStayDateRange() {
        if (!checkIn || !checkOut || !checkIn.value) return;
        const minimumCheckout = addOneStayDate(checkIn.value);
        checkOut.min = minimumCheckout;
        if (checkOut._flatpickr) checkOut._flatpickr.set('minDate', minimumCheckout);
        if (!checkOut.value || checkOut.value <= checkIn.value) {
            checkOut.value = minimumCheckout;
            if (checkOut._flatpickr) checkOut._flatpickr.setDate(minimumCheckout, false);
        }
    }

    checkIn?.addEventListener('change', syncStayDateRange);
    checkIn?.addEventListener('project-date-change', syncStayDateRange);
    syncStayDateRange();

    const serviceChecks = Array.from(document.querySelectorAll('.service-check'));
    const promoChecks = Array.from(document.querySelectorAll('.promo-check'));
    const promotionRules = @json($promotionSelectionRules);

    function toast(message, type = 'info') {
        if (window.AppToast && typeof window.AppToast.show === 'function') {
            window.AppToast.show(message, type);
        }
    }

    function updateCard(input) {
        const card = input.closest('[data-select-card]');
        if (card) card.classList.toggle('is-selected', input.checked);
        if (input.classList.contains('service-check')) {
            const qty = card?.querySelector('.service-qty');
            if (qty) qty.disabled = !input.checked;
        }
    }

    function refreshSummary() {
        const selected = roomSelect?.options[roomSelect.selectedIndex];
        if (document.getElementById('summaryRoom')) document.getElementById('summaryRoom').textContent = selected ? selected.textContent.split('·')[0].trim() : '---';
        if (checkIn?.value && checkOut?.value) {
            const from = new Date(checkIn.value + 'T00:00:00');
            const to = new Date(checkOut.value + 'T00:00:00');
            const days = Math.max(0, Math.round((to - from) / 86400000));
            document.getElementById('summaryNights').textContent = days || '—';
        }
        const selectedServiceCount = serviceChecks.filter(el => el.checked).length;
        const selectedPromos = promoChecks.filter(el => el.checked);
        document.getElementById('summaryServices').textContent = selectedServiceCount;
        document.getElementById('summaryPromos').textContent = selectedPromos.length;
        document.getElementById('serviceSelectedBadge').textContent = selectedServiceCount;
        document.getElementById('servicePickerHint').textContent = selectedServiceCount ? `Đã chọn ${selectedServiceCount} dịch vụ` : 'Chưa chọn dịch vụ';
        document.getElementById('promoSelectedBadge').textContent = selectedPromos.length;
        document.getElementById('promotionPickerHint').textContent = selectedPromos.length ? 'Đang chọn: ' + selectedPromos.map(el => el.dataset.code || el.value).join(', ') : 'Chưa chọn mã nào';
    }

    function filterChoices(kind) {
        const isService = kind === 'service';
        const search = document.getElementById(isService ? 'serviceSearch' : 'promotionSearch')?.value.trim().toLocaleLowerCase('vi') || '';
        const filter = document.getElementById(isService ? 'serviceGroupFilter' : 'promotionTypeFilter')?.value || '';
        const selectedOnly = document.getElementById(isService ? 'serviceSelectedOnly' : 'promotionSelectedOnly')?.checked || false;
        const choices = Array.from(document.querySelectorAll(isService ? '.service-choice' : '.promo-choice'));
        let visible = 0;
        choices.forEach(choice => {
            const checkbox = choice.querySelector(isService ? '.service-check' : '.promo-check');
            const nameMatch = !search || (choice.dataset.name || '').includes(search);
            const typeValue = isService ? choice.dataset.group : choice.dataset.type;
            const typeMatch = !filter || typeValue === filter;
            const selectedMatch = !selectedOnly || checkbox?.checked;
            choice.hidden = !(nameMatch && typeMatch && selectedMatch);
            if (!choice.hidden) visible++;
        });
        document.getElementById(isService ? 'serviceFilterEmpty' : 'promotionFilterEmpty')?.classList.toggle('d-none', visible > 0 || choices.length === 0);
    }

    function promotionLimitFor(input) {
        const type = input?.dataset.type || '';
        const rule = promotionRules[type] || {};
        const raw = rule.limit ?? input?.dataset.typeLimit ?? null;
        return raw === null || raw === undefined || raw === '' ? null : Number(raw);
    }

    function refreshPromotionAvailability() {
        const checked = promoChecks.filter(item => item.checked);
        const selectedSolo = checked.find(item => item.dataset.stackable === '0') || null;

        promoChecks.forEach(item => {
            if (item.checked) {
                item.disabled = false;
                item.closest('[data-select-card]')?.classList.remove('is-disabled');
                item.removeAttribute('title');
                return;
            }

            let disabled = false;
            let reason = '';

            if (selectedSolo) {
                disabled = true;
                reason = `Mã ${selectedSolo.dataset.code || selectedSolo.value} đang được chọn và chỉ được dùng một mình.`;
            } else if (item.dataset.stackable === '0' && checked.length > 0) {
                disabled = true;
                reason = 'Mã này chỉ dùng một mình. Hãy bỏ các mã đang chọn trước.';
            } else {
                const limit = promotionLimitFor(item);
                if (limit !== null) {
                    const sameTypeCount = checked.filter(selected => selected.dataset.type === item.dataset.type).length;
                    if (sameTypeCount >= limit) {
                        disabled = true;
                        const rule = promotionRules[item.dataset.type] || {};
                        reason = `${rule.label || 'Loại mã này'} chỉ được chọn tối đa ${limit} mã.`;
                    }
                }
            }

            item.disabled = disabled;
            const card = item.closest('[data-select-card]');
            card?.classList.toggle('is-disabled', disabled);
            if (disabled) {
                item.title = reason;
                card?.setAttribute('title', reason);
            } else {
                item.removeAttribute('title');
                card?.removeAttribute('title');
            }
        });
    }

    function enforcePromotionRules(changed) {
        if (!changed.checked) return true;

        const selectedOthers = promoChecks.filter(item => item.checked && item !== changed);
        const code = changed.dataset.code || changed.value;

        if (changed.dataset.stackable === '0' && selectedOthers.length > 0) {
            changed.checked = false;
            toast(`Mã ${code} chỉ được dùng một mình. Hãy bỏ các mã khác trước.`, 'warning');
            return false;
        }

        const anotherSolo = selectedOthers.find(item => item.dataset.stackable === '0');
        if (anotherSolo) {
            changed.checked = false;
            toast(`Mã ${anotherSolo.dataset.code || anotherSolo.value} đang được chọn và chỉ được dùng một mình.`, 'warning');
            return false;
        }

        const limit = promotionLimitFor(changed);
        if (limit !== null) {
            const selectedSameType = promoChecks.filter(item => item.checked && item.dataset.type === changed.dataset.type);
            if (selectedSameType.length > limit) {
                changed.checked = false;
                const rule = promotionRules[changed.dataset.type] || {};
                toast(`${rule.label || 'Loại mã này'} chỉ được chọn tối đa ${limit} mã.`, 'warning');
                return false;
            }
        }

        return true;
    }

    serviceChecks.forEach(input => input.addEventListener('change', function () { updateCard(this); refreshSummary(); filterChoices('service'); }));
    promoChecks.forEach(input => input.addEventListener('change', function () { enforcePromotionRules(this); updateCard(this); refreshPromotionAvailability(); refreshSummary(); filterChoices('promotion'); }));
    [roomSelect, checkIn, checkOut].forEach(input => input?.addEventListener('change', refreshSummary));

    ['serviceSearch','serviceGroupFilter','serviceSelectedOnly'].forEach(id => document.getElementById(id)?.addEventListener(id === 'serviceSearch' ? 'input' : 'change', () => filterChoices('service')));
    ['promotionSearch','promotionTypeFilter','promotionSelectedOnly'].forEach(id => document.getElementById(id)?.addEventListener(id === 'promotionSearch' ? 'input' : 'change', () => filterChoices('promotion')));

    serviceChecks.forEach(updateCard); promoChecks.forEach(updateCard); refreshPromotionAvailability(); refreshSummary(); filterChoices('service'); filterChoices('promotion');
});
</script>
@endsection
