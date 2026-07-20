-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 13, 2026 lúc 01:05 PM
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
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(30) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `room_category_id` bigint(20) UNSIGNED NOT NULL,
  `booking_type` enum('overnight','hourly') NOT NULL DEFAULT 'overnight',
  `booking_mode` enum('advance','walk_in') NOT NULL DEFAULT 'advance',
  `booking_source` enum('user_online','reception') NOT NULL DEFAULT 'reception',
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `check_in_at` datetime NOT NULL,
  `check_out_at` datetime NOT NULL,
  `cleaning_buffer_minutes` int(11) NOT NULL DEFAULT 60,
  `actual_check_in` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `adult_count` int(11) NOT NULL DEFAULT 1,
  `child_count` int(11) NOT NULL DEFAULT 0,
  `room_quantity` int(11) NOT NULL DEFAULT 1,
  `prefer_adjacent_rooms` tinyint(1) NOT NULL DEFAULT 0,
  `subtotal_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estimated_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_arrival_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `late_arrival_hours` decimal(5,2) DEFAULT NULL,
  `late_arrival_policy` varchar(255) DEFAULT NULL,
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

INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `created_by`, `room_category_id`, `booking_type`, `booking_mode`, `booking_source`, `check_in_date`, `check_out_date`, `check_in_at`, `check_out_at`, `cleaning_buffer_minutes`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `room_quantity`, `prefer_adjacent_rooms`, `subtotal_amount`, `discount_amount`, `estimated_total`, `deposit_amount`, `late_arrival_fee`, `late_arrival_hours`, `late_arrival_policy`, `payment_status`, `status`, `note`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'BK202606090531488WW', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 1, 3, 1, 10800000.00, 0.00, 10800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-08 22:31:48', '2026-06-08 22:32:58'),
(2, 'BK20260609053403WG2', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 2, 1, 7200000.00, 0.00, 7200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'checked_out', NULL, NULL, '2026-06-08 22:34:03', '2026-06-09 00:28:43'),
(3, 'BK202606090535414T8', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 3600000.00, 0.00, 3600000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'pending', NULL, '2026-06-08 22:35:54', '2026-06-08 22:35:41', '2026-06-08 22:35:54'),
(4, 'BK20260609084552N4R', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 3, 1, 10800000.00, 0.00, 10800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:23:21', '2026-06-09 01:45:52', '2026-06-10 06:23:21'),
(5, 'BK20260610120420EOH', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 2, 1, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'confirmed', NULL, '2026-06-10 06:23:17', '2026-06-10 05:04:20', '2026-06-10 06:23:17'),
(6, 'BK202606101254125AV', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5000000.00, 0.00, 5000000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:23:14', '2026-06-10 05:54:12', '2026-06-10 06:23:14'),
(7, 'BK202606101323005VJ', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5000000.00, 0.00, 5000000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:23:11', '2026-06-10 06:23:00', '2026-06-10 06:23:11'),
(8, 'BK20260610132706BW9', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, '2026-06-11 15:33:00', 1, 0, 1, 0, 5100000.00, 0.00, 5100000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 14:45 - Đã yêu cầu kiểm tra phòng trước khi check-out.\r\n11/06/2026 15:10 - Admin duyệt phí hư hại phòng 401: +100.000đ.', NULL, '2026-06-10 06:27:06', '2026-06-11 08:33:00'),
(9, 'BK20260610132748PTD', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:28:57', '2026-06-10 06:27:48', '2026-06-10 06:28:57'),
(10, 'BK202606101329142YO', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:29:26', '2026-06-10 06:29:14', '2026-06-10 06:29:26'),
(11, 'BK260611ZSG78', 2, 4, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-16', '2026-06-12 14:00:00', '2026-06-16 12:00:00', 60, NULL, NULL, 6, 2, 3, 1, 14400000.00, 0.00, 14400000.00, 5000000.00, 0.00, NULL, NULL, 'partial', 'cancelled', '11/06/2026 13:33 - Đổi từ phòng 402 sang phòng 405. Lý do: hỏng điều hòa\n11/06/2026 16:44 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-11 06:14:43', '2026-06-11 09:44:11'),
(12, 'BK202606111345136LH', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 14:00:47', NULL, 1, 0, 1, 0, 1450000.00, 0.00, 1450000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 14:04 - Đã yêu cầu kiểm tra phòng trước khi check-out.\r\n11/06/2026 14:23 - Đã yêu cầu kiểm tra phòng trước khi check-out.\r\n11/06/2026 14:39 - Đã xác nhận hư hại phòng 102: +250.000đ.', NULL, '2026-06-11 06:45:13', '2026-06-11 07:45:36'),
(13, 'BK20260611153335DYG', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 15:33:51', '2026-06-11 15:37:35', 1, 0, 1, 0, 5100000.00, 0.00, 5100000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 15:33 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n11/06/2026 15:34 - Admin duyệt phí hư hại phòng 401: +100.000đ.', NULL, '2026-06-11 08:33:35', '2026-06-11 08:37:35'),
(14, 'BK20260611154510RKR', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 15:45:24', '2026-06-11 15:46:25', 1, 0, 1, 0, 4200000.00, 0.00, 4200000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 15:45 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n11/06/2026 15:45 - Admin duyệt phí hư hại phòng 103: +3.000.000đ.', NULL, '2026-06-11 08:45:10', '2026-06-11 08:46:25'),
(15, 'BK260611IWZGS', 3, 4, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 1200000.00, 0.00, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '11/06/2026 16:43 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-11 09:42:51', '2026-06-11 09:43:47'),
(16, 'BK20260611164508JNO', 1, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 16:46:23', '2026-06-11 16:46:51', 2, 1, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 16:46 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-11 09:45:08', '2026-06-11 09:46:51'),
(17, 'BK260611SHIVO', 3, 4, 2, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 16:46:59', '2026-06-11 16:47:56', 6, 2, 2, 1, 1950000.00, 0.00, 1950000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 16:47 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n11/06/2026 16:47 - Admin duyệt phí hư hại phòng 202: +150.000đ.', NULL, '2026-06-11 09:46:14', '2026-06-11 09:47:56'),
(18, 'BK260612XMDDG', 2, 4, 1, 'overnight', 'advance', 'reception', '2026-06-13', '2026-06-14', '2026-06-13 14:00:00', '2026-06-14 12:00:00', 60, NULL, NULL, 6, 0, 4, 1, 4800000.00, 0.00, 4800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-12 00:10:46', '2026-06-12 00:11:03'),
(19, 'BK26061269TGR', 2, 4, 1, 'overnight', 'advance', 'reception', '2026-06-13', '2026-06-14', '2026-06-13 14:00:00', '2026-06-14 12:00:00', 60, NULL, NULL, 6, 0, 6, 1, 7200000.00, 0.00, 7200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-12 00:15:08', '2026-06-12 00:15:37'),
(20, 'BK20260612073145TNT', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-13', '2026-06-14', '2026-06-13 14:00:00', '2026-06-14 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-12 00:31:45', '2026-06-12 00:32:02'),
(21, 'BK20260613084707AIF', 4, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-14', '2026-06-15', '2026-06-14 14:00:00', '2026-06-15 12:00:00', 60, '2026-06-13 08:47:28', '2026-06-13 08:48:19', 2, 0, 1, 0, 5200000.00, 0.00, 5200000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '13/06/2026 08:47 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n13/06/2026 08:48 - Admin duyệt phí hư hại phòng 401: +200.000đ.', NULL, '2026-06-13 01:47:07', '2026-06-13 01:48:19'),
(22, 'BK20260614022738ATJ', 4, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-15', '2026-06-16', '2026-06-15 14:00:00', '2026-06-16 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 15:59 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-13 19:27:38', '2026-06-15 08:59:31'),
(23, 'BK20260614023320K98', 5, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-15', '2026-06-16', '2026-06-15 14:00:00', '2026-06-16 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 15:59 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-13 19:33:20', '2026-06-15 08:59:22'),
(24, 'BK202606151600037SQ', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-15 09:00:03', '2026-06-15 09:00:37'),
(25, 'BK20260615161102IP0', 4, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, '2026-06-15 19:36:23', NULL, 6, 0, 1, 0, 5400000.00, 0.00, 5400000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 17:12 - Check-in thực tế: 4 người lớn / 0 trẻ em. Khách vượt sức chứa, đã đổi sang hạng phòng Presidential Suite. Lý do: Vượt sức chứa khi check-in.\r\n15/06/2026 19:27 - Check-in thực tế: 7 người lớn / 0 trẻ em. Khách vượt sức chứa, giữ nguyên phòng và thu phụ phí 400.000đ.\r\n15/06/2026 19:36 - Check-in thực tế: 6 người lớn / 0 trẻ em.', NULL, '2026-06-15 09:11:02', '2026-06-15 12:36:41'),
(26, 'BK20260615193744EQZ', 4, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-15 19:38:17', NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 19:38 - Check-in thực tế: 1 người lớn / 0 trẻ em.', NULL, '2026-06-15 12:37:44', '2026-06-15 12:40:42'),
(27, 'BK20260615194053GFA', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 03:07:20', NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 900000.00, 7.14, 'Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm.', 'unpaid', 'cancelled', '16/06/2026 02:59 - Hủy phòng do khách đến muộn quá 6 giờ, từ chối hoàn tiền cọc.\r\n16/06/2026 03:00 - Check-in thực tế: 1 người lớn / 0 trẻ em.  Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 900.000đ.\r\n16/06/2026 03:07 - Check-in thực tế: 1 người lớn / 0 trẻ em.  Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 900.000đ.', NULL, '2026-06-15 12:40:53', '2026-06-15 21:59:35'),
(28, 'BK20260616033519CFA', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, '2026-06-16 03:35:32', '2026-06-16 03:39:25', 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 03:35 - Check-in thực tế: 1 người lớn / 0 trẻ em. \n16/06/2026 03:38 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-15 20:35:19', '2026-06-15 20:39:25'),
(29, 'BK260616ODKK0', 7, 4, 2, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, '2026-06-16 05:43:23', '2026-06-16 13:21:18', 4, 0, 1, 0, 2184000.00, 0.00, 2184000.00, 0.00, 180000.00, 3.13, 'Khách đến muộn từ 2 đến dưới 4 giờ, phụ thu 20% giá/đêm.', 'paid', 'checked_out', '16/06/2026 05:10 - Đổi từ phòng 202 sang phòng 201. Lý do: Hỏng đèn\n16/06/2026 05:43 - Check-in thực tế: 4 người lớn / 0 trẻ em / 0 em bé. Đã thu phụ phí phát sinh khi check-in: Phụ thu thêm người lớn x 2: 400.000đ. Tổng phụ thu: 400.000đ. Khách đến muộn từ 2 đến dưới 4 giờ, phụ thu 20% giá/đêm. Số tiền phụ thu: 180.000đ.\n16/06/2026 11:54 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n16/06/2026 13:20 - Admin duyệt phí hư hại phòng 201: +150.000đ.', NULL, '2026-06-15 21:04:23', '2026-06-16 06:21:18'),
(30, 'BK20260616132210LAT', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 13:22:39', '2026-06-16 13:39:07', 1, 0, 1, 0, 984000.00, 0.00, 984000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 13:22 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. \n16/06/2026 13:22 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n16/06/2026 13:23 - Admin duyệt phí hư hại phòng 201: +70.000đ.', NULL, '2026-06-16 06:22:10', '2026-06-16 06:39:07'),
(31, 'BK20260616133921JRE', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 13:39:53', '2026-06-16 13:55:04', 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 13:39 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. \n16/06/2026 13:54 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-16 06:39:21', '2026-06-16 06:55:04'),
(32, 'BK20260616135547IMG', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 14:02:42', '2026-06-16 14:24:33', 1, 0, 1, 0, 1350000.00, 0.00, 1350000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 14:02 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. \n16/06/2026 14:23 - Đổi từ phòng 203 sang phòng 201. Lý do: Đổi phòng và gia hạn cho khách\n16/06/2026 14:23 - Gia hạn lưu trú từ 18/06/2026 11:00 đến 18/06/2026 15:00. Gia hạn thêm 4 giờ, phụ thu 50% giá/đêm. Phụ thu: 450.000đ.\n16/06/2026 14:24 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-16 06:55:47', '2026-06-16 07:24:33'),
(33, 'BK260616PBMOZ', 8, 4, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 03:08 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-16 07:02:26', '2026-06-17 20:08:49'),
(34, 'BK20260618011733YM4', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-17 18:17:33', '2026-06-17 18:18:16'),
(35, 'BK20260618012232J3A', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1260000.00, 0.00, 1260000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 12:26 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-17 18:22:32', '2026-06-18 05:26:27'),
(36, 'BK260618QNDUZ', 9, 4, 1, 'hourly', 'advance', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-18 11:42:14', '2026-06-18 13:13:25', 2, 0, 1, 0, 2640000.00, 0.00, 2640000.00, 0.00, 1200000.00, 8.70, 'Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm.', 'paid', 'checked_out', '18/06/2026 11:42 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé.  Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 1.200.000đ.\n18/06/2026 12:29 - Gia hạn lưu trú từ 18/06/2026 09:00 đến 18/06/2026 15:00. Booking theo giờ, gia hạn thêm 6 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 120.000đ/giờ. Chuyển phòng 103 → 101 cùng hạng Deluxe Sea View. Phụ thu: 720.000đ.\n18/06/2026 13:12 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-17 19:55:46', '2026-06-18 06:13:25'),
(37, 'BK260618TEG6J', 10, 4, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 14:09 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-17 19:57:47', '2026-06-18 07:09:02'),
(38, 'BK260618GSSGF', 11, 4, 3, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 03:08 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-17 20:07:51', '2026-06-17 20:08:03'),
(39, 'BK20260618122651N35', 4, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1200000.00, 0.00, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-18 05:26:51', '2026-06-18 05:27:00'),
(40, 'BK20260618122747XPM', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 2, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 13:34 - Đã đổi toàn bộ booking sang hạng phòng Superior Double. Lý do: Khách yêu cầu đổi hạng phòng.\n18/06/2026 13:52 - Đã thêm 1 phòng hạng Superior Double vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.\n18/06/2026 13:52 - Đã đổi phòng 201 từ hạng Superior Double sang phòng 103 hạng Deluxe Sea View. Chênh lệch tiền phòng: 300.000đ. Lý do: Khách yêu cầu đổi 1 phòng.\n18/06/2026 14:03 - Đã đổi phòng 103 từ hạng Deluxe Sea View sang phòng 201 hạng Superior Double. Chênh lệch tiền phòng: -300.000đ. Lý do: Khách yêu cầu đổi 1 phòng.\n18/06/2026 16:27 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-18 05:27:47', '2026-06-18 09:27:53'),
(41, 'BK260618C7BGC', 12, 4, 2, 'hourly', 'walk_in', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-18 16:42:47', '2026-06-18 19:51:14', 1, 0, 1, 0, 270000.00, 0.00, 270000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 16:42 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n18/06/2026 19:50 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-18 09:40:30', '2026-06-18 12:51:14'),
(42, 'BK260618TGJ8P', 13, 4, 2, 'hourly', 'walk_in', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 14:00:00', '2026-06-18 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 270000.00, 0.00, 270000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 17:00 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-18 09:56:08', '2026-06-18 10:00:16'),
(43, 'BK20260618173207IPM', 4, NULL, 1, 'overnight', 'advance', 'user_online', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1200000.00, 0.00, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 17:35 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-18 10:32:07', '2026-06-18 10:35:14'),
(44, 'BK20260618173544HLO', 4, NULL, 1, 'overnight', 'advance', 'user_online', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, '2026-06-18 17:36:26', '2026-06-18 17:37:00', 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 600000.00, 3.61, 'Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm.', 'paid', 'checked_out', '18/06/2026 17:36 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm. Số tiền phụ thu: 600.000đ.\n18/06/2026 17:36 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-18 10:35:44', '2026-06-18 10:37:00'),
(45, 'BK20260618184642DNU', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, '2026-06-18 20:03:27', '2026-06-18 20:04:58', 2, 0, 1, 0, 1140000.00, 0.00, 1140000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 20:03 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n18/06/2026 20:04 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-18 11:46:42', '2026-06-18 13:04:58'),
(46, 'BK2606182FY12', 16, 4, 3, 'hourly', 'walk_in', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-18 19:57:54', '2026-06-19 21:57:46', 1, 0, 1, 0, 6240000.00, 0.00, 6240000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 19:57 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n19/06/2026 21:57 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 21:57 - Check-out thực tế. Tổng phải thu: 6.240.000đ. Cọc: 0đ. Còn lại đã thu: 6.240.000đ. Phí phát sinh: Phụ thu check-out muộn: 5.520.000đ. Booking theo giờ trả muộn 22.96 giờ, tính thêm 23 giờ theo đơn giá tạm tính 240.000đ/giờ.', NULL, '2026-06-18 12:52:52', '2026-06-19 14:57:46'),
(47, 'BK202606182043185FJ', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5300000.00, 0.00, 5300000.00, 0.00, 0.00, 7.10, 'No-show sau 18:00, không liên lạc được. Chưa ghi nhận tiền cọc trên hệ thống; lễ tân kiểm tra lại thanh toán nếu có.', 'unpaid', 'cancelled', '19/06/2026 21:05 - Hủy no-show do khách chưa đến sau 18:00 và không liên lạc được. Chưa ghi nhận tiền cọc trên hệ thống; lễ tân kiểm tra lại thanh toán nếu có. Phòng được mở bán lại.', NULL, '2026-06-18 13:43:18', '2026-06-19 14:05:50'),
(48, 'BK20260618204608QO7', 14, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-19', '2026-06-20 14:00:00', '2026-06-19 12:00:00', 60, '2026-06-18 20:48:53', '2026-06-19 21:55:44', 2, 0, 1, 0, 15000000.00, 0.00, 15000000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 20:48 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n18/06/2026 20:56 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 21:55 - Check-out thực tế. Tổng phải thu: 15.000.000đ. Cọc: 0đ. Còn lại đã thu: 15.000.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-18 13:46:08', '2026-06-19 14:55:44'),
(49, 'BK20260619211657P59', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-21', '2026-06-20 14:00:00', '2026-06-21 12:00:00', 60, '2026-06-19 21:53:14', '2026-06-19 21:54:44', 1, 0, 1, 0, 1800000.00, 0.00, 1800000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '19/06/2026 21:53 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Check-in sớm lúc 19/06/2026 21:53, sớm hơn giờ chuẩn 16 giờ 6 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 900.000đ.\n19/06/2026 21:53 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 21:54 - Check-out thực tế. Tổng phải thu: 1.800.000đ. Cọc: 0đ. Còn lại đã thu: 1.800.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-19 14:16:57', '2026-06-19 14:54:44'),
(50, 'BK20260619221047JWD', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-21', '2026-06-20 14:00:00', '2026-06-21 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5000000.00, 0.00, 5000000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-19 15:10:47', '2026-06-19 15:14:07'),
(51, 'BK260619TWVKJ', 17, 4, 4, 'hourly', 'walk_in', 'reception', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, '2026-06-19 22:13:23', '2026-06-19 22:15:49', 1, 0, 1, 0, 5674157.00, 0.00, 5674157.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '19/06/2026 22:14 - Gia hạn lưu trú từ 20/06/2026 13:00 đến 20/06/2026 15:00. Booking theo giờ, gia hạn thêm 2 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 337.079đ/giờ. Phụ thu: 674.157đ.\n19/06/2026 22:14 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 22:15 - Check-out thực tế. Tổng phải thu: 5.674.157đ. Cọc: 0đ. Còn lại đã thu: 5.674.157đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-19 15:13:23', '2026-06-19 15:15:49'),
(52, 'BK20260619221619QVP', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-21', '2026-06-20 14:00:00', '2026-06-21 12:00:00', 60, '2026-06-19 22:16:43', '2026-06-19 23:00:07', 1, 0, 1, 0, 10750000.00, 0.00, 10750000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '19/06/2026 22:16 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Check-in sớm lúc 19/06/2026 22:16, sớm hơn giờ chuẩn 15 giờ 43 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 5.000.000đ.\n19/06/2026 22:58 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 22:58 - Admin duyệt kiểm tra phòng 401: dịch vụ tại phòng +50.000đ, hư hại +50.000đ. Tổng cộng +100.000đ.\n19/06/2026 23:00 - Check-out thực tế. Tổng phải thu: 10.750.000đ. Cọc: 0đ. Còn lại đã thu: 10.750.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-19 15:16:19', '2026-06-19 16:00:07'),
(53, 'BK20260620144210BEP', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-21', '2026-06-22', '2026-06-21 14:00:00', '2026-06-22 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-20 07:42:10', '2026-06-20 07:42:46'),
(54, 'BK202606201454387BA', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-21', '2026-06-22', '2026-06-21 14:00:00', '2026-06-22 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-20 07:54:38', '2026-06-21 09:58:30'),
(55, 'BK20260621145034HUC', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '\nH?y booking test VNPay chýa thanh toán.', NULL, '2026-06-21 07:50:34', '2026-06-21 07:50:34'),
(56, 'BK20260621154514LSJ', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1200000.00, 0.00, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '\nH?y booking test VNPay l?i ch? k?.', NULL, '2026-06-21 08:45:14', '2026-06-21 08:45:14'),
(57, 'BK20260621160902USY', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '\nH?y booking test VNPay l?i ch? k?.', NULL, '2026-06-21 09:09:02', '2026-06-21 09:09:02'),
(58, 'BK202606211610574CW', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-21 09:10:57', '2026-06-21 09:54:57'),
(59, 'BK20260621165522C1G', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 900000.00, 0.00, NULL, NULL, 'paid', 'cancelled', NULL, NULL, '2026-06-21 09:55:22', '2026-06-21 09:58:20'),
(60, 'BK20260621170026NHW', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-21 10:00:26', '2026-06-21 10:00:51'),
(61, 'BK202606211717001PU', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1120000.00, 0.00, 1120000.00, 270000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-21 10:17:00', '2026-06-21 14:13:49'),
(62, 'BK20260621211437VTS', 4, NULL, 3, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-23', '2026-06-22 14:00:00', '2026-06-23 12:00:00', 60, '2026-06-21 23:46:53', '2026-06-22 10:41:26', 1, 0, 1, 0, 1240000.00, 680000.00, 3940000.00, 243000.00, 0.00, NULL, NULL, 'paid', 'checked_out', '21/06/2026 23:31 - Đã đổi toàn bộ booking sang hạng phòng Family Suite. Chênh lệch tiền phòng: 900.000đ. Lý do: Khách yêu cầu đổi toàn bộ hạng phòng.\n21/06/2026 23:46 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm lúc 21/06/2026 23:46, sớm hơn giờ chuẩn 14 giờ 13 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 1.800.000đ.\n22/06/2026 10:40 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n22/06/2026 10:41 - Check-out thực tế. Tổng phải thu: 3.940.000đ. Cọc: 243.000đ. Còn lại đã thu: 3.697.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-21 14:14:37', '2026-06-22 03:41:26'),
(63, 'BK20260622104650PIF', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-24', '2026-06-26', '2026-06-24 14:00:00', '2026-06-26 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 10180000.00, 380000.00, 9800000.00, 2940000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-22 03:46:50', '2026-06-22 04:03:41'),
(64, 'BK20260622105342WAB', 14, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-26', '2026-06-29', '2026-06-26 14:00:00', '2026-06-29 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 15180000.00, 380000.00, 14800000.00, 14800000.00, 0.00, NULL, NULL, 'paid', 'cancelled', 'Đã ghi nhận thanh toán VNPay nhưng hệ thống không còn phòng trống để tự động gán. Cần lễ tân xử lý thủ công.', NULL, '2026-06-22 03:53:42', '2026-06-22 04:05:18'),
(65, 'BK20260622110355MWQ', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-26', '2026-06-30', '2026-06-26 14:00:00', '2026-06-30 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 20000000.00, 0.00, 20000000.00, 6000000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-22 04:03:55', '2026-06-22 08:04:45'),
(66, 'BK20260622110638MYQ', 14, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-23', '2026-06-26', '2026-06-23 14:00:00', '2026-06-26 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 15000000.00, 0.00, 15000000.00, 4500000.00, 0.00, 48.38, 'Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 24/06/2026 14:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 4.500.000đ.', 'partial', 'cancelled', '25/06/2026 14:23 - Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 24/06/2026 14:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 4.500.000đ. Phòng được mở bán lại.', NULL, '2026-06-22 04:06:38', '2026-06-25 07:23:01'),
(67, 'BK20260622150521YKX', 4, NULL, 3, 'overnight', 'advance', 'user_online', '2026-06-22', '2026-06-24', '2026-06-22 14:00:00', '2026-06-24 12:00:00', 60, '2026-06-22 16:15:39', '2026-06-22 16:23:54', 1, 0, 1, 0, 3600000.00, 0.00, 3600000.00, 540000.00, 0.00, 0.01, 'Booking nhiều đêm, khách cọc một phần/chưa thanh toán đủ và vẫn trong hạn giữ phòng 1 ngày đến 23/06/2026 16:15. Cho check-in bình thường, không phụ thu check-in muộn. Giữ nguyên ngày trả phòng ban đầu.', 'paid', 'checked_out', '22/06/2026 16:15 - Đổi ngày lưu trú từ 23/06/2026 14:00 → 24/06/2026 12:00 sang 22/06/2026 16:15 → 24/06/2026 12:00. Chênh lệch tiền phòng: 1.800.000đ.\n22/06/2026 16:15 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking nhiều đêm, khách cọc một phần/chưa thanh toán đủ và vẫn trong hạn giữ phòng 1 ngày đến 23/06/2026 16:15. Cho check-in bình thường, không phụ thu check-in muộn. Giữ nguyên ngày trả phòng ban đầu.\n22/06/2026 16:23 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n22/06/2026 16:23 - Check-out thực tế. Tổng phải thu: 3.600.000đ. Cọc: 540.000đ. Còn lại đã thu: 3.060.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-22 08:05:21', '2026-06-22 09:23:54'),
(68, 'BK20260622163249NQJ', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-23', '2026-06-24', '2026-06-23 14:00:00', '2026-06-24 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-22 09:32:49', '2026-06-22 09:58:58'),
(69, 'BK20260623111437BIR', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-23', '2026-06-24', '2026-06-23 14:00:00', '2026-06-24 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 900000.00, 0.00, NULL, NULL, 'paid', 'cancelled', NULL, NULL, '2026-06-23 04:14:37', '2026-06-23 06:20:04'),
(70, 'BK20260623132045HY1', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-23', '2026-06-24', '2026-06-23 14:00:00', '2026-06-24 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-23 06:20:45', '2026-06-23 06:28:47'),
(71, 'BK202606231329044B1', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-23', '2026-06-24', '2026-06-23 14:00:00', '2026-06-24 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-23 06:29:04', '2026-06-23 06:36:15'),
(72, 'BK20260623133959OXO', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-23', '2026-06-24', '2026-06-23 14:00:00', '2026-06-24 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, NULL, NULL, 'partial', 'cancelled', NULL, NULL, '2026-06-23 06:39:59', '2026-06-23 08:16:42'),
(73, 'BK20260630113239BS2', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-30', '2026-07-01', '2026-06-30 14:00:00', '2026-07-01 12:00:00', 60, '2026-06-30 11:34:13', '2026-06-30 11:35:05', 2, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, NULL, NULL, 'paid', 'checked_out', '30/06/2026 11:34 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 30/06/2026 11:34, sớm hơn giờ chuẩn 2 giờ 25 phút. Check-in sớm cùng ngày từ 11:00 đến trước 14:00, miễn phí nếu phòng đã sẵn sàng. Phụ thu: 0đ.\n30/06/2026 11:34 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n30/06/2026 11:35 - Check-out thực tế. Tổng phải thu: 900.000đ. Cọc: 270.000đ. Còn lại đã thu: 630.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-30 04:32:39', '2026-06-30 04:35:05'),
(74, 'BK20260630160020FTF', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-01', '2026-07-02', '2026-07-01 14:00:00', '2026-07-02 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, 221.31, 'Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 01/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ.', 'partial', 'cancelled', '10/07/2026 19:18 - Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 01/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ. Phòng được mở bán lại.', NULL, '2026-06-30 09:00:20', '2026-07-10 12:18:30'),
(75, 'BK20260630160322EMC', 14, NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-02', '2026-07-03', '2026-07-02 14:00:00', '2026-07-03 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, 197.31, 'Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 02/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ.', 'partial', 'cancelled', '10/07/2026 19:18 - Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 02/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ. Phòng được mở bán lại.', NULL, '2026-06-30 09:03:22', '2026-07-10 12:18:38'),
(76, 'BK260710MLVBG', 18, 4, 1, 'hourly', 'walk_in', 'reception', '2026-07-10', '2026-07-10', '2026-07-10 19:29:00', '2026-07-10 21:29:00', 60, '2026-07-10 19:30:45', '2026-07-10 19:36:12', 1, 0, 1, 0, 607000.00, 0.00, 707000.00, 707000.00, 0.00, NULL, NULL, 'paid', 'checked_out', '10/07/2026 19:35 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n10/07/2026 19:35 - Admin duyệt kiểm tra phòng 402: hư hại +50.000đ. Tổng cộng +50.000đ.\n10/07/2026 19:36 - Check-out thực tế. Tổng phải thu: 707.000đ. Đã thu trước check-out: 0đ. Còn lại khi check-out: 707.000đ. Thu thêm khi check-out: 707.000đ bằng tiền mặt tại quầy. Mã giao dịch: CASHOUTBK260710MLVBG20260710193612A9EWR. Không phát sinh phụ thu check-out.', NULL, '2026-07-10 12:30:45', '2026-07-10 12:36:12'),
(77, 'BK20260711131605DQF', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-07-11', '2026-07-12', '2026-07-11 14:00:00', '2026-07-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 900000.00, 270000.00, 0.00, 5.61, 'Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 11/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ.', 'partial', 'cancelled', '11/07/2026 19:36 - Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 11/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ. Phòng được mở bán lại.', NULL, '2026-07-11 06:16:05', '2026-07-11 12:36:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_guests`
--

