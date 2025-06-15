<?php

    // models/RentalHistory.php
    class RentalHistory
    {
        private $db;
        private $table = 'rental_history';

        public function __construct($database)
        {
            $this->db = $database;
        }

        // Tạo lịch sử mới
        public function create($data)
        {
            $sql = "INSERT INTO {$this->table} (rental_id, action, action_by, action_date, notes) 
                    VALUES (:rental_id, :action, :action_by, :action_date, :notes)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':rental_id', $data['rental_id']);
            $stmt->bindParam(':action', $data['action']);
            $stmt->bindParam(':action_by', $data['action_by']);
            $stmt->bindParam(':action_date', $data['action_date'] ?? date('Y-m-d H:i:s'));
            $stmt->bindParam(':notes', $data['notes'] ?? '');

            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        }

        // Lấy lịch sử của một đơn thuê
        public function getRentalHistory($rentalId)
        {
            $sql = "SELECT rh.*, u.username, u.full_name
                    FROM {$this->table} rh
                    LEFT JOIN users u ON rh.action_by = u.user_id
                    WHERE rh.rental_id = :rental_id
                    ORDER BY rh.action_date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':rental_id', $rentalId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Lấy lịch sử hoạt động gần đây
        public function getRecentActivity($limit = 20)
        {
            $sql = "SELECT rh.*, u.username, u.full_name, r.rental_id as rental_number,
                        gc.console_name, ru.username as renter_username
                    FROM {$this->table} rh
                    LEFT JOIN users u ON rh.action_by = u.user_id
                    JOIN rentals r ON rh.rental_id = r.rental_id
                    JOIN game_consoles gc ON r.console_id = gc.console_id
                    JOIN users ru ON r.user_id = ru.user_id
                    ORDER BY rh.action_date DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Thống kê hoạt động theo action
        public function getActionStats($startDate = null, $endDate = null)
        {
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if ($startDate) {
                $whereClause .= " AND action_date >= :start_date";
                $params[':start_date'] = $startDate;
            }
            
            if ($endDate) {
                $whereClause .= " AND action_date <= :end_date";
                $params[':end_date'] = $endDate;
            }

            $sql = "SELECT action, COUNT(*) as count FROM {$this->table} {$whereClause} GROUP BY action";
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
?>