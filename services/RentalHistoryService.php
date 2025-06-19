<?php
namespace services;

use models\RentalHistory;
use Exception;

class RentalHistoryService
{
    private $historyModel;

    public function __construct($db)
    {
        $this->historyModel = new RentalHistory($db);
    }

    public function getAll()
    {
        return $this->historyModel->getRecentActivity(100);
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
