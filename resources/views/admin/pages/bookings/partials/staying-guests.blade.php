@php
    $stayingGuests = $booking->guests;
    $declaredAdults = $stayingGuests->where('guest_type', 'adult')->count();
    $declaredChildren = $stayingGuests->where('guest_type', 'child')->count();
    $declaredInfants = $stayingGuests->where('guest_type', 'infant')->count();
    $declaredTotal = $stayingGuests->count();
    $expectedTotal = (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0);
    $adultGuests = $stayingGuests->where('guest_type', 'adult');
    $procedureGuestAlreadyDeclared = $stayingGuests->contains(function ($guest) use ($booking) {
        $bookingDocument = trim((string) $booking->booked_customer_cccd);
        $guestDocument = trim((string) ($guest->document_number ?: $guest->cccd));
        if ($bookingDocument !== '' && $guestDocument !== '') {
            return $bookingDocument === $guestDocument;
        }

        return mb_strtolower(trim((string) $guest->full_name)) === mb_strtolower(trim((string) $booking->booked_customer_name));
    });
    $canEditStayGuests = in_array($booking->status, ['confirmed', 'checked_in'], true);
    $guestTypeLabels = ['adult' => 'Người lớn', 'child' => 'Trẻ em', 'infant' => 'Em bé'];
    $documentTypeLabels = [
        'cccd' => 'CCCD',
        'passport' => 'Hộ chiếu',
        'birth_certificate' => 'Giấy khai sinh',
        'personal_id' => 'Mã định danh',
        'other' => 'Giấy tờ khác',
        'none' => 'Chưa xuất trình giấy tờ',
    ];
@endphp

