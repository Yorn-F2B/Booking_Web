<?php

namespace App\Services;

use App\Models\RoomCategory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingRecommendationService
{
    public function recommend(string $checkInAt, string $checkOutAt, int $adults, int $children = 0, int $babies = 0, ?int $categoryId = null): Collection
    {
        $adults = max(1, $adults);
        $children = max(0, $children);
        $babies = max(0, $babies);
        $checkIn = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh');
        $checkOut = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh');
        $nights = max(1, $checkIn->copy()->startOfDay()->diffInDays($checkOut->copy()->startOfDay()));

        $bufferMinutes = max(0, (int) app(HotelPolicyService::class)->get('booking.cleaning_buffer_minutes', 0));
        $query = RoomCategory::query()->where('status', 'active')->with(['rooms' => function ($q) use ($checkIn, $checkOut, $bufferMinutes) {
            $q->bookableForPeriod($checkIn, $checkOut, null, $bufferMinutes);
        }]);
        if ($categoryId) {
            $query->whereKey($categoryId);
        }

        $maxRooms = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30));

        return $query->get()->map(function (RoomCategory $category) use ($adults, $children, $babies, $nights, $maxRooms) {
            $adultCap = max(1, (int) $category->adult_capacity);
            $childCap = max(0, (int) $category->child_capacity);
            $roomsForAdults = (int) ceil($adults / $adultCap);
            $minorCount = $children + $babies;
            $roomsForChildren = $minorCount > 0
                ? ($childCap > 0 ? (int) ceil($minorCount / $childCap) : PHP_INT_MAX)
                : 1;
            $required = max(1, $roomsForAdults, $roomsForChildren);
            $available = $category->rooms->count();
            // Nghiệp vụ check-in yêu cầu mỗi phòng có một người lớn đại diện.
            // Không gợi ý phương án mà đến bước xác nhận/check-in chắc chắn bị từ chối.
            if ($required === PHP_INT_MAX || $required > $adults || $required > $maxRooms || $available < $required) {
                return null;
            }

            $totalCapacity = ($adultCap + $childCap) * $required;
            $guestTotal = $adults + $children + $babies;
            $wasted = max(0, $totalCapacity - $guestTotal);
            $total = (float) $category->price * $nights * $required;

            return [
                'room_category_id' => (int) $category->id,
                'category_name' => $category->name,
                'room_quantity' => $required,
                'available_rooms' => $available,
                'adult_capacity_total' => $adultCap * $required,
                'child_capacity_total' => $childCap * $required,
                'total_capacity' => $totalCapacity,
                'wasted_capacity' => $wasted,
                'estimated_room_total' => $total,
                'room_ids' => $category->rooms->take($required)->pluck('id')->all(),
                'room_numbers' => $category->rooms->take($required)->pluck('room_number')->all(),
            ];
        })->filter()->values()->pipe(function (Collection $items) {
            if ($items->isEmpty()) return $items;
            $cheapest = $items->sortBy('estimated_room_total')->first();
            $fewest = $items->sortBy([['room_quantity','asc'],['estimated_room_total','asc']])->first();
            $comfortable = $items->sortBy([['wasted_capacity','desc'],['estimated_room_total','asc']])->first();

            return $items->map(function (array $item) use ($cheapest, $fewest, $comfortable) {
                $labels = [];
                if ($item['room_category_id'] === $cheapest['room_category_id']) $labels[] = 'Tiết kiệm nhất';
                if ($item['room_category_id'] === $fewest['room_category_id']) $labels[] = 'Ít phòng nhất';
                if ($item['room_category_id'] === $comfortable['room_category_id']) $labels[] = 'Thoải mái';
                $item['labels'] = array_values(array_unique($labels));
                return $item;
            })->sortBy([['estimated_room_total','asc'],['room_quantity','asc']])->values();
        });
    }
}
