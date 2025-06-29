<?php
namespace controllers;

use services\UserService;
use core\Database;
use Exception;

class UserController
{
    private $userService;
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database ?? Database::getInstance()->getConnection();
        $this->userService = new UserService($this->db);
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

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            // Xác thực và lấy thông tin user từ token
            $authResult = $this->userService->getUserFromToken($authHeader);
            if (!$authResult['success']) {
                $this->sendResponse(401, false, $authResult['message']);
                return;
            }

            $userId = $authResult['user']['user_id'];

            // Lấy thông tin user
            $result = $this->userService->getUserbyId($userId, $authHeader);
            
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
     * Lấy người dùng theo id
     * GET /api/users/{id}
     */
    public function getUserById($userId)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            // Lấy thông tin user
            $result = $this->userService->getUserbyId($userId, $authHeader);
            
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy thông tin thành công', $result['user']);
            } else {
                $statusCode = ($result['message'] === 'Bạn không có quyền truy cập thông tin này') ? 403 : 404;
                $this->sendResponse($statusCode, false, $result['message']);
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

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            // Xác thực và lấy thông tin user từ token
            $authResult = $this->userService->getUserFromToken($authHeader);
            if (!$authResult['success']) {
                $this->sendResponse(401, false, $authResult['message']);
                return;
            }

            $userId = $authResult['user']['user_id'];

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Cập nhật thông tin
            $result = $this->userService->updateUser($userId, $input, $authHeader);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $statusCode = (strpos($result['message'], 'quyền') !== false) ? 403 : 400;
                $this->sendResponse($statusCode, false, $result['message'], $result['error'] ?? null);
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

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            // Xác thực và lấy thông tin user từ token
            $authResult = $this->userService->getUserFromToken($authHeader);
            if (!$authResult['success']) {
                $this->sendResponse(401, false, $authResult['message']);
                return;
            }

            $userId = $authResult['user']['user_id'];

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['old_password']) || empty($input['new_password'])) {
                $this->sendResponse(400, false, 'Mật khẩu cũ và mật khẩu mới là bắt buộc');
                return;
            }

            // Đổi mật khẩu
            $result = $this->userService->changePassword(
                $userId,
                $input['old_password'],
                $input['new_password'],
                $authHeader
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

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            // Lấy parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            // Validate parameters
            $page = max(1, $page);
            $limit = max(1, min(100, $limit)); // Giới hạn tối đa 100 records

            $result = $this->userService->getUsers($page, $limit, $search, $authHeader);
            
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
                $statusCode = (strpos($result['message'], 'quyền') !== false) ? 403 : 500;
                $this->sendResponse($statusCode, false, $result['message']);
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

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            // Kiểm tra quyền admin (có thể thêm vào UserService)
            $authResult = $this->userService->getUserFromToken($authHeader);
            if (!$authResult['success']) {
                $this->sendResponse(401, false, $authResult['message']);
                return;
            }

            if ($authResult['user']['role'] !== 'admin') {
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

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            $result = $this->userService->deleteUser($userId, $authHeader);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $statusCode = (strpos($result['message'], 'quyền') !== false) ? 403 : 400;
                $this->sendResponse($statusCode, false, $result['message']);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cập nhật thông tin người dùng (Admin only)
     * PUT /api/users/{id}
     */
    public function updateUser($userId)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Lấy auth header
            $authHeader = $this->getAuthHeader();
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            $result = $this->userService->updateUser($userId, $input, $authHeader);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $statusCode = (strpos($result['message'], 'quyền') !== false) ? 403 : 400;
                $this->sendResponse($statusCode, false, $result['message'], $result['error'] ?? null);
            }

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Refresh token
     * POST /api/users/refresh-token
     * Note: Chức năng này có thể được chuyển sang AuthController
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

            // Có thể implement logic refresh token trong UserService
            // hoặc chuyển sang AuthService/AuthController
            $this->sendResponse(501, false, 'Chức năng chưa được triển khai');

        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Lấy auth header từ request
     */
    private function getAuthHeader()
    {
        $headers = getallheaders();
        return isset($headers['Authorization']) ? $headers['Authorization'] : 
               (isset($headers['authorization']) ? $headers['authorization'] : null);
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