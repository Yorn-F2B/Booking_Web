@if ($items->isEmpty())
    <div class="soft-note">{{ $emptyText ?? 'Chưa có khoản nào.' }}</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-clean align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên khoản thu</th>
                    <th>Phạm vi</th>
                    <th>Loại</th>
                    <th>Đơn giá</th>
                    <th>SL</th>
                    <th>Dùng</th>
                    <th>Thành tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    @php
                        $typeLabels = \App\Models\Service::typeLabels();
                        $isSurcharge = in_array($item->type, \App\Models\Service::surchargeCatalogTypes(), true)
                            || $item->type === 'violation_fee';
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $item->name }}</strong>
                            @if ($item->billing_status === 'pending')
                                <span class="badge bg-warning text-dark ms-1">Khách yêu cầu · Chờ xác nhận</span>
                            @elseif ($item->billing_status === 'unused')
                                <span class="badge bg-secondary ms-1">Chưa sử dụng</span>
                            @elseif ($item->billing_status === 'cancelled')
                                <span class="badge bg-secondary ms-1">Đã hủy</span>
                            @endif
                            @if ($item->note)
                                <div class="text-muted small">{{ $item->note }}</div>
                            @endif
                        </td>
                        <td>
                            @if (($item->scope ?? 'booking') === 'room')
                                <span class="badge bg-primary">Phòng {{ $item->bookingRoom?->room?->room_number ?? $item->roomSnapshot?->room_number ?? '---' }}</span>
                            @else
                                <span class="badge bg-secondary">Toàn bộ đơn</span>
                            @endif
                        </td>
                        <td>
                            @if ($isSurcharge)
                                <span class="badge-clean status-muted">{{ $typeLabels[$item->type] ?? 'Phụ thu' }}</span>
                            @elseif ($item->type === 'minibar_order')
                                <span class="badge bg-info text-dark">Minibar gọi thêm</span>
                            @elseif ($item->type === 'minibar')
                                <span class="badge-clean status-warning">Minibar</span>
                            @else
                                <span class="badge-clean status-info">{{ $typeLabels[$item->type] ?? 'Dịch vụ' }}</span>
                            @endif
                        </td>
                        <td>{{ number_format((float) $item->unit_price, 0, ',', '.') }}đ</td>
                        <td style="min-width: 120px;">
                            @if ($canEditServiceItems && in_array($item->type, ['service', 'minibar_order'], true))
                                <form
                                    action="{{ route('admin.bookings.service-items.update', [$booking->id, $item->id]) }}"
                                    method="POST" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" class="form-control form-control-sm"
                                        value="{{ $item->quantity }}" min="1" max="999" style="width: 72px;">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        {{ $item->billing_status === 'pending' ? 'Xác nhận' : 'Lưu' }}
                                    </button>
                                </form>
                            @else
                                {{ $item->quantity }}
                            @endif
                        </td>
                        <td>{{ $item->billing_status === 'pending' ? 0 : ($item->used_quantity ?? $item->quantity) }}</td>
                        <td class="fw-bold {{ $item->billing_status === 'pending' ? 'text-warning' : 'text-danger' }}">
                            @if ($item->billing_status === 'pending')
                                Chờ xác nhận
                            @else
                                {{ number_format((float) $item->total, 0, ',', '.') }}đ
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($canEditServiceItems && in_array($item->type, ['service', 'minibar_order'], true))
                                <form
                                    action="{{ route('admin.bookings.service-items.destroy', [$booking->id, $item->id]) }}"
                                    method="POST" onsubmit="return confirm('Xóa dịch vụ này khỏi đơn?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        {{ $item->billing_status === 'pending' ? 'Từ chối' : 'Xóa' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
