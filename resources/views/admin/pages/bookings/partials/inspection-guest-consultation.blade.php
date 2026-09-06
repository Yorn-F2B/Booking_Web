@php
    $stageLabels = [
        'housekeeping_report' => 'Buồng phòng đang kiểm tra',
        'guest_consultation' => 'Cần trao đổi với khách',
        'housekeeping_recheck' => 'Buồng phòng đang kiểm tra lại',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-secondary',
        'guest_consultation' => 'bg-primary',
        'housekeeping_recheck' => 'bg-warning text-dark',
        'completed' => 'bg-success',
    ];
    $attachmentContextLabels = \App\Models\RoomInspectionAttachment::contextOptions();
@endphp

<div class="mb-3">
    <div class="alert alert-warning">
        <strong>Chưa thể check-out.</strong> Mọi khoản minibar, mất đồ hoặc hư hại phải được khách xem lại. Nếu khách chưa đồng ý, buồng phòng kiểm tra lại và lễ tân tiếp tục trao đổi; khi kết quả buồng phòng khớp với ý kiến khách thì phiếu tự hoàn tất.
    </div>

    @foreach ($booking->roomInspections as $inspection)
        @php
            $stage = $inspection->workflow_stage ?? 'housekeeping_report';
            $hasRecheckResult = $inspection->items->contains(fn ($item) => in_array($item->recheck_decision, ['keep_charge', 'remove_charge'], true));
        @endphp
        <details class="compact-panel mb-3" @if($stage === 'guest_consultation') open @endif>
            <summary>
                <span>Phòng {{ $inspection->room->room_number ?? '---' }}</span>
                <span class="badge {{ $stageClasses[$stage] ?? 'bg-secondary' }}">{{ $stageLabels[$stage] ?? $stage }}</span>
            </summary>
            <div class="compact-panel-body">
                @if ($inspection->attachments->isNotEmpty())
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="fw-semibold mb-2">Ảnh minh chứng buồng phòng</div>
                        @foreach ($inspection->attachments->groupBy('context') as $context => $attachments)
                            <div class="small text-muted mb-2">{{ $attachmentContextLabels[$context] ?? 'Ảnh minh chứng' }}</div>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                @foreach ($attachments as $attachment)
                                    <div style="width:110px">
                                        <a href="{{ route('admin.floor-inspection-attachments.show', $attachment) }}" target="_blank">
                                            <img src="{{ route('admin.floor-inspection-attachments.show', $attachment) }}" alt="Ảnh minh chứng" style="width:110px;height:110px;object-fit:cover;border-radius:10px;border:1px solid #ddd;">
                                        </a>
                                        <div class="small text-muted mt-1">{{ $attachment->uploader->name ?? 'Buồng phòng' }} · {{ $attachment->created_at?->format('d/m H:i') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                        <div class="small text-muted">Lễ tân có thể mở ảnh để đối chiếu trực tiếp khi trao đổi với khách.</div>
                    </div>
                @endif

                @if ($stage === 'guest_consultation')
                    <div class="alert alert-info small">
                        @if ($hasRecheckResult)
                            <strong>Buồng phòng vừa cập nhật kết quả kiểm tra lại.</strong> Hạng mục đã khớp với số khách xác nhận được khóa tự động. Lễ tân chỉ cần trao đổi lại các khoản còn lệch.
                        @else
                            Nói rõ từng khoản dự kiến với khách. Khoản khách chưa đồng ý phải ghi lý do cụ thể để buồng phòng kiểm tra lại.
                        @endif
                    </div>

                    <form action="{{ route('admin.bookings.inspections.guest-consultation', [$booking->id, $inspection->id]) }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle guest-consultation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hạng mục</th>
                                        <th style="min-width:210px">Kết quả hiện tại</th>
                                        <th style="min-width:240px">Khách xác nhận</th>
                                        <th style="width:150px">Số lượng khách xác nhận</th>
                                        <th style="min-width:260px">Ghi chú nếu cần kiểm tra lại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inspection->items as $item)
                                        @php
                                            $oldResponse = old(
                                                'item_responses.' . $item->id,
                                                $item->guest_response === 'disputed' ? 'disputed' : 'accepted'
                                            );
                                            $oldClaimedQuantity = old(
                                                'item_claimed_quantities.' . $item->id,
                                                $item->guest_claimed_quantity !== null
                                                    ? (int) $item->guest_claimed_quantity
                                                    : (int) $item->quantity
                                            );
                                            $isLockedAccepted = $item->guest_response === 'accepted';
                                        @endphp
                                        <tr class="{{ (float) $item->total <= 0 ? 'table-success' : '' }}">
                                            <td>
                                                <strong>{{ $item->name }}</strong>
                                                <div class="small text-muted">{{ $item->type === 'minibar' ? 'Minibar / đồ dùng' : 'Hư hại / mất đồ' }}</div>
                                                @if (($item->detection_source ?? 'initial') === 'supplemental')
                                                    <span class="badge bg-warning text-dark mt-1">Phát hiện bổ sung</span>
                                                    <div class="small text-muted mt-1">
                                                        {{ $item->detector->name ?? 'Buồng phòng' }} · {{ $item->detected_at?->format('d/m H:i') ?? $item->created_at?->format('d/m H:i') }}
                                                        @if($item->detection_version) · Lần #{{ $item->detection_version }} @endif
                                                    </div>
                                                @endif
                                                @if ($item->guest_response_note && $item->guest_response !== 'accepted')
                                                    <div class="small text-danger mt-1"><strong>Khách đã phản hồi trước:</strong> {{ $item->guest_response_note }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->recheck_decision === 'remove_charge')
                                                    <span class="badge bg-success mb-1">Buồng phòng xác minh số lượng bằng 0</span>
                                                @elseif ($item->recheck_decision === 'keep_charge')
                                                    <span class="badge bg-warning text-dark mb-1">Kết quả buồng phòng xác minh lại</span>
                                                @else
                                                    <span class="badge bg-secondary mb-1">Kết quả kiểm tra ban đầu</span>
                                                @endif
                                                <div>
                                                    {{ (int) $item->quantity }} {{ $item->unit ?: 'đơn vị' }}
                                                    × {{ number_format((float) $item->price, 0, ',', '.') }}đ
                                                    = <strong class="{{ (float) $item->total > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $item->total, 0, ',', '.') }}đ</strong>
                                                </div>
                                                @if ($item->recheck_note)
                                                    <div class="small text-muted mt-1"><strong>Kết quả xác minh:</strong> {{ $item->recheck_note }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($isLockedAccepted)
                                                    <input type="hidden" name="item_responses[{{ $item->id }}]" value="accepted">
                                                    <div class="alert alert-success py-2 px-3 mb-0 small">
                                                        <strong>Đã thống nhất với khách</strong><br>
                                                        Hạng mục này không cần phản hồi lại. Nếu thực tế thay đổi, buồng phòng phải cập nhật từ màn kiểm tra phòng.
                                                    </div>
                                                @else
                                                    <div class="form-check">
                                                        <input class="form-check-input guest-response-radio" type="radio" name="item_responses[{{ $item->id }}]" id="accept{{ $item->id }}" value="accepted" data-note-id="guestNote{{ $item->id }}" data-quantity-id="guestQty{{ $item->id }}" data-current-quantity="{{ (int) $item->quantity }}" @checked($oldResponse === 'accepted')>
                                                        <label class="form-check-label" for="accept{{ $item->id }}">Khách đồng ý kết quả hiện tại</label>
                                                    </div>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input guest-response-radio" type="radio" name="item_responses[{{ $item->id }}]" id="dispute{{ $item->id }}" value="disputed" data-note-id="guestNote{{ $item->id }}" data-quantity-id="guestQty{{ $item->id }}" data-current-quantity="{{ (int) $item->quantity }}" @checked($oldResponse === 'disputed')>
                                                        <label class="form-check-label text-danger" for="dispute{{ $item->id }}">Khách chưa đồng ý số lượng hiện tại</label>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="guest-quantity-cell">
                                                @if ($isLockedAccepted)
                                                    <input type="hidden" name="item_claimed_quantities[{{ $item->id }}]" value="{{ (int) $item->quantity }}">
                                                    <div class="text-center py-2">
                                                        <strong class="fs-5 text-success">{{ (int) $item->quantity }}</strong>
                                                        <div class="small text-muted">{{ $item->unit ?: 'đơn vị' }}</div>
                                                    </div>
                                                @else
                                                    <div class="guest-quantity-control" data-quantity-control>
                                                        <button type="button" class="btn btn-outline-secondary guest-quantity-step" data-step="-1" aria-label="Giảm số lượng">−</button>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            max="999"
                                                            step="1"
                                                            inputmode="numeric"
                                                            class="form-control guest-claimed-quantity"
                                                            id="guestQty{{ $item->id }}"
                                                            name="item_claimed_quantities[{{ $item->id }}]"
                                                            value="{{ $oldClaimedQuantity }}"
                                                            data-current-quantity="{{ (int) $item->quantity }}"
                                                            aria-label="Số lượng khách xác nhận"
                                                        >
                                                        <button type="button" class="btn btn-outline-secondary guest-quantity-step" data-step="1" aria-label="Tăng số lượng">+</button>
                                                    </div>
                                                    <div class="small text-muted text-center mt-1">{{ $item->unit ?: 'đơn vị' }}</div>
                                                    <div class="small text-danger text-center mt-1 quantity-match-warning d-none">Số lượng đã trùng kết quả hiện tại; không cần kiểm tra lại.</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($isLockedAccepted)
                                                    <div class="small text-muted py-2">Không cần nhập.</div>
                                                @else
                                                    <textarea class="form-control guest-response-note" id="guestNote{{ $item->id }}" name="item_notes[{{ $item->id }}]" rows="3" placeholder="Ví dụ: Khách nói chỉ vỡ 2 ly; đề nghị đếm lại.">{{ old('item_notes.' . $item->id, $item->guest_response === 'disputed' ? $item->guest_response_note : '') }}</textarea>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú trao đổi chung</label>
                            <textarea name="guest_consultation_note" rows="2" class="form-control" placeholder="Ghi chú thêm nếu cần">{{ old('guest_consultation_note', $inspection->guest_consultation_note) }}</textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit" onclick="return confirm('Đã trao đổi rõ kết quả hiện tại với khách và ghi nhận đúng lựa chọn?')">
                            Gửi lựa chọn của khách
                        </button>
                    </form>
                @elseif ($stage === 'housekeeping_recheck')
                    <div class="alert alert-warning mb-2">
                        <strong>Đang chờ buồng phòng xác minh lại.</strong> Sau khi có kết quả mới, phiếu sẽ tự quay lại đây để lễ tân trao đổi tiếp với khách.
                    </div>
                    @foreach ($inspection->items->where('guest_response', 'disputed') as $item)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $item->name }}</strong>
                            <div class="text-danger small">Khách xác nhận: <strong>{{ $item->guest_claimed_quantity ?? $item->quantity }} {{ $item->unit ?: 'đơn vị' }}</strong></div>
                            @if($item->guest_response_note)<div class="small text-muted">Ghi chú: {{ $item->guest_response_note }}</div>@endif
                        </div>
                    @endforeach
                @elseif ($stage === 'completed')
                    <div class="alert alert-success mb-0">Kết quả đã thống nhất và phiếu đã hoàn tất. Tổng phí kiểm tra phòng được chốt: <strong>{{ number_format((float)$inspection->approved_total,0,',','.') }}đ</strong>.</div>
                @else
                    <div class="alert alert-secondary mb-0">Buồng phòng đang thực hiện kiểm tra ban đầu.</div>
                @endif
            </div>
        </details>
    @endforeach
