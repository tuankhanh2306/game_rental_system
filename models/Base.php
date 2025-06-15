<?php
    abstract class BaseModel
    {
        protected $db;
        protected $table;
        protected $primaryKey = 'id';
        protected $fillable = [];
        protected $timestamps = true;

        public function __construct($database)
        {
            $this->db = $database;
        }

        // Tìm bản ghi theo ID
        public function find($id)
        {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Tìm bản ghi đầu tiên theo điều kiện
        public function findWhere($conditions, $operator = 'AND')
        {
            $whereClause = $this->buildWhereClause($conditions, $operator);
            $sql = "SELECT * FROM {$this->table} WHERE {$whereClause['clause']} LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            foreach ($whereClause['params'] as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Lấy tất cả bản ghi theo điều kiện
        public function where($conditions, $operator = 'AND', $orderBy = null, $limit = null)
        {
            $whereClause = $this->buildWhereClause($conditions, $operator);
            $sql = "SELECT * FROM {$this->table} WHERE {$whereClause['clause']}";
            
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            
            if ($limit) {
                $sql .= " LIMIT {$limit}";
            }
            
            $stmt = $this->db->prepare($sql);
            foreach ($whereClause['params'] as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Đếm số bản ghi
        public function countWhere($conditions = [], $operator = 'AND')
        {
            if (empty($conditions)) {
                $sql = "SELECT COUNT(*) FROM {$this->table}";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                return $stmt->fetchColumn();
            }
            
            $whereClause = $this->buildWhereClause($conditions, $operator);
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause['clause']}";
            
            $stmt = $this->db->prepare($sql);
            foreach ($whereClause['params'] as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchColumn();
        }

        // Tạo bản ghi mới
        public function insert($data)
        {
            // Lọc dữ liệu theo fillable
            if (!empty($this->fillable)) {
                $data = array_intersect_key($data, array_flip($this->fillable));
            }
            
            // Thêm timestamps nếu cần
            if ($this->timestamps) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindParam(":{$key}", $data[$key]);
            }
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        }

        // Cập nhật bản ghi
        public function updateWhere($conditions, $data, $operator = 'AND')
        {
            // Lọc dữ liệu theo fillable
            if (!empty($this->fillable)) {
                $data = array_intersect_key($data, array_flip($this->fillable));
            }
            
            // Thêm updated_at nếu cần
            if ($this->timestamps && !isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "{$key} = :set_{$key}";
            }
            
            $whereClause = $this->buildWhereClause($conditions, $operator);
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$whereClause['clause']}";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind set parameters
            foreach ($data as $key => $value) {
                $stmt->bindParam(":set_{$key}", $data[$key]);
            }
            
            // Bind where parameters
            foreach ($whereClause['params'] as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            return $stmt->execute();
        }

        // Xóa bản ghi
        public function deleteWhere($conditions, $operator = 'AND')
        {
            $whereClause = $this->buildWhereClause($conditions, $operator);
            $sql = "DELETE FROM {$this->table} WHERE {$whereClause['clause']}";
            
            $stmt = $this->db->prepare($sql);
            foreach ($whereClause['params'] as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            return $stmt->execute();
        }

        // Xây dựng WHERE clause
        protected function buildWhereClause($conditions, $operator = 'AND')
        {
            $clauses = [];
            $params = [];
            $counter = 0;
            
            foreach ($conditions as $field => $value) {
                $paramKey = ":where_{$field}_{$counter}";
                
                if (is_array($value)) {
                    // Xử lý IN clause
                    $placeholders = [];
                    foreach ($value as $i => $val) {
                        $placeholder = "{$paramKey}_{$i}";
                        $placeholders[] = $placeholder;
                        $params[$placeholder] = $val;
                    }
                    $clauses[] = "{$field} IN (" . implode(', ', $placeholders) . ")";
                } else {
                    $clauses[] = "{$field} = {$paramKey}";
                    $params[$paramKey] = $value;
                }
                
                $counter++;
            }
            
            return [
                'clause' => implode(" {$operator} ", $clauses),
                'params' => $params
            ];
        }

        // Bắt đầu transaction
        public function beginTransaction()
        {
            return $this->db->beginTransaction();
        }

        // Commit transaction
        public function commit()
        {
            return $this->db->commit();
        }

        // Rollback transaction
        public function rollback()
        {
            return $this->db->rollback();
        }

        // Thực thi raw SQL
        public function query($sql, $params = [])
        {
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindParam($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Lấy last insert ID
        public function getLastInsertId()
        {
            return $this->db->lastInsertId();
        }
    }
?>