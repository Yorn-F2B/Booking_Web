@extends('layouts.user')

@section('title', 'Chi tiết đơn phòng')

@section('content')

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
                                @if ($booking->status == 'pending')
                                    <span class="badge text-bg-warning">Chờ xác nhận</span>
                                @elseif ($booking->status == 'confirmed')
                                    <span class="badge text-bg-primary">Đã xác nhận</span>
                                @elseif ($booking->status == 'checked_in')
                                    <span class="badge text-bg-info">Đã nhận phòng</span>
                                @elseif ($booking->status == 'checked_out')
                                    <span class="badge text-bg-success">Đã trả phòng</span>
                                @else
                                    <span class="badge text-bg-danger">Đã hủy</span>
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
                                        <td>{{ date('d/m/Y', strtotime($booking->check_in_date)) }}</td>
                                    </tr>

                                    <tr>
                                        <th>Ngày trả phòng</th>
                                        <td>{{ date('d/m/Y', strtotime($booking->check_out_date)) }}</td>
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
                                                Đã hoàn tiền
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

                </div>

                <div class="col-lg-4">

                    <div class="settings-section mb-4">

                        <h3 class="h6 fw-bold mb-3">
                            Phòng đã được gán
                        </h3>

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

                    </div>

                    @if (in_array($booking->status, ['pending', 'confirmed']))

                        <div class="settings-section">

                            <h3 class="h6 fw-bold mb-3">
                                Thao tác
                            </h3>

                            <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST"
                                onsubmit="return confirm('Bạn có chắc muốn hủy đơn đặt phòng này không?')">

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

@endsection