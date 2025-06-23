<?php
namespace services;

use models\RentalHistory;
use Exception;

class RentalHistoryService
{
    private $historyModel;
     private $db;

    public function __construct($db)
    {
        $this->historyModel = new RentalHistory($db);
        $this->db = $db;
    }

    public function getAll($filters = [])
{
    $sql = "SELECT * FROM rental_history WHERE 1=1";
    $params = [];

    if (!empty($filters['action'])) {
        $sql .= " AND action = :action";
        $params[':action'] = $filters['action'];
    }

    if (!empty($filters['action_by'])) {
        $sql .= " AND action_by = :action_by";
        $params[':action_by'] = $filters['action_by'];
    }

    if (!empty($filters['from_date'])) {
        $sql .= " AND action_date >= :from_date";
        $params[':from_date'] = $filters['from_date'];
    }

    if (!empty($filters['to_date'])) {
        $sql .= " AND action_date <= :to_date";
        $params[':to_date'] = $filters['to_date'];
    }

    $sql .= " ORDER BY action_date DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}


    public function getByRentalId($rentalId)
    {
        return $this->historyModel->getRentalHistory($rentalId);
    }

    public function getRecent($limit = 20)
    {
        return $this->historyModel->getRecentActivity($limit);
    }

    public function getStats($start = null, $end = null)
    {
        return $this->historyModel->getActionStats($start, $end);
    }
}
