@extends('layouts.admin')

@section('title', 'Từ cấm đánh giá')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <div class="admin-page-head">
            <div>
                <h2>Từ cấm đánh giá</h2>
                <p>Đánh giá chứa nội dung bị cấm sẽ bị từ chối trước khi ghi vào hệ thống.</p>
            </div>
            <a href="{{ route('admin.banned-words.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>
                Thêm từ cấm
            </a>
        </div>

        <section class="card">
            <div class="card-body">
                @forelse($words as $item)
                    <span class="d-inline-flex align-items-center gap-2 border rounded-pill px-3 py-2 me-2 mb-2 bg-light">
                        <strong>{{ $item->word }}</strong>
                        <form method="POST" action="{{ route('admin.banned-words.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Xóa từ cấm này?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm p-0 border-0 text-danger" title="Xóa ngay" aria-label="Xóa {{ $item->word }}">
                                <i class="bx bx-x fs-5"></i>
                            </button>
                        </form>
                    </span>
                @empty
                    <div class="text-muted text-center py-5">Chưa có từ cấm nào.</div>
                @endforelse
            </div>
        </section>
    </main>
</div>
@endsection
