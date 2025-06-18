<?php
    namespace services;
    use models\Rental;
    use models\User;
    use models\Game;
    use Exception;
    class RentalService
    {
        private $rentalModel;
        private $gameModel;
        private $userModel;

        public function __construct($database = null)
        {
            $this->rentalModel = new Rental($database);
            $this->gameModel = new Game($database);
            $this->userModel = new User($database);
        }

        //tạo đơn thuê mới
        public function createRental($data){
            try{
                $validation= $this->ValidateRentalData($data);
                if($validation["valid"]){
                    return [
                        "success" => false,
                        "message"=> $validation["message"],
                        "error" => $validation["error"],
                    ];
                }

                //kiểm tra sự tồn tại của user
                $user = $this->userModel->findById($data["user_id"]);
                if(!$user){
                    return [
                        "success"=> false,
                        "message"=> 'Người dùng không tồn tại'
                    ];


                }

                //kiểm tra máy chơi game có tồn tại hay không
                $game = $this->gameModel->findById($data['console_id']);
                if(!$game){
                    return [
                        'success'=> false,
                        'message'=> 'máy chơi game không tồn tại'
                    ];

                }

                if($game['status'] !== 'available'){
                    return [
                        'success'=> false,
                        'message'=> 'Máy chơi game không có sẵn'
                    ];
                }


                // kiểm tra xung đột thời gian 
                if($this->rentalModel->checkTimeConflict($data['console_id'], $data['rental_start'],$data['rental_end'])){
                    return [
                        'success' => false,
                        'message' => 'Thời gian thuê bị trung với đơn thuê khác'
                    ];
                }

                //tính toán tổng tiền 
                $totalHours = $this -> calculateTotalHours($data['rental_start'],$data['rental_end']);
                $totalAmount = $totalHours * $game['rental_price_per_hour'];

                $rentalData = [
                    'user_id' => $data['user_id'],
                    'console_id' => $data['console_id'],
                    'rental_start' => $data['rental_start'],
                    'rental_end' => $data['rental_end'],
                    'total_hours' => $totalHours,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? ''
                ];

                $rentalId = $this->rentalModel->create($rentalData);

                if ($rentalId) {
                    return [
                        'success' => true,
                        'message' => 'Tạo đơn thuê thành công',
                        'rental_id' => $rentalId,
                        'total_amount' => $totalAmount
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Không thể tạo đơn thuê'
                    ];
                }
            }
            catch(Exception $e){

            }
        }
        

        //validate dữ liệu đơn thuê
        private function validateRentalData($data)
        {
            $errors = [];

            // Kiểm tra các trường bắt buộc
            $requiredFields = ['user_id', 'console_id', 'rental_start', 'rental_end'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errors[] = "Trường {$field} là bắt buộc";
                }
            }

            // Kiểm tra thời gian
            if (!empty($data['rental_start']) && !empty($data['rental_end'])) {
                $startTime = strtotime($data['rental_start']);
                $endTime = strtotime($data['rental_end']);

                if ($startTime >= $endTime) {
                    $errors[] = "Thời gian kết thúc phải sau thời gian bắt đầu";
                }

                if ($startTime < time()) {
                    $errors[] = "Thời gian bắt đầu phải trong tương lai";
                }

                // Tối thiểu 1 giờ thuê
                if (($endTime - $startTime) < 3600) {
                    $errors[] = "Thời gian thuê tối thiểu là 1 giờ";
                }
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'message' => empty($errors) ? '' : implode(', ', $errors)
            ];
        }

            /**
         * Tính tổng số giờ thuê
         */
        private function calculateTotalHours($startTime, $endTime)
        {
            $start = strtotime($startTime);
            $end = strtotime($endTime);
            return ceil(($end - $start) / 3600);
        }

        /**
         * Validate việc chuyển trạng thái
         */
        private function validateStatusChange($currentStatus, $newStatus)
        {
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['active', 'cancelled'],
                'active' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => []
            ];

            if (!isset($allowedTransitions[$currentStatus]) || 
                !in_array($newStatus, $allowedTransitions[$currentStatus])) {
                return [
                    'valid' => false,
                    'message' => "Không thể chuyển từ trạng thái {$currentStatus} sang {$newStatus}"
                ];
            }

            return ['valid' => true];
        }

        /**
         * Cập nhật trạng thái console dựa trên trạng thái đơn thuê
         */
        private function updateConsoleStatusBasedOnRental($consoleId, $rentalStatus)
        {
            switch ($rentalStatus) {
                case 'active':
                    $this->gameModel->updateStatus($consoleId, 'rented');
                    break;
                case 'completed':
                case 'cancelled':
                    $this->gameModel->updateStatus($consoleId, 'available');
                    break;
            }
        }

        /**
         * Tính toán các slot thời gian có sẵn
         */
        private function calculateAvailableSlots($bookedSlots, $date)
        {
            // Giờ hoạt động: 8:00 - 22:00
            $openHour = 8;
            $closeHour = 22;
            $availableSlots = [];

            $currentHour = $openHour;
            
            foreach ($bookedSlots as $slot) {
                $bookedStart = date('H', strtotime($slot['rental_start']));
                $bookedEnd = date('H', strtotime($slot['rental_end']));

                // Thêm các slot trống trước slot đã book
                while ($currentHour < $bookedStart) {
                    $availableSlots[] = [
                        'start' => sprintf('%02d:00', $currentHour),
                        'end' => sprintf('%02d:00', $currentHour + 1)
                    ];
                    $currentHour = $bookedEnd;
                }

                // Thêm các slot còn lại sau slot cuối cùng
                while ($currentHour < $closeHour) {
                    $availableSlots[] = [
                        'start' => sprintf('%02d:00', $currentHour),
                        'end' => sprintf('%02d:00', $currentHour + 1)
                    ];
                    $currentHour++;
                }

                return $availableSlots;
            }
        }
    

        /**
         * Cập nhật tự động các đơn thuê hết hạn
         */
        public function updateExpiredRentals()
        {
            try {
                $result = $this->rentalModel->updateExpiredRentals();
                return [
                    'success' => true,
                    'message' => 'Cập nhật đơn thuê hết hạn thành công'
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi cập nhật đơn thuê hết hạn: ' . $e->getMessage()
                ];
            }
        }

        /**
         * Gia hạn đơn thuê
         */
        public function extendRental($id, $newEndTime)
        {
            try {
                $rental = $this->rentalModel->findById($id);
                
                if (!$rental) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy đơn thuê'
                    ];
                }

                if ($rental['status'] !== 'active') {
                    return [
                        'success' => false,
                        'message' => 'Chỉ có thể gia hạn đơn thuê đang hoạt động'
                    ];
                }

                $newEndTimeStamp = strtotime($newEndTime);
                $currentEndTimeStamp = strtotime($rental['rental_end']);

                if ($newEndTimeStamp <= $currentEndTimeStamp) {
                    return [
                        'success' => false,
                        'message' => 'Thời gian gia hạn phải sau thời gian kết thúc hiện tại'
                    ];
                }

                // Kiểm tra xung đột thời gian với slot mới
                if ($this->rentalModel->checkTimeConflict($rental['console_id'], $rental['rental_end'], $newEndTime, $id)) {
                    return [
                        'success' => false,
                        'message' => 'Thời gian gia hạn bị trung với đơn thuê khác'
                    ];
                }

                // Tính toán thêm tiền
                $console = $this->gameModel->findById($rental['console_id']);
                $additionalHours = ceil(($newEndTimeStamp - $currentEndTimeStamp) / 3600);
                $additionalAmount = $additionalHours * $console['rental_price_per_hour'];
                $newTotalHours = $rental['total_hours'] + $additionalHours;
                $newTotalAmount = $rental['total_amount'] + $additionalAmount;

                $updateData = [
                    'rental_end' => $newEndTime,
                    'total_hours' => $newTotalHours,
                    'total_amount' => $newTotalAmount
                ];

                $result = $this->rentalModel->update($id, $updateData);

                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Gia hạn đơn thuê thành công',
                        'additional_amount' => $additionalAmount,
                        'new_total_amount' => $newTotalAmount
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Không thể gia hạn đơn thuê'
                    ];
                }

            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi khi gia hạn đơn thuê: ' . $e->getMessage()
                ];
            }
        }
        
    }

?>