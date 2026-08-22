<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SurchargeController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query()->whereIn('type', Service::surchargeCatalogTypes());

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);
            $query->where(function ($feeQuery) use ($keyword) {
                $feeQuery->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhere('unit', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('type') && in_array($request->type, Service::surchargeCatalogTypes(), true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $surcharges = $query
            ->orderByRaw("FIELD(type, 'damage_fee', 'occupancy_fee', 'extra_guest_fee', 'early_checkin_fee', 'late_checkout_fee', 'extension_fee', 'policy_violation_fee', 'manual_fee')")
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $typeLabels = Service::surchargeTypeLabels();

        return view('admin.pages.surcharges.index', compact('surcharges', 'typeLabels'));
    }

    public function create()
    {
        $typeLabels = Service::surchargeTypeLabels();

        return view('admin.pages.surcharges.create', compact('typeLabels'));
    }

    public function store(Request $request)
    {
        Service::create($this->validatedSurchargeData($request));

        return redirect()
            ->route('surcharges.index')
            ->with('success', 'Thêm phụ thu / phí phát sinh thành công');
    }

    public function show(Service $surcharge)
    {
        $this->guardSurcharge($surcharge);

        return view('admin.pages.surcharges.show', compact('surcharge'));
    }

    public function edit(Service $surcharge)
    {
        $this->guardSurcharge($surcharge);
        $typeLabels = Service::surchargeTypeLabels();

        return view('admin.pages.surcharges.edit', compact('surcharge', 'typeLabels'));
    }

    public function update(Request $request, Service $surcharge)
    {
        $this->guardSurcharge($surcharge);
        $surcharge->update($this->validatedSurchargeData($request));

        return redirect()
            ->route('surcharges.index')
            ->with('success', 'Cập nhật phụ thu / phí phát sinh thành công');
    }

    public function destroy(Service $surcharge)
    {
        $this->guardSurcharge($surcharge);

        $hasHistory = DB::table('booking_service_items')->where('service_id', $surcharge->id)->exists()
            || DB::table('room_inspection_items')->where('service_id', $surcharge->id)->exists()
            || DB::table('promotion_service_offers')->where('service_id', $surcharge->id)->exists();

        if ($hasHistory) {
            $surcharge->update(['status' => 'inactive']);

            return redirect()
                ->route('surcharges.index')
                ->with('success', 'Khoản phí đã phát sinh dữ liệu nên hệ thống chỉ chuyển sang Ngừng hoạt động để bảo toàn lịch sử.');
        }

        $surcharge->delete();

        return redirect()
            ->route('surcharges.index')
            ->with('success', 'Xóa phụ thu / phí phát sinh thành công');
    }

    private function validatedSurchargeData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => ['required', Rule::in(Service::surchargeCatalogTypes())],
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ], [
            'name.required' => 'Vui lòng nhập tên khoản phụ thu / phí phát sinh.',
            'type.required' => 'Vui lòng chọn loại phí.',
            'price.required' => 'Vui lòng nhập mức phí mặc định.',
            'price.min' => 'Mức phí không được âm.',
            'unit.required' => 'Vui lòng nhập đơn vị tính.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);

        $data['service_group'] = Service::GROUP_OTHER;
        $data['billing_rule'] = Service::BILLING_ONCE;

        return $data;
    }

    private function guardSurcharge(Service $surcharge): void
    {
        abort_unless($surcharge->isSurchargeCatalog(), 404);
    }
}
