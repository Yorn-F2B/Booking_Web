# CHI TIẾT CÁC THAY ĐỔI & CHÚ THÍCH MÃ NGUỒN (CODE COMMENTS)

---

## 📂 1. `app/Services/BookingFinancialService.php`
**Nhiệm vụ**: Cập nhật bộ tính tiền `currentTotal()` để đảm bảo duy nhất 1 nguồn thu và chỉ tính từ các phiếu kiểm tra phòng đã được Admin duyệt (`confirmed`).

```php
    public function currentTotal(Booking $booking): float
    {
        $booking->loadMissing(['bookingRooms', 'serviceItems', 'roomInspections.items']);
        $nights = max(1, $booking->check_in_at->copy()->startOfDay()->diffInDays($booking->check_out_at->copy()->startOfDay()));
        $roomTotal = (float) $booking->bookingRooms->sum(fn ($item) => (float) $item->price_at_booking * $nights + (float) $item->surcharge);
        if ($roomTotal <= 0) {
            $roomTotal = max(0, (float) $booking->subtotal_amount - (float) $booking->serviceItems->sum('total'));
        }

        // YÊU CẦU 3 & 6: Lấy danh sách phiếu kiểm tra phòng có trạng thái 'confirmed' (kết quả mới nhất đã duyệt)
        $confirmedInspections = $booking->roomInspections->where('status', 'confirmed');

        // Danh sách ID các dịch vụ hư hại/minibar đã có trong phiếu kiểm tra phòng confirmed
        $approvedInspectionServiceIds = $confirmedInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // YÊU CẦU 2: Dịch vụ: tính các khoản confirmed và loại bỏ khoản minibar/damage_fee trùng với kiểm tra phòng đã duyệt
        $services = (float) $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->reject(function ($item) use ($approvedInspectionServiceIds) {
                return in_array($item->type, ['damage_fee', 'minibar'], true)
                    && in_array($item->service_id, $approvedInspectionServiceIds, true);
            })
            ->sum('total');

        // YÊU CẦU 3: Phí kiểm tra phòng: Chỉ lấy các khoản đã duyệt thuộc phiếu kiểm tra phòng 'confirmed' (kết quả mới nhất đã duyệt)
        $inspection = (float) $confirmedInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');

        return max(0, round($roomTotal + $services + $inspection - (float) $booking->discount_amount, 0));
    }
```

---

## 📂 2. `app/Http/Controllers/Admin/FloorInspectionController.php`
**Nhiệm vụ**: Chống cộng trùng minibar khi buồng phòng gửi báo cáo kiểm tra.

```php
            $roomMinibarServiceIds = array_values(array_unique(array_map('intval', $data['room_minibar_service_ids'] ?? [])));
            $roomMinibarQuantities = $data['room_minibar_quantities'] ?? [];

            if (count($roomMinibarServiceIds) > 0) {
                $minibarServices = Service::whereIn('id', $roomMinibarServiceIds)
                    ->where('type', 'minibar')
                    ->where('status', 'active')
                    ->get()
                    ->keyBy('id');

                foreach ($roomMinibarServiceIds as $serviceId) {
                    $service = $minibarServices->get($serviceId);
                    if (!$service) {
                        continue;
                    }

                    $actualQuantity = max(1, (int) ($roomMinibarQuantities[$serviceId] ?? 1));

                    // YÊU CẦU 1: Chống cộng trùng minibar: Trừ số lượng đã được ghi nhận và tính tiền trước đó
                    $alreadyBilledQty = (int) \App\Models\BookingServiceItem::where('booking_id', $inspection->booking_id)
                        ->where('service_id', $serviceId)
                        ->where('billing_status', 'confirmed')
                        ->sum('quantity');

                    $netQuantity = max(0, $actualQuantity - $alreadyBilledQty);
                    $lineTotal = (float) $service->price * $netQuantity;

                    $note = $alreadyBilledQty > 0
                        ? "Thực tế dùng: {$actualQuantity}, đã ghi nhận trước: {$alreadyBilledQty} => tính thêm: {$netQuantity}"
                        : null;

                    $item = RoomInspectionItem::create([
                        'room_inspection_id' => $inspection->id,
                        'service_id' => $service->id,
                        'type' => 'minibar',
                        'name' => $service->name,
                        'unit' => $service->unit,
                        'price' => $service->price,
                        'quantity' => $netQuantity,
                        'total' => $lineTotal,
                        'original_total' => $lineTotal,
                        'status' => 'pending',
                        'admin_note' => $note,
                        'guest_response' => 'pending',
                        'recheck_decision' => 'not_required',
                    ]);

                    $createdItems->push($item);
                    $minibarTotal += $lineTotal;
                    if ($netQuantity > 0) {
                        $minibarMessages[] = $service->name . ' x' . $netQuantity . ' = ' . number_format($lineTotal, 0, ',', '.') . 'đ'
                            . ($alreadyBilledQty > 0 ? " (Đã tính trước {$alreadyBilledQty})" : '');
                    } else {
                        $minibarMessages[] = $service->name . ' x' . $actualQuantity . ' (Đã tính đủ ' . $alreadyBilledQty . ' trước đó -> +0đ)';
                    }
                }
            }
```

---

## 📂 3. `app/Http/Controllers/Admin/InspectionApprovalController.php`
**Nhiệm vụ**: Chống tính trùng khoản ở cả 2 nơi (hủy dịch vụ cũ khi duyệt từ kiểm tra phòng) và chống bấm lặp duyệt.

```php
            // YÊU CẦU 5: Chống bấm lặp / Idempotency khi duyệt phiếu kiểm tra phòng
            if ($inspection->status === 'confirmed') {
                return redirect()
                    ->route('admin.inspection-approvals.index')
                    ->with('info', 'Phiếu kiểm tra phòng này đã được xác nhận thành công trước đó.');
            }

            // ... trong vòng lặp duyệt từng hạng mục ...
            if (in_array((int) $item->id, $approvedItemIds, true)) {
                $item->update([
                    'status' => 'approved',
                    'admin_note' => null,
                ]);

                if ($item->type === 'minibar') {
                    $approvedMinibarTotal += (float) $item->total;
                } else {
                    $approvedDamageTotal += (float) $item->total;
                }

                // YÊU CẦU 2: Mỗi khoản chỉ tính từ một nguồn.
                // Khi duyệt khoản minibar/damage_fee từ phiếu kiểm tra phòng, tự động chuyển khoản tương ứng bên dịch vụ (booking_service_items) sang bị hủy/thay thế để tránh tính trùng 2 lần.
                if ($item->service_id) {
                    \App\Models\BookingServiceItem::where('booking_id', $inspection->booking_id)
                        ->where('service_id', $item->service_id)
                        ->where('billing_status', 'confirmed')
                        ->update([
                            'billing_status' => 'cancelled',
                            'note' => 'Đã hủy do chuyển nguồn tính trong phiếu kiểm tra phòng ' . ($inspection->room->room_number ?? ''),
                        ]);
                }
            }
```

---

## 📂 4. `app/Http/Controllers/Admin/BookingLifecycleController.php`
**Nhiệm vụ**: Chặn check-out nếu còn phòng chưa hoàn thành kiểm tra & Chống bấm lặp cho Thêm phí / Check-out.

```php
    public function addCheckoutFee(Request $request, Booking $booking)
    {
        // ...
        // YÊU CẦU 5: Chống bấm lặp: Kiểm tra xem khoản phí cùng tên và số tiền vừa được thêm trong 5 giây vừa qua chưa
        $recentDuplicate = BookingServiceItem::where('booking_id', $booking->id)
            ->where('name', $feeName)
            ->where('unit_price', $feeAmount)
            ->where('created_at', '>=', now('Asia/Ho_Chi_Minh')->subSeconds(5))
            ->first();

        if ($recentDuplicate) {
            DB::commit();
            return back()->with('success', 'Khoản phí “' . $feeName . '” đã được hệ thống ghi nhận trước đó.');
        }
        // ...
    }

    public function checkOut(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        // YÊU CẦU 5: Chống bấm lặp check-out
        if ($booking->status === 'checked_out') {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('success', 'Booking này đã được check-out hoàn tất trước đó.');
        }

        // YÊU CẦU 4: Theo dõi từng phòng và chỉ cho phép checkout toàn bộ khi TẤT CẢ các phòng đã hoàn thành kiểm tra
        $incompleteRooms = [];
        foreach ($booking->bookingRooms as $bRoom) {
            $rId = $bRoom->room_id;
            $rNum = $bRoom->room?->room_number ?? '---';
            $insp = $booking->roomInspections->firstWhere('room_id', $rId);

            if (!$insp) {
                $incompleteRooms[] = "Phòng {$rNum} (Chưa được tạo phiếu kiểm tra)";
            } elseif ($insp->status !== 'confirmed') {
                $statusText = match ($insp->status) {
                    'pending' => 'Chưa kiểm tra',
                    'reported' => 'Đang xử lý báo cáo',
                    'rejected' => 'Bị từ chối',
                    default => $insp->status,
                };
                $stageText = match ($insp->workflow_stage) {
                    'housekeeping_report' => 'Buồng phòng chưa gửi báo cáo',
                    'guest_consultation' => 'Đang trao đổi với khách',
                    'housekeeping_recheck' => 'Đang kiểm tra lại',
                    'admin_approval' => 'Chờ admin xác nhận',
                    default => $insp->workflow_stage,
                };
                $incompleteRooms[] = "Phòng {$rNum} ({$statusText} - {$stageText})";
            }
        }

        if (count($incompleteRooms) > 0) {
            return back()->with(
                'error',
                'Chưa thể check-out toàn bộ booking. Vẫn còn phòng chưa hoàn thành kiểm tra: ' . implode('; ', $incompleteRooms) . '.'
            );
        }
        // ...
    }
```

---

## 📂 5. Views & JavaScript Double-Submit Lock
* **`resources/views/admin/pages/inspection-approvals/show.blade.php`**: Nút submit form duyệt kiểm tra tự khóa và chuyển sang "Đang xử lý...".
* **`resources/views/admin/pages/bookings/_workspace.blade.php`**: Nút submit form Thêm phí và Check-out tự khóa khi bấm xác nhận.
