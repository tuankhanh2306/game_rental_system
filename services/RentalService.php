<?php
namespace services;
use models\Rental;
use models\User;
use models\Game;
use models\RentalHistory;
use Exception;

class RentalService
{
    private $rentalModel;
    private $gameModel;
    private $userModel;
    private $db;

    public function __construct($database = null)
    {
        $this->db = $database;
        $this->rentalModel = new Rental($database);
        $this->gameModel = new Game($database);
        $this->userModel = new User($database);
    }

    //tạo đơn thuê mới
    public function createRental($data)
    {
        try {
            $validation = $this->validateRentalData($data);
            
            // Sửa lỗi logic validation
            if (!$validation["valid"]) {
                return [
                    "success" => false,
                    "message" => $validation["message"],
                    "errors" => $validation["errors"],
                ];
            }

            //kiểm tra sự tồn tại của user
            $user = $this->userModel->findById($data["user_id"]);
            if (!$user) {
                return [
                    "success" => false,
                    "message" => 'Người dùng không tồn tại'
                ];
            }

            //kiểm tra máy chơi game có tồn tại hay không
            $game = $this->gameModel->findById($data['console_id']);
            if (!$game) {
                return [
                    'success' => false,
                    'message' => 'Máy chơi game không tồn tại'
                ];
            }

            if ($game['status'] !== 'available') {
                return [
                    'success' => false,
                    'message' => 'Máy chơi game không có sẵn'
                ];
            }

            // kiểm tra xung đột thời gian 
            if ($this->rentalModel->checkTimeConflict($data['console_id'], $data['rental_start'], $data['rental_end'])) {
                return [
                    'success' => false,
                    'message' => 'Thời gian thuê bị trung với đơn thuê khác'
                ];
            }

            //tính toán tổng tiền 
            $totalHours = $this->calculateTotalHours($data['rental_start'], $data['rental_end']);
            $totalAmount = $totalHours * $game['rental_price_per_hour'];

            $rentalData = [
                'user_id' => $data['user_id'],
                'console_id' => $data['console_id'],
                'rental_start' => $data['rental_start'],
                'rental_end' => $data['rental_end'],
                'total_hours' => $totalHours,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'notes' => $data['notes'] ?? ''
            ];

            $rentalId = $this->rentalModel->create($rentalData);

            if ($rentalId) {
                return [
                    'success' => true,
                    'message' => 'Tạo đơn thuê thành công',
                    'rental_id' => $rentalId,
                    'total_amount' => $totalAmount
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể tạo đơn thuê'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi tạo đơn thuê: ' . $e->getMessage()
            ];
        }
    }

    //validate dữ liệu đơn thuê
    private function validateRentalData($data)
    {
        $errors = [];

        // Kiểm tra các trường bắt buộc
        $requiredFields = ['user_id', 'console_id', 'rental_start', 'rental_end'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Trường {$field} là bắt buộc";
            }
        }

        // Kiểm tra thời gian
        if (!empty($data['rental_start']) && !empty($data['rental_end'])) {
            $startTime = strtotime($data['rental_start']);
            $endTime = strtotime($data['rental_end']);

            if ($startTime >= $endTime) {
                $errors[] = "Thời gian kết thúc phải sau thời gian bắt đầu";
            }

            if ($startTime < time()) {
                $errors[] = "Thời gian bắt đầu phải trong tương lai";
            }

            // Tối thiểu 1 giờ thuê
            if (($endTime - $startTime) < 3600) {
                $errors[] = "Thời gian thuê tối thiểu là 1 giờ";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? '' : implode(', ', $errors)
        ];
    }

    /**
     * Tính tổng số giờ thuê
     */
    private function calculateTotalHours($startTime, $endTime)
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        return ceil(($end - $start) / 3600);
    }