</div>


<style>
.guest-consultation-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
}
.guest-consultation-table th,
.guest-consultation-table td {
    vertical-align: top;
    overflow-wrap: anywhere;
}
.guest-consultation-table th:nth-child(1){width:13%}
.guest-consultation-table th:nth-child(2){width:24%}
.guest-consultation-table th:nth-child(3){width:27%}
.guest-consultation-table th:nth-child(4){width:18%}
.guest-consultation-table th:nth-child(5){width:18%}
.guest-consultation-table .guest-quantity-cell {
    min-width: 0;
    width: auto;
}
.guest-quantity-control {
    display: grid;
    grid-template-columns: 30px minmax(44px, 54px) 30px;
    gap: 0;
    align-items: stretch;
    width: 100%;
    max-width: 114px;
    min-width: 0;
    margin: 0 auto;
}
.guest-quantity-control .guest-claimed-quantity {
    min-width: 0;
    height: 34px;
    padding: 4px 5px;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.2;
    text-align: center;
    color: #172033 !important;
    background-color: #fff !important;
    opacity: 1 !important;
    -webkit-text-fill-color: #172033;
}
.guest-quantity-control .guest-claimed-quantity[readonly] {
    color: #495057 !important;
    background-color: #f3f4f6 !important;
    -webkit-text-fill-color: #495057;
}
.guest-quantity-control .guest-quantity-step {
    height: 34px;
    min-width: 0;
    padding: 0;
    font-size: 17px;
    font-weight: 700;
    line-height: 1;
}
.guest-quantity-control .guest-claimed-quantity::-webkit-outer-spin-button,
.guest-quantity-control .guest-claimed-quantity::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.guest-quantity-control .guest-claimed-quantity {
    -moz-appearance: textfield;
    appearance: textfield;
    border-radius: 0;
}
@media (max-width: 991.98px) {
    .guest-consultation-table,
    .guest-consultation-table tbody,
    .guest-consultation-table tr,
    .guest-consultation-table td {
        display: block;
        width: 100%;
        min-width: 0 !important;
    }
    .guest-consultation-table { table-layout: auto; }
    .guest-consultation-table thead { display: none; }
    .guest-consultation-table tr {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
    }
    .guest-consultation-table td {
        border-width: 0 0 1px 0;
        padding: 10px 12px;
    }
    .guest-consultation-table td:last-child { border-bottom: 0; }
    .guest-consultation-table td::before {
        display: block;
        margin-bottom: 5px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
    }
    .guest-consultation-table td:nth-child(1)::before { content: 'Hạng mục'; }
    .guest-consultation-table td:nth-child(2)::before { content: 'Kết quả hiện tại'; }
    .guest-consultation-table td:nth-child(3)::before { content: 'Khách xác nhận'; }
    .guest-consultation-table td:nth-child(4)::before { content: 'Số lượng khách xác nhận'; }
    .guest-consultation-table td:nth-child(5)::before { content: 'Ghi chú'; }
    .guest-quantity-control { margin-left: 0; }
    .guest-quantity-cell .small { text-align: left !important; }
}
</style>

