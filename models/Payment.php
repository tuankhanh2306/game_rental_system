<?php
namespace models;
use PDO;

class Payment
{
    private $db;
    private $table = 'payments';

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, total_amount, payment_method, payment_status)
                VALUES (:user_id, :total_amount, :payment_method, :payment_status)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':total_amount', $data['total_amount']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':payment_status', $data['payment_status']);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
