<?php
namespace models;
use PDO;
use DateTime;

class Rental
{
    private $db;
    private $table = 'rentals';

    public function __construct($database)
    {
        $this->db = $database;
    }

    // Tạo đơn thuê mới
    public function create($data)
        {
            $sql = "INSERT INTO {$this->table} (user_id, console_id, rental_start, rental_end, total_hours, total_amount, status, notes) 
                    VALUES (:user_id, :console_id, :rental_start, :rental_end, :total_hours, :total_amount, :status, :notes)";
            
            $stmt = $this->db->prepare($sql);

             // Tách giá trị ra biến
            $user_id = $data['user_id'];
            $console_id = $data['console_id'];
            $rental_start = $data['rental_start'];
            $rental_end = $data['rental_end'];
            $total_hours = $data['total_hours'];
            $total_amount = $data['total_amount'];
            $status = $data['status'] ?? 'pending';
            $notes = $data['notes'] ?? '';

            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':console_id', $console_id);
            $stmt->bindParam(':rental_start', $rental_start);
            $stmt->bindParam(':rental_end', $rental_end);
            $stmt->bindParam(':total_hours', $total_hours);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':notes', $notes);

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        }

    // Tìm đơn thuê theo ID
    public function findById($id)
    {
        $sql = "SELECT r.*, u.username, u.full_name, u.phone, u.email,
                    gc.console_name, gc.console_type, gc.rental_price_per_hour
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.user_id
                JOIN game_consoles gc ON r.console_id = gc.console_id
                WHERE r.rental_id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy đơn thuê của user
    public function getUserRentals($userId, $page = 1, $limit = 10, $status = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE r.user_id = :user_id";
        $params = [':user_id' => $userId];
        
        if (!empty(trim($status))) {
            $whereClause .= " AND r.status = :status";
            $params[':status'] = $status;
        }

        $sql = "SELECT r.*, gc.console_name, gc.console_type, gc.image_url
                FROM {$this->table} r
                JOIN game_consoles gc ON r.console_id = gc.console_id
                {$whereClause}
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả đơn thuê (admin)
    public function getList($offset = 0, $limit = 10, $filters = [])
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Xử lý filters
        if (!empty($filters['search'])) {
            $whereClause .= " AND (u.username LIKE :search OR u.full_name LIKE :search OR gc.console_name LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $whereClause .= " AND r.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }

        if (!empty($filters['console_id'])) {
            $whereClause .= " AND r.console_id = :console_id";
            $params[':console_id'] = $filters['console_id'];
        }

        $sql = "SELECT r.*, u.username, u.full_name, gc.console_name, gc.console_type
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.user_id
                JOIN game_consoles gc ON r.console_id = gc.console_id
                {$whereClause}
                ORDER BY r.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả đơn thuê (admin)
    public function getAll($page = 1, $limit = 10, $search = '', $status = '')
    {
        $offset = ($page - 1) * $limit;
        $filters = [
            'search' => $search,
            'status' => $status
        ];
        return $this->getList($offset, $limit, $filters);
    }

    // Đếm tổng số đơn thuê 
    public function getTotalCount($filters = [])
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $whereClause .= " AND r.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (u.username LIKE :search OR u.full_name LIKE :search OR gc.console_name LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND r.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['console_id'])) {
            $whereClause .= " AND r.console_id = :console_id";
            $params[':console_id'] = $filters['console_id'];
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} r
                JOIN users u ON r.user_id = u.user_id
                JOIN game_consoles gc ON r.console_id = gc.console_id
                {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Đếm tổng số đơn thuê 
    public function count($userId = null, $search = '', $status = '')
    {
        $filters = [
            'search' => $search,
            'status' => $status
        ];
        if ($userId) {
            $filters['user_id'] = $userId;
        }
        return $this->getTotalCount($filters);
    }

    // Cập nhật đơn thuê 
    public function update($id, $data)
    {
        $fields = []; 
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'rental_id') { 
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", 
                updated_at = NOW() WHERE rental_id = :id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value); 
        }

        return $stmt->execute();
    }

    // Cập nhật trạng thái đơn thuê
    public function updateStatus($id, $status, $notes = '')
    {
        $sql = "UPDATE {$this->table} SET status = :status, notes = :notes, updated_at = NOW() WHERE rental_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        return $stmt->execute();
    }

    // Kiểm tra xung đột thời gian thuê
    public function checkTimeConflict($consoleId, $startTime, $endTime, $excludeRentalId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE console_id = :console_id 
                AND status IN ('pending', 'confirmed', 'active')
                AND (
                    (rental_start <= :start_time AND rental_end > :start_time) OR
                    (rental_start < :end_time AND rental_end >= :end_time) OR
                    (rental_start >= :start_time AND rental_end <= :end_time)
                )";
        
        if ($excludeRentalId) {
            $sql .= " AND rental_id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':console_id', $consoleId);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        
        if ($excludeRentalId) {
            $stmt->bindParam(':exclude_id', $excludeRentalId);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // Lấy các slot thời gian có sẵn cho console
    public function getAvailableTimeSlots($consoleId, $date)
    {
        $sql = "SELECT rental_start, rental_end FROM {$this->table} 
                WHERE console_id = :console_id 
                AND DATE(rental_start) = :date
                AND status IN ('pending', 'confirmed', 'active')
                ORDER BY rental_start";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':console_id', $consoleId);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy thống kê đơn thuê theo trạng thái
    public function getStatusStats()
    {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy doanh thu theo tháng
    public function getMonthlyRevenue($year)
    {
        $sql = "SELECT MONTH(created_at) as month, SUM(total_amount) as revenue
                FROM {$this->table}
                WHERE YEAR(created_at) = :year AND status = 'completed'
                GROUP BY MONTH(created_at)
                ORDER BY month";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy đơn thuê sắp hết hạn
    public function getUpcomingRentals($hours = 24)
    {
        $toTime = (new DateTime())->modify("+$hours hours")->format('Y-m-d H:i:s');

        $sql = "SELECT r.*, u.username, u.full_name, u.phone, gc.console_name
            FROM {$this->table} r
            JOIN users u ON r.user_id = u.user_id
            JOIN game_consoles gc ON r.console_id = gc.console_id
            WHERE r.status = 'confirmed'
            AND r.rental_end BETWEEN NOW() AND :to_time
            ORDER BY r.rental_end ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':to_time', $toTime);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy đơn thuê đang hoạt động
    public function getActiveRentals()
    {
        $sql = "SELECT r.*, u.username, u.full_name, gc.console_name
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.user_id
                JOIN game_consoles gc ON r.console_id = gc.console_id
                WHERE r.status = 'active'
                AND r.rental_start <= NOW()
                AND r.rental_end >= NOW()
                ORDER BY r.rental_end ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tự động cập nhật trạng thái đơn thuê hết hạn
    public function updateExpiredRentals()
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'completed', updated_at = NOW()
                WHERE status = 'active' 
                AND rental_end < NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount(); // Trả về số dòng được cập nhật
    }

    // Xóa đơn thuê
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE rental_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Thêm một số hàm tiện ích
    
    // Kiểm tra đơn thuê có thuộc về user không
    public function belongsToUser($rentalId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE rental_id = :rental_id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':rental_id', $rentalId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // Lấy doanh thu theo ngày
    public function getDailyRevenue($date)
    {
        $sql = "SELECT SUM(total_amount) as revenue FROM {$this->table} 
                WHERE DATE(created_at) = :date AND status = 'completed'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchColumn() ?? 0;
    }

    // Lấy top console được thuê nhiều nhất
    public function getTopRentedConsoles($limit = 10)
    {
        $sql = "SELECT gc.console_name, gc.console_type, COUNT(*) as rental_count,
                       SUM(r.total_amount) as total_revenue
                FROM {$this->table} r
                JOIN game_consoles gc ON r.console_id = gc.console_id
                WHERE r.status = 'completed'
                GROUP BY r.console_id
                ORDER BY rental_count DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>