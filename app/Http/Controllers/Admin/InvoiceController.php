<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['date', 'booking_code', 'customer_name', 'payment_status']);
        
        $invoices = Invoice::with(['booking', 'booking.customer', 'customer', 'issuer'])
            ->filter($filters)
            ->latest()
            ->paginate(20);

        return view('admin.pages.invoices.index', compact('invoices', 'filters'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'booking',
            'booking.customer',
            'booking.bookingRooms.room',
            'booking.payments',
            'customer',
            'issuer',
        ]);
        
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

        $booking->load(['customer', 'bookingRooms.room', 'roomInspections.items', 'payments']);

        $roomCharge = $booking->calculateRoomCharge();
        $serviceCharge = $booking->calculateServiceCharge();
        $inspectionCharge = $booking->calculateApprovedInspectionCharge();
        $discountAmount = $booking->calculatePromotionDiscount();
        $finalTotal = $booking->calculateFinalTotal();
        $totalPaid = $booking->calculateTotalPaid();
        $remainingAmount = max(0, $finalTotal - $totalPaid);
        $overpaymentAmount = max(0, $totalPaid - $finalTotal);

        $invoiceCode = 'INV' . now()->format('Ymd') . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'invoice_code' => $invoiceCode,
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'room_total' => $roomCharge,
                'service_total' => $serviceCharge,
                'inspection_total' => $inspectionCharge,
                'promotion_discount' => $discountAmount,
                'final_total' => $finalTotal,
                'total_paid' => $totalPaid,
                'remaining_amount' => $remainingAmount,
                'overpayment_amount' => $overpaymentAmount,
                'status' => 'issued',
                'issued_at' => now(),
                'issued_by' => Auth::id(),
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
        $invoice->load([
            'booking',
            'booking.customer',
            'booking.bookingRooms.room',
            'booking.payments',
            'customer',
            'issuer',
        ]);
        
        if (Schema::hasColumn('invoices', 'printed_at')) {
            $invoice->update(['printed_at' => now()]);
        }

        return view('admin.pages.invoices.print', compact('invoice'));
    }
}
