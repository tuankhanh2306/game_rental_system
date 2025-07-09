x-- ===================================================================
-- DATABASE: GAME CONSOLE RENTAL MANAGEMENT SYSTEM
-- Hệ thống quản lý và đặt thuê máy chơi game
-- Ngôn ngữ: PHP
-- ===================================================================

-- Tạo database
CREATE DATABASE game_rental_system;
USE game_rental_system;

-- ===================================================================
-- BẢNG 1: USERS - Quản lý người dùng và quản trị viên
-- ===================================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Mật khẩu đã hash (dùng password_hash() trong PHP)
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('user', 'admin') DEFAULT 'user', -- Phân quyền: user/admin
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

SELECT * from users;

-- ===================================================================
-- BẢNG 3: GAME_CONSOLES - Quản lý máy chơi game
-- ===================================================================
CREATE TABLE game_consoles (
    console_id INT PRIMARY KEY AUTO_INCREMENT,
    console_name VARCHAR(100) NOT NULL, -- Tên máy (VD: PlayStation 5, Xbox Series X)
    console_type VARCHAR(50) NOT NULL, -- Loại máy (PlayStation, Xbox, Nintendo Switch)
    description TEXT, -- Mô tả chi tiết
    image_url VARCHAR(255), -- Đường dẫn hình ảnh
    rental_price_per_hour DECIMAL(10,2) NOT NULL, -- Giá thuê theo giờ
    quantity INT NOT NULL DEFAULT 1, -- Tổng số lượng máy
    available_quantity INT NOT NULL DEFAULT 1, -- Số lượng hiện còn sẵn sàng để thuê
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available', -- Trạng thái tổng thể
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- ===================================================================
-- BẢNG 4: RENTALS - Quản lý đơn thuê máy
-- ===================================================================
CREATE TABLE rentals (
    rental_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    console_id INT NOT NULL,
    rental_start DATETIME NOT NULL, -- Thời gian bắt đầu thuê
    rental_end DATETIME NOT NULL, -- Thời gian kết thúc thuê
    total_hours INT NOT NULL, -- Tổng số giờ thuê
    total_amount DECIMAL(10,2) NOT NULL, -- Tổng tiền
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT, -- Ghi chú
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (console_id) REFERENCES game_consoles(console_id) ON DELETE CASCADE
);

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    payment_method ENUM('cash', 'bank_transfer') NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);


-- ===================================================================
-- BẢNG 5: RENTAL_HISTORY - Lịch sử thuê máy (cho thống kê)
-- ===================================================================
CREATE TABLE rental_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    rental_id INT NOT NULL,
    action VARCHAR(50) NOT NULL, -- created, confirmed, completed, cancelled
    action_by INT, -- user_id của người thực hiện action (admin/user)
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(user_id) ON DELETE SET NULL
);

