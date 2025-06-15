<?php
    class Session
    {
        private $db;
        private $table = 'user_sessions';

        public function __construct($database)
        {
            $this->db = $database;
        }

        // Tạo session mới
        public function create($data)
        {
            $sql = "INSERT INTO {$this->table} (session_id, user_id, ip_address, user_agent, expires_at) 
                    VALUES (:session_id, :user_id, :ip_address, :user_agent, :expires_at)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $data['session_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':ip_address', $data['ip_address']);
            $stmt->bindParam(':user_agent', $data['user_agent']);
            $stmt->bindParam(':expires_at', $data['expires_at']);

            return $stmt->execute();
        }

        // Tìm session theo ID
        public function findById($sessionId)
        {
            $sql = "SELECT s.*, u.username, u.role, u.status as user_status
                    FROM {$this->table} s
                    JOIN users u ON s.user_id = u.user_id
                    WHERE s.session_id = :session_id AND s.expires_at > NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Cập nhật thời gian hết hạn
        public function updateExpiry($sessionId, $expiresAt)
        {
            $sql = "UPDATE {$this->table} SET expires_at = :expires_at WHERE session_id = :session_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->bindParam(':expires_at', $expiresAt);
            return $stmt->execute();
        }

        // Xóa session
        public function delete($sessionId)
        {
            $sql = "DELETE FROM {$this->table} WHERE session_id = :session_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $sessionId);
            return $stmt->execute();
        }

        // Xóa tất cả session của user
        public function deleteUserSessions($userId)
        {
            $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            return $stmt->execute();
        }

        // Xóa session hết hạn
        public function cleanExpiredSessions()
        {
            $sql = "DELETE FROM {$this->table} WHERE expires_at <= NOW()";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        }

        // Lấy session hoạt động của user
        public function getUserActiveSessions($userId)
        {
            $sql = "SELECT session_id, ip_address, user_agent, created_at, expires_at
                    FROM {$this->table}
                    WHERE user_id = :user_id AND expires_at > NOW()
                    ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Thống kê session
        public function getSessionStats()
        {
            $sql = "SELECT 
                        COUNT(*) as total_sessions,
                        COUNT(DISTINCT user_id) as unique_users,
                        COUNT(CASE WHEN expires_at > NOW() THEN 1 END) as active_sessions
                    FROM {$this->table}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }



// models/BaseModel.php - Model cơ sở cho các model khác extend


?>