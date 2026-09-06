@extends('layouts.admin')

@section('title', 'Chính sách khách sạn')

@section('content')
@php
    $definitionMap = $definitions ?? [];

    $policyMap = collect($groups)
        ->flatten(1)
        ->keyBy('key');

    $getPolicy = function (string $key) use ($policyMap, $definitionMap) {
        $row = $policyMap->get($key);
        if ($row) {
            return $row;
        }

        $definition = $definitionMap[$key] ?? null;
        if (!$definition) {
            return null;
        }

        return (object) [
            'id' => 'missing_' . $key,
            'key' => $key,
            'value' => $definition['default'] ?? '',
            'type' => $definition['type'] ?? 'string',
            'label' => $definition['label'] ?? $key,
            'description' => $definition['description'] ?? null,
        ];
    };

    $renderField = function ($policy, ?string $label = null, ?string $help = null) {
        if (!$policy) {
            return '';
        }

        $inputType = $policy->type === 'time'
            ? 'time'
            : (in_array($policy->type, ['integer', 'decimal'], true) ? 'number' : 'text');
        $step = $policy->type === 'decimal' ? '0.01' : '1';
        $name = 'values[' . $policy->id . ']';
        $errorKey = 'values.' . $policy->id;
        $fieldLabel = $label ?: $policy->label;
        $fieldHelp = $help ?: $policy->description;

        return <<<HTML
            <div>
                <label class="form-label fw-semibold mb-1" for="policy-{$policy->id}">{$fieldLabel}</label>
                <input
                    id="policy-{$policy->id}"
                    name="{$name}"
                    type="{$inputType}"
                    class="form-control @error('{$errorKey}') is-invalid @enderror"
                    value="{{ old('{$errorKey}', {$policy->value}) }}"
                    " . ($inputType === 'number' ? "step=\"{$step}\"" : '') . "
                    required
                >
                @error('{$errorKey}')
                    <div class="invalid-feedback">{{ \$message }}</div>
                @enderror
                " . ($fieldHelp ? "<div class=\"form-text\">{$fieldHelp}</div>" : '') . "
            </div>
        HTML;
    };

    $bookingPolicies = [
        $getPolicy('booking.min_age'),
        $getPolicy('booking.cleaning_buffer_minutes'),
        $getPolicy('booking.direct_cancel_cutoff_time'),
        $getPolicy('booking.hourly_cancel_grace_minutes'),
        $getPolicy('booking.manual_room_selection_fee'),
        $getPolicy('booking.max_online_guests'),
        $getPolicy('booking.max_online_rooms'),
    ];

    $paymentPolicies = [
        $getPolicy('payment.deposit_percent'),
        $getPolicy('payment.deposit_percent_2_rooms'),
        $getPolicy('payment.deposit_percent_3_rooms'),
        $getPolicy('payment.deposit_percent_4_rooms'),
        $getPolicy('payment.deposit_percent_5plus_rooms'),
        $getPolicy('payment.vnpay_expire_minutes'),
        $getPolicy('payment.admin_vnpay_expire_minutes'),
    ];

    $stayGeneral = [
        $getPolicy('stay.standard_check_in_time'),
        $getPolicy('stay.standard_check_out_time'),
        $getPolicy('stay.priority_cleaning_start_time'),
        $getPolicy('stay.priority_cleaning_window_minutes'),
        $getPolicy('stay.late_arrival_form_expire_minutes'),
    ];

    $roomIssuePolicies = $groups['room_issue'] ?? collect();
    $housekeepingPolicies = $groups['housekeeping'] ?? collect();
    $chatPolicies = $groups['chat'] ?? collect();
@endphp

