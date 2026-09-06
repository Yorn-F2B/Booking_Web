@extends('layouts.user')

@section('title', 'Chi tiết đơn phòng')

@section('content')
    @php
        $detailPolicy = app(\App\Services\HotelPolicyService::class);
        $detailDepositPercent = (float) $detailPolicy->depositRate($booking) * 100;
        $detailDepositLabel = rtrim(rtrim(number_format($detailDepositPercent, 2, '.', ''), '0'), '.') . '%';
        $detailCheckIn = substr((string) $detailPolicy->forBooking($booking, 'stay.standard_check_in_time', '14:00'), 0, 5);
        $detailCheckOut = substr((string) $detailPolicy->forBooking($booking, 'stay.standard_check_out_time', '12:00'), 0, 5);
        $detailEarlyFree = substr((string) $detailPolicy->forBooking($booking, 'stay.early_checkin_free_from', '12:00'), 0, 5);
    @endphp

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

            @if ($booking->status == 'pending' && $booking->payment_status == 'unpaid')
                @php
                    $deposit30Amount = app(\App\Services\BookingFinancialService::class)->requiredDeposit($booking);
                    $fullAmount = (float) $booking->estimated_total;
                    $selectedPaymentType = old('payment_type', $defaultPaymentType ?? 'deposit_30');
                @endphp

                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-bold mb-1">
                                Đơn này chưa thanh toán
                            </div>

                            <div class="small">
                                Booking đang chờ thanh toán để xác nhận.
                            </div>

                            @if (!empty($latestPayment))
                                <div class="small text-muted mt-2">
                                    Giao dịch gần nhất:
                                    {{ number_format((float) $latestPayment->amount, 0, ',', '.') }}đ
                                    -
                                    @if ($latestPayment->status == 'pending')
                                        đang chờ thanh toán
                                    @elseif ($latestPayment->status == 'failed')
                                        không thành công
                                    @elseif ($latestPayment->status == 'success')
                                        thành công
                                    @else
                                        {{ $latestPayment->status }}
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div style="min-width: 280px;">
                            <a href="{{ route('bookings.edit-before-payment', $booking) }}" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bx bx-edit-alt me-1"></i>
                                Chỉnh sửa đơn trước thanh toán
                            </a>

                            <form action="{{ route('payment.vnpay.create', $booking->id) }}" method="POST">
                                @csrf

                                <div class="border rounded-3 p-3 bg-white mb-2">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_type"
                                        id="continueDeposit30" value="deposit_30"
                                        {{ $selectedPaymentType == 'deposit_30' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="continueDeposit30">
                                        Cọc {{ $detailDepositLabel }}
                                        <strong>{{ number_format($deposit30Amount, 0, ',', '.') }}đ</strong>
                                    </label>
                                </div>
                            </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-credit-card me-1"></i>
                                    Tiếp tục thanh toán VNPay
                                </button>
                            </form>
                        </div>
                    </div>
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
                                @if ($booking->status == 'pending' && $booking->payment_status == 'unpaid')
                                    <span class="badge text-bg-warning">Chờ thanh toán</span>
                                @elseif ($booking->status == 'pending')
                                    <span class="badge text-bg-warning">Chờ xác nhận</span>
                                @elseif ($booking->status == 'confirmed')
                                    <span class="badge text-bg-primary">Đã xác nhận</span>
                                @elseif ($booking->status == 'checked_in')
                                    <span class="badge text-bg-info">Đã nhận phòng</span>
                                @elseif ($booking->status == 'checked_out')
                                    <span class="badge text-bg-success">Đã trả phòng</span>
                                @elseif ($booking->status == 'completed')
                                    <span class="badge text-bg-success">Đã hoàn tất</span>
                                @elseif ($booking->status == 'inspection_requested')
                                    <span class="badge text-bg-secondary">Đang kiểm tra phòng</span>
                                @elseif ($booking->status == 'cancelled')
                                    <span class="badge text-bg-danger">Đã hủy</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $booking->status }}</span>
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
                                            <div class="small text-muted">Nhận phòng linh hoạt {{ $detailEarlyFree }}–{{ $detailCheckIn }} nếu phòng đã sẵn sàng</div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Ngày trả phòng</th>
                                        <td>
                                            {{ date('d/m/Y', strtotime($booking->check_out_date)) }}
                                            <div class="small text-muted">Trả phòng theo giờ chuẩn {{ $detailCheckOut }}</div>
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

                                    @if ((float) ($booking->room_selection_fee ?? 0) > 0)
                                        <tr>
                                            <th>Phí chọn phòng thủ công</th>
                                            <td>{{ number_format((float) $booking->room_selection_fee, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif

                                    @if ((float) ($booking->discount_amount ?? 0) > 0)
                                        <tr>
                                            <th>Tổng trước ưu đãi</th>
                                            <td>{{ number_format((float) ($booking->subtotal_amount ?? ($booking->estimated_total + $booking->discount_amount)), 0, ',', '.') }}đ</td>
                                        </tr>

                                        <tr>
                                            <th>Ưu đãi đã áp dụng</th>
                                            <td class="text-success fw-bold">-{{ number_format((float) $booking->discount_amount, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endif

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
                                                Không xác định
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




                    @if (($booking->bookingPromotions ?? collect())->count() > 0)
                        <div class="settings-section mb-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h3 class="h6 fw-bold mb-1">
                                        Mã ưu đãi đã áp dụng
                                    </h3>
                                    <p class="text-muted small mb-0">
                                        Các mã bên dưới đã được tính vào tổng tiền của đơn. Một số mã có thể do khách sạn áp dụng để hỗ trợ trải nghiệm của bạn.
                                    </p>
                                </div>
                                <span class="badge text-bg-success">
                                    -{{ number_format((float) $booking->bookingPromotions->sum('discount_amount'), 0, ',', '.') }}đ
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã</th>
                                            <th>Loại</th>
                                            <th>Nguồn áp dụng</th>
                                            <th class="text-end">Số tiền giảm</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($booking->bookingPromotions as $bookingPromotion)
                                            <tr>
                                                <td class="fw-bold">{{ $bookingPromotion->code_snapshot }}</td>
                                                <td>{{ $bookingPromotion->type_label }}</td>
                                                <td>
                                                    @if ($bookingPromotion->applied_channel == 'admin')
                                                        Khách sạn hỗ trợ
                                                    @else
                                                        Khách tự chọn
                                                    @endif
                                                </td>
                                                <td class="text-end text-success fw-bold">
                                                    -{{ number_format((float) $bookingPromotion->discount_amount, 0, ',', '.') }}đ
                                                </td>
                                                <td class="small text-muted">
                                                    @if ($bookingPromotion->applied_channel == 'admin' && $bookingPromotion->promotion_type_snapshot == \App\Models\Promotion::TYPE_SUPPORT)
                                                        Mã hỗ trợ được khách sạn áp dụng cho đơn này.
                                                    @else
                                                        {{ $bookingPromotion->note ?: '---' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @php
                        $canCustomerAddService = in_array($booking->status, ['confirmed', 'checked_in'], true)
                            && in_array($booking->payment_status, ['partial', 'paid'], true);
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
                                                        data-type="{{ $service->type }}"
                                                        data-group="{{ $service->service_group ?? 'general' }}">
                                                        {{ $service->name }} - {{ number_format((float) $service->price, 0, ',', '.') }}đ / {{ $service->unit }}
                                                        - {{ $service->type === 'minibar_order' ? 'Minibar gọi thêm' : ($service->group_label ?? 'Dịch vụ') }}
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
                                Chỉ có thể tự thêm dịch vụ sau khi đơn đã thanh toán cọc/thanh toán đủ và được xác nhận.
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
                                                    @elseif ($item->type == 'minibar_order')
                                                        <span class="badge bg-info text-dark">Minibar gọi thêm</span>
                                                    @elseif ($item->type == 'minibar')
                                                        <span class="badge text-bg-warning">Minibar kiểm kê</span>
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

                        @php
                            $roomSelectionStatus = $booking->room_selection_status ?? 'not_required';
                            $isManualRoomSelection = ($booking->room_selection_mode ?? 'automatic') === 'manual';
                            $hideFallbackRoomNumbers = $isManualRoomSelection && $roomSelectionStatus === 'pending';
                            $showAssignedRoomNumbers = !$hideFallbackRoomNumbers
                                && !in_array($roomSelectionStatus, ['fallback_declined'], true);
                        @endphp

                        <h3 class="h6 fw-bold mb-3">Thông tin phòng</h3>

                        @if ($booking->status == 'pending' && $booking->payment_status == 'unpaid')
                            <div class="alert alert-warning small mb-3">
                                Đơn này đang chờ thanh toán để xác nhận.
                            </div>
                        @endif

                        @if ($isManualRoomSelection)
                            @if ($roomSelectionStatus === 'pending')
                                <div class="alert alert-info small mb-3">
                                    <strong>Đang chờ lễ tân xử lý yêu cầu phòng.</strong><br>
                                    Yêu cầu của bạn: {{ $booking->room_selection_request ?: '---' }}<br>
                                    Khách sạn đang giữ đủ số lượng phòng để tránh bán vượt, nhưng <strong>chưa công bố số phòng dự phòng</strong> cho đến khi có kết quả xử lý yêu cầu. Chưa thu phí đảm bảo yêu cầu phòng.
                                </div>
                            @elseif ($roomSelectionStatus === 'fulfilled')
                                <div class="alert alert-success small mb-3">
                                    <strong>Khách sạn đã đáp ứng yêu cầu phòng.</strong><br>
                                    Phí đảm bảo yêu cầu phòng: {{ number_format((float) ($booking->room_selection_fee ?? 0), 0, ',', '.') }}đ.
                                    @if ($booking->room_selection_handling_note)
                                        <br>Ghi chú: {{ $booking->room_selection_handling_note }}
                                    @endif
                                </div>
                            @elseif ($roomSelectionStatus === 'awaiting_guest')
                                <div class="alert alert-warning small mb-3">
                                    <strong>Khách sạn không thể đáp ứng đầy đủ yêu cầu đã ghi.</strong><br>
                                    @if ($booking->room_selection_handling_note)
                                        Lý do: {{ $booking->room_selection_handling_note }}<br>
                                    @endif
                                    Phòng dự phòng bên dưới vẫn đang được giữ cho bạn và <strong>không thu phí đảm bảo yêu cầu phòng</strong>. Vui lòng chọn Đồng ý nếu muốn tiếp tục, hoặc Từ chối để hủy booking. Nếu từ chối, khách sạn phải hoàn lại toàn bộ số tiền đã thanh toán.
                                </div>
                                <div class="d-grid gap-2 mb-3">
                                    <form action="{{ route('bookings.room-selection-fallback', $booking) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="decision" value="accept">
                                        <button class="btn btn-success w-100" type="submit"
                                            onclick="return confirm('Bạn đồng ý sử dụng phòng dự phòng đang được khách sạn giữ?');">
                                            Đồng ý sử dụng phòng dự phòng
                                        </button>
                                    </form>
                                    <form action="{{ route('bookings.room-selection-fallback', $booking) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="decision" value="decline">
                                        <button class="btn btn-outline-danger w-100" type="submit"
                                            onclick="return confirm('Từ chối phòng dự phòng sẽ hủy booking. Khách sạn sẽ phải hoàn lại toàn bộ số tiền bạn đã thanh toán. Tiếp tục?');">
                                            Từ chối phòng dự phòng và hủy booking
                                        </button>
                                    </form>
                                    <a href="{{ route('rooms') }}" class="btn btn-outline-primary w-100">Xem hạng phòng khác</a>
                                </div>
                            @elseif ($roomSelectionStatus === 'fallback_accepted')
                                <div class="alert alert-success small mb-3">
                                    <strong>Bạn đã đồng ý sử dụng phòng dự phòng.</strong><br>
                                    Booking tiếp tục giữ nguyên và không thu phí đảm bảo yêu cầu phòng.
                                </div>
                            @elseif ($roomSelectionStatus === 'fallback_declined')
                                <div class="alert alert-secondary small mb-3">
                                    <strong>Bạn đã từ chối phòng dự phòng và booking đã được hủy.</strong><br>
                                    @if ((float) ($booking->refund_due_amount ?? 0) > 0)
                                        Số tiền khách sạn phải hoàn lại: <strong>{{ number_format((float) $booking->refund_due_amount, 0, ',', '.') }}đ</strong>.
                                        Trạng thái hoàn tiền: <strong>{{ $booking->refund_status === 'completed' ? 'Đã hoàn tất' : 'Đang chờ xử lý' }}</strong>.
                                    @else
                                        Booking chưa phát sinh khoản thanh toán cần hoàn.
                                    @endif
                                    <div class="mt-2"><a href="{{ route('rooms') }}">Xem các hạng phòng khác phù hợp hơn</a>.</div>
                                </div>
                            @endif
                        @endif

                        @if ($showAssignedRoomNumbers)
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
                        @endif
                    </div>


                    @if (in_array($booking->status, ['checked_in', 'inspection_requested'], true) && $booking->actual_check_in)
                        <div class="settings-section mb-4" id="room-issue-request">
                            @php
                                $roomIssueGroup = $latestRoomIssueRequest ? $booking->roomIssueRequests->where('group_uuid', $latestRoomIssueRequest->group_uuid)->sortBy('id') : collect();
                                $issueRepairCompleted = $roomIssueGroup->isNotEmpty() && $roomIssueGroup->every(fn($i) => $i->repair_status === 'completed');
                                $issueDisplayStatus = $issueRepairCompleted
                                    ? 'repair_completed'
                                    : ($latestRoomIssueRequest?->status ?? null);
                                $issueStatusLabels = [
                                    'pending' => match($latestRoomIssueRequest?->workflow_status) {
                                        'waiting_guest_confirmation' => 'Đang trao đổi phương án',
                                        'guest_accepted' => 'Khách đã chọn phương án',
                                        'guest_requested_change' => 'Đang điều chỉnh phương án',
                                        'awaiting_housekeeping' => 'Chờ buồng phòng kiểm tra',
                                        'housekeeping_verified' => 'Buồng phòng đã xác nhận lỗi · chờ quản lý',
                                        'housekeeping_not_found' => 'Buồng phòng không phát hiện lỗi · chờ quản lý kết luận',
                                        default => 'Đang chờ quản lý',
                                    },
                                    'approved' => 'Đã đổi phòng',
                                    'repair_only' => 'Đang khắc phục',
                                    'repair_completed' => 'Đã sửa xong',
                                    'rejected' => 'Không được đổi phòng · yêu cầu đã đóng',
                                ];
                                $issueStatusClasses = [
                                    'pending' => 'text-bg-warning',
                                    'approved' => 'text-bg-success',
                                    'repair_only' => 'text-bg-info',
                                    'repair_completed' => 'text-bg-success',
                                    'rejected' => 'text-bg-secondary',
                                ];
                                $issueResolutionLabels = [
                                    'same_category' => 'Đổi phòng cùng hạng',
                                    'upgrade_category' => 'Nâng hạng phòng miễn phí',
                                    'no_room' => 'Giữ phòng hiện tại và sửa tại chỗ',
                                ];
                            @endphp

                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h3 class="h6 fw-bold mb-1">
                                        {{ $latestRoomIssueRequest ? 'Yêu cầu hỗ trợ phòng' : 'Phòng đang sử dụng có sự cố?' }}
                                    </h3>
                                    <p class="text-muted small mb-0">
                                        {{ $latestRoomIssueRequest
                                            ? 'Trạng thái mới nhất và kết quả xử lý được cập nhật tại đây.'
                                            : 'Báo ngay để khách sạn kiểm tra đổi phòng hoặc hỗ trợ khắc phục.' }}
                                    </p>
                                </div>
                                @if ($latestRoomIssueRequest)
                                    <span class="badge {{ $issueStatusClasses[$issueDisplayStatus] ?? 'text-bg-secondary' }}">
                                        {{ $issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus }}
                                    </span>
                                @endif
                            </div>

                            @if ($latestRoomIssueRequest)
                                <button type="button" class="btn btn-outline-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#roomIssueDetailModal">
                                    <i class="bx bx-detail me-1"></i> Xem chi tiết sự cố gần nhất
                                </button>
                            @endif
                            @if ($canRequestRoomIssue)
                                <button type="button" class="btn btn-danger w-100 mt-2" data-bs-toggle="modal" data-bs-target="#roomIssueModal">
                                    <i class="bx bx-error-circle me-1"></i>
                                    {{ $latestRoomIssueRequest ? 'Báo thêm sự cố phòng khác' : 'Báo sự cố / yêu cầu đổi phòng' }}
                                </button>
                            @endif
                        </div>

                        @if ($latestRoomIssueRequest)
                            <div class="modal fade" id="roomIssueDetailModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                        <div class="modal-header border-0 bg-light px-4 py-3">
                                            <div>
                                                <h5 class="modal-title fw-bold mb-1">Chi tiết sự cố phòng</h5>
                                                <div class="small text-muted">Yêu cầu gửi lúc {{ optional($latestRoomIssueRequest->created_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <span class="badge {{ $issueStatusClasses[$issueDisplayStatus] ?? 'text-bg-secondary' }}">{{ $issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus }}</span>
                                                                                            </div>
                                            @foreach($roomIssueGroup as $groupIssue)
                                                @php
                                                    $displayResolution = $groupIssue->resolution_type ?: $groupIssue->guest_selected_resolution_type ?: $groupIssue->proposed_resolution_type;
                                                    $targetRoom = $groupIssue->approvedRoom ?: $groupIssue->proposedRoom;
                                                    $inspectionNotFound = $groupIssue->housekeeping_verdict === 'not_found'
                                                        || $groupIssue->workflow_status === 'housekeeping_not_found';
                                                    $requestRejected = $groupIssue->status === 'rejected'
                                                        || $groupIssue->workflow_status === 'rejected';
                                                @endphp
                                                <div class="border rounded-3 p-3 mb-3">
                                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                                        <div>
                                                            <strong>Phòng {{ $groupIssue->currentRoom?->room_number ?? '---' }}</strong>
                                                            <div class="small text-muted">{{ $groupIssue->issue_description }}</div>
                                                        </div>
                                                        @if ($requestRejected)
                                                            <span class="badge text-bg-secondary">Không được đổi phòng · đã đóng</span>
                                                        @elseif ($inspectionNotFound)
                                                            <span class="badge text-bg-light border">Không phát hiện sự cố</span>
                                                        @else
                                                            <span class="badge text-bg-light border">{{ $issueResolutionLabels[$displayResolution] ?? ($displayResolution === 'repair_only' ? 'Giữ nguyên phòng và sửa tại chỗ' : 'Đang chờ phương án') }}</span>
                                                        @endif
                                                    </div>

                                                    @if ($inspectionNotFound || $requestRejected)
                                                        @if ($groupIssue->housekeeping_note)
                                                            <div class="alert alert-light border py-2 mt-3 mb-0">
                                                                <strong>Kết quả kiểm tra:</strong>
                                                                @if ($inspectionNotFound) Không phát hiện sự cố. @endif
                                                                {{ $groupIssue->housekeeping_note }}
                                                            </div>
                                                        @elseif ($inspectionNotFound)
                                                            <div class="alert alert-light border py-2 mt-3 mb-0"><strong>Kết quả kiểm tra:</strong> Không phát hiện sự cố.</div>
                                                        @endif

                                                        @if ($requestRejected)
                                                            <div class="alert alert-secondary py-2 mt-2 mb-0">
                                                                <strong>Kết luận:</strong> Yêu cầu đổi phòng không được chấp thuận.
                                                                @if ($groupIssue->admin_note)
                                                                    <br><strong>Lý do:</strong> {{ $groupIssue->admin_note }}
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="alert alert-info py-2 mt-2 mb-0">Đang chờ quản lý kết luận sau kết quả kiểm tra của buồng phòng.</div>
                                                        @endif
                                                    @else
                                                        @if ($targetRoom)
                                                            <div class="alert alert-info py-2 mt-3 mb-0">{{ $groupIssue->status === 'pending' ? 'Dự kiến đổi' : 'Đã đổi' }} sang phòng <strong>{{ $targetRoom->room_number }}</strong> · {{ $targetRoom->category?->name }}</div>
                                                        @elseif ($displayResolution === 'no_room' || $displayResolution === 'repair_only')
                                                            <div class="alert alert-warning py-2 mt-3 mb-0">Giữ nguyên phòng và chuyển buồng phòng khắc phục.</div>
                                                        @endif

                                                        @if($groupIssue->repair_status === 'completed')
                                                            <div class="alert alert-success py-2 mt-2 mb-0">
                                                                Đã sửa xong
                                                                @if($groupIssue->repair_note)
                                                                    : {{ $groupIssue->repair_note }}
                                                                @endif
                                                            </div>
                                                        @elseif($groupIssue->repair_status === 'waiting')
                                                            <div class="alert alert-secondary py-2 mt-2 mb-0">Buồng phòng đang xử lý riêng phòng này.</div>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                            <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Đóng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($canRequestRoomIssue)
                            <div class="modal fade" id="roomIssueModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                    <form action="{{ route('bookings.room-issues.store', $booking) }}" method="POST" enctype="multipart/form-data" id="userRoomIssueForm" class="modal-content border-0 shadow">
                                            @csrf
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title fw-bold">Báo sự cố phòng</h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-check border rounded-3 p-3 mb-3 bg-light">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="userSelectAllIssueRooms">
                                                    <label class="form-check-label fw-semibold" for="userSelectAllIssueRooms">Chọn tất cả phòng có thể báo sự cố</label>
                                                </div>

                                                <div class="row g-3">
                                                    @foreach ($booking->bookingRooms as $bookingRoom)
                                                        @continue(!$bookingRoom->room)
                                                        @php
                                                            $roomId = (int) $bookingRoom->room_id;
                                                            $blocked = $activeRoomIssueRoomIds->contains($roomId);
                                                            $selected = in_array($roomId, array_map('intval', old('selected_room_ids', [])), true);
                                                        @endphp
                                                        <div class="col-12">
                                                            <div class="border rounded-3 overflow-hidden {{ $blocked ? 'bg-light opacity-75' : '' }}">
                                                                <label class="d-flex align-items-start gap-2 p-3 mb-0 {{ $blocked ? '' : 'cursor-pointer' }}">
                                                                    <input type="checkbox"
                                                                           class="form-check-input mt-1 js-user-room-issue-selector"
                                                                           name="selected_room_ids[]"
                                                                           value="{{ $roomId }}"
                                                                           data-room-id="{{ $roomId }}"
                                                                           @checked($selected && !$blocked)
                                                                           @disabled($blocked)>
                                                                    <span>
                                                                        <strong>Phòng {{ $bookingRoom->room->room_number }}</strong>
                                                                        <span class="text-muted">· {{ $bookingRoom->room->category?->name ?? $booking->roomCategory?->name }}</span>
                                                                        @if($blocked)
                                                                            <span class="d-block small text-warning mt-1">Phòng này đang có yêu cầu sự cố chưa hoàn tất.</span>
                                                                        @endif
                                                                    </span>
                                                                </label>

                                                                @if(!$blocked)
                                                                    <div class="border-top bg-light p-3 d-none" id="userRoomIssueDetail{{ $roomId }}">
                                                                        <label class="form-label fw-semibold">Sự cố của phòng {{ $bookingRoom->room->room_number }}</label>
                                                                        <textarea name="issues[{{ $roomId }}][description]"
                                                                                  class="form-control mb-3"
                                                                                  rows="4" minlength="10" maxlength="2000"
                                                                                  placeholder="Mô tả rõ sự cố riêng của phòng {{ $bookingRoom->room->room_number }}..."
                                                                                  disabled>{{ old("issues.$roomId.description") }}</textarea>

                                                                        <label class="form-label fw-semibold">Ảnh minh chứng của phòng {{ $bookingRoom->room->room_number }} <span class="text-muted fw-normal">(tối đa 5 ảnh)</span></label>
                                                                        <input type="file"
                                                                               id="userRoomIssueImages{{ $roomId }}"
                                                                               name="issues[{{ $roomId }}][images][]"
                                                                               class="form-control js-camera-capture-input"
                                                                               accept="image/jpeg,image/png,image/webp"
                                                                               multiple
                                                                               data-persistent-files
                                                                               data-camera-button="#userRoomIssueCameraButton{{ $roomId }}"
                                                                               data-scan-side="photo"
                                                                               disabled>
                                                                        <div class="d-flex gap-2 align-items-center mt-2 flex-wrap">
                                                                            <button type="button"
                                                                                    id="userRoomIssueCameraButton{{ $roomId }}"
                                                                                    class="btn btn-outline-primary btn-sm js-open-camera js-user-room-camera"
                                                                                    data-target-input="#userRoomIssueImages{{ $roomId }}"
                                                                                    disabled>
                                                                                <i class="bx bx-camera me-1"></i> Chụp bằng camera
                                                                            </button>
                                                                            <span class="small text-muted">Có thể chọn ảnh có sẵn hoặc chụp trực tiếp.</span>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Để sau</button>
                                                <button type="submit" class="btn btn-danger" id="userSubmitRoomIssues" disabled>
                                                    Xác nhận gửi quản lý (<span id="userSelectedRoomCount">0</span> yêu cầu)
                                                </button>
                                            </div>
                                    </form>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const selectors = Array.from(document.querySelectorAll('.js-user-room-issue-selector:not(:disabled)'));
                                    const selectAll = document.getElementById('userSelectAllIssueRooms');
                                    const submit = document.getElementById('userSubmitRoomIssues');
                                    const count = document.getElementById('userSelectedRoomCount');

                                    function syncRoom(checkbox) {
                                        const detail = document.getElementById('userRoomIssueDetail' + checkbox.dataset.roomId);
                                        if (!detail) return;
                                        detail.classList.toggle('d-none', !checkbox.checked);
                                        detail.querySelectorAll('textarea,input[type="file"]').forEach(function (field) {
                                            field.disabled = !checkbox.checked;
                                            if (field.tagName === 'TEXTAREA') field.required = checkbox.checked;
                                        });
                                        detail.querySelectorAll('.js-user-room-camera').forEach(function (button) {
                                            button.disabled = !checkbox.checked;
                                        });
                                    }

                                    function syncAll() {
                                        selectors.forEach(syncRoom);
                                        const selected = selectors.filter(function (item) { return item.checked; }).length;
                                        if (count) count.textContent = selected;
                                        if (submit) submit.disabled = selected === 0;
                                        if (selectAll) {
                                            selectAll.checked = selectors.length > 0 && selected === selectors.length;
                                            selectAll.indeterminate = selected > 0 && selected < selectors.length;
                                        }
                                    }

                                    selectors.forEach(function (checkbox) {
                                        checkbox.addEventListener('change', syncAll);
                                    });
                                    if (selectAll) {
                                        selectAll.addEventListener('change', function () {
                                            selectors.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
                                            syncAll();
                                        });
                                    }
                                    syncAll();
                                });
                            </script>
                        @endif
                    @endif


                    @php
                        $review = $booking->hotelReview ?? null;
                        $reviewEligible = $canReviewBooking ?? in_array($booking->status, ['checked_out', 'completed'], true);
                    @endphp

                    <div class="settings-section mb-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">Đánh giá khách sạn</h3>
                            </div>
                            @if ($review)
                                <span class="badge {{ $review->status_badge_class }}">{{ $review->status_label }}</span>
                            @endif
                        </div>

                        @if ($review)
                            <div class="border rounded-3 p-3">
                                <div class="text-warning mb-1">{{ $review->star_text }} <span class="text-muted small">{{ number_format((float) $review->rating, 1) }}/5</span></div>
                                @if ($review->title)
                                    <div class="fw-semibold mb-1">{{ $review->title }}</div>
                                @endif
                                <p class="small text-muted mb-2">{{ $review->comment }}</p>

                                @if ($review->admin_reply)
                                    <div class="alert alert-info small mb-2">
                                        <div class="fw-semibold mb-1">Phản hồi từ khách sạn</div>
                                        {{ $review->admin_reply }}
                                    </div>
                                @endif

                                <a href="{{ route('reviews.edit', $review) }}" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bx bx-edit me-1"></i>
                                    Chỉnh sửa đánh giá
                                </a>
                            </div>
                        @elseif ($reviewEligible)
                            <a href="{{ route('bookings.reviews.create', $booking) }}" class="btn btn-primary w-100">
                                <i class="bx bx-star me-1"></i>
                                Đánh giá kỳ lưu trú
                            </a>
                        @else
                            <div class="alert alert-light border small mb-0">
                                Bạn sẽ có thể đánh giá sau khi đơn phòng được trả phòng/hoàn tất.
                            </div>
                        @endif
                    </div>



                    @if (($canUseLateArrivalFlow ?? false) || !empty($latestLateArrivalRequest))
                        @php
                            $lateStatus = $latestLateArrivalRequest?->status;
                            $lateStatusLabel = match ($lateStatus) {
                                'pending' => 'Đang xử lý',
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Đã từ chối',
                                default => null,
                            };
                            $lateStatusClass = match ($lateStatus) {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-warning text-dark',
                            };
                            $lateButtonText = !$latestLateArrivalRequest
                                ? 'Báo đến muộn'
                                : ($lateStatus === 'pending' ? 'Cập nhật yêu cầu' : 'Xem chi tiết');
                            $lateButtonRoute = $latestLateArrivalRequest && $lateStatus !== 'pending'
                                ? route('bookings.customer-requests.show', $booking)
                                : route('bookings.customer-requests.create', $booking);
                        @endphp
                        <div class="settings-section mb-4" id="late-arrival-request">
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <div>
                                    <h4 class="mb-1">Đến muộn</h4>
                                    @if ($lateStatusLabel)
                                        <span class="badge {{ $lateStatusClass }}">{{ $lateStatusLabel }}</span>
                                    @endif
                                </div>
                                <a class="btn btn-primary" href="{{ $lateButtonRoute }}">{{ $lateButtonText }}</a>
                            </div>
                        </div>
                    @endif

                    @if (($canCustomerCancel ?? false) && (($booking->room_selection_status ?? null) !== 'awaiting_guest'))
                        <div class="settings-section" id="cancel-policy">
                            <h3 class="h6 fw-bold mb-3">Thao tác hủy đơn</h3>
                            <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST"
                                class="js-cancel-booking-form"
                                data-mode="direct"
                                data-policy="{{ $cancellationPolicy['label'] ?? 'Theo chính sách hủy' }}"
                                data-paid="{{ $cancellationPolicy['paid_amount'] ?? 0 }}"
                                data-forfeit="{{ $cancellationPolicy['forfeit_amount'] ?? 0 }}"
                                data-credit="0"
                                data-cutoff="{{ optional($cancellationCutoff)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}">
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

@once
<div class="modal fade" id="cancelBookingPolicyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
    <div class="modal-header">
      <h5 class="modal-title" id="cancelModalTitle">Xác nhận hủy đơn</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning" id="cancelModeMessage"></div>
      <div class="d-grid gap-2">
        <div class="d-flex justify-content-between gap-3"><span>Chính sách áp dụng</span><strong class="text-end" id="cancelPolicyLabel"></strong></div>
        <div class="d-flex justify-content-between"><span>Đã thanh toán</span><strong id="cancelPaid"></strong></div>
        <div class="d-flex justify-content-between text-danger"><span>Khách sạn giữ lại</span><strong id="cancelForfeit"></strong></div>
        <div class="d-flex justify-content-between"><span>Tiền hoàn lại</span><strong>0đ</strong></div>
      </div>
      <div class="mt-3 d-none" id="cancelReasonWrap">
        <label for="cancelReason" class="form-label fw-semibold">Lý do hủy <span class="text-muted fw-normal">(không bắt buộc)</span></label>
        <textarea id="cancelReason" class="form-control" rows="3" maxlength="1000" placeholder="Ví dụ: thay đổi lịch trình, không thể đến đúng kế hoạch..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Không hủy</button>
      <button type="button" class="btn btn-danger" id="confirmCancelBookingButton">Đồng ý hủy đơn</button>
    </div>
  </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeForm = null;
    const formatMoney = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    const modalElement = document.getElementById('cancelBookingPolicyModal');
    const confirmButton = document.getElementById('confirmCancelBookingButton');

    document.querySelectorAll('.js-cancel-booking-form').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            activeForm = form;

            const requestMode = form.dataset.mode === 'request';
            document.getElementById('cancelModalTitle').textContent = requestMode
                ? 'Xác nhận hủy qua email'
                : 'Xác nhận hủy đơn';
            document.getElementById('cancelModeMessage').textContent = requestMode
                ? 'Mã xác nhận sẽ được gửi về email.'
                : 'Hủy đơn sẽ mất toàn bộ khoản đã thanh toán, bao gồm tiền cọc ' + @json($detailDepositLabel) + '. Khoản này không được hoàn lại và không được bảo lưu.';
            document.getElementById('cancelPolicyLabel').textContent = form.dataset.policy || 'Theo chính sách hủy';
            document.getElementById('cancelPaid').textContent = formatMoney(form.dataset.paid);
            document.getElementById('cancelForfeit').textContent = formatMoney(form.dataset.forfeit);
            document.getElementById('cancelReasonWrap').classList.toggle('d-none', !requestMode);
            confirmButton.textContent = requestMode ? 'Gửi mã xác nhận' : 'Đồng ý hủy đơn';

            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });

    confirmButton?.addEventListener('click', () => {
        if (!activeForm) return;
        const oldInput = activeForm.querySelector('input[name="reason"]');
        if (oldInput) oldInput.remove();

        if (activeForm.dataset.mode === 'request') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reason';
            input.value = document.getElementById('cancelReason').value || '';
            activeForm.appendChild(input);
        }

        confirmButton.disabled = true;
        activeForm.submit();
    });
});
</script>
@endonce


@include('partials.camera-capture')


<script src="{{ asset('assets/js/persistent-file-inputs.js') }}?v={{ filemtime(public_path('assets/js/persistent-file-inputs.js')) }}"></script>
@endsection
