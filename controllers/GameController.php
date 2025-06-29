<?php
namespace controllers;
use services\GameService;
use core\Database;
use Exception;

class GameController{
    private $gameService;
    private $db;
    
    public function __construct($database = null){
        $this->db = $database ?? Database::getInstance();
        $this->gameService = new GameService($this->db); 
    }
    
    // Hiển thị danh sách máy chơi game
    public function index(){
        try{
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }
            
            $params = [
                'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 12,
                'type' => isset($_GET['type']) ? $_GET['type'] : '',
                'search' => isset($_GET['search']) ? $_GET['search'] : '',
                'available_only' => isset($_GET['available_only']) ? filter_var($_GET['available_only'], FILTER_VALIDATE_BOOLEAN) : true
            ];
            
            // Validate query parameters
            $params['page'] = max(1, $params['page']);
            $params['limit'] = max(1, min(100, $params['limit'])); // Giới hạn tối đa là 100

            $result = $this->gameService->getConsoleList($params);
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            }
            else {
                $this->sendResponse(500, false, $result['message']);
            }

        }
        catch(Exception $e){
            $this->sendResponse(500, false, 'Lỗi hệ thống ' . $e->getMessage());
        }
    }
    
    // Tạo máy chơi game mới
    public function create(){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Kiểm tra các trường bắt buộc
            $requiredFields = ['console_name', 'console_type', 'rental_price_per_hour'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse(400, false, "Trường {$field} là bắt buộc");
                    return;
                }
            }

            $result = $this->gameService->createGame($data);
            if ($result['success']) {
                $this->sendResponse(201, true, $result['message'], ['console_id' => $result['data']]);
            } else {
                $statusCode = isset($result['errors']) ? 400 : 500; 
                $this->sendResponse($statusCode, false, $result['message'], isset($result['errors']) ? ['errors' => $result['errors']] : null);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy chi tiết máy chơi game
    public function show($id){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->gameService->getConsoleDetail($id);
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $statusCode = $result['message'] === 'ID không hợp lệ.' || $result['message'] === 'Không tìm thấy máy chơi game.' ? 404 : 500;
                $this->sendResponse($statusCode, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Cập nhật máy chơi game
    public function update($id){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            $result = $this->gameService->updateGame($id, $data);
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], ['console_id' => $result['console_id']]);
            } else {
                $statusCode = isset($result['errors']) ? 400 : 500;
                $this->sendResponse($statusCode, false, $result['message'], isset($result['errors']) ? ['errors' => $result['errors']] : null);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Xóa máy chơi game
    public function delete($id){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->gameService->deleteGame($id);
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $statusCode = $result['message'] === 'ID không hợp lệ.' || $result['message'] === 'Không tìm thấy máy chơi game.' ? 404 : 500;
                $this->sendResponse($statusCode, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy các loại máy chơi game
    public function getTypes(){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->gameService->getConsoleType();
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy thống kê máy chơi game
    public function getStats(){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->gameService->getConsoleStats();
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy console phổ biến
    public function getPopular(){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $limit = isset($_GET['limit']) ? max(1, min(20, (int)$_GET['limit'])) : 5;
            $result = $this->gameService->getPopularConsoles($limit);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Tìm console theo khoảng giá
    public function findByPrice(){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $minPrice = isset($_GET['min_price']) ? max(0, (float)$_GET['min_price']) : 0;
            $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000000;
            
            if ($minPrice > $maxPrice) {
                $this->sendResponse(400, false, 'Giá tối thiểu không được lớn hơn giá tối đa');
                return;
            }

            $result = $this->gameService->findByPriceRange($minPrice, $maxPrice);
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Cập nhật trạng thái console
    public function updateStatus($id){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data) || !isset($data['status'])) {
                $this->sendResponse(400, false, 'Trạng thái là bắt buộc');
                return;
            }

            $allowedStatuses = ['available', 'rented', 'maintenance'];
            if (!in_array($data['status'], $allowedStatuses)) {
                $this->sendResponse(400, false, 'Trạng thái không hợp lệ');
                return;
            }

            $result = $this->gameService->updateConsoleStatus($id, $data['status']);
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message']);
            } else {
                $statusCode = $result['message'] === 'ID không hợp lệ.' ? 404 : 500;
                $this->sendResponse($statusCode, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Kiểm tra tình trạng có sẵn
    public function checkAvailability($id){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $quantity = isset($_GET['quantity']) ? max(1, (int)$_GET['quantity']) : 1;
            $result = $this->gameService->checkAvailability($id, $quantity);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy console sắp hết hàng
    public function getLowStock(){
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $threshold = isset($_GET['threshold']) ? max(1, (int)$_GET['threshold']) : 2;
            $result = $this->gameService->getLowStockConsoles($threshold);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Gửi phản hồi JSON
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