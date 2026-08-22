<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Booking;
use App\Services\BookingIdentityGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with(['bookings' => function ($query) {
            $query->whereNotIn('status', ['cancelled'])
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
                $query->whereNotIn('status', ['cancelled'])
                    ->with([
                        'roomCategory',
                        'bookingRooms.room',
                        'payments' => fn ($paymentQuery) => $paymentQuery->where('status', 'success'),
                    ])
                    ->latest();
            }
        ]);

        $totalBookings = $customer->bookings->count();
        $totalSpent = $customer->bookings->sum(function ($booking) {
            return (float) ($booking->final_total ?? $booking->estimated_total ?? 0);
        });
        $totalPaid = $customer->bookings->sum(function ($booking) {
            return (float) $booking->payments->sum('amount');
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->ignore($customer->id)],
            'email' => [
                'nullable', 'email', 'max:150',
                Rule::unique('customers', 'email')->ignore($customer->id),
                Rule::unique('users', 'email')->ignore($customer->user_id),
            ],
            'cccd' => ['nullable', 'string', 'max:20', Rule::unique('customers', 'cccd')->ignore($customer->id)],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'blacklist'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($customer, $data): void {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            app(BookingIdentityGuard::class)->assertIdentityUpdateAllowed(
                $customer,
                $data['cccd'] ?? null,
                $data['birthday'] ?? null
            );
            $customer->update($data);

            $user = $customer->user()->lockForUpdate()->first();
            if ($user) {
                $newEmail = $data['email'] ?: $user->email;
                if ($newEmail && strcasecmp((string) $user->email, (string) $newEmail) !== 0) {
                    $user->email_verified_at = null;
                }

                $user->name = trim($data['last_name'] . ' ' . $data['first_name']);
                if ($newEmail) {
                    $user->email = $newEmail;
                }
                // Blacklist khách đồng thời khóa đăng nhập; mở blacklist thì kích hoạt lại
                // tài khoản khách (không đụng tới tài khoản nhân viên nếu dữ liệu bị gắn nhầm).
                if ($user->role === 'customer') {
                    $user->status = $data['status'] === 'blacklist' ? 'banned' : 'active';
                }
                $user->save();
            }
        });

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Cập nhật thông tin khách hàng thành công');
    }

    public function destroy(Customer $customer)
    {
        // Hồ sơ gắn với tài khoản đăng nhập không được xóa rời rạc: làm vậy sẽ để lại
        // user đang hoạt động nhưng không còn customer, trong khi user_id/CCCD/phone vẫn
        // bị unique giữ lại. Với khách có tài khoản, thao tác "Xóa" được chuyển thành
        // ngừng phục vụ + khóa đăng nhập để dữ liệu lịch sử và định danh luôn nhất quán.
        if ($customer->user_id) {
            DB::transaction(function () use ($customer): void {
                $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
                $customer->status = 'blacklist';
                $customer->save();

                $user = $customer->user()->lockForUpdate()->first();
                if ($user && $user->role === 'customer') {
                    $user->status = 'banned';
                    $user->save();
                }
            });

            return redirect()
                ->route('admin.customers.index')
                ->with('success', 'Khách có tài khoản đăng nhập nên đã được chuyển sang blacklist và khóa đăng nhập thay vì xóa hồ sơ.');
        }

        // Khách vãng lai đã từng phát sinh booking là dữ liệu lịch sử nghiệp vụ, không xóa.
        if ($customer->bookings()->withTrashed()->exists()) {
            return back()->with(
                'error',
                'Khách hàng đã có lịch sử booking nên không thể xóa. Nếu cần ngừng phục vụ, hãy chuyển trạng thái sang blacklist.'
            );
        }

        // Không có tài khoản và chưa có lịch sử: xóa vật lý để giải phóng CCCD/điện thoại
        // cho lần nhập đúng sau này, tránh soft-delete vẫn giữ khóa unique.
        $customer->forceDelete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Xóa khách hàng thành công');
    }
}
