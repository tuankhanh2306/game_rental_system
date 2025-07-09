<?php
namespace controllers;
use services\PaymentService;
class PaymentController
{
    private $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    /**
     * Tạo thanh toán mới
     */
    public function create()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
                return;
            }

            // Lấy body
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
                return;
            }

            // Tạo thanh toán
            $result = $this->paymentService->createPayment($data);

            if ($result['success']) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Tạo thanh toán thành công', 'data' => $result['data']]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Lấy danh sách thanh toán
     */
    public function getAll()
    {
        try {
            $payments = $this->paymentService->getAllPayments();
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $payments]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống', 'error' => $e->getMessage()]);
        }
    }
}
?>
