<?php
namespace controllers;

use services\RentalHistoryService;
use core\Database;
use Exception;

class RentalHistoryController
{
    private $historyService;

    public function __construct($db = null)
    {
        $db = $db ?? Database::getInstance();
        $this->historyService = new RentalHistoryService($db);
    }

    // Lấy tất cả lịch sử đơn thuê
    public function index()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            // Lấy các tham số lọc từ query string
        $filters = [
            'action' => $_GET['action'] ?? null,
            'action_by' => $_GET['action_by'] ?? null,
            'from_date' => $_GET['from_date'] ?? null,
            'to_date' => $_GET['to_date'] ?? null,
        ];


            $result = $this->historyService->getAll($filters);
            $this->sendResponse(200, true, 'Lấy lịch sử thành công', $result);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy lịch sử theo rental_id
    public function showByRentalId($rentalId)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $result = $this->historyService->getByRentalId($rentalId);
            $this->sendResponse(200, true, 'Lấy lịch sử theo đơn thuê thành công', $result);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    // Lấy hoạt động gần đây
    public function recentActivity()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendResponse(405, false, 'Method không được hỗ trợ');
                return;
            }

            $limit = $_GET['limit'] ?? 20;
            $result = $this->historyService->getRecent($limit);
            $this->sendResponse(200, true, 'Hoạt động gần đây', $result);
        } catch (Exception $e) {
            $this->sendResponse(500, false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    private function sendResponse($code, $success, $message, $data = null)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => $success, 'message' => $message];
        if ($data !== null) $response['data'] = $data;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
