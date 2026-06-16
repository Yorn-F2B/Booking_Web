<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->paginate(10);

        return view(
            'admin.pages.services.index',
            compact('services')
        );
    }

    public function create()
    {
        return view('admin.pages.services.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([

            'name' => 'required|max:100',

            'type' => 'required|in:service,minibar,damage_fee,violation_fee',
            
            'price' => 'required|numeric|min:0',

            'unit' => 'required|max:50',

            'description' => 'nullable|string',

            'status' => 'required|in:active,inactive',

        ]);

        Service::create($data);

        return redirect()
            ->route('services.index')
            ->with('success', 'Thêm dịch vụ thành công');
    }

    public function show(Service $service)
    {
        return view(
            'admin.pages.services.show',
            compact('service')
        );
    }

    public function edit(Service $service)
    {
        return view(
            'admin.pages.services.edit',
            compact('service')
        );
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([

            'name' => 'required|max:100',

            'type' => 'required|in:service,minibar,damage_fee,violation_fee',

            'price' => 'required|numeric|min:0',

            'unit' => 'required|max:50',

            'description' => 'nullable|string',

            'status' => 'required|in:active,inactive',

        ]);

        $service->update($data);

        return redirect()
            ->route('services.index')
            ->with('success', 'Cập nhật dịch vụ thành công');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()
            ->route('services.index')
            ->with('success', 'Xóa dịch vụ thành công');
    }
}