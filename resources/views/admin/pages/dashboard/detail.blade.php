@extends('layouts.admin')
@section('title', $title)
@section('content')
<div class="admin-wrapper"><div class="admin-content">
    <div class="admin-page-head d-flex justify-content-between align-items-start gap-3">
        <div><h1>{{ $title }}</h1><p>{{ $formula }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}"><i class="bx bx-arrow-back me-1"></i>Dashboard</a>
    </div>
    <div class="card mb-3"><div class="card-body d-flex justify-content-between flex-wrap gap-2"><span><strong>Kỳ:</strong> {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span><span><strong>{{ in_array($metric,['new_bookings','failed_emails']) ? 'Số lượng' : 'Tổng' }}:</strong> {{ in_array($metric,['new_bookings','failed_emails']) ? number_format($total,0,',','.') : number_format($total,0,',','.') . 'đ' }}</span></div></div>
    <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Booking</th><th>Khách / người nhận</th><th>Loại / trạng thái</th><th>Thời điểm</th><th class="text-end">Số tiền</th><th></th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td>{{ $row['booking'] ?: '—' }}</td><td>{{ $row['customer'] ?: '—' }}</td><td>{{ $row['kind'] ?: '—' }}</td><td>{{ $row['time'] ? \Carbon\Carbon::parse($row['time'])->format('d/m/Y H:i') : '—' }}</td><td class="text-end">{{ $row['amount'] === null ? '—' : number_format($row['amount'],0,',','.') . 'đ' }}</td><td class="text-end">@if($row['url'])<a href="{{ $row['url'] }}" class="btn btn-sm btn-outline-primary">Xem</a>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">Không có dữ liệu trong kỳ.</td></tr>@endforelse
    </tbody></table></div></div>
</div></div>
@endsection
