@php($editing = isset($surcharge))
@if($errors->any())
    <div class="alert alert-danger"><strong>Không thể lưu khoản phí:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="settings-section">
    <div class="row g-3">
        <div class="col-lg-6">
            <label class="form-label">Tên khoản phí <span class="text-danger">*</span></label>
            <input type="text" name="name" maxlength="100" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $surcharge->name ?? '') }}" placeholder="Ví dụ: Mất thẻ phòng">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-6">
            <label class="form-label">Loại phí <span class="text-danger">*</span></label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                @foreach($typeLabels as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $surcharge->type ?? 'manual_fee') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4">
            <label class="form-label">Mức phí mặc định <span class="text-danger">*</span></label>
            <input type="number" name="price" min="0" step="1000" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $surcharge->price ?? 0) }}">
            <div class="form-text">Có thể để 0 với khoản được tính động theo chính sách.</div>
            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4">
            <label class="form-label">Đơn vị <span class="text-danger">*</span></label>
            <input type="text" name="unit" maxlength="50" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $surcharge->unit ?? 'lần') }}" placeholder="lần, người, giờ, cái...">
            @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-lg-4">
            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="active" @selected(old('status', $surcharge->status ?? 'active') === 'active')>Hoạt động</option>
                <option value="inactive" @selected(old('status', $surcharge->status ?? 'active') === 'inactive')>Ngừng hoạt động</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Mô tả</label>
            <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror" placeholder="Mô tả điều kiện áp dụng hoặc cách ghi nhận khoản phí">{{ old('description', $surcharge->description ?? '') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <div class="alert alert-light border mb-0 small">
                Trang này chỉ quản lý <strong>phụ thu / phí phát sinh</strong>. Nhóm dữ liệu và cách tính được khóa về <strong>Khác / tính một lần</strong> để không lẫn với dịch vụ khách mua/gọi.
            </div>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-gold">{{ $editing ? 'Cập nhật khoản phí' : 'Lưu khoản phí' }}</button>
            <a href="{{ route('surcharges.index') }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>
    </div>
</div>
