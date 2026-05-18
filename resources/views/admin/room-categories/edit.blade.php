@extends('layouts.admin')

@section('header', 'Chỉnh sửa Loại phòng: ' . $roomCategory->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('admin.room-categories.update', $roomCategory->id) }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tên loại phòng -->
                <div class="col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên loại phòng <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $roomCategory->name) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" required>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Giá -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Giá mỗi đêm (VND) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="price" value="{{ old('price', (int)$roomCategory->price) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" required min="0" step="1000">
                    @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Số người tối đa -->
                <div>
                    <label for="max_people" class="block text-sm font-medium text-gray-700 mb-1">Số người tối đa <span class="text-red-500">*</span></label>
                    <input type="number" name="max_people" id="max_people" value="{{ old('max_people', $roomCategory->max_people) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" required min="1">
                    @error('max_people') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Số giường -->
                <div>
                    <label for="bed_count" class="block text-sm font-medium text-gray-700 mb-1">Số giường</label>
                    <input type="number" name="bed_count" id="bed_count" value="{{ old('bed_count', $roomCategory->bed_count) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" min="1">
                    @error('bed_count') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Loại giường -->
                <div>
                    <label for="bed_type" class="block text-sm font-medium text-gray-700 mb-1">Loại giường</label>
                    <input type="text" name="bed_type" id="bed_type" value="{{ old('bed_type', $roomCategory->bed_type) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    @error('bed_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Diện tích -->
                <div>
                    <label for="area" class="block text-sm font-medium text-gray-700 mb-1">Diện tích (m2)</label>
                    <input type="number" name="area" id="area" value="{{ old('area', $roomCategory->area) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" min="0" step="0.1">
                    @error('area') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Trạng thái -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Trạng thái <span class="text-red-500">*</span></label>
                    <select name="status" id="status" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                        <option value="active" {{ old('status', $roomCategory->status) == 'active' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="inactive" {{ old('status', $roomCategory->status) == 'inactive' ? 'selected' : '' }}>Ngưng hoạt động</option>
                    </select>
                    @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Mô tả -->
                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Mô tả chi tiết</label>
                    <textarea name="description" id="description" rows="4" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">{{ old('description', $roomCategory->description) }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Ảnh đại diện -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ảnh đại diện</label>
                    
                    @if($roomCategory->thumbnail)
                        <div class="mb-4">
                            <p class="text-sm text-gray-500 mb-2">Ảnh hiện tại:</p>
                            <img src="{{ Storage::url($roomCategory->thumbnail) }}" alt="{{ $roomCategory->name }}" class="h-32 rounded-lg object-cover border border-gray-200">
                        </div>
                    @endif

                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-indigo-400 transition-colors bg-gray-50/50">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 justify-center">
                                <label for="thumbnail" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>Tải ảnh mới lên</span>
                                    <input id="thumbnail" name="thumbnail" type="file" class="sr-only" accept="image/*">
                                </label>
                                <p class="pl-1">hoặc kéo thả vào đây</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                PNG, JPG, WEBP tối đa 2MB. Bỏ trống nếu không muốn đổi ảnh.
                            </p>
                        </div>
                    </div>
                    @error('thumbnail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.room-categories.index') }}" class="px-5 py-2.5 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Hủy bỏ
                </a>
                <button type="submit" class="px-5 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                    Cập nhật lưu
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