CREATE TABLE `booking_guests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `cccd` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 28, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em. ', '2026-06-15 20:35:32', '2026-06-15 20:35:32'),
(2, 28, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 203.', '2026-06-15 20:38:23', '2026-06-15 20:38:23'),
(3, 28, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 203: không có hư hại. Chờ admin duyệt.', '2026-06-15 20:38:44', '2026-06-15 20:38:44'),
(4, 28, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 203. Phí hư hại được duyệt: 0đ.', '2026-06-15 20:38:53', '2026-06-15 20:38:53'),
(5, 28, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 203. Thanh toán chuyển sang đã thanh toán.', '2026-06-15 20:39:25', '2026-06-15 20:39:25'),
(6, 29, 4, 'booking_created', 'Tạo booking bởi lễ tân. Gán phòng: 202. Tổng tiền tạm tính: 1.217.000đ.', '2026-06-15 21:04:23', '2026-06-15 21:04:23'),
(7, 29, 4, 'service_added', 'Thêm dịch vụ \"Ăn sáng buffet\" x 1. Thành tiền: 120.000đ.', '2026-06-15 21:04:50', '2026-06-15 21:04:50'),
(8, 29, 4, 'service_added', 'Thêm dịch vụ \"Đưa đón sân bay\" x 1. Thành tiền: 300.000đ.', '2026-06-15 21:05:04', '2026-06-15 21:05:04'),
(9, 29, 4, 'service_removed', 'Xóa dịch vụ \"Đưa đón sân bay\". Trừ khỏi tổng tiền: 300.000đ.', '2026-06-15 21:13:46', '2026-06-15 21:13:46'),
(10, 29, 4, 'service_added', 'Thêm dịch vụ \"Bia\" x 2. Thành tiền: 20.000đ.', '2026-06-15 21:19:53', '2026-06-15 21:19:53'),
(11, 29, 4, 'service_added', 'Thêm dịch vụ \"Nước suối\" x 1. Thành tiền: 7.000đ.', '2026-06-15 21:44:16', '2026-06-15 21:44:16'),
(12, 29, 4, 'payment_update', 'Cập nhật thanh toán từ unpaid sang paid.', '2026-06-15 21:57:10', '2026-06-15 21:57:10'),
(13, 29, 4, 'payment_update', 'Cập nhật thanh toán từ paid sang unpaid.', '2026-06-15 21:57:14', '2026-06-15 21:57:14'),
(14, 29, 4, 'payment_update', 'Cập nhật thanh toán từ unpaid sang paid.', '2026-06-15 21:59:20', '2026-06-15 21:59:20'),
(15, 27, 4, 'payment_update', 'Cập nhật thanh toán từ unpaid sang partial.', '2026-06-15 21:59:26', '2026-06-15 21:59:26'),
(16, 27, 4, 'payment_update', 'Cập nhật thanh toán từ partial sang refunded.', '2026-06-15 21:59:35', '2026-06-15 21:59:35'),
(17, 29, 4, 'service_added', 'Thêm dịch vụ \"Ăn sáng buffet\" x 1. Thành tiền: 120.000đ.', '2026-06-15 22:03:26', '2026-06-15 22:03:26'),
(18, 29, 4, 'service_quantity_updated', 'Cập nhật số lượng \"Bia\" từ 3 sang 1. Chênh lệch: -20.000đ.', '2026-06-15 22:07:04', '2026-06-15 22:07:04'),
(19, 29, 4, 'change_room', 'Đổi từ phòng 202 sang phòng 201. Lý do: Hỏng đèn. Trạng thái phòng cũ: maintenance.', '2026-06-15 22:10:22', '2026-06-15 22:10:22'),
(20, 29, 4, 'check_in', 'Xác nhận check-in thực tế: 4 người lớn / 0 trẻ em / 0 em bé. Đã thu phụ phí phát sinh khi check-in: Phụ thu thêm người lớn x 2: 400.000đ. Tổng phụ thu: 400.000đ. Khách đến muộn từ 2 đến dưới 4 giờ, phụ thu 20% giá/đêm. Số tiền phụ thu: 180.000đ.', '2026-06-15 22:43:23', '2026-06-15 22:43:23'),
(21, 29, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-16 04:54:09', '2026-06-16 04:54:09'),
(22, 29, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: có hư hại, tổng tạm tính 150.000đ. Chờ admin duyệt.', '2026-06-16 04:54:49', '2026-06-16 04:54:49'),
(23, 29, 4, 'service_removed', 'Xóa dịch vụ \"Bia\". Trừ khỏi tổng tiền: 10.000đ.', '2026-06-16 06:08:02', '2026-06-16 06:08:02'),
(24, 29, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: có hư hại, tổng tạm tính 150.000đ. Chờ admin duyệt.', '2026-06-16 06:08:31', '2026-06-16 06:08:31'),
(25, 29, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: có hư hại, tổng tạm tính 150.000đ. Chờ admin duyệt.', '2026-06-16 06:20:21', '2026-06-16 06:20:21'),
(26, 29, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Phí hư hại được duyệt: 150.000đ. Mục duyệt: Vỡ ly thủy tinh x3 = 150.000đ.', '2026-06-16 06:20:35', '2026-06-16 06:20:35'),
(27, 29, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 201. Thanh toán chuyển sang đã thanh toán.', '2026-06-16 06:21:18', '2026-06-16 06:21:18'),
(28, 30, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. ', '2026-06-16 06:22:39', '2026-06-16 06:22:39'),
(29, 30, 4, 'service_added', 'Thêm dịch vụ \"Nước suối\" x 1. Thành tiền: 0đ.', '2026-06-16 06:22:49', '2026-06-16 06:22:49'),
(30, 30, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-16 06:22:58', '2026-06-16 06:22:58'),
(31, 30, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: có hư hại, tổng tạm tính 50.000đ. Chờ admin duyệt.', '2026-06-16 06:23:23', '2026-06-16 06:23:23'),
(32, 30, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Phí hư hại được duyệt: 70.000đ. Mục duyệt: Vỡ ly thủy tinh x1 = 50.000đ; Bia x2 = 20.000đ.', '2026-06-16 06:23:43', '2026-06-16 06:23:43'),
(33, 30, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 201. Thanh toán chuyển sang đã thanh toán.', '2026-06-16 06:39:07', '2026-06-16 06:39:07'),
(34, 31, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. ', '2026-06-16 06:39:53', '2026-06-16 06:39:53'),
(35, 31, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 203.', '2026-06-16 06:54:42', '2026-06-16 06:54:42'),
(36, 31, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 203: không có hư hại. Chờ admin duyệt.', '2026-06-16 06:54:52', '2026-06-16 06:54:52'),
(37, 31, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 203. Phí hư hại được duyệt: 0đ.', '2026-06-16 06:54:58', '2026-06-16 06:54:58'),
(38, 31, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 203. Thanh toán chuyển sang đã thanh toán.', '2026-06-16 06:55:04', '2026-06-16 06:55:04'),
(39, 33, 4, 'booking_created', 'Tạo booking bởi lễ tân. Gán phòng: 203. Tổng tiền tạm tính: 900.000đ.', '2026-06-16 07:02:26', '2026-06-16 07:02:26'),
(40, 32, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. ', '2026-06-16 07:02:42', '2026-06-16 07:02:42'),
(41, 32, 4, 'change_room', 'Đổi từ phòng 203 sang phòng 201. Lý do: Đổi phòng và gia hạn cho khách. Trạng thái phòng cũ: available.', '2026-06-16 07:23:48', '2026-06-16 07:23:48'),
(42, 32, 4, 'extend_stay', 'Gia hạn lưu trú từ 18/06/2026 11:00 đến 18/06/2026 15:00. Gia hạn thêm 4 giờ, phụ thu 50% giá/đêm. Phụ thu: 450.000đ.', '2026-06-16 07:23:57', '2026-06-16 07:23:57'),
(43, 32, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-16 07:24:10', '2026-06-16 07:24:10'),
(44, 32, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: không có hư hại. Chờ admin duyệt.', '2026-06-16 07:24:19', '2026-06-16 07:24:19'),
(45, 32, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Phí hư hại được duyệt: 0đ.', '2026-06-16 07:24:26', '2026-06-16 07:24:26'),
(46, 32, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 201. Thanh toán chuyển sang đã thanh toán.', '2026-06-16 07:24:33', '2026-06-16 07:24:33'),
(47, 35, 4, 'service_added', 'Thêm dịch vụ \"Ăn sáng buffet\" x 1. Thành tiền: 120.000đ.', '2026-06-17 18:41:11', '2026-06-17 18:41:11'),
(48, 35, 4, 'service_added', 'Thêm dịch vụ/minibar vào booking: Ăn sáng buffet x 2 = 240.000đ. Tổng cộng thêm: 240.000đ.', '2026-06-17 19:07:26', '2026-06-17 19:07:26'),
(49, 35, 4, 'service_added', 'Thêm dịch vụ/minibar vào booking: Coca Cola x 3 = 0đ. Tổng cộng thêm: 0đ.', '2026-06-17 19:07:38', '2026-06-17 19:07:38'),
(50, 36, 4, 'booking_created', 'Tạo booking theo giờ bởi lễ tân. Gán phòng: 103. Thời gian: 18/06/2026 03:00 - 18/06/2026 09:00. Tổng tiền tạm tính: 720.000đ.', '2026-06-17 19:55:46', '2026-06-17 19:55:46'),
(51, 37, 4, 'booking_created', 'Tạo booking qua đêm bởi lễ tân. Gán phòng: 202. Thời gian: 18/06/2026 14:00 - 19/06/2026 12:00. Tổng tiền tạm tính: 900.000đ.', '2026-06-17 19:57:47', '2026-06-17 19:57:47'),
(52, 38, 4, 'booking_created', 'Tạo booking qua đêm bởi lễ tân. Gán phòng: 302. Thời gian: 18/06/2026 14:00 - 19/06/2026 12:00. Tổng tiền tạm tính: 1.800.000đ.', '2026-06-17 20:07:51', '2026-06-17 20:07:51'),
(53, 36, 4, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 1.200.000đ.', '2026-06-18 04:42:14', '2026-06-18 04:42:14'),
(54, 36, 4, 'extend_stay', 'Gia hạn lưu trú từ 18/06/2026 09:00 đến 18/06/2026 15:00. Booking theo giờ, gia hạn thêm 6 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 120.000đ/giờ. Chuyển phòng 103 → 101 cùng hạng Deluxe Sea View. Phụ thu: 720.000đ.', '2026-06-18 05:29:36', '2026-06-18 05:29:36'),
(55, 36, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-06-18 06:12:52', '2026-06-18 06:12:52'),
(56, 36, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: không có hư hại. Chờ admin duyệt.', '2026-06-18 06:13:03', '2026-06-18 06:13:03'),
(57, 36, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Phí hư hại được duyệt: 0đ.', '2026-06-18 06:13:10', '2026-06-18 06:13:10'),
(58, 36, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 101. Thanh toán chuyển sang đã thanh toán.', '2026-06-18 06:13:25', '2026-06-18 06:13:25'),
(59, 40, 4, 'change_booking_category', 'Đã đổi toàn bộ booking sang hạng phòng Superior Double. Lý do: Khách yêu cầu đổi hạng phòng.', '2026-06-18 06:34:15', '2026-06-18 06:34:15'),
(60, 40, 4, 'add_room_to_booking', 'Đã thêm 1 phòng hạng Superior Double vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.', '2026-06-18 06:52:17', '2026-06-18 06:52:17'),
(61, 40, 4, 'change_one_room_category', 'Đã đổi phòng 201 từ hạng Superior Double sang phòng 103 hạng Deluxe Sea View. Chênh lệch tiền phòng: 300.000đ. Lý do: Khách yêu cầu đổi 1 phòng.', '2026-06-18 06:52:47', '2026-06-18 06:52:47'),
(62, 40, 4, 'change_one_room_category', 'Đã đổi phòng 103 từ hạng Deluxe Sea View sang phòng 201 hạng Superior Double. Chênh lệch tiền phòng: -300.000đ. Lý do: Khách yêu cầu đổi 1 phòng.', '2026-06-18 07:03:41', '2026-06-18 07:03:41'),
(63, 41, 4, 'booking_created', 'Tạo booking ở ngay - theo giờ bởi lễ tân. Gán phòng: 201. Thời gian: 18/06/2026 16:45 - 18/06/2026 18:45. Tổng tiền tạm tính: 270.000đ.', '2026-06-18 09:40:30', '2026-06-18 09:40:30'),
(64, 41, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.', '2026-06-18 09:42:47', '2026-06-18 09:42:47'),
(65, 42, 4, 'booking_created', 'Tạo booking ở ngay - theo giờ bởi lễ tân. Gán phòng: 203. Thời gian: 18/06/2026 19:00 - 18/06/2026 21:00. Cảnh báo: ca thuê theo giờ này trả phòng lúc 18/06/2026 21:00, cộng 60 phút dọn phòng sẽ chiếm phòng đến 18/06/2026 22:00, vượt mốc check-in qua đêm 14:00. Sau khi giữ 1 phòng theo giờ, hạng Superior Double còn 1 phòng có thể bán qua đêm trong khung 18/06/2026 14:00 → 19/06/2026 12:00. Lễ tân vẫn được tạo booking nếu khách xác nhận thuê theo giờ.. Tổng tiền tạm tính: 270.000đ.', '2026-06-18 09:56:08', '2026-06-18 09:56:08'),
(66, 44, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm. Số tiền phụ thu: 600.000đ.', '2026-06-18 10:36:26', '2026-06-18 10:36:26'),
(67, 44, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 101.', '2026-06-18 10:36:38', '2026-06-18 10:36:38'),
(68, 44, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 101: không có hư hại. Chờ admin duyệt.', '2026-06-18 10:36:47', '2026-06-18 10:36:47'),
(69, 44, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 101. Phí hư hại được duyệt: 0đ.', '2026-06-18 10:36:54', '2026-06-18 10:36:54'),
(70, 44, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 101. Thanh toán chuyển sang đã thanh toán.', '2026-06-18 10:37:00', '2026-06-18 10:37:00'),
(71, 41, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-18 12:50:32', '2026-06-18 12:50:32'),
(72, 41, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: không có hư hại. Chờ admin duyệt.', '2026-06-18 12:50:42', '2026-06-18 12:50:42'),
(73, 41, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Phí hư hại được duyệt: 0đ.', '2026-06-18 12:50:51', '2026-06-18 12:50:51'),
(74, 41, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 201. Thanh toán chuyển sang đã thanh toán.', '2026-06-18 12:51:14', '2026-06-18 12:51:14'),
(75, 46, 4, 'booking_created', 'Tạo booking ở ngay - theo giờ bởi lễ tân. Gán phòng: 301. Thời gian: 18/06/2026 20:00 - 18/06/2026 23:00. Cảnh báo: ca thuê theo giờ này trả phòng lúc 18/06/2026 23:00, cộng 60 phút dọn phòng sẽ chiếm phòng đến 19/06/2026 00:00, vượt mốc check-in qua đêm 14:00. Sau khi giữ 1 phòng theo giờ, hạng Family Suite còn 1 phòng có thể bán qua đêm trong khung 18/06/2026 14:00 → 19/06/2026 12:00. Lễ tân vẫn được tạo booking nếu khách xác nhận thuê theo giờ.. Tổng tiền tạm tính: 720.000đ.', '2026-06-18 12:52:52', '2026-06-18 12:52:52'),
(76, 46, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.', '2026-06-18 12:57:54', '2026-06-18 12:57:54'),
(77, 46, 4, 'service_added', 'Thêm dịch vụ/minibar vào booking: Bia x 3 = 0đ; Snack x 1 = 0đ. Tổng cộng thêm: 0đ.', '2026-06-18 13:01:02', '2026-06-18 13:01:02'),
(78, 45, 4, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.', '2026-06-18 13:03:27', '2026-06-18 13:03:27'),
(79, 45, 4, 'service_added', 'Thêm dịch vụ/minibar vào booking: Ăn sáng buffet x 1 = 120.000đ. Tổng cộng thêm: 120.000đ.', '2026-06-18 13:03:44', '2026-06-18 13:03:44'),
(80, 45, 4, 'service_added', 'Thêm dịch vụ/minibar vào booking: Coca Cola x 1 = 0đ. Tổng cộng thêm: 0đ.', '2026-06-18 13:03:59', '2026-06-18 13:03:59'),
(81, 45, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-18 13:04:08', '2026-06-18 13:04:08'),
(82, 45, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: không có hư hại. Chờ admin duyệt.', '2026-06-18 13:04:31', '2026-06-18 13:04:31'),
(83, 45, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Phí hư hại được duyệt: 0đ. Mục không duyệt: Bia - không duyệt; Coca Cola - không duyệt.', '2026-06-18 13:04:45', '2026-06-18 13:04:45'),
(84, 45, 4, 'check_out', 'Xác nhận check-out. Phòng chuyển sang cần dọn: 201. Thanh toán chuyển sang đã thanh toán.', '2026-06-18 13:04:58', '2026-06-18 13:04:58'),
(85, 46, 4, 'service_quantity_updated', 'Cập nhật số lượng \"Bia\" từ 3 sang 3. Chênh lệch: 0đ.', '2026-06-18 13:20:18', '2026-06-18 13:20:18'),
(86, 48, 4, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.', '2026-06-18 13:48:53', '2026-06-18 13:48:53'),
(87, 48, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 401.', '2026-06-18 13:56:21', '2026-06-18 13:56:21'),
(88, 48, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 401: không có hư hại. Chờ admin duyệt.', '2026-06-19 14:04:49', '2026-06-19 14:04:49'),
(89, 48, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 401. Phí hư hại được duyệt: 0đ.', '2026-06-19 14:05:11', '2026-06-19 14:05:11'),
(90, 47, 4, 'cancel_late_arrival', 'Hủy no-show do khách chưa đến sau 18:00 và không liên lạc được. Chưa ghi nhận tiền cọc trên hệ thống; lễ tân kiểm tra lại thanh toán nếu có. Phòng được mở bán lại.', '2026-06-19 14:05:50', '2026-06-19 14:05:50'),
(91, 49, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Check-in sớm lúc 19/06/2026 21:53, sớm hơn giờ chuẩn 16 giờ 6 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 900.000đ.', '2026-06-19 14:53:14', '2026-06-19 14:53:14'),
(92, 49, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-19 14:53:51', '2026-06-19 14:53:51'),
(93, 49, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: không có hư hại. Chờ admin duyệt.', '2026-06-19 14:53:58', '2026-06-19 14:53:58'),
(94, 49, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Phí hư hại được duyệt: 0đ.', '2026-06-19 14:54:03', '2026-06-19 14:54:03'),
(95, 49, 4, 'check_out', 'Xác nhận check-out lúc 19/06/2026 21:54. Phòng chuyển sang cần dọn: 201. Tiền phòng: 900.000đ. Dịch vụ/phụ thu: 900.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 1.800.000đ. Còn lại đã thu: 1.800.000đ. Không phát sinh phụ thu check-out.', '2026-06-19 14:54:44', '2026-06-19 14:54:44'),
(96, 48, 4, 'check_out', 'Xác nhận check-out lúc 19/06/2026 21:55. Phòng chuyển sang cần dọn: 401. Tiền phòng: 15.000.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 15.000.000đ. Còn lại đã thu: 15.000.000đ. Không phát sinh phụ thu check-out.', '2026-06-19 14:55:44', '2026-06-19 14:55:44'),
(97, 46, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 301.', '2026-06-19 14:57:12', '2026-06-19 14:57:12'),
(98, 46, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 301: không có hư hại. Chờ admin duyệt.', '2026-06-19 14:57:29', '2026-06-19 14:57:29'),
(99, 46, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 301. Phí hư hại được duyệt: 0đ.', '2026-06-19 14:57:36', '2026-06-19 14:57:36'),
(100, 46, 4, 'check_out', 'Xác nhận check-out lúc 19/06/2026 21:57. Phòng chuyển sang cần dọn: 301. Tiền phòng: 720.000đ. Dịch vụ/phụ thu: 5.520.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 6.240.000đ. Còn lại đã thu: 6.240.000đ. Phí phát sinh: Phụ thu check-out muộn: 5.520.000đ. Booking theo giờ trả muộn 22.96 giờ, tính thêm 23 giờ theo đơn giá tạm tính 240.000đ/giờ.', '2026-06-19 14:57:46', '2026-06-19 14:57:46'),
(101, 51, 4, 'booking_created', 'Tạo booking ở ngay - theo giờ bởi lễ tân. Gán phòng: 401. Thời gian: 19/06/2026 22:10 - 20/06/2026 13:00. Chính sách giá: Ở ngay theo giờ vượt quá 12 giờ, tự động tính 100% giá qua đêm. Thời lượng thực tế được làm tròn lên 15 giờ. Tỷ lệ tính tiền: 100% giá qua đêm.. Cảnh báo: ca thuê theo giờ này trả phòng lúc 20/06/2026 13:00, cộng 60 phút dọn phòng sẽ chiếm phòng đến 20/06/2026 14:00, vượt mốc check-in cam kết 14:00. Sau khi giữ 1 phòng theo giờ, hạng Presidential Suite còn 0 phòng có thể bán qua đêm trong khung 19/06/2026 14:00 → 20/06/2026 12:00. Lễ tân vẫn được tạo booking nếu khách xác nhận thuê theo giờ.. Tổng tiền tạm tính: 5.000.000đ.', '2026-06-19 15:13:23', '2026-06-19 15:13:23'),
(102, 51, 4, 'extend_stay', 'Gia hạn lưu trú từ 20/06/2026 13:00 đến 20/06/2026 15:00. Booking theo giờ, gia hạn thêm 2 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 337.079đ/giờ. Phụ thu: 674.157đ.', '2026-06-19 15:14:32', '2026-06-19 15:14:32'),
(103, 51, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 401.', '2026-06-19 15:14:58', '2026-06-19 15:14:58'),
(104, 51, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 401: không có hư hại. Chờ admin duyệt.', '2026-06-19 15:15:10', '2026-06-19 15:15:10'),
(105, 51, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 401: không có hư hại. Chờ admin duyệt.', '2026-06-19 15:15:14', '2026-06-19 15:15:14'),
(106, 51, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 401: không có hư hại. Chờ admin duyệt.', '2026-06-19 15:15:22', '2026-06-19 15:15:22'),
(107, 51, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 401. Phí hư hại được duyệt: 0đ. Mục không duyệt: Bia - không duyệt.', '2026-06-19 15:15:32', '2026-06-19 15:15:32'),
(108, 51, 4, 'check_out', 'Xác nhận check-out lúc 19/06/2026 22:15. Phòng chuyển sang cần dọn: 401. Tiền phòng: 5.000.000đ. Dịch vụ/phụ thu: 674.157đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 5.674.157đ. Còn lại đã thu: 5.674.157đ. Không phát sinh phụ thu check-out.', '2026-06-19 15:15:49', '2026-06-19 15:15:49'),
(109, 52, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Check-in sớm lúc 19/06/2026 22:16, sớm hơn giờ chuẩn 15 giờ 43 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 5.000.000đ.', '2026-06-19 15:16:43', '2026-06-19 15:16:43'),
(110, 52, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 401.', '2026-06-19 15:58:04', '2026-06-19 15:58:04'),
(111, 52, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 401: dịch vụ tại phòng: Bia x1 = 10.000đ; Snack x1 = 20.000đ — tạm tính 30.000đ. hư hại: Vỡ ly thủy tinh x1 = 50.000đ — tạm tính 50.000đ. Chờ admin duyệt.', '2026-06-19 15:58:23', '2026-06-19 15:58:23'),
(112, 52, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 401: dịch vụ tại phòng: Bia x1 = 10.000đ; Snack x2 = 40.000đ — tạm tính 50.000đ. hư hại: Vỡ ly thủy tinh x1 = 50.000đ — tạm tính 50.000đ. Chờ admin duyệt.', '2026-06-19 15:58:30', '2026-06-19 15:58:30'),
(113, 52, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 401. Dịch vụ tại phòng được duyệt: 50.000đ. Hư hại được duyệt: 50.000đ. Tổng cộng: 100.000đ. Mục duyệt: hư hại - Vỡ ly thủy tinh x1 = 50.000đ; dịch vụ tại phòng - Bia x1 = 10.000đ; dịch vụ tại phòng - Snack x2 = 40.000đ.', '2026-06-19 15:58:45', '2026-06-19 15:58:45'),
(114, 52, 4, 'service_quantity_updated', 'Cập nhật số lượng \"Coca Cola\" từ 1 sang 2. Chênh lệch: 50.000đ.', '2026-06-19 15:59:26', '2026-06-19 15:59:26'),
(115, 52, 4, 'check_out', 'Xác nhận check-out lúc 19/06/2026 23:00. Phòng chuyển sang cần dọn: 401. Tiền phòng: 5.000.000đ. Dịch vụ/phụ thu: 5.650.000đ. Minibar/hư hại duyệt: 100.000đ. Tổng phải thu: 10.750.000đ. Còn lại đã thu: 10.750.000đ. Không phát sinh phụ thu check-out.', '2026-06-19 16:00:07', '2026-06-19 16:00:07'),
(116, 58, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial.', '2026-06-21 09:12:01', '2026-06-21 09:12:01'),
(117, 59, 8, 'vnpay_payment_failed', 'Thanh toán VNPay không thành công. Mã phản hồi: 24.', '2026-06-21 09:56:16', '2026-06-21 09:56:16'),
(118, 59, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 900.000đ. Trạng thái thanh toán: paid.', '2026-06-21 09:57:25', '2026-06-21 09:57:25'),
(119, 61, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-21 10:18:52', '2026-06-21 10:18:52'),
(120, 62, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 243.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-21 14:15:29', '2026-06-21 14:15:29'),
(121, 62, 4, 'change_all_room_category', 'Đã đổi toàn bộ booking sang hạng phòng Family Suite. Chênh lệch tiền phòng: 900.000đ. Lý do: Khách yêu cầu đổi toàn bộ hạng phòng.', '2026-06-21 16:31:16', '2026-06-21 16:31:16'),
(122, 62, 4, 'promotion_added', 'Áp dụng mã ưu đãi sau khi tạo booking: EARLY_UPGRADE, SUPPORT100K. Giảm tiền thêm: 250.000đ, ưu đãi dịch vụ thêm: 340.000đ, tổng ưu đãi thêm: 590.000đ. Lý do: khách đến sớm nhưng khách sạn không còn phòng cùng hạng cho khách check in', '2026-06-21 16:46:16', '2026-06-21 16:46:16'),
(123, 62, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Check-in sớm lúc 21/06/2026 23:46, sớm hơn giờ chuẩn 14 giờ 13 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 1.800.000đ.', '2026-06-21 16:46:53', '2026-06-21 16:46:53'),
(124, 62, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 301.', '2026-06-22 03:40:46', '2026-06-22 03:40:46'),
(125, 62, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 301: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-06-22 03:41:10', '2026-06-22 03:41:10'),
(126, 62, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 301. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-06-22 03:41:18', '2026-06-22 03:41:18'),
(127, 62, 4, 'check_out', 'Xác nhận check-out lúc 22/06/2026 10:41. Phòng chuyển sang cần dọn: 301. Tiền phòng: 1.800.000đ. Dịch vụ/phụ thu: 2.140.000đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 3.940.000đ. Còn lại đã thu: 3.697.000đ. Không phát sinh phụ thu check-out.', '2026-06-22 03:41:26', '2026-06-22 03:41:26'),
(128, 63, 8, 'promotion_added', 'Khách áp dụng mã ưu đãi khi đặt phòng online: WELCOME200BF. Giảm tiền: 200.000đ, ưu đãi dịch vụ: 180.000đ, tổng ưu đãi: 380.000đ.', '2026-06-22 03:46:50', '2026-06-22 03:46:50'),
(129, 63, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 2.940.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-22 03:47:24', '2026-06-22 03:47:24'),
(130, 64, 11, 'promotion_added', 'Khách áp dụng mã ưu đãi khi đặt phòng online: WELCOME200BF. Giảm tiền: 200.000đ, ưu đãi dịch vụ: 180.000đ, tổng ưu đãi: 380.000đ.', '2026-06-22 03:53:42', '2026-06-22 03:53:42'),
(131, 64, 11, 'vnpay_payment_success_no_room', 'Thanh toán VNPay thành công 14.800.000đ nhưng không còn phòng trống để tự động gán.', '2026-06-22 03:54:17', '2026-06-22 03:54:17'),
(132, 65, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 6.000.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-22 04:04:20', '2026-06-22 04:04:20'),
(133, 66, 11, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 4.500.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-22 04:07:02', '2026-06-22 04:07:02'),
(134, 67, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 540.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-22 08:05:48', '2026-06-22 08:05:48'),
(135, 67, 4, 'change_stay_dates', 'Đổi ngày lưu trú từ 23/06/2026 14:00 → 24/06/2026 12:00 sang 22/06/2026 16:15 → 24/06/2026 12:00. Chênh lệch tiền phòng: 1.800.000đ.', '2026-06-22 09:15:25', '2026-06-22 09:15:25'),
(136, 67, 4, 'check_in', 'Xác nhận check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking nhiều đêm, khách cọc một phần/chưa thanh toán đủ và vẫn trong hạn giữ phòng 1 ngày đến 23/06/2026 16:15. Cho check-in bình thường, không phụ thu check-in muộn. Giữ nguyên ngày trả phòng ban đầu.', '2026-06-22 09:15:39', '2026-06-22 09:15:39'),
(137, 67, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 302.', '2026-06-22 09:23:26', '2026-06-22 09:23:26'),
(138, 67, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 302: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-06-22 09:23:35', '2026-06-22 09:23:35'),
(139, 67, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 302. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-06-22 09:23:41', '2026-06-22 09:23:41'),
(140, 67, 4, 'check_out', 'Xác nhận check-out lúc 22/06/2026 16:23. Phòng chuyển sang cần dọn: 302. Tiền phòng: 3.600.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 3.600.000đ. Còn lại đã thu: 3.060.000đ. Không phát sinh phụ thu check-out.', '2026-06-22 09:23:54', '2026-06-22 09:23:54'),
(141, 68, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-22 09:33:17', '2026-06-22 09:33:17'),
(142, 69, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Đã tự động gán phòng và xác nhận booking. Trạng thái thanh toán: partial.', '2026-06-23 04:15:10', '2026-06-23 04:15:10'),
(143, 69, 4, 'admin_vnpay_created', 'Lễ tân tạo thanh toán VNPay: 630.000đ (thu đủ còn lại).', '2026-06-23 05:33:22', '2026-06-23 05:33:22'),
(144, 69, 4, 'admin_vnpay_email_sent', 'Lễ tân gửi yêu cầu thanh toán VNPay qua email chientr319@gmail.com: 630.000đ (thanh toán số tiền còn lại). Mã giao dịch: BK20260623111437BIR20260623125445M0UQS.', '2026-06-23 05:54:51', '2026-06-23 05:54:51'),
(145, 69, 4, 'admin_vnpay_email_sent', 'Lễ tân gửi yêu cầu thanh toán VNPay qua email chientr319@gmail.com: 630.000đ (thanh toán số tiền còn lại). Mã giao dịch: BK20260623111437BIR20260623125748IVAJ6.', '2026-06-23 05:57:52', '2026-06-23 05:57:52'),
(146, 69, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 630.000đ. Trạng thái thanh toán: paid. Giao dịch tạo từ admin..', '2026-06-23 05:58:35', '2026-06-23 05:58:35'),
(147, 70, 8, 'booking_email_sent', 'Đã gửi email xác nhận booking đến tc19092006@gmail.com.', '2026-06-23 06:20:49', '2026-06-23 06:20:49'),
(148, 70, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-06-23 06:21:32', '2026-06-23 06:21:32'),
(149, 71, 8, 'booking_email_sent', 'Đã gửi email xác nhận booking đến tc19092006@gmail.com.', '2026-06-23 06:29:09', '2026-06-23 06:29:09'),
(150, 71, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-06-23 06:29:44', '2026-06-23 06:29:44'),
(151, 72, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-06-23 06:40:34', '2026-06-23 06:40:34'),
(152, 72, 8, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến tc19092006@gmail.com.', '2026-06-23 06:40:39', '2026-06-23 06:40:39'),
(153, 66, 4, 'cancel_late_arrival', 'Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 24/06/2026 14:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 4.500.000đ. Phòng được mở bán lại.', '2026-06-25 07:23:01', '2026-06-25 07:23:01'),
(154, 73, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-06-30 04:33:21', '2026-06-30 04:33:21'),
(155, 73, 8, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến vlinh319@gmail.com.', '2026-06-30 04:33:28', '2026-06-30 04:33:28'),
(156, 73, 4, 'check_in', 'Xác nhận check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé. Check-in sớm trong cùng ngày lúc 30/06/2026 11:34, sớm hơn giờ chuẩn 2 giờ 25 phút. Check-in sớm cùng ngày từ 11:00 đến trước 14:00, miễn phí nếu phòng đã sẵn sàng. Phụ thu: 0đ.', '2026-06-30 04:34:13', '2026-06-30 04:34:13'),
(157, 73, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 201.', '2026-06-30 04:34:20', '2026-06-30 04:34:20'),
(158, 73, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 201: không phát sinh minibar/hư hại. Chờ admin duyệt.', '2026-06-30 04:34:30', '2026-06-30 04:34:30'),
(159, 73, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 201. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 0đ. Tổng cộng: 0đ.', '2026-06-30 04:34:39', '2026-06-30 04:34:39'),
(160, 73, 4, 'check_out', 'Xác nhận check-out lúc 30/06/2026 11:35. Phòng chuyển sang cần dọn: 201. Tiền phòng: 900.000đ. Dịch vụ/phụ thu: 0đ. Minibar/hư hại duyệt: 0đ. Tổng phải thu: 900.000đ. Còn lại đã thu: 630.000đ. Không phát sinh phụ thu check-out.', '2026-06-30 04:35:05', '2026-06-30 04:35:05'),
(161, 73, 8, 'review_submitted', 'Khách gửi đánh giá khách sạn 4/5 sao. Chờ admin duyệt.', '2026-06-30 04:36:29', '2026-06-30 04:36:29'),
(162, 73, 4, 'review_approved', 'Admin duyệt đánh giá khách sạn 4/5 sao.', '2026-06-30 04:37:17', '2026-06-30 04:37:17'),
(163, 74, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-06-30 09:01:16', '2026-06-30 09:01:16'),
(164, 74, 8, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến vlinh319@gmail.com.', '2026-06-30 09:01:23', '2026-06-30 09:01:23'),
(165, 75, 11, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-06-30 09:04:00', '2026-06-30 09:04:00'),
(166, 75, 11, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến du319@gmail.com.', '2026-06-30 09:04:05', '2026-06-30 09:04:05'),
(167, 74, 4, 'cancel_late_arrival', 'Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 01/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ. Phòng được mở bán lại.', '2026-07-10 12:18:30', '2026-07-10 12:18:30'),
(168, 75, 4, 'cancel_late_arrival', 'Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 02/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ. Phòng được mở bán lại.', '2026-07-10 12:18:38', '2026-07-10 12:18:38'),
(169, 76, 4, 'booking_created', 'Tạo booking ở ngay - theo giờ bởi lễ tân. Gán phòng: 402. Thời gian: 10/07/2026 19:29 - 10/07/2026 21:29. Chính sách giá: Ở ngay theo giờ: block tối thiểu 2 giờ đầu = 50% giá qua đêm. Thời lượng thực tế được làm tròn lên 2 giờ. Tỷ lệ tính tiền: 50% giá qua đêm.. Cảnh báo: ca thuê theo giờ này trả phòng lúc 10/07/2026 21:29, cộng 60 phút dọn phòng sẽ chiếm phòng đến 10/07/2026 22:29, vượt mốc check-in cam kết 14:00. Sau khi giữ 1 phòng theo giờ, hạng Deluxe Sea View còn 6 phòng có thể bán qua đêm trong khung 10/07/2026 14:00 → 11/07/2026 12:00. Lễ tân vẫn được tạo booking nếu khách xác nhận thuê theo giờ.. Tổng tiền tạm tính: 600.000đ.', '2026-07-10 12:30:45', '2026-07-10 12:30:45'),
(170, 76, 4, 'booking_email_failed', 'Không gửi được email xác nhận booking đến vanp33@gmail.com: Failed to authenticate on SMTP server with username \"chientr319@gmail.com\" using the following authenticators: \"LOGIN\", \"PLAIN\", \"XOAUTH2\". Authenticator \"LOGIN\" returned \"Expected response code \"235\" but got code \"535\", with message \"535-5.7.8 Username and Password not accepted. For more information, go to\r\n535 5.7.8  https://support.google.com/mail/?p=BadCredentials d9443c01a7336-2ccc9d1e5d0sm60081035ad.42 - gsmtp\".\". Authenticator \"PLAIN\" returned \"Expected response code \"235\" but got code \"535\", with message \"535-5.7.8 Username and Password not accepted. For more information, go to\r\n535 5.7.8  https://support.google.com/mail/?p=BadCredentials d9443c01a7336-2ccc9d1e5d0sm60081035ad.42 - gsmtp\".\". Authenticator \"XOAUTH2\" returned \"Expected response code \"235\" but got code \"334\", with message \"334 eyJzdGF0dXMiOiI0MDAiLCJzY2hlbWVzIjoiQmVhcmVyIiwic2NvcGUiOiJodHRwczovL21haWwuZ29vZ2xlLmNvbS8ifQ==\".\".', '2026-07-10 12:30:54', '2026-07-10 12:30:54'),
(171, 76, 4, 'service_added', 'Thêm dịch vụ/minibar vào booking: Nước suối x 1 = 7.000đ. Tổng cộng thêm: 7.000đ.', '2026-07-10 12:35:11', '2026-07-10 12:35:11'),
(172, 76, 4, 'request_inspection', 'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: 402.', '2026-07-10 12:35:17', '2026-07-10 12:35:17'),
(173, 76, 4, 'inspection_reported', 'Buồng phòng báo cáo kiểm tra phòng 402: hư hại: Vỡ ly thủy tinh x1 = 50.000đ — tạm tính 50.000đ. Chờ admin duyệt.', '2026-07-10 12:35:34', '2026-07-10 12:35:34'),
(174, 76, 4, 'inspection_approved', 'Admin duyệt kiểm tra phòng 402. Dịch vụ tại phòng được duyệt: 0đ. Hư hại được duyệt: 50.000đ. Tổng cộng: 50.000đ. Mục duyệt: hư hại - Vỡ ly thủy tinh x1 = 50.000đ.', '2026-07-10 12:35:48', '2026-07-10 12:35:48'),
(175, 76, 4, 'check_out', 'Xác nhận check-out lúc 10/07/2026 19:36. Phòng chuyển sang cần dọn: 402. Tiền phòng: 650.000đ. Dịch vụ/phụ thu: 7.000đ. Minibar/hư hại duyệt: 50.000đ. Tổng phải thu: 707.000đ. Đã thu trước check-out: 0đ. Còn lại khi check-out: 707.000đ. Thu thêm khi check-out: 707.000đ bằng tiền mặt tại quầy. Mã giao dịch: CASHOUTBK260710MLVBG20260710193612A9EWR. Không phát sinh phụ thu check-out.', '2026-07-10 12:36:12', '2026-07-10 12:36:12'),
(176, 77, 8, 'vnpay_payment_success', 'Thanh toán VNPay thành công: 270.000đ. Trạng thái thanh toán: partial. Đã tự động gán phòng/xác nhận booking nếu đơn còn chờ xử lý..', '2026-07-11 06:17:43', '2026-07-11 06:17:43'),
(177, 77, 8, 'booking_email_sent_after_payment', 'Đã gửi email xác nhận booking sau khi thanh toán VNPay thành công đến vlinh319@gmail.com.', '2026-07-11 06:17:51', '2026-07-11 06:17:51'),
(178, 77, 4, 'cancel_late_arrival', 'Hủy no-show do khách không đến trong hạn giữ phòng. Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng 11/07/2026 18:00, xử lý no-show/không hoàn cọc. Giữ 100% tiền cọc/tiền đã thu: 270.000đ. Phòng được mở bán lại.', '2026-07-11 12:36:50', '2026-07-11 12:36:50');

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
  `payment_type` enum('deposit_30','full_100') NOT NULL,
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
(1, 55, 'vnpay', 'BK20260621145034HUC_20260621145034_1HQQY', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 07:50:34', '2026-06-21 07:50:34'),
(2, 56, 'vnpay', 'BK20260621154514LSJ_20260621154514_PKF4T', 360000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 08:45:14', '2026-06-21 08:45:14'),
(3, 57, 'vnpay', 'BK20260621160902USY20260621160902OH86I', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 09:09:02', '2026-06-21 09:09:02'),
(4, 58, 'vnpay', 'BK202606211610574CW20260621161057PFMHJ', 270000.00, 'success', 'deposit_30', 'NCB', '15592003', '00', '00', '2026-06-21 09:12:01', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592003\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK202606211610574CW\",\"vnp_PayDate\":\"20260621161154\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592003\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202606211610574CW20260621161057PFMHJ\",\"vnp_SecureHash\":\"1d6b2b8883c5896e000c893150852163e5455b650d8c6470c98a0c769bc5ee1ad9ae3588363c154d82e09f97d2ec7187c700aed86b9f562bfed4929d25d53561\"}', '2026-06-21 09:10:57', '2026-06-21 09:12:01'),
(5, 59, 'vnpay', 'BK20260621165522C1G20260621165522MKP07', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 09:55:22', '2026-06-21 09:55:22'),
(6, 59, 'vnpay', 'BK20260621165522C1G20260621165547PKPO8', 900000.00, 'pending', 'full_100', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 09:55:47', '2026-06-21 09:55:47'),
(7, 59, 'vnpay', 'BK20260621165522C1G20260621165603GSLJK', 900000.00, 'failed', 'full_100', 'VNPAY', '0', '24', '02', NULL, '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"VNPAY\",\"vnp_CardType\":\"QRCODE\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260621165522C1G\",\"vnp_PayDate\":\"20260621165604\",\"vnp_ResponseCode\":\"24\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"0\",\"vnp_TransactionStatus\":\"02\",\"vnp_TxnRef\":\"BK20260621165522C1G20260621165603GSLJK\",\"vnp_SecureHash\":\"50e1448428ab36dd1407544726b9ca5d83a844efbde53494d31a3b71be3496496ce7534b04327a30953532acb9b552cd8820831bc386ef2eada8633808d04f03\"}', '2026-06-21 09:56:03', '2026-06-21 09:56:16'),
(8, 59, 'vnpay', 'BK20260621165522C1G20260621165640DKJ0C', 900000.00, 'pending', 'full_100', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 09:56:40', '2026-06-21 09:56:40'),
(9, 59, 'vnpay', 'BK20260621165522C1G20260621165655RPCVL', 900000.00, 'success', 'full_100', 'NCB', '15592068', '00', '00', '2026-06-21 09:57:25', '{\"vnp_Amount\":\"90000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592068\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260621165522C1G\",\"vnp_PayDate\":\"20260621165716\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592068\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260621165522C1G20260621165655RPCVL\",\"vnp_SecureHash\":\"0344bf5789d7d02180cafe177902aa574c7a758e49e63baf4448841daf06929a0747621fd8d4c48c81ba6a776c7fafdd9e7dc72eae0f40ae07d3d3be54c8f27d\"}', '2026-06-21 09:56:55', '2026-06-21 09:57:25'),
(10, 60, 'vnpay', 'BK20260621170026NHW20260621170026B30XO', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 10:00:26', '2026-06-21 10:00:26'),
(11, 61, 'vnpay', 'BK202606211717001PU20260621171700IPYLE', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 10:17:00', '2026-06-21 10:17:00'),
(12, 61, 'vnpay', 'BK202606211717001PU20260621171750NCQBK', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 10:17:50', '2026-06-21 10:17:50'),
(13, 61, 'vnpay', 'BK202606211717001PU20260621171757SUXI2', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 10:17:57', '2026-06-21 10:17:57'),
(14, 61, 'vnpay', 'BK202606211717001PU20260621171812BJJI7', 900000.00, 'pending', 'full_100', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 10:18:12', '2026-06-21 10:18:12'),
(15, 61, 'vnpay', 'BK202606211717001PU202606211718171EHXI', 270000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 10:18:17', '2026-06-21 10:18:17'),
(16, 61, 'vnpay', 'BK202606211717001PU202606211718244NPAL', 270000.00, 'success', 'deposit_30', 'NCB', '15592103', '00', '00', '2026-06-21 10:18:52', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592103\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK202606211717001PU\",\"vnp_PayDate\":\"20260621171847\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592103\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202606211717001PU202606211718244NPAL\",\"vnp_SecureHash\":\"d25f80b9ba331608ab2dd89f43ccb63bfc8e062ce55b509e57d796bb5e614a8f43cdbc8073f2123cc331d359aa627aab906fab9e44729b7ac7d97fe0ea05823a\"}', '2026-06-21 10:18:24', '2026-06-21 10:18:52'),
(17, 62, 'vnpay', 'BK20260621211437VTS20260621211437RGMXJ', 243000.00, 'pending', 'deposit_30', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 14:14:37', '2026-06-21 14:14:37'),
(18, 62, 'vnpay', 'BK20260621211437VTS20260621211458PSIYM', 243000.00, 'success', 'deposit_30', 'NCB', '15592286', '00', '00', '2026-06-21 14:15:29', '{\"vnp_Amount\":\"24300000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592286\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260621211437VTS\",\"vnp_PayDate\":\"20260621211523\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592286\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260621211437VTS20260621211458PSIYM\",\"vnp_SecureHash\":\"f9293beb1eed2ea0f1a009f4afb91e87e184edfb7653c82311292620f00d4d8934ce1c5a144de15da443c186d6ddea45673f12994ccb59d5f734df10af11a761\"}', '2026-06-21 14:14:58', '2026-06-21 14:15:29'),
(19, 63, 'vnpay', 'BK20260622104650PIF202606221046507TTIW', 2940000.00, 'success', 'deposit_30', 'NCB', '15592935', '00', '00', '2026-06-22 03:47:24', '{\"vnp_Amount\":\"294000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592935\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260622104650PIF\",\"vnp_PayDate\":\"20260622104718\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592935\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260622104650PIF202606221046507TTIW\",\"vnp_SecureHash\":\"48dd8248053bae4ce817eaac9c160cf96bd86b133e2cd9e3a8bf4ca8d60aa17c2d65d3723231990603e4a7ce654fdc47f6f72a77be93a2ebd3bee1c1d441e912\"}', '2026-06-22 03:46:50', '2026-06-22 03:47:24'),
(20, 64, 'vnpay', 'BK20260622105342WAB20260622105342ZAZ4I', 14800000.00, 'success', 'full_100', 'NCB', '15592950', '00', '00', '2026-06-22 03:54:17', '{\"vnp_Amount\":\"1480000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592950\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260622105342WAB\",\"vnp_PayDate\":\"20260622105412\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592950\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260622105342WAB20260622105342ZAZ4I\",\"vnp_SecureHash\":\"d20329f6b523b517d2e1fb9bbb1bd5c1d1fe1f5aecd37105ca70971fdbd0d4bf22683c6743b2df5c0dbd7e88583e8cf47373b244114b611ac3239839c8acee59\"}', '2026-06-22 03:53:42', '2026-06-22 03:54:17'),
(21, 65, 'vnpay', 'BK20260622110355MWQ2026062211035604SWL', 6000000.00, 'success', 'deposit_30', 'NCB', '15592976', '00', '00', '2026-06-22 04:04:20', '{\"vnp_Amount\":\"600000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592976\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260622110355MWQ\",\"vnp_PayDate\":\"20260622110413\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592976\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260622110355MWQ2026062211035604SWL\",\"vnp_SecureHash\":\"c3b789e22f6bb4437c8bf24a203c8a22989a971b645af58ef1e519ae0a138e6c08814b8cb901597cf80bdfb6dc84d098319458e5e2d6d6caa5ef16477460b9e9\"}', '2026-06-22 04:03:56', '2026-06-22 04:04:20'),
(22, 66, 'vnpay', 'BK20260622110638MYQ20260622110638I1VSE', 4500000.00, 'success', 'deposit_30', 'NCB', '15592978', '00', '00', '2026-06-22 04:07:02', '{\"vnp_Amount\":\"450000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15592978\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260622110638MYQ\",\"vnp_PayDate\":\"20260622110657\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15592978\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260622110638MYQ20260622110638I1VSE\",\"vnp_SecureHash\":\"d25afb28fd384a33e418503b22912a72082a1c59984c9af02ec02649db855051b08fdaebe663944155a9bf2991453a8645c926d2726b371853c0b2c4802c4da5\"}', '2026-06-22 04:06:38', '2026-06-22 04:07:02'),
(23, 67, 'vnpay', 'BK20260622150521YKX20260622150522FK57G', 540000.00, 'success', 'deposit_30', 'NCB', '15593393', '00', '00', '2026-06-22 08:05:48', '{\"vnp_Amount\":\"54000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15593393\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260622150521YKX\",\"vnp_PayDate\":\"20260622150543\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15593393\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260622150521YKX20260622150522FK57G\",\"vnp_SecureHash\":\"7fd476c0542d0fb03363b0cc5df21a474d5080c1ccd12ecf11b7aeb6bf632a13752795ff3c3931318c7e8e365da2b24f087d6af2e9dd2a8f64f32e76ef1811fd\"}', '2026-06-22 08:05:22', '2026-06-22 08:05:48'),
(24, 68, 'vnpay', 'BK20260622163249NQJ202606221632492ANU7', 270000.00, 'success', 'deposit_30', 'NCB', '15593579', '00', '00', '2026-06-22 09:33:17', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15593579\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260622163249NQJ\",\"vnp_PayDate\":\"20260622163310\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15593579\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260622163249NQJ202606221632492ANU7\",\"vnp_SecureHash\":\"9c757d9ee3fe1ab90766a1d7093357e22bde3f16074824e330dab600d6b7a0ee712354dd9f560a2ac11dce304958c423eff5be0fdddb5efa5b18685d5a07caf9\"}', '2026-06-22 09:32:49', '2026-06-22 09:33:17'),
(25, 69, 'vnpay', 'BK20260623111437BIR20260623111438RESUW', 270000.00, 'success', 'deposit_30', 'NCB', '15594769', '00', '00', '2026-06-23 04:15:10', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15594769\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan booking BK20260623111437BIR\",\"vnp_PayDate\":\"20260623111457\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15594769\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260623111437BIR20260623111438RESUW\",\"vnp_SecureHash\":\"e01b1a19c8e06dbab6f8bc10c78551ff7e1a382ab641f52203cffaad8cdea0e35f5f151151fc3ef57a1ad8c8b33002771f191cd95c8907c3ae8a6b75c11979c5\"}', '2026-06-23 04:14:38', '2026-06-23 04:15:10'),
(26, 69, 'admin_vnpay', 'BK20260623111437BIR20260623123322Q6UWH', 630000.00, 'pending', 'full_100', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-23 05:33:22', '2026-06-23 05:33:22'),
(27, 69, 'admin_vnpay', 'BK20260623111437BIR20260623125445M0UQS', 630000.00, 'pending', 'full_100', NULL, NULL, NULL, NULL, NULL, '{\"source\":\"admin_email_request\",\"customer_email\":\"chientr319@gmail.com\",\"staff_id\":4,\"payment_url\":\"https:\\/\\/sandbox.vnpayment.vn\\/paymentv2\\/vpcpay.html?vnp_Amount=63000000&vnp_Command=pay&vnp_CreateDate=20260623125445&vnp_CurrCode=VND&vnp_ExpireDate=20260623132445&vnp_IpAddr=127.0.0.1&vnp_Locale=vn&vnp_OrderInfo=Thanh+toan+con+lai+booking+BK20260623111437BIR+-+GD+BK20260623111437BIR20260623125445M0UQS&vnp_OrderType=other&vnp_ReturnUrl=http%3A%2F%2F127.0.0.1%3A8000%2Fpayment%2Fvnpay%2Freturn&vnp_TmnCode=B9A7D6RU&vnp_TxnRef=BK20260623111437BIR20260623125445M0UQS&vnp_Version=2.1.0&vnp_SecureHash=218a196a42dc1aac70c018494f1e782da1e0ad283fa9cd26b0460b6238f01fa91139a584e5359b1e859c80e72d9f624a1d5555d4805187bab0d8e08c0ee576f1\",\"expires_at\":\"2026-06-23 13:24:45\",\"email_sent_at\":\"2026-06-23 12:54:51\"}', '2026-06-23 05:54:45', '2026-06-23 05:54:51'),
(28, 69, 'admin_vnpay', 'BK20260623111437BIR20260623125748IVAJ6', 630000.00, 'success', 'full_100', 'NCB', '15594910', '00', '00', '2026-06-23 05:58:35', '{\"vnp_Amount\":\"63000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15594910\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Thanh toan con lai booking BK20260623111437BIR - GD BK20260623111437BIR20260623125748IVAJ6\",\"vnp_PayDate\":\"20260623125828\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15594910\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260623111437BIR20260623125748IVAJ6\",\"vnp_SecureHash\":\"0f98b8061f5f399f51bcedf5a28e7483854da9dd5ec7b71b675ad08b4988d0ed1ca25d5ecbc371c741f1470718ee88a6c363ed118f2b2c6fa088b36e94600c83\"}', '2026-06-23 05:57:48', '2026-06-23 05:58:35'),
(29, 70, 'vnpay', 'BK20260623132045HY1202606231320455DXZS', 270000.00, 'success', 'deposit_30', 'NCB', '15594939', '00', '00', '2026-06-23 06:21:32', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15594939\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260623132045HY1 - GD BK20260623132045HY1202606231320455DXZS\",\"vnp_PayDate\":\"20260623132110\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15594939\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260623132045HY1202606231320455DXZS\",\"vnp_SecureHash\":\"d376bfc900304d47a73fd8805547204bb678e2f426d481d92917262f5736d7c0a0c739fde4251d52e24fea182a319c11c645b60cd38b04ac9c52359658f24ebd\"}', '2026-06-23 06:20:45', '2026-06-23 06:21:32'),
(30, 71, 'vnpay', 'BK202606231329044B120260623132904A6WWY', 270000.00, 'success', 'deposit_30', 'NCB', '15594953', '00', '00', '2026-06-23 06:29:44', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15594953\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK202606231329044B1 - GD BK202606231329044B120260623132904A6WWY\",\"vnp_PayDate\":\"20260623132928\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15594953\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK202606231329044B120260623132904A6WWY\",\"vnp_SecureHash\":\"12d5cffb808ea14fdddd2cb40a08b02198f912d63d1d819d00757c3d7fc1de3feb680e4a4659b00f105290e72c8b4d0b0e8574b7bc345c76a68f53089517d784\"}', '2026-06-23 06:29:04', '2026-06-23 06:29:44'),
(31, 72, 'vnpay', 'BK20260623133959OXO20260623133959YUTTL', 270000.00, 'success', 'deposit_30', 'NCB', '15594970', '00', '00', '2026-06-23 06:40:34', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15594970\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260623133959OXO - GD BK20260623133959OXO20260623133959YUTTL\",\"vnp_PayDate\":\"20260623134019\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15594970\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260623133959OXO20260623133959YUTTL\",\"vnp_SecureHash\":\"f2096cd770fe6602fee2f323d9ed88db8a06acd1455ba530f35c22d63b9b67bc0fa7d9c38378720ea8d8332d7e6233bfa8196e80f487a6b19cfd24608ee273b6\",\"booking_confirm_email_sent_at\":\"2026-06-23 13:40:39\",\"booking_confirm_email_to\":\"tc19092006@gmail.com\"}', '2026-06-23 06:39:59', '2026-06-23 06:40:39'),
(32, 73, 'vnpay', 'BK20260630113239BS220260630113240LKCBT', 270000.00, 'success', 'deposit_30', 'NCB', '15604249', '00', '00', '2026-06-30 04:33:20', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15604249\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260630113239BS2 - GD BK20260630113239BS220260630113240LKCBT\",\"vnp_PayDate\":\"20260630113308\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15604249\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260630113239BS220260630113240LKCBT\",\"vnp_SecureHash\":\"7a1b7b909caa0e920c95dd50c2c1dd07e0f6c6985090ba6a6e441dbef80690525da0e37e9c3acb24405519ac8abf9c6ae0263676398d30c8593000b2b47b96b1\",\"booking_confirm_email_sent_at\":\"2026-06-30 11:33:28\",\"booking_confirm_email_to\":\"vlinh319@gmail.com\"}', '2026-06-30 04:32:40', '2026-06-30 04:33:28'),
(33, 74, 'vnpay', 'BK20260630160020FTF20260630160021VA2IK', 270000.00, 'success', 'deposit_30', 'NCB', '15604688', '00', '00', '2026-06-30 09:01:16', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15604688\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260630160020FTF - GD BK20260630160020FTF20260630160021VA2IK\",\"vnp_PayDate\":\"20260630160057\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15604688\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260630160020FTF20260630160021VA2IK\",\"vnp_SecureHash\":\"84b594e1208c24cced07561d69819671d04b75284bf757fb85d5bcdb20d5fe35a0aa5451092d7280fb18f0764e84a5eddb9366b193ab402c849dc230ef179e91\",\"booking_confirm_email_sent_at\":\"2026-06-30 16:01:23\",\"booking_confirm_email_to\":\"vlinh319@gmail.com\"}', '2026-06-30 09:00:21', '2026-06-30 09:01:23'),
(34, 75, 'vnpay', 'BK20260630160322EMC20260630160322EOEGG', 270000.00, 'success', 'deposit_30', 'NCB', '15604693', '00', '00', '2026-06-30 09:04:00', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15604693\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260630160322EMC - GD BK20260630160322EMC20260630160322EOEGG\",\"vnp_PayDate\":\"20260630160344\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15604693\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260630160322EMC20260630160322EOEGG\",\"vnp_SecureHash\":\"01b9701e20dc66f9f5113801758185fb97696763ab458c2076bc1994ed3afb868811ce570f8e36cd8f8ac782c13bab6385055f736f719ee885279e0bdee61913\",\"booking_confirm_email_sent_at\":\"2026-06-30 16:04:05\",\"booking_confirm_email_to\":\"du319@gmail.com\"}', '2026-06-30 09:03:22', '2026-06-30 09:04:05'),
(35, 76, 'cash', 'CASHOUTBK260710MLVBG20260710193612A9EWR', 707000.00, 'success', 'full_100', NULL, NULL, NULL, NULL, '2026-07-10 12:36:12', '{\"source\":\"checkout\",\"method\":\"cash\",\"type\":\"remaining_at_checkout\",\"staff_id\":4,\"note\":\"L\\u1ec5 t\\u00e2n x\\u00e1c nh\\u1eadn kh\\u00e1ch \\u0111\\u00e3 thanh to\\u00e1n kho\\u1ea3n c\\u00f2n l\\u1ea1i khi check-out.\"}', '2026-07-10 12:36:12', '2026-07-10 12:36:12'),
(36, 77, 'vnpay', 'BK20260711131605DQF202607111316068D5WY', 270000.00, 'success', 'deposit_30', 'NCB', '15617162', '00', '00', '2026-07-11 06:17:43', '{\"vnp_Amount\":\"27000000\",\"vnp_BankCode\":\"NCB\",\"vnp_BankTranNo\":\"VNP15617162\",\"vnp_CardType\":\"ATM\",\"vnp_OrderInfo\":\"Coc 30 phan tram booking BK20260711131605DQF - GD BK20260711131605DQF202607111316068D5WY\",\"vnp_PayDate\":\"20260711131737\",\"vnp_ResponseCode\":\"00\",\"vnp_TmnCode\":\"B9A7D6RU\",\"vnp_TransactionNo\":\"15617162\",\"vnp_TransactionStatus\":\"00\",\"vnp_TxnRef\":\"BK20260711131605DQF202607111316068D5WY\",\"vnp_SecureHash\":\"c9657962337f622a980331b7213838c8a759273b285cb39aa96690b9cdf446f98b7dc5f6ec179c731b1264e068399e6e1d448fb745efbb4fddf1ab083a91780f\",\"booking_confirm_email_sent_at\":\"2026-07-11 13:17:51\",\"booking_confirm_email_to\":\"vlinh319@gmail.com\"}', '2026-07-11 06:16:06', '2026-07-11 06:17:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_promotions`
--

