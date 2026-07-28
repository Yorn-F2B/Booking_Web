@extends('layouts.user')

@section('title', 'Chi tiết yêu cầu đến muộn')

@section('content')
@php
    $statusLabel = match ($customerRequest->status) {
        'approved' => 'Đã duyệt',
        'rejected' => 'Đã từ chối',
        default => 'Đang xử lý',
    };
    $statusClass = match ($customerRequest->status) {
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        default => 'bg-warning text-dark',
    };
@endphp
<div class="container py-4">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                <h2 class="h4 mb-0">Chi tiết yêu cầu đến muộn</h2>
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>

            <dl class="row mb-4">
                <dt class="col-sm-4">Giờ dự kiến đến</dt>
                <dd class="col-sm-8">{{ optional($customerRequest->expected_arrival_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-4">Lý do</dt>
                <dd class="col-sm-8">{{ $customerRequest->reason }}</dd>
                @if ($customerRequest->admin_note)
                    <dt class="col-sm-4">Phản hồi khách sạn</dt>
                    <dd class="col-sm-8">{{ $customerRequest->admin_note }}</dd>
                @endif
            </dl>

            @if ($customerRequest->attachments->isNotEmpty())
                <div class="row g-3 mb-4">
                    @foreach ($customerRequest->attachments as $attachment)
                        <div class="col-6 col-md-4">
                            @if (str_starts_with((string) $attachment->mime_type, 'image/'))
                                <a href="{{ route('bookings.customer-requests.attachment', [$booking, $attachment]) }}" target="_blank">
                                    <img src="{{ route('bookings.customer-requests.attachment', [$booking, $attachment]) }}" class="img-fluid rounded border" alt="{{ $attachment->original_name }}" style="height:150px;width:100%;object-fit:cover;">
                                </a>
                            @else
                                <a class="btn btn-outline-secondary w-100" href="{{ route('bookings.customer-requests.attachment', [$booking, $attachment]) }}" target="_blank">{{ $attachment->original_name }}</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary">Quay lại đơn phòng</a>
        </div>
    </div>
</div>
@endsection
