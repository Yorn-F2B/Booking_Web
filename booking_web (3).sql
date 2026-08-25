-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 24, 2026 lúc 06:24 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `booking_web`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `amenities`
--

CREATE TABLE `amenities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `amenities`
--

INSERT INTO `amenities` (`id`, `name`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Wifi', 'bx bx-wifi', '2026-06-08 22:06:17', '2026-06-08 22:06:17'),
(2, 'Điều hòa', 'bx bx-wind', '2026-06-08 22:07:24', '2026-06-08 22:07:24'),
(3, 'TV', 'bx bx-tv', '2026-06-08 22:07:52', '2026-06-08 22:07:52'),
(4, 'Bồn tắm', 'bx bx-bath', '2026-06-08 22:08:24', '2026-06-08 22:08:24'),
(5, 'Ăn sáng', 'bx bx-bowl-hot', '2026-06-08 22:09:10', '2026-06-08 22:09:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banned_words`
--

CREATE TABLE `banned_words` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `word` varchar(255) NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banned_words`
--

INSERT INTO `banned_words` (`id`, `word`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'địt', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(2, 'đụ', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(3, 'đéo', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(4, 'lồn', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(5, 'cặc', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(6, 'buồi', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(7, 'đĩ', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(8, 'chó chết', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(9, 'fuck', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(10, 'fucking', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(11, 'shit', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(12, 'bitch', NULL, '2026-07-28 04:55:41', '2026-07-28 04:55:41'),
(14, 'dkm', 7, '2026-08-21 09:08:01', '2026-08-21 09:08:01'),
(15, 'cmm', 7, '2026-08-21 09:08:01', '2026-08-21 09:08:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `customer_name_snapshot` varchar(255) DEFAULT NULL,
  `customer_phone_snapshot` varchar(30) DEFAULT NULL,
  `customer_email_snapshot` varchar(255) DEFAULT NULL,
  `customer_cccd_snapshot` varchar(30) DEFAULT NULL,
  `customer_birthday_snapshot` date DEFAULT NULL,
  `customer_gender_snapshot` varchar(20) DEFAULT NULL,
  `customer_address_snapshot` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `room_category_id` bigint(20) UNSIGNED NOT NULL,
  `booking_type` enum('overnight','hourly') NOT NULL DEFAULT 'overnight',
  `booking_mode` enum('advance','walk_in') NOT NULL DEFAULT 'advance',
  `booking_source` enum('user_online','reception') NOT NULL DEFAULT 'reception',
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `check_in_at` datetime NOT NULL,
  `check_out_at` datetime NOT NULL,
  `cleaning_buffer_minutes` int(11) NOT NULL DEFAULT 0,
  `policy_snapshot` longtext DEFAULT NULL,
  `actual_check_in` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `adult_count` int(11) NOT NULL DEFAULT 1,
  `child_count` int(11) NOT NULL DEFAULT 0,
  `baby_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `room_quantity` int(11) NOT NULL DEFAULT 1,
  `prefer_adjacent_rooms` tinyint(1) NOT NULL DEFAULT 0,
  `room_selection_mode` enum('automatic','manual') NOT NULL DEFAULT 'automatic',
  `room_selection_request` text DEFAULT NULL,
  `room_selection_status` enum('not_required','pending','fulfilled','unfulfilled','awaiting_guest','fallback_accepted','fallback_declined') NOT NULL DEFAULT 'not_required',
  `room_selection_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `room_selection_handled_by` bigint(20) UNSIGNED DEFAULT NULL,
  `room_selection_handled_at` timestamp NULL DEFAULT NULL,
  `room_selection_handling_note` text DEFAULT NULL,
  `room_selection_guest_decided_at` timestamp NULL DEFAULT NULL,
  `refund_due_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `refund_status` enum('none','pending','completed') NOT NULL DEFAULT 'none',
  `refund_reason` text DEFAULT NULL,
  `refund_processed_at` timestamp NULL DEFAULT NULL,
  `refund_processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `subtotal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estimated_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `final_total` decimal(12,2) DEFAULT NULL,
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `required_deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overpayment_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_expires_at` datetime DEFAULT NULL,
  `late_arrival_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_arrival_hours` decimal(5,2) DEFAULT NULL,
  `late_arrival_policy` varchar(255) DEFAULT NULL,
  `late_arrival_confirmed_at` timestamp NULL DEFAULT NULL,
  `late_arrival_confirmed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `status` enum('pending','confirmed','checked_in','inspection_requested','checked_out','completed','cancelled') NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `customer_name_snapshot`, `customer_phone_snapshot`, `customer_email_snapshot`, `customer_cccd_snapshot`, `customer_birthday_snapshot`, `customer_gender_snapshot`, `customer_address_snapshot`, `created_by`, `room_category_id`, `booking_type`, `booking_mode`, `booking_source`, `check_in_date`, `check_out_date`, `check_in_at`, `check_out_at`, `cleaning_buffer_minutes`, `policy_snapshot`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `baby_count`, `room_quantity`, `prefer_adjacent_rooms`, `room_selection_mode`, `room_selection_request`, `room_selection_status`, `room_selection_fee`, `room_selection_handled_by`, `room_selection_handled_at`, `room_selection_handling_note`, `room_selection_guest_decided_at`, `refund_due_amount`, `refund_status`, `refund_reason`, `refund_processed_at`, `refund_processed_by`, `subtotal_amount`, `discount_amount`, `estimated_total`, `final_total`, `deposit_amount`, `required_deposit_amount`, `overpayment_amount`, `payment_expires_at`, `late_arrival_fee`, `late_arrival_hours`, `late_arrival_policy`, `late_arrival_confirmed_at`, `late_arrival_confirmed_by`, `payment_status`, `status`, `note`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'BK20260718134205VOA', 1, 'Trịnh huy', '0985795608', 'tc19092006@gmail.com', '038206022142', '2008-08-14', 'male', 'Huyện vệ thôn', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-18', '2026-07-19', '2026-07-18 14:00:00', '2026-07-19 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1000000.00, 0.00, 1000000.00, NULL, 300000.00, 300000.00, 0.00, NULL, 0.00, NULL, 'Tự động hủy no-show lúc 19/07/2026 09:38 vì khách chưa check-in trước giờ G 18:00 18/07/2026.', NULL, NULL, 'partial', 'cancelled', '19/07/2026 09:38 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-18 06:42:05', '2026-07-19 02:38:03'),
(2, 'BK202607190949110GW', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-22', '2026-07-19 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-19 09:52:31', '2026-07-19 10:16:02', 3, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 600000.00, 3200000.00, 3200000.00, 3200000.00, 960000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '19/07/2026 09:52 - Check-in thực tế: 3 người lớn / 0 trẻ em / 0 em bé. Đã thu phụ phí phát sinh khi check-in: Phụ thu thêm người lớn x 1: 200.000đ. Tổng phụ thu: 200.000đ. Check-in sớm trong cùng ngày lúc 19/07/2026 09:52, sớm hơn giờ chuẩn 4 giờ 7 phút. Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm. Phụ thu: 200.000đ.\n19/07/2026 09:59 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/07/2026 10:16 - Check-out thực tế. Tổng phải thu: 3.200.000đ. Đã thu trước check-out: 3.200.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trước khi check-out, không cần thu thêm. Không phát sinh phụ thu check-out.', NULL, '2026-07-19 02:49:11', '2026-07-19 03:16:02'),
(3, 'BK20260719095051SUS', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-25', '2026-07-19 09:57:00', '2026-07-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 6000000.00, 0.00, 6000000.00, NULL, 900000.00, 1800000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '19/07/2026 09:57 - Đổi ngày lưu trú từ 22/07/2026 14:00 → 25/07/2026 12:00 sang 19/07/2026 09:57 → 25/07/2026 12:00. Chênh lệch tiền phòng: 3.000.000đ.\n19/07/2026 09:58 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-19 02:50:51', '2026-07-19 02:58:07'),
(4, 'BK20260719102934OGT', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-22', '2026-07-19 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-19 10:30:25', '2026-07-19 12:19:44', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3180000.00, 580000.00, 2900000.00, 2900000.00, 2900000.00, 870000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '19/07/2026 10:30 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 19/07/2026 10:30, sớm hơn giờ chuẩn 3 giờ 29 phút. Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm. Phụ thu: 200.000đ.\n19/07/2026 10:39 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/07/2026 12:19 - Check-out thực tế. Tổng phải thu: 2.900.000đ. Đã thu trước check-out: 2.900.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-19 03:29:34', '2026-07-19 05:19:44'),
(5, 'BK202607191922377BB', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-23', '2026-07-19 19:23:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 4000000.00, 0.00, 4000000.00, NULL, 900000.00, 1200000.00, 0.00, NULL, 0.00, NULL, 'Tự động hủy no-show lúc 19/07/2026 19:24 vì khách chưa check-in trước giờ G 18:00 19/07/2026.', NULL, NULL, 'partial', 'cancelled', '19/07/2026 19:23 - Đổi ngày lưu trú từ 20/07/2026 14:00 → 23/07/2026 12:00 sang 19/07/2026 19:23 → 23/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.\n19/07/2026 19:24 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-19 12:22:37', '2026-07-19 12:24:01'),
(6, 'BK20260719192455CXS', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-22', '2026-07-19 19:26:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3000000.00, 0.00, 3000000.00, NULL, 600000.00, 900000.00, 0.00, NULL, 0.00, NULL, 'Tự động hủy no-show lúc 19/07/2026 19:27 vì khách chưa check-in trước giờ G 18:00 19/07/2026.', NULL, NULL, 'partial', 'cancelled', '19/07/2026 19:26 - Đổi ngày lưu trú từ 20/07/2026 14:00 → 22/07/2026 12:00 sang 19/07/2026 19:26 → 22/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.\n19/07/2026 19:27 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-19 12:24:55', '2026-07-19 12:27:01'),
(7, 'BK202607191936100WP', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-22', '2026-07-19 19:36:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-19 19:36:55', '2026-07-19 19:40:54', 2, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 600000.00, 3130000.00, 3130000.00, 3130000.00, 939000.00, 0.00, NULL, 0.00, 0.02, 'Khách đến muộn nhưng vẫn trước giờ G 21:36 19/07/2026. Cho check-in bình thường; các phụ thu phát sinh khác được chốt khi check-out.', '2026-07-19 12:36:45', 4, 'paid', 'checked_out', '19/07/2026 19:36 - Đổi ngày lưu trú từ 20/07/2026 14:00 → 22/07/2026 12:00 sang 19/07/2026 19:36 → 22/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.\n19/07/2026 19:36 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách đến muộn nhưng vẫn trước giờ G 21:36 19/07/2026. Cho check-in bình thường; các phụ thu phát sinh khác được chốt khi check-out.\n19/07/2026 19:39 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/07/2026 19:39 - Admin duyệt kiểm tra phòng 101: dịch vụ tại phòng +30.000đ. Tổng cộng +30.000đ.\n19/07/2026 19:40 - Check-out thực tế. Tổng phải thu: 3.130.000đ. Đã thu trước check-out: 3.130.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-19 12:36:10', '2026-07-19 12:40:54'),
(8, 'BK20260719211711IQK', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-19', '2026-07-23', '2026-07-19 21:18:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-19 21:18:12', '2026-07-19 21:20:01', 2, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 4800000.00, 0.00, 4820000.00, 4820000.00, 4820000.00, 1440000.00, 0.00, NULL, 0.00, 0.00, 'Khách đến muộn nhưng vẫn trước giờ G 23:18 19/07/2026. Cho check-in bình thường; các phụ thu phát sinh khác được chốt khi check-out.', '2026-07-19 14:18:05', 4, 'paid', 'checked_out', '19/07/2026 21:18 - Đổi ngày lưu trú từ 20/07/2026 14:00 → 23/07/2026 12:00 sang 19/07/2026 21:18 → 23/07/2026 12:00. Chênh lệch tiền phòng: 1.200.000đ.\n19/07/2026 21:18 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách đến muộn nhưng vẫn trước giờ G 23:18 19/07/2026. Cho check-in bình thường; các phụ thu phát sinh khác được chốt khi check-out.\n19/07/2026 21:18 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/07/2026 21:19 - Admin duyệt kiểm tra phòng 103: dịch vụ tại phòng +20.000đ. Tổng cộng +20.000đ.\n19/07/2026 21:20 - Check-out thực tế. Tổng phải thu: 4.820.000đ. Đã thu trước check-out: 4.820.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-19 14:17:11', '2026-07-19 14:20:01'),
(9, 'BK20260720100542VED', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-21', '2026-07-20 14:00:00', '2026-07-21 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 900000.00, 0.00, 900000.00, NULL, 270000.00, 270000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 10:06 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 03:05:42', '2026-07-20 03:06:41'),
(10, 'BK20260720100702N6W', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 1800000.00, NULL, 540000.00, 540000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 10:09 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 03:07:02', '2026-07-20 03:09:00'),
(11, 'BK202607201009199TZ', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 1800000.00, NULL, 540000.00, 540000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 10:11 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 03:09:19', '2026-07-20 03:11:27'),
(12, 'BK20260720101302M69', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 1800000.00, NULL, 540000.00, 540000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 10:13 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 03:13:02', '2026-07-20 03:13:31'),
(13, 'BK20260720102439XX2', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 2, 'overnight', 'advance', 'user_online', '2026-08-06', '2026-08-09', '2026-08-06 14:00:00', '2026-08-09 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 2700000.00, 0.00, 2700000.00, NULL, 810000.00, 810000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 10:26 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 03:24:39', '2026-07-20 03:26:01'),
(14, 'BK20260720102624PPQ', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 1800000.00, NULL, 540000.00, 540000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 10:27 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 03:26:24', '2026-07-20 03:27:01'),
(15, 'BK260720K3R9M', 3, 'Nguyen Van A', '0985795628', 'chientr319@gmail.com', '038206022628', NULL, NULL, 'yên định', 4, 2, 'overnight', 'advance', 'reception', '2026-07-20', '2026-07-24', '2026-07-20 14:00:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 4, 1, 0, 2, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 7380000.00, 380000.00, 7000000.00, NULL, 0.00, 2100000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'unpaid', 'cancelled', '20/07/2026 11:48 - Khách vãng lai tự hủy qua trang tra cứu xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 04:14:53', '2026-07-20 04:48:50'),
(16, 'BK260720VVHDR', 3, 'Nguyễn Văn A', '0985795628', 'chientr319@gmail.com', '038206022628', NULL, NULL, 'yên định', 4, 2, 'overnight', 'advance', 'reception', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 4, 1, 0, 2, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 300000.00, 3300000.00, NULL, 990000.00, 990000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '20/07/2026 12:00 - Khách vãng lai tự hủy qua trang tra cứu xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 04:33:40', '2026-07-20 05:00:49'),
(17, 'BK20260720150140HDT', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 2000000.00, 0.00, 2000000.00, 2200000.00, 600000.00, 600000.00, 0.00, NULL, 200000.00, 21.00, 'Lễ tân xác nhận khách không đến và hủy no-show lúc 20/07/2026 16:02. Giờ G của booking: 15:30 21/07/2026.', '2026-07-20 08:36:05', 4, 'partial', 'cancelled', '20/07/2026 16:02 - Lễ tân xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 08:01:40', '2026-07-20 09:02:44');
INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `customer_name_snapshot`, `customer_phone_snapshot`, `customer_email_snapshot`, `customer_cccd_snapshot`, `customer_birthday_snapshot`, `customer_gender_snapshot`, `customer_address_snapshot`, `created_by`, `room_category_id`, `booking_type`, `booking_mode`, `booking_source`, `check_in_date`, `check_out_date`, `check_in_at`, `check_out_at`, `cleaning_buffer_minutes`, `policy_snapshot`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `baby_count`, `room_quantity`, `prefer_adjacent_rooms`, `room_selection_mode`, `room_selection_request`, `room_selection_status`, `room_selection_fee`, `room_selection_handled_by`, `room_selection_handled_at`, `room_selection_handling_note`, `room_selection_guest_decided_at`, `refund_due_amount`, `refund_status`, `refund_reason`, `refund_processed_at`, `refund_processed_by`, `subtotal_amount`, `discount_amount`, `estimated_total`, `final_total`, `deposit_amount`, `required_deposit_amount`, `overpayment_amount`, `payment_expires_at`, `late_arrival_fee`, `late_arrival_hours`, `late_arrival_policy`, `late_arrival_confirmed_at`, `late_arrival_confirmed_by`, `payment_status`, `status`, `note`, `deleted_at`, `created_at`, `updated_at`) VALUES
(18, 'BK20260720160343NO6', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 1800000.00, NULL, 540000.00, 540000.00, 0.00, NULL, 0.00, NULL, 'Tự động hủy no-show lúc 20/07/2026 18:01 vì khách chưa check-in trước hạn giữ phòng 18:00 20/07/2026.', NULL, NULL, 'partial', 'cancelled', '20/07/2026 16:17 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 09:03:43', '2026-07-20 09:17:40'),
(19, 'BK20260720160508I3L', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1000000.00, 0.00, 1000000.00, NULL, 300000.00, 300000.00, 0.00, NULL, 0.00, 3.00, 'Tự động hủy no-show lúc 20/07/2026 21:31 vì khách chưa check-in trước hạn giữ phòng 21:30 20/07/2026.', '2026-07-20 09:08:50', NULL, 'partial', 'cancelled', '20/07/2026 16:19 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 09:05:08', '2026-07-20 09:19:11'),
(20, 'BK202607201632095ZL', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 1800000.00, NULL, 540000.00, 540000.00, 0.00, NULL, 0.00, NULL, 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 20/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 20/07/2026 18:01.', NULL, NULL, 'partial', 'cancelled', '20/07/2026 16:37 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 09:32:09', '2026-07-20 09:37:59'),
(21, 'BK20260720163251CL9', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-22', '2026-07-20 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1000000.00, 0.00, 1000000.00, 2500000.00, 300000.00, 300000.00, 0.00, NULL, 500000.00, 4.50, 'Hệ thống tự động hủy booking do khách không check-in trước hạn giữ phòng đã gia hạn. Giờ G ban đầu: 20/07/2026 18:00. Khách đã xác nhận dự kiến đến lúc: 20/07/2026 22:30. Hạn giữ mới: 20/07/2026 23:00. Thời điểm hệ thống xử lý: 20/07/2026 23:01.', '2026-07-20 09:36:40', 4, 'partial', 'cancelled', '20/07/2026 16:38 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 09:32:51', '2026-07-20 09:38:32'),
(22, 'BK20260720173922A6S', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-24', '2026-07-20 17:40:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-20 17:40:40', '2026-07-20 18:00:25', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 4800000.00, 800000.00, 4010000.00, 4010000.00, 900000.00, 1203000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '20/07/2026 17:40 - Đổi ngày lưu trú từ 21/07/2026 14:00 → 24/07/2026 12:00 sang 20/07/2026 17:40 → 24/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.\n20/07/2026 17:40 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n20/07/2026 17:59 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n20/07/2026 17:59 - Admin duyệt kiểm tra phòng 103: dịch vụ tại phòng +10.000đ. Tổng cộng +10.000đ.\n20/07/2026 18:00 - Check-out thực tế. Tổng phải thu: 4.010.000đ. Đã thu trước check-out: 4.010.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 10:39:22', '2026-07-20 11:00:25'),
(23, 'BK2607203IW1I', 3, 'Nguyễn Văn A', '0985795628', 'chientr319@gmail.com', '038206022628', NULL, NULL, 'yên định', 4, 1, 'overnight', 'advance', 'reception', '2026-07-20', '2026-07-24', '2026-07-20 14:00:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-20 17:48:11', '2026-07-20 17:58:31', 4, 1, 0, 4, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 19380000.00, 780000.00, 21645000.00, 21645000.00, 5610000.00, 5760000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '20/07/2026 17:48 - Check-in thực tế: 4 người lớn / 1 trẻ em / 0 em bé. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n20/07/2026 17:56 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n20/07/2026 17:57 - Admin duyệt kiểm tra phòng 102: dịch vụ tại phòng +20.000đ. Tổng cộng +20.000đ.\n20/07/2026 17:57 - Admin duyệt kiểm tra phòng 403: dịch vụ tại phòng +25.000đ. Tổng cộng +25.000đ.\n20/07/2026 17:57 - Admin duyệt kiểm tra phòng 402: hư hại +3.000.000đ. Tổng cộng +3.000.000đ.\n20/07/2026 17:58 - Check-out thực tế. Tổng phải thu: 21.645.000đ. Đã thu trước check-out: 21.645.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 10:46:19', '2026-07-20 10:58:31'),
(24, 'BK20260720180117KLW', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-20', '2026-07-23', '2026-07-20 18:02:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-20 18:24:43', '2026-07-20 22:27:16', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 600000.00, 3000000.00, 3000000.00, 600000.00, 900000.00, 0.00, NULL, 0.00, NULL, '[RESCHEDULED_AFTER_G] Đơn được lễ tân chuyển từ ngày tương lai về hôm nay lúc 20/07/2026 18:02. Đây là đổi ngày nhận phòng, không phải khách đến muộn và không phát sinh phụ thu sau giờ G.', '2026-07-20 11:02:01', 4, 'paid', 'checked_out', '20/07/2026 18:02 - Đổi ngày lưu trú từ 21/07/2026 14:00 → 23/07/2026 12:00 sang 20/07/2026 18:02 → 23/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.\n20/07/2026 18:24 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Đơn được lễ tân chuyển từ ngày tương lai về hôm nay lúc 20/07/2026 18:02. Đây là đổi ngày nhận phòng, không phải khách đến muộn và không phát sinh phụ thu sau giờ G.\n20/07/2026 22:25 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n20/07/2026 22:27 - Check-out thực tế. Tổng phải thu: 3.000.000đ. Đã thu trước check-out: 3.000.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 11:01:17', '2026-07-20 15:27:16'),
(25, 'BK2607200E8TP', 3, 'Nguyễn Văn A', '0985795628', 'chientr319@gmail.com', '038206022628', NULL, NULL, 'yên định', 4, 1, 'overnight', 'walk_in', 'reception', '2026-07-20', '2026-07-23', '2026-07-20 21:22:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 6, 0, 0, 3, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 10800000.00, 0.00, 10800000.00, NULL, 3240000.00, 3240000.00, 0.00, NULL, 0.00, NULL, 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 20/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 20/07/2026 21:24.', NULL, NULL, 'partial', 'cancelled', '20/07/2026 21:24 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 14:23:53', '2026-07-20 14:24:01'),
(26, 'BK260720SPUTZ', 3, 'Nguyễn Văn A', '0985795628', 'chientr319@gmail.com', '038206022628', NULL, NULL, 'yên định', 4, 1, 'overnight', 'walk_in', 'reception', '2026-07-20', '2026-07-23', '2026-07-20 21:39:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-20 21:40:55', '2026-07-20 22:22:56', 6, 0, 0, 4, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 18000000.00, 3600000.00, 14890000.00, 14890000.00, 4320000.00, 4467000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '20/07/2026 21:40 - Check-in thực tế: 6 người lớn / 0 trẻ em / 0 em bé. \n20/07/2026 22:20 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n20/07/2026 22:21 - Admin duyệt kiểm tra phòng 301: dịch vụ tại phòng +50.000đ, hư hại +150.000đ. Tổng cộng +200.000đ.\n20/07/2026 22:22 - Admin duyệt kiểm tra phòng 405: dịch vụ tại phòng +40.000đ. Tổng cộng +40.000đ.\n20/07/2026 22:22 - Admin duyệt kiểm tra phòng 302: hư hại +250.000đ. Tổng cộng +250.000đ.\n20/07/2026 22:22 - Check-out thực tế. Tổng phải thu: 14.890.000đ. Đã thu trước check-out: 14.890.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 14:40:10', '2026-07-20 15:22:56'),
(27, 'BK202607202227509LJ', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-23', '2026-07-21 14:00:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 0.00, 3600000.00, NULL, 0.00, 1080000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'unpaid', 'cancelled', '20/07/2026 22:28 - Khách hàng xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 15:27:50', '2026-07-20 15:28:11'),
(28, 'BK20260720222828YJC', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-23', '2026-07-21 14:00:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 0.00, 3600000.00, NULL, 0.00, 1080000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'unpaid', 'cancelled', '20/07/2026 22:31 - Khách hàng xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-20 15:28:28', '2026-07-20 15:31:34'),
(29, 'BK20260720223155HQU', 1, 'TRỊNH NGỌC CHIẾN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 4, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-24', '2026-07-21 14:00:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 02:04:43', '2026-07-21 04:00:36', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 15000000.00, 9600000.00, 7300000.00, 7300000.00, 1620000.00, 1620000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '21/07/2026 02:04 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 02:04, sớm hơn giờ chuẩn 11 giờ 55 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.800.000đ.\n21/07/2026 02:07 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 03:08 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 03:59 - Admin xác nhận kiểm tra phòng 401: minibar/đồ dùng +50.000đ, hư hại/mất đồ +50.000đ. Tổng cộng +100.000đ.\n21/07/2026 04:00 - Check-out thực tế. Tổng phải thu: 7.300.000đ. Đã thu trước check-out: 7.300.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 15:31:55', '2026-07-20 21:00:36'),
(30, 'BK20260720223300OTI', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-25', '2026-07-21 00:06:00', '2026-07-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 00:08:02', '2026-07-21 00:37:09', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 0.00, 4675000.00, 4675000.00, 1620000.00, 1080000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '21/07/2026 00:07 - Đổi ngày lưu trú từ 09/08/2026 14:00 → 12/08/2026 12:00 sang 21/07/2026 00:06 → 25/07/2026 12:00 và đổi toàn bộ sang hạng Superior Double. Chênh lệch tiền phòng: -1.800.000đ. Phòng 302 → 201 (Family Suite → Superior Double).\n21/07/2026 00:08 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 00:08, sớm hơn giờ chuẩn 13 giờ 51 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 900.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n21/07/2026 00:08 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 00:08 - Admin duyệt kiểm tra phòng 201: dịch vụ tại phòng +125.000đ, hư hại +50.000đ. Tổng cộng +175.000đ.\n21/07/2026 00:37 - Check-out thực tế. Tổng phải thu: 4.675.000đ. Đã thu trước check-out: 4.675.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 15:33:00', '2026-07-20 17:37:09'),
(31, 'BK202607210038160NH', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-23', '2026-07-21 00:39:00', '2026-07-23 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 00:41:52', '2026-07-21 00:44:41', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 1800000.00, 0.00, 2740000.00, 2740000.00, 2700000.00, 540000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '21/07/2026 00:40 - Đổi ngày lưu trú từ 08/08/2026 14:00 → 13/08/2026 12:00 sang 21/07/2026 00:39 → 23/07/2026 12:00 và đổi toàn bộ sang hạng Superior Double. Chênh lệch tiền phòng: -7.200.000đ. Phòng 302 → 201 (Family Suite → Superior Double).\n21/07/2026 00:41 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 00:41, sớm hơn giờ chuẩn 13 giờ 18 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 900.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n21/07/2026 00:43 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 00:44 - Admin duyệt kiểm tra phòng 201: dịch vụ tại phòng +40.000đ. Tổng cộng +40.000đ.\n21/07/2026 00:44 - Check-out thực tế. Tổng phải thu: 2.740.000đ. Đã thu trước check-out: 2.740.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 17:38:16', '2026-07-20 17:44:42'),
(32, 'BK20260721004610LAH', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-26', '2026-07-21 00:46:00', '2026-07-26 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 00:50:21', '2026-07-21 01:16:47', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 6000000.00, 0.00, 7200000.00, 7200000.00, 540000.00, 1800000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '21/07/2026 00:47 - Đổi ngày lưu trú từ 31/07/2026 14:00 → 01/08/2026 12:00 sang 21/07/2026 00:46 → 26/07/2026 12:00 và đổi toàn bộ sang hạng Deluxe Sea View. Chênh lệch tiền phòng: 4.200.000đ. Phòng 302 → 101 (Family Suite → Deluxe Sea View).\n21/07/2026 00:50 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 00:50, sớm hơn giờ chuẩn 13 giờ 9 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.200.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n21/07/2026 01:16 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 01:16 - Check-out thực tế. Tổng phải thu: 7.200.000đ. Đã thu trước check-out: 7.200.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 17:46:10', '2026-07-20 18:16:47'),
(33, 'BK260721CJNQJ', 4, 'Nguyễn Văn A', '0985795611', 'chientr319@gmail.com', '038206022411', NULL, NULL, 'yên định', 4, 1, 'overnight', 'walk_in', 'reception', '2026-07-21', '2026-07-25', '2026-07-21 04:32:00', '2026-07-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 06:03:19', '2026-07-21 07:46:27', 4, 1, 0, 2, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 12000000.00, 200000.00, 11967000.00, 11967000.00, 2820000.00, 2820000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '21/07/2026 06:03 - Check-in thực tế: 4 người lớn / 1 trẻ em / 0 em bé. Phụ thu check-in sớm đã được tính khi tạo booking; không thu trùng lần nữa.\n21/07/2026 07:39 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 07:44 - Admin xác nhận kiểm tra phòng 403: minibar/đồ dùng +7.000đ. Tổng cộng +7.000đ.\n21/07/2026 07:45 - Admin xác nhận kiểm tra phòng 402: minibar/đồ dùng +60.000đ, hư hại/mất đồ +100.000đ. Tổng cộng +160.000đ.\n21/07/2026 07:46 - Check-out thực tế. Tổng phải thu: 11.967.000đ. Đã thu trước check-out: 11.967.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-20 21:33:44', '2026-07-21 00:46:27');
INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `customer_name_snapshot`, `customer_phone_snapshot`, `customer_email_snapshot`, `customer_cccd_snapshot`, `customer_birthday_snapshot`, `customer_gender_snapshot`, `customer_address_snapshot`, `created_by`, `room_category_id`, `booking_type`, `booking_mode`, `booking_source`, `check_in_date`, `check_out_date`, `check_in_at`, `check_out_at`, `cleaning_buffer_minutes`, `policy_snapshot`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `baby_count`, `room_quantity`, `prefer_adjacent_rooms`, `room_selection_mode`, `room_selection_request`, `room_selection_status`, `room_selection_fee`, `room_selection_handled_by`, `room_selection_handled_at`, `room_selection_handling_note`, `room_selection_guest_decided_at`, `refund_due_amount`, `refund_status`, `refund_reason`, `refund_processed_at`, `refund_processed_by`, `subtotal_amount`, `discount_amount`, `estimated_total`, `final_total`, `deposit_amount`, `required_deposit_amount`, `overpayment_amount`, `payment_expires_at`, `late_arrival_fee`, `late_arrival_hours`, `late_arrival_policy`, `late_arrival_confirmed_at`, `late_arrival_confirmed_by`, `payment_status`, `status`, `note`, `deleted_at`, `created_at`, `updated_at`) VALUES
(36, 'BK260721R3B79', 1, 'TRỊNH NGỌC CHIEN', '0985795608', 'chientr319@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', 4, 1, 'overnight', 'walk_in', 'reception', '2026-07-21', '2026-07-24', '2026-07-21 08:58:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 10:00:55', '2026-07-21 11:52:56', 5, 0, 0, 3, 1, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 13800000.00, 0.00, 13880000.00, 13880000.00, 2520000.00, 3780000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '21/07/2026 10:00 - Check-in thực tế: 4 người lớn / 0 trẻ em / 0 em bé. Đã ghi phụ phí vượt sức chứa theo từng phòng: phòng 402 - Phụ thu thêm người lớn x 1: 200.000đ. Tổng phụ thu: 200.000đ. Phụ thu check-in sớm đã được tính khi tạo booking; không thu trùng lần nữa.\n21/07/2026 10:03 - Đã thêm 1 phòng hạng Deluxe Sea View vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.\n21/07/2026 10:24 - Đã thêm 1 phòng hạng Deluxe Sea View vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.\n21/07/2026 10:26 - Đã đổi phòng 405 (Deluxe Sea View) sang phòng 302 (Family Suite). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. Tiền chênh: 1.800.000đ. Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: Khách yêu cầu đổi phòng.\n21/07/2026 10:32 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 10:37 - Admin xác nhận kiểm tra phòng 402: minibar/đồ dùng +30.000đ, hư hại/mất đồ +50.000đ. Tổng cộng +80.000đ.\n21/07/2026 11:52 - Check-out thực tế. Tổng phải thu: 13.880.000đ. Đã thu trước check-out: 13.880.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-21 01:59:47', '2026-07-21 04:52:56'),
(37, 'BK20260721113345YCB', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-24', '2026-07-21 14:00:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3000000.00, 0.00, 3000000.00, NULL, 900000.00, 900000.00, 0.00, NULL, 0.00, NULL, 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 21/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 26/07/2026 09:16.', NULL, NULL, 'partial', 'cancelled', '26/07/2026 09:16 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-21 04:33:45', '2026-07-26 02:16:05'),
(38, 'BK20260721115507D0N', 1, 'TRỊNH NGỌC CHIEN', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 5, 'overnight', 'advance', 'user_online', '2026-07-24', '2026-07-27', '2026-07-24 14:00:00', '2026-07-27 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3000000.00, 0.00, 3000000.00, NULL, 900000.00, 900000.00, 0.00, NULL, 0.00, NULL, 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 24/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 26/07/2026 09:16.', NULL, NULL, 'partial', 'cancelled', '26/07/2026 09:16 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-21 04:55:07', '2026-07-26 02:16:05'),
(39, 'BK202607211343459FH', 1, 'Triện Ngọc Chính Chốn Liền', '0985795608', 'tc19092006@gmail.com', '038206022002', '2008-08-14', 'male', 'Thôn Vệ 3', NULL, 4, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-22', '2026-07-21 14:00:00', '2026-07-22 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 5180000.00, 180000.00, 5000000.00, NULL, 1500000.00, 1500000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '21/07/2026 13:50 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-21 06:43:45', '2026-07-21 06:50:51'),
(40, 'BK20260721141911AQB', 1, 'Trịnh Ngọc Chiến', '0985795608', 'tc19092006@gmail.com', '038206022002', '2006-09-19', 'male', 'Thôn Vệ 3', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-22', '2026-07-24', '2026-07-22 14:00:00', '2026-07-24 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 3, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3680000.00, 0.00, 3680000.00, NULL, 1080000.00, 1080000.00, 0.00, NULL, 0.00, NULL, 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 22/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 26/07/2026 09:16.', NULL, NULL, 'partial', 'cancelled', '26/07/2026 09:16 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-21 07:19:11', '2026-07-26 02:16:05'),
(41, 'BK20260721142728TZA', 16, 'Dương Cường', '0353725042', 'sccuong5222@gmail.com', '036206022065', NULL, NULL, 'Huyện Yên Định', NULL, 1, 'overnight', 'advance', 'user_online', '2026-07-21', '2026-07-25', '2026-07-21 14:28:00', '2026-07-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', '2026-07-21 14:33:52', '2026-07-21 14:43:32', 2, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 4800000.00, 0.00, 4950000.00, 4950000.00, 1080000.00, 1440000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '21/07/2026 14:29 - Đổi ngày lưu trú từ 22/07/2026 14:00 → 25/07/2026 12:00 sang 21/07/2026 14:28 → 25/07/2026 12:00. Chênh lệch tiền phòng: 1.200.000đ.\n21/07/2026 14:33 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n21/07/2026 14:35 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n21/07/2026 14:38 - Admin xác nhận kiểm tra phòng 101: minibar/đồ dùng +50.000đ, hư hại/mất đồ +100.000đ. Tổng cộng +150.000đ.\n21/07/2026 14:43 - Check-out thực tế. Tổng phải thu: 4.950.000đ. Đã thu trước check-out: 4.950.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-07-21 07:27:28', '2026-07-21 07:43:32'),
(42, 'BK20260727192932U5G', 1, 'Trịnh Ngọc Chiến', '0985795608', 'tc19092006@gmail.com', '038206022002', '2006-09-19', 'male', 'Thôn Vệ 3', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-28', '2026-07-31', '2026-07-28 14:00:00', '2026-07-31 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 5400000.00, 0.00, 5400000.00, NULL, 1620000.00, 1620000.00, 0.00, NULL, 0.00, 2.50, 'Hệ thống tự động hủy booking do khách không check-in trước hạn giữ phòng đã gia hạn. Giờ G ban đầu: 28/07/2026 18:00. Khách đã xác nhận dự kiến đến lúc: 28/07/2026 20:30. Hạn giữ mới: 28/07/2026 21:00. Thời điểm hệ thống xử lý: 01/08/2026 23:54.', '2026-07-28 09:44:14', 4, 'partial', 'cancelled', '01/08/2026 23:54 - Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-27 12:29:32', '2026-08-01 16:54:38'),
(43, 'BK28072026-001', 2, 'Đào Du', '0985795123', 'du319@gmail.com', '038245722123', '2002-09-19', 'male', 'Hậu Lộc', NULL, 3, 'overnight', 'advance', 'user_online', '2026-07-31', '2026-08-02', '2026-07-31 14:00:00', '2026-08-02 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"legacy-backfill-before-policy-page\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3600000.00, 0.00, 3600000.00, NULL, 0.00, 1080000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'unpaid', 'cancelled', '28/07/2026 12:02 - Khách hàng xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-07-28 05:02:28', '2026-07-28 05:02:41'),
(44, 'BK21082026-001', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 5, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-25', '2026-08-22 14:00:00', '2026-08-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-21T20:45:46+07:00\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3000000.00, 0.00, 3000000.00, NULL, 0.00, 900000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-08-21 13:45:46', '2026-08-21 14:17:01'),
(45, 'BK21082026-002', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 5, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-26', '2026-08-22 14:00:00', '2026-08-26 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-21T22:31:24+07:00\"}', NULL, NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 4000000.00, 0.00, 4000000.00, NULL, 0.00, 1200000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-08-21 15:31:24', '2026-08-21 16:02:01'),
(46, 'BK21082026-003', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 1, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-25', '2026-08-22 14:00:00', '2026-08-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-21T23:40:41+07:00\"}', '2026-08-22 00:25:02', '2026-08-22 05:36:04', 3, 0, 0, 2, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 8100000.00, 640000.00, 7310000.00, 7310000.00, 810000.00, 1710000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '22/08/2026 00:25 - Check-in thực tế: 3 người lớn / 0 trẻ em / 0 em bé. Đã ghi phụ phí vượt sức chứa theo từng phòng: phòng 501 - Phụ thu thêm người lớn x 1: 200.000đ. Tổng phụ thu: 200.000đ. Check-in sớm trong cùng ngày lúc 22/08/2026 00:25, sớm hơn giờ chuẩn 13 giờ 34 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.000.000đ.\n22/08/2026 03:31 - Đã thêm 1 phòng hạng Phòng demo vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.\n22/08/2026 03:33 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n22/08/2026 05:36 - Check-out thực tế. Tổng phải thu: 7.310.000đ. Đã thu trước check-out: 7.310.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-08-21 16:40:41', '2026-08-21 22:36:04'),
(47, 'BK22082026-001', 7, 'Trần Quốc Bảo', '0901000002', 'demo.user02@booking.local', '038200000002', '1995-06-20', 'male', 'Huyện Đông Sơn, Thanh Hóa', NULL, 1, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-28', '2026-08-22 06:37:00', '2026-08-28 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-22T00:58:48+07:00\"}', '2026-08-22 06:39:09', '2026-08-22 07:43:44', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 6660000.00, 160000.00, 6500000.00, 6500000.00, 900000.00, 1800000.00, 0.00, NULL, 0.00, NULL, 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', NULL, NULL, 'paid', 'checked_out', '22/08/2026 06:37 - Đổi ngày lưu trú từ 25/08/2026 14:00 → 28/08/2026 12:00 sang 22/08/2026 06:37 → 28/08/2026 12:00. Chênh lệch tiền phòng: 3.000.000đ.\n22/08/2026 06:39 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 06:39, sớm hơn giờ chuẩn 7 giờ 20 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 500.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.\n22/08/2026 07:31 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n22/08/2026 07:43 - Check-out thực tế. Tổng phải thu: 6.500.000đ. Đã thu trước check-out: 6.500.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-08-21 17:58:48', '2026-08-22 00:43:44'),
(48, 'BK22082026-002', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 5, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-25', '2026-08-22 14:00:00', '2026-08-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-22T08:44:40+07:00\"}', '2026-08-22 08:46:54', '2026-08-22 08:48:42', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3500000.00, 0.00, 3500000.00, 3500000.00, 900000.00, 900000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '22/08/2026 08:46 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 08:46, sớm hơn giờ chuẩn 5 giờ 13 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 500.000đ.\n22/08/2026 08:47 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n22/08/2026 08:48 - Check-out thực tế. Tổng phải thu: 3.500.000đ. Đã thu trước check-out: 3.500.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-08-22 01:44:40', '2026-08-22 01:48:42'),
(49, 'BK22082026-003', 7, 'Trần Quốc Bảo', '0901000002', 'demo.user02@booking.local', '038200000002', '1995-06-20', 'male', 'Huyện Đông Sơn, Thanh Hóa', NULL, 5, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-25', '2026-08-22 14:00:00', '2026-08-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-22T08:50:54+07:00\"}', '2026-08-22 08:53:39', '2026-08-24 00:05:14', 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 3500000.00, 0.00, 3500000.00, 3500000.00, 900000.00, 900000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '22/08/2026 08:53 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 08:53, sớm hơn giờ chuẩn 5 giờ 6 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 500.000đ.\n22/08/2026 08:53 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n24/08/2026 00:05 - Check-out thực tế. Tổng phải thu: 3.500.000đ. Đã thu trước check-out: 3.500.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-08-22 01:50:54', '2026-08-23 17:05:14'),
(50, 'BK22082026-004', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 1, 'overnight', 'advance', 'user_online', '2026-08-22', '2026-08-25', '2026-08-22 14:00:00', '2026-08-25 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-22T08:55:28+07:00\"}', '2026-08-22 08:56:32', '2026-08-24 00:08:22', 1, 0, 0, 2, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 7980000.00, 580000.00, 7400000.00, 7400000.00, 1080000.00, 2040000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'paid', 'checked_out', '22/08/2026 08:56 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 08:56, sớm hơn giờ chuẩn 5 giờ 3 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 600.000đ.\n22/08/2026 08:57 - Đã thêm 1 phòng hạng Deluxe Sea View vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.\n22/08/2026 09:02 - Đã đổi phòng 101 (Deluxe Sea View) sang phòng 405 (Deluxe Sea View). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. Tiền chênh: 0đ. Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: thích\n24/08/2026 00:07 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n24/08/2026 00:08 - Check-out thực tế. Tổng phải thu: 7.400.000đ. Đã thu trước check-out: 7.400.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', NULL, '2026-08-22 01:55:28', '2026-08-23 17:08:22'),
(51, 'BK24082026-001', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 1, 'overnight', 'advance', 'user_online', '2026-08-24', '2026-08-26', '2026-08-24 14:00:00', '2026-08-26 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"booking.manual_room_selection_fee\":50000,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-24T00:41:59+07:00\"}', NULL, NULL, 1, 0, 0, 1, 0, 'manual', 'tầng cao', 'unfulfilled', 0.00, 5, '2026-08-23 17:43:57', NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 2400000.00, 0.00, 2400000.00, NULL, 720000.00, 720000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'cancelled', '24/08/2026 00:48 - Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.', NULL, '2026-08-23 17:41:59', '2026-08-23 17:48:40');
INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `customer_name_snapshot`, `customer_phone_snapshot`, `customer_email_snapshot`, `customer_cccd_snapshot`, `customer_birthday_snapshot`, `customer_gender_snapshot`, `customer_address_snapshot`, `created_by`, `room_category_id`, `booking_type`, `booking_mode`, `booking_source`, `check_in_date`, `check_out_date`, `check_in_at`, `check_out_at`, `cleaning_buffer_minutes`, `policy_snapshot`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `baby_count`, `room_quantity`, `prefer_adjacent_rooms`, `room_selection_mode`, `room_selection_request`, `room_selection_status`, `room_selection_fee`, `room_selection_handled_by`, `room_selection_handled_at`, `room_selection_handling_note`, `room_selection_guest_decided_at`, `refund_due_amount`, `refund_status`, `refund_reason`, `refund_processed_at`, `refund_processed_by`, `subtotal_amount`, `discount_amount`, `estimated_total`, `final_total`, `deposit_amount`, `required_deposit_amount`, `overpayment_amount`, `payment_expires_at`, `late_arrival_fee`, `late_arrival_hours`, `late_arrival_policy`, `late_arrival_confirmed_at`, `late_arrival_confirmed_by`, `payment_status`, `status`, `note`, `deleted_at`, `created_at`, `updated_at`) VALUES
(52, 'BK24082026-002', 6, 'Nguyễn Minh Anh', '0901000001', 'demo.user01@booking.local', '038200000001', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 1, 'overnight', 'advance', 'user_online', '2026-08-24', '2026-08-26', '2026-08-24 14:00:00', '2026-08-26 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"booking.manual_room_selection_fee\":50000,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-24T03:13:11+07:00\"}', '2026-08-24 10:16:57', NULL, 1, 0, 0, 1, 0, 'manual', 'tần cao', 'fulfilled', 50000.00, 29, '2026-08-24 02:39:26', '', NULL, 0.00, 'none', NULL, NULL, NULL, 3050000.00, 160000.00, 2890000.00, NULL, 720000.00, 735000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'inspection_requested', '24/08/2026 10:16 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 24/08/2026 10:16, sớm hơn giờ chuẩn 3 giờ 43 phút. Check-in sớm từ 09:00 đến trước 12:00, phụ thu 20% giá 1 đêm. Phụ thu: 240.000đ.\n24/08/2026 10:31 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-08-23 20:13:11', '2026-08-24 03:31:46'),
(53, 'BK24082026-003', 7, 'Trần Quốc Bảo', '0901000002', 'demo.user02@booking.local', '038200000002', '1995-06-20', 'male', 'Huyện Đông Sơn, Thanh Hóa', NULL, 2, 'overnight', 'advance', 'user_online', '2026-08-24', '2026-08-26', '2026-08-24 14:00:00', '2026-08-26 12:00:00', 0, '{\"booking.min_age\":18,\"booking.cleaning_buffer_minutes\":0,\"booking.direct_cancel_cutoff_time\":\"14:00\",\"booking.hourly_cancel_grace_minutes\":30,\"booking.manual_room_selection_fee\":50000,\"payment.deposit_percent\":30,\"payment.vnpay_expire_minutes\":30,\"payment.admin_vnpay_expire_minutes\":1440,\"stay.standard_check_in_time\":\"14:00\",\"stay.standard_check_out_time\":\"12:00\",\"stay.early_checkin_free_from\":\"12:00\",\"stay.early_checkin_tier1_end\":\"06:00\",\"stay.early_checkin_tier2_end\":\"09:00\",\"stay.early_checkin_percent_1\":100,\"stay.early_checkin_percent_2\":50,\"stay.early_checkin_percent_3\":20,\"stay.late_checkout_free_minutes\":15,\"stay.late_checkout_tier1_end\":\"13:00\",\"stay.late_checkout_tier2_end\":\"14:00\",\"stay.late_checkout_tier3_end\":\"15:00\",\"stay.late_checkout_full_night_from\":\"18:00\",\"stay.late_checkout_percent_1\":20,\"stay.late_checkout_percent_2\":40,\"stay.late_checkout_percent_3\":60,\"stay.late_checkout_percent_4\":80,\"stay.late_checkout_percent_full\":100,\"stay.late_arrival_cutoff_time\":\"18:00\",\"stay.late_arrival_tier1_end\":\"21:00\",\"stay.late_arrival_percent_1\":20,\"stay.late_arrival_percent_2\":50,\"stay.late_arrival_percent_next_day\":100,\"stay.late_arrival_grace_minutes\":30,\"stay.rescheduled_after_cutoff_grace_minutes\":120,\"stay.priority_cleaning_start_time\":\"12:00\",\"stay.priority_cleaning_window_minutes\":120,\"stay.late_arrival_form_expire_minutes\":1440,\"stay.short_stay_min_minutes\":30,\"stay.short_stay_to_overnight_hours\":12,\"stay.short_stay_base_hours\":2,\"stay.short_stay_base_percent\":50,\"stay.short_stay_extra_hour_percent\":10,\"stay.short_stay_max_percent\":80,\"room_issue.proposal_hold_minutes\":30,\"housekeeping.slow_room_alert_minutes\":120,\"chat.archive_retention_days\":730,\"_captured_at\":\"2026-08-24T05:36:35+07:00\"}', '2026-08-24 05:51:49', NULL, 1, 0, 0, 1, 0, 'automatic', NULL, 'not_required', 0.00, NULL, NULL, NULL, NULL, 0.00, 'none', NULL, NULL, NULL, 2980000.00, 180000.00, 2800000.00, NULL, 600000.00, 540000.00, 0.00, NULL, 0.00, NULL, NULL, NULL, NULL, 'partial', 'checked_in', '24/08/2026 05:51 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 24/08/2026 05:51, sớm hơn giờ chuẩn 8 giờ 8 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.000.000đ.\n24/08/2026 09:53 - Đã đổi phòng 501 (Phòng demo) sang phòng 202 (Superior Double). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. Tiền chênh: -200.000đ. Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: vfryw', NULL, '2026-08-23 22:36:35', '2026-08-24 02:53:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_cancellation_requests`
--

CREATE TABLE `booking_cancellation_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `policy_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`policy_snapshot`)),
  `requested_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_guests`
--

CREATE TABLE `booking_guests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `guest_type` enum('adult','child','infant') NOT NULL DEFAULT 'adult',
  `document_type` enum('cccd','passport','birth_certificate','personal_id','other','none') NOT NULL DEFAULT 'cccd',
  `document_number` varchar(50) DEFAULT NULL,
  `document_exception_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `document_exception_reason` varchar(500) DEFAULT NULL,
  `document_exception_acknowledged_at` datetime DEFAULT NULL,
  `document_exception_acknowledged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `document_exception_acknowledged_by_name` varchar(255) DEFAULT NULL,
  `cccd` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_booking_representative` tinyint(1) NOT NULL DEFAULT 0,
  `guardian_guest_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `planned_check_in_at` datetime DEFAULT NULL,
  `planned_check_out_at` datetime DEFAULT NULL,
  `actual_check_in_at` datetime DEFAULT NULL,
  `actual_check_out_at` datetime DEFAULT NULL,
  `status` enum('registered','checked_in','checked_out') NOT NULL DEFAULT 'registered',
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_guests`
--

INSERT INTO `booking_guests` (`id`, `booking_id`, `booking_room_id`, `full_name`, `guest_type`, `document_type`, `document_number`, `document_exception_acknowledged`, `document_exception_reason`, `document_exception_acknowledged_at`, `document_exception_acknowledged_by`, `document_exception_acknowledged_by_name`, `cccd`, `birthday`, `gender`, `nationality`, `address`, `is_booking_representative`, `guardian_guest_id`, `guardian_relationship`, `planned_check_in_at`, `planned_check_out_at`, `actual_check_in_at`, `actual_check_out_at`, `status`, `note`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-19 14:00:00', '2026-07-22 12:00:00', '2026-07-19 09:52:31', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-19 02:52:31', '2026-07-19 02:52:31'),
(2, 4, 4, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-19 14:00:00', '2026-07-22 12:00:00', '2026-07-19 10:30:25', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-19 03:30:25', '2026-07-19 03:30:25'),
(3, 7, 7, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-19 19:36:00', '2026-07-22 12:00:00', '2026-07-19 19:36:55', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-19 12:36:55', '2026-07-19 12:36:55'),
(4, 8, 8, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-19 21:18:00', '2026-07-23 12:00:00', '2026-07-19 21:18:12', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-19 14:18:12', '2026-07-19 14:18:12'),
(5, 22, 24, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-20 17:40:00', '2026-07-24 12:00:00', '2026-07-20 17:40:40', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-20 10:40:40', '2026-07-20 10:40:40'),
(6, 23, 25, 'Nguyễn Văn A', 'adult', 'cccd', '038206022628', 0, NULL, NULL, NULL, NULL, '038206022628', NULL, NULL, 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-20 14:00:00', '2026-07-24 12:00:00', '2026-07-20 17:48:11', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: yên định', NULL, NULL, '2026-07-20 10:48:11', '2026-07-20 10:48:11'),
(7, 24, 29, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-20 18:02:00', '2026-07-23 12:00:00', '2026-07-20 18:24:43', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-20 11:24:43', '2026-07-20 11:24:43'),
(8, 26, 33, 'Nguyễn Văn A', 'adult', 'cccd', '038206022628', 0, NULL, NULL, NULL, NULL, '038206022628', NULL, NULL, 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-20 21:39:00', '2026-07-23 12:00:00', '2026-07-20 21:40:55', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: yên định', NULL, NULL, '2026-07-20 14:40:55', '2026-07-20 14:40:55'),
(9, 30, 40, 'Đào Du', 'adult', 'cccd', '038245722123', 0, NULL, NULL, NULL, NULL, '038245722123', '2002-09-19', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-21 00:06:00', '2026-07-25 12:00:00', '2026-07-21 00:08:02', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Hậu Lộc', NULL, NULL, '2026-07-20 17:08:02', '2026-07-20 17:08:02'),
(10, 31, 41, 'Đào Du', 'adult', 'cccd', '038245722123', 0, NULL, NULL, NULL, NULL, '038245722123', '2002-09-19', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-21 00:39:00', '2026-07-23 12:00:00', '2026-07-21 00:41:52', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Hậu Lộc', NULL, NULL, '2026-07-20 17:41:52', '2026-07-20 17:41:52'),
(11, 32, 42, 'Đào Du', 'adult', 'cccd', '038245722123', 0, NULL, NULL, NULL, NULL, '038245722123', '2002-09-19', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-21 00:46:00', '2026-07-26 12:00:00', '2026-07-21 00:50:21', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Hậu Lộc', NULL, NULL, '2026-07-20 17:50:21', '2026-07-20 17:50:21'),
(12, 29, 39, 'TRỊNH NGỌC CHIẾN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2008-08-14', 'male', 'Việt Nam', NULL, 1, NULL, NULL, '2026-07-21 14:00:00', '2026-07-24 12:00:00', '2026-07-21 02:04:43', NULL, 'checked_in', 'Khai báo khi check-in. Địa chỉ: Thôn Vệ 3', NULL, NULL, '2026-07-20 19:04:43', '2026-07-20 19:04:43'),
(13, 33, 43, 'Nguyễn Văn A', 'adult', 'cccd', '038206022411', 0, NULL, NULL, NULL, NULL, '038206022411', '2003-09-19', 'male', 'Việt Nam', 'huyện Yên Định', 1, NULL, NULL, '2026-07-21 04:32:00', '2026-07-25 12:00:00', '2026-07-21 06:03:19', NULL, 'checked_in', NULL, 4, 4, '2026-07-20 21:39:12', '2026-07-20 23:03:19'),
(14, 33, 43, 'Trịnh N C', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2006-09-19', 'male', 'Việt Nam', 'Thôn Vệ 3', 0, NULL, NULL, '2026-07-21 04:32:00', '2026-07-25 12:00:00', '2026-07-21 06:03:19', NULL, 'checked_in', NULL, 4, 4, '2026-07-20 21:53:39', '2026-07-20 23:03:19'),
(15, 33, 44, 'Ngô Văn C', 'adult', 'cccd', '038206022003', 0, NULL, NULL, NULL, NULL, '038206022003', '2004-05-21', 'male', 'Việt Nam', 'Da nang', 0, NULL, NULL, '2026-07-21 04:32:00', '2026-07-25 12:00:00', '2026-07-21 06:03:19', NULL, 'checked_in', NULL, 4, 4, '2026-07-20 21:54:47', '2026-07-20 23:03:19'),
(16, 33, 43, 'Trịnh Chiến', 'child', 'birth_certificate', '4325456432134565', 0, NULL, NULL, NULL, NULL, '4325456432134565', '2016-02-01', 'male', 'Việt Nam', 'thanh hoa', 0, 13, 'bố', '2026-07-21 04:32:00', '2026-07-25 12:00:00', '2026-07-21 06:03:19', NULL, 'checked_in', NULL, 4, 4, '2026-07-20 21:55:52', '2026-07-20 23:03:19'),
(17, 33, 44, 'Trịnh Ngọc Chiến', 'adult', 'birth_certificate', '5467865432456', 0, NULL, NULL, NULL, NULL, '5467865432456', '2008-02-21', 'male', 'Việt Nam', 'huyện Yên Định, tỉnh Thanh Hóa', 0, NULL, NULL, '2026-07-21 04:32:00', '2026-07-25 12:00:00', '2026-07-21 06:03:19', NULL, 'checked_in', NULL, 4, 4, '2026-07-20 21:56:40', '2026-07-20 23:03:19'),
(22, 36, 49, 'TRỊNH NGỌC CHIEN', 'adult', 'cccd', '038206022002', 0, NULL, NULL, NULL, NULL, '038206022002', '2006-09-19', 'male', 'Việt Nam', 'Thôn Vệ 3', 0, NULL, NULL, '2026-07-21 08:58:00', '2026-07-24 12:00:00', '2026-07-21 10:00:55', NULL, 'checked_in', NULL, 4, 4, '2026-07-21 02:00:46', '2026-07-21 03:29:28'),
(23, 36, 50, 'Chiến Trịnh', 'adult', 'cccd', '038206022003', 0, NULL, NULL, NULL, NULL, '038206022003', '2000-02-18', 'male', 'Việt Nam', 'Da nang', 0, NULL, NULL, '2026-07-21 08:58:00', '2026-07-24 12:00:00', '2026-07-21 10:00:55', NULL, 'checked_in', NULL, 4, 4, '2026-07-21 02:59:08', '2026-07-21 03:00:55'),
(24, 36, 55, 'Trịnh Ngọc C', 'adult', 'cccd', '038206022004', 0, NULL, NULL, NULL, NULL, '038206022004', '2007-12-13', 'male', 'Việt Nam', '123 Đh-yen-TH', 1, NULL, NULL, '2026-07-21 08:58:00', '2026-07-24 12:00:00', '2026-07-21 10:00:55', NULL, 'checked_in', NULL, 4, 4, '2026-07-21 02:59:47', '2026-07-21 03:29:38'),
(25, 36, 50, 'Nguyễn C', 'adult', 'cccd', '038206022001', 0, NULL, NULL, NULL, NULL, '038206022001', '2005-03-08', 'male', 'Việt Nam', '69B/62, Chi Lăng, Phường Quảng Phú, Tỉnh Thanh Hóa', 0, NULL, NULL, '2026-07-21 08:58:00', '2026-07-24 12:00:00', '2026-07-21 10:00:55', NULL, 'checked_in', NULL, 4, 4, '2026-07-21 03:00:17', '2026-07-21 03:00:55'),
(26, 36, 55, 'Bùi D', 'adult', 'cccd', '038206022005', 0, NULL, NULL, NULL, NULL, '038206022005', '2002-01-11', 'male', 'Việt Nam', 'Da nang', 0, NULL, NULL, '2026-07-21 08:58:00', '2026-07-24 12:00:00', '2026-07-21 10:28:41', NULL, 'checked_in', NULL, 4, 4, '2026-07-21 03:28:41', '2026-07-21 03:28:41'),
(27, 41, 60, 'Dương Cường', 'adult', 'cccd', '036206022065', 0, NULL, NULL, NULL, NULL, '036206022065', '2006-05-29', 'male', 'Việt Nam', 'thanh hoa', 1, NULL, NULL, '2026-07-21 14:28:00', '2026-07-25 12:00:00', '2026-07-21 14:33:52', NULL, 'checked_in', NULL, 7, 7, '2026-07-21 07:32:55', '2026-07-21 07:33:52'),
(28, 41, 60, 'Trịnh chiến', 'adult', 'cccd', '038206022037', 0, NULL, NULL, NULL, NULL, '038206022037', '2006-09-19', 'male', 'Việt Nam', '123 Đh-yen-TH', 0, NULL, NULL, '2026-07-21 14:28:00', '2026-07-25 12:00:00', '2026-07-21 14:33:52', NULL, 'checked_in', NULL, 7, 7, '2026-07-21 07:33:45', '2026-07-21 07:33:52'),
(30, 46, 65, 'Nguyễn Minh Anh', 'adult', 'cccd', '038200000001', 0, NULL, NULL, NULL, NULL, '038200000001', '1998-02-15', 'female', 'Việt Nam', 'Thành phố Thanh Hóa, Thanh Hóa', 1, NULL, NULL, '2026-08-22 14:00:00', '2026-08-25 12:00:00', '2026-08-22 00:25:02', NULL, 'checked_in', NULL, 4, 4, '2026-08-21 17:24:06', '2026-08-21 17:25:02'),
(31, 46, 65, 'Hồ Tuấn Minh', 'adult', 'none', NULL, 1, 'Bị mất chưa làm lại được', '2026-08-22 00:24:06', 4, 'Chiến Trịnh', NULL, '1970-08-20', 'male', 'Việt Nam', NULL, 0, NULL, NULL, '2026-08-22 14:00:00', '2026-08-25 12:00:00', '2026-08-22 00:25:02', NULL, 'checked_in', NULL, 4, 4, '2026-08-21 17:24:06', '2026-08-21 17:25:02'),
(32, 46, 70, 'Trần Đức Bo', 'adult', 'none', NULL, 1, 'Bị mất chưa làm lại được', '2026-08-22 03:33:05', 5, 'LT1', NULL, '1992-08-07', 'other', 'Việt Nam', NULL, 0, NULL, NULL, '2026-08-22 14:00:00', '2026-08-25 12:00:00', '2026-08-22 00:25:02', NULL, 'checked_in', NULL, 4, 5, '2026-08-21 17:24:06', '2026-08-21 20:33:05'),
(33, 47, 68, 'Trần Quốc Bảo', 'adult', 'cccd', '038200000002', 0, NULL, NULL, NULL, NULL, '038200000002', '1995-06-20', 'male', 'Việt Nam', 'Huyện Đông Sơn, Thanh Hóa', 1, NULL, NULL, '2026-08-22 06:37:00', '2026-08-28 12:00:00', '2026-08-22 06:39:09', NULL, 'checked_in', NULL, 5, 5, '2026-08-21 23:38:36', '2026-08-21 23:39:09'),
(34, 48, 71, 'Nguyễn Minh Anh', 'adult', 'cccd', '038200000001', 0, NULL, NULL, NULL, NULL, '038200000001', '1998-02-15', 'female', 'Việt Nam', 'Thành phố Thanh Hóa, Thanh Hóa', 1, NULL, NULL, '2026-08-22 14:00:00', '2026-08-25 12:00:00', '2026-08-22 08:46:54', NULL, 'checked_in', NULL, 5, 5, '2026-08-22 01:46:45', '2026-08-22 01:46:54'),
(35, 49, 72, 'Trần Quốc Bảo', 'adult', 'cccd', '038200000002', 0, NULL, NULL, NULL, NULL, '038200000002', '1995-06-20', 'male', 'Việt Nam', 'Huyện Đông Sơn, Thanh Hóa', 1, NULL, NULL, '2026-08-22 14:00:00', '2026-08-25 12:00:00', '2026-08-22 08:53:39', '2026-08-24 00:05:14', 'checked_out', NULL, 5, 4, '2026-08-22 01:53:20', '2026-08-23 17:05:14'),
(36, 50, 73, 'Nguyễn Minh Anh', 'adult', 'cccd', '038200000001', 0, NULL, NULL, NULL, NULL, '038200000001', '1998-02-15', 'female', 'Việt Nam', 'Thành phố Thanh Hóa, Thanh Hóa', 1, NULL, NULL, '2026-08-22 14:00:00', '2026-08-25 12:00:00', '2026-08-22 08:56:32', '2026-08-24 00:08:22', 'checked_out', NULL, 5, 5, '2026-08-22 01:56:25', '2026-08-23 17:08:22'),
(37, 53, 78, 'Trần Quốc Bảo', 'adult', 'cccd', '038200000002', 0, NULL, NULL, NULL, NULL, '038200000002', '1995-06-20', 'male', 'Việt Nam', 'Huyện Đông Sơn, Thanh Hóa', 1, NULL, NULL, '2026-08-24 14:00:00', '2026-08-26 12:00:00', '2026-08-24 05:51:49', NULL, 'checked_in', NULL, 5, 5, '2026-08-23 22:42:37', '2026-08-23 22:51:49'),
(38, 52, 77, 'Nguyễn Minh Anh', 'adult', 'cccd', '038200000001', 0, NULL, NULL, NULL, NULL, '038200000001', '1998-02-15', 'female', 'Việt Nam', 'Thành phố Thanh Hóa, Thanh Hóa', 1, NULL, NULL, '2026-08-24 14:00:00', '2026-08-26 12:00:00', '2026-08-24 10:16:57', NULL, 'checked_in', NULL, 5, 5, '2026-08-24 03:16:20', '2026-08-24 03:16:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_guest_room_histories`
--

CREATE TABLE `booking_guest_room_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_guest_id` bigint(20) UNSIGNED NOT NULL,
  `from_booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_booking_room_id` bigint(20) UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_guest_room_histories`
--

INSERT INTO `booking_guest_room_histories` (`id`, `booking_guest_id`, `from_booking_room_id`, `to_booking_room_id`, `started_at`, `ended_at`, `reason`, `changed_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 2, '2026-07-19 09:52:31', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(2, 2, NULL, 4, '2026-07-19 10:30:25', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(3, 3, NULL, 7, '2026-07-19 19:36:55', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(4, 4, NULL, 8, '2026-07-19 21:18:12', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(5, 5, NULL, 24, '2026-07-20 17:40:40', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(6, 6, NULL, 25, '2026-07-20 17:48:11', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(7, 7, NULL, 29, '2026-07-20 18:24:43', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(8, 8, NULL, 33, '2026-07-20 21:40:55', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(9, 12, NULL, 39, '2026-07-21 02:04:43', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(10, 9, NULL, 40, '2026-07-21 00:08:02', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(11, 10, NULL, 41, '2026-07-21 00:41:52', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(12, 11, NULL, 42, '2026-07-21 00:50:21', NULL, 'Dữ liệu phòng lưu trú được chuyển từ hệ thống cũ.', NULL, '2026-07-20 21:29:06', '2026-07-20 21:29:06'),
(16, 13, NULL, 43, '2026-07-21 04:39:12', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-20 21:39:12', '2026-07-20 21:39:12'),
(17, 14, NULL, 44, '2026-07-21 04:53:39', '2026-07-21 06:02:12', 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-20 21:53:39', '2026-07-20 23:02:12'),
(18, 15, NULL, 44, '2026-07-21 04:54:47', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-20 21:54:47', '2026-07-20 21:54:47'),
(19, 16, NULL, 43, '2026-07-21 04:55:52', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-20 21:55:52', '2026-07-20 21:55:52'),
(20, 17, NULL, 44, '2026-07-21 04:56:40', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-20 21:56:40', '2026-07-20 21:56:40'),
(21, 14, 44, 43, '2026-07-21 06:02:12', NULL, 'Lễ tân cập nhật phòng lưu trú của khách.', 4, '2026-07-20 23:02:12', '2026-07-20 23:02:12'),
(27, 22, NULL, 49, '2026-07-21 09:00:46', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-21 02:00:46', '2026-07-21 02:00:46'),
(28, 23, NULL, 49, '2026-07-21 09:59:08', '2026-07-21 10:00:35', 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-21 02:59:08', '2026-07-21 03:00:35'),
(29, 24, NULL, 50, '2026-07-21 09:59:47', '2026-07-21 10:24:41', 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-21 02:59:47', '2026-07-21 03:24:41'),
(30, 25, NULL, 50, '2026-07-21 10:00:17', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-21 03:00:17', '2026-07-21 03:00:17'),
(31, 23, 49, 50, '2026-07-21 10:00:35', NULL, 'Lễ tân cập nhật phòng lưu trú của khách.', 4, '2026-07-21 03:00:35', '2026-07-21 03:00:35'),
(32, 24, 50, 55, '2026-07-21 10:24:41', NULL, 'Lễ tân cập nhật phòng lưu trú của khách.', 4, '2026-07-21 03:24:41', '2026-07-21 03:24:41'),
(33, 26, NULL, 55, '2026-07-21 10:28:41', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-07-21 03:28:41', '2026-07-21 03:28:41'),
(34, 27, NULL, 60, '2026-07-21 14:32:56', NULL, 'Khai báo phòng lưu trú ban đầu.', 7, '2026-07-21 07:32:56', '2026-07-21 07:32:56'),
(35, 28, NULL, 60, '2026-07-21 14:33:45', NULL, 'Khai báo phòng lưu trú ban đầu.', 7, '2026-07-21 07:33:45', '2026-07-21 07:33:45'),
(37, 30, NULL, 65, '2026-08-22 00:24:06', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-08-21 17:24:06', '2026-08-21 17:24:06'),
(38, 31, NULL, 65, '2026-08-22 00:24:06', NULL, 'Khai báo phòng lưu trú ban đầu.', 4, '2026-08-21 17:24:06', '2026-08-21 17:24:06'),
(39, 32, NULL, 65, '2026-08-22 00:24:06', '2026-08-22 03:33:05', 'Khai báo phòng lưu trú ban đầu.', 4, '2026-08-21 17:24:06', '2026-08-21 20:33:05'),
(40, 32, 65, 70, '2026-08-22 03:33:05', NULL, 'Lễ tân cập nhật phòng lưu trú của khách.', 5, '2026-08-21 20:33:05', '2026-08-21 20:33:05'),
(41, 33, NULL, 68, '2026-08-22 06:38:36', NULL, 'Khai báo phòng lưu trú ban đầu.', 5, '2026-08-21 23:38:36', '2026-08-21 23:38:36'),
(42, 34, NULL, 71, '2026-08-22 08:46:45', NULL, 'Khai báo phòng lưu trú ban đầu.', 5, '2026-08-22 01:46:45', '2026-08-22 01:46:45'),
(43, 35, NULL, 72, '2026-08-22 08:53:20', '2026-08-24 00:05:14', 'Khai báo phòng lưu trú ban đầu.', 5, '2026-08-22 01:53:20', '2026-08-23 17:05:14'),
(44, 36, NULL, 73, '2026-08-22 08:56:25', '2026-08-24 00:08:22', 'Khai báo phòng lưu trú ban đầu.', 5, '2026-08-22 01:56:25', '2026-08-23 17:08:22'),
(45, 37, NULL, 78, '2026-08-24 05:42:37', NULL, 'Khai báo phòng lưu trú ban đầu.', 5, '2026-08-23 22:42:37', '2026-08-23 22:42:37'),
(46, 38, NULL, 77, '2026-08-24 10:16:20', NULL, 'Khai báo phòng lưu trú ban đầu.', 5, '2026-08-24 03:16:20', '2026-08-24 03:16:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_logs`
--

CREATE TABLE `booking_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_logs`
--

INSERT INTO `booking_logs` (`id`, `booking_id`, `user_id`, `action`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 300.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-18 06:42:30', '2026-07-18 06:42:30'),
(2, 1, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-18 06:42:36', '2026-07-18 06:42:36'),
(3, 1, NULL, 'system_no_show_cancelled', 'Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 300.000đ; không hoàn lại, không bảo lưu.', '2026-07-19 02:38:02', '2026-07-19 02:38:02'),
(4, 2, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 02:49:44', '2026-07-19 02:49:44'),
(5, 2, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-19 02:49:53', '2026-07-19 02:49:53'),
(6, 3, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 02:51:13', '2026-07-19 02:51:13'),
(7, 3, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-19 02:51:18', '2026-07-19 02:51:18'),
(8, 2, 4, 'check_in', 'Xác nhận check-in thực tế: 3 người lớn / 0 trẻ em / 0 em bé. Đã thu phụ phí phát sinh khi check-in: Phụ thu thêm người lớn x 1: 200.000đ. Tổng phụ thu: 200.000đ. Check-in sớm trong cùng ngày lúc 19/07/2026 09:52, sớm hơn giờ chuẩn 4 giờ 7 phút. Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm. Phụ thu: 200.000đ.', '2026-07-19 02:52:31', '2026-07-19 02:52:31'),
(9, 2, 14, 'room_issue_requested', 'Khách báo sự cố tại phòng 501 và gửi yêu cầu đổi phòng. Nội dung: điều hòa không hoạt động', '2026-07-19 02:53:47', '2026-07-19 02:53:47'),
(10, 2, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: đổi hạng miễn phí từ phòng 501 (Phòng demo) sang phòng 101 (Deluxe Sea View). Mã bù đắp: DEMO_INCIDENT_FULL. Ghi chú: xác nhận hỏng điều hòa', '2026-07-19 02:55:41', '2026-07-19 02:55:41'),
(11, 2, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. đã sửa xong', '2026-07-19 02:56:29', '2026-07-19 02:56:29'),
(12, 3, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 22/07/2026 14:00 → 25/07/2026 12:00 sang 19/07/2026 09:57 → 25/07/2026 12:00. Chênh lệch tiền phòng: 3.000.000đ.', '2026-07-19 02:57:33', '2026-07-19 02:57:33'),
(13, 3, 15, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 900.000đ; không hoàn lại, không bảo lưu.', '2026-07-19 02:58:07', '2026-07-19 02:58:07'),
(14, 2, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-07-19 02:59:31', '2026-07-19 02:59:31'),
(15, 2, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-07-19 02:59:41', '2026-07-19 02:59:41'),
(16, 2, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-07-19 02:59:56', '2026-07-19 02:59:56'),
(17, 2, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 2.300.000đ. Đã thu: 3.200.000đ / 3.400.000đ. Trạng thái thanh toán: partial → partial. Mã giao dịch: CASHBK202607190949110GW20260719100028F7RRE', '2026-07-19 03:00:28', '2026-07-19 03:00:28'),
(18, 2, 4, 'check_out', 'Xác nhận check-out lúc 19/07/2026 10:16. Phòng chuyển sang cần dọn: 101. Tiền phòng: 3.600.000đ. Dịch vụ/phụ thu: 400.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 3.200.000đ. Đã thu trước check-out: 3.200.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trước khi check-out, không cần thu thêm. Không phát sinh phụ thu check-out.', '2026-07-19 03:16:02', '2026-07-19 03:16:02'),
(19, 4, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 03:30:01', '2026-07-19 03:30:01'),
(20, 4, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-19 03:30:08', '2026-07-19 03:30:08'),
(21, 4, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 19/07/2026 10:30, sớm hơn giờ chuẩn 3 giờ 29 phút. Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm. Phụ thu: 200.000đ.', '2026-07-19 03:30:25', '2026-07-19 03:30:25'),
(22, 4, 14, 'room_issue_requested', 'Khách báo sự cố tại phòng 501 và gửi yêu cầu đổi phòng. Nội dung: tắc cống', '2026-07-19 03:31:15', '2026-07-19 03:31:15'),
(23, 4, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: không còn phòng phù hợp để đổi; giữ nguyên phòng 501 và chuyển buồng phòng sửa gấp. Mã bù đắp: DEMO200K, WELCOME200BF. Ghi chú: xác nhận phòng ngập nước', '2026-07-19 03:34:42', '2026-07-19 03:34:42'),
(24, 4, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. đã sửa xong cống', '2026-07-19 03:36:06', '2026-07-19 03:36:06'),
(25, 4, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 501.', '2026-07-19 03:39:41', '2026-07-19 03:39:41'),
(26, 4, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 501: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-07-19 03:40:02', '2026-07-19 03:40:02'),
(27, 4, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 501. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-07-19 03:40:09', '2026-07-19 03:40:09'),
(28, 4, 4, 'checkout_fee_added', 'Thêm phí phát sinh trước check-out: mất thẻ phòng - 100.000đ', '2026-07-19 04:49:54', '2026-07-19 04:49:54'),
(29, 4, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 1.300.000đ. Đã thu: 2.200.000đ / 2.900.000đ. Trạng thái thanh toán: partial → partial.', '2026-07-19 04:56:39', '2026-07-19 05:17:58'),
(30, 4, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 700.000đ. Đã thu: 2.900.000đ / 2.900.000đ. Trạng thái thanh toán: partial → paid. Khách đưa/chuyển 1.000.000đ; tiền thừa cần trả/hoàn khách: 300.000đ.', '2026-07-19 04:56:57', '2026-07-19 05:17:58'),
(31, 4, 4, 'check_out', 'Xác nhận check-out lúc 19/07/2026 12:19. Phòng chuyển sang cần dọn: 501. Tiền phòng: 3.000.000đ. Dịch vụ/phụ thu: 480.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 2.900.000đ. Đã thu trước check-out: 2.900.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-19 05:19:44', '2026-07-19 05:19:44'),
(32, 5, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 12:23:15', '2026-07-19 12:23:15'),
(33, 5, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-19 12:23:21', '2026-07-19 12:23:21'),
(34, 5, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 20/07/2026 14:00 → 23/07/2026 12:00 sang 19/07/2026 19:23 → 23/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.', '2026-07-19 12:23:42', '2026-07-19 12:23:42'),
(35, 5, NULL, 'system_no_show_cancelled', 'Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 900.000đ; không hoàn lại, không bảo lưu.', '2026-07-19 12:24:01', '2026-07-19 12:24:01'),
(36, 6, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 600.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 12:25:41', '2026-07-19 12:25:41'),
(37, 6, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-19 12:25:51', '2026-07-19 12:25:51'),
(38, 6, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 20/07/2026 14:00 → 22/07/2026 12:00 sang 19/07/2026 19:26 → 22/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.', '2026-07-19 12:26:29', '2026-07-19 12:26:29'),
(39, 6, NULL, 'system_no_show_cancelled', 'Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 600.000đ; không hoàn lại, không bảo lưu.', '2026-07-19 12:27:01', '2026-07-19 12:27:01'),
(40, 7, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 600.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 12:36:30', '2026-07-19 12:36:30'),
(41, 7, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-19 12:36:35', '2026-07-19 12:36:35'),
(42, 7, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 20/07/2026 14:00 → 22/07/2026 12:00 sang 19/07/2026 19:36 → 22/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.', '2026-07-19 12:36:45', '2026-07-19 12:36:45'),
(43, 7, 4, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách đến muộn nhưng vẫn trước giờ G 21:36 19/07/2026. Cho check-in bình thường; các phụ thu phát sinh khác được chốt khi check-out.', '2026-07-19 12:36:55', '2026-07-19 12:36:55'),
(44, 7, 14, 'room_issue_requested', 'Khách báo sự cố tại phòng 501 và gửi yêu cầu đổi phòng. Nội dung: hỏng điều hòa', '2026-07-19 12:37:19', '2026-07-19 12:37:19'),
(45, 7, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: đổi hạng miễn phí từ phòng 501 (Phòng demo) sang phòng 101 (Deluxe Sea View). Mã bù đắp: DEMO_INCIDENT_FULL. Ghi chú: hư điều hòa', '2026-07-19 12:37:43', '2026-07-19 12:37:43'),
(46, 7, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-07-19 12:39:22', '2026-07-19 12:39:22'),
(47, 7, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: dịch vụ tại phòng: Bia x3 = 30.000đ — tạm tính 30.000đ. Chờ admin duyệt.', '2026-07-19 12:39:39', '2026-07-19 12:39:39'),
(48, 7, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Dịch vụ tại phòng được duyệt: 30.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 30.000đ. Mục duyệt: dịch vụ tại phòng - Bia x3 = 30.000đ.', '2026-07-19 12:39:50', '2026-07-19 12:39:50'),
(49, 7, 4, 'checkout_fee_added', 'Thêm phí phát sinh trước check-out: mất thẻ phòng - 100.000đ', '2026-07-19 12:40:28', '2026-07-19 12:40:28'),
(50, 7, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 2.530.000đ. Đã thu: 3.130.000đ / 3.130.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK202607191936100WP20260719194046LDPH9. Khách đưa/chuyển thừa 470.000đ; cần trả/hoàn lại khách.', '2026-07-19 12:40:46', '2026-07-19 12:40:46'),
(51, 7, 4, 'check_out', 'Xác nhận check-out lúc 19/07/2026 19:40. Phòng chuyển sang cần dọn: 101. Tiền phòng: 3.600.000đ. Dịch vụ/phụ thu: 100.000đ. Minibar/hư hại duyệt: 30.000đ. Tổng phải thu: 3.130.000đ. Đã thu trước check-out: 3.130.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-19 12:40:54', '2026-07-19 12:40:54'),
(52, 8, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.080.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-19 14:17:42', '2026-07-19 14:17:42'),
(53, 8, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-19 14:17:47', '2026-07-19 14:17:47'),
(54, 8, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 20/07/2026 14:00 → 23/07/2026 12:00 sang 19/07/2026 21:18 → 23/07/2026 12:00. Chênh lệch tiền phòng: 1.200.000đ.', '2026-07-19 14:18:05', '2026-07-19 14:18:05'),
(55, 8, 4, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách đến muộn nhưng vẫn trước giờ G 23:18 19/07/2026. Cho check-in bình thường; các phụ thu phát sinh khác được chốt khi check-out.', '2026-07-19 14:18:12', '2026-07-19 14:18:12'),
(56, 8, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 103.', '2026-07-19 14:18:35', '2026-07-19 14:18:35'),
(57, 8, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 103: dịch vụ tại phòng: Bia x2 = 20.000đ — tạm tính 20.000đ. Chờ admin duyệt.', '2026-07-19 14:18:53', '2026-07-19 14:18:53'),
(58, 8, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 103. Dịch vụ tại phòng được duyệt: 20.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 20.000đ. Mục duyệt: dịch vụ tại phòng - Bia x2 = 20.000đ.', '2026-07-19 14:19:24', '2026-07-19 14:19:24'),
(59, 8, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 3.740.000đ. Đã thu: 4.820.000đ / 4.820.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK20260719211711IQK20260719211951FG2ZF. Khách đưa/chuyển thừa 260.000đ; cần trả/hoàn lại khách.', '2026-07-19 14:19:51', '2026-07-19 14:19:51'),
(60, 8, 4, 'check_out', 'Xác nhận check-out lúc 19/07/2026 21:20. Phòng chuyển sang cần dọn: 103. Tiền phòng: 4.800.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 20.000đ. Tổng phải thu: 4.820.000đ. Đã thu trước check-out: 4.820.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-19 14:20:01', '2026-07-19 14:20:01'),
(61, 7, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. đã xong', '2026-07-19 14:20:41', '2026-07-19 14:20:41'),
(62, 9, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 03:06:17', '2026-07-20 03:06:17'),
(63, 9, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 03:06:24', '2026-07-20 03:06:24'),
(64, 9, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 270.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 03:06:41', '2026-07-20 03:06:41'),
(65, 10, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 03:07:22', '2026-07-20 03:07:22'),
(66, 10, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 03:07:29', '2026-07-20 03:07:29'),
(67, 10, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 540.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 03:09:00', '2026-07-20 03:09:00'),
(68, 11, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 03:09:49', '2026-07-20 03:09:49'),
(69, 11, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 03:09:55', '2026-07-20 03:09:55'),
(70, 11, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 540.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 03:11:27', '2026-07-20 03:11:27'),
(71, 12, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 03:13:21', '2026-07-20 03:13:21'),
(72, 12, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 03:13:27', '2026-07-20 03:13:27'),
(73, 12, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 540.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 03:13:31', '2026-07-20 03:13:31'),
(74, 13, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 810.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 03:25:02', '2026-07-20 03:25:02'),
(75, 13, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 03:25:08', '2026-07-20 03:25:08'),
(76, 13, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 810.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 03:26:01', '2026-07-20 03:26:01'),
(77, 14, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 03:26:51', '2026-07-20 03:26:51'),
(78, 14, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 03:26:57', '2026-07-20 03:26:57'),
(79, 14, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ tiền cọc 30% đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 540.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 03:27:01', '2026-07-20 03:27:01'),
(80, 15, 4, 'promotion_added', 'Áp dụng mã ưu đãi khi tạo booking: DEMO_FREE_BF, DEMO200K. Giảm tiền: 200.000đ, ưu đãi dịch vụ: 180.000đ, tổng ưu đãi: 380.000đ.', '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(81, 15, 4, 'booking_created', 'Tạo booking đặt trước - qua đêm bởi lễ tân. Gán phòng: 203, 202. Thời gian: 20/07/2026 14:00 - 24/07/2026 12:00. Ưu đãi giảm: 380.000đ. Tổng tiền tạm tính: 7.000.000đ.', '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(82, 15, 4, 'admin_vnpay_created', 'Tạo giao dịch VNPay khi tạo booking: 2.100.000đ (cọc 30%). Mã giao dịch: ADMVNPBK260720K3R9M20260720111453X2MBV.', '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(83, 16, 4, 'promotion_added', 'Áp dụng mã ưu đãi khi tạo booking: DEMO_EVENT10. Giảm tiền: 300.000đ, ưu đãi dịch vụ: 0đ, tổng ưu đãi: 300.000đ.', '2026-07-20 04:33:40', '2026-07-20 04:33:40'),
(84, 16, 4, 'booking_created', 'Tạo booking đặt trước - qua đêm bởi lễ tân. Gán phòng: 202, 203. Thời gian: 20/07/2026 14:00 - 22/07/2026 12:00. Ưu đãi giảm: 300.000đ. Tổng tiền tạm tính: 3.300.000đ.', '2026-07-20 04:33:40', '2026-07-20 04:33:40'),
(85, 16, 4, 'admin_vnpay_created', 'Tạo giao dịch VNPay khi tạo booking: 990.000đ (cọc 30%). Mã giao dịch: ADMVNPBK260720VVHDR20260720113340UTPK2.', '2026-07-20 04:33:40', '2026-07-20 04:33:40'),
(86, 16, 4, 'admin_vnpay_email_sent', 'Đã gửi email thanh toán VNPay cho khách vãng lai chientr319@gmail.com. Số tiền cọc: 990.000đ. Hạn thanh toán: 20/07/2026 11:48.', '2026-07-20 04:33:48', '2026-07-20 04:33:48'),
(87, 16, NULL, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 990.000đ. Trạng thái thanh toán: partial. Giao dịch tạo từ admin..', '2026-07-20 04:34:43', '2026-07-20 04:34:43'),
(88, 16, NULL, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến chientr319@gmail.com.', '2026-07-20 04:34:48', '2026-07-20 04:34:48'),
(89, 15, NULL, 'guest_lookup_otp_sent', 'Đã gửi OTP tra cứu booking vãng lai đến email ch********@gmail.com.', '2026-07-20 04:48:02', '2026-07-20 04:48:02'),
(90, 15, NULL, 'guest_cancelled_via_lookup', 'Khách vãng lai tự hủy qua trang tra cứu xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền giữ lại: 0đ; không hoàn lại, không bảo lưu.', '2026-07-20 04:48:50', '2026-07-20 04:48:50'),
(91, 15, NULL, 'guest_cancellation_reason', 'Lý do khách cung cấp: thay đổi kế hoạch', '2026-07-20 04:48:50', '2026-07-20 04:48:50'),
(92, 16, NULL, 'guest_lookup_otp_sent', 'Đã gửi OTP tra cứu booking vãng lai đến email ch********@gmail.com.', '2026-07-20 04:49:19', '2026-07-20 04:49:19'),
(93, 16, NULL, 'guest_cancelled_via_lookup', 'Khách vãng lai tự hủy qua trang tra cứu xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 990.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 05:00:49', '2026-07-20 05:00:49'),
(94, 16, NULL, 'guest_cancellation_reason', 'Lý do khách cung cấp: chọn nhầm ngày', '2026-07-20 05:00:49', '2026-07-20 05:00:49'),
(95, 17, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 600.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 08:02:05', '2026-07-20 08:02:05'),
(96, 17, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 08:02:11', '2026-07-20 08:02:11'),
(97, 17, 4, 'late_arrival_hold_extended', 'Lễ tân xác nhận khách sẽ đến sau giờ G. Giờ G: 20/07/2026 18:00. Giờ khách dự kiến đến: 20/07/2026 20:00. Phòng được giữ tiếp đến: 20/07/2026 20:30. Chính sách: Khách dự kiến đến sau 18:00 đến 21:00, phụ thu 20% giá 1 đêm để tiếp tục giữ phòng. Phụ thu khách đến muộn: 200.000đ. Tổng booking sau phụ thu: 2.200.000đ.', '2026-07-20 08:36:05', '2026-07-20 08:36:05'),
(98, 17, 4, 'receptionist_no_show_cancelled', 'Lễ tân xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 600.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 09:02:44', '2026-07-20 09:02:44'),
(99, 18, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 09:04:13', '2026-07-20 09:04:13'),
(100, 18, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 09:04:20', '2026-07-20 09:04:20'),
(101, 19, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 300.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 09:05:35', '2026-07-20 09:05:35'),
(102, 19, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-20 09:05:41', '2026-07-20 09:05:41'),
(103, 18, NULL, 'system_no_show_cancelled', 'Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 540.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 09:17:40', '2026-07-20 09:17:40'),
(104, 19, NULL, 'system_no_show_cancelled', 'Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 300.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 09:19:11', '2026-07-20 09:19:11'),
(105, 20, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 09:32:31', '2026-07-20 09:32:31'),
(106, 20, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 09:32:36', '2026-07-20 09:32:36'),
(107, 21, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 300.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 09:33:17', '2026-07-20 09:33:17'),
(108, 21, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-20 09:33:22', '2026-07-20 09:33:22'),
(109, 21, 4, 'late_arrival_hold_extended', 'Lễ tân xác nhận khách sẽ đến sau giờ G. Giờ G: 20/07/2026 18:00. Giờ khách dự kiến đến: 20/07/2026 22:30. Phòng được giữ tiếp đến: 20/07/2026 23:00. Chính sách: Khách dự kiến đến sau 21:00 đến trước 00:00, phụ thu 50% giá 1 đêm để tiếp tục giữ phòng. Phụ thu khách đến muộn: 500.000đ. Tổng booking sau phụ thu: 2.500.000đ.', '2026-07-20 09:36:40', '2026-07-20 09:36:40'),
(110, 20, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 20/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 20/07/2026 18:01. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 540.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 09:37:59', '2026-07-20 09:37:59'),
(111, 21, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước hạn giữ phòng đã gia hạn. Giờ G ban đầu: 20/07/2026 18:00. Khách đã xác nhận dự kiến đến lúc: 20/07/2026 22:30. Hạn giữ mới: 20/07/2026 23:00. Thời điểm hệ thống xử lý: 20/07/2026 23:01. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 300.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 09:38:32', '2026-07-20 09:38:32'),
(112, 22, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 10:39:43', '2026-07-20 10:39:43'),
(113, 22, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 10:39:49', '2026-07-20 10:39:49'),
(114, 22, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 21/07/2026 14:00 → 24/07/2026 12:00 sang 20/07/2026 17:40 → 24/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.', '2026-07-20 10:40:24', '2026-07-20 10:40:24'),
(115, 22, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-07-20 10:40:40', '2026-07-20 10:40:40'),
(116, 23, 4, 'promotion_added', 'Áp dụng mã ưu đãi khi tạo booking: WELCOME200BF, FAMILY10DECOR. Giảm tiền: 500.000đ, ưu đãi dịch vụ: 180.000đ, tổng ưu đãi: 680.000đ.', '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(117, 23, 4, 'booking_created', 'Tạo booking đặt trước - qua đêm bởi lễ tân. Gán phòng: 405, 404, 403, 402. Thời gian: 20/07/2026 14:00 - 24/07/2026 12:00. Ưu đãi giảm: 680.000đ. Tổng tiền tạm tính: 18.700.000đ.', '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(118, 23, 4, 'admin_vnpay_created', 'Tạo giao dịch VNPay khi tạo booking: 5.610.000đ (cọc 30%). Mã giao dịch: ADMVNPBK2607203IW1I20260720174619XRZXP.', '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(119, 23, 4, 'admin_vnpay_email_sent', 'Đã gửi email thanh toán VNPay cho khách vãng lai chientr319@gmail.com. Số tiền cọc: 5.610.000đ. Hạn thanh toán: 20/07/2026 18:01.', '2026-07-20 10:46:25', '2026-07-20 10:46:25'),
(120, 23, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 5.610.000đ. Trạng thái thanh toán: partial. Giao dịch tạo từ admin..', '2026-07-20 10:47:29', '2026-07-20 10:47:29'),
(121, 23, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến chientr319@gmail.com.', '2026-07-20 10:47:35', '2026-07-20 10:47:35'),
(122, 23, 4, 'check_in', 'Xác nhận check-in thực tế: 4 người lớn / 1 trẻ em / 0 em bé. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-07-20 10:48:11', '2026-07-20 10:48:11'),
(123, 23, 4, 'room_issue_form_emailed', 'Lễ tân đã gửi biểu mẫu báo sự cố tới email chientr319@gmail.com. Có 4 phòng có thể chọn báo sự cố.', '2026-07-20 10:48:29', '2026-07-20 10:48:29'),
(124, 22, 14, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 501 và gửi yêu cầu xử lý. Nội dung: hư điều hòa', '2026-07-20 10:49:16', '2026-07-20 10:49:16'),
(125, 23, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 405 và gửi yêu cầu xử lý. Nội dung: tv bật không được', '2026-07-20 10:50:35', '2026-07-20 10:50:35'),
(126, 23, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 404 và gửi yêu cầu xử lý. Nội dung: phòng rột trần', '2026-07-20 10:50:35', '2026-07-20 10:50:35'),
(127, 23, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: đổi phòng cùng hạng từ phòng 405 sang phòng 101. Mã bù đắp: SUPPORT100K. Ghi chú: xác nhận lỗi', '2026-07-20 10:51:29', '2026-07-20 10:51:29'),
(128, 23, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: đổi phòng cùng hạng từ phòng 404 sang phòng 102. Mã bù đắp: không áp dụng. Ghi chú: xác nhận lỗi', '2026-07-20 10:51:43', '2026-07-20 10:51:43'),
(129, 22, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: đổi hạng miễn phí từ phòng 501 (Phòng demo) sang phòng 103 (Deluxe Sea View). Mã bù đắp: DEMO_INCIDENT_FULL. Ghi chú: xác nhận lỗi', '2026-07-20 10:52:03', '2026-07-20 10:52:03'),
(130, 22, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. xác sửa xong', '2026-07-20 10:52:29', '2026-07-20 10:52:29'),
(131, 23, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 404. xác sửa xong', '2026-07-20 10:52:36', '2026-07-20 10:52:36'),
(132, 23, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 405. xác sửa xong', '2026-07-20 10:52:44', '2026-07-20 10:52:44'),
(133, 23, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101, 102, 403, 402.', '2026-07-20 10:56:33', '2026-07-20 10:56:33'),
(134, 23, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-07-20 10:56:49', '2026-07-20 10:56:49'),
(135, 23, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 102: dịch vụ tại phòng: Bia x2 = 20.000đ — tạm tính 20.000đ. Chờ admin duyệt.', '2026-07-20 10:56:55', '2026-07-20 10:56:55'),
(136, 23, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 403: dịch vụ tại phòng: Coca Cola x1 = 25.000đ — tạm tính 25.000đ. Chờ admin duyệt.', '2026-07-20 10:57:04', '2026-07-20 10:57:04'),
(137, 23, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 402: hư hại: Hỏng TV x1 = 3.000.000đ — tạm tính 3.000.000đ. Chờ admin duyệt.', '2026-07-20 10:57:12', '2026-07-20 10:57:12'),
(138, 23, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-07-20 10:57:23', '2026-07-20 10:57:23'),
(139, 23, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 102. Dịch vụ tại phòng được duyệt: 20.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 20.000đ. Mục duyệt: dịch vụ tại phòng - Bia x2 = 20.000đ.', '2026-07-20 10:57:28', '2026-07-20 10:57:28'),
(140, 23, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 403. Dịch vụ tại phòng được duyệt: 25.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 25.000đ. Mục duyệt: dịch vụ tại phòng - Coca Cola x1 = 25.000đ.', '2026-07-20 10:57:35', '2026-07-20 10:57:35'),
(141, 23, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 402. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 3.000.000đ. Tổng cộng: 3.000.000đ. Mục duyệt: hư hại - Hỏng TV x1 = 3.000.000đ.', '2026-07-20 10:57:40', '2026-07-20 10:57:40'),
(142, 23, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 16.035.000đ. Đã thu: 21.645.000đ / 21.645.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK2607203IW1I20260720175822GZ3AP. Khách đưa/chuyển thừa 465.000đ; cần trả/hoàn lại khách.', '2026-07-20 10:58:22', '2026-07-20 10:58:22'),
(143, 23, 4, 'check_out', 'Xác nhận check-out lúc 20/07/2026 17:58. Phòng chuyển sang cần dọn: 101, 102, 403, 402. Tiền phòng: 19.200.000đ. Dịch vụ/phụ thu: 180.000đ. Minibar/hư hại duyệt: 3.045.000đ. Tổng phải thu: 21.645.000đ. Đã thu trước check-out: 21.645.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 10:58:31', '2026-07-20 10:58:31'),
(144, 22, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 103.', '2026-07-20 10:59:14', '2026-07-20 10:59:14'),
(145, 22, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 103: dịch vụ tại phòng: Bia x1 = 10.000đ — tạm tính 10.000đ. Chờ admin duyệt.', '2026-07-20 10:59:25', '2026-07-20 10:59:25'),
(146, 22, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 103. Dịch vụ tại phòng được duyệt: 10.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 10.000đ. Mục duyệt: dịch vụ tại phòng - Bia x1 = 10.000đ.', '2026-07-20 10:59:42', '2026-07-20 10:59:42'),
(147, 22, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 3.110.000đ. Đã thu: 4.010.000đ / 4.010.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK20260720173922A6S20260720180020THOCG', '2026-07-20 11:00:20', '2026-07-20 11:00:20'),
(148, 22, 4, 'check_out', 'Xác nhận check-out lúc 20/07/2026 18:00. Phòng chuyển sang cần dọn: 103. Tiền phòng: 4.800.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 10.000đ. Tổng phải thu: 4.010.000đ. Đã thu trước check-out: 4.010.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 11:00:25', '2026-07-20 11:00:25'),
(149, 24, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 600.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 11:01:46', '2026-07-20 11:01:46'),
(150, 24, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 11:01:52', '2026-07-20 11:01:52'),
(151, 24, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 21/07/2026 14:00 → 23/07/2026 12:00 sang 20/07/2026 18:02 → 23/07/2026 12:00. Chênh lệch tiền phòng: 1.000.000đ.', '2026-07-20 11:02:01', '2026-07-20 11:02:01'),
(152, 24, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Đơn được lễ tân chuyển từ ngày tương lai về hôm nay lúc 20/07/2026 18:02. Đây là đổi ngày nhận phòng, không phải khách đến muộn và không phát sinh phụ thu sau giờ G.', '2026-07-20 11:24:43', '2026-07-20 11:24:43'),
(153, 24, 14, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 501 và gửi yêu cầu xử lý. Nội dung: hưu điều hòa', '2026-07-20 11:25:52', '2026-07-20 11:25:52'),
(154, 24, 4, 'manager_approved_room_issue', 'Quản lý đã phê duyệt sự cố: đổi hạng miễn phí từ phòng 501 (Phòng demo) sang phòng 101 (Deluxe Sea View). Mã bù đắp: DEMO_INCIDENT_FULL. Ghi chú: hưu điều hòa', '2026-07-20 11:26:52', '2026-07-20 11:26:52'),
(155, 24, 14, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 101 và gửi yêu cầu xử lý. Nội dung: hỏng vòi sen', '2026-07-20 11:38:40', '2026-07-20 11:38:40'),
(156, 24, 4, 'room_issue_auto_proposal_created', 'Hệ thống tự lập phương án theo thứ tự cùng hạng → nâng hạng → sửa gấp: phòng 101: Đổi phòng cùng hạng sang phòng 102. Phòng thay thế được giữ cố định 30 phút đến 20/07/2026 21:18.', '2026-07-20 13:48:00', '2026-07-20 13:48:00'),
(157, 24, 4, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 101: giữ nguyên phòng và sửa gấp.', '2026-07-20 13:48:39', '2026-07-20 13:48:39'),
(158, 24, 4, 'room_issue_auto_proposal_created', 'Hệ thống tự lập phương án theo thứ tự cùng hạng → nâng hạng → sửa gấp: phòng 101: Giữ nguyên phòng, sửa gấp. Phòng thay thế được giữ cố định 30 phút đến 20/07/2026 21:19.', '2026-07-20 13:49:07', '2026-07-20 13:49:07'),
(159, 24, 4, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 101: giữ nguyên phòng và sửa gấp.', '2026-07-20 13:49:35', '2026-07-20 13:49:35'),
(160, 24, 4, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 101 giữ nguyên và sửa gấp. Mã hỗ trợ: không áp dụng.', '2026-07-20 13:50:51', '2026-07-20 13:50:51'),
(161, 25, 4, 'booking_created', 'Tạo booking ở ngay - qua đêm bởi lễ tân. Gán phòng: 405, 404, 403. Thời gian: 20/07/2026 21:22 - 23/07/2026 12:00. Chính sách giá: Check-in từ 14:00, không phụ thu. Khách ở 3 đêm và trả phòng lúc 12:00 ngày 23/07/2026.. Tổng tiền tạm tính: 10.800.000đ.', '2026-07-20 14:23:53', '2026-07-20 14:23:53'),
(162, 25, 4, 'admin_payment_received', 'Thu tiền khi tạo booking bằng tiền mặt tại quầy: 3.240.000đ. Trạng thái thanh toán: partial. Mã giao dịch: CASHBK2607200E8TP202607202123533G0ZR.', '2026-07-20 14:23:53', '2026-07-20 14:23:53'),
(163, 25, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 20/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 20/07/2026 21:24. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 3.240.000đ; không hoàn lại, không bảo lưu.', '2026-07-20 14:24:01', '2026-07-20 14:24:01'),
(164, 25, 4, 'booking_email_sent', 'Đã gửi email xác nhận booking đến chientr319@gmail.com.', '2026-07-20 14:24:04', '2026-07-20 14:24:04'),
(165, 26, 4, 'booking_created', 'Tạo booking ở ngay - qua đêm bởi lễ tân. Gán phòng: 404, 403, 405, 402. Thời gian: 20/07/2026 21:39 - 23/07/2026 12:00. Chính sách giá: Check-in từ 14:00, không phụ thu. Khách ở 3 đêm và trả phòng lúc 12:00 ngày 23/07/2026.. Tổng tiền tạm tính: 14.400.000đ.', '2026-07-20 14:40:10', '2026-07-20 14:40:10'),
(166, 26, 4, 'admin_payment_received', 'Thu tiền khi tạo booking bằng tiền mặt tại quầy: 4.320.000đ. Trạng thái thanh toán: partial. Mã giao dịch: CASHBK260720SPUTZ20260720214010NMZHM.', '2026-07-20 14:40:10', '2026-07-20 14:40:10'),
(167, 26, 4, 'booking_email_sent', 'Đã gửi email xác nhận booking đến chientr319@gmail.com.', '2026-07-20 14:40:19', '2026-07-20 14:40:19'),
(168, 26, 4, 'check_in', 'Xác nhận check-in thực tế: 6 người lớn / 0 trẻ em / 0 em bé. ', '2026-07-20 14:40:55', '2026-07-20 14:40:55'),
(169, 26, 4, 'room_issue_form_emailed', 'Lễ tân đã gửi biểu mẫu báo sự cố tới email chientr319@gmail.com. Có 4 phòng có thể chọn báo sự cố.', '2026-07-20 14:44:23', '2026-07-20 14:44:23'),
(170, 26, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 404 và gửi yêu cầu xử lý. Nội dung: đèn không sáng', '2026-07-20 14:45:38', '2026-07-20 14:45:38'),
(171, 26, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 403 và gửi yêu cầu xử lý. Nội dung: điều hòa k chạy', '2026-07-20 14:45:39', '2026-07-20 14:45:39'),
(172, 26, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 402 và gửi yêu cầu xử lý. Nội dung: thích thì báo', '2026-07-20 14:45:39', '2026-07-20 14:45:39'),
(173, 26, NULL, 'room_issue_proposal_reserved_immediately', 'Hệ thống lập phương án ngay khi nhận báo cáo: phòng 404: Đổi phòng cùng hạng sang phòng 102; phòng 403: Nâng hạng miễn phí sang phòng 301; phòng 402: Nâng hạng miễn phí sang phòng 302. Các phòng thay thế được giữ 30 phút kể từ thời điểm khách báo sự cố.', '2026-07-20 14:45:39', '2026-07-20 14:45:39'),
(174, 26, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 404: Đổi phòng cùng hạng sang phòng 102; phòng 403: Nâng hạng miễn phí sang phòng 301; phòng 402: Nâng hạng miễn phí sang phòng 302. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 20/07/2026 22:16. Mã bù đắp đang lưu: chưa chọn.', '2026-07-20 14:46:15', '2026-07-20 14:46:15'),
(175, 26, 4, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 404: giữ nguyên phòng và sửa gấp; phòng 403: giữ nguyên phòng và sửa gấp; phòng 402: giữ nguyên phòng và sửa gấp.', '2026-07-20 14:46:50', '2026-07-20 14:46:50'),
(176, 26, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 404: Đổi phòng cùng hạng sang phòng 102; phòng 403: Nâng hạng miễn phí sang phòng 301; phòng 402: Nâng hạng miễn phí sang phòng 302. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 20/07/2026 22:17. Mã bù đắp đang lưu: chưa chọn.', '2026-07-20 14:47:07', '2026-07-20 14:47:07'),
(177, 26, 4, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 404: giữ nguyên phòng và sửa gấp; phòng 403: nâng hạng miễn phí sang phòng 301; phòng 402: nâng hạng miễn phí sang phòng 302.', '2026-07-20 14:48:14', '2026-07-20 14:48:14'),
(178, 26, 4, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 404 giữ nguyên và sửa gấp; phòng 403 nâng miễn phí sang phòng 301 (Family Suite); phòng 402 nâng miễn phí sang phòng 302 (Family Suite). Mã hỗ trợ: DEMO_INCIDENT_FULL.', '2026-07-20 14:48:49', '2026-07-20 14:48:49'),
(179, 26, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 404. đã done', '2026-07-20 14:49:31', '2026-07-20 14:49:31'),
(180, 24, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 101. đã done', '2026-07-20 14:49:40', '2026-07-20 14:49:40'),
(181, 26, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 403. đã done', '2026-07-20 14:49:47', '2026-07-20 14:49:47'),
(182, 26, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 402. đã done', '2026-07-20 14:49:56', '2026-07-20 14:49:56'),
(183, 24, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. đã done', '2026-07-20 14:50:03', '2026-07-20 14:50:03'),
(184, 26, 4, 'room_issue_form_emailed', 'Lễ tân đã gửi biểu mẫu báo sự cố tới email chientr319@gmail.com. Có 4 phòng có thể chọn báo sự cố.', '2026-07-20 14:50:51', '2026-07-20 14:50:51'),
(185, 26, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 404, 301, 405, 302.', '2026-07-20 15:20:39', '2026-07-20 15:20:39'),
(186, 26, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 404: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-07-20 15:20:49', '2026-07-20 15:20:49'),
(187, 26, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 301: dịch vụ tại phòng: Coca Cola x2 = 50.000đ — tạm tính 50.000đ. hư hại: Vỡ ly thủy tinh x3 = 150.000đ — tạm tính 150.000đ. Chờ admin duyệt.', '2026-07-20 15:21:07', '2026-07-20 15:21:07'),
(188, 26, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 405: dịch vụ tại phòng: Bia x4 = 40.000đ — tạm tính 40.000đ. Chờ admin duyệt.', '2026-07-20 15:21:21', '2026-07-20 15:21:21'),
(189, 26, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 302: hư hại: Vỡ ly thủy tinh x5 = 250.000đ — tạm tính 250.000đ. Chờ admin duyệt.', '2026-07-20 15:21:36', '2026-07-20 15:21:36'),
(190, 26, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 404. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-07-20 15:21:50', '2026-07-20 15:21:50'),
(191, 26, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 301. Dịch vụ tại phòng được duyệt: 50.000đ. Hư hại được duyệt: 150.000đ. Tổng cộng: 200.000đ. Mục duyệt: hư hại - Vỡ ly thủy tinh x3 = 150.000đ; dịch vụ tại phòng - Coca Cola x2 = 50.000đ.', '2026-07-20 15:21:59', '2026-07-20 15:21:59'),
(192, 26, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 405. Dịch vụ tại phòng được duyệt: 40.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 40.000đ. Mục duyệt: dịch vụ tại phòng - Bia x4 = 40.000đ.', '2026-07-20 15:22:06', '2026-07-20 15:22:06'),
(193, 26, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 302. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 250.000đ. Tổng cộng: 250.000đ. Mục duyệt: hư hại - Vỡ ly thủy tinh x5 = 250.000đ.', '2026-07-20 15:22:11', '2026-07-20 15:22:11'),
(194, 26, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 10.570.000đ. Đã thu: 14.890.000đ / 14.890.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK260720SPUTZ202607202222379OP7X. Khách đưa/chuyển thừa 430.000đ; cần trả/hoàn lại khách.', '2026-07-20 15:22:37', '2026-07-20 15:22:37'),
(195, 26, 4, 'check_out', 'Xác nhận check-out lúc 20/07/2026 22:22. Phòng chuyển sang cần dọn: 404, 301, 405, 302. Tiền phòng: 18.000.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 490.000đ. Tổng phải thu: 14.890.000đ. Đã thu trước check-out: 14.890.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 15:22:56', '2026-07-20 15:22:56'),
(196, 24, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-07-20 15:25:35', '2026-07-20 15:25:35'),
(197, 24, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-07-20 15:25:53', '2026-07-20 15:25:53'),
(198, 24, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-07-20 15:26:04', '2026-07-20 15:26:04'),
(199, 24, 4, 'admin_payment_received', 'Ghi nhận thanh toán chuyển khoản tại quầy: 2.400.000đ. Đã thu: 3.000.000đ / 3.000.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: BANKBK20260720180117KLW20260720222635QOQ0K', '2026-07-20 15:26:35', '2026-07-20 15:26:35'),
(200, 24, 4, 'check_out', 'Xác nhận check-out lúc 20/07/2026 22:27. Phòng chuyển sang cần dọn: 101. Tiền phòng: 3.600.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 3.000.000đ. Đã thu trước check-out: 3.000.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 15:27:16', '2026-07-20 15:27:16'),
(201, 27, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền giữ lại: 0đ; không hoàn lại, không bảo lưu.', '2026-07-20 15:28:11', '2026-07-20 15:28:11'),
(202, 28, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền giữ lại: 0đ; không hoàn lại, không bảo lưu.', '2026-07-20 15:31:34', '2026-07-20 15:31:34'),
(203, 29, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.620.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 15:32:25', '2026-07-20 15:32:25'),
(204, 29, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-20 15:32:35', '2026-07-20 15:32:35'),
(205, 30, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.620.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 15:33:26', '2026-07-20 15:33:26'),
(206, 30, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-20 15:33:36', '2026-07-20 15:33:36'),
(207, 30, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 09/08/2026 14:00 → 12/08/2026 12:00 sang 21/07/2026 00:06 → 25/07/2026 12:00; đổi toàn bộ sang hạng Superior Double. Số đêm: 3 → 4. Tiền phòng: 5.400.000đ → 3.600.000đ. Tổng đơn: 5.400.000đ → 3.600.000đ. Đã thanh toán: 1.620.000đ. Còn phải thu: 1.980.000đ. Khách đang trả dư: 0đ. Mức cọc mới: 1.080.000đ. Phòng 302 → 201 (Family Suite → Superior Double).', '2026-07-20 17:07:14', '2026-07-20 17:07:14'),
(208, 30, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 1.980.000đ. Đã thu: 3.600.000đ / 3.600.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK20260720223300OTI20260721000748AJV81. Khách đưa/chuyển thừa 20.000đ; cần trả/hoàn lại khách.', '2026-07-20 17:07:49', '2026-07-20 17:07:49'),
(209, 30, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 00:08, sớm hơn giờ chuẩn 13 giờ 51 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 900.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-07-20 17:08:02', '2026-07-20 17:08:02'),
(210, 30, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-07-20 17:08:14', '2026-07-20 17:08:14'),
(211, 30, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: dịch vụ tại phòng: Coca Cola x5 = 125.000đ — tạm tính 125.000đ. Chờ admin duyệt.', '2026-07-20 17:08:29', '2026-07-20 17:08:29'),
(212, 30, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: dịch vụ tại phòng: Coca Cola x5 = 125.000đ — tạm tính 125.000đ. hư hại: Vỡ ly thủy tinh x1 = 50.000đ — tạm tính 50.000đ. Chờ admin duyệt.', '2026-07-20 17:08:37', '2026-07-20 17:08:37'),
(213, 30, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Dịch vụ tại phòng được duyệt: 125.000đ. Hư hại được duyệt: 50.000đ. Tổng cộng: 175.000đ. Mục duyệt: hư hại - Vỡ ly thủy tinh x1 = 50.000đ; dịch vụ tại phòng - Coca Cola x5 = 125.000đ.', '2026-07-20 17:08:49', '2026-07-20 17:08:49'),
(214, 30, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 1.075.000đ. Tổng đã thu: 4.675.000đ. Mức cọc 30% hiện tại: 1.080.000đ; đã phân bổ vào cọc: 1.080.000đ; thanh toán thêm/trả trước: 3.595.000đ. Tổng booking hiện tại: 4.675.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK20260720223300OTI20260721003657UBU1P', '2026-07-20 17:36:57', '2026-07-20 17:36:57'),
(215, 30, 4, 'check_out', 'Xác nhận check-out lúc 21/07/2026 00:37. Phòng chuyển sang cần dọn: 201. Tiền phòng: 3.600.000đ. Dịch vụ/phụ thu: 900.000đ. Minibar/hư hại duyệt: 175.000đ. Tổng phải thu: 4.675.000đ. Đã thu trước check-out: 4.675.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 17:37:09', '2026-07-20 17:37:09');
INSERT INTO `booking_logs` (`id`, `booking_id`, `user_id`, `action`, `description`, `created_at`, `updated_at`) VALUES
(216, 31, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 2.700.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 17:38:40', '2026-07-20 17:38:40'),
(217, 31, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-20 17:38:51', '2026-07-20 17:38:51'),
(218, 31, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 08/08/2026 14:00 → 13/08/2026 12:00 sang 21/07/2026 00:39 → 23/07/2026 12:00; đổi toàn bộ sang hạng Superior Double. Số đêm: 5 → 2. Tiền phòng: 9.000.000đ → 1.800.000đ. Tổng đơn: 9.000.000đ → 1.800.000đ. Đã thanh toán: 2.700.000đ. Còn phải thu: 0đ. Khách đang trả dư: 900.000đ. Mức cọc mới: 540.000đ. Phòng 302 → 201 (Family Suite → Superior Double).', '2026-07-20 17:40:27', '2026-07-20 17:40:27'),
(219, 31, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 00:41, sớm hơn giờ chuẩn 13 giờ 18 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 900.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-07-20 17:41:52', '2026-07-20 17:41:52'),
(220, 31, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-07-20 17:43:33', '2026-07-20 17:43:33'),
(221, 31, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: dịch vụ tại phòng: Bia x4 = 40.000đ — tạm tính 40.000đ. Chờ admin duyệt.', '2026-07-20 17:43:46', '2026-07-20 17:43:46'),
(222, 31, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Dịch vụ tại phòng được duyệt: 40.000đ. Hư hại được duyệt: 0đ. Tổng cộng: 40.000đ. Mục duyệt: dịch vụ tại phòng - Bia x4 = 40.000đ.', '2026-07-20 17:44:14', '2026-07-20 17:44:14'),
(223, 31, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 40.000đ. Tổng đã thu: 2.740.000đ. Mức cọc 30% hiện tại: 540.000đ; đã phân bổ vào cọc: 540.000đ; thanh toán thêm/trả trước: 2.200.000đ. Tổng booking hiện tại: 2.740.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK202607210038160NH202607210044342YN0R', '2026-07-20 17:44:34', '2026-07-20 17:44:34'),
(224, 31, 4, 'check_out', 'Xác nhận check-out lúc 21/07/2026 00:44. Phòng chuyển sang cần dọn: 201. Tiền phòng: 1.800.000đ. Dịch vụ/phụ thu: 900.000đ. Minibar/hư hại duyệt: 40.000đ. Tổng phải thu: 2.740.000đ. Đã thu trước check-out: 2.740.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 17:44:42', '2026-07-20 17:44:42'),
(225, 32, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-20 17:46:35', '2026-07-20 17:46:35'),
(226, 32, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-20 17:46:44', '2026-07-20 17:46:44'),
(227, 32, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 31/07/2026 14:00 → 01/08/2026 12:00 sang 21/07/2026 00:46 → 26/07/2026 12:00; đổi toàn bộ sang hạng Deluxe Sea View. Số đêm: 1 → 5. Tiền phòng: 1.800.000đ → 6.000.000đ. Tổng đơn: 1.800.000đ → 6.000.000đ. Đã thanh toán: 540.000đ. Còn phải thu: 5.460.000đ. Khách đang trả dư: 0đ. Mức cọc mới: 1.800.000đ. Phòng 302 → 101 (Family Suite → Deluxe Sea View).', '2026-07-20 17:47:59', '2026-07-20 17:47:59'),
(228, 32, 4, 'admin_vnpay_email_sent', 'Lễ tân gửi yêu cầu thanh toán VNPay qua email chientr33@gmail.com: 1.260.000đ (cọc 30%). Mã giao dịch: BK20260721004610LAH20260721004838VBSC7. Link yêu cầu có hiệu lực đến 22/07/2026 00:48.', '2026-07-20 17:48:43', '2026-07-20 17:48:43'),
(229, 32, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.260.000đ. Trạng thái thanh toán: partial. Giao dịch tạo từ admin..', '2026-07-20 17:49:13', '2026-07-20 17:49:13'),
(230, 32, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-20 17:49:22', '2026-07-20 17:49:22'),
(231, 32, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 00:50, sớm hơn giờ chuẩn 13 giờ 9 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.200.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-07-20 17:50:21', '2026-07-20 17:50:21'),
(232, 32, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-07-20 18:16:00', '2026-07-20 18:16:00'),
(233, 32, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-07-20 18:16:14', '2026-07-20 18:16:14'),
(234, 32, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-07-20 18:16:22', '2026-07-20 18:16:22'),
(235, 32, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 5.400.000đ. Tổng đã thu: 7.200.000đ. Mức cọc 30% hiện tại: 1.800.000đ; đã phân bổ vào cọc: 1.800.000đ; thanh toán thêm/trả trước: 5.400.000đ. Tổng booking hiện tại: 7.200.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK20260721004610LAH20260721011638FUWVP', '2026-07-20 18:16:38', '2026-07-20 18:16:38'),
(236, 32, 4, 'check_out', 'Xác nhận check-out lúc 21/07/2026 01:16. Phòng chuyển sang cần dọn: 101. Tiền phòng: 6.000.000đ. Dịch vụ/phụ thu: 1.200.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 7.200.000đ. Đã thu trước check-out: 7.200.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 18:16:47', '2026-07-20 18:16:47'),
(237, 29, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 21/07/2026 02:04, sớm hơn giờ chuẩn 11 giờ 55 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.800.000đ.', '2026-07-20 19:04:43', '2026-07-20 19:04:43'),
(238, 29, 14, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 302 và gửi yêu cầu xử lý. Nội dung: thích t đổi', '2026-07-20 19:05:58', '2026-07-20 19:05:58'),
(239, 29, 14, 'room_issue_proposal_reserved_immediately', 'Hệ thống lập phương án ngay khi nhận báo cáo: phòng 302: Nâng hạng miễn phí sang phòng 401. Các phòng thay thế được giữ 30 phút kể từ thời điểm khách báo sự cố.', '2026-07-20 19:05:58', '2026-07-20 19:05:58'),
(240, 29, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 302: Nâng hạng miễn phí sang phòng 401. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 21/07/2026 02:36. Mã bù đắp đang lưu: chưa chọn.', '2026-07-20 19:06:20', '2026-07-20 19:06:20'),
(241, 29, 4, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 302: nâng hạng miễn phí sang phòng 401.', '2026-07-20 19:06:38', '2026-07-20 19:06:38'),
(242, 29, 4, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 302 nâng miễn phí sang phòng 401 (Presidential Suite). Mã hỗ trợ: DEMO_INCIDENT_FULL.', '2026-07-20 19:07:07', '2026-07-20 19:07:07'),
(244, 29, 4, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 302. ok done', '2026-07-20 19:08:13', '2026-07-20 19:08:13'),
(248, 29, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 401.', '2026-07-20 20:08:59', '2026-07-20 20:08:59'),
(249, 29, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 401: minibar/đồ dùng: Bia x5 = 50.000đ — 50.000đ. hư hại/mất đồ: Hỏng TV x1 = 3.000.000đ; Vỡ ly thủy tinh x3 = 150.000đ — 3.150.000đ. Chờ lễ tân trao đổi với khách.', '2026-07-20 20:09:24', '2026-07-20 20:09:24'),
(250, 29, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 401 với khách. Lễ tân đã trao đổi với khách: 2 hạng mục cần buồng phòng kiểm tra lại (Hỏng TV, Vỡ ly thủy tinh).', '2026-07-20 20:34:57', '2026-07-20 20:34:57'),
(251, 29, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 401: Hỏng TV: khách 0, buồng phòng 0 lần; Vỡ ly thủy tinh: khách 1, buồng phòng 2 cái. Lễ tân cần đối chiếu lại số lượng với khách trước khi chuyển admin.', '2026-07-20 20:36:07', '2026-07-20 20:36:07'),
(252, 29, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 401 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-07-20 20:37:14', '2026-07-20 20:37:14'),
(253, 29, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 401: Vỡ ly thủy tinh: khách 1, buồng phòng 2 cái. Lễ tân cần đối chiếu lại số lượng với khách trước khi chuyển admin.', '2026-07-20 20:40:14', '2026-07-20 20:40:14'),
(254, 29, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 401 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-07-20 20:58:20', '2026-07-20 20:58:20'),
(255, 29, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 401: Vỡ ly thủy tinh: khách 1, xác minh 1 cái. Tất cả kết quả đã khớp với ý kiến khách; chuyển admin xác nhận cuối.', '2026-07-20 20:58:50', '2026-07-20 20:58:50'),
(256, 29, 4, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 401. Minibar/đồ dùng được duyệt: 50.000đ; hư hại/mất đồ được duyệt: 50.000đ; tổng cộng: 100.000đ.', '2026-07-20 20:59:53', '2026-07-20 20:59:53'),
(257, 29, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 5.680.000đ. Tổng đã thu: 7.300.000đ. Mức cọc 30% hiện tại: 1.620.000đ; đã phân bổ vào cọc: 1.620.000đ; thanh toán thêm/trả trước: 5.680.000đ. Tổng booking hiện tại: 7.300.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK20260720223155HQU20260721040027SGCAA', '2026-07-20 21:00:27', '2026-07-20 21:00:27'),
(258, 29, 4, 'check_out', 'Xác nhận check-out lúc 21/07/2026 04:00. Phòng chuyển sang cần dọn: 401. Tiền phòng: 15.000.000đ. Dịch vụ/phụ thu: 1.800.000đ. Minibar/hư hại duyệt: 100.000đ. Tổng phải thu: 7.300.000đ. Đã thu trước check-out: 7.300.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-20 21:00:36', '2026-07-20 21:00:36'),
(259, 33, 4, 'promotion_added', 'Áp dụng mã ưu đãi khi tạo booking: WELCOME10. Giảm tiền: 200.000đ, ưu đãi dịch vụ: 0đ, tổng ưu đãi: 200.000đ.', '2026-07-20 21:33:44', '2026-07-20 21:33:44'),
(260, 33, 4, 'booking_created', 'Tạo booking ở ngay - qua đêm bởi lễ tân. Gán phòng: 403, 402. Thời gian: 21/07/2026 04:32 - 25/07/2026 12:00. Chính sách giá: Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Khách ở 4 đêm và trả phòng lúc 12:00 ngày 25/07/2026.. Ưu đãi giảm: 200.000đ. Tổng tiền tạm tính: 11.800.000đ.', '2026-07-20 21:33:44', '2026-07-20 21:33:44'),
(261, 33, 4, 'admin_payment_received', 'Thu tiền khi tạo booking bằng tiền mặt tại quầy: 2.820.000đ. Trạng thái thanh toán: partial. Mã giao dịch: CASHBK260721CJNQJ20260721043344UDMOO.', '2026-07-20 21:33:44', '2026-07-20 21:33:44'),
(262, 33, 4, 'booking_email_sent', 'Đã gửi email xác nhận booking đến chientr319@gmail.com.', '2026-07-20 21:33:58', '2026-07-20 21:33:58'),
(263, 33, 4, 'guest_added', 'Thêm khách lưu trú: Nguyễn Văn A · Người lớn · Phòng 403', '2026-07-20 21:39:12', '2026-07-20 21:39:12'),
(264, 33, 4, 'guest_added', 'Thêm khách lưu trú: Trịnh N C · Người lớn · Phòng 402', '2026-07-20 21:53:39', '2026-07-20 21:53:39'),
(265, 33, 4, 'guest_added', 'Thêm khách lưu trú: Ngô Văn C · Người lớn · Phòng 402', '2026-07-20 21:54:47', '2026-07-20 21:54:47'),
(266, 33, 4, 'guest_added', 'Thêm khách lưu trú: Trịnh Chiến · Trẻ em · Phòng 403', '2026-07-20 21:55:52', '2026-07-20 21:55:52'),
(267, 33, 4, 'guest_added', 'Thêm khách lưu trú: Trịnh Ngọc Chiến · Em bé · Phòng 402', '2026-07-20 21:56:40', '2026-07-20 21:56:40'),
(268, 33, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh Ngọc Chiến.', '2026-07-20 21:56:54', '2026-07-20 21:56:54'),
(269, 33, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh Ngọc Chiến.', '2026-07-20 22:00:58', '2026-07-20 22:00:58'),
(270, 33, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh Ngọc Chiến.', '2026-07-20 22:33:24', '2026-07-20 22:33:24'),
(271, 33, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh Ngọc Chiến.', '2026-07-20 22:36:32', '2026-07-20 22:36:32'),
(272, 33, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh N C.', '2026-07-20 23:02:12', '2026-07-20 23:02:12'),
(273, 33, 4, 'check_in', 'Xác nhận check-in thực tế: 4 người lớn / 1 trẻ em / 0 em bé. Phụ thu check-in sớm đã được tính khi tạo booking; không thu trùng lần nữa.', '2026-07-20 23:03:19', '2026-07-20 23:03:19'),
(274, 33, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 403, 402.', '2026-07-21 00:39:18', '2026-07-21 00:39:18'),
(275, 33, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 402: minibar/đồ dùng: Snack x3 = 60.000đ — 60.000đ. hư hại/mất đồ: Vỡ ly thủy tinh x3 = 150.000đ — 150.000đ. Chờ lễ tân trao đổi với khách.', '2026-07-21 00:39:47', '2026-07-21 00:39:47'),
(276, 33, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 403: minibar/đồ dùng: Nước suối x2 = 14.000đ — 14.000đ. Chờ lễ tân trao đổi với khách.', '2026-07-21 00:40:05', '2026-07-21 00:40:05'),
(277, 33, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 402 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-07-21 00:40:44', '2026-07-21 00:40:44'),
(278, 33, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 403 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Nước suối).', '2026-07-21 00:41:09', '2026-07-21 00:41:09'),
(279, 33, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 403: Nước suối: khách 1, xác minh 2 chai. Các khoản còn khác ý kiến khách cần lễ tân trao đổi lại: Nước suối.', '2026-07-21 00:41:39', '2026-07-21 00:41:39'),
(280, 33, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 402: Vỡ ly thủy tinh: khách 2, xác minh 3 cái; Snack: khách 3, xác minh 4 gói. Các khoản còn khác ý kiến khách cần lễ tân trao đổi lại: Vỡ ly thủy tinh, Snack.', '2026-07-21 00:42:13', '2026-07-21 00:42:13'),
(281, 33, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 402 với khách. Lễ tân đã trao đổi với khách: 2 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh, Snack).', '2026-07-21 00:42:44', '2026-07-21 00:42:44'),
(282, 33, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 403 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Nước suối).', '2026-07-21 00:43:08', '2026-07-21 00:43:08'),
(283, 33, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 403: Nước suối: khách 1, xác minh 1 chai. Tất cả kết quả đã khớp với ý kiến khách; chuyển admin xác nhận cuối.', '2026-07-21 00:43:26', '2026-07-21 00:43:26'),
(284, 33, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 402: Vỡ ly thủy tinh: khách 2, xác minh 2 cái; Snack: khách 3, xác minh 3 gói. Tất cả kết quả đã khớp với ý kiến khách; chuyển admin xác nhận cuối.', '2026-07-21 00:43:54', '2026-07-21 00:43:54'),
(285, 33, 4, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 403. Minibar/đồ dùng được duyệt: 7.000đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 7.000đ.', '2026-07-21 00:44:22', '2026-07-21 00:44:22'),
(286, 33, 4, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 402. Minibar/đồ dùng được duyệt: 60.000đ; hư hại/mất đồ được duyệt: 100.000đ; tổng cộng: 160.000đ.', '2026-07-21 00:45:49', '2026-07-21 00:45:49'),
(287, 33, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 9.147.000đ. Tổng đã thu: 11.967.000đ. Mức cọc 30% hiện tại: 2.820.000đ; đã phân bổ vào cọc: 2.820.000đ; thanh toán thêm/trả trước: 9.147.000đ. Tổng booking hiện tại: 11.967.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK260721CJNQJ20260721074618EXEZC', '2026-07-21 00:46:18', '2026-07-21 00:46:18'),
(288, 33, 4, 'check_out', 'Xác nhận check-out lúc 21/07/2026 07:46. Phòng chuyển sang cần dọn: 403, 402. Tiền phòng: 9.600.000đ. Dịch vụ/phụ thu: 2.400.000đ. Minibar/hư hại duyệt: 167.000đ. Tổng phải thu: 11.967.000đ. Đã thu trước check-out: 11.967.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-21 00:46:27', '2026-07-21 00:46:27'),
(313, 36, 4, 'booking_created', 'Tạo booking ở ngay - qua đêm bởi lễ tân. Gán phòng: 403, 402. Thời gian: 21/07/2026 08:58 - 24/07/2026 12:00. Chính sách giá: Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Khách ở 3 đêm và trả phòng lúc 12:00 ngày 24/07/2026.. Tổng tiền tạm tính: 8.400.000đ.', '2026-07-21 01:59:47', '2026-07-21 01:59:47'),
(314, 36, 4, 'admin_payment_received', 'Thu tiền khi tạo booking bằng tiền mặt tại quầy: 2.520.000đ. Trạng thái thanh toán: partial. Mã giao dịch: CASHBK260721R3B7920260721085947K93J0.', '2026-07-21 01:59:47', '2026-07-21 01:59:47'),
(315, 36, 4, 'booking_email_sent', 'Đã gửi email xác nhận booking đến chientr319@gmail.com.', '2026-07-21 01:59:59', '2026-07-21 01:59:59'),
(316, 36, 4, 'guest_added', 'Thêm khách lưu trú: TRỊNH NGỌC CHIEN · Người lớn · Phòng 403', '2026-07-21 02:00:46', '2026-07-21 02:00:46'),
(317, 36, 4, 'guest_added', 'Thêm khách lưu trú: Chiến Trịnh · Người lớn · Phòng 403', '2026-07-21 02:59:08', '2026-07-21 02:59:08'),
(318, 36, 4, 'guest_added', 'Thêm khách lưu trú: Trịnh Ngọc C · Người lớn · Phòng 402', '2026-07-21 02:59:47', '2026-07-21 02:59:47'),
(319, 36, 4, 'guest_added', 'Thêm khách lưu trú: Nguyễn C · Người lớn · Phòng 402', '2026-07-21 03:00:17', '2026-07-21 03:00:17'),
(320, 36, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Chiến Trịnh.', '2026-07-21 03:00:35', '2026-07-21 03:00:35'),
(321, 36, 4, 'check_in', 'Xác nhận check-in thực tế: 4 người lớn / 0 trẻ em / 0 em bé. Đã ghi phụ phí vượt sức chứa theo từng phòng: phòng 402 - Phụ thu thêm người lớn x 1: 200.000đ. Tổng phụ thu: 200.000đ. Phụ thu check-in sớm đã được tính khi tạo booking; không thu trùng lần nữa.', '2026-07-21 03:00:55', '2026-07-21 03:00:55'),
(322, 36, 4, 'add_room_to_booking', 'Đã thêm 1 phòng hạng Deluxe Sea View vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.', '2026-07-21 03:03:17', '2026-07-21 03:03:17'),
(323, 36, 4, 'add_room_to_booking', 'Đã thêm 1 phòng hạng Deluxe Sea View vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.', '2026-07-21 03:24:27', '2026-07-21 03:24:27'),
(324, 36, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh Ngọc C.', '2026-07-21 03:24:41', '2026-07-21 03:24:41'),
(325, 36, 4, 'change_one_room_category', 'Đã đổi phòng 405 (Deluxe Sea View) sang phòng 302 (Family Suite). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. Tiền chênh: 1.800.000đ. Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: Khách yêu cầu đổi phòng.', '2026-07-21 03:26:52', '2026-07-21 03:26:52'),
(326, 36, 4, 'guest_added', 'Thêm khách lưu trú: Bùi D · Người lớn · Phòng 302', '2026-07-21 03:28:41', '2026-07-21 03:28:41'),
(327, 36, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: TRỊNH NGỌC CHIEN.', '2026-07-21 03:29:29', '2026-07-21 03:29:29'),
(328, 36, 4, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trịnh Ngọc C.', '2026-07-21 03:29:38', '2026-07-21 03:29:38'),
(329, 36, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 403, 402, 302.', '2026-07-21 03:32:19', '2026-07-21 03:32:19'),
(330, 36, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 302: không phát sinh minibar, mất đồ hoặc hư hại. Không có khoản phí; chờ admin xác nhận.', '2026-07-21 03:32:36', '2026-07-21 03:32:36'),
(331, 36, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 402: minibar/đồ dùng: Bia x3 = 30.000đ — 30.000đ. hư hại/mất đồ: Vỡ ly thủy tinh x2 = 100.000đ — 100.000đ. Chờ lễ tân trao đổi với khách.', '2026-07-21 03:32:51', '2026-07-21 03:32:51'),
(332, 36, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 403: không phát sinh minibar, mất đồ hoặc hư hại. Không có khoản phí; chờ admin xác nhận.', '2026-07-21 03:32:57', '2026-07-21 03:32:57'),
(333, 36, 4, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 402 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-07-21 03:36:18', '2026-07-21 03:36:18'),
(334, 36, 4, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 402: Vỡ ly thủy tinh: khách 1, xác minh 1 cái. Tất cả kết quả đã khớp với ý kiến khách; chuyển admin xác nhận cuối.', '2026-07-21 03:36:28', '2026-07-21 03:36:28'),
(335, 36, 4, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 403. Minibar/đồ dùng được duyệt: 0đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 0đ.', '2026-07-21 03:36:57', '2026-07-21 03:36:57'),
(336, 36, 4, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 402. Minibar/đồ dùng được duyệt: 30.000đ; hư hại/mất đồ được duyệt: 50.000đ; tổng cộng: 80.000đ.', '2026-07-21 03:37:19', '2026-07-21 03:37:19'),
(337, 36, 4, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 302. Minibar/đồ dùng được duyệt: 0đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 0đ.', '2026-07-21 03:37:31', '2026-07-21 03:37:31'),
(338, 37, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-21 04:34:08', '2026-07-21 04:34:08'),
(339, 37, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-07-21 04:34:16', '2026-07-21 04:34:16'),
(340, 36, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 11.360.000đ. Tổng đã thu: 13.880.000đ. Mức cọc 30% hiện tại: 3.780.000đ; đã phân bổ vào cọc: 3.780.000đ; thanh toán thêm/trả trước: 10.100.000đ. Tổng booking hiện tại: 13.880.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK260721R3B79202607211152472ELEX', '2026-07-21 04:52:47', '2026-07-21 04:52:47'),
(341, 36, 4, 'check_out', 'Xác nhận check-out lúc 21/07/2026 11:52. Phòng chuyển sang cần dọn: 403, 402, 302. Tiền phòng: 12.600.000đ. Dịch vụ/phụ thu: 1.200.000đ. Minibar/hư hại duyệt: 80.000đ. Tổng phải thu: 13.880.000đ. Đã thu trước check-out: 13.880.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-21 04:52:56', '2026-07-21 04:52:56'),
(342, 38, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-21 04:55:39', '2026-07-21 04:55:39'),
(343, 38, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-21 04:55:46', '2026-07-21 04:55:46'),
(344, 39, 14, 'promotion_added', 'Khách áp dụng mã ưu đãi khi đặt phòng online: DEMO_FREE_BF. Giảm tiền: 0đ, ưu đãi dịch vụ: 180.000đ, tổng ưu đãi: 180.000đ.', '2026-07-21 06:43:45', '2026-07-21 06:43:45'),
(345, 39, 7, 'admin_vnpay_email_sent', 'Lễ tân gửi yêu cầu thanh toán VNPay qua email tc19092006@gmail.com: 1.500.000đ (cọc 30%). Mã giao dịch: BK202607211343459FH20260721134841QAE3N. Link yêu cầu có hiệu lực đến 22/07/2026 13:48.', '2026-07-21 06:48:48', '2026-07-21 06:48:48'),
(346, 39, 15, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.500.000đ. Trạng thái thanh toán: partial. Giao dịch tạo từ admin..', '2026-07-21 06:49:34', '2026-07-21 06:49:34'),
(347, 39, 15, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-21 06:49:40', '2026-07-21 06:49:40'),
(348, 39, 14, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 1.500.000đ; không hoàn lại, không bảo lưu.', '2026-07-21 06:50:51', '2026-07-21 06:50:51'),
(349, 40, 14, 'priority_cleaning_auto', 'Tự động gửi yêu cầu buồng phòng ưu tiên chuẩn bị phòng 302 vì phòng hiện đang dọn.', '2026-07-21 07:19:11', '2026-07-21 07:19:11'),
(350, 40, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.080.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-21 07:19:42', '2026-07-21 07:19:42'),
(351, 40, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-21 07:19:47', '2026-07-21 07:19:47'),
(352, 41, 28, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.080.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-21 07:27:59', '2026-07-21 07:27:59'),
(353, 41, 28, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến sccuong5222@gmail.com.', '2026-07-21 07:28:04', '2026-07-21 07:28:04'),
(354, 41, 7, 'change_stay_dates', 'Đổi ngày lưu trú từ 22/07/2026 14:00 → 25/07/2026 12:00 sang 21/07/2026 14:28 → 25/07/2026 12:00. Số đêm: 3 → 4. Tiền phòng: 3.600.000đ → 4.800.000đ. Tổng đơn: 3.600.000đ → 4.800.000đ. Đã thanh toán: 1.080.000đ. Còn phải thu: 3.720.000đ. Khách đang trả dư: 0đ. Mức cọc mới: 1.440.000đ.', '2026-07-21 07:29:51', '2026-07-21 07:29:51'),
(355, 41, 7, 'admin_vnpay_email_sent', 'Lễ tân gửi yêu cầu thanh toán VNPay qua email sccuong5222@gmail.com: 360.000đ (cọc 30%). Mã giao dịch: BK20260721142728TZA20260721143021A7URV. Link yêu cầu có hiệu lực đến 22/07/2026 14:30.', '2026-07-21 07:30:25', '2026-07-21 07:30:25'),
(356, 41, 28, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 360.000đ. Trạng thái thanh toán: partial. Giao dịch tạo từ admin..', '2026-07-21 07:30:54', '2026-07-21 07:30:54'),
(357, 41, 28, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến sccuong5222@gmail.com.', '2026-07-21 07:30:59', '2026-07-21 07:30:59'),
(358, 41, 7, 'guest_added', 'Thêm khách lưu trú: Dương Cường · Người lớn · Phòng 101', '2026-07-21 07:32:56', '2026-07-21 07:32:56'),
(359, 41, 7, 'guest_added', 'Thêm khách lưu trú: Trịnh chiến · Người lớn · Phòng 101', '2026-07-21 07:33:45', '2026-07-21 07:33:45'),
(360, 41, 7, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-07-21 07:33:52', '2026-07-21 07:33:52'),
(361, 41, 7, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-07-21 07:35:33', '2026-07-21 07:35:33'),
(362, 41, 7, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 101: minibar/đồ dùng: Bia x5 = 50.000đ — 50.000đ. hư hại/mất đồ: Vỡ ly thủy tinh x4 = 200.000đ — 200.000đ. Chờ lễ tân trao đổi với khách.', '2026-07-21 07:36:25', '2026-07-21 07:36:25'),
(363, 41, 7, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 101 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-07-21 07:37:00', '2026-07-21 07:37:00'),
(364, 41, 7, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 101: Vỡ ly thủy tinh: khách 2, xác minh 4 cái. Các khoản còn khác ý kiến khách cần lễ tân trao đổi lại: Vỡ ly thủy tinh.', '2026-07-21 07:37:36', '2026-07-21 07:37:36'),
(365, 41, 7, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 101 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-07-21 07:38:00', '2026-07-21 07:38:00'),
(366, 41, 7, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 101: Vỡ ly thủy tinh: khách 2, xác minh 2 cái. Tất cả kết quả đã khớp với ý kiến khách; chuyển admin xác nhận cuối.', '2026-07-21 07:38:18', '2026-07-21 07:38:18'),
(367, 41, 7, 'inspection_approved', 'Admin xác nhận cuối kết quả kiểm tra phòng 101. Minibar/đồ dùng được duyệt: 50.000đ; hư hại/mất đồ được duyệt: 100.000đ; tổng cộng: 150.000đ.', '2026-07-21 07:38:43', '2026-07-21 07:38:43'),
(368, 41, 7, 'admin_vnpay_email_sent', 'Lễ tân gửi yêu cầu thanh toán VNPay qua email sccuong5222@gmail.com: 3.510.000đ (thanh toán số tiền còn lại). Mã giao dịch: BK20260721142728TZA20260721143921X5ATF. Link yêu cầu có hiệu lực đến 22/07/2026 14:39.', '2026-07-21 07:39:27', '2026-07-21 07:39:27'),
(369, 41, 28, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 3.510.000đ. Trạng thái thanh toán: paid. Giao dịch tạo từ admin..', '2026-07-21 07:40:50', '2026-07-21 07:40:50'),
(370, 41, 28, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến sccuong5222@gmail.com.', '2026-07-21 07:40:55', '2026-07-21 07:40:55'),
(371, 41, 7, 'check_out', 'Xác nhận check-out lúc 21/07/2026 14:43. Phòng chuyển sang cần dọn: 101. Tiền phòng: 4.800.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 150.000đ. Tổng phải thu: 4.950.000đ. Đã thu trước check-out: 4.950.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-07-21 07:43:32', '2026-07-21 07:43:32'),
(372, 37, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 21/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 26/07/2026 09:16. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 900.000đ; không hoàn lại, không bảo lưu.', '2026-07-26 02:16:05', '2026-07-26 02:16:05'),
(373, 40, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 22/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 26/07/2026 09:16. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 1.080.000đ; không hoàn lại, không bảo lưu.', '2026-07-26 02:16:05', '2026-07-26 02:16:05'),
(374, 38, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. Giờ G: 24/07/2026 18:00. Khách không xác nhận giữ phòng sau giờ G. Thời điểm hệ thống xử lý: 26/07/2026 09:16. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 900.000đ; không hoàn lại, không bảo lưu.', '2026-07-26 02:16:05', '2026-07-26 02:16:05'),
(375, 42, 14, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.620.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-27 12:29:59', '2026-07-27 12:29:59'),
(376, 42, 14, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-07-27 12:30:06', '2026-07-27 12:30:06'),
(377, 43, 15, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.. Tiền giữ lại: 0đ; không hoàn lại, không bảo lưu.', '2026-07-28 05:02:41', '2026-07-28 05:02:41'),
(378, 42, 4, 'late_arrival_request_approved', 'Đã duyệt yêu cầu đến sau giờ G. Dự kiến đến: 28/07/2026 20:30.', '2026-07-28 09:44:14', '2026-07-28 09:44:14'),
(379, 42, NULL, 'system_no_show_cancelled', 'Hệ thống tự động hủy booking do khách không check-in trước hạn giữ phòng đã gia hạn. Giờ G ban đầu: 28/07/2026 18:00. Khách đã xác nhận dự kiến đến lúc: 28/07/2026 20:30. Hạn giữ mới: 28/07/2026 21:00. Thời điểm hệ thống xử lý: 01/08/2026 23:54. Hệ thống xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 1.620.000đ; không hoàn lại, không bảo lưu.', '2026-08-01 16:54:33', '2026-08-01 16:54:38'),
(380, 44, NULL, 'payment_hold_expired', 'Booking tự hủy vì hết thời hạn giữ cọc và tổng đã thanh toán 0đ chưa đạt mức cọc 900.000đ. Phòng đã được đồng bộ lại trạng thái.', '2026-08-21 14:17:01', '2026-08-21 14:17:01'),
(381, 45, NULL, 'payment_hold_expired', 'Booking tự hủy vì hết thời hạn giữ cọc và tổng đã thanh toán 0đ chưa đạt mức cọc 1.200.000đ. Phòng đã được đồng bộ lại trạng thái.', '2026-08-21 16:02:01', '2026-08-21 16:02:01'),
(382, 46, 18, 'customer_pre_payment_edit', 'Khách chỉnh sửa booking trước thanh toán; hệ thống đã tính lại phòng, dịch vụ, ưu đãi và vô hiệu link VNPay cũ.', '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(383, 46, 18, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 810.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-21 16:45:20', '2026-08-21 16:45:20'),
(384, 46, 18, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user01@booking.local.', '2026-08-21 16:45:27', '2026-08-21 16:45:27'),
(386, 46, 4, 'guest_added', 'Thêm khách lưu trú: Nguyễn Minh Anh · Người lớn · Phòng 501', '2026-08-21 17:24:06', '2026-08-21 17:24:06'),
(387, 46, 4, 'guest_added', 'Thêm khách lưu trú: Hồ Tuấn Minh · Người lớn · Phòng 501', '2026-08-21 17:24:06', '2026-08-21 17:24:06'),
(388, 46, 4, 'guest_added', 'Thêm khách lưu trú: Trần Đức Bo · Người lớn · Phòng 501', '2026-08-21 17:24:06', '2026-08-21 17:24:06'),
(389, 46, 4, 'check_in', 'Xác nhận check-in thực tế: 3 người lớn / 0 trẻ em / 0 em bé. Đã ghi phụ phí vượt sức chứa theo từng phòng: phòng 501 - Phụ thu thêm người lớn x 1: 200.000đ. Tổng phụ thu: 200.000đ. Check-in sớm trong cùng ngày lúc 22/08/2026 00:25, sớm hơn giờ chuẩn 13 giờ 34 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.000.000đ.', '2026-08-21 17:25:02', '2026-08-21 17:25:02'),
(390, 47, 19, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-21 17:59:13', '2026-08-21 17:59:13'),
(391, 47, 19, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user02@booking.local.', '2026-08-21 17:59:18', '2026-08-21 17:59:18'),
(392, 46, 4, 'room_issue_form_emailed', 'Lễ tân đã gửi biểu mẫu báo sự cố tới email chientr33@gmail.com. Có 1 phòng có thể chọn báo sự cố.', '2026-08-21 18:00:49', '2026-08-21 18:00:49'),
(393, 46, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 501 và gửi yêu cầu xử lý. Nội dung: lỗi điều hòa', '2026-08-21 18:01:26', '2026-08-21 18:01:26'),
(394, 46, NULL, 'room_issue_proposal_reserved_immediately', 'Hệ thống lập phương án ngay khi nhận báo cáo: phòng 501: Nâng hạng miễn phí sang phòng 101. Các phòng thay thế được giữ 30 phút kể từ thời điểm khách báo sự cố.', '2026-08-21 18:01:27', '2026-08-21 18:01:27'),
(395, 46, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 501: Nâng hạng miễn phí sang phòng 101. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 22/08/2026 01:31. Mã bù đắp theo phòng: phòng 501: không chọn.', '2026-08-21 18:01:46', '2026-08-21 18:01:46'),
(396, 46, 5, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 501: nâng hạng miễn phí sang phòng 101.', '2026-08-21 18:02:53', '2026-08-21 18:02:53'),
(397, 46, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 501: Nâng hạng miễn phí sang phòng 101. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 22/08/2026 02:09. Mã bù đắp theo phòng: phòng 501: không chọn.', '2026-08-21 18:39:25', '2026-08-21 18:39:25'),
(398, 46, 5, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 501: nâng hạng miễn phí sang phòng 101.', '2026-08-21 18:39:37', '2026-08-21 18:39:37'),
(399, 46, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 501: Nâng hạng miễn phí sang phòng 101. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 22/08/2026 02:20. Mã bù đắp theo phòng: phòng 501: không chọn.', '2026-08-21 18:50:17', '2026-08-21 18:50:17'),
(400, 46, 5, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 501: nâng hạng miễn phí sang phòng 101.', '2026-08-21 18:50:51', '2026-08-21 18:50:51'),
(401, 46, 4, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 501 nâng hạng sang phòng 101 (Deluxe Sea View). Mã theo từng phòng: phòng 501: không áp mã bổ sung.', '2026-08-21 18:51:08', '2026-08-21 18:51:08'),
(402, 46, 5, 'promotion_added', 'Áp dụng mã sau khi tạo/đổi phòng: DEMO_INCIDENT_FULL. Giảm tiền/dịch vụ thêm: 160.000đ. Quyền lợi nâng hạng ghi nhận: 0đ. Lý do: ok', '2026-08-21 18:52:09', '2026-08-21 18:52:09'),
(403, 46, 6, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. ok', '2026-08-21 20:30:37', '2026-08-21 20:30:37'),
(404, 46, 5, 'add_room_to_booking', 'Đã thêm 1 phòng hạng Phòng demo vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng. [room-op:d5830aad-a4bd-4e88-8324-cc5987c565e5]', '2026-08-21 20:31:34', '2026-08-21 20:31:34'),
(405, 46, 5, 'guest_updated', 'Cập nhật hồ sơ lưu trú: Trần Đức Bo.', '2026-08-21 20:33:05', '2026-08-21 20:33:05'),
(406, 46, 5, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101, 501.', '2026-08-21 20:33:34', '2026-08-21 20:33:34'),
(407, 46, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 101: hư hại/mất đồ: Vỡ ly thủy tinh x2 = 100.000đ — 100.000đ. Chờ lễ tân trao đổi với khách.', '2026-08-21 20:34:04', '2026-08-21 20:34:04'),
(408, 46, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 501: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '2026-08-21 20:34:21', '2026-08-21 20:34:21'),
(409, 46, 6, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-21 20:34:21', '2026-08-21 20:34:21'),
(410, 46, 5, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 101 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Vỡ ly thủy tinh).', '2026-08-21 22:32:46', '2026-08-21 22:32:46'),
(411, 46, 6, 'inspection_supplemental_detected', 'Buồng phòng phát hiện bổ sung sau lần kiểm tra trước tại phòng 501: Bia x3 = 30.000đ. Lý do/căn cứ: thấy thgeem vỏ bia. Các khoản cũ được giữ nguyên; booking tiếp tục bị chặn checkout cho đến khi khoản mới được xử lý.', '2026-08-21 22:33:43', '2026-08-21 22:33:43'),
(412, 46, 5, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 501 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Bia).', '2026-08-21 22:34:07', '2026-08-21 22:34:07'),
(413, 46, 6, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 501: Bia: khách 0, xác minh 0 lon. Tất cả kết quả đã khớp với ý kiến khách; phiếu được hoàn tất ngay.', '2026-08-21 22:34:38', '2026-08-21 22:34:38'),
(414, 46, 6, 'inspection_completed', 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-21 22:34:38', '2026-08-21 22:34:38'),
(415, 46, 6, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 101: Vỡ ly thủy tinh: khách 1, xác minh 1 cái. Tất cả kết quả đã khớp với ý kiến khách; phiếu được hoàn tất ngay.', '2026-08-21 22:34:52', '2026-08-21 22:34:52'),
(416, 46, 6, 'inspection_completed', 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 50.000đ; tổng cộng: 50.000đ.', '2026-08-21 22:34:52', '2026-08-21 22:34:52'),
(417, 46, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 6.500.000đ. Tổng đã thu: 7.310.000đ. Mức cọc 30% hiện tại: 1.710.000đ; đã phân bổ vào cọc: 1.710.000đ; thanh toán thêm/trả trước: 5.600.000đ. Tổng booking hiện tại: 7.310.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK2108202600320260822053555VPT2Y', '2026-08-21 22:35:55', '2026-08-21 22:35:55'),
(418, 46, 5, 'check_out', 'Xác nhận check-out lúc 22/08/2026 05:36. Phòng chuyển sang cần dọn: 101, 501. Tiền phòng: 6.000.000đ. Dịch vụ/phụ thu: 1.900.000đ. Minibar/hư hại duyệt: 50.000đ. Tổng phải thu: 7.310.000đ. Đã thu trước check-out: 7.310.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-08-21 22:36:04', '2026-08-21 22:36:04'),
(419, 46, 18, 'review_submitted', 'Khách gửi đánh giá khách sạn 1/5 sao. Đánh giá được hiển thị tự động sau khi vượt qua bộ lọc từ cấm.', '2026-08-21 22:40:26', '2026-08-21 22:40:26'),
(420, 47, 5, 'change_stay_dates', 'Đổi ngày lưu trú từ 25/08/2026 14:00 → 28/08/2026 12:00 sang 22/08/2026 06:37 → 28/08/2026 12:00. Số đêm: 3 → 6. Tiền phòng: 3.000.000đ → 6.000.000đ. Tổng đơn: 3.000.000đ → 6.000.000đ. Đã thanh toán: 900.000đ. Còn phải thu: 5.100.000đ. Khách đang trả dư: 0đ. Mức cọc mới: 1.800.000đ.', '2026-08-21 23:37:48', '2026-08-21 23:37:48'),
(421, 47, 5, 'guest_added', 'Thêm khách lưu trú: Trần Quốc Bảo · Người lớn · Phòng 501', '2026-08-21 23:38:36', '2026-08-21 23:38:36'),
(422, 47, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 900.000đ. Tổng đã thu: 1.800.000đ. Mức cọc 30% hiện tại: 1.800.000đ; đã phân bổ vào cọc: 1.800.000đ; thanh toán thêm/trả trước: 0đ. Tổng booking hiện tại: 6.000.000đ. Trạng thái thanh toán: partial → partial. Mã giao dịch: CASHBK2208202600120260822063900AXNMB', '2026-08-21 23:39:00', '2026-08-21 23:39:00'),
(423, 47, 5, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 06:39, sớm hơn giờ chuẩn 7 giờ 20 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 500.000đ. Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.', '2026-08-21 23:39:09', '2026-08-21 23:39:09'),
(424, 47, 19, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 501 và gửi yêu cầu xử lý. Nội dung: đèn hỏng', '2026-08-21 23:40:35', '2026-08-21 23:40:35'),
(425, 47, 19, 'room_issue_proposal_reserved_immediately', 'Hệ thống lập phương án ngay khi nhận báo cáo: phòng 501: Nâng hạng miễn phí sang phòng 101. Các phòng thay thế được giữ 30 phút kể từ thời điểm khách báo sự cố.', '2026-08-21 23:40:35', '2026-08-21 23:40:35'),
(426, 47, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 501: Nâng hạng miễn phí sang phòng 101. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 22/08/2026 07:11. Mã bù đắp theo phòng: phòng 501: không chọn.', '2026-08-21 23:41:00', '2026-08-21 23:41:00'),
(427, 47, 5, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 501: nâng hạng miễn phí sang phòng 101.', '2026-08-21 23:41:21', '2026-08-21 23:41:21'),
(428, 47, 4, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 501 nâng hạng sang phòng 101 (Deluxe Sea View). Mã theo từng phòng: phòng 501: DEMO_INCIDENT_FULL.', '2026-08-22 00:04:18', '2026-08-22 00:04:18'),
(429, 47, 6, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 501. ok', '2026-08-22 00:04:37', '2026-08-22 00:04:37'),
(430, 47, 5, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-08-22 00:31:24', '2026-08-22 00:31:24'),
(431, 47, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 101: minibar/đồ dùng: Bia x3 = 30.000đ — 30.000đ. Chờ lễ tân trao đổi với khách.', '2026-08-22 00:32:20', '2026-08-22 00:32:20'),
(432, 47, 5, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 101 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Bia).', '2026-08-22 00:34:01', '2026-08-22 00:34:01'),
(433, 47, 6, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 101: Bia: khách 0, xác minh 0 lon. Tất cả kết quả đã khớp với ý kiến khách; phiếu được hoàn tất ngay.', '2026-08-22 00:34:57', '2026-08-22 00:34:57'),
(434, 47, 6, 'inspection_completed', 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-22 00:34:57', '2026-08-22 00:34:57'),
(435, 47, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 4.700.000đ. Tổng đã thu: 6.500.000đ. Mức cọc 30% hiện tại: 1.800.000đ; đã phân bổ vào cọc: 1.800.000đ; thanh toán thêm/trả trước: 4.700.000đ. Tổng booking hiện tại: 6.500.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK2208202600120260822074327EAJPL', '2026-08-22 00:43:27', '2026-08-22 00:43:27'),
(436, 47, 5, 'check_out', 'Xác nhận check-out lúc 22/08/2026 07:43. Phòng chuyển sang cần dọn: 101. Tiền phòng: 6.000.000đ. Dịch vụ/phụ thu: 660.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 6.500.000đ. Đã thu trước check-out: 6.500.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-08-22 00:43:44', '2026-08-22 00:43:44'),
(437, 48, 18, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-22 01:45:30', '2026-08-22 01:45:30'),
(438, 48, 18, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user01@booking.local.', '2026-08-22 01:45:38', '2026-08-22 01:45:38'),
(439, 48, 5, 'guest_added', 'Thêm khách lưu trú: Nguyễn Minh Anh · Người lớn · Phòng 501', '2026-08-22 01:46:45', '2026-08-22 01:46:45'),
(440, 48, 5, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 08:46, sớm hơn giờ chuẩn 5 giờ 13 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 500.000đ.', '2026-08-22 01:46:54', '2026-08-22 01:46:54'),
(441, 48, 5, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 501.', '2026-08-22 01:47:15', '2026-08-22 01:47:15'),
(442, 48, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 501: minibar/đồ dùng: Nước suối x2 = 14.000đ — 14.000đ. Chờ lễ tân trao đổi với khách.', '2026-08-22 01:47:36', '2026-08-22 01:47:36'),
(443, 48, 5, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 501 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Nước suối).', '2026-08-22 01:47:58', '2026-08-22 01:47:58'),
(444, 48, 6, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 501: Nước suối: khách 0, xác minh 0 chai. Tất cả kết quả đã khớp với ý kiến khách; phiếu được hoàn tất ngay.', '2026-08-22 01:48:18', '2026-08-22 01:48:18'),
(445, 48, 6, 'inspection_completed', 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-22 01:48:18', '2026-08-22 01:48:18'),
(446, 48, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 2.600.000đ. Tổng đã thu: 3.500.000đ. Mức cọc 30% hiện tại: 900.000đ; đã phân bổ vào cọc: 900.000đ; thanh toán thêm/trả trước: 2.600.000đ. Tổng booking hiện tại: 3.500.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK2208202600220260822084835PJ3YO', '2026-08-22 01:48:35', '2026-08-22 01:48:35'),
(447, 48, 5, 'check_out', 'Xác nhận check-out lúc 22/08/2026 08:48. Phòng chuyển sang cần dọn: 501. Tiền phòng: 3.000.000đ. Dịch vụ/phụ thu: 500.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 3.500.000đ. Đã thu trước check-out: 3.500.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-08-22 01:48:42', '2026-08-22 01:48:42'),
(448, 48, 18, 'review_submitted', 'Khách gửi đánh giá khách sạn 3/5 sao. Đánh giá được hiển thị tự động sau khi vượt qua bộ lọc từ cấm.', '2026-08-22 01:49:35', '2026-08-22 01:49:35'),
(449, 49, 19, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-22 01:51:22', '2026-08-22 01:51:22'),
(450, 49, 19, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user02@booking.local.', '2026-08-22 01:51:28', '2026-08-22 01:51:28'),
(451, 49, 5, 'guest_added', 'Thêm khách lưu trú: Trần Quốc Bảo · Người lớn · Phòng 501', '2026-08-22 01:53:20', '2026-08-22 01:53:20'),
(452, 49, 5, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 08:53, sớm hơn giờ chuẩn 5 giờ 6 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 500.000đ.', '2026-08-22 01:53:40', '2026-08-22 01:53:40'),
(453, 49, 5, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 501.', '2026-08-22 01:53:51', '2026-08-22 01:53:51'),
(454, 50, 18, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 1.080.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-22 01:55:51', '2026-08-22 01:55:51'),
(455, 50, 18, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user01@booking.local.', '2026-08-22 01:55:57', '2026-08-22 01:55:57'),
(456, 50, 5, 'guest_added', 'Thêm khách lưu trú: Nguyễn Minh Anh · Người lớn · Phòng 402', '2026-08-22 01:56:25', '2026-08-22 01:56:25');
INSERT INTO `booking_logs` (`id`, `booking_id`, `user_id`, `action`, `description`, `created_at`, `updated_at`) VALUES
(457, 50, 5, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 22/08/2026 08:56, sớm hơn giờ chuẩn 5 giờ 3 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Phụ thu: 600.000đ.', '2026-08-22 01:56:32', '2026-08-22 01:56:32'),
(458, 50, 5, 'add_room_to_booking', 'Đã thêm 1 phòng hạng Deluxe Sea View vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng. [room-op:a95209b3-f960-4095-8fbc-7fc17f4cb40b]', '2026-08-22 01:57:37', '2026-08-22 01:57:37'),
(459, 50, 5, 'room_issue_form_emailed', 'Lễ tân đã gửi biểu mẫu báo sự cố tới email chientr33@gmail.com. Có 2 phòng có thể chọn báo sự cố.', '2026-08-22 01:58:11', '2026-08-22 01:58:11'),
(460, 50, NULL, 'room_issue_requested', 'Biểu mẫu qua email do lễ tân gửi báo sự cố tại phòng 402 và gửi yêu cầu xử lý. Nội dung: hư quạt gió', '2026-08-22 01:58:47', '2026-08-22 01:58:47'),
(461, 50, NULL, 'room_issue_proposal_reserved_immediately', 'Hệ thống lập phương án ngay khi nhận báo cáo: phòng 402: Đổi phòng cùng hạng sang phòng 101. Các phòng thay thế được giữ 30 phút kể từ thời điểm khách báo sự cố.', '2026-08-22 01:58:47', '2026-08-22 01:58:47'),
(462, 50, 7, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 402: Đổi phòng cùng hạng sang phòng 101. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến 22/08/2026 09:29. Mã bù đắp theo phòng: phòng 402: không chọn.', '2026-08-22 01:59:09', '2026-08-22 01:59:09'),
(463, 50, 5, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 402: đổi phòng cùng hạng sang phòng 101.', '2026-08-22 01:59:34', '2026-08-22 01:59:34'),
(464, 50, 7, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 402 đổi cùng hạng sang phòng 101. Mã theo từng phòng: phòng 402: DEMO200K, WELCOME200BF.', '2026-08-22 01:59:58', '2026-08-22 01:59:58'),
(465, 50, 6, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 402. ok', '2026-08-22 02:00:34', '2026-08-22 02:00:34'),
(466, 50, 5, 'change_one_room_category', 'Đã đổi phòng 101 (Deluxe Sea View) sang phòng 405 (Deluxe Sea View). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. Tiền chênh: 0đ. Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: thích [room-op:265c009e-5d3e-448d-a441-e187857d0a08]', '2026-08-22 02:02:05', '2026-08-22 02:02:05'),
(467, 49, 4, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 501: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '2026-08-23 17:04:30', '2026-08-23 17:04:30'),
(468, 49, 4, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-23 17:04:30', '2026-08-23 17:04:30'),
(469, 49, 4, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 2.600.000đ. Tổng đã thu: 3.500.000đ. Mức cọc 30% hiện tại: 900.000đ; đã phân bổ vào cọc: 900.000đ; thanh toán thêm/trả trước: 2.600.000đ. Tổng booking hiện tại: 3.500.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK2208202600320260824000507TCWPS', '2026-08-23 17:05:07', '2026-08-23 17:05:07'),
(470, 49, 4, 'check_out', 'Xác nhận check-out lúc 24/08/2026 00:05. Phòng chuyển sang cần dọn: 501. Tiền phòng: 3.000.000đ. Dịch vụ/phụ thu: 500.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 3.500.000đ. Đã thu trước check-out: 3.500.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-08-23 17:05:14', '2026-08-23 17:05:14'),
(471, 50, 5, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 405, 403.', '2026-08-23 17:07:31', '2026-08-23 17:07:31'),
(472, 50, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 405: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '2026-08-23 17:07:45', '2026-08-23 17:07:45'),
(473, 50, 6, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-23 17:07:45', '2026-08-23 17:07:45'),
(474, 50, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 403: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '2026-08-23 17:07:58', '2026-08-23 17:07:58'),
(475, 50, 6, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-23 17:07:58', '2026-08-23 17:07:58'),
(476, 50, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 6.320.000đ. Tổng đã thu: 7.400.000đ. Mức cọc 30% hiện tại: 2.040.000đ; đã phân bổ vào cọc: 2.040.000đ; thanh toán thêm/trả trước: 5.360.000đ. Tổng booking hiện tại: 7.400.000đ. Trạng thái thanh toán: partial → paid. Mã giao dịch: CASHBK2208202600420260824000815FDZHL', '2026-08-23 17:08:15', '2026-08-23 17:08:15'),
(477, 50, 5, 'check_out', 'Xác nhận check-out lúc 24/08/2026 00:08. Phòng chuyển sang cần dọn: 405, 403. Tiền phòng: 7.200.000đ. Dịch vụ/phụ thu: 780.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 7.400.000đ. Đã thu trước check-out: 7.400.000đ. Còn lại khi check-out: 0đ. Khách đã thanh toán đủ trên hệ thống trước khi check-out. Không phát sinh phụ thu check-out.', '2026-08-23 17:08:22', '2026-08-23 17:08:22'),
(478, 51, 18, 'manual_room_selection_requested', 'Khách yêu cầu lễ tân chọn phòng thủ công: tầng cao. Hệ thống vẫn giữ một phòng dự phòng và chỉ thu phí nếu yêu cầu được đáp ứng.', '2026-08-23 17:41:59', '2026-08-23 17:41:59'),
(479, 51, 18, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 720.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-23 17:42:22', '2026-08-23 17:42:22'),
(480, 51, 18, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user01@booking.local.', '2026-08-23 17:42:30', '2026-08-23 17:42:30'),
(481, 51, 5, 'manual_room_selection_unfulfilled', 'Không thể đáp ứng yêu cầu chọn phòng thủ công. Giữ nguyên phòng dự phòng, không thu phí. Lý do: k cho', '2026-08-23 17:43:57', '2026-08-23 17:43:57'),
(482, 51, 18, 'customer_cancelled', 'Khách hàng xác nhận hủy booking. Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.. Tiền giữ lại: 720.000đ; không hoàn lại, không bảo lưu.', '2026-08-23 17:48:40', '2026-08-23 17:48:40'),
(483, 52, 18, 'manual_room_selection_requested', 'Khách yêu cầu lễ tân chọn phòng thủ công: tần cao. Hệ thống vẫn giữ một phòng dự phòng và chỉ thu phí nếu yêu cầu được đáp ứng.', '2026-08-23 20:13:12', '2026-08-23 20:13:12'),
(484, 52, 18, 'customer_pre_payment_edit', 'Khách chỉnh sửa booking trước thanh toán; hệ thống đã tính lại phòng, dịch vụ, ưu đãi và vô hiệu link VNPay cũ.', '2026-08-23 20:14:24', '2026-08-23 20:14:24'),
(485, 52, 18, 'customer_pre_payment_edit', 'Khách chỉnh sửa booking trước thanh toán; hệ thống đã tính lại phòng, dịch vụ, ưu đãi và vô hiệu link VNPay cũ.', '2026-08-23 20:16:42', '2026-08-23 20:16:42'),
(486, 52, 18, 'customer_pre_payment_edit', 'Khách chỉnh sửa booking trước thanh toán; hệ thống đã tính lại phòng, dịch vụ, ưu đãi và vô hiệu link VNPay cũ.', '2026-08-23 20:16:49', '2026-08-23 20:16:49'),
(487, 52, 18, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 720.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-23 20:17:16', '2026-08-23 20:17:16'),
(488, 52, 18, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user01@booking.local.', '2026-08-23 20:17:23', '2026-08-23 20:17:23'),
(489, 53, 19, 'promotion_added', 'Khách áp dụng mã ưu đãi khi đặt phòng online: DEMO_FREE_BF. Giảm tiền: 0đ, ưu đãi dịch vụ: 180.000đ, tổng ưu đãi: 180.000đ.', '2026-08-23 22:36:35', '2026-08-23 22:36:35'),
(490, 53, 19, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 600.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-08-23 22:37:03', '2026-08-23 22:37:03'),
(491, 53, 19, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến demo.user02@booking.local.', '2026-08-23 22:37:08', '2026-08-23 22:37:08'),
(492, 53, 5, 'guest_added', 'Thêm khách lưu trú: Trần Quốc Bảo · Người lớn · Phòng 501', '2026-08-23 22:42:37', '2026-08-23 22:42:37'),
(493, 53, 5, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 24/08/2026 05:51, sớm hơn giờ chuẩn 8 giờ 8 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Phụ thu: 1.000.000đ.', '2026-08-23 22:51:49', '2026-08-23 22:51:49'),
(494, 53, 19, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 501 và gửi yêu cầu để buồng phòng kiểm tra thực tế trước khi quản lý duyệt. Nội dung: lỗi điều hòa', '2026-08-23 22:52:53', '2026-08-23 22:52:53'),
(495, 53, 19, 'room_issue_waiting_housekeeping_verification', 'Phiếu sự cố đang chờ buồng phòng kiểm tra thực tế. Hệ thống chưa giữ phòng thay thế để tránh chiếm tồn phòng khi sự cố chưa được xác minh.', '2026-08-23 22:52:53', '2026-08-23 22:52:53'),
(496, 53, 6, 'room_issue_housekeeping_not_found', 'Buồng phòng kiểm tra phòng 501: không phát hiện sự cố. Kết quả: điều hòa chỉ quên cắm nguồn', '2026-08-23 22:54:09', '2026-08-23 22:54:09'),
(497, 53, 4, 'manager_rejected_room_issue', 'Quản lý từ chối/đóng sự cố phòng 501. Lý do: ok1 cvgbh', '2026-08-23 22:55:05', '2026-08-23 22:55:05'),
(498, 52, 29, 'manual_room_selection_fulfilled', 'Đã đáp ứng yêu cầu chọn phòng thủ công. Phòng được chọn: 405. Phí đảm bảo yêu cầu phòng: 50.000đ.', '2026-08-24 02:39:26', '2026-08-24 02:39:26'),
(499, 53, 5, 'change_one_room_category', 'Đã đổi phòng 501 (Phòng demo) sang phòng 202 (Superior Double). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. Tiền chênh: -200.000đ. Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: vfryw [room-op:ac573653-5c38-46d9-a5fc-2730954165b1]', '2026-08-24 02:53:47', '2026-08-24 02:53:47'),
(500, 52, 5, 'guest_added', 'Thêm khách lưu trú: Nguyễn Minh Anh · Người lớn · Phòng 405', '2026-08-24 03:16:20', '2026-08-24 03:16:20'),
(501, 52, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 15.000đ. Tổng đã thu: 735.000đ. Mức cọc 30% hiện tại: 735.000đ; đã phân bổ vào cọc: 735.000đ; thanh toán thêm/trả trước: 0đ. Tổng booking hiện tại: 2.650.000đ. Trạng thái thanh toán: partial → partial. Mã giao dịch: CASHBK24082026002202608241016482Y7CV', '2026-08-24 03:16:48', '2026-08-24 03:16:48'),
(502, 52, 5, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 24/08/2026 10:16, sớm hơn giờ chuẩn 3 giờ 43 phút. Check-in sớm từ 09:00 đến trước 12:00, phụ thu 20% giá 1 đêm. Phụ thu: 240.000đ.', '2026-08-24 03:16:57', '2026-08-24 03:16:57'),
(503, 52, 18, 'room_issue_requested', 'Tài khoản khách hàng báo sự cố tại phòng 405 và gửi yêu cầu để buồng phòng kiểm tra thực tế trước khi quản lý duyệt. Nội dung: sẻdtgyhctfvgybuh', '2026-08-24 03:17:55', '2026-08-24 03:17:55'),
(504, 52, 18, 'room_issue_waiting_housekeeping_verification', 'Phiếu sự cố đang chờ buồng phòng kiểm tra thực tế. Hệ thống chưa giữ phòng thay thế để tránh chiếm tồn phòng khi sự cố chưa được xác minh.', '2026-08-24 03:17:55', '2026-08-24 03:17:55'),
(505, 52, 6, 'room_issue_housekeeping_confirmed', 'Buồng phòng kiểm tra phòng 405: xác nhận có sự cố. Kết quả: guhinj', '2026-08-24 03:18:28', '2026-08-24 03:18:28'),
(506, 52, 4, 'room_issue_auto_proposal_created', 'Gửi lại các phương án khả dụng cho lễ tân: phòng 405: đổi phòng cùng hạng sang phòng 101. Nếu có đổi phòng, phòng thay thế được giữ 30 phút đến 24/08/2026 10:49; phương án sửa tại phòng không giữ phòng khác. Mã bù đắp theo phòng: phòng 405: không chọn.', '2026-08-24 03:19:20', '2026-08-24 03:19:20'),
(507, 52, 5, 'guest_selected_room_issue_resolutions', 'Lễ tân ghi nhận lựa chọn của khách: phòng 405: giữ nguyên phòng và sửa gấp.', '2026-08-24 03:24:55', '2026-08-24 03:24:55'),
(508, 52, 4, 'manager_finalized_room_issue_group', 'Quản lý xác nhận cuối phương án sự cố: phòng 405 giữ nguyên và sửa gấp. Mã theo từng phòng: phòng 405: INCIDENT_FULL.', '2026-08-24 03:25:34', '2026-08-24 03:25:34'),
(509, 52, 6, 'room_issue_repair_completed', 'Buồng phòng xác nhận đã sửa xong phòng 405. xdcfyubhij', '2026-08-24 03:27:40', '2026-08-24 03:27:40'),
(510, 52, 5, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 405.', '2026-08-24 03:31:46', '2026-08-24 03:31:46'),
(511, 52, 6, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 405: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '2026-08-24 03:32:10', '2026-08-24 03:32:10'),
(512, 52, 6, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-24 03:32:10', '2026-08-24 03:32:10'),
(513, 52, 6, 'inspection_supplemental_detected', 'Buồng phòng phát hiện bổ sung sau lần kiểm tra trước tại phòng 405: Bia x2 = 20.000đ. Lý do/căn cứ: zsrdvgbh. Các khoản cũ được giữ nguyên; booking tiếp tục bị chặn checkout cho đến khi khoản mới được xử lý.', '2026-08-24 03:32:49', '2026-08-24 03:32:49'),
(514, 52, 5, 'inspection_guest_disputed', 'Trao đổi kết quả kiểm tra phòng 405 với khách. Lễ tân đã trao đổi với khách: 1 hạng mục cần buồng phòng kiểm tra lại (Bia).', '2026-08-24 03:33:06', '2026-08-24 03:33:06'),
(515, 52, 6, 'inspection_rechecked', 'Buồng phòng đã cập nhật kết quả kiểm tra lại phòng 405: Bia: khách 0, xác minh 0 lon. Tất cả kết quả đã khớp với ý kiến khách; phiếu được hoàn tất ngay.', '2026-08-24 03:33:18', '2026-08-24 03:33:18'),
(516, 52, 6, 'inspection_completed', 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-24 03:33:18', '2026-08-24 03:33:18'),
(517, 52, 5, 'admin_payment_received', 'Ghi nhận thanh toán tiền mặt tại quầy: 2.105.000đ. Tổng đã thu: 2.840.000đ. Mức cọc 30% hiện tại: 735.000đ; đã phân bổ vào cọc: 735.000đ; thanh toán thêm/trả trước: 2.105.000đ. Tổng booking hiện tại: 2.890.000đ. Trạng thái thanh toán: partial → partial. Mã giao dịch: CASHBK2408202600220260824103334I4QYY', '2026-08-24 03:33:34', '2026-08-24 03:33:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_payments`
--

CREATE TABLE `booking_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'vnpay',
  `txn_ref` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `payment_type` enum('deposit_30','custom') NOT NULL,
  `bank_code` varchar(50) DEFAULT NULL,
  `transaction_no` varchar(100) DEFAULT NULL,
  `response_code` varchar(20) DEFAULT NULL,
  `transaction_status` varchar(20) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `raw_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_payments`
--

INSERT INTO `booking_payments` (`id`, `booking_id`, `provider`, `txn_ref`, `amount`, `status`, `payment_type`, `bank_code`, `transaction_no`, `response_code`, `transaction_status`, `paid_at`, `raw_response`, `created_at`, `updated_at`) VALUES
(1, 1, 'vnpay', 'BK20260718134205VOA20260718134206BCMWX', 300000.00, 'success', 'deposit_30', 'NCB', '15626080', '00', '00', '2026-07-18 06:42:30', '{\"vnp_Amount\":\"30000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626080\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260718134205VOA - GD BK20260718134205VOA20260718134206BCMWX\",\"vnp_PayDate\":\"20260718134225\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626080\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260718134205VOA20260718134206BCMWX\",\"vnp_SecureHash\":\"2c9dbf52e31548993062049f37a93dc6a90a6dd4069015592ef06f9d52eda1b9eb011c9404ae3b0205c46e8f9f7d00c84872f096593615023c6548264a3b30aa\",\"booking_confirm_email_sent_at\":\"2026-07-18 13:42:36\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-18 06:42:06', '2026-07-18 06:42:36'),
(2, 2, 'vnpay', 'BK202607190949110GW20260719094913BRAUY', 900000.00, 'success', 'deposit_30', 'NCB', '15626585', '00', '00', '2026-07-19 02:49:44', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626585\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607190949110GW - GD BK202607190949110GW20260719094913BRAUY\",\"vnp_PayDate\":\"20260719094933\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626585\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607190949110GW20260719094913BRAUY\",\"vnp_SecureHash\":\"f63c7f6def11565b073fe85f310f7dab20bee3631af8f7794e18990b6c643e8137cdb61cb0640e4e5f3079cd58617055780e75b67e77e044e961e45ba2c2459f\",\"booking_confirm_email_sent_at\":\"2026-07-19 09:49:53\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-19 02:49:13', '2026-07-19 02:49:53'),
(3, 3, 'vnpay', 'BK20260719095051SUS20260719095051O57B2', 900000.00, 'success', 'deposit_30', 'NCB', '15626586', '00', '00', '2026-07-19 02:51:13', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626586\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260719095051SUS - GD BK20260719095051SUS20260719095051O57B2\",\"vnp_PayDate\":\"20260719095108\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626586\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260719095051SUS20260719095051O57B2\",\"vnp_SecureHash\":\"059700e08201878a1e3e9c51517c3b0820890d464e7f05d586c9663b00bcc4bdecf79a6d03b8f5e182ddaf3910101ce8c61c1f9da30ba8e2e6c166760c69b097\",\"booking_confirm_email_sent_at\":\"2026-07-19 09:51:18\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-19 02:50:51', '2026-07-19 02:51:18'),
(4, 2, 'cash', 'CASHBK202607190949110GW20260719100028F7RRE', 2300000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-07-19 03:00:28', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4}', '2026-07-19 03:00:28', '2026-07-19 03:00:28'),
(5, 4, 'vnpay', 'BK20260719102934OGT20260719102934BLZEM', 900000.00, 'success', 'deposit_30', 'NCB', '15626590', '00', '00', '2026-07-19 03:30:01', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626590\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260719102934OGT - GD BK20260719102934OGT20260719102934BLZEM\",\"vnp_PayDate\":\"20260719102951\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626590\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260719102934OGT20260719102934BLZEM\",\"vnp_SecureHash\":\"4f1c4501786e37f571b7bd120a48a6c05f28718d131785494f9a34f940d1adb2bd0c93dce746f70bb76ef4c7d9669b755ff8b7b192b8bb0245909d28311e6c40\",\"booking_confirm_email_sent_at\":\"2026-07-19 10:30:08\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-19 03:29:34', '2026-07-19 03:30:08'),
(6, 4, 'cash', 'CASHBK20260719102934OGT20260719115639TDDTC', 1300000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-19 04:56:39', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4}', '2026-07-19 04:56:39', '2026-07-19 04:56:39'),
(7, 4, 'cash', 'CASHBK20260719102934OGT20260719115657G29W9', 700000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-19 04:56:57', '{\"source\": \"admin\", \"method\": \"cash\", \"type\": \"custom\", \"note\": null, \"staff_id\": 4, \"tendered_amount\": 1000000, \"recorded_amount\": 700000, \"change_due\": 300000}', '2026-07-19 04:56:57', '2026-07-19 04:56:57'),
(8, 5, 'vnpay', 'BK202607191922377BB20260719192237EPGQG', 900000.00, 'success', 'deposit_30', 'NCB', '15626948', '00', '00', '2026-07-19 12:23:15', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626948\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607191922377BB - GD BK202607191922377BB20260719192237EPGQG\",\"vnp_PayDate\":\"20260719192259\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626948\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607191922377BB20260719192237EPGQG\",\"vnp_SecureHash\":\"225407620d9ad5a27fcd5ca96f5160f8b798347648b30b6bc3b3ea405dee4aea1e0b5273e65804722dde8c42c841c39ca716b3efc3144f451b067223380b9623\",\"booking_confirm_email_sent_at\":\"2026-07-19 19:23:21\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-19 12:22:37', '2026-07-19 12:23:21'),
(9, 6, 'vnpay', 'BK20260719192455CXS20260719192455ZU3PE', 600000.00, 'success', 'deposit_30', 'NCB', '15626949', '00', '00', '2026-07-19 12:25:39', '{\"vnp_Amount\":\"60000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626949\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260719192455CXS - GD BK20260719192455CXS20260719192455ZU3PE\",\"vnp_PayDate\":\"20260719192510\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626949\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260719192455CXS20260719192455ZU3PE\",\"vnp_SecureHash\":\"67834f858b5a8fffbdb828bbc8e67389feeaaa47399c5e34af83cec09d91db06229c5d51340604f3464eec94181c9fc21e4c9c107fefa7693f29058a7b713703\",\"booking_confirm_email_sent_at\":\"2026-07-19 19:25:51\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-19 12:24:55', '2026-07-19 12:25:51'),
(10, 7, 'vnpay', 'BK202607191936100WP20260719193610R92UL', 600000.00, 'success', 'deposit_30', 'NCB', '15626955', '00', '00', '2026-07-19 12:36:30', '{\"vnp_Amount\":\"60000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15626955\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607191936100WP - GD BK202607191936100WP20260719193610R92UL\",\"vnp_PayDate\":\"20260719193625\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15626955\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607191936100WP20260719193610R92UL\",\"vnp_SecureHash\":\"5fa86de06d617b2561a5c3ffbeaefac2b8a5561e638cc86a28c9244051fba315b09c5241d5cacf5fa5998a1572272c1b05170862780177fabaa3e8270d6c54d3\",\"booking_confirm_email_sent_at\":\"2026-07-19 19:36:35\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-19 12:36:10', '2026-07-19 12:36:35'),
(11, 7, 'cash', 'CASHBK202607191936100WP20260719194046LDPH9', 2530000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-19 12:40:46', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":3000000,\"recorded_amount\":2530000,\"change_due\":470000}', '2026-07-19 12:40:46', '2026-07-19 12:40:46'),
(12, 8, 'vnpay', 'BK20260719211711IQK20260719211711JPX5B', 1080000.00, 'success', 'deposit_30', 'NCB', '15627052', '00', '00', '2026-07-19 14:17:42', '{\"vnp_Amount\":\"108000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627052\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260719211711IQK - GD BK20260719211711IQK20260719211711JPX5B\",\"vnp_PayDate\":\"20260719211731\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627052\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260719211711IQK20260719211711JPX5B\",\"vnp_SecureHash\":\"60028b039be6547875980f8f98af9a942b451df9cee7a622006f5b2328226e0930508322cb0663ce386e67373495bc77a43de13c287bf78c0ed77c2599251fee\",\"booking_confirm_email_sent_at\":\"2026-07-19 21:17:47\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-19 14:17:11', '2026-07-19 14:17:47'),
(13, 8, 'cash', 'CASHBK20260719211711IQK20260719211951FG2ZF', 3740000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-19 14:19:51', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":4000000,\"recorded_amount\":3740000,\"change_due\":260000}', '2026-07-19 14:19:51', '2026-07-19 14:19:51'),
(14, 9, 'vnpay', 'BK20260720100542VED20260720100543NJDUS', 270000.00, 'success', 'deposit_30', 'NCB', '15627443', '00', '00', '2026-07-20 03:06:17', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627443\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720100542VED - GD BK20260720100542VED20260720100543NJDUS\",\"vnp_PayDate\":\"20260720100605\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627443\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720100542VED20260720100543NJDUS\",\"vnp_SecureHash\":\"4ee4b58007ccc4b246e5fef0afde62d4b5fbab5e59faa5c279177bb6ffa6a284abc95c7ced53619240ff83bf63aa959e995a7a77a9a71877ae7496f093cd380e\",\"booking_confirm_email_sent_at\":\"2026-07-20 10:06:24\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 03:05:43', '2026-07-20 03:06:24'),
(15, 10, 'vnpay', 'BK20260720100702N6W20260720100702XEKBM', 540000.00, 'success', 'deposit_30', 'NCB', '15627447', '00', '00', '2026-07-20 03:07:22', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627447\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720100702N6W - GD BK20260720100702N6W20260720100702XEKBM\",\"vnp_PayDate\":\"20260720100716\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627447\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720100702N6W20260720100702XEKBM\",\"vnp_SecureHash\":\"8bd1e7240335fb908924f4c27729978349ce473dd0d14ceb17845352fe7b7a37b2325e9160cb01ccc31d59ee336cf2951ebd06f02121956d1c2f21dfd112713b\",\"booking_confirm_email_sent_at\":\"2026-07-20 10:07:29\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 03:07:02', '2026-07-20 03:07:29'),
(16, 11, 'vnpay', 'BK202607201009199TZ20260720100919JECSG', 540000.00, 'success', 'deposit_30', 'NCB', '15627451', '00', '00', '2026-07-20 03:09:49', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627451\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607201009199TZ - GD BK202607201009199TZ20260720100919JECSG\",\"vnp_PayDate\":\"20260720100934\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627451\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607201009199TZ20260720100919JECSG\",\"vnp_SecureHash\":\"fa6dda9a797c2638157e2ac82cbc4d438cf49856b854a3f09727b9eda08730ebc276c5fa93bd39b3366d9248e3a917b9adfa75568742fac5d66b26d0bca1a7e0\",\"booking_confirm_email_sent_at\":\"2026-07-20 10:09:55\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 03:09:19', '2026-07-20 03:09:55'),
(17, 12, 'vnpay', 'BK20260720101302M6920260720101302KG0AN', 540000.00, 'success', 'deposit_30', 'NCB', '15627459', '00', '00', '2026-07-20 03:13:21', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627459\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720101302M69 - GD BK20260720101302M6920260720101302KG0AN\",\"vnp_PayDate\":\"20260720101316\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627459\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720101302M6920260720101302KG0AN\",\"vnp_SecureHash\":\"2251468984991508dee7728909aedfe61bed659db93fbb4a604d0ae1082b8d602eab70e1393ea82b505856ef3842241365d9e7701884308ddaaaeb726cf78a54\",\"booking_confirm_email_sent_at\":\"2026-07-20 10:13:27\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 03:13:02', '2026-07-20 03:13:27'),
(18, 13, 'vnpay', 'BK20260720102439XX220260720102439VXEFZ', 810000.00, 'success', 'deposit_30', 'NCB', '15627486', '00', '00', '2026-07-20 03:25:02', '{\"vnp_Amount\":\"81000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627486\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720102439XX2 - GD BK20260720102439XX220260720102439VXEFZ\",\"vnp_PayDate\":\"20260720102455\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627486\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720102439XX220260720102439VXEFZ\",\"vnp_SecureHash\":\"33205057186b70b880ad877a5ac2d1828231d143da5fba7c9a6d8b1d425f392be5792fdd0ccf06d0cee2ae801782bb15e0cc53c755e62aa0385ca88606e6d164\",\"booking_confirm_email_sent_at\":\"2026-07-20 10:25:08\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 03:24:39', '2026-07-20 03:25:08'),
(19, 14, 'vnpay', 'BK20260720102624PPQ20260720102624GQ5LX', 540000.00, 'success', 'deposit_30', 'NCB', '15627496', '00', '00', '2026-07-20 03:26:51', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627496\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720102624PPQ - GD BK20260720102624PPQ20260720102624GQ5LX\",\"vnp_PayDate\":\"20260720102640\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627496\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720102624PPQ20260720102624GQ5LX\",\"vnp_SecureHash\":\"92bc78e7956d9effefcd138f3fbb6a31d5952e79629a41c0d7c5cb0ea9b962da07da10aacabfce2459d9fb0c207afdf2230b8ea2007dd2a7bcc8b253f08347ae\",\"booking_confirm_email_sent_at\":\"2026-07-20 10:26:57\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 03:26:24', '2026-07-20 03:26:57'),
(20, 15, 'admin_vnpay', 'ADMVNPBK260720K3R9M20260720111453X2MBV', 2100000.00, 'failed', 'deposit_30', NULL, NULL, 'BOOKING_CANCELLED', 'BOOKING_CANCELLED', NULL, NULL, '2026-07-20 04:14:53', '2026-07-20 04:48:50'),
(21, 16, 'admin_vnpay', 'ADMVNPBK260720VVHDR20260720113340UTPK2', 990000.00, 'success', 'deposit_30', 'NCB', '15627577', '00', '00', '2026-07-20 04:34:42', '{\"vnp_Amount\":\"99000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627577\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK260720VVHDR - GD ADMVNPBK260720VVHDR20260720113340UTPK2\",\"vnp_PayDate\":\"20260720113427\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627577\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"ADMVNPBK260720VVHDR20260720113340UTPK2\",\"vnp_SecureHash\":\"0028ebf78d887f341832bdae7c92fe0868d3d3d195ffa9745298b680488286b76835d64e1b11f57bc1bb64782087874b19eb6db8a74c9841689cdf9062ad77e6\",\"booking_confirm_email_sent_at\":\"2026-07-20 11:34:48\",\"booking_confirm_email_to\":\"chientr319@gmail.com\"}', '2026-07-20 04:33:40', '2026-07-20 04:34:48'),
(22, 17, 'vnpay', 'BK20260720150140HDT20260720150140HO2UX', 600000.00, 'success', 'deposit_30', 'NCB', '15627856', '00', '00', '2026-07-20 08:02:05', '{\"vnp_Amount\":\"60000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627856\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720150140HDT - GD BK20260720150140HDT20260720150140HO2UX\",\"vnp_PayDate\":\"20260720150154\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627856\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720150140HDT20260720150140HO2UX\",\"vnp_SecureHash\":\"99d797b58e714fe8a079bdbdfb689dd700c85715f67176574149deda64974564e09aae959b5983eac54821625a40f9fbf4fd5b3d1c1dd7f1b1d14cac51c9cbd6\",\"booking_confirm_email_sent_at\":\"2026-07-20 15:02:11\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 08:01:40', '2026-07-20 08:02:11'),
(23, 18, 'vnpay', 'BK20260720160343NO620260720160344ZVZBO', 540000.00, 'success', 'deposit_30', 'NCB', '15627965', '00', '00', '2026-07-20 09:04:13', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627965\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720160343NO6 - GD BK20260720160343NO620260720160344ZVZBO\",\"vnp_PayDate\":\"20260720160403\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627965\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720160343NO620260720160344ZVZBO\",\"vnp_SecureHash\":\"6387a36cbad8e9bce20e20e3e0b6085e789212606a055c233479125af0d4d4eae6ad15d75a8ac79d43df96a033ceee93a6c1baec4e4e776756c8f1e62b79bb7c\",\"booking_confirm_email_sent_at\":\"2026-07-20 16:04:20\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 09:03:44', '2026-07-20 09:04:20'),
(24, 19, 'vnpay', 'BK20260720160508I3L20260720160508ZLVKN', 300000.00, 'success', 'deposit_30', 'NCB', '15627970', '00', '00', '2026-07-20 09:05:35', '{\"vnp_Amount\":\"30000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15627970\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720160508I3L - GD BK20260720160508I3L20260720160508ZLVKN\",\"vnp_PayDate\":\"20260720160525\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15627970\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720160508I3L20260720160508ZLVKN\",\"vnp_SecureHash\":\"fc2e70761ca4988ea39cd39749cffda82fdbf8fcb28449854510e8c57920bfb4088d034d9e8de23c02101c1191bf1d6608415540a839089410cd37a90972321f\",\"booking_confirm_email_sent_at\":\"2026-07-20 16:05:41\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-20 09:05:08', '2026-07-20 09:05:41'),
(25, 20, 'vnpay', 'BK202607201632095ZL20260720163209HMDCU', 540000.00, 'success', 'deposit_30', 'NCB', '15628035', '00', '00', '2026-07-20 09:32:31', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628035\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607201632095ZL - GD BK202607201632095ZL20260720163209HMDCU\",\"vnp_PayDate\":\"20260720163225\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628035\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607201632095ZL20260720163209HMDCU\",\"vnp_SecureHash\":\"b56df6d5f1690eb82bf101815007941895101aab59190fe24fb340d1095fc8b1af96671654ae83bf6f1a692a47d4e7900c6e3889cbf7ee5d9067a99fc442e927\",\"booking_confirm_email_sent_at\":\"2026-07-20 16:32:36\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 09:32:09', '2026-07-20 09:32:36'),
(26, 21, 'vnpay', 'BK20260720163251CL920260720163251J9RMK', 300000.00, 'success', 'deposit_30', 'NCB', '15628036', '00', '00', '2026-07-20 09:33:16', '{\"vnp_Amount\":\"30000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628036\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720163251CL9 - GD BK20260720163251CL920260720163251J9RMK\",\"vnp_PayDate\":\"20260720163310\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628036\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720163251CL920260720163251J9RMK\",\"vnp_SecureHash\":\"30dae521b36ebc413cec3cb88594e971b42c63f58b12f65ee411b064a55de2ce68ef06e27691df7a73ae0ab785b0b78b9bcf76d78715c0b9010fdc2c92f231cd\",\"booking_confirm_email_sent_at\":\"2026-07-20 16:33:22\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-20 09:32:51', '2026-07-20 09:33:22'),
(27, 22, 'vnpay', 'BK20260720173922A6S202607201739231KVIF', 900000.00, 'success', 'deposit_30', 'NCB', '15628155', '00', '00', '2026-07-20 10:39:43', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628155\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720173922A6S - GD BK20260720173922A6S202607201739231KVIF\",\"vnp_PayDate\":\"20260720173937\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628155\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720173922A6S202607201739231KVIF\",\"vnp_SecureHash\":\"05f286ec847c111ca34159ed6953346c22748aedf3ac0bc80caf2e558b84641397724cabb15597d6592c95bce4096ad4956820ff77ffb277c3b2b223ab1375a5\",\"booking_confirm_email_sent_at\":\"2026-07-20 17:39:49\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 10:39:23', '2026-07-20 10:39:49'),
(28, 23, 'admin_vnpay', 'ADMVNPBK2607203IW1I20260720174619XRZXP', 5610000.00, 'success', 'deposit_30', 'NCB', '15628161', '00', '00', '2026-07-20 10:47:29', '{\"vnp_Amount\":\"561000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628161\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK2607203IW1I - GD ADMVNPBK2607203IW1I20260720174619XRZXP\",\"vnp_PayDate\":\"20260720174721\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628161\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"ADMVNPBK2607203IW1I20260720174619XRZXP\",\"vnp_SecureHash\":\"874a427d488ab8db3f092aefafd6da27c9bdb59846d7175cef7385898c8b65c3bb8bb017228c87939b8dae809dcc3d3dd951bd6d19e709e3613b218196205c27\",\"booking_confirm_email_sent_at\":\"2026-07-20 17:47:35\",\"booking_confirm_email_to\":\"chientr319@gmail.com\"}', '2026-07-20 10:46:19', '2026-07-20 10:47:35'),
(29, 23, 'cash', 'CASHBK2607203IW1I20260720175822GZ3AP', 16035000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 10:58:22', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":16500000,\"recorded_amount\":16035000,\"change_due\":465000}', '2026-07-20 10:58:22', '2026-07-20 10:58:22'),
(30, 22, 'cash', 'CASHBK20260720173922A6S20260720180020THOCG', 3110000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 11:00:20', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":3110000,\"recorded_amount\":3110000,\"change_due\":0}', '2026-07-20 11:00:20', '2026-07-20 11:00:20'),
(31, 24, 'vnpay', 'BK20260720180117KLW20260720180117WYHZP', 600000.00, 'success', 'deposit_30', 'NCB', '15628178', '00', '00', '2026-07-20 11:01:46', '{\"vnp_Amount\":\"60000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628178\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720180117KLW - GD BK20260720180117KLW20260720180117WYHZP\",\"vnp_PayDate\":\"20260720180133\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628178\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720180117KLW20260720180117WYHZP\",\"vnp_SecureHash\":\"3408b006ae3df26886787e72795c131c23e4202ce3dfbcc14ad2fe49f4f97bfd4bee61ca870e766886bbd3adeb13f2e4b5b3946f375b9ba5d5af653bb210c455\",\"booking_confirm_email_sent_at\":\"2026-07-20 18:01:52\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 11:01:17', '2026-07-20 11:01:52'),
(32, 25, 'cash', 'CASHBK2607200E8TP202607202123533G0ZR', 3240000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-07-20 14:23:53', '{\"source\":\"admin_create_booking\",\"method\":\"cash\",\"type\":\"deposit_30\",\"staff_id\":4}', '2026-07-20 14:23:53', '2026-07-20 14:23:53'),
(33, 26, 'cash', 'CASHBK260720SPUTZ20260720214010NMZHM', 4320000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-07-20 14:40:10', '{\"source\":\"admin_create_booking\",\"method\":\"cash\",\"type\":\"deposit_30\",\"staff_id\":4}', '2026-07-20 14:40:10', '2026-07-20 14:40:10'),
(34, 26, 'cash', 'CASHBK260720SPUTZ202607202222379OP7X', 10570000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 15:22:37', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":11000000,\"recorded_amount\":10570000,\"change_due\":430000}', '2026-07-20 15:22:37', '2026-07-20 15:22:37'),
(35, 24, 'bank_transfer', 'BANKBK20260720180117KLW20260720222635QOQ0K', 2400000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 15:26:35', '{\"source\":\"admin\",\"method\":\"bank_transfer\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":2400000,\"recorded_amount\":2400000,\"change_due\":0}', '2026-07-20 15:26:35', '2026-07-20 15:26:35'),
(36, 27, 'vnpay', 'BK202607202227509LJ20260720222802NRDOW', 1080000.00, 'failed', 'deposit_30', NULL, NULL, 'BOOKING_CANCELLED', 'BOOKING_CANCELLED', NULL, '{\"request_expires_at\":\"2026-07-20 22:43:02\",\"request_expire_minutes\":15}', '2026-07-20 15:28:02', '2026-07-20 15:28:11'),
(37, 29, 'vnpay', 'BK20260720223155HQU20260720223159TUIIZ', 1620000.00, 'success', 'deposit_30', 'NCB', '15628409', '00', '00', '2026-07-20 15:32:25', '{\"vnp_Amount\":\"162000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628409\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720223155HQU - GD BK20260720223155HQU20260720223159TUIIZ\",\"vnp_PayDate\":\"20260720223220\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628409\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720223155HQU20260720223159TUIIZ\",\"vnp_SecureHash\":\"586839435a1fe4be4edb64a1b3ed60dac99420e8035fe934cd92098ff69cd4763e4e4bf8ddfa6aa95d33ef505e9e3850bc197385d1641254f196724d7e071dd8\",\"booking_confirm_email_sent_at\":\"2026-07-20 22:32:35\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-20 15:31:59', '2026-07-20 15:32:35'),
(38, 30, 'vnpay', 'BK20260720223300OTI20260720223304DITEW', 1620000.00, 'success', 'deposit_30', 'NCB', '15628410', '00', '00', '2026-07-20 15:33:26', '{\"vnp_Amount\":\"162000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628410\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260720223300OTI - GD BK20260720223300OTI20260720223304DITEW\",\"vnp_PayDate\":\"20260720223321\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628410\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260720223300OTI20260720223304DITEW\",\"vnp_SecureHash\":\"c25e8fbdae150de85a40e07c445a1ace29336f28d56cfbe7ce5034fbe8eab7234d3b88ba071c64b70923198cb7bf2e3a3c05caea3333ab2f1dc54a60cdce58f0\",\"booking_confirm_email_sent_at\":\"2026-07-20 22:33:36\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-20 15:33:04', '2026-07-20 15:33:36'),
(39, 30, 'cash', 'CASHBK20260720223300OTI20260721000748AJV81', 1980000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 17:07:48', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":2000000,\"recorded_amount\":1980000,\"change_due\":20000}', '2026-07-20 17:07:48', '2026-07-20 17:07:48'),
(40, 30, 'cash', 'CASHBK20260720223300OTI20260721003657UBU1P', 1075000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 17:36:57', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":1075000,\"recorded_amount\":1075000,\"required_deposit_at_payment\":1080000,\"allocated_deposit_after\":1080000,\"prepaid_amount_after\":3595000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-07-20 17:36:57', '2026-07-20 17:36:57'),
(41, 31, 'vnpay', 'BK202607210038160NH20260721003820QI47C', 2700000.00, 'success', 'deposit_30', 'NCB', '15628499', '00', '00', '2026-07-20 17:38:40', '{\"vnp_Amount\":\"270000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628499\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607210038160NH - GD BK202607210038160NH20260721003820QI47C\",\"vnp_PayDate\":\"20260721003836\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628499\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607210038160NH20260721003820QI47C\",\"vnp_SecureHash\":\"a7f48247fde83ccf816afcde70c3d1cec5c7077af55e232cf11e656758407dcad0ff761754c7a94b80914ac4581f6dd3fac48bc0877b74c444368d43f5e671e2\",\"booking_confirm_email_sent_at\":\"2026-07-21 00:38:51\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-20 17:38:20', '2026-07-20 17:38:51'),
(42, 31, 'cash', 'CASHBK202607210038160NH202607210044342YN0R', 40000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 17:44:34', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":40000,\"recorded_amount\":40000,\"required_deposit_at_payment\":540000,\"allocated_deposit_after\":540000,\"prepaid_amount_after\":2200000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-07-20 17:44:34', '2026-07-20 17:44:34'),
(43, 32, 'vnpay', 'BK20260721004610LAH20260721004614DSVKI', 540000.00, 'success', 'deposit_30', 'NCB', '15628504', '00', '00', '2026-07-20 17:46:35', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628504\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721004610LAH - GD BK20260721004610LAH20260721004614DSVKI\",\"vnp_PayDate\":\"20260721004632\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628504\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721004610LAH20260721004614DSVKI\",\"vnp_SecureHash\":\"f79aaa4ebd6fec78cdb9e826f3c69ddf65fa426718fdb8929cdbc30aafdd24cd1bb0af744976e6f4dc7e8073c94625c16b5ea60c988a03bc8b7f0603adb99301\",\"booking_confirm_email_sent_at\":\"2026-07-21 00:46:44\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-20 17:46:14', '2026-07-20 17:46:44'),
(44, 32, 'admin_vnpay', 'BK20260721004610LAH20260721004838VBSC7', 1260000.00, 'success', 'deposit_30', 'NCB', '15628506', '00', '00', '2026-07-20 17:49:13', '{\"vnp_Amount\":\"126000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15628506\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721004610LAH - GD BK20260721004610LAH20260721004838VBSC7\",\"vnp_PayDate\":\"20260721004909\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15628506\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721004610LAH20260721004838VBSC7\",\"vnp_SecureHash\":\"acf3714288dda76bfc7c4e664cb9507a0327b4a75d1dd6d8d7da6dd6a0e0d9af2b8fe500845ec5c5d1b9af3e729ea6ac22304eb06860defcaf1cd803d1cc351f\",\"booking_confirm_email_sent_at\":\"2026-07-21 00:49:22\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-20 17:48:38', '2026-07-20 17:49:22'),
(45, 32, 'cash', 'CASHBK20260721004610LAH20260721011638FUWVP', 5400000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 18:16:38', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":5400000,\"recorded_amount\":5400000,\"required_deposit_at_payment\":1800000,\"allocated_deposit_after\":1800000,\"prepaid_amount_after\":5400000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-07-20 18:16:38', '2026-07-20 18:16:38'),
(46, 29, 'cash', 'CASHBK20260720223155HQU20260721040027SGCAA', 5680000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-20 21:00:27', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":5680000,\"recorded_amount\":5680000,\"required_deposit_at_payment\":1620000,\"allocated_deposit_after\":1620000,\"prepaid_amount_after\":5680000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-07-20 21:00:27', '2026-07-20 21:00:27'),
(47, 33, 'cash', 'CASHBK260721CJNQJ20260721043344UDMOO', 2820000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-07-20 21:33:44', '{\"source\":\"admin_create_booking\",\"method\":\"cash\",\"type\":\"deposit_30\",\"staff_id\":4}', '2026-07-20 21:33:44', '2026-07-20 21:33:44'),
(48, 33, 'cash', 'CASHBK260721CJNQJ20260721074618EXEZC', 9147000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-21 00:46:18', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":9147000,\"recorded_amount\":9147000,\"required_deposit_at_payment\":2820000,\"allocated_deposit_after\":2820000,\"prepaid_amount_after\":9147000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-07-21 00:46:18', '2026-07-21 00:46:18'),
(52, 36, 'cash', 'CASHBK260721R3B7920260721085947K93J0', 2520000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-07-21 01:59:47', '{\"source\":\"admin_create_booking\",\"method\":\"cash\",\"type\":\"deposit_30\",\"staff_id\":4}', '2026-07-21 01:59:47', '2026-07-21 01:59:47'),
(53, 37, 'vnpay', 'BK20260721113345YCB202607211133451WSAW', 900000.00, 'success', 'deposit_30', 'NCB', '15629028', '00', '00', '2026-07-21 04:34:08', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629028\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721113345YCB - GD BK20260721113345YCB202607211133451WSAW\",\"vnp_PayDate\":\"20260721113403\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629028\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721113345YCB202607211133451WSAW\",\"vnp_SecureHash\":\"e95725f6c23b606faa20082b35540ccfb0fec2caa45c774f455bc275437570464998307d7953cbe4b0ab074b20c64c44337e34df7a39679026114b262b15823d\",\"booking_confirm_email_sent_at\":\"2026-07-21 11:34:16\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-07-21 04:33:45', '2026-07-21 04:34:16'),
(54, 36, 'cash', 'CASHBK260721R3B79202607211152472ELEX', 11360000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-07-21 04:52:47', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":11360000,\"recorded_amount\":11360000,\"required_deposit_at_payment\":3780000,\"allocated_deposit_after\":3780000,\"prepaid_amount_after\":10100000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-07-21 04:52:47', '2026-07-21 04:52:47'),
(55, 38, 'vnpay', 'BK20260721115507D0N20260721115507RZHXB', 900000.00, 'success', 'deposit_30', 'NCB', '15629063', '00', '00', '2026-07-21 04:55:39', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629063\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721115507D0N - GD BK20260721115507D0N20260721115507RZHXB\",\"vnp_PayDate\":\"20260721115534\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629063\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721115507D0N20260721115507RZHXB\",\"vnp_SecureHash\":\"e76936c15055c9baf381f1702b9a3ac231249de32fef55cf3ee5e457c93519bb7eeb74e6cdff6567cc06e0ba9d3171f9e908195fdb7125d9452d8f7edc0880ff\",\"booking_confirm_email_sent_at\":\"2026-07-21 11:55:46\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-21 04:55:07', '2026-07-21 04:55:46'),
(56, 39, 'vnpay', 'BK202607211343459FH20260721134345LUPFU', 1500000.00, 'failed', 'deposit_30', NULL, NULL, 'BOOKING_CANCELLED', 'BOOKING_CANCELLED', NULL, NULL, '2026-07-21 06:43:45', '2026-07-21 06:50:51'),
(57, 39, 'admin_vnpay', 'BK202607211343459FH20260721134841QAE3N', 1500000.00, 'success', 'deposit_30', 'NCB', '15629203', '00', '00', '2026-07-21 06:49:34', '{\"vnp_Amount\":\"150000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629203\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202607211343459FH - GD BK202607211343459FH20260721134841QAE3N\",\"vnp_PayDate\":\"20260721134926\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629203\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202607211343459FH20260721134841QAE3N\",\"vnp_SecureHash\":\"556712c52daaff69f493a00d0c32e182e79aa733ac0575595c463966f06fcbe3055b5db33230a8d221edcdaeb2a4813b5e828c1b62ea243f1e9f0fd7fe823f81\",\"booking_confirm_email_sent_at\":\"2026-07-21 13:49:40\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-21 06:48:42', '2026-07-21 06:49:40'),
(58, 40, 'vnpay', 'BK20260721141911AQB20260721141911BUK1G', 1080000.00, 'success', 'deposit_30', 'NCB', '15629274', '00', '00', '2026-07-21 07:19:42', '{\"vnp_Amount\":\"108000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629274\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721141911AQB - GD BK20260721141911AQB20260721141911BUK1G\",\"vnp_PayDate\":\"20260721141935\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629274\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721141911AQB20260721141911BUK1G\",\"vnp_SecureHash\":\"68887af403eaabb04f5f4b3a3038d757a0cabed88f58ad062d749c1ab5ba59ebf944852bfb6db6465cdd61e473594e59502697c693420ffd1c051171b16c4848\",\"booking_confirm_email_sent_at\":\"2026-07-21 14:19:47\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-21 07:19:11', '2026-07-21 07:19:47'),
(59, 41, 'vnpay', 'BK20260721142728TZA20260721142728YVHJA', 1080000.00, 'success', 'deposit_30', 'NCB', '15629292', '00', '00', '2026-07-21 07:27:59', '{\"vnp_Amount\":\"108000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629292\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721142728TZA - GD BK20260721142728TZA20260721142728YVHJA\",\"vnp_PayDate\":\"20260721142752\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629292\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721142728TZA20260721142728YVHJA\",\"vnp_SecureHash\":\"512edd3a83985dc29da87b1e7685950a83f9bc77722f0476e223d50ad5e0420c01896dc44aeba43e0016e88ad6a6e607d2264e67ed2fe3ec462060c8193c431d\",\"booking_confirm_email_sent_at\":\"2026-07-21 14:28:04\",\"booking_confirm_email_to\":\"sccuong5222@gmail.com\"}', '2026-07-21 07:27:28', '2026-07-21 07:28:04'),
(60, 41, 'admin_vnpay', 'BK20260721142728TZA20260721143021A7URV', 360000.00, 'success', 'deposit_30', 'NCB', '15629299', '00', '00', '2026-07-21 07:30:54', '{\"vnp_Amount\":\"36000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629299\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260721142728TZA - GD BK20260721142728TZA20260721143021A7URV\",\"vnp_PayDate\":\"20260721143047\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629299\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721142728TZA20260721143021A7URV\",\"vnp_SecureHash\":\"5388d5e649ee8cfcffc038a86d2bd58299e3dc24e97a7662f6bd6e8da374105d6e3291c98dada64cba04cfc44e069de930d9be0bcaef886b8c094ae9098b40dd\",\"booking_confirm_email_sent_at\":\"2026-07-21 14:30:59\",\"booking_confirm_email_to\":\"sccuong5222@gmail.com\"}', '2026-07-21 07:30:21', '2026-07-21 07:30:59'),
(61, 41, 'admin_vnpay', 'BK20260721142728TZA20260721143921X5ATF', 3510000.00, 'success', 'custom', 'NCB', '15629331', '00', '00', '2026-07-21 07:40:50', '{\"vnp_Amount\":\"351000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15629331\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan con lai booking BK20260721142728TZA - GD BK20260721142728TZA20260721143921X5ATF\",\"vnp_PayDate\":\"20260721144044\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15629331\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260721142728TZA20260721143921X5ATF\",\"vnp_SecureHash\":\"d05beee066b03fcf08e203c2e5f85db9845c677eaf2ab0c3fb1a2f3ed140dd9be9c22567e48872ff6f1d92ec31bbb89a6f095e655ae14a205cf5dae0cbd6a7e3\",\"booking_confirm_email_sent_at\":\"2026-07-21 14:40:55\",\"booking_confirm_email_to\":\"sccuong5222@gmail.com\"}', '2026-07-21 07:39:21', '2026-07-21 07:40:55'),
(62, 42, 'vnpay', 'BK20260727192932U5G20260727192932DYR29', 1620000.00, 'success', 'deposit_30', 'NCB', '15637285', '00', '00', '2026-07-27 12:29:59', '{\"vnp_Amount\":\"162000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15637285\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260727192932U5G - GD BK20260727192932U5G20260727192932DYR29\",\"vnp_PayDate\":\"20260727192949\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15637285\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260727192932U5G20260727192932DYR29\",\"vnp_SecureHash\":\"2a59413efcee927edd6872119840e691666a64c5f9066a38b9773c147c8767b92e6cac349cee9d31dd780ea30949dd3ad1cbc0263174e2d535013ae1f7435258\",\"booking_confirm_email_sent_at\":\"2026-07-27 19:30:06\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-07-27 12:29:32', '2026-07-27 12:30:06'),
(63, 43, 'vnpay', 'BK2807202600120260728120228FOK1R', 1080000.00, 'failed', 'deposit_30', NULL, NULL, 'BOOKING_CANCELLED', 'BOOKING_CANCELLED', NULL, NULL, '2026-07-28 05:02:28', '2026-07-28 05:02:41'),
(64, 44, 'vnpay', 'BK2108202600120260821204547GONYF', 900000.00, 'failed', 'deposit_30', NULL, NULL, 'REPLACED', 'REPLACED', NULL, '{\"closed_reason\":\"created_new_vnpay_request_same_purpose\",\"closed_at\":\"2026-08-21 20:46:04\",\"closed_by\":18}', '2026-08-21 13:45:47', '2026-08-21 13:46:04'),
(65, 44, 'vnpay', 'BK2108202600120260821204604QB1GZ', 900000.00, 'failed', 'deposit_30', NULL, NULL, 'EXPIRED', 'EXPIRED', NULL, '{\"request_expires_at\":\"2026-08-21 21:16:04\",\"request_expire_minutes\":30}', '2026-08-21 13:46:04', '2026-08-21 14:17:01'),
(66, 45, 'vnpay', 'BK2108202600220260821223125TWDXE', 1200000.00, 'failed', 'deposit_30', NULL, NULL, 'EXPIRED', 'EXPIRED', NULL, NULL, '2026-08-21 15:31:25', '2026-08-21 16:02:01'),
(67, 46, 'vnpay', 'BK2108202600320260821234042ZSYDT', 900000.00, 'failed', 'deposit_30', NULL, NULL, 'REPLACED', 'REPLACED', NULL, '{\"closed_reason\":\"customer_edited_booking_before_payment\",\"closed_at\":\"2026-08-21 23:41:41\",\"closed_by\":18}', '2026-08-21 16:40:42', '2026-08-21 16:41:41'),
(68, 46, 'vnpay', 'BK2108202600320260821234206AC4HA', 810000.00, 'failed', 'deposit_30', NULL, NULL, 'REPLACED', 'REPLACED', NULL, '{\"request_expires_at\":\"2026-08-22 00:12:06\",\"request_expire_minutes\":30,\"closed_reason\":\"created_new_vnpay_request_same_purpose\",\"closed_at\":\"2026-08-21 23:42:19\",\"closed_by\":18}', '2026-08-21 16:42:06', '2026-08-21 16:42:19'),
(69, 46, 'vnpay', 'BK2108202600320260821234219IGVNT', 810000.00, 'success', 'deposit_30', 'NCB', '15664511', '00', '00', '2026-08-21 16:45:20', '{\"vnp_Amount\":\"81000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15664511\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK21082026-003 - GD BK2108202600320260821234219IGVNT\",\"vnp_PayDate\":\"20260821234240\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15664511\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2108202600320260821234219IGVNT\",\"vnp_SecureHash\":\"e4f90200ffb09f6b6c0e82b818e99a1814b2014604b74bfec69d3d46d6de987147dce7fe30f0f1fecf4e10ebb4541ae20dc11e529be691909fad32363ed53e6b\",\"booking_confirm_email_sent_at\":\"2026-08-21 23:45:27\",\"booking_confirm_email_to\":\"demo.user01@booking.local\"}', '2026-08-21 16:42:19', '2026-08-21 16:45:27'),
(70, 47, 'vnpay', 'BK22082026001202608220058484MKKF', 900000.00, 'success', 'deposit_30', 'NCB', '15664527', '00', '00', '2026-08-21 17:59:13', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15664527\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK22082026-001 - GD BK22082026001202608220058484MKKF\",\"vnp_PayDate\":\"20260822005907\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15664527\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK22082026001202608220058484MKKF\",\"vnp_SecureHash\":\"768bc8e3b4c6f9461209277941dc9059a6b8b82a4a400dfc6d3cd1f9e0aef8d92e7b3a2ef9ba86063cfd435b845a2c8836d83b79a5ea519ff6c3ac6a2af4a5ce\",\"booking_confirm_email_sent_at\":\"2026-08-22 00:59:18\",\"booking_confirm_email_to\":\"demo.user02@booking.local\"}', '2026-08-21 17:58:48', '2026-08-21 17:59:18'),
(71, 46, 'cash', 'CASHBK2108202600320260822053555VPT2Y', 6500000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-08-21 22:35:55', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":5,\"tendered_amount\":6500000,\"recorded_amount\":6500000,\"required_deposit_at_payment\":1710000,\"allocated_deposit_after\":1710000,\"prepaid_amount_after\":5600000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-21 22:35:55', '2026-08-21 22:35:55'),
(72, 47, 'cash', 'CASHBK2208202600120260822063900AXNMB', 900000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-08-21 23:39:00', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"deposit_30\",\"note\":null,\"staff_id\":5,\"tendered_amount\":900000,\"recorded_amount\":900000,\"required_deposit_at_payment\":1800000,\"allocated_deposit_after\":1800000,\"prepaid_amount_after\":0,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-21 23:39:00', '2026-08-21 23:39:00'),
(73, 47, 'cash', 'CASHBK2208202600120260822074327EAJPL', 4700000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-08-22 00:43:27', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":5,\"tendered_amount\":4700000,\"recorded_amount\":4700000,\"required_deposit_at_payment\":1800000,\"allocated_deposit_after\":1800000,\"prepaid_amount_after\":4700000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-22 00:43:27', '2026-08-22 00:43:27'),
(74, 48, 'vnpay', 'BK2208202600220260822084443VRHJX', 900000.00, 'success', 'deposit_30', 'NCB', '15664559', '00', '00', '2026-08-22 01:45:30', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15664559\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK22082026-002 - GD BK2208202600220260822084443VRHJX\",\"vnp_PayDate\":\"20260822084524\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15664559\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2208202600220260822084443VRHJX\",\"vnp_SecureHash\":\"fbd87ad0565bb84db60aa2e016c4616614a45a6cbdfc139d85fd8c17f70f3331787308a50fd4b850808795c4dda1918f2ec443afc4d7dc49106a42e1e9856088\",\"booking_confirm_email_sent_at\":\"2026-08-22 08:45:38\",\"booking_confirm_email_to\":\"demo.user01@booking.local\"}', '2026-08-22 01:44:43', '2026-08-22 01:45:38'),
(75, 48, 'cash', 'CASHBK2208202600220260822084835PJ3YO', 2600000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-08-22 01:48:35', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":5,\"tendered_amount\":2600000,\"recorded_amount\":2600000,\"required_deposit_at_payment\":900000,\"allocated_deposit_after\":900000,\"prepaid_amount_after\":2600000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-22 01:48:35', '2026-08-22 01:48:35'),
(76, 49, 'vnpay', 'BK2208202600320260822085056MO71E', 900000.00, 'success', 'deposit_30', 'NCB', '15664562', '00', '00', '2026-08-22 01:51:22', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15664562\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK22082026-003 - GD BK2208202600320260822085056MO71E\",\"vnp_PayDate\":\"20260822085116\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15664562\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2208202600320260822085056MO71E\",\"vnp_SecureHash\":\"1e8b3d077fc94c6fb427d67fb49c1f92a323195ecd0521cba39b0ff4997ffe3138b5b75469c578915d7889c674171d6bac675c94c0ce285cb860484721679357\",\"booking_confirm_email_sent_at\":\"2026-08-22 08:51:28\",\"booking_confirm_email_to\":\"demo.user02@booking.local\"}', '2026-08-22 01:50:56', '2026-08-22 01:51:28'),
(77, 50, 'vnpay', 'BK2208202600420260822085530FSR8A', 1080000.00, 'success', 'deposit_30', 'NCB', '15664564', '00', '00', '2026-08-22 01:55:51', '{\"vnp_Amount\":\"108000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15664564\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK22082026-004 - GD BK2208202600420260822085530FSR8A\",\"vnp_PayDate\":\"20260822085545\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15664564\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2208202600420260822085530FSR8A\",\"vnp_SecureHash\":\"e01382aa2d35af9a6a162f7004fc84030e6c150c4cf550285a9bc224af3912449e43a076cc5a4b67e39ee52788e7baa79655dafba47f2e0af8de1e060ddf01c0\",\"booking_confirm_email_sent_at\":\"2026-08-22 08:55:57\",\"booking_confirm_email_to\":\"demo.user01@booking.local\"}', '2026-08-22 01:55:30', '2026-08-22 01:55:57'),
(78, 49, 'cash', 'CASHBK2208202600320260824000507TCWPS', 2600000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-08-23 17:05:07', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":4,\"tendered_amount\":2600000,\"recorded_amount\":2600000,\"required_deposit_at_payment\":900000,\"allocated_deposit_after\":900000,\"prepaid_amount_after\":2600000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-23 17:05:07', '2026-08-23 17:05:07');
INSERT INTO `booking_payments` (`id`, `booking_id`, `provider`, `txn_ref`, `amount`, `status`, `payment_type`, `bank_code`, `transaction_no`, `response_code`, `transaction_status`, `paid_at`, `raw_response`, `created_at`, `updated_at`) VALUES
(79, 50, 'cash', 'CASHBK2208202600420260824000815FDZHL', 6320000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-08-23 17:08:15', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":5,\"tendered_amount\":6320000,\"recorded_amount\":6320000,\"required_deposit_at_payment\":2040000,\"allocated_deposit_after\":2040000,\"prepaid_amount_after\":5360000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-23 17:08:15', '2026-08-23 17:08:15'),
(80, 51, 'vnpay', 'BK2408202600120260824004159X4U4L', 720000.00, 'success', 'deposit_30', 'NCB', '15665726', '00', '00', '2026-08-23 17:42:22', '{\"vnp_Amount\":\"72000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15665726\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK24082026-001 - GD BK2408202600120260824004159X4U4L\",\"vnp_PayDate\":\"20260824004216\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15665726\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2408202600120260824004159X4U4L\",\"vnp_SecureHash\":\"3cea003c1453f7a2e27999621d9d0f1a5b76a549b2f5508752a21fb6005ce25519c51b61b813bc5a246b71f1a848b8723d8811492cea87a1c66321b0ab22e546\",\"booking_confirm_email_sent_at\":\"2026-08-24 00:42:30\",\"booking_confirm_email_to\":\"demo.user01@booking.local\"}', '2026-08-23 17:41:59', '2026-08-23 17:42:30'),
(81, 52, 'vnpay', 'BK24082026002202608240313124WBUL', 720000.00, 'failed', 'deposit_30', NULL, NULL, 'REPLACED', 'REPLACED', NULL, '{\"closed_reason\":\"customer_edited_booking_before_payment\",\"closed_at\":\"2026-08-24 03:14:24\",\"closed_by\":18}', '2026-08-23 20:13:12', '2026-08-23 20:14:24'),
(82, 52, 'vnpay', 'BK2408202600220260824031653XBEGE', 720000.00, 'success', 'deposit_30', 'NCB', '15665757', '00', '00', '2026-08-23 20:17:16', '{\"vnp_Amount\":\"72000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15665757\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK24082026-002 - GD BK2408202600220260824031653XBEGE\",\"vnp_PayDate\":\"20260824031709\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15665757\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2408202600220260824031653XBEGE\",\"vnp_SecureHash\":\"2c554de77c9fe480c6ba8ed3242966190587dae95be6cb8433dc1dac7826b660e8cf027bf8d124e5e699f227571f581e3cda616e0bcad3d96ceeb7659d82b374\",\"booking_confirm_email_sent_at\":\"2026-08-24 03:17:23\",\"booking_confirm_email_to\":\"demo.user01@booking.local\"}', '2026-08-23 20:16:53', '2026-08-23 20:17:23'),
(83, 53, 'vnpay', 'BK2408202600320260824053636TZXDS', 600000.00, 'success', 'deposit_30', 'NCB', '15665770', '00', '00', '2026-08-23 22:37:03', '{\"vnp_Amount\":\"60000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15665770\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK24082026-003 - GD BK2408202600320260824053636TZXDS\",\"vnp_PayDate\":\"20260824053656\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15665770\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK2408202600320260824053636TZXDS\",\"vnp_SecureHash\":\"203879d7ef8500fb651365f4681663696b60811629a8f70e7cff0f15160e9c2b9d1cae8043c127474bf9885aaf10e3500d1b1404d2772ba06b1aa993b88d01c3\",\"booking_confirm_email_sent_at\":\"2026-08-24 05:37:08\",\"booking_confirm_email_to\":\"demo.user02@booking.local\"}', '2026-08-23 22:36:36', '2026-08-23 22:37:08'),
(84, 52, 'cash', 'CASHBK24082026002202608241016482Y7CV', 15000.00, 'success', 'deposit_30', NULL, NULL, NULL, NULL, '2026-08-24 03:16:48', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"deposit_30\",\"note\":null,\"staff_id\":5,\"tendered_amount\":15000,\"recorded_amount\":15000,\"required_deposit_at_payment\":735000,\"allocated_deposit_after\":735000,\"prepaid_amount_after\":0,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-24 03:16:48', '2026-08-24 03:16:48'),
(85, 52, 'cash', 'CASHBK2408202600220260824103334I4QYY', 2105000.00, 'success', 'custom', NULL, NULL, NULL, NULL, '2026-08-24 03:33:34', '{\"source\":\"admin\",\"method\":\"cash\",\"type\":\"custom\",\"note\":null,\"staff_id\":5,\"tendered_amount\":2105000,\"recorded_amount\":2105000,\"required_deposit_at_payment\":735000,\"allocated_deposit_after\":735000,\"prepaid_amount_after\":2105000,\"overpayment_after\":0,\"retained_as_prepayment\":0,\"change_due\":0}', '2026-08-24 03:33:34', '2026-08-24 03:33:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_promotions`
--

CREATE TABLE `booking_promotions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('booking','room') NOT NULL DEFAULT 'booking',
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_issue_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code_snapshot` varchar(50) NOT NULL,
  `promotion_type_snapshot` varchar(50) NOT NULL,
  `discount_type_snapshot` varchar(50) NOT NULL,
  `discount_value_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `money_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `service_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `room_upgrade_discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `applied_by` bigint(20) UNSIGNED DEFAULT NULL,
  `applied_channel` enum('user','admin') NOT NULL DEFAULT 'user',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_promotions`
--

INSERT INTO `booking_promotions` (`id`, `booking_id`, `scope`, `booking_room_id`, `room_issue_request_id`, `promotion_id`, `code_snapshot`, `promotion_type_snapshot`, `discount_type_snapshot`, `discount_value_snapshot`, `money_discount_amount`, `service_discount_amount`, `room_upgrade_discount_amount`, `discount_amount`, `applied_by`, `applied_channel`, `note`, `created_at`, `updated_at`) VALUES
(1, 2, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 0.00, 600000.00, 600000.00, 4, 'admin', 'xác nhận hỏng điều hòa', '2026-07-19 02:55:41', '2026-07-19 02:55:41'),
(2, 4, 'booking', NULL, NULL, 8, 'DEMO200K', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 0.00, 0.00, 200000.00, 4, 'admin', 'xác nhận phòng ngập nước', '2026-07-19 03:34:42', '2026-07-19 03:34:42'),
(3, 4, 'booking', NULL, NULL, 5, 'WELCOME200BF', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 180000.00, 0.00, 380000.00, 4, 'admin', 'xác nhận phòng ngập nước', '2026-07-19 03:34:42', '2026-07-19 03:34:42'),
(4, 7, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 0.00, 600000.00, 600000.00, 4, 'admin', 'hư điều hòa', '2026-07-19 12:37:43', '2026-07-19 12:37:43'),
(5, 15, 'booking', NULL, NULL, 10, 'DEMO_FREE_BF', 'normal_discount', 'fixed_amount', 0.00, 0.00, 180000.00, 0.00, 180000.00, 4, 'admin', NULL, '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(6, 15, 'booking', NULL, NULL, 8, 'DEMO200K', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 0.00, 0.00, 200000.00, 4, 'admin', NULL, '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(7, 16, 'booking', NULL, NULL, 9, 'DEMO_EVENT10', 'event_discount', 'percent', 10.00, 300000.00, 0.00, 0.00, 300000.00, 4, 'admin', NULL, '2026-07-20 04:33:40', '2026-07-20 04:33:40'),
(8, 23, 'booking', NULL, NULL, 5, 'WELCOME200BF', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 180000.00, 0.00, 380000.00, 4, 'admin', NULL, '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(9, 23, 'booking', NULL, NULL, 6, 'FAMILY10DECOR', 'event_discount', 'percent', 10.00, 300000.00, 0.00, 0.00, 300000.00, 4, 'admin', NULL, '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(10, 23, 'booking', NULL, NULL, 3, 'SUPPORT100K', 'support_discount', 'fixed_amount', 100000.00, 100000.00, 0.00, 0.00, 100000.00, 4, 'admin', 'xác nhận lỗi', '2026-07-20 10:51:29', '2026-07-20 10:51:29'),
(11, 22, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 0.00, 800000.00, 800000.00, 4, 'admin', 'xác nhận lỗi', '2026-07-20 10:52:03', '2026-07-20 10:52:03'),
(12, 24, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 0.00, 600000.00, 600000.00, 4, 'admin', 'hưu điều hòa', '2026-07-20 11:26:52', '2026-07-20 11:26:52'),
(13, 26, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 0.00, 1800000.00, 1800000.00, 4, 'admin', 'ok cho đổi', '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(14, 29, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 0.00, 9600000.00, 9600000.00, 4, 'admin', 'ok cho đổi', '2026-07-20 19:07:07', '2026-07-20 19:07:07'),
(15, 33, 'booking', NULL, NULL, 1, 'WELCOME10', 'normal_discount', 'percent', 10.00, 200000.00, 0.00, 0.00, 200000.00, 4, 'admin', NULL, '2026-07-20 21:33:44', '2026-07-20 21:33:44'),
(17, 39, 'booking', NULL, NULL, 10, 'DEMO_FREE_BF', 'normal_discount', 'fixed_amount', 0.00, 0.00, 180000.00, 0.00, 180000.00, 14, 'user', NULL, '2026-07-21 06:43:45', '2026-07-21 06:43:45'),
(18, 46, 'booking', NULL, NULL, 10, 'DEMO_FREE_BF', 'normal_discount', 'fixed_amount', 0.00, 0.00, 180000.00, 0.00, 180000.00, 18, 'user', 'Khách chỉnh sửa đơn trước khi thanh toán.', '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(19, 46, 'booking', NULL, NULL, 9, 'DEMO_EVENT10', 'event_discount', 'percent', 10.00, 300000.00, 0.00, 0.00, 300000.00, 18, 'user', 'Khách chỉnh sửa đơn trước khi thanh toán.', '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(20, 46, 'booking', NULL, NULL, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 160000.00, 0.00, 160000.00, 5, 'admin', 'ok', '2026-08-21 18:52:09', '2026-08-21 18:52:09'),
(21, 47, 'room', 68, 15, 12, 'DEMO_INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 160000.00, 0.00, 160000.00, 4, 'admin', 'ok', '2026-08-22 00:04:18', '2026-08-22 00:04:18'),
(22, 50, 'room', 73, 16, 8, 'DEMO200K', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 0.00, 0.00, 200000.00, 7, 'admin', 'ok', '2026-08-22 01:59:58', '2026-08-22 01:59:58'),
(23, 50, 'room', 73, 16, 5, 'WELCOME200BF', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 180000.00, 0.00, 380000.00, 7, 'admin', 'ok', '2026-08-22 01:59:58', '2026-08-22 01:59:58'),
(26, 53, 'booking', NULL, NULL, 10, 'DEMO_FREE_BF', 'normal_discount', 'fixed_amount', 0.00, 0.00, 180000.00, 0.00, 180000.00, 19, 'user', NULL, '2026-08-23 22:36:35', '2026-08-23 22:36:35'),
(27, 52, 'room', 77, 18, 12, 'INCIDENT_FULL', 'support_discount', 'fixed_amount', 0.00, 0.00, 160000.00, 0.00, 160000.00, 4, 'admin', 'yvu', '2026-08-24 03:25:34', '2026-08-24 03:25:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_promotion_room_upgrades`
--

CREATE TABLE `booking_promotion_room_upgrades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `promotion_room_upgrade_offer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `new_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_room_category_name_snapshot` varchar(150) NOT NULL,
  `old_room_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `new_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `new_room_category_name_snapshot` varchar(150) NOT NULL,
  `new_room_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `night_count` int(11) NOT NULL DEFAULT 1,
  `room_quantity` int(11) NOT NULL DEFAULT 1,
  `original_difference_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `covered_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `guest_extra_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `upgrade_kind_snapshot` varchar(50) NOT NULL,
  `cover_type_snapshot` varchar(50) NOT NULL,
  `cover_value_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_promotion_room_upgrades`
--

INSERT INTO `booking_promotion_room_upgrades` (`id`, `booking_id`, `booking_promotion_id`, `promotion_id`, `promotion_room_upgrade_offer_id`, `booking_room_id`, `old_room_id`, `new_room_id`, `old_room_category_id`, `old_room_category_name_snapshot`, `old_room_price_snapshot`, `new_room_category_id`, `new_room_category_name_snapshot`, `new_room_price_snapshot`, `night_count`, `room_quantity`, `original_difference_amount`, `covered_amount`, `guest_extra_amount`, `upgrade_kind_snapshot`, `cover_type_snapshot`, `cover_value_snapshot`, `reason`, `note`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 12, 3, 2, 15, 1, 5, 'Phòng demo', 1000000.00, 1, 'Deluxe Sea View', 1200000.00, 3, 1, 600000.00, 600000.00, 0.00, 'incident_support', 'full_difference', 100.00, 'xác nhận hỏng điều hòa', 'xác nhận hỏng điều hòa', '2026-07-19 02:55:41', '2026-07-19 02:55:41'),
(2, 7, 4, 12, 3, 7, 15, 1, 5, 'Phòng demo', 1000000.00, 1, 'Deluxe Sea View', 1200000.00, 3, 1, 600000.00, 600000.00, 0.00, 'incident_support', 'full_difference', 100.00, 'hư điều hòa', 'hư điều hòa', '2026-07-19 12:37:43', '2026-07-19 12:37:43'),
(3, 22, 11, 12, 3, 24, 15, 3, 5, 'Phòng demo', 1000000.00, 1, 'Deluxe Sea View', 1200000.00, 4, 1, 800000.00, 800000.00, 0.00, 'incident_support', 'full_difference', 100.00, 'xác nhận lỗi', 'xác nhận lỗi', '2026-07-20 10:52:03', '2026-07-20 10:52:03'),
(4, 24, 12, 12, 3, 29, 15, 1, 5, 'Phòng demo', 1000000.00, 1, 'Deluxe Sea View', 1200000.00, 3, 1, 600000.00, 600000.00, 0.00, 'incident_support', 'full_difference', 100.00, 'hưu điều hòa', 'hưu điều hòa', '2026-07-20 11:26:52', '2026-07-20 11:26:52'),
(5, 26, 13, 12, 3, 36, 10, 8, 1, 'Deluxe Sea View', 1200000.00, 3, 'Family Suite', 1800000.00, 3, 1, 1800000.00, 1800000.00, 0.00, 'incident_support', 'full_difference', 100.00, 'ok cho đổi', 'ok cho đổi', '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(6, 29, 14, 12, 3, 39, 8, 9, 3, 'Family Suite', 1800000.00, 4, 'Presidential Suite', 5000000.00, 3, 1, 9600000.00, 9600000.00, 0.00, 'incident_support', 'full_difference', 100.00, 'ok cho đổi', 'ok cho đổi', '2026-07-20 19:07:07', '2026-07-20 19:07:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_promotion_service_offers`
--

CREATE TABLE `booking_promotion_service_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_id_snapshot` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `promotion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `promotion_service_offer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `code_snapshot` varchar(50) NOT NULL,
  `service_name_snapshot` varchar(255) NOT NULL,
  `service_unit_snapshot` varchar(50) DEFAULT NULL,
  `service_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type_snapshot` varchar(50) NOT NULL,
  `discount_value_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `original_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `final_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_promotion_service_offers`
--

INSERT INTO `booking_promotion_service_offers` (`id`, `booking_id`, `booking_room_id`, `room_id_snapshot`, `booking_promotion_id`, `promotion_id`, `promotion_service_offer_id`, `service_id`, `code_snapshot`, `service_name_snapshot`, `service_unit_snapshot`, `service_price_snapshot`, `discount_type_snapshot`, `discount_value_snapshot`, `quantity`, `original_amount`, `discount_amount`, `final_amount`, `note`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, NULL, 3, 5, 1, 32, 'WELCOME200BF', 'Buffet sáng', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-07-19 03:34:42', '2026-07-19 03:34:42'),
(2, 15, NULL, NULL, 5, 10, NULL, 34, 'DEMO_FREE_BF', 'Buffet sáng DEMO', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(3, 23, NULL, NULL, 8, 5, 1, 32, 'WELCOME200BF', 'Buffet sáng', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(4, 39, NULL, NULL, 17, 10, NULL, 34, 'DEMO_FREE_BF', 'Buffet sáng DEMO', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-07-21 06:43:45', '2026-07-21 06:43:45'),
(13, 46, NULL, NULL, 18, 10, NULL, 34, 'DEMO_FREE_BF', 'Buffet sáng DEMO', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-08-21 20:31:34', '2026-08-21 20:31:34'),
(14, 46, NULL, NULL, 20, 12, NULL, 35, 'DEMO_INCIDENT_FULL', 'Welcome drink DEMO', 'phần', 80000.00, 'percent', 100.00, 2, 160000.00, 160000.00, 0.00, 'Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-21 20:31:34', '2026-08-21 20:31:34'),
(15, 47, 68, 1, 21, 12, NULL, 35, 'DEMO_INCIDENT_FULL', 'Welcome drink DEMO', 'phần', 80000.00, 'percent', 100.00, 2, 160000.00, 160000.00, 0.00, 'Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-22 00:04:18', '2026-08-22 00:04:18'),
(19, 50, 73, 13, 23, 5, 1, 32, 'WELCOME200BF', 'Buffet sáng', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-08-23 17:08:22', '2026-08-23 17:08:22'),
(23, 53, NULL, NULL, 26, 10, 8, 34, 'FREE_BF', 'Buffet sáng DEMO', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-08-24 02:53:47', '2026-08-24 02:53:47'),
(28, 52, 77, 13, 27, 12, 9, 35, 'INCIDENT_FULL', 'Welcome drink DEMO', 'phần', 80000.00, 'percent', 100.00, 2, 160000.00, 160000.00, 0.00, 'Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-24 04:24:11', '2026-08-24 04:24:11');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_rooms`
--

CREATE TABLE `booking_rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `adult_count` int(11) NOT NULL DEFAULT 1,
  `child_count` int(11) NOT NULL DEFAULT 0,
  `price_at_booking` decimal(12,2) NOT NULL DEFAULT 0.00,
  `surcharge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `surcharge_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_rooms`
--

INSERT INTO `booking_rooms` (`id`, `booking_id`, `room_id`, `adult_count`, `child_count`, `price_at_booking`, `surcharge`, `surcharge_reason`, `created_at`) VALUES
(1, 1, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-18 06:42:05'),
(2, 2, 1, 2, 0, 1200000.00, 0.00, 'Quản lý duyệt đổi phòng do sự cố: xác nhận hỏng điều hòa', '2026-07-19 02:49:11'),
(3, 3, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-19 02:50:51'),
(4, 4, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-19 03:29:34'),
(5, 5, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-19 12:22:37'),
(6, 6, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-19 12:24:55'),
(7, 7, 1, 2, 0, 1200000.00, 0.00, 'Quản lý duyệt đổi phòng do sự cố: hư điều hòa', '2026-07-19 12:36:10'),
(8, 8, 3, 2, 0, 1200000.00, 0.00, NULL, '2026-07-19 14:17:11'),
(9, 9, 4, 1, 0, 900000.00, 0.00, NULL, '2026-07-20 03:05:42'),
(10, 10, 4, 1, 0, 900000.00, 0.00, NULL, '2026-07-20 03:07:02'),
(11, 11, 4, 1, 0, 900000.00, 0.00, NULL, '2026-07-20 03:09:19'),
(12, 12, 4, 1, 0, 900000.00, 0.00, NULL, '2026-07-20 03:13:02'),
(13, 13, 4, 1, 0, 900000.00, 0.00, NULL, '2026-07-20 03:24:39'),
(14, 14, 5, 1, 0, 900000.00, 0.00, NULL, '2026-07-20 03:26:24'),
(15, 15, 6, 0, 0, 900000.00, 0.00, NULL, '2026-07-20 04:14:53'),
(16, 15, 5, 0, 0, 900000.00, 0.00, NULL, '2026-07-20 04:14:53'),
(17, 16, 5, 0, 0, 900000.00, 0.00, NULL, '2026-07-20 04:33:40'),
(18, 16, 6, 0, 0, 900000.00, 0.00, NULL, '2026-07-20 04:33:40'),
(19, 17, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-20 08:01:40'),
(20, 18, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-07-20 09:03:43'),
(21, 19, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-20 09:05:08'),
(22, 20, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-07-20 09:32:09'),
(23, 21, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-20 09:32:51'),
(24, 22, 3, 1, 0, 1200000.00, 0.00, 'Quản lý duyệt đổi phòng do sự cố: xác nhận lỗi', '2026-07-20 10:39:22'),
(25, 23, 1, 0, 0, 1200000.00, 0.00, 'Quản lý duyệt đổi phòng do sự cố: xác nhận lỗi', '2026-07-20 10:46:19'),
(26, 23, 2, 0, 0, 1200000.00, 0.00, 'Quản lý duyệt đổi phòng do sự cố: xác nhận lỗi', '2026-07-20 10:46:19'),
(27, 23, 11, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 10:46:19'),
(28, 23, 10, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 10:46:19'),
(29, 24, 1, 1, 0, 1200000.00, 0.00, 'Quản lý duyệt đổi phòng do sự cố: hưu điều hòa', '2026-07-20 11:01:17'),
(30, 25, 13, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 14:23:53'),
(31, 25, 12, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 14:23:53'),
(32, 25, 11, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 14:23:53'),
(33, 26, 12, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 14:40:10'),
(34, 26, 7, 0, 0, 1800000.00, 0.00, 'Đổi phòng do sự cố: ok cho đổi', '2026-07-20 14:40:10'),
(35, 26, 13, 0, 0, 1200000.00, 0.00, NULL, '2026-07-20 14:40:10'),
(36, 26, 8, 0, 0, 1800000.00, 0.00, 'Đổi phòng do sự cố: ok cho đổi', '2026-07-20 14:40:10'),
(37, 27, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-07-20 15:27:50'),
(38, 28, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-07-20 15:28:28'),
(39, 29, 9, 1, 0, 5000000.00, 0.00, 'Đổi phòng do sự cố: ok cho đổi', '2026-07-20 15:31:55'),
(40, 30, 4, 1, 0, 900000.00, 0.00, 'Đổi sang hạng Superior Double để phù hợp với lịch lưu trú mới 21/07/2026 00:06 → 25/07/2026 12:00.', '2026-07-20 15:33:00'),
(41, 31, 4, 1, 0, 900000.00, 0.00, 'Đổi sang hạng Superior Double để phù hợp với lịch lưu trú mới 21/07/2026 00:39 → 23/07/2026 12:00.', '2026-07-20 17:38:16'),
(42, 32, 1, 1, 0, 1200000.00, 0.00, 'Đổi sang hạng Deluxe Sea View để phù hợp với lịch lưu trú mới 21/07/2026 00:46 → 26/07/2026 12:00.', '2026-07-20 17:46:10'),
(43, 33, 11, 2, 1, 1200000.00, 0.00, NULL, '2026-07-20 21:33:44'),
(44, 33, 10, 2, 0, 1200000.00, 0.00, NULL, '2026-07-20 21:33:44'),
(49, 36, 11, 1, 0, 1200000.00, 0.00, NULL, '2026-07-21 01:59:47'),
(50, 36, 10, 2, 0, 1200000.00, 0.00, NULL, '2026-07-21 01:59:47'),
(55, 36, 8, 2, 0, 1800000.00, 0.00, 'Lễ tân đổi một phòng sang hạng khác.', '2026-07-21 03:24:27'),
(56, 37, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-21 04:33:45'),
(57, 38, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-07-21 04:55:07'),
(58, 39, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-07-21 06:43:45'),
(59, 40, 8, 3, 0, 1800000.00, 0.00, NULL, '2026-07-21 07:19:11'),
(60, 41, 1, 2, 0, 1200000.00, 0.00, NULL, '2026-07-21 07:27:28'),
(61, 42, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-07-27 12:29:32'),
(62, 43, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-07-28 05:02:28'),
(63, 44, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-08-21 13:45:46'),
(64, 45, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-08-21 15:31:24'),
(65, 46, 1, 2, 0, 1000000.00, 0.00, 'Đổi phòng do sự cố: ok | Khách sạn nâng hạng miễn phí, giữ nguyên đơn giá đã chốt.', '2026-08-21 16:40:41'),
(68, 47, 1, 1, 0, 1000000.00, 0.00, 'Đổi phòng do sự cố: ok | Khách sạn nâng hạng miễn phí, giữ nguyên đơn giá đã chốt.', '2026-08-21 17:58:48'),
(70, 46, 15, 1, 0, 1000000.00, 0.00, 'Thêm phòng khi check-in do vượt sức chứa.', '2026-08-21 20:31:34'),
(71, 48, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-08-22 01:44:40'),
(72, 49, 15, 1, 0, 1000000.00, 0.00, NULL, '2026-08-22 01:50:54'),
(73, 50, 13, 1, 0, 1200000.00, 0.00, 'Đổi phòng do sự cố: ok | thích', '2026-08-22 01:55:28'),
(75, 50, 11, 0, 0, 1200000.00, 0.00, 'Thêm phòng khi check-in do vượt sức chứa.', '2026-08-22 01:57:37'),
(76, 51, 11, 1, 0, 1200000.00, 0.00, NULL, '2026-08-23 17:41:59'),
(77, 52, 13, 1, 0, 1200000.00, 0.00, NULL, '2026-08-23 20:13:11'),
(78, 53, 5, 1, 0, 900000.00, 0.00, 'vfryw', '2026-08-23 22:36:35');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_room_changes`
--

CREATE TABLE `booking_room_changes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_issue_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `new_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `new_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_room_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `new_room_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `night_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `price_difference_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `change_source` enum('front_desk','incident') NOT NULL DEFAULT 'front_desk',
  `reason` text DEFAULT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_room_changes`
--

INSERT INTO `booking_room_changes` (`id`, `booking_id`, `booking_room_id`, `room_issue_request_id`, `old_room_id`, `new_room_id`, `old_room_category_id`, `new_room_category_id`, `old_room_price`, `new_room_price`, `night_count`, `price_difference_total`, `change_source`, `reason`, `changed_by`, `created_at`, `updated_at`) VALUES
(2, 2, 2, 1, 15, 1, 5, 1, 1000000.00, 1200000.00, 3, 600000.00, 'incident', 'xác nhận hỏng điều hòa', 4, '2026-07-19 02:55:41', '2026-07-19 02:55:41'),
(3, 7, 7, 3, 15, 1, 5, 1, 1000000.00, 1200000.00, 3, 600000.00, 'incident', 'hư điều hòa', 4, '2026-07-19 12:37:43', '2026-07-19 12:37:43'),
(4, 23, 25, 5, 13, 1, 1, 1, 1200000.00, 1200000.00, 4, 0.00, 'incident', 'xác nhận lỗi', 4, '2026-07-20 10:51:29', '2026-07-20 10:51:29'),
(5, 23, 26, 6, 12, 2, 1, 1, 1200000.00, 1200000.00, 4, 0.00, 'incident', 'xác nhận lỗi', 4, '2026-07-20 10:51:43', '2026-07-20 10:51:43'),
(6, 22, 24, 4, 15, 3, 5, 1, 1000000.00, 1200000.00, 4, 800000.00, 'incident', 'xác nhận lỗi', 4, '2026-07-20 10:52:03', '2026-07-20 10:52:03'),
(7, 24, 29, 7, 15, 1, 5, 1, 1000000.00, 1200000.00, 3, 600000.00, 'incident', 'hưu điều hòa', 4, '2026-07-20 11:26:52', '2026-07-20 11:26:52'),
(8, 26, 34, 10, 11, 7, 1, 3, 1200000.00, 1800000.00, 3, 1800000.00, 'incident', 'ok cho đổi', 4, '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(9, 26, 36, 11, 10, 8, 1, 3, 1200000.00, 1800000.00, 3, 1800000.00, 'incident', 'ok cho đổi', 4, '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(10, 30, 40, NULL, 8, 4, 3, 2, 1800000.00, 900000.00, 4, -1800000.00, 'front_desk', 'Đổi sang hạng Superior Double để phù hợp với lịch lưu trú mới 21/07/2026 00:06 → 25/07/2026 12:00.', 4, '2026-07-20 17:07:14', '2026-07-20 17:07:14'),
(11, 31, 41, NULL, 8, 4, 3, 2, 1800000.00, 900000.00, 2, -7200000.00, 'front_desk', 'Đổi sang hạng Superior Double để phù hợp với lịch lưu trú mới 21/07/2026 00:39 → 23/07/2026 12:00.', 4, '2026-07-20 17:40:27', '2026-07-20 17:40:27'),
(12, 32, 42, NULL, 8, 1, 3, 1, 1800000.00, 1200000.00, 5, 4200000.00, 'front_desk', 'Đổi sang hạng Deluxe Sea View để phù hợp với lịch lưu trú mới 21/07/2026 00:46 → 26/07/2026 12:00.', 4, '2026-07-20 17:47:59', '2026-07-20 17:47:59'),
(13, 29, 39, 12, 8, 9, 3, 4, 1800000.00, 5000000.00, 3, 9600000.00, 'incident', 'ok cho đổi', 4, '2026-07-20 19:07:07', '2026-07-20 19:07:07'),
(16, 36, 55, NULL, 13, 8, 1, 3, 1200000.00, 1800000.00, 3, 1800000.00, 'front_desk', NULL, 4, '2026-07-21 03:26:52', '2026-07-21 03:26:52'),
(17, 46, 65, 14, 15, 1, 5, 1, 1000000.00, 1200000.00, 3, 0.00, 'incident', 'ok | Nâng hạng miễn phí do lỗi/sự cố phía khách sạn.', 4, '2026-08-21 18:51:08', '2026-08-21 18:51:08'),
(18, 47, 68, 15, 15, 1, 5, 1, 1000000.00, 1200000.00, 6, 0.00, 'incident', 'ok | Nâng hạng miễn phí do lỗi/sự cố phía khách sạn.', 4, '2026-08-22 00:04:18', '2026-08-22 00:04:18'),
(19, 50, 73, 16, 10, 1, 1, 1, 1200000.00, 1200000.00, 3, 0.00, 'incident', 'ok', 7, '2026-08-22 01:59:58', '2026-08-22 01:59:58'),
(21, 50, 73, NULL, 1, 13, 1, 1, 1200000.00, 1200000.00, 3, 0.00, 'front_desk', 'thích', 5, '2026-08-22 02:02:05', '2026-08-22 02:02:05'),
(22, 52, 77, NULL, 1, 13, 1, 1, 1200000.00, 1200000.00, 2, 0.00, 'front_desk', 'Lễ tân chọn phòng theo yêu cầu của khách.', 29, '2026-08-24 02:39:26', '2026-08-24 02:39:26'),
(24, 53, 78, NULL, 15, 5, 5, 2, 1000000.00, 900000.00, 2, -200000.00, 'front_desk', 'vfryw', 5, '2026-08-24 02:53:47', '2026-08-24 02:53:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_service_items`
--

CREATE TABLE `booking_service_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('booking','room') NOT NULL DEFAULT 'booking',
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_id_snapshot` bigint(20) UNSIGNED DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `billing_rule_snapshot` varchar(40) NOT NULL DEFAULT 'once',
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `base_quantity` int(11) NOT NULL DEFAULT 1,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `used_quantity` int(11) NOT NULL DEFAULT 0,
  `nights_snapshot` int(11) NOT NULL DEFAULT 1,
  `rooms_snapshot` int(11) NOT NULL DEFAULT 1,
  `people_snapshot` int(11) NOT NULL DEFAULT 1,
  `billing_status` enum('pending','confirmed','unused','cancelled') NOT NULL DEFAULT 'pending',
  `confirmed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirm_note` text DEFAULT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_service_items`
--

INSERT INTO `booking_service_items` (`id`, `booking_id`, `scope`, `booking_room_id`, `room_id_snapshot`, `source_type`, `source_id`, `service_id`, `name`, `type`, `billing_rule_snapshot`, `unit_price`, `base_quantity`, `quantity`, `used_quantity`, `nights_snapshot`, `rooms_snapshot`, `people_snapshot`, `billing_status`, `confirmed_by`, `confirmed_at`, `confirm_note`, `total`, `note`, `created_at`, `updated_at`) VALUES
(1, 2, 'booking', NULL, NULL, NULL, NULL, 10, 'Phụ thu thêm người lớn', 'occupancy_fee', 'once', 200000.00, 1, 1, 0, 3, 1, 3, 'pending', NULL, NULL, NULL, 200000.00, 'Phụ thu phát sinh khi check-in.', '2026-07-19 02:52:31', '2026-07-19 02:52:31'),
(2, 2, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 200000.00, 1, 1, 1, 3, 1, 3, 'confirmed', 4, '2026-07-19 09:52:31', 'Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm.', 200000.00, 'Check-in sớm lúc 19/07/2026 09:52. Đến sớm 4 giờ 7 phút. Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm.', '2026-07-19 02:52:31', '2026-07-19 02:52:31'),
(3, 4, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 200000.00, 1, 1, 1, 3, 1, 1, 'confirmed', 4, '2026-07-19 10:30:25', 'Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm.', 200000.00, 'Check-in sớm lúc 19/07/2026 10:30. Đến sớm 3 giờ 29 phút. Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm.', '2026-07-19 03:30:25', '2026-07-19 03:30:25'),
(4, 4, 'booking', NULL, NULL, NULL, NULL, 32, 'Buffet sáng', 'service', 'once', 180000.00, 1, 1, 1, 3, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi WELCOME200BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-07-19 03:34:42', '2026-07-19 03:34:42'),
(5, 4, 'booking', NULL, NULL, NULL, NULL, 44, 'mất thẻ phòng', 'manual_fee', 'once', 100000.00, 1, 1, 1, 3, 1, 1, 'confirmed', 4, '2026-07-19 11:49:54', NULL, 100000.00, 'Phí phát sinh được thêm trước khi check-out.', '2026-07-19 04:49:54', '2026-07-19 04:49:54'),
(6, 7, 'booking', NULL, NULL, NULL, NULL, 44, 'mất thẻ phòng', 'manual_fee', 'once', 100000.00, 1, 1, 1, 3, 1, 2, 'confirmed', 4, '2026-07-19 19:40:28', NULL, 100000.00, 'Phí phát sinh được thêm trước khi check-out.', '2026-07-19 12:40:28', '2026-07-19 12:40:28'),
(7, 15, 'booking', NULL, NULL, NULL, NULL, 34, 'Buffet sáng DEMO', 'service', 'once', 180000.00, 1, 1, 1, 4, 2, 5, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi DEMO_FREE_BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-07-20 04:14:53', '2026-07-20 04:14:53'),
(8, 17, 'booking', NULL, NULL, NULL, NULL, 28, 'Phụ thu khách đến muộn', 'late_arrival_fee', 'once', 200000.00, 1, 1, 1, 2, 1, 1, 'confirmed', 4, '2026-07-20 15:36:05', 'Khách dự kiến đến sau 18:00 đến 21:00, phụ thu 20% giá 1 đêm để tiếp tục giữ phòng.', 200000.00, 'Giờ G: 20/07/2026 18:00. Khách dự kiến đến: 20/07/2026 20:00. Giữ phòng đến: 20/07/2026 20:30.', '2026-07-20 08:36:05', '2026-07-20 08:36:05'),
(9, 21, 'booking', NULL, NULL, NULL, NULL, 28, 'Phụ thu khách đến muộn', 'late_arrival_fee', 'once', 500000.00, 1, 1, 1, 2, 1, 1, 'confirmed', 4, '2026-07-20 16:36:40', 'Khách dự kiến đến sau 21:00 đến trước 00:00, phụ thu 50% giá 1 đêm để tiếp tục giữ phòng.', 500000.00, 'Giờ G: 20/07/2026 18:00. Khách dự kiến đến: 20/07/2026 22:30. Giữ phòng đến: 20/07/2026 23:00.', '2026-07-20 09:36:40', '2026-07-20 09:36:40'),
(10, 23, 'booking', NULL, NULL, NULL, NULL, 32, 'Buffet sáng', 'service', 'once', 180000.00, 1, 1, 1, 4, 4, 5, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi WELCOME200BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-07-20 10:46:19', '2026-07-20 10:46:19'),
(11, 30, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 900000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 4, '2026-07-21 00:08:02', 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', 900000.00, 'Check-in sớm lúc 21/07/2026 00:08. Đến sớm 13 giờ 51 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', '2026-07-20 17:08:02', '2026-07-20 17:08:02'),
(12, 31, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 900000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 4, '2026-07-21 00:41:52', 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', 900000.00, 'Check-in sớm lúc 21/07/2026 00:41. Đến sớm 13 giờ 18 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', '2026-07-20 17:41:52', '2026-07-20 17:41:52'),
(13, 32, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 1200000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 4, '2026-07-21 00:50:21', 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', 1200000.00, 'Check-in sớm lúc 21/07/2026 00:50. Đến sớm 13 giờ 9 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', '2026-07-20 17:50:21', '2026-07-20 17:50:21'),
(14, 29, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 1800000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 4, '2026-07-21 02:04:43', 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', 1800000.00, 'Check-in sớm lúc 21/07/2026 02:04. Đến sớm 11 giờ 55 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', '2026-07-20 19:04:43', '2026-07-20 19:04:43'),
(15, 33, 'booking', NULL, NULL, NULL, NULL, 45, 'Phụ thu nhận phòng sớm', 'policy_violation_fee', 'once', 2400000.00, 1, 1, 1, 1, 1, 1, 'confirmed', NULL, NULL, NULL, 2400000.00, 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm. Khách ở 4 đêm và trả phòng lúc 12:00 ngày 25/07/2026.', '2026-07-20 21:33:44', '2026-07-20 21:33:44'),
(19, 36, 'booking', NULL, NULL, NULL, NULL, 45, 'Phụ thu nhận phòng sớm', 'policy_violation_fee', 'once', 1200000.00, 1, 1, 1, 1, 1, 1, 'confirmed', NULL, NULL, NULL, 1200000.00, 'Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm. Khách ở 3 đêm và trả phòng lúc 12:00 ngày 24/07/2026.', '2026-07-21 01:59:47', '2026-07-21 01:59:47'),
(21, 39, 'booking', NULL, NULL, NULL, NULL, 34, 'Buffet sáng DEMO', 'service', 'once', 180000.00, 1, 1, 1, 1, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi DEMO_FREE_BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-07-21 06:43:45', '2026-07-21 06:43:45'),
(22, 40, 'booking', NULL, NULL, NULL, NULL, 35, 'Welcome drink DEMO', 'service', 'once', 80000.00, 1, 1, 1, 2, 1, 3, 'confirmed', NULL, NULL, NULL, 80000.00, NULL, '2026-07-21 07:19:11', '2026-07-21 07:19:11'),
(23, 46, 'booking', NULL, NULL, 'booking_initial', NULL, 37, 'Gửi ô tô qua đêm', 'service', 'per_night', 100000.00, 1, 1, 3, 3, 1, 1, 'confirmed', NULL, NULL, NULL, 300000.00, NULL, '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(24, 46, 'booking', NULL, NULL, 'booking_initial', NULL, 32, 'Buffet sáng', 'service', 'once', 180000.00, 1, 1, 1, 3, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, NULL, '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(25, 46, 'booking', NULL, NULL, 'booking_initial', NULL, 34, 'Buffet sáng DEMO', 'service', 'once', 180000.00, 1, 1, 1, 3, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, NULL, '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(26, 46, 'booking', NULL, NULL, 'booking_initial', NULL, 33, 'Đồ uống chào mừng', 'service', 'once', 80000.00, 1, 1, 1, 3, 1, 1, 'confirmed', NULL, NULL, NULL, 80000.00, NULL, '2026-08-21 16:41:41', '2026-08-21 16:41:41'),
(28, 46, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 1000000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 4, '2026-08-22 00:25:02', 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', 1000000.00, 'Check-in sớm lúc 22/08/2026 00:25. Đến sớm 13 giờ 34 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', '2026-08-21 17:25:02', '2026-08-21 17:25:02'),
(29, 46, 'booking', NULL, NULL, 'promotion', NULL, 35, 'Welcome drink DEMO', 'service', 'once', 80000.00, 2, 2, 2, 3, 1, 3, 'confirmed', NULL, NULL, NULL, 160000.00, 'Tự thêm từ mã ưu đãi DEMO_INCIDENT_FULL: Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-21 18:52:09', '2026-08-21 18:52:09'),
(30, 47, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 500000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 5, '2026-08-22 06:39:09', 'Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', 500000.00, 'Check-in sớm lúc 22/08/2026 06:39. Đến sớm 7 giờ 20 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', '2026-08-21 23:39:09', '2026-08-21 23:39:09'),
(31, 47, 'room', 68, 1, 'room_issue_promotion', 15, 35, 'Welcome drink DEMO', 'service', 'once', 80000.00, 2, 2, 2, 6, 1, 1, 'confirmed', NULL, NULL, NULL, 160000.00, 'Tự thêm từ mã ưu đãi DEMO_INCIDENT_FULL: Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-22 00:04:18', '2026-08-22 00:04:18'),
(32, 48, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 500000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 5, '2026-08-22 08:46:54', 'Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', 500000.00, 'Check-in sớm lúc 22/08/2026 08:46. Đến sớm 5 giờ 13 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', '2026-08-22 01:46:54', '2026-08-22 01:46:54'),
(33, 49, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 500000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 5, '2026-08-22 08:53:39', 'Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', 500000.00, 'Check-in sớm lúc 22/08/2026 08:53. Đến sớm 5 giờ 6 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', '2026-08-22 01:53:40', '2026-08-22 01:53:40'),
(34, 50, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 600000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 5, '2026-08-22 08:56:32', 'Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', 600000.00, 'Check-in sớm lúc 22/08/2026 08:56. Đến sớm 5 giờ 3 phút. Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.', '2026-08-22 01:56:32', '2026-08-22 01:56:32'),
(35, 50, 'room', 73, 1, 'room_issue_promotion', 16, 32, 'Buffet sáng', 'service', 'once', 180000.00, 1, 1, 1, 3, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi WELCOME200BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-08-22 01:59:58', '2026-08-22 01:59:58'),
(38, 52, 'booking', NULL, NULL, 'booking_initial', NULL, 37, 'Gửi ô tô qua đêm', 'service', 'per_night', 100000.00, 1, 1, 2, 2, 1, 1, 'confirmed', NULL, NULL, NULL, 200000.00, NULL, '2026-08-23 20:16:49', '2026-08-23 20:16:49'),
(39, 53, 'booking', NULL, NULL, 'promotion', NULL, 34, 'Buffet sáng DEMO', 'service', 'once', 180000.00, 1, 1, 1, 2, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi DEMO_FREE_BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-08-23 22:36:35', '2026-08-23 22:36:35'),
(40, 53, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 1000000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 5, '2026-08-24 05:51:49', 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', 1000000.00, 'Check-in sớm lúc 24/08/2026 05:51. Đến sớm 8 giờ 8 phút. Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.', '2026-08-23 22:51:49', '2026-08-23 22:51:49'),
(41, 52, 'booking', NULL, NULL, NULL, NULL, 43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'once', 240000.00, 1, 1, 1, 1, 1, 1, 'confirmed', 5, '2026-08-24 10:16:57', 'Check-in sớm từ 09:00 đến trước 12:00, phụ thu 20% giá 1 đêm.', 240000.00, 'Check-in sớm lúc 24/08/2026 10:16. Đến sớm 3 giờ 43 phút. Check-in sớm từ 09:00 đến trước 12:00, phụ thu 20% giá 1 đêm.', '2026-08-24 03:16:57', '2026-08-24 03:16:57'),
(42, 52, 'room', 77, 13, 'room_issue_promotion', 18, 35, 'Welcome drink DEMO', 'service', 'once', 80000.00, 2, 2, 2, 2, 1, 1, 'confirmed', NULL, NULL, NULL, 160000.00, 'Tự thêm từ mã ưu đãi INCIDENT_FULL: Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-24 03:25:34', '2026-08-24 03:25:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_staff_assignments`
--

CREATE TABLE `booking_staff_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `role_in_booking` enum('owner','check_in','check_out','payment','support') NOT NULL DEFAULT 'owner',
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','done','canceled') NOT NULL DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_staff_assignments`
--

INSERT INTO `booking_staff_assignments` (`id`, `booking_id`, `staff_id`, `role_in_booking`, `assigned_by`, `status`, `note`, `created_at`, `updated_at`) VALUES
(1, 49, 5, 'owner', 7, 'done', NULL, '2026-08-22 01:51:51', '2026-08-23 17:05:14'),
(2, 52, 5, 'owner', NULL, 'canceled', NULL, '2026-08-23 22:34:51', '2026-08-23 22:35:26'),
(3, 52, 29, 'owner', NULL, 'canceled', NULL, '2026-08-23 22:35:26', '2026-08-23 23:09:21'),
(4, 53, 5, 'owner', NULL, 'active', NULL, '2026-08-23 22:36:35', '2026-08-23 22:36:35'),
(5, 52, 5, 'owner', NULL, 'canceled', NULL, '2026-08-23 23:09:21', '2026-08-23 23:24:51'),
(6, 52, 29, 'owner', NULL, 'canceled', NULL, '2026-08-23 23:24:51', '2026-08-23 23:42:21'),
(7, 52, 5, 'owner', NULL, 'canceled', NULL, '2026-08-23 23:42:21', '2026-08-24 02:03:49'),
(8, 52, 29, 'owner', NULL, 'canceled', NULL, '2026-08-24 02:03:49', '2026-08-24 02:59:33'),
(9, 52, 5, 'owner', NULL, 'active', NULL, '2026-08-24 02:59:33', '2026-08-24 02:59:33');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel_cache_1b6453892473a467d07372d45eb05abc2031647a', 'i:1;', 1787504856),
('laravel_cache_1b6453892473a467d07372d45eb05abc2031647a:timer', 'i:1787504856;', 1787504856),
('laravel_cache_5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1787505262),
('laravel_cache_5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1787505262;', 1787505262),
('laravel_cache_91032ad7bbcb6cf72875e8e8207dcfba80173f7c', 'i:1;', 1787524805),
('laravel_cache_91032ad7bbcb6cf72875e8e8207dcfba80173f7c:timer', 'i:1787524805;', 1787524805),
('laravel_cache_9e6a55b6b4563e652a23be9d623ca5055c356940', 'i:2;', 1787517584),
('laravel_cache_9e6a55b6b4563e652a23be9d623ca5055c356940:timer', 'i:1787517584;', 1787517584),
('laravel_cache_ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4', 'i:1;', 1787505101),
('laravel_cache_ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4:timer', 'i:1787505101;', 1787505101),
('laravel_cache_b3f0c7f6bb763af1be91d9e74eabfeb199dc1f1f', 'i:4;', 1787516826),
('laravel_cache_b3f0c7f6bb763af1be91d9e74eabfeb199dc1f1f:timer', 'i:1787516826;', 1787516826),
('laravel_cache_f1abd670358e036c31296e66b3b66c382ac00812', 'i:1;', 1787524522),
('laravel_cache_f1abd670358e036c31296e66b3b66c382ac00812:timer', 'i:1787524522;', 1787524522),
('laravel_cache_receptionist-working-booking:52', 'i:5;', 1787546073),
('laravel_cache_receptionist-working-booking:53', 'i:5;', 1787540988),
('laravel_cache_vlinh319@gmail.com|127.0.0.1', 'i:1;', 1787516455),
('laravel_cache_vlinh319@gmail.com|127.0.0.1:timer', 'i:1787516455;', 1787516455);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_assignment_logs`
--

CREATE TABLE `chat_assignment_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `from_staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_assignment_logs`
--

INSERT INTO `chat_assignment_logs` (`id`, `conversation_id`, `from_staff_id`, `to_staff_id`, `reason`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 5, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 20:19:01', '2026-08-23 20:19:01'),
(2, 2, NULL, 29, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 20:19:13', '2026-08-23 20:19:13'),
(3, 3, NULL, 5, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 20:19:23', '2026-08-23 20:19:23'),
(4, 4, NULL, 29, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 20:20:26', '2026-08-23 20:20:26'),
(5, 2, 29, 5, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 20:23:56', '2026-08-23 20:23:56'),
(6, 4, 29, 5, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 20:23:56', '2026-08-23 20:23:56'),
(7, 2, 5, 29, 'Chuyển cuộc trò chuyện cho nhân viên khác', '2026-08-23 20:27:22', '2026-08-23 20:27:22'),
(8, 2, 29, 5, 'Gán cho nhân viên online đang xử lý ít khách nhất', '2026-08-23 21:02:21', '2026-08-23 21:02:21'),
(9, 1, 5, 29, 'Cân bằng tải khi có thêm lễ tân Online', '2026-08-23 22:35:26', '2026-08-23 22:35:26'),
(10, 2, 5, 29, 'Cân bằng chat khi có thêm lễ tân Online', '2026-08-23 22:35:26', '2026-08-23 22:35:26'),
(11, 1, 29, 5, 'Bàn giao tự động vì lễ tân cũ Offline', '2026-08-23 23:09:21', '2026-08-23 23:09:21'),
(12, 2, 29, 5, 'Tự động gán gói khách cho lễ tân online có tải thấp nhất', '2026-08-23 23:09:22', '2026-08-23 23:09:22'),
(13, 1, 5, 29, 'Cân bằng tải khi có thêm lễ tân Online', '2026-08-23 23:24:51', '2026-08-23 23:24:51'),
(14, 2, 5, 29, 'Cân bằng chat khi có thêm lễ tân Online', '2026-08-23 23:24:51', '2026-08-23 23:24:51'),
(15, 4, 5, 29, 'Cân bằng chat khi có thêm lễ tân Online', '2026-08-23 23:24:51', '2026-08-23 23:24:51'),
(16, 1, 29, 5, 'Bàn giao tự động vì lễ tân cũ Offline', '2026-08-23 23:42:21', '2026-08-23 23:42:21'),
(17, 2, 29, 5, 'Tự động gán gói khách cho lễ tân online có tải thấp nhất', '2026-08-23 23:42:21', '2026-08-23 23:42:21'),
(18, 4, 29, 5, 'Tự động gán gói khách cho lễ tân online có tải thấp nhất', '2026-08-23 23:42:22', '2026-08-23 23:42:22'),
(19, 1, 5, 29, 'Cân bằng tải khi có thêm lễ tân Online', '2026-08-24 02:03:50', '2026-08-24 02:03:50'),
(20, 2, 5, 29, 'Cân bằng chat khi có thêm lễ tân Online', '2026-08-24 02:03:50', '2026-08-24 02:03:50'),
(21, 1, 29, 5, 'Bàn giao tự động vì lễ tân cũ Offline', '2026-08-24 02:59:34', '2026-08-24 02:59:34'),
(22, 2, 29, 5, 'Tự động gán gói khách cho lễ tân online có tải thấp nhất', '2026-08-24 02:59:34', '2026-08-24 02:59:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_attachments`
--

CREATE TABLE `chat_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `type` enum('image','file') NOT NULL DEFAULT 'file',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_attachments`
--

INSERT INTO `chat_attachments` (`id`, `message_id`, `disk`, `path`, `original_name`, `mime_type`, `extension`, `size`, `type`, `created_at`, `updated_at`) VALUES
(1, 1, 'local', 'chat-attachments/2026/08/24/734220b0-03f9-4826-98d4-509599fcc291.jpg', 'camera-1787516331570.jpg', 'image/jpeg', 'jpg', 18144, 'image', '2026-08-23 20:19:02', '2026-08-23 20:19:02'),
(2, 9, 'local', 'chat-attachments/2026/08/24/66fd8d2a-692e-4c53-9c91-3849f832c87f.docx', '04.118bmđt10-Phieu-dang-ky-TTDN.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 27316, 'file', '2026-08-23 20:23:20', '2026-08-23 20:23:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `booking_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_staff_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_name` varchar(255) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_phone` varchar(30) DEFAULT NULL,
  `status` enum('waiting','assigned','active','closed') NOT NULL DEFAULT 'waiting',
  `priority_score` int(10) UNSIGNED NOT NULL DEFAULT 20,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `customer_id`, `booking_id`, `assigned_staff_id`, `guest_name`, `guest_email`, `guest_phone`, `status`, `priority_score`, `last_message_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 18, NULL, 5, NULL, NULL, NULL, 'assigned', 20, '2026-08-23 20:19:02', NULL, '2026-08-23 20:19:01', '2026-08-24 02:59:34'),
(2, 15, NULL, 5, NULL, NULL, NULL, 'assigned', 20, '2026-08-23 20:19:13', NULL, '2026-08-23 20:19:13', '2026-08-24 02:59:34'),
(3, 19, NULL, 5, NULL, NULL, NULL, 'assigned', 20, '2026-08-23 20:26:42', NULL, '2026-08-23 20:19:23', '2026-08-23 22:36:35'),
(4, 20, NULL, 5, NULL, NULL, NULL, 'assigned', 20, '2026-08-23 20:24:16', NULL, '2026-08-23 20:20:26', '2026-08-23 23:42:21');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_type` enum('customer','staff','system') NOT NULL,
  `sender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `conversation_id`, `sender_type`, `sender_id`, `message`, `is_read`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'customer', 18, 'đơn cuat tôi chọn đc phòng chưa', 1, '2026-08-23 20:24:25', '2026-08-23 20:19:01', '2026-08-23 20:24:25'),
(2, 2, 'customer', 15, 'chào', 1, '2026-08-23 20:19:38', '2026-08-23 20:19:13', '2026-08-23 20:19:38'),
(3, 3, 'customer', 19, 'helo', 1, '2026-08-23 20:19:31', '2026-08-23 20:19:23', '2026-08-23 20:19:31'),
(4, 4, 'customer', 20, 'dxtcfybh', 1, '2026-08-23 20:20:33', '2026-08-23 20:20:26', '2026-08-23 20:20:33'),
(5, 4, 'staff', 29, 'èwreb', 0, NULL, '2026-08-23 20:20:56', '2026-08-23 20:20:56'),
(6, 4, 'customer', 20, 'uyig', 1, '2026-08-23 20:22:02', '2026-08-23 20:22:02', '2026-08-23 20:22:02'),
(7, 4, 'customer', 20, 'cfvgwbhcne', 1, '2026-08-23 20:22:26', '2026-08-23 20:22:26', '2026-08-23 20:22:26'),
(8, 4, 'staff', 29, 'ytrbtevr', 0, NULL, '2026-08-23 20:22:36', '2026-08-23 20:22:36'),
(9, 3, 'staff', 5, NULL, 0, NULL, '2026-08-23 20:23:20', '2026-08-23 20:23:20'),
(10, 3, 'customer', 19, 'ok', 1, '2026-08-23 20:24:05', '2026-08-23 20:24:04', '2026-08-23 20:24:05'),
(11, 4, 'customer', 20, 't5hyj', 1, '2026-08-23 20:24:17', '2026-08-23 20:24:16', '2026-08-23 20:24:17'),
(12, 3, 'customer', 19, 'tfvgbuh', 1, '2026-08-23 20:26:09', '2026-08-23 20:26:08', '2026-08-23 20:26:09'),
(13, 3, 'staff', 5, 'ftucw', 0, NULL, '2026-08-23 20:26:42', '2026-08-23 20:26:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_staff_presences`
--

CREATE TABLE `chat_staff_presences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('online','away','offline') NOT NULL DEFAULT 'offline',
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_staff_presences`
--

INSERT INTO `chat_staff_presences` (`id`, `user_id`, `status`, `last_seen_at`, `last_assigned_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'online', '2026-08-24 04:24:19', '2026-08-24 03:31:46', '2026-08-21 18:02:05', '2026-08-24 04:24:19'),
(2, 29, 'offline', '2026-08-24 02:59:33', '2026-08-24 02:39:27', '2026-08-23 20:18:27', '2026-08-24 02:59:33');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `cccd` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('active','blacklist') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `first_name`, `last_name`, `phone`, `cccd`, `email`, `birthday`, `gender`, `address`, `avatar`, `note`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 14, 'Chiến', 'Trịnh Ngọc', '0985795608', '038206022002', 'tc19092006@gmail.com', '2006-09-19', 'male', 'Thôn Vệ, Định Hưng, Yên Định, Thanh Hóa', NULL, NULL, 'active', '2026-07-17 10:58:20', '2026-07-28 05:06:01', NULL),
(2, 15, 'Du', 'Đào', '0985795123', '038245722123', 'du319@gmail.com', '2002-09-19', 'male', 'Hậu Lộc', NULL, NULL, 'active', '2026-07-17 14:09:47', '2026-07-17 14:09:47', NULL),
(3, NULL, 'A', 'Nguyễn Văn', '0985795628', '038206022628', 'chientr319@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-07-20 04:14:53', '2026-07-20 04:33:40', NULL),
(4, NULL, 'A', 'Nguyễn Văn', '0985795611', '038206022411', 'chientr319@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-07-20 21:33:44', '2026-07-20 21:33:44', NULL),
(5, 16, 'Anh', 'Nguyễn', '0985795638', '036206022123', 'nguyena1@gmail.com', '2006-06-16', 'male', 'số 12, xã Xuân Hinh\r\nsố 04, đường Nguyễn Du', NULL, NULL, 'active', '2026-07-21 04:39:14', '2026-07-21 04:39:14', NULL),
(6, 18, 'Minh Anh', 'Nguyễn', '0901000001', '038200000001', 'demo.user01@booking.local', '1998-02-15', 'female', 'Thành phố Thanh Hóa, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(7, 19, 'Quốc Bảo', 'Trần', '0901000002', '038200000002', 'demo.user02@booking.local', '1995-06-20', 'male', 'Huyện Đông Sơn, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(8, 20, 'Thu Hà', 'Lê', '0901000003', '038200000003', 'demo.user03@booking.local', '2000-11-03', 'female', 'Thị xã Bỉm Sơn, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(9, 21, 'Hoàng Nam', 'Phạm', '0901000004', '038200000004', 'demo.user04@booking.local', '1997-09-12', 'male', 'Huyện Hoằng Hóa, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(10, 22, 'Ngọc Lan', 'Vũ', '0901000005', '038200000005', 'demo.user05@booking.local', '2001-04-28', 'female', 'Huyện Quảng Xương, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(11, 23, 'Văn Hùng', 'Đỗ', '0901000006', '038200000006', 'demo.user06@booking.local', '1993-12-08', 'male', 'Huyện Yên Định, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(12, 24, 'Khánh Linh', 'Bùi', '0901000007', '038200000007', 'demo.user07@booking.local', '1999-07-19', 'female', 'Huyện Thiệu Hóa, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(13, 25, 'Đức Long', 'Hoàng', '0901000008', '038200000008', 'demo.user08@booking.local', '1996-03-25', 'male', 'Huyện Hà Trung, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(14, 26, 'Mai Phương', 'Đặng', '0901000009', '038200000009', 'demo.user09@booking.local', '2002-01-17', 'female', 'Huyện Nông Cống, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(15, 27, 'Tuấn Kiệt', 'Ngô', '0901000010', '038200000010', 'demo.user10@booking.local', '1994-10-30', 'male', 'Thành phố Sầm Sơn, Thanh Hóa', NULL, 'Tài khoản khách hàng dùng để demo đăng nhập thường', 'active', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(16, 28, 'Cường', 'Dương', '0353725042', '036206022066', 'sccuong5222@gmail.com', '2017-04-13', 'male', 'Huyện Yên Định', NULL, NULL, 'active', '2026-07-21 07:27:28', '2026-07-28 05:08:56', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_credits`
--

CREATE TABLE `customer_credits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `source_booking_id` bigint(20) UNSIGNED NOT NULL,
  `original_amount` decimal(12,2) NOT NULL,
  `remaining_amount` decimal(12,2) NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('active','used','expired') NOT NULL DEFAULT 'active',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_requests`
--

CREATE TABLE `customer_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('late_arrival','extend_stay','schedule_change','guest_information','invoice') NOT NULL,
  `source` enum('customer_web','guest_email','receptionist') NOT NULL DEFAULT 'customer_web',
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `requested_at` timestamp NULL DEFAULT NULL,
  `expected_arrival_at` timestamp NULL DEFAULT NULL,
  `requested_check_out_at` timestamp NULL DEFAULT NULL,
  `receptionist_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `customer_requests`
--

INSERT INTO `customer_requests` (`id`, `booking_id`, `type`, `source`, `status`, `customer_name`, `customer_email`, `reason`, `details`, `requested_at`, `expected_arrival_at`, `requested_check_out_at`, `receptionist_note`, `admin_note`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 42, 'late_arrival', 'customer_web', 'approved', 'Trịnh Ngọc Chiến', 'tc19092006@gmail.com', 'xe đến muộn', '{\"version\":8,\"admin_acknowledged_version\":8,\"last_update_summary\":\"Kh\\u00e1ch \\u0111\\u00e3 c\\u1eadp nh\\u1eadt l\\u1ea1i gi\\u1edd d\\u1ef1 ki\\u1ebfn \\u0111\\u1ebfn v\\u00e0 n\\u1ed9i dung y\\u00eau c\\u1ea7u.\",\"last_updated_at\":\"2026-07-28 16:43:58\",\"admin_acknowledged_at\":\"2026-07-28 16:44:09\"}', '2026-07-28 09:43:58', '2026-07-28 13:30:00', NULL, NULL, NULL, 4, '2026-07-28 09:44:14', '2026-07-28 05:43:07', '2026-07-28 09:44:14');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_request_attachments`
--

CREATE TABLE `customer_request_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_request_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `customer_request_attachments`
--

INSERT INTO `customer_request_attachments` (`id`, `customer_request_id`, `file_path`, `original_name`, `mime_type`, `file_size`, `created_at`, `updated_at`) VALUES
(1, 1, 'customer-requests/1/WcWbz8Dd9nMTEYcersP9bBy2RSx8q0iXjIMPtCBG.png', 'Desktop - 10.png', 'image/png', 2031860, '2026-07-28 05:43:08', '2026-07-28 05:43:08'),
(2, 1, 'customer-requests/1/Ax063XHTW2BdNhXQh3alJTP9aFB5ddSY8rM25xoW.jpg', 'pexels-denis-linine-214373-714258.jpg', 'image/jpeg', 1036850, '2026-07-28 05:43:08', '2026-07-28 05:43:08'),
(3, 1, 'customer-requests/1/Sn6ImKS4iX3O7p5S1Z8SDAwAsBz0ffHQFLTJXOfY.jpg', 'pexels-eberhardgross-858115.jpg', 'image/jpeg', 2314979, '2026-07-28 05:43:08', '2026-07-28 05:43:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hotel_policies`
--

CREATE TABLE `hotel_policies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `policy_group` varchar(50) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','integer','decimal','boolean','time','json') NOT NULL DEFAULT 'string',
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hotel_policies`
--

INSERT INTO `hotel_policies` (`id`, `policy_group`, `key`, `value`, `type`, `label`, `description`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
(1, 'booking', 'booking.min_age', '18', 'integer', 'Tuổi tối thiểu người đứng tên', 'Áp dụng cho khách đứng tên booking ở mọi kênh.', 10, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(2, 'booking', 'booking.cleaning_buffer_minutes', '0', 'integer', 'Buffer dọn phòng giữa hai booking (phút)', 'Khoảng đệm thêm khi kiểm tra phòng trống. Không thay thế ca dọn phòng thực tế.', 20, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(3, 'booking', 'booking.direct_cancel_cutoff_time', '14:00', 'time', 'Mốc hủy trực tiếp ngày nhận phòng', 'Từ mốc này khách không tự hủy trực tiếp mà chuyển sang luồng xác nhận/đến muộn.', 21, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(4, 'booking', 'booking.hourly_cancel_grace_minutes', '30', 'integer', 'Ân hạn hủy booking theo giờ (phút)', 'Khoảng thời gian sau giờ nhận dự kiến mà booking theo giờ còn áp dụng mốc hủy.', 22, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(5, 'payment', 'payment.deposit_percent', '30', 'decimal', 'Mức cọc tối thiểu (%)', 'Tính trên tiền phòng sau ưu đãi áp vào phòng.', 30, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(6, 'payment', 'payment.vnpay_expire_minutes', '30', 'integer', 'Thời hạn link VNPay khi khách đặt online (phút)', 'Hết hạn thì link không còn được dùng để thanh toán đơn đó.', 40, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(7, 'payment', 'payment.admin_vnpay_expire_minutes', '1440', 'integer', 'Thời hạn link VNPay do lễ tân gửi (phút)', 'Link mới phải vô hiệu hóa link cũ còn hiệu lực của cùng mục đích.', 50, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(8, 'stay', 'stay.standard_check_in_time', '14:00', 'time', 'Giờ check-in tiêu chuẩn', NULL, 60, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(9, 'stay', 'stay.standard_check_out_time', '12:00', 'time', 'Giờ check-out tiêu chuẩn', NULL, 70, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(10, 'stay', 'stay.early_checkin_free_from', '12:00', 'time', 'Bắt đầu khung check-in sớm miễn phí', 'Miễn phí khi phòng đã sẵn sàng.', 80, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(11, 'stay', 'stay.early_checkin_tier1_end', '06:00', 'time', 'Mốc check-in sớm mức 1', NULL, 90, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(12, 'stay', 'stay.early_checkin_tier2_end', '09:00', 'time', 'Mốc check-in sớm mức 2', NULL, 100, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(13, 'stay', 'stay.early_checkin_percent_1', '100', 'decimal', 'Phụ thu check-in sớm mức 1 (%)', NULL, 110, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(14, 'stay', 'stay.early_checkin_percent_2', '50', 'decimal', 'Phụ thu check-in sớm mức 2 (%)', NULL, 120, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(15, 'stay', 'stay.early_checkin_percent_3', '20', 'decimal', 'Phụ thu check-in sớm mức 3 (%)', NULL, 130, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(16, 'stay', 'stay.late_checkout_free_minutes', '15', 'integer', 'Ân hạn check-out muộn (phút)', NULL, 140, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(17, 'stay', 'stay.late_checkout_tier1_end', '13:00', 'time', 'Mốc check-out muộn mức 1', NULL, 150, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(18, 'stay', 'stay.late_checkout_tier2_end', '14:00', 'time', 'Mốc check-out muộn mức 2', NULL, 160, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(19, 'stay', 'stay.late_checkout_tier3_end', '15:00', 'time', 'Mốc check-out muộn mức 3', NULL, 170, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(20, 'stay', 'stay.late_checkout_full_night_from', '18:00', 'time', 'Mốc check-out tính thêm một đêm', NULL, 180, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(21, 'stay', 'stay.late_checkout_percent_1', '20', 'decimal', 'Phụ thu check-out muộn mức 1 (%)', NULL, 190, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(22, 'stay', 'stay.late_checkout_percent_2', '40', 'decimal', 'Phụ thu check-out muộn mức 2 (%)', NULL, 200, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(23, 'stay', 'stay.late_checkout_percent_3', '60', 'decimal', 'Phụ thu check-out muộn mức 3 (%)', NULL, 210, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(24, 'stay', 'stay.late_checkout_percent_4', '80', 'decimal', 'Phụ thu check-out muộn mức 4 (%)', NULL, 220, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(25, 'stay', 'stay.late_checkout_percent_full', '100', 'decimal', 'Phụ thu từ mốc tính thêm đêm (%)', NULL, 230, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(26, 'stay', 'stay.late_arrival_cutoff_time', '18:00', 'time', 'Giờ G giữ phòng', 'Sau mốc này áp dụng luồng đến muộn/gia hạn thay vì giữ phòng vô hạn.', 240, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(27, 'stay', 'stay.late_arrival_tier1_end', '21:00', 'time', 'Mốc đến muộn mức 1', NULL, 250, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(28, 'stay', 'stay.late_arrival_percent_1', '20', 'decimal', 'Phụ thu đến muộn mức 1 (%)', NULL, 260, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(29, 'stay', 'stay.late_arrival_percent_2', '50', 'decimal', 'Phụ thu đến muộn mức 2 (%)', NULL, 270, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(30, 'stay', 'stay.late_arrival_percent_next_day', '100', 'decimal', 'Phụ thu đến từ ngày hôm sau (%)', NULL, 280, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(31, 'stay', 'stay.late_arrival_grace_minutes', '30', 'integer', 'Ân hạn sau giờ khách báo đến (phút)', NULL, 290, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(32, 'stay', 'stay.rescheduled_after_cutoff_grace_minutes', '120', 'integer', 'Ân hạn đơn đổi lịch sau giờ G (phút)', NULL, 300, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(33, 'stay', 'stay.priority_cleaning_start_time', '12:00', 'time', 'Mốc bắt đầu dọn phòng ưu tiên', NULL, 310, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(34, 'stay', 'stay.priority_cleaning_window_minutes', '120', 'integer', 'Khoảng báo dọn gấp trước khách kế tiếp (phút)', 'Khi phòng vừa trả và có booking kế tiếp trong khoảng này, hệ thống đánh dấu dọn gấp.', 311, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(35, 'stay', 'stay.late_arrival_form_expire_minutes', '1440', 'integer', 'Thời hạn form báo đến muộn gửi email (phút)', 'Thời gian khách có thể dùng đường dẫn được lễ tân gửi để báo giờ dự kiến đến.', 312, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(36, 'stay', 'stay.short_stay_min_minutes', '30', 'integer', 'Thời lượng tối thiểu booking theo giờ (phút)', 'Không cho tạo ca ở theo giờ ngắn hơn mốc này.', 315, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(37, 'stay', 'stay.short_stay_to_overnight_hours', '12', 'integer', 'Ngưỡng chuyển booking theo giờ sang qua đêm (giờ)', 'Nếu thời lượng vượt mốc này, hệ thống tính theo chính sách qua đêm.', 316, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(38, 'stay', 'stay.short_stay_base_hours', '2', 'integer', 'Số giờ cơ bản của gói ở theo giờ', NULL, 320, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(39, 'stay', 'stay.short_stay_base_percent', '50', 'decimal', 'Giá gói giờ cơ bản (% giá đêm)', NULL, 330, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(40, 'stay', 'stay.short_stay_extra_hour_percent', '10', 'decimal', 'Mỗi giờ thêm (% giá đêm)', NULL, 340, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(41, 'stay', 'stay.short_stay_max_percent', '80', 'decimal', 'Trần giá ở theo giờ (% giá đêm)', NULL, 350, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(42, 'room_issue', 'room_issue.proposal_hold_minutes', '30', 'integer', 'Thời gian giữ phòng thay thế (phút)', 'Giữ tạm phòng được đề xuất trong khi lễ tân trao đổi với khách.', 360, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(43, 'housekeeping', 'housekeeping.slow_room_alert_minutes', '120', 'integer', 'Mốc cảnh báo phòng chờ xử lý quá lâu (phút)', 'Dashboard cảnh báo phòng ở trạng thái chờ dọn/chờ kiểm tra lâu hơn mốc này.', 365, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(44, 'chat', 'chat.archive_retention_days', '730', 'integer', 'Mốc lưu trữ hội thoại tham chiếu (ngày)', 'Dùng để archive, không tự xóa hội thoại booking/tranh chấp đang cần tra cứu.', 370, 1, '2026-08-21 08:31:50', '2026-08-21 08:31:50'),
(45, 'booking', 'booking.manual_room_selection_fee', '50000', 'decimal', 'Phí đảm bảo yêu cầu phòng (đ/phòng)', 'Chỉ thu khi khách sạn đáp ứng được yêu cầu chọn phòng thủ công. Không thu nếu không thể đáp ứng.', 23, 1, '2026-08-23 14:02:05', '2026-08-23 14:02:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hotel_reviews`
--

CREATE TABLE `hotel_reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `cleanliness_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `service_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `comfort_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `location_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `staff_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `value_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','approved','hidden') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `hidden_by` bigint(20) UNSIGNED DEFAULT NULL,
  `hidden_at` timestamp NULL DEFAULT NULL,
  `hidden_reason` varchar(500) DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_by` bigint(20) UNSIGNED DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hotel_reviews`
--

INSERT INTO `hotel_reviews` (`id`, `booking_id`, `user_id`, `customer_id`, `room_category_id`, `rating`, `cleanliness_rating`, `service_rating`, `comfort_rating`, `location_rating`, `staff_rating`, `value_rating`, `title`, `comment`, `status`, `approved_by`, `approved_at`, `hidden_by`, `hidden_at`, `hidden_reason`, `admin_reply`, `replied_by`, `replied_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 46, 18, 6, 1, 1, 1, 1, 1, 1, 1, 1, 'bố tổ', 'nhân viên kém', 'approved', NULL, '2026-08-21 22:40:26', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-21 22:40:26', '2026-08-21 22:40:26', NULL),
(2, 48, 18, 6, 5, 3, 3, 3, 3, 3, 3, 1, 'kém', 'dmm như qq', 'approved', NULL, '2026-08-22 01:49:35', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-22 01:49:35', '2026-08-22 01:49:35', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_code` varchar(255) NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `room_numbers` text NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `actual_check_in` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `room_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minibar_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `extra_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `damage_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `issued_at` timestamp NULL DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000001_create_cache_table', 1),
(2, '0001_01_01_000002_create_jobs_table', 1),
(3, '2026_05_11_023236_create_users_table', 1),
(4, '2026_05_11_023332_create_customers_table', 1),
(5, '2026_05_11_030206_create_sessions_table', 1),
(6, '2026_05_11_033939_add_avatar_to_users_table', 1),
(7, '2026_05_11_121301_create_password_reset_tokens_table', 1),
(8, '2026_05_21_041303_create_staffs_table', 1),
(9, '2026_05_24_100520_create_room_categories_table', 1),
(10, '2026_05_25_030238_create_room_categories_table', 2),
(11, '2026_05_25_030303_create_room_category_images_table', 2),
(12, '2026_05_30_060245_create_rooms_table', 3),
(13, '2026_06_05_121610_create_rooms_table', 4),
(14, '2026_06_05_123052_create_services_table', 4),
(15, '2026_06_05_124556_create_amenities_table', 5),
(16, '2026_06_05_125749_create_room_category_amenities_table', 6),
(17, '2026_06_07_190316_create_bookings_table', 7),
(18, '2026_06_07_190319_create_booking_rooms_table', 7),
(19, '2026_06_11_140937_create_room_inspections_table', 8),
(20, '2026_06_11_145746_create_room_inspection_items_table', 9),
(21, '2026_06_14_160425_add_check_in_out_at_to_bookings_table', 10),
(22, '2026_06_14_161005_add_check_in_out_at_to_bookings_table', 11),
(23, '2026_07_07_134033_add_google_id_to_users_table', 12),
(24, '2026_06_15_164536_create_booking_service_items_table', 13),
(25, '2026_06_15_164608_update_services_type_add_extra_guest_fee', 14),
(26, '2026_06_30_000001_create_hotel_reviews_table', 15),
(27, '2026_07_08_015859_create_invoices_table', 16),
(28, '2026_06_22_163017_create_booking_guests_table', 17),
(29, '2026_07_02_210156_create_room_action_logs_table', 17),
(30, '2026_07_03_054022_add_inspection_to_rooms_status_enum', 17),
(31, '2026_07_03_070028_add_status_schedule_to_rooms_table', 17);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotions`
--

CREATE TABLE `promotions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `promotion_type` enum('normal_discount','event_discount','support_discount','conditional_discount') NOT NULL DEFAULT 'normal_discount',
  `discount_type` enum('percent','fixed_amount') NOT NULL DEFAULT 'fixed_amount',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(12,2) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `stay_from` date DEFAULT NULL,
  `stay_to` date DEFAULT NULL,
  `min_booking_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_nights` int(11) NOT NULL DEFAULT 0,
  `min_rooms` int(11) NOT NULL DEFAULT 0,
  `min_completed_bookings` int(11) NOT NULL DEFAULT 0,
  `min_total_spent` decimal(12,2) NOT NULL DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `per_customer_limit` int(11) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `user_can_apply` tinyint(1) NOT NULL DEFAULT 1,
  `admin_can_apply` tinyint(1) NOT NULL DEFAULT 1,
  `requires_note` tinyint(1) NOT NULL DEFAULT 0,
  `is_stackable` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `promotions`
--

INSERT INTO `promotions` (`id`, `code`, `name`, `description`, `promotion_type`, `discount_type`, `discount_value`, `max_discount_amount`, `valid_from`, `valid_to`, `stay_from`, `stay_to`, `min_booking_amount`, `min_nights`, `min_rooms`, `min_completed_bookings`, `min_total_spent`, `usage_limit`, `used_count`, `per_customer_limit`, `is_public`, `user_can_apply`, `admin_can_apply`, `requires_note`, `is_stackable`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME10', 'Mã thường cho khách', 'Khách tự chọn được khi đặt phòng online.', 'normal_discount', 'percent', 10.00, 200000.00, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 0.00, 0, 0, 0, 0.00, 500, 3, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-08-23 20:16:42'),
(2, 'EVENT15', 'Mã sự kiện', 'Áp dụng cho dịp/sự kiện khách sạn chỉ định.', 'event_discount', 'percent', 15.00, 300000.00, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 1000000.00, 0, 0, 0, 0.00, 200, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-08-23 20:16:42'),
(3, 'SUPPORT100K', 'Mã hỗ trợ khách', 'Chỉ admin/lễ tân dùng khi cần hỗ trợ khách vì sự cố thực tế.', 'support_discount', 'fixed_amount', 100000.00, NULL, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 2, NULL, 0, 0, 1, 1, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-07-20 10:51:29'),
(4, 'STAY2NIGHT', 'Ở từ 2 đêm', 'Mã điều kiện: booking từ 2 đêm trở lên.', 'conditional_discount', 'fixed_amount', 150000.00, NULL, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 0.00, 2, 0, 0, 0.00, 300, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-06-21 13:56:04'),
(5, 'WELCOME200BF', 'Giảm 200k + buffet sáng miễn phí', 'Mã test: giảm trực tiếp 200.000đ và tặng 1 suất buffet sáng 100%. Khách online và admin đều có thể áp dụng.', 'normal_discount', 'fixed_amount', 200000.00, NULL, '2026-06-21 23:29:13', '2026-09-19 23:29:13', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 5, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 16:29:13', '2026-08-22 01:59:58'),
(6, 'FAMILY10DECOR', 'Family 10% + giảm 50% trang trí', 'Mã test: giảm 10% tiền booking, tối đa 300.000đ, kèm giảm 50% dịch vụ trang trí sinh nhật.', 'event_discount', 'percent', 10.00, 300000.00, '2026-06-21 23:29:13', '2026-09-19 23:29:13', NULL, NULL, 1000000.00, 1, 0, 0, 0.00, 100, 1, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 16:29:13', '2026-07-20 10:46:19'),
(7, 'EARLY_UPGRADE', 'Hỗ trợ đổi hạng khi khách đến sớm', 'Mã hỗ trợ nội bộ: dùng khi khách đến check-in sớm nhưng hạng phòng đã đặt chưa có phòng sẵn. Lễ tân đổi sang hạng còn phòng rồi áp mã để hỗ trợ trải nghiệm khách.', 'support_discount', 'fixed_amount', 150000.00, NULL, '2026-06-21 23:29:13', '2026-09-19 23:29:13', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 1, NULL, 0, 0, 1, 1, 1, 'active', NULL, '2026-06-21 16:29:13', '2026-06-21 16:46:16'),
(8, 'REDUCE200', 'Demo giảm trực tiếp 200k', 'Mã thường: giảm thẳng 200.000đ trên tổng booking.', 'normal_discount', 'fixed_amount', 200000.00, NULL, '2026-06-25 14:21:00', '2026-09-23 14:21:00', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 4, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:25', '2026-08-24 02:32:38'),
(9, 'EVENT10', 'Demo sự kiện giảm 10%', 'Mã sự kiện: giảm 10% tổng booking, tối đa 300.000đ.', 'event_discount', 'percent', 10.00, 300000.00, '2026-06-25 14:21:00', '2026-09-23 14:21:00', NULL, NULL, 1000000.00, 0, 0, 0, 0.00, 100, 2, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:25', '2026-08-24 02:33:08'),
(10, 'FREE_BF', 'Demo tặng buffet sáng', 'Mã freebies: tự động tặng 1 suất buffet sáng, không giảm tiền phòng.', 'normal_discount', 'fixed_amount', 0.00, NULL, '2026-06-25 14:21:00', '2026-09-23 14:21:00', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 5, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:26', '2026-08-24 02:30:44'),
(11, 'UPGRADE20', 'Demo upsell nâng hạng 20%', 'Mã điều kiện: giảm 20% phần chênh lệch khi khách chủ động nâng lên hạng cao hơn. Khách vẫn trả phần chênh còn lại.', 'conditional_discount', 'fixed_amount', 0.00, NULL, '2026-06-25 14:21:00', '2026-09-23 14:21:00', NULL, NULL, 0.00, 1, 1, 0, 0.00, 100, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:26', '2026-08-24 02:31:22'),
(12, 'INCIDENT_FULL', 'Demo hỗ trợ nâng hạng do sự cố', 'Mã hỗ trợ: dùng khi phòng lỗi/hết phòng cùng hạng, khách sạn chịu toàn bộ tiền chênh nâng hạng. Khách không trả thêm tiền.', 'support_discount', 'fixed_amount', 0.00, NULL, '2026-06-25 14:21:00', '2026-09-23 14:21:00', NULL, NULL, 0.00, 0, 0, 0, 0.00, NULL, 9, NULL, 0, 0, 1, 1, 1, 'active', NULL, '2026-06-25 07:21:26', '2026-08-24 03:25:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_room_upgrade_offers`
--

CREATE TABLE `promotion_room_upgrade_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `upgrade_kind` enum('incident_support','paid_upsell') NOT NULL DEFAULT 'paid_upsell',
  `from_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `to_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cover_type` enum('full_difference','percent_difference','fixed_amount') NOT NULL DEFAULT 'percent_difference',
  `cover_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_cover_amount` decimal(12,2) DEFAULT NULL,
  `requires_hotel_fault_reason` tinyint(1) NOT NULL DEFAULT 0,
  `guest_must_pay_extra` tinyint(1) NOT NULL DEFAULT 1,
  `auto_apply_on_upgrade` tinyint(1) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `promotion_room_upgrade_offers`
--

INSERT INTO `promotion_room_upgrade_offers` (`id`, `promotion_id`, `upgrade_kind`, `from_room_category_id`, `to_room_category_id`, `cover_type`, `cover_value`, `max_cover_amount`, `requires_hotel_fault_reason`, `guest_must_pay_extra`, `auto_apply_on_upgrade`, `note`, `created_at`, `updated_at`) VALUES
(4, 11, 'paid_upsell', NULL, NULL, 'percent_difference', 20.00, NULL, 0, 1, 0, 'Demo mã điều kiện upsell: giảm 20% phần chênh lệch nâng hạng, khách trả phần còn lại.', '2026-08-24 02:31:22', '2026-08-24 02:31:22'),
(5, 12, 'incident_support', NULL, NULL, 'full_difference', 100.00, NULL, 1, 0, 0, 'Demo mã hỗ trợ sự cố: khách sạn chịu toàn bộ tiền chênh, khách không trả thêm.', '2026-08-24 02:31:46', '2026-08-24 02:31:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_service_offers`
--

CREATE TABLE `promotion_service_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promotion_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `discount_type` enum('percent','fixed_amount') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 100.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `auto_add_service` tinyint(1) NOT NULL DEFAULT 1,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `promotion_service_offers`
--

INSERT INTO `promotion_service_offers` (`id`, `promotion_id`, `service_id`, `discount_type`, `discount_value`, `quantity`, `auto_add_service`, `note`, `created_at`, `updated_at`) VALUES
(1, 5, 32, 'percent', 100.00, 1, 1, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(2, 6, 16, 'percent', 50.00, 1, 0, 'Giảm 50% dịch vụ trang trí sinh nhật nếu khách có chọn dịch vụ này.', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(3, 7, 32, 'percent', 100.00, 1, 1, 'Tặng 1 suất buffet sáng để bù trải nghiệm khi khách phải đổi hạng phòng do đến sớm.', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(4, 7, 33, 'percent', 100.00, 2, 1, 'Tặng 2 phần đồ uống chào mừng trong lúc khách chờ xử lý nhận phòng/đổi hạng.', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(8, 10, 34, 'percent', 100.00, 1, 1, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-08-24 02:30:44', '2026-08-24 02:30:44'),
(9, 12, 35, 'percent', 100.00, 2, 1, 'Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-08-24 02:31:46', '2026-08-24 02:31:46');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_category_id` bigint(20) UNSIGNED NOT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `status` enum('available','reserved','occupied','cleaning','maintenance','inspection') NOT NULL DEFAULT 'available',
  `status_from` datetime DEFAULT NULL COMMENT 'Thời điểm bắt đầu trạng thái hiện tại',
  `status_until` datetime DEFAULT NULL COMMENT 'Thời điểm kết thúc trạng thái hiện tại',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_category_id`, `floor_number`, `status`, `status_from`, `status_until`, `note`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '101', 1, 1, 'available', NULL, NULL, NULL, '2026-06-08 22:25:58', '2026-08-24 03:15:16', NULL),
(2, '102', 1, 1, 'available', NULL, NULL, NULL, '2026-06-08 22:26:07', '2026-08-24 02:23:07', NULL),
(3, '103', 1, 1, 'available', '2026-07-21 07:48:40', NULL, NULL, '2026-06-08 22:26:18', '2026-07-21 01:29:01', NULL),
(4, '201', 2, 2, 'available', NULL, NULL, NULL, '2026-06-08 22:26:38', '2026-07-21 01:30:52', NULL),
(5, '202', 2, 2, 'occupied', '2026-08-24 09:53:47', NULL, NULL, '2026-06-08 22:26:49', '2026-08-24 02:53:47', NULL),
(6, '203', 2, 2, 'available', NULL, NULL, NULL, '2026-06-08 22:26:59', '2026-07-20 03:12:36', NULL),
(7, '301', 3, 3, 'maintenance', NULL, NULL, NULL, '2026-06-08 22:27:09', '2026-07-20 15:24:15', NULL),
(8, '302', 3, 3, 'available', NULL, NULL, NULL, '2026-06-08 22:27:15', '2026-07-21 07:43:12', NULL),
(9, '401', 4, 4, 'available', '2026-07-21 02:04:43', NULL, NULL, '2026-06-08 22:27:46', '2026-07-21 01:30:54', NULL),
(10, '402', 1, 4, 'available', NULL, NULL, NULL, '2026-06-08 22:33:44', '2026-08-22 02:00:34', NULL),
(11, '403', 1, 4, 'available', NULL, NULL, NULL, '2026-06-11 06:12:48', '2026-08-23 17:48:40', NULL),
(12, '404', 1, 4, 'available', '2026-07-21 08:46:07', NULL, NULL, '2026-06-11 06:12:57', '2026-07-21 01:46:07', NULL),
(13, '405', 1, 4, 'occupied', '2026-08-24 11:23:45', NULL, 'Đã khắc phục sự cố; khách vẫn đang sử dụng phòng.', '2026-06-11 06:13:05', '2026-08-24 04:23:45', NULL),
(14, '406', 2, 4, 'available', NULL, NULL, NULL, '2026-06-21 05:21:55', '2026-07-20 10:59:03', NULL),
(15, '501', 5, 5, 'available', NULL, NULL, NULL, '2026-07-17 10:16:34', '2026-08-24 02:54:53', NULL),
(16, '601', 3, 6, 'available', NULL, NULL, NULL, '2026-07-21 07:41:39', '2026-07-21 07:42:00', '2026-07-21 07:42:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_action_logs`
--

CREATE TABLE `room_action_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_time` datetime NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_action_logs`
--

INSERT INTO `room_action_logs` (`id`, `room_id`, `user_id`, `action_type`, `action_time`, `note`, `created_at`, `updated_at`) VALUES
(1, 15, 4, 'check_in', '2026-07-19 09:52:31', 'Khách check-in từ booking #BK202607190949110GW', '2026-07-19 02:52:31', '2026-07-19 02:52:31'),
(2, 15, 4, 'maintenance_support', '2026-07-19 09:55:41', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK202607190949110GW. Nội dung: điều hòa không hoạt động', '2026-07-19 02:55:41', '2026-07-19 02:55:41'),
(3, 15, 4, 'maintenance_support', '2026-07-19 09:56:29', 'Đã khắc phục xong sự cố. đã sửa xong. Trạng thái phòng sau xử lý: trống.', '2026-07-19 02:56:29', '2026-07-19 02:56:29'),
(4, 1, 4, 'status_change', '2026-07-19 09:59:31', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK202607190949110GW', '2026-07-19 02:59:31', '2026-07-19 02:59:31'),
(6, 1, 4, 'check_out', '2026-07-19 10:16:02', 'Khách trả phòng từ booking #BK202607190949110GW. Chuyển sang trạng thái dọn dẹp.', '2026-07-19 03:16:02', '2026-07-19 03:16:02'),
(7, 1, 4, 'cleaning', '2026-07-19 10:16:13', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-19 03:16:13', '2026-07-19 03:16:13'),
(8, 15, 4, 'check_in', '2026-07-19 10:30:25', 'Khách check-in từ booking #BK20260719102934OGT', '2026-07-19 03:30:25', '2026-07-19 03:30:25'),
(9, 15, 4, 'maintenance_support', '2026-07-19 10:34:42', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK20260719102934OGT. Nội dung: tắc cống', '2026-07-19 03:34:42', '2026-07-19 03:34:42'),
(10, 15, 4, 'maintenance_support', '2026-07-19 10:36:06', 'Đã khắc phục xong sự cố. đã sửa xong cống. Trạng thái phòng sau xử lý: đang ở.', '2026-07-19 03:36:06', '2026-07-19 03:36:06'),
(11, 15, 4, 'status_change', '2026-07-19 10:39:41', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260719102934OGT', '2026-07-19 03:39:41', '2026-07-19 03:39:41'),
(12, 15, 4, 'check_out', '2026-07-19 12:19:44', 'Khách trả phòng từ booking #BK20260719102934OGT. Chuyển sang trạng thái dọn dẹp.', '2026-07-19 05:19:44', '2026-07-19 05:19:44'),
(13, 15, 4, 'cleaning', '2026-07-19 12:19:58', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-19 05:19:58', '2026-07-19 05:19:58'),
(14, 15, 4, 'check_in', '2026-07-19 19:36:55', 'Khách check-in từ booking #BK202607191936100WP', '2026-07-19 12:36:55', '2026-07-19 12:36:55'),
(15, 15, 4, 'maintenance_support', '2026-07-19 19:37:43', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK202607191936100WP. Nội dung: hỏng điều hòa', '2026-07-19 12:37:43', '2026-07-19 12:37:43'),
(16, 1, 4, 'status_change', '2026-07-19 19:39:22', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK202607191936100WP', '2026-07-19 12:39:22', '2026-07-19 12:39:22'),
(17, 1, 4, 'check_out', '2026-07-19 19:40:54', 'Khách trả phòng từ booking #BK202607191936100WP. Chuyển sang trạng thái dọn dẹp.', '2026-07-19 12:40:54', '2026-07-19 12:40:54'),
(18, 3, 4, 'check_in', '2026-07-19 21:18:12', 'Khách check-in từ booking #BK20260719211711IQK', '2026-07-19 14:18:12', '2026-07-19 14:18:12'),
(19, 3, 4, 'status_change', '2026-07-19 21:18:35', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260719211711IQK', '2026-07-19 14:18:35', '2026-07-19 14:18:35'),
(20, 3, 4, 'check_out', '2026-07-19 21:20:01', 'Khách trả phòng từ booking #BK20260719211711IQK. Chuyển sang trạng thái dọn dẹp.', '2026-07-19 14:20:01', '2026-07-19 14:20:01'),
(21, 15, 4, 'maintenance_support', '2026-07-19 21:20:41', 'Đã khắc phục xong sự cố. đã xong. Trạng thái phòng sau xử lý: trống.', '2026-07-19 14:20:41', '2026-07-19 14:20:41'),
(22, 1, 4, 'cleaning', '2026-07-20 10:04:09', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 03:04:09', '2026-07-20 03:04:09'),
(23, 3, 4, 'cleaning', '2026-07-20 10:04:11', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 03:04:11', '2026-07-20 03:04:11'),
(24, 15, 4, 'check_in', '2026-07-20 17:40:40', 'Khách check-in từ booking #BK20260720173922A6S', '2026-07-20 10:40:40', '2026-07-20 10:40:40'),
(25, 13, 4, 'check_in', '2026-07-20 17:48:11', 'Khách check-in từ booking #BK2607203IW1I', '2026-07-20 10:48:11', '2026-07-20 10:48:11'),
(26, 12, 4, 'check_in', '2026-07-20 17:48:11', 'Khách check-in từ booking #BK2607203IW1I', '2026-07-20 10:48:11', '2026-07-20 10:48:11'),
(27, 11, 4, 'check_in', '2026-07-20 17:48:11', 'Khách check-in từ booking #BK2607203IW1I', '2026-07-20 10:48:11', '2026-07-20 10:48:11'),
(28, 10, 4, 'check_in', '2026-07-20 17:48:11', 'Khách check-in từ booking #BK2607203IW1I', '2026-07-20 10:48:11', '2026-07-20 10:48:11'),
(29, 13, 4, 'maintenance_support', '2026-07-20 17:51:29', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK2607203IW1I. Nội dung: tv bật không được', '2026-07-20 10:51:29', '2026-07-20 10:51:29'),
(30, 12, 4, 'maintenance_support', '2026-07-20 17:51:43', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK2607203IW1I. Nội dung: phòng rột trần', '2026-07-20 10:51:43', '2026-07-20 10:51:43'),
(31, 15, 4, 'maintenance_support', '2026-07-20 17:52:03', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK20260720173922A6S. Nội dung: hư điều hòa', '2026-07-20 10:52:03', '2026-07-20 10:52:03'),
(32, 15, 4, 'maintenance_support', '2026-07-20 17:52:29', 'Đã khắc phục xong sự cố. xác sửa xong. Trạng thái phòng sau xử lý: trống.', '2026-07-20 10:52:29', '2026-07-20 10:52:29'),
(33, 12, 4, 'maintenance_support', '2026-07-20 17:52:36', 'Đã khắc phục xong sự cố. xác sửa xong. Trạng thái phòng sau xử lý: trống.', '2026-07-20 10:52:36', '2026-07-20 10:52:36'),
(34, 13, 4, 'maintenance_support', '2026-07-20 17:52:44', 'Đã khắc phục xong sự cố. xác sửa xong. Trạng thái phòng sau xử lý: trống.', '2026-07-20 10:52:44', '2026-07-20 10:52:44'),
(35, 1, 4, 'status_change', '2026-07-20 17:56:33', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK2607203IW1I', '2026-07-20 10:56:33', '2026-07-20 10:56:33'),
(36, 2, 4, 'status_change', '2026-07-20 17:56:33', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK2607203IW1I', '2026-07-20 10:56:33', '2026-07-20 10:56:33'),
(37, 11, 4, 'status_change', '2026-07-20 17:56:33', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK2607203IW1I', '2026-07-20 10:56:33', '2026-07-20 10:56:33'),
(38, 10, 4, 'status_change', '2026-07-20 17:56:33', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK2607203IW1I', '2026-07-20 10:56:33', '2026-07-20 10:56:33'),
(39, 1, 4, 'check_out', '2026-07-20 17:58:31', 'Khách trả phòng từ booking #BK2607203IW1I. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 10:58:31', '2026-07-20 10:58:31'),
(40, 2, 4, 'check_out', '2026-07-20 17:58:31', 'Khách trả phòng từ booking #BK2607203IW1I. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 10:58:31', '2026-07-20 10:58:31'),
(41, 11, 4, 'check_out', '2026-07-20 17:58:31', 'Khách trả phòng từ booking #BK2607203IW1I. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 10:58:31', '2026-07-20 10:58:31'),
(42, 10, 4, 'check_out', '2026-07-20 17:58:31', 'Khách trả phòng từ booking #BK2607203IW1I. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 10:58:31', '2026-07-20 10:58:31'),
(43, 1, 4, 'cleaning', '2026-07-20 17:58:55', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 10:58:55', '2026-07-20 10:58:55'),
(44, 2, 4, 'cleaning', '2026-07-20 17:58:57', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 10:58:57', '2026-07-20 10:58:57'),
(45, 10, 4, 'cleaning', '2026-07-20 17:58:59', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 10:58:59', '2026-07-20 10:58:59'),
(46, 11, 4, 'cleaning', '2026-07-20 17:59:01', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 10:59:01', '2026-07-20 10:59:01'),
(47, 14, 4, 'cleaning', '2026-07-20 17:59:03', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 10:59:03', '2026-07-20 10:59:03'),
(48, 3, 4, 'status_change', '2026-07-20 17:59:14', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260720173922A6S', '2026-07-20 10:59:14', '2026-07-20 10:59:14'),
(49, 3, 4, 'check_out', '2026-07-20 18:00:25', 'Khách trả phòng từ booking #BK20260720173922A6S. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 11:00:25', '2026-07-20 11:00:25'),
(50, 15, 4, 'check_in', '2026-07-20 18:24:43', 'Khách check-in từ booking #BK20260720180117KLW', '2026-07-20 11:24:43', '2026-07-20 11:24:43'),
(51, 15, 4, 'maintenance_support', '2026-07-20 18:26:52', 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking BK20260720180117KLW. Nội dung: hưu điều hòa', '2026-07-20 11:26:52', '2026-07-20 11:26:52'),
(53, 1, 4, 'maintenance_support', '2026-07-20 20:50:51', 'Booking BK20260720180117KLW; phòng 101 giữ nguyên và sửa gấp. Nội dung: hỏng vòi sen', '2026-07-20 13:50:51', '2026-07-20 13:50:51'),
(54, 12, 4, 'check_in', '2026-07-20 21:40:55', 'Khách check-in từ booking #BK260720SPUTZ', '2026-07-20 14:40:55', '2026-07-20 14:40:55'),
(55, 11, 4, 'check_in', '2026-07-20 21:40:55', 'Khách check-in từ booking #BK260720SPUTZ', '2026-07-20 14:40:55', '2026-07-20 14:40:55'),
(56, 13, 4, 'check_in', '2026-07-20 21:40:55', 'Khách check-in từ booking #BK260720SPUTZ', '2026-07-20 14:40:55', '2026-07-20 14:40:55'),
(57, 10, 4, 'check_in', '2026-07-20 21:40:55', 'Khách check-in từ booking #BK260720SPUTZ', '2026-07-20 14:40:55', '2026-07-20 14:40:55'),
(58, 12, 4, 'maintenance_support', '2026-07-20 21:48:48', 'Booking BK260720SPUTZ; phòng 404 giữ nguyên và sửa gấp. Nội dung: đèn không sáng', '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(59, 11, 4, 'maintenance_support', '2026-07-20 21:48:48', 'Booking BK260720SPUTZ; phòng 403 nâng miễn phí sang phòng 301 (Family Suite). Nội dung: điều hòa k chạy', '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(60, 10, 4, 'maintenance_support', '2026-07-20 21:48:48', 'Booking BK260720SPUTZ; phòng 402 nâng miễn phí sang phòng 302 (Family Suite). Nội dung: thích thì báo', '2026-07-20 14:48:48', '2026-07-20 14:48:48'),
(61, 12, 4, 'maintenance_support', '2026-07-20 21:49:31', 'Đã khắc phục xong sự cố. đã done. Trạng thái phòng sau xử lý: đang ở.', '2026-07-20 14:49:31', '2026-07-20 14:49:31'),
(62, 1, 4, 'maintenance_support', '2026-07-20 21:49:40', 'Đã khắc phục xong sự cố. đã done. Trạng thái phòng sau xử lý: đang ở.', '2026-07-20 14:49:40', '2026-07-20 14:49:40'),
(63, 11, 4, 'maintenance_support', '2026-07-20 21:49:47', 'Đã khắc phục xong sự cố. đã done. Trạng thái phòng sau xử lý: trống.', '2026-07-20 14:49:47', '2026-07-20 14:49:47'),
(64, 10, 4, 'maintenance_support', '2026-07-20 21:49:56', 'Đã khắc phục xong sự cố. đã done. Trạng thái phòng sau xử lý: trống.', '2026-07-20 14:49:56', '2026-07-20 14:49:56'),
(65, 15, 4, 'maintenance_support', '2026-07-20 21:50:03', 'Đã khắc phục xong sự cố. đã done. Trạng thái phòng sau xử lý: trống.', '2026-07-20 14:50:03', '2026-07-20 14:50:03'),
(66, 12, 4, 'status_change', '2026-07-20 22:20:39', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260720SPUTZ', '2026-07-20 15:20:39', '2026-07-20 15:20:39'),
(67, 7, 4, 'status_change', '2026-07-20 22:20:39', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260720SPUTZ', '2026-07-20 15:20:39', '2026-07-20 15:20:39'),
(68, 13, 4, 'status_change', '2026-07-20 22:20:39', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260720SPUTZ', '2026-07-20 15:20:39', '2026-07-20 15:20:39'),
(69, 8, 4, 'status_change', '2026-07-20 22:20:39', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260720SPUTZ', '2026-07-20 15:20:39', '2026-07-20 15:20:39'),
(70, 12, 4, 'check_out', '2026-07-20 22:22:56', 'Khách trả phòng từ booking #BK260720SPUTZ. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 15:22:56', '2026-07-20 15:22:56'),
(71, 7, 4, 'check_out', '2026-07-20 22:22:56', 'Khách trả phòng từ booking #BK260720SPUTZ. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 15:22:56', '2026-07-20 15:22:56'),
(72, 13, 4, 'check_out', '2026-07-20 22:22:56', 'Khách trả phòng từ booking #BK260720SPUTZ. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 15:22:56', '2026-07-20 15:22:56'),
(73, 8, 4, 'check_out', '2026-07-20 22:22:56', 'Khách trả phòng từ booking #BK260720SPUTZ. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 15:22:56', '2026-07-20 15:22:56'),
(74, 3, 4, 'cleaning', '2026-07-20 22:23:25', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 15:23:25', '2026-07-20 15:23:25'),
(75, 7, 4, 'cleaning', '2026-07-20 22:23:27', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 15:23:27', '2026-07-20 15:23:27'),
(76, 8, 4, 'cleaning', '2026-07-20 22:23:29', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 15:23:29', '2026-07-20 15:23:29'),
(77, 12, 4, 'cleaning', '2026-07-20 22:23:31', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 15:23:31', '2026-07-20 15:23:31'),
(78, 13, 4, 'cleaning', '2026-07-20 22:23:33', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 15:23:33', '2026-07-20 15:23:33'),
(79, 1, 4, 'status_change', '2026-07-20 22:25:35', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260720180117KLW', '2026-07-20 15:25:35', '2026-07-20 15:25:35'),
(80, 1, 4, 'check_out', '2026-07-20 22:27:16', 'Khách trả phòng từ booking #BK20260720180117KLW. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 15:27:16', '2026-07-20 15:27:16'),
(81, 4, 4, 'check_in', '2026-07-21 00:08:02', 'Khách check-in từ booking #BK20260720223300OTI', '2026-07-20 17:08:02', '2026-07-20 17:08:02'),
(82, 4, 4, 'status_change', '2026-07-21 00:08:14', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260720223300OTI', '2026-07-20 17:08:14', '2026-07-20 17:08:14'),
(83, 4, 4, 'check_out', '2026-07-21 00:37:09', 'Khách trả phòng từ booking #BK20260720223300OTI. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 17:37:09', '2026-07-20 17:37:09'),
(84, 1, 4, 'cleaning', '2026-07-21 00:37:24', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 17:37:24', '2026-07-20 17:37:24'),
(85, 4, 4, 'cleaning', '2026-07-21 00:37:27', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-20 17:37:27', '2026-07-20 17:37:27'),
(86, 4, 4, 'check_in', '2026-07-21 00:41:52', 'Khách check-in từ booking #BK202607210038160NH', '2026-07-20 17:41:52', '2026-07-20 17:41:52'),
(87, 4, 4, 'status_change', '2026-07-21 00:43:33', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK202607210038160NH', '2026-07-20 17:43:33', '2026-07-20 17:43:33'),
(88, 4, 4, 'check_out', '2026-07-21 00:44:42', 'Khách trả phòng từ booking #BK202607210038160NH. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 17:44:42', '2026-07-20 17:44:42'),
(89, 1, 4, 'check_in', '2026-07-21 00:50:21', 'Khách check-in từ booking #BK20260721004610LAH', '2026-07-20 17:50:21', '2026-07-20 17:50:21'),
(90, 1, 4, 'status_change', '2026-07-21 01:16:00', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260721004610LAH', '2026-07-20 18:16:00', '2026-07-20 18:16:00'),
(91, 1, 4, 'check_out', '2026-07-21 01:16:47', 'Khách trả phòng từ booking #BK20260721004610LAH. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 18:16:47', '2026-07-20 18:16:47'),
(92, 8, 4, 'check_in', '2026-07-21 02:04:43', 'Khách check-in từ booking #BK20260720223155HQU', '2026-07-20 19:04:43', '2026-07-20 19:04:43'),
(93, 8, 4, 'maintenance_support', '2026-07-21 02:07:07', 'Booking BK20260720223155HQU; phòng 302 nâng miễn phí sang phòng 401 (Presidential Suite). Nội dung: thích t đổi', '2026-07-20 19:07:07', '2026-07-20 19:07:07'),
(95, 8, 4, 'maintenance_support', '2026-07-21 02:08:13', 'Đã khắc phục xong sự cố. ok done. Trạng thái phòng sau xử lý: trống.', '2026-07-20 19:08:13', '2026-07-20 19:08:13'),
(96, 9, 4, 'status_change', '2026-07-21 03:08:59', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260720223155HQU', '2026-07-20 20:08:59', '2026-07-20 20:08:59'),
(97, 9, 4, 'check_out', '2026-07-21 04:00:36', 'Khách trả phòng từ booking #BK20260720223155HQU. Chuyển sang trạng thái dọn dẹp.', '2026-07-20 21:00:36', '2026-07-20 21:00:36'),
(98, 11, 4, 'check_in', '2026-07-21 06:03:19', 'Có 3 khách check-in từ booking #BK260721CJNQJ', '2026-07-20 23:03:19', '2026-07-20 23:03:19'),
(99, 10, 4, 'check_in', '2026-07-21 06:03:19', 'Có 2 khách check-in từ booking #BK260721CJNQJ', '2026-07-20 23:03:19', '2026-07-20 23:03:19'),
(100, 11, 4, 'status_change', '2026-07-21 07:39:18', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721CJNQJ', '2026-07-21 00:39:18', '2026-07-21 00:39:18'),
(101, 10, 4, 'status_change', '2026-07-21 07:39:18', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721CJNQJ', '2026-07-21 00:39:18', '2026-07-21 00:39:18'),
(102, 11, 4, 'check_out', '2026-07-21 07:46:27', 'Khách trả phòng từ booking #BK260721CJNQJ. Chuyển sang trạng thái dọn dẹp.', '2026-07-21 00:46:27', '2026-07-21 00:46:27'),
(103, 10, 4, 'check_out', '2026-07-21 07:46:27', 'Khách trả phòng từ booking #BK260721CJNQJ. Chuyển sang trạng thái dọn dẹp.', '2026-07-21 00:46:27', '2026-07-21 00:46:27'),
(104, 2, 4, 'check_in', '2026-07-21 08:19:51', 'Có 3 khách check-in từ booking #BK260721BWFOH', '2026-07-21 01:19:51', '2026-07-21 01:19:51'),
(105, 3, 4, 'check_in', '2026-07-21 08:19:51', 'Có 1 khách check-in từ booking #BK260721BWFOH', '2026-07-21 01:19:51', '2026-07-21 01:19:51'),
(106, 2, 4, 'maintenance_support', '2026-07-21 08:22:55', 'Booking BK260721BWFOH; phòng 102 đổi cùng hạng sang phòng 404. Nội dung: hư điều hòa', '2026-07-21 01:22:55', '2026-07-21 01:22:55'),
(107, 12, 4, 'status_change', '2026-07-21 08:23:26', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721BWFOH', '2026-07-21 01:23:26', '2026-07-21 01:23:26'),
(108, 3, 4, 'status_change', '2026-07-21 08:23:26', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721BWFOH', '2026-07-21 01:23:26', '2026-07-21 01:23:26'),
(109, 1, 4, 'cleaning', '2026-07-21 08:30:50', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 01:30:50', '2026-07-21 01:30:50'),
(110, 4, 4, 'cleaning', '2026-07-21 08:30:52', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 01:30:52', '2026-07-21 01:30:52'),
(111, 9, 4, 'cleaning', '2026-07-21 08:30:54', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 01:30:54', '2026-07-21 01:30:54'),
(112, 10, 4, 'cleaning', '2026-07-21 08:30:56', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 01:30:56', '2026-07-21 01:30:56'),
(113, 11, 4, 'cleaning', '2026-07-21 08:30:57', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 01:30:57', '2026-07-21 01:30:57'),
(114, 11, 4, 'check_in', '2026-07-21 10:00:55', 'Có 1 khách check-in từ booking #BK260721R3B79', '2026-07-21 03:00:55', '2026-07-21 03:00:55'),
(115, 10, 4, 'check_in', '2026-07-21 10:00:55', 'Có 3 khách check-in từ booking #BK260721R3B79', '2026-07-21 03:00:55', '2026-07-21 03:00:55'),
(116, 11, 4, 'status_change', '2026-07-21 10:32:19', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721R3B79', '2026-07-21 03:32:19', '2026-07-21 03:32:19'),
(117, 10, 4, 'status_change', '2026-07-21 10:32:19', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721R3B79', '2026-07-21 03:32:19', '2026-07-21 03:32:19'),
(118, 8, 4, 'status_change', '2026-07-21 10:32:19', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK260721R3B79', '2026-07-21 03:32:19', '2026-07-21 03:32:19'),
(119, 11, 4, 'check_out', '2026-07-21 11:52:56', 'Khách trả phòng từ booking #BK260721R3B79. Chuyển sang trạng thái dọn dẹp.', '2026-07-21 04:52:56', '2026-07-21 04:52:56'),
(120, 10, 4, 'check_out', '2026-07-21 11:52:56', 'Khách trả phòng từ booking #BK260721R3B79. Chuyển sang trạng thái dọn dẹp.', '2026-07-21 04:52:56', '2026-07-21 04:52:56'),
(121, 8, 4, 'check_out', '2026-07-21 11:52:56', 'Khách trả phòng từ booking #BK260721R3B79. Chuyển sang trạng thái dọn dẹp.', '2026-07-21 04:52:56', '2026-07-21 04:52:56'),
(122, 8, 14, 'cleaning', '2026-07-21 14:19:11', 'Yêu cầu dọn ưu tiên cho booking BK20260721141911AQB (khách đặt online). Phòng hiện đang dọn.', '2026-07-21 07:19:11', '2026-07-21 07:19:11'),
(123, 1, 7, 'check_in', '2026-07-21 14:33:52', 'Có 2 khách check-in từ booking #BK20260721142728TZA', '2026-07-21 07:33:52', '2026-07-21 07:33:52'),
(124, 1, 7, 'status_change', '2026-07-21 14:35:33', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK20260721142728TZA', '2026-07-21 07:35:33', '2026-07-21 07:35:33'),
(125, 8, 7, 'cleaning', '2026-07-21 14:43:12', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 07:43:12', '2026-07-21 07:43:12'),
(126, 10, 7, 'cleaning', '2026-07-21 14:43:15', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 07:43:15', '2026-07-21 07:43:15'),
(127, 11, 7, 'cleaning', '2026-07-21 14:43:17', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 07:43:17', '2026-07-21 07:43:17'),
(128, 13, 7, 'cleaning', '2026-07-21 14:43:20', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-07-21 07:43:20', '2026-07-21 07:43:20'),
(129, 1, 7, 'check_out', '2026-07-21 14:43:32', 'Khách trả phòng từ booking #BK20260721142728TZA. Chuyển sang trạng thái dọn dẹp.', '2026-07-21 07:43:32', '2026-07-21 07:43:32'),
(130, 1, 6, 'cleaning', '2026-08-21 16:43:52', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-21 09:43:52', '2026-08-21 09:43:52'),
(131, 15, 4, 'check_in', '2026-08-22 00:25:02', 'Có 3 khách check-in từ booking #BK21082026-003', '2026-08-21 17:25:02', '2026-08-21 17:25:02'),
(132, 15, 4, 'maintenance_support', '2026-08-22 01:51:08', 'Booking BK21082026-003; phòng 501 nâng hạng sang phòng 101 (Deluxe Sea View). Nội dung: lỗi điều hòa', '2026-08-21 18:51:08', '2026-08-21 18:51:08'),
(133, 15, 6, 'maintenance_support', '2026-08-22 03:30:37', 'Đã khắc phục xong sự cố. ok. Trạng thái phòng sau xử lý: trống.', '2026-08-21 20:30:37', '2026-08-21 20:30:37'),
(134, 1, 5, 'status_change', '2026-08-22 03:33:34', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK21082026-003', '2026-08-21 20:33:34', '2026-08-21 20:33:34'),
(135, 15, 5, 'status_change', '2026-08-22 03:33:34', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK21082026-003', '2026-08-21 20:33:34', '2026-08-21 20:33:34'),
(136, 1, 5, 'check_out', '2026-08-22 05:36:04', 'Khách trả phòng từ booking #BK21082026-003. Chuyển sang trạng thái dọn dẹp.', '2026-08-21 22:36:04', '2026-08-21 22:36:04'),
(137, 15, 5, 'check_out', '2026-08-22 05:36:04', 'Khách trả phòng từ booking #BK21082026-003. Chuyển sang trạng thái dọn dẹp.', '2026-08-21 22:36:04', '2026-08-21 22:36:04'),
(138, 1, 6, 'cleaning', '2026-08-22 05:36:21', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-21 22:36:21', '2026-08-21 22:36:21'),
(139, 15, 6, 'cleaning', '2026-08-22 05:36:27', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-21 22:36:27', '2026-08-21 22:36:27'),
(140, 15, 5, 'check_in', '2026-08-22 06:39:09', 'Có 1 khách check-in từ booking #BK22082026-001', '2026-08-21 23:39:09', '2026-08-21 23:39:09'),
(141, 15, 4, 'maintenance_support', '2026-08-22 07:04:18', 'Booking BK22082026-001; phòng 501 nâng hạng sang phòng 101 (Deluxe Sea View). Nội dung: đèn hỏng', '2026-08-22 00:04:18', '2026-08-22 00:04:18'),
(142, 15, 6, 'maintenance_support', '2026-08-22 07:04:37', 'Đã khắc phục xong sự cố. ok. Trạng thái phòng sau xử lý: trống.', '2026-08-22 00:04:37', '2026-08-22 00:04:37'),
(143, 1, 5, 'status_change', '2026-08-22 07:31:24', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK22082026-001', '2026-08-22 00:31:24', '2026-08-22 00:31:24'),
(144, 1, 5, 'check_out', '2026-08-22 07:43:44', 'Khách trả phòng từ booking #BK22082026-001. Chuyển sang trạng thái dọn dẹp.', '2026-08-22 00:43:44', '2026-08-22 00:43:44'),
(145, 1, 6, 'cleaning', '2026-08-22 07:44:11', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-22 00:44:11', '2026-08-22 00:44:11'),
(146, 15, 5, 'check_in', '2026-08-22 08:46:54', 'Có 1 khách check-in từ booking #BK22082026-002', '2026-08-22 01:46:54', '2026-08-22 01:46:54'),
(147, 15, 5, 'status_change', '2026-08-22 08:47:15', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK22082026-002', '2026-08-22 01:47:15', '2026-08-22 01:47:15'),
(148, 15, 5, 'check_out', '2026-08-22 08:48:42', 'Khách trả phòng từ booking #BK22082026-002. Chuyển sang trạng thái dọn dẹp.', '2026-08-22 01:48:42', '2026-08-22 01:48:42'),
(149, 15, 6, 'cleaning', '2026-08-22 08:48:57', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-22 01:48:57', '2026-08-22 01:48:57'),
(150, 15, 5, 'check_in', '2026-08-22 08:53:40', 'Có 1 khách check-in từ booking #BK22082026-003', '2026-08-22 01:53:40', '2026-08-22 01:53:40'),
(151, 15, 5, 'status_change', '2026-08-22 08:53:51', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK22082026-003', '2026-08-22 01:53:51', '2026-08-22 01:53:51'),
(152, 10, 5, 'check_in', '2026-08-22 08:56:32', 'Có 1 khách check-in từ booking #BK22082026-004', '2026-08-22 01:56:32', '2026-08-22 01:56:32'),
(153, 10, 7, 'maintenance_support', '2026-08-22 08:59:58', 'Booking BK22082026-004; phòng 402 đổi cùng hạng sang phòng 101. Nội dung: hư quạt gió', '2026-08-22 01:59:58', '2026-08-22 01:59:58'),
(154, 10, 6, 'maintenance_support', '2026-08-22 09:00:34', 'Đã khắc phục xong sự cố. ok. Trạng thái phòng sau xử lý: trống.', '2026-08-22 02:00:34', '2026-08-22 02:00:34'),
(155, 1, 4, 'cleaning', '2026-08-24 00:04:18', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-23 17:04:18', '2026-08-23 17:04:18'),
(156, 15, 4, 'check_out', '2026-08-24 00:05:14', 'Khách trả phòng từ booking #BK22082026-003. Chuyển sang trạng thái dọn dẹp.', '2026-08-23 17:05:14', '2026-08-23 17:05:14'),
(157, 15, 4, 'cleaning', '2026-08-24 00:05:36', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-23 17:05:36', '2026-08-23 17:05:36'),
(158, 13, 5, 'status_change', '2026-08-24 00:07:31', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK22082026-004', '2026-08-23 17:07:31', '2026-08-23 17:07:31'),
(159, 11, 5, 'status_change', '2026-08-24 00:07:31', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK22082026-004', '2026-08-23 17:07:31', '2026-08-23 17:07:31'),
(160, 13, 5, 'check_out', '2026-08-24 00:08:22', 'Khách trả phòng từ booking #BK22082026-004. Chuyển sang trạng thái dọn dẹp.', '2026-08-23 17:08:22', '2026-08-23 17:08:22'),
(161, 11, 5, 'check_out', '2026-08-24 00:08:22', 'Khách trả phòng từ booking #BK22082026-004. Chuyển sang trạng thái dọn dẹp.', '2026-08-23 17:08:22', '2026-08-23 17:08:22'),
(162, 11, 6, 'cleaning', '2026-08-24 00:08:43', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-23 17:08:43', '2026-08-23 17:08:43'),
(163, 13, 6, 'cleaning', '2026-08-24 00:08:48', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-23 17:08:48', '2026-08-23 17:08:48'),
(164, 15, 5, 'check_in', '2026-08-24 05:51:49', 'Có 1 khách check-in từ booking #BK24082026-003', '2026-08-23 22:51:49', '2026-08-23 22:51:49'),
(165, 15, 4, 'cleaning', '2026-08-24 09:54:53', 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.', '2026-08-24 02:54:53', '2026-08-24 02:54:53'),
(166, 13, 5, 'check_in', '2026-08-24 10:16:57', 'Có 1 khách check-in từ booking #BK24082026-002', '2026-08-24 03:16:57', '2026-08-24 03:16:57'),
(167, 13, 4, 'maintenance_support', '2026-08-24 10:25:34', 'Booking BK24082026-002; phòng 405 giữ nguyên và sửa gấp. Nội dung: sẻdtgyhctfvgybuh', '2026-08-24 03:25:34', '2026-08-24 03:25:34'),
(168, 13, 6, 'maintenance_support', '2026-08-24 10:27:40', 'Đã khắc phục xong sự cố. xdcfyubhij. Trạng thái phòng sau xử lý: đang ở.', '2026-08-24 03:27:40', '2026-08-24 03:27:40'),
(169, 13, 5, 'status_change', '2026-08-24 10:31:46', 'Yêu cầu kiểm tra phòng trước check-out từ booking #BK24082026-002', '2026-08-24 03:31:46', '2026-08-24 03:31:46'),
(170, 13, 5, 'inspection_completed', '2026-08-24 11:23:45', 'Đồng bộ phiếu kiểm tra đã hoàn tất của booking #BK24082026-002. Phòng trở lại trạng thái đang ở trước khi check-out.', '2026-08-24 04:23:45', '2026-08-24 04:23:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_categories`
--

CREATE TABLE `room_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `adult_capacity` int(11) NOT NULL,
  `child_capacity` int(11) NOT NULL DEFAULT 0,
  `area` decimal(6,2) DEFAULT NULL,
  `bed_count` int(11) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_categories`
--

INSERT INTO `room_categories` (`id`, `name`, `price`, `adult_capacity`, `child_capacity`, `area`, `bed_count`, `description`, `thumbnail`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Deluxe Sea View', 1200000.00, 2, 1, 35.00, 1, 'Phòng hướng biển với ban công riêng, nội thất hiện đại, phù hợp cho cặp đôi hoặc khách du lịch nghỉ dưỡng.', 'room-categories/thumbnails/nCAIuDVsic8stfuOUohW1SFAQCecUhp6PBgdC0Qu.png', 'active', '2026-06-08 22:17:35', '2026-07-19 03:36:38'),
(2, 'Superior Double', 900000.00, 2, 1, 28.00, 1, 'Phòng tiêu chuẩn với đầy đủ tiện nghi cơ bản, phù hợp cho khách công tác và du lịch ngắn ngày.', 'room-categories/thumbnails/PiKz8SyPlXFPySlWhkMc5qw5UGfr0HgX0I0A2O8c.jpg', 'active', '2026-06-08 22:23:06', '2026-07-19 03:36:31'),
(3, 'Family Suite', 1800000.00, 4, 2, 55.00, 2, 'Phòng gia đình rộng rãi với không gian sinh hoạt chung, thích hợp cho gia đình hoặc nhóm bạn.', 'room-categories/thumbnails/l8aPysA7c8gex7cNLqXwfVKgFGb6E3QryLATjmuG.jpg', 'active', '2026-06-08 22:24:15', '2026-07-19 03:36:25'),
(4, 'Presidential Suite', 5000000.00, 6, 2, 120.00, 3, 'Hạng phòng cao cấp nhất với phòng khách riêng, bồn tắm cao cấp và tầm nhìn toàn cảnh thành phố.', 'room-categories/thumbnails/ZFKLQSV5wfce7smLKStD2J0rRLVCiWlcZP0ISJxZ.jpg', 'active', '2026-06-08 22:25:21', '2026-07-19 03:36:16'),
(5, 'Phòng demo', 1000000.00, 2, 2, 40.00, 2, 'Phòng dùng demo và test', 'room-categories/thumbnails/VvhVWAMkA6v92AGJ3Az8SIAMw0Gt3QlbKacNoHFn.jpg', 'active', '2026-07-17 10:15:15', '2026-07-17 14:14:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_category_amenities`
--

CREATE TABLE `room_category_amenities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_category_id` bigint(20) UNSIGNED NOT NULL,
  `amenity_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_category_amenities`
--

INSERT INTO `room_category_amenities` (`id`, `room_category_id`, `amenity_id`, `created_at`, `updated_at`) VALUES
(1, 1, 2, NULL, NULL),
(2, 1, 3, NULL, NULL),
(3, 1, 1, NULL, NULL),
(4, 2, 2, NULL, NULL),
(5, 2, 3, NULL, NULL),
(6, 2, 1, NULL, NULL),
(7, 3, 5, NULL, NULL),
(8, 3, 4, NULL, NULL),
(9, 3, 2, NULL, NULL),
(10, 3, 3, NULL, NULL),
(11, 3, 1, NULL, NULL),
(12, 4, 5, NULL, NULL),
(13, 4, 4, NULL, NULL),
(14, 4, 2, NULL, NULL),
(15, 4, 3, NULL, NULL),
(16, 4, 1, NULL, NULL),
(17, 5, 4, NULL, NULL),
(18, 5, 2, NULL, NULL),
(19, 5, 3, NULL, NULL),
(20, 5, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_category_images`
--

CREATE TABLE `room_category_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_category_id` bigint(20) UNSIGNED NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_category_images`
--

INSERT INTO `room_category_images` (`id`, `room_category_id`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'room-categories/albums/mVBOqaTn5V6FShJ1Voa75bdEK2VD4rsPwOSsfuLM.jpg', '2026-06-08 22:22:03', '2026-06-08 22:22:03'),
(2, 1, 'room-categories/albums/olYvMVXg2HgaTmBz56LC782yW82PW13lLKo8C3vF.jpg', '2026-06-08 22:22:03', '2026-06-08 22:22:03'),
(3, 1, 'room-categories/albums/aIC7c7amGO2GED0brDMEKqr0eHYpyn2NtoYcBSN4.jpg', '2026-06-08 22:22:03', '2026-06-08 22:22:03'),
(4, 2, 'room-categories/albums/kHwhJJYmvUf5sjnyS9OpeF392rN4i5j3ETgQvUVR.jpg', '2026-06-08 22:23:06', '2026-06-08 22:23:06'),
(5, 2, 'room-categories/albums/lAlp0FfPbSDdhCBPSfrqdpgzBXdHl6J7Dm3hgR0S.jpg', '2026-06-08 22:23:06', '2026-06-08 22:23:06'),
(6, 2, 'room-categories/albums/UNtJFKCgn1TfZun5CDYY1y5wcJSGsv0Dsj45R356.jpg', '2026-06-08 22:23:06', '2026-06-08 22:23:06'),
(7, 3, 'room-categories/albums/FVlthY42pF28vpvksUnNe8BbqZ4NwOKCS9GthvxM.jpg', '2026-06-08 22:24:15', '2026-06-08 22:24:15'),
(8, 3, 'room-categories/albums/D41qnaH65xreYYUeM6RWsGJiWHBTfoguMjbV90at.jpg', '2026-06-08 22:24:15', '2026-06-08 22:24:15'),
(9, 3, 'room-categories/albums/OVqNauMwbFiWVCs7TU94OqCElMg8PMiFZtjrRriW.jpg', '2026-06-08 22:24:15', '2026-06-08 22:24:15'),
(10, 4, 'room-categories/albums/KQRMGLHFyfTrsuWQkvtXsWPwN15woZKc1a7MEcss.jpg', '2026-06-08 22:25:21', '2026-06-08 22:25:21'),
(11, 4, 'room-categories/albums/oB7SXNQUujZ1h0RhMfdWwoWDXs5FPscpKE1u3MPD.jpg', '2026-06-08 22:25:21', '2026-06-08 22:25:21'),
(12, 4, 'room-categories/albums/lLCKV5FxmftywUU2q1YqXwQzjXN0amWyqjDdpitq.jpg', '2026-06-08 22:25:21', '2026-06-08 22:25:21'),
(13, 4, 'room-categories/albums/ioPUTY4mVPdpW4FHqpS0Zqk05s7nXutRP84h7rRA.jpg', '2026-06-08 22:25:21', '2026-06-08 22:25:21'),
(14, 4, 'room-categories/albums/KVTHm71gAMgspX5hdeaMZctuvdaUA8bcsiIzRKPv.jpg', '2026-06-08 22:25:21', '2026-06-08 22:25:21'),
(15, 5, 'room-categories/albums/yo1IWwz5faa4TFfAcoMSY5t5s37ZYiW71GyduBgb.jpg', '2026-07-17 10:15:15', '2026-07-17 10:15:15'),
(16, 5, 'room-categories/albums/Y2iVmI7DfEP3EA2gGswBrCM4lgSajrE3WneEZRe8.jpg', '2026-07-17 10:15:15', '2026-07-17 10:15:15'),
(17, 5, 'room-categories/albums/irw6UgYueXEUOboTC37PbvrpftvMdvlooJqNkSb5.jpg', '2026-07-17 10:15:15', '2026-07-17 10:15:15'),
(18, 5, 'room-categories/albums/my6CvDwv7GYmTZUfWGt4v8Q6ckcvlIiBBE7L2CtV.jpg', '2026-07-17 10:15:15', '2026-07-17 10:15:15'),
(19, 5, 'room-categories/albums/3gpEZiZgXQGBFNgojmrYvbUJaD5DdBprwcvbPhgl.jpg', '2026-07-17 10:15:15', '2026-07-17 10:15:15'),
(20, 5, 'room-categories/albums/eNZwTxi6gVTAPopuvPnkZKItAdNRucYNhGRuZoHh.png', '2026-07-17 10:15:15', '2026-07-17 10:15:15'),
(21, 5, 'room-categories/albums/W0vDbZuqnrBWOz02r1RuEhyWea3e6BtivAG5lTon.jpg', '2026-07-17 14:14:50', '2026-07-17 14:14:50'),
(22, 5, 'room-categories/albums/DZF4sczOPVyKIGZjl54BB4m6t22qtjUeh9w8WQTX.jpg', '2026-07-17 14:14:50', '2026-07-17 14:14:50'),
(23, 5, 'room-categories/albums/OMtP0OLk2ZBqGcV1IC0S5fHEcqk1olWvgUYqtC9X.jpg', '2026-07-17 14:14:50', '2026-07-17 14:14:50'),
(24, 5, 'room-categories/albums/LfqhXb7nF0niRvJJPX7cw93PHBnp8JFjwz0Q1JUA.jpg', '2026-07-17 14:14:50', '2026-07-17 14:14:50'),
(25, 5, 'room-categories/albums/C8RwIPiTiQbHYYLZXxQgRc5zF6fDGAfPpBTZX9DY.jpg', '2026-07-17 14:14:50', '2026-07-17 14:14:50'),
(26, 5, 'room-categories/albums/D6H5TQHLuxdDSXALpfPCJnJd4OUY8kRmUYIt1npc.png', '2026-07-17 14:14:50', '2026-07-17 14:14:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_inspections`
--

CREATE TABLE `room_inspections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `inspected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `confirmed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','reported','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `workflow_stage` enum('housekeeping_report','guest_consultation','housekeeping_recheck','admin_approval','completed') NOT NULL DEFAULT 'housekeeping_report',
  `version` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `admin_acknowledged_version` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `admin_acknowledged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `admin_acknowledged_at` datetime DEFAULT NULL,
  `guest_consulted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_consulted_at` datetime DEFAULT NULL,
  `guest_consultation_note` text DEFAULT NULL,
  `last_update_summary` text DEFAULT NULL,
  `last_revision_at` datetime DEFAULT NULL,
  `has_damage` tinyint(1) NOT NULL DEFAULT 0,
  `damage_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`damage_items`)),
  `damage_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `minibar_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `approved_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `inspection_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `inspected_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_inspections`
--

INSERT INTO `room_inspections` (`id`, `booking_id`, `room_id`, `inspected_by`, `confirmed_by`, `status`, `workflow_stage`, `version`, `admin_acknowledged_version`, `admin_acknowledged_by`, `admin_acknowledged_at`, `guest_consulted_by`, `guest_consulted_at`, `guest_consultation_note`, `last_update_summary`, `last_revision_at`, `has_damage`, `damage_items`, `damage_total`, `minibar_total`, `approved_total`, `inspection_note`, `admin_note`, `inspected_at`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-19 09:59:56', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-19 02:59:41', '2026-07-19 02:59:56', '2026-07-19 02:59:31', '2026-07-19 02:59:56'),
(2, 4, 15, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-19 10:40:09', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-19 03:40:02', '2026-07-19 03:40:09', '2026-07-19 03:39:41', '2026-07-19 03:40:09'),
(3, 7, 1, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-19 19:39:50', 0, NULL, 0.00, 30000.00, 30000.00, NULL, NULL, '2026-07-19 12:39:39', '2026-07-19 12:39:50', '2026-07-19 12:39:22', '2026-07-19 12:39:50'),
(4, 8, 3, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-19 21:19:24', 0, NULL, 0.00, 20000.00, 20000.00, NULL, NULL, '2026-07-19 14:18:53', '2026-07-19 14:19:24', '2026-07-19 14:18:35', '2026-07-19 14:19:24'),
(5, 23, 1, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 17:57:23', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-20 10:56:49', '2026-07-20 10:57:23', '2026-07-20 10:56:33', '2026-07-20 10:57:23'),
(6, 23, 2, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 17:57:28', 0, NULL, 0.00, 20000.00, 20000.00, NULL, NULL, '2026-07-20 10:56:55', '2026-07-20 10:57:28', '2026-07-20 10:56:33', '2026-07-20 10:57:28'),
(7, 23, 11, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 17:57:35', 0, NULL, 0.00, 25000.00, 25000.00, NULL, NULL, '2026-07-20 10:57:04', '2026-07-20 10:57:35', '2026-07-20 10:56:33', '2026-07-20 10:57:35'),
(8, 23, 10, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 17:57:40', 1, NULL, 3000000.00, 0.00, 3000000.00, NULL, NULL, '2026-07-20 10:57:12', '2026-07-20 10:57:40', '2026-07-20 10:56:33', '2026-07-20 10:57:40'),
(9, 22, 3, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 17:59:42', 0, NULL, 0.00, 10000.00, 10000.00, NULL, NULL, '2026-07-20 10:59:25', '2026-07-20 10:59:42', '2026-07-20 10:59:14', '2026-07-20 10:59:42'),
(10, 26, 12, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 22:21:50', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-20 15:20:49', '2026-07-20 15:21:50', '2026-07-20 15:20:39', '2026-07-20 15:21:50'),
(11, 26, 7, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 22:21:59', 1, NULL, 150000.00, 50000.00, 200000.00, NULL, NULL, '2026-07-20 15:21:07', '2026-07-20 15:21:59', '2026-07-20 15:20:39', '2026-07-20 15:21:59'),
(12, 26, 13, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 22:22:06', 0, NULL, 0.00, 40000.00, 40000.00, NULL, NULL, '2026-07-20 15:21:21', '2026-07-20 15:22:06', '2026-07-20 15:20:39', '2026-07-20 15:22:06'),
(13, 26, 8, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 22:22:11', 1, NULL, 250000.00, 0.00, 250000.00, NULL, NULL, '2026-07-20 15:21:36', '2026-07-20 15:22:11', '2026-07-20 15:20:39', '2026-07-20 15:22:11'),
(14, 24, 1, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-20 22:26:04', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-20 15:25:53', '2026-07-20 15:26:04', '2026-07-20 15:25:35', '2026-07-20 15:26:04'),
(15, 30, 4, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-21 00:08:49', 1, NULL, 50000.00, 125000.00, 175000.00, NULL, NULL, '2026-07-20 17:08:37', '2026-07-20 17:08:49', '2026-07-20 17:08:14', '2026-07-20 17:08:49'),
(16, 31, 4, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-21 00:44:14', 0, NULL, 0.00, 40000.00, 40000.00, NULL, NULL, '2026-07-20 17:43:46', '2026-07-20 17:44:14', '2026-07-20 17:43:33', '2026-07-20 17:44:14'),
(17, 32, 1, 4, 4, 'confirmed', 'completed', 1, 1, NULL, NULL, NULL, NULL, NULL, 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', '2026-07-21 01:16:22', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-20 18:16:14', '2026-07-20 18:16:22', '2026-07-20 18:16:00', '2026-07-20 18:16:22'),
(19, 29, 9, 4, 4, 'confirmed', 'completed', 8, 7, 4, '2026-07-21 03:59:46', 4, '2026-07-21 03:58:20', NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 401. Minibar/đồ dùng được duyệt: 50.000đ; hư hại/mất đồ được duyệt: 50.000đ; tổng cộng: 100.000đ.', '2026-07-21 03:59:53', 1, NULL, 50000.00, 50000.00, 100000.00, NULL, NULL, '2026-07-20 20:09:24', '2026-07-20 20:59:53', '2026-07-20 20:08:59', '2026-07-20 20:59:53'),
(20, 33, 11, 4, 4, 'confirmed', 'completed', 6, 5, 4, '2026-07-21 07:44:18', 4, '2026-07-21 07:43:08', NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 403. Minibar/đồ dùng được duyệt: 7.000đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 7.000đ.', '2026-07-21 07:44:22', 0, NULL, 0.00, 7000.00, 7000.00, NULL, NULL, '2026-07-21 00:40:05', '2026-07-21 00:44:22', '2026-07-21 00:39:18', '2026-07-21 00:44:22'),
(21, 33, 10, 4, 4, 'confirmed', 'completed', 6, 5, 4, '2026-07-21 07:45:36', 4, '2026-07-21 07:42:44', NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 402. Minibar/đồ dùng được duyệt: 60.000đ; hư hại/mất đồ được duyệt: 100.000đ; tổng cộng: 160.000đ.', '2026-07-21 07:45:49', 1, NULL, 100000.00, 60000.00, 160000.00, NULL, NULL, '2026-07-21 00:39:47', '2026-07-21 00:45:49', '2026-07-21 00:39:18', '2026-07-21 00:45:49'),
(24, 36, 11, 4, 4, 'confirmed', 'completed', 2, 1, 4, '2026-07-21 10:36:52', NULL, NULL, NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 403. Minibar/đồ dùng được duyệt: 0đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 0đ.', '2026-07-21 10:36:57', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-21 03:32:57', '2026-07-21 03:36:57', '2026-07-21 03:32:19', '2026-07-21 03:36:57'),
(25, 36, 10, 4, 4, 'confirmed', 'completed', 4, 3, 4, '2026-07-21 10:37:14', 4, '2026-07-21 10:36:18', NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 402. Minibar/đồ dùng được duyệt: 30.000đ; hư hại/mất đồ được duyệt: 50.000đ; tổng cộng: 80.000đ.', '2026-07-21 10:37:19', 1, NULL, 50000.00, 30000.00, 80000.00, NULL, NULL, '2026-07-21 03:32:51', '2026-07-21 03:37:19', '2026-07-21 03:32:19', '2026-07-21 03:37:19'),
(26, 36, 8, 4, 4, 'confirmed', 'completed', 2, 1, 4, '2026-07-21 10:37:25', NULL, NULL, NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 302. Minibar/đồ dùng được duyệt: 0đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 0đ.', '2026-07-21 10:37:31', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-07-21 03:32:36', '2026-07-21 03:37:31', '2026-07-21 03:32:19', '2026-07-21 03:37:31'),
(27, 41, 1, 7, 7, 'confirmed', 'completed', 6, 5, 7, '2026-07-21 14:38:38', 7, '2026-07-21 14:38:00', NULL, 'Admin xác nhận cuối kết quả kiểm tra phòng 101. Minibar/đồ dùng được duyệt: 50.000đ; hư hại/mất đồ được duyệt: 100.000đ; tổng cộng: 150.000đ.', '2026-07-21 14:38:43', 1, NULL, 100000.00, 50000.00, 150000.00, NULL, NULL, '2026-07-21 07:36:25', '2026-07-21 07:38:43', '2026-07-21 07:35:33', '2026-07-21 07:38:43'),
(28, 46, 1, 6, 6, 'confirmed', 'completed', 4, 0, NULL, NULL, 5, '2026-08-22 05:32:45', NULL, 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 50.000đ; tổng cộng: 50.000đ.', '2026-08-22 05:34:52', 1, NULL, 50000.00, 0.00, 50000.00, NULL, NULL, '2026-08-21 20:34:04', '2026-08-21 22:34:52', '2026-08-21 20:33:34', '2026-08-21 22:34:52'),
(29, 46, 15, 6, 6, 'confirmed', 'completed', 6, 0, NULL, NULL, 5, '2026-08-22 05:34:07', NULL, 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-22 05:34:38', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-21 20:34:21', '2026-08-21 22:34:38', '2026-08-21 20:33:34', '2026-08-21 22:34:38'),
(30, 47, 1, 6, 6, 'confirmed', 'completed', 4, 0, NULL, NULL, 5, '2026-08-22 07:34:01', NULL, 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-22 07:34:57', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-22 00:32:20', '2026-08-22 00:34:57', '2026-08-22 00:31:24', '2026-08-22 00:34:57'),
(31, 48, 15, 6, 6, 'confirmed', 'completed', 4, 0, NULL, NULL, 5, '2026-08-22 08:47:58', NULL, 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-22 08:48:18', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-22 01:47:36', '2026-08-22 01:48:18', '2026-08-22 01:47:15', '2026-08-22 01:48:18'),
(32, 49, 15, 4, 4, 'confirmed', 'completed', 2, 0, NULL, NULL, NULL, NULL, NULL, 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-24 00:04:30', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-23 17:04:30', '2026-08-23 17:04:30', '2026-08-22 01:53:51', '2026-08-23 17:04:30'),
(33, 50, 13, 6, 6, 'confirmed', 'completed', 2, 0, NULL, NULL, NULL, NULL, NULL, 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-24 00:07:45', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-23 17:07:45', '2026-08-23 17:07:45', '2026-08-23 17:07:31', '2026-08-23 17:07:45'),
(34, 50, 11, 6, 6, 'confirmed', 'completed', 2, 0, NULL, NULL, NULL, NULL, NULL, 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-24 00:07:58', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-23 17:07:58', '2026-08-23 17:07:58', '2026-08-23 17:07:31', '2026-08-23 17:07:58'),
(35, 52, 13, 6, 6, 'confirmed', 'completed', 6, 0, NULL, NULL, 5, '2026-08-24 10:33:06', NULL, 'Buồng phòng kiểm tra lại và kết quả đã khớp với số lượng khách xác nhận; phiếu được hoàn tất. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', '2026-08-24 10:33:18', 0, NULL, 0.00, 0.00, 0.00, NULL, NULL, '2026-08-24 03:32:10', '2026-08-24 03:33:18', '2026-08-24 03:31:46', '2026-08-24 03:33:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_inspection_items`
--

CREATE TABLE `room_inspection_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_inspection_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('minibar','damage_fee') NOT NULL DEFAULT 'damage_fee',
  `name` varchar(150) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `original_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `guest_response` enum('pending','accepted','disputed') NOT NULL DEFAULT 'pending',
  `guest_response_note` text DEFAULT NULL,
  `guest_claimed_quantity` int(11) DEFAULT NULL,
  `guest_responded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_responded_at` datetime DEFAULT NULL,
  `recheck_decision` enum('not_required','pending','keep_charge','remove_charge') NOT NULL DEFAULT 'not_required',
  `recheck_note` text DEFAULT NULL,
  `rechecked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rechecked_at` datetime DEFAULT NULL,
  `detection_source` varchar(30) NOT NULL DEFAULT 'initial',
  `detected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `detected_at` datetime DEFAULT NULL,
  `detection_version` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_inspection_items`
--

INSERT INTO `room_inspection_items` (`id`, `room_inspection_id`, `service_id`, `type`, `name`, `unit`, `price`, `quantity`, `total`, `original_total`, `status`, `admin_note`, `guest_response`, `guest_response_note`, `guest_claimed_quantity`, `guest_responded_by`, `guest_responded_at`, `recheck_decision`, `recheck_note`, `rechecked_by`, `rechecked_at`, `detection_source`, `detected_by`, `detected_at`, `detection_version`, `created_at`, `updated_at`) VALUES
(1, 3, 5, 'minibar', 'Bia', 'lon', 10000.00, 3, 30000.00, 30000.00, 'approved', NULL, 'accepted', NULL, 3, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-19 19:39:39', 1, '2026-07-19 12:39:39', '2026-07-19 12:39:50'),
(2, 4, 5, 'minibar', 'Bia', 'lon', 10000.00, 2, 20000.00, 20000.00, 'approved', NULL, 'accepted', NULL, 2, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-19 21:18:53', 1, '2026-07-19 14:18:53', '2026-07-19 14:19:24'),
(3, 6, 5, 'minibar', 'Bia', 'lon', 10000.00, 2, 20000.00, 20000.00, 'approved', NULL, 'accepted', NULL, 2, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 17:56:55', 1, '2026-07-20 10:56:55', '2026-07-20 10:57:28'),
(4, 7, 18, 'minibar', 'Coca Cola', 'lon', 25000.00, 1, 25000.00, 25000.00, 'approved', NULL, 'accepted', NULL, 1, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 17:57:04', 1, '2026-07-20 10:57:04', '2026-07-20 10:57:35'),
(5, 8, 7, 'damage_fee', 'Hỏng TV', 'lần', 3000000.00, 1, 3000000.00, 3000000.00, 'approved', NULL, 'accepted', NULL, 1, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 17:57:12', 1, '2026-07-20 10:57:12', '2026-07-20 10:57:40'),
(6, 9, 5, 'minibar', 'Bia', 'lon', 10000.00, 1, 10000.00, 10000.00, 'approved', NULL, 'accepted', NULL, 1, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 17:59:25', 1, '2026-07-20 10:59:25', '2026-07-20 10:59:42'),
(7, 11, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 3, 150000.00, 150000.00, 'approved', NULL, 'accepted', NULL, 3, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 22:21:07', 1, '2026-07-20 15:21:07', '2026-07-20 15:21:59'),
(8, 11, 18, 'minibar', 'Coca Cola', 'lon', 25000.00, 2, 50000.00, 50000.00, 'approved', NULL, 'accepted', NULL, 2, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 22:21:07', 1, '2026-07-20 15:21:07', '2026-07-20 15:21:59'),
(9, 12, 5, 'minibar', 'Bia', 'lon', 10000.00, 4, 40000.00, 40000.00, 'approved', NULL, 'accepted', NULL, 4, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 22:21:21', 1, '2026-07-20 15:21:21', '2026-07-20 15:22:06'),
(10, 13, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 5, 250000.00, 250000.00, 'approved', NULL, 'accepted', NULL, 5, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-20 22:21:36', 1, '2026-07-20 15:21:36', '2026-07-20 15:22:11'),
(12, 15, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 50000.00, 'approved', NULL, 'accepted', NULL, 1, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-21 00:08:37', 1, '2026-07-20 17:08:37', '2026-07-20 17:08:49'),
(13, 15, 18, 'minibar', 'Coca Cola', 'lon', 25000.00, 5, 125000.00, 125000.00, 'approved', NULL, 'accepted', NULL, 5, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-21 00:08:37', 1, '2026-07-20 17:08:37', '2026-07-20 17:08:49'),
(14, 16, 5, 'minibar', 'Bia', 'lon', 10000.00, 4, 40000.00, 40000.00, 'approved', NULL, 'accepted', NULL, 4, NULL, NULL, 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-21 00:43:46', 1, '2026-07-20 17:43:46', '2026-07-20 17:44:14'),
(18, 19, 7, 'damage_fee', 'Hỏng TV', 'lần', 3000000.00, 0, 0.00, 3000000.00, 'rejected', 'Số lượng xác minh cuối bằng 0 nên không cộng phí. tv hoạt động rồi', 'accepted', NULL, 0, 4, '2026-07-21 03:58:20', 'remove_charge', 'tv hoạt động rồi', 4, '2026-07-21 03:36:07', 'initial', 4, '2026-07-21 03:09:24', 1, '2026-07-20 20:09:24', '2026-07-20 20:59:53'),
(19, 19, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 150000.00, 'approved', NULL, 'accepted', 'kahcsh bảo 1', 1, 4, '2026-07-21 03:58:20', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 4, '2026-07-21 03:58:50', 'initial', 4, '2026-07-21 03:09:24', 1, '2026-07-20 20:09:24', '2026-07-20 20:59:53'),
(20, 19, 5, 'minibar', 'Bia', 'lon', 10000.00, 5, 50000.00, 50000.00, 'approved', NULL, 'accepted', NULL, 5, 4, '2026-07-21 03:58:20', 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-21 03:09:24', 1, '2026-07-20 20:09:24', '2026-07-20 20:59:53'),
(21, 21, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 2, 100000.00, 150000.00, 'approved', NULL, 'accepted', 'chỉ vỡ 2', 2, 4, '2026-07-21 07:42:44', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 4, '2026-07-21 07:43:54', 'initial', 4, '2026-07-21 07:39:47', 1, '2026-07-21 00:39:47', '2026-07-21 00:45:49'),
(22, 21, 20, 'minibar', 'Snack', 'gói', 20000.00, 3, 60000.00, 60000.00, 'approved', NULL, 'accepted', 'ăn có 3 thôi', 3, 4, '2026-07-21 07:42:44', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 4, '2026-07-21 07:43:54', 'initial', 4, '2026-07-21 07:39:47', 1, '2026-07-21 00:39:47', '2026-07-21 00:45:49'),
(23, 20, 4, 'minibar', 'Nước suối', 'chai', 7000.00, 1, 7000.00, 14000.00, 'approved', NULL, 'accepted', 'dùng 1 thôi', 1, 4, '2026-07-21 07:43:08', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 4, '2026-07-21 07:43:26', 'initial', 4, '2026-07-21 07:40:05', 1, '2026-07-21 00:40:05', '2026-07-21 00:44:22'),
(24, 25, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 100000.00, 'approved', NULL, 'accepted', '1 ly thôi', 1, 4, '2026-07-21 10:36:18', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 4, '2026-07-21 10:36:28', 'initial', 4, '2026-07-21 10:32:51', 1, '2026-07-21 03:32:51', '2026-07-21 03:37:19'),
(25, 25, 5, 'minibar', 'Bia', 'lon', 10000.00, 3, 30000.00, 30000.00, 'approved', NULL, 'accepted', NULL, 3, 4, '2026-07-21 10:36:18', 'not_required', NULL, NULL, NULL, 'initial', 4, '2026-07-21 10:32:51', 1, '2026-07-21 03:32:51', '2026-07-21 03:37:19'),
(26, 27, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 2, 100000.00, 200000.00, 'approved', NULL, 'accepted', 'bosos kêu 2 thoi', 2, 7, '2026-07-21 14:38:00', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 7, '2026-07-21 14:38:18', 'initial', 7, '2026-07-21 14:36:25', 1, '2026-07-21 07:36:25', '2026-07-21 07:38:43'),
(27, 27, 5, 'minibar', 'Bia', 'lon', 10000.00, 5, 50000.00, 50000.00, 'approved', NULL, 'accepted', NULL, 5, 7, '2026-07-21 14:38:00', 'not_required', NULL, NULL, NULL, 'initial', 7, '2026-07-21 14:36:25', 1, '2026-07-21 07:36:25', '2026-07-21 07:38:43'),
(28, 28, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 100000.00, 'approved', NULL, 'accepted', 'vỡ 1 thôi', 1, 5, '2026-08-22 05:32:45', 'keep_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 6, '2026-08-22 05:34:52', 'initial', 6, '2026-08-22 03:34:04', 1, '2026-08-21 20:34:04', '2026-08-21 22:34:52'),
(29, 29, 5, 'minibar', 'Bia', 'lon', 10000.00, 0, 0.00, 30000.00, 'approved', NULL, 'accepted', 'bia tự mang', 0, 5, '2026-08-22 05:34:07', 'remove_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 6, '2026-08-22 05:34:38', 'supplemental', 6, '2026-08-22 05:33:42', 3, '2026-08-21 22:33:42', '2026-08-21 22:34:38'),
(30, 30, 5, 'minibar', 'Bia', 'lon', 10000.00, 0, 0.00, 30000.00, 'approved', NULL, 'accepted', 'tôi mang theo bia', 0, 5, '2026-08-22 07:34:01', 'remove_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 6, '2026-08-22 07:34:57', 'initial', 6, '2026-08-22 07:32:20', 1, '2026-08-22 00:32:20', '2026-08-22 00:34:57'),
(31, 31, 4, 'minibar', 'Nước suối', 'chai', 7000.00, 0, 0.00, 14000.00, 'approved', NULL, 'accepted', 'nước mang theo', 0, 5, '2026-08-22 08:47:58', 'remove_charge', 'ok', 6, '2026-08-22 08:48:18', 'initial', 6, '2026-08-22 08:47:36', 1, '2026-08-22 01:47:36', '2026-08-22 01:48:18'),
(32, 35, 5, 'minibar', 'Bia', 'lon', 10000.00, 0, 0.00, 20000.00, 'approved', NULL, 'accepted', 'ccftvgybhnji', 0, 5, '2026-08-24 10:33:06', 'remove_charge', 'Kết quả xác minh khớp với số lượng khách đã xác nhận.', 6, '2026-08-24 10:33:18', 'supplemental', 6, '2026-08-24 10:32:49', 3, '2026-08-24 03:32:49', '2026-08-24 03:33:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_inspection_revisions`
--

CREATE TABLE `room_inspection_revisions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_inspection_id` bigint(20) UNSIGNED NOT NULL,
  `room_inspection_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `summary` varchar(1000) NOT NULL,
  `before_data` longtext DEFAULT NULL,
  `after_data` longtext DEFAULT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_inspection_revisions`
--

INSERT INTO `room_inspection_revisions` (`id`, `room_inspection_id`, `room_inspection_item_id`, `version`, `event_type`, `summary`, `before_data`, `after_data`, `changed_by`, `created_at`) VALUES
(1, 1, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-19 09:59:56'),
(2, 2, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-19 10:40:09'),
(3, 3, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-19 19:39:50'),
(4, 4, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-19 21:19:24'),
(5, 5, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 17:57:23'),
(6, 6, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 17:57:28'),
(7, 7, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 17:57:35'),
(8, 8, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 17:57:40'),
(9, 9, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 17:59:42'),
(10, 10, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 22:21:50'),
(11, 11, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 22:21:59'),
(12, 12, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 22:22:06'),
(13, 13, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 22:22:11'),
(14, 14, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-20 22:26:04'),
(15, 15, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-21 00:08:49'),
(16, 16, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-21 00:44:14'),
(17, 17, NULL, 1, 'legacy_import', 'Phiếu cũ đã được admin duyệt trước khi cài luồng phiên bản.', NULL, NULL, 4, '2026-07-21 01:16:22'),
(39, 19, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 401: minibar/đồ dùng: Bia x5 = 50.000đ — 50.000đ. hư hại/mất đồ: Hỏng TV x1 = 3.000.000đ; Vỡ ly thủy tinh x3 = 150.000đ — 3.150.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":1,\"total\":3000000,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null},{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null},{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}],\"workflow_stage\":\"guest_consultation\"}', 4, '2026-07-21 03:09:24'),
(40, 19, 18, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Hỏng TV” chỉ có 0 lần.', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":1,\"total\":3000000,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":1,\"total\":3000000,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:34:57'),
(41, 19, 19, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 1 cái.', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:34:57'),
(42, 19, 20, 2, 'guest_consultation', 'Khách đồng ý hạng mục “Bia” với số lượng hiện tại là 5 lon.', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:34:57'),
(43, 19, 18, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Hỏng TV”: khách xác nhận 0 lần, buồng phòng xác minh 0 lần (khớp đúng số lượng khách xác nhận), thành tiền 0đ. tv hoạt động rồi', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":1,\"total\":3000000,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":null}', 4, '2026-07-21 03:36:07'),
(44, 19, 19, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 1 cái, buồng phòng xác minh 2 cái (cao hơn số lượng khách xác nhận 1 cái), thành tiền 100.000đ. thấy maxh vỡ và thiếu 2 ly', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"th\\u00e2\\u0301y maxh v\\u01a1\\u0303 va\\u0300 thi\\u00ea\\u0301u 2 ly\",\"admin_note\":null}', 4, '2026-07-21 03:36:07'),
(45, 19, 18, 4, 'guest_consultation', 'Khách đồng ý hạng mục “Hỏng TV” với số lượng hiện tại là 0 lần.', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":null}', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":null}', 4, '2026-07-21 03:37:14'),
(46, 19, 19, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 1 cái.', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"th\\u00e2\\u0301y maxh v\\u01a1\\u0303 va\\u0300 thi\\u00ea\\u0301u 2 ly\",\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:37:14'),
(47, 19, 20, 4, 'guest_consultation', 'Khách đồng ý hạng mục “Bia” với số lượng hiện tại là 5 lon.', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:37:14'),
(48, 19, 19, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 1 cái, buồng phòng xác minh 2 cái (cao hơn số lượng khách xác nhận 1 cái), thành tiền 100.000đ. ègnfd', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"e\\u0300gnfd\",\"admin_note\":null}', 4, '2026-07-21 03:40:14'),
(49, 19, 18, 6, 'guest_consultation', 'Khách đồng ý hạng mục “Hỏng TV” với số lượng hiện tại là 0 lần.', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":null}', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":null}', 4, '2026-07-21 03:58:20'),
(50, 19, 19, 6, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 1 cái. Ghi chú: kahcsh bảo 1', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"e\\u0300gnfd\",\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"kahcsh ba\\u0309o 1\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:58:20'),
(51, 19, 20, 6, 'guest_consultation', 'Khách đồng ý hạng mục “Bia” với số lượng hiện tại là 5 lon.', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:58:20'),
(52, 19, 19, 7, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 1 cái, xác minh thực tế 1 cái (khớp đúng số lượng khách xác nhận), thành tiền 50.000đ.', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"kahcsh ba\\u0309o 1\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"kahcsh ba\\u0309o 1\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 03:58:50'),
(53, 19, 18, 8, 'admin_approval', 'Admin không duyệt “Hỏng TV”: Số lượng xác minh cuối bằng 0 nên không cộng phí. tv hoạt động rồi', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":null}', '{\"id\":18,\"name\":\"Ho\\u0309ng TV\",\"type\":\"damage_fee\",\"unit\":\"l\\u1ea7n\",\"price\":3000000,\"quantity\":0,\"total\":0,\"original_total\":3000000,\"status\":\"rejected\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\",\"admin_note\":\"S\\u1ed1 l\\u01b0\\u1ee3ng x\\u00e1c minh cu\\u1ed1i b\\u1eb1ng 0 n\\u00ean kh\\u00f4ng c\\u1ed9ng ph\\u00ed. tv hoa\\u0323t \\u0111\\u00f4\\u0323ng r\\u00f4\\u0300i\"}', 4, '2026-07-21 03:59:53'),
(54, 19, 19, 8, 'admin_approval', 'Admin duyệt “Vỡ ly thủy tinh” với số tiền 50.000đ.', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"kahcsh ba\\u0309o 1\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', '{\"id\":19,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":150000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"kahcsh ba\\u0309o 1\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 03:59:53'),
(55, 19, 20, 8, 'admin_approval', 'Admin duyệt “Bia” với số tiền 50.000đ.', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":20,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 03:59:53'),
(56, 21, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 402: minibar/đồ dùng: Snack x3 = 60.000đ — 60.000đ. hư hại/mất đồ: Vỡ ly thủy tinh x3 = 150.000đ — 150.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null},{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}],\"workflow_stage\":\"guest_consultation\"}', 4, '2026-07-21 07:39:47'),
(57, 20, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 403: minibar/đồ dùng: Nước suối x2 = 14.000đ — 14.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}],\"workflow_stage\":\"guest_consultation\"}', 4, '2026-07-21 07:40:05'),
(58, 21, 21, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 2 cái. Ghi chú: vỡ có 2 thôi', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"v\\u01a1\\u0303 co\\u0301 2 th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 07:40:44'),
(59, 21, 22, 2, 'guest_consultation', 'Khách đồng ý hạng mục “Snack” với số lượng hiện tại là 3 gói.', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 07:40:44'),
(60, 20, 23, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Nước suối” chỉ có 1 chai. Ghi chú: dùng 1 mà', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"du\\u0300ng 1 ma\\u0300\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 07:41:09'),
(61, 20, 23, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Nước suối”: khách xác nhận 1 chai, xác minh thực tế 2 chai (cao hơn ý kiến khách 1 chai), thành tiền 14.000đ. 2 vỏ chai cơ mà', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"du\\u0300ng 1 ma\\u0300\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":\"du\\u0300ng 1 ma\\u0300\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"2 vo\\u0309 chai c\\u01a1 ma\\u0300\",\"admin_note\":null}', 4, '2026-07-21 07:41:39'),
(62, 21, 21, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 2 cái, xác minh thực tế 3 cái (cao hơn ý kiến khách 1 cái), thành tiền 150.000đ. thấy thiếu 3 ly', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"v\\u01a1\\u0303 co\\u0301 2 th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":\"v\\u01a1\\u0303 co\\u0301 2 th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"th\\u00e2\\u0301y thi\\u00ea\\u0301u 3 ly\",\"admin_note\":null}', 4, '2026-07-21 07:42:13'),
(63, 21, 22, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Snack”: khách xác nhận 3 gói, xác minh thực tế 4 gói (cao hơn ý kiến khách 1 gói), thành tiền 80.000đ. 4 vỏ snack', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":4,\"total\":80000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"4 vo\\u0309 snack\",\"admin_note\":null}', 4, '2026-07-21 07:42:13'),
(64, 21, 21, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 2 cái. Ghi chú: chỉ vỡ 2', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":\"v\\u01a1\\u0303 co\\u0301 2 th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"th\\u00e2\\u0301y thi\\u00ea\\u0301u 3 ly\",\"admin_note\":null}', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"chi\\u0309 v\\u01a1\\u0303 2\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 07:42:44'),
(65, 21, 22, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Snack” chỉ có 3 gói. Ghi chú: ăn có 3 thôi', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":4,\"total\":80000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"4 vo\\u0309 snack\",\"admin_note\":null}', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":4,\"total\":80000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"\\u0103n co\\u0301 3 th\\u00f4i\",\"guest_claimed_quantity\":3,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 07:42:44'),
(66, 20, 23, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Nước suối” chỉ có 1 chai. Ghi chú: dùng 1 thôi', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":\"du\\u0300ng 1 ma\\u0300\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"2 vo\\u0309 chai c\\u01a1 ma\\u0300\",\"admin_note\":null}', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"du\\u0300ng 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 07:43:08'),
(67, 20, 23, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Nước suối”: khách xác nhận 1 chai, xác minh thực tế 1 chai (khớp đúng số lượng khách xác nhận), thành tiền 7.000đ.', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"du\\u0300ng 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":1,\"total\":7000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"du\\u0300ng 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 07:43:26'),
(68, 21, 21, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 2 cái, xác minh thực tế 2 cái (khớp đúng số lượng khách xác nhận), thành tiền 100.000đ.', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":3,\"total\":150000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"chi\\u0309 v\\u01a1\\u0303 2\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"chi\\u0309 v\\u01a1\\u0303 2\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 07:43:54'),
(69, 21, 22, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Snack”: khách xác nhận 3 gói, xác minh thực tế 3 gói (khớp đúng số lượng khách xác nhận), thành tiền 60.000đ.', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":4,\"total\":80000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"\\u0103n co\\u0301 3 th\\u00f4i\",\"guest_claimed_quantity\":3,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"\\u0103n co\\u0301 3 th\\u00f4i\",\"guest_claimed_quantity\":3,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 07:43:54'),
(70, 20, 23, 6, 'admin_approval', 'Admin duyệt “Nước suối” với số tiền 7.000đ.', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":1,\"total\":7000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"du\\u0300ng 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', '{\"id\":23,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":1,\"total\":7000,\"original_total\":14000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"du\\u0300ng 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 07:44:22'),
(71, 21, 21, 6, 'admin_approval', 'Admin duyệt “Vỡ ly thủy tinh” với số tiền 100.000đ.', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"chi\\u0309 v\\u01a1\\u0303 2\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', '{\"id\":21,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":150000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"chi\\u0309 v\\u01a1\\u0303 2\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 07:45:49'),
(72, 21, 22, 6, 'admin_approval', 'Admin duyệt “Snack” với số tiền 60.000đ.', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"\\u0103n co\\u0301 3 th\\u00f4i\",\"guest_claimed_quantity\":3,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', '{\"id\":22,\"name\":\"Snack\",\"type\":\"minibar\",\"unit\":\"g\\u00f3i\",\"price\":20000,\"quantity\":3,\"total\":60000,\"original_total\":60000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"\\u0103n co\\u0301 3 th\\u00f4i\",\"guest_claimed_quantity\":3,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 07:45:49'),
(73, 26, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 302: không phát sinh minibar, mất đồ hoặc hư hại. Không có khoản phí; chờ admin xác nhận.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"admin_approval\"}', 4, '2026-07-21 10:32:36'),
(74, 25, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 402: minibar/đồ dùng: Bia x3 = 30.000đ — 30.000đ. hư hại/mất đồ: Vỡ ly thủy tinh x2 = 100.000đ — 100.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null},{\"id\":25,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}],\"workflow_stage\":\"guest_consultation\"}', 4, '2026-07-21 10:32:51'),
(75, 24, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 403: không phát sinh minibar, mất đồ hoặc hư hại. Không có khoản phí; chờ admin xác nhận.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"admin_approval\"}', 4, '2026-07-21 10:32:57'),
(76, 25, 24, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 1 cái. Ghi chú: 1 ly thôi', '{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"1 ly th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 10:36:18'),
(77, 25, 25, 2, 'guest_consultation', 'Khách đồng ý hạng mục “Bia” với số lượng hiện tại là 3 lon.', '{\"id\":25,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":25,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 10:36:18'),
(78, 25, 24, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 1 cái, xác minh thực tế 1 cái (khớp đúng số lượng khách xác nhận), thành tiền 50.000đ.', '{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"1 ly th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"1 ly th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 10:36:28'),
(79, 24, NULL, 2, 'admin_approval', 'Admin xác nhận cuối kết quả kiểm tra phòng 403. Minibar/đồ dùng được duyệt: 0đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 0đ.', NULL, NULL, 4, '2026-07-21 10:36:57'),
(80, 25, 24, 4, 'admin_approval', 'Admin duyệt “Vỡ ly thủy tinh” với số tiền 50.000đ.', '{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"1 ly th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', '{\"id\":24,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":100000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"1 ly th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 4, '2026-07-21 10:37:19'),
(81, 25, 25, 4, 'admin_approval', 'Admin duyệt “Bia” với số tiền 30.000đ.', '{\"id\":25,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":25,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":3,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 4, '2026-07-21 10:37:19'),
(82, 26, NULL, 2, 'admin_approval', 'Admin xác nhận cuối kết quả kiểm tra phòng 302. Minibar/đồ dùng được duyệt: 0đ; hư hại/mất đồ được duyệt: 0đ; tổng cộng: 0đ.', NULL, NULL, 4, '2026-07-21 10:37:31'),
(83, 27, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 101: minibar/đồ dùng: Bia x5 = 50.000đ — 50.000đ. hư hại/mất đồ: Vỡ ly thủy tinh x4 = 200.000đ — 200.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null},{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}],\"workflow_stage\":\"guest_consultation\"}', 7, '2026-07-21 14:36:25'),
(84, 27, 26, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 2 cái. Ghi chú: vỡ 2 ly thôi', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"v\\u01a1\\u0303 2 ly th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 7, '2026-07-21 14:37:00'),
(85, 27, 27, 2, 'guest_consultation', 'Khách đồng ý hạng mục “Bia” với số lượng hiện tại là 5 lon.', '{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 7, '2026-07-21 14:37:00'),
(86, 27, 26, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 2 cái, xác minh thực tế 4 cái (cao hơn ý kiến khách 2 cái), thành tiền 200.000đ. cfvgbhvjrv', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"v\\u01a1\\u0303 2 ly th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":\"v\\u01a1\\u0303 2 ly th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"cfvgbhvjrv\",\"admin_note\":null}', 7, '2026-07-21 14:37:36'),
(87, 27, 26, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 2 cái. Ghi chú: bosos kêu 2 thoi', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":\"v\\u01a1\\u0303 2 ly th\\u00f4i\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"cfvgbhvjrv\",\"admin_note\":null}', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"bosos k\\u00eau 2 thoi\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', 7, '2026-07-21 14:38:00'),
(88, 27, 27, 4, 'guest_consultation', 'Khách đồng ý hạng mục “Bia” với số lượng hiện tại là 5 lon.', '{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 7, '2026-07-21 14:38:00'),
(89, 27, 26, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 2 cái, xác minh thực tế 2 cái (khớp đúng số lượng khách xác nhận), thành tiền 100.000đ.', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":4,\"total\":200000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"bosos k\\u00eau 2 thoi\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"bosos k\\u00eau 2 thoi\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 7, '2026-07-21 14:38:18');
INSERT INTO `room_inspection_revisions` (`id`, `room_inspection_id`, `room_inspection_item_id`, `version`, `event_type`, `summary`, `before_data`, `after_data`, `changed_by`, `created_at`) VALUES
(90, 27, 26, 6, 'admin_approval', 'Admin duyệt “Vỡ ly thủy tinh” với số tiền 100.000đ.', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":200000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"bosos k\\u00eau 2 thoi\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', '{\"id\":26,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":200000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"bosos k\\u00eau 2 thoi\",\"guest_claimed_quantity\":2,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null}', 7, '2026-07-21 14:38:43'),
(91, 27, 27, 6, 'admin_approval', 'Admin duyệt “Bia” với số tiền 50.000đ.', '{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', '{\"id\":27,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":5,\"total\":50000,\"original_total\":50000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":null,\"guest_claimed_quantity\":5,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}', 7, '2026-07-21 14:38:43'),
(92, 28, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 101: hư hại/mất đồ: Vỡ ly thủy tinh x2 = 100.000đ — 100.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null}],\"workflow_stage\":\"guest_consultation\"}', 6, '2026-08-22 03:34:04'),
(93, 29, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 501: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"completed\"}', 6, '2026-08-22 03:34:21'),
(94, 29, NULL, 2, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', NULL, NULL, 6, '2026-08-22 03:34:21'),
(95, 28, 28, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Vỡ ly thủy tinh” chỉ có 1 cái. Ghi chú: vỡ 1 thôi', '{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T03:34:04+07:00\",\"detection_version\":1}', '{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"v\\u01a1\\u0303 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T03:34:04+07:00\",\"detection_version\":1}', 5, '2026-08-22 05:32:45'),
(96, 29, 29, 3, 'inspection_supplemental_detected', 'Phát hiện bổ sung “Bia” x3 sau lần kiểm tra trước: 30.000đ. thấy thgeem vỏ bia', NULL, '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', 6, '2026-08-22 05:33:43'),
(97, 29, 29, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Bia” chỉ có 0 lon. Ghi chú: bia tự mang', '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"bia t\\u01b0\\u0323 mang\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', 5, '2026-08-22 05:34:07'),
(98, 29, 29, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Bia”: khách xác nhận 0 lon, xác minh thực tế 0 lon (khớp đúng số lượng khách xác nhận), thành tiền 0đ.', '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"bia t\\u01b0\\u0323 mang\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"bia t\\u01b0\\u0323 mang\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', 6, '2026-08-22 05:34:38'),
(99, 29, 29, 6, 'inspection_completed', 'Chốt hạng mục “Bia” sau khi kết quả buồng phòng và ý kiến khách đã thống nhất: 0đ.', '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"bia t\\u01b0\\u0323 mang\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', '{\"id\":29,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":30000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"bia t\\u01b0\\u0323 mang\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-22T05:33:42+07:00\",\"detection_version\":3}', 6, '2026-08-22 05:34:38'),
(100, 28, 28, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Vỡ ly thủy tinh”: khách xác nhận 1 cái, xác minh thực tế 1 cái (khớp đúng số lượng khách xác nhận), thành tiền 50.000đ.', '{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":2,\"total\":100000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"v\\u01a1\\u0303 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T03:34:04+07:00\",\"detection_version\":1}', '{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"v\\u01a1\\u0303 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T03:34:04+07:00\",\"detection_version\":1}', 6, '2026-08-22 05:34:52'),
(101, 28, 28, 4, 'inspection_completed', 'Chốt hạng mục “Vỡ ly thủy tinh” sau khi kết quả buồng phòng và ý kiến khách đã thống nhất: 50.000đ.', '{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":100000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"v\\u01a1\\u0303 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T03:34:04+07:00\",\"detection_version\":1}', '{\"id\":28,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"type\":\"damage_fee\",\"unit\":\"ca\\u0301i\",\"price\":50000,\"quantity\":1,\"total\":50000,\"original_total\":100000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"v\\u01a1\\u0303 1 th\\u00f4i\",\"guest_claimed_quantity\":1,\"recheck_decision\":\"keep_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T03:34:04+07:00\",\"detection_version\":1}', 6, '2026-08-22 05:34:52'),
(102, 30, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 101: minibar/đồ dùng: Bia x3 = 30.000đ — 30.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}],\"workflow_stage\":\"guest_consultation\"}', 6, '2026-08-22 07:32:20'),
(103, 30, 30, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Bia” chỉ có 0 lon. Ghi chú: tôi mang theo bia', '{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}', '{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"t\\u00f4i mang theo bia\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}', 5, '2026-08-22 07:34:01'),
(104, 30, 30, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Bia”: khách xác nhận 0 lon, xác minh thực tế 0 lon (khớp đúng số lượng khách xác nhận), thành tiền 0đ.', '{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":3,\"total\":30000,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"t\\u00f4i mang theo bia\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}', '{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"t\\u00f4i mang theo bia\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}', 6, '2026-08-22 07:34:57'),
(105, 30, 30, 4, 'inspection_completed', 'Chốt hạng mục “Bia” sau khi kết quả buồng phòng và ý kiến khách đã thống nhất: 0đ.', '{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":30000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"t\\u00f4i mang theo bia\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}', '{\"id\":30,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":30000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"t\\u00f4i mang theo bia\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T07:32:20+07:00\",\"detection_version\":1}', 6, '2026-08-22 07:34:57'),
(106, 31, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 501: minibar/đồ dùng: Nước suối x2 = 14.000đ — 14.000đ. Chờ lễ tân trao đổi với khách.', '{\"items\":[]}', '{\"items\":[{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}],\"workflow_stage\":\"guest_consultation\"}', 6, '2026-08-22 08:47:36'),
(107, 31, 31, 2, 'guest_consultation', 'Khách xác nhận hạng mục “Nước suối” chỉ có 0 chai. Ghi chú: nước mang theo', '{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}', '{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"n\\u01b0\\u01a1\\u0301c mang theo\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}', 5, '2026-08-22 08:47:58'),
(108, 31, 31, 3, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Nước suối”: khách xác nhận 0 chai, xác minh thực tế 0 chai (khớp đúng số lượng khách xác nhận), thành tiền 0đ. ok', '{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":2,\"total\":14000,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"n\\u01b0\\u01a1\\u0301c mang theo\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}', '{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":0,\"total\":0,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"n\\u01b0\\u01a1\\u0301c mang theo\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"ok\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}', 6, '2026-08-22 08:48:18'),
(109, 31, 31, 4, 'inspection_completed', 'Chốt hạng mục “Nước suối” sau khi kết quả buồng phòng và ý kiến khách đã thống nhất: 0đ.', '{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":0,\"total\":0,\"original_total\":14000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"n\\u01b0\\u01a1\\u0301c mang theo\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"ok\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}', '{\"id\":31,\"name\":\"N\\u01b0\\u1edbc su\\u1ed1i\",\"type\":\"minibar\",\"unit\":\"chai\",\"price\":7000,\"quantity\":0,\"total\":0,\"original_total\":14000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"n\\u01b0\\u01a1\\u0301c mang theo\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"ok\",\"admin_note\":null,\"detection_source\":\"initial\",\"detected_by\":6,\"detected_at\":\"2026-08-22T08:47:36+07:00\",\"detection_version\":1}', 6, '2026-08-22 08:48:18'),
(110, 32, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 501: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"completed\"}', 4, '2026-08-24 00:04:30'),
(111, 32, NULL, 2, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', NULL, NULL, 4, '2026-08-24 00:04:30'),
(112, 33, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 405: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"completed\"}', 6, '2026-08-24 00:07:45'),
(113, 33, NULL, 2, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', NULL, NULL, 6, '2026-08-24 00:07:45'),
(114, 34, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 403: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"completed\"}', 6, '2026-08-24 00:07:58'),
(115, 34, NULL, 2, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', NULL, NULL, 6, '2026-08-24 00:07:58'),
(116, 35, NULL, 1, 'inspection_reported', 'Buồng phòng gửi kết quả kiểm tra phòng 405: không phát sinh minibar, mất đồ hoặc hư hại. Không phát sinh khoản cần đối chiếu; hoàn tất kiểm tra.', '{\"items\":[]}', '{\"items\":[],\"workflow_stage\":\"completed\"}', 6, '2026-08-24 10:32:10'),
(117, 35, NULL, 2, 'inspection_completed', 'Buồng phòng xác nhận phòng không phát sinh minibar, mất đồ hoặc hư hại; phiếu được hoàn tất ngay. Phí minibar/đồ dùng: 0đ; phí hư hại/mất đồ: 0đ; tổng cộng: 0đ.', NULL, NULL, 6, '2026-08-24 10:32:10'),
(118, 35, 32, 3, 'inspection_supplemental_detected', 'Phát hiện bổ sung “Bia” x2 sau lần kiểm tra trước: 20.000đ. zsrdvgbh', NULL, '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":2,\"total\":20000,\"original_total\":20000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', 6, '2026-08-24 10:32:49'),
(119, 35, 32, 4, 'guest_consultation', 'Khách xác nhận hạng mục “Bia” chỉ có 0 lon. Ghi chú: ccftvgybhnji', '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":2,\"total\":20000,\"original_total\":20000,\"status\":\"pending\",\"guest_response\":\"pending\",\"guest_response_note\":null,\"guest_claimed_quantity\":null,\"recheck_decision\":\"not_required\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":2,\"total\":20000,\"original_total\":20000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"ccftvgybhnji\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', 5, '2026-08-24 10:33:06'),
(120, 35, 32, 5, 'housekeeping_recheck', 'Buồng phòng kiểm tra lại “Bia”: khách xác nhận 0 lon, xác minh thực tế 0 lon (khớp đúng số lượng khách xác nhận), thành tiền 0đ.', '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":2,\"total\":20000,\"original_total\":20000,\"status\":\"pending\",\"guest_response\":\"disputed\",\"guest_response_note\":\"ccftvgybhnji\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"pending\",\"recheck_note\":null,\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":20000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"ccftvgybhnji\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', 6, '2026-08-24 10:33:18'),
(121, 35, 32, 6, 'inspection_completed', 'Chốt hạng mục “Bia” sau khi kết quả buồng phòng và ý kiến khách đã thống nhất: 0đ.', '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":20000,\"status\":\"pending\",\"guest_response\":\"accepted\",\"guest_response_note\":\"ccftvgybhnji\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', '{\"id\":32,\"name\":\"Bia\",\"type\":\"minibar\",\"unit\":\"lon\",\"price\":10000,\"quantity\":0,\"total\":0,\"original_total\":20000,\"status\":\"approved\",\"guest_response\":\"accepted\",\"guest_response_note\":\"ccftvgybhnji\",\"guest_claimed_quantity\":0,\"recheck_decision\":\"remove_charge\",\"recheck_note\":\"K\\u1ebft qu\\u1ea3 x\\u00e1c minh kh\\u1edbp v\\u1edbi s\\u1ed1 l\\u01b0\\u1ee3ng kh\\u00e1ch \\u0111\\u00e3 x\\u00e1c nh\\u1eadn.\",\"admin_note\":null,\"detection_source\":\"supplemental\",\"detected_by\":6,\"detected_at\":\"2026-08-24T10:32:49+07:00\",\"detection_version\":3}', 6, '2026-08-24 10:33:18');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_issue_attachments`
--

CREATE TABLE `room_issue_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_issue_request_id` bigint(20) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_issue_attachments`
--

INSERT INTO `room_issue_attachments` (`id`, `room_issue_request_id`, `path`, `original_name`, `mime_type`, `size_bytes`, `created_at`, `updated_at`) VALUES
(1, 1, 'room-issue-evidence/2/944zJgQ1EOJ0HZoo9vnwBU0H6ygfg41Peo4vY2Bc.jpg', 'mau-thiet-ke-noi-that-buong-vip-khach-san-co-1-ngu-1-khach-tinh-te-sang-trong-5.jpg', 'image/jpeg', 229996, '2026-07-19 02:53:47', '2026-07-19 02:53:47'),
(2, 1, 'room-issue-evidence/2/hdLp61ZBGPHXDuZEJyHE3NXIDaPql6RJOnpEEZVW.jpg', 'mau-thiet-ke-noi-that-phong-2-giuong-don-ben-trong-khach-san-3-4-5-sao-1.jpg', 'image/jpeg', 297100, '2026-07-19 02:53:47', '2026-07-19 02:53:47'),
(3, 1, 'room-issue-evidence/2/HM12pVCCl4FEoGN9IwrIYcRlqc7OXKxuCSu6tqyy.jpg', 'camera-1784429609452.jpg', 'image/jpeg', 68426, '2026-07-19 02:53:47', '2026-07-19 02:53:47'),
(4, 1, 'room-issue-evidence/2/8SroBa48w22I7dOVyy8wip12JllekUQ0g8lKiNmw.jpg', 'camera-1784429623565.jpg', 'image/jpeg', 75578, '2026-07-19 02:53:47', '2026-07-19 02:53:47'),
(5, 2, 'room-issue-evidence/4/EGvMh9driEwJm4uL2WfMvyBy49VjwoTz8OrDEgaA.jpg', 'camera-1784431872879.jpg', 'image/jpeg', 70933, '2026-07-19 03:31:15', '2026-07-19 03:31:15'),
(6, 3, 'room-issue-evidence/7/jXCM0Pw8Bb9jvTnDHm8OdhlcMn2ijk2KSYnNHrmN.jpg', 'camera-1784464636779.jpg', 'image/jpeg', 51907, '2026-07-19 12:37:19', '2026-07-19 12:37:19'),
(7, 4, 'room-issue-evidence/22/4/UlRSn6FRD7LVUj2OpfSrQdrobLMNUXzYcgH0EirC.jpg', 'mau-thiet-ke-noi-that-buong-vip-khach-san-co-1-ngu-1-khach-tinh-te-sang-trong-5.jpg', 'image/jpeg', 229996, '2026-07-20 10:49:16', '2026-07-20 10:49:16'),
(8, 5, 'room-issue-evidence/23/5/MaqHq00aTn5ma29djJ54niAGBGVheoQnuWUiJJRp.png', 'Screenshot (846).png', 'image/png', 2555070, '2026-07-20 10:50:35', '2026-07-20 10:50:35'),
(9, 6, 'room-issue-evidence/23/6/lwUXNubrqayyrszzmYCSG6ghTtvedqW5XWZrxd0o.png', 'Screenshot (845).png', 'image/png', 1591581, '2026-07-20 10:50:35', '2026-07-20 10:50:35'),
(10, 7, 'room-issue-evidence/24/7/3CAFi04IJlC1z3ynKkSjBG6bbPiAY2ZLvSoSeoMG.jpg', 'camera-1784546696646.jpg', 'image/jpeg', 46871, '2026-07-20 11:25:52', '2026-07-20 11:25:52'),
(11, 8, 'room-issue-evidence/24/8/rSxr7CUT0GacsKjs5W2tqIFIV6Uuk0xT77lGmafe.jpg', 'camera-1784547518563.jpg', 'image/jpeg', 121524, '2026-07-20 11:38:40', '2026-07-20 11:38:40'),
(12, 9, 'room-issue-evidence/26/9/OlxHr5iFtiHQESAaF4AcyDK9RATXLS26UVZ37CQq.png', 'Screenshot (868).png', 'image/png', 501068, '2026-07-20 14:45:38', '2026-07-20 14:45:38'),
(13, 10, 'room-issue-evidence/26/10/I0Ht8BMAnRTZTFHY1Kle9e7xBB4aV75jziQ1RExt.png', 'Screenshot (913).png', 'image/png', 370916, '2026-07-20 14:45:39', '2026-07-20 14:45:39'),
(14, 11, 'room-issue-evidence/26/11/Dx9AGbGfcEWYb0vnBvPnCYSnVBgvl5HPlvHEtlUv.jpg', 'camera-1784558736661.jpg', 'image/jpeg', 34783, '2026-07-20 14:45:39', '2026-07-20 14:45:39'),
(15, 12, 'room-issue-evidence/29/12/dqhQ0yfbx21QR4Wd0SJ0IqaT38cRb1ebWyz5jeOx.jpg', 'camera-1784574353852.jpg', 'image/jpeg', 51049, '2026-07-20 19:05:58', '2026-07-20 19:05:58'),
(17, 14, 'room-issue-evidence/46/14/36LUxsy6rWoeQEv8p5qpScac7QGOZHGvis71ngrV.jpg', 'lung-mat-3.jpg', 'image/jpeg', 70757, '2026-08-21 18:01:26', '2026-08-21 18:01:26'),
(18, 14, 'room-issue-evidence/46/14/XMve7Pd7d3YTXYFOudyhJGtemVKqQKpwGMde5yI2.jpg', 'camera-1787335282996.jpg', 'image/jpeg', 56469, '2026-08-21 18:01:26', '2026-08-21 18:01:26'),
(19, 16, 'room-issue-evidence/50/16/V4exHdInvMVqWpAxtsPXGBqbtdnQB5fYamR6dIvN.jpg', 'camera-1787363917009.jpg', 'image/jpeg', 79329, '2026-08-22 01:58:47', '2026-08-22 01:58:47'),
(20, 17, 'room-issue-evidence/53/17/xhnCqpc7SFatsOgsmFBe52Vugsb80n46ZomjnJYi.jpg', 'lung-mat-3.jpg', 'image/jpeg', 70757, '2026-08-23 22:52:53', '2026-08-23 22:52:53'),
(21, 18, 'room-issue-evidence/52/18/ucmrKr8Aa8YtKiIkAKotsYLK9n3IvJ0BG8Onogtb.png', 'Desktop - 10.png', 'image/png', 2031860, '2026-08-24 03:17:55', '2026-08-24 03:17:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_issue_requests`
--

CREATE TABLE `room_issue_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_uuid` char(36) NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `current_room_id` bigint(20) UNSIGNED NOT NULL,
  `current_room_category_id` bigint(20) UNSIGNED NOT NULL,
  `approved_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `repair_completed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `issue_description` text NOT NULL,
  `housekeeping_verdict` enum('confirmed','not_found') DEFAULT NULL,
  `housekeeping_can_repair_in_room` tinyint(1) NOT NULL DEFAULT 0,
  `housekeeping_note` text DEFAULT NULL,
  `housekeeping_verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `housekeeping_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','approved','repair_only','rejected') NOT NULL DEFAULT 'pending',
  `workflow_status` enum('pending','awaiting_housekeeping','housekeeping_verified','housekeeping_not_found','proposal_ready','waiting_guest_confirmation','guest_accepted','guest_requested_change','approved','completed','rejected') NOT NULL DEFAULT 'pending',
  `resolution_type` enum('same_category','upgrade_category','no_room') DEFAULT NULL,
  `proposed_resolution_type` enum('same_category','upgrade_category','repair_only') DEFAULT NULL,
  `proposed_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `proposed_room_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `repair_status` enum('waiting','completed') DEFAULT NULL,
  `price_difference_per_night` decimal(15,2) NOT NULL DEFAULT 0.00,
  `promotion_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`promotion_codes`)),
  `admin_note` text DEFAULT NULL,
  `proposal_note` text DEFAULT NULL,
  `proposal_created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `proposal_created_at` timestamp NULL DEFAULT NULL,
  `guest_response` enum('accepted','change_requested') DEFAULT NULL,
  `guest_selected_resolution_type` enum('same_category','upgrade_category','repair_only') DEFAULT NULL,
  `guest_response_note` text DEFAULT NULL,
  `guest_responded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `guest_responded_at` timestamp NULL DEFAULT NULL,
  `proposal_expires_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `repair_completed_at` timestamp NULL DEFAULT NULL,
  `repair_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_issue_requests`
--

INSERT INTO `room_issue_requests` (`id`, `group_uuid`, `booking_id`, `customer_id`, `current_room_id`, `current_room_category_id`, `approved_room_id`, `approved_room_category_id`, `reviewed_by`, `repair_completed_by`, `issue_description`, `housekeeping_verdict`, `housekeeping_can_repair_in_room`, `housekeeping_note`, `housekeeping_verified_by`, `housekeeping_verified_at`, `status`, `workflow_status`, `resolution_type`, `proposed_resolution_type`, `proposed_room_id`, `proposed_room_category_id`, `repair_status`, `price_difference_per_night`, `promotion_codes`, `admin_note`, `proposal_note`, `proposal_created_by`, `proposal_created_at`, `guest_response`, `guest_selected_resolution_type`, `guest_response_note`, `guest_responded_by`, `guest_responded_at`, `proposal_expires_at`, `reviewed_at`, `repair_completed_at`, `repair_note`, `created_at`, `updated_at`) VALUES
(1, '23dcd2c6-843c-11f1-9d3f-e89c25c0b5d3', 2, 1, 15, 5, 1, 1, 4, 4, 'điều hòa không hoạt động', NULL, 0, NULL, NULL, NULL, 'approved', 'pending', 'upgrade_category', NULL, NULL, NULL, 'completed', 200000.00, '[\"DEMO_INCIDENT_FULL\"]', 'xác nhận hỏng điều hòa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-19 02:55:41', '2026-07-19 02:56:29', 'đã sửa xong', '2026-07-19 02:53:46', '2026-07-19 02:56:29'),
(2, '23dcde33-843c-11f1-9d3f-e89c25c0b5d3', 4, 1, 15, 5, NULL, NULL, 4, 4, 'tắc cống', NULL, 0, NULL, NULL, NULL, 'repair_only', 'pending', 'no_room', NULL, NULL, NULL, 'completed', 0.00, '[\"DEMO200K\",\"WELCOME200BF\"]', 'xác nhận phòng ngập nước', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-19 03:34:42', '2026-07-19 03:36:06', 'đã sửa xong cống', '2026-07-19 03:31:14', '2026-07-19 03:36:06'),
(3, '23dcdff0-843c-11f1-9d3f-e89c25c0b5d3', 7, 1, 15, 5, 1, 1, 4, 4, 'hỏng điều hòa', NULL, 0, NULL, NULL, NULL, 'approved', 'pending', 'upgrade_category', NULL, NULL, NULL, 'completed', 200000.00, '[\"DEMO_INCIDENT_FULL\"]', 'hư điều hòa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-19 12:37:43', '2026-07-19 14:20:41', 'đã xong', '2026-07-19 12:37:18', '2026-07-19 14:20:41'),
(4, '23dce0c8-843c-11f1-9d3f-e89c25c0b5d3', 22, 1, 15, 5, 3, 1, 4, 4, 'hư điều hòa', NULL, 0, NULL, NULL, NULL, 'approved', 'pending', 'upgrade_category', NULL, NULL, NULL, 'completed', 200000.00, '[\"DEMO_INCIDENT_FULL\"]', 'xác nhận lỗi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-20 10:52:03', '2026-07-20 10:52:29', 'xác sửa xong', '2026-07-20 10:49:15', '2026-07-20 10:52:29'),
(5, '23dce160-843c-11f1-9d3f-e89c25c0b5d3', 23, 3, 13, 1, 1, 1, 4, 4, 'tv bật không được', NULL, 0, NULL, NULL, NULL, 'approved', 'pending', 'same_category', NULL, NULL, NULL, 'completed', 0.00, '[\"SUPPORT100K\"]', 'xác nhận lỗi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-20 10:51:29', '2026-07-20 10:52:44', 'xác sửa xong', '2026-07-20 10:50:35', '2026-07-20 10:52:44'),
(6, '23dce1ed-843c-11f1-9d3f-e89c25c0b5d3', 23, 3, 12, 1, 2, 1, 4, 4, 'phòng rột trần', NULL, 0, NULL, NULL, NULL, 'approved', 'pending', 'same_category', NULL, NULL, NULL, 'completed', 0.00, '[]', 'xác nhận lỗi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-20 10:51:43', '2026-07-20 10:52:36', 'xác sửa xong', '2026-07-20 10:50:35', '2026-07-20 10:52:36'),
(7, '23dce276-843c-11f1-9d3f-e89c25c0b5d3', 24, 1, 15, 5, 1, 1, 4, 4, 'hưu điều hòa', NULL, 0, NULL, NULL, NULL, 'approved', 'pending', 'upgrade_category', NULL, NULL, NULL, 'completed', 200000.00, '[\"DEMO_INCIDENT_FULL\"]', 'hưu điều hòa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-20 11:26:52', '2026-07-20 14:50:03', 'đã done', '2026-07-20 11:25:51', '2026-07-20 14:50:03'),
(8, '23dce30c-843c-11f1-9d3f-e89c25c0b5d3', 24, 1, 1, 1, NULL, NULL, 4, 4, 'hỏng vòi sen', NULL, 0, NULL, NULL, NULL, 'repair_only', 'approved', 'no_room', 'repair_only', NULL, NULL, 'completed', 0.00, '[]', 'ôk', NULL, 4, '2026-07-20 13:49:07', 'accepted', 'repair_only', NULL, 4, '2026-07-20 13:49:35', '2026-07-20 14:19:07', '2026-07-20 13:50:51', '2026-07-20 14:49:40', 'đã done', '2026-07-20 11:38:40', '2026-07-20 14:49:40'),
(9, 'b12a5180-64e0-4bc0-af64-0b7bc2a4834b', 26, 3, 12, 1, NULL, NULL, 4, 4, 'đèn không sáng', NULL, 0, NULL, NULL, NULL, 'repair_only', 'approved', 'no_room', 'same_category', 2, 1, 'completed', 0.00, '[\"DEMO_INCIDENT_FULL\"]', 'ok cho đổi', NULL, 4, '2026-07-20 14:47:07', 'accepted', 'repair_only', NULL, 4, '2026-07-20 14:48:14', '2026-07-20 15:17:07', '2026-07-20 14:48:49', '2026-07-20 14:49:31', 'đã done', '2026-07-20 14:45:38', '2026-07-20 14:49:31'),
(10, 'b12a5180-64e0-4bc0-af64-0b7bc2a4834b', 26, 3, 11, 1, 7, 3, 4, 4, 'điều hòa k chạy', NULL, 0, NULL, NULL, NULL, 'approved', 'approved', 'upgrade_category', 'upgrade_category', 7, 3, 'completed', 600000.00, '[\"DEMO_INCIDENT_FULL\"]', 'ok cho đổi', NULL, 4, '2026-07-20 14:47:07', 'accepted', 'upgrade_category', NULL, 4, '2026-07-20 14:48:14', '2026-07-20 15:17:07', '2026-07-20 14:48:49', '2026-07-20 14:49:47', 'đã done', '2026-07-20 14:45:38', '2026-07-20 14:49:47'),
(11, 'b12a5180-64e0-4bc0-af64-0b7bc2a4834b', 26, 3, 10, 1, 8, 3, 4, 4, 'thích thì báo', NULL, 0, NULL, NULL, NULL, 'approved', 'approved', 'upgrade_category', 'upgrade_category', 8, 3, 'completed', 600000.00, '[\"DEMO_INCIDENT_FULL\"]', 'ok cho đổi', NULL, 4, '2026-07-20 14:47:07', 'accepted', 'upgrade_category', NULL, 4, '2026-07-20 14:48:14', '2026-07-20 15:17:07', '2026-07-20 14:48:49', '2026-07-20 14:49:56', 'đã done', '2026-07-20 14:45:39', '2026-07-20 14:49:56'),
(12, '28eee24d-2e0b-4d8f-9179-173e3f060482', 29, 1, 8, 3, 9, 4, 4, 4, 'thích t đổi', NULL, 0, NULL, NULL, NULL, 'approved', 'approved', 'upgrade_category', 'upgrade_category', 9, 4, 'completed', 3200000.00, '[\"DEMO_INCIDENT_FULL\"]', 'ok cho đổi', NULL, 4, '2026-07-20 19:06:20', 'accepted', 'upgrade_category', NULL, 4, '2026-07-20 19:06:38', '2026-07-20 19:36:20', '2026-07-20 19:07:07', '2026-07-20 19:08:13', 'ok done', '2026-07-20 19:05:57', '2026-07-20 19:08:13'),
(14, 'c9df3396-630b-4b49-966a-ce6d114d3b9d', 46, 6, 15, 5, 1, 1, 4, 6, 'lỗi điều hòa', NULL, 0, NULL, NULL, NULL, 'approved', 'approved', 'upgrade_category', 'upgrade_category', 1, 1, 'completed', 0.00, '[]', 'ok', NULL, 4, '2026-08-21 18:50:17', 'accepted', 'upgrade_category', NULL, 5, '2026-08-21 18:50:51', '2026-08-21 19:20:17', '2026-08-21 18:51:08', '2026-08-21 20:30:37', 'ok', '2026-08-21 18:01:26', '2026-08-21 20:30:37'),
(15, '98787666-e009-4fc8-b044-79d801568c19', 47, 7, 15, 5, 1, 1, 4, 6, 'đèn hỏng', NULL, 0, NULL, NULL, NULL, 'approved', 'approved', 'upgrade_category', 'upgrade_category', 1, 1, 'completed', 0.00, '[\"DEMO_INCIDENT_FULL\"]', 'ok', NULL, 4, '2026-08-21 23:41:00', 'accepted', 'upgrade_category', NULL, 5, '2026-08-21 23:41:21', '2026-08-22 00:11:00', '2026-08-22 00:04:18', '2026-08-22 00:04:37', 'ok', '2026-08-21 23:40:35', '2026-08-22 00:04:37'),
(16, 'db22afd6-ee7e-4db1-b069-1ee9cd1b668b', 50, 6, 10, 1, 1, 1, 7, 6, 'hư quạt gió', NULL, 0, NULL, NULL, NULL, 'approved', 'approved', 'same_category', 'same_category', 1, 1, 'completed', 0.00, '[\"DEMO200K\",\"WELCOME200BF\"]', 'ok', NULL, 7, '2026-08-22 01:59:09', 'accepted', 'same_category', NULL, 5, '2026-08-22 01:59:34', '2026-08-22 02:29:09', '2026-08-22 01:59:58', '2026-08-22 02:00:34', 'ok', '2026-08-22 01:58:46', '2026-08-22 02:00:34'),
(17, '3de83398-dfd6-4734-b46d-6ad4aa544d67', 53, 7, 15, 5, NULL, NULL, 4, NULL, 'lỗi điều hòa', 'not_found', 0, 'điều hòa chỉ quên cắm nguồn', 6, '2026-08-23 22:54:09', 'rejected', 'rejected', NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 'ok1 cvgbh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-23 22:55:05', NULL, NULL, '2026-08-23 22:52:53', '2026-08-23 22:55:05'),
(18, 'b05537eb-51ca-459f-bc73-c9fd94714833', 52, 6, 13, 1, NULL, NULL, 4, 6, 'sẻdtgyhctfvgybuh', 'confirmed', 0, 'guhinj', 6, '2026-08-24 03:18:28', 'repair_only', 'approved', 'no_room', 'same_category', 1, 1, 'completed', 0.00, '[\"INCIDENT_FULL\"]', 'yvu', NULL, 4, '2026-08-24 03:19:20', 'accepted', 'repair_only', NULL, 5, '2026-08-24 03:24:55', '2026-08-24 03:49:20', '2026-08-24 03:25:34', '2026-08-24 03:27:40', 'xdcfyubhij', '2026-08-24 03:17:55', '2026-08-24 03:27:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_issue_room_holds`
--

CREATE TABLE `room_issue_room_holds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `group_uuid` char(36) NOT NULL,
  `room_issue_request_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `held_by` bigint(20) UNSIGNED DEFAULT NULL,
  `held_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `release_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_issue_room_holds`
--

INSERT INTO `room_issue_room_holds` (`id`, `group_uuid`, `room_issue_request_id`, `booking_id`, `room_id`, `held_by`, `held_at`, `expires_at`, `released_at`, `release_reason`, `created_at`, `updated_at`) VALUES
(1, '23dce30c-843c-11f1-9d3f-e89c25c0b5d3', 8, 24, 2, 4, '2026-07-20 13:48:00', '2026-07-20 21:18:00', '2026-07-20 13:48:39', 'Khách chọn giữ nguyên phòng và sửa gấp', '2026-07-20 13:48:00', '2026-07-20 13:48:39'),
(2, 'b12a5180-64e0-4bc0-af64-0b7bc2a4834b', 9, 26, 2, NULL, '2026-07-20 14:45:39', '2026-07-20 22:17:07', '2026-07-20 14:48:49', 'Đã xác nhận và thực hiện phương án', '2026-07-20 14:45:39', '2026-07-20 14:48:49'),
(3, 'b12a5180-64e0-4bc0-af64-0b7bc2a4834b', 10, 26, 7, NULL, '2026-07-20 14:45:39', '2026-07-20 22:17:07', '2026-07-20 14:48:49', 'Đã xác nhận và thực hiện phương án', '2026-07-20 14:45:39', '2026-07-20 14:48:49'),
(4, 'b12a5180-64e0-4bc0-af64-0b7bc2a4834b', 11, 26, 8, NULL, '2026-07-20 14:45:39', '2026-07-20 22:17:07', '2026-07-20 14:48:49', 'Đã xác nhận và thực hiện phương án', '2026-07-20 14:45:39', '2026-07-20 14:48:49'),
(5, '28eee24d-2e0b-4d8f-9179-173e3f060482', 12, 29, 9, 14, '2026-07-20 19:05:58', '2026-07-21 02:36:20', '2026-07-20 19:07:07', 'Đã xác nhận và thực hiện phương án', '2026-07-20 19:05:58', '2026-07-20 19:07:07'),
(7, 'c9df3396-630b-4b49-966a-ce6d114d3b9d', 14, 46, 1, NULL, '2026-08-21 18:01:27', '2026-08-22 01:31:46', '2026-08-21 18:39:25', 'Phương án cũ hết hiệu lực, hệ thống tự chọn lại', '2026-08-21 18:01:27', '2026-08-21 18:39:25'),
(8, 'c9df3396-630b-4b49-966a-ce6d114d3b9d', 14, 46, 1, 4, '2026-08-21 18:39:25', '2026-08-22 02:20:17', '2026-08-21 18:51:08', 'Đã xác nhận và thực hiện phương án', '2026-08-21 18:39:25', '2026-08-21 18:51:08'),
(9, '98787666-e009-4fc8-b044-79d801568c19', 15, 47, 1, 19, '2026-08-21 23:40:35', '2026-08-22 07:11:00', '2026-08-22 00:04:18', 'Đã xác nhận và thực hiện phương án', '2026-08-21 23:40:35', '2026-08-22 00:04:18'),
(10, 'db22afd6-ee7e-4db1-b069-1ee9cd1b668b', 16, 50, 1, NULL, '2026-08-22 01:58:47', '2026-08-22 09:29:09', '2026-08-22 01:59:58', 'Đã xác nhận và thực hiện phương án', '2026-08-22 01:58:47', '2026-08-22 01:59:58'),
(11, 'b05537eb-51ca-459f-bc73-c9fd94714833', 18, 52, 1, 4, '2026-08-24 03:19:20', '2026-08-24 10:49:20', '2026-08-24 03:25:34', 'Đã xác nhận và thực hiện phương án', '2026-08-24 03:19:20', '2026-08-24 03:25:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('service','minibar','minibar_order','damage_fee','occupancy_fee','policy_violation_fee','early_checkin_fee','late_checkout_fee','extension_fee','extra_guest_fee','manual_fee') NOT NULL DEFAULT 'service',
  `service_group` enum('general','food_drink','vehicle','laundry','transport','wellness','room_support','other') NOT NULL DEFAULT 'general',
  `price` decimal(12,2) NOT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'lần',
  `billing_rule` varchar(40) NOT NULL DEFAULT 'once',
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `services`
--

INSERT INTO `services` (`id`, `name`, `type`, `service_group`, `price`, `unit`, `billing_rule`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Giặt là', 'service', 'general', 50000.00, 'lần', 'once', 'Dịch vụ giặt và sấy quần áo cho khách lưu trú.', 'active', '2026-06-08 22:11:48', '2026-06-08 22:11:48'),
(2, 'Ăn sáng buffet', 'service', 'general', 120000.00, 'người', 'once', 'Suất ăn sáng buffet tại nhà hàng khách sạn.', 'active', '2026-06-08 22:12:39', '2026-06-08 22:12:39'),
(3, 'Đưa đón sân bay', 'service', 'general', 300000.00, 'lượt', 'once', 'Xe đưa đón khách từ sân bay về khách sạn hoặc ngược lại.', 'active', '2026-06-08 22:13:06', '2026-06-17 18:38:41'),
(4, 'Nước suối', 'minibar', 'food_drink', 7000.00, 'chai', 'once', 'Nước suối trong minibar tại phòng.', 'active', '2026-06-08 22:13:45', '2026-06-08 22:13:45'),
(5, 'Bia', 'minibar', 'food_drink', 10000.00, 'lon', 'once', 'Bia lon trong minibar, tính theo số lượng sử dụng.', 'active', '2026-06-08 22:14:12', '2026-06-08 22:14:12'),
(6, 'Vỡ ly thủy tinh', 'damage_fee', 'other', 50000.00, 'cái', 'once', 'Phí bồi thường khi khách làm vỡ ly trong phòng.', 'active', '2026-06-08 22:14:45', '2026-06-08 22:14:45'),
(7, 'Hỏng TV', 'damage_fee', 'other', 3000000.00, 'lần', 'once', 'Phí bồi thường khi khách làm hư hỏng TV trong phòng.', 'active', '2026-06-08 22:15:14', '2026-06-08 22:15:14'),
(10, 'Phụ thu thêm người lớn', 'occupancy_fee', 'other', 200000.00, 'người', 'once', 'Phụ thu khi khách phát sinh thêm người lớn lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-17 18:39:07'),
(11, 'Phụ thu thêm trẻ em', 'occupancy_fee', 'other', 100000.00, 'trẻ', 'once', 'Phụ thu khi khách phát sinh thêm trẻ em lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-15 22:35:34'),
(12, 'Phụ thu em bé', 'occupancy_fee', 'other', 50000.00, 'bé', 'once', 'Phụ thu khi khách phát sinh thêm em bé lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-15 22:35:34'),
(16, 'Trang trí sinh nhật', 'service', 'general', 300000.00, 'lần', 'once', 'Trang trí phòng theo yêu cầu', 'active', '2026-06-17 18:03:20', '2026-06-17 18:03:20'),
(18, 'Coca Cola', 'minibar', 'food_drink', 25000.00, 'lon', 'once', 'Nước ngọt trong minibar', 'active', '2026-06-17 18:03:28', '2026-06-17 18:03:28'),
(20, 'Snack', 'minibar', 'food_drink', 20000.00, 'gói', 'once', 'Đồ ăn nhẹ trong minibar', 'active', '2026-06-17 18:03:28', '2026-06-17 18:03:28'),
(22, 'Mất thẻ phòng', 'policy_violation_fee', 'other', 100000.00, 'thẻ', 'once', 'Phí bồi thường mất thẻ phòng', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(23, 'Bẩn ga giường nặng', 'policy_violation_fee', 'other', 150000.00, 'lần', 'once', 'Phí xử lý vệ sinh ga giường bẩn nặng', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(24, 'Hỏng remote điều hòa', 'policy_violation_fee', 'other', 200000.00, 'cái', 'once', 'Phí bồi thường remote điều hòa', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(27, 'Hút thuốc trong phòng', 'policy_violation_fee', 'other', 300000.00, 'lần', 'once', 'Phí xử lý mùi thuốc lá trong phòng', 'active', '2026-06-17 18:03:42', '2026-06-17 18:03:42'),
(28, 'Phụ thu khách đến muộn', 'policy_violation_fee', 'other', 0.00, 'lần', 'once', 'Phí vi phạm áp dụng khi khách đến muộn theo chính sách khách sạn.', 'active', '2026-06-18 04:42:14', '2026-06-18 04:42:14'),
(29, 'Phụ thu gia hạn lưu trú', 'policy_violation_fee', 'other', 0.00, 'lần', 'once', 'Phụ thu khi khách gia hạn thêm giờ hoặc thêm đêm.', 'active', '2026-06-18 05:29:36', '2026-06-18 05:29:36'),
(30, 'Phụ thu check-in sớm', 'policy_violation_fee', 'other', 0.00, 'lần', 'once', 'Phụ thu khi khách nhận phòng sớm trước giờ check-in chuẩn.', 'active', '2026-06-19 14:53:14', '2026-06-19 14:53:14'),
(31, 'Phụ thu check-out muộn', 'policy_violation_fee', 'other', 0.00, 'lần', 'once', 'Phụ thu khi khách trả phòng muộn so với giờ check-out trên booking.', 'active', '2026-06-19 14:57:46', '2026-06-19 14:57:46'),
(32, 'Buffet sáng', 'service', 'general', 180000.00, 'suất', 'once', 'Buffet sáng dùng để test mã ưu đãi dịch vụ.', 'active', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(33, 'Đồ uống chào mừng', 'service', 'general', 80000.00, 'phần', 'once', 'Welcome drink dùng để test mã hỗ trợ khách check-in sớm/đổi hạng phòng.', 'active', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(34, 'Buffet sáng DEMO', 'service', 'general', 180000.00, 'suất', 'once', 'Dịch vụ demo dùng để test mã tặng/giảm dịch vụ.', 'active', '2026-06-25 07:21:25', '2026-06-25 07:21:25'),
(35, 'Welcome drink DEMO', 'service', 'general', 80000.00, 'phần', 'once', 'Dịch vụ demo dùng để test mã hỗ trợ khách.', 'active', '2026-06-25 07:21:25', '2026-06-25 07:21:25'),
(36, 'Gửi xe máy qua đêm', 'service', 'vehicle', 20000.00, 'đêm', 'per_night', 'Phí gửi xe máy qua đêm cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(37, 'Gửi ô tô qua đêm', 'service', 'vehicle', 100000.00, 'đêm', 'per_night', 'Phí gửi ô tô qua đêm cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(38, 'Rửa xe máy', 'service', 'vehicle', 40000.00, 'lần', 'once', 'Dịch vụ hỗ trợ rửa xe máy cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(39, 'Rửa ô tô', 'service', 'vehicle', 120000.00, 'lần', 'once', 'Dịch vụ hỗ trợ rửa ô tô cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(40, 'Hỗ trợ gọi sửa xe', 'service', 'vehicle', 50000.00, 'lần', 'once', 'Khách sạn hỗ trợ liên hệ thợ/gara sửa xe. Phí sửa thực tế nếu có sẽ báo riêng cho khách.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(41, 'Hỗ trợ vá lốp xe máy', 'service', 'vehicle', 30000.00, 'lần', 'once', 'Khách sạn hỗ trợ liên hệ vá lốp xe máy cho khách. Phí phát sinh thực tế nếu có sẽ báo riêng.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(42, 'Hỗ trợ gọi cứu hộ xe', 'service', 'vehicle', 100000.00, 'lần', 'once', 'Khách sạn hỗ trợ liên hệ cứu hộ xe/gara cho khách khi xe gặp sự cố.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(43, 'Phụ thu check-in sớm', 'early_checkin_fee', 'other', 0.00, 'lần', 'once', 'Phụ thu khi khách nhận phòng sớm trước giờ check-in chuẩn.', 'active', '2026-07-19 02:52:31', '2026-07-19 02:52:31'),
(44, 'mất thẻ phòng', 'manual_fee', 'other', 0.00, 'lần', 'once', 'Khoản phí phát sinh được lễ tân ghi nhận trước khi check-out.', 'active', '2026-07-19 04:49:54', '2026-07-19 04:49:54'),
(45, 'Phụ thu nhận phòng sớm', 'policy_violation_fee', 'other', 0.00, 'lần', 'once', 'Phụ thu theo chính sách giờ nhận/trả phòng của khách sạn.', 'active', '2026-07-20 21:33:44', '2026-07-20 21:33:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('1VjS1mP9MrgKOeJIeBkVx1a0BKm0LqJA42ifaEx8', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicjVLU3Y1TnR0NHZPOEgyc1pEQWw3WURtd1BNcnZoTWtOaFFRZUZEVSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX19', 1787536972),
('2FCGFTRE0bVMt2gQLe3kME1jnQ4xlivOHptMAAYF', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiV1hoTms4SFN4c1J1WWlnd3hHdXJoejB1QjBRZm9JaEVmVFBYM2t1YiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX19', 1787536972),
('7Kyj1BTMCPnnoJ6FZepxFR2dcICGMRCVTyTA6VT0', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiZVRDT0xucHNlbWtwOUoyOTU0T0JNejllTUFqcHNJS2NLRE1HbDk5ciI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozOToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluL2Jvb2tpbmdzLzUyIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDg6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9mbG9vci1pbnNwZWN0aW9ucy8zNSI7czo1OiJyb3V0ZSI7czoyODoiYWRtaW4uZmxvb3ItaW5zcGVjdGlvbnMuc2hvdyI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjY7fQ==', 1787542510),
('ARIluXbawpZIAlge5ZMmXlGMCoy33cWwmt2NaSDy', 18, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSUM2MkF0UjlzRVNxaEZaVVhIaWhHTnBCWTRUQjhldk51c2JsQ0ZtcyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTg7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9ib29raW5nLWhpc3RvcnkvNTIiO3M6NToicm91dGUiO3M6MTM6ImJvb2tpbmdzLnNob3ciO319', 1787545463),
('bS3ZOa78v4z00oG6GIyVzdm5PRKuZXAs4bNV0wqE', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNlpkTVRrZEZCMk16Vlp1WGF5QlN6MTRSTFNnNHpUQlNISTJnMHlKcSI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX1zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo1MzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluL3Jvb20taXNzdWUtYXR0YWNobWVudHMvMjEiO3M6NToicm91dGUiO3M6MzM6ImFkbWluLnJvb20taXNzdWUtYXR0YWNobWVudHMuc2hvdyI7fX0=', 1787545465),
('isLs7bAh3l0WAMJANwXLM6PllcsyN4zaHyxSSFcG', 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiaWtOS0k5UVA0cURIbkZGaWdJWEEzcThTU3J2TW9DbDJ3NVNmZTQxZyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX1zOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo1MzoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2FkbWluL3Jvb20taXNzdWUtYXR0YWNobWVudHMvMjAiO3M6NToicm91dGUiO3M6MzM6ImFkbWluLnJvb20taXNzdWUtYXR0YWNobWVudHMuc2hvdyI7fX0=', 1787545463),
('jTD1K4smQTaD3dOI1NKlTPnko7KuLQMe6eOKNWRU', 29, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiZUZDakxSbWVxU1FLMU1PaGJHU1pXQlQwakZJY3pzMzl0UnR4ejBHTSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1787537005),
('oGbe3BRDHJKeHWVWEqTPL2aKPuzsklGRV2Z2Es26', 18, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiaXJld3dicUVHbGNEWXdYbHN2M3JRaFJjaDNHUW1Vd3ptdGRVcjJZRyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTg7czo2OiJfZmxhc2giO2E6Mjp7czozOiJuZXciO2E6MDp7fXM6Mzoib2xkIjthOjA6e319fQ==', 1787536943),
('OWZhssb23WiJlFrOJN1vJyMN6kq2vBmeAJHJ0gYj', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiM2U1ZzZRc21rWm9YdTJuQXpSb3Q2RWFYYnR6R3Y2QlRMemJRaGY1QyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzk6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9ib29raW5ncy81MiI7czo1OiJyb3V0ZSI7czoxOToiYWRtaW4uYm9va2luZ3Muc2hvdyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjU7fQ==', 1787545473),
('rz6C6t5NEslABCY6HXHfsmo8lHU1pddj8NwkMu7J', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZzJReUhNeURvdXRycHBJckFQN3QwNm1oWWt3eWoyYkVEeDVXSHlNWiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9fQ==', 1787540377),
('Sn4HGewQnaloTMmpNl1ICf1rTsCf99nF9HZXbxEr', 19, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSUt2ZXRJeVJFTzFJZW1kcElGc1dFQlRmYXE1Znp0dU9Td0x2VXhRaCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTk7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9ib29raW5nLWhpc3RvcnkvNTMiO3M6NToicm91dGUiO3M6MTM6ImJvb2tpbmdzLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1787536992),
('vG4cbPTpBskFS0PJmXXPvchaeRFiUITOmGWx7OlD', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWtYQ1NPMVBDMGJIUFVpYkFPU2cwVWo0dm9US0c0YmlHdDZ3WVhBcyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDtzOjY6Il9mbGFzaCI7YToyOntzOjM6Im5ldyI7YTowOnt9czozOiJvbGQiO2E6MDp7fX19', 1787536972);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `staffs`
--

CREATE TABLE `staffs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cccd` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `hire_date` date DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `work_status` enum('working','resigned','temporary_leave') NOT NULL DEFAULT 'working',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `staffs`
--

INSERT INTO `staffs` (`id`, `user_id`, `full_name`, `phone`, `cccd`, `birthday`, `gender`, `address`, `position`, `salary`, `hire_date`, `avatar`, `work_status`, `created_at`, `updated_at`) VALUES
(3, 5, 'LT1', '0985795999', '038207022000', '2019-03-12', 'female', 'Huyện Yên Định', 'Lễ tân', 10000000.00, '2026-06-13', 'staffs/GhjRKq793ecJsgcIWuuY1cWAPd613R68LUGZVkpW.png', 'working', '2026-06-12 02:56:27', '2026-06-12 02:56:27'),
(4, 6, 'Buồng 1', '0985795111', '038206022003', '2021-01-03', 'male', 'Huyện Yên Định', 'Buồng phòng', 20000000.00, '2026-06-13', 'staffs/MzMfwIbstUa8c2eRg4So8NJvaiteLrPzNPAAUyUj.png', 'working', '2026-06-12 03:42:42', '2026-06-12 03:42:58'),
(5, 7, 'Quản lý 1', '0985795222', '038206022333', '2021-01-05', 'female', 'Huyện Yên Định', 'Quản lý', 25000000.00, '2026-06-08', 'staffs/oqNKQ2nqKAabJxNbo8GUC6Qxcx6RhijOp2UIbxgZ.png', 'working', '2026-06-12 03:44:16', '2026-06-12 03:44:16'),
(6, 29, 'Hoàng Văn Minh', '0985796508', '038207033003', '1993-04-15', 'male', 'Hưng Yên', 'Lễ tân', 10000000.00, '2026-08-24', NULL, 'working', '2026-08-23 19:51:27', '2026-08-23 19:51:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `staff_floor_assignments`
--

CREATE TABLE `staff_floor_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `floor_number` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `shift` enum('morning','afternoon','evening','full_day') NOT NULL DEFAULT 'morning',
  `status` enum('active','canceled') NOT NULL DEFAULT 'active',
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `staff_floor_assignments`
--

INSERT INTO `staff_floor_assignments` (`id`, `staff_id`, `floor_number`, `work_date`, `shift`, `status`, `assigned_by`, `note`, `created_at`, `updated_at`) VALUES
(1, 6, 1, '2026-08-21', 'full_day', 'canceled', 4, NULL, '2026-08-21 09:29:43', '2026-08-21 20:28:31'),
(2, 6, 2, '2026-08-22', 'full_day', 'canceled', 4, NULL, '2026-08-21 19:46:08', '2026-08-21 20:28:31'),
(3, 6, 3, '2026-08-22', 'full_day', 'canceled', 4, NULL, '2026-08-21 19:46:08', '2026-08-21 20:28:31'),
(4, 6, 4, '2026-08-22', 'full_day', 'canceled', 4, NULL, '2026-08-21 19:46:08', '2026-08-21 20:28:31'),
(5, 6, 5, '2026-08-22', 'full_day', 'canceled', 4, NULL, '2026-08-21 19:46:08', '2026-08-21 20:28:31'),
(6, 6, 1, '2026-08-22', 'morning', 'canceled', 4, NULL, '2026-08-21 20:29:33', '2026-08-22 01:52:27'),
(7, 6, 2, '2026-08-22', 'morning', 'canceled', 4, NULL, '2026-08-21 20:29:33', '2026-08-22 01:52:27'),
(8, 6, 3, '2026-08-22', 'morning', 'canceled', 4, NULL, '2026-08-21 20:29:33', '2026-08-22 01:52:27'),
(9, 6, 4, '2026-08-22', 'morning', 'canceled', 4, NULL, '2026-08-21 20:29:33', '2026-08-22 01:52:27'),
(10, 6, 5, '2026-08-22', 'morning', 'canceled', 4, NULL, '2026-08-21 20:29:33', '2026-08-22 01:52:27'),
(11, 6, 1, '2026-08-22', 'full_day', 'canceled', 7, NULL, '2026-08-22 01:52:44', '2026-08-22 01:54:22'),
(12, 6, 4, '2026-08-22', 'full_day', 'active', 7, NULL, '2026-08-22 01:54:31', '2026-08-22 01:54:31'),
(13, 6, 5, '2026-08-22', 'full_day', 'active', 7, NULL, '2026-08-22 01:54:31', '2026-08-22 01:54:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `staff_room_assignments`
--

CREATE TABLE `staff_room_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `staff_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `shift` enum('morning','afternoon','evening','full_day') NOT NULL DEFAULT 'morning',
  `task_type` enum('cleaning','inspection','maintenance_support') NOT NULL DEFAULT 'cleaning',
  `status` enum('assigned','in_progress','completed','canceled') NOT NULL DEFAULT 'assigned',
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','manager','receptionist_lead','receptionist','housekeeping_supervisor','housekeeping','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `booking_locked_until` datetime DEFAULT NULL,
  `booking_lock_reason` varchar(500) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `google_id`, `avatar`, `email_verified_at`, `password`, `role`, `status`, `booking_locked_until`, `booking_lock_reason`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'Chiến Trịnh', 'chientr33@gmail.com', '101875707999826167782', 'avatars/ou5dhWm1C3m3Fqlr3J66AEUc8OTonk0yE3E8JyO6.jpg', '2026-07-14 08:23:43', '$2y$12$2yzPWmde1rX4Zb7iOBk9s.Rmzq2UyoW38cdb2/ire1ZbehL14eKWK', 'super_admin', 'active', NULL, NULL, 'ddnpvKxO26gPFIhXXy0ViRw1XrcDoNsKLTxCeBUpHZeu3OAeVvYVWqr9JDI7', '2026-06-05 06:28:47', '2026-07-14 08:23:43', NULL),
(5, 'LT1', 'lt1@gmail.com', NULL, NULL, NULL, '$2y$12$QDau.PoEC2nLvrTkIzz5IOu40ocx9nBFT6MJ/B3REtvTSFZhYvTFa', 'receptionist', 'active', NULL, NULL, 'p8PXcPmf4jaIe0QqDzIxhBgXX4A422wX1EuKQUjzJ2XZpBhVQ1XV7vgPWBoy', '2026-06-12 02:56:26', '2026-06-13 01:49:31', NULL),
(6, 'Buồng 1', 'bp1@gmail.com', NULL, NULL, NULL, '$2y$12$oZt9xTFvAwXhZ7SRORYFvu2vKjJRacLNHPHYas81GHldy.Q36g.7S', 'housekeeping', 'active', NULL, NULL, '7v4bU6275GkabFJ8iuIcpGJiisQTZc7rnya1ktUIWbjZZNaR8HOH1P0AxYAX', '2026-06-12 03:42:42', '2026-06-13 01:49:08', NULL),
(7, 'Quản lý 1', 'ql1@gmail.com', NULL, NULL, NULL, '$2y$12$jgIUMmboUCdS3iUWvRmiJeyVm2DsiNk3QwmDF1Ri5g0HZx3TQMIA.', 'manager', 'active', NULL, NULL, NULL, '2026-06-12 03:44:16', '2026-06-12 03:46:51', NULL),
(14, 'Trịnh Ngọc Chiến', 'tc19092006@gmail.com', '114766218040428006282', NULL, '2026-07-21 04:52:12', NULL, 'customer', 'active', NULL, NULL, 'ahkXNX06MoJDP15dwd6ZKcRpbXOdpCZWXonXXUFL8omXuo5LbK2nZKP1jFER', '2026-07-17 10:57:38', '2026-07-28 04:08:34', NULL),
(15, 'Đào Du', 'du319@gmail.com', NULL, NULL, NULL, '$2y$12$tHTaEnAWo.28xyxkfV8qAeOODNuTIE4X4j.Q6fXP0lUTB8L.19252', 'customer', 'active', '2026-08-04 12:02:41', 'Tạm khóa 7 ngày do hủy từ 3 booking trong 30 ngày.', 'srDw60PV4G3jKulZ8BTruLZadxLo1Hg2AVoePuCLnSRASZ6Kq3p1yA2tPhat', '2026-07-17 14:09:47', '2026-07-28 05:02:41', NULL),
(16, 'Nguyễn Anh', 'nguyena1@gmail.com', NULL, NULL, NULL, '$2y$12$i6u6EtEOoFBlqGpo8dkQk.KUDcGhKRAy5WwWmk/pSzKOpUtHzmidG', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:39:14', '2026-07-21 04:56:42', NULL),
(17, 'chientr319@gmail.com', 'chientr319@gmail.com', '107423942214733311359', NULL, NULL, NULL, 'customer', 'active', NULL, NULL, 'i9NADNiQadhxmVBGvSgpfv1xkP21PXu4LHpGNRZuqHSDhMKMvYEM8YhnxrLo', '2026-07-21 04:40:43', '2026-07-21 04:40:43', NULL),
(18, 'Nguyễn Minh Anh', 'demo.user01@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, 'lbkaM8SUcAfTRlL8g0GENbekYGxJIpk1PHx2Jxjwc2Dwtl6OvUNkxXIqbMRC', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(19, 'Trần Quốc Bảo', 'demo.user02@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, 'ysNhMF0Aofy4RrcF6rvMPtLzcbKUfRgQ1WKvbMHb5Jz2hL9rkYFRvIvgCTk2', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(20, 'Lê Thu Hà', 'demo.user03@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, 'stg7nLzurIts9lHtlvJJnsSXzJOL2q1CSjJm8xTmMHuwjnbIb5MDGgXzXcnK', '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(21, 'Phạm Hoàng Nam', 'demo.user04@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(22, 'Vũ Ngọc Lan', 'demo.user05@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(23, 'Đỗ Văn Hùng', 'demo.user06@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(24, 'Bùi Khánh Linh', 'demo.user07@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(25, 'Hoàng Đức Long', 'demo.user08@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(26, 'Đặng Mai Phương', 'demo.user09@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(27, 'Ngô Tuấn Kiệt', 'demo.user10@booking.local', NULL, NULL, '2026-07-21 04:57:58', '$2y$12$LEK/fRTYy5tz6ok5UYQsb.kRJ6dbXMfAIVro6YIT5./IB22neWA1m', 'customer', 'active', NULL, NULL, NULL, '2026-07-21 04:57:58', '2026-07-21 04:57:58', NULL),
(28, 'Dương Cường', 'sccuong5222@gmail.com', '115118446122045852056', NULL, NULL, NULL, 'customer', 'active', NULL, NULL, 'co0N5YXpLICzrXLGRdd5x3B0qIax6za8itm4d43oyLXEZrivGHw2ZF4qSyzJ', '2026-07-21 07:26:01', '2026-07-28 05:08:56', NULL),
(29, 'Hoàng Văn Minh', 'lt2@gmail.com', NULL, NULL, NULL, '$2y$12$5p/vo9aGkgl6Z3VqdHJK2Or.sIb6NtFcdsWf./mHfJT96kbaeLy1i', 'receptionist', 'active', NULL, NULL, 'l6yas41wp9fucsAG993aegaWbozbD1La5UVBv4pNRGoH9vcjVTf4bHwjzeBO', '2026-08-23 19:51:27', '2026-08-23 19:51:27', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `banned_words`
--
ALTER TABLE `banned_words`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `banned_words_word_unique` (`word`),
  ADD KEY `banned_words_created_by_index` (`created_by`);

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  ADD KEY `bookings_customer_id_foreign` (`customer_id`),
  ADD KEY `bookings_created_by_foreign` (`created_by`),
  ADD KEY `bookings_room_category_id_foreign` (`room_category_id`),
  ADD KEY `idx_bookings_time_range` (`check_in_at`,`check_out_at`),
  ADD KEY `idx_bookings_availability` (`status`,`check_in_at`,`check_out_at`,`payment_expires_at`),
  ADD KEY `bookings_room_selection_status_idx` (`room_selection_status`),
  ADD KEY `bookings_room_selection_handled_by_idx` (`room_selection_handled_by`),
  ADD KEY `bookings_refund_status_idx` (`refund_status`),
  ADD KEY `bookings_refund_processed_by_idx` (`refund_processed_by`);

--
-- Chỉ mục cho bảng `booking_cancellation_requests`
--
ALTER TABLE `booking_cancellation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_cancellation_requests_booking_id_status_index` (`booking_id`,`status`),
  ADD KEY `booking_cancellation_requests_customer_id_foreign` (`customer_id`),
  ADD KEY `booking_cancellation_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `booking_cancellation_requests_reviewed_by_foreign` (`reviewed_by`);

--
-- Chỉ mục cho bảng `booking_guests`
--
ALTER TABLE `booking_guests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_guests_booking_id_foreign` (`booking_id`),
  ADD KEY `booking_guests_booking_room_id_foreign` (`booking_room_id`),
  ADD KEY `idx_booking_guests_room_type` (`booking_id`,`booking_room_id`,`guest_type`),
  ADD KEY `idx_booking_guests_guardian` (`guardian_guest_id`);

--
-- Chỉ mục cho bảng `booking_guest_room_histories`
--
ALTER TABLE `booking_guest_room_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_guest_room_history_guest` (`booking_guest_id`,`ended_at`),
  ADD KEY `idx_guest_room_history_from` (`from_booking_room_id`),
  ADD KEY `idx_guest_room_history_to` (`to_booking_room_id`);

--
-- Chỉ mục cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_logs_booking_id_foreign` (`booking_id`),
  ADD KEY `booking_logs_user_id_foreign` (`user_id`);

--
-- Chỉ mục cho bảng `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `txn_ref` (`txn_ref`),
  ADD UNIQUE KEY `uq_booking_payments_txn_ref` (`txn_ref`),
  ADD KEY `booking_payments_booking_id_foreign` (`booking_id`);

--
-- Chỉ mục cho bảng `booking_promotions`
--
ALTER TABLE `booking_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_promotions_booking_id_index` (`booking_id`),
  ADD KEY `booking_promotions_promotion_id_index` (`promotion_id`),
  ADD KEY `booking_promotions_applied_by_index` (`applied_by`),
  ADD KEY `idx_booking_promotions_scope` (`booking_id`,`scope`,`booking_room_id`),
  ADD KEY `idx_booking_promotions_code_scope` (`booking_id`,`promotion_id`,`booking_room_id`),
  ADD KEY `idx_booking_promotions_issue` (`room_issue_request_id`);

--
-- Chỉ mục cho bảng `booking_promotion_room_upgrades`
--
ALTER TABLE `booking_promotion_room_upgrades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_promotion_room_upgrades_booking_id_index` (`booking_id`),
  ADD KEY `booking_promotion_room_upgrades_booking_promotion_id_index` (`booking_promotion_id`),
  ADD KEY `booking_promotion_room_upgrades_promotion_id_index` (`promotion_id`);

--
-- Chỉ mục cho bảng `booking_promotion_service_offers`
--
ALTER TABLE `booking_promotion_service_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bps_offers_booking_fk` (`booking_id`),
  ADD KEY `bps_offers_booking_promo_fk` (`booking_promotion_id`),
  ADD KEY `bps_offers_promotion_fk` (`promotion_id`),
  ADD KEY `bps_offers_promo_service_fk` (`promotion_service_offer_id`),
  ADD KEY `bps_offers_service_fk` (`service_id`),
  ADD KEY `idx_booking_promo_service_room` (`booking_room_id`);

--
-- Chỉ mục cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_rooms_booking_id_foreign` (`booking_id`),
  ADD KEY `booking_rooms_room_id_foreign` (`room_id`);

--
-- Chỉ mục cho bảng `booking_room_changes`
--
ALTER TABLE `booking_room_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_room_changes_booking_source_index` (`booking_id`,`change_source`),
  ADD KEY `booking_room_changes_category_index` (`old_room_category_id`,`new_room_category_id`),
  ADD KEY `booking_room_changes_booking_room_id_index` (`booking_room_id`),
  ADD KEY `booking_room_changes_issue_id_index` (`room_issue_request_id`),
  ADD KEY `booking_room_changes_old_room_id_index` (`old_room_id`),
  ADD KEY `booking_room_changes_new_room_id_index` (`new_room_id`),
  ADD KEY `booking_room_changes_changed_by_index` (`changed_by`),
  ADD KEY `booking_room_changes_new_category_id_foreign` (`new_room_category_id`);

--
-- Chỉ mục cho bảng `booking_service_items`
--
ALTER TABLE `booking_service_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_service_items_booking` (`booking_id`),
  ADD KEY `fk_booking_service_items_service` (`service_id`),
  ADD KEY `idx_booking_service_items_room` (`booking_room_id`),
  ADD KEY `idx_booking_service_items_scope` (`booking_id`,`scope`,`booking_room_id`),
  ADD KEY `idx_booking_service_items_source` (`source_type`,`source_id`);

--
-- Chỉ mục cho bảng `booking_staff_assignments`
--
ALTER TABLE `booking_staff_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_staff_assignments_booking_status_idx` (`booking_id`,`status`),
  ADD KEY `booking_staff_assignments_staff_status_idx` (`staff_id`,`status`),
  ADD KEY `booking_staff_assignments_role_status_idx` (`role_in_booking`,`status`),
  ADD KEY `booking_staff_assignments_assigned_by_idx` (`assigned_by`);

--
-- Chỉ mục cho bảng `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Chỉ mục cho bảng `chat_assignment_logs`
--
ALTER TABLE `chat_assignment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_assignment_logs_conversation_id_index` (`conversation_id`),
  ADD KEY `chat_assignment_logs_from_staff_id_index` (`from_staff_id`),
  ADD KEY `chat_assignment_logs_to_staff_id_index` (`to_staff_id`);

--
-- Chỉ mục cho bảng `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_attachments_message_id_index` (`message_id`);

--
-- Chỉ mục cho bảng `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_conversations_customer_id_index` (`customer_id`),
  ADD KEY `chat_conversations_booking_id_index` (`booking_id`),
  ADD KEY `chat_conversations_assigned_staff_id_index` (`assigned_staff_id`),
  ADD KEY `chat_conversations_status_assigned_index` (`status`,`assigned_staff_id`),
  ADD KEY `chat_conversations_customer_status_index` (`customer_id`,`status`),
  ADD KEY `chat_conversations_last_message_at_index` (`last_message_at`),
  ADD KEY `chat_conversations_guest_phone_index` (`guest_phone`),
  ADD KEY `chat_conversations_guest_email_index` (`guest_email`);

--
-- Chỉ mục cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_messages_conversation_id_index` (`conversation_id`),
  ADD KEY `chat_messages_sender_id_index` (`sender_id`),
  ADD KEY `chat_messages_conversation_created_index` (`conversation_id`,`created_at`);

--
-- Chỉ mục cho bảng `chat_staff_presences`
--
ALTER TABLE `chat_staff_presences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chat_staff_presences_user_id_unique` (`user_id`),
  ADD KEY `chat_staff_presences_last_seen_at_index` (`last_seen_at`),
  ADD KEY `chat_presence_status_seen_idx` (`status`,`last_seen_at`);

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_phone_unique` (`phone`),
  ADD UNIQUE KEY `customers_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `customers_cccd_unique` (`cccd`);

--
-- Chỉ mục cho bảng `customer_credits`
--
ALTER TABLE `customer_credits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_credits_source_booking_unique` (`source_booking_id`),
  ADD KEY `customer_credits_customer_status_expires_index` (`customer_id`,`status`,`expires_at`);

--
-- Chỉ mục cho bảng `customer_requests`
--
ALTER TABLE `customer_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_requests_booking` (`booking_id`),
  ADD KEY `idx_customer_requests_status_type` (`status`,`type`),
  ADD KEY `customer_requests_reviewer_fk` (`reviewed_by`);

--
-- Chỉ mục cho bảng `customer_request_attachments`
--
ALTER TABLE `customer_request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_request_attachments_request` (`customer_request_id`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Chỉ mục cho bảng `hotel_policies`
--
ALTER TABLE `hotel_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hotel_policies_key_unique` (`key`),
  ADD KEY `hotel_policies_group_active_sort_index` (`policy_group`,`active`,`sort_order`);

--
-- Chỉ mục cho bảng `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hotel_reviews_booking_id_unique` (`booking_id`),
  ADD KEY `hotel_reviews_status_rating_index` (`status`,`rating`),
  ADD KEY `hotel_reviews_room_category_id_status_index` (`room_category_id`,`status`),
  ADD KEY `hotel_reviews_customer_id_status_index` (`customer_id`,`status`),
  ADD KEY `hotel_reviews_created_at_index` (`created_at`),
  ADD KEY `hotel_reviews_user_id_foreign` (`user_id`),
  ADD KEY `hotel_reviews_approved_by_foreign` (`approved_by`),
  ADD KEY `hotel_reviews_hidden_by_foreign` (`hidden_by`),
  ADD KEY `hotel_reviews_replied_by_foreign` (`replied_by`);

--
-- Chỉ mục cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_code_unique` (`invoice_code`),
  ADD KEY `invoices_booking_id_foreign` (`booking_id`),
  ADD KEY `invoices_created_by_foreign` (`created_by`);

--
-- Chỉ mục cho bảng `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Chỉ mục cho bảng `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Chỉ mục cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `promotions_code_unique` (`code`),
  ADD KEY `promotions_type_status_index` (`promotion_type`,`status`),
  ADD KEY `promotions_valid_index` (`valid_from`,`valid_to`),
  ADD KEY `promotions_created_by_foreign` (`created_by`);

--
-- Chỉ mục cho bảng `promotion_room_upgrade_offers`
--
ALTER TABLE `promotion_room_upgrade_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_room_upgrade_offers_promotion_id_index` (`promotion_id`),
  ADD KEY `promotion_room_upgrade_offers_from_category_index` (`from_room_category_id`),
  ADD KEY `promotion_room_upgrade_offers_to_category_index` (`to_room_category_id`);

--
-- Chỉ mục cho bảng `promotion_service_offers`
--
ALTER TABLE `promotion_service_offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `promotion_service_offers_promotion_id_foreign` (`promotion_id`),
  ADD KEY `promotion_service_offers_service_id_foreign` (`service_id`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_room_number_unique` (`room_number`),
  ADD KEY `rooms_room_category_id_foreign` (`room_category_id`);

--
-- Chỉ mục cho bảng `room_action_logs`
--
ALTER TABLE `room_action_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_action_logs_room_id_foreign` (`room_id`),
  ADD KEY `room_action_logs_user_id_foreign` (`user_id`);

--
-- Chỉ mục cho bảng `room_categories`
--
ALTER TABLE `room_categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `room_category_amenities`
--
ALTER TABLE `room_category_amenities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_category_amenities_room_category_id_foreign` (`room_category_id`),
  ADD KEY `room_category_amenities_amenity_id_foreign` (`amenity_id`);

--
-- Chỉ mục cho bảng `room_category_images`
--
ALTER TABLE `room_category_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_category_images_room_category_id_foreign` (`room_category_id`);

--
-- Chỉ mục cho bảng `room_inspections`
--
ALTER TABLE `room_inspections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_room_inspections_booking_room` (`booking_id`,`room_id`),
  ADD KEY `room_inspections_booking_id_foreign` (`booking_id`),
  ADD KEY `room_inspections_room_id_foreign` (`room_id`),
  ADD KEY `room_inspections_inspected_by_foreign` (`inspected_by`),
  ADD KEY `room_inspections_confirmed_by_foreign` (`confirmed_by`),
  ADD KEY `idx_room_inspections_workflow_stage` (`workflow_stage`),
  ADD KEY `idx_room_inspections_version` (`version`),
  ADD KEY `idx_room_inspections_admin_ack_by` (`admin_acknowledged_by`),
  ADD KEY `idx_room_inspections_guest_consulted_by` (`guest_consulted_by`);

--
-- Chỉ mục cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_inspection_items_room_inspection_id_foreign` (`room_inspection_id`),
  ADD KEY `room_inspection_items_service_id_foreign` (`service_id`),
  ADD KEY `idx_inspection_items_guest_response` (`guest_response`),
  ADD KEY `idx_inspection_items_recheck_decision` (`recheck_decision`),
  ADD KEY `idx_inspection_items_guest_responded_by` (`guest_responded_by`),
  ADD KEY `idx_inspection_items_rechecked_by` (`rechecked_by`),
  ADD KEY `idx_inspection_items_detection_version` (`room_inspection_id`,`detection_version`),
  ADD KEY `idx_inspection_items_detected_by` (`detected_by`);

--
-- Chỉ mục cho bảng `room_inspection_revisions`
--
ALTER TABLE `room_inspection_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inspection_revisions_inspection_version` (`room_inspection_id`,`version`),
  ADD KEY `idx_inspection_revisions_item` (`room_inspection_item_id`),
  ADD KEY `idx_inspection_revisions_changed_by` (`changed_by`);

--
-- Chỉ mục cho bảng `room_issue_attachments`
--
ALTER TABLE `room_issue_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_issue_attachments_request_id_foreign` (`room_issue_request_id`);

--
-- Chỉ mục cho bảng `room_issue_requests`
--
ALTER TABLE `room_issue_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_issue_requests_booking_status_index` (`booking_id`,`status`),
  ADD KEY `room_issue_requests_customer_id_foreign` (`customer_id`),
  ADD KEY `room_issue_requests_current_room_id_foreign` (`current_room_id`),
  ADD KEY `room_issue_requests_current_category_id_foreign` (`current_room_category_id`),
  ADD KEY `room_issue_requests_approved_room_id_foreign` (`approved_room_id`),
  ADD KEY `room_issue_requests_approved_category_id_foreign` (`approved_room_category_id`),
  ADD KEY `room_issue_requests_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `room_issue_requests_repair_status_index` (`repair_status`),
  ADD KEY `room_issue_requests_repair_completed_by_foreign` (`repair_completed_by`),
  ADD KEY `idx_room_issue_group_uuid` (`group_uuid`),
  ADD KEY `idx_room_issue_workflow_status` (`workflow_status`),
  ADD KEY `idx_room_issue_proposed_room` (`proposed_room_id`),
  ADD KEY `fk_room_issue_proposal_creator` (`proposal_created_by`),
  ADD KEY `fk_room_issue_guest_responder` (`guest_responded_by`),
  ADD KEY `room_issue_requests_housekeeping_workflow_idx` (`workflow_status`,`housekeeping_verified_at`),
  ADD KEY `room_issue_requests_housekeeping_verified_by_idx` (`housekeeping_verified_by`);

--
-- Chỉ mục cho bảng `room_issue_room_holds`
--
ALTER TABLE `room_issue_room_holds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_issue_hold_room_active` (`room_id`,`expires_at`,`released_at`),
  ADD KEY `idx_issue_hold_group` (`group_uuid`),
  ADD KEY `idx_issue_hold_booking` (`booking_id`),
  ADD KEY `fk_issue_hold_user` (`held_by`),
  ADD KEY `idx_issue_hold_request` (`room_issue_request_id`);

--
-- Chỉ mục cho bảng `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `services_service_group_index` (`service_group`);

--
-- Chỉ mục cho bảng `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Chỉ mục cho bảng `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staffs_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `staffs_phone_unique` (`phone`),
  ADD UNIQUE KEY `staffs_cccd_unique` (`cccd`);

--
-- Chỉ mục cho bảng `staff_floor_assignments`
--
ALTER TABLE `staff_floor_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_floor_assignments_staff_status_idx` (`staff_id`,`status`),
  ADD KEY `staff_floor_assignments_floor_work_idx` (`floor_number`,`work_date`),
  ADD KEY `staff_floor_assignments_work_shift_idx` (`work_date`,`shift`),
  ADD KEY `staff_floor_assignments_assigned_by_idx` (`assigned_by`);

--
-- Chỉ mục cho bảng `staff_room_assignments`
--
ALTER TABLE `staff_room_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_room_assignments_staff_work_status_idx` (`staff_id`,`work_date`,`status`),
  ADD KEY `staff_room_assignments_room_work_idx` (`room_id`,`work_date`),
  ADD KEY `staff_room_assignments_task_status_idx` (`task_type`,`status`),
  ADD KEY `staff_room_assignments_assigned_by_idx` (`assigned_by`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `banned_words`
--
ALTER TABLE `banned_words`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT cho bảng `booking_cancellation_requests`
--
ALTER TABLE `booking_cancellation_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `booking_guests`
--
ALTER TABLE `booking_guests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT cho bảng `booking_guest_room_histories`
--
ALTER TABLE `booking_guest_room_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=518;

--
-- AUTO_INCREMENT cho bảng `booking_payments`
--
ALTER TABLE `booking_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT cho bảng `booking_promotions`
--
ALTER TABLE `booking_promotions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `booking_promotion_room_upgrades`
--
ALTER TABLE `booking_promotion_room_upgrades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `booking_promotion_service_offers`
--
ALTER TABLE `booking_promotion_service_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT cho bảng `booking_room_changes`
--
ALTER TABLE `booking_room_changes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `booking_service_items`
--
ALTER TABLE `booking_service_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `booking_staff_assignments`
--
ALTER TABLE `booking_staff_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `chat_assignment_logs`
--
ALTER TABLE `chat_assignment_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `chat_attachments`
--
ALTER TABLE `chat_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `chat_staff_presences`
--
ALTER TABLE `chat_staff_presences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `customer_credits`
--
ALTER TABLE `customer_credits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `customer_requests`
--
ALTER TABLE `customer_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `customer_request_attachments`
--
ALTER TABLE `customer_request_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `hotel_policies`
--
ALTER TABLE `hotel_policies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT cho bảng `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `promotion_room_upgrade_offers`
--
ALTER TABLE `promotion_room_upgrade_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `promotion_service_offers`
--
ALTER TABLE `promotion_service_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `room_action_logs`
--
ALTER TABLE `room_action_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT cho bảng `room_categories`
--
ALTER TABLE `room_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `room_category_amenities`
--
ALTER TABLE `room_category_amenities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `room_category_images`
--
ALTER TABLE `room_category_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `room_inspections`
--
ALTER TABLE `room_inspections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `room_inspection_revisions`
--
ALTER TABLE `room_inspection_revisions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT cho bảng `room_issue_attachments`
--
ALTER TABLE `room_issue_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `room_issue_requests`
--
ALTER TABLE `room_issue_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `room_issue_room_holds`
--
ALTER TABLE `room_issue_room_holds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT cho bảng `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `staff_floor_assignments`
--
ALTER TABLE `staff_floor_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `staff_room_assignments`
--
ALTER TABLE `staff_room_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `banned_words`
--
ALTER TABLE `banned_words`
  ADD CONSTRAINT `banned_words_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_refund_processed_by_fk` FOREIGN KEY (`refund_processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`),
  ADD CONSTRAINT `bookings_room_selection_handled_by_fk` FOREIGN KEY (`room_selection_handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_cancellation_requests`
--
ALTER TABLE `booking_cancellation_requests`
  ADD CONSTRAINT `booking_cancellation_requests_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_cancellation_requests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_cancellation_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_cancellation_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_guests`
--
ALTER TABLE `booking_guests`
  ADD CONSTRAINT `booking_guests_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_guests_booking_room_id_foreign` FOREIGN KEY (`booking_room_id`) REFERENCES `booking_rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_guests_guardian` FOREIGN KEY (`guardian_guest_id`) REFERENCES `booking_guests` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_guest_room_histories`
--
ALTER TABLE `booking_guest_room_histories`
  ADD CONSTRAINT `fk_guest_room_history_from` FOREIGN KEY (`from_booking_room_id`) REFERENCES `booking_rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_guest_room_history_guest` FOREIGN KEY (`booking_guest_id`) REFERENCES `booking_guests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guest_room_history_to` FOREIGN KEY (`to_booking_room_id`) REFERENCES `booking_rooms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD CONSTRAINT `booking_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD CONSTRAINT `booking_payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `booking_promotions`
--
ALTER TABLE `booking_promotions`
  ADD CONSTRAINT `booking_promotions_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_promotions_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_promotions_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_promotion_service_offers`
--
ALTER TABLE `booking_promotion_service_offers`
  ADD CONSTRAINT `bps_offers_booking_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bps_offers_booking_promo_fk` FOREIGN KEY (`booking_promotion_id`) REFERENCES `booking_promotions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bps_offers_promo_service_fk` FOREIGN KEY (`promotion_service_offer_id`) REFERENCES `promotion_service_offers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bps_offers_promotion_fk` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bps_offers_service_fk` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD CONSTRAINT `booking_rooms_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_rooms_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Các ràng buộc cho bảng `booking_room_changes`
--
ALTER TABLE `booking_room_changes`
  ADD CONSTRAINT `booking_room_changes_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_room_changes_booking_room_id_foreign` FOREIGN KEY (`booking_room_id`) REFERENCES `booking_rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_room_changes_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_room_changes_issue_id_foreign` FOREIGN KEY (`room_issue_request_id`) REFERENCES `room_issue_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_room_changes_new_category_id_foreign` FOREIGN KEY (`new_room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_room_changes_new_room_id_foreign` FOREIGN KEY (`new_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_room_changes_old_category_id_foreign` FOREIGN KEY (`old_room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_room_changes_old_room_id_foreign` FOREIGN KEY (`old_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `booking_service_items`
--
ALTER TABLE `booking_service_items`
  ADD CONSTRAINT `fk_booking_service_items_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_service_items_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Các ràng buộc cho bảng `booking_staff_assignments`
--
ALTER TABLE `booking_staff_assignments`
  ADD CONSTRAINT `booking_staff_assignments_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `booking_staff_assignments_booking_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_staff_assignments_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chat_assignment_logs`
--
ALTER TABLE `chat_assignment_logs`
  ADD CONSTRAINT `chat_assignment_logs_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_assignment_logs_from_staff_id_foreign` FOREIGN KEY (`from_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_assignment_logs_to_staff_id_foreign` FOREIGN KEY (`to_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `chat_attachments`
--
ALTER TABLE `chat_attachments`
  ADD CONSTRAINT `chat_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `chat_conversations_assigned_staff_id_foreign` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_conversations_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chat_conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `chat_staff_presences`
--
ALTER TABLE `chat_staff_presences`
  ADD CONSTRAINT `chat_staff_presences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `customer_credits`
--
ALTER TABLE `customer_credits`
  ADD CONSTRAINT `customer_credits_booking_fk` FOREIGN KEY (`source_booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_credits_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `customer_requests`
--
ALTER TABLE `customer_requests`
  ADD CONSTRAINT `customer_requests_booking_fk` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_requests_reviewer_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `customer_request_attachments`
--
ALTER TABLE `customer_request_attachments`
  ADD CONSTRAINT `customer_request_attachments_request_fk` FOREIGN KEY (`customer_request_id`) REFERENCES `customer_requests` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  ADD CONSTRAINT `hotel_reviews_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hotel_reviews_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hotel_reviews_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hotel_reviews_hidden_by_foreign` FOREIGN KEY (`hidden_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hotel_reviews_replied_by_foreign` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hotel_reviews_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hotel_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `promotion_service_offers`
--
ALTER TABLE `promotion_service_offers`
  ADD CONSTRAINT `promotion_service_offers_promotion_id_foreign` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_service_offers_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_action_logs`
--
ALTER TABLE `room_action_logs`
  ADD CONSTRAINT `room_action_logs_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_action_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `room_category_amenities`
--
ALTER TABLE `room_category_amenities`
  ADD CONSTRAINT `room_category_amenities_amenity_id_foreign` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_category_amenities_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_category_images`
--
ALTER TABLE `room_category_images`
  ADD CONSTRAINT `room_category_images_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_inspections`
--
ALTER TABLE `room_inspections`
  ADD CONSTRAINT `fk_room_inspections_admin_ack_by` FOREIGN KEY (`admin_acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_room_inspections_guest_consulted_by` FOREIGN KEY (`guest_consulted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_inspections_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_inspections_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_inspections_inspected_by_foreign` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_inspections_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  ADD CONSTRAINT `fk_inspection_items_guest_responded_by` FOREIGN KEY (`guest_responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inspection_items_rechecked_by` FOREIGN KEY (`rechecked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_inspection_items_room_inspection_id_foreign` FOREIGN KEY (`room_inspection_id`) REFERENCES `room_inspections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_inspection_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `room_inspection_revisions`
--
ALTER TABLE `room_inspection_revisions`
  ADD CONSTRAINT `fk_inspection_revisions_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inspection_revisions_inspection` FOREIGN KEY (`room_inspection_id`) REFERENCES `room_inspections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inspection_revisions_item` FOREIGN KEY (`room_inspection_item_id`) REFERENCES `room_inspection_items` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `room_issue_attachments`
--
ALTER TABLE `room_issue_attachments`
  ADD CONSTRAINT `room_issue_attachments_request_id_foreign` FOREIGN KEY (`room_issue_request_id`) REFERENCES `room_issue_requests` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_issue_requests`
--
ALTER TABLE `room_issue_requests`
  ADD CONSTRAINT `fk_room_issue_guest_responder` FOREIGN KEY (`guest_responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_room_issue_proposal_creator` FOREIGN KEY (`proposal_created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_room_issue_proposed_room` FOREIGN KEY (`proposed_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_issue_requests_approved_category_id_foreign` FOREIGN KEY (`approved_room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_issue_requests_approved_room_id_foreign` FOREIGN KEY (`approved_room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_issue_requests_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_issue_requests_current_category_id_foreign` FOREIGN KEY (`current_room_category_id`) REFERENCES `room_categories` (`id`),
  ADD CONSTRAINT `room_issue_requests_current_room_id_foreign` FOREIGN KEY (`current_room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `room_issue_requests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_issue_requests_housekeeping_verified_by_fk` FOREIGN KEY (`housekeeping_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_issue_requests_repair_completed_by_foreign` FOREIGN KEY (`repair_completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_issue_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `room_issue_room_holds`
--
ALTER TABLE `room_issue_room_holds`
  ADD CONSTRAINT `fk_issue_hold_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_hold_request` FOREIGN KEY (`room_issue_request_id`) REFERENCES `room_issue_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_hold_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_hold_user` FOREIGN KEY (`held_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `staffs`
--
ALTER TABLE `staffs`
  ADD CONSTRAINT `staffs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `staff_floor_assignments`
--
ALTER TABLE `staff_floor_assignments`
  ADD CONSTRAINT `staff_floor_assignments_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_floor_assignments_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `staff_room_assignments`
--
ALTER TABLE `staff_room_assignments`
  ADD CONSTRAINT `staff_room_assignments_assigned_by_fk` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_room_assignments_room_fk` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_room_assignments_staff_fk` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
