    @php
        $isReceptionDesk = in_array(auth()->user()?->role, ['receptionist', 'receptionist_lead'], true);
        $canConfirmRefund = in_array(auth()->user()?->role, ['manager', 'super_admin'], true);

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
        $depositPercentPolicy = (float) app(\App\Services\HotelPolicyService::class)->depositRate($booking) * 100;
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
            return !in_array($room->status ?? null, ['available', 'reserved'], true);
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
        $isBeforeHourlyCheckInNow = false;
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
            $booking->status === 'confirmed'
            && $booking->booking_type === 'hourly'
            && $bookingCheckInAtForFlow
            && $nowVnForCheckInFlow->lessThan($bookingCheckInAtForFlow)
        ) {
            $isBeforeHourlyCheckInNow = true;
            $beforeBookingDateMessage = 'Chưa đến giờ bắt đầu booking theo giờ. Nếu khách muốn vào sớm, hãy đổi thời gian lưu trú để hệ thống tính lại giá và kiểm tra lại phòng trống.';
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
        $isCancelledBooking = in_array($booking->status, ['cancelled', 'canceled'], true);
        // Booking đã hủy không còn nghĩa vụ phải thu thêm. Tiền đã thu/đã hoàn
        // được trình bày riêng để tránh workspace báo nợ dù đơn đã kết thúc.
        $adminPaymentDepositAmount = $isCancelledBooking ? 0.0 : (float) $paymentAllocation['deposit_shortfall'];
        $remainingTotal = $isCancelledBooking ? 0.0 : (float) $paymentAllocation['remaining'];
        $currentOverpaymentTotal = $isCancelledBooking ? 0.0 : (float) $paymentAllocation['overpayment'];
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

        $assignedRoomIdsForManage = $booking->bookingRooms
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $roomCandidatesForBookingManage = \App\Models\Room::query()
            ->with('category')
            ->when($assignedRoomIdsForManage !== [], fn ($query) => $query->whereNotIn('id', $assignedRoomIdsForManage))
            ->availableForPeriod(
                $booking->check_in_at,
                $booking->check_out_at,
                $booking->id,
                (int) ($booking->cleaning_buffer_minutes ?? 0),
                ['available', 'cleaning'],
                true
            )
            ->orderByRaw("CASE rooms.status WHEN 'available' THEN 0 WHEN 'cleaning' THEN 1 ELSE 9 END")
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();

        $roomManageCountByCategory = $roomCandidatesForBookingManage
            ->groupBy('room_category_id')
            ->map->count();

        $roomCategoriesForBookingManage = \App\Models\RoomCategory::where('status', 'active')
            ->orderBy('price')
            ->get()
            ->each(function ($category) use ($roomManageCountByCategory) {
                $category->setAttribute('available_rooms_count', (int) ($roomManageCountByCategory[$category->id] ?? 0));
            });

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
        $rescheduledAfterCutoffAt = $isRescheduledAfterCutoff ? $booking->rescheduledAfterCutoffAt() : null;

        // Đơn được lễ tân chuyển từ ngày tương lai về hôm nay là một lịch nhận phòng mới.
        // Tuyệt đối không dựng lại giờ G/no-show hay "thời gian ân hạn" cho chính lần đổi lịch này.
        // Backend guardCheckInArrivalTime()/usesLateArrivalNoShowPolicy() cũng dùng cùng nguyên tắc.
        $normalNoShowEligible = $usesLateArrivalNoShowPolicy
            && $booking->status == 'confirmed'
            && !$booking->actual_check_in
            && $lateShowCheckInAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowCheckInAt)
            && (!$lateShowCheckOutAt || $lateShowNowVn->lessThan($lateShowCheckOutAt));
        $canNoShowNow = $normalNoShowEligible;
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

        $disableCheckInSubmitNow = $isBeforeBookingDateNow
            || $isBeforeHourlyCheckInNow
            || $lateShowIsPastStayTime
            || $lateShowIsCheckInTooLate;

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
    @endphp

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

    <div class="admin-wrapper booking-detail-page {{ $isReceptionDesk ? 'reception-compact' : '' }}" id="bookingDetailRoot" data-workspace-mode="{{ $workspaceMode ?? 'main' }}">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.bookings.index') }}">Đặt phòng</a> /
                Chi tiết
            </p>

            <div class="page-topbar">
                <div class="page-title">
                    <h2>Chi tiết đặt phòng</h2>
                    <p>Thông tin chính, tình trạng hiện tại và các thao tác cần thiết của đơn.</p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if ($isReceptionDesk)
                        <button type="button" class="btn btn-outline-secondary" id="toggleSecondaryBookingInfo">
                            <i class="bx bx-layer me-1"></i> Xem thông tin bổ sung
                        </button>
                    @endif
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>
            </div>

