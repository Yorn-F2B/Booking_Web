<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with(['bookings' => function ($query) {
            $query->whereNotIn('status', ['cancelled', 'no_show'])
                ->latest();
        }]);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $customers->where(function ($query) use ($keyword) {
                $query->where('first_name', 'like', '%' . $keyword . '%')
                    ->orWhere('last_name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('cccd', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $customers->where('status', $request->status);
        }

        $customers = $customers->latest()->paginate(20);

        return view('admin.pages.customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'bookings' => function ($query) {
                $query->whereNotIn('status', ['cancelled', 'no_show'])
                    ->with(['roomCategory', 'bookingRooms.room'])
                    ->latest();
            }
        ]);

        $totalBookings = $customer->bookings->count();
        $totalSpent = $customer->bookings->sum('estimated_total');
        $totalPaid = $customer->bookings->sum(function ($booking) {
            return $booking->payments->sum('amount');
        });

        return view('admin.pages.customers.show', compact(
            'customer',
            'totalBookings',
            'totalSpent',
            'totalPaid'
        ));
    }

    public function edit(Customer $customer)
    {
        return view('admin.pages.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:150|unique:customers,email,' . $customer->id,
            'cccd' => 'nullable|string|max:20|unique:customers,cccd,' . $customer->id,
            'birthday' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:255',
            'status' => 'required|in:active,blacklist',
            'note' => 'nullable|string|max:1000',
        ]);

        $customer->update($data);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Cập nhật thông tin khách hàng thành công');
    }

    public function destroy(Customer $customer)
    {
        // Check if customer has active bookings
        $activeBookings = $customer->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        if ($activeBookings > 0) {
            return back()->with('error', 'Không thể xóa khách hàng có booking đang hoạt động.');
        }

        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Xóa khách hàng thành công');
    }
}
