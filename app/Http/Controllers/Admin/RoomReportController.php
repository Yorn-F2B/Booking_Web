<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomReportController extends Controller
{
    public function index(Request $request)
    {
        $date       = $request->get('date');
        $startDate  = $request->get('start_date');
        $endDate    = $request->get('end_date');
        $floor      = $request->get('floor');
        $categoryId = $request->get('category_id');
        $status     = $request->get('status');

        $targetDate      = $date      ? Carbon::parse($date,      'Asia/Ho_Chi_Minh') : now('Asia/Ho_Chi_Minh');
        $targetStartDate = $startDate ? Carbon::parse($startDate, 'Asia/Ho_Chi_Minh') : null;
        $targetEndDate   = $endDate   ? Carbon::parse($endDate,   'Asia/Ho_Chi_Minh') : null;

        $data = $this->generateRoomReport($targetDate, $targetStartDate, $targetEndDate, $floor, $categoryId, $status);

        $floors     = Room::select('floor_number')->distinct()->orderBy('floor_number')->pluck('floor_number');
        $categories = RoomCategory::where('status', 'active')->orderBy('name')->get();
        $statuses   = [
            'available'   => 'Trống',
            'reserved'    => 'Đã đặt',
            'occupied'    => 'Đang ở',
            'inspection'  => 'Chờ kiểm tra',
            'cleaning'    => 'Đang dọn',
            'maintenance' => 'Bảo trì',
        ];

        return view('admin.pages.reports.room', array_merge($data, [
            'date'       => $date ?? $targetDate->toDateString(),
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'floor'      => $floor,
            'categoryId' => $categoryId,
            'status'     => $status,
            'floors'     => $floors,
            'categories' => $categories,
            'statuses'   => $statuses,
        ]));
    }

    /**
     * Tính tình trạng phòng theo ngày được chọn.
     *
     * Logic:
     *  - Hôm nay  → đọc trực tiếp rooms.status (chính xác, bao gồm cleaning/maintenance/inspection).
     *  - Ngày khác → tính lại từ dữ liệu booking overlap (chỉ có occupied / reserved / available).
     */
    private function generateRoomReport(
        Carbon  $targetDate,
        ?Carbon $targetStartDate,
        ?Carbon $targetEndDate,
        ?string $floor,
        ?int    $categoryId,
        ?string $status
    ): array {
        $isRangeMode  = $targetStartDate && $targetEndDate;
        $snapshotDate = $isRangeMode
            ? $targetEndDate->copy()->endOfDay()
            : $targetDate->copy()->endOfDay();
        $isToday = ($isRangeMode ? $targetEndDate : $targetDate)->isToday();

        // ── 1. Lấy tất cả phòng (chưa filter status) ──────────────────────────
        $roomBaseQuery = Room::query()->with('category');
        if ($floor)      $roomBaseQuery->where('floor_number',      $floor);
        if ($categoryId) $roomBaseQuery->where('room_category_id',  $categoryId);
        $allRooms = $roomBaseQuery->get();

        // ── 2. Tính trạng thái từng phòng tại snapshot date ──────────────────
        // roomComputedStatuses: Collection<roomId, statusString>
        // roomComputedBookings: Collection<roomId, Booking|null>
        $roomComputedStatuses = collect();
        $roomComputedBookings = collect();

        if ($isToday) {
            // Hôm nay: dùng rooms.status trực tiếp
            foreach ($allRooms as $room) {
                $roomComputedStatuses->put($room->id, $room->status);
                $roomComputedBookings->put($room->id, null); // sẽ query riêng ở bước room list
            }
        } else {
            // Ngày khác: tính từ booking overlap
            $bookingsOnDate = Booking::whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'completed'])
                ->where('check_in_at', '<=', $snapshotDate)
                ->where('check_out_at', '>', $snapshotDate->copy()->startOfDay())
                ->with(['bookingRooms', 'customer'])
                ->get();

            foreach ($allRooms as $room) {
                // Tìm booking overlap với phòng này, ưu tiên checked_in > confirmed
                $matched = $bookingsOnDate
                    ->filter(fn($b) => $b->bookingRooms->contains('room_id', $room->id))
                    ->sortByDesc(fn($b) => match ($b->status) {
                        'checked_in' => 2,
                        'confirmed'  => 1,
                        default      => 0,
                    })
                    ->first();

                if ($matched) {
                    $computed = $matched->status === 'checked_in' ? 'occupied' : 'reserved';
                    $roomComputedStatuses->put($room->id, $computed);
                    $roomComputedBookings->put($room->id, $matched);
                } else {
                    $roomComputedStatuses->put($room->id, 'available');
                    $roomComputedBookings->put($room->id, null);
                }
            }
        }

        // ── 3. Áp dụng filter trạng thái ─────────────────────────────────────
        $filteredRooms = $status
            ? $allRooms->filter(fn($r) => $roomComputedStatuses->get($r->id, 'available') === $status)
            : $allRooms;

        $totalRooms = $filteredRooms->count();

        // ── 4. Đếm theo trạng thái ────────────────────────────────────────────
        $availableRooms   = $filteredRooms->filter(fn($r) => $roomComputedStatuses->get($r->id) === 'available')->count();
        $reservedRooms    = $filteredRooms->filter(fn($r) => $roomComputedStatuses->get($r->id) === 'reserved')->count();
        $occupiedRooms    = $filteredRooms->filter(fn($r) => $roomComputedStatuses->get($r->id) === 'occupied')->count();
        // Cleaning / inspection / maintenance chỉ có dữ liệu khi xem ngày hôm nay
        $cleaningRooms    = $isToday ? $filteredRooms->filter(fn($r) => $r->status === 'cleaning')->count()    : 0;
        $inspectionRooms  = $isToday ? $filteredRooms->filter(fn($r) => $r->status === 'inspection')->count()  : 0;
        $maintenanceRooms = $isToday ? $filteredRooms->filter(fn($r) => $r->status === 'maintenance')->count() : 0;

        // ── 5. Công suất phòng ────────────────────────────────────────────────
        $activeRooms     = $reservedRooms + $occupiedRooms;
        $baseTotalForRate = $allRooms->count(); // tính trên tổng phòng, không phụ thuộc filter status
        $occupancyRate   = $baseTotalForRate > 0 ? round(($activeRooms / $baseTotalForRate) * 100, 1) : 0;

        // ── 6. So sánh với ngày hôm qua (chỉ ở chế độ ngày đơn) ─────────────
        if (!$isRangeMode) {
            $prevSnapshotDate    = ($targetDate)->copy()->subDay()->endOfDay();
            $prevBookingsOnDate  = Booking::whereIn('status', ['confirmed', 'checked_in', 'checked_out', 'completed'])
                ->where('check_in_at', '<=', $prevSnapshotDate)
                ->where('check_out_at', '>', $prevSnapshotDate->copy()->startOfDay())
                ->with(['bookingRooms'])
                ->get();

            $prevReserved = 0;
            $prevOccupied = 0;
            foreach ($allRooms as $room) {
                $prevMatched = $prevBookingsOnDate
                    ->filter(fn($b) => $b->bookingRooms->contains('room_id', $room->id))
                    ->first();
                if ($prevMatched) {
                    $prevMatched->status === 'checked_in' ? $prevOccupied++ : $prevReserved++;
                }
            }
            $prevActive            = $prevReserved + $prevOccupied;
            $prevTotal             = $allRooms->count();
            $previousOccupancyRate = $prevTotal > 0 ? round(($prevActive / $prevTotal) * 100, 1) : 0;
            $occupancyChange       = round($occupancyRate - $previousOccupancyRate, 1);
            $occupancyTrend        = $occupancyChange > 0 ? 'up' : ($occupancyChange < 0 ? 'down' : 'same');
        } else {
            $previousOccupancyRate = null;
            $occupancyChange       = null;
            $occupancyTrend        = 'same';
        }

        // ── 7. Thống kê theo hạng phòng ──────────────────────────────────────
        $categoryStats = [];
        $allCategories = RoomCategory::where('status', 'active')->orderBy('name')->get();

        foreach ($allCategories as $cat) {
            $catRooms = $filteredRooms->where('room_category_id', $cat->id);
            $catTotal = $catRooms->count();
            if ($catTotal === 0) continue;

            $catOccupied    = $catRooms->filter(fn($r) => $roomComputedStatuses->get($r->id) === 'occupied')->count();
            $catReserved    = $catRooms->filter(fn($r) => $roomComputedStatuses->get($r->id) === 'reserved')->count();
            $catAvailable   = $catRooms->filter(fn($r) => $roomComputedStatuses->get($r->id) === 'available')->count();
            $catCleaning    = $isToday ? $catRooms->filter(fn($r) => $r->status === 'cleaning')->count()    : 0;
            $catMaintenance = $isToday ? $catRooms->filter(fn($r) => $r->status === 'maintenance')->count() : 0;
            $catActive      = $catOccupied + $catReserved;
            $catOccupancyRate = round(($catActive / $catTotal) * 100, 1);

            $categoryStats[] = [
                'category'       => $cat->name,
                'total'          => $catTotal,
                'occupied'       => $catOccupied,
                'reserved'       => $catReserved,
                'available'      => $catAvailable,
                'cleaning'       => $catCleaning,
                'maintenance'    => $catMaintenance,
                'occupancy_rate' => $catOccupancyRate,
            ];
        }

        // ── 8. Danh sách phòng chi tiết ───────────────────────────────────────
        $roomList = $filteredRooms->map(function ($room) use ($snapshotDate, $isToday, $roomComputedStatuses, $roomComputedBookings) {
            $computedStatus = $roomComputedStatuses->get($room->id, 'available');
            $currentBooking = null;

            if (in_array($computedStatus, ['occupied', 'reserved'])) {
                if ($isToday) {
                    // Hôm nay: query trực tiếp
                    $currentBooking = Booking::whereHas('bookingRooms', fn($q) => $q->where('room_id', $room->id))
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->where('check_in_at', '<=', $snapshotDate)
                        ->where('check_out_at', '>', now('Asia/Ho_Chi_Minh')->startOfDay())
                        ->with('customer')
                        ->first();
                } else {
                    // Ngày khác: dùng booking đã tính sẵn
                    $currentBooking = $roomComputedBookings->get($room->id);
                }
            }

            return [
                'id'          => $room->id,
                'room_number' => $room->room_number,
                'floor'       => $room->floor_number,
                'category'    => $room->category->name ?? 'N/A',
                'status'      => $computedStatus,
                'customer'    => $currentBooking?->customer?->full_name ?? 'N/A',
                'check_out'   => $currentBooking?->check_out_at?->format('d/m/Y H:i') ?? 'N/A',
            ];
        })->values();

        // ── Chart data ─────────────────────────────────────────────────────────
        $statusChart = [
            'labels' => ['Trống', 'Đã đặt', 'Đang ở', 'Chờ kiểm tra', 'Đang dọn', 'Bảo trì'],
            'data'   => [$availableRooms, $reservedRooms, $occupiedRooms, $inspectionRooms, $cleaningRooms, $maintenanceRooms],
        ];
        $categoryChart = [
            'labels' => collect($categoryStats)->pluck('category')->toArray(),
            'data'   => collect($categoryStats)->pluck('occupancy_rate')->toArray(),
        ];

        return [
            'totalRooms'            => $totalRooms,
            'availableRooms'        => $availableRooms,
            'reservedRooms'         => $reservedRooms,
            'occupiedRooms'         => $occupiedRooms,
            'cleaningRooms'         => $cleaningRooms,
            'inspectionRooms'       => $inspectionRooms,
            'maintenanceRooms'      => $maintenanceRooms,
            'occupancyRate'         => $occupancyRate,
            'previousOccupancyRate' => $previousOccupancyRate,
            'occupancyChange'       => $occupancyChange,
            'occupancyTrend'        => $occupancyTrend,
            'categoryStats'         => $categoryStats,
            'roomList'              => $roomList,
            'statusChart'           => $statusChart,
            'categoryChart'         => $categoryChart,
            'isToday'               => $isToday,
            'isRangeMode'           => $isRangeMode,
        ];
    }

    public function exportPdf(Request $request)
    {
        $date       = $request->get('date');
        $startDate  = $request->get('start_date');
        $endDate    = $request->get('end_date');
        $floor      = $request->get('floor');
        $categoryId = $request->get('category_id');
        $status     = $request->get('status');

        $targetDate      = $date      ? Carbon::parse($date,      'Asia/Ho_Chi_Minh') : now('Asia/Ho_Chi_Minh');
        $targetStartDate = $startDate ? Carbon::parse($startDate, 'Asia/Ho_Chi_Minh') : null;
        $targetEndDate   = $endDate   ? Carbon::parse($endDate,   'Asia/Ho_Chi_Minh') : null;

        $data = $this->generateRoomReport($targetDate, $targetStartDate, $targetEndDate, $floor, $categoryId, $status);

        if ($targetStartDate && $targetEndDate) {
            $periodLabel = $targetStartDate->format('d/m/Y') . ' - ' . $targetEndDate->format('d/m/Y');
            $slug        = $targetStartDate->format('Ymd') . '-' . $targetEndDate->format('Ymd');
        } else {
            $periodLabel = $targetDate->format('d/m/Y');
            $slug        = $targetDate->format('Ymd');
        }

        $floorLabel    = $floor      ? "Tầng $floor"                                  : 'Tất cả tầng';
        $categoryLabel = $categoryId ? RoomCategory::find($categoryId)?->name         : 'Tất cả hạng phòng';
        $statusLabels  = [
            'available'   => 'Trống',
            'reserved'    => 'Đã đặt',
            'occupied'    => 'Đang ở',
            'inspection'  => 'Chờ kiểm tra',
            'cleaning'    => 'Đang dọn',
            'maintenance' => 'Bảo trì',
        ];
        $statusLabel = $status ? ($statusLabels[$status] ?? $status) : 'Tất cả trạng thái';

        $pdf = Pdf::loadView('admin.pages.reports.room-pdf', array_merge($data, [
            'periodLabel'   => $periodLabel,
            'generatedAt'   => now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            'floorLabel'    => $floorLabel,
            'categoryLabel' => $categoryLabel,
            'statusLabel'   => $statusLabel,
            'statuses'      => $statusLabels,
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('bao-cao-tinh-trang-phong-' . $slug . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $date       = $request->get('date');
        $startDate  = $request->get('start_date');
        $endDate    = $request->get('end_date');
        $floor      = $request->get('floor');
        $categoryId = $request->get('category_id');
        $status     = $request->get('status');

        $targetDate      = $date      ? Carbon::parse($date,      'Asia/Ho_Chi_Minh') : now('Asia/Ho_Chi_Minh');
        $targetStartDate = $startDate ? Carbon::parse($startDate, 'Asia/Ho_Chi_Minh') : null;
        $targetEndDate   = $endDate   ? Carbon::parse($endDate,   'Asia/Ho_Chi_Minh') : null;

        $data = $this->generateRoomReport($targetDate, $targetStartDate, $targetEndDate, $floor, $categoryId, $status);

        if ($targetStartDate && $targetEndDate) {
            $periodLabel = $targetStartDate->format('d/m/Y') . ' - ' . $targetEndDate->format('d/m/Y');
            $slug        = $targetStartDate->format('Ymd') . '-' . $targetEndDate->format('Ymd');
        } else {
            $periodLabel = $targetDate->format('d/m/Y');
            $slug        = $targetDate->format('Ymd');
        }

        $rows   = [];
        $rows[] = ['MCuong Hotel - Bao Cao Tinh Trang Phong'];
        $rows[] = ['Ky bao cao: ' . $periodLabel];
        $rows[] = ['Xuat luc: ' . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')];
        $rows[] = [];

        // Tổng quan
        $rows[] = ['TONG QUAN'];
        $rows[] = ['Tong so phong', 'Phong trong', 'Phong da dat', 'Phong dang o', 'Phong dang don', 'Phong bao tri', 'Cong suat (%)'];
        $rows[] = [
            $data['totalRooms'],
            $data['availableRooms'],
            $data['reservedRooms'],
            $data['occupiedRooms'],
            $data['cleaningRooms'],
            $data['maintenanceRooms'],
            $data['occupancyRate'],
        ];
        $rows[] = [];

        // Theo hạng phòng
        $rows[] = ['THONG KE THEO HANG PHONG'];
        $rows[] = ['Hang phong', 'Tong phong', 'Dang o', 'Da dat', 'Trong', 'Dang don', 'Bao tri', 'Cong suat (%)'];
        foreach ($data['categoryStats'] as $stat) {
            $rows[] = [
                $stat['category'],
                $stat['total'],
                $stat['occupied'],
                $stat['reserved'],
                $stat['available'],
                $stat['cleaning'],
                $stat['maintenance'],
                $stat['occupancy_rate'],
            ];
        }
        $rows[] = [];

        // Danh sách phòng chi tiết
        $rows[] = ['DANH SACH PHONG CHI TIET'];
        $rows[] = ['So phong', 'Tang', 'Hang phong', 'Trang thai', 'Khach hang', 'Gio tra phong'];
        $statusLabels = [
            'available'   => 'Trong',
            'reserved'    => 'Da dat',
            'occupied'    => 'Dang o',
            'inspection'  => 'Cho kiem tra',
            'cleaning'    => 'Dang don',
            'maintenance' => 'Bao tri',
        ];
        foreach ($data['roomList'] as $room) {
            $rows[] = [
                $room['room_number'],
                $room['floor'],
                $room['category'],
                $statusLabels[$room['status']] ?? $room['status'],
                $room['customer'],
                $room['check_out'],
            ];
        }

        // Build CSV
        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\r\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bao-cao-tinh-trang-phong-' . $slug . '.csv"',
        ]);
    }
}
