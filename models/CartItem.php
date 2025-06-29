<?php
namespace models;

use core\Database;
use PDO;

class CartItem
{
    private $db;
    private $table = 'cart_items';
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Thêm sản phẩm vào giỏ hàng
    public function addItem($userId, $consoleId, $imageUrl, $quantity = 1)
    {
        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $existingItem = $this->findByUserAndConsole($userId, $consoleId);
        
        if ($existingItem) {
            // Nếu đã có, cập nhật số lượng
            return $this->updateQuantity($existingItem['cart_item_id'], $existingItem['quantity'] + $quantity);
        } else {
            // Nếu chưa có, thêm mới
            $sql = "INSERT INTO {$this->table} (user_id, console_id, image_url, quantity) 
                    VALUES (:user_id, :console_id, :image_url, :quantity)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':console_id', $consoleId);
            $stmt->bindParam(':image_url', $imageUrl);
            $stmt->bindParam(':quantity', $quantity);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        }
    }
    
    // Tìm item theo user và console
    public function findByUserAndConsole($userId, $consoleId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND console_id = :console_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':console_id', $consoleId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Lấy tất cả items trong giỏ hàng của user
    public function getCartItems($userId)
    {
        $sql = "SELECT ci.*, gc.console_name, gc.rental_price_per_hour, gc.available_quantity,
                       (ci.quantity * gc.rental_price_per_hour) as total_price
                FROM {$this->table} ci
                JOIN game_consoles gc ON ci.console_id = gc.console_id
                WHERE ci.user_id = :user_id
                ORDER BY ci.added_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Cập nhật số lượng
    public function updateQuantity($cartItemId, $quantity)
    {
        if ($quantity <= 0) {
            return $this->removeItem($cartItemId);
        }
        
        $sql = "UPDATE {$this->table} SET quantity = :quantity WHERE cart_item_id = :cart_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':cart_item_id', $cartItemId);
        
        return $stmt->execute();
    }
    
    // Xóa một item khỏi giỏ hàng
    public function removeItem($cartItemId)
    {
        $sql = "DELETE FROM {$this->table} WHERE cart_item_id = :cart_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':cart_item_id', $cartItemId);
        
        return $stmt->execute();
    }
    
    // Xóa toàn bộ giỏ hàng của user
    public function clearCart($userId)
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    // Đếm số lượng items trong giỏ hàng
    public function getCartItemCount($userId)
    {
        $sql = "SELECT SUM(quantity) as total_items FROM {$this->table} WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_items'] ?? 0;
    }
    
    // Tính tổng tiền giỏ hàng
    public function getCartTotal($userId)
    {
        $sql = "SELECT SUM(ci.quantity * gc.rental_price_per_hour) as total_amount
                FROM {$this->table} ci
                JOIN game_consoles gc ON ci.console_id = gc.console_id
                WHERE ci.user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_amount'] ?? 0;
    }
    
    // Kiểm tra tính khả dụng của các items trong giỏ hàng
    public function validateCartAvailability($userId)
    {
        $sql = "SELECT ci.*, gc.console_name, gc.available_quantity
                FROM {$this->table} ci
                JOIN game_consoles gc ON ci.console_id = gc.console_id
                WHERE ci.user_id = :user_id 
                AND (gc.available_quantity < ci.quantity OR gc.status != 'available')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Tìm cart item theo ID
    public function findById($cartItemId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE cart_item_id = :cart_item_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':cart_item_id', $cartItemId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Kiểm tra quyền sở hữu cart item
    public function isOwner($cartItemId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE cart_item_id = :cart_item_id AND user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':cart_item_id', $cartItemId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}
?>