    /**
     * Validate việc chuyển trạng thái
     */
    private function validateStatusChange($currentStatus, $newStatus)
    {
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['active', 'cancelled'],
            'active' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => []
        ];

        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return [
                'valid' => false,
                'message' => "Không thể chuyển từ trạng thái {$currentStatus} sang {$newStatus}"
            ];
        }

        return ['valid' => true];
    }

    /**
     * Cập nhật trạng thái console dựa trên trạng thái đơn thuê
     */
    private function updateConsoleStatusBasedOnRental($consoleId, $rentalStatus)
    {
        switch ($rentalStatus) {
            case 'active':
                $this->gameModel->updateStatus($consoleId, 'rented');
                break;
            case 'completed':
            case 'cancelled':
                $this->gameModel->updateStatus($consoleId, 'available');
                break;
        }
    }

    /**
     * Tính toán các slot thời gian có sẵn
     */
    private function calculateAvailableSlots($bookedSlots, $date)
    {
        // Giờ hoạt động: 8:00 - 22:00
        $openHour = 8;
        $closeHour = 22;
        $availableSlots = [];

        $currentHour = $openHour;
        
        // Sắp xếp các slot đã book theo thời gian
        usort($bookedSlots, function($a, $b) {
            return strtotime($a['rental_start']) - strtotime($b['rental_start']);
        });

        foreach ($bookedSlots as $slot) {
            $bookedStart = (int)date('H', strtotime($slot['rental_start']));
            $bookedEnd = (int)date('H', strtotime($slot['rental_end']));

            // Thêm các slot trống trước slot đã book
            while ($currentHour < $bookedStart) {
                $availableSlots[] = [
                    'start' => sprintf('%02d:00', $currentHour),
                    'end' => sprintf('%02d:00', $currentHour + 1)
                ];
                $currentHour++;
            }

            // Nhảy qua slot đã book
            $currentHour = $bookedEnd;
        }

        // Thêm các slot còn lại sau slot cuối cùng
        while ($currentHour < $closeHour) {
            $availableSlots[] = [
                'start' => sprintf('%02d:00', $currentHour),
                'end' => sprintf('%02d:00', $currentHour + 1)
            ];
            $currentHour++;
        }

        return $availableSlots;
    }

    /**
     * Lấy danh sách đơn thuê với phân trang
     */
    public function getRentalsList($page = 1, $limit = 10, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            $data = $this->rentalModel->getList($offset, $limit, $filters);
            $total = $this->rentalModel->getTotalCount($filters);
            
            return [
                'success' => true, 
                'data' => [
                    'rentals' => $data, 
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi lấy danh sách: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật tự động các đơn thuê hết hạn
     */
    public function updateExpiredRentals()
    {
        try {
            $result = $this->rentalModel->updateExpiredRentals();
            return [
                'success' => true,
                'message' => 'Cập nhật đơn thuê hết hạn thành công',
                'updated_count' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật đơn thuê hết hạn: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gia hạn đơn thuê
     */
    public function extendRental($id, $newEndTime)
    {
        try {
            // Validate newEndTime
            if (empty($newEndTime) || strtotime($newEndTime) === false) {
                return [
                    'success' => false,
                    'message' => 'Thời gian gia hạn không hợp lệ'
                ];
            }

            $rental = $this->rentalModel->findById($id);
            
            if (!$rental) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đơn thuê'
                ];
            }

            if ($rental['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'Chỉ có thể gia hạn đơn thuê đang hoạt động'
                ];
            }

            $newEndTimeStamp = strtotime($newEndTime);
            $currentEndTimeStamp = strtotime($rental['rental_end']);

            if ($newEndTimeStamp <= $currentEndTimeStamp) {
                return [
                    'success' => false,
                    'message' => 'Thời gian gia hạn phải sau thời gian kết thúc hiện tại'
                ];
            }

            // Kiểm tra xung đột thời gian với slot mới
            if ($this->rentalModel->checkTimeConflict($rental['console_id'], $rental['rental_end'], $newEndTime, $id)) {
                return [
                    'success' => false,
                    'message' => 'Thời gian gia hạn bị trung với đơn thuê khác'
                ];
            }

            // Tính toán thêm tiền
            $console = $this->gameModel->findById($rental['console_id']);
            $additionalHours = ceil(($newEndTimeStamp - $currentEndTimeStamp) / 3600);
            $additionalAmount = $additionalHours * $console['rental_price_per_hour'];
            $newTotalHours = $rental['total_hours'] + $additionalHours;
            $newTotalAmount = $rental['total_amount'] + $additionalAmount;

            $updateData = [
                'rental_end' => $newEndTime,
                'total_hours' => $newTotalHours,
                'total_amount' => $newTotalAmount
            ];

            $result = $this->rentalModel->update($id, $updateData);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Gia hạn đơn thuê thành công',
                    'additional_amount' => $additionalAmount,
                    'new_total_amount' => $newTotalAmount
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể gia hạn đơn thuê'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi gia hạn đơn thuê: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin đơn thuê theo ID
     */
    public function getRentalById($id)
    {
        try {
            if (empty($id)) {
                return [
                    'success' => false,
                    'message' => 'ID đơn thuê không hợp lệ'
                ];
            }

            $rental = $this->rentalModel->findById($id);
            return $rental 
                ? ['success' => true, 'data' => $rental, 'message' => 'Lấy đơn thuê thành công']
                : ['success' => false, 'message' => 'Không tìm thấy đơn thuê'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Cập nhật trạng thái đơn thuê
     */
    public function updateStatus($id, $status, $notes = '')
    {
        try {
            if (empty($id) || empty($status)) {
                return [
                    'success' => false,
                    'message' => 'ID và trạng thái là bắt buộc'
                ];
            }

            // Lấy thông tin đơn thuê hiện tại
            $rental = $this->rentalModel->findById($id);
            if (!$rental) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đơn thuê'
                ];
            }

            // Validate việc chuyển trạng thái
            $validation = $this->validateStatusChange($rental['status'], $status);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            $success = $this->rentalModel->updateStatus($id, $status, $notes);

            if ($success) {
                // Cập nhật trạng thái console
                $this->updateConsoleStatusBasedOnRental($rental['console_id'], $status);

                // Ghi lịch sử nếu có model RentalHistory
                if (class_exists('models\RentalHistory')) {
                    $historyModel = new RentalHistory($this->db);
                    $historyModel->create([
                        'rental_id' => $id,
                        'action' => 'status_updated',
                        'action_by' => $_SESSION['user_id'] ?? null,
                        'notes' => $notes,
                        'action_date' => date('Y-m-d H:i:s'),
                    ]);
                }
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

    /**
     * Lấy thống kê tổng quan
     */
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

    /**
     * Lấy thống kê theo trạng thái
     */
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

    /**
     * Lấy doanh thu theo tháng
     */
    public function getMonthlyRevenue($year = null)
    {
        try {
            $year = $year ?? date('Y');
            return [
                'success' => true,
                'message' => 'Thống kê doanh thu theo tháng thành công',
                'data' => $this->rentalModel->getMonthlyRevenue($year)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi thống kê doanh thu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách đơn thuê sắp hết hạn
     */
    public function getUpcomingRentals($hours = 24)
    {
        try {
            if ($hours <= 0) {
                $hours = 24;
            }
            
            $data = $this->rentalModel->getUpcomingRentals($hours);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi khi lấy danh sách đơn sắp hết hạn: ' . $e->getMessage()];
        }
    }

    /**
     * Hủy đơn thuê
     */
    public function cancelRental($id, $reason = '')
    {
        try {
            return $this->updateStatus($id, 'cancelled', $reason);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi hủy đơn thuê: ' . $e->getMessage()
            ];
        }
    }
}
?>