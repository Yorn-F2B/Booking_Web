<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\RoomCategory;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
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
        $bookings = Booking::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->with(['roomCategory', 'customer', 'payments'])
            ->get();

        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum('estimated_total');
        $totalPaid = $bookings->sum(function ($booking) {
            return $booking->payments->sum('amount');
        });
        $totalDeposit = $bookings->sum('deposit_amount');

        $dailyData = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dayBookings = $bookings->filter(function ($booking) use ($current) {
                return Carbon::parse($booking->created_at)->isSameDay($current);
            });

            $dailyData[] = [
                'date' => $current->format('d/m/Y'),
                'bookings' => $dayBookings->count(),
                'revenue' => $dayBookings->sum('estimated_total'),
                'paid' => $dayBookings->sum(function ($b) {
                    return $b->payments->sum('amount');
                }),
            ];

            $current->addDay();
        }

        return [
            'title' => 'Báo cáo doanh thu theo ngày',
            'totalBookings' => $totalBookings,
            'totalRevenue' => $totalRevenue,
            'totalPaid' => $totalPaid,
            'totalDeposit' => $totalDeposit,
            'totalPending' => $totalRevenue - $totalPaid,
            'dailyData' => $dailyData,
        ];
    }

    private function generateMonthlyReport(Carbon $start, Carbon $end): array
    {
        $bookings = Booking::whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->with(['roomCategory', 'customer', 'payments'])
            ->get();

        $monthlyData = [];
        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthEnd = $current->copy()->endOfMonth();
            $monthBookings = $bookings->filter(function ($booking) use ($current, $monthEnd) {
                $bookingDate = Carbon::parse($booking->created_at);
                return $bookingDate->between($current, $monthEnd);
            });

            $monthlyData[] = [
                'month' => $current->format('m/Y'),
                'bookings' => $monthBookings->count(),
                'revenue' => $monthBookings->sum('estimated_total'),
                'paid' => $monthBookings->sum(function ($b) {
                    return $b->payments->sum('amount');
                }),
            ];

            $current->addMonth();
        }

        return [
            'title' => 'Báo cáo doanh thu theo tháng',
            'monthlyData' => $monthlyData,
            'totalRevenue' => $bookings->sum('estimated_total'),
            'totalPaid' => $bookings->sum(function ($b) {
                return $b->payments->sum('amount');
            }),
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
                ->whereBetween('created_at', [$start, $end])
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->get();

            $totalRevenue = $bookings->sum('estimated_total');
            $totalNights = $bookings->sum('night_count');

            $categoryData[] = [
                'category' => $category->name,
                'roomCount' => $category->rooms_count,
                'bookings' => $bookings->count(),
                'revenue' => $totalRevenue,
                'nights' => $totalNights,
                'adr' => $totalNights > 0 ? $totalRevenue / $totalNights : 0,
            ];
        }

        return [
            'title' => 'Báo cáo doanh thu theo hạng phòng',
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
                ->whereHas('booking', function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end])
                        ->whereNotIn('status', ['cancelled', 'no_show']);
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
