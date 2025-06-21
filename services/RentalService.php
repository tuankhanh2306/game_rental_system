<?php
namespace services;

use models\Rental;
use models\RentalHistory;
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
        // Tính số giờ thuê
        $start = new \DateTime($data['rental_start']);
        $end = new \DateTime($data['rental_end']);
        $diff = $start->diff($end);

        // Tổng giờ thuê
        $total_hours = max(1, ($diff->days * 24) + $diff->h + round($diff->i / 60));

        // Lấy giá thuê mỗi giờ
        $stmt = $this->db->prepare("SELECT rental_price_per_hour FROM game_consoles WHERE console_id = :console_id");
        $stmt->execute([':console_id' => $data['console_id']]);
        $price_per_hour = $stmt->fetchColumn();

        if (!$price_per_hour) {
            return ['success' => false, 'message' => 'Không tìm thấy giá thuê cho console'];
        }

        // Tính tổng tiền
        $total_amount = $total_hours * $price_per_hour;

        //  Gán lại vào $data
        $data['total_hours'] = $total_hours;
        $data['total_amount'] = $total_amount;
        $data['status'] = $data['status'] ?? 'pending';
        $data['notes'] = $data['notes'] ?? '';

        $rentalId = $this->rentalModel->create($data);

        return $rentalId
            ? ['success' => true, 'data' => [
                'rental_id' => $rentalId,
                'total_hours' => $total_hours,
                'total_amount' => $total_amount
            ]]
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
                    ? ['success' => true, 'data' => $rental, 'message' => 'Lấy đơn thuê thành công']
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
                $historyModel = new RentalHistory($this->db);
                $historyModel->create([
                    'rental_id' => $id,
                    'action' => 'status_updated',
                    'action_by' => $_SESSION['user_id'] ?? null,
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

    public function getStatusStats()
{
    try {
        return [
            'success' => true,
            'data' => $this->rentalModel->getStatusStats()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Lỗi thống kê: ' . $e->getMessage()
        ];
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
