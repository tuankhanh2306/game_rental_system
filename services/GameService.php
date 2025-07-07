<?php
namespace services;
use models\Game;
use Exception;

class GameService
{
    private $gameModel;

    public function __construct($data) 
    {
        $this->gameModel = new Game($data);
    }

    // Tạo máy chơi game mới
    public function createGame($data)
    {
        try {
            $validationResult = $this->validateGameData($data);
            if(!$validationResult['success']){
                return [
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationResult['errors']
                ];
            }

            
        
            // Chuẩn bị dữ liệu theo cấu trúc model
            $consoleData = [
                'console_name' => trim($data['console_name']),
                'console_type' => trim($data['console_type']),
                'description' => trim($data['description'] ?? ''),
                'image_url' => trim($data['image_url'] ?? ''), 
                'rental_price_per_hour' => floatval($data['rental_price_per_hour']),
                'quantity' => intval($data['quantity'] ?? 1),
                'available_quantity' => intval($data['available_quantity'] ?? ($data['quantity'] ?? 1)),
                'status' => trim($data['status'] ?? 'available'),
            ];

            $consoleId = $this->gameModel->create($consoleData);
            if ($consoleId) {
                return [
                    'success' => true,
                    'message' => 'Máy chơi game đã được tạo thành công.',
                    'data' => $consoleId
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
    public function getConsoleList($page = 1, $limit = 12, $type = '', $search = '', $availableOnly = false)
    {
        try {
            

            // Lấy dữ liệu máy
            if ($availableOnly) {
                $consoleData = $this->gameModel->getAvailable($page, $limit, $type, $search);
                $totalCount = $this->gameModel->count($type, $search, true);
            } else {
                $consoleData = $this->gameModel->getAll($page, $limit, $type, $search);
                $totalCount = $this->gameModel->count($type, $search, false);
            }

            // Xử lý dữ liệu
            $processedConsoles = array_map([$this, 'processGameData'], $consoleData);

            return [
                'success' => true,
                'data' => $processedConsoles,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit
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

            // Lấy thông tin số lượng
            $quantityInfo = $this->gameModel->getQuantityInfo($id);
            if($quantityInfo) {
                $console = array_merge($console, $quantityInfo);
            }

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

            // Chuẩn bị dữ liệu theo model
            $updateData = [];
            $allowedFields = [
                'console_name',
                'console_type', 
                'description', 
                'image_url', 
                'rental_price_per_hour',
                'quantity',
                'available_quantity',
                'status'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'rental_price_per_hour') {
                        $updateData[$field] = floatval($data[$field]);
                    } elseif (in_array($field, ['quantity', 'available_quantity'])) {
                        $updateData[$field] = intval($data[$field]);
                    } else {
                        $updateData[$field] = trim($data[$field]);
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

    public function getConsoleStats()
    {
        try {
            $statusStats = $this->gameModel->getStatusStats();

            // Khởi tạo dữ liệu tổng
            $stats = [
                'total_consoles' => 0,
                'total_quantity' => 0,
                'total_available' => 0,
                'status_breakdown' => []
            ];

            foreach ($statusStats as $status) {
                // Tính tổng số loại máy (tổng số dòng)
                $stats['total_consoles'] += $status['total_count'];
                // Cộng tổng số máy và số máy còn lại
                $stats['total_quantity'] += $status['total_quantity'];
                $stats['total_available'] += $status['total_available'];

                // Lưu chi tiết theo status
                $stats['status_breakdown'][$status['status']] = [
                    'count' => $status['total_count'],
                    'total_quantity' => $status['total_quantity'],
                    'total_available' => $status['total_available']
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


    // Lấy console phổ biến
    public function getPopularConsoles($limit = 5)
    {
        try {
            $consoles = $this->gameModel->getPopularConsoles($limit);
            $processedConsoles = array_map([$this, 'processGameData'], $consoles);

            return [
                'success' => true,
                'data' => $processedConsoles,
                'message' => 'Danh sách console phổ biến đã được lấy thành công.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi lấy danh sách console phổ biến: ' . $e->getMessage()
            ];
        }
    }

    // Tìm console theo khoảng giá
    public function findByPriceRange($minPrice = 0, $maxPrice = 1000000)
    {
        try {
            $consoles = $this->gameModel->findByPriceRange($minPrice, $maxPrice);
            $processedConsoles = array_map([$this, 'processGameData'], $consoles);

            return [
                'success' => true,
                'data' => $processedConsoles,
                'message' => 'Tìm kiếm console theo giá thành công.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi tìm kiếm console theo giá: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật trạng thái console
    public function updateConsoleStatus($id, $status)
    {
        try {
            if(!is_numeric($id) || $id <= 0) {
                return [
                    'success' => false,
                    'message' => 'ID không hợp lệ.'
                ];
            }

            $result = $this->gameModel->updateStatus($id, $status);
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Trạng thái console đã được cập nhật thành công.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể cập nhật trạng thái console.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi cập nhật trạng thái console: ' . $e->getMessage()
            ];
        }
    }

    // Kiểm tra tình trạng có sẵn
    public function checkAvailability($consoleId, $requestedQuantity = 1)
    {
        try {
            $isAvailable = $this->gameModel->checkAvailability($consoleId, $requestedQuantity);
            return [
                'success' => true,
                'data' => [
                    'is_available' => $isAvailable,
                    'console_id' => $consoleId,
                    'requested_quantity' => $requestedQuantity
                ],
                'message' => $isAvailable ? 'Console có sẵn.' : 'Console không có sẵn với số lượng yêu cầu.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi kiểm tra tình trạng có sẵn: ' . $e->getMessage()
            ];
        }
    }

    // Cập nhật số lượng có sẵn
    public function updateAvailableQuantity($consoleId, $quantity, $operation = 'decrease')
    {
        try {
            $result = $this->gameModel->updateAvailableQuantity($consoleId, $quantity, $operation);
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Số lượng có sẵn đã được cập nhật thành công.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Không thể cập nhật số lượng có sẵn.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi cập nhật số lượng có sẵn: ' . $e->getMessage()
            ];
        }
    }

    // Lấy console sắp hết hàng
    public function getLowStockConsoles($threshold = 2)
    {
        try {
            $consoles = $this->gameModel->getLowStockConsoles($threshold);
            $processedConsoles = array_map([$this, 'processGameData'], $consoles);

            return [
                'success' => true,
                'data' => $processedConsoles,
                'message' => 'Danh sách console sắp hết hàng đã được lấy thành công.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => 'Lỗi khi lấy danh sách console sắp hết hàng: ' . $e->getMessage()
            ];
        }
    }

    // Validate dữ liệu máy chơi game
    public function validateGameData($data, $required = true)
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

        // Kiểm tra giá tiền cho thuê
        if ($required || isset($data['rental_price_per_hour'])) {
            if (empty(trim($data['rental_price_per_hour'] ?? ''))) {
                $errors[] = 'Giá tiền cho thuê không được để trống.';
            } elseif (!is_numeric($data['rental_price_per_hour']) || $data['rental_price_per_hour'] <= 0) {
                $errors[] = 'Giá tiền cho thuê phải là một số dương.';
            }
        }

        // Kiểm tra số lượng
        if ($required || isset($data['quantity'])) {
            if (isset($data['quantity'])) {
                if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
                    $errors[] = 'Số lượng phải là một số nguyên dương.';
                }
            }
        }

        // Kiểm tra số lượng có sẵn
        if ($required || isset($data['available_quantity'])) {
            if (isset($data['available_quantity'])) {
                if (!is_numeric($data['available_quantity']) || $data['available_quantity'] < 0) {
                    $errors[] = 'Số lượng có sẵn phải là một số nguyên không âm.';
                }
                // Kiểm tra available_quantity không được lớn hơn quantity
                if (isset($data['quantity']) && $data['available_quantity'] > $data['quantity']) {
                    $errors[] = 'Số lượng có sẵn không được lớn hơn tổng số lượng.';
                }
            }
        }

        // Kiểm tra trạng thái máy chơi game
        if ($required || isset($data['status'])) {
            if (empty(trim($data['status'] ?? ''))) {
                $errors[] = 'Trạng thái máy chơi game không được để trống.';
            } elseif (!in_array($data['status'], ['available', 'rented', 'maintenance'])) {
                $errors[] = 'Trạng thái máy chơi game không hợp lệ.';
            }
        }

        // Kiểm tra đường dẫn hình ảnh
        if ($required || isset($data['image_url'])) {
            if (!empty(trim($data['image_url'] ?? ''))) {
                $imageUrl = trim($data['image_url']);
                
                // Chấp nhận cả URL và đường dẫn tương đối
                $isValidUrl = filter_var($imageUrl, FILTER_VALIDATE_URL) !== false;
                $isValidPath = !empty($imageUrl) && strlen($imageUrl) > 0;
                
                if (!$isValidUrl && !$isValidPath) {
                    $errors[] = 'Đường dẫn hình ảnh không được để trống.';
                }
                
                // Kiểm tra extension nếu là đường dẫn file
                if (!$isValidUrl) {
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                    $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
                    
                    if (!in_array($extension, $allowedExtensions)) {
                        $errors[] = 'Định dạng hình ảnh không được hỗ trợ. Chỉ chấp nhận: ' . implode(', ', $allowedExtensions);
                    }
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
            'quantity' => intval($console['quantity'] ?? 0),
            'available_quantity' => intval($console['available_quantity'] ?? 0),
            'rented_quantity' => intval($console['rented_quantity'] ?? 0),
            'rental_count' => intval($console['rental_count'] ?? 0),
            'status' => $console['status'],
            'status_text' => $this->getStatusText($console['status']),
            'is_available' => $console['status'] === 'available' && ($console['available_quantity'] ?? 0) > 0,
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
        
        return $statusTexts[$status] ?? 'Không xác định';
    }
}
?>