@extends('layouts.admin')

@section('title', 'Sửa dịch vụ')

@section('content')
    @php
        $typeLabels = $typeLabels ?? \App\Models\Service::typeLabels();
        $groupLabels = $groupLabels ?? \App\Models\Service::groupLabels();
        $billingRuleLabels = $billingRuleLabels ?? \App\Models\Service::billingRuleLabels();
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
                <a href="{{ route('services.index') }}">Admin</a> / Sửa dịch vụ
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Sửa dịch vụ</h2>
                    <p>Cập nhật dịch vụ khách mua/gọi, minibar có sẵn hoặc minibar gọi thêm.</p>
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

            <form action="{{ route('services.update', $service->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="service-form-card">
                    <div class="row g-3">

                        <div class="col-lg-6">
                            <label class="form-label">Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $service->name) }}" placeholder="Ví dụ: Gửi ô tô qua đêm">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Loại dịch vụ <span class="text-danger">*</span></label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror">
                                @foreach ($typeLabels as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}" {{ old('type', $service->type) == $typeValue ? 'selected' : '' }}>
                                        {{ $typeLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Nhóm dịch vụ <span class="text-danger">*</span></label>
                            <select name="service_group" class="form-select @error('service_group') is-invalid @enderror">
                                @foreach ($groupLabels as $groupValue => $groupLabel)
                                    <option value="{{ $groupValue }}" {{ old('service_group', $service->service_group ?? 'general') == $groupValue ? 'selected' : '' }}>
                                        {{ $groupLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_group')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Giá <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price', $service->price) }}" min="0" step="1000" placeholder="Ví dụ: 100000">
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Đơn vị <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror"
                                value="{{ old('unit', $service->unit) }}" placeholder="lần, đêm, giờ, chiếc...">
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Cách tính khi đổi lịch <span class="text-danger">*</span></label>
                            <select name="billing_rule" class="form-select @error('billing_rule') is-invalid @enderror">
                                @foreach ($billingRuleLabels as $ruleValue => $ruleLabel)
                                    <option value="{{ $ruleValue }}" {{ old('billing_rule', $service->billing_rule ?? 'once') == $ruleValue ? 'selected' : '' }}>
                                        {{ $ruleLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('billing_rule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $service->status) == 'active' ? 'selected' : '' }}>
                                    Hoạt động
                                </option>
                                <option value="inactive" {{ old('status', $service->status) == 'inactive' ? 'selected' : '' }}>
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
                                placeholder="Mô tả dịch vụ">{{ old('description', $service->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-gold">
                                Cập nhật dịch vụ
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
