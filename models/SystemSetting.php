<?php
    class SystemSetting
    {
        private $db;
        private $table = 'system_settings';

        public function __construct($database)
        {
            $this->db = $database;
        }

        // Lấy giá trị setting
        public function get($key, $default = null)
        {
            $sql = "SELECT setting_value FROM {$this->table} WHERE setting_key = :key";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':key', $key);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : $default;
        }

        // Lấy nhiều settings
        public function getMultiple($keys)
        {
            $placeholders = str_repeat('?,', count($keys) - 1) . '?';
            $sql = "SELECT setting_key, setting_value FROM {$this->table} WHERE setting_key IN ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($keys);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        }

        // Lấy tất cả settings
        public function getAll()
        {
            $sql = "SELECT setting_key, setting_value, description FROM {$this->table} ORDER BY setting_key";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Cập nhật hoặc tạo setting
        public function set($key, $value, $description = '')
        {
            $sql = "INSERT INTO {$this->table} (setting_key, setting_value, description, updated_at) 
                    VALUES (:key, :value, :description, NOW())
                    ON DUPLICATE KEY UPDATE 
                    setting_value = :value2, description = :description2, updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':value2', $value);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':description2', $description);
            return $stmt->execute();
        }

        // Cập nhật nhiều settings
        public function setMultiple($settings)
        {
            $this->db->beginTransaction();
            
            try {
                foreach ($settings as $key => $data) {
                    $value = is_array($data) ? $data['value'] : $data;
                    $description = is_array($data) ? ($data['description'] ?? '') : '';
                    
                    if (!$this->set($key, $value, $description)) {
                        throw new Exception("Failed to set {$key}");
                    }
                }
                
                $this->db->commit();
                return true;
            } catch (Exception $e) {
                $this->db->rollback();
                return false;
            }
        }

        // Xóa setting
        public function delete($key)
        {
            $sql = "DELETE FROM {$this->table} WHERE setting_key = :key";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':key', $key);
            return $stmt->execute();
        }

        // Kiểm tra setting có tồn tại không
        public function exists($key)
        {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE setting_key = :key";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':key', $key);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        }
    }
?>