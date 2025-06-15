<?php
// ===== FIXED GameService.php =====
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
                // FIX: Sửa logic validation - nếu không valid thì return error
                if(!$validationResult['success']){
                    return [
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $validationResult['errors']
                    ];
                }

                // Chuẩn bị dữ liệu
                $consoleData = [
                    'console_name' => trim($data['console_name']),
                    'console_type' => trim($data['console_type']),
                    'description' => trim($data['description'] ?? ''),
                    'image_url' => trim($data['image_url'] ?? ''), // FIX: Sửa key từ 'image' thành 'image_url'
                    'rental_price_per_hour' => floatval($data['rental_price_per_hour']), // FIX: Sửa key
                    'status' => trim($data['status']),
                ];

                $consoleId = $this->gameModel->create($consoleData);
                if ($consoleId) {
                    return [
                        'success' => true,
                        'message' => 'Máy chơi game đã được tạo thành công.',
                        'data' => $consoleId // FIX: Trả về data thay vì console_id
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

        // Lấy máy chơi game có phân trang
        public function getConsoleList($param=[])
        {
            try {
                $page = max(1, intval($param['page'] ?? 1));
                $limit = max(1, intval($param['limit'] ?? 12));
                $type = $param['type'] ?? '';
                $search = $param['search'] ?? '';
                $availableOnly = $param['available_only'] ?? true;

                // Lấy dữ liệu máy chơi game
                if($availableOnly){
                    $consoleData = $this->gameModel->getAvailable($page, $limit, $type, $search);
                    $totalCount = $this->gameModel->count($search, true);
                }
                else{
                    $consoleData = $this->gameModel->getAll($page, $limit, $type, $search);
                    $totalCount = $this->gameModel->count($search, false);
                }
                
                // Xử lí dữ liệu máy chơi game
                $processedConsoles = array_map([$this, 'processGameData'], $consoleData);

                // Tính toán phân trang
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

        // Lấy thông tin chi tiết máy chơi game
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
                        'message' => 'Không tìm thấy máy chơi game.'
                    ];
                }
                
                // Kiểm tra trạng thái máy chơi game
                $isRented = $this->gameModel->isCurrentlyRented($id);
                $console['is_currently_rented'] = $isRented;

                return [
                    'success' => true,
                    'data' => $this->processGameData($console),
                    'message' => 'Lấy thông tin máy chơi game thành công.'
                ];
                
            } catch (Exception $e) {
                return [
                    'success' => false, 
                    'message' => 'Lỗi khi lấy thông tin máy chơi game: ' . $e->getMessage()
                ];
            }
        }

        // Cập nhật thông tin máy chơi game
        public function updateGame($id, $data)
        {
            try {
                // Kiểm tra id máy chơi game
                if(!is_numeric($id) || $id <= 0) {
                    return [
                        'success' => false,
                        'message' => 'ID không hợp lệ.'
                    ];
                }

                // Kiểm tra máy chơi game có tồn tại không
                $console = $this->gameModel->findById($id);
                if (!$console) {
                    return [
                        'success' => false,
                        'message' => 'Không tìm thấy máy chơi game.'
                    ];
                }

                // Validate dữ liệu máy chơi game
                $validationResult = $this->validateGameData($data, false);
                if(!$validationResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'errors' => $validationResult['errors']
                    ];
                }

                // Chuẩn bị dữ liệu
                $updateData = [];
                $allowedFields = [
                    'console_name',
                    'console_type', 
                    'description', 
                    'image_url', // FIX: Sửa từ 'image' thành 'image_url'
                    'rental_price_per_hour', // FIX: Sửa từ 'rent_price' thành 'rental_price_per_hour'
                    'status'
                ];
                
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        if ($field === 'rental_price_per_hour') {
                            $updateData[$field] = floatval($data[$field]);
                        } else {
                            $updateData[$field] = trim($data[$field]); // FIX: Sửa lỗi syntax $data($field) thành $data[$field]
                        }
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

        // Xóa máy chơi game
        public function deleteGame($id)
        {
            try {
                // Kiểm tra id máy chơi game
                if(!is_numeric($id) || $id <= 0) {
                    return [
                        'success' => false,
                        'message' => 'ID không hợp lệ.'
                    ];
                }

                // Kiểm tra máy chơi game có tồn tại không
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
                
                // Xóa máy chơi game
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

        // Lấy các loại máy chơi game 
        public function getConsoleType(){
            try {
                // Lấy các loại máy chơi game
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

        // Lấy thống kê máy chơi game
        public function getConsoleStats()
        {
            try {
                // Lấy thống kê máy chơi game
                $statusStats = $this->gameModel->getStatusStats();

                $stats = [
                    'total' => 0,      
                    'available' => 0,
                    'rented' => 0,
                    'maintenance' => 0,
                    'status_breakdown' => []
                ];

                foreach ($statusStats as $status){
                    $stats['total'] += $status['count'];
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
                            break;
                    }
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

        // Validate dữ liệu máy chơi game
        public function validateGameData($data, $required = true) // FIX: Sửa typo 'reuired' thành 'required'
        {
            $errors = [];
            
            // Kiểm tra tên máy chơi game
            if ($required || isset($data['console_name'])) {
                if (empty(trim($data['console_name'] ?? ''))) {
                    $errors[] = 'Tên máy chơi game không được để trống.';
                } elseif (strlen($data['console_name']) < 3 || strlen($data['console_name']) > 50) {
                    $errors[] = 'Tên máy chơi game phải từ 3 đến 50 ký tự.';
                }
            } 

            // Kiểm tra loại máy chơi game
            if ($required || isset($data['console_type'])) {
                if (empty(trim($data['console_type'] ?? ''))) {
                    $errors[] = 'Loại máy chơi game không được để trống.';
                } 
            }

            // FIX: Bỏ kiểm tra rent_time vì không có field này trong create
            // Kiểm tra giá tiền cho thuê
            if ($required || isset($data['rental_price_per_hour'])) {
                if (empty(trim($data['rental_price_per_hour'] ?? ''))) {
                    $errors[] = 'Giá tiền cho thuê không được để trống.';
                } elseif (!is_numeric($data['rental_price_per_hour']) || $data['rental_price_per_hour'] <= 0) {
                    $errors[] = 'Giá tiền cho thuê phải là một số dương.';
                }
            }

            // Kiểm tra trạng thái máy chơi game
            if ($required || isset($data['status'])) {
                if (empty(trim($data['status'] ?? ''))) {
                    $errors[] = 'Trạng thái máy chơi game không được để trống.';
                } elseif (!in_array($data['status'], ['available', 'rented', 'maintenance'])) { // FIX: Thêm 'rented', 'maintenance'
                    $errors[] = 'Trạng thái máy chơi game không hợp lệ.';
                }
            }

            // Kiểm tra đường dẫn hình ảnh
            if ($required || isset($data['image_url'])) { // FIX: Sửa từ 'image' thành 'image_url'
                if (!empty(trim($data['image_url'] ?? ''))) { // FIX: Cho phép để trống image_url
                    if (!filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
                        $errors[] = 'Đường dẫn hình ảnh không hợp lệ.';
                    }
                }
            }
            
            return [
                'success' => empty($errors),
                'errors' => $errors
            ];
        }

        // Xử lí dữ liệu console trước khi trả về
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

        // Lấy trạng thái máy chơi game
        private function getStatusText($status) 
        {
            $statusTexts = [
                'available' => 'Có sẵn',
                'rented' => 'Đang thuê',
                'maintenance' => 'Bảo trì'
            ];
            
        }
    }
?>