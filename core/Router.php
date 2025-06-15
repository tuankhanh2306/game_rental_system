<?php
namespace core;

class Router
{
    private $routes = [];
    
    /**
     * Thêm route GET
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Thêm route POST
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Thêm route PUT
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Thêm route DELETE
     */
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Thêm route PATCH
     */
    public function patch($path, $handler)
    {
        $this->addRoute('PATCH', $path, $handler);
    }
    
    /**
     * Thêm route vào danh sách
     */
    private function addRoute($method, $path, $handler)
    {
        // Chuyển đổi path parameters thành regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    /**
     * Xử lý request
     */
    public function dispatch($uri, $method)
    {
        // Loại bỏ query string
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Loại bỏ /api prefix nếu có
        $uri = preg_replace('#^/api#', '', $uri);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                // Loại bỏ match đầu tiên (full match)
                array_shift($matches);
                
                $handler = $route['handler'];
                
                if (is_array($handler)) {
                    [$controllerClass, $methodName] = $handler;
                    
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        
                        if (method_exists($controller, $methodName)) {
                            // Gọi method với parameters từ URL
                            call_user_func_array([$controller, $methodName], $matches);
                            return;
                        } else {
                            $this->sendError(500, "Method $methodName không tồn tại trong $controllerClass");
                            return;
                        }
                    } else {
                        $this->sendError(500, "Controller $controllerClass không tồn tại");
                        return;
                    }
                } elseif (is_callable($handler)) {
                    call_user_func_array($handler, $matches);
                    return;
                }
            }
        }
        
        // Không tìm thấy route
        $this->sendError(404, 'Route không tồn tại');
    }
    
    /**
     * Gửi lỗi JSON
     */
    private function sendError($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
