<?php
    namespace services;
    use models\Game;
    use Exception;
    class GameService
    {
        private $gameModel;
    
        public function __construct($db) 
        {
            $this->gameModel = new Game($db);
        }

        // Tạo máy chơi game mới
        public function createGame($data)
        {
            try {
                $validationResult = $this->validateGameData($data);
                if($validationResult['valid']){
                        return [
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $validationResult['errors']
                    ];
                }

                //chuẩn bị dữ liệu
                $consoleData = [
                    'console_name' => trim($data['console_name']),
                    'console_type' => trim($data['console_type']),
                    'description' => trim($data['description'] ?? ''),
                    'image_url' => trim($data['image'] ?? ''),
                    'rental_price_per_hour' => floatval($data['rent_price']),
                    'status' => trim($data['status']),
                ];

                $consoleId = $this->gameModel->create($consoleData);
                if ($consoleId) {
                    return [
                        'success' => true,
                        'message' => 'Máy chơi game đã được tạo thành công.',
                        'console_id' => $consoleId
                    ];
                } else {
                    return [
                        'success' => false, 
                        'message' => 'Không thể tạo máy chơi game.'
                    ];
                }


            } catch (Exception $e) {
                error_log("Error creating game: " . $e->getMessage());
                return ['success' => false, 'message' => 'Lỗi khi tạo máy chơi game: ' . $e->getMessage()];
            }
        }

        
        //lấy máy chơi game có phân trang
        public function getConsoleList($param=[])
        {
            try {
                $page = max(1, intval($param['page'] ?? 1)); // Đảm bảo trang bắt đầu từ 1
                $limit = max(1, intval($param['limit'] ?? 12)); // Đảm bảo giới hạn lớn hơn 0
                $type = $param['type'] ?? '';
                $search = $param['search'] ?? '';
                $availableOnly = $param['available_only'] ?? true;

                //lấy dữ liệu máy chơi game
                if($availableOnly){
                    $consoleData = $this->gameModel->getAvailable($page, $limit, $type, $search);
                    $totalCount = $this->gameModel->count( $search, true);
                }
                else{
                    $consoleData = $this->gameModel->getAll($page, $limit, $type, $search);
                    $totalCount = $this->gameModel->count($search, false);
                }
                //xử lí dữ liệu máy chơi game
                $processedConsoles = array_map([$this, 'processGameData'], $consoleData);

                //tính toán phân trang
                $totalPages = ceil($totalCount / $limit);

                return [
                    'success' => true,
                    'data' => [
                        'consoles' => $processedConsoles,
                        'total_count' => $totalCount,
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'limit' => $limit,
                        'has_next' => $page < $totalPages,
                        'has_prev' => $page > 1
                    ],
                    'message' => 'Danh sách máy chơi game đã được lấy thành công.'
                ];



            } catch (Exception $e) {
            
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi lấy danh sách máy chơi game: ' . $e->getMessage()
                ];
            }
        }

        //lấy thông tin chi tiết mnay chơi game
        public function getConsoleDetail($id)
        {
            try {


                if(!is_numeric($id) || $id <= 0) {
                    return [
                        'success' => false,
                        'message' => 'ID không hợp lệ.'
                    ];
                }

                $console = $this->gameModel->findById($id);
                if (!$console) {
                    return [
                        'success' => false,
                        'message' => 'không tìm thấy máy chơi game.'
                    ];
                }
                //kiểm tra trạng thái máy chơi game
                $isRented = $this->gameModel->isCurrentlyRented($id);
                $console['is_currently_rented'] = $isRented;

                
            } catch (Exception $e) {
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi lấy thông tin máy chơi game: ' . $e->getMessage()
                ];
            }
        }

        //câp nhật thông tin máy chơi game
        public function updateGame($id, $data)
        {
            try {
                //kiểm tra id máy chơi game
                if(!is_numeric($id) || $id <= 0) {
                    return [
                        'success' => false,
                        'message' => 'ID không hợp lệ.'
                    ];
                }

                //kiểm tra máy chơi game có tồn tại không
                $console = $this->gameModel->findById($id);
                if (!$console) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy máy chơi game.'
                    ];
                }

                //validate dữ liệu máy chơi game
                $validationResult = $this->validateGameData($data, false);
                if(!$validationResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $validationResult['errors']
                    ];
                }

                //chuẩn bị dữ liệu
                $updateData=[];
                $allowedFields = [
                    'console_name',
                    'console_type', 
                    'description', 
                    'image', 
                    'rent_price', 
                    'status'
                ];
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        $updateData[$field] = floatval($data[$field]);
                    }else {
                        $updateData[$field] = trim($data($field)); // Giữ nguyên giá trị cũ nếu không có trong dữ liệu mới
                    }
                }

                $result = $this->gameModel->update($id, $updateData);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Máy chơi game đã được cập nhật thành công.',
                        'console_id' => $id
                    ];
                } else {
                    return [
                        'success' => false, 
                        'message' => 'Không thể cập nhật máy chơi game.'
                    ];
                }


            } catch (Exception $e) {
                error_log("Error updating game: " . $e->getMessage());
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi cập nhật máy chơi game: ' . $e->getMessage()
                ];
            }
        }

        //xóa máy chơi game
        public function deleteGame($id)
        {
            try {
                //kiểm tra id máy chơi game
                if(!is_numeric($id) || $id <= 0) {
                    return [
                        'success' => false,
                        'message' => 'ID không hợp lệ.'
                    ];
                }

                //kiểm tra máy chơi game có tồn tại không
                $console = $this->gameModel->findById($id);
                if (!$console) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy máy chơi game.'
                    ];
                }

                // Kiểm tra console có đang được thuê không
                if ($this->gameModel->isCurrentlyRented($id)) {
                    return [
                        'success' => false,
                        'message' => 'Không thể xóa console đang được thuê'
                    ];
                }
                //xóa máy chơi game
                $result = $this->gameModel->delete($id);
                if ($result) {
                    return [
                        'success' => true,
                        'message' => 'Máy chơi game đã được xóa thành công.'
                    ];
                } else {
                    return [
                        'success' => false, 
                        'message' => 'Không thể xóa máy chơi game.'
                    ];
                }
            } catch (Exception $e) {
                error_log("Error deleting game: " . $e->getMessage());
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi xóa máy chơi game: ' . $e->getMessage()
                ];
            }
        }


        //lấy các loại máy chơi game 
        public function getConsoleType(){

            try {
                //lấy các loại máy chơi game
                $types = $this->gameModel->getUniqueTypes();
                if (!$types) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy loại máy chơi game nào.'
                    ];
                }
                return [
                    'success' => true,
                    'data' => $types,
                    'message' => 'Danh sách loại máy chơi game đã được lấy thành công.'
                ];
            } catch (Exception $e) {
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi lấy danh sách loại máy chơi game: ' . $e->getMessage()
                ];
            }
        }


        //lấy thống kê máy chơi game
        public function getConsoleStats()
        {
            try {
                //lấy thống kê máy chơi game
                $statusStats = $this->gameModel->getStatusStats();


                $stats = [
                    'total' => 0,      
                    'available' => 0,
                    'rented' => 0,
                    'maintenance' => 0
                ];

                foreach ($statusStats as $status){
                    $stats['total']+= $status['count'];
                    $stats['status_breakdown'][$status['status']] = $status['count'];
                    switch ($status['status']) {
                        case 'available':
                            $stats['available'] = $status['count'];
                            break;
                        case 'rented':
                            $stats['rented'] = $status['count'];
                            break;
                        case 'maintenance':
                            $stats['maintenance'] = $status['count'];
                            break;
                        default:
                            // Trạng thái không xác định, có thể bỏ qua hoặc xử lý tùy ý
                            break;
                    }
                }
                

                if (!$stats) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy thống kê máy chơi game nào.'
                    ];
                }
                return [
                    'success' => true,
                    'data' => $stats,
                    'message' => 'Thống kê máy chơi game đã được lấy thành công.'
                ];
            } catch (Exception $e) {
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi lấy thống kê máy chơi game: ' . $e->getMessage()
                ];
            }
        }

        //validate dữ liệu máy chơi game
        public function validateGameData($data, $reuired = true)
        {

            $errors = [];
            // Kiểm tra tên máy chơi game
            if ($reuired || isset($data['console_name'])) {
                if (empty(trim($data['console_name'] ?? ''))) {
                    $errors[] = 'Tên máy chơi game không được để trống.';
                } elseif (strlen($data['console_name']) < 3 || strlen($data['console_name']) > 50) {
                    $errors[] = 'Tên máy chơi game phải từ 3 đến 50 ký tự.';
                }
                
            } 

            //kiểm tra loại máy chơi game
            if ($reuired || isset($data['console_type'])) {
                if (empty(trim($data['console_type'] ?? ''))) {
                    $errors[] = 'Loại máy chơi game không được để trống.';
                } 
            }
            

            // Kiểm tra thoii gian cho thuê
            if ($reuired || isset($data['rent_time'])) {
                if (empty(trim($data['rent_time'] ?? ''))) {
                    $errors[] = 'Thời gian cho thuê không được để trống.';
                } elseif (!is_numeric($data['rent_time']) || $data['rent_time'] <= 0) {
                    $errors[] = 'Thời gian cho thuê phải là một số dương.';
                }
            }

            //kiem tra giá tiền cho thuê
            if ($reuired || isset($data['rent_price'])) {
                if (empty(trim($data['rent_price'] ?? ''))) {
                    $errors[] = 'Giá tiền cho thuê không được để trống.';
                } elseif (!is_numeric($data['rent_price']) || $data['rent_price'] <= 0) {
                    $errors[] = 'Giá tiền cho thuê phải là một số dương.';
                }
            }

            //kiểm tra trạng thái máy chơi game
            if ($reuired || isset($data['status'])) {
                if (empty(trim($data['status'] ?? ''))) {
                    $errors[] = 'Trạng thái máy chơi game không được để trống.';
                } elseif (!in_array($data['status'], ['available', 'unavailable'])) {
                    $errors[] = 'Trạng thái máy chơi game không hợp lệ.';
                }
            }

            //kiểm tra đuong dẫn hình ảnh
            if ($reuired || isset($data['image'])) {
                if (empty(trim($data['image'] ?? ''))) {
                    $errors[] = 'Đường dẫn hình ảnh không được để trống.';
                } elseif (!filter_var($data['image'], FILTER_VALIDATE_URL)) {
                    $errors[] = 'Đường dẫn hình ảnh không hợp lệ.';
                }
            }
            return [
                'success' => empty($errors),
                'errors' => $errors
            ];
        }

        //xử lí dữ liệu console trước khi trả về
        public function processGameData($console)
        {
            if(!$console) {
                return null;
            }
            return [
                'id' => $console['console_id'],
                'console_name' => $console['console_name'],
                'console_type' => $console['console_type'],
                'description' => $console['description'],
                'image_url' => $console['image_url'] ?: '/assets/images/default-console.jpg',
                'rental_price_per_hour' => floatval($console['rental_price_per_hour']),
                'formatted_price' => number_format($console['rental_price_per_hour'], 0, ',', '.') . ' VNĐ/giờ',
                'status' => $console['status'],
                'status_text' => $this->getStatusText($console['status']),
                'is_available' => $console['status'] === 'available',
                'is_currently_rented' => $console['is_currently_rented'] ?? false,
                'created_at' => $console['created_at'] ?? null,
                'updated_at' => $console['updated_at'] ?? null
            ];
        }

        //lấy trạng thái máy chơi game
        private function getStatusText($status) 
        {
            $statusTexts = [
                'available' => 'Có sẵn',
                'rented' => 'Đang thuê',
                'maintenance' => 'Bảo trì'
            ];
            
            return $statusTexts[$status] ?? 'Không xác định';
        }
    }

?>