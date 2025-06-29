<?php
namespace controllers;

use services\CartService;

class CartController
{
    private $cartService;
    
    public function __construct()
    {
        $this->cartService = new CartService();
    }
    
    /**
     * Hiển thị trang giỏ hàng
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $cartResult = $this->cartService->getCart($userId);
        
        if ($cartResult['success']) {
            $cartData = $cartResult['data'];
            $this->render('cart/index', [
                'cartItems' => $cartData['items'],
                'totalAmount' => $cartData['total_amount'],
                'totalItems' => $cartData['total_items'],
                'itemCount' => $cartData['item_count']
            ]);
        } else {
            $this->setFlashMessage('error', $cartResult['message']);
            $this->render('cart/index', [
                'cartItems' => [],
                'totalAmount' => 0,
                'totalItems' => 0,
                'itemCount' => 0
            ]);
        }
    }
    
    /**
     * Thêm sản phẩm vào giỏ hàng (AJAX)
     */
    public function add()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng'
            ]);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không được phép'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $consoleId = $_POST['console_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if (!$consoleId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Thiếu thông tin sản phẩm'
            ]);
            return;
        }
        
        $result = $this->cartService->addToCart($userId, $consoleId, $quantity);
        $this->jsonResponse($result);
    }
    
    /**
     * Cập nhật số lượng sản phẩm (AJAX)
     */
    public function updateQuantity()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không được phép'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $cartItemId = $_POST['cart_item_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if (!$cartItemId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Thiếu thông tin sản phẩm'
            ]);
            return;
        }
        
        $result = $this->cartService->updateQuantity($userId, $cartItemId, $quantity);
        $this->jsonResponse($result);
    }
    
    /**
     * Tăng số lượng sản phẩm (AJAX)
     */
    public function increase()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $cartItemId = $_POST['cart_item_id'] ?? null;
        
        if (!$cartItemId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Thiếu thông tin sản phẩm'
            ]);
            return;
        }
        
        $result = $this->cartService->increaseQuantity($userId, $cartItemId);
        $this->jsonResponse($result);
    }
    
    /**
     * Giảm số lượng sản phẩm (AJAX)
     */
    public function decrease()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $cartItemId = $_POST['cart_item_id'] ?? null;
        
        if (!$cartItemId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Thiếu thông tin sản phẩm'
            ]);
            return;
        }
        
        $result = $this->cartService->decreaseQuantity($userId, $cartItemId);
        $this->jsonResponse($result);
    }
    
    /**
     * Xóa sản phẩm khỏi giỏ hàng (AJAX)
     */
    public function remove()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không được phép'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $cartItemId = $_POST['cart_item_id'] ?? null;
        
        if (!$cartItemId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Thiếu thông tin sản phẩm'
            ]);
            return;
        }
        
        $result = $this->cartService->removeFromCart($userId, $cartItemId);
        $this->jsonResponse($result);
    }
    
    /**
 * Lấy thông tin một cart item cụ thể
 */
    public function getCartItem($cartItemId)
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Kiểm tra quyền sở hữu và lấy thông tin cart item
        // Bạn có thể thêm method này vào CartService
        $result = $this->cartService->getCartItem($userId, $cartItemId);
        $this->jsonResponse($result);
    }

    /**
     * Cập nhật cart item cụ thể (PUT method)
     */
    public function updateCartItem($cartItemId)
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        // Lấy dữ liệu từ PUT request
        $input = json_decode(file_get_contents('php://input'), true);
        $quantity = (int)($input['quantity'] ?? 0);
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->updateQuantity($userId, $cartItemId, $quantity);
        $this->jsonResponse($result);
    }

    /**
     * Xóa cart item cụ thể (DELETE method)
     */
    public function removeCartItem($cartItemId)
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->removeFromCart($userId, $cartItemId);
        $this->jsonResponse($result);
    }


    /**
     * Lấy thông tin giỏ hàng (AJAX)
     */
    public function getCartData()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->getCart($userId);
        $this->jsonResponse($result);
    }
    
    /**
     * Lấy số lượng sản phẩm trong giỏ hàng (AJAX)
     */
    public function getCartCount()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => true,
                'count' => 0
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->getCartItemCount($userId);
        $this->jsonResponse($result);
    }
    
    /**
     * Tính tổng tiền giỏ hàng (AJAX)
     */
    public function calculateTotal()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->calculateTotal($userId);
        $this->jsonResponse($result);
    }
    
    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clear()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không được phép'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->clearCart($userId);
        
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            $this->jsonResponse($result);
        } else {
            if ($result['success']) {
                $this->setFlashMessage('success', $result['message']);
            } else {
                $this->setFlashMessage('error', $result['message']);
            }
            $this->redirect('/cart');
        }
    }
    
    /**
     * Đồng bộ giỏ hàng (AJAX)
     */
    public function sync()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->syncCart($userId);
        $this->jsonResponse($result);
    }
    
    /**
     * Cập nhật nhiều sản phẩm cùng lúc (AJAX)
     */
    public function updateMultiple()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không được phép'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $updates = json_decode($_POST['updates'] ?? '[]', true);
        
        if (empty($updates)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Không có dữ liệu cập nhật'
            ]);
            return;
        }
        
        $result = $this->cartService->updateMultipleItems($userId, $updates);
        $this->jsonResponse($result);
    }
    
    /**
     * Kiểm tra giỏ hàng trước khi thanh toán
     */
    public function validateForCheckout()
    {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng đăng nhập'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $result = $this->cartService->validateCartForCheckout($userId);
        $this->jsonResponse($result);
    }
    
    /**
     * Chuyển đến trang thanh toán
     */
    public function checkout()
    {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $validationResult = $this->cartService->validateCartForCheckout($userId);
        
        if (!$validationResult['success']) {
            $this->setFlashMessage('error', $validationResult['message']);
            if (isset($validationResult['unavailable_items'])) {
                $this->setFlashMessage('warning', 'Sản phẩm không đủ số lượng: ' . implode(', ', $validationResult['unavailable_items']));
            }
            $this->redirect('/cart');
            return;
        }
        
        // Chuyển đến trang thanh toán
        $this->redirect('/checkout');
    }
    
    // Helper methods
    private function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    private function redirectToLogin()
    {
        $this->redirect('/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    
    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    private function setFlashMessage($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }
    
    private function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
    
    private function render($view, $data = [])
    {
        // Extract data to variables
        extract($data);
        
        // Include view file
        include "views/{$view}.php";
    }
}
?>
