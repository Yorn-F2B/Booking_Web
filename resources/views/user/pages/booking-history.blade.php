@extends('layouts.user')

@section('title', 'Booking history')

@section('content')
    <section class="page-header">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="display-6 fw-bold mb-1">Lịch sử đặt phòng</h1>
                <p class="text-muted mb-0">Theo dõi các đơn đã đặt, trạng thái thanh toán và mã xác nhận.</p>
            </div>
            <a href="rooms.html" class="btn btn-outline-primary">Đặt phòng mới</a>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Tìm theo mã đơn (VD: MC2026-001)" />
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option>Tất cả trạng thái</option>
                                <option>Đã xác nhận</option>
                                <option>Đã hoàn tất</option>
                                <option>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option>Tất cả khoảng thời gian</option>
                                <option>30 ngày gần nhất</option>
                                <option>3 tháng gần nhất</option>
                                <option>12 tháng gần nhất</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="button">Lọc</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Phòng</th>
                                <th>Nhận/Trả phòng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>MC2026-001</td>
                                <td>Phòng Deluxe hướng biển</td>
                                <td>08/05/2026 - 10/05/2026</td>
                                <td>3.600.000đ</td>
                                <td><span class="badge text-bg-success">Đã xác nhận</span></td>
                                <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#bookingDetailModal" data-booking-id="MC2026-001">Chi tiết</button>
                                </td>
                            </tr>
                            <tr>
                                <td>MC2026-002</td>
                                <td>Suite gia đình</td>
                                <td>15/04/2026 - 17/04/2026</td>
                                <td>6.400.000đ</td>
                                <td><span class="badge text-bg-secondary">Đã hoàn tất</span></td>
                                <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#bookingDetailModal" data-booking-id="MC2026-002">Chi tiết</button>
                                </td>
                            </tr>
                            <tr>
                                <td>MC2026-003</td>
                                <td>Phòng Premier hướng phố</td>
                                <td>01/03/2026 - 02/03/2026</td>
                                <td>1.400.000đ</td>
                                <td><span class="badge text-bg-danger">Đã hủy</span></td>
                                <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#bookingDetailModal" data-booking-id="MC2026-003">Chi tiết</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection