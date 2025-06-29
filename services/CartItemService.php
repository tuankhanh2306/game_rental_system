<?php
namespace services;

use models\CartItem;
use models\Game;

class CartService
{
    private $cartModel;
    private $gameModel;
    
    public function __construct()
    {
        $this->cartModel = new CartItem();
        $this->gameModel = new Game();
    }
    
    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function addToCart($userId, $consoleId, $quantity = 1)
    {
        try {
            // Kiểm tra sản phẩm có tồn tại không
            $console = $this->gameModel->findById($consoleId);
            if (!$console) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ];
            }
            
            // Kiểm tra sản phẩm có khả dụng không
            if ($console['status'] !== 'available') {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm hiện không khả dụng'
                ];
            }
            
            // Kiểm tra số lượng có đủ không
            if ($console['available_quantity'] < $quantity) {
                return [
                    'success' => false,
                    'message' => 'Số lượng không đủ. Chỉ còn ' . $console['available_quantity'] . ' sản phẩm'
                ];
            }
            
            // Thêm vào giỏ hàng
            $result = $this->cartModel->addItem($userId, $consoleId, $console['image_url'], $quantity);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Đã thêm sản phẩm vào giỏ hàng',
                    'cart_item_id' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể thêm sản phẩm vào giỏ hàng'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function updateQuantity($userId, $cartItemId, $quantity)
    {
        try {
            // Kiểm tra quyền sở hữu
            if (!$this->cartModel->isOwner($cartItemId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'Không có quyền thực hiện thao tác này'
                ];
            }
            
            // Lấy thông tin cart item
            $cartItem = $this->cartModel->findById($cartItemId);
            if (!$cartItem) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                ];
            }
            
            // Kiểm tra số lượng có sẵn
            $console = $this->gameModel->findById($cartItem['console_id']);
            if ($quantity > $console['available_quantity']) {
                return [
                    'success' => false,
                    'message' => 'Số lượng không đủ. Chỉ còn ' . $console['available_quantity'] . ' sản phẩm'
                ];
            }
            
            // Cập nhật số lượng
            $result = $this->cartModel->updateQuantity($cartItemId, $quantity);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => $quantity > 0 ? 'Đã cập nhật số lượng' : 'Đã xóa sản phẩm khỏi giỏ hàng'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể cập nhật số lượng'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Lấy thông tin một cart item cụ thể
     */
    public function getCartItem($userId, $cartItemId)
    {
        try {
            // Kiểm tra quyền sở hữu
            if (!$this->cartModel->isOwner($cartItemId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'Không có quyền truy cập'
                ];
            }
            
            $cartItem = $this->cartModel->findById($cartItemId);
            if (!$cartItem) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
                ];
            }
            
            return [
                'success' => true,
                'data' => $cartItem
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tăng số lượng sản phẩm
     */
    public function increaseQuantity($userId, $cartItemId)
    {
        $cartItem = $this->cartModel->findById($cartItemId);
        if (!$cartItem) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
            ];
        }
        
        return $this->updateQuantity($userId, $cartItemId, $cartItem['quantity'] + 1);
    }
    
    /**
     * Giảm số lượng sản phẩm
     */
    public function decreaseQuantity($userId, $cartItemId)
    {
        $cartItem = $this->cartModel->findById($cartItemId);
        if (!$cartItem) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm trong giỏ hàng'
            ];
        }
        
        return $this->updateQuantity($userId, $cartItemId, $cartItem['quantity'] - 1);
    }
    
    /**
     * Xóa sản phẩm khỏi giỏ hàng
     */
    public function removeFromCart($userId, $cartItemId)
    {
        try {
            // Kiểm tra quyền sở hữu
            if (!$this->cartModel->isOwner($cartItemId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'Không có quyền thực hiện thao tác này'
                ];
            }
            
            $result = $this->cartModel->removeItem($cartItemId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa sản phẩm'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lấy thông tin giỏ hàng
     */
    public function getCart($userId)
    {
        try {
            $cartItems = $this->cartModel->getCartItems($userId);
            $totalAmount = $this->cartModel->getCartTotal($userId);
            $totalItems = $this->cartModel->getCartItemCount($userId);
            
             return [
                'success' => true,
                'data' => [
                    'items' => $cartItems,
                    'total_amount' => $totalAmount,
                    'total_items' => $totalItems,
                    'item_count' => count($cartItems)
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Tính tổng tiền giỏ hàng
     */
    public function calculateTotal($userId)
    {
        try {
            $totalAmount = $this->cartModel->getCartTotal($userId);
            $totalItems = $this->cartModel->getCartItemCount($userId);
            
            return [
                'success' => true,
                'data' => [
                    'total_amount' => $totalAmount,
                    'total_items' => $totalItems,
                    'formatted_total' => number_format($totalAmount, 0, ',', '.') . ' VNĐ'
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clearCart($userId)
    {
        try {
            $result = $this->cartModel->clearCart($userId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Đã xóa toàn bộ giỏ hàng'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa giỏ hàng'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Xóa giỏ hàng sau khi thanh toán thành công
     */
    public function clearCartAfterPayment($userId)
    {
        try {
            // Có thể thêm logic ghi log trước khi xóa
            $cartItems = $this->cartModel->getCartItems($userId);
            
            // Xóa giỏ hàng
            $result = $this->cartModel->clearCart($userId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Thanh toán thành công, giỏ hàng đã được xóa',
                    'cleared_items' => count($cartItems)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể xóa giỏ hàng sau thanh toán'
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Kiểm tra tính khả dụng của giỏ hàng trước khi thanh toán
     */
    public function validateCartForCheckout($userId)
    {
        try {
            // Kiểm tra giỏ hàng có rỗng không
            $cartItems = $this->cartModel->getCartItems($userId);
            if (empty($cartItems)) {
                return [
                    'success' => false,
                    'message' => 'Giỏ hàng trống'
                ];
            }
            
            // Kiểm tra tính khả dụng của từng sản phẩm
            $unavailableItems = $this->cartModel->validateCartAvailability($userId);
            
            if (!empty($unavailableItems)) {
                $errorMessages = [];
                foreach ($unavailableItems as $item) {
                    $errorMessages[] = $item['console_name'] . ' (Còn lại: ' . $item['available_quantity'] . ', Yêu cầu: ' . $item['quantity'] . ')';
                }
                
                return [
                    'success' => false,
                    'message' => 'Một số sản phẩm không đủ số lượng',
                    'unavailable_items' => $errorMessages
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Giỏ hàng hợp lệ',
                'data' => [
                    'items' => $cartItems,
                    'total_amount' => $this->cartModel->getCartTotal($userId)
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Đồng bộ giỏ hàng (xóa các sản phẩm không còn khả dụng)
     */
    public function syncCart($userId)
    {
        try {
            $unavailableItems = $this->cartModel->validateCartAvailability($userId);
            $removedCount = 0;
            
            foreach ($unavailableItems as $item) {
                $this->cartModel->removeItem($item['cart_item_id']);
                $removedCount++;
            }
            
            return [
                'success' => true,
                'message' => $removedCount > 0 ? "Đã xóa {$removedCount} sản phẩm không khả dụng" : 'Giỏ hàng đã được đồng bộ',
                'removed_count' => $removedCount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Lấy số lượng sản phẩm trong giỏ hàng (cho badge)
     */
    public function getCartItemCount($userId)
    {
        try {
            $count = $this->cartModel->getCartItemCount($userId);
            
            return [
                'success' => true,
                'count' => $count
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
                'count' => 0
            ];
        }
    }
    
    /**
     * Cập nhật nhiều sản phẩm cùng lúc
     */
    public function updateMultipleItems($userId, $updates)
    {
        try {
            $results = [];
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($updates as $update) {
                $cartItemId = $update['cart_item_id'];
                $quantity = $update['quantity'];
                
                $result = $this->updateQuantity($userId, $cartItemId, $quantity);
                $results[] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
            
            return [
                'success' => $errorCount === 0,
                'message' => "Cập nhật thành công {$successCount} sản phẩm" . ($errorCount > 0 ? ", {$errorCount} sản phẩm lỗi" : ""),
                'details' => $results,
                'success_count' => $successCount,
                'error_count' => $errorCount
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
}
?>