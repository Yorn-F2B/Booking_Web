@php
    use App\Support\Money;
    use App\Support\BookingBilling;

    $billing = BookingBilling::summary($booking);
@endphp

<div class="table-responsive">
    <table class="table table-bordered align-middle mb-0">
        <tbody>
            <tr>
                <th width="220">Tạm tính (phòng &amp; dịch vụ)</th>
                <td class="text-end">{{ Money::vnd($billing['subtotal']) }}</td>
            </tr>

            @if ($billing['discount'] > 0)
                <tr>
                    <th>Ưu đãi / giảm giá</th>
                    <td class="text-end text-success fw-semibold">-{{ Money::vnd($billing['discount']) }}</td>
                </tr>

                <tr>
                    <th>Thành tiền sau ưu đãi</th>
                    <td class="text-end">{{ Money::vnd($billing['base_after_discount']) }}</td>
                </tr>
            @endif

            @if ($billing['extra_charges'] > 0)
                <tr>
                    <th>Dịch vụ / phụ thu phát sinh</th>
                    <td class="text-end text-danger fw-semibold">+{{ Money::vnd($billing['extra_charges']) }}</td>
                </tr>
            @endif

            @if ($billing['vat_rate'] > 0)
                <tr>
                    <th>Giá chưa gồm thuế</th>
                    <td class="text-end text-muted">{{ Money::vnd($billing['net_amount']) }}</td>
                </tr>
                <tr>
                    <th>Trong đó thuế VAT ({{ rtrim(rtrim(number_format($billing['vat_rate'], 2, ',', '.'), '0'), ',') }}%)</th>
                    <td class="text-end text-muted">{{ Money::vnd($billing['vat_amount']) }}</td>
                </tr>
            @endif

            <tr class="table-light">
                <th class="fw-bold">Tổng cộng</th>
                <td class="text-end fw-bold fs-5 text-primary">{{ Money::vnd($billing['total']) }}</td>
            </tr>

            <tr>
                <th>Đã thanh toán / đã cọc</th>
                <td class="text-end fw-semibold">{{ Money::vnd($billing['paid']) }}</td>
            </tr>

            <tr>
                <th class="text-danger">Còn lại phải thanh toán</th>
                <td class="text-end fw-bold text-danger">{{ Money::vnd($billing['remaining']) }}</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="small text-muted mb-0 mt-2">
    @if ($billing['vat_rate'] > 0)
        Giá đã bao gồm thuế VAT {{ rtrim(rtrim(number_format($billing['vat_rate'], 2, ',', '.'), '0'), ',') }}% theo quy định.
    @else
        Giá đã bao gồm VAT theo quy định.
    @endif
    Số tiền cọc/thanh toán không hoàn lại khi khách hủy đơn.
</p>
