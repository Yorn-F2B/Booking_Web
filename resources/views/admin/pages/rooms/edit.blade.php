@extends('layouts.admin')

@section('title', 'Sửa phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.rooms.index') }}">Admin</a> / Sửa phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Sửa phòng</h2>
                    <p>Cập nhật thông tin phòng trong khách sạn</p>
                </div>

            </div>

            <form action="{{ route('admin.rooms.update', $room->id) }}" method="POST">

                @csrf
                @method('PUT')

                @if ($errors->any())

                    <div class="alert alert-danger">

                        <strong>Có lỗi xảy ra:</strong>

                        <ul class="mb-0">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

                <div class="settings-section">

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Số phòng
                                </label>

                                <input type="text" name="room_number" class="form-control"
                                    value="{{ old('room_number', $room->room_number) }}">

                                @error('room_number')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Loại phòng
                                </label>

                                <select name="room_category_id" class="form-select">

                                    <option value="">
                                        -- Chọn loại phòng --
                                    </option>

                                    @foreach ($categories as $category)

                                        <option value="{{ $category->id }}"
                                            {{ old('room_category_id', $room->room_category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>

                                    @endforeach

                                </select>

                                @error('room_category_id')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Tầng
                                </label>

                                <input type="number" name="floor_number" class="form-control"
                                    value="{{ old('floor_number', $room->floor_number) }}">

                                @error('floor_number')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Trạng thái
                                </label>

                                <select name="status" id="editStatusSelect" class="form-select"
                                    onchange="toggleScheduleFields(this.value)">

                                    <option value="available"
                                        {{ old('status', $room->status) == 'available' ? 'selected' : '' }}>
                                        Còn trống
                                    </option>

                                    <option value="reserved"
                                        {{ old('status', $room->status) == 'reserved' ? 'selected' : '' }}>
                                        Đã đặt trước
                                    </option>

                                    <option value="occupied"
                                        {{ old('status', $room->status) == 'occupied' ? 'selected' : '' }}>
                                        Đang có khách
                                    </option>

                                    <option value="inspection"
                                        {{ old('status', $room->status) == 'inspection' ? 'selected' : '' }}>
                                        Chờ kiểm tra
                                    </option>

                                    <option value="cleaning"
                                        {{ old('status', $room->status) == 'cleaning' ? 'selected' : '' }}>
                                        Đang dọn phòng
                                    </option>

                                    <option value="maintenance"
                                        {{ old('status', $room->status) == 'maintenance' ? 'selected' : '' }}>
                                        Bảo trì
                                    </option>

                                </select>

                                @error('status')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    {{-- Ngày giờ hiệu lực: hiện khi chọn trạng thái cần lịch --}}
                    <div class="row" id="scheduleFields"
                        style="{{ in_array(old('status', $room->status), ['maintenance','cleaning','inspection','reserved','occupied']) ? '' : 'display:none' }}">

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Từ ngày giờ</label>
                                <input type="text" name="status_from" id="editStatusFrom" class="form-control"
                                    autocomplete="off" readonly placeholder="dd/mm/yyyy HH:MM"
                                    value="{{ old('status_from', $room->status_from ? \Carbon\Carbon::parse($room->status_from)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') : '') }}">
                                @error('status_from')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Đến ngày giờ</label>
                                <input type="text" name="status_until" id="editStatusUntil" class="form-control"
                                    autocomplete="off" readonly placeholder="dd/mm/yyyy HH:MM"
                                    value="{{ old('status_until', $room->status_until ? \Carbon\Carbon::parse($room->status_until)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') : '') }}">
                                @error('status_until')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                    </div> {{-- end scheduleFields --}}

                    <div class="mb-4">

                        <label class="form-label">
                            Ghi chú
                        </label>

                        <textarea name="note" rows="5"
                            class="form-control">{{ old('note', $room->note) }}</textarea>

                        @error('note')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-gold">
                            Cập nhật phòng
                        </button>

                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                            Quay lại
                        </a>

                    </div>

                </div>

            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
flatpickr.localize({
    weekdays: {
        shorthand: ['CN','T2','T3','T4','T5','T6','T7'],
        longhand:  ['Chủ Nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy']
    },
    months: {
        shorthand: ['Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'],
        longhand:  ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6',
                    'Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12']
    },
    firstDayOfWeek: 1,
    rangeSeparator: ' đến ',
    time_24hr: true,
});

const dtOpts = {
    enableTime: true,
    dateFormat: 'd/m/Y H:i',
    time_24hr: true,
    minuteIncrement: 15,
    allowInput: false,
    disableMobile: true,
};

const fpEditFrom  = flatpickr('#editStatusFrom',  {
    ...dtOpts,
    onChange: function(d) { if (d[0]) fpEditUntil.set('minDate', d[0]); }
});
const fpEditUntil = flatpickr('#editStatusUntil', {
    ...dtOpts,
    onChange: function(d) { if (d[0]) fpEditFrom.set('maxDate', d[0]); }
});

function toggleScheduleFields(status) {
    const needsSchedule = ['maintenance', 'cleaning', 'inspection', 'reserved', 'occupied'];
    document.getElementById('scheduleFields').style.display =
        needsSchedule.includes(status) ? '' : 'none';
}
</script>

@endsection