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
        // Chuyển đổi namespace thành đường dẫn file
        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $file = __DIR__ . DIRECTORY_SEPARATOR . $className . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    });

    // Require tất cả các file cần thiếtư
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
        // Kết nối DB - đúng cách
        $database = require __DIR__ . '/config/database.php';
        
        // Debug: Kiểm tra database connection
        error_log("Database object type: " . gettype($database));
        if (is_object($database)) {
            error_log("Database class: " . get_class($database));
        }
        
        // Định tuyến
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];
        
        error_log("Request URI: " . $uri);
        error_log("Request Method: " . $method);
        
        // Xử lý routing - cải thiện để handle cả đường dẫn có thư mục
        $path = str_replace('/game_rental_system', '', $uri); // Remove base path if exists
        
        switch ($path) {
            case '/register':
                if ($method === 'POST') {
                    $authController = new AuthController($database);
                    $authController->register();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            case '/login':
                if ($method === 'POST') {
                    $authController = new AuthController($database);
                    $authController->login();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
            
            case '/users/profile':  // Thêm route API chuẩn
                if ($method === 'GET') {
                    $userController = new UserController();  // UserController đã tự khởi tạo database
                    $userController->getProfile();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            case '/users/updateProfile':  // PUT method
                if ($method === 'PUT') {
                    $userController = new UserController();
                    $userController->updateProfile();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            case '/users/change-password':
                if ($method === 'POST') {
                    $userController = new UserController();
                    $userController->changePassword();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            case '/users/refresh-token':
                if ($method === 'POST') {
                    $userController = new UserController();
                    $userController->refreshToken();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            case '/users/logout':
                if ($method === 'POST') {
                    $userController = new UserController();
                    $userController->logout();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
            case '/game_consoles/index':
                if ($method === 'GET') {
                    $gameController = new GameController(); 
                    $gameController->index();
                    
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
            case '/game_consoles/create':
                if ($method === 'POST') {
                    $gameController = new GameController();
                    $gameController->create();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            // Admin routes
            case '/users':
            case '/users':
                if ($method === 'GET') {
                    $userController = new UserController();
                    $userController->getUsers();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            case '/users/stats':
            case '/users/stats':
                if ($method === 'GET') {
                    $userController = new UserController();
                    $userController->getUserStats();
                } else {
                    http_response_code(405);
                    echo json_encode(["success" => false, "message" => "Method not allowed"]);
                }
                break;
                
            default:
                // Xử lý dynamic routes với parameters (như  /users/{id})
                if (preg_match('/^\ \/users\/(\d+)$/', $path, $matches)) {
                    $userId = $matches[1];
                    $userController = new UserController();
                    
                    if ($method === 'PUT') {
                        $userController->updateUser($userId);
                    } elseif ($method === 'DELETE') {
                        $userController->deleteUser($userId);
                    } else {
                        http_response_code(405);
                        echo json_encode(["success" => false, "message" => "Method not allowed"]);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode([
                        "success" => false, 
                        "message" => "Route not found", 
                        "path" => $path,
                        "available_routes" => [
                            "/register", 
                            "/login", 
                            "/users/getProfile",
                            " /users/profile",
                            " /users/change-password",
                            " /users/refresh-token",
                            " /users/logout",
                            " /users (GET - admin)",
                            " /users/stats (GET - admin)",
                            " /users/{id} (PUT/DELETE - admin)"
                        ]
                    ]);
                }
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