@if ($errors->any())
                @php
                    $uniqueFormErrors = collect($errors->all())->filter()->unique()->values();
                @endphp
                <div class="alert alert-danger">
                    <strong>Vui lòng kiểm tra lại:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($uniqueFormErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            @if ($latestCancellationRequest && $latestCancellationRequest->status === 'pending')
                <section class="card-clean mb-3 customer-request-card" style="border: 1px solid #f59e0b; background: #fffbeb;">
                    <div class="card-title-clean">
                        <div>
                            <h5 class="text-warning-emphasis">Khách đang yêu cầu hủy đơn</h5>
                            <p class="card-subtitle-clean mb-0">
                                Gửi lúc {{ optional($latestCancellationRequest->requested_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}.
                                Chỉ khi lễ tân xác nhận thì booking mới bị hủy và phòng mới được mở bán lại.
                            </p>
                        </div>
                        <span class="badge-clean" style="background:#fef3c7;color:#92400e;">Chờ xác nhận</span>
                    </div>

                    @if ($latestCancellationRequest->reason)
                        <div class="soft-note mb-3">
                            <strong>Lý do khách gửi:</strong> {{ $latestCancellationRequest->reason }}
                        </div>
                    @endif

                    @php
                        $requestPolicy = $latestCancellationRequest->policy_snapshot ?? [];
                    @endphp
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><div class="soft-note h-100"><span class="text-muted small">Tiền cọc đã thanh toán</span><div class="fw-bold">{{ number_format($requestPolicy['paid_amount'] ?? 0, 0, ',', '.') }}đ</div></div></div>
                        <div class="col-md-6"><div class="soft-note h-100"><span class="text-muted small">Xử lý khi hủy</span><div class="fw-bold text-danger">Mất toàn bộ tiền cọc, không hoàn lại</div></div></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <form action="{{ route('admin.bookings.cancellation-request.approve', $booking) }}" method="POST"
                                onsubmit="return confirm('Xác nhận hủy đơn và mở bán lại phòng ngay?')">
                                @csrf
                                @method('PATCH')
                                <label class="form-label fw-semibold">Ghi chú xác nhận <span class="text-muted fw-normal">(không bắt buộc)</span></label>
                                <textarea name="review_note" class="form-control mb-2" rows="2" maxlength="1000" placeholder="Thông tin đã trao đổi với khách..."></textarea>
                                <button class="btn btn-danger w-100" type="submit">
                                    <i class="bx bx-check-circle me-1"></i> Xác nhận hủy và mở bán phòng
                                </button>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <form action="{{ route('admin.bookings.cancellation-request.reject', $booking) }}" method="POST"
                                onsubmit="return confirm('Từ chối yêu cầu hủy này? Đơn sẽ tiếp tục được giữ.')">
                                @csrf
                                @method('PATCH')
                                <label class="form-label fw-semibold">Lý do từ chối</label>
                                <textarea name="review_note" class="form-control mb-2" rows="2" maxlength="1000" required placeholder="Ví dụ: khách xác nhận tiếp tục lưu trú..."></textarea>
                                <button class="btn btn-outline-secondary w-100" type="submit">
                                    <i class="bx bx-x-circle me-1"></i> Từ chối yêu cầu hủy
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            @elseif ($latestCancellationRequest && $latestCancellationRequest->status === 'rejected')
                <div class="alert alert-secondary mb-3">
                    Yêu cầu hủy gần nhất đã bị từ chối
                    {{ optional($latestCancellationRequest->reviewed_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}.
                    {{ $latestCancellationRequest->review_note }}
                </div>
            @endif


            @if ($latestRoomIssueRequest)
                @php
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
                @endphp
                <details class="compact-panel mb-3 customer-request-card" id="room-issue-admin">
                    <summary>
                        <span>
                            Sự cố phòng khách đã báo
                            <span class="text-muted fw-normal small ms-2">
                                Phòng {{ $latestRoomIssueRequest->currentRoom?->room_number ?? '---' }} · {{ \Illuminate\Support\Str::limit($latestRoomIssueRequest->issue_description, 48) }}
                            </span>
                        </span>
                        <span class="badge-clean {{ $latestRoomIssueRequest->status === 'pending' ? 'status-warning' : 'status-done' }}">
                            {{ $issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus }}
                        </span>
                    </summary>
                    <div class="compact-panel-body">
                        <div class="soft-note mb-3"><strong>Khách báo:</strong> {{ $latestRoomIssueRequest->issue_description }}</div>

                        @if ($latestRoomIssueRequest->status === 'pending')
                            <div class="alert alert-warning small mb-3">
                                Yêu cầu đang chờ quản lý duyệt tại menu <strong>Sự cố phòng</strong>. Lễ tân không cần tự đổi phòng trong khung này.
                            </div>
                        @else
                            <div class="row g-2 mb-3">
                                <div class="col-md-4"><div class="soft-note h-100"><span class="text-muted small">Phương án đã duyệt</span><div class="fw-bold">{{ $issueResolutionLabels[$latestRoomIssueRequest->resolution_type] ?? '---' }}</div></div></div>
                                <div class="col-md-4"><div class="soft-note h-100"><span class="text-muted small">Phòng mới</span><div class="fw-bold">{{ $latestRoomIssueRequest->approvedRoom?->room_number ?? 'Giữ phòng cũ' }}</div></div></div>
                                <div class="col-md-4"><div class="soft-note h-100"><span class="text-muted small">Mã bù đắp</span><div class="fw-bold">{{ collect($latestRoomIssueRequest->promotion_codes)->implode(', ') ?: 'Không áp dụng' }}</div></div></div>
                            </div>
                            <div class="soft-note"><strong>Quản lý:</strong> {{ $latestRoomIssueRequest->admin_note }}</div>
                        @endif

                        @if($latestRoomIssueRequest->status === 'pending' && $latestRoomIssueRequest->workflow_status === 'waiting_guest_confirmation')
                            <a href="{{ route('admin.bookings.room-issue-proposal', $booking) }}" class="btn btn-primary w-100 mt-3">
                                <i class="bx bx-conversation me-1"></i> Xem phương án để trao đổi với khách
                            </a>
                        @endif

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
                                    <div class="small text-muted">Booking {{ $booking->booking_code }} · Phòng {{ $latestRoomIssueRequest->currentRoom?->room_number ?? '---' }}</div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4"><div class="soft-note h-100"><span class="small text-muted d-block">Trạng thái</span><strong>{{ $issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus }}</strong></div></div>
                                    <div class="col-md-4"><div class="soft-note h-100"><span class="small text-muted d-block">Phương án</span><strong>{{ $issueResolutionLabels[$latestRoomIssueRequest->resolution_type] ?? 'Chưa có phương án' }}</strong></div></div>
                                    <div class="col-md-4"><div class="soft-note h-100"><span class="small text-muted d-block">Phòng mới</span><strong>{{ $latestRoomIssueRequest->approvedRoom?->room_number ?? 'Không có' }}</strong></div></div>
                                </div>
                                <div class="soft-note mb-3"><span class="small text-muted d-block mb-1">Khách báo</span><strong>{{ $latestRoomIssueRequest->issue_description }}</strong></div>
                                @if($latestRoomIssueRequest->admin_note)
                                    <div class="soft-note mb-3"><span class="small text-muted d-block mb-1">Phản hồi xử lý sự cố</span>{{ $latestRoomIssueRequest->admin_note }}</div>
                                @endif
                                @if($issueRepairCompleted)
                                    <div class="alert alert-success mb-0"><strong>Đã sửa xong</strong>@if($latestRoomIssueRequest->repair_completed_at) · {{ $latestRoomIssueRequest->repair_completed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}@endif @if($latestRoomIssueRequest->repair_note)<div class="mt-1">{{ $latestRoomIssueRequest->repair_note }}</div>@endif</div>
                                @elseif($latestRoomIssueRequest->repair_status === 'waiting')
                                    <div class="alert alert-info mb-0">Buồng phòng đang khắc phục sự cố.</div>
                                @endif
                            </div>
                            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                @if (in_array(auth()->user()->role ?? null, ['super_admin', 'manager'], true))
                                    <a href="{{ route('admin.room-issues.show', $latestRoomIssueRequest) }}" class="btn btn-outline-secondary">Mở trang xử lý sự cố</a>
                                @endif
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


            <section class="card-clean mb-3" id="bookingSupportActions">
                @if ($isBeforeBookingDateNow)
                    <div class="alert alert-danger compact-alert border-2 mb-3" role="alert">
                        <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Chưa đến ngày nhận phòng</div>
                        <div class="small">
                            Đơn dự kiến nhận phòng lúc <strong>{{ $lateShowCheckInAt?->format('d/m/Y H:i') ?? '---' }}</strong>.
                            Hiện tại là <strong>{{ $lateShowNowVn->format('d/m/Y H:i') }}</strong>.
                        </div>
                        <div class="small mt-1">Nếu khách muốn nhận ngay, hãy đổi ngày lưu trú và kiểm tra lại phòng trống trước khi xác nhận.</div>
                    </div>
                @elseif ($showLateCheckInWarning)
                    <div class="alert {{ $lateShowAlertClass }} compact-alert border-2 mb-3" role="alert">
                        <div class="fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>{{ $lateShowTitle }}</div>
                        <div class="small">{{ $lateShowMessage }}</div>
                        @if ($lateShowSubMessage)
                            <div class="small text-muted mt-1">{{ $lateShowSubMessage }}</div>
                        @endif
                    </div>
                @endif

                <div class="booking-summary-inline mb-3" style="display:block!important;visibility:visible!important;opacity:1!important;">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <div class="booking-code-label">Mã đơn</div>
                            <div class="booking-code-value">{{ $booking->booking_code }}</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge-clean {{ $bookingStatusClass }}">{{ $bookingStatusLabels[$booking->status] ?? $booking->status }}</span>
                            @if($isLateCheckout)
                                <span class="badge-clean status-late">Trả muộn · {{ $lateCheckoutText }}</span>
                            @endif
                            <span class="badge-clean {{ $paymentStatusClass }}">{{ $paymentStatusLabels[$effectivePaymentStatus] ?? $effectivePaymentStatus }}</span>
                        </div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-card"><span>Khách hàng</span><strong>{{ $customerName }}</strong></div>
                        <div class="metric-card"><span>Thời gian lưu trú</span><strong>{{ $lateShowCheckInAt?->format('d/m/Y H:i') ?? '---' }}<br>→ {{ $lateShowCheckOutAt?->format('d/m/Y H:i') ?? '---' }}</strong></div>
                        <div class="metric-card"><span>Hạng phòng khách đặt</span><strong>{{ $booking->roomCategory->name ?? 'Không xác định' }}<br><span class="text-muted small">{{ $booking->room_quantity }} phòng · {{ $booking->adult_count }} NL / {{ $booking->child_count }} TE · Sức chứa {{ $currentAdultCapacity }} NL / {{ $currentChildCapacity }} TE</span></strong></div>
                        @if($booking->actual_check_in)
                            @php
                                $initialCheckInAdults = (int) ($booking->check_in_adult_count ?? $booking->adult_count);
                                $initialCheckInChildren = (int) ($booking->check_in_child_count ?? $booking->child_count);
                                $initialCheckInTotal = $initialCheckInAdults + $initialCheckInChildren;
                            @endphp
                            <div class="metric-card">
                                <span>Khách thực tế lúc check-in ban đầu</span>
                                <strong>{{ $initialCheckInTotal }} khách<br><span class="text-muted small">{{ $initialCheckInAdults }} người lớn · {{ $initialCheckInChildren }} trẻ em · {{ $booking->actual_check_in->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</span></strong>
                            </div>
                        @endif
                        <div class="metric-card"><span>Còn lại cần thu</span><strong class="text-danger fs-5">{{ number_format($remainingTotal, 0, ',', '.') }}đ</strong></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 booking-secondary-actions">
                    @if ($canManageBookingRooms)
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roomAdjustmentModal">
                            <i class="bx bx-transfer-alt me-1"></i> Điều chỉnh phòng
                        </button>
                    @endif
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
                                    @if ($booking->bookingRooms->count() <= 1)
                                        Thêm phòng, đổi phòng hoặc đổi hạng phòng hiện tại.
                                    @else
                                        Thêm phòng, đổi một phòng hoặc đổi hạng nhiều phòng/toàn bộ booking.
                                    @endif
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
                                    @php
                                        $canOfferLateArrivalRequest = in_array($booking->status, ['pending', 'confirmed'], true)
                                            && empty($booking->actual_check_in);
                                        $hasPendingLateArrivalRequest = $canOfferLateArrivalRequest
                                            ? $booking->customerRequests()
                                                ->where('type', 'late_arrival')
                                                ->where('status', 'pending')
                                                ->exists()
                                            : false;
                                    @endphp
                                    @if (!$canOfferLateArrivalRequest)
                                        <div class="small text-success mt-1">
                                            <i class="bx bx-check-circle me-1"></i>Khách đã đến/đã nhận phòng nên không còn áp dụng biểu mẫu đến muộn.
                                        </div>
                                    @else
                                        <div class="small text-muted mb-2">Khách online gửi trên website; khách không đăng nhập nhận biểu mẫu qua email. Quản lý duyệt cuối.</div>
                                        @if (filled($booking->booked_customer_email))
                                            <form method="POST" action="{{ route('admin.bookings.send-customer-request-form', $booking) }}" class="d-flex gap-2 flex-wrap">
                                                @csrf
                                                <input type="hidden" name="type" value="late_arrival">
                                                <input type="email" name="email" class="form-control" value="{{ $booking->booked_customer_email }}" style="max-width:360px" required @disabled($hasPendingLateArrivalRequest)>
                                                <button class="btn btn-outline-warning" type="submit" @disabled($hasPendingLateArrivalRequest)>Gửi form đến muộn</button>
                                            </form>
                                            @if($hasPendingLateArrivalRequest)
                                                <div class="small text-warning mt-2">Khách đã gửi yêu cầu và đang chờ xử lý. Xử lý xong mới được gửi form mới.</div>
                                            @endif
                                        @else
                                            <div class="small text-muted">Booking chưa có email để gửi biểu mẫu.</div>
                                        @endif
                                    @endif
                                </div>
                                <div class="list-group-item px-0">
                                    <strong>Phân phòng theo yêu cầu khách</strong>
                                    @if (($booking->room_selection_mode ?? 'automatic') === 'manual')
                                        <div class="small text-muted mt-1 mb-2">
                                            Trạng thái: {{ $booking->room_selection_status === 'fulfilled' ? 'Đã đáp ứng' : ($booking->room_selection_status === 'fallback_accepted' ? 'Khách đã nhận phòng dự phòng' : 'Cần/đang xử lý') }}.
                                        </div>
                                        <a href="#manualRoomSelectionPanel" class="btn btn-sm btn-outline-primary" data-bs-dismiss="modal">
                                            <i class="bx bx-check-square me-1"></i> Mở phần chọn phòng thủ công
                                        </a>
                                    @else
                                        <div class="small text-muted mt-1">Booking này không yêu cầu lễ tân chọn phòng cụ thể.</div>
                                    @endif
                                </div>
                                <div class="list-group-item px-0">
                                    <strong>Xử lý đến muộn / no-show</strong>
                                    @if ($canHandleNoShowNow)
                                        <div class="small text-muted mt-1 mb-2">Đã đến thời điểm có thể xử lý theo chính sách giữ phòng.</div>
                                        <a href="#lateArrivalHandlingPanel" class="btn btn-sm btn-outline-danger" data-bs-dismiss="modal">
                                            <i class="bx bx-time-five me-1"></i> Mở xử lý đến muộn
                                        </a>
                                    @elseif (in_array($booking->status, ['pending', 'confirmed'], true) && !$booking->actual_check_in)
                                        <div class="small text-muted mt-1">
                                            Chưa đến thời điểm xử lý no-show. Hạn giữ hiện tại: <strong>{{ $lateShowNoShowLimitAt?->format('H:i d/m/Y') ?? '---' }}</strong>.
                                        </div>
                                    @else
                                        <div class="small text-muted mt-1">Không áp dụng ở trạng thái hiện tại.</div>
                                    @endif
                                </div>
                                <div class="list-group-item px-0">
                                    <strong>Báo cáo sự cố</strong>
                                    @if ($booking->status === 'checked_in' && $booking->actual_check_in)
                                        @if ($canSendRoomIssueForm)
                                            <form action="{{ route('admin.bookings.send-room-issue-form', $booking) }}" method="POST"
                                                class="mt-2"
                                                onsubmit="return confirm('Gửi biểu mẫu báo sự cố tới email đang nhập?')">
                                                @csrf
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <input type="email" name="recipient_email" class="form-control" required maxlength="255"
                                                        value="{{ old('recipient_email', $roomIssueFormEmail) }}"
                                                        placeholder="Email nhận biểu mẫu" style="max-width:360px">
                                                    <button type="submit" class="btn btn-outline-primary">
                                                        <i class="bx bx-envelope me-1"></i> Gửi form sự cố
                                                    </button>
                                                </div>
                                                @error('recipient_email')
                                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                                @enderror
                                            </form>
                                        @else
                                            <div class="small text-muted mt-1">Chưa thể gửi thêm biểu mẫu.</div>
                                        @endif
                                    @elseif ($latestRoomIssueRequest)
                                        <a href="#room-issue-admin" class="btn btn-sm btn-outline-primary mt-2"
                                            data-bs-dismiss="modal">Xem yêu cầu hiện tại</a>
                                    @else
                                        <div class="small text-muted mt-1">Chỉ khả dụng khi khách đang lưu trú.</div>
                                    @endif
                                </div>
                                <div class="list-group-item px-0">
                                    <strong>Yêu cầu hủy phòng</strong>
                                    @php
                                        $adminCancelDate = \Carbon\Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh');
                                        $adminDirectCancelCutoff = $adminCancelDate->copy()->setTime(14, 0, 0);
                                        $canAdminCancelBooking = in_array($booking->status, ['pending', 'confirmed'], true)
                                            && !$booking->actual_check_in
                                            && now('Asia/Ho_Chi_Minh')->lt($adminDirectCancelCutoff);
                                    @endphp
                                    @if ($canAdminCancelBooking)
                                        <div class="mt-2">
                                            <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}"
                                                onsubmit="return confirm('Gửi mã xác nhận hủy về email khách?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="bx bx-envelope me-1"></i> Gửi mã xác nhận hủy
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($latestCancellationRequest)
                                        <div class="small text-muted mt-1">
                                            Trạng thái: {{ $latestCancellationRequest->status ?? 'Đang xử lý' }}
                                        </div>
                                    @else
                                        <div class="small text-muted mt-1">Không khả dụng ở trạng thái hiện tại.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $deliveryTypeLabels = [
                    'booking_confirmation' => 'Xác nhận đặt phòng',
                    'payment_success_booking_confirmation' => 'Xác nhận thanh toán',
                    'vnpay_payment_request' => 'Yêu cầu thanh toán VNPay',
                    'room_selection_result' => 'Kết quả chọn phòng',
                    'late_arrival_form' => 'Form đến muộn',
                    'room_issue_form' => 'Form báo sự cố',
                    'booking_cancelled' => 'Thông báo hủy booking',
                    'guest_booking_lookup_otp' => 'OTP tra cứu booking',
                    'booking_cancel_otp' => 'OTP hủy booking',
                    'operational_notification' => 'Cập nhật booking',
                ];
                $latestCustomerDelivery = $customerDeliveryLogs->first();
            @endphp
            <details class="compact-panel mb-3" id="customer-delivery-panel">
                <summary>
                    <span>
                        <i class="bx bx-envelope me-1"></i> Thông báo đã gửi cho khách
                        <span class="text-muted fw-normal small ms-2">{{ $customerDeliveryLogs->count() }} thông báo · bấm để xem</span>
                    </span>
                    @if($latestCustomerDelivery)
                        <span class="badge-clean {{ $latestCustomerDelivery->status === 'sent' ? 'status-done' : ($latestCustomerDelivery->status === 'failed' ? 'status-cancelled' : 'status-warning') }}">
                            {{ $latestCustomerDelivery->status === 'sent' ? 'Đã gửi' : ($latestCustomerDelivery->status === 'failed' ? 'Gửi lỗi' : 'Đang gửi') }}
                        </span>
                    @else
                        <span class="badge-clean status-muted">Chưa có</span>
                    @endif
                </summary>
                <div class="compact-panel-body" style="max-height:320px; overflow-y:auto;">
                    @forelse($customerDeliveryLogs as $deliveryLog)
                        @php
                            $deliveryMeta = is_array($deliveryLog->meta) ? $deliveryLog->meta : [];
                            $deliveryTitle = trim((string) ($deliveryMeta['notification_title'] ?? $deliveryLog->subject ?? 'Thông báo cho khách'));
                            $deliveryMessage = trim((string) ($deliveryMeta['notification_message'] ?? ''));
                            $deliveryWhen = $deliveryLog->sent_at ?: $deliveryLog->failed_at ?: $deliveryLog->created_at;
                        @endphp
                        <div class="soft-note mb-2" style="border-left:4px solid {{ $deliveryLog->status === 'sent' ? '#16866f' : ($deliveryLog->status === 'failed' ? '#dc3545' : '#d9aa25') }};">
                            <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
                                <div>
                                    <div class="fw-bold">{{ $deliveryTitle }}</div>
                                    <div class="small text-muted mt-1">
                                        Gửi đến: <strong>{{ $deliveryLog->recipient ?: '---' }}</strong>
                                        · {{ $deliveryTypeLabels[$deliveryLog->mail_type] ?? $deliveryLog->mail_type }}
                                        · {{ optional($deliveryWhen)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}
                                    </div>
                                </div>
                                <span class="badge-clean {{ $deliveryLog->status === 'sent' ? 'status-done' : ($deliveryLog->status === 'failed' ? 'status-cancelled' : 'status-warning') }}">
                                    {{ $deliveryLog->status === 'sent' ? 'Đã gửi email' : ($deliveryLog->status === 'failed' ? 'Gửi thất bại' : 'Chờ gửi') }}
                                </span>
                            </div>
                            @if($deliveryMessage !== '')
                                <div class="small mt-2"><strong>Nội dung:</strong> {{ $deliveryMessage }}</div>
                            @elseif($deliveryLog->subject)
                                <div class="small mt-2"><strong>Nội dung/tiêu đề:</strong> {{ $deliveryLog->subject }}</div>
                            @endif
                            @if($deliveryLog->status === 'failed' && filled($deliveryLog->error_message))
                                <div class="small text-danger mt-2"><strong>Lỗi gửi:</strong> {{ $deliveryLog->error_message }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="soft-note text-muted">
                            Chưa có email/thông báo nào được gửi cho khách từ booking này.
                        </div>
                    @endforelse
                    <div class="small text-muted mt-2">
                        “Đã gửi email” nghĩa là hệ thống đã giao thư thành công cho máy chủ mail; khách vẫn có thể nhận ở Inbox hoặc Spam.
                    </div>
                </div>
            </details>

            <div class="booking-shell">
                <div class="main-stack">
                    <section class="card-clean primary-operation-card">
                        <div class="card-title-clean">
                            <div>
                                <h5>{{ $isReceptionDesk ? 'Việc cần làm' : 'Thao tác chính' }}</h5>
                            </div>
                            <span class="badge-clean {{ $bookingStatusClass }}">
                                {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                            </span>
                        </div>

                        <div class="operation-list">
                            @if ($booking->status == 'checked_in')
                                @include('admin.pages.bookings.partials.staying-guests')
                            @endif

                            @if ($booking->status == 'confirmed')
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Nhận phòng thực tế</div>
                                        </div>
                                    </div>

                                    @if ($roomsNotReadyForCheckIn->count() > 0)
                                        <div class="alert alert-warning small mb-3">
                                            <div class="fw-bold mb-1">Phòng gán hiện tại chưa sẵn sàng</div>
                                            {{ $notReadyRoomText }}.
                                        </div>
                                    @endif

                                    @if ($canRequestPriorityCleaning)
                                        <form action="{{ route('admin.bookings.priority-cleaning', $booking->id) }}" method="POST"
                                            class="mb-3"
                                            onsubmit="return confirm('Gửi yêu cầu buồng phòng ưu tiên dọn nhanh cho đơn này?')">
                                            @csrf
                                            @method('PATCH')

                                            <div class="soft-note">
                                                <div class="fw-bold mb-1">Khách đến sớm trong khung {{ $earlyFreeFromTimePolicy }}–{{ $standardCheckInTimePolicy }}</div>
                                                {{ $priorityCleaningRoomText }}. Có thể gửi yêu cầu ưu tiên dọn nhanh để khách được
                                                nhận phòng sớm khi phòng sẵn sàng.
                                                <button type="submit" class="btn btn-outline-warning btn-sm w-100 mt-2">
                                                    <i class="bx bx-bell me-1"></i>
                                                    Yêu cầu buồng phòng ưu tiên dọn
                                                </button>
                                            </div>
                                        </form>
                                    @endif

                                    @if ($isBeforeBookingDateNow)
                                        <details class="compact-panel mb-3" open>
                                            <summary>Đổi ngày nhận và ngày trả trước khi nhận phòng</summary>
                                            <div class="compact-panel-body">
                                                <form action="{{ route('admin.bookings.change-stay-dates', $booking->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Xác nhận đổi ngày lưu trú và tính lại tiền phòng?')">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Ngày nhận mới</label>
                                                            <input type="date" id="newCheckInDateVn" name="new_check_in_date" class="form-control"
                                                                min="{{ $nowVnForCheckInFlow->toDateString() }}"
                                                                data-paired-checkout="new_check_out_date"
                                                                data-checkout-min-days="1"
                                                                value="{{ old('new_check_in_date', $stayDateChangeCheckInDateDefault) }}"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Giờ nhận mới</label>
                                                            <input type="time" id="newCheckInTimeVn" name="new_check_in_time" class="form-control"
                                                                value="{{ old('new_check_in_time', $stayDateChangeCheckInTimeDefault) }}"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Ngày trả mới</label>
                                                            <input type="date" id="newCheckOutDateVn" name="new_check_out_date" class="form-control"
                                                                min="{{ $nowVnForCheckInFlow->copy()->addDay()->toDateString() }}"
                                                                value="{{ old('new_check_out_date', $stayDateChangeCheckOutDateDefault) }}"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Giờ trả mới</label>
                                                            <input type="time" id="newCheckOutTimeVn" name="new_check_out_time" class="form-control"
                                                                value="{{ old('new_check_out_time', $stayDateChangeCheckOutTimeDefault) }}"
                                                                required>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="btn btn-outline-primary w-100 mt-3">
                                                        Kiểm tra trùng phòng và đổi ngày lưu trú
                                                    </button>
                                                </form>

                                                @if (
                                                    is_array($stayDateRepricePreview)
                                                    && (int) ($stayDateRepricePreview['booking_id'] ?? 0) === (int) $booking->id
                                                )
                                                    @php
                                                        $repriceOld = $stayDateRepricePreview['old'] ?? [];
                                                        $repriceNew = $stayDateRepricePreview['new'] ?? [];
                                                        $repriceServices = $stayDateRepricePreview['service_preview']['lines'] ?? [];
                                                        $repriceRemovedPromotions = $stayDateRepricePreview['promotion_preview']['removed'] ?? [];
                                                        $repriceKeptPromotions = $stayDateRepricePreview['promotion_preview']['kept'] ?? [];
                                                    @endphp

                                                    <div class="border border-primary rounded-3 p-3 mt-3 bg-white shadow-sm" id="stay-date-reprice-preview">
                                                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                                                            <div>
                                                                <div class="fw-bold text-primary fs-5">Xem trước tiền trước khi đổi lịch</div>
                                                                <div class="small text-muted">
                                                                    Chưa cập nhật booking. Kiểm tra lại lịch, hạng, dịch vụ, mã ưu đãi, cọc và tiền khách đã trả rồi mới xác nhận.
                                                                </div>
                                                            </div>
                                                            <span class="badge bg-primary align-self-start">
                                                                {{ $stayDateRepricePreview['period']['text'] ?? 'Lịch mới' }}
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
                                                                        <td class="text-end">{{ $booking->roomCategory->name ?? '---' }}</td>
                                                                        <td class="text-end fw-semibold">{{ $stayDateRepricePreview['target_category_name'] ?? ($booking->roomCategory->name ?? '---') }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Số đêm</td>
                                                                        <td class="text-end">{{ $repriceOld['night_count'] ?? 0 }}</td>
                                                                        <td class="text-end fw-semibold">{{ $repriceNew['night_count'] ?? 0 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tiền phòng</td>
                                                                        <td class="text-end">{{ number_format((float) ($repriceOld['room_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                        <td class="text-end fw-semibold">{{ number_format((float) ($repriceNew['room_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Dịch vụ / phụ thu đã xác nhận</td>
                                                                        <td class="text-end">{{ number_format((float) ($repriceOld['service_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                        <td class="text-end fw-semibold">{{ number_format((float) ($repriceNew['service_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Mã giảm giá / hỗ trợ</td>
                                                                        <td class="text-end text-success">-{{ number_format((float) ($repriceOld['discount_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                        <td class="text-end text-success fw-semibold">-{{ number_format((float) ($repriceNew['discount_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    </tr>
                                                                    <tr class="table-primary">
                                                                        <td class="fw-bold">Tổng cần thanh toán</td>
                                                                        <td class="text-end fw-bold">{{ number_format((float) ($repriceOld['total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                        <td class="text-end fw-bold">{{ number_format((float) ($repriceNew['total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Khách đã thanh toán</td>
                                                                        <td class="text-end" colspan="2">{{ number_format((float) ($stayDateRepricePreview['paid_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Mức cọc yêu cầu hiện hành</td>
                                                                        <td class="text-end">{{ number_format((float) ($repriceOld['required_deposit'] ?? 0), 0, ',', '.') }}đ</td>
                                                                        <td class="text-end fw-semibold">{{ number_format((float) ($repriceNew['required_deposit'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Còn phải thu</td>
                                                                        <td class="text-end" colspan="2">
                                                                            <strong class="text-danger">{{ number_format((float) ($repriceNew['remaining'] ?? 0), 0, ',', '.') }}đ</strong>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tiền trả trước còn dư để bù trừ</td>
                                                                        <td class="text-end" colspan="2">
                                                                            <strong class="{{ ($repriceNew['overpayment'] ?? 0) > 0 ? 'text-warning' : '' }}">
                                                                                {{ number_format((float) ($repriceNew['overpayment'] ?? 0), 0, ',', '.') }}đ
                                                                            </strong>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        @if (collect($repriceServices)->contains(fn ($item) => !empty($item['will_reprice']) || !empty($item['will_remove'])))
                                                            <div class="alert alert-info py-2 small">
                                                                <div class="fw-bold mb-1">Dịch vụ được tính lại theo lịch mới</div>
                                                                @foreach ($repriceServices as $serviceLine)
                                                                    @if (!empty($serviceLine['will_reprice']) || !empty($serviceLine['will_remove']))
                                                                        <div class="mb-1">
                                                                            <strong>{{ $serviceLine['name'] ?? 'Dịch vụ' }}</strong>
                                                                            ({{ $serviceLine['billing_rule_label'] ?? 'Một lần' }}):
                                                                            {{ number_format((float) ($serviceLine['old_total'] ?? 0), 0, ',', '.') }}đ
                                                                            →
                                                                            {{ number_format((float) ($serviceLine['new_total'] ?? 0), 0, ',', '.') }}đ.
                                                                            <span class="text-muted">{{ $serviceLine['new_formula'] ?? '' }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        @if (count($repriceRemovedPromotions) > 0)
                                                            <div class="alert alert-warning py-2 small">
                                                                <div class="fw-bold mb-1">Mã sẽ bị gỡ vì lịch/hạng/tổng mới không còn đủ điều kiện</div>
                                                                @foreach ($repriceRemovedPromotions as $removedPromotion)
                                                                    <div>
                                                                        <strong>{{ $removedPromotion['code'] ?? '---' }}</strong>:
                                                                        {{ $removedPromotion['reason'] ?? 'Không còn đủ điều kiện.' }}
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        @if (count($repriceKeptPromotions) > 0)
                                                            <div class="small text-muted mb-3">
                                                                Mã còn hiệu lực:
                                                                <strong>{{ collect($repriceKeptPromotions)->pluck('code')->implode(', ') }}</strong>.
                                                            </div>
                                                        @endif

                                                        @if (($repriceNew['deposit_shortfall'] ?? 0) > 0)
                                                            <div class="alert alert-danger py-2 small">
                                                                Sau khi đổi, khách còn thiếu
                                                                <strong>{{ number_format((float) $repriceNew['deposit_shortfall'], 0, ',', '.') }}đ</strong>
                                                                để đủ mức cọc mới trước khi check-in.
                                                            </div>
                                                        @endif

                                                        <form action="{{ route('admin.bookings.change-stay-dates', $booking->id) }}" method="POST"
                                                            onsubmit="return confirm('Xác nhận cập nhật lịch, phòng/hạng, dịch vụ, mã ưu đãi và số tiền theo bảng xem trước?')">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="new_check_in_date" value="{{ $stayDateRepricePreview['new_check_in_date'] ?? '' }}">
                                                            <input type="hidden" name="new_check_in_time" value="{{ $stayDateRepricePreview['new_check_in_time'] ?? '' }}">
                                                            <input type="hidden" name="new_check_out_date" value="{{ $stayDateRepricePreview['new_check_out_date'] ?? '' }}">
                                                            <input type="hidden" name="new_check_out_time" value="{{ $stayDateRepricePreview['new_check_out_time'] ?? '' }}">
                                                            <input type="hidden" name="replacement_room_category_id" value="{{ $stayDateRepricePreview['replacement_room_category_id'] ?? '' }}">
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
                                                            action="{{ route('admin.bookings.change-stay-dates.discard-preview', $booking) }}"
                                                            method="POST" class="d-none">
                                                            @csrf
                                                        </form>
                                                    </div>
                                                @endif

                                                @if (
                                                    is_array($stayDateCategoryOptions)
                                                    && (int) ($stayDateCategoryOptions['booking_id'] ?? 0) === (int) $booking->id
                                                )
                                                    <div class="alert alert-warning mt-3 mb-3">
                                                        <div class="fw-bold mb-1">
                                                            Không còn đủ phòng cùng hạng trong lịch mới
                                                        </div>
                                                        <div class="small">
                                                            {{ $stayDateCategoryOptions['reason'] ?? '' }}
                                                        </div>
                                                        <div class="small mt-2">
                                                            Lịch đang kiểm tra:
                                                            <strong>{{ $stayDateCategoryOptions['period_text'] ?? '---' }}</strong>.
                                                            Booking cần
                                                            <strong>{{ $stayDateCategoryOptions['room_quantity'] ?? $booking->room_quantity }} phòng</strong>.
                                                            Chỉ các hạng còn đủ số phòng trong đúng khung thời gian này mới được hiển thị.
                                                        </div>
                                                    </div>

                                                    <div class="border rounded-3 p-2 bg-light">
                                                        <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-1">
                                                            <div>
                                                                <div class="fw-bold">Chọn hạng phòng thay thế theo lịch mới</div>
                                                            </div>
                                                            <span class="badge bg-primary">
                                                                {{ count($stayDateCategoryOptions['options'] ?? []) }} hạng còn đủ phòng
                                                            </span>
                                                        </div>

                                                        <div class="mt-2" style="max-height: 330px; overflow-y: auto;">
                                                            @foreach (($stayDateCategoryOptions['options'] ?? []) as $categoryOption)
                                                                <form
                                                                    action="{{ route('admin.bookings.change-stay-dates', $booking->id) }}"
                                                                    method="POST"
                                                                    class="bg-white border rounded-3 p-3 mb-2"
                                                                    onsubmit="return confirm('Xác nhận đổi lịch và chuyển toàn bộ booking sang hạng đã chọn?')">
                                                                    @csrf
                                                                    @method('PATCH')

                                                                    <input type="hidden" name="new_check_in_date"
                                                                        value="{{ $stayDateCategoryOptions['new_check_in_date'] ?? '' }}">
                                                                    <input type="hidden" name="new_check_in_time"
                                                                        value="{{ $stayDateCategoryOptions['new_check_in_time'] ?? '' }}">
                                                                    <input type="hidden" name="new_check_out_date"
                                                                        value="{{ $stayDateCategoryOptions['new_check_out_date'] ?? '' }}">
                                                                    <input type="hidden" name="new_check_out_time"
                                                                        value="{{ $stayDateCategoryOptions['new_check_out_time'] ?? '' }}">
                                                                    <input type="hidden" name="replacement_room_category_id"
                                                                        value="{{ $categoryOption['category_id'] ?? '' }}">

                                                                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                                                        <div class="flex-grow-1">
                                                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                                                <span class="fw-bold fs-6">
                                                                                    {{ $categoryOption['category_name'] ?? 'Hạng phòng' }}
                                                                                </span>
                                                                                @if (!empty($categoryOption['is_current_category']))
                                                                                    <span class="badge bg-secondary">Hạng hiện tại</span>
                                                                                @endif
                                                                            </div>


                                                                            <div class="small mt-1">
                                                                                {{ $categoryOption['price_text'] ?? '' }}
                                                                                · Sức chứa tổng:
                                                                                {{ $categoryOption['adult_capacity'] ?? 0 }} người lớn /
                                                                                {{ $categoryOption['child_capacity'] ?? 0 }} trẻ em
                                                                            </div>

                                                                            @php
                                                                                $newRoomTotal = (float) ($categoryOption['new_room_total'] ?? 0);
                                                                                $roomDifference = (float) ($categoryOption['difference'] ?? 0);
                                                                                $currentRoomTotal = max(0, $newRoomTotal - $roomDifference);
                                                                            @endphp
                                                                            <div class="small mt-2 border rounded-3 p-2 bg-light">
                                                                                <div class="d-flex justify-content-between gap-3">
                                                                                    <span class="text-muted">Tiền phòng hiện tại:</span>
                                                                                    <strong>{{ number_format($currentRoomTotal, 0, ',', '.') }}đ</strong>
                                                                                </div>
                                                                                <div class="d-flex justify-content-between gap-3 mt-1">
                                                                                    <span class="text-muted">Tiền phòng sau khi đổi lịch/hạng:</span>
                                                                                    <strong>{{ $categoryOption['new_room_total_text'] ?? '0đ' }}</strong>
                                                                                </div>
                                                                                <div class="d-flex justify-content-between gap-3 mt-1 pt-1 border-top">
                                                                                    @if ($roomDifference > 0)
                                                                                        <span class="text-danger">Tiền phòng tăng thêm:</span>
                                                                                        <strong class="text-danger">{{ number_format($roomDifference, 0, ',', '.') }}đ</strong>
                                                                                    @elseif ($roomDifference < 0)
                                                                                        <span class="text-success">Tiền phòng được giảm:</span>
                                                                                        <strong class="text-success">{{ number_format(abs($roomDifference), 0, ',', '.') }}đ</strong>
                                                                                    @else
                                                                                        <span class="text-muted">Tiền phòng không thay đổi:</span>
                                                                                        <strong>0đ</strong>
                                                                                    @endif
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
                                                            @endforeach
                                                        </div>

                                                        <div class="small text-muted px-2 pt-1">
                                                            Không chọn hạng nào thì booking vẫn giữ nguyên lịch và phòng cũ. Có thể sửa lại
                                                            ngày ở phía trên rồi kiểm tra lại.
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @endif

                                    @php
                                        $bookingAllocatedAdults = (int) $booking->bookingRooms->sum('adult_count');
                                        $bookingAllocatedChildren = (int) $booking->bookingRooms->sum('child_count');

                                        $actualAdultsForCheckIn = max(1, (int) old('actual_adult_count', $booking->adult_count));
                                        $actualChildrenForCheckIn = max(0, (int) old('actual_child_count', $booking->child_count));
                                        $actualMinorForCheckIn = $actualChildrenForCheckIn;
                                        $adultOverForCheckIn = max(0, $actualAdultsForCheckIn - $currentAdultCapacity);
                                        $minorOverForCheckIn = max(0, $actualMinorForCheckIn - $currentChildCapacity);
                                        $initialAnyOverCapacity = $adultOverForCheckIn > 0 || $minorOverForCheckIn > 0;
                                        $initialActualGuestConfirmed = (string) old('actual_guest_confirmed', '') === '1';

                                        $workflowRoomCount = max(1, $booking->bookingRooms->count());
                                        $workflowNeedsGroupRepresentative = $workflowRoomCount > 1;
                                        $workflowMissingRepresentativeRooms = $booking->bookingRooms->filter(function ($bookingRoom) use ($booking) {
                                            return !$booking->guests->contains(function ($guest) use ($bookingRoom) {
                                                return (int) $guest->booking_room_id === (int) $bookingRoom->id && $guest->guest_type === 'adult';
                                            });
                                        });
                                        $workflowGroupRepresentativeReady = !$workflowNeedsGroupRepresentative
                                            || $booking->guests->where('is_booking_representative', true)->count() === 1;
                                    @endphp

                                    <details class="compact-panel mb-3" id="checkInDepositStep">
                                        <summary>
                                            <span>Điều kiện 1 · Thu đủ cọc</span>
                                            <span class="badge-clean {{ $adminPaymentPaidAmount + 0.01 >= $adminPaymentDepositTarget ? 'status-done' : 'status-warning' }}">
                                                {{ $adminPaymentPaidAmount + 0.01 >= $adminPaymentDepositTarget ? 'Đã đủ cọc' : 'Còn thiếu ' . number_format(max(0, $adminPaymentDepositTarget - $adminPaymentPaidAmount), 0, ',', '.') . 'đ' }}
                                            </span>
                                        </summary>
                                        <div class="compact-panel-body">
                                            <div class="small text-muted mb-2">Đã thu {{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ / mức cọc cần {{ number_format($adminPaymentDepositTarget, 0, ',', '.') }}đ.</div>
                                            @if($adminPaymentPaidAmount + 0.01 < $adminPaymentDepositTarget)
                                                <a href="#bookingPaymentPanel" class="btn btn-warning btn-sm">Thu thêm {{ number_format(max(0, $adminPaymentDepositTarget - $adminPaymentPaidAmount), 0, ',', '.') }}đ</a>
                                            @endif
                                        </div>
                                    </details>

                                    <details class="compact-panel mb-3" id="checkInActualGuestsStep"
                                        data-adult-capacity="{{ (int) $currentAdultCapacity }}"
                                        data-child-capacity="{{ (int) $currentChildCapacity }}"
                                        @if($initialAnyOverCapacity) open @endif>
                                        <summary>
                                            <span>Điều kiện 2 · Khách thực tế đến nhận phòng</span>
                                            <span class="badge-clean {{ (!$initialActualGuestConfirmed || $initialAnyOverCapacity) ? 'status-warning' : 'status-done' }}" id="actualGuestCapacityBadge">
                                                {{ !$initialActualGuestConfirmed ? 'Chưa xác nhận' : ($initialAnyOverCapacity ? 'Vượt sức chứa' : 'Đã đối chiếu') }}
                                            </span>
                                        </summary>
                                        <div class="compact-panel-body">
                                            <div class="small text-muted mb-3">
                                                Chỉ nhập <strong>tổng số khách thực tế đến</strong>. Hệ thống đối chiếu với tổng sức chứa của {{ $workflowRoomCount }} phòng để xác định có cần phụ thu hay không; không cần phân từng khách vào từng phòng.
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label">Người lớn <span class="text-danger">*</span></label>
                                                    <input type="number" min="1" name="actual_adult_count" id="actualAdultCount"
                                                        form="checkInForm" class="form-control @error('actual_adult_count') is-invalid @enderror"
                                                        value="{{ $actualAdultsForCheckIn }}" required>
                                                    @error('actual_adult_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Trẻ em <span class="text-danger">*</span></label>
                                                    <input type="number" min="0" name="actual_child_count" id="actualChildCount"
                                                        form="checkInForm" class="form-control @error('actual_child_count') is-invalid @enderror"
                                                        value="{{ $actualChildrenForCheckIn }}" required>
                                                    @error('actual_child_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                            <div class="action-summary mt-3">
                                                <div class="action-summary-item"><span>Tổng sức chứa người lớn</span><strong>{{ $currentAdultCapacity }}</strong></div>
                                                <div class="action-summary-item"><span>Tổng sức chứa trẻ em</span><strong>{{ $currentChildCapacity }}</strong></div>
                                                <div class="action-summary-item"><span>Đối chiếu</span><strong id="actualGuestCapacityText">{{ $initialAnyOverCapacity ? 'Cần xử lý phụ thu/sức chứa' : 'Đủ sức chứa' }}</strong></div>
                                            </div>
                                            {{-- Vượt sức chứa được xử lý ngay tại Bước 2 bằng bảng cố định 3 nhóm khách. --}}
                                            <div id="overCapacityBox" class="{{ $initialAnyOverCapacity ? '' : 'd-none' }} mt-3">
                                                <div class="alert alert-danger small mb-3">
                                                    <strong>Số khách thực tế vượt tổng sức chứa.</strong>
                                                    <span id="aggregateCapacityIssueText">
                                                        @if($adultOverForCheckIn > 0) Vượt {{ $adultOverForCheckIn }} người lớn. @endif
                                                        @if($minorOverForCheckIn > 0) Vượt {{ $minorOverForCheckIn }} trẻ em. @endif
                                                    </span>
                                                </div>

                                                <input type="hidden" name="over_capacity_action" id="overCapacityAction"
                                                    form="checkInForm" value="{{ $initialAnyOverCapacity ? 'extra_fee' : '' }}">

                                                <div class="border rounded overflow-hidden bg-white">
                                                    <div class="px-3 py-2 border-bottom fw-bold">Phụ thu vượt sức chứa</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm align-middle mb-0" id="capacityFeeTable">
                                                            <thead>
                                                                <tr>
                                                                    <th style="min-width:120px">Khách</th>
                                                                    <th style="width:115px">Số lượng</th>
                                                                    <th style="min-width:260px">Loại phụ thu</th>
                                                                    <th style="width:140px" class="text-end">Đơn giá</th>
                                                                    <th style="width:150px" class="text-end">Thành tiền</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ([
                                                                    'adult' => ['label' => 'Người lớn', 'value' => $actualAdultsForCheckIn],
                                                                    'child' => ['label' => 'Trẻ em', 'value' => $actualChildrenForCheckIn],
                                                                ] as $feeGuestType => $feeGuestMeta)
                                                                    <tr class="capacity-fee-row" data-guest-type="{{ $feeGuestType }}">
                                                                        <td class="fw-semibold">{{ $feeGuestMeta['label'] }}</td>
                                                                        <td>
                                                                            <input type="number" min="{{ $feeGuestType === 'adult' ? 1 : 0 }}"
                                                                                class="form-control form-control-sm capacity-fee-actual-count"
                                                                                data-sync-target="{{ $feeGuestType === 'adult' ? 'actualAdultCount' : 'actualChildCount' }}"
                                                                                value="{{ $feeGuestMeta['value'] }}">
                                                                            <div class="small text-muted mt-1 capacity-fee-billed-count"></div>
                                                                        </td>
                                                                        <td>
                                                                            <select name="extra_service_ids[{{ $feeGuestType }}]" form="checkInForm"
                                                                                class="form-select form-select-sm capacity-fee-service">
                                                                                <option value="">-- Không phụ thu --</option>
                                                                                @foreach ($extraGuestServices as $service)
                                                                                    <option value="{{ $service->id }}"
                                                                                        data-price="{{ $service->price }}"
                                                                                        data-unit="{{ $service->unit }}"
                                                                                        data-service-name="{{ \Illuminate\Support\Str::lower($service->name) }}">
                                                                                        {{ $service->name }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <span class="capacity-fee-unit-price">0đ</span>
                                                                        </td>
                                                                        <td class="text-end fw-bold">
                                                                            <span class="capacity-fee-total">0đ</span>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <th colspan="4" class="text-end">Tổng phụ thu</th>
                                                                    <th class="text-end"><span id="allExtraFeeTotalText">0đ</span></th>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    Số lượng lấy từ khách thực tế ở trên. Sửa tại bảng sẽ tự cập nhật lại số khách thực tế; hệ thống chỉ tính phí cho phần vượt sức chứa.
                                                </div>
                                            </div>


                                            <div class="form-check mt-3">
                                                <input class="form-check-input @error('actual_guest_confirmed') is-invalid @enderror" type="checkbox"
                                                    value="1" name="actual_guest_confirmed" id="actualGuestConfirmed" form="checkInForm"
                                                    {{ old('actual_guest_confirmed') ? 'checked' : '' }} required>
                                                <label class="form-check-label" for="actualGuestConfirmed">Tôi đã đối chiếu đúng số khách thực tế đến nhận phòng.</label>
                                                @error('actual_guest_confirmed')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </details>

                                    @include('admin.pages.bookings.partials.staying-guests')


                                    <form action="{{ route('admin.bookings.check-in', $booking->id) }}" method="POST" enctype="multipart/form-data"
                                        id="checkInForm" class="mb-3">
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" id="adultCapacity" value="{{ $currentAdultCapacity }}">
                                        <input type="hidden" id="childCapacity" value="{{ $currentChildCapacity }}">

                                        <input type="hidden" name="early_check_in_action" id="earlyCheckInAction" value="">
                                        <input type="hidden" id="earlyCheckInIsActive" value="{{ $isEarlyCheckInNow ? 1 : 0 }}">
                                        <input type="hidden" id="earlyCheckInFeeAmount" value="{{ $earlyCheckInFeePreview }}">
                                        <input type="hidden" id="earlyCheckInPercent" value="{{ $earlyCheckInPercent }}">
                                        <input type="hidden" id="earlyCheckInBasePrice" value="{{ $earlyCheckInBasePrice }}">
                                        <input type="hidden" id="earlyCheckInPolicyText" value="{{ $earlyCheckInPolicyText }}">
                                        <input type="hidden" id="earlyCheckInNowText"
                                            value="{{ $nowVnForCheckInFlow->format('d/m/Y H:i') }}">
                                        <input type="hidden" id="earlyCheckInStandardText"
                                            value="{{ $standardCheckInAt?->format('d/m/Y H:i') }}">
                                        <input type="hidden" id="earlyCheckInDurationText" value="{{ $earlyCheckInDurationText }}">
                                        <input type="hidden" id="earlyCheckInFinalTotalPreview" value="{{ $earlyCheckInFinalTotalPreview }}">

                                        @php
                                            $checkInDeclaredRepresentatives = $booking->guests->where('guest_type', 'adult')->count();
                                            $checkInRequiredRoomCount = max(1, (int) ($booking->room_quantity ?? 1));
                                            $needsGroupRepresentativeForCheckIn = $checkInRequiredRoomCount > 1;
                                            $hasRepresentative = !$needsGroupRepresentativeForCheckIn || $booking->guests->where('is_booking_representative', true)->count() === 1;
                                            $missingAdultRepresentativeRooms = $booking->bookingRooms
                                                ->filter(function ($bookingRoom) use ($booking) {
                                                    return !$booking->guests->contains(function ($guest) use ($bookingRoom) {
                                                        return (int) $guest->booking_room_id === (int) $bookingRoom->id
                                                            && $guest->guest_type === 'adult';
                                                    });
                                                });
                                            $checkInProfileReady = !$booking->guests->isEmpty()
                                                && $hasRepresentative
                                                && $missingAdultRepresentativeRooms->isEmpty();
                                            $checkInAssignedRoomIds = $booking->bookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id);
                                            $checkInHasExactRoomAssignments = $booking->bookingRooms->count() === $checkInRequiredRoomCount
                                                && $checkInAssignedRoomIds->unique()->count() === $checkInRequiredRoomCount;
                                            $checkInSelectionReady = $booking->room_selection_mode !== 'manual'
                                                || in_array($booking->room_selection_status, ['fulfilled', 'fallback_accepted'], true);
                                            $checkInRoomsReady = $checkInHasExactRoomAssignments && $roomsNotReadyForCheckIn->isEmpty();
                                            $checkInPaymentReady = $adminPaymentDepositTarget <= 0
                                                || ($adminPaymentPaidAmount + 0.01 >= $adminPaymentDepositTarget);
                                            $checkInStaticDisabled = $disableCheckInSubmitNow
                                                || !$checkInProfileReady
                                                || !$checkInHasExactRoomAssignments
                                                || !$checkInSelectionReady
                                                || !$checkInRoomsReady
                                                || !$checkInPaymentReady;
                                            $checkInDisabledReason = collect([
                                                $disableCheckInSubmitNow ? ($lateShowMessage ?: 'Chưa đến thời điểm nhận phòng hoặc booking chưa ở trạng thái cho phép.') : null,
                                                !$checkInPaymentReady ? 'Chưa thu đủ cọc.' : null,
                                                !$checkInProfileReady ? 'Chưa đủ người đại diện phòng/đại diện đoàn.' : null,
                                                !$checkInHasExactRoomAssignments ? 'Chưa phân đủ số phòng.' : null,
                                                !$checkInSelectionReady ? 'Yêu cầu chọn phòng của khách chưa được xử lý xong.' : null,
                                                !$checkInRoomsReady ? 'Có phòng chưa sẵn sàng để khách vào ở.' : null,
                                            ])->filter()->implode(' ');
                                        @endphp

                                        @php
                                            $checkInConditionNumber = 4;
                                            $showArrivalCondition = $lateShowCheckInAt
                                                && !$booking->actual_check_in
                                                && (
                                                    $lateShowNowVn->greaterThan($lateShowCheckInAt)
                                                    || $isBeforeBookingDateNow
                                                    || $isBeforeHourlyCheckInNow
                                                    || $isRescheduledAfterCutoff
                                                );
                                            $arrivalConditionBlocked = $disableCheckInSubmitNow;
                                        @endphp

                                        @if (!$checkInHasExactRoomAssignments)
                                            <details class="compact-panel mb-3" open>
                                                <summary>
                                                    <span>Điều kiện {{ $checkInConditionNumber }} · Phân đủ phòng</span>
                                                    <span class="badge-clean status-warning">Chưa đủ phòng</span>
                                                </summary>
                                                <div class="compact-panel-body small">
                                                    Booking cần {{ $checkInRequiredRoomCount }} phòng khác nhau nhưng hiện chỉ có {{ $checkInAssignedRoomIds->unique()->count() }} phòng hợp lệ.
                                                    <a href="#manualRoomSelectionPanel" class="ms-1">Mở phần phân/chọn phòng</a>.
                                                </div>
                                            </details>
                                            @php $checkInConditionNumber++; @endphp
                                        @endif

                                        @if (($booking->room_selection_mode ?? 'automatic') === 'manual')
                                            <details class="compact-panel mb-3" {{ !$checkInSelectionReady ? 'open' : '' }}>
                                                <summary>
                                                    <span>Điều kiện {{ $checkInConditionNumber }} · Phòng theo yêu cầu khách</span>
                                                    <span class="badge-clean {{ $checkInSelectionReady ? 'status-done' : 'status-warning' }}">
                                                        {{ $checkInSelectionReady ? 'Đã xử lý' : 'Chưa xử lý' }}
                                                    </span>
                                                </summary>
                                                <div class="compact-panel-body small">
                                                    Yêu cầu: <strong>{{ $booking->room_selection_request ?: 'Khách yêu cầu lễ tân chọn phòng cụ thể' }}</strong>.
                                                    @if (!$checkInSelectionReady)
                                                        <div class="mt-2"><a href="#manualRoomSelectionPanel" class="btn btn-outline-primary btn-sm">Chọn phòng thủ công</a></div>
                                                    @endif
                                                </div>
                                            </details>
                                            @php $checkInConditionNumber++; @endphp
                                        @endif

                                        @if (!$checkInRoomsReady)
                                            <details class="compact-panel mb-3" open>
                                                <summary>
                                                    <span>Điều kiện {{ $checkInConditionNumber }} · Phòng sẵn sàng</span>
                                                    <span class="badge-clean status-warning">Chưa sẵn sàng</span>
                                                </summary>
                                                <div class="compact-panel-body small">
                                                    {{ $notReadyRoomText ?: 'Có phòng chưa ở trạng thái sẵn sàng/đã giữ.' }}.
                                                    @if ($canRequestPriorityCleaning)
                                                        <div class="mt-2">Có thể gửi yêu cầu dọn ưu tiên ở phần phía trên.</div>
                                                    @endif
                                                </div>
                                            </details>
                                            @php $checkInConditionNumber++; @endphp
                                        @endif

                                        @if ($showArrivalCondition)
                                            <details class="compact-panel mb-3" id="checkInArrivalCondition" {{ $arrivalConditionBlocked ? 'open' : '' }}>
                                                <summary>
                                                    <span>Điều kiện {{ $checkInConditionNumber }} · Thời gian nhận phòng / đến muộn</span>
                                                    <span class="badge-clean {{ $arrivalConditionBlocked ? 'status-warning' : 'status-done' }}">
                                                        @if ($arrivalConditionBlocked)
                                                            Cần xử lý
                                                        @else
                                                            Vẫn được nhận phòng
                                                        @endif
                                                    </span>
                                                </summary>
                                                <div class="compact-panel-body small">
                                                    @if ($isRescheduledAfterCutoff)
                                                        Đơn vừa được lễ tân chuyển từ ngày tương lai về hôm nay.
                                                        <strong>Giờ G/no-show không áp dụng cho lần check-in này.</strong>
                                                        Khách vẫn được nhận phòng cho tới trước giờ trả phòng của lịch mới.
                                                    @elseif ($lateShowMessage)
                                                        <strong>{{ $lateShowTitle }}</strong> — {{ $lateShowMessage }}
                                                        @if ($lateShowNoShowLimitAt)
                                                            Hạn giữ phòng: <strong>{{ $lateShowNoShowLimitAt->format('d/m/Y H:i') }}</strong>.
                                                        @endif
                                                    @endif

                                                    @if ($canConfirmLateArrivalNow)
                                                        <div class="mt-2"><a href="#lateArrivalHandlingPanel" class="btn btn-outline-warning btn-sm">Xác nhận giữ phòng đến muộn</a></div>
                                                    @endif
                                                    @if ($canNoShowNow)
                                                        <form action="{{ route('admin.bookings.cancel-late-arrival', $booking->id) }}" method="POST" class="mt-2"
                                                            onsubmit="return confirm('Xác nhận khách không đến? Booking sẽ bị hủy/no-show và phòng được giải phóng.')">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">Hủy no-show</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </details>
                                            @php $checkInConditionNumber++; @endphp
                                        @endif

                                        <div id="checkInBlockingSummary"
                                            class="alert alert-warning small py-2 {{ ($checkInStaticDisabled || $initialAnyOverCapacity || !$initialActualGuestConfirmed) ? '' : 'd-none' }}"
                                            data-static-reason="{{ $checkInDisabledReason }}">
                                            <strong>Chưa thể nhận phòng.</strong>
                                            <span id="checkInBlockingSummaryText">{{ $checkInDisabledReason ?: (!$initialActualGuestConfirmed ? 'Điều kiện 2 chưa xác nhận số khách thực tế.' : ($initialAnyOverCapacity ? 'Cần chọn đủ phụ thu cho phần vượt sức chứa.' : '')) }}</span>
                                        </div>

                                        <button type="submit" id="checkInSubmitButton" class="btn btn-success w-100 py-2 fw-semibold"
                                            @disabled($checkInStaticDisabled || $initialAnyOverCapacity)
                                            title="{{ $checkInStaticDisabled ? $checkInDisabledReason : ($initialAnyOverCapacity ? 'Cần xử lý vượt sức chứa trước khi nhận phòng.' : 'Nhận phòng') }}"
                                            data-static-disabled="{{ $checkInStaticDisabled ? 1 : 0 }}">
                                            <i class="bx bx-log-in-circle me-1"></i>
                                            <span class="check-in-submit-label">Nhận phòng</span>
                                        </button>
                                    </form>

                                    @if ($isEarlyCheckInNow)
                                        <div id="earlyCheckInConfirmPanel" class="card border-warning shadow-sm mt-3 {{ session('early_checkin_confirmation_required') ? '' : 'd-none' }}">
                                            <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-start gap-3">
                                                <div>
                                                    <div class="fw-bold">Báo giá check-in sớm</div>
                                                    <div class="text-muted small">Chỉ tiếp tục khi khách đã đồng ý khoản phụ thu này.</div>
                                                </div>
                                                <button type="button" class="btn-close" id="dismissEarlyCheckInConfirm" aria-label="Đóng"></button>
                                            </div>
                                            <div class="card-body">
                                                <div class="alert alert-warning small mb-3">
                                                    <div class="fw-bold mb-1">Khách đến sớm {{ $earlyCheckInDurationText }}</div>
                                                    Hiện tại: <strong>{{ $nowVnForCheckInFlow->format('d/m/Y H:i') }}</strong><br>
                                                    Giờ check-in chuẩn: <strong>{{ $standardCheckInAt?->format('d/m/Y H:i') }}</strong>
                                                </div>

                                                <div class="info-list">
                                                    <div class="info-line">
                                                        <span class="info-label">Chính sách áp dụng</span>
                                                        <span class="info-value">{{ $earlyCheckInPolicyText }}</span>
                                                    </div>
                                                    <div class="info-line">
                                                        <span class="info-label">Giá gốc tính phụ thu</span>
                                                        <span class="info-value">{{ number_format($earlyCheckInBasePrice, 0, ',', '.') }}đ</span>
                                                    </div>
                                                    <div class="info-line">
                                                        <span class="info-label">Tỷ lệ phụ thu</span>
                                                        <span class="info-value">{{ $earlyCheckInPercent }}%</span>
                                                    </div>
                                                    <div class="info-line">
                                                        <span class="info-label">Phụ thu nhận phòng sớm</span>
                                                        <span class="info-value text-danger fs-5 fw-bold">
                                                            {{ number_format($earlyCheckInFeePreview, 0, ',', '.') }}đ
                                                        </span>
                                                    </div>
                                                    <div class="info-line">
                                                        <span class="info-label">Tổng tiền sau khi cộng</span>
                                                        <span class="info-value text-danger fw-bold">
                                                            {{ number_format($earlyCheckInFinalTotalPreview, 0, ',', '.') }}đ
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-end gap-2 mt-3">
                                                    <button type="button" class="btn btn-outline-secondary" id="cancelEarlyCheckInSubmit">
                                                        Chưa đồng ý
                                                    </button>
                                                    <button type="submit" form="checkInForm" name="early_check_in_action" value="accept_fee" class="btn btn-success" id="confirmEarlyCheckInSubmit">
                                                        Khách đồng ý - Tiếp tục check-in
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($canHandleNoShowNow)
                                        <details class="compact-panel mt-3" id="lateArrivalHandlingPanel" {{ $autoOpenLateArrivalPanel ? 'open' : '' }}>
                                            <summary>
                                                <span>Xử lý khách đến muộn</span>
                                                <span class="badge-clean {{ $autoOpenLateArrivalPanel ? 'status-warning' : 'status-muted' }}">
                                                    {{ $autoOpenLateArrivalPanel ? 'Cần chú ý' : 'Mở khi cần' }}
                                                </span>
                                            </summary>
                                            <div class="compact-panel-body bg-light">
                                            <div class="small text-muted mb-3">
                                                Giờ G: <strong>{{ $lateShowNoShowLimitAt?->format('H:i d/m/Y') }}</strong>.
                                            </div>

                                            @if ($booking->late_arrival_confirmed_at && (float) $booking->late_arrival_hours > 0)
                                                <div class="alert alert-info py-2 small">
                                                    <div><strong>Đã xác nhận giữ phòng sau giờ G.</strong></div>
                                                    <div>Hạn giữ mới: <strong>{{ $lateShowNoShowLimitAt?->format('H:i d/m/Y') }}</strong>.</div>
                                                    <div>Phụ thu đến muộn: <strong>{{ number_format((float) $booking->late_arrival_fee, 0, ',', '.') }}đ</strong>.</div>
                                                    <div class="mt-1">{{ $booking->late_arrival_policy }}</div>
                                                </div>
                                            @endif

                                            <div class="d-flex gap-2 flex-wrap align-items-start">
                                                @if ($canConfirmLateArrivalNow)
                                                    <form action="{{ route('admin.bookings.confirm-late-arrival', $booking->id) }}" method="POST"
                                                        class="flex-fill border rounded-3 p-3 bg-white" id="lateArrivalForm"
                                                        data-one-night-total="{{ $lateArrivalOneNightTotal }}"
                                                        data-cutoff-at="{{ $lateShowCheckInAt ? $setPolicyTime($lateShowCheckInAt, $lateArrivalCutoffTimePolicy)->format('Y-m-d H:i') : '' }}"
                                                        data-tier1-end="{{ $lateArrivalTier1EndPolicy }}"
                                                        data-percent-1="{{ $lateArrivalPercent1Policy }}"
                                                        data-percent-2="{{ $lateArrivalPercent2Policy }}"
                                                        data-percent-next-day="{{ $lateArrivalNextDayPercentPolicy }}"
                                                        data-grace-minutes="{{ $lateArrivalGraceMinutesPolicy }}"
                                                        data-check-out-at="{{ $lateShowCheckOutAt?->format('Y-m-d H:i') }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <label class="form-label fw-semibold">Ngày giờ khách dự kiến đến sau giờ G</label>
                                                        <div class="row g-2 mb-2">
                                                            <div class="col-md-7">
                                                                <label class="form-label small text-muted mb-1">Ngày dự kiến đến</label>
                                                                <input type="date" name="expected_arrival_date" class="form-control" required
                                                                    value="{{ old('expected_arrival_date', optional($lateShowCheckInAt)->format('Y-m-d')) }}"
                                                                    min="{{ optional($lateShowCheckInAt)->format('Y-m-d') }}"
                                                                    max="{{ optional($lateShowCheckOutAt)->format('Y-m-d') }}">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label small text-muted mb-1">Giờ dự kiến đến</label>
                                                                <input type="text" name="expected_arrival_time" id="expectedArrivalTime" class="form-control" required
                                                                    value="{{ old('expected_arrival_time', \Carbon\Carbon::createFromFormat('H:i', $lateArrivalCutoffTimePolicy)->addMinutes($lateArrivalGraceMinutesPolicy)->format('H:i')) }}"
                                                                    placeholder="Ví dụ: 18:30" inputmode="numeric" autocomplete="off">
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-outline-primary w-100">
                                                            Xác nhận đến sau giờ G - giữ tiếp có phụ thu
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($canNoShowNow)
                                                    <form action="{{ route('admin.bookings.cancel-late-arrival', $booking->id) }}" method="POST" class="flex-fill"
                                                        onsubmit="return confirm('Xác nhận khách không đến? Đơn sẽ bị hủy và phòng được mở bán lại.')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-outline-danger w-100">
                                                            Hủy no-show
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>

                                            @if ($canConfirmLateArrivalNow)
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
                                                                    Khách dự kiến đến sau giờ G {{ $lateArrivalCutoffTimePolicy }} và yêu cầu khách sạn tiếp tục giữ phòng.
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
                                            @endif
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            @elseif ($booking->status == 'checked_in' && !$hasInspection)
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Gia hạn lưu trú</div>
                                            <div class="text-muted small">Kiểm tra trước để tránh trùng lịch với booking mới.
                                            </div>
                                        </div>
                                    </div>

                                    @php
                                        $extendTypeLabel = $booking->booking_type == 'hourly' ? 'Đặt theo giờ' : 'Đặt qua đêm';
                                        $currentRoomNumbersForExtend = $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ');
                                        $extendPreview = session('extend_stay_preview.' . $booking->id);
                                        $previewDateValue = old(
                                            'new_check_out_date',
                                            $extendPreview['new_check_out_date'] ?? ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : $booking->check_out_date)
                                        );
                                        $previewTimeValue = old(
                                            'new_check_out_time',
                                            $extendPreview['new_check_out_time'] ?? ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('H:i') : $standardCheckOutTimePolicy)
                                        );
                                    @endphp

                                    <div class="soft-note mb-3">
                                        <strong>{{ $extendTypeLabel }}</strong> · Phòng
                                        {{ $currentRoomNumbersForExtend ?: '---' }} ·
                                        Check-out hiện tại:
                                        {{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '---' }}
                                    </div>

                                    <form action="{{ route('admin.bookings.extend-stay.preview', $booking->id) }}"
                                        method="POST" id="extendStayPreviewForm"
                                        data-current-checkout-date="{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : '' }}"
                                        data-current-checkout-time="{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('H:i') : '' }}"
                                        data-current-checkout-text="{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '' }}">
                                        @csrf

                                        <div class="row g-2 mb-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Ngày trả phòng mới</label>
                                                <input type="date" id="extendCheckOutDateVn" name="new_check_out_date" class="form-control"
                                                    min="{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : date('Y-m-d') }}"
                                                    data-extension-min-date="{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : date('Y-m-d') }}"
                                                    value="{{ $previewDateValue }}" required>
                                                @error('new_check_out_date')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Giờ trả phòng mới</label>
                                                <input type="text" name="new_check_out_time" id="extendCheckOutTime"
                                                    class="form-control" value="{{ $previewTimeValue }}"
                                                    placeholder="Ví dụ: {{ $standardCheckInTimePolicy }}" required>
                                                @error('new_check_out_time')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div id="extendStayTimeRule" class="small mb-3 text-muted">
                                            Thời gian trả mới phải sau check-out hiện tại
                                            <strong>{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '---' }}</strong>.
                                        </div>

                                        <button type="submit" class="btn btn-outline-primary w-100" id="extendStayPreviewSubmit">
                                            <i class="bx bx-search-alt me-1"></i>
                                            Kiểm tra khả năng gia hạn
                                        </button>
                                    </form>

                                    @if ($extendPreview)
                                        @php
                                            $previewAlertClass = 'alert-success';
                                            if (($extendPreview['status'] ?? '') === 'need_room_change') {
                                                $previewAlertClass = 'alert-warning';
                                            }
                                            if (($extendPreview['status'] ?? '') === 'blocked') {
                                                $previewAlertClass = 'alert-danger';
                                            }
                                        @endphp

                                        <div class="alert {{ $previewAlertClass }} mt-3 mb-0" id="extend-stay-preview">
                                            <h6 class="fw-bold mb-2">{{ $extendPreview['title'] ?? 'Kết quả kiểm tra gia hạn' }}</h6>

                                            @if (($extendPreview['status'] ?? '') === 'blocked')
                                                <div class="small">
                                                    <div class="mb-2"><strong>Thời gian muốn gia hạn:</strong> {{ $extendPreview['period_text'] ?? '---' }}</div>
                                                    <div class="fw-semibold text-danger mb-1">Lý do không thể gia hạn:</div>
                                                    <div>{{ $extendPreview['message'] ?? 'Khoảng thời gian này không thể gia hạn.' }}</div>
                                                </div>
                                            @else
                                                <div class="small mb-2">
                                                    <strong>Khung giờ:</strong> {{ $extendPreview['period_text'] ?? '---' }}<br>
                                                    <strong>Phí dự kiến:</strong>
                                                    <span class="fw-bold text-danger">{{ $extendPreview['fee_text'] ?? '0đ' }}</span><br>
                                                    <strong>Cách tính:</strong> {{ $extendPreview['policy_text'] ?? '---' }}
                                                </div>
                                                <div class="small">{{ $extendPreview['message'] ?? '' }}</div>

                                            @if (empty($extendPreview['repricing']))
                                                @php
                                                    $sameDayExtensionFee = (float) ($extendPreview['fee_amount'] ?? 0);
                                                    $sameDayExtensionTotal = $finalTotal + $sameDayExtensionFee;
                                                    $sameDayExtensionRemaining = max(0, $sameDayExtensionTotal - $adminPaymentPaidAmount);
                                                @endphp
                                                <details class="border rounded bg-white p-2 mt-2 small">
                                                    <summary class="fw-semibold">Xem chi tiết tiền gia hạn</summary>
                                                    <div class="mt-2">
                                                        <div class="d-flex justify-content-between gap-2"><span>Tổng booking hiện tại</span><strong>{{ number_format($finalTotal, 0, ',', '.') }}đ</strong></div>
                                                        <div class="d-flex justify-content-between gap-2 mt-1"><span>Phụ thu gia hạn lưu trú</span><strong class="text-danger">+{{ number_format($sameDayExtensionFee, 0, ',', '.') }}đ</strong></div>
                                                        <div class="text-muted border-bottom pb-2 mt-1">{{ $extendPreview['policy_text'] ?? 'Theo chính sách gia hạn hiện hành.' }}</div>
                                                        <div class="d-flex justify-content-between gap-2 mt-2"><span>Tổng sau gia hạn</span><strong>{{ number_format($sameDayExtensionTotal, 0, ',', '.') }}đ</strong></div>
                                                        <div class="d-flex justify-content-between gap-2 mt-1"><span>Khách đã thanh toán</span><strong>-{{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ</strong></div>
                                                        <div class="d-flex justify-content-between gap-2 mt-1 text-danger"><span>Còn phải thu</span><strong>{{ number_format($sameDayExtensionRemaining, 0, ',', '.') }}đ</strong></div>
                                                    </div>
                                                </details>
                                            @endif

                                            @if (!empty($extendPreview['repricing']))
                                                @php
                                                    $extendRepricing = $extendPreview['repricing'];
                                                    $extendRepriceOld = $extendRepricing['old'] ?? [];
                                                    $extendRepriceNew = $extendRepricing['new'] ?? [];
                                                    $extendServiceLines = collect($extendRepricing['service_preview']['lines'] ?? []);
                                                    $extendServiceChanges = $extendServiceLines
                                                        ->filter(fn ($line) => !empty($line['will_reprice']) || !empty($line['will_remove']));
                                                    $extendRemovedPromotions = $extendRepricing['promotion_preview']['removed'] ?? [];
                                                @endphp
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
                                                                    <td class="text-end">{{ $extendRepriceOld['night_count'] ?? 0 }}</td>
                                                                    <td class="text-end fw-semibold">{{ $extendRepriceNew['night_count'] ?? 0 }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Tiền phòng</td>
                                                                    <td class="text-end">{{ number_format((float) ($extendRepriceOld['room_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    <td class="text-end">{{ number_format((float) ($extendRepriceNew['room_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Dịch vụ / phụ thu</td>
                                                                    <td class="text-end">{{ number_format((float) ($extendRepriceOld['service_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    <td class="text-end">{{ number_format((float) ($extendRepriceNew['service_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Khuyến mãi / hỗ trợ</td>
                                                                    <td class="text-end">-{{ number_format((float) ($extendRepriceOld['discount_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    <td class="text-end">-{{ number_format((float) ($extendRepriceNew['discount_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                <tr class="table-primary">
                                                                    <td class="fw-bold">Tổng cần thanh toán</td>
                                                                    <td class="text-end fw-bold">{{ number_format((float) ($extendRepriceOld['total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                    <td class="text-end fw-bold">{{ number_format((float) ($extendRepriceNew['total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Khách đã thanh toán</td>
                                                                    <td class="text-end" colspan="2">{{ number_format((float) ($extendRepricing['paid_total'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Còn phải thu</td>
                                                                    <td class="text-end text-danger fw-semibold" colspan="2">{{ number_format((float) ($extendRepriceNew['remaining'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Khách đang trả dư</td>
                                                                    <td class="text-end fw-semibold" colspan="2">{{ number_format((float) ($extendRepriceNew['overpayment'] ?? 0), 0, ',', '.') }}đ</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <details class="border rounded p-2 mb-2 bg-light">
                                                        <summary class="fw-semibold">Xem chi tiết từng khoản tiền sau gia hạn</summary>
                                                        <div class="mt-2">
                                                            <div class="d-flex justify-content-between gap-2 mb-1">
                                                                <span>Tiền phòng</span>
                                                                <strong>+{{ number_format((float) ($extendRepriceNew['room_total'] ?? 0), 0, ',', '.') }}đ</strong>
                                                            </div>
                                                            @foreach ($extendServiceLines as $serviceLine)
                                                                @continue(!empty($serviceLine['will_remove']))
                                                                <div class="border-top pt-2 mt-2">
                                                                    <div class="d-flex justify-content-between gap-2">
                                                                        <span>{{ $serviceLine['name'] ?? 'Khoản dịch vụ' }}</span>
                                                                        <strong>+{{ number_format((float) ($serviceLine['new_total'] ?? 0), 0, ',', '.') }}đ</strong>
                                                                    </div>
                                                                    <div class="text-muted">{{ $serviceLine['new_formula'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                            @if ((float) ($extendRepriceNew['inspection_total'] ?? 0) > 0)
                                                                <div class="d-flex justify-content-between gap-2 border-top pt-2 mt-2">
                                                                    <span>Dịch vụ tại phòng / hư hại đã duyệt</span>
                                                                    <strong>+{{ number_format((float) $extendRepriceNew['inspection_total'], 0, ',', '.') }}đ</strong>
                                                                </div>
                                                            @endif
                                                            @if ((float) ($extendRepriceNew['manual_room_selection_fee'] ?? 0) > 0)
                                                                <div class="d-flex justify-content-between gap-2 border-top pt-2 mt-2">
                                                                    <span>Phí chọn phòng thủ công</span>
                                                                    <strong>+{{ number_format((float) $extendRepriceNew['manual_room_selection_fee'], 0, ',', '.') }}đ</strong>
                                                                </div>
                                                            @endif
                                                            <div class="d-flex justify-content-between gap-2 border-top pt-2 mt-2 text-success">
                                                                <span>Mã giảm giá / hỗ trợ</span>
                                                                <strong>-{{ number_format((float) ($extendRepriceNew['discount_total'] ?? 0), 0, ',', '.') }}đ</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between gap-2 border-top pt-2 mt-2">
                                                                <span>Tổng cần thanh toán</span>
                                                                <strong>{{ number_format((float) ($extendRepriceNew['total'] ?? 0), 0, ',', '.') }}đ</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between gap-2 mt-1">
                                                                <span>Khách đã thanh toán</span>
                                                                <strong>-{{ number_format((float) ($extendRepricing['paid_total'] ?? 0), 0, ',', '.') }}đ</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between gap-2 mt-1 text-danger">
                                                                <span>Còn phải thu</span>
                                                                <strong>{{ number_format((float) ($extendRepriceNew['remaining'] ?? 0), 0, ',', '.') }}đ</strong>
                                                            </div>
                                                        </div>
                                                    </details>
                                                    @if ($extendServiceChanges->isNotEmpty())
                                                        <div class="alert alert-info py-2 mb-2">
                                                            <strong>Dịch vụ tính lại theo số đêm mới:</strong>
                                                            @foreach ($extendServiceChanges as $serviceLine)
                                                                <div>
                                                                    {{ $serviceLine['name'] ?? 'Dịch vụ' }}:
                                                                    {{ number_format((float) ($serviceLine['old_total'] ?? 0), 0, ',', '.') }}đ
                                                                    → {{ number_format((float) ($serviceLine['new_total'] ?? 0), 0, ',', '.') }}đ
                                                                    <span class="text-muted">{{ $serviceLine['new_formula'] ?? '' }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if (!empty($extendRemovedPromotions))
                                                        <div class="alert alert-warning py-2 mb-0">
                                                            <strong>Mã sẽ bị gỡ:</strong>
                                                            @foreach ($extendRemovedPromotions as $removedPromotion)
                                                                <div>{{ $removedPromotion['code'] ?? '---' }} — {{ $removedPromotion['reason'] ?? 'Không còn đủ điều kiện.' }}</div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @endif

                                            @if (!empty($extendPreview['conflicts']))
                                                <div class="border rounded bg-white p-2 mt-2 small">
                                                    <strong>{{ ($extendPreview['status'] ?? '') === 'blocked' ? 'Xung đột khiến không thể gia hạn:' : 'Booking bị giao thời gian:' }}</strong>
                                                    <ul class="mb-0 mt-1">
                                                        @foreach ($extendPreview['conflicts'] as $conflict)
                                                            <li>
                                                                Phòng {{ $conflict['room_number'] }} / {{ $conflict['category_name'] }}
                                                                đã có booking {{ $conflict['booking_code'] }} của
                                                                {{ $conflict['customer_name'] }}
                                                                từ {{ $conflict['check_in_text'] }} đến {{ $conflict['check_out_text'] }}.
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            @if (!empty($extendPreview['replacements']))
                                                <div class="border rounded bg-white p-2 mt-2 small">
                                                    <strong>Phòng cùng hạng có thể chuyển:</strong>
                                                    <ul class="mb-0 mt-1">
                                                        @foreach ($extendPreview['replacements'] as $replacement)
                                                            <li>
                                                                Chuyển phòng {{ $replacement['old_room_number'] }}
                                                                → {{ $replacement['new_room_number'] }} cùng hạng
                                                                {{ $replacement['category_name'] }}.
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <div class="d-flex gap-2 flex-wrap mt-3">
                                                <form action="{{ route('admin.bookings.extend-stay.discard-preview', $booking->id) }}" method="POST" class="flex-fill">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-secondary w-100">
                                                        <i class="bx bx-x me-1"></i>Đóng bản xem trước
                                                    </button>
                                                </form>
                                                @if (($extendPreview['status'] ?? '') !== 'blocked')
                                                    <form action="{{ route('admin.bookings.extend-stay', $booking->id) }}" method="POST"
                                                        class="flex-fill"
                                                        onsubmit="return confirm('Xác nhận gia hạn theo kết quả kiểm tra này?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="new_check_out_date"
                                                            value="{{ $extendPreview['new_check_out_date'] }}">
                                                        <input type="hidden" name="new_check_out_time"
                                                            value="{{ $extendPreview['new_check_out_time'] }}">
                                                        <button type="submit" class="btn btn-success w-100">
                                                            Xác nhận gia hạn
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <form action="{{ route('admin.bookings.request-inspection', $booking->id) }}" method="POST"
                                    onsubmit="return confirm('Chuyển phòng sang trạng thái chờ kiểm tra?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="bx bx-search-alt me-1"></i>
                                        Yêu cầu kiểm tra phòng
                                    </button>
                                </form>
                            @elseif ($booking->status == 'inspection_requested' && $allInspectionsConfirmed)
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Chốt phí và check-out</div>
                                        </div>
                                    </div>

                                    <form action="{{ route('admin.bookings.check-out', $booking->id) }}" method="POST"
                                        id="checkOutForm">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="checkout_late_fee_confirm" id="checkoutLateFeeConfirm" value="">
                                        <input type="hidden" id="checkoutLateFeeAmount" value="{{ $checkoutLateFeePreview }}">

                                        @if ($checkoutLateFeePreview > 0)
                                            <div class="alert alert-danger small mb-3">
                                                <div class="fw-bold mb-1">Phát sinh phụ thu check-out muộn</div>
                                                Khách đã quá giờ trả phòng khoảng
                                                <strong>{{ $checkoutLateHoursPreview }} giờ</strong>.
                                                <br>
                                                <strong>Chính sách:</strong> {{ $checkoutLatePolicyText }}
                                                <br>
                                                <strong>Phụ thu cần ghi thêm lúc này:</strong>
                                                <span
                                                    class="fw-bold">{{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ</span>
                                                <br>
                                                <span class="text-muted">{{ $checkoutLateNoteText }}</span>

                                                <div class="mt-2 fw-semibold">
                                                </div>
                                            </div>
                                        @else
                                            <div class="soft-note mb-3">
                                                {{ $checkoutLatePolicyText }}
                                                @if ($existingCheckoutLateFeeTotal > 0)
                                                    Khoản phụ thu check-out muộn đã được ghi nhận:
                                                    <strong>{{ number_format($existingCheckoutLateFeeTotal, 0, ',', '.') }}đ</strong>.
                                                    Hệ thống vẫn kiểm tra lại theo giờ trả thực tế khi bấm check-out và chỉ cộng thêm nếu khách đã sang mốc phí cao hơn.
                                                @endif
                                            </div>
                                        @endif

                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-clean align-middle mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td>Tiền phòng</td>
                                                        <td class="text-end fw-bold">
                                                            {{ number_format($roomTotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dịch vụ khách gọi thêm / phụ thu đã ghi nhận</td>
                                                        <td class="text-end fw-bold">
                                                            {{ number_format($serviceItemTotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dịch vụ tại phòng / hư hại đã duyệt</td>
                                                        <td class="text-end fw-bold">
                                                            {{ number_format($approvedInspectionTotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    @if ($checkoutLateFeePreview > 0)
                                                        <tr>
                                                            <td>Phụ thu check-out muộn cần ghi thêm</td>
                                                            <td class="text-end fw-bold text-danger">
                                                                {{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    @if ($promotionDiscountTotal > 0)
                                                        <tr>
                                                            <td>
                                                                Mã ưu đãi đã áp dụng
                                                                <div class="small text-muted">
                                                                    Giảm tiền: {{ number_format($promotionMoneyDiscountTotal, 0, ',', '.') }}đ · Dịch vụ: {{ number_format($promotionServiceDiscountTotal, 0, ',', '.') }}đ · Nâng hạng: {{ number_format($promotionRoomUpgradeDiscountTotal, 0, ',', '.') }}đ
                                                                </div>
                                                            </td>
                                                            <td class="text-end fw-bold text-success">
                                                                -{{ number_format($promotionDiscountTotal, 0, ',', '.') }}đ
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td>Mức cọc {{ $depositPercentLabel }} hiện tại</td>
                                                        <td class="text-end fw-bold">
                                                            {{ number_format($adminPaymentDepositTarget, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Đã phân bổ vào cọc</td>
                                                        <td class="text-end fw-bold">
                                                            -{{ number_format($actualDepositPaid, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Thanh toán thêm / trả trước</td>
                                                        <td class="text-end fw-bold">
                                                            -{{ number_format($additionalPaidTotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tổng khách đã thanh toán</td>
                                                        <td class="text-end fw-bold">
                                                            -{{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tiền trả trước còn dư để bù trừ</td>
                                                        <td class="text-end fw-bold {{ $currentOverpaymentTotal > 0 ? 'text-warning' : '' }}">
                                                            {{ number_format($currentOverpaymentTotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr class="table-light">
                                                        <td class="fw-bold">Còn lại cần thu trước khi bấm check-out</td>
                                                        <td class="text-end fw-bold text-danger fs-5">
                                                            {{ number_format($remainingTotal, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="checkout-payment-confirm-box mb-3">
                                            @if ($remainingTotal > 0)
                                                <div class="alert alert-warning small mb-0">
                                                    <div class="fw-bold mb-1">Chưa thể check-out</div>
                                                    Booking còn thiếu
                                                    <strong>{{ number_format($remainingTotal, 0, ',', '.') }}đ</strong>
                                                    trên hệ thống. Hãy ghi nhận khoản khách thực sự đã trả tại khối
                                                    <strong>Thanh toán</strong> ở thanh bên. Sau khi số còn lại về 0đ,
                                                    bấm Check-out lại.
                                                </div>
                                            @else
                                                <div class="alert alert-success small mb-0">
                                                    <div class="fw-bold mb-1">Đã đủ điều kiện thanh toán</div>
                                                </div>
                                            @endif
                                        </div>

                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bx bx-log-out-circle me-1"></i>
                                            Check-out
                                        </button>
                                    </form>

                                    @if ($checkoutLateFeePreview > 0)
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
                                                            {{ $checkoutLateReasonText }}<br>
                                                            {{ $checkoutLateNoteText }}
                                                        </div>
                                                        <div class="info-list">
                                                            <div class="info-line">
                                                                <span class="info-label">Chính sách áp dụng</span>
                                                                <span class="info-value">{{ $checkoutLatePolicyText }}</span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Giá gốc tính phụ thu</span>
                                                                <span class="info-value">{{ number_format($checkoutLateBasePrice, 0, ',', '.') }}đ</span>
                                                            </div>
                                                            @if ($booking->booking_type != 'hourly')
                                                                <div class="info-line">
                                                                    <span class="info-label">Tỷ lệ theo khung giờ</span>
                                                                    <span class="info-value">{{ rtrim(rtrim(number_format($checkoutLatePercent, 2, '.', ''), '0'), '.') }}%</span>
                                                                </div>
                                                            @endif
                                                            <div class="info-line">
                                                                <span class="info-label">Cơ chế tính</span>
                                                                <span class="info-value">{{ $checkoutLateFormulaText }}</span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Phụ thu phát sinh</span>
                                                                <span class="info-value text-danger fs-5">{{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ</span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Tổng cần thanh toán sau khi cộng</span>
                                                                <span class="info-value text-danger">{{ number_format($finalTotal, 0, ',', '.') }}đ</span>
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
                                    @endif

                                    <details class="compact-panel mt-3" @if($errors->has('checkout_extra_name') || $errors->has('checkout_extra_amount')) open @endif>
                                        <summary>
                                            <span>Thêm phí phát sinh khác</span>
                                            <span class="badge-clean status-muted">Ghi nhận trước khi thu tiền</span>
                                        </summary>
                                        <div class="compact-panel-body">
                                            <form action="{{ route('admin.bookings.checkout-fees.store', $booking->id) }}" method="POST">
                                                @csrf
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Tên khoản phí <span class="text-danger">*</span></label>
                                                        <input type="text" name="checkout_extra_name"
                                                            class="form-control @error('checkout_extra_name') is-invalid @enderror"
                                                            value="{{ old('checkout_extra_name') }}"
                                                            placeholder="Ví dụ: Mất thẻ phòng" required>
                                                        @error('checkout_extra_name')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Số tiền <span class="text-danger">*</span></label>
                                                        <input type="number" name="checkout_extra_amount"
                                                            class="form-control @error('checkout_extra_amount') is-invalid @enderror"
                                                            min="1000" step="1000" value="{{ old('checkout_extra_amount') }}"
                                                            placeholder="100000" required>
                                                        @error('checkout_extra_amount')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Ghi chú</label>
                                                        <input type="text" name="checkout_extra_note" class="form-control"
                                                            value="{{ old('checkout_extra_note') }}" placeholder="Ghi chú nếu có">
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
                            @elseif ($booking->status == 'inspection_requested' && $hasInspection && !$allInspectionsConfirmed)
                                @include('admin.pages.bookings.partials.inspection-guest-consultation')
                            @else
                                <div class="soft-note">
                                </div>
                            @endif
                        </div>
                    </section>


                    <details class="compact-panel mb-3">
                        <summary>
                            <span>Mã ưu đãi / hỗ trợ khách</span>
                            <span class="badge-clean status-muted">
                                {{ $booking->bookingPromotions->count() }} mã đã áp dụng · mở để xem/thêm
                            </span>
                        </summary>

                        <div class="compact-panel-body">
                            @php
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

                            @endphp

                            @if ($booking->bookingPromotions->count() > 0)
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
                                            @foreach ($booking->bookingPromotions as $bookingPromotion)
                                                <tr>
                                                    <td class="fw-bold">{{ $bookingPromotion->code_snapshot }}</td>
                                                    <td>
                                                        @if (($bookingPromotion->scope ?? 'booking') === 'room')
                                                            <span class="badge bg-primary">Phòng {{ $bookingPromotion->bookingRoom?->room?->room_number ?? '---' }}</span>
                                                        @else
                                                            <span class="badge bg-secondary">Toàn bộ đơn</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $appliedTypeConfig = $promotionTypeDisplayConfig[$bookingPromotion->promotion_type_snapshot] ?? [
                                                                'label' => $bookingPromotion->type_label,
                                                                'badge' => 'bg-secondary',
                                                            ];
                                                        @endphp
                                                        <span class="badge {{ $appliedTypeConfig['badge'] }}">
                                                            {{ $appliedTypeConfig['label'] }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $bookingPromotion->applied_channel == 'admin' ? 'Admin' : 'User' }}</td>
                                                    <td>{{ $bookingPromotion->user->name ?? 'Khách/User' }}</td>
                                                    <td class="text-end text-success fw-bold">
                                                        -{{ number_format((float) ($bookingPromotion->money_discount_amount ?? $bookingPromotion->discount_amount), 0, ',', '.') }}đ
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        -{{ number_format((float) ($bookingPromotion->service_discount_amount ?? 0), 0, ',', '.') }}đ
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        -{{ number_format((float) ($bookingPromotion->room_upgrade_discount_amount ?? 0), 0, ',', '.') }}đ
                                                    </td>
                                                    <td class="text-end text-success fw-bold">
                                                        -{{ number_format((float) $bookingPromotion->discount_amount, 0, ',', '.') }}đ
                                                    </td>
                                                    <td class="small text-muted">
                                                        {{ $bookingPromotion->note ?: '---' }}
                                                        @if ($bookingPromotion->serviceOffers->count() > 0)
                                                            <div class="mt-1">
                                                                @foreach ($bookingPromotion->serviceOffers as $offerSnapshot)
                                                                    <span class="badge bg-success-subtle text-success border me-1">
                                                                        {{ $offerSnapshot->service_name_snapshot }} x{{ $offerSnapshot->quantity }}: -{{ number_format((float) $offerSnapshot->discount_amount, 0, ',', '.') }}đ
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        @if ($bookingPromotion->roomUpgradeOffers->count() > 0)
                                                            <div class="mt-1">
                                                                @foreach ($bookingPromotion->roomUpgradeOffers as $upgradeSnapshot)
                                                                    <span class="badge bg-primary-subtle text-primary border me-1">
                                                                        {{ $upgradeSnapshot->old_room_category_name_snapshot }} → {{ $upgradeSnapshot->new_room_category_name_snapshot }}: hỗ trợ {{ number_format((float) $upgradeSnapshot->covered_amount, 0, ',', '.') }}đ
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="soft-note mb-3">
                                    Booking này chưa áp dụng mã ưu đãi nào.
                                </div>
                            @endif

                            @if (in_array($booking->status, ['pending', 'confirmed', 'checked_in']) && $booking->payment_status != 'paid')
                                @if (($availablePromotions ?? collect())->count() > 0)
                                    @php
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
                                    @endphp
                                    <form action="{{ route('admin.bookings.promotions.store', $booking->id) }}"
                                        method="POST"
                                        data-booking-promotion-form
                                        data-existing-type-counts='@json($existingPromotionTypeCounts)'
                                        data-existing-code-count="{{ $existingPromotionCodes->count() }}"
                                        data-existing-has-solo="{{ $existingHasSoloPromotion ? 1 : 0 }}">
                                        @csrf

                                        @foreach ($promotionTypeDisplayConfig as $promotionType => $typeConfig)
                                            @php
                                                $groupPromotions = collect($availablePromotionGroups ?? [])->get($promotionType, collect());
                                            @endphp

                                            @if ($groupPromotions->count() > 0)
                                                <div class="mb-3" data-promotion-group data-promotion-type="{{ $promotionType }}" data-promotion-limit="{{ $typeConfig['limit'] ?? '' }}">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                        <div>
                                                            <div class="fw-bold">
                                                                {{ $typeConfig['label'] }}
                                                                <span class="badge {{ $typeConfig['badge'] }} ms-1">
                                                                    {{ $groupPromotions->count() }}
                                                                </span>
                                                            </div>
                                                            <div class="promotion-meta">
                                                                {{ $typeConfig['hint'] }}
                                                            </div>
                                                            <div class="promotion-meta fw-semibold text-dark mt-1">
                                                                {{ $typeConfig['rule'] }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="promotion-list">
                                                        @foreach ($groupPromotions as $promotion)
                                                            @php
                                                                $promotionDiscountText = $promotion->discount_type == 'percent'
                                                                    ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                                    : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';

                                                                if ($promotion->discount_type == 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                                    $promotionDiscountText .= ' - tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                                }
                                                            @endphp

                                                            <label class="promotion-card mb-0">
                                                                <div class="form-check">
                                                                    <input type="checkbox"
                                                                        name="promotion_codes[]"
                                                                        value="{{ $promotion->code }}"
                                                                        class="form-check-input booking-promotion-check"
                                                                        data-code="{{ $promotion->code }}"
                                                                        data-type="{{ $promotion->promotion_type }}"
                                                                        data-stackable="{{ $promotion->is_stackable ? 1 : 0 }}"
                                                                        data-requires-note="{{ $promotion->requires_note || $promotion->promotion_type == 'support_discount' ? 1 : 0 }}">

                                                                    <div class="ms-1">
                                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                                            <div>
                                                                                <div class="promotion-code">{{ $promotion->code }}</div>
                                                                                <div class="fw-semibold">{{ $promotion->name }}</div>
                                                                            </div>
                                                                            <span class="badge {{ $typeConfig['badge'] }}">{{ $typeConfig['label'] }}</span>
                                                                        </div>
                                                                        <div class="promotion-meta mt-1">
                                                                            Giảm {{ $promotionDiscountText }}
                                                                            @if ((float) $promotion->min_booking_amount > 0)
                                                                                · Đơn từ {{ number_format((float) $promotion->min_booking_amount, 0, ',', '.') }}đ
                                                                            @endif
                                                                            @if ((int) $promotion->min_nights > 0)
                                                                                · Từ {{ (int) $promotion->min_nights }} đêm
                                                                            @endif
                                                                            @if ((int) $promotion->min_rooms > 0)
                                                                                · Từ {{ (int) $promotion->min_rooms }} phòng
                                                                            @endif
                                                                            @if ($promotion->requires_note || $promotion->promotion_type == 'support_discount')
                                                                                · Cần nhập lý do
                                                                            @endif
                                                                            · {{ $promotion->is_stackable ? 'Có thể dùng cùng nhóm mã khác' : 'Chỉ dùng một mình' }}
                                                                        </div>

                                                                        @if ($promotion->serviceOffers->count() > 0)
                                                                            <div class="promotion-meta mt-1 text-success">
                                                                                Dịch vụ ưu đãi:
                                                                                {{ $promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ') }}
                                                                            </div>
                                                                        @endif

                                                                        @if ($promotion->roomUpgradeOffers->count() > 0)
                                                                            <div class="promotion-meta mt-1 text-primary">
                                                                                Ưu đãi nâng hạng:
                                                                                {{ $promotion->roomUpgradeOffers->map(fn ($offer) => $offer->cover_label)->implode(' · ') }}
                                                                                · Chỉ áp dụng khi booking đã có lịch sử đổi lên hạng cao hơn chưa dùng mã.
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

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
                                @else
                                    <div class="soft-note mb-0">
                                        Hiện không còn mã nào phù hợp để áp dụng thêm cho booking này.
                                    </div>
                                @endif
                            @else
                                <div class="soft-note mb-0">
                                    Booking đã thanh toán đủ hoặc đã kết thúc nên không thể áp thêm mã.
                                </div>
                            @endif
                        </div>
                    </details>

                    @if ($canManageBookingRooms)
                        <details class="compact-panel d-none" id="roomManagementSource">
                            <summary>
                                <span>Quản lý phòng: thêm phòng / đổi hạng</span>
                                <span class="badge-clean status-muted">{{ $assignedRooms->count() }} phòng hiện tại</span>
                            </summary>

                            <div class="compact-panel-body">
                                <div class="soft-note mb-3">
                                    <strong>Khung kiểm tra:</strong>
                                    {{ $lateShowCheckInAt?->format('d/m/Y H:i') ?? '---' }}
                                    → {{ $lateShowCheckOutAt?->format('d/m/Y H:i') ?? '---' }}.
                                    Phòng hiện tại:
                                    {{ $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ') ?: 'Chưa gán phòng' }}.
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
                                                    @foreach ($roomCategoriesForBookingManage as $category)
                                                        <tr>
                                                            <td class="fw-bold">{{ $category->name }}</td>
                                                            <td>{{ $category->adult_capacity }} NL / {{ $category->child_capacity }}
                                                                TE</td>
                                                            <td>{{ number_format($category->price, 0, ',', '.') }}đ</td>
                                                            <td>
                                                                @if ($category->available_rooms_count > 0)
                                                                    <span
                                                                        class="badge-clean status-done">{{ $category->available_rooms_count }}
                                                                        phòng</span>
                                                                @else
                                                                    <span class="badge-clean status-cancelled">Hết</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </details>

                                @php
                                    $roomOperationPreview = session('booking_room_operation_preview');
                                @endphp

                                @if ($roomOperationPreview)
                                    <div class="alert alert-info border-primary mb-3" id="room-operation-preview">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                            <div>
                                                <h5 class="mb-1">{{ $roomOperationPreview['title'] ?? 'Xem trước thay đổi phòng' }}</h5>
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
                                                        <td class="text-end">{{ $roomOperationPreview['before']['room_quantity'] ?? 0 }}</td>
                                                        <td class="text-end fw-bold">{{ $roomOperationPreview['after']['room_quantity'] ?? 0 }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tiền phòng</td>
                                                        <td class="text-end">{{ number_format($roomOperationPreview['before']['room_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                        <td class="text-end fw-bold">{{ number_format($roomOperationPreview['after']['room_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Dịch vụ / phụ thu đã xác nhận</td>
                                                        <td class="text-end">{{ number_format($roomOperationPreview['before']['service_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                        <td class="text-end fw-bold">{{ number_format($roomOperationPreview['after']['service_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Minibar / hư hại đã duyệt</td>
                                                        <td class="text-end">{{ number_format($roomOperationPreview['before']['inspection_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                        <td class="text-end fw-bold">{{ number_format($roomOperationPreview['after']['inspection_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mã giảm giá / hỗ trợ</td>
                                                        <td class="text-end text-success">-{{ number_format($roomOperationPreview['before']['discount_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                        <td class="text-end text-success fw-bold">-{{ number_format($roomOperationPreview['after']['discount_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr class="table-primary">
                                                        <td class="fw-bold">Tổng cần thanh toán</td>
                                                        <td class="text-end fw-bold">{{ number_format($roomOperationPreview['before']['total'] ?? 0, 0, ',', '.') }}đ</td>
                                                        <td class="text-end fw-bold">{{ number_format($roomOperationPreview['after']['total'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Khách đã thanh toán</td>
                                                        <td colspan="2" class="text-end fw-bold">{{ number_format($roomOperationPreview['after']['paid_total'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mức cọc yêu cầu</td>
                                                        <td class="text-end">{{ number_format($roomOperationPreview['before']['required_deposit'] ?? 0, 0, ',', '.') }}đ</td>
                                                        <td class="text-end fw-bold">{{ number_format($roomOperationPreview['after']['required_deposit'] ?? 0, 0, ',', '.') }}đ</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Còn thiếu để đủ cọc</td>
                                                        <td colspan="2" class="text-end {{ ($roomOperationPreview['after']['deposit_shortfall'] ?? 0) > 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                                            {{ number_format($roomOperationPreview['after']['deposit_shortfall'] ?? 0, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Còn phải thu toàn bộ</td>
                                                        <td colspan="2" class="text-end text-danger fw-bold">
                                                            {{ number_format($roomOperationPreview['after']['remaining'] ?? 0, 0, ',', '.') }}đ
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        @if (!empty($roomOperationPreview['promotion_changes']))
                                            <div class="bg-white border rounded p-3 mb-3">
                                                <strong class="d-block mb-2">Mã ưu đãi sau thay đổi</strong>
                                                @foreach ($roomOperationPreview['promotion_changes'] as $promotionChange)
                                                    <div class="small mb-1">
                                                        <strong>{{ $promotionChange['code'] ?? '---' }}</strong>
                                                        @if (($promotionChange['scope'] ?? 'booking') === 'room')
                                                            · Phòng {{ $promotionChange['room_number'] ?? '---' }}
                                                        @else
                                                            · Toàn bộ đơn
                                                        @endif
                                                        —
                                                        @switch($promotionChange['status'] ?? '')
                                                            @case('removed')
                                                                <span class="text-danger">Bị gỡ</span>
                                                                @break
                                                            @case('recalculated')
                                                                <span class="text-warning">
                                                                    Tính lại {{ number_format($promotionChange['old_discount'] ?? 0, 0, ',', '.') }}đ
                                                                    → {{ number_format($promotionChange['new_discount'] ?? 0, 0, ',', '.') }}đ
                                                                </span>
                                                                @break
                                                            @case('added')
                                                                <span class="text-success">Được thêm</span>
                                                                @break
                                                            @default
                                                                <span class="text-success">Giữ nguyên</span>
                                                        @endswitch
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if (!empty($roomOperationPreview['service_changes']))
                                            <div class="bg-white border rounded p-3 mb-3">
                                                <strong class="d-block mb-2">Dịch vụ thay đổi</strong>
                                                @foreach ($roomOperationPreview['service_changes'] as $serviceChange)
                                                    <div class="small mb-1">
                                                        <strong>{{ $serviceChange['name'] ?? 'Dịch vụ' }}</strong>
                                                        @if (($serviceChange['scope'] ?? 'booking') === 'room')
                                                            · Phòng {{ $serviceChange['room_number'] ?? '---' }}
                                                        @else
                                                            · Toàn bộ đơn
                                                        @endif
                                                        —
                                                        {{ number_format($serviceChange['old_total'] ?? 0, 0, ',', '.') }}đ
                                                        → {{ number_format($serviceChange['new_total'] ?? 0, 0, ',', '.') }}đ
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="small mb-3">{{ $roomOperationPreview['message'] ?? '' }}</div>

                                        <form method="POST" action="{{ $roomOperationPreview['action_url'] }}" onsubmit="return confirm('Xác nhận lưu thay đổi phòng và cập nhật toàn bộ tiền/mã/dịch vụ?')">
                                            @csrf
                                            @if (($roomOperationPreview['http_method'] ?? 'PATCH') !== 'POST')
                                                @method($roomOperationPreview['http_method'] ?? 'PATCH')
                                            @endif
                                            <input type="hidden" name="confirm_operation" value="1">
                                            <input type="hidden" name="operation_token" value="{{ $roomOperationPreview['token'] ?? '' }}">
                                            @foreach (($roomOperationPreview['payload'] ?? []) as $key => $value)
                                                @if (is_bool($value))
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value ? 1 : 0 }}">
                                                @elseif (is_array($value))
                                                    @foreach ($value as $arrayValue)
                                                        @if (is_scalar($arrayValue) || is_null($arrayValue))
                                                            <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                                                        @endif
                                                    @endforeach
                                                @elseif (is_scalar($value) || is_null($value))
                                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                @endif
                                            @endforeach
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button type="submit" class="btn btn-primary">Xác nhận cập nhật</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('admin.bookings.room-operation.discard-preview', $booking) }}" class="mt-2">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary">Đóng / hủy bản xem trước</button>
                                        </form>
                                    </div>
                                @endif

                                <div class="form-mini-grid">
                                    <form action="{{ route('admin.bookings.add-room-to-booking', $booking->id) }}" method="POST"
                                        class="mini-form-box">
                                        @csrf
                                        @method('PATCH')

                                        <h6>Thêm phòng</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng phòng</label>
                                            <select name="additional_room_category_id" class="form-select" required>
                                                <option value="">-- Chọn hạng --</option>
                                                @foreach ($roomCategoriesForBookingManage as $category)
                                                    <option value="{{ $category->id }}" @disabled($category->available_rooms_count <= 0)>
                                                        {{ $category->name }} - Còn {{ $category->available_rooms_count }} -
                                                        {{ number_format($category->price, 0, ',', '.') }}đ
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Số phòng thêm</label>
                                            <input type="number" name="additional_room_quantity" class="form-control" value="1"
                                                min="1" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small d-block">Cách chọn phòng</label>
                                            <div class="d-flex gap-3 flex-wrap">
                                                <label class="form-check mb-0">
                                                    <input class="form-check-input js-add-room-assignment-mode" type="radio"
                                                        name="room_assignment_mode" value="auto" checked>
                                                    <span class="form-check-label">Hệ thống tự chọn</span>
                                                </label>
                                                <label class="form-check mb-0">
                                                    <input class="form-check-input js-add-room-assignment-mode" type="radio"
                                                        name="room_assignment_mode" value="manual">
                                                    <span class="form-check-label">Lễ tân chọn thủ công</span>
                                                </label>
                                            </div>
                                            <div class="form-text">Hệ thống ưu tiên phòng Sẵn sàng; chỉ khi không đủ mới lấy phòng Đang dọn.</div>
                                        </div>

                                        <div class="mb-3 d-none js-add-room-manual-wrap">
                                            <label class="form-label small">Chọn phòng cụ thể</label>
                                            <div class="border rounded-3 bg-white p-2 js-add-room-manual-list" style="max-height:260px;overflow:auto">
                                                @foreach ($roomCandidatesForBookingManage as $candidateRoom)
                                                    <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-2 js-add-room-option"
                                                        data-category-id="{{ $candidateRoom->room_category_id }}">
                                                        <input type="checkbox" name="target_room_ids[]" value="{{ $candidateRoom->id }}"
                                                            class="form-check-input mt-0 js-add-room-manual-check"
                                                            data-category-id="{{ $candidateRoom->room_category_id }}"
                                                            data-status="{{ $candidateRoom->status }}">
                                                        <span>
                                                            <strong>Phòng {{ $candidateRoom->room_number }}</strong>
                                                            <span class="text-muted">· Tầng {{ $candidateRoom->floor_number }} · {{ $candidateRoom->status === 'available' ? 'Sẵn sàng' : 'Đang dọn - sẽ yêu cầu dọn nhanh' }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="form-text js-add-room-manual-help">Chọn đúng số phòng cần thêm.</div>
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

                                    <form action="{{ route('admin.bookings.change-one-room-category', $booking->id) }}"
                                        method="POST" class="mini-form-box js-category-room-form" data-room-count="1">
                                        @csrf
                                        @method('PATCH')

                                        <h6>{{ $booking->bookingRooms->count() <= 1 ? 'Đổi hạng phòng' : 'Đổi 1 phòng' }}</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Phòng cần đổi</label>
                                            <select name="booking_room_id" class="form-select" required>
                                                <option value="">-- Chọn phòng --</option>
                                                @foreach ($booking->bookingRooms as $bookingRoom)
                                                    @if ($bookingRoom->room)
                                                        <option value="{{ $bookingRoom->id }}">
                                                            Phòng {{ $bookingRoom->room->room_number }} · {{ $bookingRoom->room->category->name ?? 'Không rõ hạng' }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng mới</label>
                                            <select name="new_room_category_id" class="form-select js-room-category-target" required>
                                                <option value="">-- Chọn hạng --</option>
                                                @foreach ($roomCategoriesForBookingManage as $category)
                                                    <option value="{{ $category->id }}" @disabled($category->available_rooms_count <= 0)>
                                                        {{ $category->name }} · Còn {{ $category->available_rooms_count }} · {{ number_format($category->price, 0, ',', '.') }}đ
                                                    </option>
                                                @endforeach
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
                                            <label class="form-label small">Chọn đúng 1 phòng</label>
                                            <div class="border rounded-3 bg-white p-2 js-manual-room-checklist" style="max-height:260px;overflow:auto">
                                                @foreach ($categoryChangeAvailableRooms as $room)
                                                    <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-2 js-manual-room-option"
                                                        data-category-id="{{ $room->room_category_id }}">
                                                        <input type="checkbox" name="target_room_ids[]" value="{{ $room->id }}"
                                                            class="form-check-input mt-0 js-manual-room-check" data-category-id="{{ $room->room_category_id }}" disabled>
                                                        <span><strong>Phòng {{ $room->room_number }}</strong> <span class="text-muted">· Tầng {{ $room->floor_number ?? '---' }}</span></span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="form-text js-manual-room-selection-status">Đã chọn 0/1 phòng.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="change_category_reason" class="form-control"
                                                placeholder="Ví dụ: Khách muốn nâng hạng 1 phòng">
                                        </div>

                                        <button type="submit" class="btn btn-outline-warning w-100">Xem trước đổi 1 phòng</button>
                                    </form>

                                    @if ($booking->bookingRooms->count() >= 2)
                                    <form action="{{ route('admin.bookings.change-all-room-category', $booking->id) }}"
                                        method="POST" class="mini-form-box js-category-room-form" data-room-count="{{ $assignedRooms->count() }}">
                                        @csrf
                                        @method('PATCH')

                                        <h6>Đổi toàn bộ</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng mới</label>
                                            <select name="new_room_category_id" class="form-select js-room-category-target" required>
                                                <option value="">-- Chọn hạng --</option>
                                                @foreach ($roomCategoriesForBookingManage as $category)
                                                    <option value="{{ $category->id }}" @disabled($category->available_rooms_count < $assignedRooms->count())>
                                                        {{ $category->name }} · Còn {{ $category->available_rooms_count }} · Cần {{ $assignedRooms->count() }}
                                                    </option>
                                                @endforeach
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
                                            <label class="form-label small">Chọn đúng {{ $assignedRooms->count() }} phòng</label>
                                            <div class="border rounded-3 bg-white p-2 js-manual-room-checklist" style="max-height:260px;overflow:auto">
                                                @foreach ($categoryChangeAvailableRooms as $room)
                                                    <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-2 js-manual-room-option"
                                                        data-category-id="{{ $room->room_category_id }}">
                                                        <input type="checkbox" name="target_room_ids[]" value="{{ $room->id }}"
                                                            class="form-check-input mt-0 js-manual-room-check" data-category-id="{{ $room->room_category_id }}" disabled>
                                                        <span><strong>Phòng {{ $room->room_number }}</strong> <span class="text-muted">· Tầng {{ $room->floor_number ?? '---' }}</span></span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="form-text js-manual-room-selection-status">Đã chọn 0/{{ $assignedRooms->count() }} phòng.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="change_category_reason" class="form-control"
                                                placeholder="Ví dụ: Khách muốn đổi toàn bộ hạng phòng">
                                        </div>

                                        <button type="submit" class="btn btn-outline-danger w-100">Xem trước đổi toàn bộ</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </details>
                    @endif

                    @if (($booking->room_selection_mode ?? 'automatic') === 'manual')
                        @php
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
                        @endphp
                        <details class="card-clean border border-warning-subtle" id="manualRoomSelectionPanel" {{ $manualSelectionFinal ? '' : 'open' }}>
                            <summary class="card-title-clean mb-0" style="cursor:pointer;list-style:none">
                                <div>
                                    <h5>Yêu cầu chọn phòng của khách</h5>
                                </div>
                                <span class="badge-clean {{ in_array($booking->room_selection_status, ['fulfilled', 'fallback_accepted'], true) ? 'status-success' : ($booking->room_selection_status === 'fallback_declined' ? 'status-muted' : 'status-warning') }}">
                                    {{ $roomSelectionStatusLabels[$booking->room_selection_status] ?? $booking->room_selection_status }}
                                </span>
                            </summary>

                            <div class="mt-3">
                                <div class="room-selection-request-highlight mb-3">
                                    <strong>Yêu cầu của khách:</strong>
                                    <span>{{ $booking->room_selection_request ?: 'Khách chưa ghi yêu cầu cụ thể.' }}</span>
                                </div>
                                @if ($booking->room_selection_status === 'pending' && in_array($booking->status, ['pending', 'confirmed'], true))
                                    <div class="alert alert-warning small">
                                        Hệ thống đang giữ {{ $booking->room_quantity }} phòng dự phòng để tránh oversell. <strong>Không công bố số phòng dự phòng cho khách ở trạng thái này.</strong> Chỉ khi chọn <strong>Đáp ứng yêu cầu</strong> hệ thống mới cộng phí đảm bảo yêu cầu phòng.
                                    </div>

                                    <form action="{{ route('admin.bookings.manual-room-selection', $booking) }}" method="POST" class="mb-3">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="fulfilled">

                                        <label class="form-label fw-semibold">Chọn đúng {{ $booking->room_quantity }} phòng đáp ứng yêu cầu</label>
                                        <div class="border rounded-3 bg-white p-2 js-fixed-room-checklist" data-required-count="{{ $booking->room_quantity }}" style="max-height:300px;overflow:auto">
                                            @foreach ($manualSelectionRooms as $room)
                                                <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-2">
                                                    <input type="checkbox" name="selected_room_ids[]" value="{{ $room->id }}"
                                                        class="form-check-input mt-0 js-fixed-room-check"
                                                        @checked($assignedRooms->contains('id', $room->id))>
                                                    <span>
                                                        <strong>Phòng {{ $room->room_number }}</strong>
                                                        <span class="text-muted">· Tầng {{ $room->floor_number ?? '---' }}{{ $assignedRooms->contains('id', $room->id) ? ' · đang giữ dự phòng' : '' }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="form-text js-fixed-room-status">Đã chọn {{ $assignedRooms->count() }}/{{ $booking->room_quantity }} phòng. Có thể dùng chính phòng dự phòng nếu phòng đó thực sự đáp ứng yêu cầu khách đã ghi.</div>

                                        <label class="form-label mt-2">Ghi chú gửi khách (không bắt buộc)</label>
                                        <textarea name="handling_note" class="form-control" rows="2" placeholder="Ví dụ: Đã bố trí phòng tầng 6, khu vực yên tĩnh."></textarea>

                                        <button class="btn btn-success w-100 mt-3">
                                            Xác nhận đáp ứng yêu cầu và tính phí
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.bookings.manual-room-selection', $booking) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="unfulfilled">
                                        <label class="form-label fw-semibold">Nếu không thể đáp ứng</label>
                                        <textarea name="handling_note" class="form-control" rows="2" required
                                            placeholder="Ghi rõ lý do để khách quyết định có nhận phòng dự phòng hay không."></textarea>
                                        <button class="btn btn-outline-danger w-100 mt-2">
                                            Không thể đáp ứng · hỏi khách về phòng dự phòng
                                        </button>
                                    </form>
                                @elseif ($booking->room_selection_status === 'awaiting_guest')
                                    <div class="alert alert-warning small mb-2">
                                        <strong>Đang chờ khách quyết định.</strong> Không thu phí đảm bảo yêu cầu phòng. Khách đã được thông báo số phòng dự phòng và có thể Đồng ý hoặc Từ chối/hủy đơn hoàn cọc.
                                        @if ($booking->room_selection_handling_note)
                                            <br><strong>Lý do đã gửi khách:</strong> {{ $booking->room_selection_handling_note }}
                                        @endif
                                    </div>
                                    <div class="room-pill-list">
                                        @foreach ($assignedRooms as $assignedRoom)
                                            <span class="room-pill">Phòng dự phòng {{ $assignedRoom->room_number }}</span>
                                        @endforeach
                                    </div>
                                @elseif ($booking->room_selection_status === 'fulfilled')
                                    <div class="soft-note">
                                        <strong>Đã đáp ứng.</strong>
                                        Phí đảm bảo yêu cầu phòng: {{ number_format((float) $booking->room_selection_fee, 0, ',', '.') }}đ.
                                        @if ($booking->room_selection_handling_note)
                                            <br>Ghi chú: {{ $booking->room_selection_handling_note }}
                                        @endif
                                    </div>
                                @elseif ($booking->room_selection_status === 'fallback_accepted')
                                    <div class="soft-note">
                                        <strong>Khách đã đồng ý sử dụng phòng dự phòng.</strong> Booking tiếp tục giữ nguyên, không thu phí đảm bảo yêu cầu phòng.
                                    </div>
                                @elseif ($booking->room_selection_status === 'fallback_declined')
                                    <div class="soft-note mb-3">
                                        <strong>Khách từ chối phòng dự phòng.</strong> Booking đã hủy do khách sạn không đáp ứng yêu cầu; không tính đây là lỗi hủy của khách.
                                    </div>
                                    @if ((float) ($booking->refund_due_amount ?? 0) > 0)
                                        <div class="alert {{ $booking->refund_status === 'completed' ? 'alert-success' : 'alert-danger' }} small">
                                            Cần hoàn khách: <strong>{{ number_format((float) $booking->refund_due_amount, 0, ',', '.') }}đ</strong> ·
                                            {{ $booking->refund_status === 'completed' ? 'Đã xác nhận hoàn tất' : 'Đang chờ hoàn tiền' }}.
                                            @if ($booking->refund_status === 'completed' && $booking->refund_processed_at)
                                                <br>Hoàn tất lúc {{ $booking->refund_processed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}.
                                                @if ($booking->refund_processed_note)
                                                    <br>Ghi chú đối soát: {{ $booking->refund_processed_note }}
                                                @endif
                                            @endif
                                        </div>
                                        @if ($booking->refund_status === 'pending')
                                            @if ($canConfirmRefund)
                                                <form action="{{ route('admin.bookings.refund-completed', $booking) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="form-label small fw-semibold">Xác nhận sau khi đã thực sự hoàn tiền cho khách</label>
                                                    <textarea name="refund_note" class="form-control" rows="2" placeholder="Ví dụ: Đã hoàn qua cổng VNPay / hoàn tiền mặt tại quầy..."></textarea>
                                                    <button class="btn btn-success w-100 mt-2" onclick="return confirm('Chỉ xác nhận khi tiền đã thực sự được hoàn cho khách. Tiếp tục?');">Xác nhận đã hoàn tiền</button>
                                                </form>
                                            @else
                                                <div class="small text-muted">Khoản hoàn này đang chờ Quản lý/Super Admin xác nhận sau khi đối soát tiền thực tế.</div>
                                            @endif
                                        @endif
                                    @else
                                        <div class="alert alert-info small mb-0">Booking chưa phát sinh khoản thanh toán cần hoàn.</div>
                                    @endif
                                @elseif ($booking->room_selection_status === 'unfulfilled')
                                    <div class="soft-note">
                                        <strong>Dữ liệu cũ:</strong> Booking được ghi nhận không thể đáp ứng yêu cầu theo luồng cũ. Hãy kiểm tra trực tiếp với khách nếu booking vẫn còn hiệu lực.
                                    </div>
                                @endif
                            </div>
                        </details>
                    @endif

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <div>
                                <h5>Phòng đang gán</h5>
                            </div>
                        </div>

                        @if ($assignedRooms->count() > 0)
                            <div class="room-pill-list mb-3">
                                @foreach ($assignedRooms as $assignedRoom)
                                    <a href="{{ route('admin.rooms.show', $assignedRoom->id) }}"
                                        class="room-pill text-decoration-none">
                                        <span>Phòng {{ $assignedRoom->room_number }}</span>
                                        <span class="text-muted">· Tầng {{ $assignedRoom->floor_number ?? '---' }}</span>
                                        <span
                                            class="badge-clean status-muted">{{ $roomStatusLabels[$assignedRoom->status] ?? $assignedRoom->status }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning mb-3">Đơn này chưa được gán phòng.</div>
                        @endif

                        @if (in_array($booking->status, ['pending', 'confirmed', 'checked_in']))
                            <details class="compact-panel">
                                <summary>Đổi phòng cùng hạng</summary>
                                <div class="compact-panel-body">
                                    <form action="{{ route('admin.bookings.change-room', $booking->id) }}" method="POST" class="js-same-rank-room-form">
                                        @csrf

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Phòng cần đổi</label>
                                                <select name="old_room_id" class="form-select js-old-room-select" required>
                                                    <option value="">-- Chọn phòng đang gán --</option>
                                                    @foreach ($assignedRooms as $assignedRoom)
                                                        <option value="{{ $assignedRoom->id }}" data-category-id="{{ $assignedRoom->room_category_id }}">
                                                            Phòng {{ $assignedRoom->room_number }} · Tầng {{ $assignedRoom->floor_number ?? '---' }}
                                                        </option>
                                                    @endforeach
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
                                                <label class="form-label">Chọn đúng 1 phòng thay thế</label>
                                                <div class="border rounded-3 bg-white p-2 js-same-rank-room-checklist" style="max-height:260px;overflow:auto">
                                                    @foreach ($timeAvailableRooms as $room)
                                                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-2 js-same-rank-room-option"
                                                            data-category-id="{{ $room->room_category_id }}">
                                                            <input type="checkbox" name="new_room_id" value="{{ $room->id }}"
                                                                class="form-check-input mt-0 js-same-rank-room-check" data-category-id="{{ $room->room_category_id }}" disabled>
                                                            <span><strong>Phòng {{ $room->room_number }}</strong> <span class="text-muted">· Tầng {{ $room->floor_number ?? '---' }}</span></span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <div class="form-text js-same-rank-room-status">Đã chọn 0/1 phòng.</div>
                                            </div>

                                            @if ($booking->status === 'checked_in')
                                                <div class="col-md-6">
                                                    <label class="form-label">Phòng cũ sau khi chuyển</label>
                                                    <select name="old_room_new_status" class="form-select" required>
                                                        <option value="cleaning">Cần dọn</option>
                                                        <option value="maintenance">Bảo trì</option>
                                                    </select>
                                                </div>
                                            @else
                                                <input type="hidden" name="old_room_new_status" value="available">
                                            @endif

                                            <div class="col-md-6">
                                                <label class="form-label">Lý do đổi phòng</label>
                                                <input type="text" name="change_reason" class="form-control"
                                                    placeholder="Ví dụ: Khách muốn chuyển sang phòng khác" required>
                                            </div>
                                        </div>

                                        @if ($timeAvailableRooms->count() == 0)
                                            <div class="alert alert-warning small mt-3 mb-0">
                                                Không còn phòng cùng hạng phù hợp trong khoảng thời gian booking.
                                            </div>
                                        @endif

                                        <button type="submit" class="btn btn-outline-warning w-100 mt-3">
                                            Xem trước đổi phòng
                                        </button>
                                    </form>
                                </div>
                            </details>
                        @endif
                    </section>

                    <section class="card-clean">
                        <details class="compact-panel booking-service-overview" @if($errors->has('services.*')) open @endif>
                            <summary>
                                <span>Dịch vụ / phụ thu</span>
                                <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                    <span class="badge-clean status-muted">{{ $booking->serviceItems->count() }} khoản</span>
                                    <span class="badge-clean {{ $serviceItemTotal > 0 ? 'status-warning' : 'status-muted' }}">{{ number_format((float) $serviceItemTotal, 0, ',', '.') }}đ</span>
                                </span>
                            </summary>
                            <div class="compact-panel-body">

                        @php
                            $serviceCatalogTypes = \App\Models\Service::serviceCatalogTypes();
                            $surchargeCatalogTypes = \App\Models\Service::surchargeCatalogTypes();
                            $serviceCatalogItems = $booking->serviceItems->filter(fn ($item) => in_array($item->type, $serviceCatalogTypes, true));
                            $surchargeItems = $booking->serviceItems->filter(fn ($item) => in_array($item->type, $surchargeCatalogTypes, true));
                            $legacyServiceItems = $booking->serviceItems->reject(fn ($item) => in_array($item->type, array_merge($serviceCatalogTypes, $surchargeCatalogTypes), true));
                            $serviceCatalogTotal = (float) $serviceCatalogItems->where('billing_status', 'confirmed')->sum('total');
                            $surchargeItemsTotal = (float) $surchargeItems->where('billing_status', 'confirmed')->sum('total');
                            $legacyServiceItemsTotal = (float) $legacyServiceItems->where('billing_status', 'confirmed')->sum('total');
                        @endphp

                        <details class="compact-panel mb-3">
                            <summary>
                                <span>Dịch vụ / minibar</span>
                                <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                    <span class="badge-clean status-muted">{{ $serviceCatalogItems->count() }} khoản</span>
                                    <span class="badge-clean {{ $serviceCatalogTotal > 0 ? 'status-warning' : 'status-muted' }}">{{ number_format($serviceCatalogTotal, 0, ',', '.') }}đ</span>
                                </span>
                            </summary>
                            <div class="compact-panel-body">
                                @include('admin.pages.bookings.partials.service-item-table', [
                                    'items' => $serviceCatalogItems,
                                    'booking' => $booking,
                                    'canEditServiceItems' => $canEditServiceItems,
                                    'emptyText' => 'Chưa có dịch vụ/minibar khách mua hoặc gọi thêm.',
                                ])
                            </div>
                        </details>

                        <details class="compact-panel mb-3">
                            <summary>
                                <span>Phụ thu / phí phát sinh</span>
                                <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                    <span class="badge-clean status-muted">{{ $surchargeItems->count() }} khoản</span>
                                    <span class="badge-clean {{ $surchargeItemsTotal > 0 ? 'status-warning' : 'status-muted' }}">{{ number_format($surchargeItemsTotal, 0, ',', '.') }}đ</span>
                                </span>
                            </summary>
                            <div class="compact-panel-body">
                                @include('admin.pages.bookings.partials.service-item-table', [
                                    'items' => $surchargeItems,
                                    'booking' => $booking,
                                    'canEditServiceItems' => $canEditServiceItems,
                                    'emptyText' => 'Chưa có phụ thu/phí phát sinh ngoài kiểm phòng.',
                                ])
                            </div>
                        </details>

                        @if ($legacyServiceItems->isNotEmpty())
                            <details class="compact-panel mb-3">
                                <summary>
                                    <span>Khoản lịch sử khác</span>
                                    <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                        <span class="badge-clean status-muted">{{ $legacyServiceItems->count() }} khoản</span>
                                        <span class="badge-clean {{ $legacyServiceItemsTotal > 0 ? 'status-warning' : 'status-muted' }}">{{ number_format($legacyServiceItemsTotal, 0, ',', '.') }}đ</span>
                                    </span>
                                </summary>
                                <div class="compact-panel-body">
                                    <div class="soft-note mb-2">Các dòng kiểu cũ được giữ nguyên để không mất lịch sử booking.</div>
                                    @include('admin.pages.bookings.partials.service-item-table', [
                                        'items' => $legacyServiceItems,
                                        'booking' => $booking,
                                        'canEditServiceItems' => $canEditServiceItems,
                                    ])
                                </div>
                            </details>
                        @endif

                        @if ($approvedInspectionItems->count() > 0)
                            <details class="compact-panel mb-3">
                                <summary>
                                    <span>Khoản kiểm phòng đã duyệt</span>
                                    <span class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                        <span class="badge-clean status-muted">{{ $approvedInspectionItems->count() }} khoản</span>
                                        <span class="badge-clean status-warning">{{ number_format((float) $approvedInspectionItems->sum('total'), 0, ',', '.') }}đ</span>
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
                                                @foreach ($approvedInspectionItems as $inspectionItem)
                                                    <tr>
                                                        <td>{{ $inspectionItem->type == 'minibar' ? 'Dịch vụ tại phòng' : 'Hư hại' }}</td>
                                                        <td>{{ $inspectionItem->name }}</td>
                                                        <td>{{ number_format((float) $inspectionItem->price, 0, ',', '.') }}đ</td>
                                                        <td>{{ $inspectionItem->quantity }}</td>
                                                        <td class="fw-bold text-danger">
                                                            {{ number_format((float) $inspectionItem->total, 0, ',', '.') }}đ
                                                        </td>
                                                        <td>{{ $inspectionItem->admin_note ?: '---' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </details>
                        @endif

                        @if ($canEditServiceItems)
                            <details class="compact-panel" @if($errors->has('services.*')) open @endif>
                                <summary>Thêm dịch vụ / minibar gọi thêm / xe cộ</summary>
                                <div class="compact-panel-body">
                                    <form action="{{ route('admin.bookings.service-items.store', $booking->id) }}" method="POST"
                                        id="multiServiceForm">
                                        @csrf

                                        <div id="serviceRows">
                                            <div class="service-input-row border rounded p-3 mb-3 bg-light">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Dịch vụ</label>
                                                        <select name="services[0][service_id]"
                                                            class="form-select service-item-select" required>
                                                            <option value="">-- Chọn dịch vụ --</option>
                                                            @foreach ($availableServices as $service)
                                                                <option value="{{ $service->id }}"
                                                                    data-price="{{ $service->price }}"
                                                                    data-unit="{{ $service->unit }}"
                                                                    data-group="{{ $service->service_group ?? 'general' }}"
                                                                    data-billing-rule="{{ $service->billing_rule ?? 'once' }}">
                                                                    {{ $service->name }} -
                                                                    {{ $service->type === 'minibar_order' ? 'Minibar gọi thêm' : ($service->group_label ?? 'Dịch vụ') }} -
                                                                    {{ number_format($service->price, 0, ',', '.') }}đ /
                                                                    {{ $service->unit }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="form-label small">Áp dụng cho</label>
                                                        <input type="hidden" name="services[0][scope]" class="service-scope-input" value="booking">
                                                        <select name="services[0][booking_room_id]" class="form-select service-room-select">
                                                            <option value="" data-room-count="{{ max(1, $booking->bookingRooms->count()) }}" data-guest-count="{{ max(1, (int) $booking->adult_count + (int) $booking->child_count) }}">Toàn bộ đơn</option>
                                                            @foreach ($booking->bookingRooms as $serviceBookingRoom)
                                                                @php
                                                                    $serviceRoomGuestCount = max(1, (int) $serviceBookingRoom->adult_count + (int) $serviceBookingRoom->child_count);
                                                                @endphp
                                                                <option value="{{ $serviceBookingRoom->id }}" data-room-count="1" data-guest-count="{{ $serviceRoomGuestCount }}">
                                                                    Phòng {{ $serviceBookingRoom->room?->room_number ?? '---' }} · {{ $serviceBookingRoom->room?->category?->name ?? '---' }}
                                                                </option>
                                                            @endforeach
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
                        @endif
                            </div>
                        </details>
                    </section>

                    <section class="card-clean">
                        <details class="history-details">
                            <summary>
                                Lịch sử thao tác
                                <span class="text-muted fw-normal ms-1">({{ $booking->logs->count() }})</span>
                            </summary>

                            <div class="log-box">
                                @forelse ($booking->logs as $log)
                                    <div class="log-item">
                                        <div class="fw-bold">
                                            {{ $log->created_at ? $log->created_at->format('d/m/Y - H:i') : '---' }}
                                            - {{ $log->user?->name ?? 'Hệ thống' }}
                                        </div>
                                        <div class="text-muted mt-1" style="white-space: pre-line;">
                                            {{ $log->description }}
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Chưa có lịch sử thao tác.</p>
                                @endforelse
                            </div>
                        </details>
                    </section>
                </div>

                <aside class="side-stack">
                    <section class="card-clean" id="bookingPaymentPanel">
                        <div class="card-title-clean">
                            <h5>Thanh toán</h5>
                        </div>

                        <div class="payment-summary-note">Các số cần nhìn ngay; bấm vào ô để xem công thức.</div>

                        <div class="payment-kpi-grid">
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailFinalTotal" data-payment-title="Tổng cần thanh toán">
                                <span class="payment-kpi-label">Tổng đơn</span>
                                <span class="payment-kpi-value">{{ number_format($finalTotal, 0, ',', '.') }}đ</span>
                            </button>
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailPayments" data-payment-title="Lịch sử tiền khách đã thanh toán">
                                <span class="payment-kpi-label">Đã thu</span>
                                <span class="payment-kpi-value text-success">{{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ</span>
                            </button>
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailRemaining" data-payment-title="Số tiền còn phải thu">
                                <span class="payment-kpi-label">Còn phải thu</span>
                                <span class="payment-kpi-value {{ $remainingTotal > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($remainingTotal, 0, ',', '.') }}đ</span>
                            </button>
                            <button type="button" class="payment-kpi payment-detail-trigger" data-payment-detail="paymentDetailDeposit" data-payment-title="Mức cọc {{ $depositPercentLabel }} hiện tại">
                                <span class="payment-kpi-label">Còn thiếu cọc</span>
                                <span class="payment-kpi-value {{ $adminPaymentDepositAmount > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ</span>
                            </button>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2 px-1">
                            <span class="small text-muted">Trạng thái thanh toán</span>
                            <span class="badge-clean {{ $paymentStatusClass }}">
                                {{ $paymentStatusLabels[$effectivePaymentStatus] ?? $effectivePaymentStatus }}
                            </span>
                        </div>

                        @if ((float) ($booking->refund_due_amount ?? 0) > 0)
                            <div class="alert {{ $booking->refund_status === 'completed' ? 'alert-success' : 'alert-warning' }} small mt-2 mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <span>{{ $booking->refund_status === 'completed' ? 'Đã hoàn khách' : 'Đang chờ hoàn khách' }}</span>
                                    <strong>{{ number_format((float) $booking->refund_due_amount, 0, ',', '.') }}đ</strong>
                                </div>
                                @if ($booking->refund_status === 'completed' && $booking->refund_processed_at)
                                    <div class="text-muted mt-1">Hoàn tất lúc {{ $booking->refund_processed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}.</div>
                                    @if ($booking->refund_processed_note)
                                        <div class="text-muted mt-1">Ghi chú đối soát: {{ $booking->refund_processed_note }}</div>
                                    @endif
                                @elseif ($booking->refund_status === 'pending' && in_array($booking->status, ['cancelled', 'canceled'], true))
                                    @if ($canConfirmRefund)
                                        <form action="{{ route('admin.bookings.refund-completed', $booking) }}" method="POST" class="mt-2">
                                            @csrf
                                            @method('PATCH')
                                            <textarea name="refund_note" class="form-control form-control-sm" rows="2" placeholder="Kênh hoàn tiền / mã giao dịch / ghi chú đối soát..."></textarea>
                                            <button type="submit" class="btn btn-sm btn-success w-100 mt-2"
                                                onclick="return confirm('Chỉ xác nhận khi tiền đã thực sự được hoàn cho khách. Tiếp tục?');">
                                                Xác nhận đã hoàn tiền
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-muted mt-1">Chờ Quản lý/Super Admin đối soát và xác nhận hoàn tiền.</div>
                                    @endif
                                @endif
                            </div>
                        @endif

                        <details class="payment-components-details">
                            <summary>Xem chi tiết cấu thành và phân bổ tiền</summary>
                            <div class="info-list">
                                <div class="payment-summary-section">Các khoản phát sinh</div>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailRoom" data-payment-title="Chi tiết tiền phòng">
                                    <span class="info-label">Tiền phòng</span>
                                    <span class="info-value">{{ number_format($roomTotal, 0, ',', '.') }}đ</span>
                                </button>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailServices" data-payment-title="Dịch vụ khách gọi thêm và phụ thu">
                                    <span class="info-label">Dịch vụ và phụ thu đã xác nhận</span>
                                    <span class="info-value {{ $serviceItemTotal > 0 ? 'text-danger' : '' }}">{{ $serviceItemTotal > 0 ? '+' : '' }}{{ number_format((float) $serviceItemTotal, 0, ',', '.') }}đ</span>
                                </button>
                                @if ($confirmedServiceItemsForBreakdown->isNotEmpty())
                                    <div class="small text-muted px-2 pb-2">
                                        @foreach ($confirmedServiceItemsForBreakdown as $item)
                                            <div class="d-flex justify-content-between gap-2 mt-1">
                                                <span>{{ $item->name }}</span>
                                                <strong class="text-danger">+{{ number_format((float) $item->total, 0, ',', '.') }}đ</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($manualRoomSelectionFee > 0)
                                    <div class="info-line">
                                        <span class="info-label">Phí chọn phòng thủ công</span>
                                        <span class="info-value text-danger">+{{ number_format($manualRoomSelectionFee, 0, ',', '.') }}đ</span>
                                    </div>
                                @endif
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailMinibar" data-payment-title="Dịch vụ tại phòng đã duyệt">
                                    <span class="info-label">Dịch vụ tại phòng</span>
                                    <span class="info-value {{ $approvedMinibarTotal > 0 ? 'text-danger' : '' }}">{{ $approvedMinibarTotal > 0 ? '+' : '' }}{{ number_format((float) $approvedMinibarTotal, 0, ',', '.') }}đ</span>
                                </button>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailDamage" data-payment-title="Phí hư hại đã duyệt">
                                    <span class="info-label">Hư hại đã duyệt</span>
                                    <span class="info-value {{ $approvedDamageTotal > 0 ? 'text-danger' : '' }}">{{ $approvedDamageTotal > 0 ? '+' : '' }}{{ number_format((float) $approvedDamageTotal, 0, ',', '.') }}đ</span>
                                </button>
                                @if ($checkoutLateFeePreview > 0)
                                    <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailLateCheckout" data-payment-title="Dự kiến phụ thu trả phòng muộn">
                                        <span class="info-label">Dự kiến trả muộn</span>
                                        <span class="info-value text-danger">+{{ number_format((float) $checkoutLateFeePreview, 0, ',', '.') }}đ</span>
                                    </button>
                                @endif
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailSubtotal" data-payment-title="Tổng phát sinh trước ưu đãi">
                                    <span class="info-label">Trước ưu đãi</span>
                                    <span class="info-value">{{ number_format($totalBeforeDiscount, 0, ',', '.') }}đ</span>
                                </button>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailPromotions" data-payment-title="Mã giảm giá và hỗ trợ">
                                    <span class="info-label">Giảm giá / hỗ trợ</span>
                                    <span class="info-value {{ $promotionDiscountTotal > 0 ? 'text-success' : '' }}">{{ $promotionDiscountTotal > 0 ? '-' : '' }}{{ number_format($promotionDiscountTotal, 0, ',', '.') }}đ</span>
                                </button>

                                <div class="payment-summary-section mt-2">Phân bổ tiền đã thu</div>
                                <div class="info-line">
                                    <span class="info-label">Mức cọc {{ $depositPercentLabel }}</span>
                                    <span class="info-value">{{ number_format($adminPaymentDepositTarget, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Đã phân bổ vào cọc</span>
                                    <span class="info-value">{{ number_format($actualDepositPaid, 0, ',', '.') }}đ</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Đã thu ngoài cọc</span>
                                    <span class="info-value">{{ number_format($additionalPaidTotal, 0, ',', '.') }}đ</span>
                                </div>
                                <button type="button" class="info-line payment-detail-trigger" data-payment-detail="paymentDetailPrepayment" data-payment-title="Tiền trả trước còn dư để bù trừ">
                                    <span class="info-label">Trả trước còn dư</span>
                                    <span class="info-value {{ $currentOverpaymentTotal > 0 ? 'text-warning fw-bold' : '' }}">{{ number_format($currentOverpaymentTotal, 0, ',', '.') }}đ</span>
                                </button>
                            </div>
                        </details>

                        @if (!in_array($booking->status, ['canceled', 'cancelled', 'no_show']) && $remainingTotal > 0.01)
                            <hr class="my-3">

                            @if (session('admin_vnpay_payment_url'))
                                <div class="alert alert-info small mb-3">
                                    <div class="fw-bold mb-1">Đã tạo link yêu cầu thanh toán VNPay</div>
                                    <a href="{{ session('admin_vnpay_payment_url') }}" target="_blank" class="fw-bold">
                                        Mở link yêu cầu thanh toán
                                    </a>
                                </div>
                            @endif

                            <div class="fw-bold mb-2">Thanh toán</div>

                            <div class="soft-note mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <span>Thiếu cọc</span>
                                    <strong class="{{ $adminPaymentDepositAmount > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ</strong>
                                </div>
                                <div class="d-flex justify-content-between gap-2 mt-1">
                                    <span>Còn phải thu</span>
                                    <strong class="text-danger">{{ number_format($remainingTotal, 0, ',', '.') }}đ</strong>
                                </div>
                            </div>

                            <select id="adminPaymentMode" class="form-select form-select-sm mb-2">
                                <option value="">-- Chọn cách thanh toán --</option>
                                <option value="cash">Tiền mặt tại quầy</option>
                                <option value="bank_transfer">Chuyển khoản tại quầy</option>
                                <option value="vnpay">Gửi thanh toán online VNPay qua email</option>
                            </select>

                            <div id="adminDirectPaymentBox" class="d-none">
                                <form action="{{ route('admin.bookings.payments.store', $booking) }}" method="POST">
                                    @csrf

                                    <input type="hidden" name="payment_method" id="adminDirectPaymentMethod" value="">
                                    <input type="hidden" name="payment_request_token" value="{{ old('payment_request_token', (string) \Illuminate\Support\Str::uuid()) }}">

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select name="payment_type" id="adminDirectPaymentType"
                                                class="form-select form-select-sm" required>
                                                <option value="deposit_30" data-amount="{{ $adminPaymentDepositAmount }}"
                                                    @disabled($adminPaymentDepositAmount <= 0)>
                                                    Thu bổ sung để đủ cọc {{ $depositPercentLabel }} - {{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ
                                                </option>
                                                <option value="custom" data-amount="{{ $adminPaymentFullAmount }}" data-entry-mode="remaining">
                                                    Thu phần còn lại - {{ number_format($adminPaymentFullAmount, 0, ',', '.') }}đ
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
                                                class="form-control form-control-sm" min="1000" step="1"
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
                                <form action="{{ route('admin.bookings.vnpay.create', $booking) }}" method="POST">
                                    @csrf

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select name="payment_type" id="adminVnpayPaymentType"
                                                class="form-select form-select-sm" required>
                                                <option value="deposit_30" data-amount="{{ $adminPaymentDepositAmount }}"
                                                    @disabled($adminPaymentDepositAmount <= 0)>
                                                    Gửi yêu cầu bổ sung cọc {{ $depositPercentLabel }} - {{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ
                                                </option>
                                                <option value="custom" data-amount="{{ $adminPaymentFullAmount }}">
                                                    Gửi yêu cầu thanh toán phần còn lại - {{ number_format($adminPaymentFullAmount, 0, ',', '.') }}đ
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <input type="email" name="customer_email" id="adminVnpayCustomerEmail"
                                                class="form-control form-control-sm"
                                                value="{{ $adminPaymentDefaultEmail }}"
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
                        @endif

                        @if ($booking->payments->count() > 0)
                            <hr class="my-3">
                            <div class="fw-bold mb-2">Lịch sử thanh toán</div>

                            <div class="d-grid gap-2">
                                @foreach ($booking->payments->sortByDesc('created_at')->take(5) as $payment)
                                    @php
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
                                    @endphp

                                    <div class="border rounded-3 p-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong>{{ $paymentProviderLabels[$payment->provider] ?? $payment->provider }}</strong>
                                            <span>{{ number_format((float) $payment->amount, 0, ',', '.') }}đ</span>
                                        </div>
                                        <div class="small text-muted">
                                            {{ $paymentStatusText }} ·
                                            {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') : ($payment->created_at ? $payment->created_at->format('d/m/Y H:i') : '---') }}
                                            @if ($paymentExpireText && $payment->status === 'pending')
                                                · {{ $paymentExpireText }}
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Khách hàng</h5>
                        </div>

                        <div class="info-list">
                            <div class="info-line">
                                <span class="info-label">Họ tên</span>
                                <span class="info-value">{{ $customerName }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">SĐT</span>
                                <span class="info-value">{{ $booking->booked_customer_phone ?? '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $booking->booked_customer_email ?? '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">CCCD</span>
                                <span class="info-value">{{ $booking->booked_customer_cccd ?? '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Địa chỉ</span>
                                <span class="info-value">{{ $booking->booked_customer_address ?? '---' }}</span>
                            </div>
                        </div>
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
        @if ($booking->booking_type === 'hourly')
            <div class="payment-breakdown-item">
                <div class="fw-bold">Tiền phòng theo thời lượng</div>
                <div class="payment-breakdown-formula">
                    {{ optional($booking->check_in_at)->format('d/m/Y H:i') }} → {{ optional($booking->check_out_at)->format('d/m/Y H:i') }}
                </div>
                <div class="d-flex justify-content-between mt-2"><span>Thành tiền</span><strong>{{ number_format($roomTotal, 0, ',', '.') }}đ</strong></div>
            </div>
        @else
            @forelse ($booking->bookingRooms as $bookingRoom)
                @php
                    $roomLineTotal = (float) $bookingRoom->price_at_booking * $nightCount;
                    $roomNumber = $bookingRoom->room?->room_number ?: 'Chưa gán phòng';
                    $roomCategoryName = $bookingRoom->room?->category?->name ?: $booking->roomCategory?->name;
                @endphp
                <div class="payment-breakdown-item">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">Phòng {{ $roomNumber }}</div>
                            <div class="small text-muted">{{ $roomCategoryName ?: 'Chưa xác định hạng' }}</div>
                        </div>
                        <strong>{{ number_format($roomLineTotal, 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="payment-breakdown-formula">
                        {{ number_format((float) $bookingRoom->price_at_booking, 0, ',', '.') }}đ × {{ $nightCount }} đêm
                        = {{ number_format($roomLineTotal, 0, ',', '.') }}đ
                    </div>
                </div>
            @empty
                <div class="alert alert-secondary mb-0">Đơn chưa có dữ liệu phòng để hiển thị chi tiết.</div>
            @endforelse
        @endif
        <div class="payment-breakdown-total"><span>Tổng tiền phòng</span><span>{{ number_format($roomTotal, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailServices">
        @forelse ($confirmedServiceItemsForBreakdown as $item)
            @php
                $itemRule = \App\Models\Service::normalizeBillingRule($item->billing_rule_snapshot ?: optional($item->service)->billing_rule);
            @endphp
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2">
                    <div>
                        <div class="fw-bold">{{ $item->name }}</div>
                        <div class="small text-muted">{{ $serviceBillingRuleLabels[$itemRule] ?? 'Một lần / theo số lượng nhập' }}</div>
                    </div>
                    <strong class="text-danger">+{{ number_format((float) $item->total, 0, ',', '.') }}đ</strong>
                </div>
                <div class="payment-breakdown-formula">{{ $serviceBillingFormula($item) }}</div>
                @if ($item->note)
                    <div class="small text-muted mt-1">Ghi chú: {{ $item->note }}</div>
                @endif
            </div>
        @empty
            <div class="alert alert-secondary mb-0">Chưa có dịch vụ hoặc phụ thu đã xác nhận.</div>
        @endforelse
        <div class="payment-breakdown-total"><span>Tổng dịch vụ / phụ thu</span><span>{{ number_format($serviceItemTotal, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailMinibar">
        @forelse ($approvedMinibarItemsForBreakdown as $item)
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong>{{ $item->name }}</strong><strong class="text-danger">+{{ number_format((float) $item->total, 0, ',', '.') }}đ</strong></div>
                <div class="payment-breakdown-formula">{{ number_format((float) $item->price, 0, ',', '.') }}đ × {{ max(1, (int) $item->quantity) }} {{ $item->unit ?: 'đơn vị' }} = {{ number_format((float) $item->total, 0, ',', '.') }}đ</div>
            </div>
        @empty
            <div class="alert alert-secondary mb-0">Chưa có dịch vụ tại phòng/minibar được duyệt.</div>
        @endforelse
        <div class="payment-breakdown-total"><span>Tổng dịch vụ tại phòng</span><span>{{ number_format($approvedMinibarTotal, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailDamage">
        @forelse ($approvedDamageItemsForBreakdown as $item)
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong>{{ $item->name }}</strong><strong class="text-danger">+{{ number_format((float) $item->total, 0, ',', '.') }}đ</strong></div>
                <div class="payment-breakdown-formula">{{ number_format((float) $item->price, 0, ',', '.') }}đ × {{ max(1, (int) $item->quantity) }} = {{ number_format((float) $item->total, 0, ',', '.') }}đ</div>
                @if ($item->admin_note)<div class="small text-muted mt-1">Ghi chú: {{ $item->admin_note }}</div>@endif
            </div>
        @empty
            <div class="alert alert-secondary mb-0">Chưa có phí hư hại được duyệt.</div>
        @endforelse
        <div class="payment-breakdown-total"><span>Tổng phí hư hại</span><span>{{ number_format($approvedDamageTotal, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailLateCheckout">
        <div class="payment-breakdown-item">
            <div class="fw-bold">{{ $checkoutLateReasonText }}</div>
            <div class="payment-breakdown-formula mt-2">{{ $checkoutLatePolicyText }}</div>
            <div class="payment-breakdown-formula">Công thức: {{ $checkoutLateFormulaText }}</div>
            @if ($checkoutLateNoteText)<div class="small text-muted mt-2">{{ $checkoutLateNoteText }}</div>@endif
        </div>
        <div class="payment-breakdown-total"><span>Phụ thu dự kiến</span><span>{{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailSubtotal">
        <div class="payment-breakdown-item">
            <div class="d-flex justify-content-between"><span>Tiền phòng</span><strong>{{ number_format($roomTotal, 0, ',', '.') }}đ</strong></div>
            <div class="d-flex justify-content-between mt-2"><span>Dịch vụ / phụ thu</span><strong>+{{ number_format($serviceItemTotal, 0, ',', '.') }}đ</strong></div>
            @if ($manualRoomSelectionFee > 0)<div class="d-flex justify-content-between mt-2"><span>Phí chọn phòng thủ công</span><strong>+{{ number_format($manualRoomSelectionFee, 0, ',', '.') }}đ</strong></div>@endif
            <div class="d-flex justify-content-between mt-2"><span>Dịch vụ tại phòng / hư hại</span><strong>+{{ number_format($approvedInspectionTotal, 0, ',', '.') }}đ</strong></div>
            @if ($checkoutLateFeePreview > 0)<div class="d-flex justify-content-between mt-2"><span>Trả phòng muộn dự kiến</span><strong>+{{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ</strong></div>@endif
        </div>
        <div class="payment-breakdown-total"><span>Tổng trước ưu đãi</span><span>{{ number_format($totalBeforeDiscount, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailPromotions">
        @forelse ($booking->bookingPromotions as $promotionUsage)
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong>{{ $promotionUsage->code_snapshot }}</strong><strong class="text-success">-{{ number_format((float) $promotionUsage->discount_amount, 0, ',', '.') }}đ</strong></div>
                <div class="small text-muted mt-1">
                    {{ $promotionUsage->type_label }} ·
                    @if (($promotionUsage->scope ?? 'booking') === 'room')
                        chỉ phòng {{ $promotionUsage->bookingRoom?->room?->room_number ?? '---' }}
                    @else
                        toàn booking
                    @endif
                </div>
                <div class="payment-breakdown-formula">Giảm tiền: {{ number_format((float) $promotionUsage->money_discount_amount, 0, ',', '.') }}đ · Dịch vụ: {{ number_format((float) $promotionUsage->service_discount_amount, 0, ',', '.') }}đ · Nâng hạng: {{ number_format((float) $promotionUsage->room_upgrade_discount_amount, 0, ',', '.') }}đ</div>
            </div>
        @empty
            <div class="alert alert-secondary mb-0">Đơn chưa áp dụng mã giảm giá hoặc mã hỗ trợ.</div>
        @endforelse
        <div class="payment-breakdown-total"><span>Tổng ưu đãi</span><span>-{{ number_format($promotionDiscountTotal, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailFinalTotal">
        <div class="payment-breakdown-item">
            <div class="d-flex justify-content-between"><span>Tổng phát sinh trước ưu đãi</span><strong>{{ number_format($totalBeforeDiscount, 0, ',', '.') }}đ</strong></div>
            @if ($manualRoomSelectionFee > 0)<div class="small text-muted mt-1">Đã gồm {{ number_format($manualRoomSelectionFee, 0, ',', '.') }}đ phí chọn phòng thủ công.</div>@endif
            <div class="d-flex justify-content-between mt-2"><span>Mã giảm giá / hỗ trợ</span><strong class="text-success">-{{ number_format($promotionDiscountTotal, 0, ',', '.') }}đ</strong></div>
        </div>
        <div class="payment-breakdown-total"><span>Tổng cần thanh toán</span><span>{{ number_format($finalTotal, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailDeposit">
        <div class="payment-breakdown-item">
            <div class="fw-bold">Mức cọc được tính lại theo đơn hiện tại</div>
            <div class="payment-breakdown-formula">{{ $depositPercentLabel }} × tiền phòng sau phần ưu đãi thuộc phạm vi tính cọc = {{ number_format($adminPaymentDepositTarget, 0, ',', '.') }}đ.</div>
            <div class="small text-muted mt-2">Lịch sử khách đã chuyển tiền vẫn giữ nguyên; hệ thống chỉ phân bổ lại số đã thu vào mức cọc mới.</div>
        </div>
        <div class="payment-breakdown-total"><span>Còn thiếu để đủ cọc</span><span>{{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ</span></div>
    </template>

    <template id="paymentDetailPayments">
        @forelse ($successfulPayments as $payment)
            @php
                $providerKey = strtolower((string) $payment->provider);
                $providerLabel = $paymentProviderLabelsForBreakdown[$providerKey] ?? strtoupper($payment->provider ?: 'Khác');
                $paidAt = $payment->paid_at ?: $payment->created_at;
            @endphp
            <div class="payment-breakdown-item">
                <div class="d-flex justify-content-between gap-2"><strong>{{ $providerLabel }}</strong><strong>-{{ number_format((float) $payment->amount, 0, ',', '.') }}đ</strong></div>
                <div class="small text-muted mt-1">{{ $paidAt ? $paidAt->format('d/m/Y H:i') : 'Chưa có thời gian' }} · {{ $payment->payment_type ?: 'Thanh toán đơn' }}</div>
                @if ($payment->transaction_no)<div class="small text-muted">Mã giao dịch: {{ $payment->transaction_no }}</div>@endif
            </div>
        @empty
            <div class="alert alert-secondary mb-0">Chưa có giao dịch thành công.</div>
        @endforelse
        <div class="payment-breakdown-total"><span>Tổng khách đã thanh toán</span><span>{{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ</span></div>
        <div class="small text-muted mt-2">Phân bổ hiện tại: {{ number_format($actualDepositPaid, 0, ',', '.') }}đ vào cọc và {{ number_format($additionalPaidTotal, 0, ',', '.') }}đ ngoài cọc.</div>
    </template>

    <template id="paymentDetailPrepayment">
        <div class="payment-breakdown-item">
            <div class="fw-bold">Khoản còn dư chưa cần dùng</div>
            <div class="payment-breakdown-formula">Tổng khách đã thanh toán {{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ - Tổng booking {{ number_format($finalTotal, 0, ',', '.') }}đ = {{ number_format($currentOverpaymentTotal, 0, ',', '.') }}đ.</div>
            <div class="small text-muted mt-2">Khoản này được giữ trên đơn để tự bù trừ dịch vụ, minibar, phụ thu hoặc chi phí phát sinh sau đó. Không tạo hoàn tiền tự động.</div>
        </div>
    </template>

    <template id="paymentDetailRemaining">
        <div class="payment-breakdown-item">
            <div class="d-flex justify-content-between"><span>Tổng cần thanh toán</span><strong>{{ number_format($finalTotal, 0, ',', '.') }}đ</strong></div>
            <div class="d-flex justify-content-between mt-2"><span>Tổng khách đã thanh toán</span><strong>-{{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ</strong></div>
            @if ($currentOverpaymentTotal > 0)<div class="small text-muted mt-2">Khách đang trả trước dư {{ number_format($currentOverpaymentTotal, 0, ',', '.') }}đ; số còn phải thu bằng 0đ.</div>@endif
        </div>
        <div class="payment-breakdown-total"><span>Còn lại cần thu</span><span class="text-danger">{{ number_format($remainingTotal, 0, ',', '.') }}đ</span></div>
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

            const checkInActualGuestsStep = document.getElementById('checkInActualGuestsStep');
            const actualAdultInput = document.getElementById('actualAdultCount');
            const actualChildInput = document.getElementById('actualChildCount');
            const actualGuestConfirmed = document.getElementById('actualGuestConfirmed');
            const actualGuestCapacityBadge = document.getElementById('actualGuestCapacityBadge');
            const actualGuestCapacityText = document.getElementById('actualGuestCapacityText');
            const readinessActualGuestStatus = document.getElementById('readinessActualGuestStatus');
            const checkInReadinessBadge = document.getElementById('checkInReadinessBadge');
            const aggregateCapacityIssueText = document.getElementById('aggregateCapacityIssueText');
            const normalCheckInBox = document.getElementById('normalCheckInBox');
            const overCapacityBox = document.getElementById('overCapacityBox');
            const overCapacityAction = document.getElementById('overCapacityAction');
            const stayingGuestsPanel = document.getElementById('stayingGuestsPanel');

            const checkInForm = document.getElementById('checkInForm');
            const checkInSubmitButton = document.getElementById('checkInSubmitButton');
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
            const earlyCheckInConfirmPanel = document.getElementById('earlyCheckInConfirmPanel');
            const confirmEarlyCheckInSubmit = document.getElementById('confirmEarlyCheckInSubmit');
            const cancelEarlyCheckInSubmit = document.getElementById('cancelEarlyCheckInSubmit');
            const dismissEarlyCheckInConfirm = document.getElementById('dismissEarlyCheckInConfirm');
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

            const capacityFeeRows = Array.from(document.querySelectorAll('.capacity-fee-row'));
            const allExtraFeeTotalText = document.getElementById('allExtraFeeTotalText');

            function currentActualGuestState() {
                // Dùng đúng tổng sức chứa đang hiển thị của TOÀN BỘ booking.
                // Không đọc hidden input riêng để tránh giữ capacity cũ sau khi thêm/đổi phòng.
                const adultCapacity = Math.max(0, parseInt(checkInActualGuestsStep?.dataset.adultCapacity || 0, 10));
                const childCapacity = Math.max(0, parseInt(checkInActualGuestsStep?.dataset.childCapacity || 0, 10));
                const adults = Math.max(0, parseInt(actualAdultInput?.value || 0));
                const children = Math.max(0, parseInt(actualChildInput?.value || 0));
                const adultOver = Math.max(0, adults - adultCapacity);

                const childOver = Math.max(0, children - childCapacity);
                const minorOver = childOver;

                return {
                    adults, children,
                    adultCapacity, childCapacity,
                    adultOver, childOver, minorOver,
                    isOver: adultOver > 0 || minorOver > 0
                };
            }

            function overCountForType(type, state = currentActualGuestState()) {
                if (type === 'adult') return state.adultOver;
                if (type === 'child') return state.childOver;
                return 0;
            }

            function currentCheckInCapacityIsOver() {
                return currentActualGuestState().isOver;
            }

            function syncFeeTableCountsFromActual() {
                const values = {
                    adult: Math.max(0, parseInt(actualAdultInput?.value || 0)),
                    child: Math.max(0, parseInt(actualChildInput?.value || 0)),
                };
                capacityFeeRows.forEach(function (row) {
                    const type = row.dataset.guestType;
                    const input = row.querySelector('.capacity-fee-actual-count');
                    if (input && Object.prototype.hasOwnProperty.call(values, type)) {
                        input.value = values[type];
                    }
                });
            }

            function syncActualFromFeeTable(row) {
                const countInput = row.querySelector('.capacity-fee-actual-count');
                const targetId = countInput?.dataset.syncTarget;
                const target = targetId ? document.getElementById(targetId) : null;
                if (!countInput || !target) return;
                const minimum = row.dataset.guestType === 'adult' ? 1 : 0;
                const value = Math.max(minimum, parseInt(countInput.value || minimum));
                countInput.value = value;
                target.value = value;
                target.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function autoSelectFeeService(row) {
                const type = row.dataset.guestType;
                const select = row.querySelector('.capacity-fee-service');
                if (!select || select.value) return;

                const keywords = type === 'adult'
                    ? ['người lớn', 'nguoi lon']
                    : ['trẻ em', 'tre em', 'trẻ'];
                const matched = Array.from(select.options).find(function (option) {
                    const name = (option.dataset.serviceName || option.textContent || '').toLowerCase();
                    return keywords.some(keyword => name.includes(keyword));
                });
                if (matched) select.value = matched.value;
            }

            function updateCapacityFeeTable() {
                const state = currentActualGuestState();
                let grandTotal = 0;

                capacityFeeRows.forEach(function (row) {
                    const type = row.dataset.guestType;
                    const serviceSelect = row.querySelector('.capacity-fee-service');
                    const unitPriceText = row.querySelector('.capacity-fee-unit-price');
                    const totalText = row.querySelector('.capacity-fee-total');
                    const billedCountText = row.querySelector('.capacity-fee-billed-count');
                    const overCount = overCountForType(type, state);

                    row.classList.toggle('table-warning', overCount > 0);
                    if (billedCountText) billedCountText.textContent = overCount > 0 ? ('Tính phí: ' + overCount) : 'Không vượt';
                    if (serviceSelect) {
                        serviceSelect.required = overCount > 0;
                        if (overCount > 0) {
                            autoSelectFeeService(row);
                        } else {
                            serviceSelect.value = '';
                        }
                    }

                    const option = serviceSelect?.options[serviceSelect.selectedIndex];
                    const price = option ? parseFloat(option.dataset.price || 0) : 0;
                    const total = overCount * price;
                    if (unitPriceText) unitPriceText.textContent = price > 0 ? numberFormat(price) : '0đ';
                    if (totalText) totalText.textContent = total > 0 ? numberFormat(total) : '0đ';
                    grandTotal += total;
                });

                if (allExtraFeeTotalText) allExtraFeeTotalText.textContent = numberFormat(grandTotal);
            }

            function capacityFeeSelectionsReady() {
                const state = currentActualGuestState();
                return capacityFeeRows.every(function (row) {
                    const overCount = overCountForType(row.dataset.guestType, state);
                    if (overCount <= 0) return true;
                    return !!row.querySelector('.capacity-fee-service')?.value;
                });
            }

            function updateActualGuestCapacityUi() {
                const state = currentActualGuestState();
                const parts = [];
                if (state.adultOver > 0) parts.push('Vượt ' + state.adultOver + ' người lớn');
                if (state.childOver > 0) parts.push('vượt ' + state.childOver + ' trẻ em');
                const text = state.isOver ? parts.join(' · ') : 'Đủ sức chứa';

                if (actualGuestCapacityBadge) {
                    const confirmed = !!actualGuestConfirmed?.checked;
                    actualGuestCapacityBadge.textContent = !confirmed ? 'Chưa xác nhận' : (state.isOver ? 'Vượt sức chứa' : 'Đã đối chiếu');
                    actualGuestCapacityBadge.classList.toggle('status-warning', !confirmed || state.isOver);
                    actualGuestCapacityBadge.classList.toggle('status-done', confirmed && !state.isOver);
                }
                if (actualGuestCapacityText) actualGuestCapacityText.textContent = text;
                if (readinessActualGuestStatus) {
                    readinessActualGuestStatus.textContent = (actualGuestConfirmed?.checked ? 'Đã đối chiếu · ' : 'Chưa xác nhận · ') + text;
                    readinessActualGuestStatus.classList.toggle('text-success', !!actualGuestConfirmed?.checked && !state.isOver);
                    readinessActualGuestStatus.classList.toggle('text-warning', !actualGuestConfirmed?.checked || state.isOver);
                }
                if (aggregateCapacityIssueText) aggregateCapacityIssueText.textContent = state.isOver ? text + '.' : '';
            }

            function updateCheckInSubmitState() {
                if (!checkInSubmitButton) return;
                const staticDisabled = checkInSubmitButton.dataset.staticDisabled === '1';
                const state = currentActualGuestState();
                const actualConfirmed = !!actualGuestConfirmed?.checked;
                const enoughAdultsForRooms = state.adults >= {{ max(1, $booking->bookingRooms->count()) }};
                const capacityResolved = !state.isOver || capacityFeeSelectionsReady();
                const blocked = staticDisabled || !actualConfirmed || !enoughAdultsForRooms || !capacityResolved;
                checkInSubmitButton.disabled = blocked;

                const summary = document.getElementById('checkInBlockingSummary');
                const summaryText = document.getElementById('checkInBlockingSummaryText');
                if (summary && summaryText) {
                    const reasons = [];
                    const staticReason = (summary.dataset.staticReason || '').trim();
                    if (staticReason) reasons.push(staticReason);
                    if (!actualConfirmed) reasons.push('Điều kiện 2 chưa xác nhận số khách thực tế.');
                    if (!enoughAdultsForRooms) reasons.push('Số người lớn thực tế phải đủ để mỗi phòng có một người đại diện.');
                    if (state.isOver && !capacityResolved) reasons.push('Chưa chọn đủ loại phụ thu cho phần khách vượt sức chứa.');
                    summary.classList.toggle('d-none', !blocked);
                    summaryText.textContent = reasons.join(' ');
                }
            }

            function checkCapacity() {
                const state = currentActualGuestState();
                if (overCapacityBox) {
                    overCapacityBox.classList.toggle('d-none', !state.isOver);
                }
                if (overCapacityAction) {
                    overCapacityAction.value = state.isOver ? 'extra_fee' : '';
                }
                if (state.isOver) {
                    const actualGuestsStep = document.getElementById('checkInActualGuestsStep');
                    if (actualGuestsStep) actualGuestsStep.open = true;
                }
                updateCapacityFeeTable();
                updateCheckInSubmitState();
            }

            capacityFeeRows.forEach(function (row) {
                const countInput = row.querySelector('.capacity-fee-actual-count');
                const serviceSelect = row.querySelector('.capacity-fee-service');
                if (countInput) {
                    countInput.addEventListener('input', function () {
                        syncActualFromFeeTable(row);
                    });
                }
                if (serviceSelect) {
                    serviceSelect.addEventListener('change', function () {
                        updateCapacityFeeTable();
                        updateCheckInSubmitState();
                    });
                }
            });

            [actualAdultInput, actualChildInput].forEach(function (input) {
                if (!input) return;
                input.addEventListener('input', function () {
                    syncFeeTableCountsFromActual();
                    updateActualGuestCapacityUi();
                    checkCapacity();
                });
            });

            if (actualGuestConfirmed) {
                actualGuestConfirmed.addEventListener('change', function () {
                    updateActualGuestCapacityUi();
                    updateCheckInSubmitState();
                });
            }

            syncFeeTableCountsFromActual();
            updateActualGuestCapacityUi();
            checkCapacity();

            let checkInSubmitConfirmed = false;

            function showEarlyCheckInConfirmPanel() {
                if (!earlyCheckInConfirmPanel) return false;
                earlyCheckInConfirmPanel.classList.remove('d-none');
                setTimeout(function () {
                    try {
                        earlyCheckInConfirmPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } catch (_) {}
                }, 20);
                return true;
            }

            if (earlyCheckInConfirmPanel && !earlyCheckInConfirmPanel.classList.contains('d-none')) {
                setTimeout(function () {
                    try {
                        earlyCheckInConfirmPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } catch (_) {}
                }, 80);
            }

            function hideEarlyCheckInConfirmPanel() {
                if (earlyCheckInConfirmPanel) {
                    earlyCheckInConfirmPanel.classList.add('d-none');
                }
                if (earlyCheckInAction) {
                    earlyCheckInAction.value = '';
                }
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

            function submitCheckInWithEarlyFeeAccepted(event) {
                if (event) {
                    event.preventDefault();
                }
                if (earlyCheckInAction) {
                    earlyCheckInAction.value = 'accept_fee';
                }

                checkInSubmitConfirmed = true;

                // requestSubmit giữ lại submitter nên name/value=early_check_in_action=accept_fee
                // vẫn được gửi. Fallback dùng hidden field đã set ở trên.
                if (checkInForm.requestSubmit && confirmEarlyCheckInSubmit) {
                    checkInForm.requestSubmit(confirmEarlyCheckInSubmit);
                } else {
                    checkInForm.submit();
                }
            }

            if (confirmEarlyCheckInSubmit) {
                confirmEarlyCheckInSubmit.addEventListener('click', submitCheckInWithEarlyFeeAccepted);
            }
            if (cancelEarlyCheckInSubmit) {
                cancelEarlyCheckInSubmit.addEventListener('click', hideEarlyCheckInConfirmPanel);
            }
            if (dismissEarlyCheckInConfirm) {
                dismissEarlyCheckInConfirm.addEventListener('click', hideEarlyCheckInConfirmPanel);
            }

            function revealInvalidCheckInControl(control) {
                if (!control) return;

                const details = control.closest('details');
                if (details) details.open = true;

                // Nếu field nằm trong bước khách thực tế đang đóng thì mở đúng bước đó,
                // tránh cảm giác nút Nhận phòng không phản hồi vì browser chặn submit ngầm.
                const actualGuestsStep = document.getElementById('checkInActualGuestsStep');
                if (actualGuestsStep && (
                    control === actualAdultInput
                    || control === actualChildInput
                    || control === actualGuestConfirmed
                    || control.classList?.contains('capacity-fee-service')
                )) {
                    actualGuestsStep.open = true;
                }

                setTimeout(function () {
                    try {
                        control.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        control.focus({ preventScroll: true });
                    } catch (_) {}
                }, 50);
            }

            if (checkInSubmitButton && checkInForm) {
                checkInSubmitButton.addEventListener('click', function (event) {
                    if (checkInSubmitConfirmed || checkInSubmitButton.disabled) {
                        return;
                    }

                    // Bắt validation ngay từ click. Submit event của browser không chạy khi
                    // có field required không hợp lệ, trước đây tạo cảm giác bấm nút không có gì xảy ra.
                    if (!checkInForm.checkValidity()) {
                        event.preventDefault();
                        const invalidControl = checkInForm.querySelector(':invalid');
                        revealInvalidCheckInControl(invalidControl);
                        checkInForm.reportValidity();
                        return false;
                    }

                    const isEarly = earlyCheckInIsActive && earlyCheckInIsActive.value === '1';
                    if (!isEarly) {
                        return;
                    }

                    event.preventDefault();
                    if (earlyCheckInAction) {
                        earlyCheckInAction.value = '';
                    }

                    if (showEarlyCheckInConfirmPanel()) {
                        return false;
                    }

                    // Fallback cực hiếm khi panel không tồn tại trong DOM.
                    if (confirm(getEarlyCheckInConfirmMessage())) {
                        submitCheckInWithEarlyFeeAccepted();
                    }
                    return false;
                });
            }

            if (checkInForm) {
                checkInForm.addEventListener('submit', function (event) {
                    if (checkInSubmitConfirmed) {
                        return;
                    }

                    const isEarly = earlyCheckInIsActive && earlyCheckInIsActive.value === '1';

                    // Luồng early đã được xử lý ở click để không bị native validation nuốt mất.
                    // Giữ fallback này cho trường hợp submit bằng Enter/requestSubmit từ script khác.
                    if (isEarly) {
                        event.preventDefault();

                        if (earlyCheckInAction) {
                            earlyCheckInAction.value = '';
                        }

                        if (showEarlyCheckInConfirmPanel()) {
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
                    const nights = Math.max(1, Number({{ json_encode((int) $nightCount) }}));
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
            const adminRemainingAmount = Number({{ json_encode((float) $remainingTotal) }});
            const adminDepositPercentLabel = @json($depositPercentLabel);
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
                            const minimumDayGap = @json($booking->booking_type === 'hourly' ? 0 : 1);
                            minimum.setDate(minimum.getDate() + minimumDayGap);
                            const minimumLocal = new Date(minimum.getTime() - minimum.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
                            checkOutDate.min = minimumLocal;
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

            const extendStayForm = document.getElementById('extendStayPreviewForm');
            const extendStayDate = document.getElementById('extendCheckOutDateVn');
            const extendStayTime = document.getElementById('extendCheckOutTime');
            const extendStayRule = document.getElementById('extendStayTimeRule');
            const extendStaySubmit = document.getElementById('extendStayPreviewSubmit');

            const validateExtendStayTime = function () {
                if (!extendStayForm || !extendStayDate || !extendStayTime || !extendStayRule) return true;

                const currentDate = extendStayForm.dataset.currentCheckoutDate || '';
                const currentTime = extendStayForm.dataset.currentCheckoutTime || '';
                const currentText = extendStayForm.dataset.currentCheckoutText || '';
                const newDate = extendStayDate.value || '';
                const newTime = extendStayTime.value || '';

                let valid = true;
                let message = 'Thời gian trả mới phải sau check-out hiện tại ' + currentText + '.';

                if (newDate && currentDate && newDate < currentDate) {
                    valid = false;
                    message = 'Không thể gia hạn: ngày trả mới không được trước ' + currentText + '.';
                } else if (newDate && newTime && currentDate && currentTime
                    && newDate === currentDate && newTime <= currentTime) {
                    valid = false;
                    message = 'Không thể gia hạn: nếu giữ ngày ' + currentDate.split('-').reverse().join('/')
                        + ', giờ trả mới phải sau ' + currentTime + '.';
                }

                extendStayRule.textContent = message;
                extendStayRule.classList.toggle('text-danger', !valid);
                extendStayRule.classList.toggle('text-muted', valid);
                extendStayDate.classList.toggle('is-invalid', !valid && newDate !== '');
                extendStayTime.classList.toggle('is-invalid', !valid && newTime !== '');
                if (extendStaySubmit) extendStaySubmit.disabled = !valid;
                return valid;
            };

            if (extendStayTime && typeof flatpickr !== 'undefined') {
                flatpickr('#extendCheckOutTime', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 30,
                    locale: 'vn',
                    onChange: validateExtendStayTime
                });
            }

            [extendStayDate, extendStayTime].forEach(function (field) {
                field?.addEventListener('change', validateExtendStayTime);
                field?.addEventListener('input', validateExtendStayTime);
            });
            extendStayForm?.addEventListener('submit', function (event) {
                if (!validateExtendStayTime()) event.preventDefault();
            });
            validateExtendStayTime();

            document.querySelectorAll('.js-category-room-form').forEach(function (form) {
                const mode = form.querySelector('.js-room-assignment-mode');
                const category = form.querySelector('.js-room-category-target');
                const picker = form.querySelector('[data-manual-room-picker]');
                const checks = Array.from(form.querySelectorAll('.js-manual-room-check'));
                const options = Array.from(form.querySelectorAll('.js-manual-room-option'));
                const selectionStatus = form.querySelector('.js-manual-room-selection-status');
                const expected = Number(form.dataset.roomCount || 1);

                const chosenChecks = () => checks.filter(check => check.checked && !check.disabled);

                const updateSelectionStatus = function () {
                    if (!selectionStatus) return;
                    const chosen = chosenChecks().length;
                    selectionStatus.textContent = 'Đã chọn ' + chosen + '/' + expected + ' phòng.';
                    selectionStatus.classList.toggle('text-danger', chosen > expected);
                    selectionStatus.classList.toggle('text-success', chosen === expected);
                };

                const enforceLimit = function (changedCheck) {
                    const chosen = chosenChecks();
                    if (chosen.length > expected && changedCheck) {
                        changedCheck.checked = false;
                    }
                    updateSelectionStatus();
                };

                const sync = function () {
                    const isManual = mode?.value === 'manual';
                    picker?.classList.toggle('d-none', !isManual);
                    const categoryId = String(category?.value || '');

                    checks.forEach(function (check) {
                        const visible = isManual && categoryId !== '' && String(check.dataset.categoryId || '') === categoryId;
                        check.disabled = !visible;
                        if (!visible) check.checked = false;
                    });
                    options.forEach(function (option) {
                        option.classList.toggle('d-none', categoryId === '' || String(option.dataset.categoryId || '') !== categoryId);
                    });
                    updateSelectionStatus();
                };

                checks.forEach(function (check) {
                    check.addEventListener('change', function () {
                        enforceLimit(check);
                    });
                });

                mode?.addEventListener('change', sync);
                category?.addEventListener('change', sync);
                form.addEventListener('submit', function (event) {
                    if (mode?.value !== 'manual') return;
                    const chosen = chosenChecks().length;
                    if (chosen !== expected) {
                        event.preventDefault();
                        alert('Vui lòng tích đúng ' + expected + ' phòng.');
                        picker?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                });
                sync();
            });

            document.querySelectorAll('.js-same-rank-room-form').forEach(function (form) {
                const mode = form.querySelector('.js-room-assignment-mode');
                const oldRoom = form.querySelector('.js-old-room-select');
                const picker = form.querySelector('[data-manual-room-picker]');
                const checks = Array.from(form.querySelectorAll('.js-same-rank-room-check'));
                const options = Array.from(form.querySelectorAll('.js-same-rank-room-option'));
                const status = form.querySelector('.js-same-rank-room-status');

                const updateStatus = function () {
                    const chosen = checks.filter(check => check.checked && !check.disabled).length;
                    if (status) {
                        status.textContent = 'Đã chọn ' + chosen + '/1 phòng.';
                        status.classList.toggle('text-success', chosen === 1);
                    }
                };

                checks.forEach(function (check) {
                    check.addEventListener('change', function () {
                        if (check.checked) {
                            checks.forEach(other => {
                                if (other !== check) other.checked = false;
                            });
                        }
                        updateStatus();
                    });
                });

                const sync = function () {
                    const isManual = mode?.value === 'manual';
                    picker?.classList.toggle('d-none', !isManual);
                    const categoryId = String(oldRoom?.selectedOptions?.[0]?.dataset?.categoryId || '');
                    checks.forEach(function (check) {
                        const visible = isManual && categoryId !== '' && String(check.dataset.categoryId || '') === categoryId;
                        check.disabled = !visible;
                        if (!visible) check.checked = false;
                    });
                    options.forEach(function (option) {
                        option.classList.toggle('d-none', categoryId === '' || String(option.dataset.categoryId || '') !== categoryId);
                    });
                    updateStatus();
                };

                mode?.addEventListener('change', sync);
                oldRoom?.addEventListener('change', sync);
                form.addEventListener('submit', function (event) {
                    if (mode?.value !== 'manual') return;
                    const chosen = checks.filter(check => check.checked && !check.disabled).length;
                    if (chosen !== 1) {
                        event.preventDefault();
                        alert('Vui lòng tích đúng 1 phòng thay thế.');
                        picker?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                });
                sync();
            });

            document.querySelectorAll('.js-fixed-room-checklist').forEach(function (list) {
                const form = list.closest('form');
                const checks = Array.from(list.querySelectorAll('.js-fixed-room-check'));
                const expected = Math.max(1, Number(list.dataset.requiredCount || 1));
                const status = form?.querySelector('.js-fixed-room-status');

                const update = function (changedCheck = null) {
                    let chosen = checks.filter(check => check.checked);
                    if (chosen.length > expected && changedCheck) {
                        changedCheck.checked = false;
                        chosen = checks.filter(check => check.checked);
                    }
                    if (status) {
                        const suffix = status.textContent.includes('Có thể dùng chính phòng dự phòng')
                            ? ' Có thể dùng chính phòng dự phòng nếu phòng đó thực sự đáp ứng yêu cầu khách đã ghi.'
                            : '';
                        status.textContent = 'Đã chọn ' + chosen.length + '/' + expected + ' phòng.' + suffix;
                        status.classList.toggle('text-success', chosen.length === expected);
                        status.classList.toggle('text-danger', chosen.length !== expected);
                    }
                };

                checks.forEach(check => check.addEventListener('change', () => update(check)));
                form?.addEventListener('submit', function (event) {
                    const chosen = checks.filter(check => check.checked).length;
                    if (chosen !== expected) {
                        event.preventDefault();
                        alert('Vui lòng tích đúng ' + expected + ' phòng.');
                        list.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                });
                update();
            });

            const bookingDetailRoot = document.getElementById('bookingDetailRoot');
            const toggleSecondaryBookingInfo = document.getElementById('toggleSecondaryBookingInfo');

            if (bookingDetailRoot && bookingDetailRoot.classList.contains('reception-compact')) {
                const secondaryHeadings = ['Lịch sử thao tác', 'Khách hàng'];
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
@include('partials.cccd-scanner-script')





<script>
document.addEventListener('DOMContentLoaded', function () {
    const roomModalBody = document.getElementById('roomAdjustmentModalBody');
    const roomModalElement = document.getElementById('roomAdjustmentModal');
    if (!roomModalBody || !roomModalElement) return;

    const stateKey = 'booking-support-state-{{ $booking->id }}';
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
                : (rawTitle.includes('toàn bộ') ? 'Đổi hạng toàn bộ đơn' : ({{ $booking->bookingRooms->count() <= 1 ? 'true' : 'false' }} ? 'Đổi hạng phòng' : 'Đổi hạng một phòng'));
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addRoomForm = document.querySelector('form[action*="add-room-to-booking"]');
    if (!addRoomForm) return;

    const categorySelect = addRoomForm.querySelector('select[name="additional_room_category_id"]');
    const quantityInput = addRoomForm.querySelector('input[name="additional_room_quantity"]');
    const modeInputs = addRoomForm.querySelectorAll('.js-add-room-assignment-mode');
    const manualWrap = addRoomForm.querySelector('.js-add-room-manual-wrap');
    const checks = Array.from(addRoomForm.querySelectorAll('.js-add-room-manual-check'));
    const options = Array.from(addRoomForm.querySelectorAll('.js-add-room-option'));
    const manualHelp = addRoomForm.querySelector('.js-add-room-manual-help');

    if (!categorySelect || !quantityInput || !manualWrap || checks.length === 0) return;

    const selectedChecks = () => checks.filter(check => check.checked && !check.disabled);

    const refreshManualRooms = function () {
        const categoryId = String(categorySelect.value || '');
        let visibleCount = 0;
        const mode = addRoomForm.querySelector('.js-add-room-assignment-mode:checked')?.value || 'auto';
        const isManual = mode === 'manual';

        checks.forEach(function (check) {
            const matches = isManual && categoryId !== '' && String(check.dataset.categoryId || '') === categoryId;
            check.disabled = !matches;
            if (!matches) check.checked = false;
            if (matches) visibleCount++;
        });
        options.forEach(function (option) {
            option.classList.toggle('d-none', categoryId === '' || String(option.dataset.categoryId || '') !== categoryId);
        });

        const quantity = Math.max(1, parseInt(quantityInput.value || '1', 10));
        const selectedCount = selectedChecks().length;
        if (manualHelp) {
            manualHelp.textContent = categoryId === ''
                ? 'Chọn hạng phòng trước.'
                : `Có ${visibleCount} phòng hợp lệ. Đã chọn ${selectedCount}/${quantity} phòng.`;
            manualHelp.classList.toggle('text-success', selectedCount === quantity);
            manualHelp.classList.toggle('text-danger', selectedCount > quantity);
        }
    };

    const enforceLimit = function (changedCheck) {
        const quantity = Math.max(1, parseInt(quantityInput.value || '1', 10));
        if (selectedChecks().length > quantity) changedCheck.checked = false;
        refreshManualRooms();
    };

    const refreshMode = function () {
        const mode = addRoomForm.querySelector('.js-add-room-assignment-mode:checked')?.value || 'auto';
        manualWrap.classList.toggle('d-none', mode !== 'manual');
        if (mode !== 'manual') checks.forEach(check => check.checked = false);
        refreshManualRooms();
    };

    checks.forEach(check => check.addEventListener('change', () => enforceLimit(check)));
    modeInputs.forEach(input => input.addEventListener('change', refreshMode));
    categorySelect.addEventListener('change', refreshManualRooms);
    quantityInput.addEventListener('input', function () {
        const quantity = Math.max(1, parseInt(quantityInput.value || '1', 10));
        const chosen = selectedChecks();
        chosen.slice(quantity).forEach(check => check.checked = false);
        refreshManualRooms();
    });

    addRoomForm.addEventListener('submit', function (event) {
        const mode = addRoomForm.querySelector('.js-add-room-assignment-mode:checked')?.value || 'auto';
        if (mode !== 'manual') return;

        const quantity = Math.max(1, parseInt(quantityInput.value || '1', 10));
        const selected = selectedChecks();
        if (selected.length !== quantity) {
            event.preventDefault();
            if (manualHelp) {
                manualHelp.textContent = `Bạn đang chọn ${selected.length}/${quantity} phòng. Hãy tích đúng số phòng cần thêm.`;
                manualHelp.classList.add('text-danger');
            }
            manualWrap.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else if (manualHelp) {
            manualHelp.classList.remove('text-danger');
        }
    });

    refreshMode();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const draftKey = 'booking-checkin-workflow-draft-{{ $booking->id }}';
    const draftMaxAgeMs = 6 * 60 * 60 * 1000;

    const workflowSelectors = [
        '#checkInForm input',
        '#checkInForm select',
        '#checkInForm textarea',
        '[form="checkInForm"]',
        '#stayingGuestsPanel form[data-staying-guest-submit] input',
        '#stayingGuestsPanel form[data-staying-guest-submit] select',
        '#stayingGuestsPanel form[data-staying-guest-submit] textarea',
        '#stayingGuestsPanel [form="batchRoomRepresentativesForm"]',
        '#stayingGuestsPanel .js-use-booker-as-role',
        '#stayingGuestsPanel .js-booker-group-representative'
    ];

    const allDraftFields = () => Array.from(document.querySelectorAll(workflowSelectors.join(',')))
        .filter((field, index, fields) => fields.indexOf(field) === index)
        .filter(field => {
            if (!field || field.disabled) return false;
            const type = String(field.type || '').toLowerCase();
            if (['submit', 'button', 'reset', 'file', 'password'].includes(type)) return false;
            if (field.name === '_token' || field.name === '_method') return false;
            return true;
        });

    const fieldKey = (field) => {
        const roomScope = field.closest('[data-booking-room-representative]')?.getAttribute('data-booking-room-representative') || '';
        const form = field.form || field.closest('form');
        const action = form?.getAttribute('action') || field.getAttribute('form') || '';
        const identity = field.name || field.id || field.getAttribute('data-booking-room-id') || field.className || '';
        const optionValue = ['radio', 'checkbox'].includes(String(field.type || '').toLowerCase()) ? String(field.value || '1') : '';
        return [roomScope, action, identity, optionValue].join('::');
    };

    const captureField = (field) => {
        const type = String(field.type || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
            return { checked: !!field.checked };
        }
        if (field.tagName === 'SELECT' && field.multiple) {
            return { values: Array.from(field.selectedOptions).map(option => option.value) };
        }
        return { value: field.value };
    };

    const saveDraft = () => {
        try {
            const fields = {};
            allDraftFields().forEach(field => {
                fields[fieldKey(field)] = captureField(field);
            });
            sessionStorage.setItem(draftKey, JSON.stringify({
                savedAt: Date.now(),
                scrollY: window.scrollY,
                fields
            }));
        } catch (error) {}
    };

    const restoreField = (field, state) => {
        if (!state) return;
        const type = String(field.type || '').toLowerCase();

        if (type === 'checkbox' || type === 'radio') {
            field.checked = !!state.checked;
            return;
        }

        if (field.tagName === 'SELECT' && field.multiple && Array.isArray(state.values)) {
            Array.from(field.options).forEach(option => {
                option.selected = state.values.includes(option.value);
            });
            return;
        }

        if (!Object.prototype.hasOwnProperty.call(state, 'value')) return;
        const value = state.value ?? '';

        if (field._flatpickr && value) {
            try {
                field._flatpickr.setDate(value, false, 'Y-m-d');
            } catch (error) {
                field.value = value;
            }
        } else {
            field.value = value;
        }
    };

    const restoreDraft = () => {
        try {
            const raw = sessionStorage.getItem(draftKey);
            if (!raw) return;

            const draft = JSON.parse(raw);
            if (!draft || Date.now() - Number(draft.savedAt || 0) > draftMaxAgeMs) {
                sessionStorage.removeItem(draftKey);
                return;
            }

            const fields = draft.fields || {};
            allDraftFields().forEach(field => restoreField(field, fields[fieldKey(field)]));

            // Đồng bộ lại các phần tính toán/validation sau khi khôi phục mà không submit form.
            allDraftFields().forEach(field => {
                if (['hidden', 'checkbox', 'radio'].includes(String(field.type || '').toLowerCase())) return;
                field.dispatchEvent(new Event('input', { bubbles: true }));
            });

            if (Number.isFinite(Number(draft.scrollY))) {
                window.setTimeout(() => window.scrollTo({ top: Number(draft.scrollY), behavior: 'auto' }), 30);
            }
        } catch (error) {
            try { sessionStorage.removeItem(draftKey); } catch (ignored) {}
        }
    };

    document.addEventListener('input', event => {
        if (allDraftFields().includes(event.target)) saveDraft();
    });
    document.addEventListener('change', event => {
        if (allDraftFields().includes(event.target)) saveDraft();
    });

    document.querySelectorAll('#checkInForm, #stayingGuestsPanel form, .js-group-representative-form').forEach(form => {
        form.addEventListener('submit', saveDraft);
    });

    window.addEventListener('beforeunload', saveDraft);

    // Chờ các date picker/script của trang khởi tạo xong rồi mới phục hồi bản nháp.
    window.setTimeout(restoreDraft, 0);
});
</script>
