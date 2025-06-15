<?php
namespace models;

class User
{
    private $db;
    private $table = 'users';
    
    public function __construct($database){
        $this->db = $database;
    }
    
    //tạo người dùng mới
    public function create($data){
        $sql = "INSERT INTO " . $this->table . " (username, password_hash, email, full_name, phone, role, status)
                VALUES (:username, :password_hash, :email, :full_name, :phone, :role, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password_hash', $data['password_hash']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':status', $data['status']);
        
        if($stmt->execute()){
            return $this->db->lastInsertId();
        } else {
            $error = $stmt->errorInfo();
            throw new \Exception("SQL Error: " . $error[2]);
        }
    }
    
    //tìm thông tin người dùng theo id
    public function findById($id){
        $sql = "SELECT * FROM " . $this->table . " WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    //tìm thông tin người dùng theo username
    public function findByUsername($username){
        $sql = "SELECT * FROM " . $this->table . " WHERE username = :username AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    //tìm thông tin người dùng theo email
    public function findByEmail($email){
        $sql = "SELECT * FROM " . $this->table . " WHERE email = :email AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    //kiểm tra xem người dùng có tồn tại hay không
    public function userNameExists($username){
        $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    //kiểm tra xem email có tồn tại hay không
    public function emailExists($email){
        $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    //cập nhật thông tin người dùng
    public function update($id, $data){
        $sql = "UPDATE " . $this->table . 
                " SET username = :username,
                    email = :email,
                    full_name = :full_name,
                    phone = :phone,
                    role = :role,
                    status = :status
                WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $id);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':status', $data['status']);
        return $stmt->execute();
    }
    
    // Lấy kết nối cơ sở dữ liệu
    public function getDatabase() {
        return $this->db;
    }
    
    //cập nhật mật khẩu người dùng
    public function updatePassword($id, $passwordHash) {
        $sql = "UPDATE " . $this->table . " SET password_hash = :password_hash WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':password_hash', $passwordHash);
        return $stmt->execute();
    }
    
    //xóa người dùng
    public function delete($id){
        $sql = "DELETE FROM " . $this->table . " WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    //lấy danh sách người dùng với phân trang
    public function getAll($page = 1, $limit = 10, $search = ''){
        $offset = ($page - 1) * $limit;
        $whereClause = "WHERE status != 'deleted' ";
        $params = [];
        
        if(!empty($search)){
            $whereClause .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql = "SELECT user_id, username, email, full_name, phone, role, status, created_at, updated_at
                FROM " . $this->table . " " . $whereClause . 
                " ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    //Đếm tổng số người dùng
    public function countAll($search = ''){
        $whereClause = "WHERE status != 'deleted' ";
        $params = [];
        
        if(!empty($search)){
            $whereClause .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql = "SELECT COUNT(*) FROM " . $this->table . " " . $whereClause;
        $stmt = $this->db->prepare($sql);
        
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    //xóa mềm người dùng
    public function softDelete($id){
        $sql = "UPDATE " . $this->table . " SET status = 'deleted' WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    //thống kê người dùng theo vai trò
    public function countByRole(){
        $sql = "SELECT role, COUNT(*) as total
                FROM " . $this->table . "
                WHERE status = 'active'
                GROUP BY role";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>
