<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query()->whereIn('type', Service::serviceCatalogTypes());

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($serviceQuery) use ($keyword) {
                $serviceQuery->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhere('unit', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('type') && in_array($request->type, Service::serviceCatalogTypes(), true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('service_group')) {
            $query->where('service_group', $request->service_group);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query
            ->orderByRaw("FIELD(type, 'service', 'minibar_order', 'minibar')")
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $typeLabels = Service::serviceTypeLabels();
        $groupLabels = Service::groupLabels();
        $billingRuleLabels = Service::billingRuleLabels();

        return view(
            'admin.pages.services.index',
            compact('services', 'typeLabels', 'groupLabels', 'billingRuleLabels')
        );
    }

    public function create()
    {
        $typeLabels = Service::serviceTypeLabels();
        $groupLabels = Service::groupLabels();
        $billingRuleLabels = Service::billingRuleLabels();

        return view('admin.pages.services.create', compact('typeLabels', 'groupLabels', 'billingRuleLabels'));
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
        $this->guardServiceCatalog($service);

        return view('admin.pages.services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        $this->guardServiceCatalog($service);

        $typeLabels = Service::serviceTypeLabels();
        $groupLabels = Service::groupLabels();
        $billingRuleLabels = Service::billingRuleLabels();

        return view(
            'admin.pages.services.edit',
            compact('service', 'typeLabels', 'groupLabels', 'billingRuleLabels')
        );
    }

    public function update(Request $request, Service $service)
    {
        $this->guardServiceCatalog($service);
        $service->update($this->validatedServiceData($request));

        return redirect()
            ->route('services.index')
            ->with('success', 'Cập nhật dịch vụ thành công');
    }

    public function destroy(Service $service)
    {
        $this->guardServiceCatalog($service);
        $this->deleteOrDeactivate($service);

        return redirect()
            ->route('services.index')
            ->with('success', $service->exists
                ? 'Dịch vụ đã phát sinh dữ liệu nên hệ thống chỉ chuyển sang Ngừng hoạt động để bảo toàn lịch sử.'
                : 'Xóa dịch vụ thành công');
    }

    private function validatedServiceData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'type' => ['required', Rule::in(Service::serviceCatalogTypes())],
            'service_group' => ['required', Rule::in(array_keys(Service::groupLabels()))],
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'billing_rule' => ['required', Rule::in(array_keys(Service::billingRuleLabels()))],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên dịch vụ.',
            'type.required' => 'Vui lòng chọn loại dịch vụ.',
            'service_group.required' => 'Vui lòng chọn nhóm dịch vụ.',
            'price.required' => 'Vui lòng nhập giá dịch vụ.',
            'price.min' => 'Giá dịch vụ không được âm.',
            'unit.required' => 'Vui lòng nhập đơn vị tính.',
            'billing_rule.required' => 'Vui lòng chọn cách tính dịch vụ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);
    }

    private function guardServiceCatalog(Service $service): void
    {
        abort_unless($service->isServiceCatalog(), 404);
    }

    private function deleteOrDeactivate(Service $service): void
    {
        $hasHistory = DB::table('booking_service_items')->where('service_id', $service->id)->exists()
            || DB::table('room_inspection_items')->where('service_id', $service->id)->exists()
            || DB::table('promotion_service_offers')->where('service_id', $service->id)->exists();

        if ($hasHistory) {
            $service->update(['status' => 'inactive']);
            return;
        }

        $service->delete();
    }
}