CREATE TABLE `booking_promotions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
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

INSERT INTO `booking_promotions` (`id`, `booking_id`, `promotion_id`, `code_snapshot`, `promotion_type_snapshot`, `discount_type_snapshot`, `discount_value_snapshot`, `money_discount_amount`, `service_discount_amount`, `room_upgrade_discount_amount`, `discount_amount`, `applied_by`, `applied_channel`, `note`, `created_at`, `updated_at`) VALUES
(1, 62, 1, 'WELCOME10', 'normal_discount', 'percent', 10.00, 90000.00, 0.00, 0.00, 90000.00, 8, 'user', NULL, '2026-06-21 14:14:37', '2026-06-21 14:14:37'),
(2, 62, 7, 'EARLY_UPGRADE', 'support_discount', 'fixed_amount', 150000.00, 150000.00, 340000.00, 0.00, 490000.00, 4, 'admin', 'khách đến sớm nhưng khách sạn không còn phòng cùng hạng cho khách check in', '2026-06-21 16:46:15', '2026-06-21 16:46:15'),
(3, 62, 3, 'SUPPORT100K', 'support_discount', 'fixed_amount', 100000.00, 100000.00, 0.00, 0.00, 100000.00, 4, 'admin', 'khách đến sớm nhưng khách sạn không còn phòng cùng hạng cho khách check in', '2026-06-21 16:46:16', '2026-06-21 16:46:16'),
(4, 63, 5, 'WELCOME200BF', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 180000.00, 0.00, 380000.00, 8, 'user', NULL, '2026-06-22 03:46:50', '2026-06-22 03:46:50'),
(5, 64, 5, 'WELCOME200BF', 'normal_discount', 'fixed_amount', 200000.00, 200000.00, 180000.00, 0.00, 380000.00, 11, 'user', NULL, '2026-06-22 03:53:42', '2026-06-22 03:53:42');

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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_promotion_service_offers`
--

