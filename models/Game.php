<?php
namespace models;

use core\Database;
use PDO;

class Game{
    private $db;
    private $table = 'game_consoles';
    
    public function __construct(){
        $this->db = Database::getInstance()->getConnection();
    }

    //Tạo máy chơi game mới
    public function create($data){
        $sql = "INSERT INTO {$this->table} (console_name, console_type, description, image_url, rental_price_per_hour, quantity, available_quantity, status)
             VALUES (:console_name, :console_type, :description, :image_url, :rental_price_per_hour, :quantity, :available_quantity, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':console_name', $data['console_name']);
        $stmt->bindParam(':console_type', $data['console_type']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':rental_price_per_hour', $data['rental_price_per_hour']);
        $stmt->bindParam(':quantity', $data['quantity'] ?? 1);
        $stmt->bindParam(':available_quantity', $data['available_quantity'] ?? $data['quantity'] ?? 1);
        $stmt->bindParam(':status', $data['status'] ?? 'available');
        
        if($stmt->execute()){
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }

    //tìm máy theo id
    public function findById($id){
        $sql = "SELECT * FROM {$this->table} WHERE console_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //lấy tất cả máy chơi game có sẵn
    public function getAvailable($page = 1, $limit = 12, $type = '', $search = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE available_quantity > 0 AND status = 'available'";
        $params = [];
        
        if (!empty(trim($type))) {
            $whereClause .= " AND console_type = :type";
            $params[':type'] = $type;
        }
        
        if (!empty(trim($search))) {
            $whereClause .= " AND (console_name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT * FROM {$this->table} {$whereClause}
                 ORDER BY console_name ASC
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

    //lấy tất cả máy (admin)
    public function getAll($page = 1, $limit = 12, $type = '', $search = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        
        if (!empty(trim($type))) {
            $whereClause .= " WHERE console_type = :type";
            $params[':type'] = $type;
        }
        
        if (!empty(trim($search))) {
            if ($whereClause == '') {
                $whereClause .= " WHERE ";
            } else {
                $whereClause .= " AND ";
            }
            $whereClause .= "(console_name LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT * FROM {$this->table} {$whereClause}
                 ORDER BY console_name ASC
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
    
    // Đếm tổng số máy
    public function count($search = '', $availableOnly = false)
    {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($availableOnly) {
            $whereClause .= " AND available_quantity > 0 AND status = 'available'";
        }
        
        if (!empty(trim($search))) {
            $whereClause .= " AND (console_name LIKE :search OR console_type LIKE :search OR description LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $sql = "SELECT COUNT(*) FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindParam($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    // Cập nhật thông tin máy
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];
        $allowedFields = ['console_name', 'console_type', 'description', 'image_url', 'rental_price_per_hour', 'quantity', 'available_quantity', 'status'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE console_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // Xóa máy
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE console_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Lấy các loại máy duy nhất
    public function getUniqueTypes()
    {
        $sql = "SELECT DISTINCT console_type FROM {$this->table} WHERE available_quantity > 0 AND status = 'available' ORDER BY console_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Kiểm tra máy có đang được thuê không
    public function isCurrentlyRented($consoleId)
    {
        $sql = "SELECT COUNT(*) FROM rentals
                 WHERE console_id = :console_id
                 AND status IN ('active', 'confirmed')
                 AND rental_start <= NOW()
                 AND rental_end >= NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':console_id', $consoleId);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    // Thống kê máy theo trạng thái
    public function getStatusStats()
    {
        $sql = "SELECT status, COUNT(*) as count, SUM(quantity) as total_quantity, SUM(available_quantity) as total_available 
                FROM {$this->table} GROUP BY status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy console phổ biến nhất
    public function getPopularConsoles($limit = 5)
    {
        $sql = "SELECT gc.*, COUNT(r.rental_id) as rental_count
                 FROM {$this->table} gc
                 LEFT JOIN rentals r ON gc.console_id = r.console_id
                 WHERE gc.available_quantity > 0 AND gc.status = 'available'
                GROUP BY gc.console_id
                 ORDER BY rental_count DESC, gc.console_name ASC
                 LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tìm kiếm console theo giá
    public function findByPriceRange($minPrice = 0, $maxPrice = 1000000)
    {
        $sql = "SELECT * FROM {$this->table}
                 WHERE available_quantity > 0 AND status = 'available'
                 AND rental_price_per_hour BETWEEN :min_price AND :max_price
                 ORDER BY rental_price_per_hour ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':min_price', $minPrice);
        $stmt->bindParam(':max_price', $maxPrice);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái console
    public function updateStatus($id, $status)
    {
        $validStatuses = ['available', 'rented', 'maintenance'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET status = :status WHERE console_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Kiểm tra số lượng có sẵn
    public function checkAvailability($consoleId, $requestedQuantity = 1)
    {
        $sql = "SELECT available_quantity FROM {$this->table} WHERE console_id = :id AND status = 'available'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $consoleId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['available_quantity'] >= $requestedQuantity;
    }

    // Cập nhật số lượng có sẵn (khi thuê/trả máy)
    public function updateAvailableQuantity($consoleId, $quantity, $operation = 'decrease')
    {
        $operator = ($operation === 'increase') ? '+' : '-';
        
        $sql = "UPDATE {$this->table} 
                SET available_quantity = available_quantity {$operator} :quantity 
                WHERE console_id = :id 
                AND available_quantity >= :check_quantity";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':id', $consoleId);
        $stmt->bindParam(':check_quantity', $operation === 'decrease' ? $quantity : 0);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    // Lấy thông tin số lượng
    public function getQuantityInfo($consoleId)
    {
        $sql = "SELECT quantity, available_quantity, (quantity - available_quantity) as rented_quantity 
                FROM {$this->table} WHERE console_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $consoleId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy danh sách máy sắp hết hàng
    public function getLowStockConsoles($threshold = 2)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE available_quantity <= :threshold 
                AND available_quantity > 0 
                AND status = 'available'
                ORDER BY available_quantity ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':threshold', $threshold);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
