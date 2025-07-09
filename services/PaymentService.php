<?php
namespace services;
use models\Payment;
class PaymentService
{
    private $paymentModel;

    public function __construct($database = null)
    {
        // Giả sử PaymentModel là một lớp tương tác với cơ sở dữ liệu
        $this->paymentModel = new Payment($database);
    }
   

    /**
     * Tạo thanh toán mới
     */
    public function createPayment($data)
    {
        // Validate dữ liệu
        if (empty($data['user_id']) || empty($data['total_amount']) || empty($data['payment_method'])) {
            return [
                'success' => false,
                'message' => 'Thiếu thông tin thanh toán'
            ];
        }

        // Trạng thái mặc định
        $data['payment_status'] = $data['payment_status'] ?? 'pending';

        // Tạo bản ghi
        $paymentId = $this->paymentModel->create($data);

        if ($paymentId) {
            return [
                'success' => true,
                'data' => [
                    'payment_id' => $paymentId
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Không thể tạo thanh toán'
        ];
    }

    /**
     * Lấy tất cả thanh toán
     */
    public function getAllPayments()
    {
        return $this->paymentModel->getAll();
    }
}
?>
