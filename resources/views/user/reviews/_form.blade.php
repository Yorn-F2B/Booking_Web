@php
    $scoreOptions = [5 => '5 sao - Rất tốt', 4 => '4 sao - Tốt', 3 => '3 sao - Bình thường', 2 => '2 sao - Chưa hài lòng', 1 => '1 sao - Kém'];
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label fw-semibold">Dịch vụ <span class="text-danger">*</span></label>
        <select name="service_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            @foreach ($scoreOptions as $score => $label)
                <option value="{{ $score }}" {{ (string) old('service_rating', $review->service_rating ?? '') === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Nhân viên <span class="text-danger">*</span></label>
        <select name="staff_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            @foreach ($scoreOptions as $score => $label)
                <option value="{{ $score }}" {{ (string) old('staff_rating', $review->staff_rating ?? '') === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Chất lượng phòng <span class="text-danger">*</span></label>
        <select name="room_quality_rating" class="form-select" required>
            <option value="">Chọn điểm</option>
            @foreach ($scoreOptions as $score => $label)
                <option value="{{ $score }}" {{ (string) old('room_quality_rating', $review->room_quality_rating ?? '') === (string) $score ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <div class="alert alert-light border py-2 small mb-0">
            Điểm tổng = trung bình của 3 mục trên và được làm tròn về số sao gần nhất.
        </div>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Đánh giá <span class="text-danger">*</span></label>
        <textarea name="comment" rows="6" class="form-control" required maxlength="1500"
            placeholder="Chia sẻ ngắn gọn trải nghiệm của bạn...">{{ old('comment', $review->comment ?? '') }}</textarea>
        <div class="form-text">Tối thiểu 10 ký tự.</div>
    </div>
</div>
