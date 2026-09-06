@php
    $stayingGuests = $booking->guests;
    $roomCountForRepresentatives = max(1, $booking->bookingRooms->count());
    $needsGroupRepresentative = $roomCountForRepresentatives > 1;
    $groupRepresentative = $stayingGuests->firstWhere('is_booking_representative', true);
    $roomsMissingRepresentative = $booking->bookingRooms->filter(function ($bookingRoom) use ($stayingGuests) {
        return !$stayingGuests->where('booking_room_id', $bookingRoom->id)
            ->contains(fn ($guest) => $guest->guest_type === 'adult');
    });
    $representativeReady = $roomsMissingRepresentative->isEmpty()
        && (!$needsGroupRepresentative || $stayingGuests->where('is_booking_representative', true)->count() === 1);
    $canEditStayGuests = in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true);
@endphp

<details class="compact-panel mb-3" id="stayingGuestsPanel">
    <summary>
        <span>Điều kiện 3 · Người đại diện từng phòng</span>
        <span class="badge-clean {{ $representativeReady ? 'status-done' : 'status-warning' }}">
            {{ $stayingGuests->where('guest_type', 'adult')->whereNotNull('booking_room_id')->count() }}/{{ $roomCountForRepresentatives }} phòng có đại diện
            @if($needsGroupRepresentative) · {{ $groupRepresentative ? 'đã có đại diện đoàn' : 'thiếu đại diện đoàn' }} @endif
        </span>
    </summary>

    <div class="compact-panel-body">

        @php
            $bookerDocument = trim((string) $booking->booked_customer_cccd);
            $bookerNameNormalized = mb_strtolower(trim((string) $booking->booked_customer_name));
            $bookerMatchingGuests = $stayingGuests->filter(function ($guest) use ($bookerDocument, $bookerNameNormalized) {
                $guestDocument = trim((string) ($guest->document_number ?: $guest->cccd));
                if ($bookerDocument !== '' && $guestDocument !== '') {
                    return $bookerDocument === $guestDocument;
                }

                return $bookerNameNormalized !== ''
                    && mb_strtolower(trim((string) $guest->full_name)) === $bookerNameNormalized;
            });
            $bookerDeclaredGuest = $bookerMatchingGuests->sortByDesc(fn ($guest) => $guest->booking_room_id ? 1 : 0)->first();
            $bookerRoom = $bookerMatchingGuests->first(fn ($guest) => $guest->booking_room_id)?->bookingRoom?->room?->room_number;
            $bookerIsGroupRepresentative = $bookerMatchingGuests->contains(fn ($guest) => (bool) $guest->is_booking_representative);
            $bookerRepresentativeRoomIds = $bookerMatchingGuests
                ->filter(fn ($guest) => !empty($guest->booking_room_id))
                ->pluck('booking_room_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $bookerBirthday = $booking->booked_customer_birthday
                ? \Carbon\Carbon::parse($booking->booked_customer_birthday)->format('d/m/Y')
                : '---';
            $bookerGender = match((string) $booking->booked_customer_gender) {
                'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', default => '---'
            };
        @endphp

        <div class="border rounded-3 p-3 mb-3 bg-light" id="bookerVerificationPanel">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="fw-bold"><i class="bx bx-id-card me-1"></i>Xác minh người đặt phòng</div>
                </div>
                <span class="badge {{ $bookerDeclaredGuest ? 'text-bg-success' : 'text-bg-warning' }}">
                    {{ $bookerDeclaredGuest ? 'Đã gắn vào hồ sơ đại diện' : 'Chưa gắn vào phòng' }}
                </span>
            </div>

            <div class="row g-2 mt-1 small">
                <div class="col-md-4"><span class="text-muted">Họ tên:</span> <strong>{{ $booking->booked_customer_name ?: '---' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">CCCD:</span> <strong>{{ $booking->booked_customer_cccd ?: '---' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Ngày sinh:</span> <strong>{{ $bookerBirthday }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Giới tính:</span> <strong>{{ $bookerGender }}</strong></div>
                <div class="col-md-4"><span class="text-muted">Email:</span> <strong>{{ $booking->booked_customer_email ?: '---' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">SĐT:</span> <strong>{{ $booking->booked_customer_phone ?: '---' }}</strong></div>
                <div class="col-12"><span class="text-muted">Địa chỉ:</span> <strong>{{ $booking->booked_customer_address ?: '---' }}</strong></div>
                @if($bookerDeclaredGuest)
                    <div class="col-12 text-success">
                        @if($bookerRoom && $bookerIsGroupRepresentative)
                            Đang là đại diện phòng {{ $bookerRoom }} và đồng thời là đại diện chung của cả đoàn.
                        @elseif($bookerRoom)
                            Đang là đại diện phòng {{ $bookerRoom }}.
                        @elseif($bookerIsGroupRepresentative)
                            Đang là đại diện chung của cả đoàn.
                        @else
                            Đã có hồ sơ trong booking.
                        @endif
                    </div>
                @endif
            </div>

            @if($canEditStayGuests && $booking->bookingRooms->isNotEmpty())
                <div class="mt-3 border rounded-3 bg-white p-3">
                    <div class="small fw-semibold mb-2">Dùng người đặt làm:</div>
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        @foreach($booking->bookingRooms as $bookingRoom)
                            @php
                                $isBookerRoomRepresentative = in_array((int) $bookingRoom->id, $bookerRepresentativeRoomIds, true);
                            @endphp
                            <div class="form-check mb-0">
                                <input class="form-check-input js-use-booker-as-role" type="checkbox"
                                    id="bookerRoomRepresentative{{ $bookingRoom->id }}"
                                    data-booking-room-id="{{ $bookingRoom->id }}"
                                    @checked($isBookerRoomRepresentative)>
                                <label class="form-check-label" for="bookerRoomRepresentative{{ $bookingRoom->id }}">
                                    Đại diện phòng {{ $bookingRoom->room?->room_number ?? '---' }}
                                </label>
                            </div>
                        @endforeach

                        @if($needsGroupRepresentative)
                            <form method="POST" action="{{ route('admin.bookings.booker-group-representative', $booking) }}"
                                class="d-inline js-group-representative-form">
                                @csrf
                                <input type="hidden" name="enabled" value="{{ $bookerIsGroupRepresentative ? 0 : 1 }}" data-group-representative-enabled>
                                <div class="form-check mb-0">
                                    <input class="form-check-input js-booker-group-representative" type="checkbox"
                                        id="bookerGroupRepresentative" @checked($bookerIsGroupRepresentative)>
                                    <label class="form-check-label fw-semibold" for="bookerGroupRepresentative">
                                        Đại diện đoàn
                                    </label>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if($canEditStayGuests && $roomsMissingRepresentative->isNotEmpty())
            <form id="batchRoomRepresentativesForm" method="POST" action="{{ route('admin.bookings.guests.store', $booking) }}" data-staying-guest-submit data-batch-representatives>
                @csrf
            </form>
        @endif

        @foreach ($booking->bookingRooms as $bookingRoom)
            @php
                $roomRepresentative = $stayingGuests->where('booking_room_id', $bookingRoom->id)
                    ->first(fn ($guest) => $guest->guest_type === 'adult');
                $roomCategory = $bookingRoom->room?->category;
            @endphp
            <div class="border rounded mb-3 overflow-hidden" data-booking-room-representative="{{ $bookingRoom->id }}">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <strong>Phòng {{ $bookingRoom->room?->room_number ?? '---' }}</strong>
                        <span class="text-muted small">· {{ $roomCategory?->name ?? 'Chưa rõ hạng' }}</span>
                    </div>
                    @if($roomRepresentative)
                        <span class="badge text-bg-success">Đã có đại diện</span>
                    @else
                        <span class="badge text-bg-warning">Chưa có đại diện</span>
                    @endif
                </div>

                <div class="p-3 border-top">
                    @if($roomRepresentative)
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                            <div>
                                <div class="fw-bold">{{ $roomRepresentative->full_name }}</div>
                                <div class="small text-muted">
                                    {{ $roomRepresentative->display_document ?: 'Chưa xuất trình giấy tờ' }}
                                    @if($roomRepresentative->is_booking_representative)
                                        · <span class="text-primary fw-semibold">Đại diện cả đoàn</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($canEditStayGuests)
                            <details class="border rounded">
                                <summary class="px-3 py-2 small fw-semibold" style="cursor:pointer">Sửa hồ sơ đại diện</summary>
                                <div class="p-3 border-top">
                                    <form method="POST" action="{{ route('admin.bookings.guests.update', [$booking, $roomRepresentative]) }}" data-staying-guest-submit>
                                        @csrf
                                        @method('PATCH')
                                        @include('admin.pages.bookings.partials.staying-guest-fields', ['editingGuest' => $roomRepresentative, 'defaultBookingRoomId' => $bookingRoom->id])
                                        <button class="btn btn-primary btn-sm mt-3" type="submit">Lưu hồ sơ đại diện</button>
                                    </form>
                                </div>
                            </details>
                        @endif
                    @elseif($canEditStayGuests)
                        @include('admin.pages.bookings.partials.staying-guest-fields', [
                            'editingGuest' => null,
                            'defaultBookingRoomId' => $bookingRoom->id,
                            'batchKey' => (string) $bookingRoom->id,
                            'externalFormId' => 'batchRoomRepresentativesForm',
                        ])
                        <div class="small text-muted mt-2">Nhập xong các phòng còn thiếu rồi lưu một lần ở cuối danh sách.</div>
                    @else
                        <div class="small text-muted">Booking đã kết thúc nên hồ sơ đại diện bị khóa.</div>
                    @endif
                </div>
            </div>
        @endforeach

        @if($canEditStayGuests && $roomsMissingRepresentative->isNotEmpty())
            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-primary" type="submit" form="batchRoomRepresentativesForm">
                    <i class="bx bx-save me-1"></i> Lưu đại diện cho {{ $roomsMissingRepresentative->count() }} phòng
                </button>
            </div>
        @endif

    </div>
</details>

<div id="noDocumentRiskPanel" class="no-document-risk-backdrop d-none" role="dialog" aria-modal="true" aria-labelledby="noDocumentRiskTitle">
    <div class="no-document-risk-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="text-uppercase small fw-bold text-warning mb-1">Cảnh báo lưu trú</div>
                <h5 class="mb-1" id="noDocumentRiskTitle">Người đại diện chưa xuất trình giấy tờ</h5>
                <div class="text-muted small">Phải xác nhận trước khi lưu hồ sơ.</div>
            </div>
            <button type="button" class="btn-close" data-no-document-cancel aria-label="Đóng"></button>
        </div>
        <div class="alert alert-warning py-2 small">
            <strong>Người chưa có giấy tờ hợp lệ:</strong>
            <ul class="mb-0 mt-1" id="noDocumentRiskGuestList"></ul>
        </div>
        <div class="border rounded p-3 bg-light small mb-3">
            Khách xác nhận thông tin đã khai là đúng và sẽ bổ sung giấy tờ khi được yêu cầu. Xác nhận này chỉ là dấu vết vận hành, không thay thế nghĩa vụ khai báo lưu trú theo quy định.
        </div>
        <div class="mb-3">
            <label for="noDocumentRiskReason" class="form-label fw-semibold">Lý do chưa xuất trình giấy tờ <span class="text-danger">*</span></label>
            <textarea id="noDocumentRiskReason" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
        <div class="form-check border rounded p-3 ps-5 mb-3">
            <input class="form-check-input" type="checkbox" id="noDocumentRiskConfirm">
            <label class="form-check-label fw-semibold" for="noDocumentRiskConfirm">Tôi xác nhận đã thông báo và khách đồng ý tiếp tục làm thủ tục.</label>
        </div>
        <div class="small text-danger d-none mb-2" id="noDocumentRiskError"></div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-no-document-cancel>Quay lại</button>
            <button type="button" class="btn btn-warning" id="noDocumentRiskAccept">Xác nhận và lưu</button>
        </div>
    </div>
</div>

@once
<style>
.no-document-risk-backdrop { position: fixed; inset: 0; z-index: 1095; background: rgba(15,23,42,.58); display:flex; align-items:center; justify-content:center; padding:20px; }
.no-document-risk-backdrop.d-none { display:none !important; }
.no-document-risk-card { width:min(680px,100%); max-height:calc(100vh - 40px); overflow-y:auto; background:#fff; border-radius:16px; padding:22px; box-shadow:0 24px 70px rgba(15,23,42,.28); }
body.no-document-risk-open { overflow:hidden; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const riskPanel = document.getElementById('noDocumentRiskPanel');
    const riskGuestList = document.getElementById('noDocumentRiskGuestList');
    const riskReason = document.getElementById('noDocumentRiskReason');
    const riskConfirm = document.getElementById('noDocumentRiskConfirm');
    const riskError = document.getElementById('noDocumentRiskError');
    const riskAccept = document.getElementById('noDocumentRiskAccept');
    let pendingForm = null;
    let pendingRows = [];

    const fieldSelector = (name) => `[name="${name}"], [name$="[${name}]"]`;
    const rowsForForm = (form) => {
        if (!form) return [];
        if (form.matches('[data-batch-representatives]')) {
            return Array.from(document.querySelectorAll('#stayingGuestsPanel [data-guest-form]'))
                .filter(row => row.querySelector(`[form="${CSS.escape(form.id)}"]`));
        }
        const row = form.querySelector('[data-guest-form]');
        return row ? [row] : [];
    };

    const hasValidDocument = (row) => {
        const type = row?.querySelector('.js-document-type')?.value || 'none';
        const number = row?.querySelector('.js-document-number')?.value?.trim() || '';
        return type !== 'none' && number !== '';
    };
    const closeRisk = () => {
        riskPanel?.classList.add('d-none');
        document.body.classList.remove('no-document-risk-open');
        pendingForm = null;
        pendingRows = [];
    };
    document.querySelectorAll('[data-no-document-cancel]').forEach(btn => btn.addEventListener('click', closeRisk));
    riskPanel?.addEventListener('click', e => { if (e.target === riskPanel) closeRisk(); });

    const setRepresentativeFieldValue = (row, selector, value) => {
        const field = row?.querySelector(selector);
        if (!field || value === undefined || value === null || value === '') return;

        const stringValue = String(value);
        if (field._flatpickr && /^\d{4}-\d{2}-\d{2}$/.test(stringValue)) {
            // Admin date fields use Flatpickr with an altInput. Writing only to
            // field.value updates the hidden/original input but leaves the visible
            // date box blank. Keep both in sync.
            field._flatpickr.setDate(stringValue, false, 'Y-m-d');
            field.value = stringValue;
            if (field._flatpickr.altInput) {
                const [year, month, day] = stringValue.split('-');
                field._flatpickr.altInput.value = `${day}/${month}/${year}`;
            }
        } else {
            field.value = stringValue;
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const fillBookerIntoRepresentativeForm = (row, markRoleAutofill = false) => {
        if (!row) return false;

        const setValue = (selector, value) => setRepresentativeFieldValue(row, selector, value);

        setValue(fieldSelector('full_name'), @json($booking->booked_customer_name));
        setValue(fieldSelector('birthday'), @json($booking->booked_customer_birthday ? \Carbon\Carbon::parse($booking->booked_customer_birthday)->format('Y-m-d') : ''));
        setValue(fieldSelector('gender'), @json($booking->booked_customer_gender));
        setValue(fieldSelector('nationality'), 'Việt Nam');
        setValue(fieldSelector('document_type'), @json($booking->booked_customer_cccd ? 'cccd' : 'none'));
        setValue(fieldSelector('document_number'), @json($booking->booked_customer_cccd));
        setValue(fieldSelector('address'), @json($booking->booked_customer_address));

        if (row.querySelector('[data-no-document-ack]')) row.querySelector('[data-no-document-ack]').value = '0';
        if (row.querySelector('[data-no-document-reason]')) row.querySelector('[data-no-document-reason]').value = '';
        if (markRoleAutofill) row.dataset.bookerRoleAutofilled = '1';
        return true;
    };

    const clearBookerRoleAutofill = (row) => {
        if (!row || row.dataset.bookerRoleAutofilled !== '1') return;

        const clearField = (selector, fallback = '') => {
            const field = row.querySelector(selector);
            if (!field) return;

            if (field._flatpickr) {
                field._flatpickr.clear(false);
                field.value = '';
                if (field._flatpickr.altInput) field._flatpickr.altInput.value = '';
            } else {
                field.value = fallback;
            }

            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        };

        clearField(fieldSelector('full_name'));
        clearField(fieldSelector('birthday'));
        clearField(fieldSelector('gender'));
        clearField(fieldSelector('nationality'), 'Việt Nam');
        clearField(fieldSelector('document_type'), 'cccd');
        clearField(fieldSelector('document_number'));
        clearField(fieldSelector('address'));

        if (row.querySelector('[data-no-document-ack]')) row.querySelector('[data-no-document-ack]').value = '0';
        if (row.querySelector('[data-no-document-reason]')) row.querySelector('[data-no-document-reason]').value = '';
        delete row.dataset.bookerRoleAutofilled;
    };

    const bookerRoomRepresentativeCheckboxes = Array.from(document.querySelectorAll('.js-use-booker-as-role'));

    // Một người chỉ được làm đại diện cho một phòng. Vẫn dùng checkbox theo yêu cầu UI,
    // nhưng hành vi là chọn độc quyền: tick phòng mới thì tự bỏ tick các phòng còn lại.
    bookerRoomRepresentativeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            if (!checkbox.checked) {
                // Nếu đây chỉ là lựa chọn vừa tick trên giao diện (chưa lưu), bỏ tick
                // thì phải trả form về rỗng. Hồ sơ đã lưu vẫn được bảo vệ để tránh xoá nhầm.
                if (checkbox.defaultChecked) {
                    checkbox.checked = true;
                    window.alert('Đại diện phòng này đã được lưu. Muốn đổi đại diện, hãy nhập thông tin người mới ở hồ sơ phòng và bấm Lưu.');
                } else {
                    const roomId = checkbox.dataset.bookingRoomId || '';
                    const target = document.querySelector(`[data-booking-room-representative="${CSS.escape(roomId)}"]`);
                    clearBookerRoleAutofill(target?.querySelector('[data-guest-form]'));
                }
                return;
            }

            // Chỉ một phòng được chọn. Khi chuyển sang phòng khác, bỏ tick phòng cũ
            // và xoá luôn dữ liệu người đặt đã tự điền ở form cũ nếu chưa lưu.
            bookerRoomRepresentativeCheckboxes.forEach(other => {
                if (other === checkbox || !other.checked) return;
                other.checked = false;

                const otherRoomId = other.dataset.bookingRoomId || '';
                const otherTarget = document.querySelector(`[data-booking-room-representative="${CSS.escape(otherRoomId)}"]`);
                if (!other.defaultChecked) {
                    clearBookerRoleAutofill(otherTarget?.querySelector('[data-guest-form]'));
                }
            });

            const roomId = checkbox.dataset.bookingRoomId || '';
            const target = document.querySelector(`[data-booking-room-representative="${CSS.escape(roomId)}"]`);
            const row = target?.querySelector('[data-guest-form]');
            if (!row) return;

            const existingName = row.querySelector(fieldSelector('full_name'))?.value?.trim() || '';
            const bookerName = String(@json($booking->booked_customer_name) || '').trim();
            if (existingName && bookerName && existingName.toLocaleLowerCase('vi') !== bookerName.toLocaleLowerCase('vi')) {
                const ok = window.confirm(`Phòng này đang có đại diện ${existingName}. Thay hồ sơ đang hiển thị bằng thông tin người đặt ${bookerName}?`);
                if (!ok) {
                    checkbox.checked = false;
                    return;
                }
            }

            const panel = document.getElementById('stayingGuestsPanel');
            if (panel) panel.open = true;
            fillBookerIntoRepresentativeForm(row, true);
            // Không tự cuộn/focus xuống form: lễ tân có thể chọn checkbox liên tiếp
            // ngay tại khu xác minh mà không phải kéo màn hình ngược lên.
        });
    });

    const groupRepresentativeCheckbox = document.querySelector('.js-booker-group-representative');
    if (groupRepresentativeCheckbox) {
        groupRepresentativeCheckbox.addEventListener('change', () => {
            const form = groupRepresentativeCheckbox.closest('.js-group-representative-form');
            const enabled = form?.querySelector('[data-group-representative-enabled]');
            if (!form || !enabled) return;

            const action = groupRepresentativeCheckbox.checked ? 'chọn' : 'bỏ';
            if (!window.confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} người đặt làm đại diện chung của toàn bộ đoàn?`)) {
                groupRepresentativeCheckbox.checked = !groupRepresentativeCheckbox.checked;
                return;
            }

            enabled.value = groupRepresentativeCheckbox.checked ? '1' : '0';
            form.submit();
        });
    }

    document.querySelectorAll('.js-use-booker-info').forEach(button => {
        button.addEventListener('click', () => {
            const row = button.closest('[data-guest-form]');
            if (!row) return;

            const setValue = (selector, value) => setRepresentativeFieldValue(row, selector, value);

            setValue(fieldSelector('full_name'), button.dataset.fullName);
            setValue(fieldSelector('birthday'), button.dataset.birthday);
            setValue(fieldSelector('gender'), button.dataset.gender);
            setValue(fieldSelector('nationality'), button.dataset.nationality || 'Việt Nam');
            setValue(fieldSelector('document_type'), button.dataset.documentNumber ? 'cccd' : 'none');
            setValue(fieldSelector('document_number'), button.dataset.documentNumber);
            setValue(fieldSelector('address'), button.dataset.address);

            if (row.querySelector('[data-no-document-ack]')) row.querySelector('[data-no-document-ack]').value = '0';
            if (row.querySelector('[data-no-document-reason]')) row.querySelector('[data-no-document-reason]').value = '';
        });
    });

    document.querySelectorAll('[data-staying-guest-submit]').forEach(form => {
        form.addEventListener('submit', event => {
            const rows = rowsForForm(form);
            const riskyRows = rows.filter(row => {
                const name = row.querySelector(fieldSelector('full_name'))?.value?.trim() || '';
                const ack = row.querySelector('[data-no-document-ack]')?.value === '1';
                const reason = row.querySelector('[data-no-document-reason]')?.value?.trim() || '';
                return name && !hasValidDocument(row) && (!ack || !reason);
            });

            if (riskyRows.length) {
                event.preventDefault();
                pendingForm = form;
                pendingRows = riskyRows;
                if (riskGuestList) {
                    riskGuestList.innerHTML = riskyRows
                        .map(row => `<li>${row.querySelector(fieldSelector('full_name'))?.value?.trim() || 'Chưa nhập tên'}</li>`)
                        .join('');
                }
                if (riskReason) riskReason.value = '';
                if (riskConfirm) riskConfirm.checked = false;
                riskError?.classList.add('d-none');
                riskPanel?.classList.remove('d-none');
                document.body.classList.add('no-document-risk-open');
                riskReason?.focus();
            }
        });
    });

    document.addEventListener('input', event => {
        if (!event.target.matches('.js-document-type, .js-document-number')) return;
        const row = event.target.closest('[data-guest-form]');
        if (row?.querySelector('[data-no-document-ack]')) row.querySelector('[data-no-document-ack]').value = '0';
        if (row?.querySelector('[data-no-document-reason]')) row.querySelector('[data-no-document-reason]').value = '';
    });

    riskAccept?.addEventListener('click', () => {
        const reason = riskReason?.value?.trim() || '';
        if (!reason || !riskConfirm?.checked) {
            if (riskError) {
                riskError.textContent = !reason ? 'Vui lòng ghi lý do.' : 'Phải tích xác nhận đã thông báo cho khách.';
                riskError.classList.remove('d-none');
            }
            return;
        }
        pendingRows.forEach(row => {
            if (row.querySelector('[data-no-document-ack]')) row.querySelector('[data-no-document-ack]').value = '1';
            if (row.querySelector('[data-no-document-reason]')) row.querySelector('[data-no-document-reason]').value = reason;
        });
        const form = pendingForm;
        closeRisk();
        form?.requestSubmit();
    });
});
</script>
@endonce
