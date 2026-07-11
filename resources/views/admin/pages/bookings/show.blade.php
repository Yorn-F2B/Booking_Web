@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')

@section('content')
    @php
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
        $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? ''));
        $customerName = $customerName !== '' ? $customerName : 'Chưa có tên';

        $nightCount = max(1, (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400);
        $assignedRooms = $booking->bookingRooms->pluck('room')->filter();
        $assignedRoomIds = $assignedRooms->pluck('id')->values()->toArray();

        $nowVnForCheckInFlow = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $bookingCheckInAtForFlow = $booking->check_in_at
            ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
            : null;
        $priorityCleaningStartAt = $bookingCheckInAtForFlow
            ? $bookingCheckInAtForFlow->copy()->setTime(12, 0, 0)
            : null;
        $earlyCheckInStartAt = $bookingCheckInAtForFlow
            ? $bookingCheckInAtForFlow->copy()->setTime(13, 0, 0)
            : null;
        $standardCheckInAt = $bookingCheckInAtForFlow
            ? $bookingCheckInAtForFlow->copy()->setTime(14, 0, 0)
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
            : '12:00';

        if ($booking->check_out_at) {
            $stayDateChangeCheckOutDateDefault = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->toDateString();
        }

        if (
            $booking->status == 'confirmed'
            && $booking->booking_type != 'hourly'
            && $bookingCheckInAtForFlow
            && $nowVnForCheckInFlow->toDateString() < $bookingCheckInAtForFlow->toDateString()
        ) {
            $isBeforeBookingDateNow = true;
            $beforeBookingDateMessage = 'Khách đang đến trước ngày booking. Đây là đổi ngày lưu trú / mở rộng kỳ ở / tạo booking mới, không phải check-in sớm trong cùng ngày.';
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

            $earlyCheckInMinutesOfDay = ((int) $nowVnForCheckInFlow->format('H')) * 60
                + ((int) $nowVnForCheckInFlow->format('i'));

            if ($earlyCheckInMinutesOfDay < 360) {
                $earlyCheckInPercent = 100;
                $earlyCheckInPolicyText = 'Check-in sớm cùng ngày trước 06:00, phụ thu 100% giá 1 đêm.';
            } elseif ($earlyCheckInMinutesOfDay < 540) {
                $earlyCheckInPercent = 50;
                $earlyCheckInPolicyText = 'Check-in sớm cùng ngày từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.';
            } elseif ($earlyCheckInMinutesOfDay < 660) {
                $earlyCheckInPercent = 20;
                $earlyCheckInPolicyText = 'Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm.';
            } else {
                $earlyCheckInPercent = 0;
                $earlyCheckInPolicyText = 'Check-in sớm cùng ngày từ 11:00 đến trước 14:00, miễn phí nếu phòng đã sẵn sàng.';
            }

            $earlyCheckInFeePreview = round(($earlyCheckInBasePrice * $earlyCheckInPercent) / 100, 0);
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
                ->addMinutes($booking->cleaning_buffer_minutes ?? 60);
        }

        $existingCheckoutLateFeeTotal = $booking->serviceItems
            ->filter(function ($item) {
                return $item->type == 'violation_fee'
                    && $item->name == 'Phụ thu check-out muộn'
                    && !in_array($item->billing_status, ['unused', 'cancelled']);
            })
            ->sum(function ($item) {
                return (float) $item->total;
            });

        $checkoutLateFeePreview = 0;
        $checkoutLateHoursPreview = 0;
        $checkoutLateChargedHours = 0;
        $checkoutLatePolicyText = 'Khách chưa quá giờ check-out, không phát sinh phụ thu trả muộn.';
        $checkoutLateNoteText = '';

        if (
            $booking->status == 'checked_in'
            && $booking->check_out_at
            && $existingCheckoutLateFeeTotal <= 0
        ) {
            $nowVnForCheckout = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
            $plannedCheckOutForPreview = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

            if ($nowVnForCheckout->greaterThan($plannedCheckOutForPreview)) {
                $lateCheckoutMinutes = $plannedCheckOutForPreview->diffInMinutes($nowVnForCheckout);
                $checkoutLateHoursPreview = round($lateCheckoutMinutes / 60, 2);

                if ($booking->booking_type == 'hourly') {
                    if ($lateCheckoutMinutes <= 15) {
                        $checkoutLatePolicyText = 'Booking theo giờ trả muộn không quá 15 phút, miễn phí.';
                    } else {
                        $currentMinutesForCheckout = max(
                            60,
                            \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
                                ->diffInMinutes($plannedCheckOutForPreview)
                        );
                        $currentHoursForCheckout = max(1, $currentMinutesForCheckout / 60);
                        $hourlyRateForCheckout = $roomTotal > 0
                            ? $roomTotal / $currentHoursForCheckout
                            : (float) ($booking->roomCategory->price ?? 0) / 24;
                        $checkoutLateChargedHours = max(1, (int) ceil($lateCheckoutMinutes / 60));
                        $checkoutLateFeePreview = round($hourlyRateForCheckout * $checkoutLateChargedHours, 0);
                        $checkoutLatePolicyText = 'Booking theo giờ trả muộn '
                            . $checkoutLateHoursPreview
                            . ' giờ, tính thêm '
                            . $checkoutLateChargedHours
                            . ' giờ × '
                            . number_format($hourlyRateForCheckout, 0, ',', '.')
                            . 'đ/giờ.';
                    }
                } else {
                    if ($lateCheckoutMinutes <= 60) {
                        $checkoutLatePolicyText = 'Booking qua đêm trả muộn không quá 1 giờ, miễn phí.';
                    } else {
                        $oneNightTotalForCheckout = $booking->bookingRooms->sum(function ($bookingRoom) {
                            return (float) $bookingRoom->price_at_booking;
                        });

                        if ($oneNightTotalForCheckout <= 0) {
                            $oneNightTotalForCheckout = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
                        }

                        if ($lateCheckoutMinutes <= 180) {
                            $checkoutLateFeePreview = round($oneNightTotalForCheckout * 0.3, 0);
                            $checkoutLatePolicyText = 'Booking qua đêm trả muộn trên 1 đến 3 giờ, phụ thu 30% giá 1 đêm.';
                        } elseif ($lateCheckoutMinutes <= 360) {
                            $checkoutLateFeePreview = round($oneNightTotalForCheckout * 0.5, 0);
                            $checkoutLatePolicyText = 'Booking qua đêm trả muộn trên 3 đến 6 giờ, phụ thu 50% giá 1 đêm.';
                        } else {
                            $checkoutLateFeePreview = round($oneNightTotalForCheckout, 0);
                            $checkoutLatePolicyText = 'Booking qua đêm trả muộn trên 6 giờ, tính thêm 1 đêm.';
                        }
                    }
                }

                $checkoutLateNoteText = 'Dự kiến: giờ trả phòng '
                    . $plannedCheckOutForPreview->format('d/m/Y H:i')
                    . ', hiện tại '
                    . $nowVnForCheckout->format('d/m/Y H:i')
                    . '.';
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

        $finalTotal = max(0, $roomTotal + $serviceItemTotal + $approvedInspectionTotal + $checkoutLateFeePreview - $promotionDiscountTotal);
        $remainingTotal = max(0, $finalTotal - (float) $booking->deposit_amount);

        $adminPaymentPaidAmount = (float) $booking->deposit_amount;
        $adminPaymentDepositTarget = round($finalTotal * 0.3, 0);
        $adminPaymentDepositAmount = max(0, min($adminPaymentDepositTarget - $adminPaymentPaidAmount, $remainingTotal));
        $adminPaymentFullAmount = $remainingTotal;
        $adminPaymentDefaultEmail = old('customer_email', $booking->customer->email ?? '');

        $currentAdultCapacity = $booking->bookingRooms->sum(function ($bookingRoom) {
            return $bookingRoom->room->category->adult_capacity ?? 0;
        });

        $currentChildCapacity = $booking->bookingRooms->sum(function ($bookingRoom) {
            return $bookingRoom->room->category->child_capacity ?? 0;
        });

        $roomCategoriesForBookingManage = \App\Models\RoomCategory::where('status', 'active')
            ->withCount([
                'rooms as available_rooms_count' => function ($query) use ($booking) {
                    $query->availableForPeriod(
                        $booking->check_in_at,
                        $booking->check_out_at,
                        $booking->id
                    );
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
                return in_array($inspection->status ?? null, ['confirmed', 'completed', 'approved']);
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

            $timeAvailableRooms = \App\Models\Room::whereIn('room_category_id', $currentAssignedCategoryIds)
                ->whereNotIn('id', $assignedRoomIds)
                ->availableForPeriod($changeRoomCheckInAt, $changeRoomCheckOutAt, $booking->id)
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

        $lateShowEstimatedTotal = (float) $booking->estimated_total;
        $lateShowDepositAmount = (float) $booking->deposit_amount;
        $lateShowIsFullPaidOrFullDeposit = $booking->payment_status === 'paid'
            || ($lateShowEstimatedTotal > 0 && $lateShowDepositAmount >= $lateShowEstimatedTotal);

        $lateShowNightCount = 1;
        if ($lateShowCheckInAt && $lateShowCheckOutAt) {
            $lateShowNightCount = max(
                1,
                $lateShowCheckInAt->copy()->startOfDay()
                    ->diffInDays($lateShowCheckOutAt->copy()->startOfDay())
            );
        }

        $lateShowNoShowLimitAt = null;
        if ($lateShowCheckInAt && $lateShowCheckOutAt) {
            if ($lateShowIsFullPaidOrFullDeposit) {
                $lateShowNoShowLimitAt = $lateShowCheckOutAt->copy();
            } elseif ($booking->booking_type == 'hourly') {
                $lateShowNoShowLimitAt = $lateShowCheckInAt->copy()->addMinutes(30);
            } elseif ($lateShowNightCount <= 1) {
                $lateShowNoShowLimitAt = $lateShowCheckInAt->copy()->setTime(18, 0, 0);
            } else {
                $lateShowNoShowLimitAt = $lateShowCheckInAt->copy()->addDay();
            }
        }

        $lateShowIsAfterNoShowLimit = $lateShowNoShowLimitAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowNoShowLimitAt);
        $lateShowIsPastStayTime = $lateShowCheckOutAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowCheckOutAt);
        $lateShowIsCheckInTooLate = $booking->status == 'confirmed'
            && $lateShowCheckInAt
            && !$lateShowIsFullPaidOrFullDeposit
            && $lateShowNoShowLimitAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowNoShowLimitAt);
        $canCancelNoShowNow = $booking->status == 'confirmed'
            && $lateShowNoShowLimitAt
            && $lateShowNowVn->greaterThanOrEqualTo($lateShowNoShowLimitAt);

        $disableCheckInSubmitNow = $isBeforeBookingDateNow || $lateShowIsPastStayTime || $lateShowIsCheckInTooLate;

        $lateShowHours = 0;
        $showLateCheckInWarning = false;
        $lateShowAlertClass = 'alert-info';
        $lateShowTitle = '';
        $lateShowMessage = '';
        $lateShowSubMessage = '';

        if ($booking->status == 'confirmed' && $isBeforeBookingDateNow) {
            $showLateCheckInWarning = true;
            $lateShowAlertClass = 'alert-danger';
            $lateShowTitle = 'Khách đến trước ngày booking';
            $lateShowMessage = 'Không check-in trực tiếp vào booking hiện tại. Cần đổi ngày lưu trú, mở rộng kỳ ở hoặc tạo booking mới.';
            $lateShowSubMessage = 'Booking hiện bắt đầu lúc '
                . ($lateShowCheckInAt ? $lateShowCheckInAt->format('d/m/Y H:i') : '---')
                . ', hiện tại là '
                . $lateShowNowVn->format('d/m/Y H:i')
                . '.';
        } elseif (
            $booking->status == 'confirmed'
            && $lateShowCheckInAt
            && $lateShowNowVn->greaterThan($lateShowCheckInAt)
        ) {
            $showLateCheckInWarning = true;
            $lateShowMinutes = $lateShowCheckInAt->diffInMinutes($lateShowNowVn);
            $lateShowHours = round($lateShowMinutes / 60, 2);

            if ($lateShowIsPastStayTime) {
                $lateShowAlertClass = 'alert-danger';
                $lateShowTitle = 'Booking đã quá thời gian lưu trú';
                $lateShowMessage = 'Không được check-in booking cũ vì đã qua giờ trả phòng dự kiến.';
                $lateShowSubMessage = 'Nếu khách vẫn muốn ở, hãy hủy/no-show booking cũ và tạo booking mới.';
            } elseif ($lateShowIsFullPaidOrFullDeposit) {
                $lateShowAlertClass = 'alert-info';
                $lateShowTitle = 'Khách check-in muộn nhưng đã thanh toán/cọc 100%';
                $lateShowMessage = 'Khách được check-in bất kỳ giờ nào trong thời gian lưu trú, không phụ thu check-in muộn.';
                $lateShowSubMessage = 'Booking vẫn giữ phòng đến ' . ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : 'giờ trả phòng dự kiến') . '.';
            } elseif (!$lateShowIsAfterNoShowLimit) {
                $lateShowAlertClass = 'alert-warning';
                $lateShowTitle = 'Khách check-in muộn trong hạn giữ phòng';
                if ($booking->booking_type == 'hourly') {
                    $lateShowMessage = 'Khách đặt theo giờ và vẫn trong hạn giữ phòng tạm thời. Cho check-in nếu khách đến, không phụ thu check-in muộn.';
                } elseif ($lateShowNightCount <= 1) {
                    $lateShowMessage = 'Booking 1 đêm, cọc một phần/chưa thanh toán đủ. Giữ phòng đến 18:00 cùng ngày check-in. Trong hạn này cho check-in, không phụ thu.';
                } else {
                    $lateShowMessage = 'Booking nhiều đêm, cọc một phần/chưa thanh toán đủ. Giữ phòng tối đa 1 ngày từ giờ check-in. Trong hạn này cho check-in, không phụ thu.';
                }
                $lateShowSubMessage = 'Hạn giữ phòng: ' . ($lateShowNoShowLimitAt ? $lateShowNoShowLimitAt->format('d/m/Y H:i') : '---') . '.';
            } else {
                $lateShowAlertClass = 'alert-danger';
                $lateShowTitle = 'Đã quá hạn giữ phòng - hủy/no-show';
                $lateShowMessage = 'Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng. Không được check-in booking này nữa.';
                $lateShowSubMessage = 'Hạn giữ phòng: '
                    . ($lateShowNoShowLimitAt ? $lateShowNoShowLimitAt->format('d/m/Y H:i') : '---')
                    . '. Hủy/no-show để giữ cọc nếu có và mở bán lại phòng.';
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

    </style>

    <div class="admin-wrapper booking-detail-page">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.bookings.index') }}">Đặt phòng</a> /
                Chi tiết
            </p>

            <div class="page-topbar">
                <div class="page-title">
                    <h2>Chi tiết đặt phòng</h2>
                    <p>Ưu tiên thao tác theo quy trình, các chức năng phụ được gom lại để dễ nhìn.</p>
                </div>

                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Không thể xử lý yêu cầu:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($showLateCheckInWarning)
                <div class="alert {{ $lateShowAlertClass }} compact-alert border-2">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="fw-bold mb-1">
                                <i class="bx bx-error-circle me-1"></i>
                                {{ $lateShowTitle }}
                            </div>
                            <div class="small">
                                Check-in dự kiến:
                                <strong>{{ $lateShowCheckInAt?->format('d/m/Y H:i') }}</strong> ·
                                Hiện tại:
                                <strong>{{ $lateShowNowVn->format('d/m/Y H:i') }}</strong>
                                @if ($isBeforeBookingDateNow)
                                    · Đến sớm khác ngày booking
                                @elseif ($lateShowHours > 0)
                                    · Trễ khoảng <strong>{{ $lateShowHours }} giờ</strong>
                                @endif
                            </div>
                            <div class="small mt-1">{{ $lateShowMessage }}</div>
                            @if ($lateShowSubMessage)
                                <div class="small text-muted mt-1">{{ $lateShowSubMessage }}</div>
                            @endif
                        </div>

                    </div>
                </div>
            @endif

            <section class="hero-clean">
                <div class="hero-head">
                    <div>
                        <div class="booking-code-label">Mã booking</div>
                        <div class="booking-code-value">{{ $booking->booking_code }}</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <span class="badge-clean {{ $bookingStatusClass }}">
                            {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                        </span>
                        <span class="badge-clean {{ $paymentStatusClass }}">
                            {{ $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status }}
                        </span>
                    </div>
                </div>

                <div class="metric-grid">
                    <div class="metric-card">
                        <span>Khách hàng</span>
                        <strong>{{ $customerName }}</strong>
                    </div>

                    <div class="metric-card">
                        <span>Thời gian</span>
                        <strong>
                            {{ $lateShowCheckInAt?->format('d/m/Y H:i') ?? '---' }}
                            <br>
                            → {{ $lateShowCheckOutAt?->format('d/m/Y H:i') ?? '---' }}
                        </strong>
                    </div>

                    <div class="metric-card">
                        <span>Phòng / khách</span>
                        <strong>
                            {{ $booking->room_quantity }} phòng · {{ $booking->adult_count }} NL /
                            {{ $booking->child_count }} TE
                            <br>
                            <span class="text-muted small">Sức chứa: {{ $currentAdultCapacity }} NL /
                                {{ $currentChildCapacity }} TE</span>
                        </strong>
                    </div>

                    <div class="metric-card">
                        <span>Còn lại cần thu</span>
                        <strong class="text-danger fs-5">{{ number_format($remainingTotal, 0, ',', '.') }}đ</strong>
                    </div>
                </div>
            </section>

            <div class="booking-shell">
                <div class="main-stack">
                    <section class="card-clean">
                        <div class="card-title-clean">
                            <div>
                                <h5>Thao tác chính</h5>
                                <p class="card-subtitle-clean">Chỉ hiển thị thao tác phù hợp với trạng thái hiện tại.</p>
                            </div>
                            <span class="badge-clean {{ $bookingStatusClass }}">
                                {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                            </span>
                        </div>

                        <div class="operation-list">
                            @if ($booking->status == 'confirmed')
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Nhận phòng thực tế</div>
                                            <div class="text-muted small">Nhập số khách thực tế, kiểm tra sức chứa và phụ thu
                                                nếu có.</div>
                                        </div>
                                    </div>

                                    @if ($roomsNotReadyForCheckIn->count() > 0)
                                        <div class="alert alert-warning small mb-3">
                                            <div class="fw-bold mb-1">Phòng gán hiện tại chưa sẵn sàng</div>
                                            {{ $notReadyRoomText }}.
                                            Khi bấm check-in, hệ thống sẽ thử tìm phòng sạch cùng hạng để đổi cho khách.
                                            Nếu không còn phòng sạch cùng hạng, hệ thống sẽ chặn và yêu cầu khách đợi buồng phòng
                                            dọn/kiểm tra xong.
                                        </div>
                                    @endif

                                    @if ($canRequestPriorityCleaning)
                                        <form action="{{ route('admin.bookings.priority-cleaning', $booking->id) }}" method="POST"
                                            class="mb-3"
                                            onsubmit="return confirm('Gửi yêu cầu buồng phòng ưu tiên dọn nhanh cho booking này?')">
                                            @csrf
                                            @method('PATCH')

                                            <div class="soft-note">
                                                <div class="fw-bold mb-1">Khách đến sớm trong khung 12:00–14:00</div>
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
                                        <div class="alert alert-danger small mb-3">
                                            <div class="fw-bold mb-1">Khách đến trước ngày booking</div>
                                            Booking hiện bắt đầu lúc
                                            <strong>{{ $bookingCheckInAtForFlow?->format('d/m/Y H:i') }}</strong>,
                                            còn hiện tại là
                                            <strong>{{ $nowVnForCheckInFlow->format('d/m/Y H:i') }}</strong>.
                                            Không check-in trực tiếp. Hãy đổi ngày lưu trú, mở rộng kỳ ở hoặc tạo booking mới.
                                        </div>

                                        <details class="compact-panel mb-3" open>
                                            <summary>Đổi ngày lưu trú cho khách đến sớm khác ngày</summary>
                                            <div class="compact-panel-body">
                                                <form action="{{ route('admin.bookings.change-stay-dates', $booking->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Xác nhận đổi ngày lưu trú? Hệ thống sẽ kiểm tra trùng phòng và tính lại tiền phòng theo số đêm mới.')">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div class="soft-note mb-3">
                                                        Mặc định hệ thống gợi ý nhận từ thời điểm hiện tại và giữ ngày trả cũ.
                                                        Nếu khách chỉ muốn dời nguyên 1 đêm, sửa lại ngày trả phòng cho phù hợp.
                                                    </div>

                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Ngày nhận mới</label>
                                                            <input type="date" name="new_check_in_date" class="form-control"
                                                                value="{{ old('new_check_in_date', $stayDateChangeCheckInDateDefault) }}"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Giờ nhận mới</label>
                                                            <input type="time" name="new_check_in_time" class="form-control"
                                                                value="{{ old('new_check_in_time', $stayDateChangeCheckInTimeDefault) }}"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Ngày trả mới</label>
                                                            <input type="date" name="new_check_out_date" class="form-control"
                                                                value="{{ old('new_check_out_date', $stayDateChangeCheckOutDateDefault) }}"
                                                                required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small">Giờ trả mới</label>
                                                            <input type="time" name="new_check_out_time" class="form-control"
                                                                value="{{ old('new_check_out_time', $stayDateChangeCheckOutTimeDefault) }}"
                                                                required>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="btn btn-outline-primary w-100 mt-3">
                                                        Kiểm tra trùng phòng và đổi ngày lưu trú
                                                    </button>
                                                </form>
                                            </div>
                                        </details>
                                    @endif

                                    <form action="{{ route('admin.bookings.check-in', $booking->id) }}" method="POST"
                                        id="checkInForm">
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

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label small">Người lớn thực tế</label>
                                                <input type="number" name="actual_adult_count" id="actualAdultCount"
                                                    class="form-control"
                                                    value="{{ old('actual_adult_count', $booking->adult_count) }}" min="1"
                                                    required>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label small">Trẻ em thực tế</label>
                                                <input type="number" name="actual_child_count" id="actualChildCount"
                                                    class="form-control"
                                                    value="{{ old('actual_child_count', $booking->child_count) }}" min="0">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label small">Em bé phát sinh</label>
                                                <input type="number" name="actual_baby_count" id="actualBabyCount"
                                                    class="form-control" value="{{ old('actual_baby_count', 0) }}" min="0">
                                            </div>
                                        </div>

                                        <div id="normalCheckInBox" class="action-summary mb-3">
                                            <div class="action-summary-item">
                                                <span>Sức chứa hiện tại</span>
                                                <strong>{{ $currentAdultCapacity }} NL / {{ $currentChildCapacity }} TE</strong>
                                            </div>
                                            <div class="action-summary-item">
                                                <span>Phòng đang giữ</span>
                                                <strong>{{ $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ') ?: 'Chưa gán' }}</strong>
                                            </div>
                                            <div class="action-summary-item">
                                                <span>Khuyến nghị</span>
                                                <strong>Đủ sức chứa thì check-in bình thường</strong>
                                            </div>
                                        </div>

                                        @if ($isEarlyCheckInNow)
                                            <div class="soft-note mb-3">
                                                Khách đang đến sớm khoảng <strong>{{ $earlyCheckInDurationText }}</strong>.
                                                Nếu phòng sẵn sàng, khi bấm <strong>Xác nhận check-in</strong>, hệ thống sẽ hiện bảng báo giá phụ thu nếu có.
                                            </div>
                                        @endif

                                        <div id="overCapacityBox" class="d-none mb-3">
                                            <div class="alert alert-warning small mb-2">
                                                Số khách thực tế vượt sức chứa. Thu phụ phí tại đây, hoặc dùng mục
                                                <strong>Quản lý phòng</strong> bên dưới để thêm phòng / đổi hạng trước khi
                                                check-in.
                                            </div>

                                            <label class="form-label">Cách xử lý</label>
                                            <select name="over_capacity_action" id="overCapacityAction"
                                                class="form-select mb-3">
                                                <option value="">-- Chọn cách xử lý --</option>
                                                <option value="extra_fee">Khách ở phòng hiện tại và thu phụ phí</option>
                                            </select>

                                            <div id="extraFeeBox" class="d-none border rounded p-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h6 class="fw-bold mb-0">Phụ thu khi check-in</h6>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        id="addExtraFeeRow">
                                                        + Thêm dòng
                                                    </button>
                                                </div>

                                                <div id="extraFeeRows">
                                                    <div class="extra-fee-row border rounded p-3 mb-3 bg-white">
                                                        <div class="row g-2 align-items-end">
                                                            <div class="col-md-5">
                                                                <label class="form-label small">Loại phụ thu</label>
                                                                <select name="extra_service_ids[]"
                                                                    class="form-select extra-service-select">
                                                                    <option value="">-- Chọn phụ thu --</option>
                                                                    @foreach ($extraGuestServices as $service)
                                                                        <option value="{{ $service->id }}"
                                                                            data-price="{{ $service->price }}"
                                                                            data-unit="{{ $service->unit }}">
                                                                            {{ $service->name }} -
                                                                            {{ number_format($service->price, 0, ',', '.') }}đ /
                                                                            {{ $service->unit }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <div class="col-md-2">
                                                                <label class="form-label small">SL</label>
                                                                <input type="number" name="extra_quantities[]"
                                                                    class="form-control extra-quantity-input" value="1" min="1">
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label class="form-label small">Tạm tính</label>
                                                                <input type="text" class="form-control extra-total-text"
                                                                    value="0đ" readonly>
                                                            </div>

                                                            <div class="col-md-2">
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

                                        @if ($booking->check_in_at && $lateShowHours > 0)
                                            <div class="alert {{ $lateShowAlertClass }} small">
                                                <div class="fw-bold mb-1">{{ $lateShowTitle }}</div>

                                                Khách đang trễ khoảng <strong>{{ $lateShowHours }} giờ</strong>
                                                so với mốc check-in
                                                <strong>{{ $lateShowCheckInAt?->format('d/m/Y H:i') }}</strong>.
                                                <br>
                                                <strong>Chính sách:</strong> {{ $lateShowMessage }}

                                                @if ($lateShowSubMessage)
                                                    <br>
                                                    <span class="text-muted">{{ $lateShowSubMessage }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($isBeforeBookingDateNow)
                                            <div class="alert alert-danger small">
                                                Khách đến trước ngày booking nên nút check-in trực tiếp đã bị khóa.
                                                Hãy đổi ngày lưu trú ở khung phía trên rồi mới check-in.
                                            </div>
                                        @elseif ($lateShowIsPastStayTime)
                                            <div class="alert alert-danger small">
                                                Booking đã quá giờ trả phòng dự kiến nên không thể check-in booking cũ.
                                                Hãy hủy/no-show và tạo booking mới nếu khách vẫn muốn ở.
                                            </div>
                                        @elseif ($lateShowIsCheckInTooLate)
                                            <div class="alert alert-danger small">
                                                Khách đã quá hạn giữ phòng
                                                <strong>{{ $lateShowNoShowLimitAt?->format('d/m/Y H:i') }}</strong>.
                                                Không phát sinh phụ thu check-in muộn; quá hạn thì hủy/no-show, không hoàn cọc.
                                            </div>
                                        @endif

                                        <button type="submit" class="btn btn-success w-100" @disabled($disableCheckInSubmitNow)>
                                            <i class="bx bx-log-in-circle me-1"></i>
                                            {{ $disableCheckInSubmitNow ? 'Không thể check-in trực tiếp' : 'Xác nhận check-in' }}
                                        </button>
                                    </form>

                                    @if ($isEarlyCheckInNow)
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
                                                                <span class="info-label">Phụ thu check-in sớm</span>
                                                                <span class="info-value text-danger fs-5">
                                                                    {{ number_format($earlyCheckInFeePreview, 0, ',', '.') }}đ
                                                                </span>
                                                            </div>
                                                            <div class="info-line">
                                                                <span class="info-label">Tổng tiền sau khi cộng</span>
                                                                <span class="info-value text-danger">
                                                                    {{ number_format($earlyCheckInFinalTotalPreview, 0, ',', '.') }}đ
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
                                    @endif

                                    @if ($canCancelNoShowNow)
                                        <form action="{{ route('admin.bookings.cancel-late-arrival', $booking->id) }}" method="POST"
                                            class="mt-2"
                                            onsubmit="return confirm('Xác nhận hủy/no-show? Chỉ dùng khi booking đã quá hạn giữ phòng. Hệ thống sẽ giữ cọc/tiền đã thu nếu có và mở bán lại phòng.')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                Hủy/no-show quá hạn giữ phòng, giữ cọc và mở bán lại phòng
                                            </button>
                                        </form>
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
                                        $extendPreview = session('extend_stay_preview');
                                        $previewDateValue = old(
                                            'new_check_out_date',
                                            $extendPreview['new_check_out_date'] ?? ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : $booking->check_out_date)
                                        );
                                        $previewTimeValue = old(
                                            'new_check_out_time',
                                            $extendPreview['new_check_out_time'] ?? ($lateShowCheckOutAt ? $lateShowCheckOutAt->format('H:i') : '12:00')
                                        );
                                    @endphp

                                    <div class="soft-note mb-3">
                                        <strong>{{ $extendTypeLabel }}</strong> · Phòng
                                        {{ $currentRoomNumbersForExtend ?: '---' }} ·
                                        Check-out hiện tại:
                                        {{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '---' }}
                                    </div>

                                    <form action="{{ route('admin.bookings.extend-stay.preview', $booking->id) }}"
                                        method="POST">
                                        @csrf

                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Ngày trả phòng mới</label>
                                                <input type="date" name="new_check_out_date" class="form-control"
                                                    min="{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('Y-m-d') : date('Y-m-d') }}"
                                                    value="{{ $previewDateValue }}" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Giờ trả phòng mới</label>
                                                <input type="text" name="new_check_out_time" id="extendCheckOutTime"
                                                    class="form-control" value="{{ $previewTimeValue }}"
                                                    placeholder="Ví dụ: 14:00" required>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-outline-primary w-100">
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

                                        <div class="alert {{ $previewAlertClass }} mt-3 mb-0">
                                            <h6 class="fw-bold mb-2">{{ $extendPreview['title'] ?? 'Kết quả kiểm tra gia hạn' }}
                                            </h6>
                                            <div class="small mb-2">
                                                <strong>Khung giờ:</strong> {{ $extendPreview['period_text'] ?? '---' }}<br>
                                                <strong>Phí dự kiến:</strong>
                                                <span
                                                    class="fw-bold text-danger">{{ $extendPreview['fee_text'] ?? '0đ' }}</span><br>
                                                <strong>Cách tính:</strong> {{ $extendPreview['policy_text'] ?? '---' }}
                                            </div>
                                            <div class="small">{{ $extendPreview['message'] ?? '' }}</div>

                                            @if (!empty($extendPreview['conflicts']))
                                                <div class="border rounded bg-white p-2 mt-2 small">
                                                    <strong>Booking bị giao thời gian:</strong>
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

                                            @if (($extendPreview['status'] ?? '') !== 'blocked')
                                                <form action="{{ route('admin.bookings.extend-stay', $booking->id) }}" method="POST"
                                                    class="mt-3"
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
                            @elseif ($booking->status == 'checked_in' && $allInspectionsConfirmed)
                                <div class="operation-row">
                                    <div class="operation-row-head">
                                        <div>
                                            <div class="operation-row-title">Chốt phí và check-out</div>
                                            <div class="text-muted small">
                                                Hệ thống gom tiền phòng, dịch vụ khách gọi thêm/phụ thu, dịch vụ tại phòng đã duyệt, hư hại đã duyệt và phụ thu trả
                                                phòng muộn nếu có.
                                            </div>
                                        </div>
                                    </div>

                                    <form action="{{ route('admin.bookings.check-out', $booking->id) }}" method="POST"
                                        onsubmit="return confirm('Xác nhận đã thu đủ tiền và check-out booking này?')">
                                        @csrf
                                        @method('PATCH')

                                        @if ($checkoutLateFeePreview > 0)
                                            <div class="alert alert-danger small mb-3">
                                                <div class="fw-bold mb-1">Phát sinh phụ thu check-out muộn</div>
                                                Khách đã quá giờ trả phòng khoảng
                                                <strong>{{ $checkoutLateHoursPreview }} giờ</strong>.
                                                <br>
                                                <strong>Chính sách:</strong> {{ $checkoutLatePolicyText }}
                                                <br>
                                                <strong>Số tiền phụ thu dự kiến:</strong>
                                                <span
                                                    class="fw-bold">{{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ</span>
                                                <br>
                                                <span class="text-muted">{{ $checkoutLateNoteText }}</span>

                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" value="1"
                                                        name="checkout_late_fee_confirm" id="checkoutLateFeeConfirm" required>
                                                    <label class="form-check-label fw-bold" for="checkoutLateFeeConfirm">
                                                        Khách đã chấp nhận phụ thu check-out muộn
                                                        {{ number_format($checkoutLateFeePreview, 0, ',', '.') }}đ
                                                    </label>
                                                </div>
                                            </div>
                                        @else
                                            <div class="soft-note mb-3">
                                                {{ $checkoutLatePolicyText }}
                                                @if ($existingCheckoutLateFeeTotal > 0)
                                                    Khoản phụ thu check-out muộn đã được ghi nhận:
                                                    <strong>{{ number_format($existingCheckoutLateFeeTotal, 0, ',', '.') }}đ</strong>.
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
                                                            <td>Phụ thu check-out muộn dự kiến</td>
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
                                                        <td>Tiền cọc</td>
                                                        <td class="text-end fw-bold">
                                                            -{{ number_format((float) $booking->deposit_amount, 0, ',', '.') }}đ
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

                                        <div class="checkout-payment-confirm-box mb-3" id="checkoutPaymentConfirmBox"
                                            data-base-remaining="{{ (float) $remainingTotal }}">
                                            @if ($remainingTotal > 0)
                                                <div class="alert alert-warning small mb-3" id="checkoutPaymentNotice">
                                                    <div class="fw-bold mb-1">Booking còn khoản chưa thanh toán</div>
                                                    Còn phải thu
                                                    <strong>{{ number_format($remainingTotal, 0, ',', '.') }}đ</strong>.
                                                    Lễ tân cần kiểm tra khách đã thanh toán ngoài thực tế, chọn phương thức
                                                    thanh toán và tick xác nhận thì mới được check-out.
                                                </div>
                                            @else
                                                <div class="alert alert-success small mb-3" id="checkoutPaymentNotice">
                                                    <div class="fw-bold mb-1">Booking đã thanh toán đủ theo hệ thống</div>
                                                    Nếu không nhập thêm phí phát sinh bên dưới, có thể check-out bình thường.
                                                    Nếu có thêm phí phát sinh, hệ thống sẽ yêu cầu chọn phương thức thanh toán
                                                    cho khoản phát sinh đó.
                                                </div>
                                            @endif

                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">
                                                        Phương thức thanh toán khi check-out
                                                        <span class="text-danger checkout-payment-required-mark {{ $remainingTotal > 0 ? '' : 'd-none' }}">*</span>
                                                    </label>

                                                    <div class="payment-method-options">
                                                        <div class="form-check">
                                                            <input class="form-check-input checkout-payment-method" type="radio"
                                                                name="checkout_payment_method" id="checkoutPaymentCash"
                                                                value="cash"
                                                                {{ old('checkout_payment_method') == 'cash' ? 'checked' : '' }}
                                                                {{ $remainingTotal > 0 ? 'required' : '' }}>
                                                            <label class="form-check-label" for="checkoutPaymentCash">
                                                                Tiền mặt tại quầy
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input checkout-payment-method" type="radio"
                                                                name="checkout_payment_method" id="checkoutPaymentBank"
                                                                value="bank_transfer"
                                                                {{ old('checkout_payment_method') == 'bank_transfer' ? 'checked' : '' }}
                                                                {{ $remainingTotal > 0 ? 'required' : '' }}>
                                                            <label class="form-check-label" for="checkoutPaymentBank">
                                                                Chuyển khoản tại quầy
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Xác nhận thu tiền</label>
                                                    <div class="form-check border rounded-3 p-3 h-100 bg-light">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                            name="checkout_payment_confirm" id="checkoutPaymentConfirm"
                                                            {{ old('checkout_payment_confirm') == '1' ? 'checked' : '' }}
                                                            {{ $remainingTotal > 0 ? 'required' : '' }}>
                                                        <label class="form-check-label fw-bold" for="checkoutPaymentConfirm">
                                                            Đã kiểm tra và đã thu đủ khoản còn lại ngoài thực tế
                                                        </label>
                                                        <div class="text-muted small mt-1" id="checkoutPaymentConfirmHint">
                                                            {{ $remainingTotal > 0 ? 'Bắt buộc vì booking còn tiền phải thu.' : 'Không bắt buộc nếu booking không phát sinh thêm tiền.' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <details class="compact-panel mb-3">
                                            <summary>Thêm phí phát sinh khác khi check-out</summary>
                                            <div class="compact-panel-body">
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Tên khoản phí</label>
                                                        <input type="text" name="checkout_extra_name" class="form-control"
                                                            placeholder="Ví dụ: Mất thẻ phòng">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Số tiền</label>
                                                        <input type="number" name="checkout_extra_amount" class="form-control"
                                                            min="0" step="1000" value="0">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small">Ghi chú</label>
                                                        <input type="text" name="checkout_extra_note" class="form-control"
                                                            placeholder="Ghi chú nếu có">
                                                    </div>
                                                </div>
                                                <div class="text-muted small mt-2">
                                                    Khoản này sẽ được ghi vào Dịch vụ / phụ thu và cộng vào tổng tiền khi
                                                    check-out.
                                                </div>
                                            </div>
                                        </details>

                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="bx bx-log-out-circle me-1"></i>
                                            Đã thu đủ tiền - Check-out
                                        </button>
                                    </form>
                                </div>
                            @elseif ($booking->status == 'checked_in' && $hasInspection && !$allInspectionsConfirmed)
                                <div class="alert alert-warning mb-0">
                                    Phòng đã được yêu cầu kiểm tra. Cần quản lý tầng báo cáo và admin duyệt xong mới được
                                    check-out.
                                </div>
                            @else
                                <div class="soft-note">
                                    Booking hiện không có thao tác nghiệp vụ chính cần xử lý ở bước này.
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
                                    ],
                                    'event_discount' => [
                                        'label' => 'Mã sự kiện',
                                        'badge' => 'bg-success',
                                        'hint' => 'Mã theo chiến dịch, mùa lễ, combo hoặc chương trình bán hàng.',
                                    ],
                                    'conditional_discount' => [
                                        'label' => 'Mã điều kiện',
                                        'badge' => 'bg-warning text-dark',
                                        'hint' => 'Mã chỉ áp dụng khi booking đạt điều kiện như tổng tiền, số đêm, số phòng hoặc lịch sử khách.',
                                    ],
                                    'support_discount' => [
                                        'label' => 'Mã hỗ trợ khách',
                                        'badge' => 'bg-danger',
                                        'hint' => 'Dùng cho nghiệp vụ hỗ trợ: khách đến sớm, phòng chưa sẵn sàng, cần đổi phòng/đổi hạng, khách chờ lâu hoặc có phát sinh bất tiện. Không tách riêng từng loại hỗ trợ.',
                                    ],
                                ];

                                $availablePromotionGroups = collect($availablePromotions ?? collect())->groupBy('promotion_type');
                            @endphp

                            @if ($booking->bookingPromotions->count() > 0)
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-clean align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Mã</th>
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
                                    <form action="{{ route('admin.bookings.promotions.store', $booking->id) }}"
                                        method="POST"
                                        data-booking-promotion-form>
                                        @csrf

                                        @foreach ($promotionTypeDisplayConfig as $promotionType => $typeConfig)
                                            @php
                                                $groupPromotions = $availablePromotionGroups->get($promotionType, collect());
                                            @endphp

                                            @if ($groupPromotions->count() > 0)
                                                <div class="mb-3">
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
                                                                        data-type="{{ $promotion->promotion_type }}"
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
                                                                        </div>

                                                                        @if ($promotion->serviceOffers->count() > 0)
                                                                            <div class="promotion-meta mt-1 text-success">
                                                                                Dịch vụ ưu đãi:
                                                                                {{ $promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ') }}
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

                                            checks.forEach(function (checkbox) {
                                                checkbox.addEventListener('change', refreshNoteRequiredState);
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
                        <details class="compact-panel">
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
                                    $roomUpgradePromotionOptions = collect($availablePromotions ?? collect())
                                        ->filter(fn ($promotion) => $promotion->roomUpgradeOffers->count() > 0)
                                        ->values();
                                @endphp

                                <datalist id="roomUpgradePromotionCodes">
                                    @foreach ($roomUpgradePromotionOptions as $promotion)
                                        <option value="{{ $promotion->code }}">{{ $promotion->name }}</option>
                                    @endforeach
                                </datalist>

                                <div class="form-mini-grid">
                                    <form action="{{ route('admin.bookings.add-room-to-booking', $booking->id) }}" method="POST"
                                        class="mini-form-box" onsubmit="return confirm('Xác nhận thêm phòng vào booking này?')">
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

                                        <button type="submit" class="btn btn-outline-primary w-100">Thêm phòng</button>
                                    </form>

                                    <form action="{{ route('admin.bookings.change-one-room-category', $booking->id) }}"
                                        method="POST" class="mini-form-box"
                                        onsubmit="return confirm('Xác nhận đổi hạng cho 1 phòng trong booking này?')">
                                        @csrf
                                        @method('PATCH')

                                        <h6>Đổi 1 phòng</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Phòng cần đổi</label>
                                            <select name="booking_room_id" class="form-select" required>
                                                <option value="">-- Chọn phòng --</option>
                                                @foreach ($booking->bookingRooms as $bookingRoom)
                                                    @if ($bookingRoom->room)
                                                        <option value="{{ $bookingRoom->id }}">
                                                            Phòng {{ $bookingRoom->room->room_number }} -
                                                            {{ $bookingRoom->room->category->name ?? 'Không rõ hạng' }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng mới</label>
                                            <select name="new_room_category_id" class="form-select" required>
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
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="change_category_reason" class="form-control"
                                                placeholder="Ví dụ: Khách muốn nâng hạng 1 phòng">
                                        </div>



                                        <div class="mb-3">
                                            <label class="form-label small">Xử lý tiền chênh</label>
                                            <select name="upgrade_payment_action" class="form-select">
                                                <option value="guest_pay">Khách trả toàn bộ tiền chênh</option>
                                                <option value="incident_support">Mã hỗ trợ sự cố - khách không trả thêm</option>
                                                <option value="paid_upsell">Mã điều kiện upsell - khách trả phần còn lại</option>
                                            </select>
                                            <div class="small text-muted mt-1">
                                                Chỉ nhập mã khi chọn hỗ trợ sự cố hoặc upsell nâng hạng.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Mã nâng hạng</label>
                                            <input type="text" name="room_upgrade_promotion_code" class="form-control text-uppercase"
                                                list="roomUpgradePromotionCodes" placeholder="VD: INCIDENT_UPGRADE_FULL hoặc UPGRADE20">
                                        </div>

                                        <button type="submit" class="btn btn-outline-warning w-100">Đổi 1 phòng</button>
                                    </form>

                                    <form action="{{ route('admin.bookings.change-all-room-category', $booking->id) }}"
                                        method="POST" class="mini-form-box"
                                        onsubmit="return confirm('Đổi toàn bộ các phòng hiện tại sang hạng mới?')">
                                        @csrf
                                        @method('PATCH')

                                        <h6>Đổi toàn bộ</h6>

                                        <div class="mb-3">
                                            <label class="form-label small">Hạng mới</label>
                                            <select name="new_room_category_id" class="form-select" required>
                                                <option value="">-- Chọn hạng --</option>
                                                @foreach ($roomCategoriesForBookingManage as $category)
                                                    <option value="{{ $category->id }}" @disabled($category->available_rooms_count < $booking->room_quantity)>
                                                        {{ $category->name }} - Còn {{ $category->available_rooms_count }} - Cần
                                                        {{ $booking->room_quantity }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Lý do</label>
                                            <input type="text" name="change_category_reason" class="form-control"
                                                placeholder="Ví dụ: Khách muốn đổi toàn bộ hạng phòng">
                                        </div>



                                        <div class="mb-3">
                                            <label class="form-label small">Xử lý tiền chênh</label>
                                            <select name="upgrade_payment_action" class="form-select">
                                                <option value="guest_pay">Khách trả toàn bộ tiền chênh</option>
                                                <option value="incident_support">Mã hỗ trợ sự cố - khách không trả thêm</option>
                                                <option value="paid_upsell">Mã điều kiện upsell - khách trả phần còn lại</option>
                                            </select>
                                            <div class="small text-muted mt-1">
                                                Chỉ nhập mã khi chọn hỗ trợ sự cố hoặc upsell nâng hạng.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small">Mã nâng hạng</label>
                                            <input type="text" name="room_upgrade_promotion_code" class="form-control text-uppercase"
                                                list="roomUpgradePromotionCodes" placeholder="VD: INCIDENT_UPGRADE_FULL hoặc UPGRADE20">
                                        </div>

                                        <button type="submit" class="btn btn-outline-danger w-100">Đổi toàn bộ</button>
                                    </form>
                                </div>
                            </div>
                        </details>
                    @endif

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <div>
                                <h5>Phòng đang gán</h5>
                                <p class="card-subtitle-clean">Danh sách phòng thật của booking.</p>
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
                            <div class="alert alert-warning mb-3">Booking này chưa có phòng thật được gán.</div>
                        @endif

                        @if (in_array($booking->status, ['pending', 'confirmed', 'checked_in']))
                            <details class="compact-panel">
                                <summary>Đổi phòng do sự cố / bảo trì</summary>
                                <div class="compact-panel-body">
                                    <form action="{{ route('admin.bookings.change-room', $booking->id) }}" method="POST">
                                        @csrf

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Phòng bị sự cố</label>
                                                <select name="old_room_id" class="form-select" required>
                                                    <option value="">-- Chọn phòng đang gán --</option>
                                                    @foreach ($assignedRooms as $assignedRoom)
                                                        <option value="{{ $assignedRoom->id }}">
                                                            Phòng {{ $assignedRoom->room_number }} - Tầng
                                                            {{ $assignedRoom->floor_number ?? '---' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Phòng thay thế cùng hạng</label>
                                                <select name="new_room_id" class="form-select" required>
                                                    <option value="">-- Chọn phòng trống --</option>
                                                    @foreach ($timeAvailableRooms as $room)
                                                        <option value="{{ $room->id }}">
                                                            Phòng {{ $room->room_number }} - Tầng {{ $room->floor_number ?? '---' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Trạng thái phòng cũ</label>
                                                <select name="old_room_new_status" class="form-select" required>
                                                    <option value="maintenance">Bảo trì</option>
                                                    <option value="cleaning">Cần dọn</option>
                                                    <option value="available">Trống</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Lý do đổi phòng</label>
                                                <input type="text" name="change_reason" class="form-control"
                                                    placeholder="Ví dụ: Hỏng điều hòa, khóa lỗi..." required>
                                            </div>
                                        </div>

                                        @if ($timeAvailableRooms->count() == 0)
                                            <div class="alert alert-warning small mt-3 mb-0">
                                                Không còn phòng cùng hạng trống trong khoảng thời gian của booking này.
                                            </div>
                                        @endif

                                        <button type="submit" class="btn btn-warning w-100 mt-3"
                                            onclick="return confirm('Xác nhận đổi phòng cho booking này?')">
                                            Đổi phòng
                                        </button>
                                    </form>
                                </div>
                            </details>
                        @endif
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <div>
                                <h5>Dịch vụ / phụ thu</h5>
                                <p class="card-subtitle-clean">Dịch vụ khách gọi thêm được tính ngay. Dịch vụ có sẵn tại phòng và hư hại chỉ tính sau khi admin duyệt.</p>
                            </div>
                        </div>

                        @if ($booking->serviceItems->count() > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-clean align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tên khoản thu</th>
                                            <th>Loại</th>
                                            <th>Đơn giá</th>
                                            <th>SL</th>
                                            <th>Dùng</th>
                                            <th>Thành tiền</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($booking->serviceItems as $item)
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->name }}</strong>
                                                    @if ($item->note)
                                                        <div class="text-muted small">{{ $item->note }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (in_array($item->type, ['violation_fee', 'policy_violation_fee', 'occupancy_fee']))
                                                        <span class="badge-clean status-muted">Phụ thu</span>
                                                    @elseif ($item->type == 'minibar')
                                                        <span class="badge-clean status-warning">Khách gọi thêm</span>
                                                    @elseif ($item->type == 'damage_fee')
                                                        <span class="badge-clean status-cancelled">Hư hại</span>
                                                    @else
                                                        <span class="badge-clean status-info">Dịch vụ</span>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                                                <td style="min-width: 120px;">
                                                    @if ($canEditServiceItems && in_array($item->type, ['service', 'minibar']))
                                                        <form
                                                            action="{{ route('admin.bookings.service-items.update', [$booking->id, $item->id]) }}"
                                                            method="POST" class="d-flex gap-1 align-items-center">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="number" name="quantity" class="form-control form-control-sm"
                                                                value="{{ $item->quantity }}" min="1" style="width: 72px;">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">Lưu</button>
                                                        </form>
                                                    @else
                                                        {{ $item->quantity }}
                                                    @endif
                                                </td>
                                                <td>{{ $item->used_quantity ?? $item->quantity }}</td>
                                                <td class="fw-bold text-danger">{{ number_format($item->total, 0, ',', '.') }}đ</td>
                                                <td class="text-end">
                                                    @if ($canEditServiceItems && in_array($item->type, ['service', 'minibar']))
                                                        <form
                                                            action="{{ route('admin.bookings.service-items.destroy', [$booking->id, $item->id]) }}"
                                                            method="POST" onsubmit="return confirm('Xóa dịch vụ này khỏi booking?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="soft-note mb-3">Chưa có dịch vụ hoặc phụ thu phát sinh.</div>
                        @endif

                        @if ($approvedInspectionItems->count() > 0)
                            <details class="compact-panel mb-3">
                                <summary>Dịch vụ tại phòng / hư hại đã duyệt từ kiểm tra phòng</summary>
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
                            <details class="compact-panel">
                                <summary>Thêm dịch vụ / minibar / xe cộ</summary>
                                <div class="compact-panel-body">
                                    <form action="{{ route('admin.bookings.service-items.store', $booking->id) }}" method="POST"
                                        id="multiServiceForm">
                                        @csrf

                                        <div id="serviceRows">
                                            <div class="service-input-row border rounded p-3 mb-3 bg-light">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-5">
                                                        <label class="form-label small">Dịch vụ</label>
                                                        <select name="services[0][service_id]"
                                                            class="form-select service-item-select" required>
                                                            <option value="">-- Chọn dịch vụ --</option>
                                                            @foreach ($availableServices as $service)
                                                                <option value="{{ $service->id }}"
                                                                    data-price="{{ $service->price }}"
                                                                    data-unit="{{ $service->unit }}"
                                                                    data-group="{{ $service->service_group ?? 'general' }}">
                                                                    {{ $service->name }} -
                                                                    {{ $service->group_label ?? ($service->type == 'minibar' ? 'Khách gọi thêm' : 'Dịch vụ') }} -
                                                                    {{ number_format($service->price, 0, ',', '.') }}đ /
                                                                    {{ $service->unit }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <label class="form-label small">SL</label>
                                                        <input type="number" name="services[0][quantity]"
                                                            class="form-control service-item-quantity" value="1" min="1"
                                                            required>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="form-label small">Tạm tính</label>
                                                        <input type="text" class="form-control service-item-total-text"
                                                            value="0đ" readonly>
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
                                            <span>Tổng tạm tính, sẽ cộng ngay vào booking</span>
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
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <div>
                                <h5>Lịch sử thao tác</h5>
                                <p class="card-subtitle-clean">Theo dõi các bước đã xử lý với booking.</p>
                            </div>
                        </div>

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
                    </section>
                </div>

                <aside class="side-stack">
                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Thanh toán</h5>
                        </div>

                        <div class="info-list">
                            <div class="info-line">
                                <span class="info-label">Tiền phòng</span>
                                <span class="info-value">{{ number_format($roomTotal, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Dịch vụ khách gọi thêm / phụ thu</span>
                                <span class="info-value {{ $serviceItemTotal > 0 ? 'text-danger' : '' }}">
                                    {{ $serviceItemTotal > 0 ? '+' : '' }}{{ number_format((float) $serviceItemTotal, 0, ',', '.') }}đ
                                </span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Dịch vụ tại phòng duyệt</span>
                                <span class="info-value {{ $approvedMinibarTotal > 0 ? 'text-danger' : '' }}">
                                    {{ $approvedMinibarTotal > 0 ? '+' : '' }}{{ number_format((float) $approvedMinibarTotal, 0, ',', '.') }}đ
                                </span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Phí hư hại duyệt</span>
                                <span class="info-value {{ $approvedDamageTotal > 0 ? 'text-danger' : '' }}">
                                    {{ $approvedDamageTotal > 0 ? '+' : '' }}{{ number_format((float) $approvedDamageTotal, 0, ',', '.') }}đ
                                </span>
                            </div>
                            @if ($checkoutLateFeePreview > 0)
                                <div class="info-line">
                                    <span class="info-label">Dự kiến trả muộn</span>
                                    <span class="info-value text-danger">
                                        +{{ number_format((float) $checkoutLateFeePreview, 0, ',', '.') }}đ
                                    </span>
                                </div>
                            @endif
                            <div class="info-line">
                                <span class="info-label">Tiền cọc</span>
                                <span class="info-value">-{{ number_format($booking->deposit_amount, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Còn lại</span>
                                <span
                                    class="info-value text-danger fs-5">{{ number_format($remainingTotal, 0, ',', '.') }}đ</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Trạng thái</span>
                                <span class="info-value">
                                    <span class="badge-clean {{ $paymentStatusClass }}">
                                        {{ $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        @if (!in_array($booking->status, ['canceled', 'cancelled', 'no_show']) && $booking->payment_status != 'paid')
                            <hr class="my-3">

                            @if (session('admin_vnpay_payment_url'))
                                <div class="alert alert-info small mb-3">
                                    <div class="fw-bold mb-1">Đã tạo link yêu cầu thanh toán VNPay</div>
                                    Link này là link của website mình. Khi khách bấm vào, hệ thống mới tạo phiên VNPay mới, nên không bị kẹt link cổng VNPay cũ đã hết hạn. Nếu email chưa tới khách, có thể copy link này gửi thủ công:
                                    <a href="{{ session('admin_vnpay_payment_url') }}" target="_blank" class="fw-bold">
                                        Mở link yêu cầu thanh toán
                                    </a>
                                </div>
                            @endif

                            <div class="fw-bold mb-2">Thanh toán</div>

                            <div class="soft-note mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <span>Tổng cần thanh toán</span>
                                    <strong>{{ number_format($finalTotal, 0, ',', '.') }}đ</strong>
                                </div>
                                <div class="d-flex justify-content-between gap-2 mt-1">
                                    <span>Đã thu</span>
                                    <strong>{{ number_format($adminPaymentPaidAmount, 0, ',', '.') }}đ</strong>
                                </div>
                                <div class="d-flex justify-content-between gap-2 mt-1">
                                    <span>Còn lại cần thu</span>
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

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <select name="payment_type" id="adminDirectPaymentType"
                                                class="form-select form-select-sm" required>
                                                <option value="deposit_30" data-amount="{{ $adminPaymentDepositAmount }}"
                                                    @disabled($adminPaymentDepositAmount <= 0)>
                                                    Thu cọc 30% - {{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ
                                                </option>
                                                <option value="full_100" data-amount="{{ $adminPaymentFullAmount }}">
                                                    Thu đủ còn lại - {{ number_format($adminPaymentFullAmount, 0, ',', '.') }}đ
                                                </option>
                                                <option value="custom" data-amount="0">
                                                    Thu số tiền khác
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <div class="soft-note" id="adminDirectPaymentSpeakText">
                                                Chọn kiểu thu tiền để hệ thống hiện câu nói cho lễ tân.
                                            </div>
                                        </div>

                                        <div class="col-12 d-none" id="adminDirectCustomAmountBox">
                                            <input type="number" name="amount" id="adminDirectCustomAmount"
                                                class="form-control form-control-sm" min="1000" step="1000"
                                                max="{{ $remainingTotal }}"
                                                placeholder="Nhập số tiền khách trả, ví dụ 500000">
                                            <div class="text-muted small mt-1">
                                                Số tiền nhập không được lớn hơn số còn lại:
                                                {{ number_format($remainingTotal, 0, ',', '.') }}đ.
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
                                                    Gửi yêu cầu cọc 30% - {{ number_format($adminPaymentDepositAmount, 0, ',', '.') }}đ
                                                </option>
                                                <option value="full_100" data-amount="{{ $adminPaymentFullAmount }}">
                                                    Gửi yêu cầu thanh toán đủ còn lại - {{ number_format($adminPaymentFullAmount, 0, ',', '.') }}đ
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
                                                        ? (int) config('vnpay.admin_request_expire_minutes', 1440)
                                                        : (int) config('vnpay.expire_minutes', 30)
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
                                <span class="info-value">{{ $booking->customer->phone ?? '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ $booking->customer->email ?? '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">CCCD</span>
                                <span class="info-value">{{ $booking->customer->cccd ?? '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Địa chỉ</span>
                                <span class="info-value">{{ $booking->customer->address ?? '---' }}</span>
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
                                <span class="info-value">{{ $booking->roomCategory->name ?? 'Không xác định' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Loại đặt</span>
                                <span
                                    class="info-value">{{ $booking->booking_type == 'hourly' ? 'Theo giờ' : 'Qua đêm' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Nhận phòng</span>
                                <span
                                    class="info-value">{{ $lateShowCheckInAt ? $lateShowCheckInAt->format('d/m/Y H:i') : '---' }}</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Trả phòng</span>
                                <span
                                    class="info-value">{{ $lateShowCheckOutAt ? $lateShowCheckOutAt->format('d/m/Y H:i') : '---' }}</span>
                            </div>
                            @if ($booking->booking_type == 'hourly')
                                <div class="info-line">
                                    <span class="info-label">Dọn phòng đến</span>
                                    <span
                                        class="info-value">{{ $hourlyCleaningUntil ? $hourlyCleaningUntil->format('d/m/Y H:i') : '---' }}</span>
                                </div>
                            @endif
                            <div class="info-line">
                                <span
                                    class="info-label">{{ $booking->booking_type == 'hourly' ? 'Thời lượng' : 'Số đêm' }}</span>
                                <span class="info-value">
                                    @if ($booking->booking_type == 'hourly')
                                        {{ $booking->check_in_at && $booking->check_out_at ? $booking->check_in_at->diffInHours($booking->check_out_at) . ' giờ' : '---' }}
                                    @else
                                        {{ $nightCount }} đêm
                                    @endif
                                </span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Số khách</span>
                                <span class="info-value">{{ $booking->adult_count }} NL / {{ $booking->child_count }}
                                    TE</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Số phòng</span>
                                <span class="info-value">{{ $booking->room_quantity }} phòng</span>
                            </div>
                            <div class="info-line">
                                <span class="info-label">Tạo lúc</span>
                                <span
                                    class="info-value">{{ $booking->created_at ? $booking->created_at->format('d/m/Y H:i') : '---' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="card-clean">
                        <div class="card-title-clean">
                            <h5>Ghi chú nội bộ</h5>
                        </div>

                        <form action="{{ route('admin.bookings.update-note', $booking->id) }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <textarea name="note" rows="5" class="form-control"
                                placeholder="Nhập ghi chú nội bộ cho booking nếu có">{{ old('note', $booking->note) }}</textarea>

                            @error('note')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function numberFormat(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            const adultCapacityInput = document.getElementById('adultCapacity');
            const childCapacityInput = document.getElementById('childCapacity');
            const actualAdultInput = document.getElementById('actualAdultCount');
            const actualChildInput = document.getElementById('actualChildCount');
            const normalCheckInBox = document.getElementById('normalCheckInBox');
            const overCapacityBox = document.getElementById('overCapacityBox');
            const overCapacityAction = document.getElementById('overCapacityAction');
            const extraFeeBox = document.getElementById('extraFeeBox');

            const checkInForm = document.getElementById('checkInForm');
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
                const isOver = actualAdult > adultCapacity || actualChild > childCapacity;

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
                const serviceSelect = row.querySelector('.extra-service-select');
                const quantityInput = row.querySelector('.extra-quantity-input');
                const removeButton = row.querySelector('.remove-extra-fee-row');

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

                    if (select) {
                        select.name = `services[${index}][service_id]`;
                    }

                    if (quantity) {
                        quantity.name = `services[${index}][quantity]`;
                    }

                    if (note) {
                        note.name = `services[${index}][note]`;
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

                    if (!select || !quantityInput || !totalText) {
                        return;
                    }

                    const selectedOption = select.options[select.selectedIndex];
                    const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
                    const quantity = Math.max(1, parseInt(quantityInput.value || 1));
                    const total = price * quantity;

                    totalText.value = numberFormat(total);
                    grandTotal += total;
                });

                multiServiceTotalText.textContent = numberFormat(grandTotal);
            }

            function bindServiceRow(row) {
                const select = row.querySelector('.service-item-select');
                const quantityInput = row.querySelector('.service-item-quantity');
                const removeButton = row.querySelector('.remove-service-row');

                if (select) {
                    select.addEventListener('change', updateMultiServiceTotals);
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', updateMultiServiceTotals);
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
                    adminDirectPaymentSubmit.innerHTML = '<i class="bx bx-money-withdraw me-1"></i> Ghi nhận đã thu ' + amountText;
                    message = 'Lễ tân nói với khách: Anh/chị thanh toán ' + amountText + ' bằng ' + methodLabel + ' ạ.';
                } else if (type === 'deposit_30') {
                    adminDirectPaymentSubmit.innerHTML = '<i class="bx bx-money-withdraw me-1"></i> Ghi nhận đã thu ' + formatMoneyVn(amount);
                    message = 'Lễ tân nói với khách: Anh/chị cần cọc 30% là ' + formatMoneyVn(amount) + ' để giữ phòng ạ.';
                } else {
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
                const purpose = type === 'deposit_30' ? 'cọc 30%' : 'thanh toán số tiền còn lại';
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

            const checkoutPaymentBox = document.getElementById('checkoutPaymentConfirmBox');
            const checkoutExtraAmountInput = document.querySelector('input[name="checkout_extra_amount"]');
            const checkoutPaymentMethods = document.querySelectorAll('.checkout-payment-method');
            const checkoutPaymentConfirm = document.getElementById('checkoutPaymentConfirm');
            const checkoutPaymentConfirmHint = document.getElementById('checkoutPaymentConfirmHint');
            const checkoutPaymentRequiredMarks = document.querySelectorAll('.checkout-payment-required-mark');
            const checkoutPaymentNotice = document.getElementById('checkoutPaymentNotice');

            function updateCheckoutPaymentRequirement() {
                if (!checkoutPaymentBox) {
                    return;
                }

                const baseRemaining = Number(checkoutPaymentBox.dataset.baseRemaining || 0);
                const manualExtraAmount = Number(checkoutExtraAmountInput?.value || 0);
                const needPayment = baseRemaining > 0 || manualExtraAmount > 0;

                checkoutPaymentMethods.forEach(function (input) {
                    input.required = needPayment;
                });

                if (checkoutPaymentConfirm) {
                    checkoutPaymentConfirm.required = needPayment;
                }

                checkoutPaymentRequiredMarks.forEach(function (mark) {
                    mark.classList.toggle('d-none', !needPayment);
                });

                if (checkoutPaymentConfirmHint) {
                    checkoutPaymentConfirmHint.textContent = needPayment
                        ? 'Bắt buộc vì booking còn tiền phải thu hoặc có phí phát sinh khi check-out.'
                        : 'Không bắt buộc nếu booking không phát sinh thêm tiền.';
                }

                if (checkoutPaymentNotice && baseRemaining <= 0) {
                    checkoutPaymentNotice.classList.toggle('alert-success', !needPayment);
                    checkoutPaymentNotice.classList.toggle('alert-warning', needPayment);
                }
            }

            if (checkoutExtraAmountInput) {
                checkoutExtraAmountInput.addEventListener('input', updateCheckoutPaymentRequirement);
                updateCheckoutPaymentRequirement();
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
        });
    </script>
@endsection
