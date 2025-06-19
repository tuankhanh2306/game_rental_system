<?php
namespace controllers;

use services\RentalService;
use core\Database;
use Exception;

class RentalController
{
    private $rentalService;
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database ?? Database::getInstance();
        $this->rentalService = new RentalService($this->db);
    }

    // Tạo đơn đặt thuê máy chơi game
    public function create()
    {
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

            $requiredFields = ['user_id', 'console_id', 'rental_start', 'rental_end'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse(400, false, "Trường {$field} là bắt buộc");
                    return;
                }
            }

            $result = $this->rentalService->createRental($data);
            if ($result['success']) {
                $this->sendResponse(201, true, 'Đặt thuê máy thành công', ['rental_id' => $result['data']]);
            } else {
                $statusCode = isset($result['errors']) ? 400 : 500;
                $this->sendResponse($statusCode, false, $result['message'], isset($result['errors']) ? ['errors' => $result['errors']] : null);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
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
                'console_id' => $_GET['console_id'] ?? null
            ];

            $result = $this->rentalService->getAllRentals($filters);
            if ($result['success']) {
                $this->sendResponse(200, true, 'Lấy danh sách đơn thuê thành công', $result['data']);
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
            $this->sendResponse($result['success'] ? 200 : 404, $result['success'], $result['message'], $result['data'] ?? null);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Cập nhật trạng thái đơn thuê
    public function updateStatus()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['rental_id']) || empty($data['status'])) {
                $this->sendResponse(400, false, 'Thiếu rental_id hoặc status');
                return;
            }

            $notes = $data['notes'] ?? '';
            $result = $this->rentalService->updateStatus($data['rental_id'], $data['status'], $notes);
            $this->sendResponse($result['success'] ? 200 : 400, $result['success'], $result['message']);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Đơn thuê sắp hết hạn (GET /rentals/upcoming?hours=24)
    public function upcoming()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $hours = $_GET['hours'] ?? 24;
            $result = $this->rentalService->getUpcomingRentals($hours);
            $this->sendResponse(200, true, 'Lấy danh sách thành công', $result['data']);
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
