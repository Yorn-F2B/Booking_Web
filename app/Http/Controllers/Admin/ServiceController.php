<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($serviceQuery) use ($keyword) {
                $serviceQuery->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhere('unit', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('service_group')) {
            $query->where('service_group', $request->service_group);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query
            ->orderByRaw("FIELD(type, 'service', 'minibar', 'occupancy_fee', 'policy_violation_fee', 'damage_fee')")
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $typeLabels = Service::typeLabels();
        $groupLabels = Service::groupLabels();

        return view(
            'admin.pages.services.index',
            compact('services', 'typeLabels', 'groupLabels')
        );
    }

    public function create()
    {
        $typeLabels = Service::typeLabels();
        $groupLabels = Service::groupLabels();

        return view('admin.pages.services.create', compact('typeLabels', 'groupLabels'));
    }

    public function store(Request $request)
    {
        Service::create($this->validatedServiceData($request));

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
        $typeLabels = Service::typeLabels();
        $groupLabels = Service::groupLabels();

        return view(
            'admin.pages.services.edit',
            compact('service', 'typeLabels', 'groupLabels')
        );
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validatedServiceData($request));

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

    private function validatedServiceData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'type' => ['required', Rule::in(array_keys(Service::typeLabels()))],
            'service_group' => ['required', Rule::in(array_keys(Service::groupLabels()))],
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên dịch vụ.',
            'type.required' => 'Vui lòng chọn loại dịch vụ.',
            'service_group.required' => 'Vui lòng chọn nhóm dịch vụ.',
            'price.required' => 'Vui lòng nhập giá dịch vụ.',
            'price.min' => 'Giá dịch vụ không được âm.',
            'unit.required' => 'Vui lòng nhập đơn vị tính.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);
    }
}
