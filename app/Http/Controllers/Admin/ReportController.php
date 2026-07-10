<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\RoomCategory;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // ── Helper: phân tích kỳ báo cáo từ request ─────────────────────────
    private function resolveReportPeriod(Request $request): array
    {
        $tz   = 'Asia/Ho_Chi_Minh';
        $mode = $request->get('pdf_mode', 'year');

        switch ($mode) {
            case 'month':
                $year  = (int) $request->get('year',  now($tz)->year);
                $month = (int) $request->get('month', now($tz)->month);
                $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz);
                $end   = $start->copy()->endOfMonth();
                $label = 'Tháng ' . sprintf('%02d', $month) . '/' . $year;
                $slug  = sprintf('%02d', $month) . '-' . $year;
                break;

            case 'range':
                $start = Carbon::parse($request->get('range_from', now($tz)->startOfMonth()->toDateString()), $tz)->startOfDay();
                $end   = Carbon::parse($request->get('range_to', now($tz)->toDateString()), $tz)->endOfDay();
                $label = $start->format('d/m/Y') . ' — ' . $end->format('d/m/Y');
                $slug  = $start->format('Ymd') . '-' . $end->format('Ymd');
                break;

            default: // year
                $year  = (int) $request->get('year', now($tz)->year);
                $start = Carbon::create($year, 1, 1, 0, 0, 0, $tz);
                $end   = Carbon::create($year, 12, 31, 23, 59, 59, $tz);
                $label = 'Nam ' . $year;
                $slug  = (string) $year;
                break;
        }

        return compact('mode', 'start', 'end', 'label', 'slug', 'tz');
    }

    // ── Helper: tổng hợp số liệu ─────────────────────────────────────
    private function buildReportData(string $mode, Carbon $start, Carbon $end, string $tz): array
    {
        // Lọc bookings theo check_in_at trong khoảng thời gian
        $allBookings = Booking::whereBetween('check_in_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'canceled', 'no_show'])
            ->with('payments')
            ->get();

        $totalRevenue  = $allBookings->sum('estimated_total');
        $totalPaid     = $allBookings->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount'));
        $totalBookings = $allBookings->count();

        // Theo tháng (chỉ mode year) - phân nhóm theo check_out_at để doanh thu ghi nhận đúng thời điểm
        $monthlyData = null;
        if ($mode === 'year') {
            $monthlyData = [];
            for ($m = 1; $m <= 12; $m++) {
                $mS  = Carbon::create($start->year, $m, 1, 0, 0, 0, $tz);
                $mE  = $mS->copy()->endOfMonth();
                // Phân nhóm theo check_out_at để doanh thu ghi nhận vào tháng trả phòng
                $mB  = $allBookings->filter(fn($b) => $b->check_out_at && Carbon::parse($b->check_out_at, $tz)->between($mS, $mE));
                $rev = $mB->sum('estimated_total');
                $pd  = $mB->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount'));
                $monthlyData[] = [
                    'month'    => $mS->format('m/Y'),
                    'bookings' => $mB->count(),
                    'revenue'  => $rev,
                    'paid'     => $pd,
                    'pending'  => max(0, $rev - $pd),
                ];
            }
        }

        // Theo ngày (mode month hoặc range) - phân nhóm theo check_out_at
        $dailyData = null;
        if (in_array($mode, ['month', 'range'])) {
            $dailyData = [];
            $cur = $start->copy();
            while ($cur->lte($end)) {
                // Phân nhóm theo check_out_at để doanh thu ghi nhận vào ngày trả phòng
                $dayB = $allBookings->filter(fn($b) => $b->check_out_at && Carbon::parse($b->check_out_at, $tz)->isSameDay($cur));
                $dailyData[] = [
                    'date'     => $cur->format('d/m/Y'),
                    'bookings' => $dayB->count(),
                    'revenue'  => $dayB->sum('estimated_total'),
                    'paid'     => $dayB->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount')),
                ];
                $cur->addDay();
            }
        }

        // Theo hạng phòng
        $categoryData = RoomCategory::all()->map(function ($cat) use ($allBookings) {
            $bk = $allBookings->where('room_category_id', $cat->id);
            return ['name' => $cat->name, 'bookings' => $bk->count(), 'revenue' => $bk->sum('estimated_total')];
        })->filter(fn($c) => $c['bookings'] > 0)->sortByDesc('revenue')->values();

        return compact('allBookings', 'totalRevenue', 'totalPaid', 'totalBookings', 'monthlyData', 'dailyData', 'categoryData');
    }

    public function exportPdf(Request $request)
    {
        ['mode' => $mode, 'start' => $start, 'end' => $end,
         'label' => $label, 'slug' => $slug, 'tz' => $tz] = $this->resolveReportPeriod($request);

        $data = $this->buildReportData($mode, $start, $end, $tz);

        $pdf = Pdf::loadView('admin.pages.reports.pdf', array_merge($data, [
            'mode'        => $mode,
            'periodLabel' => $label,
            'totalPending' => max(0, $data['totalRevenue'] - $data['totalPaid']),
            'generatedAt' => now($tz)->format('d/m/Y H:i'),
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('bao-cao-' . $slug . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        ['mode' => $mode, 'start' => $start, 'end' => $end,
         'label' => $label, 'slug' => $slug, 'tz' => $tz] = $this->resolveReportPeriod($request);

        $data = $this->buildReportData($mode, $start, $end, $tz);

        $rows   = [];
        $rows[] = ['MCuong Hotel - Bao cao doanh thu - ' . $label];
        $rows[] = ['Xuat luc: ' . now($tz)->format('d/m/Y H:i')];
        $rows[] = [];

        // Tổng quan
        $rows[] = ['TONG QUAN'];
        $rows[] = ['Tong booking', 'Tong doanh thu', 'Da thu', 'Con no'];
        $rows[] = [
            $data['totalBookings'],
            $data['totalRevenue'],
            $data['totalPaid'],
            max(0, $data['totalRevenue'] - $data['totalPaid']),
        ];
        $rows[] = [];

        // Chi tiết theo tháng
        if ($data['monthlyData']) {
            $rows[] = ['CHI TIET THEO THANG'];
            $rows[] = ['Thang', 'So booking', 'Doanh thu', 'Da thu', 'Con no', 'Ty le thu (%)'];
            foreach ($data['monthlyData'] as $row) {
                $rows[] = [
                    $row['month'],
                    $row['bookings'],
                    $row['revenue'],
                    $row['paid'],
                    $row['pending'],
                    $row['revenue'] > 0 ? round($row['paid'] / $row['revenue'] * 100, 1) : 0,
                ];
            }
            $rows[] = [
                'Tong cong',
                $data['totalBookings'],
                $data['totalRevenue'],
                $data['totalPaid'],
                max(0, $data['totalRevenue'] - $data['totalPaid']),
                $data['totalRevenue'] > 0 ? round($data['totalPaid'] / $data['totalRevenue'] * 100, 1) : 0,
            ];
            $rows[] = [];
        }

        // Chi tiết theo ngày
        if ($data['dailyData']) {
            $rows[] = ['CHI TIET THEO NGAY'];
            $rows[] = ['Ngay', 'So booking', 'Doanh thu', 'Da thu', 'Con no'];
            foreach ($data['dailyData'] as $row) {
                if ($row['bookings'] === 0) continue;
                $rows[] = [
                    $row['date'],
                    $row['bookings'],
                    $row['revenue'],
                    $row['paid'],
                    max(0, $row['revenue'] - $row['paid']),
                ];
            }
            $rows[] = ['Tong cong', $data['totalBookings'], $data['totalRevenue'], $data['totalPaid'], max(0, $data['totalRevenue'] - $data['totalPaid'])];
            $rows[] = [];
        }

        // Theo hạng phòng
        if ($data['categoryData']->isNotEmpty()) {
            $rows[] = ['DOANH THU THEO HANG PHONG'];
            $rows[] = ['Hang phong', 'So booking', 'Doanh thu', 'Ty trong (%)'];
            foreach ($data['categoryData'] as $cat) {
                $rows[] = [
                    $cat['name'],
                    $cat['bookings'],
                    $cat['revenue'],
                    $data['totalRevenue'] > 0 ? round($cat['revenue'] / $data['totalRevenue'] * 100, 1) : 0,
                ];
            }
        }

        // Build CSV
        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\r\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bao-cao-' . $slug . '.csv"',
        ]);
    }

    public function index(Request $request)
    {
        $reportType = $request->get('type', 'daily');
        $startDate = $request->get('start_date', now('Asia/Ho_Chi_Minh')->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now('Asia/Ho_Chi_Minh')->format('Y-m-d'));

        $start = Carbon::parse($startDate, 'Asia/Ho_Chi_Minh')->startOfDay();
        $end = Carbon::parse($endDate, 'Asia/Ho_Chi_Minh')->endOfDay();

        $data = $this->generateReport($reportType, $start, $end);

        return view('admin.pages.reports.index', array_merge($data, [
            'reportType' => $reportType,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]));
    }

    private function generateReport(string $reportType, Carbon $start, Carbon $end): array
    {
        switch ($reportType) {
            case 'daily':
                return $this->generateDailyReport($start, $end);
            case 'monthly':
                return $this->generateMonthlyReport($start, $end);
            case 'room_category':
                return $this->generateRoomCategoryReport($start, $end);
            case 'service':
                return $this->generateServiceReport($start, $end);
            default:
                return $this->generateDailyReport($start, $end);
        }
    }

    private function generateDailyReport(Carbon $start, Carbon $end): array
    {
        $bookings = Booking::whereBetween('check_in_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'canceled', 'no_show'])
            ->with(['roomCategory', 'customer', 'payments'])
            ->get();

        $totalBookings = $bookings->count();
        $totalRevenue  = $bookings->sum('estimated_total');
        $totalPaid     = $bookings->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount'));
        $totalDeposit  = $bookings->sum('deposit_amount');

        $dailyData = [];
        $current   = $start->copy();

        while ($current->lte($end)) {
            // Phân nhóm theo check_out_at để doanh thu ghi nhận vào ngày trả phòng
            $dayBookings = $bookings->filter(function ($booking) use ($current) {
                return $booking->check_out_at && Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->isSameDay($current);
            });

            $dailyData[] = [
                'date'     => $current->format('d/m/Y'),
                'bookings' => $dayBookings->count(),
                'revenue'  => $dayBookings->sum('estimated_total'),
                'paid'     => $dayBookings->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount')),
            ];

            $current->addDay();
        }

        return [
            'title'         => 'Báo cáo doanh thu theo ngày',
            'totalBookings' => $totalBookings,
            'totalRevenue'  => $totalRevenue,
            'totalPaid'     => $totalPaid,
            'totalDeposit'  => $totalDeposit,
            'totalPending'  => max(0, $totalRevenue - $totalPaid),
            'dailyData'     => $dailyData,
        ];
    }

    private function generateMonthlyReport(Carbon $start, Carbon $end): array
    {
        $bookings = Booking::whereBetween('check_in_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'canceled', 'no_show'])
            ->with(['roomCategory', 'customer', 'payments'])
            ->get();

        $monthlyData = [];
        $current     = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthEnd      = $current->copy()->endOfMonth();
            // Phân nhóm theo check_out_at để doanh thu ghi nhận vào tháng trả phòng
            $monthBookings = $bookings->filter(function ($booking) use ($current, $monthEnd) {
                return $booking->check_out_at && Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->between($current, $monthEnd);
            });

            $monthlyData[] = [
                'month'    => $current->format('m/Y'),
                'bookings' => $monthBookings->count(),
                'revenue'  => $monthBookings->sum('estimated_total'),
                'paid'     => $monthBookings->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount')),
            ];

            $current->addMonth();
        }

        $totalRevenue = $bookings->sum('estimated_total');
        $totalPaid    = $bookings->sum(fn($b) => $b->payments->where('status', 'success')->sum('amount'));

        return [
            'title'        => 'Báo cáo doanh thu theo tháng',
            'monthlyData'  => $monthlyData,
            'totalRevenue' => $totalRevenue,
            'totalPaid'    => $totalPaid,
        ];
    }

    private function generateRoomCategoryReport(Carbon $start, Carbon $end): array
    {
        $categories = RoomCategory::where('status', 'active')
            ->withCount('rooms')
            ->get();

        $categoryData = [];

        foreach ($categories as $category) {
            $bookings = Booking::where('room_category_id', $category->id)
                ->whereBetween('check_in_at', [$start, $end])
                ->whereNotIn('status', ['cancelled', 'canceled', 'no_show'])
                ->get();

            $totalRevenue = $bookings->sum('estimated_total');
            $totalNights  = $bookings->sum('night_count');

            $categoryData[] = [
                'category'  => $category->name,
                'roomCount' => $category->rooms_count,
                'bookings'  => $bookings->count(),
                'revenue'   => $totalRevenue,
                'nights'    => $totalNights,
                'adr'       => $totalNights > 0 ? $totalRevenue / $totalNights : 0,
            ];
        }

        return [
            'title'        => 'Báo cáo doanh thu theo hạng phòng',
            'categoryData' => $categoryData,
        ];
    }

    private function generateServiceReport(Carbon $start, Carbon $end): array
    {
        $services = Service::where('status', 'active')
            ->get();

        $serviceData = [];

        foreach ($services as $service) {
            $serviceItems = \App\Models\BookingServiceItem::where('service_id', $service->id)
                ->where('billing_status', 'confirmed')
                ->whereHas('booking', function ($query) use ($start, $end) {
                    $query->whereBetween('check_in_at', [$start, $end])
                        ->whereNotIn('status', ['cancelled', 'canceled', 'no_show']);
                })
                ->get();

            $totalRevenue = $serviceItems->sum('total');
            $totalQuantity = $serviceItems->sum('quantity');

            $serviceData[] = [
                'service' => $service->name,
                'quantity' => $totalQuantity,
                'revenue' => $totalRevenue,
            ];
        }

        return [
            'title' => 'Báo cáo doanh thu dịch vụ',
            'serviceData' => $serviceData,
        ];
    }
}
