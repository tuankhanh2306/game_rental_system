<?php
namespace services;

use core\JWTAuth;
use models\User;
use Exception;

class AuthenticationService
{
    private $userModel;
    private $jwtAuth;
    private $jwtConfig;
    
    public function __construct($database )
    {
        $this->userModel = new User($database);
        $this->jwtAuth = new JWTAuth($database);
        
        // Xử lý config JWT - kiểm tra file tồn tại
        $configPath = __DIR__ . '/../config/jwt_config.php';
        if (file_exists($configPath)) {
            $this->jwtConfig = require $configPath;
        } else {
            // Fallback config nếu file không tồn tại
            $this->jwtConfig = [
                'secret' => 'default-secret-key-change-this-in-production',
                'algorithm' => 'HS256',
                'ttl' => [
                    'access_token' => 3600, // 1 hour
                    'refresh_token' => 86400 * 7, // 7 days
                ],
                'issuer' => 'game_rental_system',
                'audience' => 'game_rental_users'
            ];
            
            // Log warning
            error_log("Warning: JWT config file not found at {$configPath}, using default config");
        }
    }
    
    public function register(array $data): array
    {
        // Validate dữ liệu
        $errors = [];
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors['username'] = 'Tên đăng nhập tối thiểu 3 ký tự';
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ';
        }
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Mật khẩu tối thiểu 6 ký tự';
        }
        if (isset($data['full_name']) && strlen($data['full_name']) > 100) {
            $errors['full_name'] = 'Họ tên không được quá 100 ký tự';
        }
        if (isset($data['phone']) && !preg_match('/^\+?[0-9]{10,15}$/', $data['phone'])) {
            $errors['phone'] = 'Số điện thoại không hợp lệ';
        }
        //
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Kiểm tra tồn tại username/email/phone
        if ($this->userModel->userNameExists($data['username'])) {
            return ['success' => false, 'errors' => ['username' => 'Tên đăng nhập đã tồn tại']];
        }
        if ($this->userModel->emailExists($data['email'])) {
            return ['success' => false, 'errors' => ['email' => 'Email đã được sử dụng']];
        }
        if (isset($data['phone']) && $this->userModel->phoneExists($data['phone'])) {
            return ['success' => false, 'errors' => ['phone' => 'Số điện thoại đã được sử dụng']];
        }
        // Mã hóa mật khẩu và chuẩn bị dữ liệu
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $userData = [
            'username'      => $data['username'],
            'password_hash' => $passwordHash,
            'email'         => $data['email'],
            'full_name'     => $data['full_name'] ?? '',
            'phone'         => $data['phone'] ?? '',
            'role'          => $data['role'] ?? 'user',
            'status'        => $data['status'] ?? 'active'
        ];
        
        // Tạo user mới
        try {
            $userId = $this->userModel->create($userData);
            return ['success' => true, 'user_id' => $userId, 'message' => 'Đăng ký thành công'];
        } catch (Exception $e) {
            error_log("Lỗi đăng ký: " . $e->getMessage());
            return ['success' => false, 'errors' => ['exception' => $e->getMessage()]];
        }
    }
    
    public function login(string $identifier, string $password): array
    {
        try {
            // 1. Lấy user theo username hoặc email
            $user = $this->userModel->findByUsername($identifier) ?: $this->userModel->findByEmail($identifier);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
            }
            
            // 2. Kiểm tra mật khẩu
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
            }
            
            // 3. Kiểm tra trạng thái
            if ($user['status'] !== 'active') {
                return ['success' => false, 'message' => 'Tài khoản không hoạt động'];
            }
            
            // 4. Tạo JWT token
            $payload = [
                'user_id' => $user['user_id'], // Sử dụng user_id từ database
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            $token = $this->jwtAuth->generateToken($payload, 3600);
            
            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'token' => $token,
                'user' => [
                    'id' => $user['user_id'], // Sử dụng user_id từ database
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $this->jwtConfig['ttl']['access_token'] ?? 3600
            ];
        } catch (Exception $e) {
            error_log("Lỗi đăng nhập: " . $e->getMessage());
            return ['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống'];
        }
    }

    /**
     * Xác thực token và lấy thông tin user
     */
    public function authenticate($authHeader) {
        if (empty($authHeader)) {
            return [
                'success' => false,
                'message' => 'Token không được cung cấp'
            ];
        }
        
        $userInfo = $this->jwtAuth->getUserFromToken($authHeader);
        if (!$userInfo) {
            return [
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ];
        }
        
        return [
            'success' => true,
            'user' => $userInfo
        ];
    }
    
    /**
     * Kiểm tra quyền truy cập dựa trên role
     */
    public function hasPermission($userRole, $requiredRole) {
        $roleHierarchy = [
            'admin' => 3,
            'manager' => 2,
            'user' => 1
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Kiểm tra quyền truy cập dữ liệu user
     */
    public function canAccessUserData($currentUser, $targetUserId, $action = 'read') {
        // Admin có thể làm tất cả
        if ($currentUser['role'] === 'admin') {
            return true;
        }
        
        // Manager có thể xem và sửa user thường
        if ($currentUser['role'] === 'manager' && in_array($action, ['read', 'update'])) {
            return true;
        }
        
        // User chỉ có thể truy cập dữ liệu của chính mình
        if ($currentUser['user_id'] == $targetUserId) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Kiểm tra quyền phân trang dựa trên role
     */
    public function canAccessPagination($userRole) {
        return in_array($userRole, ['admin']);
    }
}
?>
