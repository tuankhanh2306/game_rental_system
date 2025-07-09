<?php
namespace controllers;

use services\RentalService;
use core\Database;
use Exception;
use services\AuthenticationService;

class RentalController
{
    private $rentalService;
    private $db;
    private $authService;


    public function __construct($database = null)
    {
        $this->db = $database ?? Database::getInstance();
        $this->rentalService = new RentalService($this->db);
        $this->authService = new AuthenticationService($this->db);
    }



    /**
     * Xử lý yêu cầu từ client để tạo đơn đặt thuê máy chơi game.
     * Phương thức này sẽ xác thực người dùng, kiểm tra dữ liệu đầu vào,
     * và gọi service để thực hiện việc tạo đơn đặt thuê.
     *
     * 
     */
    // Tạo đơn đặt thuê máy chơi game
    public function create()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $authHeader = $this->getAuthHeader();
            // Kiểm tra xem token có được cung cấp không
            if (!$authHeader) {
                $this->sendResponse(401, false, 'Token không được cung cấp');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                $this->sendResponse(400, false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Gọi service mới
            $result = $this->rentalService->createPaymentWithRentals($data, $authHeader);

            if ($result['success']) {
                $this->sendResponse(201, true, 'Đặt thuê thành công', $result['data']);
            } else {
                $statusCode = isset($result['error']) ? 500 : 400;
                $this->sendResponse(
                    $statusCode,
                    false,
                    $result['message'],
                    isset($result['error']) ? ['error' => $result['error']] : null
                );
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống', ['error' => $e->getMessage()]);
        }
    }




    // Lấy danh sách các đơn thuê
    public function index()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $filters = [
                'status' => $_GET['status'] ?? null,
                'user_id' => $_GET['user_id'] ?? null,
                'console_id' => $_GET['console_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'page' => $_GET['page'] ?? 1,
                'limit' => $_GET['limit'] ?? 10
            ];

            // Loại bỏ các giá trị null hoặc rỗng
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->rentalService->getAllRentals($filters);
            
            if ($result['success']) {
                $this->sendResponse(200, true, $result['message'], $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }


    // Thống kê tổng quan
    public function stats()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->rentalService->getStats();
            if ($result['success']) {
                $this->sendResponse(200, true, 'Thống kê thành công', $result['data']);
            } else {
                $this->sendResponse(500, false, $result['message']);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy đơn thuê theo ID
    public function show($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->rentalService->getRentalById($id);
            $this->sendResponse($result['success'] ? 200 : 404, $result['success'], $result['message'], $result['data'] ?? '');
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Cập nhật trạng thái đơn thuê (PUT /rentals/{id}/status)
    public function updateStatus($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['status'])) {
                $this->sendResponse(400, false, 'Thiếu trường status');
                return;
            }

            $notes = $data['notes'] ?? '';
            $result = $this->rentalService->updateStatus($id, $data['status'], $notes);

            $this->sendResponse($result['success'] ? 200 : 400, $result['success'], $result['message']);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }


    // Lấy thống kê theo trạng thái đơn thuê
    public function getStatusStats()
    {
        try {
            $stats = $this->rentalService->getStatusStats();
            $this->sendResponse(200, true, 'Thống kê trạng thái thành công', $stats);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi thống kê: ' . $e->getMessage());
        }
    }

    // Lấy thống kê doanh thu theo tháng
    public function getMonthlyRevenue()
    {
        try {
            $year = $_GET['year'] ?? date('Y');
            $data = $this->rentalService->getMonthlyRevenue($year);
            $this->sendResponse(200, true, 'Thống kê doanh thu thành công', $data);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi thống kê: ' . $e->getMessage());
        }
    }


    // Lấy tổng doanh thu
    public function getTotalRevenue()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->rentalService->getTotalRevenue();
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy tổng doanh thu thành công', $result['data']);
            } else {
                $this->sendResponse(500, false, 'Không thể lấy tổng doanh thu');
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

    private function getAuthHeader()
    {
        $headers = getallheaders();
        return isset($headers['Authorization']) ? $headers['Authorization'] : 
               (isset($headers['authorization']) ? $headers['authorization'] : null);
    }
}
