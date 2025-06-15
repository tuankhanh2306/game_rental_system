<?php
namespace controllers;

use services\AuthenticationService;
use Exception;

class AuthController
{
    private $authService;

    public function __construct($database = null)
    {
        $this->authService = new AuthenticationService($database);
    }

    public function register()
    {
        // Bắt đầu output buffering để tránh output trước header
        ob_start();
        
        try {
            error_log("=== REGISTER REQUEST START ===");
            
            // Set headers
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            
            // Lấy raw input
            $rawInput = file_get_contents('php://input');
            error_log("Raw input: " . $rawInput);
            
            if (empty($rawInput)) {
                $result = ['success' => false, 'message' => 'Không có dữ liệu được gửi'];
                error_log("Empty input data");
                echo json_encode($result);
                return;
            }
            
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $result = ['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg()];
                error_log("JSON decode error: " . json_last_error_msg());
                echo json_encode($result);
                return;
            }

            error_log("Parsed data: " . print_r($data, true));
            
            $result = $this->authService->register($data);
            
            error_log("Service result: " . print_r($result, true));
            
            // Set status code
            if (!$result['success']) {
                http_response_code(400);
            } else {
                http_response_code(201);
            }
            
            $jsonResult = json_encode($result);
            error_log("Final JSON output: " . $jsonResult);
            
            echo $jsonResult;
            
        } catch (Exception $e) {
            error_log("Exception in register: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ]);
        } finally {
            error_log("=== REGISTER REQUEST END ===");
            ob_end_flush();
        }
    }

    public function login()
    {
        ob_start();
        
        try {
            error_log("=== LOGIN REQUEST START ===");
            
            // Set headers
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: Content-Type');
            
            $rawInput = file_get_contents('php://input');
            error_log("Raw input: " . $rawInput);
            
            if (empty($rawInput)) {
                echo json_encode(['success' => false, 'message' => 'Không có dữ liệu được gửi']);
                return;
            }
            
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg()]);
                return;
            }

            if (!isset($data['identifier'], $data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đăng nhập']);
                return;
            }

            error_log("Login data: " . print_r($data, true));
            
            $result = $this->authService->login($data['identifier'], $data['password']);
            
            error_log("Login result: " . print_r($result, true));
            
            if (!$result['success']) {
                http_response_code(401);
            }
            
            $jsonResult = json_encode($result);
            error_log("Final login JSON: " . $jsonResult);
            
            echo $jsonResult;
            
        } catch (Exception $e) {
            error_log("Exception in login: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ]);
        } finally {
            error_log("=== LOGIN REQUEST END ===");
            ob_end_flush();
        }
    }
}
?>
