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
    status ENUM('available', 'rented', 'maintenance') DEFAULT 'available', -- Trạng thái máy
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

-- ===================================================================
-- BẢNG 6: SYSTEM_SETTINGS - Cấu hình hệ thống
-- ===================================================================
CREATE TABLE system_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Kiểm tra cấu trúc bảng hiện tại
DESCRIBE token_blacklist;

-- Kiểm tra indexes
SHOW INDEX FROM token_blacklist;

-- Kiểm tra dữ liệu
SELECT COUNT(*) as total_blacklisted_tokens FROM token_blacklist;
SELECT COUNT(*) as expired_tokens FROM token_blacklist WHERE expires_at <= NOW();
-- =================================================================
-- BẢNG 7: TOKEN_BLACKLIST - Quản lý token bị blacklist (để logout)

CREATE TABLE IF NOT EXISTS token_blacklist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token_hash VARCHAR(255) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                INDEX idx_token_hash (token_hash),
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at)
);

-- ===================================================================
-- CHÈN DỮ LIỆU MẪU (SAMPLE DATA)
-- ===================================================================

-- Tạo tài khoản admin mặc định
INSERT INTO users (username, email, password_hash, full_name, role) VALUES 
('admin', 'admin@gamerental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Tạo một số user mẫu
INSERT INTO users (username, email, password_hash, full_name, phone) VALUES 
('user1', 'user1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '0901234567'),
('user2', 'user2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', '0907654321');

-- Tạo một số máy chơi game mẫu
INSERT INTO game_consoles (console_name, console_type, description, rental_price_per_hour, status) VALUES 
('PlayStation 5 Standard', 'PlayStation', 'Máy PlayStation 5 phiên bản tiêu chuẩn với đầy đủ game hot nhất', 50000.00, 'available'),
('Xbox Series X', 'Xbox', 'Xbox Series X với Game Pass Ultimate, hàng trăm game miễn phí', 45000.00, 'available'),
('Nintendo Switch OLED', 'Nintendo', 'Nintendo Switch phiên bản OLED, màn hình lớn sắc nét', 40000.00, 'available'),
('PlayStation 5 Digital', 'PlayStation', 'PlayStation 5 Digital Edition, chơi game số hoàn toàn', 45000.00, 'maintenance');

-- Cấu hình hệ thống
INSERT INTO system_settings (setting_key, setting_value, description) VALUES 
('site_name', 'Game Rental System', 'Tên website'),
('session_timeout', '3600', 'Thời gian timeout session (giây)'),
('max_rental_hours', '24', 'Số giờ thuê tối đa cho một lần'),
('min_rental_hours', '1', 'Số giờ thuê tối thiểu');

-- ===================================================================
-- INDEX VÀ CONSTRAINTS BỔ SUNG
-- ===================================================================

-- Index cho tìm kiếm nhanh
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_rentals_user_id ON rentals(user_id);
CREATE INDEX idx_rentals_console_id ON rentals(console_id);
CREATE INDEX idx_rentals_status ON rentals(status);
CREATE INDEX idx_rentals_date ON rentals(rental_start, rental_end);

-- ===================================================================
-- STORED PROCEDURES CHO PHP (Tùy chọn)
-- ===================================================================

-- Procedure kiểm tra máy có sẵn trong khoảng thời gian
DELIMITER //
CREATE PROCEDURE CheckConsoleAvailability(
    IN p_console_id INT,
    IN p_start_time DATETIME,
    IN p_end_time DATETIME,
    OUT p_available BOOLEAN
)
BEGIN
    DECLARE conflict_count INT;
    
    SELECT COUNT(*) INTO conflict_count
    FROM rentals 
    WHERE console_id = p_console_id 
    AND status IN ('confirmed', 'pending')
    AND (
        (rental_start <= p_start_time AND rental_end > p_start_time) OR
        (rental_start < p_end_time AND rental_end >= p_end_time) OR
        (rental_start >= p_start_time AND rental_end <= p_end_time)
    );
    
    SET p_available = (conflict_count = 0);
END //
DELIMITER ;

-- ===================================================================
-- GHI CHÚ QUAN TRỌNG CHO DEVELOPER PHP
-- ===================================================================

/*
1. KẾT NỐI DATABASE TRONG PHP:
   - Sử dụng PDO hoặc MySQLi
   - Tạo file config.php để lưu thông tin kết nối
   
2. BẢO MẬT MẬT KHẨU:
   - Sử dụng password_hash($password, PASSWORD_DEFAULT) để hash
   - Sử dụng password_verify($password, $hash) để xác thực
   
3. QUẢN LÝ SESSION:
   - Lưu session_id vào bảng user_sessions
   - Kiểm tra expires_at trước khi cho phép truy cập
   - Xóa session khi logout hoặc hết hạn
   
4. PHÂN QUYỀN:
   - Kiểm tra role trong bảng users
   - Tạo middleware để protect admin routes
   
5. XỬ LÝ DATETIME:
   - Sử dụng MySQL DATETIME format: Y-m-d H:i:s
   - Timezone cần được set đúng
   
6. VALIDATION:
   - Validate input trước khi insert/update
   - Sử dụng prepared statements để tránh SQL injection
   
7. ERROR HANDLING:
   - Luôn sử dụng try-catch khi làm việc với database
   - Log lỗi để debug
*/