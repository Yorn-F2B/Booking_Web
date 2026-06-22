-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 22, 2026 lúc 06:18 AM
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

INSERT INTO `bookings` (`id`, `booking_code`, `customer_id`, `created_by`, `room_category_id`, `booking_type`, `booking_mode`, `booking_source`, `check_in_date`, `check_out_date`, `check_in_at`, `check_out_at`, `cleaning_buffer_minutes`, `actual_check_in`, `actual_check_out`, `adult_count`, `child_count`, `room_quantity`, `prefer_adjacent_rooms`, `estimated_total`, `deposit_amount`, `late_arrival_fee`, `late_arrival_hours`, `late_arrival_policy`, `payment_status`, `status`, `note`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'BK202606090531488WW', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 1, 3, 1, 10800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-08 22:31:48', '2026-06-08 22:32:58'),
(2, 'BK20260609053403WG2', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 2, 1, 7200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'checked_out', NULL, NULL, '2026-06-08 22:34:03', '2026-06-09 00:28:43'),
(3, 'BK202606090535414T8', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 3600000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'pending', NULL, '2026-06-08 22:35:54', '2026-06-08 22:35:41', '2026-06-08 22:35:54'),
(4, 'BK20260609084552N4R', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-10', '2026-06-13', '2026-06-10 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 3, 1, 10800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:23:21', '2026-06-09 01:45:52', '2026-06-10 06:23:21'),
(5, 'BK20260610120420EOH', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-16 01:36:08', '2026-06-12 12:00:00', 60, NULL, NULL, 2, 1, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'confirmed', NULL, '2026-06-10 06:23:17', '2026-06-10 05:04:20', '2026-06-10 06:23:17'),
(6, 'BK202606101254125AV', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5000000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:23:14', '2026-06-10 05:54:12', '2026-06-10 06:23:14'),
(7, 'BK202606101323005VJ', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5000000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:23:11', '2026-06-10 06:23:00', '2026-06-10 06:23:11'),
(8, 'BK20260610132706BW9', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-15 23:41:44', '2026-06-12 12:00:00', 60, NULL, '2026-06-11 15:33:00', 1, 0, 1, 0, 5100000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 14:45 - Đã yêu cầu kiểm tra phòng trước khi check-out.\r\n11/06/2026 15:10 - Admin duyệt phí hư hại phòng 401: +100.000đ.', NULL, '2026-06-10 06:27:06', '2026-06-11 08:33:00'),
(9, 'BK20260610132748PTD', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:28:57', '2026-06-10 06:27:48', '2026-06-10 06:28:57'),
(10, 'BK202606101329142YO', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-11', '2026-06-12', '2026-06-11 14:00:00', '2026-06-12 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, '2026-06-10 06:29:26', '2026-06-10 06:29:14', '2026-06-10 06:29:26'),
(11, 'BK260611ZSG78', 2, 4, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-16', '2026-06-12 14:00:00', '2026-06-16 12:00:00', 60, NULL, NULL, 6, 2, 3, 1, 14400000.00, 5000000.00, 0.00, NULL, NULL, 'partial', 'cancelled', '11/06/2026 13:33 - Đổi từ phòng 402 sang phòng 405. Lý do: hỏng điều hòa\n11/06/2026 16:44 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-11 06:14:43', '2026-06-11 09:44:11'),
(12, 'BK202606111345136LH', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 14:00:47', NULL, 1, 0, 1, 0, 1450000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 14:04 - Đã yêu cầu kiểm tra phòng trước khi check-out.\r\n11/06/2026 14:23 - Đã yêu cầu kiểm tra phòng trước khi check-out.\r\n11/06/2026 14:39 - Đã xác nhận hư hại phòng 102: +250.000đ.', NULL, '2026-06-11 06:45:13', '2026-06-11 07:45:36'),
(13, 'BK20260611153335DYG', 1, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 15:33:51', '2026-06-11 15:37:35', 1, 0, 1, 0, 5100000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 15:33 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n11/06/2026 15:34 - Admin duyệt phí hư hại phòng 401: +100.000đ.', NULL, '2026-06-11 08:33:35', '2026-06-11 08:37:35'),
(14, 'BK20260611154510RKR', 1, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 15:45:24', '2026-06-11 15:46:25', 1, 0, 1, 0, 4200000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 15:45 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n11/06/2026 15:45 - Admin duyệt phí hư hại phòng 103: +3.000.000đ.', NULL, '2026-06-11 08:45:10', '2026-06-11 08:46:25'),
(15, 'BK260611IWZGS', 3, 4, 1, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '11/06/2026 16:43 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-11 09:42:51', '2026-06-11 09:43:47'),
(16, 'BK20260611164508JNO', 1, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 16:46:23', '2026-06-11 16:46:51', 2, 1, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 16:46 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-11 09:45:08', '2026-06-11 09:46:51'),
(17, 'BK260611SHIVO', 3, 4, 2, 'overnight', 'advance', 'reception', '2026-06-12', '2026-06-13', '2026-06-12 14:00:00', '2026-06-13 12:00:00', 60, '2026-06-11 16:46:59', '2026-06-11 16:47:56', 6, 2, 2, 1, 1950000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '11/06/2026 16:47 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n11/06/2026 16:47 - Admin duyệt phí hư hại phòng 202: +150.000đ.', NULL, '2026-06-11 09:46:14', '2026-06-11 09:47:56'),
(18, 'BK260612XMDDG', 2, 4, 1, 'overnight', 'advance', 'reception', '2026-06-13', '2026-06-14', '2026-06-13 14:00:00', '2026-06-14 12:00:00', 60, NULL, NULL, 6, 0, 4, 1, 4800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-12 00:10:46', '2026-06-12 00:11:03'),
(19, 'BK26061269TGR', 2, 4, 1, 'overnight', 'advance', 'reception', '2026-06-13', '2026-06-14', '2026-06-13 14:00:00', '2026-06-14 12:00:00', 60, NULL, NULL, 6, 0, 6, 1, 7200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-12 00:15:08', '2026-06-12 00:15:37'),
(20, 'BK20260612073145TNT', 1, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-13', '2026-06-14', '2026-06-13 14:00:00', '2026-06-14 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-12 00:31:45', '2026-06-12 00:32:02'),
(21, 'BK20260613084707AIF', 4, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-14', '2026-06-15', '2026-06-14 14:00:00', '2026-06-15 12:00:00', 60, '2026-06-13 08:47:28', '2026-06-13 08:48:19', 2, 0, 1, 0, 5200000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '13/06/2026 08:47 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n13/06/2026 08:48 - Admin duyệt phí hư hại phòng 401: +200.000đ.', NULL, '2026-06-13 01:47:07', '2026-06-13 01:48:19'),
(22, 'BK20260614022738ATJ', 4, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-15', '2026-06-16', '2026-06-15 14:00:00', '2026-06-16 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 15:59 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-13 19:27:38', '2026-06-15 08:59:31'),
(23, 'BK20260614023320K98', 5, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-15', '2026-06-16', '2026-06-15 14:00:00', '2026-06-16 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 15:59 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-13 19:33:20', '2026-06-15 08:59:22'),
(24, 'BK202606151600037SQ', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, NULL, NULL, 2, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-15 09:00:03', '2026-06-15 09:00:37'),
(25, 'BK20260615161102IP0', 4, NULL, 4, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, '2026-06-15 19:36:23', NULL, 6, 0, 1, 0, 5400000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 17:12 - Check-in thực tế: 4 người lớn / 0 trẻ em. Khách vượt sức chứa, đã đổi sang hạng phòng Presidential Suite. Lý do: Vượt sức chứa khi check-in.\r\n15/06/2026 19:27 - Check-in thực tế: 7 người lớn / 0 trẻ em. Khách vượt sức chứa, giữ nguyên phòng và thu phụ phí 400.000đ.\r\n15/06/2026 19:36 - Check-in thực tế: 6 người lớn / 0 trẻ em.', NULL, '2026-06-15 09:11:02', '2026-06-15 12:36:41'),
(26, 'BK20260615193744EQZ', 4, NULL, 3, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-15 19:38:17', NULL, 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '15/06/2026 19:38 - Check-in thực tế: 1 người lớn / 0 trẻ em.', NULL, '2026-06-15 12:37:44', '2026-06-15 12:40:42'),
(27, 'BK20260615194053GFA', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-15 19:58:42', '2026-06-18 12:00:00', 60, '2026-06-16 03:07:20', NULL, 1, 0, 1, 0, 1800000.00, 0.00, 900000.00, 7.14, 'Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm.', 'unpaid', 'cancelled', '16/06/2026 02:59 - Hủy phòng do khách đến muộn quá 6 giờ, từ chối hoàn tiền cọc.\r\n16/06/2026 03:00 - Check-in thực tế: 1 người lớn / 0 trẻ em.  Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 900.000đ.\r\n16/06/2026 03:07 - Check-in thực tế: 1 người lớn / 0 trẻ em.  Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 900.000đ.', NULL, '2026-06-15 12:40:53', '2026-06-15 21:59:35'),
(28, 'BK20260616033519CFA', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 14:00:00', '2026-06-17 12:00:00', 60, '2026-06-16 03:35:32', '2026-06-16 03:39:25', 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 03:35 - Check-in thực tế: 1 người lớn / 0 trẻ em. \n16/06/2026 03:38 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-15 20:35:19', '2026-06-15 20:39:25'),
(29, 'BK260616ODKK0', 7, 4, 2, 'overnight', 'advance', 'reception', '2026-06-16', '2026-06-17', '2026-06-16 02:35:34', '2026-06-17 12:00:00', 60, '2026-06-16 05:43:23', '2026-06-16 13:21:18', 4, 0, 1, 0, 2184000.00, 0.00, 180000.00, 3.13, 'Khách đến muộn từ 2 đến dưới 4 giờ, phụ thu 20% giá/đêm.', 'paid', 'checked_out', '16/06/2026 05:10 - Đổi từ phòng 202 sang phòng 201. Lý do: Hỏng đèn\n16/06/2026 05:43 - Check-in thực tế: 4 người lớn / 0 trẻ em / 0 em bé. Đã thu phụ phí phát sinh khi check-in: Phụ thu thêm người lớn x 2: 400.000đ. Tổng phụ thu: 400.000đ. Khách đến muộn từ 2 đến dưới 4 giờ, phụ thu 20% giá/đêm. Số tiền phụ thu: 180.000đ.\n16/06/2026 11:54 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n16/06/2026 13:20 - Admin duyệt phí hư hại phòng 201: +150.000đ.', NULL, '2026-06-15 21:04:23', '2026-06-16 06:21:18'),
(30, 'BK20260616132210LAT', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 13:22:39', '2026-06-16 13:39:07', 1, 0, 1, 0, 984000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 13:22 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. \n16/06/2026 13:22 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n16/06/2026 13:23 - Admin duyệt phí hư hại phòng 201: +70.000đ.', NULL, '2026-06-16 06:22:10', '2026-06-16 06:39:07'),
(31, 'BK20260616133921JRE', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 13:39:53', '2026-06-16 13:55:04', 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 13:39 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. \n16/06/2026 13:54 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-16 06:39:21', '2026-06-16 06:55:04'),
(32, 'BK20260616135547IMG', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-17', '2026-06-18', '2026-06-17 14:00:00', '2026-06-18 12:00:00', 60, '2026-06-16 14:02:42', '2026-06-16 14:24:33', 1, 0, 1, 0, 1350000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '16/06/2026 14:02 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. \n16/06/2026 14:23 - Đổi từ phòng 203 sang phòng 201. Lý do: Đổi phòng và gia hạn cho khách\n16/06/2026 14:23 - Gia hạn lưu trú từ 18/06/2026 11:00 đến 18/06/2026 15:00. Gia hạn thêm 4 giờ, phụ thu 50% giá/đêm. Phụ thu: 450.000đ.\n16/06/2026 14:24 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-16 06:55:47', '2026-06-16 07:24:33'),
(33, 'BK260616PBMOZ', 8, 4, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 03:08 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-16 07:02:26', '2026-06-17 20:08:49'),
(34, 'BK20260618011733YM4', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-17 18:17:33', '2026-06-17 18:18:16'),
(35, 'BK20260618012232J3A', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1260000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 12:26 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-17 18:22:32', '2026-06-18 05:26:27'),
(36, 'BK260618QNDUZ', 9, 4, 1, 'hourly', 'advance', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 03:00:00', '2026-06-18 11:00:00', 60, '2026-06-18 11:42:14', '2026-06-18 13:13:25', 2, 0, 1, 0, 2640000.00, 0.00, 1200000.00, 8.70, 'Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm.', 'paid', 'checked_out', '18/06/2026 11:42 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé.  Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm. Số tiền phụ thu: 1.200.000đ.\n18/06/2026 12:29 - Gia hạn lưu trú từ 18/06/2026 09:00 đến 18/06/2026 15:00. Booking theo giờ, gia hạn thêm 6 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 120.000đ/giờ. Chuyển phòng 103 → 101 cùng hạng Deluxe Sea View. Phụ thu: 720.000đ.\n18/06/2026 13:12 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-17 19:55:46', '2026-06-18 06:13:25'),
(37, 'BK260618TEG6J', 10, 4, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 14:09 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-17 19:57:47', '2026-06-18 07:09:02'),
(38, 'BK260618GSSGF', 11, 4, 3, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 03:08 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-17 20:07:51', '2026-06-17 20:08:03'),
(39, 'BK20260618122651N35', 4, NULL, 1, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-18 05:26:51', '2026-06-18 05:27:00'),
(40, 'BK20260618122747XPM', 4, NULL, 2, 'overnight', 'advance', 'reception', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 2, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 13:34 - Đã đổi toàn bộ booking sang hạng phòng Superior Double. Lý do: Khách yêu cầu đổi hạng phòng.\n18/06/2026 13:52 - Đã thêm 1 phòng hạng Superior Double vào booking. Lý do: Khách phát sinh nhu cầu thêm phòng.\n18/06/2026 13:52 - Đã đổi phòng 201 từ hạng Superior Double sang phòng 103 hạng Deluxe Sea View. Chênh lệch tiền phòng: 300.000đ. Lý do: Khách yêu cầu đổi 1 phòng.\n18/06/2026 14:03 - Đã đổi phòng 103 từ hạng Deluxe Sea View sang phòng 201 hạng Superior Double. Chênh lệch tiền phòng: -300.000đ. Lý do: Khách yêu cầu đổi 1 phòng.\n18/06/2026 16:27 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-18 05:27:47', '2026-06-18 09:27:53'),
(41, 'BK260618C7BGC', 12, 4, 2, 'hourly', 'walk_in', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 16:45:00', '2026-06-18 18:45:00', 60, '2026-06-18 16:42:47', '2026-06-18 19:51:14', 1, 0, 1, 0, 270000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 16:42 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n18/06/2026 19:50 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-18 09:40:30', '2026-06-18 12:51:14'),
(42, 'BK260618TGJ8P', 13, 4, 2, 'hourly', 'walk_in', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 19:00:00', '2026-06-18 21:00:00', 60, NULL, NULL, 1, 0, 1, 0, 270000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 17:00 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-18 09:56:08', '2026-06-18 10:00:16'),
(43, 'BK20260618173207IPM', 4, NULL, 1, 'overnight', 'advance', 'user_online', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 1200000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', '18/06/2026 17:35 - Booking đã được hủy bởi nhân viên.', NULL, '2026-06-18 10:32:07', '2026-06-18 10:35:14'),
(44, 'BK20260618173544HLO', 4, NULL, 1, 'overnight', 'advance', 'user_online', '2026-06-18', '2026-06-19', '2026-06-18 14:00:00', '2026-06-19 12:00:00', 60, '2026-06-18 17:36:26', '2026-06-18 17:37:00', 1, 0, 1, 0, 1800000.00, 0.00, 600000.00, 3.61, 'Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm.', 'paid', 'checked_out', '18/06/2026 17:36 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Khách đến muộn từ trên 3 giờ đến 6 giờ, đã thông báo trước/đã xác nhận giữ phòng, phụ thu 50% giá 1 đêm. Số tiền phụ thu: 600.000đ.\n18/06/2026 17:36 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-18 10:35:44', '2026-06-18 10:37:00'),
(45, 'BK20260618184642DNU', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, '2026-06-18 20:03:27', '2026-06-18 20:04:58', 2, 0, 1, 0, 1140000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 20:03 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n18/06/2026 20:04 - Đã yêu cầu kiểm tra phòng trước khi check-out.', NULL, '2026-06-18 11:46:42', '2026-06-18 13:04:58'),
(46, 'BK2606182FY12', 16, 4, 3, 'hourly', 'walk_in', 'reception', '2026-06-18', '2026-06-18', '2026-06-18 20:00:00', '2026-06-18 23:00:00', 60, '2026-06-18 19:57:54', '2026-06-19 21:57:46', 1, 0, 1, 0, 6240000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 19:57 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n19/06/2026 21:57 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 21:57 - Check-out thực tế. Tổng phải thu: 6.240.000đ. Cọc: 0đ. Còn lại đã thu: 6.240.000đ. Phí phát sinh: Phụ thu check-out muộn: 5.520.000đ. Booking theo giờ trả muộn 22.96 giờ, tính thêm 23 giờ theo đơn giá tạm tính 240.000đ/giờ.', NULL, '2026-06-18 12:52:52', '2026-06-19 14:57:46'),
(47, 'BK202606182043185FJ', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-19', '2026-06-20', '2026-06-19 14:00:00', '2026-06-20 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5300000.00, 0.00, 0.00, 7.10, 'No-show sau 18:00, không liên lạc được. Chưa ghi nhận tiền cọc trên hệ thống; lễ tân kiểm tra lại thanh toán nếu có.', 'unpaid', 'cancelled', '19/06/2026 21:05 - Hủy no-show do khách chưa đến sau 18:00 và không liên lạc được. Chưa ghi nhận tiền cọc trên hệ thống; lễ tân kiểm tra lại thanh toán nếu có. Phòng được mở bán lại.', NULL, '2026-06-18 13:43:18', '2026-06-19 14:05:50'),
(48, 'BK20260618204608QO7', 14, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-19', '2026-06-20 14:00:00', '2026-06-19 21:55:44', 60, '2026-06-18 20:48:53', '2026-06-19 21:55:44', 2, 0, 1, 0, 15000000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '18/06/2026 20:48 - Check-in thực tế: 2 người lớn / 0 trẻ em / 0 em bé.  Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.\n18/06/2026 20:56 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 21:55 - Check-out thực tế. Tổng phải thu: 15.000.000đ. Cọc: 0đ. Còn lại đã thu: 15.000.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-18 13:46:08', '2026-06-19 14:55:44'),
(49, 'BK20260619211657P59', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-21', '2026-06-20 14:00:00', '2026-06-21 12:00:00', 60, '2026-06-19 21:53:14', '2026-06-19 21:54:44', 1, 0, 1, 0, 1800000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '19/06/2026 21:53 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Check-in sớm lúc 19/06/2026 21:53, sớm hơn giờ chuẩn 16 giờ 6 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 900.000đ.\n19/06/2026 21:53 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 21:54 - Check-out thực tế. Tổng phải thu: 1.800.000đ. Cọc: 0đ. Còn lại đã thu: 1.800.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-19 14:16:57', '2026-06-19 14:54:44'),
(50, 'BK20260619221047JWD', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-21', '2026-06-20 14:00:00', '2026-06-21 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 5000000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-19 15:10:47', '2026-06-19 15:14:07'),
(51, 'BK260619TWVKJ', 17, 4, 4, 'hourly', 'walk_in', 'reception', '2026-06-19', '2026-06-20', '2026-06-19 22:10:00', '2026-06-20 15:00:00', 60, '2026-06-19 22:13:23', '2026-06-19 22:15:49', 1, 0, 1, 0, 5674157.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '19/06/2026 22:14 - Gia hạn lưu trú từ 20/06/2026 13:00 đến 20/06/2026 15:00. Booking theo giờ, gia hạn thêm 2 giờ. Đơn giá tạm tính theo giá giờ hiện tại: 337.079đ/giờ. Phụ thu: 674.157đ.\n19/06/2026 22:14 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 22:15 - Check-out thực tế. Tổng phải thu: 5.674.157đ. Cọc: 0đ. Còn lại đã thu: 5.674.157đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-19 15:13:23', '2026-06-19 15:15:49'),
(52, 'BK20260619221619QVP', 4, NULL, 4, 'overnight', 'advance', 'user_online', '2026-06-20', '2026-06-21', '2026-06-20 14:00:00', '2026-06-21 12:00:00', 60, '2026-06-19 22:16:43', '2026-06-19 23:00:07', 1, 0, 1, 0, 10750000.00, 0.00, 0.00, NULL, NULL, 'paid', 'checked_out', '19/06/2026 22:16 - Check-in thực tế: 1 người lớn / 0 trẻ em / 0 em bé. Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out. Check-in sớm lúc 19/06/2026 22:16, sớm hơn giờ chuẩn 15 giờ 43 phút. Nhận trước ngày check-in 1 ngày, tính thêm 1 đêm. Phụ thu: 5.000.000đ.\n19/06/2026 22:58 - Đã yêu cầu kiểm tra phòng trước khi check-out.\n19/06/2026 22:58 - Admin duyệt kiểm tra phòng 401: dịch vụ tại phòng +50.000đ, hư hại +50.000đ. Tổng cộng +100.000đ.\n19/06/2026 23:00 - Check-out thực tế. Tổng phải thu: 10.750.000đ. Cọc: 0đ. Còn lại đã thu: 10.750.000đ. Không phát sinh phụ thu check-out.', NULL, '2026-06-19 15:16:19', '2026-06-19 16:00:07'),
(53, 'BK20260620144210BEP', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-21', '2026-06-22', '2026-06-21 14:00:00', '2026-06-22 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'cancelled', NULL, NULL, '2026-06-20 07:42:10', '2026-06-20 07:42:46'),
(54, 'BK202606201454387BA', 4, NULL, 2, 'overnight', 'advance', 'user_online', '2026-06-21', '2026-06-22', '2026-06-21 14:00:00', '2026-06-22 12:00:00', 60, NULL, NULL, 1, 0, 1, 0, 900000.00, 0.00, 0.00, NULL, NULL, 'unpaid', 'confirmed', NULL, NULL, '2026-06-20 07:54:38', '2026-06-20 07:54:38');

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
(115, 52, 4, 'check_out', 'Xác nhận check-out lúc 19/06/2026 23:00. Phòng chuyển sang cần dọn: 401. Tiền phòng: 5.000.000đ. Dịch vụ/phụ thu: 5.650.000đ. Minibar/hư hại duyệt: 100.000đ. Tổng phải thu: 10.750.000đ. Còn lại đã thu: 10.750.000đ. Không phát sinh phụ thu check-out.', '2026-06-19 16:00:07', '2026-06-19 16:00:07');

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
(69, 54, 4, 1, 0, 900000.00, 0.00, NULL, '2026-06-20 07:54:38');

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
(27, 52, 18, 'Coca Cola', 'minibar', 25000.00, 2, 2, 'confirmed', 4, '2026-06-19 22:59:26', 'Dịch vụ/minibar khách gọi thêm, tính tiền ngay vào booking.', 50000.00, 'Khách tự thêm trên website', '2026-06-19 15:57:37', '2026-06-19 15:59:26');

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
(4, 8, 'Văn', 'Linh', '0985795111', '036206022111', 'vlinh319@gmail.com', '2006-07-14', 'male', 'Lê Hoàn', NULL, NULL, 'active', '2026-06-13 01:37:37', '2026-06-13 01:37:37', NULL),
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
(17, NULL, 'a', 'Tran', '0985795157', '038206022157', 'nguyena157@gmail.com', NULL, NULL, 'yên định', NULL, NULL, 'active', '2026-06-19 15:13:23', '2026-06-19 15:13:23', NULL);

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
(20, '2026_06_11_145746_create_room_inspection_items_table', 9);

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
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `hotel_rating` tinyint(4) NOT NULL COMMENT 'Đánh giá khách sạn 1-5 sao',
  `hotel_comment` text DEFAULT NULL,
  `staff_rating` tinyint(4) NOT NULL COMMENT 'Đánh giá nhân viên 1-5 sao',
  `staff_comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `room_category_id` bigint(20) UNSIGNED NOT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `status` enum('available','reserved','occupied','inspection','cleaning','maintenance') DEFAULT 'available',
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_category_id`, `floor_number`, `status`, `note`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '101', 1, 1, 'available', NULL, '2026-06-08 22:25:58', '2026-06-18 10:37:05', NULL),
(2, '102', 1, 1, 'available', NULL, '2026-06-08 22:26:07', '2026-06-18 10:35:14', NULL),
(3, '103', 1, 1, 'available', NULL, '2026-06-08 22:26:18', '2026-06-18 07:03:41', NULL),
(4, '201', 2, 2, 'reserved', NULL, '2026-06-08 22:26:38', '2026-06-20 07:54:38', NULL),
(5, '202', 2, 2, 'maintenance', NULL, '2026-06-08 22:26:49', '2026-06-18 10:00:51', NULL),
(6, '203', 2, 2, 'maintenance', NULL, '2026-06-08 22:26:59', '2026-06-18 10:00:54', NULL),
(7, '301', 3, 3, 'available', NULL, '2026-06-08 22:27:09', '2026-06-19 15:00:37', NULL),
(8, '302', 3, 3, 'available', NULL, '2026-06-08 22:27:15', '2026-06-17 20:08:03', NULL),
(9, '401', 4, 4, 'available', NULL, '2026-06-08 22:27:46', '2026-06-20 06:35:06', NULL),
(10, '402', 1, 4, 'available', NULL, '2026-06-08 22:33:44', '2026-06-18 07:04:06', NULL),
(11, '403', 1, 4, 'available', NULL, '2026-06-11 06:12:48', '2026-06-18 07:04:18', NULL),
(12, '404', 1, 4, 'available', NULL, '2026-06-11 06:12:57', '2026-06-18 07:04:22', NULL),
(13, '405', 1, 4, 'available', NULL, '2026-06-11 06:13:05', '2026-06-18 07:04:25', NULL);

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
(22, 52, 9, 4, 4, 'confirmed', 1, NULL, 100000.00, NULL, NULL, '2026-06-19 15:58:30', '2026-06-19 15:58:45', '2026-06-19 15:58:04', '2026-06-19 15:58:45');

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
(24, 22, 20, 'minibar', 'Snack', 'gói', 20000.00, 2, 40000.00, 'approved', NULL, '2026-06-19 15:58:30', '2026-06-19 15:58:45');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('service','minibar','damage_fee','occupancy_fee','policy_violation_fee') NOT NULL,
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

INSERT INTO `services` (`id`, `name`, `type`, `price`, `unit`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Giặt là', 'service', 50000.00, 'lần', 'Dịch vụ giặt và sấy quần áo cho khách lưu trú.', 'active', '2026-06-08 22:11:48', '2026-06-08 22:11:48'),
(2, 'Ăn sáng buffet', 'service', 120000.00, 'người', 'Suất ăn sáng buffet tại nhà hàng khách sạn.', 'active', '2026-06-08 22:12:39', '2026-06-08 22:12:39'),
(3, 'Đưa đón sân bay', 'service', 300000.00, 'lượt', 'Xe đưa đón khách từ sân bay về khách sạn hoặc ngược lại.', 'active', '2026-06-08 22:13:06', '2026-06-17 18:38:41'),
(4, 'Nước suối', 'minibar', 7000.00, 'chai', 'Nước suối trong minibar tại phòng.', 'active', '2026-06-08 22:13:45', '2026-06-08 22:13:45'),
(5, 'Bia', 'minibar', 10000.00, 'lon', 'Bia lon trong minibar, tính theo số lượng sử dụng.', 'active', '2026-06-08 22:14:12', '2026-06-08 22:14:12'),
(6, 'Vỡ ly thủy tinh', 'damage_fee', 50000.00, 'cái', 'Phí bồi thường khi khách làm vỡ ly trong phòng.', 'active', '2026-06-08 22:14:45', '2026-06-08 22:14:45'),
(7, 'Hỏng TV', 'damage_fee', 3000000.00, 'lần', 'Phí bồi thường khi khách làm hư hỏng TV trong phòng.', 'active', '2026-06-08 22:15:14', '2026-06-08 22:15:14'),
(10, 'Phụ thu thêm người lớn', 'occupancy_fee', 200000.00, 'người', 'Phụ thu khi khách phát sinh thêm người lớn lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-17 18:39:07'),
(11, 'Phụ thu thêm trẻ em', 'occupancy_fee', 100000.00, 'trẻ', 'Phụ thu khi khách phát sinh thêm trẻ em lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-15 22:35:34'),
(12, 'Phụ thu em bé', 'occupancy_fee', 50000.00, 'bé', 'Phụ thu khi khách phát sinh thêm em bé lúc check-in.', 'active', '2026-06-15 22:35:34', '2026-06-15 22:35:34'),
(16, 'Trang trí sinh nhật', 'service', 300000.00, 'lần', 'Trang trí phòng theo yêu cầu', 'active', '2026-06-17 18:03:20', '2026-06-17 18:03:20'),
(18, 'Coca Cola', 'minibar', 25000.00, 'lon', 'Nước ngọt trong minibar', 'active', '2026-06-17 18:03:28', '2026-06-17 18:03:28'),
(20, 'Snack', 'minibar', 20000.00, 'gói', 'Đồ ăn nhẹ trong minibar', 'active', '2026-06-17 18:03:28', '2026-06-17 18:03:28'),
(22, 'Mất thẻ phòng', 'policy_violation_fee', 100000.00, 'thẻ', 'Phí bồi thường mất thẻ phòng', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(23, 'Bẩn ga giường nặng', 'policy_violation_fee', 150000.00, 'lần', 'Phí xử lý vệ sinh ga giường bẩn nặng', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(24, 'Hỏng remote điều hòa', 'policy_violation_fee', 200000.00, 'cái', 'Phí bồi thường remote điều hòa', 'active', '2026-06-17 18:03:35', '2026-06-17 18:03:35'),
(27, 'Hút thuốc trong phòng', 'policy_violation_fee', 300000.00, 'lần', 'Phí xử lý mùi thuốc lá trong phòng', 'active', '2026-06-17 18:03:42', '2026-06-17 18:03:42'),
(28, 'Phụ thu khách đến muộn', 'policy_violation_fee', 0.00, 'lần', 'Phí vi phạm áp dụng khi khách đến muộn theo chính sách khách sạn.', 'active', '2026-06-18 04:42:14', '2026-06-18 04:42:14'),
(29, 'Phụ thu gia hạn lưu trú', 'policy_violation_fee', 0.00, 'lần', 'Phụ thu khi khách gia hạn thêm giờ hoặc thêm đêm.', 'active', '2026-06-18 05:29:36', '2026-06-18 05:29:36'),
(30, 'Phụ thu check-in sớm', 'policy_violation_fee', 0.00, 'lần', 'Phụ thu khi khách nhận phòng sớm trước giờ check-in chuẩn.', 'active', '2026-06-19 14:53:14', '2026-06-19 14:53:14'),
(31, 'Phụ thu check-out muộn', 'policy_violation_fee', 0.00, 'lần', 'Phụ thu khi khách trả phòng muộn so với giờ check-out trên booking.', 'active', '2026-06-19 14:57:46', '2026-06-19 14:57:46');

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
('U72hNjegtmyQZmzRBodKXb455C9PzarXdi8jNDJs', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.121.0 Chrome/142.0.7444.265 Electron/39.8.8 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNVQyUkkzM25GMzdpeDJnSkhzMmZVZnZpRXF6dm8zTk1VY2YxSVVFRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1782069723),
('WJNc4ZihJ7dsFh8OAKIZdy1uZByiZnlEhRTj5dSQ', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicUNnOU4zS2ZtNXRzOEQ2SHZKWVNvSnp5V1NENzRvOWg1RVprUkRpaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYW5oLXNhY2gtZGFuaC1naWEiO3M6NToicm91dGUiO3M6MTI6InJldmlld3MubGlzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ7fQ==', 1782069967);

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
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','manager','receptionist','housekeeping','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `avatar`, `email_verified_at`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'Chiến Trịnh', 'chientr33@gmail.com', 'avatars/ou5dhWm1C3m3Fqlr3J66AEUc8OTonk0yE3E8JyO6.jpg', NULL, '$2y$12$2yzPWmde1rX4Zb7iOBk9s.Rmzq2UyoW38cdb2/ire1ZbehL14eKWK', 'super_admin', 'active', 'UCMGCr2ERZ1JmfcKuiTJKmAUWQwtwnOEdEiEXcFoK6Ol7oLEkF9d72R5taSz', '2026-06-05 06:28:47', '2026-06-05 06:29:03', NULL),
(5, 'LT1', 'lt1@gmail.com', NULL, NULL, '$2y$12$QDau.PoEC2nLvrTkIzz5IOu40ocx9nBFT6MJ/B3REtvTSFZhYvTFa', 'receptionist', 'active', 'Lf2eRgPXYRyW5kjxqMcBYJEGWI96rqniXPoWQgJEWvV4ITRm10b6jH3q9KnZ', '2026-06-12 02:56:26', '2026-06-13 01:49:31', NULL),
(6, 'Buồng 1', 'bp1@gmail.com', NULL, NULL, '$2y$12$oZt9xTFvAwXhZ7SRORYFvu2vKjJRacLNHPHYas81GHldy.Q36g.7S', 'housekeeping', 'active', NULL, '2026-06-12 03:42:42', '2026-06-13 01:49:08', NULL),
(7, 'Quản lý 1', 'ql1@gmail.com', NULL, NULL, '$2y$12$jgIUMmboUCdS3iUWvRmiJeyVm2DsiNk3QwmDF1Ri5g0HZx3TQMIA.', 'manager', 'active', NULL, '2026-06-12 03:44:16', '2026-06-12 03:46:51', NULL),
(8, 'Văn Linh', 'vlinh319@gmail.com', 'avatars/d1rPXMAKPhIDOTtzfMS7Z59sZxNz2KFDKkIozx7O.png', NULL, '$2y$12$mWt1IDXuO7VoGKJsgDjMVeJpA9tYT4Lnpo/m0/icQcgrblUzVUP2m', 'customer', 'active', NULL, '2026-06-13 01:37:37', '2026-06-13 01:37:53', NULL),
(9, 'Trịnh Chiến', 'chientrinh3@gmail.com', NULL, NULL, '$2y$12$U5KELy3DfyyMdT6d1ftTFOprhudInfE1Z/3mqycRBIP5MTZNnR.Y6', 'customer', 'active', NULL, '2026-06-13 19:32:52', '2026-06-13 19:32:52', NULL),
(10, 'Trịnh a', 'chientrinh1@gmail.com', NULL, NULL, '$2y$12$Ve5YrscIP59YSWfsNDhv7.yyL8iV7dqfbjsO73ZUV4kZf9bYH9KOi', 'customer', 'active', NULL, '2026-06-13 19:34:31', '2026-06-13 19:34:31', NULL),
(11, 'Đào Du', 'du319@gmail.com', NULL, NULL, '$2y$12$BPBWaHx9Eg7JKE8nO5LTlO3QdKcl62ZY11TT3ORquEcuzasyMXsDO', 'customer', 'active', NULL, '2026-06-18 12:28:59', '2026-06-18 12:28:59', NULL),
(12, 'Nguyễn Anh', 'anh319@gmail.com', NULL, NULL, '$2y$12$tkQhEttAO2bwRH8IRy0KGec8Q5KZc8UhO/5zHOvkda5L4PXTCJL8a', 'customer', 'active', NULL, '2026-06-18 12:29:46', '2026-06-18 12:46:18', NULL);

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
-- Chỉ mục cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_logs_booking_id_foreign` (`booking_id`),
  ADD KEY `booking_logs_user_id_foreign` (`user_id`);

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
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_booking_review` (`booking_id`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_room_number_unique` (`room_number`),
  ADD KEY `rooms_room_category_id_foreign` (`room_category_id`);

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
  ADD PRIMARY KEY (`id`);

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
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT cho bảng `booking_rooms`
--
ALTER TABLE `booking_rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT cho bảng `booking_service_items`
--
ALTER TABLE `booking_service_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `failed_jobs`
--
ALTER TABLE `failed_jobs`
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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `room_inspection_items`
--
ALTER TABLE `room_inspection_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Các ràng buộc cho bảng `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD CONSTRAINT `booking_logs_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
-- Các ràng buộc cho bảng `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_room_category_id_foreign` FOREIGN KEY (`room_category_id`) REFERENCES `room_categories` (`id`) ON DELETE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
