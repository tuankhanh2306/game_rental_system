<?php
namespace controllers;

use services\UserService;
use core\Database;
use core\JWTAuth;
use Exception;

class UserController
{
    private $userService;
    private $jwtAuth;
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database ?? Database::getInstance()->getConnection();
        $this->userService = new UserService($this->db);
        $this->jwtAuth = new JWTAuth();
    }

    /**
     * Lấy thông tin profile người dùng hiện tại
     * GET /api/users/profile
     */
    public function getProfile()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực token
            $user = $this->authenticateUser();
            if (!$user) return;

            // Lấy thông tin user
            $result = $this->userService->getUserbyId($user['user_id']);
            
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy thông tin thành công', $result['user']);
            } else {
                $this->sendResponse(404, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    //lấy người dùng theo id
    public function getUserById($userId)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực token
            $user = $this->authenticateUser();
            if (!$user) return;

            // Chỉ admin mới có thể lấy thông tin người dùng khác
            if ($user['role'] !== 'admin') {
                $this->sendResponse(403, false, 'Không có quyền truy cập');
                return;
            }

            // Lấy thông tin user
            $result = $this->userService->getUserbyId($userId);
            
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy thông tin thành công', $result['user']);
            } else {
                $this->sendResponse(404, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cập nhật thông tin profile
     * PUT /api/users/profile
     */
    public function updateProfile()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực token
            $user = $this->authenticateUser();
            if (!$user) return;

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Cập nhật thông tin
            $result = $this->userService->updateUser($user['user_id'], $input);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $this->sendResponse(400, false, $result['message'], $result['error'] ?? null);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Đổi mật khẩu
     * POST /api/users/change-password
     */
    public function changePassword()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực token
            $user = $this->authenticateUser();
            if (!$user) return;

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['old_password']) || empty($input['new_password'])) {
                $this->sendResponse(400, false, 'Mật khẩu cũ và mật khẩu mới là bắt buộc');
                return;
            }

            // Đổi mật khẩu
            $result = $this->userService->changePassword(
                $user['user_id'],
                $input['old_password'],
                $input['new_password']
            );
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $this->sendResponse(400, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Lấy danh sách người dùng (Admin only)
     * GET /api/users
     */
    public function getUsers()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực và kiểm tra quyền admin
            $user = $this->authenticateUser();
            if (!$user) return;

            if ($user['role'] !== 'admin') {
                $this->sendResponse(403, false, 'Không có quyền truy cập');
                return;
            }

            // Lấy parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            // Validate parameters
            $page = max(1, $page);
            $limit = max(1, min(100, $limit)); // Giới hạn tối đa 100 records

            $result = $this->userService->getUsers($page, $limit, $search);
            
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy danh sách thành công', [
                    'users' => $result['users'],
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'limit' => $result['limit'],
                        'total_pages' => ceil($result['total'] / $result['limit'])
                    ]
                ]);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Lấy thống kê người dùng (Admin only)
     * GET /api/users/stats
     */
    public function getUserStats()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực và kiểm tra quyền admin
            $user = $this->authenticateUser();
            if (!$user) return;

            if ($user['role'] !== 'admin') {
                $this->sendResponse(403, false, 'Không có quyền truy cập');
                return;
            }

            $result = $this->userService->getUserStatsByRole();
            
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy thống kê thành công', $result['stats']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Xóa người dùng (Admin only)
     * DELETE /api/users/{id}
     */
    public function deleteUser($userId)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực và kiểm tra quyền admin
            $user = $this->authenticateUser();
            if (!$user) return;

            if ($user['role'] !== 'admin') {
                $this->sendResponse(403, false, 'Không có quyền truy cập');
                return;
            }

            // Không cho phép admin tự xóa chính mình
            if ($user['user_id'] == $userId) {
                $this->sendResponse(400, false, 'Không thể xóa chính mình');
                return;
            }

            $result = $this->userService->deleteUser($userId);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $this->sendResponse(400, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cập nhật thông tin người dùng (Admin only)
     * PUT /api/users/{id}
     */
    public function updateUser($userId )
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Xác thực và kiểm tra quyền admin
            $user = $this->authenticateUser();
            if (!$user) return;

            if ($user['role'] !== 'admin') {
                $this->sendResponse(403, false, 'Không có quyền truy cập');
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            $result = $this->userService->updateUser($userId, $input);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $this->sendResponse(400, false, $result['message'], $result['error'] ?? null);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Refresh token
     * POST /api/users/refresh-token
     */
    public function refreshToken()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['refresh_token'])) {
                $this->sendResponse(400, false, 'Refresh token là bắt buộc');
                return;
            }

            // Validate refresh token
            $tokenData = $this->jwtAuth->validateToken($input['refresh_token']);
            
            if (!$tokenData || $tokenData['type'] !== 'refresh_token') {
                $this->sendResponse(401, false, 'Refresh token không hợp lệ');
                return;
            }

            // Tạo access token mới
            $tokenPayload = [
                'user_id' => $tokenData['user_id'],
                'role' => $tokenData['role']
            ];
            
            $newAccessToken = $this->jwtAuth->generateToken($tokenPayload, 'access_token');

            $this->sendResponse(200, true, 'Token đã được làm mới', [
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer'
            ]);

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    

    /**
     * Xác thực người dùng từ JWT token
     */
    private function authenticateUser()
    {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : 
                     (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader) {
            $this->sendResponse(401, false, 'Token không được cung cấp');
            return false;
        }

        $tokenData = $this->jwtAuth->validateToken($authHeader);
        
        if (!$tokenData) {
            $this->sendResponse(401, false, 'Token không hợp lệ hoặc đã hết hạn');
            return false;
        }

        return $tokenData;
    }

    /**
     * Gửi response JSON
     */
    private function sendResponse($statusCode, $success, $message, $data = null)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>