<style>
    .policy-page-note {
        color: #64748b;
        max-width: 900px;
    }

    .policy-section-card {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .policy-section-card + .policy-section-card {
        margin-top: 1.25rem;
    }

    .policy-section-head {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #eef2f7;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .policy-section-head h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .policy-section-head p {
        margin: .35rem 0 0;
        color: #64748b;
    }

    .policy-section-body {
        padding: 1.25rem;
    }

    .policy-subsection + .policy-subsection {
        margin-top: 1.5rem;
    }

    .policy-subsection-title {
        margin-bottom: .85rem;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .policy-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .policy-range-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
        gap: 1rem;
    }

    .policy-range-box {
        border: 1px solid #dbeafe;
        background: #f8fbff;
        border-radius: 16px;
        padding: 1rem;
    }

    .policy-range-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .3rem .65rem;
        background: #dbeafe;
        color: #1d4ed8;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        margin-bottom: .65rem;
    }

    .policy-range-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: .35rem;
    }

    .policy-range-desc {
        font-size: .92rem;
        color: #475569;
        margin-bottom: .85rem;
    }

    .policy-inline-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .85rem;
    }

    .policy-simple-box {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1rem;
        background: #fff;
    }

    .policy-simple-box h4 {
        margin: 0 0 .25rem;
        font-size: .98rem;
        font-weight: 700;
        color: #0f172a;
    }

    .policy-simple-box p {
        margin: 0 0 .8rem;
        color: #64748b;
        font-size: .9rem;
    }

    .policy-timeline-note {
        margin-top: 1rem;
        border-left: 4px solid #2563eb;
        background: #eff6ff;
        padding: .9rem 1rem;
        border-radius: 0 12px 12px 0;
        color: #1e3a8a;
        font-size: .92rem;
    }

    .policy-submit-bar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(8px);
        border-top: 1px solid #e5e7eb;
        padding: 1rem 0 0;
        margin-top: 1rem;
    }

    @media (max-width: 991.98px) {
        .policy-grid,
        .policy-inline-fields {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-wrapper">
    <main class="admin-content">
        <div class="admin-page-head">
            <div>
                <h2>Chính sách khách sạn</h2>
                <p class="policy-page-note">Trang này đã được trình bày lại theo ngôn ngữ nghiệp vụ để dễ đọc. Mỗi box là một quy tắc riêng: mốc thời gian, khoảng áp dụng và mức phụ thu/ý nghĩa tương ứng. Booking mới sẽ dùng mức đang cấu hình; booking cũ vẫn giữ snapshot chính sách tại thời điểm chốt đơn.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.policies.update') }}">
            @csrf
            @method('PATCH')

            <section class="policy-section-card">
                <div class="policy-section-head">
                    <h3>Đặt phòng</h3>
                    <p>Các giới hạn cơ bản áp dụng khi tạo booking mới.</p>
                </div>
                <div class="policy-section-body">
                    <div class="policy-grid">
                        @foreach($bookingPolicies as $policy)
                            @if($policy)
                                <div class="policy-simple-box">
                                    <h4>{{ $policy->label }}</h4>
                                    @if($policy->description)
                                        <p>{{ $policy->description }}</p>
                                    @endif
                                    @php
                                        $inputType = $policy->type === 'time' ? 'time' : (in_array($policy->type, ['integer', 'decimal'], true) ? 'number' : 'text');
                                        $step = $policy->type === 'decimal' ? '0.01' : '1';
                                    @endphp
                                    <input
                                        id="policy-{{ $policy->id }}"
                                        name="values[{{ $policy->id }}]"
                                        type="{{ $inputType }}"
                                        @if($inputType === 'number') step="{{ $step }}" @endif
                                        class="form-control @error('values.' . $policy->id) is-invalid @enderror"
                                        value="{{ old('values.' . $policy->id, $policy->value) }}"
                                        required>
                                    @error('values.' . $policy->id)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="policy-section-card">
                <div class="policy-section-head">
                    <h3>Cọc & thanh toán</h3>
                    <p>Tách rõ theo số phòng để dễ giải thích cho khách và nhân viên.</p>
                </div>
                <div class="policy-section-body">
                    <div class="policy-subsection">
                        <div class="policy-subsection-title">Mức cọc theo số phòng</div>
                        <div class="policy-range-grid">
                            @php
                                $depositBoxes = [
                                    ['badge' => '1 phòng', 'title' => 'Booking 1 phòng', 'desc' => 'Mức cọc nền áp dụng cho đơn chỉ có 1 phòng.', 'percent' => $getPolicy('payment.deposit_percent')],
                                    ['badge' => '2 phòng', 'title' => 'Booking 2 phòng', 'desc' => 'Áp dụng khi đơn có đúng 2 phòng.', 'percent' => $getPolicy('payment.deposit_percent_2_rooms')],
                                    ['badge' => '3 phòng', 'title' => 'Booking 3 phòng', 'desc' => 'Áp dụng khi đơn có đúng 3 phòng.', 'percent' => $getPolicy('payment.deposit_percent_3_rooms')],
                                    ['badge' => '4 phòng', 'title' => 'Booking 4 phòng', 'desc' => 'Áp dụng khi đơn có đúng 4 phòng.', 'percent' => $getPolicy('payment.deposit_percent_4_rooms')],
                                    ['badge' => 'Từ 5 phòng', 'title' => 'Booking từ 5 phòng trở lên', 'desc' => 'Bậc cọc cao nhất để giảm rủi ro giữ nhiều inventory.', 'percent' => $getPolicy('payment.deposit_percent_5plus_rooms')],
                                ];
                            @endphp
                            @foreach($depositBoxes as $box)
                                <div class="policy-range-box">
                                    <div class="policy-range-badge">{{ $box['badge'] }}</div>
                                    <div class="policy-range-title">{{ $box['title'] }}</div>
                                    <div class="policy-range-desc">{{ $box['desc'] }}</div>
                                    @php $policy = $box['percent']; @endphp
                                    @if($policy)
                                        <label class="form-label fw-semibold mb-1" for="policy-{{ $policy->id }}">Mức cọc (%)</label>
                                        <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="number" step="0.01" class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                        @error('values.' . $policy->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="policy-subsection">
                        <div class="policy-subsection-title">Thời hạn link thanh toán</div>
                        <div class="policy-grid">
                            @foreach([$getPolicy('payment.vnpay_expire_minutes'), $getPolicy('payment.admin_vnpay_expire_minutes')] as $policy)
                                @if($policy)
                                    <div class="policy-simple-box">
                                        <h4>{{ $policy->label }}</h4>
                                        @if($policy->description)
                                            <p>{{ $policy->description }}</p>
                                        @endif
                                        <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="number" step="1" class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                        @error('values.' . $policy->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="policy-section-card">
                <div class="policy-section-head">
                    <h3>Nhận / trả phòng & lưu trú</h3>
                    <p>Trình bày thành từng box nghiệp vụ: giờ tiêu chuẩn, check-in sớm, check-out muộn, đến muộn và booking theo giờ.</p>
                </div>
                <div class="policy-section-body">
                    <div class="policy-subsection">
                        <div class="policy-subsection-title">1) Giờ tiêu chuẩn & mốc chung</div>
                        <div class="policy-grid">
                            @foreach($stayGeneral as $policy)
                                @if($policy)
                                    <div class="policy-simple-box">
                                        <h4>{{ $policy->label }}</h4>
                                        @if($policy->description)
                                            <p>{{ $policy->description }}</p>
                                        @endif
                                        @php
                                            $inputType = $policy->type === 'time' ? 'time' : (in_array($policy->type, ['integer', 'decimal'], true) ? 'number' : 'text');
                                            $step = $policy->type === 'decimal' ? '0.01' : '1';
                                        @endphp
                                        <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="{{ $inputType }}" @if($inputType === 'number') step="{{ $step }}" @endif class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                        @error('values.' . $policy->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="policy-subsection">
                        <div class="policy-subsection-title">2) Check-in sớm</div>
                        <div class="policy-range-grid">
                            @php
                                $earlyBoxes = [
                                    [
                                        'badge' => 'Mức 1',
                                        'title' => 'Trước hoặc bằng mốc 1',
                                        'desc' => 'Ví dụ: trước 06:00 thì phụ thu mức cao nhất.',
                                        'time_label' => 'Trước / đến',
                                        'time_policy' => $getPolicy('stay.early_checkin_tier1_end'),
                                        'percent_policy' => $getPolicy('stay.early_checkin_percent_1'),
                                    ],
                                    [
                                        'badge' => 'Mức 2',
                                        'title' => 'Sau mốc 1 đến mốc 2',
                                        'desc' => 'Ví dụ: từ sau 06:00 đến 09:00.',
                                        'time_label' => 'Đến',
                                        'time_policy' => $getPolicy('stay.early_checkin_tier2_end'),
                                        'percent_policy' => $getPolicy('stay.early_checkin_percent_2'),
                                    ],
                                    [
                                        'badge' => 'Mức 3',
                                        'title' => 'Sau mốc 2 đến trước khung miễn phí',
                                        'desc' => 'Ví dụ: từ sau 09:00 đến trước 12:00.',
                                        'time_label' => 'Bắt đầu miễn phí từ',
                                        'time_policy' => $getPolicy('stay.early_checkin_free_from'),
                                        'percent_policy' => $getPolicy('stay.early_checkin_percent_3'),
                                    ],
                                    [
                                        'badge' => 'Miễn phí',
                                        'title' => 'Từ khung miễn phí đến trước giờ check-in tiêu chuẩn',
                                        'desc' => 'Khách được vào sớm miễn phí nếu phòng đã sẵn sàng.',
                                        'time_label' => 'Giờ check-in tiêu chuẩn',
                                        'time_policy' => $getPolicy('stay.standard_check_in_time'),
                                        'percent_policy' => null,
                                    ],
                                ];
                            @endphp
                            @foreach($earlyBoxes as $box)
                                <div class="policy-range-box">
                                    <div class="policy-range-badge">{{ $box['badge'] }}</div>
                                    <div class="policy-range-title">{{ $box['title'] }}</div>
                                    <div class="policy-range-desc">{{ $box['desc'] }}</div>
                                    <div class="policy-inline-fields">
                                        @php $timePolicy = $box['time_policy']; @endphp
                                        @if($timePolicy)
                                            <div>
                                                <label class="form-label fw-semibold mb-1" for="policy-{{ $timePolicy->id }}">{{ $box['time_label'] }}</label>
                                                <input id="policy-{{ $timePolicy->id }}" name="values[{{ $timePolicy->id }}]" type="time" class="form-control @error('values.' . $timePolicy->id) is-invalid @enderror" value="{{ old('values.' . $timePolicy->id, $timePolicy->value) }}" required>
                                                @error('values.' . $timePolicy->id)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endif
                                        @php $percentPolicy = $box['percent_policy']; @endphp
                                        @if($percentPolicy)
                                            <div>
                                                <label class="form-label fw-semibold mb-1" for="policy-{{ $percentPolicy->id }}">Phụ thu (%)</label>
                                                <input id="policy-{{ $percentPolicy->id }}" name="values[{{ $percentPolicy->id }}]" type="number" step="0.01" class="form-control @error('values.' . $percentPolicy->id) is-invalid @enderror" value="{{ old('values.' . $percentPolicy->id, $percentPolicy->value) }}" required>
                                                @error('values.' . $percentPolicy->id)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <div>
                                                <label class="form-label fw-semibold mb-1">Phụ thu</label>
                                                <input type="text" class="form-control" value="0% / Miễn phí" disabled>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="policy-timeline-note">Cách hiểu nhanh: <strong>Trước</strong> mốc đầu tiên là mức 1, <strong>từ sau</strong> mốc 1 đến mốc 2 là mức 2, <strong>từ sau</strong> mốc 2 đến <strong>trước</strong> khung miễn phí là mức 3, còn từ khung miễn phí đến trước giờ check-in tiêu chuẩn thì miễn phí nếu phòng sẵn sàng.</div>
                    </div>

                    <div class="policy-subsection">
                        <div class="policy-subsection-title">3) Check-out muộn</div>
                        <div class="policy-grid mb-3">
                            @foreach([$getPolicy('stay.standard_check_out_time'), $getPolicy('stay.late_checkout_free_minutes')] as $policy)
                                @if($policy)
                                    <div class="policy-simple-box">
                                        <h4>{{ $policy->label }}</h4>
                                        <p>{{ $policy->description ?: 'Mốc nền để tính các khung trả phòng muộn.' }}</p>
                                        @php $inputType = $policy->type === 'time' ? 'time' : 'number'; @endphp
                                        <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="{{ $inputType }}" @if($inputType === 'number') step="1" @endif class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                        @error('values.' . $policy->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="policy-range-grid">
                            @php
                                $lateCheckoutBoxes = [
                                    ['badge' => 'Mức 1', 'title' => 'Sau giờ tiêu chuẩn đến mốc 1', 'desc' => 'Sau khi hết ân hạn, trả đến mốc 1 sẽ tính mức 1.', 'time' => $getPolicy('stay.late_checkout_tier1_end'), 'percent' => $getPolicy('stay.late_checkout_percent_1')],
                                    ['badge' => 'Mức 2', 'title' => 'Sau mốc 1 đến mốc 2', 'desc' => 'Ví dụ: sau 13:00 đến 14:00.', 'time' => $getPolicy('stay.late_checkout_tier2_end'), 'percent' => $getPolicy('stay.late_checkout_percent_2')],
                                    ['badge' => 'Mức 3', 'title' => 'Sau mốc 2 đến mốc 3', 'desc' => 'Ví dụ: sau 14:00 đến 15:00.', 'time' => $getPolicy('stay.late_checkout_tier3_end'), 'percent' => $getPolicy('stay.late_checkout_percent_3')],
                                    ['badge' => 'Mức 4', 'title' => 'Sau mốc 3 đến trước mốc tính thêm đêm', 'desc' => 'Ví dụ: sau 15:00 đến trước 18:00.', 'time' => $getPolicy('stay.late_checkout_full_night_from'), 'percent' => $getPolicy('stay.late_checkout_percent_4')],
                                    ['badge' => 'Thêm đêm', 'title' => 'Từ mốc tính thêm đêm trở đi', 'desc' => 'Từ mốc này trở đi xem như phụ thu trọn mức thêm một đêm.', 'time' => $getPolicy('stay.late_checkout_full_night_from'), 'percent' => $getPolicy('stay.late_checkout_percent_full')],
                                ];
                            @endphp
                            @foreach($lateCheckoutBoxes as $box)
                                <div class="policy-range-box">
                                    <div class="policy-range-badge">{{ $box['badge'] }}</div>
                                    <div class="policy-range-title">{{ $box['title'] }}</div>
                                    <div class="policy-range-desc">{{ $box['desc'] }}</div>
                                    <div class="policy-inline-fields">
                                        @php $timePolicy = $box['time']; $percentPolicy = $box['percent']; @endphp
                                        @if($timePolicy)
                                            <div>
                                                <label class="form-label fw-semibold mb-1" for="policy-{{ $timePolicy->id }}">Mốc kết thúc / bắt đầu</label>
                                                <input id="policy-{{ $timePolicy->id }}" name="values[{{ $timePolicy->id }}]" type="time" class="form-control @error('values.' . $timePolicy->id) is-invalid @enderror" value="{{ old('values.' . $timePolicy->id, $timePolicy->value) }}" required>
                                                @error('values.' . $timePolicy->id)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endif
                                        @if($percentPolicy)
                                            <div>
                                                <label class="form-label fw-semibold mb-1" for="policy-{{ $percentPolicy->id }}">Phụ thu (%)</label>
                                                <input id="policy-{{ $percentPolicy->id }}" name="values[{{ $percentPolicy->id }}]" type="number" step="0.01" class="form-control @error('values.' . $percentPolicy->id) is-invalid @enderror" value="{{ old('values.' . $percentPolicy->id, $percentPolicy->value) }}" required>
                                                @error('values.' . $percentPolicy->id)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="policy-subsection">
                        <div class="policy-subsection-title">4) Đến muộn / giữ phòng sau giờ G</div>
                        <div class="policy-grid mb-3">
                            @foreach([$getPolicy('stay.late_arrival_cutoff_time'), $getPolicy('stay.late_arrival_grace_minutes'), $getPolicy('stay.rescheduled_after_cutoff_grace_minutes')] as $policy)
                                @if($policy)
                                    <div class="policy-simple-box">
                                        <h4>{{ $policy->label }}</h4>
                                        <p>{{ $policy->description ?: 'Mốc nền để xử lý booking đến muộn.' }}</p>
                                        @php $inputType = $policy->type === 'time' ? 'time' : 'number'; @endphp
                                        <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="{{ $inputType }}" @if($inputType === 'number') step="1" @endif class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                        @error('values.' . $policy->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div class="policy-range-grid">
                            @php
                                $lateArrivalBoxes = [
                                    ['badge' => 'Trước giờ G', 'title' => 'Khách đến trước hoặc đúng giờ G', 'desc' => 'Vẫn giữ phòng theo luồng tiêu chuẩn, chưa áp phụ thu đến muộn.', 'time' => $getPolicy('stay.late_arrival_cutoff_time'), 'percent' => null],
                                    ['badge' => 'Mức 1', 'title' => 'Sau giờ G đến mốc mức 1', 'desc' => 'Ví dụ: sau 18:00 đến 21:00.', 'time' => $getPolicy('stay.late_arrival_tier1_end'), 'percent' => $getPolicy('stay.late_arrival_percent_1')],
                                    ['badge' => 'Mức 2', 'title' => 'Sau mốc mức 1 đến hết ngày nhận phòng', 'desc' => 'Áp dụng cho khách báo đến rất muộn nhưng vẫn trong ngày nhận phòng.', 'time' => $getPolicy('stay.late_arrival_tier1_end'), 'percent' => $getPolicy('stay.late_arrival_percent_2')],
                                    ['badge' => 'Ngày hôm sau', 'title' => 'Từ ngày hôm sau trở đi', 'desc' => 'Nếu khách dời sang ngày hôm sau thì áp mức phụ thu cao nhất của luồng đến muộn.', 'time' => null, 'percent' => $getPolicy('stay.late_arrival_percent_next_day')],
                                ];
                            @endphp
                            @foreach($lateArrivalBoxes as $box)
                                <div class="policy-range-box">
                                    <div class="policy-range-badge">{{ $box['badge'] }}</div>
                                    <div class="policy-range-title">{{ $box['title'] }}</div>
                                    <div class="policy-range-desc">{{ $box['desc'] }}</div>
                                    <div class="policy-inline-fields">
                                        @php $timePolicy = $box['time']; $percentPolicy = $box['percent']; @endphp
                                        @if($timePolicy)
                                            <div>
                                                <label class="form-label fw-semibold mb-1" for="policy-{{ $timePolicy->id }}">Mốc thời gian</label>
                                                <input id="policy-{{ $timePolicy->id }}" name="values[{{ $timePolicy->id }}]" type="time" class="form-control @error('values.' . $timePolicy->id) is-invalid @enderror" value="{{ old('values.' . $timePolicy->id, $timePolicy->value) }}" required>
                                                @error('values.' . $timePolicy->id)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <div>
                                                <label class="form-label fw-semibold mb-1">Mốc thời gian</label>
                                                <input type="text" class="form-control" value="Từ 00:00 ngày hôm sau" disabled>
                                            </div>
                                        @endif
                                        @if($percentPolicy)
                                            <div>
                                                <label class="form-label fw-semibold mb-1" for="policy-{{ $percentPolicy->id }}">Phụ thu (%)</label>
                                                <input id="policy-{{ $percentPolicy->id }}" name="values[{{ $percentPolicy->id }}]" type="number" step="0.01" class="form-control @error('values.' . $percentPolicy->id) is-invalid @enderror" value="{{ old('values.' . $percentPolicy->id, $percentPolicy->value) }}" required>
                                                @error('values.' . $percentPolicy->id)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <div>
                                                <label class="form-label fw-semibold mb-1">Phụ thu</label>
                                                <input type="text" class="form-control" value="0% / Không phụ thu" disabled>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="policy-subsection">
                        <div class="policy-subsection-title">5) Booking theo giờ</div>
                        <div class="policy-range-grid">
                            @php
                                $shortStayBoxes = [
                                    ['badge' => 'Điều kiện tối thiểu', 'title' => 'Số phút tối thiểu để cho phép tạo booking theo giờ', 'desc' => 'Ngắn hơn mốc này thì không được tạo đơn.', 'policies' => [$getPolicy('stay.short_stay_min_minutes')]],
                                    ['badge' => 'Ngưỡng chuyển qua đêm', 'title' => 'Nếu vượt mốc này thì tính như booking qua đêm', 'desc' => 'Giúp lễ tân không phải tự suy luận khi khách ở quá lâu.', 'policies' => [$getPolicy('stay.short_stay_to_overnight_hours')]],
                                    ['badge' => 'Gói cơ bản', 'title' => 'Số giờ đầu & giá gói cơ bản', 'desc' => 'Ví dụ: 2 giờ đầu = 50% giá đêm.', 'policies' => [$getPolicy('stay.short_stay_base_hours'), $getPolicy('stay.short_stay_base_percent')]],
                                    ['badge' => 'Giờ phát sinh', 'title' => 'Mỗi giờ thêm sau gói cơ bản', 'desc' => 'Ví dụ: mỗi giờ thêm +10% giá đêm.', 'policies' => [$getPolicy('stay.short_stay_extra_hour_percent'), $getPolicy('stay.short_stay_max_percent')]],
                                ];
                            @endphp
                            @foreach($shortStayBoxes as $box)
                                <div class="policy-range-box">
                                    <div class="policy-range-badge">{{ $box['badge'] }}</div>
                                    <div class="policy-range-title">{{ $box['title'] }}</div>
                                    <div class="policy-range-desc">{{ $box['desc'] }}</div>
                                    <div class="policy-grid">
                                        @foreach($box['policies'] as $policy)
                                            @if($policy)
                                                <div>
                                                    <label class="form-label fw-semibold mb-1" for="policy-{{ $policy->id }}">{{ $policy->label }}</label>
                                                    <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="number" step="{{ $policy->type === 'decimal' ? '0.01' : '1' }}" class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                                    @error('values.' . $policy->id)
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            @foreach([
                ['key' => 'room_issue', 'title' => 'Sự cố phòng', 'desc' => 'Các mốc phụ trợ cho luồng xử lý sự cố phòng.', 'rows' => $roomIssuePolicies],
                ['key' => 'housekeeping', 'title' => 'Buồng phòng', 'desc' => 'Các mốc cảnh báo & vận hành nội bộ của buồng phòng.', 'rows' => $housekeepingPolicies],
                ['key' => 'chat', 'title' => 'Tin nhắn', 'desc' => 'Các mốc lưu trữ để tra cứu lịch sử khi cần.', 'rows' => $chatPolicies],
            ] as $section)
                @if(($section['rows'] instanceof \Illuminate\Support\Collection && $section['rows']->isNotEmpty()) || (is_iterable($section['rows']) && count($section['rows']) > 0))
                    <section class="policy-section-card">
                        <div class="policy-section-head">
                            <h3>{{ $section['title'] }}</h3>
                            <p>{{ $section['desc'] }}</p>
                        </div>
                        <div class="policy-section-body">
                            <div class="policy-grid">
                                @foreach($section['rows'] as $policy)
                                    <div class="policy-simple-box">
                                        <h4>{{ $policy->label }}</h4>
                                        <p>{{ $policy->description ?: 'Thiết lập áp dụng cho luồng nghiệp vụ tương ứng.' }}</p>
                                        @php
                                            $inputType = $policy->type === 'time' ? 'time' : (in_array($policy->type, ['integer', 'decimal'], true) ? 'number' : 'text');
                                            $step = $policy->type === 'decimal' ? '0.01' : '1';
                                        @endphp
                                        <input id="policy-{{ $policy->id }}" name="values[{{ $policy->id }}]" type="{{ $inputType }}" @if($inputType === 'number') step="{{ $step }}" @endif class="form-control @error('values.' . $policy->id) is-invalid @enderror" value="{{ old('values.' . $policy->id, $policy->value) }}" required>
                                        @error('values.' . $policy->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            @endforeach

            <div class="policy-submit-bar">
                <div class="d-flex justify-content-end pb-3">
                    <button class="btn btn-primary px-4" type="submit">
                        <i class="bx bx-save me-1"></i>
                        Lưu chính sách
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>
@endsection
