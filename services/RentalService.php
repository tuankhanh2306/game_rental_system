<?php
namespace services;

use models\Rental;
use Exception;

class RentalService
{
    private $db;
    private $rentalModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->rentalModel = new Rental($db);
    }

    public function createRental($data)
    {
        try {
            $rentalId = $this->rentalModel->create($data);
            return $rentalId 
                ? ['success' => true, 'data' => $rentalId]
                : ['success' => false, 'message' => 'Không thể tạo đơn thuê'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    public function getAllRentals($filters = [])
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $status = $filters['status'] ?? '';
            $userId = $filters['user_id'] ?? null;

            if ($userId) {
                $data = $this->rentalModel->getUserRentals($userId, $page, $limit, $status);
                $total = $this->rentalModel->count($userId, $search, $status);
            } else {
                $data = $this->rentalModel->getAll($page, $limit, $search, $status);
                $total = $this->rentalModel->count(null, $search, $status);
            }

            return ['success' => true, 'data' => ['rentals' => $data, 'total' => $total]];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi lấy danh sách: ' . $e->getMessage()];
        }
    }

        public function getRentalById($id)
        {
            try {
                $rental = $this->rentalModel->findById($id);
                return $rental 
                    ? ['success' => true, 'data' => $rental]
                    : ['success' => false, 'message' => 'Không tìm thấy đơn thuê'];
            } catch (Exception $e) {
                return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
            }
        }

        public function updateStatus($id, $status, $notes = '')
    {
        try {
            $success = $this->rentalModel->updateStatus($id, $status, $notes);

            if ($success) {
                // Ghi lịch sử
                $historyModel = new \models\RentalHistory($this->db); // hoặc `use models\RentalHistory` ở đầu file
                $historyModel->create([
                    'rental_id' => $id,
                    'action' => 'status_updated',
                    'action_by' => $_SESSION['user_id'] ?? 0,
                    'notes' => $notes,
                    'action_date' => date('Y-m-d H:i:s'),
                ]);
            }

            return [
                'success' => $success,
                'message' => $success ? 'Cập nhật thành công' : 'Cập nhật thất bại'
            ];
     } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi cập nhật: ' . $e->getMessage()
            ];
        }
    }


    public function getStats()
    {
        try {
            $statusStats = $this->rentalModel->getStatusStats();
            $year = date('Y');
            $monthlyRevenue = $this->rentalModel->getMonthlyRevenue($year);

            return ['success' => true, 'data' => [
                'status_stats' => $statusStats,
                'monthly_revenue' => $monthlyRevenue
            ]];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi thống kê: ' . $e->getMessage()];
        }
    }

    public function getUpcomingRentals($hours = 24)
    {
        try {
            $data = $this->rentalModel->getUpcomingRentals($hours);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi lấy danh sách đơn sắp hết hạn: ' . $e->getMessage()];
        }
    }
}
