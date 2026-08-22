@extends('layouts.admin')

@section('title', 'Thêm từ cấm')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <div class="admin-page-head">
            <div>
                <h2>Thêm từ cấm</h2>
                <p>Có thể nhập nhiều từ, mỗi từ một dòng hoặc ngăn cách bằng dấu phẩy.</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('admin.banned-words.index') }}">
                <i class="bx bx-arrow-back me-1"></i>
                Quay lại
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <section class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.banned-words.store') }}">
                            @csrf
                            <label class="form-label fw-semibold" for="bannedWords">Danh sách từ cấm</label>
                            <textarea
                                id="bannedWords"
                                name="words"
                                rows="10"
                                class="form-control @error('words') is-invalid @enderror"
                                placeholder="Ví dụ:&#10;từ thứ nhất&#10;từ thứ hai"
                            >{{ old('words') }}</textarea>
                            @error('words')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <a class="btn btn-light" href="{{ route('admin.banned-words.index') }}">Hủy</a>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bx bx-save me-1"></i>
                                    Lưu danh sách
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
@endsection
