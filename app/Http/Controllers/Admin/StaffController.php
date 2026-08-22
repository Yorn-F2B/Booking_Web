<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = Staff::with('user')->latest()->paginate(10);

        return view('admin.pages.staffs.index', compact('staffs'));
    }

    public function create()
    {
        $managerExists = User::query()->where('role', 'manager')->exists();

        return view('admin.pages.staffs.create', compact('managerExists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',

            'full_name' => 'required|max:100',
            'phone' => 'nullable|max:20|unique:staffs,phone',
            'cccd' => 'nullable|max:20|unique:staffs,cccd',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable',
            'position' => 'required|in:Quản lý,Trưởng lễ tân,Lễ tân,Trưởng buồng phòng,Buồng phòng',
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'work_status' => 'nullable|in:working,resigned,temporary_leave',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $role = $this->roleForPosition($data['position']);
        $this->guardSingletonLeadershipRole($role);
        $workStatus = $data['work_status'] ?? 'working';
        $avatarPath = null;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('staffs', 'public');
        }

        try {
            DB::transaction(function () use ($data, $role, $workStatus, $avatarPath): void {
                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => mb_strtolower(trim($data['email'])),
                    'password' => Hash::make($data['password']),
                    'role' => $role,
                    'status' => $workStatus === 'working' ? 'active' : 'inactive',
                ]);

                Staff::create([
                    'user_id' => $user->id,
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'] ?? null,
                    'cccd' => $data['cccd'] ?? null,
                    'birthday' => $data['birthday'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'address' => $data['address'] ?? null,
                    'position' => $data['position'],
                    'salary' => $data['salary'] ?? 0,
                    'hire_date' => $data['hire_date'] ?? null,
                    'work_status' => $workStatus,
                    'avatar' => $avatarPath,
                ]);
            });
        } catch (Throwable $e) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            report($e);
            return back()->withInput()->with('error', 'Không thể tạo nhân viên. Vui lòng kiểm tra dữ liệu và thử lại.');
        }

        return redirect()->route('staffs.index')
            ->with('success', 'Thêm nhân viên thành công');
    }

    public function show(Staff $staff)
    {
        $staff->load('user');

        return view('admin.pages.staffs.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        $staff->load('user');
        $managerExists = User::query()
            ->where('role', 'manager')
            ->where('id', '!=', (int) $staff->user_id)
            ->exists();

        return view('admin.pages.staffs.edit', compact('staff', 'managerExists'));
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([

            'email' => [
                'required',
                'email',
                'unique:users,email,' . $staff->user_id,
            ],

            'password' => [
                'nullable',
                'min:6',
            ],

            'full_name' => [
                'required',
                'max:100',
            ],

            'phone' => [
                'nullable',
                'digits_between:10,11',
                'unique:staffs,phone,' . $staff->id,
            ],

            'cccd' => [
                'nullable',
                'digits:12',
                'unique:staffs,cccd,' . $staff->id,
            ],

            'birthday' => [
                'nullable',
                'date',
            ],

            'gender' => [
                'nullable',
                'in:male,female,other',
            ],

            'address' => [
                'nullable',
            ],

            'position' => 'required|in:Quản lý,Trưởng lễ tân,Lễ tân,Trưởng buồng phòng,Buồng phòng',

            'salary' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'hire_date' => [
                'nullable',
                'date',
            ],

            'work_status' => [
                'nullable',
                'in:working,resigned,temporary_leave',
            ],

            'avatar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

        ], [

            'email.required' => 'Email không được để trống',
            'email.email' => 'Email không đúng định dạng',
            'email.unique' => 'Email đã tồn tại',

            'password.min' => 'Mật khẩu tối thiểu 6 ký tự',

            'full_name.required' => 'Họ tên không được để trống',

            'phone.digits_between' => 'Số điện thoại phải có 10 đến 11 số',
            'phone.unique' => 'Số điện thoại đã tồn tại',

            'cccd.digits' => 'CCCD phải có đúng 12 số',
            'cccd.unique' => 'CCCD đã tồn tại',

            'position.in' => 'Chức vụ không hợp lệ',

            'avatar.image' => 'File tải lên phải là hình ảnh',
            'avatar.mimes' => 'Ảnh phải có định dạng jpg, jpeg, png hoặc webp',
            'avatar.max' => 'Ảnh không được vượt quá 2MB',
        ]);

        $oldAvatarPath = $staff->avatar;
        $newAvatarPath = null;
        $avatarPath = $oldAvatarPath;

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('staffs', 'public');
            $avatarPath = $newAvatarPath;
        }

        $role = $this->roleForPosition($data['position']);
        $this->guardSingletonLeadershipRole($role, (int) $staff->user_id);
        $workStatus = $data['work_status'] ?? 'working';

        try {
            DB::transaction(function () use ($staff, $data, $avatarPath, $role, $workStatus): void {
                $staff->update([
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'] ?? null,
                    'cccd' => $data['cccd'] ?? null,
                    'birthday' => $data['birthday'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'address' => $data['address'] ?? null,
                    'position' => $data['position'],
                    'salary' => $data['salary'] ?? 0,
                    'hire_date' => $data['hire_date'] ?? null,
                    'work_status' => $workStatus,
                    'avatar' => $avatarPath,
                ]);

                $user = User::whereKey($staff->user_id)->lockForUpdate()->firstOrFail();
                $oldEmail = (string) $user->email;
                $newEmail = mb_strtolower(trim($data['email']));

                $user->name = $data['full_name'];
                $user->email = $newEmail;
                $user->role = $role;
                $user->status = $workStatus === 'working' ? 'active' : 'inactive';
                if (strcasecmp($oldEmail, $newEmail) !== 0) {
                    $user->email_verified_at = null;
                }
                if (!empty($data['password'])) {
                    $user->password = Hash::make($data['password']);
                }
                $user->save();
            });
        } catch (Throwable $e) {
            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            report($e);
            return back()->withInput()->with('error', 'Không thể cập nhật nhân viên. Vui lòng thử lại.');
        }

        if ($newAvatarPath && $oldAvatarPath && $oldAvatarPath !== $newAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return redirect()->route('staffs.index')
            ->with('success', 'Cập nhật nhân viên thành công');
    }
    public function destroy(Staff $staff)
    {
        $avatarPath = $staff->avatar;

        DB::transaction(function () use ($staff): void {
            $user = User::whereKey($staff->user_id)->lockForUpdate()->first();
            if ($user) {
                // User dùng soft delete để giữ nguyên khóa ngoại/lịch sử phân công.
                $user->delete();
            }
            $staff->delete();
        });

        if ($avatarPath) {
            Storage::disk('public')->delete($avatarPath);
        }

        return redirect()->route('staffs.index')
            ->with('success', 'Xóa nhân viên thành công');
    }

    private function guardSingletonLeadershipRole(string $role, ?int $exceptUserId = null): void
    {
        if (!in_array($role, ['super_admin', 'manager'], true)) {
            return;
        }

        $exists = User::query()
            ->where('role', $role)
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->exists();

        if ($exists) {
            $label = $role === 'super_admin' ? 'Super Admin' : 'Quản lý';
            throw ValidationException::withMessages([
                'position' => "Khách sạn chỉ được có 1 {$label}. Hãy cập nhật tài khoản hiện có thay vì tạo thêm.",
            ]);
        }
    }

    private function roleForPosition(string $position): string
    {
        return match ($position) {
            'Quản lý' => 'manager',
            'Trưởng lễ tân' => 'receptionist_lead',
            'Lễ tân' => 'receptionist',
            'Trưởng buồng phòng' => 'housekeeping_supervisor',
            'Buồng phòng' => 'housekeeping',
            default => throw new \InvalidArgumentException('Chức vụ nhân viên không hợp lệ.'),
        };
    }
}