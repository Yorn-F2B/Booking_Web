<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = Staff::with('user')->latest()->paginate(10);

        return view('admin.pages.staffs.index', compact('staffs'));
    }

    public function create()
    {
        return view('admin.pages.staffs.create');
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
            'position' => 'nullable|in:Quản lý,Trưởng lễ tân,Lễ tân,Trưởng buồng phòng,Buồng phòng',
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'work_status' => 'nullable|in:working,resigned,temporary_leave',
        ]);

        $role = match ($data['position'] ?? null) {
            'Quản lý' => 'manager',
            'Trưởng lễ tân' => 'receptionist_lead',
            'Lễ tân' => 'receptionist',
            'Trưởng buồng phòng' => 'housekeeping_supervisor',
            'Buồng phòng' => 'housekeeping',
            default => 'customer',
        };

        $user = User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'status' => 'active',
        ]);

        $avatarPath = null;

        if ($request->hasFile('avatar')) {

            $avatarPath = $request->file('avatar')
                ->store('staffs', 'public');
        }

        Staff::create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'cccd' => $data['cccd'] ?? null,
            'birthday' => $data['birthday'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'position' => $data['position'] ?? null,
            'salary' => $data['salary'] ?? 0,
            'hire_date' => $data['hire_date'] ?? null,
            'work_status' => $data['work_status'] ?? 'working',
            'avatar' => $avatarPath,
        ]);

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

        return view('admin.pages.staffs.edit', compact('staff'));
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

            'position' => 'nullable|in:Quản lý,Trưởng lễ tân,Lễ tân,Trưởng buồng phòng,Buồng phòng',

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

        $avatarPath = $staff->avatar;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('staffs', 'public');
        }

        $staff->update([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'cccd' => $data['cccd'] ?? null,
            'birthday' => $data['birthday'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'position' => $data['position'] ?? null,
            'salary' => $data['salary'] ?? 0,
            'hire_date' => $data['hire_date'] ?? null,
            'work_status' => $data['work_status'] ?? 'working',
            'avatar' => $avatarPath,
        ]);

        if ($staff->user) {
            $role = match ($data['position'] ?? null) {
                'Quản lý' => 'manager',
                'Trưởng lễ tân', 'Lễ tân' => 'receptionist',
                'Trưởng buồng phòng', 'Buồng phòng' => 'housekeeping',
                default => 'customer',
            };

            $updateUserData = [
                'name' => $data['full_name'],
                'email' => $data['email'],
                'role' => $role,
            ];

            if (!empty($data['password'])) {
                $updateUserData['password'] = Hash::make($data['password']);
            }

            $staff->user->update($updateUserData);
        }

        return redirect()->route('staffs.index')
            ->with('success', 'Cập nhật nhân viên thành công');
    }
    public function destroy(Staff $staff)
    {
        if ($staff->user) {
            $staff->user->delete();
        }

        $staff->delete();

        return redirect()->route('staffs.index')
            ->with('success', 'Xóa nhân viên thành công');
    }
}