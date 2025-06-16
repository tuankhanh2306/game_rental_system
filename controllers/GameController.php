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
                    'available_only' => false // false để lấy tất cả máy chơi game, không chỉ máy có sẵn
                ];
                
                // Validate query parameters
                $params['page'] = max(1, $params['page']);
                $params['limit'] = max(1, min(100, $params['limit'])); // Giới hạn tối đa là 100

                $result = $this->gameService->getConsoleList($params);
                if ($result['success']) {
                    $this->sendResponse(200, true, 'Lấy danh sách máy chơi game thành công', $result['data']);
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

                // FIX: Kiểm tra các trường bắt buộc với tên đúng
                $requiredFields = ['console_name', 'console_type', 'rental_price_per_hour', 'status'];
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        $this->sendResponse(400, false, "Trường {$field} là bắt buộc");
                        return;
                    }
                }

                $result = $this->gameService->createGame($data);
                if ($result['success']) {
                    $this->sendResponse(201, true, 'Tạo máy chơi game thành công', ['console_id' => $result['data']]);
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
                    $this->sendResponse(200, true, 'Lấy thông tin máy chơi game thành công', $result['data']);
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
                    $this->sendResponse(200, true, 'Cập nhật máy chợi game thành công', ['console_id' => $result['console_id']]);
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
                    $this->sendResponse(200, true, 'Xóa máy chơi game thành công');
                } else {
                    $statusCode = $result['message'] === 'ID không hợp lệ.' || $result['message'] === 'Không tìm thấy máy chơi game.' ? 404 : 500;
                    $this->sendResponse($statusCode, false, $result['message']);
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