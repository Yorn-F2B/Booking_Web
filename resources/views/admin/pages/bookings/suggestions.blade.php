@extends('layouts.admin')

@section('title', 'Gợi ý phòng thay thế')

@section('content')

    <style>
        .suggestion-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            height: 100%;
            overflow: hidden;
        }

        .suggestion-card-head {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-card-body {
            padding: 16px;
        }

        .suggestion-room-group {
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 10px;
        }

        .suggestion-room-group strong {
            display: block;
            color: #1f2937;
        }

        .suggestion-sub-text {
            font-size: 13px;
            color: #64748b;
        }

        .request-box {
            border: 1px solid #facc15;
            background: #fef9c3;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 18px;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Đặt phòng / Gợi ý phòng thay thế
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Gợi ý phòng thay thế</h2>
                    <p>Hạng phòng đã chọn không đủ số lượng. Lễ tân chọn một phương án bên dưới để tạo booking nhanh.</p>
                </div>

                <a href="{{ route('admin.bookings.create') }}" class="btn btn-outline-secondary">
                    Quay lại tạo booking
                </a>
            </div>

            <div class="request-box">
                <strong>Yêu cầu ban đầu</strong>

                <div class="row mt-2">
                    <div class="col-md-3">
                        <div class="suggestion-sub-text">Hạng phòng</div>
                        <strong>{{ $roomCategory->name }}</strong>
                    </div>

                    <div class="col-md-2">
                        <div class="suggestion-sub-text">Số phòng</div>
                        <strong>{{ $data['room_quantity'] }}</strong>
                    </div>

                    <div class="col-md-2">
                        <div class="suggestion-sub-text">Ngày nhận</div>
                        <strong>{{ date('d/m/Y', strtotime($data['check_in_date'])) }}</strong>
                    </div>

                    <div class="col-md-2">
                        <div class="suggestion-sub-text">Ngày trả</div>
                        <strong>{{ date('d/m/Y', strtotime($data['check_out_date'])) }}</strong>
                    </div>

                    <div class="col-md-3">
                        <div class="suggestion-sub-text">Yêu cầu cạnh nhau</div>
                        <strong>{{ $preferAdjacentRooms ? 'Có' : 'Không' }}</strong>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                @foreach ($suggestions as $index => $suggestion)
                    <div class="col-md-6 col-xl-4">
                        <div class="suggestion-card">

                            <div class="suggestion-card-head">
                                <div>
                                    <h5 class="mb-0">Phương án {{ $index + 1 }}</h5>
                                    <div class="suggestion-sub-text">
                                        {{ $suggestion['label'] ?? 'Phương án thay thế' }}
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark">
                                    {{ count($suggestion['rooms']) }} phòng
                                </span>
                            </div>

                            <div class="px-3 pt-2">

                                <div class="text-muted small">
                                    Tổng tiền tạm tính
                                </div>

                                <h4 class="fw-bold text-primary mb-0">
                                    {{ number_format($suggestion['estimated_total'], 0, ',', '.') }}đ
                                </h4>

                                <small class="text-muted">
                                    {{ $suggestion['night_count'] }} đêm
                                </small>

                            </div>

                            <div class="suggestion-card-body">
                                @foreach ($suggestion['summary'] as $item)
                                    <div class="suggestion-room-group">
                                        <strong>
                                            {{ $item['quantity'] }} phòng {{ $item['category_name'] }}
                                        </strong>

                                        <div class="suggestion-sub-text">
                                            Tầng:
                                            {{ $item['floors']->implode(', ') }}
                                        </div>

                                        <div class="suggestion-sub-text">
                                            Giá:
                                            {{ number_format($item['price'], 0, ',', '.') }}đ / đêm
                                        </div>

                                        <div class="suggestion-sub-text">
                                            Phòng: {{ implode(', ', $item['rooms']->toArray()) }}
                                        </div>
                                    </div>
                                @endforeach

                                <form method="POST" action="{{ route('admin.bookings.suggestions.store') }}" class="mt-3">
                                    @csrf

                                    @foreach ($data as $key => $value)
                                        @if (is_array($value))
                                            @foreach ($value as $index => $item)
                                                @if (is_array($item))
                                                    @foreach ($item as $subKey => $subValue)
                                                        <input type="hidden" name="{{ $key }}[{{ $index }}][{{ $subKey }}]" value="{{ $subValue }}">
                                                    @endforeach
                                                @else
                                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                                @endif
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach

                                    @foreach ($suggestion['rooms'] as $room)
                                        <input type="hidden" name="selected_room_ids[]" value="{{ $room->id }}">
                                    @endforeach

                                    <button type="submit" class="btn btn-gold w-100">
                                        Chọn phương án này
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection