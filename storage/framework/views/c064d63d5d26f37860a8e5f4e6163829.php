    <?php
        $isReceptionDesk = in_array(auth()->user()?->role, ['receptionist', 'receptionist_lead'], true);

        $bookingStatusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Chờ kiểm tra',
            'checked_out' => 'Đã trả phòng',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'canceled' => 'Đã hủy',
            'no_show' => 'No-show',
        ];

        $bookingStatusClasses = [
            'pending' => 'status-pending',
            'confirmed' => 'status-confirmed',
            'checked_in' => 'status-checked-in',
            'inspection_requested' => 'status-warning',
            'checked_out' => 'status-done',
            'completed' => 'status-done',
            'cancelled' => 'status-cancelled',
            'canceled' => 'status-cancelled',
            'no_show' => 'status-cancelled',
        ];

        $paymentStatusLabels = [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
        ];

        $paymentStatusClasses = [
            'unpaid' => 'status-muted',
            'partial' => 'status-warning',
            'paid' => 'status-done',
        ];

        $roomStatusLabels = [
            'available' => 'Trống',
            'reserved' => 'Đã giữ',
            'occupied' => 'Đang ở',
            'cleaning' => 'Cần dọn',
            'inspection' => 'Chờ kiểm tra',
            'maintenance' => 'Bảo trì',
        ];

        $bookingStatusClass = $bookingStatusClasses[$booking->status] ?? 'status-muted';
        $paymentStatusClass = $paymentStatusClasses[$booking->payment_status] ?? 'status-muted';

        $hotelPolicy = app(\App\Services\HotelPolicyService::class);
        $bookingPolicy = fn (string $key, mixed $fallback = null) => $hotelPolicy->forBooking($booking, $key, $fallback);
        $policyTime = fn (string $key, string $fallback) => substr((string) $bookingPolicy($key, $fallback), 0, 5);
        $formatPercent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        $standardCheckInTimePolicy = $policyTime('stay.standard_check_in_time', '14:00');
        $standardCheckOutTimePolicy = $policyTime('stay.standard_check_out_time', '12:00');
        $priorityCleaningTimePolicy = $policyTime('stay.priority_cleaning_start_time', '12:00');
        $earlyTier1TimePolicy = $policyTime('stay.early_checkin_tier1_end', '06:00');
        $earlyTier2TimePolicy = $policyTime('stay.early_checkin_tier2_end', '09:00');
        $earlyFreeFromTimePolicy = $policyTime('stay.early_checkin_free_from', '12:00');
        $directCancelCutoffTimePolicy = $policyTime('booking.direct_cancel_cutoff_time', '14:00');
        $lateArrivalCutoffTimePolicy = $policyTime('stay.late_arrival_cutoff_time', '18:00');
        $lateArrivalTier1EndPolicy = $policyTime('stay.late_arrival_tier1_end', '21:00');
        $positiveLateArrivalPercent = function (string $key, float $fallback) use ($bookingPolicy, $hotelPolicy): float {
            $value = (float) $bookingPolicy($key, $fallback);
            if ($value <= 0) {
                $value = (float) $hotelPolicy->get($key, $fallback);
            }

            return $value > 0 ? $value : $fallback;
        };
        $lateArrivalPercent1Policy = $positiveLateArrivalPercent('stay.late_arrival_percent_1', 20);
        $lateArrivalPercent2Policy = $positiveLateArrivalPercent('stay.late_arrival_percent_2', 50);
        $lateArrivalNextDayPercentPolicy = $positiveLateArrivalPercent('stay.late_arrival_percent_next_day', 100);
        $lateArrivalGraceMinutesPolicy = max(0, (int) $bookingPolicy('stay.late_arrival_grace_minutes', 30));
        $shortStayOvernightHoursPolicy = max(1, (int) $bookingPolicy('stay.short_stay_to_overnight_hours', 12));
        $depositPercentPolicy = (float) $bookingPolicy('payment.deposit_percent', 30);
        $depositPercentLabel = $formatPercent($depositPercentPolicy) . '%';

        $setPolicyTime = function (\Carbon\Carbon $date, string $time): \Carbon\Carbon {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            return $date->copy()->setTime($hour, $minute, 0);
        };

        $customerName = $booking->booked_customer_name !== ''
            ? $booking->booked_customer_name
            : 'Chưa có tên';

        $nightCount = max(1, (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400);
        $assignedRooms = $booking->bookingRooms->pluck('room')->filter();
        $assignedRoomIds = $assignedRooms->pluck('id')->values()->toArray();

        $nowVnForCheckInFlow = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $bookingCheckInAtForFlow = $booking->check_in_at
            ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
            : null;
        $priorityCleaningStartAt = $bookingCheckInAtForFlow
            ? $setPolicyTime($bookingCheckInAtForFlow, $priorityCleaningTimePolicy)
            : null;
        $standardCheckInAt = $bookingCheckInAtForFlow
            ? $setPolicyTime($bookingCheckInAtForFlow, $standardCheckInTimePolicy)
            : null;
        $roomsNeedPreparation = $assignedRooms->filter(function ($room) {
            return in_array($room->status ?? null, ['inspection', 'cleaning']);
        });
        $roomsNotReadyForCheckIn = $assignedRooms->filter(function ($room) {
            return in_array($room->status ?? null, ['inspection', 'cleaning', 'maintenance']);
        });
        $canRequestPriorityCleaning = $booking->status == 'confirmed'
            && $priorityCleaningStartAt
            && $nowVnForCheckInFlow->isSameDay($bookingCheckInAtForFlow)
            && $nowVnForCheckInFlow->greaterThanOrEqualTo($priorityCleaningStartAt)
            && $roomsNeedPreparation->count() > 0;
        $priorityCleaningRoomText = $roomsNeedPreparation
            ->map(function ($room) use ($roomStatusLabels) {
                return 'Phòng '
                    . ($room->room_number ?? '---')
                    . ' đang '
                    . mb_strtolower($roomStatusLabels[$room->status] ?? $room->status ?? 'chưa rõ');
            })
            ->implode(', ');

        $notReadyRoomText = $roomsNotReadyForCheckIn
            ->map(function ($room) use ($roomStatusLabels) {
                return 'Phòng '
                    . ($room->room_number ?? '---')
                    . ' đang '
                    . mb_strtolower($roomStatusLabels[$room->status] ?? $room->status ?? 'chưa rõ');
            })
            ->implode(', ');

        $earlyCheckInPercent = 0;
        $earlyCheckInFeePreview = 0;
        $earlyCheckInBasePrice = 0;
        $earlyCheckInPolicyText = '';
        $earlyCheckInDurationText = '';
        $earlyCheckInMinutes = 0;
        $earlyCheckInFinalTotalPreview = 0;
        $isEarlyCheckInNow = false;
        $isBeforeBookingDateNow = false;
        $beforeBookingDateMessage = '';
        $stayDateChangeCheckInDateDefault = $nowVnForCheckInFlow->toDateString();
        $stayDateChangeCheckInTimeDefault = $nowVnForCheckInFlow->format('H:i');
        $stayDateChangeCheckOutDateDefault = $bookingCheckInAtForFlow
            ? $bookingCheckInAtForFlow->copy()->addDay()->toDateString()
            : $nowVnForCheckInFlow->copy()->addDay()->toDateString();
        $stayDateChangeCheckOutTimeDefault = $booking->check_out_at
            ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->format('H:i')
            : $standardCheckOutTimePolicy;

        $stayDateCategoryOptions = session('stay_date_category_options');
        $stayDateRepricePreview = session('stay_date_reprice_preview');

        if ($booking->check_out_at) {
            $stayDateChangeCheckOutDateDefault = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->toDateString();
        }

        $bookingCheckInDateForFlow = $booking->check_in_date
            ? \Carbon\Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh')->toDateString()
            : $bookingCheckInAtForFlow?->toDateString();

        if (
            in_array($booking->status, ['pending', 'confirmed'], true)
            && !$booking->actual_check_in
            && $booking->booking_type != 'hourly'
            && $bookingCheckInDateForFlow
            && $nowVnForCheckInFlow->toDateString() < $bookingCheckInDateForFlow
        ) {
            $isBeforeBookingDateNow = true;
            $beforeBookingDateMessage = 'Chưa đến ngày nhận phòng. Nếu khách muốn nhận ngay, hãy đổi ngày lưu trú và kiểm tra lại phòng trống trước khi xác nhận.';
        }

        if (
            $booking->status == 'confirmed'
            && $booking->booking_type != 'hourly'
            && $bookingCheckInAtForFlow
            && $standardCheckInAt
            && $nowVnForCheckInFlow->isSameDay($bookingCheckInAtForFlow)
            && $nowVnForCheckInFlow->lessThan($standardCheckInAt)
        ) {
            $isEarlyCheckInNow = true;
            $earlyCheckInMinutes = $nowVnForCheckInFlow->diffInMinutes($standardCheckInAt);
            $earlyCheckInHoursOnly = intdiv($earlyCheckInMinutes, 60);
            $earlyCheckInRemainMinutes = $earlyCheckInMinutes % 60;
            $earlyCheckInDurationText = $earlyCheckInHoursOnly . ' giờ'
                . ($earlyCheckInRemainMinutes > 0 ? ' ' . $earlyCheckInRemainMinutes . ' phút' : '');

            $earlyCheckInBasePrice = $booking->bookingRooms->sum(function ($bookingRoom) {
                return (float) $bookingRoom->price_at_booking;
            });

            if ($earlyCheckInBasePrice <= 0) {
                $earlyCheckInBasePrice = (float) ($booking->roomCategory->price ?? 0)
                    * max(1, (int) $booking->room_quantity);
            }

            $earlyPolicyPreview = app(\App\Services\StayPricingPolicyService::class)
                ->earlyCheckIn($nowVnForCheckInFlow, $earlyCheckInBasePrice, $booking);
            $earlyCheckInPercent = (float) ($earlyPolicyPreview['percent'] ?? 0);
            $earlyCheckInPolicyText = (string) ($earlyPolicyPreview['policy_text'] ?? '');
            $earlyCheckInFeePreview = (float) ($earlyPolicyPreview['amount'] ?? 0);
            $earlyCheckInFinalTotalPreview = (float) $booking->estimated_total + $earlyCheckInFeePreview;
        }

        $availableServices = $availableServices ?? collect();

        $serviceItemTotal = $serviceItemTotal ?? 0;
        $approvedDamageTotal = $approvedDamageTotal ?? 0;
        $approvedMinibarTotal = $approvedMinibarTotal ?? 0;
        $approvedInspectionTotal = $approvedInspectionTotal ?? ($approvedDamageTotal + $approvedMinibarTotal);

        if ($booking->booking_type == 'hourly') {
            $roomTotal = max(0, (float) $booking->estimated_total - $serviceItemTotal - $approvedInspectionTotal);
        } else {
            $roomTotal = $booking->bookingRooms->sum(function ($bookingRoom) use ($nightCount) {
                return (float) $bookingRoom->price_at_booking * $nightCount;
            });

            if ($roomTotal <= 0) {
                $roomTotal = max(0, (float) $booking->estimated_total - $serviceItemTotal - $approvedInspectionTotal);
            }
        }

        $hourlyCleaningUntil = null;
        if ($booking->booking_type == 'hourly' && $booking->check_out_at) {
            $hourlyCleaningUntil = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')
                ->addMinutes($booking->cleaning_buffer_minutes ?? (int) $bookingPolicy('booking.cleaning_buffer_minutes', 0));
        }

        $existingCheckoutLateFeeTotal = $booking->serviceItems
            ->filter(function ($item) {
                return $item->type == 'late_checkout_fee'
                    && $item->name == 'Phụ thu check-out muộn'
                    && !in_array($item->billing_status, ['unused', 'cancelled']);
            })
            ->sum(function ($item) {
                return (float) $item->total;
            });

        $checkoutLateFeePreview = 0;
        $checkoutLateRequiredTotalPreview = 0;
        $checkoutLateHoursPreview = 0;
        $checkoutLateChargedHours = 0;
        $checkoutLatePercent = 0;
        $checkoutLateBasePrice = 0;
        $checkoutLatePolicyText = 'Khách chưa quá giờ trả phòng, không phát sinh phụ thu trả muộn.';
        $checkoutLateReasonText = 'Khách trả phòng đúng giờ dự kiến.';
        $checkoutLateFormulaText = 'Không phát sinh phụ thu.';
        $checkoutLateNoteText = '';

        if (
            in_array($booking->status, ['checked_in', 'inspection_requested'])
            && $booking->check_out_at
        ) {
            $nowVnForCheckout = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
            $plannedCheckOutForPreview = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

            if ($nowVnForCheckout->greaterThan($plannedCheckOutForPreview)) {
                $lateCheckoutMinutes = $plannedCheckOutForPreview->diffInMinutes($nowVnForCheckout);
                $checkoutLateHoursPreview = round($lateCheckoutMinutes / 60, 2);
                $checkoutLateReasonText = 'Khách trả phòng thực tế sau giờ trả phòng dự kiến khoảng '
                    . $checkoutLateHoursPreview . ' giờ.';

                $pricingPolicyForCheckout = app(\App\Services\StayPricingPolicyService::class);

                if ($booking->booking_type == 'hourly') {
                    $checkInAtForCheckout = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
                    $totalMinutesForCheckout = $checkInAtForCheckout->diffInMinutes($nowVnForCheckout);
                    $nightPriceForCheckout = (float) ($booking->roomCategory->price ?? 0);
                    $quantityForCheckout = max(1, (int) $booking->room_quantity);

                    $newPricingForCheckout = $totalMinutesForCheckout > $shortStayOvernightHoursPolicy * 60
                        ? $pricingPolicyForCheckout->longStay(
                            $checkInAtForCheckout,
                            $nowVnForCheckout,
                            $nightPriceForCheckout,
                            $quantityForCheckout,
                            $booking
                        )
                        : $pricingPolicyForCheckout->shortStay(
                            $nightPriceForCheckout,
                            $quantityForCheckout,
                            $totalMinutesForCheckout,
                            $booking
                        );

                    $newRoomTotalForCheckout = (float) ($newPricingForCheckout['total_amount'] ?? $newPricingForCheckout['amount'] ?? 0);
                    $checkoutLateBasePrice = max(0, (float) $roomTotal);
                    $checkoutLateFeePreview = max(0, round($newRoomTotalForCheckout - $checkoutLateBasePrice, 0));
                    $checkoutLatePolicyText = $totalMinutesForCheckout > $shortStayOvernightHoursPolicy * 60
                        ? 'Áp dụng chính sách qua đêm, chỉ thu phần chênh lệch.'
                        : 'Booking theo giờ được tính lại theo tổng thời gian ở thực tế và chỉ thu phần chênh lệch.';
                    $checkoutLateFormulaText = number_format($newRoomTotalForCheckout, 0, ',', '.')
                        . 'đ giá theo thời lượng thực tế - '
                        . number_format($checkoutLateBasePrice, 0, ',', '.')
                        . 'đ tiền phòng đã tính = '
                        . number_format($checkoutLateFeePreview, 0, ',', '.') . 'đ.';
                } else {
                    $checkoutLateBasePrice = (float) $booking->bookingRooms->sum(function ($bookingRoom) {
                        return (float) $bookingRoom->price_at_booking;
                    });

                    if ($checkoutLateBasePrice <= 0) {
                        $checkoutLateBasePrice = (float) ($booking->roomCategory->price ?? 0)
                            * max(1, (int) $booking->room_quantity);
                    }

                    $extraCheckoutDays = max(
                        0,
                        $plannedCheckOutForPreview->copy()->startOfDay()
                            ->diffInDays($nowVnForCheckout->copy()->startOfDay())
                    );
                    $checkoutDayPolicy = $pricingPolicyForCheckout->lateCheckOut(
                        $nowVnForCheckout,
                        $checkoutLateBasePrice,
                        $booking
                    );
                    $checkoutLatePercent = (float) $checkoutDayPolicy['percent'];
                    $checkoutLateFeePreview = round(
                        ($extraCheckoutDays * $checkoutLateBasePrice) + (float) $checkoutDayPolicy['amount'],
                        0
                    );
                    $checkoutLatePolicyText = ($extraCheckoutDays > 0
                            ? 'Trả sang thêm ' . $extraCheckoutDays . ' ngày, tính thêm '
                                . $extraCheckoutDays . ' đêm. '
                            : '')
                        . $checkoutDayPolicy['policy_text'];

                    $formulaParts = [];
                    if ($extraCheckoutDays > 0) {
                        $formulaParts[] = $extraCheckoutDays . ' đêm × '
                            . number_format($checkoutLateBasePrice, 0, ',', '.') . 'đ';
                    }
                    if ($checkoutLatePercent > 0) {
                        $formulaParts[] = rtrim(rtrim(number_format($checkoutLatePercent, 2, '.', ''), '0'), '.')
                            . '% × ' . number_format($checkoutLateBasePrice, 0, ',', '.') . 'đ';
                    }
                    $checkoutLateFormulaText = count($formulaParts) > 0
                        ? implode(' + ', $formulaParts) . ' = '
                            . number_format($checkoutLateFeePreview, 0, ',', '.') . 'đ.'
                        : 'Trong thời gian miễn phí, phụ thu = 0đ.';
                }

                // serviceItemTotal đã bao gồm khoản phụ thu trả muộn từng ghi trước đó.
                // Preview chỉ cộng phần còn thiếu so với mức phải thu tại thời điểm hiện tại,
                // tránh cộng trùng và vẫn tăng phí nếu khách tiếp tục ở sang mốc chính sách cao hơn.
                $checkoutLateRequiredTotalPreview = max(0, (float) $checkoutLateFeePreview);
                $checkoutLateFeePreview = max(0, round(
                    $checkoutLateRequiredTotalPreview - $existingCheckoutLateFeeTotal,
                    0
                ));

                $checkoutLateNoteText = 'Giờ trả phòng dự kiến: '
                    . $plannedCheckOutForPreview->format('d/m/Y H:i')
                    . '. Thời điểm kiểm tra: '
                    . $nowVnForCheckout->format('d/m/Y H:i')
                    . '.';
                if ($existingCheckoutLateFeeTotal > 0) {
                    $checkoutLateNoteText .= ' Đã ghi nhận '
                        . number_format($existingCheckoutLateFeeTotal, 0, ',', '.')
                        . 'đ; hệ thống chỉ cộng thêm phần chênh lệch nếu mức phí hiện tại cao hơn.';
                }
            }
        }

        $promotionDiscountTotal = (float) ($booking->discount_amount ?? 0);

        if ($promotionDiscountTotal <= 0 && isset($booking->bookingPromotions)) {
            $promotionDiscountTotal = (float) $booking->bookingPromotions->sum('discount_amount');
        }

        $promotionMoneyDiscountTotal = isset($booking->bookingPromotions)
            ? (float) $booking->bookingPromotions->sum('money_discount_amount')
            : $promotionDiscountTotal;

        $promotionServiceDiscountTotal = isset($booking->bookingPromotions)
            ? (float) $booking->bookingPromotions->sum('service_discount_amount')
            : 0;

        $promotionRoomUpgradeDiscountTotal = isset($booking->bookingPromotions)
            ? (float) $booking->bookingPromotions->sum('room_upgrade_discount_amount')
            : 0;

        $manualRoomSelectionFee = max(0, (float) ($booking->room_selection_fee ?? 0));
        $finalTotal = max(0, $roomTotal + $serviceItemTotal + $approvedInspectionTotal
            + $manualRoomSelectionFee + $checkoutLateFeePreview - $promotionDiscountTotal);

        // Lịch sử chuyển tiền giữ nguyên; chỉ phân bổ lại theo tổng booking và
        // mức cọc theo policy snapshot của booking sau khi đổi ngày/hạng.
        $successfulPayments = $booking->payments
            ->where('status', 'success')
            ->sortBy(function ($payment) {
                return ($payment->paid_at?->timestamp ?? $payment->created_at?->timestamp ?? 0) . '-' . str_pad((string) $payment->id, 20, '0', STR_PAD_LEFT);
            })
            ->values();

        $financialServiceForView = app(\App\Services\BookingFinancialService::class);
        $paymentAllocation = $financialServiceForView->paymentAllocation($booking, $finalTotal);
        $adminPaymentPaidAmount = (float) $paymentAllocation['paid_total'];
        $adminPaymentDepositTarget = (float) $paymentAllocation['required_deposit'];
        $actualDepositPaid = (float) $paymentAllocation['allocated_deposit'];
        $additionalPaidTotal = (float) $paymentAllocation['prepaid_amount'];
        $adminPaymentDepositAmount = (float) $paymentAllocation['deposit_shortfall'];
        $remainingTotal = (float) $paymentAllocation['remaining'];
        $currentOverpaymentTotal = (float) $paymentAllocation['overpayment'];
        $totalBeforeDiscount = (float) ($roomTotal + $serviceItemTotal + $approvedInspectionTotal
            + $manualRoomSelectionFee + $checkoutLateFeePreview);
        $confirmedServiceItemsForBreakdown = $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->values();
        $approvedMinibarItemsForBreakdown = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->where('type', 'minibar')
            ->values();
        $approvedDamageItemsForBreakdown = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->where('type', 'damage_fee')
            ->values();
        $serviceBillingRuleLabels = \App\Models\Service::billingRuleLabels();
        $serviceBillingFormula = function ($item) use ($nightCount, $booking) {
            $rule = \App\Models\Service::normalizeBillingRule(
                $item->billing_rule_snapshot ?: optional($item->service)->billing_rule
            );
            $baseQuantity = max(1, (int) ($item->base_quantity ?: $item->quantity ?: 1));
            $nights = max(1, (int) ($item->nights_snapshot ?: $nightCount));
            $rooms = max(1, (int) ($item->rooms_snapshot ?: $booking->room_quantity ?: 1));
            $people = max(1, (int) ($item->people_snapshot ?: ((int) $booking->adult_count + (int) $booking->child_count) ?: 1));
            $parts = [number_format((float) $item->unit_price, 0, ',', '.') . 'đ', '× ' . $baseQuantity];

            if (in_array($rule, [\App\Models\Service::BILLING_PER_NIGHT, \App\Models\Service::BILLING_PER_ROOM_PER_NIGHT, \App\Models\Service::BILLING_PER_GUEST_PER_NIGHT], true)) {
                $parts[] = '× ' . $nights . ' đêm';
            }
            if (in_array($rule, [\App\Models\Service::BILLING_PER_ROOM, \App\Models\Service::BILLING_PER_ROOM_PER_NIGHT], true)) {
                $parts[] = '× ' . $rooms . ' phòng';
            }
            if (in_array($rule, [\App\Models\Service::BILLING_PER_GUEST, \App\Models\Service::BILLING_PER_GUEST_PER_NIGHT], true)) {
                $parts[] = '× ' . $people . ' khách';
            }

            return implode(' ', $parts) . ' = ' . number_format((float) $item->total, 0, ',', '.') . 'đ';
        };
        $paymentProviderLabelsForBreakdown = [
            'cash' => 'Tiền mặt tại quầy',
            'bank_transfer' => 'Chuyển khoản tại quầy',
            'vnpay' => 'VNPay',
            'admin_cash' => 'Tiền mặt tại quầy',
            'admin_bank_transfer' => 'Chuyển khoản tại quầy',
        ];

        $effectivePaymentStatus = $adminPaymentPaidAmount <= 0
            ? 'unpaid'
            : ($remainingTotal <= 0.01 ? 'paid' : 'partial');
        $paymentStatusClass = $paymentStatusClasses[$effectivePaymentStatus] ?? 'status-muted';

        $adminPaymentFullAmount = $remainingTotal;
        $adminPaymentDefaultEmail = old('customer_email', $booking->booked_customer_email ?? '');

        $currentAdultCapacity = $booking->bookingRooms->sum(function ($bookingRoom) {
            return $bookingRoom->room->category->adult_capacity ?? 0;
        });

        $currentChildCapacity = $booking->bookingRooms->sum(function ($bookingRoom) {
            return $bookingRoom->room->category->child_capacity ?? 0;
        });

        $roomCategoriesForBookingManage = \App\Models\RoomCategory::where('status', 'active')
            ->withCount([
                'rooms as available_rooms_count' => function ($query) use ($booking) {
                    if ($booking->status === 'checked_in') {
                        $query->availableForPeriod(
                            $booking->check_in_at,
                            $booking->check_out_at,
                            $booking->id
                        )->where('status', 'available');
                    } else {
                        $query->bookableForPeriod(
                            $booking->check_in_at,
                            $booking->check_out_at,
                            $booking->id
                        );
                    }
                },
            ])
            ->orderBy('price')
            ->get();

        $extraGuestServices = \App\Models\Service::where('type', 'occupancy_fee')
            ->where('status', 'active')
            ->where('price', '>', 0)
            ->orderBy('name')
            ->get();

        $inspectionCollection = $booking->roomInspections ?? collect();
        $hasInspection = $hasInspection ?? $inspectionCollection->count() > 0;
        $allInspectionsConfirmed = $allInspectionsConfirmed ?? (
            $hasInspection
            && $inspectionCollection->every(function ($inspection) {
                return ($inspection->status ?? null) === 'confirmed'
                    || ($inspection->workflow_stage ?? null) === \App\Models\RoomInspection::STAGE_COMPLETED;
            })
        );

        $approvedInspectionItems = $inspectionCollection
            ->flatMap->items
            ->where('status', 'approved');

        $changeRoomCheckInAt = $booking->check_in_at;
        $changeRoomCheckOutAt = $booking->check_out_at;
        $timeAvailableRooms = collect();

        if ($changeRoomCheckInAt && $changeRoomCheckOutAt && $assignedRooms->count() > 0) {
            $currentAssignedCategoryIds = $assignedRooms
                ->pluck('room_category_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $sameRankRoomQuery = \App\Models\Room::whereIn('room_category_id', $currentAssignedCategoryIds)
                ->whereNotIn('id', $assignedRoomIds)
                ->availableForPeriod($changeRoomCheckInAt, $changeRoomCheckOutAt, $booking->id);
            if ($booking->status === 'checked_in') {
                $sameRankRoomQuery->where('status', 'available');
            }
            $timeAvailableRooms = $sameRankRoomQuery
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get();
        }


        $categoryChangeAvailableRooms = collect();
        if ($changeRoomCheckInAt && $changeRoomCheckOutAt) {
            $categoryRoomQuery = \App\Models\Room::query()
                ->whereNotIn('id', $assignedRoomIds)
                ->bookableForPeriod($changeRoomCheckInAt, $changeRoomCheckOutAt, $booking->id);

            if ($booking->status === 'checked_in') {
                $categoryRoomQuery->where('status', 'available');
            }

            $categoryChangeAvailableRooms = $categoryRoomQuery
                ->orderBy('room_category_id')
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get();
        }

        $canManageBookingRooms = in_array($booking->status, ['confirmed', 'checked_in']) && !$hasInspection;
        $canEditServiceItems = in_array($booking->status, ['pending', 'confirmed', 'checked_in']);

        $lateShowNowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $lateShowCheckInAt = $booking->check_in_at
            ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
            : null;
        $lateShowCheckOutAt = $booking->check_out_at
            ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')
            : null;

        $isLateCheckout = $booking->isLateCheckout($lateShowNowVn);
        $lateCheckoutMinutesDisplay = $booking->lateCheckoutMinutes($lateShowNowVn);
        $lateCheckoutText = $lateCheckoutMinutesDisplay >= 60
            ? intdiv($lateCheckoutMinutesDisplay, 60) . ' giờ ' . ($lateCheckoutMinutesDisplay % 60) . ' phút'
            : $lateCheckoutMinutesDisplay . ' phút';

        $usesLateArrivalNoShowPolicy = $booking->usesLateArrivalNoShowPolicy();
        $lateShowNoShowLimitAt = $booking->lateArrivalHoldLimitAt();
        $isRescheduledAfterCutoff = $booking->isRescheduledAfterCutoff();

        $lateShowIsAfterNoShowLimit = $lateShowNoShowLimitAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowNoShowLimitAt);
        $lateShowIsPastStayTime = $lateShowCheckOutAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowCheckOutAt);
        $lateShowIsCheckInTooLate = $booking->status == 'confirmed'
            && $lateShowNoShowLimitAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowNoShowLimitAt);
        $lateFlowDate = $lateShowCheckInAt?->copy()->startOfDay();
        $noShowStartAt = $lateFlowDate ? $setPolicyTime($lateFlowDate, $directCancelCutoffTimePolicy) : null;
        $noShowEndAt = $lateFlowDate ? $setPolicyTime($lateFlowDate, $lateArrivalCutoffTimePolicy) : null;
        $canNoShowNow = $usesLateArrivalNoShowPolicy
            && $booking->status == 'confirmed'
            && !$booking->actual_check_in
            && $noShowStartAt
            && $lateShowNowVn->greaterThanOrEqualTo($noShowStartAt)
            && $lateShowNowVn->lessThan($noShowEndAt);
        $canConfirmLateArrivalNow = $usesLateArrivalNoShowPolicy
            && $booking->status == 'confirmed'
            && !$booking->actual_check_in
            && $noShowEndAt
            && $lateShowNowVn->greaterThanOrEqualTo($noShowEndAt)
            && (!$lateShowCheckOutAt || $lateShowNowVn->lessThan($lateShowCheckOutAt));
        $canHandleNoShowNow = $canNoShowNow || $canConfirmLateArrivalNow;
        $autoOpenLateArrivalPanel = $canHandleNoShowNow;

        $lateArrivalOneNightTotal = (float) $booking->bookingRooms->sum(function ($bookingRoom) {
            return (float) $bookingRoom->price_at_booking;
        });
        if ($lateArrivalOneNightTotal <= 0) {
            $lateArrivalOneNightTotal = (float) ($booking->roomCategory->price ?? 0)
                * max(1, (int) $booking->room_quantity);
        }

        $disableCheckInSubmitNow = $isBeforeBookingDateNow || $lateShowIsPastStayTime || $lateShowIsCheckInTooLate;

        $lateShowHours = 0;
        $lateShowMinutes = 0;
        $lateShowDurationText = '';
        $showLateCheckInWarning = false;
        $lateShowAlertClass = 'alert-info';
        $lateShowTitle = '';
        $lateShowMessage = '';
        $lateShowSubMessage = '';

        if (in_array($booking->status, ['pending', 'confirmed'], true) && !$booking->actual_check_in && $isBeforeBookingDateNow) {
            $showLateCheckInWarning = true;
            $lateShowAlertClass = 'alert-danger';
            $lateShowTitle = 'Chưa đến ngày nhận phòng';
            $lateShowMessage = 'Khách chưa thể nhận phòng theo lịch hiện tại. Nếu khách muốn nhận ngay, hãy đổi ngày lưu trú và kiểm tra lại phòng trống.';
            $lateShowSubMessage = 'Lịch nhận phòng của đơn: '
                . ($lateShowCheckInAt ? $lateShowCheckInAt->format('d/m/Y H:i') : '---')
                . '. Thời điểm hiện tại: '
                . $lateShowNowVn->format('d/m/Y H:i')
                . '.';
        } elseif (
            $booking->status == 'confirmed'
            && $lateShowCheckInAt
            && $lateShowNowVn->greaterThan($lateShowCheckInAt)
        ) {
            $lateShowMinutes = (int) round($lateShowCheckInAt->diffInSeconds($lateShowNowVn) / 60);
            $lateShowHours = round($lateShowMinutes / 60, 2);
            $lateShowWholeHours = intdiv($lateShowMinutes, 60);
            $lateShowRemainMinutes = $lateShowMinutes % 60;
            $lateShowDurationText = $lateShowWholeHours > 0
                ? $lateShowWholeHours . ' giờ' . ($lateShowRemainMinutes > 0 ? ' ' . $lateShowRemainMinutes . ' phút' : '')
                : $lateShowMinutes . ' phút';

            if ($lateShowIsPastStayTime) {
                $showLateCheckInWarning = true;
                $lateShowAlertClass = 'alert-danger';
                $lateShowTitle = 'Đơn đã quá thời gian lưu trú';
                $lateShowMessage = 'Đơn này đã qua giờ trả phòng nên không thể nhận phòng.';
                $lateShowSubMessage = 'Nếu khách vẫn muốn ở, hãy tạo đơn mới theo thời gian thực tế.';
            } elseif ($usesLateArrivalNoShowPolicy) {
                $showLateCheckInWarning = true;

                if (!$lateShowIsAfterNoShowLimit) {
                    $lateShowAlertClass = 'alert-warning';
                    $lateShowTitle = 'Khách đến muộn nhưng phòng vẫn đang được giữ';
                    $lateShowMessage = 'Khách vẫn có thể nhận phòng. Nếu dự kiến đến sau giờ giữ phòng, hãy ghi nhận giờ đến mới.';
                    $lateShowSubMessage = 'Hạn giữ phòng: '
                        . ($lateShowNoShowLimitAt ? $lateShowNoShowLimitAt->format('d/m/Y H:i') : '---')
                        . '.';
                } else {
                    $lateShowAlertClass = 'alert-danger';
                    $lateShowTitle = 'Đã quá thời gian giữ phòng';
                    $lateShowMessage = 'Không thể nhận phòng bằng đơn quá hạn.';
                    $lateShowSubMessage = 'Phòng được giữ đến: '
                        . ($lateShowNoShowLimitAt ? $lateShowNoShowLimitAt->format('d/m/Y H:i') : '---')
                        . '. Khoản cọc không được hoàn lại.';
                }
            }
        }
    ?>

    <style>
        .booking-detail-page {
            --border: #e5e7eb;
            --muted: #64748b;
            --soft: #f8fafc;
            --ink: #111827;
            --gold: #d4af37;
        }

        .page-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .page-title h2 {
            font-size: 24px;
            font-weight: 900;
            margin: 0;
            color: var(--ink);
        }

        .page-title p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .booking-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px;
            gap: 18px;
            align-items: start;
        }

        @media (max-width: 1199px) {
            .booking-shell {
                grid-template-columns: 1fr;
            }
        }

        .card-clean {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.035);
        }

        .card-title-clean {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .card-title-clean h5,
        .card-title-clean h6 {
            margin: 0;
            color: var(--ink);
            font-weight: 900;
        }

        .card-subtitle-clean {
            color: var(--muted);
            font-size: 13px;
            margin: 3px 0 0;
        }

        .main-stack,
        .side-stack {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .side-stack {
            position: sticky;
            top: 86px;
            align-self: start;
            height: max-content;
            overflow: visible;
        }

        @media (max-width: 1199px) {
            .side-stack {
                position: static;
            }
        }

        .hero-clean {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .hero-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .booking-code-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .booking-code-value {
            font-size: 28px;
            font-weight: 950;
            color: var(--ink);
            line-height: 1.1;
        }

        .badge-clean {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .status-pending {
            color: #854d0e;
            background: #fef3c7;
            border-color: #fde68a;
        }

        .status-confirmed {
            color: #1d4ed8;
            background: #dbeafe;
            border-color: #bfdbfe;
        }

        .status-checked-in {
            color: #0f766e;
            background: #ccfbf1;
            border-color: #99f6e4;
        }

        .status-warning {
            color: #92400e;
            background: #ffedd5;
            border-color: #fed7aa;
        }

        .status-late {
            color: #6b21a8;
            background: #f3e8ff;
            border-color: #d8b4fe;
        }

        .status-done {
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .status-cancelled {
            color: #991b1b;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .status-muted {
            color: #475569;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }

        .status-info {
            color: #0369a1;
            background: #e0f2fe;
            border-color: #bae6fd;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        @media (max-width: 991px) {
            .metric-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575px) {
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }

        .metric-card {
            background: var(--soft);
            border: 1px solid #eef2f7;
            border-radius: 14px;
            padding: 12px;
        }

        .metric-card span {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .metric-card strong {
            display: block;
            color: var(--ink);
            font-size: 15px;
            line-height: 1.35;
        }

        .operation-list {
            display: grid;
            gap: 10px;
        }

        .operation-row {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px;
            background: #fff;
        }

        .operation-row-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .operation-row-title {
            font-weight: 900;
            color: var(--ink);
        }

        .soft-note {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px 12px;
            color: #475569;
            font-size: 13px;
        }

        details.compact-panel {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
        }

        details.compact-panel summary {
            cursor: pointer;
            list-style: none;
            padding: 13px 14px;
            font-weight: 900;
            color: var(--ink);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        details.compact-panel summary::-webkit-details-marker {
            display: none;
        }

        details.compact-panel summary::after {
            content: '+';
            color: var(--muted);
            font-size: 18px;
        }

        details.compact-panel[open] summary::after {
            content: '–';
        }

        .compact-panel-body {
            border-top: 1px solid var(--border);
            padding: 14px;
            background: #fff;
        }

        .form-mini-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        @media (max-width: 991px) {
            .form-mini-grid {
                grid-template-columns: 1fr;
            }
        }

        .mini-form-box {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
            background: var(--soft);
            height: 100%;
        }

        .mini-form-box h6 {
            font-weight: 900;
            margin-bottom: 12px;
            color: var(--ink);
        }

        .info-list {
            display: grid;
            gap: 0;
        }

        .info-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #eef2f7;
            font-size: 14px;
        }

        .info-line:last-child {
            border-bottom: 0;
        }

        .info-label {
            color: var(--muted);
        }

        .info-value {
            font-weight: 800;
            color: var(--ink);
            text-align: right;
        }

        .payment-summary-note {
            color: var(--muted);
            font-size: 13px;
            margin-top: -6px;
            margin-bottom: 8px;
        }

        .payment-kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .payment-kpi {
            min-width: 0;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 11px;
            background: #f8fafc;
        }

        button.payment-kpi {
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
        }

        button.payment-kpi:hover,
        button.payment-kpi:focus-visible {
            border-color: #cbd5e1;
            background: #f1f5f9;
            outline: none;
        }

        .payment-kpi-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.3;
        }

        .payment-kpi-value {
            display: block;
            margin-top: 4px;
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.25;
        }

        .payment-components-details,
        .history-details {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }

        .payment-components-details > summary,
        .history-details > summary {
            list-style: none;
            cursor: pointer;
            padding: 10px 12px;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .payment-components-details > summary::-webkit-details-marker,
        .history-details > summary::-webkit-details-marker {
            display: none;
        }

        .payment-components-details > summary::after,
        .history-details > summary::after {
            content: '＋';
            float: right;
            color: #64748b;
        }

        .payment-components-details[open] > summary::after,
        .history-details[open] > summary::after {
            content: '−';
        }

        .payment-components-details .info-list {
            border-top: 1px solid #eef2f7;
            padding: 0 12px 8px;
        }

        .history-details .log-box {
            border-top: 1px solid #eef2f7;
            padding: 12px;
        }

        .payment-summary-section {
            margin: 8px 0 2px;
            padding: 8px 0 5px;
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            border-bottom: 1px solid #dbe3ee;
        }

        button.payment-detail-trigger {
            width: 100%;
            border: 0;
            border-bottom: 1px solid #eef2f7;
            background: transparent;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
        }

        button.payment-detail-trigger:hover,
        button.payment-detail-trigger:focus-visible {
            background: #f8fafc;
            outline: none;
        }

        button.payment-detail-trigger .info-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        button.payment-detail-trigger .info-label::after {
            content: '›';
            color: #3b82f6;
            font-size: 18px;
            line-height: 1;
        }

        .payment-total-highlight {
            margin-top: 4px;
            padding: 11px 10px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0 !important;
        }

        .payment-breakdown-offcanvas {
            width: min(520px, 94vw) !important;
        }

        .payment-breakdown-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 11px 12px;
            margin-bottom: 10px;
            background: #fff;
        }

        .payment-breakdown-item:last-child {
            margin-bottom: 0;
        }

        .payment-breakdown-formula {
            color: #475569;
            font-size: 13px;
            margin-top: 4px;
        }

        .payment-breakdown-total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 2px solid #e2e8f0;
            font-weight: 900;
        }

        .room-pill-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .room-pill {
            border: 1px solid var(--border);
            background: var(--soft);
            border-radius: 999px;
            padding: 7px 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 800;
            color: var(--ink);
        }

        .table-clean {
            font-size: 14px;
        }

        .table-clean th {
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .02em;
            white-space: nowrap;
        }

        .table-clean td {
            vertical-align: middle;
        }

        .log-box {
            max-height: 360px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .log-item {
            border-left: 3px solid var(--gold);
            padding: 0 0 12px 12px;
            margin-bottom: 12px;
        }

        .log-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-divider {
            height: 1px;
            background: var(--border);
            margin: 14px 0;
        }

        .action-policy-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-top: 10px;
        }

        @media (max-width: 991px) {
            .action-policy-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575px) {
            .action-policy-grid {
                grid-template-columns: 1fr;
            }
        }

        .policy-chip {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 12px;
            padding: 9px 10px;
            font-size: 12px;
        }

        .policy-chip strong {
            display: block;
            color: var(--ink);
            font-size: 13px;
        }

        .compact-alert {
            border-radius: 14px;
            padding: 12px 14px;
        }

        .action-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        @media (max-width: 991px) {
            .action-summary {
                grid-template-columns: 1fr;
            }
        }

        .action-summary-item {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--soft);
            padding: 10px 12px;
        }

        .action-summary-item span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 3px;
        }

        .action-summary-item strong {
            color: var(--ink);
            font-size: 14px;
        }

        .promotion-list {
            display: grid;
            gap: 10px;
        }

        .promotion-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px;
            background: #fff;
        }

        .promotion-code {
            font-weight: 800;
            letter-spacing: 0.03em;
            color: var(--ink);
        }

        .promotion-meta {
            color: var(--muted);
            font-size: 12px;
        }

        .promotion-summary-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 14px;
            margin-top: 8px;
        }

        .birth-date-picker {
            display: grid;
            grid-template-columns: minmax(74px, .8fr) minmax(108px, 1.1fr) minmax(96px, 1fr);
            gap: 8px;
        }

        .birth-date-picker .form-select {
            min-height: 44px;
            padding-left: 12px;
            padding-right: 34px;
            border-color: #dbe3ed;
            background-color: #fff;
            color: var(--ink);
            font-weight: 700;
        }

        .birth-date-picker .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .12);
        }

        .birth-date-field .form-text {
            margin-top: 6px;
            color: var(--muted);
        }

        @media (max-width: 575px) {
            .birth-date-picker {
                grid-template-columns: 1fr;
            }
        }

        .reception-compact .secondary-booking-card { display:none; }
        .reception-compact.show-secondary .secondary-booking-card { display:block; }
        .reception-compact .customer-request-card { border-left-width:4px !important; }
        .reception-compact .booking-shell { grid-template-columns:minmax(0,1fr) 340px; align-items:start; }
        .reception-compact .operation-row .text-muted.small { line-height:1.35; }
        .reception-compact .side-stack { align-self:start; display:flex; flex-direction:column; gap:18px; }
        .room-selection-request-highlight {
            border:1px solid #bfdbfe;
            background:#eff6ff;
            border-radius:12px;
            padding:11px 13px;
            color:#1e293b;
            line-height:1.45;
        }
        .room-selection-request-highlight strong { color:#1d4ed8; }
        @media (max-width:1199px) {
            .reception-compact .booking-shell { grid-template-columns:1fr; }
        }
        .reception-compact:not(.show-secondary) .side-stack .secondary-booking-card { display:none; }
        .reception-compact .primary-operation-card { border-top:3px solid #2563eb; }
        .compact-toggle-bar { position:sticky; top:72px; z-index:20; display:flex; justify-content:flex-end; margin-bottom:12px; pointer-events:none; }
        .compact-toggle-bar button { pointer-events:auto; box-shadow:0 8px 24px rgba(15,23,42,.12); }

    </style>

    <div class="admin-wrapper booking-detail-page <?php echo e($isReceptionDesk ? 'reception-compact' : ''); ?>" id="bookingDetailRoot" data-workspace-mode="<?php echo e($workspaceMode ?? 'main'); ?>">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
                <a href="<?php echo e(route('admin.bookings.index')); ?>">Đặt phòng</a> /
                Chi tiết
            </p>

            <div class="page-topbar">
                <div class="page-title">
                    <h2>Chi tiết đặt phòng</h2>
                    <p>Thông tin chính, tình trạng hiện tại và các thao tác cần thiết của đơn.</p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if($isReceptionDesk): ?>
                        <button type="button" class="btn btn-outline-secondary" id="toggleSecondaryBookingInfo">
                            <i class="bx bx-layer me-1"></i> Xem thông tin bổ sung
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo e(route('admin.bookings.index')); ?>" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>
            </div>

<?php if($errors->any()): ?>
                <?php
                    $uniqueFormErrors = collect($errors->all())->filter()->unique()->values();
                ?>
                <div class="alert alert-danger">
                    <strong>Vui lòng kiểm tra lại:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $uniqueFormErrors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>


            <?php if($latestCancellationRequest && $latestCancellationRequest->status === 'pending'): ?>
                <section class="card-clean mb-3 customer-request-card" style="border: 1px solid #f59e0b; background: #fffbeb;">
                    <div class="card-title-clean">
                        <div>
                            <h5 class="text-warning-emphasis">Khách đang yêu cầu hủy đơn</h5>
                            <p class="card-subtitle-clean mb-0">
                                Gửi lúc <?php echo e(optional($latestCancellationRequest->requested_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>.
                                Chỉ khi lễ tân xác nhận thì booking mới bị hủy và phòng mới được mở bán lại.
                            </p>
                        </div>
                        <span class="badge-clean" style="background:#fef3c7;color:#92400e;">Chờ xác nhận</span>
                    </div>

                    <?php if($latestCancellationRequest->reason): ?>
                        <div class="soft-note mb-3">
                            <strong>Lý do khách gửi:</strong> <?php echo e($latestCancellationRequest->reason); ?>

                        </div>
                    <?php endif; ?>

                    <?php
                        $requestPolicy = $latestCancellationRequest->policy_snapshot ?? [];
                    ?>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><div class="soft-note h-100"><span class="text-muted small">Tiền cọc đã thanh toán</span><div class="fw-bold"><?php echo e(number_format($requestPolicy['paid_amount'] ?? 0, 0, ',', '.')); ?>đ</div></div></div>
                        <div class="col-md-6"><div class="soft-note h-100"><span class="text-muted small">Xử lý khi hủy</span><div class="fw-bold text-danger">Mất toàn bộ tiền cọc, không hoàn lại</div></div></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <form action="<?php echo e(route('admin.bookings.cancellation-request.approve', $booking)); ?>" method="POST"
                                onsubmit="return confirm('Xác nhận hủy đơn và mở bán lại phòng ngay?')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <label class="form-label fw-semibold">Ghi chú xác nhận <span class="text-muted fw-normal">(không bắt buộc)</span></label>
                                <textarea name="review_note" class="form-control mb-2" rows="2" maxlength="1000" placeholder="Thông tin đã trao đổi với khách..."></textarea>
                                <button class="btn btn-danger w-100" type="submit">
                                    <i class="bx bx-check-circle me-1"></i> Xác nhận hủy và mở bán phòng
                                </button>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <form action="<?php echo e(route('admin.bookings.cancellation-request.reject', $booking)); ?>" method="POST"
                                onsubmit="return confirm('Từ chối yêu cầu hủy này? Đơn sẽ tiếp tục được giữ.')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <label class="form-label fw-semibold">Lý do từ chối</label>
                                <textarea name="review_note" class="form-control mb-2" rows="2" maxlength="1000" required placeholder="Ví dụ: khách xác nhận tiếp tục lưu trú..."></textarea>
                                <button class="btn btn-outline-secondary w-100" type="submit">
                                    <i class="bx bx-x-circle me-1"></i> Từ chối yêu cầu hủy
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            <?php elseif($latestCancellationRequest && $latestCancellationRequest->status === 'rejected'): ?>
                <div class="alert alert-secondary mb-3">
                    Yêu cầu hủy gần nhất đã bị từ chối
                    <?php echo e(optional($latestCancellationRequest->reviewed_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>.
                    <?php echo e($latestCancellationRequest->review_note); ?>

                </div>
            <?php endif; ?>


            <?php if($latestRoomIssueRequest): ?>
                <?php
                    $roomIssueGroup = $booking->roomIssueRequests->where('group_uuid', $latestRoomIssueRequest->group_uuid)->sortBy('id');
                    $issueRepairCompleted = $roomIssueGroup->isNotEmpty() && $roomIssueGroup->every(fn($i) => $i->repair_status === 'completed');
                    $issueDisplayStatus = $issueRepairCompleted ? 'repair_completed' : $latestRoomIssueRequest->status;
                    $issueStatusLabels = [
                        'pending' => match($latestRoomIssueRequest->workflow_status) {
                            'waiting_guest_confirmation' => 'Chờ trao đổi với khách',
                            'guest_accepted' => 'Khách đã đồng ý',
                            'guest_requested_change' => 'Khách yêu cầu đổi phương án',
                            default => 'Chờ quản lý lập phương án',
                        },
                        'approved' => 'Đã xử lý đổi phòng',
                        'repair_only' => 'Đang khắc phục',
                        'repair_completed' => 'Đã sửa xong',
                        'rejected' => 'Đã từ chối',
                    ];
                    $issueResolutionLabels = [
                        'same_category' => 'Đổi phòng cùng hạng',
                        'upgrade_category' => 'Đổi hạng phòng miễn phí',
                        'no_room' => 'Giữ nguyên phòng và sửa gấp',
                    ];
                ?>
                <details class="compact-panel mb-3 customer-request-card" id="room-issue-admin">
                    <summary>
                        <span>
                            Sự cố phòng khách đã báo
                            <span class="text-muted fw-normal small ms-2">
                                Phòng <?php echo e($latestRoomIssueRequest->currentRoom?->room_number ?? '---'); ?> · <?php echo e(\Illuminate\Support\Str::limit($latestRoomIssueRequest->issue_description, 48)); ?>

                            </span>
                        </span>
                        <span class="badge-clean <?php echo e($latestRoomIssueRequest->status === 'pending' ? 'status-warning' : 'status-done'); ?>">
                            <?php echo e($issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus); ?>

                        </span>
                    </summary>
                    <div class="compact-panel-body">
                        <div class="soft-note mb-3"><strong>Khách báo:</strong> <?php echo e($latestRoomIssueRequest->issue_description); ?></div>

                        <?php if($latestRoomIssueRequest->status === 'pending'): ?>
                            <div class="alert alert-warning small mb-3">
                                Yêu cầu đang chờ quản lý duyệt tại menu <strong>Sự cố phòng</strong>. Lễ tân không cần tự đổi phòng trong khung này.
                            </div>
                        <?php else: ?>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4"><div class="soft-note h-100"><span class="text-muted small">Phương án đã duyệt</span><div class="fw-bold"><?php echo e($issueResolutionLabels[$latestRoomIssueRequest->resolution_type] ?? '---'); ?></div></div></div>
                                <div class="col-md-4"><div class="soft-note h-100"><span class="text-muted small">Phòng mới</span><div class="fw-bold"><?php echo e($latestRoomIssueRequest->approvedRoom?->room_number ?? 'Giữ phòng cũ'); ?></div></div></div>
                                <div class="col-md-4"><div class="soft-note h-100"><span class="text-muted small">Mã bù đắp</span><div class="fw-bold"><?php echo e(collect($latestRoomIssueRequest->promotion_codes)->implode(', ') ?: 'Không áp dụng'); ?></div></div></div>
                            </div>
                            <div class="soft-note"><strong>Quản lý:</strong> <?php echo e($latestRoomIssueRequest->admin_note); ?></div>
                        <?php endif; ?>

                        <?php if($latestRoomIssueRequest->status === 'pending' && $latestRoomIssueRequest->workflow_status === 'waiting_guest_confirmation'): ?>
                            <a href="<?php echo e(route('admin.bookings.room-issue-proposal', $booking)); ?>" class="btn btn-primary w-100 mt-3">
                                <i class="bx bx-conversation me-1"></i> Xem phương án để trao đổi với khách
                            </a>
                        <?php endif; ?>

                        <button type="button" class="btn btn-outline-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#adminRoomIssueDetailModal">
                            <i class="bx bx-detail me-1"></i> Xem chi tiết sự cố
                        </button>
                    </div>
                </details>

                <div class="modal fade" id="adminRoomIssueDetailModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="modal-header border-0 bg-light px-4 py-3">
                                <div>
                                    <h5 class="modal-title fw-bold mb-1">Chi tiết sự cố phòng</h5>
                                    <div class="small text-muted">Booking <?php echo e($booking->booking_code); ?> · Phòng <?php echo e($latestRoomIssueRequest->currentRoom?->room_number ?? '---'); ?></div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4"><div class="soft-note h-100"><span class="small text-muted d-block">Trạng thái</span><strong><?php echo e($issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus); ?></strong></div></div>
                                    <div class="col-md-4"><div class="soft-note h-100"><span class="small text-muted d-block">Phương án</span><strong><?php echo e($issueResolutionLabels[$latestRoomIssueRequest->resolution_type] ?? 'Chưa có phương án'); ?></strong></div></div>
                                    <div class="col-md-4"><div class="soft-note h-100"><span class="small text-muted d-block">Phòng mới</span><strong><?php echo e($latestRoomIssueRequest->approvedRoom?->room_number ?? 'Không có'); ?></strong></div></div>
                                </div>
                                <div class="soft-note mb-3"><span class="small text-muted d-block mb-1">Khách báo</span><strong><?php echo e($latestRoomIssueRequest->issue_description); ?></strong></div>
                                <?php if($latestRoomIssueRequest->admin_note): ?>
                                    <div class="soft-note mb-3"><span class="small text-muted d-block mb-1">Phản hồi xử lý sự cố</span><?php echo e($latestRoomIssueRequest->admin_note); ?></div>
                                <?php endif; ?>
                                <?php if($issueRepairCompleted): ?>
                                    <div class="alert alert-success mb-0"><strong>Đã sửa xong</strong><?php if($latestRoomIssueRequest->repair_completed_at): ?> · <?php echo e($latestRoomIssueRequest->repair_completed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?><?php endif; ?> <?php if($latestRoomIssueRequest->repair_note): ?><div class="mt-1"><?php echo e($latestRoomIssueRequest->repair_note); ?></div><?php endif; ?></div>
                                <?php elseif($latestRoomIssueRequest->repair_status === 'waiting'): ?>
                                    <div class="alert alert-info mb-0">Buồng phòng đang khắc phục sự cố.</div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                <?php if(in_array(auth()->user()->role ?? null, ['super_admin', 'manager'], true)): ?>
                                    <a href="<?php echo e(route('admin.room-issues.show', $latestRoomIssueRequest)); ?>" class="btn btn-outline-secondary">Mở trang xử lý sự cố</a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>


            <section class="card-clean mb-3" id="bookingSupportActions">
                <?php if($isBeforeBookingDateNow): ?>
                    <div class="alert alert-danger compact-alert border-2 mb-3" role="alert">
                        <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Chưa đến ngày nhận phòng</div>
                        <div class="small">
                            Đơn dự kiến nhận phòng lúc <strong><?php echo e($lateShowCheckInAt?->format('d/m/Y H:i') ?? '---'); ?></strong>.
                            Hiện tại là <strong><?php echo e($lateShowNowVn->format('d/m/Y H:i')); ?></strong>.
                        </div>
                        <div class="small mt-1">Nếu khách muốn nhận ngay, hãy đổi ngày lưu trú và kiểm tra lại phòng trống trước khi xác nhận.</div>
                    </div>
                <?php elseif($showLateCheckInWarning): ?>
                    <div class="alert <?php echo e($lateShowAlertClass); ?> compact-alert border-2 mb-3" role="alert">
                        <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i><?php echo e($lateShowTitle); ?></div>
                        <div class="small"><?php echo e($lateShowMessage); ?></div>
                        <?php if($lateShowSubMessage): ?>
                            <div class="small text-muted mt-1"><?php echo e($lateShowSubMessage); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="booking-summary-inline mb-3" style="display:block!important;visibility:visible!important;opacity:1!important;">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <div class="booking-code-label">Mã đơn</div>
                            <div class="booking-code-value"><?php echo e($booking->booking_code); ?></div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge-clean <?php echo e($bookingStatusClass); ?>"><?php echo e($bookingStatusLabels[$booking->status] ?? $booking->status); ?></span>
                            <?php if($isLateCheckout): ?>
                                <span class="badge-clean status-late">Trả muộn · <?php echo e($lateCheckoutText); ?></span>
                            <?php endif; ?>
                            <span class="badge-clean <?php echo e($paymentStatusClass); ?>"><?php echo e($paymentStatusLabels[$effectivePaymentStatus] ?? $effectivePaymentStatus); ?></span>
                        </div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-card"><span>Khách hàng</span><strong><?php echo e($customerName); ?></strong></div>
                        <div class="metric-card"><span>Thời gian lưu trú</span><strong><?php echo e($lateShowCheckInAt?->format('d/m/Y H:i') ?? '---'); ?><br>→ <?php echo e($lateShowCheckOutAt?->format('d/m/Y H:i') ?? '---'); ?></strong></div>
                        <div class="metric-card"><span>Hạng phòng khách đặt</span><strong><?php echo e($booking->roomCategory->name ?? 'Không xác định'); ?><br><span class="text-muted small"><?php echo e($booking->room_quantity); ?> phòng · <?php echo e($booking->adult_count); ?> NL / <?php echo e($booking->child_count); ?> TE · Sức chứa <?php echo e($currentAdultCapacity); ?> NL / <?php echo e($currentChildCapacity); ?> TE</span></strong></div>
                        <div class="metric-card"><span>Còn lại cần thu</span><strong class="text-danger fs-5"><?php echo e(number_format($remainingTotal, 0, ',', '.')); ?>đ</strong></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 booking-secondary-actions">
                    <?php if($canManageBookingRooms): ?>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roomAdjustmentModal">
                            <i class="bx bx-transfer-alt me-1"></i> Điều chỉnh phòng
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#specialWorkflowModal">
                        <i class="bx bx-dots-horizontal-rounded me-1"></i> Tình huống đặc biệt
                    </button>
                </div>
            </section>

            <div class="modal fade" id="roomAdjustmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold">Điều chỉnh phòng</h5>
                                <div class="small text-muted">
                                    <?php if($booking->bookingRooms->count() <= 1): ?>
                                        Thêm phòng, đổi phòng hoặc đổi hạng phòng hiện tại.
                                    <?php else: ?>
                                        Thêm phòng, đổi một phòng hoặc đổi hạng nhiều phòng/toàn bộ booking.
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="roomAdjustmentModalBody">
                            <div class="text-muted">Đang tải biểu mẫu…</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="specialWorkflowModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Tình huống đặc biệt</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item px-0">
                                    <strong>Đến sau giờ G</strong>
                                    <?php
                                        $canOfferLateArrivalRequest = in_array($booking->status, ['pending', 'confirmed'], true)
                                            && empty($booking->actual_check_in);
                                        $hasPendingLateArrivalRequest = $canOfferLateArrivalRequest
                                            ? $booking->customerRequests()
                                                ->where('type', 'late_arrival')
                                                ->where('status', 'pending')
                                                ->exists()
                                            : false;
                                    ?>
                                    <?php if(!$canOfferLateArrivalRequest): ?>
                                        <div class="small text-success mt-1">
                                            <i class="bx bx-check-circle me-1"></i>Khách đã đến/đã nhận phòng nên không còn áp dụng biểu mẫu đến muộn.
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-muted mb-2">Khách online gửi trên website; khách không đăng nhập nhận biểu mẫu qua email. Quản lý duyệt cuối.</div>
                                        <?php if(filled($booking->booked_customer_email)): ?>
                                            <form method="POST" action="<?php echo e(route('admin.bookings.send-customer-request-form', $booking)); ?>" class="d-flex gap-2 flex-wrap">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="type" value="late_arrival">
                                                <input type="email" name="email" class="form-control" value="<?php echo e($booking->booked_customer_email); ?>" style="max-width:360px" required <?php if($hasPendingLateArrivalRequest): echo 'disabled'; endif; ?>>
                                                <button class="btn btn-outline-warning" type="submit" <?php if($hasPendingLateArrivalRequest): echo 'disabled'; endif; ?>>Gửi form đến muộn</button>
                                            </form>
                                            <?php if($hasPendingLateArrivalRequest): ?>
                                                <div class="small text-warning mt-2">Khách đã gửi yêu cầu và đang chờ xử lý. Xử lý xong mới được gửi form mới.</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="small text-muted">Booking chưa có email để gửi biểu mẫu.</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="list-group-item px-0">
                                    <strong>Báo cáo sự cố</strong>
                                    <?php if($booking->status === 'checked_in' && $booking->actual_check_in): ?>
                                        <?php if($canSendRoomIssueForm): ?>
                                            <form action="<?php echo e(route('admin.bookings.send-room-issue-form', $booking)); ?>" method="POST"
                                                class="mt-2"
                                                onsubmit="return confirm('Gửi biểu mẫu báo sự cố tới email đang nhập?')">
                                                <?php echo csrf_field(); ?>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <input type="email" name="recipient_email" class="form-control" required maxlength="255"
                                                        value="<?php echo e(old('recipient_email', $roomIssueFormEmail)); ?>"
                                                        placeholder="Email nhận biểu mẫu" style="max-width:360px">
                                                    <button type="submit" class="btn btn-outline-primary">
                                                        <i class="bx bx-envelope me-1"></i> Gửi form sự cố
                                                    </button>
                                                </div>
                                                <?php $__errorArgs = ['recipient_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <div class="text-danger small mt-2"><?php echo e($message); ?></div>
                                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            </form>
                                        <?php else: ?>
                                            <div class="small text-muted mt-1">Chưa thể gửi thêm biểu mẫu.</div>
                                        <?php endif; ?>
                                    <?php elseif($latestRoomIssueRequest): ?>
                                        <a href="#room-issue-admin" class="btn btn-sm btn-outline-primary mt-2"
                                            data-bs-dismiss="modal">Xem yêu cầu hiện tại</a>
                                    <?php else: ?>
                                        <div class="small text-muted mt-1">Chỉ khả dụng khi khách đang lưu trú.</div>
                                    <?php endif; ?>
                                </div>
                                <div class="list-group-item px-0">
                                    <strong>Yêu cầu hủy phòng</strong>
                                    <?php
                                        $adminCancelDate = \Carbon\Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh');
                                        $adminDirectCancelCutoff = $adminCancelDate->copy()->setTime(14, 0, 0);
                                        $canAdminCancelBooking = in_array($booking->status, ['pending', 'confirmed'], true)
                                            && !$booking->actual_check_in
                                            && now('Asia/Ho_Chi_Minh')->lt($adminDirectCancelCutoff);
                                    ?>
                                    <?php if($canAdminCancelBooking): ?>
                                        <div class="mt-2">
                                            <form method="POST" action="<?php echo e(route('admin.bookings.cancel', $booking)); ?>"
                                                onsubmit="return confirm('Gửi mã xác nhận hủy về email khách?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bx bx-envelope me-1"></i> Gửi mã xác nhận hủy
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif($latestCancellationRequest): ?>
                                        <div class="small text-muted mt-1">
                                            Trạng thái: <?php echo e($latestCancellationRequest->status ?? 'Đang xử lý'); ?>

                                        </div>
                                    <?php else: ?>
                                        <div class="small text-muted mt-1">Không khả dụng ở trạng thái hiện tại.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="booking-shell">
                <div class="main-stack">
                    <section class="card-clean primary-operation-card">
                        <div class="card-title-clean">
                            <div>
                                <h5><?php echo e($isReceptionDesk ? 'Việc cần làm' : 'Thao tác chính'); ?></h5>
                            </div>
                            <span class="badge-clean <?php echo e($bookingStatusClass); ?>">
                                <?php echo e($bookingStatusLabels[$booking->status] ?? $booking->status); ?>

                            </span>
                        </div>

                        <div class="operation-list">
                            <?php if($booking->status == 'confirmed'): ?>
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Nhận phòng thực tế</div>
                                        </div>
                                    </div>

                                    <?php if($roomsNotReadyForCheckIn->count() > 0): ?>
                                        <div class="alert alert-warning small mb-3">
                                            <div class="fw-bold mb-1">Phòng gán hiện tại chưa sẵn sàng</div>
                                            <?php echo e($notReadyRoomText); ?>.
                                        </div>
                                    <?php endif; ?>

                                    <?php if($canRequestPriorityCleaning): ?>
                                        <form action="<?php echo e(route('admin.bookings.priority-cleaning', $booking->id)); ?>" method="POST"
                                            class="mb-3"
                                            onsubmit="return confirm('Gửi yêu cầu buồng phòng ưu tiên dọn nhanh cho đơn này?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>

                                            <div class="soft-note">
                                                <div class="fw-bold mb-1">Khách đến sớm trong khung <?php echo e($earlyFreeFromTimePolicy); ?>–<?php echo e($standardCheckInTimePolicy); ?></div>
                                                <?php echo e($priorityCleaningRoomText); ?>. Có thể gửi yêu cầu ưu tiên dọn nhanh để khách được
                                                nhận phòng sớm khi phòng sẵn sàng.
                                                <button type="submit" class="btn btn-outline-warning btn-sm w-100 mt-2">
                                                    <i class="bx bx-bell me-1"></i>
                                                    Yêu cầu buồng phòng ưu tiên dọn
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <?php if($isBeforeBookingDateNow): ?>
                                        <details class="compact-panel mb-3" open>
                                            <summary>Đổi ngày nhận và ngày trả trước khi nhận phòng</summary>
                                            <div class="compact-panel-body">
                                                <form action="<?php echo e(route('admin.bookings.change-stay-dates', $booking->id)); ?>"
                                                    method="POST"
                                                    onsubmit="return confirm('Xác nhận đổi ngày lưu trú và tính lại tiền phòng?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>

                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Ngày nhận mới</label>
                                                            <input type="date" id="newCheckInDateVn" name="new_check_in_date" class="form-control"
                                                                min="<?php echo e($nowVnForCheckInFlow->toDateString()); ?>"
                                                                data-paired-checkout="new_check_out_date"
                                                                data-checkout-min-days="1"
                                                                value="<?php echo e(old('new_check_in_date', $stayDateChangeCheckInDateDefault)); ?>"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Giờ nhận mới</label>
                                                            <input type="time" id="newCheckInTimeVn" name="new_check_in_time" class="form-control"
                                                                value="<?php echo e(old('new_check_in_time', $stayDateChangeCheckInTimeDefault)); ?>"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Ngày trả mới</label>
                                                            <input type="date" id="newCheckOutDateVn" name="new_check_out_date" class="form-control"
                                                                min="<?php echo e($nowVnForCheckInFlow->copy()->addDay()->toDateString()); ?>"
                                                                value="<?php echo e(old('new_check_out_date', $stayDateChangeCheckOutDateDefault)); ?>"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Giờ trả mới</label>
                                                            <input type="time" id="newCheckOutTimeVn" name="new_check_out_time" class="form-control"
                                                                value="<?php echo e(old('new_check_out_time', $stayDateChangeCheckOutTimeDefault)); ?>"
                                                                required>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="btn btn-outline-primary w-100 mt-3">
                                                        Kiểm tra trùng phòng và đổi ngày lưu trú
                                                    </button>
                                                </form>

                                                <?php if(
                                                    is_array($stayDateRepricePreview)
                                                    && (int) ($stayDateRepricePreview['booking_id'] ?? 0) === (int) $booking->id
                                                ): ?>
                                                    <?php
                                                        $repriceOld = $stayDateRepricePreview['old'] ?? [];
                                                        $repriceNew = $stayDateRepricePreview['new'] ?? [];
                                                        $repriceServices = $stayDateRepricePreview['service_preview']['lines'] ?? [];
                                                        $repriceRemovedPromotions = $stayDateRepricePreview['promotion_preview']['removed'] ?? [];
                                                        $repriceKeptPromotions = $stayDateRepricePreview['promotion_preview']['kept'] ?? [];
                                                    ?>

                                                    <div class="border border-primary rounded-3 p-3 mt-3 bg-white shadow-sm" id="stay-date-reprice-preview">
                                                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                                                            <div>
                                                                <div class="fw-bold text-primary fs-5">Xem trước tiền trước khi đổi lịch</div>
                                                                <div class="small text-muted">
                                                                    Chưa cập nhật booking. Kiểm tra lại lịch, hạng, dịch vụ, mã ưu đãi, cọc và tiền khách đã trả rồi mới xác nhận.
                                                                </div>
                                                            </div>
                                                            <span class="badge bg-primary align-self-start">
                                                                <?php echo e($stayDateRepricePreview['period']['text'] ?? 'Lịch mới'); ?>

                                                            </span>
                                                        </div>

                                                        <div class="table-responsive">
                                                            <table class="table table-sm align-middle mb-3">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Nội dung</th>
                                                                        <th class="text-end">Booking hiện tại</th>
                                                                        <th class="text-end">Sau khi đổi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>Hạng phòng</td>
                                                                        <td class="text-end"><?php echo e($booking->roomCategory->name ?? '---'); ?></td>
                                                                        <td class="text-end fw-semibold"><?php echo e($stayDateRepricePreview['target_category_name'] ?? ($booking->roomCategory->name ?? '---')); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Số đêm</td>
                                                                        <td class="text-end"><?php echo e($repriceOld['night_count'] ?? 0); ?></td>
                                                                        <td class="text-end fw-semibold"><?php echo e($repriceNew['night_count'] ?? 0); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tiền phòng</td>
                                                                        <td class="text-end"><?php echo e(number_format((float) ($repriceOld['room_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                        <td class="text-end fw-semibold"><?php echo e(number_format((float) ($repriceNew['room_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Dịch vụ / phụ thu đã xác nhận</td>
                                                                        <td class="text-end"><?php echo e(number_format((float) ($repriceOld['service_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                        <td class="text-end fw-semibold"><?php echo e(number_format((float) ($repriceNew['service_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Mã giảm giá / hỗ trợ</td>
                                                                        <td class="text-end text-success">-<?php echo e(number_format((float) ($repriceOld['discount_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                        <td class="text-end text-success fw-semibold">-<?php echo e(number_format((float) ($repriceNew['discount_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    </tr>
                                                                    <tr class="table-primary">
                                                                        <td class="fw-bold">Tổng cần thanh toán</td>
                                                                        <td class="text-end fw-bold"><?php echo e(number_format((float) ($repriceOld['total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                        <td class="text-end fw-bold"><?php echo e(number_format((float) ($repriceNew['total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Khách đã thanh toán</td>
                                                                        <td class="text-end" colspan="2"><?php echo e(number_format((float) ($stayDateRepricePreview['paid_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Mức cọc yêu cầu hiện hành</td>
                                                                        <td class="text-end"><?php echo e(number_format((float) ($repriceOld['required_deposit'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                        <td class="text-end fw-semibold"><?php echo e(number_format((float) ($repriceNew['required_deposit'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Còn phải thu</td>
                                                                        <td class="text-end" colspan="2">
                                                                            <strong class="text-danger"><?php echo e(number_format((float) ($repriceNew['remaining'] ?? 0), 0, ',', '.')); ?>đ</strong>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tiền trả trước còn dư để bù trừ</td>
                                                                        <td class="text-end" colspan="2">
                                                                            <strong class="<?php echo e(($repriceNew['overpayment'] ?? 0) > 0 ? 'text-warning' : ''); ?>">
                                                                                <?php echo e(number_format((float) ($repriceNew['overpayment'] ?? 0), 0, ',', '.')); ?>đ
                                                                            </strong>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <?php if(collect($repriceServices)->contains(fn ($item) => !empty($item['will_reprice']) || !empty($item['will_remove']))): ?>
                                                            <div class="alert alert-info py-2 small">
                                                                <div class="fw-bold mb-1">Dịch vụ được tính lại theo lịch mới</div>
                                                                <?php $__currentLoopData = $repriceServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceLine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <?php if(!empty($serviceLine['will_reprice']) || !empty($serviceLine['will_remove'])): ?>
                                                                        <div class="mb-1">
                                                                            <strong><?php echo e($serviceLine['name'] ?? 'Dịch vụ'); ?></strong>
                                                                            (<?php echo e($serviceLine['billing_rule_label'] ?? 'Một lần'); ?>):
                                                                            <?php echo e(number_format((float) ($serviceLine['old_total'] ?? 0), 0, ',', '.')); ?>đ
                                                                            →
                                                                            <?php echo e(number_format((float) ($serviceLine['new_total'] ?? 0), 0, ',', '.')); ?>đ.
                                                                            <span class="text-muted"><?php echo e($serviceLine['new_formula'] ?? ''); ?></span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if(count($repriceRemovedPromotions) > 0): ?>
                                                            <div class="alert alert-warning py-2 small">
                                                                <div class="fw-bold mb-1">Mã sẽ bị gỡ vì lịch/hạng/tổng mới không còn đủ điều kiện</div>
                                                                <?php $__currentLoopData = $repriceRemovedPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $removedPromotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <div>
                                                                        <strong><?php echo e($removedPromotion['code'] ?? '---'); ?></strong>:
                                                                        <?php echo e($removedPromotion['reason'] ?? 'Không còn đủ điều kiện.'); ?>

                                                                    </div>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if(count($repriceKeptPromotions) > 0): ?>
                                                            <div class="small text-muted mb-3">
                                                                Mã còn hiệu lực:
                                                                <strong><?php echo e(collect($repriceKeptPromotions)->pluck('code')->implode(', ')); ?></strong>.
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if(($repriceNew['deposit_shortfall'] ?? 0) > 0): ?>
                                                            <div class="alert alert-danger py-2 small">
                                                                Sau khi đổi, khách còn thiếu
                                                                <strong><?php echo e(number_format((float) $repriceNew['deposit_shortfall'], 0, ',', '.')); ?>đ</strong>
                                                                để đủ mức cọc mới trước khi check-in.
                                                            </div>
                                                        <?php endif; ?>

                                                        <form action="<?php echo e(route('admin.bookings.change-stay-dates', $booking->id)); ?>" method="POST"
                                                            onsubmit="return confirm('Xác nhận cập nhật lịch, phòng/hạng, dịch vụ, mã ưu đãi và số tiền theo bảng xem trước?')">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('PATCH'); ?>
                                                            <input type="hidden" name="new_check_in_date" value="<?php echo e($stayDateRepricePreview['new_check_in_date'] ?? ''); ?>">
                                                            <input type="hidden" name="new_check_in_time" value="<?php echo e($stayDateRepricePreview['new_check_in_time'] ?? ''); ?>">
                                                            <input type="hidden" name="new_check_out_date" value="<?php echo e($stayDateRepricePreview['new_check_out_date'] ?? ''); ?>">
                                                            <input type="hidden" name="new_check_out_time" value="<?php echo e($stayDateRepricePreview['new_check_out_time'] ?? ''); ?>">
                                                            <input type="hidden" name="replacement_room_category_id" value="<?php echo e($stayDateRepricePreview['replacement_room_category_id'] ?? ''); ?>">
                                                            <input type="hidden" name="confirm_reprice" value="1">

                                                            <div class="d-flex flex-column flex-md-row gap-2">
                                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                                    <i class="bx bx-check-circle me-1"></i>
                                                                    Xác nhận đổi lịch và tính lại toàn bộ
                                                                </button>
                                                                <button type="submit"
                                                                    class="btn btn-outline-secondary"
                                                                    form="discard-stay-date-preview-form">
                                                                    Bỏ bản xem trước
                                                                </button>
                                                            </div>
                                                        </form>
                                                        <form id="discard-stay-date-preview-form"
                                                            action="<?php echo e(route('admin.bookings.change-stay-dates.discard-preview', $booking)); ?>"
                                                            method="POST" class="d-none">
                                                            <?php echo csrf_field(); ?>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if(
                                                    is_array($stayDateCategoryOptions)
                                                    && (int) ($stayDateCategoryOptions['booking_id'] ?? 0) === (int) $booking->id
                                                ): ?>
                                                    <div class="alert alert-warning mt-3 mb-3">
                                                        <div class="fw-bold mb-1">
                                                            Không còn đủ phòng cùng hạng trong lịch mới
                                                        </div>
                                                        <div class="small">
                                                            <?php echo e($stayDateCategoryOptions['reason'] ?? ''); ?>

                                                        </div>
                                                        <div class="small mt-2">
                                                            Lịch đang kiểm tra:
                                                            <strong><?php echo e($stayDateCategoryOptions['period_text'] ?? '---'); ?></strong>.
                                                            Booking cần
                                                            <strong><?php echo e($stayDateCategoryOptions['room_quantity'] ?? $booking->room_quantity); ?> phòng</strong>.
                                                            Chỉ các hạng còn đủ số phòng trong đúng khung thời gian này mới được hiển thị.
                                                        </div>
                                                    </div>

                                                    <div class="border rounded-3 p-2 bg-light">
                                                        <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-1">
                                                            <div>
                                                                <div class="fw-bold">Chọn hạng phòng thay thế theo lịch mới</div>
                                                            </div>
                                                            <span class="badge bg-primary">
                                                                <?php echo e(count($stayDateCategoryOptions['options'] ?? [])); ?> hạng còn đủ phòng
                                                            </span>
                                                        </div>

                                                        <div class="mt-2" style="max-height: 330px; overflow-y: auto;">
                                                            <?php $__currentLoopData = ($stayDateCategoryOptions['options'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <form
                                                                    action="<?php echo e(route('admin.bookings.change-stay-dates', $booking->id)); ?>"
                                                                    method="POST"
                                                                    class="bg-white border rounded-3 p-3 mb-2"
                                                                    onsubmit="return confirm('Xác nhận đổi lịch và chuyển toàn bộ booking sang hạng đã chọn?')">
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('PATCH'); ?>

                                                                    <input type="hidden" name="new_check_in_date"
                                                                        value="<?php echo e($stayDateCategoryOptions['new_check_in_date'] ?? ''); ?>">
                                                                    <input type="hidden" name="new_check_in_time"
                                                                        value="<?php echo e($stayDateCategoryOptions['new_check_in_time'] ?? ''); ?>">
                                                                    <input type="hidden" name="new_check_out_date"
                                                                        value="<?php echo e($stayDateCategoryOptions['new_check_out_date'] ?? ''); ?>">
                                                                    <input type="hidden" name="new_check_out_time"
                                                                        value="<?php echo e($stayDateCategoryOptions['new_check_out_time'] ?? ''); ?>">
                                                                    <input type="hidden" name="replacement_room_category_id"
                                                                        value="<?php echo e($categoryOption['category_id'] ?? ''); ?>">

                                                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                                                        <div class="flex-grow-1">
                                                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                                                <span class="fw-bold fs-6">
                                                                                    <?php echo e($categoryOption['category_name'] ?? 'Hạng phòng'); ?>

                                                                                </span>
                                                                                <?php if(!empty($categoryOption['is_current_category'])): ?>
                                                                                    <span class="badge bg-secondary">Hạng hiện tại</span>
                                                                                <?php endif; ?>
                                                                            </div>


                                                                            <div class="small mt-1">
                                                                                <?php echo e($categoryOption['price_text'] ?? ''); ?>

                                                                                · Sức chứa tổng:
                                                                                <?php echo e($categoryOption['adult_capacity'] ?? 0); ?> người lớn /
                                                                                <?php echo e($categoryOption['child_capacity'] ?? 0); ?> trẻ em
                                                                            </div>

                                                                            <?php
                                                                                $newRoomTotal = (float) ($categoryOption['new_room_total'] ?? 0);
                                                                                $roomDifference = (float) ($categoryOption['difference'] ?? 0);
                                                                                $currentRoomTotal = max(0, $newRoomTotal - $roomDifference);
                                                                            ?>
                                                                            <div class="small mt-2 border rounded-3 p-2 bg-light">
                                                                                <div class="d-flex justify-content-between gap-3">
                                                                                    <span class="text-muted">Tiền phòng hiện tại:</span>
                                                                                    <strong><?php echo e(number_format($currentRoomTotal, 0, ',', '.')); ?>đ</strong>
                                                                                </div>
                                                                                <div class="d-flex justify-content-between gap-3 mt-1">
                                                                                    <span class="text-muted">Tiền phòng sau khi đổi lịch/hạng:</span>
                                                                                    <strong><?php echo e($categoryOption['new_room_total_text'] ?? '0đ'); ?></strong>
                                                                                </div>
                                                                                <div class="d-flex justify-content-between gap-3 mt-1 pt-1 border-top">
                                                                                    <?php if($roomDifference > 0): ?>
                                                                                        <span class="text-danger">Tiền phòng tăng thêm:</span>
                                                                                        <strong class="text-danger"><?php echo e(number_format($roomDifference, 0, ',', '.')); ?>đ</strong>
                                                                                    <?php elseif($roomDifference < 0): ?>
                                                                                        <span class="text-success">Tiền phòng được giảm:</span>
                                                                                        <strong class="text-success"><?php echo e(number_format(abs($roomDifference), 0, ',', '.')); ?>đ</strong>
                                                                                    <?php else: ?>
                                                                                        <span class="text-muted">Tiền phòng không thay đổi:</span>
                                                                                        <strong>0đ</strong>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <div class="text-muted mt-1" style="font-size: .74rem;">
                                                                                    Đây mới là chênh lệch tiền phòng; tổng cuối còn được tính lại với dịch vụ, phụ thu, khuyến mãi và số tiền khách đã thanh toán.
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <button type="submit" class="btn btn-primary flex-shrink-0">
                                                                            <i class="bx bx-transfer-alt me-1"></i>
                                                                            Chọn hạng này và đổi lịch
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </div>

                                                        <div class="small text-muted px-2 pt-1">
                                                            Không chọn hạng nào thì booking vẫn giữ nguyên lịch và phòng cũ. Có thể sửa lại
                                                            ngày ở phía trên rồi kiểm tra lại.
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>

                                    <?php
                                        $checkInRoomCapacityStates = $booking->bookingRooms->map(function ($bookingRoom) use ($booking) {
                                            $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
                                            $adultCount = $roomGuests->where('guest_type', 'adult')->count();
                                            $minorCount = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();
                                            $adultCapacity = (int) ($bookingRoom->room?->category?->adult_capacity ?? 0);
                                            $childCapacity = (int) ($bookingRoom->room?->category?->child_capacity ?? 0);

                                            return [
                                                'booking_room_id' => (int) $bookingRoom->id,
                                                'room_number' => $bookingRoom->room?->room_number ?? '---',
                                                'adult_count' => $adultCount,
                                                'minor_count' => $minorCount,
                                                'adult_capacity' => $adultCapacity,
                                                'child_capacity' => $childCapacity,
                                                'adult_over' => max(0, $adultCount - $adultCapacity),
                                                'minor_over' => max(0, $minorCount - $childCapacity),
                                                'adult_spare' => max(0, $adultCapacity - $adultCount),
                                                'minor_spare' => max(0, $childCapacity - $minorCount),
                                                'adult_guests' => $roomGuests->where('guest_type', 'adult')->values(),
                                                'minor_guests' => $roomGuests->whereIn('guest_type', ['child', 'infant'])->values(),
                                            ];
                                        })->values();

                                        $perRoomOverCapacityForCheckIn = $checkInRoomCapacityStates
                                            ->contains(fn ($state) => $state['adult_over'] > 0 || $state['minor_over'] > 0);

                                        $virtualRoomStates = $checkInRoomCapacityStates->map(fn ($state) => $state)->all();
                                        $capacityMoveSuggestions = [];
                                        $unresolvedAdultOver = 0;
                                        $unresolvedMinorOver = 0;

                                        foreach ($virtualRoomStates as $sourceIndex => $sourceState) {
                                            for ($move = 0; $move < $sourceState['adult_over']; $move++) {
                                                $destinationIndex = collect($virtualRoomStates)->search(
                                                    fn ($candidate, $index) => $index !== $sourceIndex && $candidate['adult_spare'] > 0
                                                );

                                                if ($destinationIndex === false) {
                                                    $unresolvedAdultOver++;
                                                    continue;
                                                }

                                                $guest = $sourceState['adult_guests']->reverse()->values()->get($move);
                                                $capacityMoveSuggestions[] = [
                                                    'guest_name' => $guest?->full_name,
                                                    'guest_type' => 'người lớn',
                                                    'from_room' => $sourceState['room_number'],
                                                    'to_room' => $virtualRoomStates[$destinationIndex]['room_number'],
                                                ];
                                                $virtualRoomStates[$destinationIndex]['adult_spare']--;
                                            }

                                            for ($move = 0; $move < $sourceState['minor_over']; $move++) {
                                                $destinationIndex = collect($virtualRoomStates)->search(
                                                    fn ($candidate, $index) => $index !== $sourceIndex && $candidate['minor_spare'] > 0
                                                );

                                                if ($destinationIndex === false) {
                                                    $unresolvedMinorOver++;
                                                    continue;
                                                }

                                                $guest = $sourceState['minor_guests']->reverse()->values()->get($move);
                                                $capacityMoveSuggestions[] = [
                                                    'guest_name' => $guest?->full_name,
                                                    'guest_type' => 'trẻ em/em bé',
                                                    'from_room' => $sourceState['room_number'],
                                                    'to_room' => $virtualRoomStates[$destinationIndex]['room_number'],
                                                ];
                                                $virtualRoomStates[$destinationIndex]['minor_spare']--;
                                            }
                                        }

                                        $canResolveCapacityByMovingGuests = $perRoomOverCapacityForCheckIn
                                            && $unresolvedAdultOver === 0
                                            && $unresolvedMinorOver === 0
                                            && count($capacityMoveSuggestions) > 0;

                                        $capacityMoveSuggestionGroups = collect($capacityMoveSuggestions)
                                            ->groupBy(function ($suggestion) {
                                                return $suggestion['guest_type'] . '|' . $suggestion['from_room'] . '|' . $suggestion['to_room'];
                                            })
                                            ->map(function ($suggestions) {
                                                $first = $suggestions->first();

                                                return [
                                                    'count' => $suggestions->count(),
                                                    'guest_type' => $first['guest_type'],
                                                    'from_room' => $first['from_room'],
                                                    'to_room' => $first['to_room'],
                                                ];
                                            })
                                            ->values();

                                        $initialActualAdults = (int) $booking->guests->where('guest_type', 'adult')->count();
                                        $initialActualChildren = (int) $booking->guests->where('guest_type', 'child')->count();
                                        $initialActualBabies = (int) $booking->guests->where('guest_type', 'infant')->count();
                                        $initialAggregateOverCapacity = $initialActualAdults > $currentAdultCapacity
                                            || ($initialActualChildren + $initialActualBabies) > $currentChildCapacity;
                                        $initialAnyOverCapacity = $initialAggregateOverCapacity || $perRoomOverCapacityForCheckIn;
                                    ?>

                                    <form action="<?php echo e(route('admin.bookings.check-in', $booking->id)); ?>" method="POST" enctype="multipart/form-data"
                                        id="checkInForm">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>

                                        <input type="hidden" id="adultCapacity" value="<?php echo e($currentAdultCapacity); ?>">
                                        <input type="hidden" id="childCapacity" value="<?php echo e($currentChildCapacity); ?>">
                                        <input type="hidden" id="perRoomOverCapacity" value="<?php echo e($perRoomOverCapacityForCheckIn ? 1 : 0); ?>">

                                        <input type="hidden" name="early_check_in_action" id="earlyCheckInAction" value="">
                                        <input type="hidden" id="earlyCheckInIsActive" value="<?php echo e($isEarlyCheckInNow ? 1 : 0); ?>">
                                        <input type="hidden" id="earlyCheckInFeeAmount" value="<?php echo e($earlyCheckInFeePreview); ?>">
                                        <input type="hidden" id="earlyCheckInPercent" value="<?php echo e($earlyCheckInPercent); ?>">
                                        <input type="hidden" id="earlyCheckInBasePrice" value="<?php echo e($earlyCheckInBasePrice); ?>">
                                        <input type="hidden" id="earlyCheckInPolicyText" value="<?php echo e($earlyCheckInPolicyText); ?>">
                                        <input type="hidden" id="earlyCheckInNowText"
                                            value="<?php echo e($nowVnForCheckInFlow->format('d/m/Y H:i')); ?>">
                                        <input type="hidden" id="earlyCheckInStandardText"
                                            value="<?php echo e($standardCheckInAt?->format('d/m/Y H:i')); ?>">
                                        <input type="hidden" id="earlyCheckInDurationText" value="<?php echo e($earlyCheckInDurationText); ?>">
                                        <input type="hidden" id="earlyCheckInFinalTotalPreview" value="<?php echo e($earlyCheckInFinalTotalPreview); ?>">

                                        <?php
                                            $checkInDeclaredAdults = $booking->guests->where('guest_type', 'adult')->count();
                                            $checkInDeclaredChildren = $booking->guests->where('guest_type', 'child')->count();
                                            $checkInDeclaredInfants = $booking->guests->where('guest_type', 'infant')->count();
                                            $hasRepresentative = $booking->guests->where('is_booking_representative', true)->count() === 1;
                                        ?>
                                        <?php if($booking->guests->isEmpty() || !$hasRepresentative): ?>
                                            <div class="alert alert-warning small mb-3">
                                                <strong>Chưa đủ thông tin khách lưu trú.</strong>
                                                Hiện có <?php echo e($checkInDeclaredAdults); ?> người lớn / <?php echo e($checkInDeclaredChildren); ?> trẻ em / <?php echo e($checkInDeclaredInfants); ?> em bé.
                                                Hãy khai báo đủ khách, gán phòng và chọn một người đại diện trước khi nhận phòng.
                                                <a href="#stayingGuestsPanel" class="alert-link">Khai báo ngay</a>.
                                            </div>
                                        <?php else: ?>
                                            <div class="small text-muted mb-3">
                                                Đã khai báo <?php echo e($checkInDeclaredAdults); ?> người lớn / <?php echo e($checkInDeclaredChildren); ?> trẻ em / <?php echo e($checkInDeclaredInfants); ?> em bé và đã chọn người đại diện.
                                            </div>
                                        <?php endif; ?>

                                        <div class="border rounded p-3 mb-3 bg-light">
                                            <div class="fw-semibold mb-2">Thông tin người làm thủ tục nhận phòng</div>
                                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                                <button type="button" id="checkInCccdImageButton" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('checkInCccdImage').click()">
                                                    <i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh
                                                </button>
                                            </div>
                                            <input type="file" name="cccd_image" id="checkInCccdImage" class="d-none js-cccd-image"
                                                accept="image/jpeg,image/png,image/webp"
                                                data-button="#checkInCccdImageButton" data-status="#checkInCccdStatus"
                                                data-target-cccd="#checkInCccd" data-target-full-name="#checkInScannedFullName"
                                                data-target-birthday="#checkInScannedBirthday" data-target-gender="#checkInScannedGender"
                                                data-target-address="#checkInScannedAddress" data-target-nationality="#checkInScannedNationality"
                                                data-required-fields="cccd,full_name,birthday,gender,nationality,address">
                                            <small id="checkInCccdStatus" class="text-muted d-block mb-3"></small>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small">Họ tên người làm thủ tục (tùy chọn)</label>
                                                    <input type="text" name="scanned_full_name" id="checkInScannedFullName" class="form-control"
                                                        value="<?php echo e(old('scanned_full_name', $booking->guests->firstWhere('is_booking_representative', true)?->full_name ?? $booking->booked_customer_name)); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Số giấy tờ người làm thủ tục (tùy chọn)</label>
                                                    <input type="text" name="check_in_cccd" id="checkInCccd" class="form-control" maxlength="20"
                                                        value="<?php echo e(old('check_in_cccd', $booking->booked_customer_cccd)); ?>"
                                                        data-booking-cccd="<?php echo e($booking->booked_customer_cccd); ?>">
                                                </div>
                                                <div class="col-md-6 birth-date-field">
                                                    <label class="form-label small">Ngày sinh</label>
                                                    <input type="date" name="scanned_birthday" id="checkInScannedBirthday"
                                                        class="form-control" min="1900-01-01"
                                                        max="<?php echo e(now('Asia/Ho_Chi_Minh')->toDateString()); ?>" data-birth-date
                                                        value="<?php echo e(old('scanned_birthday', $booking->booked_customer_birthday ? \Carbon\Carbon::parse($booking->booked_customer_birthday)->format('Y-m-d') : '')); ?>">
                                                    <div class="form-text">Ngày sinh không được nằm trong tương lai.</div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Giới tính</label>
                                                    <select name="scanned_gender" id="checkInScannedGender" class="form-select">
                                                        <option value="">-- Chọn --</option>
                                                        <option value="male" <?php echo e(old('scanned_gender', $booking->booked_customer_gender) === 'male' ? 'selected' : ''); ?>>Nam</option>
                                                        <option value="female" <?php echo e(old('scanned_gender', $booking->booked_customer_gender) === 'female' ? 'selected' : ''); ?>>Nữ</option>
                                                        <option value="other" <?php echo e(old('scanned_gender', $booking->booked_customer_gender) === 'other' ? 'selected' : ''); ?>>Khác</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Quốc tịch</label>
                                                    <input type="text" name="guest_nationality" id="checkInScannedNationality" class="form-control" value="<?php echo e(old('guest_nationality', 'Việt Nam')); ?>">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small">Địa chỉ</label>
                                                    <textarea name="scanned_address" id="checkInScannedAddress" class="form-control" rows="2"><?php echo e(old('scanned_address', $booking->booked_customer_address)); ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-12">
                                                <div class="soft-note mb-0">
                                                    <strong>Số khách thực tế được lấy tự động từ danh sách khách lưu trú bên dưới.</strong>
                                                    Thêm, sửa hoặc xóa khách xong trang sẽ tự cập nhật; lễ tân không cần nhập lại số lượng.
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">Người lớn đã khai</label>
                                                <input type="number" name="actual_adult_count" id="actualAdultCount"
                                                    class="form-control bg-light" value="<?php echo e($checkInDeclaredAdults); ?>" readonly>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label small">Trẻ em đã khai</label>
                                                <input type="number" name="actual_child_count" id="actualChildCount"
                                                    class="form-control bg-light" value="<?php echo e($checkInDeclaredChildren); ?>" readonly>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label small">Em bé đã khai</label>
                                                <input type="number" name="actual_baby_count" id="actualBabyCount"
                                                    class="form-control bg-light" value="<?php echo e($checkInDeclaredInfants); ?>" readonly>
                                            </div>
                                        </div>

                                        <div id="normalCheckInBox" class="action-summary mb-3 <?php echo e($initialAnyOverCapacity ? 'd-none' : ''); ?>">
                                            <div class="action-summary-item">
                                                <span>Sức chứa tổng</span>
                                                <strong><?php echo e($currentAdultCapacity); ?> NL / <?php echo e($currentChildCapacity); ?> TE/EB</strong>
                                            </div>
                                            <div class="action-summary-item">
                                                <span>Phân bổ từng phòng</span>
                                                <strong>
                                                    <?php $__currentLoopData = $checkInRoomCapacityStates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $state): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="badge <?php echo e(($state['adult_over'] > 0 || $state['minor_over'] > 0) ? 'text-bg-danger' : 'text-bg-success'); ?> me-1 mb-1">
                                                            P.<?php echo e($state['room_number']); ?>:
                                                            <?php echo e($state['adult_count']); ?>/<?php echo e($state['adult_capacity']); ?> NL ·
                                                            <?php echo e($state['minor_count']); ?>/<?php echo e($state['child_capacity']); ?> TE/EB
                                                        </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </strong>
                                            </div>
                                            <div class="action-summary-item">
                                                <span>Khuyến nghị</span>
                                                <strong>Phân bổ khách theo phòng hợp lệ, có thể nhận phòng.</strong>
                                            </div>
                                        </div>

                                        <?php if($roomsNotReadyForCheckIn->isNotEmpty()): ?>
                                            <div class="alert alert-warning small mb-3">
                                                <strong>Phòng chưa sẵn sàng:</strong>
                                                <?php $__currentLoopData = $roomsNotReadyForCheckIn; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    Phòng <?php echo e($room->room_number); ?> đang <?php echo e(mb_strtolower($roomStatusLabels[$room->status] ?? $room->status)); ?><?php echo e(!$loop->last ? ', ' : '.'); ?>

                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if($isEarlyCheckInNow): ?>
                                            <div class="soft-note mb-3">
                                                Khách đang đến sớm khoảng <strong><?php echo e($earlyCheckInDurationText); ?></strong>.
                                            </div>
                                        <?php endif; ?>

                                        <div id="overCapacityBox" class="<?php echo e($initialAnyOverCapacity ? '' : 'd-none'); ?> mb-3">
                                            <div class="alert alert-danger small mb-2">
                                                <strong>Chưa thể nhận phòng theo phân bổ hiện tại.</strong>
                                                Tổng sức chứa có thể vẫn đủ, nhưng ít nhất một phòng đang vượt sức chứa riêng.
                                                <?php $__currentLoopData = $checkInRoomCapacityStates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $state): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if($state['adult_over'] > 0 || $state['minor_over'] > 0): ?>
                                                        <div class="mt-1">
                                                            Phòng <?php echo e($state['room_number']); ?>:
                                                            <?php echo e($state['adult_count']); ?>/<?php echo e($state['adult_capacity']); ?> người lớn,
                                                            <?php echo e($state['minor_count']); ?>/<?php echo e($state['child_capacity']); ?> trẻ em/em bé
                                                            <?php if($state['adult_over'] > 0): ?> · vượt <?php echo e($state['adult_over']); ?> người lớn <?php endif; ?>
                                                            <?php if($state['minor_over'] > 0): ?> · vượt <?php echo e($state['minor_over']); ?> trẻ em/em bé <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>

                                            <?php if($canResolveCapacityByMovingGuests): ?>
                                                <div class="alert alert-info small mb-2">
                                                    <strong>Phương án nên làm trước:</strong>
                                                    <?php $__currentLoopData = $capacityMoveSuggestionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <div>
                                                            Chuyển
                                                            <strong><?php echo e($suggestion['count']); ?> <?php echo e($suggestion['guest_type']); ?></strong>
                                                            từ phòng <?php echo e($suggestion['from_room']); ?>

                                                            sang phòng <?php echo e($suggestion['to_room']); ?>.
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    Sau khi chuyển, phân bổ sẽ nằm trong sức chứa và không cần phụ thu vượt sức chứa.
                                                    <div class="mt-2">
                                                        <a href="#stayingGuestsPanel" class="btn btn-sm btn-outline-primary"
                                                           onclick="const panel=document.getElementById('stayingGuestsPanel'); if(panel){panel.open=true;}">
                                                            Mở khai báo khách để chuyển phòng
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-warning small mb-2">
                                                    Không thể xử lý hết chỉ bằng cách chuyển khách giữa các phòng hiện có.
                                                    Lễ tân cần chọn <strong>thu phụ phí</strong>, hoặc dùng mục
                                                    <strong>Quản lý phòng</strong> để thêm phòng / đổi hạng toàn bộ phòng trước khi check-in.
                                                </div>
                                            <?php endif; ?>

                                            <label class="form-label">Cách xử lý nếu vẫn giữ phân bổ hiện tại</label>
                                            <select name="over_capacity_action" id="overCapacityAction"
                                                class="form-select mb-3">
                                                <option value="">-- Chọn cách xử lý --</option>
                                                <option value="extra_fee">Khách ở phòng hiện tại và thu phụ phí</option>
                                            </select>

                                            <div id="extraFeeBox" class="d-none border rounded p-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="fw-bold mb-0">Phụ thu khi nhận phòng</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        id="addExtraFeeRow">
                                                        + Thêm dòng
                                                    </button>
                                                </div>

                                                <div class="alert alert-info small mb-3">
                                                    Mỗi khoản phụ thu phải gắn đúng <strong>phòng đang vượt</strong> và đúng loại khách.
                                                </div>

                                                <div id="extraFeeRows">
                                                    <div class="extra-fee-row border rounded p-3 mb-3 bg-white">
                                                        <div class="row g-2 align-items-end">
                                                            <div class="col-md-3">
                                                                <label class="form-label small">Phòng bị vượt</label>
                                                                <select name="extra_booking_room_ids[]"
                                                                    class="form-select extra-room-select">
                                                                    <option value="">-- Chọn phòng --</option>
                                                                    <?php $__currentLoopData = $checkInRoomCapacityStates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $capacityState): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <?php if($capacityState['adult_over'] > 0 || $capacityState['minor_over'] > 0): ?>
                                                                            <option value="<?php echo e($capacityState['booking_room_id']); ?>"
                                                                                data-adult-over="<?php echo e($capacityState['adult_over']); ?>"
                                                                                data-minor-over="<?php echo e($capacityState['minor_over']); ?>">
                                                                                Phòng <?php echo e($capacityState['room_number']); ?>

                                                                                · vượt <?php echo e($capacityState['adult_over']); ?> NL / <?php echo e($capacityState['minor_over']); ?> TE/EB
                                                                            </option>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-2">
                                                                <label class="form-label small">Loại khách</label>
                                                                <select name="extra_guest_types[]" class="form-select extra-guest-type-select">
                                                                    <option value="">-- Chọn --</option>
                                                                    <option value="adult">Người lớn</option>
                                                                    <option value="minor">Trẻ em / em bé</option>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label class="form-label small">Loại phụ thu</label>
                                                                <select name="extra_service_ids[]"
                                                                    class="form-select extra-service-select">
                                                                    <option value="">-- Chọn phụ thu --</option>
                                                                    <?php $__currentLoopData = $extraGuestServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <option value="<?php echo e($service->id); ?>"
                                                                            data-price="<?php echo e($service->price); ?>"
                                                                            data-unit="<?php echo e($service->unit); ?>">
                                                                            <?php echo e($service->name); ?> -
                                                                            <?php echo e(number_format($service->price, 0, ',', '.')); ?>đ /
                                                                            <?php echo e($service->unit); ?>

                                                                        </option>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-1">
                                                                <label class="form-label small">SL vượt</label>
                                                                <input type="number" name="extra_quantities[]"
                                                                    class="form-control extra-quantity-input" value="1" min="1">
                                                            </div>

                                                            <div class="col-md-2">
                                                                <label class="form-label small">Tạm tính</label>
                                                                <input type="text" class="form-control extra-total-text"
                                                                    value="0đ" readonly>
                                                            </div>

                                                            <div class="col-md-1">
                                                                <button type="button"
                                                                    class="btn btn-outline-danger w-100 remove-extra-fee-row">Xóa</button>
                                                            </div>

                                                            <div class="col-md-12">
                                                                <label class="form-label small">Ghi chú</label>
                                                                <input type="text" name="extra_fee_notes[]" class="form-control"
                                                                    placeholder="Ví dụ: Phụ thu thêm người / vượt sức chứa">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="soft-note d-flex justify-content-between align-items-center">
                                                    <span>Tổng phụ thu</span>
                                                    <strong id="allExtraFeeTotalText">0đ</strong>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" id="checkInSubmitButton" class="btn btn-success w-100" disabled data-policy-disabled="<?php echo e($disableCheckInSubmitNow ? 1 : 0); ?>">
                                            <i class="bx bx-log-in-circle me-1"></i>
                                            <?php echo e($disableCheckInSubmitNow ? 'Chưa thể nhận phòng' : 'Xác nhận nhận phòng'); ?>

                                        </button>
                                    </form>

                                    <?php if($isEarlyCheckInNow): ?>
                                        <div class="modal fade" id="earlyCheckInConfirmModal" tabindex="-1"
                                            aria-labelledby="earlyCheckInConfirmModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 rounded-4 shadow">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h5 class="modal-title fw-bold" id="earlyCheckInConfirmModalLabel">
                                                                Báo giá check-in sớm
                                                            </h5>
                                                            <div class="text-muted small">Chỉ xác nhận khi khách đã đồng ý phụ thu.</div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="alert alert-warning small mb-3">
                                                            <div class="fw-bold mb-1">Khách đến sớm <?php echo e($earlyCheckInDurationText); ?></div>
                                                            Hiện tại: <strong><?php echo e($nowVnForCheckInFlow->format('d/m/Y H:i')); ?></strong><br>
                                                            Giờ check-in chuẩn: <strong><?php echo e($standardCheckInAt?->format('d/m/Y H:i')); ?></strong>
                                                        </div>

                                                        <div class="info-list">
                                                            <div class="info-line">
                                                                <span class="info-label">Chính sách áp dụng</span>
                                                                <span class="info-value"><?php echo e($earlyCheckInPolicyText); ?></span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Giá gốc tính phụ thu</span>
                                                                <span class="info-value"><?php echo e(number_format($earlyCheckInBasePrice, 0, ',', '.')); ?>đ</span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Tỷ lệ phụ thu</span>
                                                                <span class="info-value"><?php echo e($earlyCheckInPercent); ?>%</span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Phụ thu nhận phòng sớm</span>
                                                                <span class="info-value text-danger fs-5">
                                                                    <?php echo e(number_format($earlyCheckInFeePreview, 0, ',', '.')); ?>đ
                                                                </span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Tổng tiền sau khi cộng</span>
                                                                <span class="info-value text-danger">
                                                                    <?php echo e(number_format($earlyCheckInFinalTotalPreview, 0, ',', '.')); ?>đ
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                            Chưa đồng ý
                                                        </button>
                                                        <button type="button" class="btn btn-success" id="confirmEarlyCheckInSubmit">
                                                            Khách đồng ý - Tiếp tục check-in
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($canHandleNoShowNow): ?>
                                        <details class="compact-panel mt-3" <?php echo e($autoOpenLateArrivalPanel ? 'open' : ''); ?>>
                                            <summary>
                                                <span><?php echo e($isRescheduledAfterCutoff ? 'Đơn vừa đổi ngày nhận phòng' : 'Xử lý khách đến muộn'); ?></span>
                                                <span class="badge-clean <?php echo e($autoOpenLateArrivalPanel ? 'status-warning' : 'status-muted'); ?>">
                                                    <?php echo e($autoOpenLateArrivalPanel ? 'Cần chú ý' : 'Mở khi cần'); ?>

                                                </span>
                                            </summary>
                                            <div class="compact-panel-body bg-light">
                                            <div class="small text-muted mb-3">
                                                <?php if($isRescheduledAfterCutoff): ?>
                                                    Hạn check-in sau khi đổi lịch: <strong><?php echo e($lateShowNoShowLimitAt?->format('H:i d/m/Y')); ?></strong>.
                                                    Đơn này được chuyển từ ngày tương lai về hôm nay nên không bị coi là đến muộn tại mốc giờ G cũ.
                                                <?php else: ?>
                                                    Giờ G: <strong><?php echo e($lateShowNoShowLimitAt?->format('H:i d/m/Y')); ?></strong>.
                                                <?php endif; ?>
                                            </div>

                                            <?php if($booking->late_arrival_confirmed_at && (float) $booking->late_arrival_hours > 0): ?>
                                                <div class="alert alert-info py-2 small">
                                                    <div><strong>Đã xác nhận giữ phòng sau giờ G.</strong></div>
                                                    <div>Hạn giữ mới: <strong><?php echo e($lateShowNoShowLimitAt?->format('H:i d/m/Y')); ?></strong>.</div>
                                                    <div>Phụ thu đến muộn: <strong><?php echo e(number_format((float) $booking->late_arrival_fee, 0, ',', '.')); ?>đ</strong>.</div>
                                                    <div class="mt-1"><?php echo e($booking->late_arrival_policy); ?></div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="d-flex gap-2 flex-wrap align-items-start">
                                                <?php if($canConfirmLateArrivalNow): ?>
                                                    <form action="<?php echo e(route('admin.bookings.confirm-late-arrival', $booking->id)); ?>" method="POST"
                                                        class="flex-fill border rounded-3 p-3 bg-white" id="lateArrivalForm"
                                                        data-one-night-total="<?php echo e($lateArrivalOneNightTotal); ?>"
                                                        data-cutoff-at="<?php echo e($lateShowCheckInAt ? $setPolicyTime($lateShowCheckInAt, $lateArrivalCutoffTimePolicy)->format('Y-m-d H:i') : ''); ?>"
                                                        data-tier1-end="<?php echo e($lateArrivalTier1EndPolicy); ?>"
                                                        data-percent-1="<?php echo e($lateArrivalPercent1Policy); ?>"
                                                        data-percent-2="<?php echo e($lateArrivalPercent2Policy); ?>"
                                                        data-percent-next-day="<?php echo e($lateArrivalNextDayPercentPolicy); ?>"
                                                        data-grace-minutes="<?php echo e($lateArrivalGraceMinutesPolicy); ?>"
                                                        data-check-out-at="<?php echo e($lateShowCheckOutAt?->format('Y-m-d H:i')); ?>">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('PATCH'); ?>
                                                        <label class="form-label fw-semibold">Ngày giờ khách dự kiến đến sau giờ G</label>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-md-7">
                                                                <label class="form-label small text-muted mb-1">Ngày dự kiến đến</label>
                                                                <input type="date" name="expected_arrival_date" class="form-control" required
                                                                    value="<?php echo e(old('expected_arrival_date', optional($lateShowCheckInAt)->format('Y-m-d'))); ?>"
                                                                    min="<?php echo e(optional($lateShowCheckInAt)->format('Y-m-d')); ?>"
                                                                    max="<?php echo e(optional($lateShowCheckOutAt)->format('Y-m-d')); ?>">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label small text-muted mb-1">Giờ dự kiến đến</label>
                                                                <input type="text" name="expected_arrival_time" id="expectedArrivalTime" class="form-control" required
                                                                    value="<?php echo e(old('expected_arrival_time', \Carbon\Carbon::createFromFormat('H:i', $lateArrivalCutoffTimePolicy)->addMinutes($lateArrivalGraceMinutesPolicy)->format('H:i'))); ?>"
                                                                    placeholder="Ví dụ: 18:30" inputmode="numeric" autocomplete="off">
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-outline-primary w-100">
                                                            Xác nhận đến sau giờ G - giữ tiếp có phụ thu
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if($canNoShowNow && !$isRescheduledAfterCutoff): ?>
                                                    <form action="<?php echo e(route('admin.bookings.cancel-late-arrival', $booking->id)); ?>" method="POST" class="flex-fill"
                                                        onsubmit="return confirm('Xác nhận khách không đến? Đơn sẽ bị hủy và phòng được mở bán lại.')">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('PATCH'); ?>
                                                        <button type="submit" class="btn btn-outline-danger w-100">
                                                            Hủy no-show
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>

                                            <?php if($canConfirmLateArrivalNow): ?>
                                                <div class="modal fade" id="lateArrivalFeeModal" tabindex="-1"
                                                    aria-labelledby="lateArrivalFeeModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0 rounded-4 shadow">
                                                            <div class="modal-header">
                                                                <div>
                                                                    <h5 class="modal-title fw-bold" id="lateArrivalFeeModalLabel">
                                                                        Xác nhận phụ thu giữ phòng sau giờ G
                                                                    </h5>
                                                                    <div class="text-muted small">Kiểm tra lại trước khi ghi nhận khoản phụ thu vào đơn.</div>
                                                                </div>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="alert alert-warning small mb-3">
                                                                    <div class="fw-bold mb-1">Lý do phát sinh</div>
                                                                    Khách dự kiến đến sau giờ G <?php echo e($lateArrivalCutoffTimePolicy); ?> và yêu cầu khách sạn tiếp tục giữ phòng.
                                                                </div>
                                                                <div class="info-list">
                                                                    <div class="info-line">
                                                                        <span class="info-label">Giờ G</span>
                                                                        <span class="info-value" id="lateArrivalModalCutoff">---</span>
                                                                    </div>
                                                                    <div class="info-line">
                                                                        <span class="info-label">Khách dự kiến đến</span>
                                                                        <span class="info-value" id="lateArrivalModalExpected">---</span>
                                                                    </div>
                                                                    <div class="info-line">
                                                                        <span class="info-label">Hạn giữ phòng mới</span>
                                                                        <span class="info-value" id="lateArrivalModalHoldUntil">---</span>
                                                                    </div>
                                                                    <div class="info-line">
                                                                        <span class="info-label">Chính sách áp dụng</span>
                                                                        <span class="info-value" id="lateArrivalModalPolicy">---</span>
                                                                    </div>
                                                                    <div class="info-line">
                                                                        <span class="info-label">Giá 1 đêm dùng tính phí</span>
                                                                        <span class="info-value" id="lateArrivalModalBasePrice">---</span>
                                                                    </div>
                                                                    <div class="info-line">
                                                                        <span class="info-label">Cơ chế tính</span>
                                                                        <span class="info-value" id="lateArrivalModalFormula">---</span>
                                                                    </div>
                                                                    <div class="info-line">
                                                                        <span class="info-label">Phụ thu phát sinh</span>
                                                                        <span class="info-value text-danger fs-5" id="lateArrivalModalAmount">---</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                                    Chưa đồng ý
                                                                </button>
                                                                <button type="button" class="btn btn-primary" id="confirmLateArrivalFeeSubmit">
                                                                    Khách đồng ý - Ghi nhận phụ thu
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php elseif($booking->status == 'checked_in' && !$hasInspection): ?>
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Gia hạn lưu trú</div>
                                            <div class="text-muted small">Kiểm tra trước để tránh trùng lịch với booking mới.
                                            </div>
                                        </div>
                                    </div>

                                    <?php
                                        $extendTypeLabel = $booking->booking_type == 'hourly' ? 'Đặt theo giờ' : 'Đặt qua đêm';
                                        $currentRoomNumbersForExtend = $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ');
                                        $extendPreview = session('extend_stay_preview');
                                        $previewDateValue = old(
                                            'new_check_out_date',
                                            $extendPreview['new_check_out_date'] ?? ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : $booking->check_out_date)
                                        );
                                        $previewTimeValue = old(
                                            'new_check_out_time',
                                            $extendPreview['new_check_out_time'] ?? ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('H:i') : $standardCheckOutTimePolicy)
                                        );
                                    ?>

                                    <div class="soft-note mb-3">
                                        <strong><?php echo e($extendTypeLabel); ?></strong> · Phòng
                                        <?php echo e($currentRoomNumbersForExtend ?: '---'); ?> ·
                                        Check-out hiện tại:
                                        <?php echo e($lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '---'); ?>

                                    </div>

                                    <form action="<?php echo e(route('admin.bookings.extend-stay.preview', $booking->id)); ?>"
                                        method="POST">
                                        <?php echo csrf_field(); ?>

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Ngày trả phòng mới</label>
                                                <input type="date" id="newCheckOutDateVn" name="new_check_out_date" class="form-control"
                                                    min="<?php echo e($lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : date('Y-m-d')); ?>"
                                                    data-extension-min-date="<?php echo e($lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : date('Y-m-d')); ?>"
                                                    value="<?php echo e($previewDateValue); ?>" required>
                                                <div class="form-text">
                                                    Gia hạn chỉ được giữ nguyên ngày hiện tại với giờ trả muộn hơn,
                                                    hoặc chọn một ngày sau ngày trả hiện tại.
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Giờ trả phòng mới</label>
                                                <input type="text" name="new_check_out_time" id="extendCheckOutTime"
                                                    class="form-control" value="<?php echo e($previewTimeValue); ?>"
                                                    placeholder="Ví dụ: <?php echo e($standardCheckInTimePolicy); ?>" required>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-outline-primary w-100">
                                            <i class="bx bx-search-alt me-1"></i>
                                            Kiểm tra khả năng gia hạn
                                        </button>
                                    </form>

                                    <?php if($extendPreview): ?>
                                        <?php
                                            $previewAlertClass = 'alert-success';
                                            if (($extendPreview['status'] ?? '') === 'need_room_change') {
                                                $previewAlertClass = 'alert-warning';
                                            }
                                            if (($extendPreview['status'] ?? '') === 'blocked') {
                                                $previewAlertClass = 'alert-danger';
                                            }
                                        ?>

                                        <div class="alert <?php echo e($previewAlertClass); ?> mt-3 mb-0" id="extend-stay-preview">
                                            <h6 class="fw-bold mb-2"><?php echo e($extendPreview['title'] ?? 'Kết quả kiểm tra gia hạn'); ?>

                                            </h6>
                                            <div class="small mb-2">
                                                <strong>Khung giờ:</strong> <?php echo e($extendPreview['period_text'] ?? '---'); ?><br>
                                                <strong>Phí dự kiến:</strong>
                                                <span
                                                    class="fw-bold text-danger"><?php echo e($extendPreview['fee_text'] ?? '0đ'); ?></span><br>
                                                <strong>Cách tính:</strong> <?php echo e($extendPreview['policy_text'] ?? '---'); ?>

                                            </div>
                                            <div class="small"><?php echo e($extendPreview['message'] ?? ''); ?></div>

                                            <?php if(!empty($extendPreview['repricing'])): ?>
                                                <?php
                                                    $extendRepricing = $extendPreview['repricing'];
                                                    $extendRepriceOld = $extendRepricing['old'] ?? [];
                                                    $extendRepriceNew = $extendRepricing['new'] ?? [];
                                                    $extendServiceChanges = collect($extendRepricing['service_preview']['lines'] ?? [])
                                                        ->filter(fn ($line) => !empty($line['will_reprice']) || !empty($line['will_remove']));
                                                    $extendRemovedPromotions = $extendRepricing['promotion_preview']['removed'] ?? [];
                                                ?>
                                                <div class="border rounded bg-white p-2 mt-2 small">
                                                    <strong>Tính lại toàn bộ khi gia hạn thêm đêm:</strong>
                                                    <div class="table-responsive mt-2">
                                                        <table class="table table-sm table-bordered mb-2">
                                                            <thead>
                                                                <tr>
                                                                    <th>Nội dung</th>
                                                                    <th class="text-end">Hiện tại</th>
                                                                    <th class="text-end">Sau gia hạn</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td>Số đêm</td>
                                                                    <td class="text-end"><?php echo e($extendRepriceOld['night_count'] ?? 0); ?></td>
                                                                    <td class="text-end fw-semibold"><?php echo e($extendRepriceNew['night_count'] ?? 0); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Tiền phòng</td>
                                                                    <td class="text-end"><?php echo e(number_format((float) ($extendRepriceOld['room_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    <td class="text-end"><?php echo e(number_format((float) ($extendRepriceNew['room_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Dịch vụ / phụ thu</td>
                                                                    <td class="text-end"><?php echo e(number_format((float) ($extendRepriceOld['service_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    <td class="text-end"><?php echo e(number_format((float) ($extendRepriceNew['service_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Khuyến mãi / hỗ trợ</td>
                                                                    <td class="text-end">-<?php echo e(number_format((float) ($extendRepriceOld['discount_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    <td class="text-end">-<?php echo e(number_format((float) ($extendRepriceNew['discount_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                                <tr class="table-primary">
                                                                    <td class="fw-bold">Tổng cần thanh toán</td>
                                                                    <td class="text-end fw-bold"><?php echo e(number_format((float) ($extendRepriceOld['total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                    <td class="text-end fw-bold"><?php echo e(number_format((float) ($extendRepriceNew['total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Khách đã thanh toán</td>
                                                                    <td class="text-end" colspan="2"><?php echo e(number_format((float) ($extendRepricing['paid_total'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Còn phải thu</td>
                                                                    <td class="text-end text-danger fw-semibold" colspan="2"><?php echo e(number_format((float) ($extendRepriceNew['remaining'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Khách đang trả dư</td>
                                                                    <td class="text-end fw-semibold" colspan="2"><?php echo e(number_format((float) ($extendRepriceNew['overpayment'] ?? 0), 0, ',', '.')); ?>đ</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <?php if($extendServiceChanges->isNotEmpty()): ?>
                                                        <div class="alert alert-info py-2 mb-2">
                                                            <strong>Dịch vụ tính lại theo số đêm mới:</strong>
                                                            <?php $__currentLoopData = $extendServiceChanges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceLine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <div>
                                                                    <?php echo e($serviceLine['name'] ?? 'Dịch vụ'); ?>:
                                                                    <?php echo e(number_format((float) ($serviceLine['old_total'] ?? 0), 0, ',', '.')); ?>đ
                                                                    → <?php echo e(number_format((float) ($serviceLine['new_total'] ?? 0), 0, ',', '.')); ?>đ
                                                                    <span class="text-muted"><?php echo e($serviceLine['new_formula'] ?? ''); ?></span>
                                                                </div>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if(!empty($extendRemovedPromotions)): ?>
                                                        <div class="alert alert-warning py-2 mb-0">
                                                            <strong>Mã sẽ bị gỡ:</strong>
                                                            <?php $__currentLoopData = $extendRemovedPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $removedPromotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <div><?php echo e($removedPromotion['code'] ?? '---'); ?> — <?php echo e($removedPromotion['reason'] ?? 'Không còn đủ điều kiện.'); ?></div>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($extendPreview['conflicts'])): ?>
                                                <div class="border rounded bg-white p-2 mt-2 small">
                                                    <strong>Booking bị giao thời gian:</strong>
                                                    <ul class="mb-0 mt-1">
                                                        <?php $__currentLoopData = $extendPreview['conflicts']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conflict): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <li>
                                                                Phòng <?php echo e($conflict['room_number']); ?> / <?php echo e($conflict['category_name']); ?>

                                                                đã có booking <?php echo e($conflict['booking_code']); ?> của
                                                                <?php echo e($conflict['customer_name']); ?>

                                                                từ <?php echo e($conflict['check_in_text']); ?> đến <?php echo e($conflict['check_out_text']); ?>.
                                                            </li>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <?php if(!empty($extendPreview['replacements'])): ?>
                                                <div class="border rounded bg-white p-2 mt-2 small">
                                                    <strong>Phòng cùng hạng có thể chuyển:</strong>
                                                    <ul class="mb-0 mt-1">
                                                        <?php $__currentLoopData = $extendPreview['replacements']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $replacement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <li>
                                                                Chuyển phòng <?php echo e($replacement['old_room_number']); ?>

                                                                → <?php echo e($replacement['new_room_number']); ?> cùng hạng
                                                                <?php echo e($replacement['category_name']); ?>.
                                                            </li>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <div class="d-flex gap-2 flex-wrap mt-3">
                                                <form action="<?php echo e(route('admin.bookings.extend-stay.discard-preview', $booking->id)); ?>" method="POST" class="flex-fill">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-outline-secondary w-100">
                                                        <i class="bx bx-x me-1"></i>Đóng bản xem trước
                                                    </button>
                                                </form>
                                                <?php if(($extendPreview['status'] ?? '') !== 'blocked'): ?>
                                                    <form action="<?php echo e(route('admin.bookings.extend-stay', $booking->id)); ?>" method="POST"
                                                        class="flex-fill"
                                                        onsubmit="return confirm('Xác nhận gia hạn theo kết quả kiểm tra này?')">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('PATCH'); ?>
                                                        <input type="hidden" name="new_check_out_date"
                                                            value="<?php echo e($extendPreview['new_check_out_date']); ?>">
                                                        <input type="hidden" name="new_check_out_time"
                                                            value="<?php echo e($extendPreview['new_check_out_time']); ?>">
                                                        <button type="submit" class="btn btn-success w-100">
                                                            Xác nhận gia hạn
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <form action="<?php echo e(route('admin.bookings.request-inspection', $booking->id)); ?>" method="POST"
                                    onsubmit="return confirm('Chuyển phòng sang trạng thái chờ kiểm tra?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bx bx-search-alt me-1"></i>
                                        Yêu cầu kiểm tra phòng
                                    </button>
                                </form>
                            <?php elseif($booking->status == 'inspection_requested' && $allInspectionsConfirmed): ?>
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Chốt phí và check-out</div>
                                        </div>
                                    </div>

                                    <form action="<?php echo e(route('admin.bookings.check-out', $booking->id)); ?>" method="POST"
                                        id="checkOutForm">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="checkout_late_fee_confirm" id="checkoutLateFeeConfirm" value="">
                                        <input type="hidden" id="checkoutLateFeeAmount" value="<?php echo e($checkoutLateFeePreview); ?>">

                                        <?php if($checkoutLateFeePreview > 0): ?>
                                            <div class="alert alert-danger small mb-3">
                                                <div class="fw-bold mb-1">Phát sinh phụ thu check-out muộn</div>
                                                Khách đã quá giờ trả phòng khoảng
                                                <strong><?php echo e($checkoutLateHoursPreview); ?> giờ</strong>.
                                                <br>
                                                <strong>Chính sách:</strong> <?php echo e($checkoutLatePolicyText); ?>

                                                <br>
                                                <strong>Phụ thu cần ghi thêm lúc này:</strong>
                                                <span
                                                    class="fw-bold"><?php echo e(number_format($checkoutLateFeePreview, 0, ',', '.')); ?>đ</span>
                                                <br>
                                                <span class="text-muted"><?php echo e($checkoutLateNoteText); ?></span>

                                                <div class="mt-2 fw-semibold">
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="soft-note mb-3">
                                                <?php echo e($checkoutLatePolicyText); ?>

                                                <?php if($existingCheckoutLateFeeTotal > 0): ?>
                                                    Khoản phụ thu check-out muộn đã được ghi nhận:
                                                    <strong><?php echo e(number_format($existingCheckoutLateFeeTotal, 0, ',', '.')); ?>đ</strong>.
                                                    Hệ thống vẫn kiểm tra lại theo giờ trả thực tế khi bấm check-out và chỉ cộng thêm nếu khách đã sang mốc phí cao hơn.
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-clean align-middle mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td>Tiền phòng</td>
                                                        <td class="text-end fw-bold">
                                                            <?php echo e(number_format($roomTotal, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dịch vụ khách gọi thêm / phụ thu đã ghi nhận</td>
                                                        <td class="text-end fw-bold">
                                                            <?php echo e(number_format($serviceItemTotal, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dịch vụ tại phòng / hư hại đã duyệt</td>
                                                        <td class="text-end fw-bold">
                                                            <?php echo e(number_format($approvedInspectionTotal, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <?php if($checkoutLateFeePreview > 0): ?>
                                                        <tr>
                                                            <td>Phụ thu check-out muộn cần ghi thêm</td>
                                                            <td class="text-end fw-bold text-danger">
                                                                <?php echo e(number_format($checkoutLateFeePreview, 0, ',', '.')); ?>đ
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php if($promotionDiscountTotal > 0): ?>
                                                        <tr>
                                                            <td>
                                                                Mã ưu đãi đã áp dụng
                                                                <div class="small text-muted">
                                                                    Giảm tiền: <?php echo e(number_format($promotionMoneyDiscountTotal, 0, ',', '.')); ?>đ · Dịch vụ: <?php echo e(number_format($promotionServiceDiscountTotal, 0, ',', '.')); ?>đ · Nâng hạng: <?php echo e(number_format($promotionRoomUpgradeDiscountTotal, 0, ',', '.')); ?>đ
                                                                </div>
                                                            </td>
                                                            <td class="text-end fw-bold text-success">
                                                                -<?php echo e(number_format($promotionDiscountTotal, 0, ',', '.')); ?>đ
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <td>Mức cọc <?php echo e($depositPercentLabel); ?> hiện tại</td>
                                                        <td class="text-end fw-bold">
                                                            <?php echo e(number_format($adminPaymentDepositTarget, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Đã phân bổ vào cọc</td>
                                                        <td class="text-end fw-bold">
                                                            -<?php echo e(number_format($actualDepositPaid, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Thanh toán thêm / trả trước</td>
                                                        <td class="text-end fw-bold">
                                                            -<?php echo e(number_format($additionalPaidTotal, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tổng khách đã thanh toán</td>
                                                        <td class="text-end fw-bold">
                                                            -<?php echo e(number_format($adminPaymentPaidAmount, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tiền trả trước còn dư để bù trừ</td>
                                                        <td class="text-end fw-bold <?php echo e($currentOverpaymentTotal > 0 ? 'text-warning' : ''); ?>">
                                                            <?php echo e(number_format($currentOverpaymentTotal, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr class="table-light">
                                                        <td class="fw-bold">Còn lại cần thu trước khi bấm check-out</td>
                                                        <td class="text-end fw-bold text-danger fs-5">
                                                            <?php echo e(number_format($remainingTotal, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="checkout-payment-confirm-box mb-3">
                                            <?php if($remainingTotal > 0): ?>
                                                <div class="alert alert-warning small mb-0">
                                                    <div class="fw-bold mb-1">Chưa thể check-out</div>
                                                    Booking còn thiếu
                                                    <strong><?php echo e(number_format($remainingTotal, 0, ',', '.')); ?>đ</strong>
                                                    trên hệ thống. Hãy ghi nhận khoản khách thực sự đã trả tại khối
                                                    <strong>Thanh toán</strong> ở thanh bên. Sau khi số còn lại về 0đ,
                                                    bấm Check-out lại.
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-success small mb-0">
                                                    <div class="fw-bold mb-1">Đã đủ điều kiện thanh toán</div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bx bx-log-out-circle me-1"></i>
                                            Check-out
                                        </button>
                                    </form>

                                    <?php if($checkoutLateFeePreview > 0): ?>
                                        <div class="modal fade" id="checkoutLateFeeModal" tabindex="-1"
                                            aria-labelledby="checkoutLateFeeModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 rounded-4 shadow">
                                                    <div class="modal-header">
                                                        <div>
                                                            <h5 class="modal-title fw-bold" id="checkoutLateFeeModalLabel">
                                                                Xác nhận phụ thu check-out muộn
                                                            </h5>
                                                            <div class="text-muted small">Chỉ tiếp tục khi khách đã được giải thích và đồng ý.</div>
                                                        </div>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-danger small mb-3">
                                                            <div class="fw-bold mb-1">Lý do phát sinh</div>
                                                            <?php echo e($checkoutLateReasonText); ?><br>
                                                            <?php echo e($checkoutLateNoteText); ?>

                                                        </div>
                                                        <div class="info-list">
                                                            <div class="info-line">
                                                                <span class="info-label">Chính sách áp dụng</span>
                                                                <span class="info-value"><?php echo e($checkoutLatePolicyText); ?></span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Giá gốc tính phụ thu</span>
                                                                <span class="info-value"><?php echo e(number_format($checkoutLateBasePrice, 0, ',', '.')); ?>đ</span>
                                                            </div>
                                                            <?php if($booking->booking_type != 'hourly'): ?>
                                                                <div class="info-line">
                                                                    <span class="info-label">Tỷ lệ theo khung giờ</span>
                                                                    <span class="info-value"><?php echo e(rtrim(rtrim(number_format($checkoutLatePercent, 2, '.', ''), '0'), '.')); ?>%</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="info-line">
                                                                <span class="info-label">Cơ chế tính</span>
                                                                <span class="info-value"><?php echo e($checkoutLateFormulaText); ?></span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Phụ thu phát sinh</span>
                                                                <span class="info-value text-danger fs-5"><?php echo e(number_format($checkoutLateFeePreview, 0, ',', '.')); ?>đ</span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Tổng cần thanh toán sau khi cộng</span>
                                                                <span class="info-value text-danger"><?php echo e(number_format($finalTotal, 0, ',', '.')); ?>đ</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                            Chưa đồng ý
                                                        </button>
                                                        <button type="button" class="btn btn-danger" id="confirmCheckoutLateFeeSubmit">
                                                            Khách đồng ý - Tiếp tục check-out
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <details class="compact-panel mt-3" <?php if($errors->has('checkout_extra_name') || $errors->has('checkout_extra_amount')): ?> open <?php endif; ?>>
                                        <summary>
                                            <span>Thêm phí phát sinh khác</span>
                                            <span class="badge-clean status-muted">Ghi nhận trước khi thu tiền</span>
                                        </summary>
                                        <div class="compact-panel-body">
                                            <form action="<?php echo e(route('admin.bookings.checkout-fees.store', $booking->id)); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Tên khoản phí <span class="text-danger">*</span></label>
                                                        <input type="text" name="checkout_extra_name"
                                                            class="form-control <?php $__errorArgs = ['checkout_extra_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                            value="<?php echo e(old('checkout_extra_name')); ?>"
                                                            placeholder="Ví dụ: Mất thẻ phòng" required>
                                                        <?php $__errorArgs = ['checkout_extra_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Số tiền <span class="text-danger">*</span></label>
                                                        <input type="number" name="checkout_extra_amount"
                                                            class="form-control <?php $__errorArgs = ['checkout_extra_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                            min="1000" step="1000" value="<?php echo e(old('checkout_extra_amount')); ?>"
                                                            placeholder="100000" required>
                                                        <?php $__errorArgs = ['checkout_extra_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Ghi chú</label>
                                                        <input type="text" name="checkout_extra_note" class="form-control"
                                                            value="<?php echo e(old('checkout_extra_note')); ?>" placeholder="Ghi chú nếu có">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="submit" class="btn btn-primary w-100"
                                                            onclick="return confirm('Thêm khoản phí này vào tổng tiền booking?')">
                                                            <i class="bx bx-plus-circle me-1"></i> Thêm phí
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="alert alert-info small mt-3 mb-0">
                                                    Sau khi thêm, trang sẽ tải lại và khoản phí được cộng ngay vào
                                                    <strong>Dịch vụ khách gọi thêm / phụ thu</strong>, tổng cần thanh toán và số còn lại.
                                                    Lễ tân thu đủ theo số mới rồi mới bấm Check-out.
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                </div>
                            <?php elseif($booking->status == 'inspection_requested' && $hasInspection && !$allInspectionsConfirmed): ?>
                                <?php echo $__env->make('admin.pages.bookings.partials.inspection-guest-consultation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php else: ?>
                                <div class="soft-note">
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <?php echo $__env->make('admin.pages.bookings.partials.staying-guests', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    <details class="compact-panel mb-3">
                        <summary>
                            <span>Mã ưu đãi / hỗ trợ khách</span>
                            <span class="badge-clean status-muted">
                                <?php echo e($booking->bookingPromotions->count()); ?> mã đã áp dụng · mở để xem/thêm
                            </span>
                        </summary>

                        <div class="compact-panel-body">
                            <?php
                                $promotionTypeDisplayConfig = [
                                    'normal_discount' => [
                                        'label' => 'Mã thường',
                                        'badge' => 'bg-primary',
                                        'hint' => 'Mã phổ thông, dùng cho giảm giá trực tiếp hoặc tặng/giảm dịch vụ cơ bản.',
                                        'limit' => 1,
                                        'rule' => 'Booking chỉ được có tối đa 1 mã thường.',
                                    ],
                                    'event_discount' => [
                                        'label' => 'Mã sự kiện',
                                        'badge' => 'bg-success',
                                        'hint' => 'Mã theo chiến dịch, mùa lễ, combo hoặc chương trình bán hàng.',
                                        'limit' => 1,
                                        'rule' => 'Booking chỉ được có tối đa 1 mã sự kiện.',
                                    ],
                                    'conditional_discount' => [
                                        'label' => 'Mã điều kiện',
                                        'badge' => 'bg-warning text-dark',
                                        'hint' => 'Mã chỉ áp dụng khi booking đạt điều kiện như tổng tiền, số đêm, số phòng hoặc lịch sử khách.',
                                        'limit' => 1,
                                        'rule' => 'Booking chỉ được có tối đa 1 mã điều kiện.',
                                    ],
                                    'support_discount' => [
                                        'label' => 'Mã hỗ trợ khách',
                                        'badge' => 'bg-danger',
                                        'hint' => '',
                                        'limit' => null,
                                        'rule' => 'Có thể chọn nhiều mã hỗ trợ nếu từng mã cho phép dùng chung.',
                                    ],
                                ];

                                $availablePromotionGroups = collect($availablePromotions ?? collect())->groupBy('promotion_type');
                            ?>

                            <?php if($booking->bookingPromotions->count() > 0): ?>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-clean align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Mã</th>
                                                <th>Phạm vi</th>
                                                <th>Loại</th>
                                                <th>Kênh</th>
                                                <th>Người áp dụng</th>
                                                <th class="text-end">Giảm tiền</th>
                                                <th class="text-end">Ưu đãi DV</th>
                                                <th class="text-end">Nâng hạng</th>
                                                <th class="text-end">Tổng</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $booking->bookingPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingPromotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo e($bookingPromotion->code_snapshot); ?></td>
                                                    <td>
                                                        <?php if(($bookingPromotion->scope ?? 'booking') === 'room'): ?>
                                                            <span class="badge bg-primary">Phòng <?php echo e($bookingPromotion->bookingRoom?->room?->room_number ?? '---'); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Toàn bộ đơn</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $appliedTypeConfig = $promotionTypeDisplayConfig[$bookingPromotion->promotion_type_snapshot] ?? [
                                                                'label' => $bookingPromotion->type_label,
                                                                'badge' => 'bg-secondary',
                                                            ];
                                                        ?>
                                                        <span class="badge <?php echo e($appliedTypeConfig['badge']); ?>">
                                                            <?php echo e($appliedTypeConfig['label']); ?>

                                                        </span>
                                                    </td>
                                                    <td><?php echo e($bookingPromotion->applied_channel == 'admin' ? 'Admin' : 'User'); ?></td>
                                                    <td><?php echo e($bookingPromotion->user->name ?? 'Khách/User'); ?></td>
                                                    <td class="text-end text-success fw-bold">
                                                        -<?php echo e(number_format((float) ($bookingPromotion->money_discount_amount ?? $bookingPromotion->discount_amount), 0, ',', '.')); ?>đ
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        -<?php echo e(number_format((float) ($bookingPromotion->service_discount_amount ?? 0), 0, ',', '.')); ?>đ
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        -<?php echo e(number_format((float) ($bookingPromotion->room_upgrade_discount_amount ?? 0), 0, ',', '.')); ?>đ
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        -<?php echo e(number_format((float) $bookingPromotion->discount_amount, 0, ',', '.')); ?>đ
                                                    </td>
                                                    <td class="small text-muted">
                                                        <?php echo e($bookingPromotion->note ?: '---'); ?>

                                                        <?php if($bookingPromotion->serviceOffers->count() > 0): ?>
                                                            <div class="mt-1">
                                                                <?php $__currentLoopData = $bookingPromotion->serviceOffers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $offerSnapshot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <span class="badge bg-success-subtle text-success border me-1">
                                                                        <?php echo e($offerSnapshot->service_name_snapshot); ?> x<?php echo e($offerSnapshot->quantity); ?>: -<?php echo e(number_format((float) $offerSnapshot->discount_amount, 0, ',', '.')); ?>đ
                                                                    </span>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if($bookingPromotion->roomUpgradeOffers->count() > 0): ?>
                                                            <div class="mt-1">
                                                                <?php $__currentLoopData = $bookingPromotion->roomUpgradeOffers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $upgradeSnapshot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <span class="badge bg-primary-subtle text-primary border me-1">
                                                                        <?php echo e($upgradeSnapshot->old_room_category_name_snapshot); ?> → <?php echo e($upgradeSnapshot->new_room_category_name_snapshot); ?>: hỗ trợ <?php echo e(number_format((float) $upgradeSnapshot->covered_amount, 0, ',', '.')); ?>đ
                                                                    </span>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="soft-note mb-3">
                                    Booking này chưa áp dụng mã ưu đãi nào.
                                </div>
                            <?php endif; ?>

                            <?php if(in_array($booking->status, ['pending', 'confirmed', 'checked_in']) && $booking->payment_status != 'paid'): ?>
                                <?php if(($availablePromotions ?? collect())->count() > 0): ?>
                                    <?php
                                        $existingPromotionTypeCounts = $booking->bookingPromotions
                                            ->groupBy('promotion_type_snapshot')
                                            ->map->count();
                                        $existingPromotionCodes = $booking->bookingPromotions
                                            ->pluck('code_snapshot')
                                            ->map(fn ($code) => strtoupper(trim((string) $code)))
                                            ->values();
                                        $existingHasSoloPromotion = \App\Models\Promotion::query()
                                            ->whereIn('code', $existingPromotionCodes)
                                            ->where('is_stackable', false)
                                            ->exists();
                                    ?>
                                    <form action="<?php echo e(route('admin.bookings.promotions.store', $booking->id)); ?>"
                                        method="POST"
                                        data-booking-promotion-form
                                        data-existing-type-counts='<?php echo json_encode($existingPromotionTypeCounts, 15, 512) ?>'
                                        data-existing-code-count="<?php echo e($existingPromotionCodes->count()); ?>"
                                        data-existing-has-solo="<?php echo e($existingHasSoloPromotion ? 1 : 0); ?>">
                                        <?php echo csrf_field(); ?>

                                        <?php $__currentLoopData = $promotionTypeDisplayConfig; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotionType => $typeConfig): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $groupPromotions = $availablePromotionGroups->get($promotionType, collect());
                                            ?>

                                            <?php if($groupPromotions->count() > 0): ?>
                                                <div class="mb-3" data-promotion-group data-promotion-type="<?php echo e($promotionType); ?>" data-promotion-limit="<?php echo e($typeConfig['limit'] ?? ''); ?>">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                        <div>
                                                            <div class="fw-bold">
                                                                <?php echo e($typeConfig['label']); ?>

                                                                <span class="badge <?php echo e($typeConfig['badge']); ?> ms-1">
                                                                    <?php echo e($groupPromotions->count()); ?>

                                                                </span>
                                                            </div>
                                                            <div class="promotion-meta">
                                                                <?php echo e($typeConfig['hint']); ?>

                                                            </div>
                                                            <div class="promotion-meta fw-semibold text-dark mt-1">
                                                                <?php echo e($typeConfig['rule']); ?>

                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="promotion-list">
                                                        <?php $__currentLoopData = $groupPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php
                                                                $promotionDiscountText = $promotion->discount_type == 'percent'
                                                                    ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                                    : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';

                                                                if ($promotion->discount_type == 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                                    $promotionDiscountText .= ' - tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                                }
                                                            ?>

                                                            <label class="promotion-card mb-0">
                                                                <div class="form-check">
                                                                    <input type="checkbox"
                                                                        name="promotion_codes[]"
                                                                        value="<?php echo e($promotion->code); ?>"
                                                                        class="form-check-input booking-promotion-check"
                                                                        data-code="<?php echo e($promotion->code); ?>"
                                                                        data-type="<?php echo e($promotion->promotion_type); ?>"
                                                                        data-stackable="<?php echo e($promotion->is_stackable ? 1 : 0); ?>"
                                                                        data-requires-note="<?php echo e($promotion->requires_note || $promotion->promotion_type == 'support_discount' ? 1 : 0); ?>">

                                                                    <div class="ms-1">
                                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                                            <div>
                                                                                <div class="promotion-code"><?php echo e($promotion->code); ?></div>
                                                                                <div class="fw-semibold"><?php echo e($promotion->name); ?></div>
                                                                            </div>
                                                                            <span class="badge <?php echo e($typeConfig['badge']); ?>"><?php echo e($typeConfig['label']); ?></span>
                                                                        </div>
                                                                        <div class="promotion-meta mt-1">
                                                                            Giảm <?php echo e($promotionDiscountText); ?>

                                                                            <?php if((float) $promotion->min_booking_amount > 0): ?>
                                                                                · Đơn từ <?php echo e(number_format((float) $promotion->min_booking_amount, 0, ',', '.')); ?>đ
                                                                            <?php endif; ?>
                                                                            <?php if((int) $promotion->min_nights > 0): ?>
                                                                                · Từ <?php echo e((int) $promotion->min_nights); ?> đêm
                                                                            <?php endif; ?>
                                                                            <?php if((int) $promotion->min_rooms > 0): ?>
                                                                                · Từ <?php echo e((int) $promotion->min_rooms); ?> phòng
                                                                            <?php endif; ?>
                                                                            <?php if($promotion->requires_note || $promotion->promotion_type == 'support_discount'): ?>
                                                                                · Cần nhập lý do
                                                                            <?php endif; ?>
                                                                            · <?php echo e($promotion->is_stackable ? 'Có thể dùng cùng nhóm mã khác' : 'Chỉ dùng một mình'); ?>

                                                                        </div>

                                                                        <?php if($promotion->serviceOffers->count() > 0): ?>
                                                                            <div class="promotion-meta mt-1 text-success">
                                                                                Dịch vụ ưu đãi:
                                                                                <?php echo e($promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ')); ?>

                                                                            </div>
                                                                        <?php endif; ?>

                                                                        <?php if($promotion->roomUpgradeOffers->count() > 0): ?>
                                                                            <div class="promotion-meta mt-1 text-primary">
                                                                                Ưu đãi nâng hạng:
                                                                                <?php echo e($promotion->roomUpgradeOffers->map(fn ($offer) => $offer->cover_label)->implode(' · ')); ?>

                                                                                · Chỉ áp dụng khi booking đã có lịch sử đổi lên hạng cao hơn chưa dùng mã.
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <div class="mb-3">
                                            <label class="form-label small">
                                                Lý do áp mã hỗ trợ / ghi chú lịch sử
                                                <span class="text-danger" id="bookingPromotionNoteRequiredMark" style="display:none">*</span>
                                            </label>
                                            <textarea name="promotion_note" id="bookingPromotionNote" rows="3" class="form-control"
                                                placeholder="Ví dụ: khách đến sớm, hạng phòng cũ chưa sẵn sàng nên hỗ trợ đổi hạng và tặng dịch vụ."></textarea>
                                            <div class="promotion-meta mt-1">
                                                Bắt buộc nếu chọn mã hỗ trợ khách hoặc mã có cấu hình yêu cầu lý do.
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-gold">
                                            Áp dụng mã đã chọn
                                        </button>
                                    </form>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function () {
                                            const promotionForm = document.querySelector('[data-booking-promotion-form]');
                                            const noteInput = document.getElementById('bookingPromotionNote');
                                            const requiredMark = document.getElementById('bookingPromotionNoteRequiredMark');
                                            const checks = document.querySelectorAll('.booking-promotion-check');

                                            function hasRequiredNotePromotion() {
                                                return Array.from(checks).some(function (checkbox) {
                                                    return checkbox.checked && checkbox.dataset.requiresNote === '1';
                                                });
                                            }

                                            function refreshNoteRequiredState() {
                                                const required = hasRequiredNotePromotion();

                                                if (noteInput) {
                                                    noteInput.required = required;
                                                }

                                                if (requiredMark) {
                                                    requiredMark.style.display = required ? 'inline' : 'none';
                                                }
                                            }

                                            let existingTypeCounts = {};
                                            try {
                                                existingTypeCounts = JSON.parse(promotionForm?.dataset.existingTypeCounts || '{}');
                                            } catch (error) {
                                                existingTypeCounts = {};
                                            }
                                            const existingCodeCount = Number(promotionForm?.dataset.existingCodeCount || 0);
                                            const existingHasSolo = promotionForm?.dataset.existingHasSolo === '1';

                                            function typeLabel(type) {
                                                if (type === 'normal_discount') return 'mã thường';
                                                if (type === 'event_discount') return 'mã sự kiện';
                                                if (type === 'conditional_discount') return 'mã điều kiện';
                                                return 'mã cùng nhóm';
                                            }

                                            function enforceBookingPromotionSelection(changedCheckbox) {
                                                if (!changedCheckbox.checked) return true;

                                                const selected = Array.from(document.querySelectorAll('.booking-promotion-check:checked'));
                                                if (existingHasSolo) {
                                                    changedCheckbox.checked = false;
                                                    alert('Booking đã có một mã chỉ được dùng một mình nên không thể áp thêm mã khác.');
                                                    return false;
                                                }

                                                if (changedCheckbox.dataset.stackable === '0' && (existingCodeCount > 0 || selected.length > 1)) {
                                                    changedCheckbox.checked = false;
                                                    alert('Mã ' + (changedCheckbox.dataset.code || '') + ' chỉ được dùng một mình.');
                                                    return false;
                                                }

                                                const anotherSolo = selected.find(item => item !== changedCheckbox && item.dataset.stackable === '0');
                                                if (anotherSolo) {
                                                    changedCheckbox.checked = false;
                                                    alert('Mã ' + (anotherSolo.dataset.code || '') + ' đang được chọn và chỉ được dùng một mình.');
                                                    return false;
                                                }

                                                const type = changedCheckbox.dataset.type || '';
                                                if (['normal_discount', 'event_discount', 'conditional_discount'].includes(type)) {
                                                    const selectedSameType = selected.filter(item => item.dataset.type === type).length;
                                                    const existingSameType = Number(existingTypeCounts[type] || 0);
                                                    if (existingSameType + selectedSameType > 1) {
                                                        changedCheckbox.checked = false;
                                                        alert('Booking chỉ được có tối đa 1 ' + typeLabel(type) + '.');
                                                        return false;
                                                    }
                                                }
                                                return true;
                                            }

                                            function setPromotionDisabled(checkbox, disabled) {
                                                checkbox.disabled = disabled;
                                                const card = checkbox.closest('.promotion-card');
                                                if (card) {
                                                    card.classList.toggle('is-unavailable', disabled);
                                                    card.setAttribute('aria-disabled', disabled ? 'true' : 'false');
                                                }
                                            }

                                            function refreshPromotionAvailability() {
                                                const selected = Array.from(checks).filter(item => item.checked);
                                                const selectedSolo = selected.find(item => item.dataset.stackable === '0');
                                                const selectedTypeCounts = selected.reduce(function (counts, item) {
                                                    const type = item.dataset.type || '';
                                                    counts[type] = (counts[type] || 0) + 1;
                                                    return counts;
                                                }, {});

                                                checks.forEach(function (checkbox) {
                                                    if (checkbox.checked) {
                                                        setPromotionDisabled(checkbox, false);
                                                        return;
                                                    }

                                                    const type = checkbox.dataset.type || '';
                                                    const isLimitedType = ['normal_discount', 'event_discount', 'conditional_discount'].includes(type);
                                                    const typeLimitReached = isLimitedType
                                                        && (Number(existingTypeCounts[type] || 0) + Number(selectedTypeCounts[type] || 0) >= 1);
                                                    const incompatibleWithExisting = existingHasSolo
                                                        || (checkbox.dataset.stackable === '0' && existingCodeCount > 0);
                                                    const incompatibleWithSelection = Boolean(selectedSolo)
                                                        || (checkbox.dataset.stackable === '0' && selected.length > 0);

                                                    setPromotionDisabled(
                                                        checkbox,
                                                        incompatibleWithExisting || incompatibleWithSelection || typeLimitReached
                                                    );
                                                });
                                            }

                                            checks.forEach(function (checkbox) {
                                                checkbox.addEventListener('change', function () {
                                                    enforceBookingPromotionSelection(checkbox);
                                                    refreshNoteRequiredState();
                                                    refreshPromotionAvailability();
                                                });
                                            });

                                            if (promotionForm) {
                                                promotionForm.addEventListener('submit', function (event) {
                                                    if (hasRequiredNotePromotion() && noteInput && noteInput.value.trim() === '') {
                                                        event.preventDefault();
                                                        noteInput.focus();
                                                        alert('Vui lòng nhập lý do khi áp mã hỗ trợ khách.');
                                                    }
                                                });
                                            }

                                            refreshNoteRequiredState();
                                            refreshPromotionAvailability();
                                        });
                                    </script>
                                <?php else: ?>
                                    <div class="soft-note mb-0">
                                        Hiện không còn mã nào phù hợp để áp dụng thêm cho booking này.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="soft-note mb-0">
                                    Booking đã thanh toán đủ hoặc đã kết thúc nên không thể áp thêm mã.
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>

                    <?php if($canManageBookingRooms): ?>
                        <details class="compact-panel d-none" id="roomManagementSource">
                            <summary>
                                <span>Quản lý phòng: thêm phòng / đổi hạng</span>
                                <span class="badge-clean status-muted"><?php echo e($assignedRooms->count()); ?> phòng hiện tại</span>
                            </summary>

                            <div class="compact-panel-body">
                                <div class="soft-note mb-3">
                                    <strong>Khung kiểm tra:</strong>
                                    <?php echo e($lateShowCheckInAt?->format('d/m/Y H:i') ?? '---'); ?>

                                    → <?php echo e($lateShowCheckOutAt?->format('d/m/Y H:i') ?? '---'); ?>.
                                    Phòng hiện tại:
                                    <?php echo e($booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ') ?: 'Chưa gán phòng'); ?>.
                                </div>

                                <details class="compact-panel mb-3">
                                    <summary> Xem số phòng trống theo từng hạng </summary>
                                    <div class="compact-panel-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-clean align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Hạng</th>
                                                        <th>Sức chứa</th>
                                                        <th>Giá/đêm</th>
                                                        <th>Trống</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $roomCategoriesForBookingManage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <td class="fw-bold"><?php echo e($category->name); ?></td>
                                                            <td><?php echo e($category->adult_capacity); ?> NL / <?php echo e($category->child_capacity); ?>

                                                                TE</td>
                                                            <td><?php echo e(number_format($category->price, 0, ',', '.')); ?>đ</td>
                                                            <td>
                                                                <?php if($category->available_rooms_count > 0): ?>
                                                                    <span
                                                                        class="badge-clean status-done"><?php echo e($category->available_rooms_count); ?>

                                                                        phòng</span>
                                                                <?php else: ?>
                                                                    <span class="badge-clean status-cancelled">Hết</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </details>

                                <?php
                                    $roomOperationPreview = session('booking_room_operation_preview');
                                ?>

                                <?php if($roomOperationPreview): ?>
                                    <div class="alert alert-info border-primary mb-3" id="room-operation-preview">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo e($roomOperationPreview['title'] ?? 'Xem trước thay đổi phòng'); ?></h5>
                                                <div class="small">
                                                    Chưa cập nhật booking. Kiểm tra tiền phòng, dịch vụ, mã ưu đãi, cọc và số còn phải thu trước khi xác nhận.
                                                </div>
                                            </div>
                                            <span class="badge-clean status-info">Bản xem trước</span>
                                        </div>

                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm align-middle mb-0 bg-white">
                                                <thead>
                                                    <tr>
                                                        <th>Nội dung</th>
                                                        <th class="text-end">Hiện tại</th>
                                                        <th class="text-end">Sau thay đổi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Số phòng</td>
                                                        <td class="text-end"><?php echo e($roomOperationPreview['before']['room_quantity'] ?? 0); ?></td>
                                                        <td class="text-end fw-bold"><?php echo e($roomOperationPreview['after']['room_quantity'] ?? 0); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tiền phòng</td>
                                                        <td class="text-end"><?php echo e(number_format($roomOperationPreview['before']['room_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['after']['room_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dịch vụ / phụ thu đã xác nhận</td>
                                                        <td class="text-end"><?php echo e(number_format($roomOperationPreview['before']['service_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['after']['service_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Minibar / hư hại đã duyệt</td>
                                                        <td class="text-end"><?php echo e(number_format($roomOperationPreview['before']['inspection_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['after']['inspection_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mã giảm giá / hỗ trợ</td>
                                                        <td class="text-end text-success">-<?php echo e(number_format($roomOperationPreview['before']['discount_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                        <td class="text-end text-success fw-bold">-<?php echo e(number_format($roomOperationPreview['after']['discount_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr class="table-primary">
                                                        <td class="fw-bold">Tổng cần thanh toán</td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['before']['total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['after']['total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Khách đã thanh toán</td>
                                                        <td colspan="2" class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['after']['paid_total'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mức cọc yêu cầu</td>
                                                        <td class="text-end"><?php echo e(number_format($roomOperationPreview['before']['required_deposit'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format($roomOperationPreview['after']['required_deposit'] ?? 0, 0, ',', '.')); ?>đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Còn thiếu để đủ cọc</td>
                                                        <td colspan="2" class="text-end <?php echo e(($roomOperationPreview['after']['deposit_shortfall'] ?? 0) > 0 ? 'text-danger' : 'text-success'); ?> fw-bold">
                                                            <?php echo e(number_format($roomOperationPreview['after']['deposit_shortfall'] ?? 0, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Còn phải thu toàn bộ</td>
                                                        <td colspan="2" class="text-end text-danger fw-bold">
                                                            <?php echo e(number_format($roomOperationPreview['after']['remaining'] ?? 0, 0, ',', '.')); ?>đ
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php if(!empty($roomOperationPreview['promotion_changes'])): ?>
                                            <div class="bg-white border rounded p-3 mb-3">
                                                <strong class="d-block mb-2">Mã ưu đãi sau thay đổi</strong>
                                                <?php $__currentLoopData = $roomOperationPreview['promotion_changes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotionChange): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="small mb-1">
                                                        <strong><?php echo e($promotionChange['code'] ?? '---'); ?></strong>
                                                        <?php if(($promotionChange['scope'] ?? 'booking') === 'room'): ?>
                                                            · Phòng <?php echo e($promotionChange['room_number'] ?? '---'); ?>

                                                        <?php else: ?>
                                                            · Toàn bộ đơn
                                                        <?php endif; ?>
                                                        —
                                                        <?php switch($promotionChange['status'] ?? ''):
                                                            case ('removed'): ?>
                                                                <span class="text-danger">Bị gỡ</span>
                                                                <?php break; ?>
                                                            <?php case ('recalculated'): ?>
                                                                <span class="text-warning">
                                                                    Tính lại <?php echo e(number_format($promotionChange['old_discount'] ?? 0, 0, ',', '.')); ?>đ
                                                                    → <?php echo e(number_format($promotionChange['new_discount'] ?? 0, 0, ',', '.')); ?>đ
                                                                </span>
                                                                <?php break; ?>
                                                            <?php case ('added'): ?>
                                                                <span class="text-success">Được thêm</span>
                                                                <?php break; ?>
                                                            <?php default: ?>
                                                                <span class="text-success">Giữ nguyên</span>
                                                        <?php endswitch; ?>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if(!empty($roomOperationPreview['service_changes'])): ?>
                                            <div class="bg-white border rounded p-3 mb-3">
                                                <strong class="d-block mb-2">Dịch vụ thay đổi</strong>
                                                <?php $__currentLoopData = $roomOperationPreview['service_changes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceChange): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="small mb-1">
                                                        <strong><?php echo e($serviceChange['name'] ?? 'Dịch vụ'); ?></strong>
                                                        <?php if(($serviceChange['scope'] ?? 'booking') === 'room'): ?>
                                                            · Phòng <?php echo e($serviceChange['room_number'] ?? '---'); ?>

                                                        <?php else: ?>
                                                            · Toàn bộ đơn
                                                        <?php endif; ?>
                                                        —
                                                        <?php echo e(number_format($serviceChange['old_total'] ?? 0, 0, ',', '.')); ?>đ
                                                        → <?php echo e(number_format($serviceChange['new_total'] ?? 0, 0, ',', '.')); ?>đ
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="small mb-3"><?php echo e($roomOperationPreview['message'] ?? ''); ?></div>

                                        <form method="POST" action="<?php echo e($roomOperationPreview['action_url']); ?>" onsubmit="return confirm('Xác nhận lưu thay đổi phòng và cập nhật toàn bộ tiền/mã/dịch vụ?')">
                                            <?php echo csrf_field(); ?>
                                            <?php if(($roomOperationPreview['http_method'] ?? 'PATCH') !== 'POST'): ?>
                                                <?php echo method_field($roomOperationPreview['http_method'] ?? 'PATCH'); ?>
                                            <?php endif; ?>
                                            <input type="hidden" name="confirm_operation" value="1">
                                            <input type="hidden" name="operation_token" value="<?php echo e($roomOperationPreview['token'] ?? ''); ?>">
                                            <?php $__currentLoopData = ($roomOperationPreview['payload'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if(is_bool($value)): ?>
                                                    <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value ? 1 : 0); ?>">
                                                <?php elseif(is_array($value)): ?>
                                                    <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $arrayValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php if(is_scalar($arrayValue) || is_null($arrayValue)): ?>
                                                            <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($arrayValue); ?>">
                                                        <?php endif; ?>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php elseif(is_scalar($value) || is_null($value)): ?>
                                                    <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="submit" class="btn btn-primary">Xác nhận cập nhật</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="<?php echo e(route('admin.bookings.room-operation.discard-preview', $booking)); ?>" class="mt-2">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-outline-secondary">Đóng / hủy bản xem trước</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                                <div class="form-mini-grid">
                                    <form action="<?php echo e(route('admin.bookings.add-room-to-booking', $booking->id)); ?>" method="POST"
                                        class="mini-form-box">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>

                                        <h6>Thêm phòng</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng phòng</label>
                                            <select name="additional_room_category_id" class="form-select" required>
                                                <option value="">-- Chọn hạng --</option>
                                                <?php $__currentLoopData = $roomCategoriesForBookingManage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($category->id); ?>" <?php if($category->available_rooms_count <= 0): echo 'disabled'; endif; ?>>
                                                        <?php echo e($category->name); ?> - Còn <?php echo e($category->available_rooms_count); ?> -
                                                        <?php echo e(number_format($category->price, 0, ',', '.')); ?>đ
                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Số phòng thêm</label>
                                            <input type="number" name="additional_room_quantity" class="form-control" value="1"
                                                min="1" required>
                                        </div>

                                        <div class="form-check mb-3">
                                            <input type="checkbox" name="prefer_near_current_rooms" value="1"
                                                class="form-check-input" id="managePreferNearCurrentRooms">
                                            <label class="form-check-label" for="managePreferNearCurrentRooms">
                                                Ưu tiên gần phòng hiện tại
                                            </label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="add_room_reason" class="form-control"
                                                placeholder="Ví dụ: Bạn khách đến thêm">
                                        </div>

                                        <button type="submit" class="btn btn-outline-primary w-100">Xem trước thêm phòng</button>
                                    </form>

                                    <form action="<?php echo e(route('admin.bookings.change-one-room-category', $booking->id)); ?>"
                                        method="POST" class="mini-form-box js-category-room-form" data-room-count="1">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>

                                        <h6><?php echo e($booking->bookingRooms->count() <= 1 ? 'Đổi hạng phòng' : 'Đổi 1 phòng'); ?></h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Phòng cần đổi</label>
                                            <select name="booking_room_id" class="form-select" required>
                                                <option value="">-- Chọn phòng --</option>
                                                <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if($bookingRoom->room): ?>
                                                        <option value="<?php echo e($bookingRoom->id); ?>">
                                                            Phòng <?php echo e($bookingRoom->room->room_number); ?> · <?php echo e($bookingRoom->room->category->name ?? 'Không rõ hạng'); ?>

                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng mới</label>
                                            <select name="new_room_category_id" class="form-select js-room-category-target" required>
                                                <option value="">-- Chọn hạng --</option>
                                                <?php $__currentLoopData = $roomCategoriesForBookingManage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($category->id); ?>" <?php if($category->available_rooms_count <= 0): echo 'disabled'; endif; ?>>
                                                        <?php echo e($category->name); ?> · Còn <?php echo e($category->available_rooms_count); ?> · <?php echo e(number_format($category->price, 0, ',', '.')); ?>đ
                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Cách chọn phòng</label>
                                            <select name="room_assignment_mode" class="form-select js-room-assignment-mode" required>
                                                <option value="auto" selected>Hệ thống tự chọn</option>
                                                <option value="manual">Chọn phòng thủ công</option>
                                            </select>
                                        </div>

                                        <div class="mb-3 d-none" data-manual-room-picker>
                                            <label class="form-label small">Phòng cụ thể</label>
                                            <select name="target_room_ids[]" class="form-select js-manual-room-select" disabled>
                                                <option value="">-- Chọn phòng --</option>
                                                <?php $__currentLoopData = $categoryChangeAvailableRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($room->id); ?>" data-category-id="<?php echo e($room->room_category_id); ?>">
                                                        Phòng <?php echo e($room->room_number); ?> · Tầng <?php echo e($room->floor_number ?? '---'); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="change_category_reason" class="form-control"
                                                placeholder="Ví dụ: Khách muốn nâng hạng 1 phòng">
                                        </div>

                                        <button type="submit" class="btn btn-outline-warning w-100">Xem trước đổi 1 phòng</button>
                                    </form>

                                    <?php if($booking->bookingRooms->count() >= 2): ?>
                                    <form action="<?php echo e(route('admin.bookings.change-all-room-category', $booking->id)); ?>"
                                        method="POST" class="mini-form-box js-category-room-form" data-room-count="<?php echo e($assignedRooms->count()); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>

                                        <h6>Đổi toàn bộ</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng mới</label>
                                            <select name="new_room_category_id" class="form-select js-room-category-target" required>
                                                <option value="">-- Chọn hạng --</option>
                                                <?php $__currentLoopData = $roomCategoriesForBookingManage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($category->id); ?>" <?php if($category->available_rooms_count < $assignedRooms->count()): echo 'disabled'; endif; ?>>
                                                        <?php echo e($category->name); ?> · Còn <?php echo e($category->available_rooms_count); ?> · Cần <?php echo e($assignedRooms->count()); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Cách chọn phòng</label>
                                            <select name="room_assignment_mode" class="form-select js-room-assignment-mode" required>
                                                <option value="auto" selected>Hệ thống tự chọn</option>
                                                <option value="manual">Chọn phòng thủ công</option>
                                            </select>
                                        </div>

                                        <div class="mb-3 d-none" data-manual-room-picker>
                                            <label class="form-label small">Chọn đúng <?php echo e($assignedRooms->count()); ?> phòng</label>
                                            <select name="target_room_ids[]" class="form-select js-manual-room-select" multiple size="6" disabled>
                                                <?php $__currentLoopData = $categoryChangeAvailableRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($room->id); ?>" data-category-id="<?php echo e($room->room_category_id); ?>">
                                                        Phòng <?php echo e($room->room_number); ?> · Tầng <?php echo e($room->floor_number ?? '---'); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="change_category_reason" class="form-control"
                                                placeholder="Ví dụ: Khách muốn đổi toàn bộ hạng phòng">
                                        </div>

                                        <button type="submit" class="btn btn-outline-danger w-100">Xem trước đổi toàn bộ</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </details>
                    <?php endif; ?>

                    <?php if(($booking->room_selection_mode ?? 'automatic') === 'manual'): ?>
                        <?php
                            $manualSelectionRooms = $assignedRooms
                                ->concat($timeAvailableRooms)
                                ->unique('id')
                                ->sortBy(fn ($room) => sprintf('%04d-%s', (int) ($room->floor_number ?? 0), $room->room_number))
                                ->values();
                            $roomSelectionStatusLabels = [
                                'pending' => 'Chờ lễ tân xử lý',
                                'fulfilled' => 'Đã đáp ứng yêu cầu',
                                'awaiting_guest' => 'Chờ khách xác nhận phòng dự phòng',
                                'fallback_accepted' => 'Khách đã đồng ý phòng dự phòng',
                                'fallback_declined' => 'Khách từ chối · booking đã hủy',
                                'unfulfilled' => 'Không thể đáp ứng (dữ liệu cũ)',
                            ];
                            $manualSelectionFinal = in_array($booking->room_selection_status, ['fulfilled', 'fallback_accepted', 'fallback_declined', 'unfulfilled'], true);
                        ?>
                        <details class="card-clean border border-warning-subtle" <?php echo e($manualSelectionFinal ? '' : 'open'); ?>>
                            <summary class="card-title-clean mb-0" style="cursor:pointer;list-style:none">
                                <div>
                                    <h5>Yêu cầu chọn phòng của khách</h5>
                                </div>
                                <span class="badge-clean <?php echo e(in_array($booking->room_selection_status, ['fulfilled', 'fallback_accepted'], true) ? 'status-success' : ($booking->room_selection_status === 'fallback_declined' ? 'status-muted' : 'status-warning')); ?>">
                                    <?php echo e($roomSelectionStatusLabels[$booking->room_selection_status] ?? $booking->room_selection_status); ?>

                                </span>
                            </summary>

                            <div class="mt-3">
                                <div class="room-selection-request-highlight mb-3">
                                    <strong>Yêu cầu của khách:</strong>
                                    <span><?php echo e($booking->room_selection_request ?: 'Khách chưa ghi yêu cầu cụ thể.'); ?></span>
                                </div>
                                <?php if($booking->room_selection_status === 'pending' && in_array($booking->status, ['pending', 'confirmed'], true)): ?>
                                    <div class="alert alert-warning small">
                                        Hệ thống đang giữ <?php echo e($booking->room_quantity); ?> phòng dự phòng để tránh oversell. <strong>Không công bố số phòng dự phòng cho khách ở trạng thái này.</strong> Chỉ khi chọn <strong>Đáp ứng yêu cầu</strong> hệ thống mới cộng phí đảm bảo yêu cầu phòng.
                                    </div>

                                    <form action="<?php echo e(route('admin.bookings.manual-room-selection', $booking)); ?>" method="POST" class="mb-3">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="decision" value="fulfilled">

                                        <label class="form-label fw-semibold">Chọn đúng <?php echo e($booking->room_quantity); ?> phòng đáp ứng yêu cầu</label>
                                        <select name="selected_room_ids[]" class="form-select" multiple size="<?php echo e(min(8, max(4, $manualSelectionRooms->count()))); ?>" required>
                                            <?php $__currentLoopData = $manualSelectionRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($room->id); ?>" <?php if($assignedRooms->contains('id', $room->id)): echo 'selected'; endif; ?>>
                                                    Phòng <?php echo e($room->room_number); ?> · Tầng <?php echo e($room->floor_number ?? '---'); ?>

                                                    <?php echo e($assignedRooms->contains('id', $room->id) ? '· đang giữ dự phòng' : ''); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <div class="form-text">Có thể dùng chính phòng dự phòng nếu phòng đó thực sự đáp ứng yêu cầu khách đã ghi.</div>

                                        <label class="form-label mt-2">Ghi chú gửi khách (không bắt buộc)</label>
                                        <textarea name="handling_note" class="form-control" rows="2" placeholder="Ví dụ: Đã bố trí phòng tầng 6, khu vực yên tĩnh."></textarea>

                                        <button class="btn btn-success w-100 mt-3">
                                            Xác nhận đáp ứng yêu cầu và tính phí
                                        </button>
                                    </form>

                                    <form action="<?php echo e(route('admin.bookings.manual-room-selection', $booking)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <input type="hidden" name="decision" value="unfulfilled">
                                        <label class="form-label fw-semibold">Nếu không thể đáp ứng</label>
                                        <textarea name="handling_note" class="form-control" rows="2" required
                                            placeholder="Ghi rõ lý do để khách quyết định có nhận phòng dự phòng hay không."></textarea>
                                        <button class="btn btn-outline-danger w-100 mt-2">
                                            Không thể đáp ứng · hỏi khách về phòng dự phòng
                                        </button>
                                    </form>
                                <?php elseif($booking->room_selection_status === 'awaiting_guest'): ?>
                                    <div class="alert alert-warning small mb-2">
                                        <strong>Đang chờ khách quyết định.</strong> Không thu phí đảm bảo yêu cầu phòng. Khách đã được thông báo số phòng dự phòng và có thể Đồng ý hoặc Từ chối/hủy đơn hoàn cọc.
                                        <?php if($booking->room_selection_handling_note): ?>
                                            <br><strong>Lý do đã gửi khách:</strong> <?php echo e($booking->room_selection_handling_note); ?>

                                        <?php endif; ?>
                                    </div>
                                    <div class="room-pill-list">
                                        <?php $__currentLoopData = $assignedRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignedRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="room-pill">Phòng dự phòng <?php echo e($assignedRoom->room_number); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php elseif($booking->room_selection_status === 'fulfilled'): ?>
                                    <div class="soft-note">
                                        <strong>Đã đáp ứng.</strong>
                                        Phí đảm bảo yêu cầu phòng: <?php echo e(number_format((float) $booking->room_selection_fee, 0, ',', '.')); ?>đ.
                                        <?php if($booking->room_selection_handling_note): ?>
                                            <br>Ghi chú: <?php echo e($booking->room_selection_handling_note); ?>

                                        <?php endif; ?>
                                    </div>
                                <?php elseif($booking->room_selection_status === 'fallback_accepted'): ?>
                                    <div class="soft-note">
                                        <strong>Khách đã đồng ý sử dụng phòng dự phòng.</strong> Booking tiếp tục giữ nguyên, không thu phí đảm bảo yêu cầu phòng.
                                    </div>
                                <?php elseif($booking->room_selection_status === 'fallback_declined'): ?>
                                    <div class="soft-note mb-3">
                                        <strong>Khách từ chối phòng dự phòng.</strong> Booking đã hủy do khách sạn không đáp ứng yêu cầu; không tính đây là lỗi hủy của khách.
                                    </div>
                                    <?php if((float) ($booking->refund_due_amount ?? 0) > 0): ?>
                                        <div class="alert <?php echo e($booking->refund_status === 'completed' ? 'alert-success' : 'alert-danger'); ?> small">
                                            Cần hoàn khách: <strong><?php echo e(number_format((float) $booking->refund_due_amount, 0, ',', '.')); ?>đ</strong> ·
                                            <?php echo e($booking->refund_status === 'completed' ? 'Đã xác nhận hoàn tất' : 'Đang chờ hoàn tiền'); ?>.
                                            <?php if($booking->refund_status === 'completed' && $booking->refund_processed_at): ?>
                                                <br>Hoàn tất lúc <?php echo e($booking->refund_processed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>.
                                            <?php endif; ?>
                                        </div>
                                        <?php if($booking->refund_status === 'pending'): ?>
                                            <form action="<?php echo e(route('admin.bookings.manual-room-selection.refund-completed', $booking)); ?>" method="POST">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <label class="form-label small fw-semibold">Xác nhận sau khi đã thực sự hoàn tiền cho khách</label>
                                                <textarea name="refund_note" class="form-control" rows="2" placeholder="Ví dụ: Đã hoàn qua cổng VNPay / hoàn tiền mặt tại quầy..."></textarea>
                                                <button class="btn btn-success w-100 mt-2" onclick="return confirm('Chỉ xác nhận khi tiền đã thực sự được hoàn cho khách. Tiếp tục?');">Xác nhận đã hoàn tiền</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info small mb-0">Booking chưa phát sinh khoản thanh toán cần hoàn.</div>
                                    <?php endif; ?>
                                <?php elseif($booking->room_selection_status === 'unfulfilled'): ?>
                                    <div class="soft-note">
                                        <strong>Dữ liệu cũ:</strong> Booking được ghi nhận không thể đáp ứng yêu cầu theo luồng cũ. Hãy kiểm tra trực tiếp với khách nếu booking vẫn còn hiệu lực.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endif; ?>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <div>
                                <h5>Phòng đang gán</h5>
                            </div>
                        </div>

                        <?php if($assignedRooms->count() > 0): ?>
                            <div class="room-pill-list mb-3">
                                <?php $__currentLoopData = $assignedRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignedRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('admin.rooms.show', $assignedRoom->id)); ?>"
                                        class="room-pill text-decoration-none">
                                        <span>Phòng <?php echo e($assignedRoom->room_number); ?></span>
                                        <span class="text-muted">· Tầng <?php echo e($assignedRoom->floor_number ?? '---'); ?></span>
                                        <span
                                            class="badge-clean status-muted"><?php echo e($roomStatusLabels[$assignedRoom->status] ?? $assignedRoom->status); ?></span>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-3">Đơn này chưa được gán phòng.</div>
                        <?php endif; ?>

                        <?php if(in_array($booking->status, ['pending', 'confirmed', 'checked_in'])): ?>
                            <details class="compact-panel">
                                <summary>Đổi phòng cùng hạng</summary>
                                <div class="compact-panel-body">
                                    <form action="<?php echo e(route('admin.bookings.change-room', $booking->id)); ?>" method="POST" class="js-same-rank-room-form">
                                        <?php echo csrf_field(); ?>

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Phòng cần đổi</label>
                                                <select name="old_room_id" class="form-select js-old-room-select" required>
                                                    <option value="">-- Chọn phòng đang gán --</option>
                                                    <?php $__currentLoopData = $assignedRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignedRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($assignedRoom->id); ?>" data-category-id="<?php echo e($assignedRoom->room_category_id); ?>">
                                                            Phòng <?php echo e($assignedRoom->room_number); ?> · Tầng <?php echo e($assignedRoom->floor_number ?? '---'); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Cách chọn phòng</label>
                                                <select name="room_assignment_mode" class="form-select js-room-assignment-mode" required>
                                                    <option value="auto" selected>Hệ thống tự chọn</option>
                                                    <option value="manual">Chọn phòng thủ công</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 d-none" data-manual-room-picker>
                                                <label class="form-label">Phòng thay thế cùng hạng</label>
                                                <select name="new_room_id" class="form-select js-manual-room-select" disabled>
                                                    <option value="">-- Chọn phòng --</option>
                                                    <?php $__currentLoopData = $timeAvailableRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($room->id); ?>" data-category-id="<?php echo e($room->room_category_id); ?>">
                                                            Phòng <?php echo e($room->room_number); ?> · Tầng <?php echo e($room->floor_number ?? '---'); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>

                                            <?php if($booking->status === 'checked_in'): ?>
                                                <div class="col-md-6">
                                                    <label class="form-label">Phòng cũ sau khi chuyển</label>
                                                    <select name="old_room_new_status" class="form-select" required>
                                                        <option value="cleaning">Cần dọn</option>
                                                        <option value="maintenance">Bảo trì</option>
                                                    </select>
                                                </div>
                                            <?php else: ?>
                                                <input type="hidden" name="old_room_new_status" value="available">
                                            <?php endif; ?>

                                            <div class="col-md-6">
                                                <label class="form-label">Lý do đổi phòng</label>
                                                <input type="text" name="change_reason" class="form-control"
                                                    placeholder="Ví dụ: Khách muốn chuyển sang phòng khác" required>
                                            </div>
                                        </div>

                                        <?php if($timeAvailableRooms->count() == 0): ?>
                                            <div class="alert alert-warning small mt-3 mb-0">
                                                Không còn phòng cùng hạng phù hợp trong khoảng thời gian booking.
                                            </div>
                                        <?php endif; ?>

                                        <button type="submit" class="btn btn-outline-warning w-100 mt-3">
                                            Xem trước đổi phòng
                                        </button>
                                    </form>
                                </div>
                            </details>
                        <?php endif; ?>
                    </section>

                    <section class="card-clean">
                        <details class="compact-panel booking-service-overview" <?php if($errors->has('services.*')): ?> open <?php endif; ?>>
                            <summary>
                                <span>Dịch vụ / phụ thu</span>
                                <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                    <span class="badge-clean status-muted"><?php echo e($booking->serviceItems->count()); ?> khoản</span>
                                    <span class="badge-clean <?php echo e($serviceItemTotal > 0 ? 'status-warning' : 'status-muted'); ?>"><?php echo e(number_format((float) $serviceItemTotal, 0, ',', '.')); ?>đ</span>
                                </span>
                            </summary>
                            <div class="compact-panel-body">

                        <?php
                            $serviceCatalogTypes = \App\Models\Service::serviceCatalogTypes();
                            $surchargeCatalogTypes = \App\Models\Service::surchargeCatalogTypes();
                            $serviceCatalogItems = $booking->serviceItems->filter(fn ($item) => in_array($item->type, $serviceCatalogTypes, true));
                            $surchargeItems = $booking->serviceItems->filter(fn ($item) => in_array($item->type, $surchargeCatalogTypes, true));
                            $legacyServiceItems = $booking->serviceItems->reject(fn ($item) => in_array($item->type, array_merge($serviceCatalogTypes, $surchargeCatalogTypes), true));
                            $serviceCatalogTotal = (float) $serviceCatalogItems->where('billing_status', 'confirmed')->sum('total');
                            $surchargeItemsTotal = (float) $surchargeItems->where('billing_status', 'confirmed')->sum('total');
                            $legacyServiceItemsTotal = (float) $legacyServiceItems->where('billing_status', 'confirmed')->sum('total');
                        ?>

                        <details class="compact-panel mb-3">
                            <summary>
                                <span>Dịch vụ / minibar</span>
                                <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                    <span class="badge-clean status-muted"><?php echo e($serviceCatalogItems->count()); ?> khoản</span>
                                    <span class="badge-clean <?php echo e($serviceCatalogTotal > 0 ? 'status-warning' : 'status-muted'); ?>"><?php echo e(number_format($serviceCatalogTotal, 0, ',', '.')); ?>đ</span>
                                </span>
                            </summary>
                            <div class="compact-panel-body">
                                <?php echo $__env->make('admin.pages.bookings.partials.service-item-table', [
                                    'items' => $serviceCatalogItems,
                                    'booking' => $booking,
                                    'canEditServiceItems' => $canEditServiceItems,
                                    'emptyText' => 'Chưa có dịch vụ/minibar khách mua hoặc gọi thêm.',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </details>

                        <details class="compact-panel mb-3">
                            <summary>
                                <span>Phụ thu / phí phát sinh</span>
                                <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                    <span class="badge-clean status-muted"><?php echo e($surchargeItems->count()); ?> khoản</span>
                                    <span class="badge-clean <?php echo e($surchargeItemsTotal > 0 ? 'status-warning' : 'status-muted'); ?>"><?php echo e(number_format($surchargeItemsTotal, 0, ',', '.')); ?>đ</span>
                                </span>
                            </summary>
                            <div class="compact-panel-body">
                                <?php echo $__env->make('admin.pages.bookings.partials.service-item-table', [
                                    'items' => $surchargeItems,
                                    'booking' => $booking,
                                    'canEditServiceItems' => $canEditServiceItems,
                                    'emptyText' => 'Chưa có phụ thu/phí phát sinh ngoài kiểm phòng.',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </details>

                        <?php if($legacyServiceItems->isNotEmpty()): ?>
                            <details class="compact-panel mb-3">
                                <summary>
                                    <span>Khoản lịch sử khác</span>
                                    <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                        <span class="badge-clean status-muted"><?php echo e($legacyServiceItems->count()); ?> khoản</span>
                                        <span class="badge-clean <?php echo e($legacyServiceItemsTotal > 0 ? 'status-warning' : 'status-muted'); ?>"><?php echo e(number_format($legacyServiceItemsTotal, 0, ',', '.')); ?>đ</span>
                                    </span>
                                </summary>
                                <div class="compact-panel-body">
                                    <div class="soft-note mb-2">Các dòng kiểu cũ được giữ nguyên để không mất lịch sử booking.</div>
                                    <?php echo $__env->make('admin.pages.bookings.partials.service-item-table', [
                                        'items' => $legacyServiceItems,
                                        'booking' => $booking,
                                        'canEditServiceItems' => $canEditServiceItems,
                                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                </div>
                            </details>
                        <?php endif; ?>

                        <?php if($approvedInspectionItems->count() > 0): ?>
                            <details class="compact-panel mb-3">
                                <summary>
                                    <span>Khoản kiểm phòng đã duyệt</span>
                                    <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                        <span class="badge-clean status-muted"><?php echo e($approvedInspectionItems->count()); ?> khoản</span>
                                        <span class="badge-clean status-warning"><?php echo e(number_format((float) $approvedInspectionItems->sum('total'), 0, ',', '.')); ?>đ</span>
                                    </span>
                                </summary>
                                <div class="compact-panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-clean align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Loại</th>
                                                    <th>Hạng mục</th>
                                                    <th>Đơn giá</th>
                                                    <th>SL</th>
                                                    <th>Thành tiền</th>
                                                    <th>Ghi chú</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $approvedInspectionItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inspectionItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td><?php echo e($inspectionItem->type == 'minibar' ? 'Dịch vụ tại phòng' : 'Hư hại'); ?></td>
                                                        <td><?php echo e($inspectionItem->name); ?></td>
                                                        <td><?php echo e(number_format((float) $inspectionItem->price, 0, ',', '.')); ?>đ</td>
                                                        <td><?php echo e($inspectionItem->quantity); ?></td>
                                                        <td class="fw-bold text-danger">
                                                            <?php echo e(number_format((float) $inspectionItem->total, 0, ',', '.')); ?>đ
                                                        </td>
                                                        <td><?php echo e($inspectionItem->admin_note ?: '---'); ?></td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </details>
                        <?php endif; ?>

                        <?php if($canEditServiceItems): ?>
                            <details class="compact-panel" <?php if($errors->has('services.*')): ?> open <?php endif; ?>>
                                <summary>Thêm dịch vụ / minibar gọi thêm / xe cộ</summary>
                                <div class="compact-panel-body">
                                    <form action="<?php echo e(route('admin.bookings.service-items.store', $booking->id)); ?>" method="POST"
                                        id="multiServiceForm">
                                        <?php echo csrf_field(); ?>

                                        <div id="serviceRows">
                                            <div class="service-input-row border rounded p-3 mb-3 bg-light">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Dịch vụ</label>
                                                        <select name="services[0][service_id]"
                                                            class="form-select service-item-select" required>
                                                            <option value="">-- Chọn dịch vụ --</option>
                                                            <?php $__currentLoopData = $availableServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($service->id); ?>"
                                                                    data-price="<?php echo e($service->price); ?>"
                                                                    data-unit="<?php echo e($service->unit); ?>"
                                                                    data-group="<?php echo e($service->service_group ?? 'general'); ?>"
                                                                    data-billing-rule="<?php echo e($service->billing_rule ?? 'once'); ?>">
                                                                    <?php echo e($service->name); ?> -
                                                                    <?php echo e($service->type === 'minibar_order' ? 'Minibar gọi thêm' : ($service->group_label ?? 'Dịch vụ')); ?> -
                                                                    <?php echo e(number_format($service->price, 0, ',', '.')); ?>đ /
                                                                    <?php echo e($service->unit); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="form-label small">Áp dụng cho</label>
                                                        <input type="hidden" name="services[0][scope]" class="service-scope-input" value="booking">
                                                        <select name="services[0][booking_room_id]" class="form-select service-room-select">
                                                            <option value="" data-room-count="<?php echo e(max(1, $booking->bookingRooms->count())); ?>" data-guest-count="<?php echo e(max(1, $booking->guests->count())); ?>">Toàn bộ đơn</option>
                                                            <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceBookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <?php
                                                                    $serviceRoomGuestCount = $booking->guests->where('booking_room_id', $serviceBookingRoom->id)->count();
                                                                    if ($serviceRoomGuestCount <= 0) {
                                                                        $serviceRoomGuestCount = max(1, (int) $serviceBookingRoom->adult_count + (int) $serviceBookingRoom->child_count);
                                                                    }
                                                                ?>
                                                                <option value="<?php echo e($serviceBookingRoom->id); ?>" data-room-count="1" data-guest-count="<?php echo e($serviceRoomGuestCount); ?>">
                                                                    Phòng <?php echo e($serviceBookingRoom->room?->room_number ?? '---'); ?> · <?php echo e($serviceBookingRoom->room?->category?->name ?? '---'); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-1">
                                                        <label class="form-label small">SL</label>
                                                        <input type="number" name="services[0][quantity]"
                                                            class="form-control service-item-quantity" value="1" min="1"
                                                            required>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <label class="form-label small">Tạm tính</label>
                                                        <input type="text" class="form-control service-item-total-text"
                                                            value="0đ" readonly>
                                                        <div class="small text-muted service-item-formula mt-1">Chọn dịch vụ để xem cách tính</div>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <button type="button"
                                                            class="btn btn-outline-danger w-100 remove-service-row">Xóa</button>
                                                    </div>

                                                    <div class="col-md-12">
                                                        <label class="form-label small">Ghi chú</label>
                                                        <input type="text" name="services[0][note]"
                                                            class="form-control service-item-note"
                                                            placeholder="Ví dụ: Khách gọi lễ tân yêu cầu thêm nước suối">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="soft-note d-flex justify-content-between align-items-center mb-3">
                                            <span>Tổng tạm tính, sẽ cộng ngay vào đơn</span>
                                            <strong id="multiServiceTotalText">0đ</strong>
                                        </div>

                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-primary" id="addServiceRowButton">
                                                + Thêm dòng
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                Lưu dịch vụ
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        <?php endif; ?>
                            </div>
                        </details>
                    </section>

                    <section class="card-clean">
                        <details class="history-details">
                            <summary>
                                Lịch sử thao tác
                                <span class="text-muted fw-normal ms-1">(<?php echo e($booking->logs->count()); ?>)</span>
                            </summary>

                            <div class="log-box">
                                <?php $__empty_1 = true; $__currentLoopData = $booking->logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="log-item">
                                        <div class="fw-bold">
                                            <?php echo e($log->created_at ? $log->created_at->format('d/m/Y - H:i') : '---'); ?>

                                            - <?php echo e($log->user?->name ?? 'Hệ thống'); ?>

                                        </div>
                                        <div class="text-muted mt-1" style="white-space: pre-line;">
                                            <?php echo e($log->description); ?>

                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <p class="text-muted mb-0">Chưa có lịch sử thao tác.</p>
                                <?php endif; ?>
                            </div>
                        </details>
                    </section>
                </div>

                <aside class="side-stack">
                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Thanh toán</h5>
                        </div>

                        <div class="payment-summary-note">Các số cần nhìn ngay; bấm vào ô để xem công thức.</div>

                        <div class="payment-kpi-grid">
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailFinalTotal" data-payment-title="Tổng cần thanh toán">
                                <span class="payment-kpi-label">Tổng đơn</span>
                                <span class="payment-kpi-value"><?php echo e(number_format($finalTotal, 0, ',', '.')); ?>đ</span>
                            </button>
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailPayments" data-payment-title="Lịch sử tiền khách đã thanh toán">
                                <span class="payment-kpi-label">Đã thu</span>
                                <span class="payment-kpi-value text-success"><?php echo e(number_format($adminPaymentPaidAmount, 0, ',', '.')); ?>đ</span>
                            </button>
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailRemaining" data-payment-title="Số tiền còn phải thu">
                                <span class="payment-kpi-label">Còn phải thu</span>
                                <span class="payment-kpi-value <?php echo e($remainingTotal > 0 ? 'text-danger' : 'text-success'); ?>"><?php echo e(number_format($remainingTotal, 0, ',', '.')); ?>đ</span>
                            </button>
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailDeposit" data-payment-title="Mức cọc <?php echo e($depositPercentLabel); ?> hiện tại">
                                <span class="payment-kpi-label">Còn thiếu cọc</span>
                                <span class="payment-kpi-value <?php echo e($adminPaymentDepositAmount > 0 ? 'text-warning' : 'text-success'); ?>"><?php echo e(number_format($adminPaymentDepositAmount, 0, ',', '.')); ?>đ</span>
                            </button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2 px-1">
                            <span class="small text-muted">Trạng thái thanh toán</span>
                            <span class="badge-clean <?php echo e($paymentStatusClass); ?>">
                                <?php echo e($paymentStatusLabels[$effectivePaymentStatus] ?? $effectivePaymentStatus); ?>

                            </span>
                        </div>

                        <details class="payment-components-details">
                            <summary>Xem chi tiết cấu thành và phân bổ tiền</summary>
                            <div class="info-list">
                                <div class="payment-summary-section">Các khoản phát sinh</div>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailRoom" data-payment-title="Chi tiết tiền phòng">
                                    <span class="info-label">Tiền phòng</span>
                                    <span class="info-value"><?php echo e(number_format($roomTotal, 0, ',', '.')); ?>đ</span>
                                </button>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailServices" data-payment-title="Dịch vụ khách gọi thêm và phụ thu">
                                    <span class="info-label">Dịch vụ / phụ thu</span>
                                    <span class="info-value <?php echo e($serviceItemTotal > 0 ? 'text-danger' : ''); ?>"><?php echo e($serviceItemTotal > 0 ? '+' : ''); ?><?php echo e(number_format((float) $serviceItemTotal, 0, ',', '.')); ?>đ</span>
                                </button>
                                <?php if($manualRoomSelectionFee > 0): ?>
                                    <div class="info-line">
                                        <span class="info-label">Phí chọn phòng thủ công</span>
                                        <span class="info-value text-danger">+<?php echo e(number_format($manualRoomSelectionFee, 0, ',', '.')); ?>đ</span>
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailMinibar" data-payment-title="Dịch vụ tại phòng đã duyệt">
                                    <span class="info-label">Dịch vụ tại phòng</span>
                                    <span class="info-value <?php echo e($approvedMinibarTotal > 0 ? 'text-danger' : ''); ?>"><?php echo e($approvedMinibarTotal > 0 ? '+' : ''); ?><?php echo e(number_format((float) $approvedMinibarTotal, 0, ',', '.')); ?>đ</span>
                                </button>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailDamage" data-payment-title="Phí hư hại đã duyệt">
                                    <span class="info-label">Hư hại đã duyệt</span>
                                    <span class="info-value <?php echo e($approvedDamageTotal > 0 ? 'text-danger' : ''); ?>"><?php echo e($approvedDamageTotal > 0 ? '+' : ''); ?><?php echo e(number_format((float) $approvedDamageTotal, 0, ',', '.')); ?>đ</span>
                                </button>
                                <?php if($checkoutLateFeePreview > 0): ?>
                                    <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailLateCheckout" data-payment-title="Dự kiến phụ thu trả phòng muộn">
                                        <span class="info-label">Dự kiến trả muộn</span>
                                        <span class="info-value text-danger">+<?php echo e(number_format((float) $checkoutLateFeePreview, 0, ',', '.')); ?>đ</span>
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailSubtotal" data-payment-title="Tổng phát sinh trước ưu đãi">
                                    <span class="info-label">Trước ưu đãi</span>
                                    <span class="info-value"><?php echo e(number_format($totalBeforeDiscount, 0, ',', '.')); ?>đ</span>
                                </button>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailPromotions" data-payment-title="Mã giảm giá và hỗ trợ">
                                    <span class="info-label">Giảm giá / hỗ trợ</span>
                                    <span class="info-value <?php echo e($promotionDiscountTotal > 0 ? 'text-success' : ''); ?>"><?php echo e($promotionDiscountTotal > 0 ? '-' : ''); ?><?php echo e(number_format($promotionDiscountTotal, 0, ',', '.')); ?>đ</span>
                                </button>

                                <div class="payment-summary-section mt-2">Phân bổ tiền đã thu</div>
                                <div class="info-line">
                                    <span class="info-label">Mức cọc <?php echo e($depositPercentLabel); ?></span>
                                    <span class="info-value"><?php echo e(number_format($adminPaymentDepositTarget, 0, ',', '.')); ?>đ</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Đã phân bổ vào cọc</span>
                                    <span class="info-value"><?php echo e(number_format($actualDepositPaid, 0, ',', '.')); ?>đ</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Đã thu ngoài cọc</span>
                                    <span class="info-value"><?php echo e(number_format($additionalPaidTotal, 0, ',', '.')); ?>đ</span>
                                </div>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailPrepayment" data-payment-title="Tiền trả trước còn dư để bù trừ">
                                    <span class="info-label">Trả trước còn dư</span>
                                    <span class="info-value <?php echo e($currentOverpaymentTotal > 0 ? 'text-warning fw-bold' : ''); ?>"><?php echo e(number_format($currentOverpaymentTotal, 0, ',', '.')); ?>đ</span>
                                </button>
                            </div>
                        </details>

                        <?php if(!in_array($booking->status, ['canceled', 'cancelled', 'no_show']) && $remainingTotal > 0.01): ?>
                            <hr class="my-3">

                            <?php if(session('admin_vnpay_payment_url')): ?>
                                <div class="alert alert-info small mb-3">
                                    <div class="fw-bold mb-1">Đã tạo link yêu cầu thanh toán VNPay</div>
                                    <a href="<?php echo e(session('admin_vnpay_payment_url')); ?>" target="_blank" class="fw-bold">
                                        Mở link yêu cầu thanh toán
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="fw-bold mb-2">Thanh toán</div>

                            <div class="soft-note mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <span>Thiếu cọc</span>
                                    <strong class="<?php echo e($adminPaymentDepositAmount > 0 ? 'text-warning' : 'text-success'); ?>"><?php echo e(number_format($adminPaymentDepositAmount, 0, ',', '.')); ?>đ</strong>
                                </div>
                                <div class="d-flex justify-content-between gap-2 mt-1">
                                    <span>Còn phải thu</span>
                                    <strong class="text-danger"><?php echo e(number_format($remainingTotal, 0, ',', '.')); ?>đ</strong>
                                </div>
                            </div>

                            <select id="adminPaymentMode" class="form-select form-select-sm mb-2">
                                <option value="">-- Chọn cách thanh toán --</option>
                                <option value="cash">Tiền mặt tại quầy</option>
                                <option value="bank_transfer">Chuyển khoản tại quầy</option>
                                <option value="vnpay">Gửi thanh toán online VNPay qua email</option>
                            </select>

                            <div id="adminDirectPaymentBox" class="d-none">
                                <form action="<?php echo e(route('admin.bookings.payments.store', $booking)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>

                                    <input type="hidden" name="payment_method" id="adminDirectPaymentMethod" value="">

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select name="payment_type" id="adminDirectPaymentType"
                                                class="form-select form-select-sm" required>
                                                <option value="deposit_30" data-amount="<?php echo e($adminPaymentDepositAmount); ?>"
                                                    <?php if($adminPaymentDepositAmount <= 0): echo 'disabled'; endif; ?>>
                                                    Thu bổ sung để đủ cọc <?php echo e($depositPercentLabel); ?> - <?php echo e(number_format($adminPaymentDepositAmount, 0, ',', '.')); ?>đ
                                                </option>
                                                <option value="custom" data-amount="<?php echo e($adminPaymentFullAmount); ?>" data-entry-mode="remaining">
                                                    Thu phần còn lại - <?php echo e(number_format($adminPaymentFullAmount, 0, ',', '.')); ?>đ
                                                </option>
                                                <option value="custom" data-amount="0" data-entry-mode="manual">
                                                    Thu số tiền khác / nhận thêm tiền trả trước
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <div class="soft-note" id="adminDirectPaymentSpeakText">
                                            </div>
                                        </div>

                                        <div class="col-12 d-none" id="adminDirectCustomAmountBox">
                                            <label for="adminDirectCustomAmount" class="form-label small fw-semibold mb-1">
                                                Số tiền khách đưa/chuyển
                                            </label>
                                            <input type="number" name="amount" id="adminDirectCustomAmount"
                                                class="form-control form-control-sm" min="1000" step="1000"
                                                placeholder="Ví dụ: 2000000">

                                            <div class="d-flex justify-content-between align-items-center rounded border px-3 py-2 mt-2 bg-light"
                                                id="adminDirectChangeDueBox">
                                                <span class="small fw-semibold">Phần sẽ giữ làm trả trước</span>
                                                <strong id="adminDirectChangeDueText">0đ</strong>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <textarea name="payment_note" rows="2" class="form-control form-control-sm"
                                                placeholder="Ghi chú thanh toán nếu có"></textarea>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" id="adminDirectPaymentSubmit"
                                                class="btn btn-sm btn-dark w-100"
                                                onclick="return confirm('Xác nhận ghi nhận khoản thanh toán này?')">
                                                <i class="bx bx-money-withdraw me-1"></i>
                                                Ghi nhận đã thu
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div id="adminVnpayPaymentBox" class="d-none">
                                <form action="<?php echo e(route('admin.bookings.vnpay.create', $booking)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select name="payment_type" id="adminVnpayPaymentType"
                                                class="form-select form-select-sm" required>
                                                <option value="deposit_30" data-amount="<?php echo e($adminPaymentDepositAmount); ?>"
                                                    <?php if($adminPaymentDepositAmount <= 0): echo 'disabled'; endif; ?>>
                                                    Gửi yêu cầu bổ sung cọc <?php echo e($depositPercentLabel); ?> - <?php echo e(number_format($adminPaymentDepositAmount, 0, ',', '.')); ?>đ
                                                </option>
                                                <option value="custom" data-amount="<?php echo e($adminPaymentFullAmount); ?>">
                                                    Gửi yêu cầu thanh toán phần còn lại - <?php echo e(number_format($adminPaymentFullAmount, 0, ',', '.')); ?>đ
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <input type="email" name="customer_email" id="adminVnpayCustomerEmail"
                                                class="form-control form-control-sm"
                                                value="<?php echo e($adminPaymentDefaultEmail); ?>"
                                                placeholder="Email khách nhận link thanh toán VNPay" required>
                                        </div>

                                        <div class="col-12">
                                            <div class="soft-note" id="adminVnpayPaymentSpeakText">
                                                Email sẽ có mã booking, mã giao dịch, số tiền và nút mở thanh toán. Khách bấm lại link email sẽ tạo phiên VNPay mới nếu yêu cầu còn hạn.
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" id="adminVnpayPaymentSubmit"
                                                class="btn btn-sm btn-outline-primary w-100"
                                                onclick="return confirm('Gửi email yêu cầu thanh toán VNPay cho khách?')">
                                                <i class="bx bx-envelope me-1"></i>
                                                Gửi email thanh toán VNPay
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if($booking->payments->count() > 0): ?>
                            <hr class="my-3">
                            <div class="fw-bold mb-2">Lịch sử thanh toán</div>

                            <div class="d-grid gap-2">
                                <?php $__currentLoopData = $booking->payments->sortByDesc('created_at')->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $paymentProviderLabels = [
                                            'vnpay' => 'VNPay',
                                            'admin_vnpay' => 'VNPay admin',
                                            'cash' => 'Tiền mặt',
                                            'bank_transfer' => 'Chuyển khoản',
                                        ];

                                        $paymentRawResponse = is_array($payment->raw_response ?? null)
                                            ? $payment->raw_response
                                            : [];

                                        $paymentExpireText = null;
                                        $paymentIsExpiredPending = false;

                                        if ($payment->status === 'pending') {
                                            $paymentExpiresAt = null;

                                            if (!empty($paymentRawResponse['request_expires_at'] ?? null)) {
                                                $paymentExpiresAt = \Carbon\Carbon::parse($paymentRawResponse['request_expires_at'], 'Asia/Ho_Chi_Minh');
                                            } elseif (!empty($paymentRawResponse['expires_at'] ?? null)) {
                                                $paymentExpiresAt = \Carbon\Carbon::parse($paymentRawResponse['expires_at'], 'Asia/Ho_Chi_Minh');
                                            } elseif ($payment->created_at) {
                                                $paymentExpiresAt = $payment->created_at->copy()->timezone('Asia/Ho_Chi_Minh')->addMinutes(
                                                    $payment->provider === 'admin_vnpay'
                                                        ? (int) $bookingPolicy('payment.admin_vnpay_expire_minutes', 1440)
                                                        : (int) $bookingPolicy('payment.vnpay_expire_minutes', 30)
                                                );
                                            }

                                            if ($paymentExpiresAt) {
                                                $paymentExpireText = 'Hạn: ' . $paymentExpiresAt->format('d/m/Y H:i');
                                                $paymentIsExpiredPending = now('Asia/Ho_Chi_Minh')->greaterThan($paymentExpiresAt);
                                            }
                                        }

                                        $paymentStatusText = $payment->status === 'success'
                                            ? 'Thành công'
                                            : ($payment->status === 'failed' ? 'Đã đóng/thất bại' : ($paymentIsExpiredPending ? 'Hết hạn' : 'Đang chờ'));
                                    ?>

                                    <div class="border rounded-3 p-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong><?php echo e($paymentProviderLabels[$payment->provider] ?? $payment->provider); ?></strong>
                                            <span><?php echo e(number_format((float) $payment->amount, 0, ',', '.')); ?>đ</span>
                                        </div>
                                        <div class="small text-muted">
                                            <?php echo e($paymentStatusText); ?> ·
                                            <?php echo e($payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') : ($payment->created_at ? $payment->created_at->format('d/m/Y H:i') : '---')); ?>

                                            <?php if($paymentExpireText && $payment->status === 'pending'): ?>
                                                · <?php echo e($paymentExpireText); ?>

                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Khách hàng</h5>
                        </div>

                        <div class="info-list">
                            <div class="info-line">
                                <span class="info-label">Họ tên</span>
                                <span class="info-value"><?php echo e($customerName); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">SĐT</span>
                                <span class="info-value"><?php echo e($booking->booked_customer_phone ?? '---'); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo e($booking->booked_customer_email ?? '---'); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">CCCD</span>
                                <span class="info-value"><?php echo e($booking->booked_customer_cccd ?? '---'); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Địa chỉ</span>
                                <span class="info-value"><?php echo e($booking->booked_customer_address ?? '---'); ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Lưu trú</h5>
                        </div>

                        <div class="info-list">
                            <div class="info-line">
                                <span class="info-label">Hạng phòng</span>
                                <span class="info-value"><?php echo e($booking->roomCategory->name ?? 'Không xác định'); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Loại đặt</span>
                                <span
                                    class="info-value"><?php echo e($booking->booking_type == 'hourly' ? 'Theo giờ' : 'Qua đêm'); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Nhận phòng</span>
                                <span
                                    class="info-value"><?php echo e($lateShowCheckInAt ? $lateShowCheckInAt->format('d/m/Y H:i') : '---'); ?></span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Trả phòng</span>
                                <span
                                    class="info-value"><?php echo e($lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '---'); ?></span>
                            </div>
                            <?php if($booking->booking_type == 'hourly'): ?>
                                <div class="info-line">
                                    <span class="info-label">Dọn phòng đến</span>
                                    <span
                                        class="info-value"><?php echo e($hourlyCleaningUntil ? $hourlyCleaningUntil->format('d/m/Y H:i') : '---'); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-line">
                                <span
                                    class="info-label"><?php echo e($booking->booking_type == 'hourly' ? 'Thời lượng' : 'Số đêm'); ?></span>
                                <span class="info-value">
                                    <?php if($booking->booking_type == 'hourly'): ?>
                                        <?php echo e($booking->check_in_at && $booking->check_out_at ? $booking->check_in_at->diffInHours($booking->check_out_at) . ' giờ' : '---'); ?>

                                    <?php else: ?>
                                        <?php echo e($nightCount); ?> đêm
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Số khách</span>
                                <span class="info-value"><?php echo e($booking->adult_count); ?> NL / <?php echo e($booking->child_count); ?>

                                    TE</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Số phòng</span>
                                <span class="info-value"><?php echo e($booking->room_quantity); ?> phòng</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Tạo lúc</span>
                                <span
                                    class="info-value"><?php echo e($booking->created_at ? $booking->created_at->format('d/m/Y H:i') : '---'); ?></span>
                            </div>
                        </div>
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Ghi chú nội bộ</h5>
                        </div>

                        <form action="<?php echo e(route('admin.bookings.update-note', $booking->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>

                            <textarea name="note" rows="5" class="form-control"
                                placeholder="Nhập ghi chú nội bộ cho đơn nếu có"><?php echo e(old('note', $booking->note)); ?></textarea>

                            <?php $__errorArgs = ['note'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                Lưu ghi chú
                            </button>
                        </form>
                    </section>
                </aside>
            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>

    <div class="offcanvas offcanvas-end payment-breakdown-offcanvas" tabindex="-1" id="paymentBreakdownOffcanvas"
        aria-labelledby="paymentBreakdownOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <div>
                <h5 class="offcanvas-title fw-bold" id="paymentBreakdownOffcanvasLabel">Chi tiết khoản tiền</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
        </div>
        <div class="offcanvas-body" id="paymentBreakdownOffcanvasBody"></div>
    </div>

    <template id="paymentDetailRoom">
        <?php if($booking->booking_type === 'hourly'): ?>
            <div class="payment-breakdown-item">
                <div class="fw-bold">Tiền phòng theo thời lượng</div>
                <div class="payment-breakdown-formula">
                    <?php echo e(optional($booking->check_in_at)->format('d/m/Y H:i')); ?> → <?php echo e(optional($booking->check_out_at)->format('d/m/Y H:i')); ?>

                </div>
                <div class="d-flex justify-content-between mt-2"><span>Thành tiền</span><strong><?php echo e(number_format($roomTotal, 0, ',', '.')); ?>đ</strong></div>
            </div>
        <?php else: ?>
            <?php $__empty_1 = true; $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $roomLineTotal = (float) $bookingRoom->price_at_booking * $nightCount;
                    $roomNumber = $bookingRoom->room?->room_number ?: 'Chưa gán phòng';
                    $roomCategoryName = $bookingRoom->room?->category?->name ?: $booking->roomCategory?->name;
                ?>
                <div class="payment-breakdown-item">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">Phòng <?php echo e($roomNumber); ?></div>
                            <div class="small text-muted"><?php echo e($roomCategoryName ?: 'Chưa xác định hạng'); ?></div>
                        </div>
                        <strong><?php echo e(number_format($roomLineTotal, 0, ',', '.')); ?>đ</strong>
                    </div>
                    <div class="payment-breakdown-formula">
                        <?php echo e(number_format((float) $bookingRoom->price_at_booking, 0, ',', '.')); ?>đ × <?php echo e($nightCount); ?> đêm
                        = <?php echo e(number_format($roomLineTotal, 0, ',', '.')); ?>đ
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="alert alert-secondary mb-0">Đơn chưa có dữ liệu phòng để hiển thị chi tiết.</div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="payment-breakdown-total"><span>Tổng tiền phòng</span><span><?php echo e(number_format($roomTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailServices">
        <?php $__empty_1 = true; $__currentLoopData = $confirmedServiceItemsForBreakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $itemRule = \App\Models\Service::normalizeBillingRule($item->billing_rule_snapshot ?: optional($item->service)->billing_rule);
            ?>
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2">
                    <div>
                        <div class="fw-bold"><?php echo e($item->name); ?></div>
                        <div class="small text-muted"><?php echo e($serviceBillingRuleLabels[$itemRule] ?? 'Một lần / theo số lượng nhập'); ?></div>
                    </div>
                    <strong class="text-danger">+<?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</strong>
                </div>
                <div class="payment-breakdown-formula"><?php echo e($serviceBillingFormula($item)); ?></div>
                <?php if($item->note): ?>
                    <div class="small text-muted mt-1">Ghi chú: <?php echo e($item->note); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="alert alert-secondary mb-0">Chưa có dịch vụ hoặc phụ thu đã xác nhận.</div>
        <?php endif; ?>
        <div class="payment-breakdown-total"><span>Tổng dịch vụ / phụ thu</span><span><?php echo e(number_format($serviceItemTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailMinibar">
        <?php $__empty_1 = true; $__currentLoopData = $approvedMinibarItemsForBreakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong><?php echo e($item->name); ?></strong><strong class="text-danger">+<?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</strong></div>
                <div class="payment-breakdown-formula"><?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ × <?php echo e(max(1, (int) $item->quantity)); ?> <?php echo e($item->unit ?: 'đơn vị'); ?> = <?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="alert alert-secondary mb-0">Chưa có dịch vụ tại phòng/minibar được duyệt.</div>
        <?php endif; ?>
        <div class="payment-breakdown-total"><span>Tổng dịch vụ tại phòng</span><span><?php echo e(number_format($approvedMinibarTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailDamage">
        <?php $__empty_1 = true; $__currentLoopData = $approvedDamageItemsForBreakdown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong><?php echo e($item->name); ?></strong><strong class="text-danger">+<?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</strong></div>
                <div class="payment-breakdown-formula"><?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ × <?php echo e(max(1, (int) $item->quantity)); ?> = <?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</div>
                <?php if($item->admin_note): ?><div class="small text-muted mt-1">Ghi chú: <?php echo e($item->admin_note); ?></div><?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="alert alert-secondary mb-0">Chưa có phí hư hại được duyệt.</div>
        <?php endif; ?>
        <div class="payment-breakdown-total"><span>Tổng phí hư hại</span><span><?php echo e(number_format($approvedDamageTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailLateCheckout">
        <div class="payment-breakdown-item">
            <div class="fw-bold"><?php echo e($checkoutLateReasonText); ?></div>
            <div class="payment-breakdown-formula mt-2"><?php echo e($checkoutLatePolicyText); ?></div>
            <div class="payment-breakdown-formula">Công thức: <?php echo e($checkoutLateFormulaText); ?></div>
            <?php if($checkoutLateNoteText): ?><div class="small text-muted mt-2"><?php echo e($checkoutLateNoteText); ?></div><?php endif; ?>
        </div>
        <div class="payment-breakdown-total"><span>Phụ thu dự kiến</span><span><?php echo e(number_format($checkoutLateFeePreview, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailSubtotal">
        <div class="payment-breakdown-item">
            <div class="d-flex justify-content-between"><span>Tiền phòng</span><strong><?php echo e(number_format($roomTotal, 0, ',', '.')); ?>đ</strong></div>
            <div class="d-flex justify-content-between mt-2"><span>Dịch vụ / phụ thu</span><strong>+<?php echo e(number_format($serviceItemTotal, 0, ',', '.')); ?>đ</strong></div>
            <?php if($manualRoomSelectionFee > 0): ?><div class="d-flex justify-content-between mt-2"><span>Phí chọn phòng thủ công</span><strong>+<?php echo e(number_format($manualRoomSelectionFee, 0, ',', '.')); ?>đ</strong></div><?php endif; ?>
            <div class="d-flex justify-content-between mt-2"><span>Dịch vụ tại phòng / hư hại</span><strong>+<?php echo e(number_format($approvedInspectionTotal, 0, ',', '.')); ?>đ</strong></div>
            <?php if($checkoutLateFeePreview > 0): ?><div class="d-flex justify-content-between mt-2"><span>Trả phòng muộn dự kiến</span><strong>+<?php echo e(number_format($checkoutLateFeePreview, 0, ',', '.')); ?>đ</strong></div><?php endif; ?>
        </div>
        <div class="payment-breakdown-total"><span>Tổng trước ưu đãi</span><span><?php echo e(number_format($totalBeforeDiscount, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailPromotions">
        <?php $__empty_1 = true; $__currentLoopData = $booking->bookingPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotionUsage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong><?php echo e($promotionUsage->code_snapshot); ?></strong><strong class="text-success">-<?php echo e(number_format((float) $promotionUsage->discount_amount, 0, ',', '.')); ?>đ</strong></div>
                <div class="small text-muted mt-1">
                    <?php echo e($promotionUsage->type_label); ?> ·
                    <?php if(($promotionUsage->scope ?? 'booking') === 'room'): ?>
                        chỉ phòng <?php echo e($promotionUsage->bookingRoom?->room?->room_number ?? '---'); ?>

                    <?php else: ?>
                        toàn booking
                    <?php endif; ?>
                </div>
                <div class="payment-breakdown-formula">Giảm tiền: <?php echo e(number_format((float) $promotionUsage->money_discount_amount, 0, ',', '.')); ?>đ · Dịch vụ: <?php echo e(number_format((float) $promotionUsage->service_discount_amount, 0, ',', '.')); ?>đ · Nâng hạng: <?php echo e(number_format((float) $promotionUsage->room_upgrade_discount_amount, 0, ',', '.')); ?>đ</div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="alert alert-secondary mb-0">Đơn chưa áp dụng mã giảm giá hoặc mã hỗ trợ.</div>
        <?php endif; ?>
        <div class="payment-breakdown-total"><span>Tổng ưu đãi</span><span>-<?php echo e(number_format($promotionDiscountTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailFinalTotal">
        <div class="payment-breakdown-item">
            <div class="d-flex justify-content-between"><span>Tổng phát sinh trước ưu đãi</span><strong><?php echo e(number_format($totalBeforeDiscount, 0, ',', '.')); ?>đ</strong></div>
            <?php if($manualRoomSelectionFee > 0): ?><div class="small text-muted mt-1">Đã gồm <?php echo e(number_format($manualRoomSelectionFee, 0, ',', '.')); ?>đ phí chọn phòng thủ công.</div><?php endif; ?>
            <div class="d-flex justify-content-between mt-2"><span>Mã giảm giá / hỗ trợ</span><strong class="text-success">-<?php echo e(number_format($promotionDiscountTotal, 0, ',', '.')); ?>đ</strong></div>
        </div>
        <div class="payment-breakdown-total"><span>Tổng cần thanh toán</span><span><?php echo e(number_format($finalTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailDeposit">
        <div class="payment-breakdown-item">
            <div class="fw-bold">Mức cọc được tính lại theo đơn hiện tại</div>
            <div class="payment-breakdown-formula"><?php echo e($depositPercentLabel); ?> × tiền phòng sau phần ưu đãi thuộc phạm vi tính cọc = <?php echo e(number_format($adminPaymentDepositTarget, 0, ',', '.')); ?>đ.</div>
            <div class="small text-muted mt-2">Lịch sử khách đã chuyển tiền vẫn giữ nguyên; hệ thống chỉ phân bổ lại số đã thu vào mức cọc mới.</div>
        </div>
        <div class="payment-breakdown-total"><span>Còn thiếu để đủ cọc</span><span><?php echo e(number_format($adminPaymentDepositAmount, 0, ',', '.')); ?>đ</span></div>
    </template>

    <template id="paymentDetailPayments">
        <?php $__empty_1 = true; $__currentLoopData = $successfulPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $providerKey = strtolower((string) $payment->provider);
                $providerLabel = $paymentProviderLabelsForBreakdown[$providerKey] ?? strtoupper($payment->provider ?: 'Khác');
                $paidAt = $payment->paid_at ?: $payment->created_at;
            ?>
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong><?php echo e($providerLabel); ?></strong><strong>-<?php echo e(number_format((float) $payment->amount, 0, ',', '.')); ?>đ</strong></div>
                <div class="small text-muted mt-1"><?php echo e($paidAt ? $paidAt->format('d/m/Y H:i') : 'Chưa có thời gian'); ?> · <?php echo e($payment->payment_type ?: 'Thanh toán đơn'); ?></div>
                <?php if($payment->transaction_no): ?><div class="small text-muted">Mã giao dịch: <?php echo e($payment->transaction_no); ?></div><?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="alert alert-secondary mb-0">Chưa có giao dịch thành công.</div>
        <?php endif; ?>
        <div class="payment-breakdown-total"><span>Tổng khách đã thanh toán</span><span><?php echo e(number_format($adminPaymentPaidAmount, 0, ',', '.')); ?>đ</span></div>
        <div class="small text-muted mt-2">Phân bổ hiện tại: <?php echo e(number_format($actualDepositPaid, 0, ',', '.')); ?>đ vào cọc và <?php echo e(number_format($additionalPaidTotal, 0, ',', '.')); ?>đ ngoài cọc.</div>
    </template>

    <template id="paymentDetailPrepayment">
        <div class="payment-breakdown-item">
            <div class="fw-bold">Khoản còn dư chưa cần dùng</div>
            <div class="payment-breakdown-formula">Tổng khách đã thanh toán <?php echo e(number_format($adminPaymentPaidAmount, 0, ',', '.')); ?>đ - Tổng booking <?php echo e(number_format($finalTotal, 0, ',', '.')); ?>đ = <?php echo e(number_format($currentOverpaymentTotal, 0, ',', '.')); ?>đ.</div>
            <div class="small text-muted mt-2">Khoản này được giữ trên đơn để tự bù trừ dịch vụ, minibar, phụ thu hoặc chi phí phát sinh sau đó. Không tạo hoàn tiền tự động.</div>
        </div>
    </template>

    <template id="paymentDetailRemaining">
        <div class="payment-breakdown-item">
            <div class="d-flex justify-content-between"><span>Tổng cần thanh toán</span><strong><?php echo e(number_format($finalTotal, 0, ',', '.')); ?>đ</strong></div>
            <div class="d-flex justify-content-between mt-2"><span>Tổng khách đã thanh toán</span><strong>-<?php echo e(number_format($adminPaymentPaidAmount, 0, ',', '.')); ?>đ</strong></div>
            <?php if($currentOverpaymentTotal > 0): ?><div class="small text-muted mt-2">Khách đang trả trước dư <?php echo e(number_format($currentOverpaymentTotal, 0, ',', '.')); ?>đ; số còn phải thu bằng 0đ.</div><?php endif; ?>
        </div>
        <div class="payment-breakdown-total"><span>Còn lại cần thu</span><span class="text-danger"><?php echo e(number_format($remainingTotal, 0, ',', '.')); ?>đ</span></div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function numberFormat(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            const paymentBreakdownOffcanvas = document.getElementById('paymentBreakdownOffcanvas');
            const paymentBreakdownTitle = document.getElementById('paymentBreakdownOffcanvasLabel');
            const paymentBreakdownBody = document.getElementById('paymentBreakdownOffcanvasBody');
            let paymentBreakdownInstance = null;

            if (paymentBreakdownOffcanvas && window.bootstrap && bootstrap.Offcanvas) {
                paymentBreakdownInstance = bootstrap.Offcanvas.getOrCreateInstance(paymentBreakdownOffcanvas);
            }

            document.querySelectorAll('[data-payment-detail]').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    const templateId = trigger.dataset.paymentDetail;
                    const template = document.getElementById(templateId);
                    if (!template || !paymentBreakdownBody) {
                        return;
                    }

                    if (paymentBreakdownTitle) {
                        paymentBreakdownTitle.textContent = trigger.dataset.paymentTitle || 'Chi tiết khoản tiền';
                    }
                    paymentBreakdownBody.innerHTML = template.innerHTML;

                    if (paymentBreakdownInstance) {
                        paymentBreakdownInstance.show();
                    }
                });
            });

            const adultCapacityInput = document.getElementById('adultCapacity');
            const childCapacityInput = document.getElementById('childCapacity');
            const perRoomOverCapacityInput = document.getElementById('perRoomOverCapacity');
            const actualAdultInput = document.getElementById('actualAdultCount');
            const actualChildInput = document.getElementById('actualChildCount');
            const normalCheckInBox = document.getElementById('normalCheckInBox');
            const overCapacityBox = document.getElementById('overCapacityBox');
            const overCapacityAction = document.getElementById('overCapacityAction');
            const extraFeeBox = document.getElementById('extraFeeBox');

            const checkInForm = document.getElementById('checkInForm');
            const checkInBirthdayInput = document.getElementById('checkInScannedBirthday');

            if (checkInBirthdayInput && window.initializeProjectDatePicker) {
                window.initializeProjectDatePicker(checkInBirthdayInput);
            }

            const earlyCheckInAction = document.getElementById('earlyCheckInAction');
            const earlyCheckInIsActive = document.getElementById('earlyCheckInIsActive');
            const earlyCheckInFeeAmount = document.getElementById('earlyCheckInFeeAmount');
            const earlyCheckInPercent = document.getElementById('earlyCheckInPercent');
            const earlyCheckInBasePrice = document.getElementById('earlyCheckInBasePrice');
            const earlyCheckInPolicyText = document.getElementById('earlyCheckInPolicyText');
            const earlyCheckInNowText = document.getElementById('earlyCheckInNowText');
            const earlyCheckInStandardText = document.getElementById('earlyCheckInStandardText');
            const earlyCheckInDurationText = document.getElementById('earlyCheckInDurationText');
            const earlyCheckInFinalTotalPreview = document.getElementById('earlyCheckInFinalTotalPreview');
            const earlyCheckInConfirmModal = document.getElementById('earlyCheckInConfirmModal');
            const confirmEarlyCheckInSubmit = document.getElementById('confirmEarlyCheckInSubmit');
            const checkOutForm = document.getElementById('checkOutForm');
            const checkoutLateFeeConfirm = document.getElementById('checkoutLateFeeConfirm');
            const checkoutLateFeeAmount = document.getElementById('checkoutLateFeeAmount');
            const checkoutLateFeeModal = document.getElementById('checkoutLateFeeModal');
            const confirmCheckoutLateFeeSubmit = document.getElementById('confirmCheckoutLateFeeSubmit');
            const lateArrivalForm = document.getElementById('lateArrivalForm');
            const lateArrivalFeeModal = document.getElementById('lateArrivalFeeModal');
            const confirmLateArrivalFeeSubmit = document.getElementById('confirmLateArrivalFeeSubmit');
            const lateArrivalModalCutoff = document.getElementById('lateArrivalModalCutoff');
            const lateArrivalModalExpected = document.getElementById('lateArrivalModalExpected');
            const lateArrivalModalHoldUntil = document.getElementById('lateArrivalModalHoldUntil');
            const lateArrivalModalPolicy = document.getElementById('lateArrivalModalPolicy');
            const lateArrivalModalBasePrice = document.getElementById('lateArrivalModalBasePrice');
            const lateArrivalModalFormula = document.getElementById('lateArrivalModalFormula');
            const lateArrivalModalAmount = document.getElementById('lateArrivalModalAmount');

            function hideAllActionBoxes() {
                if (extraFeeBox) {
                    extraFeeBox.classList.add('d-none');
                }
            }

            function checkCapacity() {
                if (!adultCapacityInput || !childCapacityInput || !actualAdultInput || !actualChildInput || !normalCheckInBox || !overCapacityBox || !overCapacityAction) {
                    return;
                }

                const adultCapacity = parseInt(adultCapacityInput.value || 0);
                const childCapacity = parseInt(childCapacityInput.value || 0);
                const actualAdult = parseInt(actualAdultInput.value || 0);
                const actualChild = parseInt(actualChildInput.value || 0);
                const hasPerRoomOverCapacity = perRoomOverCapacityInput
                    && perRoomOverCapacityInput.value === '1';
                const isOver = actualAdult > adultCapacity
                    || actualChild > childCapacity
                    || hasPerRoomOverCapacity;

                if (isOver) {
                    normalCheckInBox.classList.add('d-none');
                    overCapacityBox.classList.remove('d-none');
                } else {
                    normalCheckInBox.classList.remove('d-none');
                    overCapacityBox.classList.add('d-none');
                    overCapacityAction.value = '';
                    hideAllActionBoxes();
                }
            }

            function toggleActionBox() {
                if (!overCapacityAction) {
                    return;
                }

                hideAllActionBoxes();

                if (overCapacityAction.value === 'extra_fee' && extraFeeBox) {
                    extraFeeBox.classList.remove('d-none');
                }
            }

            const extraFeeRows = document.getElementById('extraFeeRows');
            const addExtraFeeRowButton = document.getElementById('addExtraFeeRow');
            const allExtraFeeTotalText = document.getElementById('allExtraFeeTotalText');

            function updateAllExtraFeeTotals() {
                if (!extraFeeRows || !allExtraFeeTotalText) {
                    return;
                }

                let grandTotal = 0;

                extraFeeRows.querySelectorAll('.extra-fee-row').forEach(function (row) {
                    const serviceSelect = row.querySelector('.extra-service-select');
                    const quantityInput = row.querySelector('.extra-quantity-input');
                    const totalText = row.querySelector('.extra-total-text');

                    if (!serviceSelect || !quantityInput || !totalText) {
                        return;
                    }

                    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                    const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
                    const quantity = parseInt(quantityInput.value || 1);
                    const total = price * quantity;

                    totalText.value = numberFormat(total);
                    grandTotal += total;
                });

                allExtraFeeTotalText.textContent = numberFormat(grandTotal);
            }

            function bindExtraFeeRow(row) {
                const roomSelect = row.querySelector('.extra-room-select');
                const guestTypeSelect = row.querySelector('.extra-guest-type-select');
                const serviceSelect = row.querySelector('.extra-service-select');
                const quantityInput = row.querySelector('.extra-quantity-input');
                const removeButton = row.querySelector('.remove-extra-fee-row');

                function syncExtraFeeQuantityLimit() {
                    if (!roomSelect || !guestTypeSelect || !quantityInput) {
                        return;
                    }

                    const roomOption = roomSelect.options[roomSelect.selectedIndex];
                    const guestType = guestTypeSelect.value;
                    const maximum = guestType === 'adult'
                        ? parseInt(roomOption?.dataset.adultOver || 0)
                        : (guestType === 'minor' ? parseInt(roomOption?.dataset.minorOver || 0) : 0);

                    if (maximum > 0) {
                        quantityInput.max = maximum;
                        quantityInput.value = Math.min(Math.max(1, parseInt(quantityInput.value || 1)), maximum);
                    } else {
                        quantityInput.removeAttribute('max');
                    }

                    updateAllExtraFeeTotals();
                }

                if (roomSelect) {
                    roomSelect.addEventListener('change', syncExtraFeeQuantityLimit);
                }

                if (guestTypeSelect) {
                    guestTypeSelect.addEventListener('change', syncExtraFeeQuantityLimit);
                }

                if (serviceSelect) {
                    serviceSelect.addEventListener('change', updateAllExtraFeeTotals);
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', updateAllExtraFeeTotals);
                }

                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        const rowCount = extraFeeRows.querySelectorAll('.extra-fee-row').length;

                        if (rowCount <= 1) {
                            row.querySelectorAll('select, input').forEach(function (input) {
                                if (input.tagName === 'SELECT') {
                                    input.value = '';
                                } else if (input.type === 'number') {
                                    input.value = 1;
                                } else {
                                    input.value = '';
                                }
                            });

                            updateAllExtraFeeTotals();
                            return;
                        }

                        row.remove();
                        updateAllExtraFeeTotals();
                    });
                }
            }

            if (extraFeeRows) {
                extraFeeRows.querySelectorAll('.extra-fee-row').forEach(bindExtraFeeRow);
            }

            if (addExtraFeeRowButton && extraFeeRows) {
                addExtraFeeRowButton.addEventListener('click', function () {
                    const firstRow = extraFeeRows.querySelector('.extra-fee-row');

                    if (!firstRow) {
                        return;
                    }

                    const newRow = firstRow.cloneNode(true);

                    newRow.querySelectorAll('select, input').forEach(function (input) {
                        if (input.tagName === 'SELECT') {
                            input.value = '';
                        } else if (input.type === 'number') {
                            input.value = 1;
                        } else {
                            input.value = '';
                        }
                    });

                    const totalInput = newRow.querySelector('.extra-total-text');
                    if (totalInput) {
                        totalInput.value = '0đ';
                    }

                    extraFeeRows.appendChild(newRow);
                    bindExtraFeeRow(newRow);
                    updateAllExtraFeeTotals();
                });
            }

            if (actualAdultInput) {
                actualAdultInput.addEventListener('input', checkCapacity);
            }

            if (actualChildInput) {
                actualChildInput.addEventListener('input', checkCapacity);
            }

            if (overCapacityAction) {
                overCapacityAction.addEventListener('change', toggleActionBox);
            }

            checkCapacity();
            updateAllExtraFeeTotals();

            let checkInSubmitConfirmed = false;
            let earlyCheckInModalInstance = null;

            if (earlyCheckInConfirmModal && window.bootstrap && bootstrap.Modal) {
                earlyCheckInModalInstance = new bootstrap.Modal(earlyCheckInConfirmModal);
            }

            function getEarlyCheckInConfirmMessage() {
                const earlyFee = earlyCheckInFeeAmount ? parseFloat(earlyCheckInFeeAmount.value || 0) : 0;
                const earlyPercent = earlyCheckInPercent ? parseFloat(earlyCheckInPercent.value || 0) : 0;
                const earlyBasePrice = earlyCheckInBasePrice ? parseFloat(earlyCheckInBasePrice.value || 0) : 0;
                const finalTotal = earlyCheckInFinalTotalPreview ? parseFloat(earlyCheckInFinalTotalPreview.value || 0) : 0;
                const policyText = earlyCheckInPolicyText ? earlyCheckInPolicyText.value : '';
                const nowText = earlyCheckInNowText ? earlyCheckInNowText.value : '';
                const standardText = earlyCheckInStandardText ? earlyCheckInStandardText.value : '';
                const durationText = earlyCheckInDurationText ? earlyCheckInDurationText.value : '';

                return 'BÁO GIÁ CHECK-IN SỚM\n\n'
                    + 'Khách đến sớm: ' + durationText + '\n'
                    + 'Hiện tại: ' + nowText + '\n'
                    + 'Giờ check-in chuẩn: ' + standardText + '\n\n'
                    + 'Chính sách: ' + policyText + '\n'
                    + 'Phụ thu: ' + numberFormat(earlyFee)
                    + ' = ' + earlyPercent + '% × ' + numberFormat(earlyBasePrice) + '\n'
                    + 'Tổng tiền sau khi cộng: ' + numberFormat(finalTotal) + '\n\n'
                    + 'Bấm OK nếu khách đã đồng ý phụ thu và tiếp tục check-in.\n'
                    + 'Bấm Cancel nếu khách chưa đồng ý.';
            }

            function submitCheckInWithEarlyFeeAccepted() {
                if (earlyCheckInAction) {
                    earlyCheckInAction.value = 'accept_fee';
                }

                checkInSubmitConfirmed = true;
                checkInForm.submit();
            }

            if (confirmEarlyCheckInSubmit) {
                confirmEarlyCheckInSubmit.addEventListener('click', submitCheckInWithEarlyFeeAccepted);
            }

            if (checkInForm) {
                checkInForm.addEventListener('submit', function (event) {
                    if (checkInSubmitConfirmed) {
                        return;
                    }

                    const isEarly = earlyCheckInIsActive && earlyCheckInIsActive.value === '1';

                    if (isEarly) {
                        event.preventDefault();

                        if (earlyCheckInAction) {
                            earlyCheckInAction.value = '';
                        }

                        if (earlyCheckInModalInstance) {
                            earlyCheckInModalInstance.show();
                            return false;
                        }

                        if (!confirm(getEarlyCheckInConfirmMessage())) {
                            return false;
                        }

                        submitCheckInWithEarlyFeeAccepted();
                        return false;
                    }

                    if (!confirm('Xác nhận check-in cho booking này?')) {
                        event.preventDefault();
                        return false;
                    }
                });
            }

            let lateArrivalSubmitConfirmed = false;
            let lateArrivalFeeModalInstance = null;

            if (lateArrivalFeeModal && window.bootstrap && bootstrap.Modal) {
                lateArrivalFeeModalInstance = new bootstrap.Modal(lateArrivalFeeModal);
            }

            function parseLocalBookingDateTime(value) {
                if (!value) {
                    return null;
                }

                const normalized = value.includes('T') ? value : value.replace(' ', 'T');
                const date = new Date(normalized.length === 16 ? normalized + ':00' : normalized);

                return Number.isNaN(date.getTime()) ? null : date;
            }

            function formatLocalBookingDateTime(date) {
                if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                    return '---';
                }

                const pad = value => String(value).padStart(2, '0');
                return pad(date.getDate()) + '/' + pad(date.getMonth() + 1) + '/' + date.getFullYear()
                    + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
            }

            if (confirmLateArrivalFeeSubmit && lateArrivalForm) {
                confirmLateArrivalFeeSubmit.addEventListener('click', function () {
                    lateArrivalSubmitConfirmed = true;
                    lateArrivalForm.submit();
                });
            }

            if (lateArrivalForm) {
                lateArrivalForm.addEventListener('submit', function (event) {
                    if (lateArrivalSubmitConfirmed) {
                        return;
                    }

                    event.preventDefault();

                    if (!lateArrivalForm.reportValidity()) {
                        return false;
                    }

                    const dateInput = lateArrivalForm.querySelector('[name="expected_arrival_date"]');
                    const timeInput = lateArrivalForm.querySelector('[name="expected_arrival_time"]');
                    const expectedAt = parseLocalBookingDateTime(
                        (dateInput ? dateInput.value : '') + ' ' + (timeInput ? timeInput.value : '')
                    );
                    const cutoffAt = parseLocalBookingDateTime(lateArrivalForm.dataset.cutoffAt || '');
                    const checkOutAt = parseLocalBookingDateTime(lateArrivalForm.dataset.checkOutAt || '');
                    const oneNightTotal = parseFloat(lateArrivalForm.dataset.oneNightTotal || 0);
                    const tier1EndText = lateArrivalForm.dataset.tier1End || '21:00';
                    const tier1Parts = tier1EndText.split(':').map(Number);
                    const tier1Minutes = (tier1Parts[0] || 0) * 60 + (tier1Parts[1] || 0);
                    const percent1 = Number(lateArrivalForm.dataset.percent1 || 0);
                    const percent2 = Number(lateArrivalForm.dataset.percent2 || 0);
                    const percentNextDay = Number(lateArrivalForm.dataset.percentNextDay || 0);
                    const graceMinutes = Math.max(0, Number(lateArrivalForm.dataset.graceMinutes || 0));

                    if (!expectedAt || !cutoffAt || !checkOutAt) {
                        alert('Không đọc được ngày giờ dự kiến đến. Vui lòng kiểm tra lại.');
                        return false;
                    }

                    if (expectedAt <= cutoffAt) {
                        alert('Giờ dự kiến đến phải sau giờ G ' + formatLocalBookingDateTime(cutoffAt) + '.');
                        return false;
                    }

                    if (expectedAt >= checkOutAt) {
                        alert('Giờ dự kiến đến phải trước giờ trả phòng ' + formatLocalBookingDateTime(checkOutAt) + '.');
                        return false;
                    }

                    const sameDate = expectedAt.getFullYear() === cutoffAt.getFullYear()
                        && expectedAt.getMonth() === cutoffAt.getMonth()
                        && expectedAt.getDate() === cutoffAt.getDate();
                    const expectedMinutes = expectedAt.getHours() * 60 + expectedAt.getMinutes();
                    let percent = percentNextDay;
                    let policy = 'Khách dự kiến đến từ ngày hôm sau, phụ thu ' + percentNextDay + '% giá 1 đêm để tiếp tục giữ phòng.';

                    if (sameDate && expectedMinutes <= tier1Minutes) {
                        percent = percent1;
                        policy = 'Khách dự kiến đến sau giờ G đến ' + tier1EndText + ', phụ thu ' + percent1 + '% giá 1 đêm để tiếp tục giữ phòng.';
                    } else if (sameDate) {
                        percent = percent2;
                        policy = 'Khách dự kiến đến sau ' + tier1EndText + ' đến trước 00:00, phụ thu ' + percent2 + '% giá 1 đêm để tiếp tục giữ phòng.';
                    }

                    const amount = Math.round(oneNightTotal * percent / 100);
                    const holdUntil = new Date(Math.min(expectedAt.getTime() + graceMinutes * 60 * 1000, checkOutAt.getTime()));

                    if (lateArrivalModalCutoff) lateArrivalModalCutoff.textContent = formatLocalBookingDateTime(cutoffAt);
                    if (lateArrivalModalExpected) lateArrivalModalExpected.textContent = formatLocalBookingDateTime(expectedAt);
                    if (lateArrivalModalHoldUntil) lateArrivalModalHoldUntil.textContent = formatLocalBookingDateTime(holdUntil);
                    if (lateArrivalModalPolicy) lateArrivalModalPolicy.textContent = policy;
                    if (lateArrivalModalBasePrice) lateArrivalModalBasePrice.textContent = numberFormat(oneNightTotal);
                    if (lateArrivalModalFormula) {
                        lateArrivalModalFormula.textContent = percent + '% × ' + numberFormat(oneNightTotal)
                            + ' = ' + numberFormat(amount);
                    }
                    if (lateArrivalModalAmount) lateArrivalModalAmount.textContent = numberFormat(amount);

                    if (lateArrivalFeeModalInstance) {
                        lateArrivalFeeModalInstance.show();
                        return false;
                    }

                    if (confirm('Phụ thu giữ phòng sau giờ G: ' + numberFormat(amount) + '. Khách đã đồng ý?')) {
                        lateArrivalSubmitConfirmed = true;
                        lateArrivalForm.submit();
                    }

                    return false;
                });
            }

            let checkOutSubmitConfirmed = false;
            let checkoutLateFeeModalInstance = null;

            if (checkoutLateFeeModal && window.bootstrap && bootstrap.Modal) {
                checkoutLateFeeModalInstance = new bootstrap.Modal(checkoutLateFeeModal);
            }

            if (confirmCheckoutLateFeeSubmit && checkOutForm) {
                confirmCheckoutLateFeeSubmit.addEventListener('click', function () {
                    if (checkoutLateFeeConfirm) {
                        checkoutLateFeeConfirm.value = '1';
                    }

                    checkOutSubmitConfirmed = true;
                    checkOutForm.submit();
                });
            }

            if (checkOutForm) {
                checkOutForm.addEventListener('submit', function (event) {
                    if (checkOutSubmitConfirmed) {
                        return;
                    }

                    const lateFee = checkoutLateFeeAmount
                        ? parseFloat(checkoutLateFeeAmount.value || 0)
                        : 0;

                    if (lateFee > 0) {
                        event.preventDefault();

                        if (checkoutLateFeeConfirm) {
                            checkoutLateFeeConfirm.value = '';
                        }

                        if (checkoutLateFeeModalInstance) {
                            checkoutLateFeeModalInstance.show();
                            return false;
                        }

                        if (!confirm('Khách trả phòng muộn và phát sinh phụ thu ' + numberFormat(lateFee) + '. Khách đã đồng ý phụ thu?')) {
                            return false;
                        }

                        if (checkoutLateFeeConfirm) {
                            checkoutLateFeeConfirm.value = '1';
                        }

                        checkOutSubmitConfirmed = true;
                        checkOutForm.submit();
                        return false;
                    }

                    if (!confirm('Xác nhận đã thu đủ tiền và check-out booking này?')) {
                        event.preventDefault();
                        return false;
                    }
                });
            }

            const serviceRows = document.getElementById('serviceRows');
            const addServiceRowButton = document.getElementById('addServiceRowButton');
            const multiServiceTotalText = document.getElementById('multiServiceTotalText');

            function updateServiceRowNames() {
                if (!serviceRows) {
                    return;
                }

                serviceRows.querySelectorAll('.service-input-row').forEach(function (row, index) {
                    const select = row.querySelector('.service-item-select');
                    const quantity = row.querySelector('.service-item-quantity');
                    const note = row.querySelector('.service-item-note');
                    const roomSelect = row.querySelector('.service-room-select');
                    const scopeInput = row.querySelector('.service-scope-input');

                    if (select) {
                        select.name = `services[${index}][service_id]`;
                    }

                    if (quantity) {
                        quantity.name = `services[${index}][quantity]`;
                    }

                    if (note) {
                        note.name = `services[${index}][note]`;
                    }
                    if (roomSelect) {
                        roomSelect.name = `services[${index}][booking_room_id]`;
                    }
                    if (scopeInput) {
                        scopeInput.name = `services[${index}][scope]`;
                        scopeInput.value = roomSelect && roomSelect.value ? 'room' : 'booking';
                    }
                });
            }

            function updateMultiServiceTotals() {
                if (!serviceRows || !multiServiceTotalText) {
                    return;
                }

                let grandTotal = 0;

                serviceRows.querySelectorAll('.service-input-row').forEach(function (row) {
                    const select = row.querySelector('.service-item-select');
                    const quantityInput = row.querySelector('.service-item-quantity');
                    const totalText = row.querySelector('.service-item-total-text');
                    const formulaText = row.querySelector('.service-item-formula');
                    const roomSelect = row.querySelector('.service-room-select');
                    const scopeInput = row.querySelector('.service-scope-input');

                    if (!select || !quantityInput || !totalText) {
                        return;
                    }

                    const selectedOption = select.options[select.selectedIndex];
                    const selectedRoomOption = roomSelect ? roomSelect.options[roomSelect.selectedIndex] : null;
                    const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
                    const quantity = Math.max(1, parseInt(quantityInput.value || 1));
                    const rule = selectedOption ? (selectedOption.dataset.billingRule || 'once') : 'once';
                    const nights = Math.max(1, Number(<?php echo e(json_encode((int) $nightCount)); ?>));
                    const rooms = Math.max(1, parseInt(selectedRoomOption?.dataset.roomCount || 1));
                    const guests = Math.max(1, parseInt(selectedRoomOption?.dataset.guestCount || 1));
                    const multiplier = rule === 'per_night' ? nights
                        : rule === 'per_room' ? rooms
                        : rule === 'per_room_per_night' ? rooms * nights
                        : rule === 'per_guest' ? guests
                        : rule === 'per_guest_per_night' ? guests * nights
                        : 1;
                    const billedQuantity = quantity * multiplier;
                    const total = price * billedQuantity;

                    if (scopeInput) {
                        scopeInput.value = roomSelect && roomSelect.value ? 'room' : 'booking';
                    }
                    totalText.value = numberFormat(total);
                    if (formulaText) {
                        formulaText.textContent = selectedOption && selectedOption.value
                            ? `${numberFormat(price)} × ${quantity}${multiplier > 1 ? ` × ${multiplier}` : ''} = ${numberFormat(total)}`
                            : 'Chọn dịch vụ để xem cách tính';
                    }
                    grandTotal += total;
                });

                multiServiceTotalText.textContent = numberFormat(grandTotal);
            }

            function bindServiceRow(row) {
                const select = row.querySelector('.service-item-select');
                const quantityInput = row.querySelector('.service-item-quantity');
                const removeButton = row.querySelector('.remove-service-row');
                const roomSelect = row.querySelector('.service-room-select');

                if (select) {
                    select.addEventListener('change', updateMultiServiceTotals);
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', updateMultiServiceTotals);
                }
                if (roomSelect) {
                    roomSelect.addEventListener('change', function () {
                        updateServiceRowNames();
                        updateMultiServiceTotals();
                    });
                }

                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        const rowCount = serviceRows.querySelectorAll('.service-input-row').length;

                        if (rowCount <= 1) {
                            row.querySelectorAll('select, input').forEach(function (input) {
                                if (input.tagName === 'SELECT') {
                                    input.value = '';
                                } else if (input.type === 'number') {
                                    input.value = 1;
                                } else {
                                    input.value = '';
                                }
                            });

                            updateMultiServiceTotals();
                            return;
                        }

                        row.remove();
                        updateServiceRowNames();
                        updateMultiServiceTotals();
                    });
                }
            }

            if (serviceRows) {
                serviceRows.querySelectorAll('.service-input-row').forEach(bindServiceRow);
            }

            if (addServiceRowButton && serviceRows) {
                addServiceRowButton.addEventListener('click', function () {
                    const firstRow = serviceRows.querySelector('.service-input-row');

                    if (!firstRow) {
                        return;
                    }

                    const newRow = firstRow.cloneNode(true);

                    newRow.querySelectorAll('select, input').forEach(function (input) {
                        if (input.tagName === 'SELECT') {
                            input.value = '';
                        } else if (input.type === 'number') {
                            input.value = 1;
                        } else {
                            input.value = '';
                        }
                    });

                    const totalText = newRow.querySelector('.service-item-total-text');

                    if (totalText) {
                        totalText.value = '0đ';
                    }

                    serviceRows.appendChild(newRow);
                    bindServiceRow(newRow);
                    updateServiceRowNames();
                    updateMultiServiceTotals();
                });
            }

            updateServiceRowNames();
            updateMultiServiceTotals();

            const adminPaymentMode = document.getElementById('adminPaymentMode');
            const adminDirectPaymentBox = document.getElementById('adminDirectPaymentBox');
            const adminVnpayPaymentBox = document.getElementById('adminVnpayPaymentBox');
            const adminDirectPaymentMethod = document.getElementById('adminDirectPaymentMethod');
            const adminDirectPaymentType = document.getElementById('adminDirectPaymentType');
            const adminDirectCustomAmountBox = document.getElementById('adminDirectCustomAmountBox');
            const adminDirectCustomAmount = document.getElementById('adminDirectCustomAmount');
            const adminDirectPaymentSubmit = document.getElementById('adminDirectPaymentSubmit');
            const adminDirectPaymentSpeakText = document.getElementById('adminDirectPaymentSpeakText');
            const adminDirectChangeDueBox = document.getElementById('adminDirectChangeDueBox');
            const adminDirectChangeDueText = document.getElementById('adminDirectChangeDueText');
            const adminRemainingAmount = Number(<?php echo e(json_encode((float) $remainingTotal)); ?>);
            const adminDepositPercentLabel = <?php echo json_encode($depositPercentLabel, 15, 512) ?>;
            const adminVnpayPaymentType = document.getElementById('adminVnpayPaymentType');
            const adminVnpayPaymentSubmit = document.getElementById('adminVnpayPaymentSubmit');
            const adminVnpayPaymentSpeakText = document.getElementById('adminVnpayPaymentSpeakText');

            function formatMoneyVn(amount) {
                const number = Number(amount || 0);
                return new Intl.NumberFormat('vi-VN').format(Math.max(0, number)) + 'đ';
            }

            function getSelectedOptionAmount(select) {
                if (!select || !select.selectedOptions || !select.selectedOptions.length) {
                    return 0;
                }

                return Number(select.selectedOptions[0].dataset.amount || 0);
            }

            function getAdminPaymentMethodLabel() {
                if (!adminPaymentMode) {
                    return 'thanh toán';
                }

                if (adminPaymentMode.value === 'bank_transfer') {
                    return 'chuyển khoản tại quầy';
                }

                return 'tiền mặt tại quầy';
            }

            function updateAdminDirectPaymentText() {
                if (!adminDirectPaymentType || !adminDirectPaymentSubmit) {
                    return;
                }

                const type = adminDirectPaymentType.value;
                const methodLabel = getAdminPaymentMethodLabel();
                let amount = getSelectedOptionAmount(adminDirectPaymentType);
                let message = '';

                if (type === 'custom') {
                    amount = Number(adminDirectCustomAmount?.value || 0);
                    const amountText = amount > 0 ? formatMoneyVn(amount) : 'số tiền khách trả';
                    const retainedPrepayment = Math.max(0, amount - adminRemainingAmount);
                    if (adminDirectChangeDueText) {
                        adminDirectChangeDueText.textContent = formatMoneyVn(retainedPrepayment);
                    }
                    adminDirectPaymentSubmit.innerHTML = '<i class="bx bx-money-withdraw me-1"></i> Ghi nhận đã thu ' + amountText;
                    message = 'Lễ tân nói với khách: Anh/chị thanh toán ' + amountText + ' bằng ' + methodLabel + ' ạ.'
                        + (retainedPrepayment > 0
                            ? ' Phần vượt ' + formatMoneyVn(retainedPrepayment) + ' sẽ được giữ làm tiền trả trước để bù trừ phát sinh.'
                            : '');
                } else if (type === 'deposit_30') {
                    if (adminDirectChangeDueText) adminDirectChangeDueText.textContent = '0đ';
                    adminDirectPaymentSubmit.innerHTML = '<i class="bx bx-money-withdraw me-1"></i> Ghi nhận bổ sung cọc ' + formatMoneyVn(amount);
                    message = 'Lễ tân nói với khách: Booking hiện còn thiếu ' + formatMoneyVn(amount) + ' để đủ mức cọc ' + adminDepositPercentLabel + ' ạ.';
                } else {
                    if (adminDirectChangeDueText) adminDirectChangeDueText.textContent = '0đ';
                    adminDirectPaymentSubmit.innerHTML = '<i class="bx bx-money-withdraw me-1"></i> Ghi nhận đã thu ' + formatMoneyVn(amount);
                    message = 'Lễ tân nói với khách: Số tiền còn lại cần thanh toán là ' + formatMoneyVn(amount) + ' ạ.';
                }

                if (adminDirectPaymentSpeakText) {
                    adminDirectPaymentSpeakText.textContent = message;
                }
            }

            function updateAdminVnpayPaymentText() {
                if (!adminVnpayPaymentType || !adminVnpayPaymentSubmit) {
                    return;
                }

                const amount = getSelectedOptionAmount(adminVnpayPaymentType);
                const type = adminVnpayPaymentType.value;
                const purpose = type === 'deposit_30' ? 'bổ sung để đủ cọc ' + adminDepositPercentLabel : 'thanh toán số tiền còn lại';
                const amountText = formatMoneyVn(amount);

                adminVnpayPaymentSubmit.innerHTML = '<i class="bx bx-envelope me-1"></i> Gửi email VNPay ' + amountText;

                if (adminVnpayPaymentSpeakText) {
                    adminVnpayPaymentSpeakText.textContent = 'Email gửi cho khách sẽ có mã booking, mã giao dịch, nội dung "' + purpose + '", số tiền ' + amountText + ' và nút thanh toán qua VNPay.';
                }
            }

            function toggleAdminPaymentBoxes() {
                if (!adminPaymentMode) {
                    return;
                }

                const mode = adminPaymentMode.value;
                const isDirectPayment = mode === 'cash' || mode === 'bank_transfer';
                const isVnpayPayment = mode === 'vnpay';

                if (adminDirectPaymentBox) {
                    adminDirectPaymentBox.classList.toggle('d-none', !isDirectPayment);
                }

                if (adminVnpayPaymentBox) {
                    adminVnpayPaymentBox.classList.toggle('d-none', !isVnpayPayment);
                }

                if (adminDirectPaymentMethod) {
                    adminDirectPaymentMethod.value = isDirectPayment ? mode : '';
                }

                toggleAdminDirectCustomAmount();
                updateAdminDirectPaymentText();
                updateAdminVnpayPaymentText();
            }

            function toggleAdminDirectCustomAmount() {
                if (!adminDirectPaymentType || !adminDirectCustomAmountBox || !adminDirectCustomAmount) {
                    return;
                }

                const isCustom = adminDirectPaymentType.value === 'custom';
                adminDirectCustomAmountBox.classList.toggle('d-none', !isCustom);
                adminDirectCustomAmount.required = isCustom;
                adminDirectCustomAmount.disabled = !isCustom;

                if (!isCustom) {
                    adminDirectCustomAmount.value = '';
                } else {
                    const option = adminDirectPaymentType.selectedOptions?.[0];
                    const entryMode = option?.dataset.entryMode || 'manual';
                    const suggestedAmount = Number(option?.dataset.amount || 0);
                    adminDirectCustomAmount.value = entryMode === 'remaining' && suggestedAmount > 0
                        ? String(Math.round(suggestedAmount))
                        : '';
                }

                updateAdminDirectPaymentText();
            }

            if (adminPaymentMode) {
                adminPaymentMode.addEventListener('change', toggleAdminPaymentBoxes);
                toggleAdminPaymentBoxes();
            }

            if (adminDirectPaymentType) {
                adminDirectPaymentType.addEventListener('change', toggleAdminDirectCustomAmount);
                toggleAdminDirectCustomAmount();
            }

            if (adminDirectCustomAmount) {
                adminDirectCustomAmount.addEventListener('input', updateAdminDirectPaymentText);
            }

            if (adminVnpayPaymentType) {
                adminVnpayPaymentType.addEventListener('change', updateAdminVnpayPaymentText);
                updateAdminVnpayPaymentText();
            }

            function initializeStayChangeDateTimePickers() {
                if (typeof flatpickr === 'undefined') return;

                const checkInDate = document.getElementById('newCheckInDateVn');
                const checkOutDate = document.getElementById('newCheckOutDateVn');
                const checkInTime = document.getElementById('newCheckInTimeVn');
                const checkOutTime = document.getElementById('newCheckOutTimeVn');

                const dateOptions = {
                    locale: window.flatpickr?.l10ns?.vn || 'default',
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd/m/Y',
                    allowInput: true,
                    disableMobile: true,
                    monthSelectorType: 'dropdown'
                };

                if (checkInDate && !checkInDate._flatpickr) {
                    flatpickr(checkInDate, {
                        ...dateOptions,
                        minDate: checkInDate.getAttribute('min') || 'today',
                        onChange(selectedDates) {
                            if (!checkOutDate?._flatpickr || selectedDates.length === 0) return;
                            const minimum = new Date(selectedDates[0]);
                            minimum.setDate(minimum.getDate() + 1);
                            checkOutDate._flatpickr.set('minDate', minimum);
                            const current = checkOutDate._flatpickr.selectedDates[0];
                            if (!current || current < minimum) {
                                checkOutDate._flatpickr.setDate(minimum, true);
                            }
                        }
                    });
                }

                if (checkOutDate && !checkOutDate._flatpickr) {
                    flatpickr(checkOutDate, {
                        ...dateOptions,
                        minDate: checkOutDate.getAttribute('min') || null
                    });
                }

                const timeOptions = {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altInput: true,
                    altFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 1,
                    allowInput: true,
                    disableMobile: true,
                    locale: window.flatpickr?.l10ns?.vn || 'default'
                };

                if (checkInTime && !checkInTime._flatpickr) flatpickr(checkInTime, timeOptions);
                if (checkOutTime && !checkOutTime._flatpickr) flatpickr(checkOutTime, timeOptions);
            }

            initializeStayChangeDateTimePickers();

            if (document.getElementById('expectedArrivalTime') && typeof flatpickr !== 'undefined') {
                flatpickr('#expectedArrivalTime', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 5,
                    locale: 'vn',
                    allowInput: true
                });
            }

            if (document.getElementById('extendCheckOutTime') && typeof flatpickr !== 'undefined') {
                flatpickr('#extendCheckOutTime', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 30,
                    locale: 'vn'
                });
            }

            document.querySelectorAll('.js-category-room-form').forEach(function (form) {
                const mode = form.querySelector('.js-room-assignment-mode');
                const category = form.querySelector('.js-room-category-target');
                const picker = form.querySelector('[data-manual-room-picker]');
                const roomSelect = form.querySelector('.js-manual-room-select');

                const sync = function () {
                    const isManual = mode?.value === 'manual';
                    picker?.classList.toggle('d-none', !isManual);
                    if (roomSelect) {
                        roomSelect.disabled = !isManual;
                        roomSelect.required = isManual;
                        const categoryId = category?.value || '';
                        Array.from(roomSelect.options).forEach(function (option) {
                            if (!option.value) return;
                            const visible = categoryId !== '' && option.dataset.categoryId === categoryId;
                            option.hidden = !visible;
                            option.disabled = !visible;
                            if (!visible) option.selected = false;
                        });
                    }
                };

                mode?.addEventListener('change', sync);
                category?.addEventListener('change', sync);
                form.addEventListener('submit', function (event) {
                    if (mode?.value !== 'manual' || !roomSelect) return;
                    const expected = Number(form.dataset.roomCount || 1);
                    const chosen = Array.from(roomSelect.selectedOptions).filter(option => option.value).length;
                    if (chosen !== expected) {
                        event.preventDefault();
                        alert('Vui lòng chọn đúng ' + expected + ' phòng.');
                    }
                });
                sync();
            });

            document.querySelectorAll('.js-same-rank-room-form').forEach(function (form) {
                const mode = form.querySelector('.js-room-assignment-mode');
                const oldRoom = form.querySelector('.js-old-room-select');
                const picker = form.querySelector('[data-manual-room-picker]');
                const roomSelect = form.querySelector('.js-manual-room-select');

                const sync = function () {
                    const isManual = mode?.value === 'manual';
                    picker?.classList.toggle('d-none', !isManual);
                    if (!roomSelect) return;
                    roomSelect.disabled = !isManual;
                    roomSelect.required = isManual;
                    const categoryId = oldRoom?.selectedOptions?.[0]?.dataset?.categoryId || '';
                    Array.from(roomSelect.options).forEach(function (option) {
                        if (!option.value) return;
                        const visible = categoryId !== '' && option.dataset.categoryId === categoryId;
                        option.hidden = !visible;
                        option.disabled = !visible;
                        if (!visible) option.selected = false;
                    });
                };

                mode?.addEventListener('change', sync);
                oldRoom?.addEventListener('change', sync);
                sync();
            });

            const bookingDetailRoot = document.getElementById('bookingDetailRoot');
            const toggleSecondaryBookingInfo = document.getElementById('toggleSecondaryBookingInfo');

            if (bookingDetailRoot && bookingDetailRoot.classList.contains('reception-compact')) {
                const secondaryHeadings = ['Lịch sử thao tác', 'Khách hàng', 'Lưu trú', 'Ghi chú nội bộ'];
                bookingDetailRoot.querySelectorAll('.card-clean').forEach(function (card) {
                    const heading = card.querySelector('.card-title-clean h5');
                    if (heading && secondaryHeadings.includes(heading.textContent.trim())) {
                        card.classList.add('secondary-booking-card');
                    }
                });

                if (toggleSecondaryBookingInfo) {
                    toggleSecondaryBookingInfo.addEventListener('click', function () {
                        const showing = bookingDetailRoot.classList.toggle('show-secondary');
                        toggleSecondaryBookingInfo.innerHTML = showing
                            ? '<i class="bx bx-hide me-1"></i> Ẩn thông tin bổ sung'
                            : '<i class="bx bx-layer me-1"></i> Xem thông tin bổ sung';
                    });
                }
            }
        });
    </script>
<?php echo $__env->make('partials.cccd-scanner-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>





<script>
document.addEventListener('DOMContentLoaded', function () {
    const roomModalBody = document.getElementById('roomAdjustmentModalBody');
    const roomModalElement = document.getElementById('roomAdjustmentModal');
    if (!roomModalBody || !roomModalElement) return;

    const stateKey = 'booking-support-state-<?php echo e($booking->id); ?>';
    const roomPanel = Array.from(document.querySelectorAll('details.compact-panel')).find(function (panel) {
        const summary = panel.querySelector(':scope > summary');
        return summary && (summary.textContent || '').includes('Quản lý phòng: thêm phòng / đổi hạng');
    });
    const sameRankPanel = Array.from(document.querySelectorAll('details.compact-panel')).find(function (panel) {
        const summary = panel.querySelector(':scope > summary');
        return summary && (summary.textContent || '').includes('Đổi phòng cùng hạng');
    });

    const buildSection = (title, content, key) => {
        const details = document.createElement('details');
        details.className = 'compact-panel mb-2 js-room-operation';
        details.dataset.operation = key;
        details.innerHTML = `<summary><span>${title}</span><span class="badge-clean status-muted">Mở biểu mẫu</span></summary><div class="compact-panel-body"></div>`;
        details.querySelector('.compact-panel-body').appendChild(content);
        return details;
    };

    if (roomPanel) {
        const sourceBody = roomPanel.querySelector(':scope > .compact-panel-body');
        const forms = sourceBody ? Array.from(sourceBody.querySelectorAll(':scope form.mini-form-box')) : [];
        const roomPreview = sourceBody?.querySelector('#room-operation-preview') || null;
        if (roomPreview) roomPreview.remove();
        roomModalBody.innerHTML = '';
        if (roomPreview) roomModalBody.appendChild(roomPreview);

        forms.forEach(function (form, index) {
            const heading = form.querySelector('h6');
            const rawTitle = heading?.textContent?.trim() || `Thao tác ${index + 1}`;
            if (heading) heading.remove();
            const title = rawTitle.includes('Thêm') ? 'Thêm phòng'
                : (rawTitle.includes('toàn bộ') ? 'Đổi hạng toàn bộ đơn' : (<?php echo e($booking->bookingRooms->count() <= 1 ? 'true' : 'false'); ?> ? 'Đổi hạng phòng' : 'Đổi hạng một phòng'));
            const key = rawTitle.includes('Thêm') ? 'add-room'
                : (rawTitle.includes('toàn bộ') ? 'change-all-category' : 'change-one-category');
            form.dataset.keepSupportPosition = key;
            roomModalBody.appendChild(buildSection(title, form, key));
        });

        if (sameRankPanel) {
            sameRankPanel.open = true;
            sameRankPanel.classList.remove('mb-3');
            sameRankPanel.querySelectorAll('form').forEach((form) => form.dataset.keepSupportPosition = 'change-room');
            roomModalBody.insertBefore(buildSection('Đổi phòng cùng hạng', sameRankPanel, 'change-room'), roomModalBody.children[1] || null);
        }

        roomPanel.remove();
    } else {
        roomModalBody.innerHTML = '<div class="alert alert-info mb-0">Đơn hiện không có thao tác điều chỉnh phòng phù hợp với trạng thái này.</div>';
    }

    document.querySelectorAll('form[data-keep-support-position]').forEach(function (form) {
        form.addEventListener('submit', function () {
            try {
                sessionStorage.setItem(stateKey, JSON.stringify({
                    modal: 'roomAdjustmentModal',
                    operation: form.dataset.keepSupportPosition,
                    y: window.scrollY,
                    savedAt: Date.now()
                }));
            } catch (error) {}
        });
    });

    try {
        const raw = sessionStorage.getItem(stateKey);
        if (raw) {
            const state = JSON.parse(raw);
            sessionStorage.removeItem(stateKey);
            if (state && Date.now() - Number(state.savedAt || 0) < 180000 && state.modal === 'roomAdjustmentModal') {
                const operation = roomModalBody.querySelector(`[data-operation="${state.operation}"]`);
                if (operation) operation.open = true;
                const modal = bootstrap.Modal.getOrCreateInstance(roomModalElement);
                modal.show();
                roomModalElement.addEventListener('shown.bs.modal', function restorePosition() {
                    operation?.scrollIntoView({ block: 'center', behavior: 'auto' });
                    roomModalElement.removeEventListener('shown.bs.modal', restorePosition);
                });
            }
        }
    } catch (error) {}

    const previewTargetId = window.location.hash ? window.location.hash.slice(1) : '';
    if (previewTargetId) {
        const previewTarget = document.getElementById(previewTargetId);
        if (previewTarget) {
            const parentDetails = previewTarget.closest('details');
            if (parentDetails) parentDetails.open = true;
            if (previewTargetId === 'room-operation-preview' && roomModalElement) {
                bootstrap.Modal.getOrCreateInstance(roomModalElement).show();
            }
            window.setTimeout(() => previewTarget.scrollIntoView({block: 'center', behavior: 'auto'}), 200);
        }
    }
});
</script>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/_workspace.blade.php ENDPATH**/ ?>