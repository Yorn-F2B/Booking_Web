@extends('layouts.admin')
@section('content')
<div class="admin-wrapper">
<main class="admin-content">
<div class="container-fluid px-0">
    <div class="admin-page-head d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Yêu cầu đến muộn</h1>
    </div>

    <form class="card card-body row g-2 mb-3 mx-0">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                @foreach(['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','completed'=>'Hoàn tất'] as $key=>$label)
                    <option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col"><button class="btn btn-primary">Lọc</button></div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead><tr><th>Mã đơn</th><th>Khách</th><th>Giờ dự kiến đến</th><th>Nguồn</th><th>Trạng thái</th><th>Ngày gửi</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->booking?->booking_code }}</td>
                        <td>{{ $item->customer_name }}</td>
                        <td>{{ optional($item->expected_arrival_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->source==='customer_web' ? 'Website' : 'Email vãng lai' }}</td>
                        <td>{{ $item->status_label }}</td>
                        <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.customer-requests.show',$item) }}">Xử lý</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có yêu cầu đến muộn</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $items->links() }}</div>
</div>
</main>
</div>
@endsection
