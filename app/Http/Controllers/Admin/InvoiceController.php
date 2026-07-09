<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\BookingServiceItem;
use App\Models\BookingRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['date', 'booking_code', 'customer_name', 'payment_status']);
        
        $invoices = Invoice::with(['booking', 'creator'])
            ->filter($filters)
            ->latest()
            ->paginate(20);

        return view('admin.pages.invoices.index', compact('invoices', 'filters'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['booking', 'booking.customer', 'booking.bookingRooms.room', 'creator']);
        
        return view('admin.pages.invoices.show', compact('invoice'));
    }

    public function createFromBooking(Booking $booking)
    {
        // Kiểm tra xem booking đã check-out chưa
        if (!in_array($booking->status, ['checked_out', 'completed'])) {
            return back()->with('error', 'Chỉ có thể xuất hóa đơn cho booking đã trả phòng.');
        }

        // Kiểm tra xem đã có hóa đơn chưa
        $existingInvoice = Invoice::where('booking_id', $booking->id)->first();
        if ($existingInvoice) {
            return redirect()->route('admin.invoices.show', $existingInvoice)
                ->with('info', 'Hóa đơn cho booking này đã được tạo.');
        }

        $booking->load(['customer', 'bookingRooms.room', 'serviceItems', 'payments']);

        // Tính toán các khoản phí
        $roomCharge = $this->calculateRoomCharge($booking);
        $serviceCharge = $this->calculateServiceCharge($booking);
        $minibarCharge = $this->calculateMinibarCharge($booking);
        $extraCharge = $this->calculateExtraCharge($booking);
        $damageFee = $this->calculateDamageFee($booking);
        $depositAmount = $this->calculateDepositAmount($booking);

        $totalAmount = $roomCharge + $serviceCharge + $minibarCharge + $extraCharge + $damageFee;
        $remainingAmount = $totalAmount - $depositAmount;

        // Tạo mã hóa đơn
        $invoiceCode = 'INV-' . date('Ymd') . '-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);

        // Lấy danh sách số phòng
        $roomNumbers = $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ');

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'invoice_code' => $invoiceCode,
                'booking_id' => $booking->id,
                'customer_name' => trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')),
                'room_numbers' => $roomNumbers,
                'check_in_date' => $booking->check_in_date,
                'check_out_date' => $booking->check_out_date,
                'actual_check_in' => $booking->actual_check_in,
                'actual_check_out' => $booking->actual_check_out,
                'room_charge' => $roomCharge,
                'service_charge' => $serviceCharge,
                'minibar_charge' => $minibarCharge,
                'extra_charge' => $extraCharge,
                'damage_fee' => $damageFee,
                'deposit_amount' => $depositAmount,
                'remaining_amount' => $remainingAmount,
                'total_amount' => $totalAmount,
                'payment_status' => $remainingAmount <= 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'unpaid'),
                'issued_at' => now(),
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.invoices.show', $invoice)
                ->with('success', 'Hóa đơn đã được tạo thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi khi tạo hóa đơn: ' . $e->getMessage());
        }
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['booking', 'booking.customer', 'booking.bookingRooms.room', 'creator']);
        
        // Cập nhật thời gian in
        $invoice->update(['printed_at' => now()]);

        return view('admin.pages.invoices.print', compact('invoice'));
    }

    private function calculateRoomCharge(Booking $booking): float
    {
        return $booking->bookingRooms->sum(function ($bookingRoom) {
            return (float) ($bookingRoom->price_at_booking ?? 0);
        });
    }

    private function calculateServiceCharge(Booking $booking): float
    {
        return $booking->serviceItems->sum(function ($serviceItem) {
            if ($serviceItem->service && $serviceItem->service->type === 'extra_guest_fee') {
                return 0; // Phí khách thừa tính vào extra_charge
            }
            return (float) ($serviceItem->total_price ?? 0);
        });
    }

    private function calculateMinibarCharge(Booking $booking): float
    {
        // Giả sử có service loại minibar, nếu không có thì trả về 0
        return $booking->serviceItems->sum(function ($serviceItem) {
            if ($serviceItem->service && $serviceItem->service->type === 'minibar') {
                return (float) ($serviceItem->total_price ?? 0);
            }
            return 0;
        });
    }

    private function calculateExtraCharge(Booking $booking): float
    {
        // Phụ thu (khách thừa, check-in sớm, check-out muộn, v.v.)
        $extraCharge = 0;

        // Phí khách thừa
        $extraCharge += $booking->serviceItems->sum(function ($serviceItem) {
            if ($serviceItem->service && $serviceItem->service->type === 'extra_guest_fee') {
                return (float) ($serviceItem->total_price ?? 0);
            }
            return 0;
        });

        // Phí đến trễ (nếu có)
        if ($booking->late_arrival_fee) {
            $extraCharge += (float) $booking->late_arrival_fee;
        }

        return $extraCharge;
    }

    private function calculateDamageFee(Booking $booking): float
    {
        // Phí hư hại từ room inspection
        $damageFee = 0;
        
        foreach ($booking->roomInspections as $inspection) {
            if ($inspection->status === 'damage_detected') {
                $damageFee += $inspection->items->sum(function ($item) {
                    return (float) ($item->damage_fee ?? 0);
                });
            }
        }

        return $damageFee;
    }

    private function calculateDepositAmount(Booking $booking): float
    {
        // Tổng số tiền đã thanh toán
        return (float) $booking->payments->where('status', 'success')->sum('amount');
    }
}