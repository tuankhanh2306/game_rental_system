<?php
    // Bật error reporting để debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Autoloader function
    spl_autoload_register(function ($className) {
        // Chuyển đổi namespace thành đường dẫn file
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $file = __DIR__ . DIRECTORY_SEPARATOR . $className . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    });

    // Log request để debug
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request Body: " . file_get_contents('php://input'));

    use core\Router;
    use controllers\AuthController;
    use controllers\GameController;
    use controllers\UserController;
    use middleware\TokenAuthMiddleware;

    $router = new Router();

    // ===== PUBLIC API ROUTES =====
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/login', [AuthController::class, 'login']);

    // ===== USER AUTHENTICATION ROUTES =====
    $router->post('/users/refresh-token', [UserController::class, 'refreshToken']);

    // ===== PROTECTED USER ROUTES =====
    // Routes cho user đã đăng nhập
    $router->get('/users/profile', [UserController::class, 'getProfile']);
    $router->put('/users/profile', [UserController::class, 'updateProfile']);
    $router->post('/users/change-password', [UserController::class, 'changePassword']);
    $router->post('/users/logout', [UserController::class, 'logout']);
    $router->post('/users/updateProfile', [UserController::class, 'updateProfile']);

    // ===== GAME CONSOLE ROUTES ===== 
    // FIX: Routes cho game console
    $router->get('/game_consoles/index', [GameController::class, 'index']);           // Lấy danh sách
    $router->post('/game_consoles/create', [GameController::class, 'create']);         // Tạo mới  
    $router->get('/game_consoles/{id}', [GameController::class, 'show']);       // Lấy chi tiết
    $router->put('/game_consoles/{id}', [GameController::class, 'update']);     // Cập nhật
    $router->delete('/game_consoles/{id}', [GameController::class, 'delete']);  // Xóa

    // ===== ADMIN ONLY ROUTES =====
    // Routes chỉ dành cho admin
    $router->get('/users', [UserController::class, 'getUsers']);
    $router->get('/users/stats', [UserController::class, 'getUserStats']);
    $router->put('/users/{id}', [UserController::class, 'updateUser']);
    $router->delete('/users/{id}', [UserController::class, 'deleteUser']);

    // Thực thi dispatch
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>