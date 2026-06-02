@extends('admin.layouts.app')

@section('header_title', 'Bảng điều khiển Tổng quan')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Card 1 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-4 rounded-full bg-blue-50 text-blue-500 mr-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Tổng số phòng</p>
            <p class="text-2xl font-bold text-gray-800">120</p>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-4 rounded-full bg-green-50 text-green-500 mr-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Phòng đã đặt</p>
            <p class="text-2xl font-bold text-gray-800">45</p>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-4 rounded-full bg-yellow-50 text-yellow-500 mr-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Khách hàng</p>
            <p class="text-2xl font-bold text-gray-800">320</p>
        </div>
    </div>

    <!-- Card 4 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
        <div class="p-4 rounded-full bg-red-50 text-red-500 mr-4">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Doanh thu tháng</p>
            <p class="text-2xl font-bold text-gray-800">$15,400</p>
        </div>
    </div>
</div>

<!-- Main Content Area Example -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">Đặt phòng gần đây</h3>
        <a href="#" class="text-sm text-blue-500 hover:underline">Xem tất cả</a>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm whitespace-nowrap">
                <thead class="uppercase tracking-wider border-b-2 dark:border-neutral-600 bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4">Mã Đặt Phòng</th>
                        <th scope="col" class="px-6 py-4">Khách hàng</th>
                        <th scope="col" class="px-6 py-4">Ngày Check-in</th>
                        <th scope="col" class="px-6 py-4">Ngày Check-out</th>
                        <th scope="col" class="px-6 py-4">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b dark:border-neutral-600 hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">#BK-0012</td>
                        <td class="px-6 py-4">Nguyễn Văn A</td>
                        <td class="px-6 py-4">18/05/2026</td>
                        <td class="px-6 py-4">20/05/2026</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold leading-tight text-green-700 bg-green-100 rounded-full">Đã xác nhận</span></td>
                    </tr>
                    <tr class="border-b dark:border-neutral-600 hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">#BK-0013</td>
                        <td class="px-6 py-4">Trần Thị B</td>
                        <td class="px-6 py-4">20/05/2026</td>
                        <td class="px-6 py-4">25/05/2026</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold leading-tight text-yellow-700 bg-yellow-100 rounded-full">Chờ xử lý</span></td>
                    </tr>
                    <tr class="border-b dark:border-neutral-600 hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">#BK-0014</td>
                        <td class="px-6 py-4">Lê Hoàng C</td>
                        <td class="px-6 py-4">22/05/2026</td>
                        <td class="px-6 py-4">23/05/2026</td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs font-semibold leading-tight text-red-700 bg-red-100 rounded-full">Đã hủy</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
