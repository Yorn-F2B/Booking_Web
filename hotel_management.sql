
-- =========================================================
-- HOTEL BOOKING MANAGEMENT SYSTEM DATABASE
-- Framework: Laravel 12
-- Database: MySQL / MariaDB
-- Charset: utf8mb4
-- =========================================================

CREATE DATABASE booking_web
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE booking_web;

-- =========================================================
-- TABLE: users
-- Chức năng:
-- Lưu tài khoản đăng nhập hệ thống
-- Bao gồm:
-- admin / staff / customer
-- Laravel Breeze sẽ dùng bảng này để authentication
-- =========================================================

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Tên đăng nhập hiển thị
    name VARCHAR(100) NOT NULL,

    -- Email dùng để đăng nhập
    email VARCHAR(100) UNIQUE NOT NULL,

    -- Thời gian xác thực email
    email_verified_at TIMESTAMP NULL DEFAULT NULL,

    -- Mật khẩu mã hóa bcrypt
    password VARCHAR(255) NOT NULL,

    -- Vai trò hệ thống
    role ENUM(
        'admin',
        'staff',
        'customer'
    ) DEFAULT 'customer',

    -- Trạng thái tài khoản
    status ENUM(
        'active',
        'inactive',
        'banned'
    ) DEFAULT 'active',

    -- Token remember login Laravel
    remember_token VARCHAR(100) NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL
);

-- =========================================================
-- TABLE: staffs
-- Chức năng:
-- Lưu thông tin nhân viên khách sạn
-- Mỗi staff liên kết với 1 user đăng nhập
-- =========================================================

CREATE TABLE staffs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Liên kết users
    user_id BIGINT UNSIGNED UNIQUE,

    -- Họ tên nhân viên
    full_name VARCHAR(100) NOT NULL,

    -- Số điện thoại
    phone VARCHAR(20) UNIQUE,

    -- CCCD
    cccd VARCHAR(20) UNIQUE,

    -- Ngày sinh
    birthday DATE,

    -- Giới tính
    gender ENUM(
        'male',
        'female',
        'other'
    ),

    -- Địa chỉ
    address TEXT,

    -- Chức vụ
    position VARCHAR(100),

    -- Lương
    salary DECIMAL(12,2) DEFAULT 0,

    -- Ngày vào làm
    hire_date DATE,

    -- Ảnh đại diện
    avatar VARCHAR(255),

    -- Trạng thái làm việc
    work_status ENUM(
        'working',
        'resigned',
        'temporary_leave'
    ) DEFAULT 'working',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: customers
-- Chức năng:
-- Lưu hồ sơ khách hàng
-- Có thể liên kết với user nếu khách đăng ký tài khoản
-- =========================================================

CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Liên kết tài khoản user
    user_id BIGINT UNSIGNED NULL UNIQUE,

    -- Họ
    first_name VARCHAR(50) NOT NULL,

    -- Tên
    last_name VARCHAR(50) NOT NULL,

    -- SĐT
    phone VARCHAR(20) UNIQUE NOT NULL,

    -- CCCD / Passport
    cccd VARCHAR(30) UNIQUE,

    -- Email liên hệ
    email VARCHAR(100),

    -- Ngày sinh
    birthday DATE,

    -- Giới tính
    gender ENUM(
        'male',
        'female',
        'other'
    ),

    -- Địa chỉ
    address TEXT,

    -- Ảnh đại diện
    avatar VARCHAR(255),

    -- Ghi chú khách hàng
    note TEXT,

    -- Trạng thái khách
    status ENUM(
        'active',
        'blacklist'
    ) DEFAULT 'active',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: room_categories
-- Chức năng:
-- Loại phòng:
-- Standard / Deluxe / VIP ...
-- =========================================================

CREATE TABLE room_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Tên loại phòng
    name VARCHAR(100) NOT NULL,

    -- Giá mặc định
    price DECIMAL(12,2) NOT NULL,

    -- Số người tối đa
    max_people INT NOT NULL,

    -- Diện tích phòng
    area DECIMAL(6,2),

    -- Số giường
    bed_count INT DEFAULT 1,

    -- Loại giường
    bed_type VARCHAR(50),

    -- Mô tả
    description TEXT,

    -- Ảnh thumbnail
    thumbnail VARCHAR(255),

    -- Trạng thái
    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- TABLE: amenities
