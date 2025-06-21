<?php
/**
 * Test Database Connection
 * Chạy file này để kiểm tra kết nối
 */
    require_once __DIR__ . '/vendor/autoload.php';

    use core\Database;

    try {
        echo "<h2>🔍 Testing Database Connection...</h2>";
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        echo "<p>✅ <strong>Connection successful!</strong></p>";
        
        // Test query
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        
        echo "<p>📊 Total users in database: <strong>" . $result['total'] . "</strong></p>";
        
        // Test sample data
        $stmt = $conn->query("SELECT console_name, console_type, rental_price_per_hour FROM game_consoles LIMIT 3");
        $consoles = $stmt->fetchAll();
        
        echo "<h3>🎮 Sample Game Consoles:</h3>";
        echo "<ul>";
        foreach ($consoles as $console) {
            echo "<li><strong>{$console['console_name']}</strong> ({$console['console_type']}) - " . 
                number_format($console['rental_price_per_hour']) . " VNĐ/giờ</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p>❌ <strong>Connection failed:</strong> " . $e->getMessage() . "</p>";
    }
?>