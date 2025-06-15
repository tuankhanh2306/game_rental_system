<?php
    class Auth {
        private $db;
        
        public function __construct() {
            $this->db = Database::getInstance()->getConnection();
        }
        
        /**
         * Đăng ký người dùng mới
         */
        public function register($username, $email, $password, $fullName, $phone = null) {
            try {
                // Validate input
                $validation = $this->validateRegistration($username, $email, $password, $fullName);
                if (!$validation['success']) {
                    return $validation;
                }
                
                // Check email và username đã tồn tại chưa
                if ($this->isEmailExists($email)) {
                    return ['success' => false, 'message' => 'Email đã được sử dụng'];
                }
                
                if ($this->isUsernameExists($username)) {
                    return ['success' => false, 'message' => 'Username đã được sử dụng'];
                }
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user vào database
                $sql = "INSERT INTO users (username, email, password_hash, full_name, phone, role, status) 
                        VALUES (?, ?, ?, ?, ?, 'user', 'active')";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([$username, $email, $hashedPassword, $fullName, $phone]);
                
                if ($result) {
                    return [
                        'success' => true, 
                        'message' => 'Đăng ký thành công',
                        'user_id' => $this->db->lastInsertId()
                    ];
                } else {
                    return ['success' => false, 'message' => 'Lỗi khi tạo tài khoản'];
                }
                
            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Lỗi hệ thống'];
            }
        }
        
        /**
         * Đăng nhập người dùng
         */
        public function login($loginField, $password, $rememberMe = false) {
            try {
                // Tìm user theo email hoặc username
                $user = $this->findUserByEmailOrUsername($loginField);
                
                if (!$user) {
                    return ['success' => false, 'message' => 'Tài khoản không tồn tại'];
                }
                
                // Kiểm tra tài khoản có bị khóa không
                if ($user['status'] !== 'active') {
                    return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
                }
                
                // Verify password
                if (!password_verify($password, $user['password_hash'])) {
                    return ['success' => false, 'message' => 'Mật khẩu không chính xác'];
                }
                
                // Tạo session
                $sessionResult = $this->createSession($user['user_id'], $rememberMe);
                
                if ($sessionResult) {
                    return [
                        'success' => true,
                        'message' => 'Đăng nhập thành công',
                        'user' => [
                            'user_id' => $user['user_id'],
                            'username' => $user['username'],
                            'email' => $user['email'],
                            'full_name' => $user['full_name'],
                            'role' => $user['role']
                        ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Lỗi tạo phiên đăng nhập'];
                }
                
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Lỗi hệ thống'];
            }
        }
        
        /**
         * Đăng xuất người dùng
         */
        public function logout() {
            try {
                $sessionId = session_id();
                
                // Xóa session khỏi database
                $sql = "DELETE FROM user_sessions WHERE session_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$sessionId]);
                
                // Xóa session PHP
                session_unset();
                session_destroy();
                
                // Xóa cookie remember me nếu có
                if (isset($_COOKIE['remember_token'])) {
                    setcookie('remember_token', '', time() - 3600, '/');
                }
                
                return ['success' => true, 'message' => 'Đăng xuất thành công'];
                
            } catch (PDOException $e) {
                error_log("Logout error: " . $e->getMessage());
                return ['success' => false, 'message' => 'Lỗi khi đăng xuất'];
            }
        }
        
        /**
         * Kiểm tra user đã đăng nhập chưa
         */
        public function isLoggedIn() {
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
                return false;
            }
            
            return $this->validateSession($_SESSION['session_id'], $_SESSION['user_id']);
        }
        
        /**
         * Kiểm tra user có quyền admin không
         */
        public function isAdmin() {
            return $this->isLoggedIn() && $_SESSION['role'] === 'admin';
        }
        
        /**
         * Lấy thông tin user hiện tại
         */
        public function getCurrentUser() {
            if (!$this->isLoggedIn()) {
                return null;
            }
            
            $sql = "SELECT user_id, username, email, full_name, phone, role, status 
                    FROM users WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
            
            return $stmt->fetch();
        }
        
        /**
         * Validate dữ liệu đăng ký
         */
        private function validateRegistration($username, $email, $password, $fullName) {
            $errors = [];
            
            // Validate username
            if (empty($username)) {
                $errors[] = 'Username không được để trống';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username phải có ít nhất 3 ký tự';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors[] = 'Username chỉ được chứa chữ, số và dấu gạch dưới';
            }
            
            // Validate email
            if (empty($email)) {
                $errors[] = 'Email không được để trống';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email không hợp lệ';
            }
            
            // Validate password
            if (empty($password)) {
                $errors[] = 'Mật khẩu không được để trống';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
            }
            
            // Validate full name
            if (empty($fullName)) {
                $errors[] = 'Họ tên không được để trống';
            } elseif (strlen($fullName) < 2) {
                $errors[] = 'Họ tên phải có ít nhất 2 ký tự';
            }
            
            if (empty($errors)) {
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
        }
        
        /**
         * Kiểm tra email đã tồn tại chưa
         */
        private function isEmailExists($email) {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        }
        
        /**
         * Kiểm tra username đã tồn tại chưa
         */
        private function isUsernameExists($username) {
            $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetchColumn() > 0;
        }
        
        /**
         * Tìm user theo email hoặc username
         */
        private function findUserByEmailOrUsername($loginField) {
            $sql = "SELECT user_id, username, email, password_hash, full_name, role, status 
                    FROM users WHERE email = ? OR username = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$loginField, $loginField]);
            return $stmt->fetch();
        }
        
        /**
         * Tạo session cho user
         */
        private function createSession($userId, $rememberMe = false) {
            try {
                // Tạo session ID mới
                session_regenerate_id(true);
                $sessionId = session_id();
                
                // Xác định thời gian hết hạn
                $sessionLifetime = $rememberMe ? (30 * 24 * 60 * 60) : 3600; // 30 ngày hoặc 1 giờ
                $expiresAt = date('Y-m-d H:i:s', time() + $sessionLifetime);
                
                // Lưu session vào database
                $sql = "INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    $sessionId,
                    $userId,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    $expiresAt
                ]);
                
                if ($result) {
                    // Lấy thông tin user
                    $user = $this->getUserById($userId);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['session_id'] = $sessionId;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    // Set cookie nếu remember me
                    if ($rememberMe) {
                        setcookie('remember_token', $sessionId, time() + $sessionLifetime, '/');
                    }
                    
                    return true;
                }
                
                return false;
                
            } catch (PDOException $e) {
                error_log("Create session error: " . $e->getMessage());
                return false;
            }
        }
        
        /**
         * Validate session
         */
        private function validateSession($sessionId, $userId) {
            try {
                $sql = "SELECT COUNT(*) FROM user_sessions 
                        WHERE session_id = ? AND user_id = ? AND expires_at > NOW()";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$sessionId, $userId]);
                
                return $stmt->fetchColumn() > 0;
                
            } catch (PDOException $e) {
                error_log("Validate session error: " . $e->getMessage());
                return false;
            }
        }
        
        /**
         * Lấy thông tin user theo ID
         */
        private function getUserById($userId) {
            $sql = "SELECT user_id, username, email, full_name, phone, role, status 
                    FROM users WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch();
        }
        
        /**
         * Xóa session hết hạn
         */
        public function cleanExpiredSessions() {
            try {
                $sql = "DELETE FROM user_sessions WHERE expires_at < NOW()";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute();
            } catch (PDOException $e) {
                error_log("Clean expired sessions error: " . $e->getMessage());
                return false;
            }
        }
    }
?>