<details class="compact-panel mb-3" id="stayingGuestsPanel">
    <summary>
        <span>Khai báo toàn bộ khách lưu trú</span>
        <span class="badge-clean {{ $declaredTotal >= $expectedTotal ? 'status-done' : 'status-warning' }}">
            {{ $declaredTotal }} khách đã khai · {{ $declaredAdults }} NL / {{ $declaredChildren }} TE / {{ $declaredInfants }} EB
        </span>
    </summary>

    <div class="compact-panel-body">

        @if(session('capacity_warning'))
            <div class="alert alert-danger py-2 small">
                <strong>Cảnh báo sức chứa:</strong> {{ session('capacity_warning') }}
            </div>
        @endif

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                    <div class="text-muted small">Dự kiến khi đặt</div>
                    <strong>{{ $booking->adult_count }} NL / {{ $booking->child_count }} TE / {{ $booking->baby_count ?? 0 }} EB</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                    <div class="text-muted small">Đã khai báo</div>
                    <strong>{{ $declaredAdults }} NL / {{ $declaredChildren }} TE / {{ $declaredInfants }} EB</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                    <div class="text-muted small">Người đại diện đoàn</div>
                    <strong>{{ $stayingGuests->firstWhere('is_booking_representative', true)?->full_name ?? 'Chưa chọn' }}</strong>
                </div>
            </div>
        </div>

        @foreach ($booking->bookingRooms as $bookingRoom)
            @php
                $roomGuests = $stayingGuests->where('booking_room_id', $bookingRoom->id);
                $roomAdults = $roomGuests->where('guest_type', 'adult')->count();
                $roomChildren = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();
                $roomCategory = $bookingRoom->room?->category;
                $adultCapacity = (int) ($roomCategory?->adult_capacity ?? 0);
                $childCapacity = (int) ($roomCategory?->child_capacity ?? 0);
                $adultOver = max(0, $roomAdults - $adultCapacity);
                $childOver = max(0, $roomChildren - $childCapacity);
                $isRoomOverCapacity = $adultOver > 0 || $childOver > 0;
            @endphp
            <div class="border rounded mb-3 overflow-hidden {{ $isRoomOverCapacity ? 'border-danger' : '' }}">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 bg-light border-bottom">
                    <div>
                        <strong>Phòng {{ $bookingRoom->room?->room_number ?? '---' }}</strong>
                        <span class="text-muted small">· {{ $roomCategory?->name ?? 'Chưa rõ hạng' }}</span>
                    </div>
                    <span class="badge {{ $isRoomOverCapacity ? 'text-bg-danger' : 'text-bg-light border' }}">
                        {{ $roomGuests->count() }} khách · {{ $roomAdults }}/{{ $adultCapacity }} NL · {{ $roomChildren }}/{{ $childCapacity }} TE/EB
                    </span>
                </div>

                <div class="p-3">
                    @if($isRoomOverCapacity)
                        <div class="alert alert-danger py-2 small mb-2">
                            <strong>Phòng đang vượt sức chứa:</strong>
                            @if($adultOver > 0) vượt {{ $adultOver }} người lớn. @endif
                            @if($childOver > 0) vượt {{ $childOver }} trẻ em/em bé. @endif
                            Trước check-in phải thu phụ phí, thêm phòng, đổi hạng hoặc phân lại khách.
                        </div>
                    @endif
                    @forelse ($roomGuests as $guest)
                        <details class="border rounded mb-2">
                            <summary class="px-3 py-2 d-flex justify-content-between align-items-center gap-2">
                                <span>
                                    <strong>{{ $guest->full_name }}</strong>
                                    <span class="text-muted small">· {{ $guestTypeLabels[$guest->guest_type] ?? $guest->guest_type }}</span>
                                    @if($guest->is_booking_representative)
                                        <span class="badge text-bg-primary ms-1">Đại diện đoàn</span>
                                    @endif
                                </span>
                                <span class="small text-muted">{{ $guest->display_document ?: ($documentTypeLabels[$guest->document_type] ?? 'Chưa có giấy tờ') }}</span>
                            </summary>

                            <div class="p-3 border-top">
                                @if($canEditStayGuests)
                                    <form method="POST" action="{{ route('admin.bookings.guests.update', [$booking, $guest]) }}" data-staying-guest-submit>
                                        @csrf
                                        @method('PATCH')
                                        @include('admin.pages.bookings.partials.staying-guest-fields', ['editingGuest' => $guest, 'defaultBookingRoomId' => $bookingRoom->id])
                                        <div class="d-flex justify-content-between gap-2 mt-3">
                                            <button class="btn btn-primary btn-sm" type="submit">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('admin.bookings.guests.destroy', [$booking, $guest]) }}" class="mt-2" data-staying-guest-submit onsubmit="return confirm('Xóa khách này khỏi danh sách lưu trú?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Xóa khách</button>
                                    </form>
                                @else
                                    <div class="small text-muted">Hồ sơ đã khóa sau khi kỳ lưu trú kết thúc.</div>
                                @endif
                            </div>
                        </details>
                    @empty
                        <div class="text-muted small">Chưa khai khách nào cho phòng này.</div>
                    @endforelse
                </div>
            </div>
        @endforeach

        @if($canEditStayGuests)
            <div class="border rounded p-3 bg-light" id="batchGuestEntry">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
                    <h6 class="fw-bold mb-1">Khai báo khách lưu trú</h6>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="procedureGuestIsStaying"
                                   @checked($procedureGuestAlreadyDeclared) @disabled($procedureGuestAlreadyDeclared)>
                            <label class="form-check-label small fw-semibold" for="procedureGuestIsStaying">
                                Người làm thủ tục có lưu trú
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="fillBookingRepresentativeGuest" disabled>
                            {{ $procedureGuestAlreadyDeclared ? 'Đã có trong danh sách lưu trú' : 'Thêm người làm thủ tục vào danh sách' }}
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addBatchGuestRow">+ Thêm người khác</button>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.bookings.guests.store', $booking) }}" data-staying-guest-submit id="batchStayingGuestsForm">
                    @csrf
                    <input type="hidden" name="batch_mode" value="1">
                    <div id="batchGuestRows">
                        @include('admin.pages.bookings.partials.staying-guest-batch-row', ['index' => 0])
                    </div>
                    <button type="submit" class="btn btn-primary">Xác nhận thêm toàn bộ khách</button>
                </form>
                <template id="batchGuestRowTemplate">
                    @include('admin.pages.bookings.partials.staying-guest-batch-row', ['index' => '__INDEX__'])
                </template>
            </div>
        @endif
    </div>
</details>


@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'booking-staying-guests-scroll-{{ $booking->id }}';
    const panel = document.getElementById('stayingGuestsPanel');

    document.querySelectorAll('[data-staying-guest-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            try {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    y: window.scrollY,
                    open: panel?.open === true,
                    savedAt: Date.now()
                }));
            } catch (error) {}
        });
    });

    try {
        const raw = sessionStorage.getItem(storageKey);
        if (raw) {
            const saved = JSON.parse(raw);
            sessionStorage.removeItem(storageKey);
            if (saved && Date.now() - Number(saved.savedAt || 0) <= 120000) {
                if (panel && saved.open) panel.open = true;
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    window.scrollTo({ top: Number(saved.y || 0), behavior: 'auto' });
                }));
            }
        }
    } catch (error) {}

    const rows = document.getElementById('batchGuestRows');
    const template = document.getElementById('batchGuestRowTemplate');
    const addButton = document.getElementById('addBatchGuestRow');
    const fillRepresentativeButton = document.getElementById('fillBookingRepresentativeGuest');
    const procedureGuestIsStaying = document.getElementById('procedureGuestIsStaying');
    let nextIndex = rows ? rows.querySelectorAll('.js-batch-guest-row').length : 1;

    const renumber = () => rows?.querySelectorAll('.js-batch-number').forEach((el, i) => el.textContent = String(i + 1));
    const syncBatchGuardians = () => {
        if (!rows) return;
        const adultRows = Array.from(rows.querySelectorAll('.js-batch-guest-row')).filter((row) => row.querySelector('[name$="[guest_type]"]')?.value === 'adult');
        rows.querySelectorAll('.js-batch-guest-row').forEach((row) => {
            const type = row.querySelector('[name$="[guest_type]"]')?.value;
            const isMinor = type === 'child' || type === 'infant';
            row.querySelectorAll('.js-guardian-fields').forEach((field) => field.classList.toggle('d-none', !isMinor));
            const select = row.querySelector('.js-guardian-reference');
            if (!select) return;
            const selected = select.value;
            Array.from(select.querySelectorAll('option[data-batch-option]')).forEach((option) => option.remove());
            adultRows.forEach((adultRow) => {
                if (adultRow === row) return;
                const adultIndex = adultRow.dataset.index;
                const name = adultRow.querySelector('[name$="[full_name]"]')?.value?.trim() || `Khách người lớn ${Number(adultIndex) + 1}`;
                const option = document.createElement('option');
                option.value = `batch:${adultIndex}`;
                option.dataset.batchOption = '1';
                option.textContent = `${name} (trong biểu mẫu)`;
                select.appendChild(option);
            });
            if (Array.from(select.options).some((option) => option.value === selected)) select.value = selected;
        });
    };
    const setBirthday = (row, isoDate) => {
        if (!row || !isoDate) return;
        const input = row.querySelector('.js-birthday-value');
        if (!input) return;
        input.value = String(isoDate).slice(0, 10);
        if (input._flatpickr) input._flatpickr.setDate(input.value, false, 'Y-m-d');
    };
    const addRow = () => {
        if (!rows || !template) return null;
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        rows.insertAdjacentHTML('beforeend', html);
        renumber();
        const addedRow = rows.lastElementChild;
        const birthdayInput = addedRow?.querySelector('.js-birthday-value');
        if (birthdayInput && window.initializeProjectDatePicker) window.initializeProjectDatePicker(birthdayInput);
        syncBatchGuardians();
        return addedRow;
    };
    addButton?.addEventListener('click', addRow);
    rows?.addEventListener('click', (event) => {
        const remove = event.target.closest('.js-remove-batch-guest');
        if (!remove) return;
        const all = rows.querySelectorAll('.js-batch-guest-row');
        if (all.length === 1) return;
        remove.closest('.js-batch-guest-row')?.remove();
        renumber();
        syncBatchGuardians();
    });
    rows?.addEventListener('change', (event) => {
        const changedRow = event.target.closest('.js-batch-guest-row');
        if (event.target.matches('[name$="[guest_type]"]')) syncBatchGuardians();
        if (!event.target.classList.contains('js-representative-checkbox') || !event.target.checked) return;
        rows.querySelectorAll('.js-representative-checkbox').forEach((box) => { if (box !== event.target) box.checked = false; });
    });
    rows?.addEventListener('input', (event) => {
        if (event.target.matches('[name$="[full_name]"]')) syncBatchGuardians();
    });
    syncBatchGuardians();
    procedureGuestIsStaying?.addEventListener('change', () => {
        if (fillRepresentativeButton) fillRepresentativeButton.disabled = !procedureGuestIsStaying.checked;
        if (procedureGuestIsStaying.checked) fillRepresentativeButton?.click();
    });
    fillRepresentativeButton?.addEventListener('click', () => {
        if (procedureGuestIsStaying && !procedureGuestIsStaying.checked) return;
        const bookingDocument = String(@json($booking->booked_customer_cccd) || '').trim();
        let row = Array.from(rows?.querySelectorAll('.js-batch-guest-row') || []).find((candidate) => {
            const documentValue = candidate.querySelector('[name$="[document_number]"]')?.value?.trim() || '';
            return bookingDocument && documentValue === bookingDocument;
        });
        if (!row) {
            row = Array.from(rows?.querySelectorAll('.js-batch-guest-row') || []).find((candidate) => !candidate.querySelector('[name$="[full_name]"]')?.value?.trim());
        }
        if (!row) row = addRow();
        if (!row) return;
        const set = (suffix, value, overwrite = false) => {
            const input = row.querySelector(`[name$="[${suffix}]"]`);
            if (input && (overwrite || !input.value)) input.value = value || '';
        };
        set('full_name', @json($booking->booked_customer_name));
        setBirthday(row, @json(optional($booking->customer_birthday_snapshot)->format('Y-m-d')));
        set('gender', @json($booking->customer_gender_snapshot));
        set('guest_type', 'adult', true);
        set('document_number', @json($booking->booked_customer_cccd));
        set('address', @json($booking->booked_customer_address));
        const representative = row.querySelector('.js-representative-checkbox');
        if (representative) {
            representative.checked = true;
            representative.dispatchEvent(new Event('change', { bubbles: true }));
        }
        syncBatchGuardians();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    rows?.querySelectorAll('.js-birthday-value').forEach((input) => window.initializeProjectDatePicker?.(input));
});
</script>
@endonce
