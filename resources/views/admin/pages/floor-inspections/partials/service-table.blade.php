@if ($services->isEmpty())
    <div class="text-muted">Chưa có danh mục phù hợp.</div>
@else
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light"><tr><th style="width:60px">Chọn</th><th>Hạng mục</th><th>Đơn giá</th><th style="width:120px">Số lượng</th><th>Tạm tính</th></tr></thead>
            <tbody>
                @foreach ($services as $service)
                    @php
                        $oldItem = $itemMap[$service->id] ?? null;
                        $checked = (bool) $oldItem;
                        $quantity = $oldItem?->quantity ?? 1;
                    @endphp
                    <tr>
                        <td><input type="checkbox" name="{{ $checkboxName }}" value="{{ $service->id }}" class="form-check-input {{ $checkboxClass }}" @checked($checked)></td>
                        <td><strong>{{ $service->name }}</strong>@if($service->description)<div class="small text-muted">{{ $service->description }}</div>@endif</td>
                        <td>{{ number_format((float) $service->price, 0, ',', '.') }}đ / {{ $service->unit ?: 'lần' }}</td>
                        <td><input type="number" min="1" name="{{ $quantityName }}[{{ $service->id }}]" value="{{ $quantity }}" class="form-control form-control-sm inspection-service-quantity" data-price="{{ (float) $service->price }}" {{ $checked ? '' : 'disabled' }}></td>
                        <td class="fw-semibold inspection-service-total">{{ $checked ? number_format((float) $service->price * $quantity, 0, ',', '.') . 'đ' : '0đ' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
