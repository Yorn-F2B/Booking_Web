@extends('layouts.admin')
@section('title', $title)
@section('content')
<div class="admin-wrapper"><div class="admin-content">
    <div class="admin-page-head d-flex justify-content-between align-items-start gap-3">
        <div><h1>{{ $title }}</h1><p>{{ $formula }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}"><i class="bx bx-arrow-back me-1"></i>Dashboard</a>
    </div>
    <div class="card mb-3"><div class="card-body d-flex justify-content-between flex-wrap gap-2"><span><strong>Kỳ:</strong> {{ $from->format('d/m/Y') }} – {{ $to->format('d/m/Y') }}</span><span><strong>{{ $totalLabel }}:</strong> @if($metric === 'new_bookings' || $valueType === 'integer'){{ number_format($total,0,',','.') }}@elseif($valueType === 'number'){{ number_format($total,2,',','.') }}@elseif($valueType === 'none'){{ number_format($total,0,',','.') }}@else{{ number_format($total,0,',','.') }}đ@endif</span></div></div>
    <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Booking / phòng</th><th>Khách / hạng phòng</th><th>Loại / trạng thái</th><th>Thời điểm</th>@if($valueType !== 'none')<th class="text-end">{{ $valueLabel }}</th>@endif<th></th></tr></thead><tbody>
    @forelse($rows as $row)<tr><td>{{ $row['booking'] ?: '—' }}</td><td>{{ $row['customer'] ?: '—' }}</td><td>{{ $row['kind'] ?: '—' }}</td><td>{{ $row['time'] ? \Carbon\Carbon::parse($row['time'])->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') : '—' }}</td>@if($valueType !== 'none')<td class="text-end">@if($row['amount'] === null)—@elseif($valueType === 'money'){{ number_format($row['amount'],0,',','.') }}đ@elseif($valueType === 'integer'){{ number_format($row['amount'],0,',','.') }}@else{{ number_format($row['amount'],2,',','.') }}@endif</td>@endif<td class="text-end">@if($row['url'])<a href="{{ $row['url'] }}" class="btn btn-sm btn-outline-primary">Xem</a>@endif</td></tr>@empty<tr><td colspan="{{ $valueType !== 'none' ? 6 : 5 }}" class="text-center text-muted py-5">Không có dữ liệu trong kỳ.</td></tr>@endforelse
    </tbody></table></div></div>
</div></div>
@endsection