-- Chức năng:
-- Danh sách tiện nghi:
-- wifi, tv, minibar...
-- =========================================================

CREATE TABLE amenities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Tên tiện nghi
    name VARCHAR(100) NOT NULL,

    -- Icon hiển thị
    icon VARCHAR(255),

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- TABLE: category_amenities
-- Chức năng:
-- Liên kết loại phòng và tiện nghi
-- Quan hệ many-to-many
-- =========================================================

CREATE TABLE category_amenities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    category_id BIGINT UNSIGNED NOT NULL,
    amenity_id BIGINT UNSIGNED NOT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES room_categories(id)
        ON DELETE CASCADE,

    FOREIGN KEY (amenity_id)
        REFERENCES amenities(id)
        ON DELETE CASCADE
);

-- =========================================================
-- TABLE: rooms
-- Chức năng:
-- Danh sách phòng khách sạn
-- =========================================================

CREATE TABLE rooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Số phòng
    room_number VARCHAR(20) UNIQUE NOT NULL,

    -- Loại phòng
    category_id BIGINT UNSIGNED NOT NULL,

    -- Tầng
    floor_number INT,

    -- Trạng thái phòng
    status ENUM(
        'available',
        'reserved',
        'occupied',
        'cleaning',
        'maintenance'
    ) DEFAULT 'available',

    -- Ghi chú
    note TEXT,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (category_id)
        REFERENCES room_categories(id)
);

-- =========================================================
-- TABLE: room_images
-- Chức năng:
-- Nhiều ảnh cho 1 phòng
-- =========================================================

CREATE TABLE room_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    room_id BIGINT UNSIGNED NOT NULL,

    image_url VARCHAR(255) NOT NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON DELETE CASCADE
);

-- =========================================================
-- TABLE: bookings
-- Chức năng:
-- Phiếu đặt phòng
-- =========================================================

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Mã booking
    booking_code VARCHAR(30) UNIQUE NOT NULL,

    -- Khách đặt
    customer_id BIGINT UNSIGNED NOT NULL,

    -- Nhân viên tạo booking
    created_by BIGINT UNSIGNED NULL,

    -- Check-in dự kiến
    check_in_date DATE NOT NULL,

    -- Check-out dự kiến
    check_out_date DATE NOT NULL,

    -- Check-in thực tế
    actual_check_in DATETIME NULL,

    -- Check-out thực tế
    actual_check_out DATETIME NULL,

    -- Người lớn
    adult_count INT DEFAULT 1,

    -- Trẻ em
    child_count INT DEFAULT 0,

    -- Tổng tiền tạm tính
    estimated_total DECIMAL(12,2) DEFAULT 0,

    -- Tiền cọc
    deposit_amount DECIMAL(12,2) DEFAULT 0,

    -- Trạng thái thanh toán
    payment_status ENUM(
        'unpaid',
        'partial',
        'paid',
        'refunded'
    ) DEFAULT 'unpaid',

    -- Trạng thái booking
    status ENUM(
        'pending',
        'confirmed',
        'checked_in',
        'checked_out',
        'cancelled'
    ) DEFAULT 'pending',

    -- Ghi chú
    note TEXT,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (customer_id)
        REFERENCES customers(id),

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: booking_rooms
-- Chức năng:
-- Danh sách phòng thuộc booking
-- =========================================================

CREATE TABLE booking_rooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    booking_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,

    -- Số người ở phòng này
    people_count INT DEFAULT 1,

    -- Giá tại thời điểm đặt
    price_at_booking DECIMAL(12,2),

    -- Phụ phí
    surcharge DECIMAL(12,2) DEFAULT 0,

    -- Lý do phụ phí
    surcharge_reason VARCHAR(255),

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE,

    FOREIGN KEY (room_id)
        REFERENCES rooms(id)
);

-- =========================================================
-- TABLE: services
-- Chức năng:
-- Dịch vụ khách sạn
-- minibar / giặt ủi / đồ ăn...
-- =========================================================

CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    type ENUM(
        'service',
        'minibar',
        'damage_fee'
    ) DEFAULT 'service',

    price DECIMAL(12,2) NOT NULL,

    unit VARCHAR(50) DEFAULT 'lần',

    description TEXT,

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- TABLE: booking_services
-- Chức năng:
-- Dịch vụ phát sinh trong booking
-- =========================================================

CREATE TABLE booking_services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    booking_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,

    quantity INT DEFAULT 1,

    -- Giá tại thời điểm dùng
    price_at_use DECIMAL(12,2),

    total_price DECIMAL(12,2),

    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Nhân viên thêm dịch vụ
    added_by BIGINT UNSIGNED NULL,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE,

    FOREIGN KEY (service_id)
        REFERENCES services(id),

    FOREIGN KEY (added_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: invoices
-- Chức năng:
-- Hóa đơn booking
-- =========================================================

CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    invoice_code VARCHAR(30) UNIQUE NOT NULL,

    booking_id BIGINT UNSIGNED UNIQUE NOT NULL,

    room_total DECIMAL(12,2) DEFAULT 0,

    service_total DECIMAL(12,2) DEFAULT 0,

    surcharge_total DECIMAL(12,2) DEFAULT 0,

    discount DECIMAL(12,2) DEFAULT 0,

    tax_amount DECIMAL(12,2) DEFAULT 0,

    net_total DECIMAL(12,2) NOT NULL,

    status ENUM(
        'draft',
        'paid',
        'cancelled'
    ) DEFAULT 'draft',

    paid_at DATETIME NULL,

    created_by BIGINT UNSIGNED NULL,

    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id),

    FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: payments
-- Chức năng:
-- Lịch sử thanh toán
-- =========================================================

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    payment_code VARCHAR(30) UNIQUE NOT NULL,

    invoice_id BIGINT UNSIGNED NULL,

    booking_id BIGINT UNSIGNED NULL,

    payment_type ENUM(
        'deposit',
        'partial',
        'final',
        'refund'
    ) NOT NULL,

    amount DECIMAL(12,2) NOT NULL,

    payment_method ENUM(
        'cash',
        'bank_transfer',
        'momo',
        'vnpay'
    ) DEFAULT 'cash',

    transaction_ref VARCHAR(100),

    received_by BIGINT UNSIGNED NULL,

    paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    note TEXT,

    FOREIGN KEY (invoice_id)
        REFERENCES invoices(id)
        ON DELETE SET NULL,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE SET NULL,

    FOREIGN KEY (received_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: room_logs
-- Chức năng:
-- Log thay đổi trạng thái phòng
-- =========================================================

CREATE TABLE room_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    room_id BIGINT UNSIGNED NOT NULL,

    old_status VARCHAR(50),

    new_status VARCHAR(50),

    note TEXT,

    changed_by BIGINT UNSIGNED NULL,

    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON DELETE CASCADE,

    FOREIGN KEY (changed_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: booking_status_logs
-- Chức năng:
-- Lưu lịch sử thay đổi booking
-- =========================================================

CREATE TABLE booking_status_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    booking_id BIGINT UNSIGNED NOT NULL,

    old_status VARCHAR(50),

    new_status VARCHAR(50),

    changed_by BIGINT UNSIGNED NULL,

    note TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON DELETE CASCADE,

    FOREIGN KEY (changed_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- TABLE: activity_logs
-- Chức năng:
-- Log thao tác hệ thống
-- =========================================================

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id BIGINT UNSIGNED NULL,

    action VARCHAR(100),

    table_name VARCHAR(100),

    record_id BIGINT,

    old_data JSON NULL,

    new_data JSON NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_rooms_status
ON rooms(status);

CREATE INDEX idx_bookings_dates
ON bookings(check_in_date, check_out_date);

CREATE INDEX idx_bookings_status
ON bookings(status);

CREATE INDEX idx_customers_phone
ON customers(phone);

CREATE INDEX idx_customers_cccd
ON customers(cccd);

CREATE INDEX idx_payments_booking
ON payments(booking_id);

CREATE INDEX idx_room_logs_room
ON room_logs(room_id);

CREATE INDEX idx_activity_logs_user
ON activity_logs(user_id);