CREATE TABLE `booking_promotion_service_offers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
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

INSERT INTO `booking_promotion_service_offers` (`id`, `booking_id`, `booking_promotion_id`, `promotion_id`, `promotion_service_offer_id`, `service_id`, `code_snapshot`, `service_name_snapshot`, `service_unit_snapshot`, `service_price_snapshot`, `discount_type_snapshot`, `discount_value_snapshot`, `quantity`, `original_amount`, `discount_amount`, `final_amount`, `note`, `created_at`, `updated_at`) VALUES
(1, 62, 2, 7, 3, 32, 'EARLY_UPGRADE', 'Buffet sáng', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng để bù trải nghiệm khi khách phải đổi hạng phòng do đến sớm.', '2026-06-21 16:46:15', '2026-06-21 16:46:15'),
(2, 62, 2, 7, 4, 33, 'EARLY_UPGRADE', 'Đồ uống chào mừng', 'phần', 80000.00, 'percent', 100.00, 2, 160000.00, 160000.00, 0.00, 'Tặng 2 phần đồ uống chào mừng trong lúc khách chờ xử lý nhận phòng/đổi hạng.', '2026-06-21 16:46:16', '2026-06-21 16:46:16'),
(3, 63, 4, 5, 1, 32, 'WELCOME200BF', 'Buffet sáng', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-06-22 03:46:50', '2026-06-22 03:46:50'),
(4, 64, 5, 5, 1, 32, 'WELCOME200BF', 'Buffet sáng', 'suất', 180000.00, 'percent', 100.00, 1, 180000.00, 180000.00, 0.00, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-06-22 03:53:42', '2026-06-22 03:53:42');

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
(1, 2, 1, 0, 0, 1200000.00, 0.00, NULL, '2026-06-08 22:34:37'),
(2, 2, 2, 0, 0, 1200000.00, 0.00, NULL, '2026-06-08 22:34:37'),
(3, 4, 1, 0, 0, 1200000.00, 0.00, NULL, '2026-06-09 01:47:35'),
(4, 4, 2, 0, 0, 1200000.00, 0.00, NULL, '2026-06-09 01:47:35'),
(5, 4, 3, 0, 0, 1200000.00, 0.00, NULL, '2026-06-09 01:47:35'),
(6, 5, 7, 2, 1, 1800000.00, 0.00, NULL, '2026-06-10 05:04:20'),
(7, 6, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-10 05:54:12'),
(8, 7, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-10 06:23:00'),
(9, 8, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-10 06:27:06'),
(10, 9, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-06-10 06:27:48'),
(11, 10, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-06-10 06:29:14'),
(12, 11, 13, 0, 0, 1200000.00, 0.00, 'Đổi từ phòng 402 sang phòng 405. Lý do: hỏng điều hòa', '2026-06-11 06:14:43'),
(13, 11, 11, 0, 0, 1200000.00, 0.00, NULL, '2026-06-11 06:14:43'),
(14, 11, 12, 0, 0, 1200000.00, 0.00, NULL, '2026-06-11 06:14:43'),
(15, 12, 2, 1, 0, 1200000.00, 0.00, NULL, '2026-06-11 06:45:13'),
(16, 13, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-11 08:33:35'),
(17, 14, 3, 1, 0, 1200000.00, 0.00, NULL, '2026-06-11 08:45:10'),
(18, 15, 1, 0, 0, 1200000.00, 0.00, NULL, '2026-06-11 09:42:51'),
(19, 16, 4, 2, 1, 900000.00, 0.00, NULL, '2026-06-11 09:45:08'),
(20, 17, 5, 0, 0, 900000.00, 0.00, NULL, '2026-06-11 09:46:14'),
(21, 17, 6, 0, 0, 900000.00, 0.00, NULL, '2026-06-11 09:46:14'),
(22, 18, 10, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:10:46'),
(23, 18, 11, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:10:46'),
(24, 18, 12, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:10:46'),
(25, 18, 13, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:10:46'),
(26, 19, 1, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:15:08'),
(27, 19, 2, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:15:08'),
(28, 19, 3, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:15:08'),
(29, 19, 4, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:15:08'),
(30, 19, 5, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:15:08'),
(31, 19, 6, 0, 0, 1200000.00, 0.00, NULL, '2026-06-12 00:15:08'),
(32, 20, 7, 1, 0, 1800000.00, 0.00, NULL, '2026-06-12 00:31:45'),
(33, 21, 9, 2, 0, 5000000.00, 0.00, NULL, '2026-06-13 01:47:07'),
(34, 22, 8, 2, 0, 1800000.00, 0.00, NULL, '2026-06-13 19:27:38'),
(35, 23, 7, 1, 0, 1800000.00, 0.00, NULL, '2026-06-13 19:33:20'),
(36, 24, 5, 2, 0, 900000.00, 0.00, NULL, '2026-06-15 09:00:04'),
(38, 25, 9, 0, 0, 5000000.00, 0.00, 'Đổi hạng phòng khi check-in do vượt sức chứa.', '2026-06-15 10:12:06'),
(39, 26, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-06-15 12:37:44'),
(40, 27, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-15 12:40:53'),
(41, 28, 6, 1, 0, 900000.00, 0.00, NULL, '2026-06-15 20:35:19'),
(42, 29, 4, 0, 0, 900000.00, 0.00, 'Đổi từ phòng 202 sang phòng 201. Lý do: Hỏng đèn', '2026-06-15 21:04:23'),
(43, 30, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-16 06:22:10'),
(44, 31, 6, 1, 0, 900000.00, 0.00, NULL, '2026-06-16 06:39:21'),
(45, 32, 4, 1, 0, 900000.00, 0.00, 'Đổi từ phòng 203 sang phòng 201. Lý do: Đổi phòng và gia hạn cho khách', '2026-06-16 06:55:47'),
(46, 33, 6, 0, 0, 900000.00, 0.00, NULL, '2026-06-16 07:02:26'),
(47, 34, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-17 18:17:33'),
(48, 35, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-17 18:22:32'),
(49, 36, 1, 0, 0, 1200000.00, 0.00, 'Chuyển từ phòng 103 sang phòng 101 để gia hạn do phòng cũ có booking kế tiếp.', '2026-06-17 19:55:46'),
(50, 37, 5, 0, 0, 900000.00, 0.00, NULL, '2026-06-17 19:57:47'),
(51, 38, 8, 0, 0, 1800000.00, 0.00, NULL, '2026-06-17 20:07:51'),
(52, 39, 10, 1, 0, 1200000.00, 0.00, NULL, '2026-06-18 05:26:51'),
(54, 40, 4, 0, 0, 900000.00, 0.00, 'Đổi 1 phòng sang hạng khác.', '2026-06-18 06:34:15'),
(55, 40, 6, 0, 0, 900000.00, 0.00, 'Thêm phòng khi check-in do vượt sức chứa.', '2026-06-18 06:52:16'),
(56, 41, 4, 0, 0, 900000.00, 0.00, NULL, '2026-06-18 09:40:30'),
(57, 42, 6, 0, 0, 900000.00, 0.00, NULL, '2026-06-18 09:56:08'),
(58, 43, 2, 1, 0, 1200000.00, 0.00, NULL, '2026-06-18 10:32:07'),
(59, 44, 1, 1, 0, 1200000.00, 0.00, NULL, '2026-06-18 10:35:44'),
(60, 45, 4, 2, 0, 900000.00, 0.00, NULL, '2026-06-18 11:46:42'),
(61, 46, 7, 0, 0, 1800000.00, 0.00, NULL, '2026-06-18 12:52:52'),
(62, 47, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-18 13:43:18'),
(63, 48, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-18 13:46:08'),
(64, 49, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-19 14:16:57'),
(65, 50, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-19 15:10:47'),
(66, 51, 9, 0, 0, 5000000.00, 0.00, NULL, '2026-06-19 15:13:23'),
(67, 52, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-19 15:16:19'),
(68, 53, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-20 07:42:10'),
(69, 54, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-20 07:54:38'),
(70, 55, 14, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 07:50:34'),
(71, 56, 14, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 08:45:14'),
(72, 57, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 09:09:02'),
(73, 58, 14, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 09:10:57'),
(74, 59, 14, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 09:55:22'),
(75, 60, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 10:00:26'),
(76, 61, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-21 10:18:52'),
(78, 62, 7, 0, 0, 1800000.00, 0.00, 'Đổi toàn bộ booking sang hạng khác.', '2026-06-21 16:31:16'),
(79, 63, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-22 03:47:24'),
(80, 65, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-22 04:04:20'),
(81, 66, 9, 1, 0, 5000000.00, 0.00, NULL, '2026-06-22 04:07:02'),
(82, 67, 8, 1, 0, 1800000.00, 0.00, NULL, '2026-06-22 08:05:48'),
(83, 68, 4, 2, 0, 900000.00, 0.00, NULL, '2026-06-22 09:33:17'),
(84, 69, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-23 04:15:10'),
(85, 70, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-23 06:21:32'),
(86, 71, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-23 06:29:44'),
(87, 72, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-23 06:40:34'),
(88, 73, 4, 2, 0, 900000.00, 0.00, NULL, '2026-06-30 04:33:21'),
(89, 74, 14, 1, 0, 900000.00, 0.00, NULL, '2026-06-30 09:01:16'),
(90, 75, 14, 2, 0, 900000.00, 0.00, NULL, '2026-06-30 09:04:00'),
(91, 76, 10, 0, 0, 1200000.00, 0.00, NULL, '2026-07-10 12:30:45'),
(92, 77, 14, 1, 0, 900000.00, 0.00, NULL, '2026-07-11 06:16:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_service_items`
--

