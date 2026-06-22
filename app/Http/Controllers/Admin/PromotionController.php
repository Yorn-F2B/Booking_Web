<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPromotion;
use App\Models\Promotion;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    private array $promotionTypes = [
        Promotion::TYPE_NORMAL => 'Mã thường',
        Promotion::TYPE_EVENT => 'Mã sự kiện',
        Promotion::TYPE_SUPPORT => 'Mã hỗ trợ',
        Promotion::TYPE_CONDITIONAL => 'Mã điều kiện',
    ];

    private array $discountTypes = [
        Promotion::DISCOUNT_PERCENT => 'Giảm theo phần trăm',
        Promotion::DISCOUNT_FIXED => 'Giảm số tiền cố định',
    ];

    public function index(Request $request)
    {
        $query = Promotion::query()
            ->with(['serviceOffers.service'])
            ->withCount('usages')
            ->withSum('usages as total_discount_used', 'discount_amount')
            ->withSum('usages as total_money_discount_used', 'money_discount_amount')
            ->withSum('usages as total_service_discount_used', 'service_discount_amount')
            ->latest();

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $query->where(function ($promotionQuery) use ($keyword) {
                $promotionQuery->where('code', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%')
                    ->orWhereHas('serviceOffers.service', function ($serviceQuery) use ($keyword) {
                        $serviceQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->filled('promotion_type')) {
            $query->where('promotion_type', $request->promotion_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('visibility')) {
            match ($request->visibility) {
                'user' => $query->where('is_public', true)->where('user_can_apply', true),
                'admin' => $query->where('admin_can_apply', true),
                'support' => $query->where('promotion_type', Promotion::TYPE_SUPPORT),
                'hidden_user' => $query->where(function ($visibilityQuery) {
                    $visibilityQuery->where('is_public', false)
                        ->orWhere('user_can_apply', false);
                }),
                default => null,
            };
        }

        if ($request->filled('valid_state')) {
            $now = Carbon::now('Asia/Ho_Chi_Minh');

            match ($request->valid_state) {
                'active_now' => $query
                    ->where('status', 'active')
                    ->where(function ($dateQuery) use ($now) {
                        $dateQuery->whereNull('valid_from')
                            ->orWhere('valid_from', '<=', $now);
                    })
                    ->where(function ($dateQuery) use ($now) {
                        $dateQuery->whereNull('valid_to')
                            ->orWhere('valid_to', '>=', $now);
                    }),
                'upcoming' => $query->whereNotNull('valid_from')->where('valid_from', '>', $now),
                'expired' => $query->whereNotNull('valid_to')->where('valid_to', '<', $now),
                default => null,
            };
        }

        $promotions = $query
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.promotions.index', [
            'promotions' => $promotions,
            'promotionTypes' => $this->promotionTypes,
            'discountTypes' => $this->discountTypes,
        ]);
    }

    public function create()
    {
        return view('admin.pages.promotions.create', [
            'promotion' => new Promotion([
                'promotion_type' => Promotion::TYPE_NORMAL,
                'discount_type' => Promotion::DISCOUNT_FIXED,
                'discount_value' => 0,
                'status' => 'active',
                'is_public' => true,
                'user_can_apply' => true,
                'admin_can_apply' => true,
                'requires_note' => false,
                'is_stackable' => true,
            ]),
            'services' => $this->activeOfferServices(),
            'promotionTypes' => $this->promotionTypes,
            'discountTypes' => $this->discountTypes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedPromotionData($request);
        $serviceOffers = $this->validatedServiceOffers($request);

        $this->ensurePromotionHasValue($data, $serviceOffers);

        DB::transaction(function () use ($data, $serviceOffers) {
            $data['created_by'] = Auth::id();

            $promotion = Promotion::create($data);
            $this->syncServiceOffers($promotion, $serviceOffers);
        });

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Thêm mã ưu đãi thành công.');
    }

    public function show(Promotion $promotion)
    {
        $promotion->load(['creator', 'serviceOffers.service']);

        $usages = BookingPromotion::with([
            'booking.customer',
            'user',
            'serviceOffers',
        ])
            ->where('promotion_id', $promotion->id)
            ->latest()
            ->paginate(10);

        $totalDiscount = BookingPromotion::where('promotion_id', $promotion->id)
            ->sum('discount_amount');

        $totalMoneyDiscount = BookingPromotion::where('promotion_id', $promotion->id)
            ->sum('money_discount_amount');

        $totalServiceDiscount = BookingPromotion::where('promotion_id', $promotion->id)
            ->sum('service_discount_amount');

        return view('admin.pages.promotions.show', [
            'promotion' => $promotion,
            'usages' => $usages,
            'totalDiscount' => $totalDiscount,
            'totalMoneyDiscount' => $totalMoneyDiscount,
            'totalServiceDiscount' => $totalServiceDiscount,
            'promotionTypes' => $this->promotionTypes,
            'discountTypes' => $this->discountTypes,
        ]);
    }

    public function edit(Promotion $promotion)
    {
        $promotion->load(['serviceOffers.service']);

        return view('admin.pages.promotions.edit', [
            'promotion' => $promotion,
            'services' => $this->activeOfferServices(),
            'promotionTypes' => $this->promotionTypes,
            'discountTypes' => $this->discountTypes,
        ]);
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $this->validatedPromotionData($request, $promotion);
        $serviceOffers = $this->validatedServiceOffers($request);

        $this->ensurePromotionHasValue($data, $serviceOffers);

        DB::transaction(function () use ($promotion, $data, $serviceOffers) {
            $promotion->update($data);
            $this->syncServiceOffers($promotion, $serviceOffers);
        });

        return redirect()
            ->route('admin.promotions.show', $promotion->id)
            ->with('success', 'Cập nhật mã ưu đãi thành công.');
    }

    public function destroy(Promotion $promotion)
    {
        if ($promotion->usages()->exists()) {
            $promotion->update([
                'status' => 'inactive',
            ]);

            return redirect()
                ->route('admin.promotions.index')
                ->with('success', 'Mã đã có lịch sử sử dụng nên không xóa khỏi hệ thống. Đã chuyển mã sang trạng thái tạm ẩn.');
        }

        $promotion->delete();

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Xóa mã ưu đãi thành công.');
    }

    public function toggleStatus(Promotion $promotion)
    {
        $promotion->update([
            'status' => $promotion->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with(
            'success',
            $promotion->status === 'active'
                ? 'Đã bật mã ưu đãi.'
                : 'Đã tạm ẩn mã ưu đãi.'
        );
    }

    private function validatedPromotionData(Request $request, ?Promotion $promotion = null): array
    {
        $this->normalizeDateInputs($request);

        $promotionId = $promotion?->id;

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('promotions', 'code')->ignore($promotionId),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',

            'promotion_type' => [
                'required',
                Rule::in(array_keys($this->promotionTypes)),
            ],
            'discount_type' => [
                'required',
                Rule::in(array_keys($this->discountTypes)),
            ],
            'discount_value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',

            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'stay_from' => 'nullable|date',
            'stay_to' => 'nullable|date|after_or_equal:stay_from',

            'min_booking_amount' => 'nullable|numeric|min:0',
            'min_nights' => 'nullable|integer|min:0',
            'min_rooms' => 'nullable|integer|min:0',
            'min_completed_bookings' => 'nullable|integer|min:0',
            'min_total_spent' => 'nullable|numeric|min:0',

            'usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',

            'status' => 'required|in:active,inactive',
        ], [
            'code.required' => 'Vui lòng nhập mã.',
            'code.regex' => 'Mã chỉ được chứa chữ cái, số, dấu gạch ngang hoặc gạch dưới.',
            'code.unique' => 'Mã này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên mã.',
            'promotion_type.required' => 'Vui lòng chọn loại mã.',
            'discount_type.required' => 'Vui lòng chọn kiểu giảm.',
            'valid_to.after_or_equal' => 'Ngày kết thúc nhập mã phải sau hoặc bằng ngày bắt đầu.',
            'stay_to.after_or_equal' => 'Ngày kết thúc lưu trú phải sau hoặc bằng ngày bắt đầu lưu trú.',
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['name'] = trim($data['name']);
        $data['discount_value'] = $this->numberOrZero($data['discount_value'] ?? 0);

        $data['min_booking_amount'] = $this->numberOrZero($data['min_booking_amount'] ?? 0);
        $data['min_nights'] = (int) ($data['min_nights'] ?? 0);
        $data['min_rooms'] = (int) ($data['min_rooms'] ?? 0);
        $data['min_completed_bookings'] = (int) ($data['min_completed_bookings'] ?? 0);
        $data['min_total_spent'] = $this->numberOrZero($data['min_total_spent'] ?? 0);

        $data['usage_limit'] = $this->nullableInt($data['usage_limit'] ?? null);
        $data['per_customer_limit'] = $this->nullableInt($data['per_customer_limit'] ?? null);

        $data['is_public'] = $request->boolean('is_public');
        $data['user_can_apply'] = $request->boolean('user_can_apply');
        $data['admin_can_apply'] = $request->boolean('admin_can_apply');
        $data['requires_note'] = $request->boolean('requires_note');
        $data['is_stackable'] = $request->boolean('is_stackable');

        if ($data['discount_type'] === Promotion::DISCOUNT_PERCENT && (float) $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount_value' => 'Giảm theo phần trăm không được vượt quá 100%.',
            ]);
        }

        if ($data['discount_type'] === Promotion::DISCOUNT_FIXED) {
            $data['max_discount_amount'] = null;
        } else {
            $data['max_discount_amount'] = $this->nullableNumber($data['max_discount_amount'] ?? null);
        }

        if ($data['promotion_type'] === Promotion::TYPE_SUPPORT) {
            $data['is_public'] = false;
            $data['user_can_apply'] = false;
            $data['admin_can_apply'] = true;
            $data['requires_note'] = true;
        }

        if ($data['user_can_apply']) {
            $data['is_public'] = true;
        }

        if (!$data['user_can_apply'] && !$data['admin_can_apply']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'admin_can_apply' => 'Mã phải cho phép ít nhất user hoặc admin áp dụng.',
            ]);
        }

        return $data;
    }

    private function validatedServiceOffers(Request $request): array
    {
        $validated = $request->validate([
            'service_offers' => 'nullable|array',
            'service_offers.*.service_id' => 'nullable|exists:services,id',
            'service_offers.*.discount_type' => ['nullable', Rule::in(array_keys($this->discountTypes))],
            'service_offers.*.discount_value' => 'nullable|numeric|min:0.01',
            'service_offers.*.quantity' => 'nullable|integer|min:1',
            'service_offers.*.auto_add_service' => 'nullable|boolean',
            'service_offers.*.note' => 'nullable|string|max:1000',
        ], [
            'service_offers.*.service_id.exists' => 'Có dịch vụ ưu đãi không hợp lệ.',
            'service_offers.*.discount_value.min' => 'Giá trị ưu đãi dịch vụ phải lớn hơn 0.',
            'service_offers.*.quantity.min' => 'Số lượng dịch vụ ưu đãi phải lớn hơn 0.',
        ]);

        $rows = collect($validated['service_offers'] ?? [])
            ->filter(fn ($row) => !empty($row['service_id']))
            ->map(function ($row, $index) use ($request) {
                $discountType = $row['discount_type'] ?? Promotion::DISCOUNT_PERCENT;
                $discountValue = (float) ($row['discount_value'] ?? 100);

                if ($discountType === Promotion::DISCOUNT_PERCENT && $discountValue > 100) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'service_offers.' . $index . '.discount_value' => 'Ưu đãi dịch vụ theo phần trăm không được vượt quá 100%.',
                    ]);
                }

                return [
                    'service_id' => (int) $row['service_id'],
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                    'auto_add_service' => $request->boolean('service_offers.' . $index . '.auto_add_service'),
                    'note' => $row['note'] ?? null,
                ];
            })
            ->values();

        return $rows->all();
    }

    private function ensurePromotionHasValue(array $data, array $serviceOffers): void
    {
        if ((float) ($data['discount_value'] ?? 0) <= 0 && empty($serviceOffers)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'discount_value' => 'Mã ưu đãi phải có giảm tiền hoặc ít nhất một ưu đãi dịch vụ.',
            ]);
        }
    }

    private function syncServiceOffers(Promotion $promotion, array $serviceOffers): void
    {
        $promotion->serviceOffers()->delete();

        foreach ($serviceOffers as $serviceOffer) {
            $promotion->serviceOffers()->create($serviceOffer);
        }
    }

    private function normalizeDateInputs(Request $request): void
    {
        $data = [];

        foreach (['valid_from', 'valid_to'] as $field) {
            $value = trim((string) $request->input($field));

            if ($value !== '') {
                try {
                    $data[$field] = Carbon::createFromFormat('d/m/Y H:i', $value, 'Asia/Ho_Chi_Minh')
                        ->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    $data[$field] = $value;
                }
            }
        }

        foreach (['stay_from', 'stay_to'] as $field) {
            $value = trim((string) $request->input($field));

            if ($value !== '') {
                try {
                    $data[$field] = Carbon::createFromFormat('d/m/Y', $value, 'Asia/Ho_Chi_Minh')
                        ->format('Y-m-d');
                } catch (\Throwable) {
                    $data[$field] = $value;
                }
            }
        }

        if (!empty($data)) {
            $request->merge($data);
        }
    }

    private function activeOfferServices()
    {
        return Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar'])
            ->orderByRaw("FIELD(type, 'service', 'minibar')")
            ->orderBy('name')
            ->get();
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(1, (int) $value);
    }

    private function nullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (float) $value);
    }

    private function numberOrZero($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return max(0, (float) $value);
    }
}
