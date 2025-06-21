<?php

    namespace core;

    require_once __DIR__ . '/../config/database.php';

    use PDO;
    use PDOException;
    use Exception;
    class Database {
        private static $instance = null;
        private $connection;
        
        private function __construct() {
            try {
                $this->connection = new PDO(
                    DB_DSN,
                    DB_USER, 
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        
        

        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function getConnection() {
            return $this->connection;
        }
        
        // Prevent cloning
        private function __clone() {}
        
        // Prevent unserialization
        public function __wakeup() {
            throw new Exception("Cannot unserialize singleton");
        }
    }
?>