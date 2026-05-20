<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomCategory;
use Illuminate\Support\Facades\DB;

class RoomCategorySeeder extends Seeder
{
    public function run()
    {
        // Xóa dữ liệu cũ bị lỗi font
        DB::table('room_categories')->delete();

        $categories = [
            [
                'name' => 'Phòng Deluxe view biển',
                'price' => 1800000,
                'max_people' => 2,
                'area' => 32.00,
                'bed_count' => 1,
                'bed_type' => 'King',
                'description' => '32m², giường King, ban công riêng nhìn trực diện biển Mỹ Khê.',
                'thumbnail' => 'https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg',
                'status' => 'active'
            ],
            [
                'name' => 'Phòng Premier view thành phố',
                'price' => 1400000,
                'max_people' => 2,
                'area' => 28.00,
                'bed_count' => 1,
                'bed_type' => 'Queen',
                'description' => '28m², giường Queen, cửa sổ lớn nhìn toàn cảnh thành phố.',
                'thumbnail' => 'https://images.pexels.com/photos/1571450/pexels-photo-1571450.jpeg',
                'status' => 'active'
            ],
            [
                'name' => 'Suite gia đình 2 phòng ngủ',
                'price' => 3200000,
                'max_people' => 4,
                'area' => 60.00,
                'bed_count' => 2,
                'bed_type' => 'King & Twin',
                'description' => '60m², 2 phòng ngủ riêng, 1 phòng khách, ban công rộng.',
                'thumbnail' => 'https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg',
                'status' => 'active'
            ],
            [
                'name' => 'Phòng Tổng thống',
                'price' => 8500000,
                'max_people' => 4,
                'area' => 120.00,
                'bed_count' => 2,
                'bed_type' => 'King',
                'description' => '120m², tầng cao nhất, view biển & thành phố 270°.',
                'thumbnail' => 'https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg',
                'status' => 'active'
            ]
        ];

        foreach ($categories as $cat) {
            RoomCategory::create($cat);
        }
    }
}
