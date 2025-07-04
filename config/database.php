<?php
    /**
     * Database Configuration
     * Đọc từ file .env để bảo mật
     */

    // Load environment variables
    if (!function_exists('loadEnv')) {
    function loadEnv($file) {
        if (!file_exists($file)) return;
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }}
    }

    // Load .env file
    loadEnv(__DIR__ . '/../.env');

    // Database constants
    if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'game_rental_system');
    if (!defined('DB_PORT')) define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
    if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
    if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');

    // DSN cho PDO
    if (!defined('DB_DSN')) define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);

    // Tạo và trả về PDO connection
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
?>
