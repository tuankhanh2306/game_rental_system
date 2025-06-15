<?php
    namespace services;
    use models\Rental;
    use Exception;
    class RentalService
    {
        private $db;

        public function __construct($database)
        {
            $this->db = $database;
        }

        public function createRental($data)
        {
            try {
                $rental = new Rental($this->db);
                return $rental->create($data);
            } catch (Exception $e) {
                error_log("Error creating rental: " . $e->getMessage());
                return ['success' => false, 'message' => 'Lỗi khi tạo thuê: ' . $e->getMessage()];
            }
        }

        //lấy tất cả các đơn thuê
        public function getRentals()
        {
            try {
                $rental = new Rental($this->db);
                return $rental->getAll();
            } catch (Exception $e) {
                error_log("Error fetching rentals: " . $e->getMessage());
                return ['success' => false, 'message' => 'Lỗi khi lấy danh sách thuê: ' . $e->getMessage()];
            }
        }
    }

?>