<?php
namespace models;

use Exception;
use PDO;

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
    //kiểm tra xem số điện thoại có tồn tại hay không
    public function phoneExists($phone){
        $sql = "SELECT COUNT(*) FROM " . $this->table . " WHERE phone = :phone";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    //cập nhật thông tin người dùng
    public function update($id, $data) {
        if (empty($data)) {
            throw new Exception("Không có dữ liệu để cập nhật.");
        }

        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(", ", $fields) . " WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);

        // Bind tất cả trường động
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        // Bind user_id
        $stmt->bindValue(':user_id', $id);

        return $stmt->execute();
    }

    
    // Lấy kết nối cơ sở dữ liệu
    public function getDatabase() {
        return $this->db;
    }
    
    //cập nhật mật khẩu người dùng
    public function updatePassword($id, $passwordHash) {
        $sql = "UPDATE " . $this->table . " SET password_hash = :password_hash WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $id);
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
    public function getAll($page = 1, $limit = 10, $search = '')
    {
        $offset = ($page - 1) * $limit;
        $params = [];

        $sql = "SELECT * FROM users";

        if (!empty($search)) {
            $sql .= " WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    // Fixed countAll method
    public function countAll($search = '')
    {
        $sql = "SELECT COUNT(*) FROM users";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
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