CREATE TABLE `booking_service_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `used_quantity` int(11) NOT NULL DEFAULT 0,
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

INSERT INTO `booking_service_items` (`id`, `booking_id`, `service_id`, `name`, `type`, `unit_price`, `quantity`, `used_quantity`, `billing_status`, `confirmed_by`, `confirmed_at`, `confirm_note`, `total`, `note`, `created_at`, `updated_at`) VALUES
(5, 29, 4, 'Nước suối', 'minibar', 7000.00, 2, 1, 'confirmed', 4, '2026-06-16 13:20:21', 'Buồng phòng xác nhận khách sử dụng 1/2 Nước suối.', 7000.00, NULL, '2026-06-15 21:04:23', '2026-06-16 06:20:21'),
(6, 29, 2, 'Ăn sáng buffet', 'service', 120000.00, 2, 2, 'confirmed', NULL, NULL, NULL, 240000.00, NULL, '2026-06-15 21:04:50', '2026-06-15 22:03:26'),
(7, 29, 3, 'Đưa đón sân bay', 'service', 300000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 300000.00, NULL, '2026-06-15 21:05:04', '2026-06-15 21:05:04'),
(8, 29, 10, 'Phụ thu thêm người lớn', 'violation_fee', 200000.00, 2, 2, 'confirmed', NULL, NULL, NULL, 400000.00, 'Phụ thu phát sinh khi check-in.', '2026-06-15 22:43:23', '2026-06-15 22:43:23'),
(10, 30, 4, 'Nước suối', 'minibar', 7000.00, 3, 1, 'confirmed', 4, '2026-06-16 13:23:23', 'Buồng phòng xác nhận khách sử dụng 1/3 Nước suối.', 7000.00, NULL, '2026-06-16 06:22:10', '2026-06-16 06:23:23'),
(12, 35, 2, 'Ăn sáng buffet', 'service', 120000.00, 3, 3, 'confirmed', NULL, NULL, NULL, 360000.00, NULL, '2026-06-17 18:41:11', '2026-06-17 19:07:26'),
(13, 35, 18, 'Coca Cola', 'minibar', 25000.00, 3, 0, 'pending', NULL, NULL, NULL, 0.00, NULL, '2026-06-17 19:07:38', '2026-06-17 19:07:38'),
(14, 36, 28, 'Phụ thu khách đến muộn', 'violation_fee', 1200000.00, 1, 0, 'pending', NULL, NULL, NULL, 1200000.00, 'Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm.', '2026-06-18 04:42:14', '2026-06-18 04:42:14'),
(15, 36, 29, 'Phụ thu gia hạn lưu trú', 'violation_fee', 720000.00, 1, 1, 'confirmed', 4, '2026-06-18 12:29:36', 'Booking theo giờ, gia hạn thêm 6 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 120.000đ/giờ.', 720000.00, 'Gia hạn từ 18/06/2026 09:00 đến 18/06/2026 15:00. Booking theo giờ, gia hạn thêm 6 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 120.000đ/giờ.', '2026-06-18 05:29:36', '2026-06-18 05:29:36'),
(16, 44, 28, 'Phụ thu khách đến muộn', 'violation_fee', 600000.00, 1, 1, 'confirmed', 4, '2026-06-18 17:36:26', 'Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm.', 600000.00, 'Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm. Giờ nhận phòng chuẩn: 14:00. Giờ trả phòng vẫn giữ nguyên theo booking, không kéo dài do khách đến muộn.', '2026-06-18 10:36:26', '2026-06-18 10:36:26'),
(17, 45, 2, 'Ăn sáng buffet', 'service', 120000.00, 2, 2, 'confirmed', NULL, NULL, NULL, 240000.00, 'Khách tự thêm trên website', '2026-06-18 11:52:44', '2026-06-18 13:03:44'),
(18, 46, 5, 'Bia', 'minibar', 10000.00, 3, 0, 'unused', 4, '2026-06-19 21:57:29', 'Buồng phòng xác nhận khách không sử dụng Bia.', 0.00, NULL, '2026-06-18 13:01:02', '2026-06-19 14:57:29'),
(19, 46, 20, 'Snack', 'minibar', 20000.00, 1, 0, 'unused', 4, '2026-06-19 21:57:29', 'Buồng phòng xác nhận khách không sử dụng Snack.', 0.00, NULL, '2026-06-18 13:01:02', '2026-06-19 14:57:29'),
(20, 45, 18, 'Coca Cola', 'minibar', 25000.00, 1, 1, 'confirmed', 4, '2026-06-18 20:04:31', 'Buồng phòng xác nhận khách sử dụng 1/1 Coca Cola.', 25000.00, NULL, '2026-06-18 13:03:59', '2026-06-18 13:04:31'),
(21, 47, 3, 'Đưa đón sân bay', 'service', 300000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 300000.00, NULL, '2026-06-18 13:43:18', '2026-06-18 13:43:18'),
(22, 49, 30, 'Phụ thu check-in sớm', 'violation_fee', 900000.00, 1, 1, 'confirmed', 4, '2026-06-19 21:53:14', 'Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm.', 900000.00, 'Check-in sớm lúc 19/06/2026 21:53. Đến sớm 16 giờ 6 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm.', '2026-06-19 14:53:14', '2026-06-19 14:53:14'),
(23, 46, 31, 'Phụ thu check-out muộn', 'violation_fee', 5520000.00, 1, 1, 'confirmed', 4, '2026-06-19 21:57:46', 'Booking theo giờ trả muộn 22.96 giờ, tính thêm 23 giờ theo đơn giá tạm tính 240.000đ/giờ.', 5520000.00, 'Giờ check-out dự kiến: 18/06/2026 23:00. Giờ check-out thực tế: 19/06/2026 21:57. Booking theo giờ trả muộn 22.96 giờ, tính thêm 23 giờ theo đơn giá tạm tính 240.000đ/giờ.', '2026-06-19 14:57:46', '2026-06-19 14:57:46'),
(24, 51, 29, 'Phụ thu gia hạn lưu trú', 'violation_fee', 674157.00, 1, 1, 'confirmed', 4, '2026-06-19 22:14:32', 'Booking theo giờ, gia hạn thêm 2 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 337.079đ/giờ.', 674157.00, 'Gia hạn từ 20/06/2026 13:00 đến 20/06/2026 15:00. Booking theo giờ, gia hạn thêm 2 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 337.079đ/giờ.', '2026-06-19 15:14:32', '2026-06-19 15:14:32'),
(25, 52, 30, 'Phụ thu check-in sớm', 'violation_fee', 5000000.00, 1, 1, 'confirmed', 4, '2026-06-19 22:16:43', 'Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm.', 5000000.00, 'Check-in sớm lúc 19/06/2026 22:16. Đến sớm 15 giờ 43 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm.', '2026-06-19 15:16:43', '2026-06-19 15:16:43'),
(26, 52, 3, 'Đưa đón sân bay', 'service', 300000.00, 2, 2, 'confirmed', NULL, NULL, NULL, 600000.00, 'Khách tự thêm trên website', '2026-06-19 15:57:16', '2026-06-19 15:57:16'),
(27, 52, 18, 'Coca Cola', 'minibar', 25000.00, 2, 2, 'confirmed', 4, '2026-06-19 22:59:26', 'Dịch vụ/minibar khách gọi thêm, tính tiền ngay vào booking.', 50000.00, 'Khách tự thêm trên website', '2026-06-19 15:57:37', '2026-06-19 15:59:26'),
(28, 56, 3, 'Đưa đón sân bay', 'service', 300000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 300000.00, NULL, '2026-06-21 08:45:14', '2026-06-21 08:45:14'),
(29, 61, 2, 'Ăn sáng buffet', 'service', 120000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 120000.00, 'Khách tự thêm trên website', '2026-06-21 10:19:15', '2026-06-21 10:19:15'),
(30, 61, 1, 'Giặt là', 'service', 50000.00, 2, 2, 'confirmed', NULL, NULL, NULL, 100000.00, 'Khách tự thêm trên website', '2026-06-21 10:19:23', '2026-06-21 10:19:23'),
(31, 62, 32, 'Buffet sáng', 'service', 180000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi EARLY_UPGRADE: Tặng 1 suất buffet sáng để bù trải nghiệm khi khách phải đổi hạng phòng do đến sớm.', '2026-06-21 16:46:15', '2026-06-21 16:46:15'),
(32, 62, 33, 'Đồ uống chào mừng', 'service', 80000.00, 2, 2, 'confirmed', NULL, NULL, NULL, 160000.00, 'Tự thêm từ mã ưu đãi EARLY_UPGRADE: Tặng 2 phần đồ uống chào mừng trong lúc khách chờ xử lý nhận phòng/đổi hạng.', '2026-06-21 16:46:15', '2026-06-21 16:46:15'),
(33, 62, 30, 'Phụ thu check-in sớm', 'violation_fee', 1800000.00, 1, 1, 'confirmed', 4, '2026-06-21 23:46:53', 'Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm.', 1800000.00, 'Check-in sớm lúc 21/06/2026 23:46. Đến sớm 14 giờ 13 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm.', '2026-06-21 16:46:53', '2026-06-21 16:46:53'),
(34, 63, 32, 'Buffet sáng', 'service', 180000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi WELCOME200BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-06-22 03:46:50', '2026-06-22 03:46:50'),
(35, 64, 32, 'Buffet sáng', 'service', 180000.00, 1, 1, 'confirmed', NULL, NULL, NULL, 180000.00, 'Tự thêm từ mã ưu đãi WELCOME200BF: Tặng 1 suất buffet sáng miễn phí khi dùng mã WELCOME200BF.', '2026-06-22 03:53:42', '2026-06-22 03:53:42'),
(36, 76, 4, 'Nước suối', 'minibar', 7000.00, 1, 1, 'confirmed', 4, '2026-07-10 19:35:11', 'Dịch vụ/minibar khách gọi thêm, tính tiền ngay vào booking.', 7000.00, NULL, '2026-07-10 12:35:11', '2026-07-10 12:35:11');

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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 1, NULL, 5, 'Gán cho nhân viên đang xử lý ít khách nhất', '2026-06-22 06:48:23', '2026-06-22 06:48:23'),
(2, 1, 5, 5, 'Chuyển cuộc trò chuyện cho nhân viên khác', '2026-06-22 06:48:40', '2026-06-22 06:48:40'),
(3, 2, NULL, 7, 'Gán cho nhân viên đang xử lý ít khách nhất', '2026-07-10 08:27:15', '2026-07-10 08:27:15');

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
(1, 9, 'local', 'chat-attachments/2026/07/10/1d01d69c-d770-45e8-ba21-2f7b31acf81a.png', 'Screenshot 2026-07-10 141037.png', 'image/png', 'png', 41674, 'image', '2026-07-10 09:45:13', '2026-07-10 09:45:13'),
(2, 10, 'local', 'chat-attachments/2026/07/10/87d35e43-b3c9-47d7-8d56-fd1d97f90c89.png', 'Screenshot 2026-07-10 141037.png', 'image/png', 'png', 41674, 'image', '2026-07-10 09:45:13', '2026-07-10 09:45:13'),
(3, 11, 'local', 'chat-attachments/2026/07/10/4abfb307-2eb0-4cbb-8899-5bc95163164a.png', 'Screenshot 2026-07-10 141037.png', 'image/png', 'png', 41674, 'image', '2026-07-10 09:45:19', '2026-07-10 09:45:19'),
(4, 12, 'local', 'chat-attachments/2026/07/10/eac14e44-c0b5-4e05-8292-b3e44ac2f779.png', 'Screenshot (881).png', 'image/png', 'png', 2629869, 'image', '2026-07-10 09:45:27', '2026-07-10 09:45:27'),
(5, 13, 'local', 'chat-attachments/2026/07/10/6e75232f-04d9-4567-b2f1-d07a1f6c5f52.zip', 'WorkShop6.zip', 'application/zip', 'zip', 20582, 'file', '2026-07-10 09:45:36', '2026-07-10 09:45:36'),
(6, 17, 'local', 'chat-attachments/2026/07/10/f210bf42-4bbf-4482-9580-39304c53b3af.png', 'Screenshot (846).png', 'image/png', 'png', 2555070, 'image', '2026-07-10 10:14:52', '2026-07-10 10:14:52'),
(7, 17, 'local', 'chat-attachments/2026/07/10/3c337cc3-dbeb-4718-903b-10bed31d500d.png', 'Screenshot (847).png', 'image/png', 'png', 2627726, 'image', '2026-07-10 10:14:52', '2026-07-10 10:14:52'),
(8, 19, 'local', 'chat-attachments/2026/07/10/34ae05f1-c4ab-46c6-b8d5-b084c7d37f60.png', 'Screenshot (845).png', 'image/png', 'png', 1591581, 'image', '2026-07-10 10:16:13', '2026-07-10 10:16:13'),
(9, 23, 'local', 'chat-attachments/2026/07/10/6b3ad335-9759-415d-b20c-8af174295abb.png', 'Screenshot (845).png', 'image/png', 'png', 1591581, 'image', '2026-07-10 10:41:30', '2026-07-10 10:41:30'),
(10, 35, 'local', 'chat-attachments/2026/07/11/87965f11-ae88-4277-ad3f-811f6deab79c.png', 'Screenshot (866).png', 'image/png', 'png', 485851, 'image', '2026-07-11 04:58:36', '2026-07-11 04:58:36');

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
(1, 8, NULL, 5, NULL, NULL, NULL, 'active', 90, '2026-07-11 04:58:41', NULL, '2026-06-22 06:48:23', '2026-07-11 04:58:41'),
(2, 13, NULL, 7, NULL, NULL, NULL, 'active', 90, '2026-07-10 15:38:03', NULL, '2026-07-10 08:27:15', '2026-07-10 15:38:03');

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
(1, 1, 'customer', 8, 'Tôi muốn hỏi còn phòng hôm nay không?', 1, '2026-07-10 10:32:02', '2026-06-22 06:48:23', '2026-07-10 10:32:02'),
(2, 1, 'staff', 4, 'bạn muốn hỏi hạng phòng nào ạ?', 0, NULL, '2026-06-22 07:09:04', '2026-06-22 07:09:04'),
(3, 1, 'customer', 8, 'Tôi cần hỗ trợ thanh toán/cọc VNPay.', 1, '2026-07-10 10:32:02', '2026-06-30 08:07:52', '2026-07-10 10:32:02'),
(4, 1, 'staff', 5, 'fegbfsno', 0, NULL, '2026-06-30 08:45:37', '2026-06-30 08:45:37'),
(5, 2, 'customer', 13, 'Tôi muốn hỏi còn phòng hôm nay không?', 1, '2026-07-10 10:31:06', '2026-07-10 08:27:15', '2026-07-10 10:31:06'),
(6, 2, 'staff', 4, 'bạn cần hạng nào?', 0, NULL, '2026-07-10 08:27:39', '2026-07-10 08:27:39'),
(7, 2, 'customer', 13, 'hạng sang nhất', 1, '2026-07-10 10:31:06', '2026-07-10 08:28:05', '2026-07-10 10:31:06'),
(8, 2, 'staff', 4, 'còn ạ', 0, NULL, '2026-07-10 09:06:51', '2026-07-10 09:06:51'),
(9, 2, 'customer', 13, 'àgrtry', 1, '2026-07-10 10:31:06', '2026-07-10 09:45:11', '2026-07-10 10:31:06'),
(10, 2, 'customer', 13, 'àgrtry', 1, '2026-07-10 10:31:06', '2026-07-10 09:45:13', '2026-07-10 10:31:06'),
(11, 2, 'customer', 13, 'àgrtry', 1, '2026-07-10 10:31:06', '2026-07-10 09:45:19', '2026-07-10 10:31:06'),
(12, 2, 'customer', 13, 'àgrtry', 1, '2026-07-10 10:31:06', '2026-07-10 09:45:27', '2026-07-10 10:31:06'),
(13, 2, 'customer', 13, 'àgrtry', 1, '2026-07-10 10:31:06', '2026-07-10 09:45:36', '2026-07-10 10:31:06'),
(14, 2, 'customer', 13, 'àgrtry', 1, '2026-07-10 10:31:06', '2026-07-10 09:45:50', '2026-07-10 10:31:06'),
(15, 2, 'customer', 13, 'ghygtrerfw', 1, '2026-07-10 10:31:06', '2026-07-10 10:09:42', '2026-07-10 10:31:06'),
(16, 2, 'staff', 4, 'aaaaa', 0, NULL, '2026-07-10 10:14:23', '2026-07-10 10:14:23'),
(17, 2, 'customer', 13, 'sssss', 1, '2026-07-10 10:31:06', '2026-07-10 10:14:52', '2026-07-10 10:31:06'),
(18, 2, 'customer', 13, 'aaw', 1, '2026-07-10 10:31:06', '2026-07-10 10:15:14', '2026-07-10 10:31:06'),
(19, 2, 'staff', 4, 'dèwergtr', 0, NULL, '2026-07-10 10:16:13', '2026-07-10 10:16:13'),
(20, 2, 'customer', 13, 'aesfffd', 1, '2026-07-10 10:31:06', '2026-07-10 10:22:29', '2026-07-10 10:31:06'),
(21, 2, 'staff', 4, 'dèwdcas', 0, NULL, '2026-07-10 10:22:55', '2026-07-10 10:22:55'),
(22, 2, 'customer', 13, 'tryuiyt', 1, '2026-07-10 10:31:06', '2026-07-10 10:22:59', '2026-07-10 10:31:06'),
(23, 2, 'customer', 13, 'frgtrfwed', 1, '2026-07-10 10:41:31', '2026-07-10 10:41:30', '2026-07-10 10:41:31'),
(24, 2, 'customer', 13, 'frg', 1, '2026-07-10 10:42:26', '2026-07-10 10:41:43', '2026-07-10 10:42:26'),
(25, 2, 'customer', 13, 'dèwr', 1, '2026-07-10 10:42:26', '2026-07-10 10:42:23', '2026-07-10 10:42:26'),
(26, 2, 'staff', 4, 'ẻghbfrwnjc', 0, NULL, '2026-07-10 10:42:44', '2026-07-10 10:42:44'),
(27, 2, 'staff', 4, 'qrewtyukujyhter', 0, NULL, '2026-07-10 10:42:53', '2026-07-10 10:42:53'),
(28, 2, 'staff', 4, 'qrewtyukujyhter', 0, NULL, '2026-07-10 10:52:45', '2026-07-10 10:52:45'),
(29, 2, 'staff', 4, 'vgbhjn', 0, NULL, '2026-07-10 10:53:04', '2026-07-10 10:53:04'),
(30, 2, 'staff', 4, 'dxcfvg', 0, NULL, '2026-07-10 10:55:36', '2026-07-10 10:55:36'),
(31, 2, 'customer', 13, 'ẻgethr', 1, '2026-07-10 15:37:58', '2026-07-10 15:37:57', '2026-07-10 15:37:58'),
(32, 2, 'staff', 4, 'eregt', 0, NULL, '2026-07-10 15:38:03', '2026-07-10 15:38:03'),
(33, 1, 'customer', 8, 'hello', 1, '2026-07-11 04:57:57', '2026-07-11 04:57:50', '2026-07-11 04:57:57'),
(34, 1, 'staff', 4, 'hi', 0, NULL, '2026-07-11 04:58:10', '2026-07-11 04:58:10'),
(35, 1, 'customer', 8, 'điều hòa hư rồi', 1, '2026-07-11 04:58:37', '2026-07-11 04:58:36', '2026-07-11 04:58:37'),
(36, 1, 'staff', 4, 'ô kê', 0, NULL, '2026-07-11 04:58:41', '2026-07-11 04:58:41');

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
(1, 4, 'Chiến', 'Trịnh', '0985795608', '036206022002', 'chientr33@gmail.com', '2006-09-19', 'male', 'Huyện Yên Định', NULL, NULL, 'active', '2026-06-08 22:31:01', '2026-06-08 22:31:25', NULL),
(2, NULL, 'Linh', 'Văn', '0985795123', '038206022123', 'vlinh33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-11 06:14:43', '2026-06-11 06:14:43', NULL),
(3, NULL, 'Hiệp', '', '0985123456', '038206022456', 'hiep33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-11 09:42:51', '2026-06-11 09:46:14', NULL),
(4, 8, 'Văn', 'Linh', '0985795111', '036206022111', 'vlinh319@gmail.com', '2006-07-14', 'female', 'Lê Hoàn', NULL, NULL, 'active', '2026-06-13 01:37:37', '2026-06-25 07:17:15', NULL),
(5, 9, 'Trịnh', 'Chiến', '0985795777', '038206022444', 'chientrinh3@gmail.com', '2026-06-01', 'male', 'Định Tân', NULL, NULL, 'active', '2026-06-13 19:32:52', '2026-06-13 19:32:52', NULL),
(6, 10, 'Trịnh', 'a', '0985795999', '038206022888', 'chientrinh1@gmail.com', '2026-06-12', 'male', 'Định Tân', NULL, NULL, 'active', '2026-06-13 19:34:31', '2026-06-13 19:34:31', NULL),
(7, NULL, 'Pham', 'Hiep', '0985795555', '038206022555', 'hiep111@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-15 21:04:23', '2026-06-15 21:04:23', NULL),
(8, NULL, 'p', 'hiep', '0985795666', '038206022666', 'hiepp33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-16 07:02:26', '2026-06-16 07:02:26', NULL),
(9, NULL, 'Trịnh', 'Chiến', '0985795579', '038206022579', 'ct33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-17 19:55:46', '2026-06-17 19:55:46', NULL),
(10, NULL, 'a', 'Nguyen', '0985795680', '038206022680', 'nguyena33@gmail.com', NULL, NULL, 'rfgthhyg', NULL, NULL, 'active', '2026-06-17 19:57:47', '2026-06-17 19:57:47', NULL),
(11, NULL, 'B', 'tran', '0985795135', '038206022135', 'chientr33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-17 20:07:51', '2026-06-17 20:07:51', NULL),
(12, NULL, 'qư', 'Nguyen', '0985123608', '038212322456', 'nguyenq33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-18 09:40:30', '2026-06-18 09:40:30', NULL),
(13, NULL, 'a', 'van', '0985753608', '038206022753', 'vc33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-18 09:56:08', '2026-06-18 09:56:08', NULL),
(14, 11, 'Đào', 'Du', '0985795457', '038245722002', 'du319@gmail.com', '2026-06-19', 'male', 'Định Tân', NULL, NULL, 'active', '2026-06-18 12:28:59', '2026-06-18 12:28:59', NULL),
(15, 12, 'Nguyễn', 'Anh', '0985795325', '038232522002', 'anh319@gmail.com', '2026-06-21', 'male', 'Định Tân', NULL, NULL, 'active', '2026-06-18 12:29:46', '2026-06-18 12:46:18', NULL),
(16, NULL, 'a', 'aa', '0985795056', '038206022056', 'a2tvdu33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-18 12:52:52', '2026-06-18 12:52:52', NULL),
(17, NULL, 'a', 'Tran', '0985795157', '038206022157', 'nguyena157@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-19 15:13:23', '2026-06-19 15:13:23', NULL),
(18, NULL, 'P', 'Nguyen Van', '0985795640', '038206022640', 'vanp33@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-07-10 12:30:45', '2026-07-10 12:30:45', NULL),
(19, 13, 'Chiến', 'Trịnh', '0985795298', '036206022298', 'tc19092006@gmail.com', '1988-10-16', 'male', 'Huyện Yên Định', NULL, NULL, 'active', '2026-07-10 15:22:38', '2026-07-10 15:22:38', NULL);

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
  `location_rating` tinyint(3) UNSIGNED DEFAULT NULL,
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

INSERT INTO `hotel_reviews` (`id`, `booking_id`, `user_id`, `customer_id`, `room_category_id`, `rating`, `cleanliness_rating`, `service_rating`, `location_rating`, `value_rating`, `title`, `comment`, `status`, `approved_by`, `approved_at`, `hidden_by`, `hidden_at`, `hidden_reason`, `admin_reply`, `replied_by`, `replied_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 73, 8, 4, 2, 4, 4, 4, 4, 4, 'Nhân viên tốt', 'Trải nghiệm tốt, nhân viên chuyên nghiệp', 'approved', 4, '2026-06-30 04:37:17', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-30 04:36:29', '2026-06-30 04:37:17', NULL);

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
(1, 'WELCOME10', 'Mã thường cho khách', 'Khách tự chọn được khi đặt phòng online.', 'normal_discount', 'percent', 10.00, 200000.00, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 0.00, 0, 0, 0, 0.00, 500, 1, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-06-21 15:17:00'),
(2, 'EVENT15', 'Mã sự kiện', 'Áp dụng cho dịp/sự kiện khách sạn chỉ định.', 'event_discount', 'percent', 15.00, 300000.00, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 1000000.00, 0, 0, 0, 0.00, 200, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-06-21 13:56:04'),
(3, 'SUPPORT100K', 'Mã hỗ trợ khách', 'Chỉ admin/lễ tân dùng khi cần hỗ trợ khách vì sự cố thực tế.', 'support_discount', 'fixed_amount', 100000.00, NULL, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 1, NULL, 0, 0, 1, 1, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-06-21 16:46:16'),
(4, 'STAY2NIGHT', 'Ở từ 2 đêm', 'Mã điều kiện: booking từ 2 đêm trở lên.', 'conditional_discount', 'fixed_amount', 150000.00, NULL, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, NULL, 0.00, 2, 0, 0, 0.00, 300, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 13:56:04', '2026-06-21 13:56:04'),
(5, 'WELCOME200BF', 'Giảm 200k + buffet sáng miễn phí', 'Mã test: giảm trực tiếp 200.000đ và tặng 1 suất buffet sáng 100%. Khách online và admin đều có thể áp dụng.', 'normal_discount', 'fixed_amount', 200000.00, NULL, '2026-06-21 23:29:13', '2026-09-19 23:29:13', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 2, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 16:29:13', '2026-06-22 03:53:42'),
(6, 'FAMILY10DECOR', 'Family 10% + giảm 50% trang trí', 'Mã test: giảm 10% tiền booking, tối đa 300.000đ, kèm giảm 50% dịch vụ trang trí sinh nhật.', 'event_discount', 'percent', 10.00, 300000.00, '2026-06-21 23:29:13', '2026-09-19 23:29:13', NULL, NULL, 1000000.00, 1, 0, 0, 0.00, 100, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(7, 'EARLY_UPGRADE', 'Hỗ trợ đổi hạng khi khách đến sớm', 'Mã hỗ trợ nội bộ: dùng khi khách đến check-in sớm nhưng hạng phòng đã đặt chưa có phòng sẵn. Lễ tân đổi sang hạng còn phòng rồi áp mã để hỗ trợ trải nghiệm khách.', 'support_discount', 'fixed_amount', 150000.00, NULL, '2026-06-21 23:29:13', '2026-09-19 23:29:13', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 1, NULL, 0, 0, 1, 1, 1, 'active', NULL, '2026-06-21 16:29:13', '2026-06-21 16:46:16'),
(8, 'DEMO200K', 'Demo giảm trực tiếp 200k', 'Mã thường: giảm thẳng 200.000đ trên tổng booking.', 'normal_discount', 'fixed_amount', 200000.00, NULL, '2026-06-25 14:21:25', '2026-09-23 14:21:25', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:25', '2026-06-25 07:21:25'),
(9, 'DEMO_EVENT10', 'Demo sự kiện giảm 10%', 'Mã sự kiện: giảm 10% tổng booking, tối đa 300.000đ.', 'event_discount', 'percent', 10.00, 300000.00, '2026-06-25 14:21:25', '2026-09-23 14:21:25', NULL, NULL, 1000000.00, 0, 0, 0, 0.00, 100, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:25', '2026-06-25 07:21:25'),
(10, 'DEMO_FREE_BF', 'Demo tặng buffet sáng', 'Mã freebies: tự động tặng 1 suất buffet sáng, không giảm tiền phòng.', 'normal_discount', 'fixed_amount', 0.00, NULL, '2026-06-25 14:21:26', '2026-09-23 14:21:26', NULL, NULL, 0.00, 0, 0, 0, 0.00, 100, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:26', '2026-06-25 07:21:26'),
(11, 'DEMO_UPGRADE20', 'Demo upsell nâng hạng 20%', 'Mã điều kiện: giảm 20% phần chênh lệch khi khách chủ động nâng lên hạng cao hơn. Khách vẫn trả phần chênh còn lại.', 'conditional_discount', 'fixed_amount', 0.00, NULL, '2026-06-25 14:21:26', '2026-09-23 14:21:26', NULL, NULL, 0.00, 1, 1, 0, 0.00, 100, 0, 1, 1, 1, 1, 0, 1, 'active', NULL, '2026-06-25 07:21:26', '2026-06-25 07:21:26'),
(12, 'DEMO_INCIDENT_FULL', 'Demo hỗ trợ nâng hạng do sự cố', 'Mã hỗ trợ: dùng khi phòng lỗi/hết phòng cùng hạng, khách sạn chịu toàn bộ tiền chênh nâng hạng. Khách không trả thêm tiền.', 'support_discount', 'fixed_amount', 0.00, NULL, '2026-06-25 14:21:00', '2026-09-23 14:21:00', NULL, NULL, 0.00, 0, 0, 0, 0.00, NULL, 0, NULL, 0, 0, 1, 1, 1, 'active', NULL, '2026-06-25 07:21:26', '2026-06-25 07:21:47');

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
(1, 11, 'paid_upsell', NULL, NULL, 'percent_difference', 20.00, NULL, 0, 1, 0, 'Demo mã điều kiện upsell: giảm 20% phần chênh lệch nâng hạng, khách trả phần còn lại.', '2026-06-25 07:21:26', '2026-06-25 07:21:26'),
(3, 12, 'incident_support', NULL, NULL, 'full_difference', 100.00, NULL, 1, 0, 0, 'Demo mã hỗ trợ sự cố: khách sạn chịu toàn bộ tiền chênh, khách không trả thêm.', '2026-06-25 07:21:47', '2026-06-25 07:21:47');

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
(5, 10, 34, 'percent', 100.00, 1, 1, 'Tặng 1 suất buffet sáng miễn phí khi dùng mã DEMO_FREE_BF.', '2026-06-25 07:21:26', '2026-06-25 07:21:26'),
(7, 12, 35, 'percent', 100.00, 2, 1, 'Tặng 2 welcome drink để bù trải nghiệm khi khách gặp sự cố phải đổi/nâng hạng.', '2026-06-25 07:21:47', '2026-06-25 07:21:47');

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
(1, '101', 1, 1, 'available', NULL, NULL, NULL, '2026-06-08 22:25:58', '2026-06-18 10:37:05', NULL),
(2, '102', 1, 1, 'available', NULL, NULL, NULL, '2026-06-08 22:26:07', '2026-06-18 10:35:14', NULL),
(3, '103', 1, 1, 'available', NULL, NULL, NULL, '2026-06-08 22:26:18', '2026-06-18 07:03:41', NULL),
(4, '201', 2, 2, 'cleaning', NULL, NULL, NULL, '2026-06-08 22:26:38', '2026-06-30 04:35:05', NULL),
(5, '202', 2, 2, 'maintenance', NULL, NULL, NULL, '2026-06-08 22:26:49', '2026-06-18 10:00:51', NULL),
(6, '203', 2, 2, 'maintenance', NULL, NULL, NULL, '2026-06-08 22:26:59', '2026-06-18 10:00:54', NULL),
(7, '301', 3, 3, 'available', NULL, NULL, NULL, '2026-06-08 22:27:09', '2026-06-30 04:32:09', NULL),
(8, '302', 3, 3, 'available', NULL, NULL, NULL, '2026-06-08 22:27:15', '2026-06-30 04:32:15', NULL),
(9, '401', 4, 4, 'available', NULL, NULL, NULL, '2026-06-08 22:27:46', '2026-06-22 08:04:45', NULL),
(10, '402', 1, 4, 'cleaning', NULL, NULL, NULL, '2026-06-08 22:33:44', '2026-07-10 12:36:12', NULL),
(11, '403', 1, 4, 'available', NULL, NULL, NULL, '2026-06-11 06:12:48', '2026-06-18 07:04:18', NULL),
(12, '404', 1, 4, 'available', NULL, NULL, NULL, '2026-06-11 06:12:57', '2026-06-18 07:04:22', NULL),
(13, '405', 1, 4, 'available', NULL, NULL, NULL, '2026-06-11 06:13:05', '2026-06-18 07:04:25', NULL),
(14, '406', 2, 4, 'available', NULL, NULL, NULL, '2026-06-21 05:21:55', '2026-07-11 12:36:50', NULL);

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
(1, 'Deluxe Sea View', 1200000.00, 2, 1, 35.00, 1, 'Phòng hướng biển với ban công riêng, nội thất hiện đại, phù hợp cho cặp đôi hoặc khách du lịch nghỉ dưỡng.', 'room-categories/thumbnails/nCAIuDVsic8stfuOUohW1SFAQCecUhp6PBgdC0Qu.png', 'active', '2026-06-08 22:17:35', '2026-06-08 22:22:03'),
(2, 'Superior Double', 900000.00, 2, 1, 28.00, 1, 'Phòng tiêu chuẩn với đầy đủ tiện nghi cơ bản, phù hợp cho khách công tác và du lịch ngắn ngày.', 'room-categories/thumbnails/PiKz8SyPlXFPySlWhkMc5qw5UGfr0HgX0I0A2O8c.jpg', 'active', '2026-06-08 22:23:06', '2026-06-08 22:23:06'),
(3, 'Family Suite', 1800000.00, 4, 2, 55.00, 2, 'Phòng gia đình rộng rãi với không gian sinh hoạt chung, thích hợp cho gia đình hoặc nhóm bạn.', 'room-categories/thumbnails/l8aPysA7c8gex7cNLqXwfVKgFGb6E3QryLATjmuG.jpg', 'active', '2026-06-08 22:24:15', '2026-06-08 22:24:15'),
(4, 'Presidential Suite', 5000000.00, 6, 2, 120.00, 3, 'Hạng phòng cao cấp nhất với phòng khách riêng, bồn tắm cao cấp và tầm nhìn toàn cảnh thành phố.', 'room-categories/thumbnails/ZFKLQSV5wfce7smLKStD2J0rRLVCiWlcZP0ISJxZ.jpg', 'active', '2026-06-08 22:25:21', '2026-06-08 22:25:21');

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
(16, 4, 1, NULL, NULL);

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
(14, 4, 'room-categories/albums/KVTHm71gAMgspX5hdeaMZctuvdaUA8bcsiIzRKPv.jpg', '2026-06-08 22:25:21', '2026-06-08 22:25:21');

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
  `has_damage` tinyint(1) NOT NULL DEFAULT 0,
  `damage_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`damage_items`)),
  `damage_total` decimal(12,2) NOT NULL DEFAULT 0.00,
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

INSERT INTO `room_inspections` (`id`, `booking_id`, `room_id`, `inspected_by`, `confirmed_by`, `status`, `has_damage`, `damage_items`, `damage_total`, `inspection_note`, `admin_note`, `inspected_at`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(1, 12, 2, 4, 4, 'confirmed', 1, '[{\"service_id\":6,\"name\":\"V\\u01a1\\u0303 ly thu\\u0309y tinh\",\"unit\":\"ca\\u0301i\",\"price\":\"50000.00\",\"quantity\":5,\"total\":250000}]', 250000.00, NULL, NULL, '2026-06-11 07:39:26', '2026-06-11 07:39:54', '2026-06-11 07:23:16', '2026-06-11 07:39:54'),
(2, 8, 9, 4, 4, 'confirmed', 1, NULL, 100000.00, NULL, NULL, '2026-06-11 08:09:39', '2026-06-11 08:10:11', '2026-06-11 07:45:58', '2026-06-11 08:10:11'),
(3, 13, 9, 4, 4, 'confirmed', 1, NULL, 100000.00, NULL, NULL, '2026-06-11 08:34:11', '2026-06-11 08:34:21', '2026-06-11 08:33:55', '2026-06-11 08:34:21'),
(4, 14, 3, 4, 4, 'confirmed', 1, NULL, 3000000.00, NULL, NULL, '2026-06-11 08:45:39', '2026-06-11 08:45:48', '2026-06-11 08:45:28', '2026-06-11 08:45:48'),
(5, 16, 4, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-11 09:46:33', '2026-06-11 09:46:42', '2026-06-11 09:46:27', '2026-06-11 09:46:42'),
(6, 17, 5, 4, 4, 'confirmed', 1, NULL, 150000.00, NULL, NULL, '2026-06-11 09:47:20', '2026-06-11 09:47:29', '2026-06-11 09:47:02', '2026-06-11 09:47:29'),
(7, 17, 6, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-11 09:47:12', '2026-06-11 09:47:33', '2026-06-11 09:47:02', '2026-06-11 09:47:33'),
(8, 21, 9, 4, 4, 'confirmed', 1, NULL, 200000.00, NULL, NULL, '2026-06-13 01:47:54', '2026-06-13 01:48:05', '2026-06-13 01:47:41', '2026-06-13 01:48:05'),
(9, 28, 6, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-15 20:38:44', '2026-06-15 20:38:52', '2026-06-15 20:38:23', '2026-06-15 20:38:53'),
(10, 29, 4, 4, 4, 'confirmed', 1, NULL, 150000.00, NULL, NULL, '2026-06-16 06:20:21', '2026-06-16 06:20:35', '2026-06-16 04:54:09', '2026-06-16 06:20:35'),
(11, 30, 4, 4, 4, 'confirmed', 1, NULL, 70000.00, NULL, NULL, '2026-06-16 06:23:23', '2026-06-16 06:23:43', '2026-06-16 06:22:58', '2026-06-16 06:23:43'),
(12, 31, 6, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-16 06:54:52', '2026-06-16 06:54:58', '2026-06-16 06:54:42', '2026-06-16 06:54:58'),
(13, 32, 4, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-16 07:24:19', '2026-06-16 07:24:26', '2026-06-16 07:24:10', '2026-06-16 07:24:26'),
(14, 36, 1, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-18 06:13:03', '2026-06-18 06:13:10', '2026-06-18 06:12:52', '2026-06-18 06:13:10'),
(15, 44, 1, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-18 10:36:47', '2026-06-18 10:36:54', '2026-06-18 10:36:38', '2026-06-18 10:36:54'),
(16, 41, 4, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-18 12:50:42', '2026-06-18 12:50:51', '2026-06-18 12:50:32', '2026-06-18 12:50:51'),
(17, 45, 4, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-18 13:04:31', '2026-06-18 13:04:45', '2026-06-18 13:04:08', '2026-06-18 13:04:45'),
(18, 48, 9, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-19 14:04:49', '2026-06-19 14:05:11', '2026-06-18 13:56:21', '2026-06-19 14:05:11'),
(19, 49, 4, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-19 14:53:58', '2026-06-19 14:54:03', '2026-06-19 14:53:51', '2026-06-19 14:54:03'),
(20, 46, 7, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-19 14:57:29', '2026-06-19 14:57:36', '2026-06-19 14:57:12', '2026-06-19 14:57:36'),
(21, 51, 9, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-19 15:15:22', '2026-06-19 15:15:32', '2026-06-19 15:14:58', '2026-06-19 15:15:32'),
(22, 52, 9, 4, 4, 'confirmed', 1, NULL, 100000.00, NULL, NULL, '2026-06-19 15:58:30', '2026-06-19 15:58:45', '2026-06-19 15:58:04', '2026-06-19 15:58:45'),
(23, 62, 7, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-22 03:41:10', '2026-06-22 03:41:18', '2026-06-22 03:40:46', '2026-06-22 03:41:18'),
(24, 67, 8, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-22 09:23:35', '2026-06-22 09:23:41', '2026-06-22 09:23:26', '2026-06-22 09:23:41'),
(25, 73, 4, 4, 4, 'confirmed', 0, NULL, 0.00, NULL, NULL, '2026-06-30 04:34:30', '2026-06-30 04:34:39', '2026-06-30 04:34:20', '2026-06-30 04:34:39'),
(26, 76, 10, 4, 4, 'confirmed', 1, NULL, 50000.00, NULL, NULL, '2026-07-10 12:35:34', '2026-07-10 12:35:48', '2026-07-10 12:35:17', '2026-07-10 12:35:48');

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
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `room_inspection_items`
--

INSERT INTO `room_inspection_items` (`id`, `room_inspection_id`, `service_id`, `type`, `name`, `unit`, `price`, `quantity`, `total`, `status`, `admin_note`, `created_at`, `updated_at`) VALUES
(3, 2, 7, 'damage_fee', 'Hỏng TV', 'lần', 3000000.00, 1, 3000000.00, 'rejected', 'TV hư sẵn', '2026-06-11 08:09:39', '2026-06-11 08:10:11'),
(4, 2, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 2, 100000.00, 'approved', NULL, '2026-06-11 08:09:39', '2026-06-11 08:10:11'),
(5, 3, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 2, 100000.00, 'approved', NULL, '2026-06-11 08:34:11', '2026-06-11 08:34:21'),
(6, 4, 7, 'damage_fee', 'Hỏng TV', 'lần', 3000000.00, 1, 3000000.00, 'approved', NULL, '2026-06-11 08:45:39', '2026-06-11 08:45:48'),
(7, 6, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 3, 150000.00, 'approved', NULL, '2026-06-11 09:47:20', '2026-06-11 09:47:29'),
(8, 8, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 4, 200000.00, 'approved', NULL, '2026-06-13 01:47:54', '2026-06-13 01:48:05'),
(12, 10, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 3, 150000.00, 'approved', NULL, '2026-06-16 06:20:21', '2026-06-16 06:20:35'),
(13, 11, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 'approved', NULL, '2026-06-16 06:23:23', '2026-06-16 06:23:43'),
(14, 11, 5, 'damage_fee', 'Bia', 'lon', 10000.00, 2, 20000.00, 'approved', NULL, '2026-06-16 06:23:23', '2026-06-16 06:23:43'),
(15, 17, 5, 'damage_fee', 'Bia', 'lon', 10000.00, 1, 10000.00, 'rejected', NULL, '2026-06-18 13:04:31', '2026-06-18 13:04:45'),
(16, 17, 18, 'damage_fee', 'Coca Cola', 'lon', 25000.00, 1, 25000.00, 'rejected', NULL, '2026-06-18 13:04:31', '2026-06-18 13:04:45'),
(18, 21, 5, 'damage_fee', 'Bia', 'lon', 10000.00, 3, 30000.00, 'rejected', NULL, '2026-06-19 15:15:22', '2026-06-19 15:15:32'),
(22, 22, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 'approved', NULL, '2026-06-19 15:58:30', '2026-06-19 15:58:45'),
(23, 22, 5, 'minibar', 'Bia', 'lon', 10000.00, 1, 10000.00, 'approved', NULL, '2026-06-19 15:58:30', '2026-06-19 15:58:45'),
(24, 22, 20, 'minibar', 'Snack', 'gói', 20000.00, 2, 40000.00, 'approved', NULL, '2026-06-19 15:58:30', '2026-06-19 15:58:45'),
(25, 26, 6, 'damage_fee', 'Vỡ ly thủy tinh', 'cái', 50000.00, 1, 50000.00, 'approved', NULL, '2026-07-10 12:35:34', '2026-07-10 12:35:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('service','minibar','damage_fee','occupancy_fee','policy_violation_fee') NOT NULL,
  `service_group` enum('general','food_drink','vehicle','laundry','transport','wellness','room_support','other') NOT NULL DEFAULT 'general',
  `price` decimal(12,2) NOT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'lần',
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `services`
--

INSERT INTO `services` (`id`, `name`, `type`, `service_group`, `price`, `unit`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Giặt là', 'service', 'general', 50000.00, 'lần', 'Dịch vụ giặt và sấy quần áo cho khách lưu trú.', 'active', '2026-06-08 22:11:48', '2026-06-08 22:11:48'),
(2, 'Ăn sáng buffet', 'service', 'general', 120000.00, 'người', 'Suất ăn sáng buffet tại nhà hàng khách sạn.', 'active', '2026-06-08 22:12:39', '2026-06-08 22:12:39'),
(3, 'Đưa đón sân bay', 'service', 'general', 300000.00, 'lượt', 'Xe đưa đón khách từ sân bay về khách sạn hoặc ngược lại.', 'active', '2026-06-08 22:13:06', '2026-06-17 18:38:41'),
(4, 'Nước suối', 'minibar', 'food_drink', 7000.00, 'chai', 'Nước suối trong minibar tại phòng.', 'active', '2026-06-08 22:13:45', '2026-06-08 22:13:45'),
(5, 'Bia', 'minibar', 'food_drink', 10000.00, 'lon', 'Bia lon trong minibar, tính theo số lượng sử dụng.', 'active', '2026-06-08 22:14:12', '2026-06-08 22:14:12'),
(6, 'Vỡ ly thủy tinh', 'damage_fee', 'other', 50000.00, 'cái', 'Phí bồi thường khi khách làm vỡ ly trong phòng.', 'active', '2026-06-08 22:14:45', '2026-06-08 22:14:45'),
(7, 'Hỏng TV', 'damage_fee', 'other', 3000000.00, 'lần', 'Phí bồi thường khi khách làm hư hỏng TV trong phòng.', 'active', '2026-06-08 22:15:14', '2026-06-08 22:15:14'),
(10, 'Phụ thu thêm người lớn', 'occupancy_fee', 'other', 200000.00, 'người', 'Phụ thu khi khách phát sinh thêm người lớn lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-17 18:39:07'),
(11, 'Phụ thu thêm trẻ em', 'occupancy_fee', 'other', 100000.00, 'trẻ', 'Phụ thu khi khách phát sinh thêm trẻ em lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-15 22:35:34'),
(12, 'Phụ thu em bé', 'occupancy_fee', 'other', 50000.00, 'bé', 'Phụ thu khi khách phát sinh thêm em bé lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-15 22:35:34'),
(16, 'Trang trí sinh nhật', 'service', 'general', 300000.00, 'lần', 'Trang trí phòng theo yêu cầu', 'active', '2026-06-17 18:03:20', '2026-06-17 18:03:20'),
(18, 'Coca Cola', 'minibar', 'food_drink', 25000.00, 'lon', 'Nước ngọt trong minibar', 'active', '2026-06-17 18:03:28', '2026-06-17 18:03:28'),
(20, 'Snack', 'minibar', 'food_drink', 20000.00, 'gói', 'Đồ ăn nhẹ trong minibar', 'active', '2026-06-17 18:03:28', '2026-06-17 18:03:28'),
(22, 'Mất thẻ phòng', 'policy_violation_fee', 'other', 100000.00, 'thẻ', 'Phí bồi thường mất thẻ phòng', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(23, 'Bẩn ga giường nặng', 'policy_violation_fee', 'other', 150000.00, 'lần', 'Phí xử lý vệ sinh ga giường bẩn nặng', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(24, 'Hỏng remote điều hòa', 'policy_violation_fee', 'other', 200000.00, 'cái', 'Phí bồi thường remote điều hòa', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(27, 'Hút thuốc trong phòng', 'policy_violation_fee', 'other', 300000.00, 'lần', 'Phí xử lý mùi thuốc lá trong phòng', 'active', '2026-06-17 18:03:42', '2026-06-17 18:03:42'),
(28, 'Phụ thu khách đến muộn', 'policy_violation_fee', 'other', 0.00, 'lần', 'Phí vi phạm áp dụng khi khách đến muộn theo chính sách khách sạn.', 'active', '2026-06-18 04:42:14', '2026-06-18 04:42:14'),
(29, 'Phụ thu gia hạn lưu trú', 'policy_violation_fee', 'other', 0.00, 'lần', 'Phụ thu khi khách gia hạn thêm giờ hoặc thêm đêm.', 'active', '2026-06-18 05:29:36', '2026-06-18 05:29:36'),
(30, 'Phụ thu check-in sớm', 'policy_violation_fee', 'other', 0.00, 'lần', 'Phụ thu khi khách nhận phòng sớm trước giờ check-in chuẩn.', 'active', '2026-06-19 14:53:14', '2026-06-19 14:53:14'),
(31, 'Phụ thu check-out muộn', 'policy_violation_fee', 'other', 0.00, 'lần', 'Phụ thu khi khách trả phòng muộn so với giờ check-out trên booking.', 'active', '2026-06-19 14:57:46', '2026-06-19 14:57:46'),
(32, 'Buffet sáng', 'service', 'general', 180000.00, 'suất', 'Buffet sáng dùng để test mã ưu đãi dịch vụ.', 'active', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(33, 'Đồ uống chào mừng', 'service', 'general', 80000.00, 'phần', 'Welcome drink dùng để test mã hỗ trợ khách check-in sớm/đổi hạng phòng.', 'active', '2026-06-21 16:29:13', '2026-06-21 16:29:13'),
(34, 'Buffet sáng DEMO', 'service', 'general', 180000.00, 'suất', 'Dịch vụ demo dùng để test mã tặng/giảm dịch vụ.', 'active', '2026-06-25 07:21:25', '2026-06-25 07:21:25'),
(35, 'Welcome drink DEMO', 'service', 'general', 80000.00, 'phần', 'Dịch vụ demo dùng để test mã hỗ trợ khách.', 'active', '2026-06-25 07:21:25', '2026-06-25 07:21:25'),
(36, 'Gửi xe máy qua đêm', 'service', 'vehicle', 20000.00, 'đêm', 'Phí gửi xe máy qua đêm cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(37, 'Gửi ô tô qua đêm', 'service', 'vehicle', 100000.00, 'đêm', 'Phí gửi ô tô qua đêm cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(38, 'Rửa xe máy', 'service', 'vehicle', 40000.00, 'lần', 'Dịch vụ hỗ trợ rửa xe máy cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(39, 'Rửa ô tô', 'service', 'vehicle', 120000.00, 'lần', 'Dịch vụ hỗ trợ rửa ô tô cho khách lưu trú.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(40, 'Hỗ trợ gọi sửa xe', 'service', 'vehicle', 50000.00, 'lần', 'Khách sạn hỗ trợ liên hệ thợ/gara sửa xe. Phí sửa thực tế nếu có sẽ báo riêng cho khách.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(41, 'Hỗ trợ vá lốp xe máy', 'service', 'vehicle', 30000.00, 'lần', 'Khách sạn hỗ trợ liên hệ vá lốp xe máy cho khách. Phí phát sinh thực tế nếu có sẽ báo riêng.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04'),
(42, 'Hỗ trợ gọi cứu hộ xe', 'service', 'vehicle', 100000.00, 'lần', 'Khách sạn hỗ trợ liên hệ cứu hộ xe/gara cho khách khi xe gặp sự cố.', 'active', '2026-06-27 03:02:04', '2026-06-27 03:02:04');

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
('cUy2mBjmcVl8Kg5VkE2LuFmMTX1HEKaOzabJW5Zw', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZHc3ZU9IVkFNcFpCWXVieWtrVlZWdWVpcDZKMWRGUkF5NzU3TE54cCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9ib29raW5ncyI7czo1OiJyb3V0ZSI7czoyMDoiYWRtaW4uYm9va2luZ3MuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O30=', 1783773421),
('Vrh4EjPiTMMzgHlU3eqiMSOzLSMJDh5q4hEto4LJ', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoibGFKclFWZ1dnb01oT3E0ZkdIdjhQU1Q3QzVxVzZQZlNsM0NCY2JjMCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC91c2VyLXNldHRpbmdzIjtzOjU6InJvdXRlIjtzOjEzOiJ1c2VyLnNldHRpbmdzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6ODt9', 1783861607),
('YZ0vzfqQIZ8UNNAhG1Y56iL5A5QZTcHkvSMLnbY5', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiUDJEZENIZ2djclYzMThFeXhxQlh4WjJBaW9wZWNObDZZTVJiVVJ0ZyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7czo1OiJyb3V0ZSI7czoxNToiYWRtaW4uZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo4NToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL2Jvb2tpbmdzL2NvbmZpcm0/cm9vbV9jYXRlZ29yeV9pZD0zJmFkdWx0X2NvdW50PTEmY2hpbGRfY291bnQ9MCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ7fQ==', 1783859312),
('ZLRz38Kk9SfrXAih1Swd3A5SeWSskuOR9B2j8WpY', 8, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiaWFWZEFmY2NJVE1icjVBdUhQQzZvVzZiNTdIeWRuS01kRld4eDdKQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6ODt9', 1783773849);

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
(5, 7, 'Quản lý 1', '0985795222', '038206022333', '2021-01-05', 'female', 'Huyện Yên Định', 'Quản lý', 25000000.00, '2026-06-08', 'staffs/oqNKQ2nqKAabJxNbo8GUC6Qxcx6RhijOp2UIbxgZ.png', 'working', '2026-06-12 03:44:16', '2026-06-12 03:44:16');

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
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `google_id`, `avatar`, `email_verified_at`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'Chiến Trịnh', 'chientr33@gmail.com', NULL, 'avatars/ou5dhWm1C3m3Fqlr3J66AEUc8OTonk0yE3E8JyO6.jpg', NULL, '$2y$12$2yzPWmde1rX4Zb7iOBk9s.Rmzq2UyoW38cdb2/ire1ZbehL14eKWK', 'super_admin', 'active', NULL, '2026-06-05 06:28:47', '2026-06-05 06:29:03', NULL),
(5, 'LT1', 'lt1@gmail.com', NULL, NULL, NULL, '$2y$12$QDau.PoEC2nLvrTkIzz5IOu40ocx9nBFT6MJ/B3REtvTSFZhYvTFa', 'receptionist', 'active', 'Lf2eRgPXYRyW5kjxqMcBYJEGWI96rqniXPoWQgJEWvV4ITRm10b6jH3q9KnZ', '2026-06-12 02:56:26', '2026-06-13 01:49:31', NULL),
(6, 'Buồng 1', 'bp1@gmail.com', NULL, NULL, NULL, '$2y$12$oZt9xTFvAwXhZ7SRORYFvu2vKjJRacLNHPHYas81GHldy.Q36g.7S', 'housekeeping', 'active', NULL, '2026-06-12 03:42:42', '2026-06-13 01:49:08', NULL),
(7, 'Quản lý 1', 'ql1@gmail.com', NULL, NULL, NULL, '$2y$12$jgIUMmboUCdS3iUWvRmiJeyVm2DsiNk3QwmDF1Ri5g0HZx3TQMIA.', 'manager', 'active', NULL, '2026-06-12 03:44:16', '2026-06-12 03:46:51', NULL),
(8, 'Linh Văn', 'vlinh319@gmail.com', NULL, 'avatars/d1rPXMAKPhIDOTtzfMS7Z59sZxNz2KFDKkIozx7O.png', NULL, '$2y$12$mWt1IDXuO7VoGKJsgDjMVeJpA9tYT4Lnpo/m0/icQcgrblUzVUP2m', 'customer', 'active', NULL, '2026-06-13 01:37:37', '2026-07-11 06:16:05', NULL),
(9, 'Trịnh Chiến', 'chientrinh3@gmail.com', NULL, NULL, NULL, '$2y$12$U5KELy3DfyyMdT6d1ftTFOprhudInfE1Z/3mqycRBIP5MTZNnR.Y6', 'customer', 'active', NULL, '2026-06-13 19:32:52', '2026-06-13 19:32:52', NULL),
(10, 'Trịnh a', 'chientrinh1@gmail.com', NULL, NULL, NULL, '$2y$12$Ve5YrscIP59YSWfsNDhv7.yyL8iV7dqfbjsO73ZUV4kZf9bYH9KOi', 'customer', 'active', NULL, '2026-06-13 19:34:31', '2026-06-13 19:34:31', NULL),
(11, 'Đào Du', 'du319@gmail.com', NULL, NULL, NULL, '$2y$12$BPBWaHx9Eg7JKE8nO5LTlO3QdKcl62ZY11TT3ORquEcuzasyMXsDO', 'customer', 'active', NULL, '2026-06-18 12:28:59', '2026-06-18 12:28:59', NULL),
(12, 'Nguyễn Anh', 'anh319@gmail.com', NULL, NULL, NULL, '$2y$12$tkQhEttAO2bwRH8IRy0KGec8Q5KZc8UhO/5zHOvkda5L4PXTCJL8a', 'customer', 'active', NULL, '2026-06-18 12:29:46', '2026-06-18 12:46:18', NULL),
(13, 'Trịnh Chiến', 'tc19092006@gmail.com', '114766218040428006282', 'https://lh3.googleusercontent.com/a/ACg8ocJSqD0le1IYnsHDdhYqivqOzwZxPdK3p3k6K3o7Jxnbybnrrw=s96-c', '2026-07-10 08:20:38', NULL, 'customer', 'active', 'iwvnTTYrbvPv5hYJIEkmjqazH0rvk6U50TKQ4SbxX41OxgoAh0sxkAT8m1Mu', '2026-07-10 08:07:06', '2026-07-10 15:22:38', NULL);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bookings_booking_code_unique` (`booking_code`),
  ADD KEY `bookings_customer_id_foreign` (`customer_id`),
  ADD KEY `bookings_created_by_foreign` (`created_by`),
  ADD KEY `bookings_room_category_id_foreign` (`room_category_id`),
  ADD KEY `idx_bookings_time_range` (`check_in_at`,`check_out_at`);

--
-- Chỉ mục cho bảng `booking_guests`
--
ALTER TABLE `booking_guests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_guests_booking_id_foreign` (`booking_id`),
  ADD KEY `booking_guests_booking_room_id_foreign` (`booking_room_id`);

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
  ADD KEY `booking_payments_booking_id_foreign` (`booking_id`);

--
-- Chỉ mục cho bảng `booking_promotions`
--
ALTER TABLE `booking_promotions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_promotions_booking_promotion_unique` (`booking_id`,`promotion_id`),
  ADD KEY `booking_promotions_booking_id_index` (`booking_id`),
  ADD KEY `booking_promotions_promotion_id_index` (`promotion_id`),
  ADD KEY `booking_promotions_applied_by_index` (`applied_by`);

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
  ADD KEY `bps_offers_service_fk` (`service_id`);

--
-- Chỉ mục cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_rooms_booking_id_foreign` (`booking_id`),
  ADD KEY `booking_rooms_room_id_foreign` (`room_id`);

--
-- Chỉ mục cho bảng `booking_service_items`
--
ALTER TABLE `booking_service_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_service_items_booking` (`booking_id`),
  ADD KEY `fk_booking_service_items_service` (`service_id`);

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
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_phone_unique` (`phone`),
  ADD UNIQUE KEY `customers_user_id_unique` (`user_id`),
  ADD UNIQUE KEY `customers_cccd_unique` (`cccd`);

--
-- Chỉ mục cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

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
  ADD KEY `room_inspections_booking_id_foreign` (`booking_id`),
  ADD KEY `room_inspections_room_id_foreign` (`room_id`),
  ADD KEY `room_inspections_inspected_by_foreign` (`inspected_by`),
  ADD KEY `room_inspections_confirmed_by_foreign` (`confirmed_by`);

--
-- Chỉ mục cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_inspection_items_room_inspection_id_foreign` (`room_inspection_id`),
  ADD KEY `room_inspection_items_service_id_foreign` (`service_id`);

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
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT cho bảng `booking_guests`
--
ALTER TABLE `booking_guests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT cho bảng `booking_payments`
--
ALTER TABLE `booking_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `booking_promotions`
--
ALTER TABLE `booking_promotions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `booking_promotion_room_upgrades`
--
ALTER TABLE `booking_promotion_room_upgrades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `booking_promotion_service_offers`
--
ALTER TABLE `booking_promotion_service_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT cho bảng `booking_service_items`
--
ALTER TABLE `booking_service_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `booking_staff_assignments`
--
ALTER TABLE `booking_staff_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `chat_assignment_logs`
--
ALTER TABLE `chat_assignment_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `chat_attachments`
--
ALTER TABLE `chat_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `hotel_reviews`
--
ALTER TABLE `hotel_reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `promotion_service_offers`
--
ALTER TABLE `promotion_service_offers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `room_action_logs`
--
ALTER TABLE `room_action_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `room_categories`
--
ALTER TABLE `room_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `room_category_amenities`
--
ALTER TABLE `room_category_amenities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `room_category_images`
--
ALTER TABLE `room_category_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `room_inspections`
--
ALTER TABLE `room_inspections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT cho bảng `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `staff_floor_assignments`
--
ALTER TABLE `staff_floor_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `staff_room_assignments`
--
ALTER TABLE `staff_room_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`);

--
-- Các ràng buộc cho bảng `booking_guests`
--
ALTER TABLE `booking_guests`
  ADD CONSTRAINT `booking_guests_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_guests_booking_room_id_foreign` FOREIGN KEY (`booking_room_id`) REFERENCES `booking_rooms` (`id`) ON DELETE SET NULL;

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
-- Các ràng buộc cho bảng `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `room_inspections_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_inspections_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_inspections_inspected_by_foreign` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_inspections_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  ADD CONSTRAINT `room_inspection_items_room_inspection_id_foreign` FOREIGN KEY (`room_inspection_id`) REFERENCES `room_inspections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_inspection_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

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
