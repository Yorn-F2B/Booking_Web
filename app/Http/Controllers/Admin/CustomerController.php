<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('cccd', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('status', $request->get('status'));
        }

        $customers = $query->latest()->paginate(15)->withQueryString();

        return view('admin.pages.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.pages.customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers',
            'cccd' => 'nullable|string|max:20|unique:customers',
            'email' => 'nullable|email|max:255',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'required|in:active,blacklist',
        ]);

        Customer::create($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Thêm khách hàng mới thành công.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['bookings' => function($q) {
            $q->latest()->take(10);
        }]);
        
        return view('admin.pages.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('admin.pages.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'cccd' => 'nullable|string|max:20|unique:customers,cccd,' . $customer->id,
            'email' => 'nullable|email|max:255',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'required|in:active,blacklist',
        ]);

        $customer->update($validated);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Cập nhật thông tin khách hàng thành công.');
    }

    public function destroy(Customer $customer)
    {
        // Kiểm tra xem khách hàng có đơn đặt phòng nào không trước khi xóa (tùy chọn)
        if ($customer->bookings()->count() > 0) {
            return redirect()->route('admin.customers.index')
                ->with('error', 'Không thể xóa khách hàng đã có đơn đặt phòng.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Đã xóa khách hàng thành công.');
    }
}
