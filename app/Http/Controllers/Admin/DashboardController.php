<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\BookingServiceItem;
use App\Models\ChatConversation;
use App\Models\EmailDeliveryLog;
use App\Models\RoomIssueRequest;
use App\Models\Service;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private array $activeBookingStatuses = ['pending', 'confirmed', 'checked_in', 'inspection_requested'];
    private array $receivableBookingStatuses = ['confirmed', 'checked_in', 'inspection_requested', 'checked_out', 'completed'];
    private array $occupancyBookingStatuses = ['confirmed', 'checked_in', 'inspection_requested', 'checked_out', 'completed'];

    public function index(Request $request)
    {
        $user = Auth::user();

        // Dashboard này là bảng điều hành cấp cao: chỉ Super Admin được xem.
        if (!$user || $user->role !== 'super_admin') {
            return match ($user?->role) {
                'manager', 'receptionist_lead', 'receptionist' => redirect()->route('admin.bookings.index'),
                'housekeeping_supervisor', 'housekeeping' => redirect()->route('admin.housekeeping.index'),
                default => redirect()->route('home'),
            };
        }

        [$from, $to, $preset] = $this->resolveDashboardRange($request);
        $periodDays = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $previousTo = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($periodDays - 1)->startOfDay();
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        $grossRevenue = $this->grossCollectedForPeriod($from, $to);
        $revenue = $grossRevenue;
        $previousGrossRevenue = $this->grossCollectedForPeriod($previousFrom, $previousTo);
        $previousRevenue = $previousGrossRevenue;
        $revenueChangePercent = $this->percentChange($revenue, $previousRevenue);

        $successPayments = BookingPayment::query()
            ->where('status', 'success')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($fallback) use ($from, $to) {
                        $fallback->whereNull('paid_at')->whereBetween('created_at', [$from, $to]);
                    });
            });

        $paymentProviderRows = (clone $successPayments)
            ->selectRaw("CASE WHEN provider IN ('vnpay','admin_vnpay') THEN 'vnpay' WHEN provider IN ('cash','admin_cash') THEN 'cash' WHEN provider IN ('bank_transfer','admin_bank_transfer') THEN 'bank_transfer' ELSE provider END as provider_key, SUM(amount) as total")
            ->groupBy('provider_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'key' => (string) $row->provider_key,
                'label' => match ((string) $row->provider_key) {
                    'vnpay' => 'VNPay',
                    'cash' => 'Tiền mặt',
                    'bank_transfer' => 'Chuyển khoản',
                    default => (string) $row->provider_key,
                },
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();

        $bookingPeriod = Booking::query()->whereBetween('created_at', [$from, $to]);
        $newBookings = (int) (clone $bookingPeriod)->count();
        $cancelledBookings = (int) (clone $bookingPeriod)->where('status', 'cancelled')->count();
        $completedBookings = (int) (clone $bookingPeriod)->whereIn('status', ['checked_out', 'completed'])->count();
        $bookingValue = (float) (clone $bookingPeriod)->where('status', '!=', 'cancelled')->sum('estimated_total');

        $noShowCount = (int) BookingLog::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('action', [
                'auto_cancel_no_show',
                'cancel_no_show',
                'late_arrival_cancelled',
                'system_no_show_cancelled',
                'receptionist_no_show_cancelled',
            ])
            ->distinct('booking_id')
            ->count('booking_id');

        $bookingSourceRows = Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("CASE
                WHEN booking_source = 'user_online' THEN 'website'
                WHEN booking_source = 'reception' AND booking_mode = 'walk_in' THEN 'walk_in'
                WHEN booking_source = 'reception' THEN 'reception_advance'
                ELSE 'other'
            END as source_key, COUNT(*) as total")
            ->groupBy('source_key')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'key' => (string) $row->source_key,
                'label' => match ((string) $row->source_key) {
                    'website' => 'Website',
                    'walk_in' => 'Walk-in tại quầy',
                    'reception_advance' => 'Lễ tân đặt trước',
                    default => 'Khác',
                },
                'count' => (int) $row->total,
            ])
            ->values()
            ->all();

        $bookingStatusRows = Booking::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $roomStatuses = Room::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $totalRooms = (int) Room::query()->count();
        $currentOccupiedRooms = (int) ($roomStatuses['occupied'] ?? 0);
        $currentAvailableRooms = (int) ($roomStatuses['available'] ?? 0);
        $currentNotReadyRooms = (int) (($roomStatuses['cleaning'] ?? 0) + ($roomStatuses['inspection'] ?? 0) + ($roomStatuses['maintenance'] ?? 0));

        $occupancy = $this->buildOccupancyReport($from, $to, $periodDays, $totalRooms);
        $categoryOccupancy = $this->buildCategoryOccupancyReport($from, $to, $periodDays);
        $revenueTrend = $this->buildRevenueTrendForRange($from, $to);
        $surchargeRows = $this->buildSurchargeReport($from, $to);

        $paidByBooking = BookingPayment::query()
            ->selectRaw('booking_id, SUM(amount) as paid_total')
            ->where('status', 'success')
            ->groupBy('booking_id');
        $receivableRow = Booking::query()
            ->leftJoinSub($paidByBooking, 'paid_ledger', fn ($join) => $join->on('paid_ledger.booking_id', '=', 'bookings.id'))
            ->whereIn('bookings.status', $this->receivableBookingStatuses)
            ->where('bookings.check_in_at', '<=', $to)
            ->where('bookings.check_out_at', '>=', $from)
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(bookings.final_total, bookings.estimated_total, 0) - COALESCE(paid_ledger.paid_total, 0), 0)), 0) as amount')
            ->selectRaw('SUM(CASE WHEN GREATEST(COALESCE(bookings.final_total, bookings.estimated_total, 0) - COALESCE(paid_ledger.paid_total, 0), 0) > 0 THEN 1 ELSE 0 END) as booking_count')
            ->first();
        $receivableAmount = (float) ($receivableRow->amount ?? 0);
        $receivableBookings = (int) ($receivableRow->booking_count ?? 0);

        $activeGuests = (int) Booking::query()->where('status', 'checked_in')
            ->sum(DB::raw('COALESCE(adult_count,0) + COALESCE(child_count,0) + COALESCE(baby_count,0)'));
        $activeStays = (int) Booking::query()->where('status', 'checked_in')->count();

        $periodLabel = $from->format('d/m/Y') === $to->format('d/m/Y')
            ? $from->format('d/m/Y')
            : $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y');
        return view('admin.pages.dashboard.dashboard', compact(
            'now',
            'from',
            'to',
            'preset',
            'periodDays',
            'periodLabel',
            'revenue',
            'grossRevenue',
            'previousRevenue',
            'revenueChangePercent',
            'paymentProviderRows',
            'newBookings',
            'cancelledBookings',
            'completedBookings',
            'bookingValue',
            'noShowCount',
            'bookingSourceRows',
            'bookingStatusRows',
            'roomStatuses',
            'totalRooms',
            'currentOccupiedRooms',
            'currentAvailableRooms',
            'currentNotReadyRooms',
            'occupancy',
            'categoryOccupancy',
            'revenueTrend',
            'surchargeRows',
            'receivableAmount',
            'receivableBookings',
            'activeGuests',
            'activeStays'
        ));
    }


    public function detail(Request $request, string $metric)
    {
        abort_unless(Auth::user()?->role === 'super_admin', 403);
        [$from, $to] = $this->resolveDashboardRange($request);

        $labels = [
            'revenue' => ['Tiền đã thu trong kỳ', 'Tổng các giao dịch payment có trạng thái thành công theo paid_at; nếu paid_at trống thì dùng created_at.'],
            'booking_value' => ['Giá trị booking phát sinh', 'Tổng estimated_total của booking tạo trong kỳ, không tính booking đã hủy.'],
            'new_bookings' => ['Booking mới', 'Tất cả booking được tạo trong kỳ.'],
            'receivables' => ['Công nợ liên quan kỳ', 'Phần giá trị phải thu (ưu tiên final_total, nếu chưa chốt thì estimated_total) còn thiếu sau khi trừ tổng payment thành công của booking đã xác nhận/đang ở/đã trả và giao với kỳ; booking pending không được coi là công nợ.'],
            'occupancy' => ['Chi tiết công suất phòng', 'Mỗi dòng là số phòng-ngày thực tế một phòng được booking hợp lệ sử dụng trong khoảng đang xem. Công suất = tổng phòng-ngày sử dụng / tổng số phòng × số ngày.'],
            'active_stays' => ['Khách đang lưu trú', 'Các booking đang checked-in tại thời điểm hiện tại; số lượng khách gồm người lớn, trẻ em và em bé trên booking.'],
            'booking_source' => ['Booking theo kênh tiếp nhận', 'Danh sách đầy đủ các booking được tạo trong kỳ thuộc đúng kênh đã chọn.'],
            'surcharges' => ['Phụ thu và phí phát sinh', 'Từng khoản phụ thu đã xác nhận trong kỳ, ghi rõ booking, tên khoản và số tiền.'],
            'payment_provider' => ['Tiền thu theo phương thức', 'Từng giao dịch thanh toán thành công trong kỳ thuộc phương thức đã chọn.'],
            'booking_status' => ['Booking theo trạng thái', 'Danh sách booking tạo trong kỳ thuộc đúng trạng thái đã chọn.'],
            'room_status' => ['Phòng theo trạng thái hiện tại', 'Danh sách phòng đang có trạng thái đã chọn tại thời điểm mở báo cáo.'],
            'category_occupancy' => ['Công suất theo hạng phòng', 'Các lượt sử dụng phòng thuộc hạng đã chọn và số phòng-ngày giao với khoảng báo cáo.'],
        ];
        abort_unless(isset($labels[$metric]), 404);
        [$title, $formula] = $labels[$metric];

        $rows = collect();
        $total = 0.0;
        $valueLabel = 'Số tiền';
        $valueType = 'money';
        $totalLabel = 'Tổng';
        $group = trim((string) $request->query('group'));
        $overlapRows = function (?int $categoryId = null) use ($from, $to) {
            return Booking::query()
                ->with(['bookingRooms.room.category'])
                ->whereIn('status', $this->occupancyBookingStatuses)
                ->where('check_in_at', '<=', $to)
                ->where('check_out_at', '>=', $from)
                ->get()
                ->flatMap(function ($booking) use ($from, $to, $categoryId) {
                    return $booking->bookingRooms->filter(function ($bookingRoom) use ($categoryId) {
                        return !$categoryId || (int) $bookingRoom->room?->room_category_id === $categoryId;
                    })->map(function ($bookingRoom) use ($booking, $from, $to) {
                        $bookingStart = Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
                        $bookingEnd = Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
                        $start = $bookingStart->greaterThan($from) ? $bookingStart : $from;
                        $end = $bookingEnd->lessThan($to) ? $bookingEnd : $to;
                        $roomDays = max(0, $start->diffInMinutes($end) / 1440);
                        return [
                            'booking' => $booking->booking_code,
                            'customer' => $booking->booked_customer_name,
                            'kind' => 'Phòng ' . ($bookingRoom->room?->room_number ?? '---') . ' · ' . ($bookingRoom->room?->category?->name ?? 'Chưa rõ hạng'),
                            'amount' => round($roomDays, 2),
                            'time' => $booking->check_in_at,
                            'url' => route('admin.bookings.show', $booking),
                        ];
                    });
                })->filter(fn ($row) => $row['amount'] > 0)->values();
        };

        if ($metric === 'revenue') {
            $paymentRows = BookingPayment::query()->with('booking')->where('status', 'success')
                ->where(fn($q) => $q->whereBetween('paid_at', [$from,$to])->orWhere(fn($x) => $x->whereNull('paid_at')->whereBetween('created_at',[$from,$to])))
                ->latest('id')->get()->map(fn($p) => ['booking'=>$p->booking?->booking_code,'customer'=>$p->booking?->customer_name_snapshot,'kind'=>'Thu · '.$p->provider,'amount'=>(float)$p->amount,'time'=>$p->paid_at ?: $p->created_at,'url'=>$p->booking ? route('admin.bookings.show',$p->booking) : null]);
            $rows=$paymentRows->sortByDesc(fn($r)=>(string)($r['time']??''))->values();
            $total=(float)$rows->sum('amount');
            $totalLabel='Tổng tiền từ '.$rows->count().' giao dịch';
        } elseif (in_array($metric, ['booking_value','new_bookings'], true)) {
            $q=Booking::query()->whereBetween('created_at',[$from,$to]);
            if ($metric !== 'new_bookings') $q->where('status','!=','cancelled');
            $rows=$q->latest()->get()->map(fn($b)=>['booking'=>$b->booking_code,'customer'=>$b->customer_name_snapshot,'kind'=>$b->status,'amount'=>(float)$b->estimated_total,'time'=>$b->created_at,'url'=>route('admin.bookings.show',$b)]);
            $total=$metric==='new_bookings'?(float)$rows->count():(float)$rows->sum('amount');
            if ($metric === 'new_bookings') {
                $totalLabel = 'Số booking';
            } else {
                $totalLabel = 'Tổng giá trị '.$rows->count().' booking';
            }
        } elseif ($metric === 'receivables') {
            $bookings=Booking::query()->whereIn('status',$this->receivableBookingStatuses)->where('check_in_at','<=',$to)->where('check_out_at','>=',$from)->latest()->get();
            $paid=BookingPayment::query()->whereIn('booking_id',$bookings->pluck('id'))->where('status','success')->selectRaw('booking_id,SUM(amount) total')->groupBy('booking_id')->pluck('total','booking_id');
            $rows=$bookings->map(function($b) use($paid){$amount=max(0,(float)($b->final_total ?? $b->estimated_total ?? 0)-(float)($paid[$b->id]??0));return ['booking'=>$b->booking_code,'customer'=>$b->customer_name_snapshot,'kind'=>$b->status,'amount'=>$amount,'time'=>$b->check_out_at,'url'=>route('admin.bookings.show',$b)];})->filter(fn($r)=>$r['amount']>0)->values();
            $total=(float)$rows->sum('amount');
            $totalLabel='Tổng công nợ '.$rows->count().' booking';
        } elseif ($metric === 'occupancy' || $metric === 'category_occupancy') {
            $categoryId = $metric === 'category_occupancy' ? (int) $group : null;
            abort_if($metric === 'category_occupancy' && $categoryId <= 0, 404);
            $rows = $overlapRows($categoryId);
            $total=(float)$rows->sum('amount');
            $valueLabel = 'Phòng-ngày';
            $valueType = 'number';
            $totalLabel = 'Tổng phòng-ngày sử dụng';
            $roomCount = $metric === 'category_occupancy'
                ? Room::query()->where('room_category_id', $categoryId)->count()
                : Room::query()->count();
            $periodDays = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
            $capacity = $roomCount * $periodDays;
            $rate = $capacity > 0 ? round(min(100, ($total / $capacity) * 100), 1) : 0;
            $formula .= ' Kỳ này: '.number_format($total, 2, ',', '.').' phòng-ngày sử dụng / '
                .number_format($capacity, 0, ',', '.').' phòng-ngày khả dụng = '
                .number_format($rate, 1, ',', '.').'%.'.($metric === 'category_occupancy' ? ' Chỉ tính hạng phòng đã chọn.' : '');
        } elseif ($metric === 'active_stays') {
            $rows = Booking::query()->where('status', 'checked_in')->latest('actual_check_in')->get()->map(function ($booking) {
                $guests = (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0);
                return ['booking'=>$booking->booking_code,'customer'=>$booking->booked_customer_name,'kind'=>'Đang ở đến '.optional($booking->check_out_at)->format('d/m/Y H:i'),'amount'=>$guests,'time'=>$booking->actual_check_in ?: $booking->check_in_at,'url'=>route('admin.bookings.show',$booking)];
            });
            $total=(float)$rows->sum('amount');
            $valueLabel='Số khách'; $valueType='integer'; $totalLabel='Tổng khách đang lưu trú';
        } elseif ($metric === 'booking_source') {
            abort_unless(in_array($group, ['website','walk_in','reception_advance','other'], true), 404);
            $query=Booking::query()->whereBetween('created_at',[$from,$to]);
            if ($group==='website') $query->where('booking_source','user_online');
            elseif ($group==='walk_in') $query->where('booking_source','reception')->where('booking_mode','walk_in');
            elseif ($group==='reception_advance') $query->where('booking_source','reception')->where('booking_mode','advance');
            else $query->whereNotIn('booking_source',['user_online','reception']);
            $rows=$query->latest()->get()->map(fn($b)=>['booking'=>$b->booking_code,'customer'=>$b->booked_customer_name,'kind'=>$b->status,'amount'=>(float)$b->estimated_total,'time'=>$b->created_at,'url'=>route('admin.bookings.show',$b)]);
            $total=(float)$rows->sum('amount');
            $totalLabel='Tổng giá trị '.$rows->count().' booking';
        } elseif ($metric === 'surcharges') {
            if ($group === 'room_adjustment') {
                $rows=DB::table('booking_rooms')->join('bookings','bookings.id','=','booking_rooms.booking_id')->leftJoin('rooms','rooms.id','=','booking_rooms.room_id')->whereNull('bookings.deleted_at')->where('booking_rooms.surcharge','>',0)->where('bookings.check_in_at','<=',$to)->where('bookings.check_out_at','>=',$from)->select('bookings.id','bookings.booking_code','bookings.customer_name_snapshot','rooms.room_number','booking_rooms.surcharge','bookings.check_in_at')->get()->map(fn($r)=>['booking'=>$r->booking_code,'customer'=>$r->customer_name_snapshot,'kind'=>'Điều chỉnh/phụ thu phòng '.($r->room_number??'---'),'amount'=>(float)$r->surcharge,'time'=>$r->check_in_at,'url'=>route('admin.bookings.show',$r->id)]);
            } else {
                abort_if($group === '', 404);
                $rows=BookingServiceItem::query()->with('booking')->where('billing_status','confirmed')->where('type',$group)->where(fn($q)=>$q->whereBetween('confirmed_at',[$from,$to])->orWhere(fn($x)=>$x->whereNull('confirmed_at')->whereBetween('created_at',[$from,$to])))->latest('id')->get()->map(fn($item)=>['booking'=>$item->booking?->booking_code,'customer'=>$item->booking?->booked_customer_name,'kind'=>$item->name,'amount'=>(float)$item->total,'time'=>$item->confirmed_at ?: $item->created_at,'url'=>$item->booking?route('admin.bookings.show',$item->booking):null]);
            }
            $total=(float)$rows->sum('amount');
            $totalLabel='Tổng '.$rows->count().' khoản';
        } elseif ($metric === 'payment_provider') {
            $providers=match($group){'vnpay'=>['vnpay','admin_vnpay'],'cash'=>['cash','admin_cash'],'bank_transfer'=>['bank_transfer','admin_bank_transfer'],default=>[$group]};
            abort_if($group === '', 404);
            $rows=BookingPayment::query()->with('booking')->where('status','success')->whereIn('provider',$providers)->where(fn($q)=>$q->whereBetween('paid_at',[$from,$to])->orWhere(fn($x)=>$x->whereNull('paid_at')->whereBetween('created_at',[$from,$to])))->latest('id')->get()->map(fn($p)=>['booking'=>$p->booking?->booking_code,'customer'=>$p->booking?->booked_customer_name,'kind'=>$p->provider,'amount'=>(float)$p->amount,'time'=>$p->paid_at ?: $p->created_at,'url'=>$p->booking?route('admin.bookings.show',$p->booking):null]);
            $total=(float)$rows->sum('amount');
            $totalLabel='Tổng '.$rows->count().' giao dịch';
        } elseif ($metric === 'booking_status') {
            abort_unless(in_array($group,['pending','confirmed','checked_in','inspection_requested','checked_out','completed','cancelled'],true),404);
            $rows=Booking::query()->whereBetween('created_at',[$from,$to])->where('status',$group)->latest()->get()->map(fn($b)=>['booking'=>$b->booking_code,'customer'=>$b->booked_customer_name,'kind'=>$b->status,'amount'=>(float)$b->estimated_total,'time'=>$b->created_at,'url'=>route('admin.bookings.show',$b)]);
            $total=(float)$rows->sum('amount');
            $totalLabel='Tổng giá trị '.$rows->count().' booking';
        } else {
            abort_unless(in_array($group,['available','reserved','occupied','cleaning','inspection','maintenance','not_ready'],true),404);
            $roomQuery=Room::query()->with('category');
            $group === 'not_ready'
                ? $roomQuery->whereIn('status',['cleaning','inspection','maintenance'])
                : $roomQuery->where('status',$group);
            $rows=$roomQuery->orderBy('floor_number')->orderBy('room_number')->get()->map(fn($room)=>['booking'=>'Phòng '.$room->room_number,'customer'=>$room->category?->name,'kind'=>$room->status,'amount'=>null,'time'=>$room->updated_at,'url'=>route('admin.rooms.show',$room)]);
            $total=(float)$rows->count();
            $valueLabel=''; $valueType='none'; $totalLabel='Số phòng';
        }

        return view('admin.pages.dashboard.detail', compact('metric','title','formula','rows','total','from','to','valueLabel','valueType','totalLabel'));
    }

    private function resolveDashboardRange(Request $request): array
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $preset = (string) $request->query('preset', 'this_month');
        $fromInput = trim((string) $request->query('from'));
        $toInput = trim((string) $request->query('to'));

        if ($fromInput !== '' || $toInput !== '') {
            try {
                $from = $fromInput !== '' ? Carbon::createFromFormat('Y-m-d', $fromInput, 'Asia/Ho_Chi_Minh') : $now->copy()->startOfMonth();
                $to = $toInput !== '' ? Carbon::createFromFormat('Y-m-d', $toInput, 'Asia/Ho_Chi_Minh') : $now->copy();
                $preset = 'custom';
            } catch (\Throwable) {
                $from = $now->copy()->startOfMonth();
                $to = $now->copy();
                $preset = 'this_month';
            }
        } else {
            [$from, $to] = match ($preset) {
                'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
                '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
                '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
                'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
                'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
                default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            };
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay(), $preset];
    }

    private function percentChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.01) {
            return abs($current) < 0.01 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function buildOccupancyReport(Carbon $from, Carbon $to, int $periodDays, int $totalRooms): array
    {
        $periodStart = $from->copy()->startOfDay()->toDateTimeString();
        $periodEndExclusive = $to->copy()->endOfDay()->addSecond()->toDateTimeString();

        $occupiedRoomDays = (float) DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->whereNull('bookings.deleted_at')
            ->whereIn('bookings.status', $this->occupancyBookingStatuses)
            ->where('bookings.check_in_at', '<', $periodEndExclusive)
            ->where('bookings.check_out_at', '>', $periodStart)
            ->selectRaw(
                'COALESCE(SUM(GREATEST(TIMESTAMPDIFF(MINUTE, GREATEST(bookings.check_in_at, ?), LEAST(bookings.check_out_at, ?)), 0) / 1440), 0) as room_days',
                [$periodStart, $periodEndExclusive]
            )
            ->value('room_days');

        $capacityRoomDays = max(0, $totalRooms * max(1, $periodDays));
        $rate = $capacityRoomDays > 0 ? round(min(100, ($occupiedRoomDays / $capacityRoomDays) * 100), 1) : 0.0;

        return [
            'occupied_room_days' => round($occupiedRoomDays, 1),
            'capacity_room_days' => $capacityRoomDays,
            'rate' => $rate,
        ];
    }

    private function buildCategoryOccupancyReport(Carbon $from, Carbon $to, int $periodDays): array
    {
        $periodStart = $from->copy()->startOfDay()->toDateTimeString();
        $periodEndExclusive = $to->copy()->endOfDay()->addSecond()->toDateTimeString();

        $roomCounts = RoomCategory::query()
            ->leftJoin('rooms', function ($join) {
                $join->on('rooms.room_category_id', '=', 'room_categories.id')->whereNull('rooms.deleted_at');
            })
            ->select('room_categories.id', 'room_categories.name')
            ->selectRaw('COUNT(rooms.id) as room_count')
            ->groupBy('room_categories.id', 'room_categories.name')
            ->get()
            ->keyBy('id');

        $used = DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->join('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
            ->whereNull('bookings.deleted_at')
            ->whereNull('rooms.deleted_at')
            ->whereIn('bookings.status', $this->occupancyBookingStatuses)
            ->where('bookings.check_in_at', '<', $periodEndExclusive)
            ->where('bookings.check_out_at', '>', $periodStart)
            ->select('rooms.room_category_id')
            ->selectRaw(
                'COALESCE(SUM(GREATEST(TIMESTAMPDIFF(MINUTE, GREATEST(bookings.check_in_at, ?), LEAST(bookings.check_out_at, ?)), 0) / 1440), 0) as room_days',
                [$periodStart, $periodEndExclusive]
            )
            ->groupBy('rooms.room_category_id')
            ->pluck('room_days', 'room_category_id');

        return $roomCounts->map(function ($row, $categoryId) use ($used, $periodDays) {
            $capacity = (int) $row->room_count * max(1, $periodDays);
            $roomDays = (float) ($used[$categoryId] ?? 0);

            return [
                'id' => (int) $categoryId,
                'name' => (string) $row->name,
                'room_count' => (int) $row->room_count,
                'room_days' => round($roomDays, 1),
                'rate' => $capacity > 0 ? round(min(100, ($roomDays / $capacity) * 100), 1) : 0,
            ];
        })->sortByDesc('rate')->values()->all();
    }

    private function buildRevenueTrendForRange(Carbon $from, Carbon $to): array
    {
        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $payments = BookingPayment::query()
            ->where('status', 'success')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($fallback) use ($from, $to) {
                        $fallback->whereNull('paid_at')->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->get(['amount', 'paid_at', 'created_at']);

        $points = [];
        $cursor = $from->copy()->startOfDay();
        if ($days <= 45) {
            while ($cursor->lte($to)) {
                $start = $cursor->copy()->startOfDay();
                $end = $cursor->copy()->endOfDay();
                $points[] = [
                    'label' => $cursor->format('d/m'),
                    'value' => (float) $payments->filter(fn ($p) => ($p->paid_at ?? $p->created_at)?->betweenIncluded($start, $end))->sum('amount'),
                ];
                $cursor->addDay();
            }
        } elseif ($days <= 180) {
            while ($cursor->lte($to)) {
                $start = $cursor->copy()->startOfDay();
                $end = $cursor->copy()->addDays(6)->endOfDay()->min($to);
                $label = $start->month === $end->month
                    ? $start->format('d') . '–' . $end->format('d/m')
                    : $start->format('d/m') . '–' . $end->format('d/m');
                $points[] = [
                    'label' => $label,
                    'value' => (float) $payments->filter(fn ($p) => ($p->paid_at ?? $p->created_at)?->betweenIncluded($start, $end))->sum('amount'),
                ];
                $cursor = $end->copy()->addDay()->startOfDay();
            }
        } else {
            $cursor = $from->copy()->startOfMonth();
            while ($cursor->lte($to)) {
                $start = $cursor->copy()->startOfMonth()->max($from);
                $end = $cursor->copy()->endOfMonth()->min($to);
                $points[] = [
                    'label' => $cursor->format('m/Y'),
                    'value' => (float) $payments->filter(fn ($p) => ($p->paid_at ?? $p->created_at)?->betweenIncluded($start, $end))->sum('amount'),
                ];
                $cursor->addMonthNoOverflow()->startOfMonth();
            }
        }

        $max = max(1, (float) collect($points)->max('value'));
        $pointCount = count($points);
        $tickStep = max(1, (int) ceil(max(1, $pointCount - 1) / 6));

        foreach ($points as $index => &$point) {
            $point['show_label'] = $index === 0
                || $index === $pointCount - 1
                || $index % $tickStep === 0;
            $point['show_value'] = (float) $point['value'] > 0
                && ($point['show_label'] || (float) $point['value'] >= $max);
        }
        unset($point);

        return ['points' => $points, 'max' => $max];
    }

    private function buildSurchargeReport(Carbon $from, Carbon $to): array
    {
        $labels = [
            Service::TYPE_EARLY_CHECKIN_FEE => 'Check-in sớm',
            Service::TYPE_LATE_CHECKOUT_FEE => 'Check-out muộn',
            Service::TYPE_OCCUPANCY_FEE => 'Phụ thu số người',
            Service::TYPE_EXTRA_GUEST_FEE => 'Vượt sức chứa',
            Service::TYPE_EXTENSION_FEE => 'Gia hạn lưu trú',
            Service::TYPE_DAMAGE_FEE => 'Hư hại / bồi thường',
            Service::TYPE_POLICY_VIOLATION_FEE => 'Vi phạm chính sách',
            Service::TYPE_MANUAL_FEE => 'Phát sinh thủ công',
            'late_arrival_fee' => 'Đến muộn',
        ];

        $rows = BookingServiceItem::query()
            ->where('billing_status', 'confirmed')
            ->whereIn('type', array_keys($labels))
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('confirmed_at', [$from, $to])
                    ->orWhere(function ($fallback) use ($from, $to) {
                        $fallback->whereNull('confirmed_at')->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->select('type', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as items'))
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'key' => (string) $row->type,
                'label' => $labels[$row->type] ?? $row->type,
                'total' => (float) $row->total,
                'items' => (int) $row->items,
            ]);

        $roomAdjustments = (float) DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->whereNull('bookings.deleted_at')
            ->where('booking_rooms.surcharge', '>', 0)
            ->where('bookings.check_in_at', '<=', $to)
            ->where('bookings.check_out_at', '>=', $from)
            ->sum('booking_rooms.surcharge');
        if ($roomAdjustments > 0) {
            $rows->push(['key' => 'room_adjustment', 'label' => 'Điều chỉnh/phụ thu phòng', 'total' => $roomAdjustments, 'items' => null]);
        }

        return $rows->sortByDesc('total')->values()->all();
    }

    private function buildExecutiveAlerts(Carbon $now): array
    {
        $alerts = [];

        // 1) Công nợ chỉ cảnh báo khi booking đang ở/chờ kiểm tra và sắp đến hạn
        // trả phòng trong 24 giờ, thay vì coi mọi khoản chưa thu là "bất thường".
        $paidByBooking = BookingPayment::query()
            ->selectRaw('booking_id, SUM(amount) as paid_total')
            ->where('status', 'success')
            ->groupBy('booking_id');

        $dueSoon = Booking::query()
            ->leftJoinSub($paidByBooking, 'paid_alert_ledger', fn ($join) => $join->on('paid_alert_ledger.booking_id', '=', 'bookings.id'))
            ->whereIn('bookings.status', ['checked_in', 'inspection_requested'])
            ->whereBetween('bookings.check_out_at', [$now->copy()->subHours(6), $now->copy()->addHours(24)])
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(bookings.final_total, bookings.estimated_total, 0) - COALESCE(paid_alert_ledger.paid_total, 0), 0)), 0) as amount')
            ->selectRaw('SUM(CASE WHEN GREATEST(COALESCE(bookings.final_total, bookings.estimated_total, 0) - COALESCE(paid_alert_ledger.paid_total, 0), 0) > 0 THEN 1 ELSE 0 END) as booking_count')
            ->first();

        $dueSoonAmount = (float) ($dueSoon->amount ?? 0);
        $dueSoonCount = (int) ($dueSoon->booking_count ?? 0);
        if ($dueSoonCount > 0) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Công nợ sắp đến hạn',
                'value' => $dueSoonCount . ' booking',
                'detail' => number_format($dueSoonAmount, 0, ',', '.') . 'đ chưa thu · khách trả phòng trong vòng 24 giờ',
                'url' => route('admin.bookings.index'),
                'action' => 'Xem booking',
            ];
        }

        // 2) Chỉ cảnh báo giao dịch đang treo quá 15 phút hoặc failed gần đây.
        $stalePending = (int) BookingPayment::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', $now->copy()->subMinutes(15))
            ->count();
        $failed24h = (int) BookingPayment::query()
            ->where('status', 'failed')
            ->where('updated_at', '>=', $now->copy()->subDay())
            ->count();
        if ($stalePending > 0 || $failed24h > 0) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Thanh toán có dấu hiệu bất thường',
                'value' => ($stalePending + $failed24h) . ' giao dịch',
                'detail' => 'Treo >15 phút: ' . $stalePending . ' · Failed 24 giờ: ' . $failed24h,
                'url' => route('admin.bookings.index'),
                'action' => 'Kiểm tra',
            ];
        }

        // 3) Phòng ở trạng thái không sẵn sàng quá lâu mới cần đưa lên dashboard cấp cao.
        $roomAlertMinutes = max(30, (int) app(\App\Services\HotelPolicyService::class)
            ->get('housekeeping.slow_room_alert_minutes', 120));
        $staleRoomCount = (int) Room::query()
            ->whereIn('status', ['cleaning', 'inspection', 'maintenance'])
            ->where('updated_at', '<=', $now->copy()->subMinutes($roomAlertMinutes))
            ->count();
        $maintenanceCount = (int) Room::query()->where('status', 'maintenance')->count();
        if ($staleRoomCount > 0) {
            $alerts[] = [
                'level' => $maintenanceCount > 0 ? 'danger' : 'info',
                'title' => 'Phòng chưa sẵn sàng quá lâu',
                'value' => $staleRoomCount . ' phòng',
                'detail' => 'Quá ' . $roomAlertMinutes . ' phút chưa trở lại sẵn sàng · đang bảo trì: ' . $maintenanceCount,
                'url' => route('admin.rooms.index'),
                'action' => 'Xem phòng',
            ];
        }

        // 4) Sự cố phòng mở quá 30 phút mà chưa khép lại.
        $staleIssues = (int) RoomIssueRequest::query()
            ->whereIn('workflow_status', ['pending', 'proposal_ready', 'waiting_guest_confirmation', 'guest_accepted', 'guest_requested_change'])
            ->where('created_at', '<=', $now->copy()->subMinutes(30))
            ->count();
        if ($staleIssues > 0) {
            $alerts[] = [
                'level' => 'danger',
                'title' => 'Sự cố phòng đang tồn đọng',
                'value' => $staleIssues . ' sự cố',
                'detail' => 'Đã mở quá 30 phút nhưng chưa hoàn tất quy trình xử lý',
                'url' => route('admin.room-issues.index'),
                'action' => 'Xem sự cố',
            ];
        }

        // 5) Khách chờ chat quá 5 phút mới là bất thường đối với Super Admin.
        $waitingChats = (int) ChatConversation::query()
            ->where('status', 'waiting')
            ->where('updated_at', '<=', $now->copy()->subMinutes(5))
            ->count();
        if ($waitingChats > 0) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Khách chờ chat quá lâu',
                'value' => $waitingChats . ' cuộc',
                'detail' => 'Chưa được tiếp nhận sau hơn 5 phút',
                'url' => route('admin.chats.index'),
                'action' => 'Xem chat',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'level' => 'success',
                'title' => 'Không có ngoại lệ cần can thiệp',
                'value' => 'Vận hành ổn định',
                'detail' => 'Các ngưỡng công nợ, thanh toán, phòng, sự cố và chat hiện chưa vượt mức cảnh báo.',
                'url' => null,
                'action' => null,
            ];
        }

        return $alerts;
    }

    private function bookingQuery()
    {
        return Booking::query();
    }

    private function grossCollectedForPeriod(Carbon $from, Carbon $to): float
    {
        return (float) BookingPayment::query()
            ->where('status', 'success')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('paid_at', [$from, $to])
                    ->orWhere(function ($subQuery) use ($from, $to) {
                        $subQuery->whereNull('paid_at')
                            ->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->sum('amount');
    }

    private function refundsCompletedForPeriod(Carbon $from, Carbon $to): float
    {
        return (float) Booking::query()
            ->where('refund_status', 'completed')
            ->where('refund_due_amount', '>', 0)
            ->whereBetween('refund_processed_at', [$from, $to])
            ->sum('refund_due_amount');
    }

    private function buildUrgentAlerts(Carbon $now, Carbon $todayStart, Carbon $todayEnd, $user): array
    {
        $alerts = [];
        $canOpenBooking = $this->canOpenBooking($user);

        $unassignedToday = $this->bookingQuery()
            ->with(['customer', 'roomCategory'])
            ->whereBetween('check_in_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDoesntHave('bookingRooms')
            ->orderBy('check_in_at')
            ->limit(4)
            ->get();

        foreach ($unassignedToday as $booking) {
            $alerts[] = [
                'level' => 'danger',
                'icon' => 'bx bx-error-circle',
                'title' => 'Booking hôm nay chưa gán phòng',
                'message' => $booking->booking_code . ' · ' . $this->customerName($booking) . ' · nhận ' . $this->formatDateTime($booking->check_in_at),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Gán phòng',
            ];
        }

        $lateArrivals = $this->bookingQuery()
            ->with(['customer', 'bookingRooms.room'])
            ->where('status', 'confirmed')
            ->where('booking_mode', 'advance')
            ->where('booking_type', 'overnight')
            ->where('check_in_at', '<', $now)
            ->orderBy('check_in_at')
            ->get()
            ->filter(fn ($booking) => $booking->usesLateArrivalNoShowPolicy())
            ->take(4);

        foreach ($lateArrivals as $booking) {
            $lateMinutes = max(0, Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($now));

            $alerts[] = [
                'level' => $lateMinutes >= 240 ? 'danger' : 'warning',
                'icon' => 'bx bx-time-five',
                'title' => 'Khách quá giờ check-in',
                'message' => $booking->booking_code . ' · muộn khoảng ' . $this->formatDuration($lateMinutes) . ' · phòng ' . $this->roomNumbers($booking),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Xử lý',
            ];
        }

        $lateCheckouts = $this->bookingQuery()
            ->with(['customer', 'bookingRooms.room'])
            ->where('status', 'checked_in')
            ->where('check_out_at', '<', $now)
            ->orderBy('check_out_at')
            ->limit(4)
            ->get();

        foreach ($lateCheckouts as $booking) {
            $lateMinutes = max(0, Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($now));

            $alerts[] = [
                'level' => 'danger',
                'icon' => 'bx bx-log-out',
                'title' => 'Khách quá giờ check-out',
                'message' => $booking->booking_code . ' · trễ ' . $this->formatDuration($lateMinutes) . ' · phòng ' . $this->roomNumbers($booking),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Check-out',
            ];
        }

        $slowRoomAlertMinutes = max(1, (int) app(\App\Services\HotelPolicyService::class)
            ->get('housekeeping.slow_room_alert_minutes', 120));
        $slowRooms = Room::query()
            ->with('category')
            ->whereIn('status', ['cleaning', 'inspection'])
            ->where('updated_at', '<', $now->copy()->subMinutes($slowRoomAlertMinutes))
            ->orderBy('updated_at')
            ->limit(5)
            ->get();

        foreach ($slowRooms as $room) {
            $waitingMinutes = max(0, Carbon::parse($room->updated_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($now));
            $statusText = $room->status === 'inspection' ? 'chờ kiểm tra' : 'chờ dọn';

            $alerts[] = [
                'level' => $room->status === 'inspection' ? 'warning' : 'danger',
                'icon' => $room->status === 'inspection' ? 'bx bx-search-alt' : 'bx bx-brush',
                'title' => 'Phòng ' . $statusText . ' quá lâu',
                'message' => 'Phòng ' . $room->room_number . ' · tầng ' . $room->floor_number . ' · chờ ' . $this->formatDuration($waitingMinutes),
                'url' => $room->status === 'cleaning' ? route('admin.housekeeping.index') : route('admin.floor-inspections.index'),
                'action' => 'Xem',
            ];
        }

        $reportedInspections = RoomInspection::query()
            ->with(['booking.customer', 'room'])
            ->where('status', 'reported')
            ->latest()
            ->limit(4)
            ->get();

        foreach ($reportedInspections as $inspection) {
            $stageText = match ($inspection->workflow_stage) {
                RoomInspection::STAGE_GUEST_CONSULTATION => 'chờ lễ tân trao đổi với khách',
                RoomInspection::STAGE_HOUSEKEEPING_RECHECK => 'chờ buồng phòng kiểm tra lại',
                default => 'đang đối chiếu kết quả',
            };

            $alerts[] = [
                'level' => 'warning',
                'icon' => 'bx bx-search-alt',
                'title' => 'Phiếu kiểm tra đang xử lý',
                'message' => 'Phòng ' . ($inspection->room->room_number ?? 'N/A') . ' · booking ' . ($inspection->booking->booking_code ?? 'N/A') . ' · ' . $stageText,
                'url' => route('admin.floor-inspections.show', $inspection),
                'action' => 'Xem',
            ];
        }

        return collect($alerts)
            ->sortBy(function ($alert) {
                return ['danger' => 0, 'warning' => 1, 'info' => 2][$alert['level']] ?? 9;
            })
            ->take(12)
            ->values()
            ->all();
    }

    private function buildSystemWarnings(Carbon $now, $user): array
    {
        $warnings = [];
        $canOpenBooking = $this->canOpenBooking($user);

        $paidButUnassigned = $this->bookingQuery()
            ->with('customer')
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereIn('payment_status', ['partial', 'paid'])
            ->whereDoesntHave('bookingRooms')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($paidButUnassigned as $booking) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Đã thu tiền nhưng chưa có phòng',
                'message' => $booking->booking_code . ' · ' . $this->customerName($booking) . ' · trạng thái thanh toán: ' . ($booking->payment_status ?? 'N/A'),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Kiểm tra',
            ];
        }

        $depositMismatch = $this->bookingQuery()
            ->with('customer')
            ->where('deposit_amount', '>', 0)
            ->where('payment_status', 'unpaid')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($depositMismatch as $booking) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Cọc khác trạng thái thanh toán',
                'message' => $booking->booking_code . ' · cọc ' . number_format((float) $booking->deposit_amount, 0, ',', '.') . 'đ nhưng vẫn unpaid',
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Sửa',
            ];
        }

        $checkedInRoomMismatch = $this->bookingQuery()
            ->with(['customer', 'bookingRooms.room'])
            ->where('status', 'checked_in')
            ->whereHas('bookingRooms.room', function ($query) {
                $query->where('status', '!=', 'occupied');
            })
            ->latest()
            ->limit(5)
            ->get();

        foreach ($checkedInRoomMismatch as $booking) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Booking đang ở nhưng phòng không occupied',
                'message' => $booking->booking_code . ' · phòng ' . $this->roomNumbers($booking) . ' · cần đồng bộ trạng thái phòng',
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Xem',
            ];
        }

        $availableWithActiveBooking = Room::query()
            ->with('category')
            ->where('status', 'available')
            ->whereHas('bookingRooms.booking', function ($query) use ($now) {
                $query->whereIn('status', $this->activeBookingStatuses)
                    ->where('check_in_at', '<=', $now)
                    ->where('check_out_at', '>', $now);
            })
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->limit(5)
            ->get();

        foreach ($availableWithActiveBooking as $room) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Phòng trống nhưng có booking active',
                'message' => 'Phòng ' . $room->room_number . ' · tầng ' . $room->floor_number . ' · dữ liệu phòng/booking đang lệch',
                'url' => route('admin.rooms.index'),
                'action' => 'Xem phòng',
            ];
        }

        $occupiedWithoutBooking = Room::query()
            ->with('category')
            ->where('status', 'occupied')
            ->whereDoesntHave('bookingRooms.booking', function ($query) use ($now) {
                $query->where('status', 'checked_in')
                    ->where('check_in_at', '<=', $now)
                    ->where('check_out_at', '>', $now);
            })
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->limit(5)
            ->get();

        foreach ($occupiedWithoutBooking as $room) {
            $warnings[] = [
                'level' => 'warning',
                'title' => 'Phòng occupied nhưng không thấy booking đang ở',
                'message' => 'Phòng ' . $room->room_number . ' · tầng ' . $room->floor_number . ' · kiểm tra lại vòng đời phòng',
                'url' => route('admin.rooms.index'),
                'action' => 'Xem phòng',
            ];
        }

        $pendingServiceAfterCheckout = BookingServiceItem::query()
            ->with(['booking.customer'])
            ->where('billing_status', 'pending')
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['checked_out', 'completed']);
            })
            ->latest()
            ->limit(5)
            ->get();

        foreach ($pendingServiceAfterCheckout as $item) {
            $booking = $item->booking;

            $warnings[] = [
                'level' => 'warning',
                'title' => 'Dịch vụ chưa chốt sau check-out',
                'message' => ($booking->booking_code ?? 'N/A') . ' · ' . ($item->name ?? 'Dịch vụ') . ' x' . ($item->quantity ?? 1) . ' vẫn pending',
                'url' => $booking && $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Kiểm tra',
            ];
        }

        return collect($warnings)
            ->sortBy(function ($warning) {
                return ['danger' => 0, 'warning' => 1, 'info' => 2][$warning['level']] ?? 9;
            })
            ->take(12)
            ->values()
            ->all();
    }

    private function buildRevenueChart(Carbon $now): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $labels[] = $day->format('d/m');
            $values[] = $this->paidRevenueForPeriod($day->copy()->startOfDay(), $day->copy()->endOfDay());
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'max' => max(max($values), 1),
        ];
    }

    private function buildRoomStatusChart($roomsByStatus, array $labels, int $totalRooms): array
    {
        return collect($labels)
            ->map(function ($label, $status) use ($roomsByStatus, $totalRooms) {
                $count = (int) ($roomsByStatus[$status] ?? 0);

                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percent' => $totalRooms > 0 ? round(($count / $totalRooms) * 100, 1) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function buildBookingStatusChart(array $labels): array
    {
        $counts = $this->bookingQuery()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereIn('status', array_keys($labels))
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = max((int) $counts->sum(), 1);

        return collect($labels)
            ->map(function ($label, $status) use ($counts, $total) {
                $count = (int) ($counts[$status] ?? 0);

                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percent' => round(($count / $total) * 100, 1),
                ];
            })
            ->values()
            ->all();
    }

    private function buildCategoryRevenueChart(Carbon $from, Carbon $to): array
    {
        $rows = BookingPayment::query()
            ->join('bookings', 'bookings.id', '=', 'booking_payments.booking_id')
            ->join('room_categories', 'room_categories.id', '=', 'bookings.room_category_id')
            ->select('room_categories.name', DB::raw('COALESCE(SUM(booking_payments.amount), 0) as total'))
            ->where('booking_payments.status', 'success')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('booking_payments.paid_at', [$from, $to])
                    ->orWhere(function ($subQuery) use ($from, $to) {
                        $subQuery->whereNull('booking_payments.paid_at')
                            ->whereBetween('booking_payments.created_at', [$from, $to]);
                    });
            })
            ->groupBy('room_categories.id', 'room_categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        if ($rows->isEmpty()) {
            $rows = RoomCategory::query()
                ->select('name', DB::raw('0 as total'))
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(6)
                ->get();
        }

        $max = max((float) $rows->max('total'), 1);

        return [
            'rows' => $rows,
            'max' => $max,
        ];
    }

    private function canOpenBooking($user): bool
    {
        return $user && in_array($user->role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist'], true);
    }


    private function customerName(Booking $booking): string
    {
        $customer = $booking->customer;

        if (!$customer) {
            return 'Chưa có khách';
        }

        $name = trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? ''));

        return $name !== '' ? $name : ($customer->phone ?? 'Khách hàng');
    }

    private function roomNumbers(Booking $booking): string
    {
        $rooms = $booking->bookingRooms
            ->pluck('room.room_number')
            ->filter()
            ->implode(', ');

        return $rooms !== '' ? $rooms : 'chưa gán';
    }

    private function formatDateTime($value): string
    {
        if (!$value) {
            return 'N/A';
        }

        return Carbon::parse($value, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i');
    }

    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours <= 0) {
            return $mins . ' phút';
        }

        return $hours . ' giờ' . ($mins > 0 ? ' ' . $mins . ' phút' : '');
    }

    private function formatCompactMoney(float $amount): string
    {
        if ($amount >= 1000000000) {
            return round($amount / 1000000000, 1) . ' tỷ';
        }

        if ($amount >= 1000000) {
            return round($amount / 1000000, 1) . ' triệu';
        }

        return number_format($amount, 0, ',', '.') . 'đ';
    }

    private function roomStatusLabels(): array
    {
        return [
            'available' => 'Trống',
            'reserved' => 'Đã đặt',
            'occupied' => 'Đang ở',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Cần dọn',
            'maintenance' => 'Bảo trì',
        ];
    }

    private function bookingStatusLabels(): array
    {
        return [
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đang ở',
            'inspection_requested' => 'Chờ kiểm tra',
            'checked_out' => 'Đã check-out',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];
    }

    private function paymentStatusLabels(): array
    {
        return [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
        ];
    }
}
