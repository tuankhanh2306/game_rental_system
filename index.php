<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $className . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Require files
require_once __DIR__ . '/controllers/GameController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/services/AuthenticationService.php';
require_once __DIR__ . '/services/UserService.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Game.php';
require_once __DIR__ . '/core/JWTAuth.php';
require_once __DIR__ . '/core/Database.php';

use controllers\AuthController;
use controllers\UserController;
use controllers\GameController;

try {
    // Database connection
    $database = require __DIR__ . '/config/database.php';
    
    // Routing
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Remove base path if exists
    $path = str_replace('/game_rental_system', '', $uri);
    
    error_log("Request URI: " . $uri);
    error_log("Request Method: " . $method);
    error_log("Processed Path: " . $path);

    // Helper function to send error response
    function sendErrorResponse($code, $message) {
        http_response_code($code);
        echo json_encode(["success" => false, "message" => $message]);
        exit;
    }

    // Route handling
    switch (true) {
        // Authentication routes
        case ($path === '/register' && $method === 'POST'):
            $controller = new AuthController($database);
            $controller->register();
            break;
            
        case ($path === '/login' && $method === 'POST'):
            $controller = new AuthController($database);
            $controller->login();
            break;

        // User profile routes
        case ($path === '/users/profile' && $method === 'GET'):
            $controller = new UserController($database);
            $controller->getProfile();
            break;
            
        case ($path === '/users/updateProfile' && $method === 'PUT'):
            $controller = new UserController($database);
            $controller->updateProfile();
            break;
            
        case ($path === '/users/change-password' && $method === 'POST'):
            $controller = new UserController($database);
            $controller->changePassword();
            break;
            
        case ($path === '/users/refresh-token' && $method === 'POST'):
            $controller = new UserController($database);
            $controller->refreshToken();
            break;
            
        // Admin user management routes
        case ($path === '/users' && $method === 'GET'):
            $controller = new UserController($database);
            $controller->getUsers();
            break;
            
        case ($path === '/users/stats' && $method === 'GET'):
            $controller = new UserController($database);
            $controller->getUserStats();
            break;

        // Game console routes
        case (($path === '/game-consoles' || $path === '/game_consoles') && $method === 'GET'):
            $controller = new GameController($database);
            $controller->index();
            break;
            
        case (($path === '/game-consoles' || $path === '/game_consoles') && $method === 'POST'):
            $controller = new GameController($database);
            $controller->create();
            break;

        // Dynamic routes with regex
        case (preg_match('/^\/users\/(\d+)$/', $path, $matches) === 1):
            $userId = $matches[1];
            $controller = new UserController($database);
            
            switch ($method) {
                case 'GET':
                    $controller->getUserById($userId);
                    break;
                case 'PUT':
                    $controller->updateUser($userId);
                    break;
                case 'DELETE':
                    $controller->deleteUser($userId);
                    break;
                default:
                    sendErrorResponse(405, "Method not allowed");
            }
            break;
            
        case (preg_match('/^\/game[-_]consoles\/(\d+)$/', $path, $matches) === 1):
            $consoleId = $matches[1];
            $controller = new GameController($database);
            
            switch ($method) {
                case 'GET':
                    $controller->show($consoleId);
                    break;
                case 'PUT':
                    $controller->update($consoleId);
                    break;
                case 'DELETE':
                    $controller->delete($consoleId);
                    break;
                default:
                    sendErrorResponse(405, "Method not allowed");
            }
            break;

        // 404 - Route not found
        default:
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Route not found",
                "path" => $path,
                "method" => $method,
                "available_routes" => [
                    "POST /register",
                    "POST /login",
                    "GET /users/profile",
                    "PUT /users/updateProfile",
                    "POST /users/change-password",
                    "POST /users/refresh-token",
                    "GET /users (admin)",
                    "GET /users/stats (admin)",
                    "GET|PUT|DELETE /users/{id} (admin)",
                    "GET|POST /game-consoles",
                    "GET|PUT|DELETE /game-consoles/{id}"
                ]
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Error in index.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
