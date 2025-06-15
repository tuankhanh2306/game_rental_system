<?php
// Bật error reporting để debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Log request để debug
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request Body: " . file_get_contents('php://input'));

    use core\Router;
    use controllers\AuthController;
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
    $router->get('/api/users/profile', [UserController::class, 'getProfile']);
    $router->put('/api/users/profile', [UserController::class, 'updateProfile']);
    $router->post('/api/users/change-password', [UserController::class, 'changePassword']);
    $router->post('/api/users/logout', [UserController::class, 'logout']);
    $router->post('/api/users/updateProfile', [UserController::class, 'updateProfile']);
    
    // ===== ADMIN ONLY ROUTES =====
    // Routes chỉ dành cho admin
    $router->get('/api/users', [UserController::class, 'getUsers']);
    $router->get('/api/users/stats', [UserController::class, 'getUserStats']);
    $router->put('/api/users/{id}', [UserController::class, 'updateUser']);
    $router->delete('/api/users/{id}', [UserController::class, 'deleteUser']);

    // Thực thi dispatch
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>

