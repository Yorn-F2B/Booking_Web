@extends('layouts.admin')
@section('title', 'Từ cấm đánh giá')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h3 class="mb-1">Từ cấm đánh giá</h3><p class="text-muted mb-0">Đánh giá chứa một trong các từ bên dưới sẽ bị từ chối và không ghi vào database.</p></div>
        <a href="{{ route('admin.banned-words.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> Thêm từ cấm</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card border-0 shadow-sm"><div class="card-body">
        @forelse($words as $item)
            <span class="d-inline-flex align-items-center gap-2 border rounded-pill px-3 py-2 me-2 mb-2 bg-light">
                <strong>{{ $item->word }}</strong>
                <form method="POST" action="{{ route('admin.banned-words.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Xóa từ cấm này?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm p-0 border-0 text-danger" title="Xóa ngay" aria-label="Xóa {{ $item->word }}"><i class="bx bx-x fs-5"></i></button>
                </form>
            </span>
        @empty
            <div class="text-muted text-center py-5">Chưa có từ cấm nào.</div>
        @endforelse
    </div></div>
</div>
@endsection
