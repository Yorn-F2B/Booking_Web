<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use PDF;

class InvoiceController extends Controller
{
    public function generate(Booking $booking)
    {
        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
        ]);

        $pdf = PDF::loadView('admin.pages.invoices.pdf', compact('booking'));

        return $pdf->download('invoice_' . $booking->booking_code . '.pdf');
    }

    public function view(Booking $booking)
    {
        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
        ]);

        return view('admin.pages.invoices.pdf', compact('booking'));
    }
}