<script>
(function () {
    function updateGuestResponseState(radio) {
        const noteId = radio.dataset.noteId;
        const quantityId = radio.dataset.quantityId;
        const note = document.getElementById(noteId);
        const quantity = document.getElementById(quantityId);
        const dispute = document.querySelector('input[data-note-id="' + noteId + '"][value="disputed"]');
        const accept = document.querySelector('input[data-note-id="' + noteId + '"][value="accepted"]');
        const isDisputed = dispute && dispute.checked;

        if (quantity) {
            quantity.required = isDisputed;
            quantity.readOnly = !isDisputed;
            quantity.classList.toggle('bg-light', !isDisputed);
            if (!isDisputed && accept) quantity.value = accept.dataset.currentQuantity || quantity.value;
        }

        if (note) {
            note.disabled = !isDisputed;
            note.required = isDisputed;
            note.placeholder = isDisputed
                ? 'Bắt buộc ghi ngắn gọn nội dung cần kiểm tra lại.'
                : 'Chỉ nhập khi khách chưa đồng ý.';
            if (!isDisputed) note.value = '';
        }

        if (quantity) {
            const warning = quantity.closest('.guest-quantity-cell')?.querySelector('.quantity-match-warning');
            const refreshWarning = function () {
                const currentQty = Number(quantity.dataset.currentQuantity || 0);
                const claimedQty = Number(quantity.value || 0);
                if (warning) warning.classList.toggle('d-none', !isDisputed || claimedQty !== currentQty);
            };
            quantity.oninput = refreshWarning;
            refreshWarning();
        }
    }
    document.querySelectorAll('.guest-response-radio').forEach(function (radio) {
        radio.addEventListener('change', function () { updateGuestResponseState(radio); });
        updateGuestResponseState(radio);
    });

    document.querySelectorAll('[data-quantity-control]').forEach(function (control) {
        const input = control.querySelector('.guest-claimed-quantity');
        if (!input) return;

        control.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (input.readOnly || input.disabled) return;
                const min = Number(input.min || 0);
                const max = Number(input.max || 999);
                const current = Number.parseInt(input.value || '0', 10);
                const step = Number.parseInt(button.dataset.step || '0', 10);
                input.value = Math.min(max, Math.max(min, (Number.isNaN(current) ? 0 : current) + step));
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            });
        });
    });
})();
</script>
