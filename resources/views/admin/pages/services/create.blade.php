@extends('layouts.admin')

@section('title', 'Thêm dịch vụ')

@section('content')
    @php
        $typeLabels = $typeLabels ?? \App\Models\Service::typeLabels();
        $groupLabels = $groupLabels ?? \App\Models\Service::groupLabels();
    @endphp

    <style>
        .service-form-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
        }

        .service-help {
            color: #64748b;
            font-size: 13px;
        }

        .service-suggest-box {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 12px;
            background: #f8fafc;
        }

        .service-suggest-box code {
            color: #92400e;
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('services.index') }}">Admin</a> / Thêm dịch vụ
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Thêm dịch vụ</h2>
                    <p>Thêm dịch vụ bán thêm, minibar hoặc phí phát sinh</p>
                </div>

                <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Không thể lưu dịch vụ:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('services.store') }}" method="POST">
                @csrf

                <div class="service-form-card">
                    <div class="row g-3">

                        <div class="col-lg-6">
                            <label class="form-label">Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Ví dụ: Gửi ô tô qua đêm">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Loại nghiệp vụ <span class="text-danger">*</span></label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}" {{ old('type', 'service') == $typeValue ? 'selected' : '' }}>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="service-help mt-1">
                                Gửi xe/rửa xe/sửa xe để là <strong>Dịch vụ</strong>.
                            </div>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Nhóm dịch vụ <span class="text-danger">*</span></label>
                            <select name="service_group" class="form-select @error('service_group') is-invalid @enderror">
                                @foreach ($groupLabels as $groupValue => $groupLabel)
                                    <option value="{{ $groupValue }}" {{ old('service_group', 'general') == $groupValue ? 'selected' : '' }}>
                                        {{ $groupLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="service-help mt-1">
                                Nhóm xe cộ dùng cho gửi xe, rửa xe, gọi sửa xe.
                            </div>
                            @error('service_group')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Giá <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price') }}" min="0" step="1000" placeholder="Ví dụ: 100000">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Đơn vị <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror"
                                value="{{ old('unit', 'lần') }}" placeholder="lần, đêm, giờ, chiếc...">
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                    Hoạt động
                                </option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                    Tạm ẩn
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror"
                                placeholder="Mô tả cách tính phí, phạm vi hỗ trợ, lưu ý cho lễ tân...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="service-suggest-box">
                                <div class="fw-bold mb-1">Gợi ý nhóm xe cộ</div>
                                <div class="service-help">
                                    Nên tạo: <code>Gửi xe máy qua đêm</code>, <code>Gửi ô tô qua đêm</code>,
                                    <code>Rửa xe máy</code>, <code>Rửa ô tô</code>, <code>Hỗ trợ gọi sửa xe</code>.
                                    Các dịch vụ này chọn <strong>Loại nghiệp vụ = Dịch vụ</strong> và
                                    <strong>Nhóm dịch vụ = Xe cộ / gửi xe</strong>.
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-gold">
                                Lưu dịch vụ
                            </button>

                            <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
                                Quay lại
                            </a>
                        </div>

                    </div>
                </div>
            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
@endsection
