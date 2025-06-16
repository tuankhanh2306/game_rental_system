<?php
    namespace services;
    use models\User;
    use Exception;
    class UserService{
        private $userModel;
        public function __construct($database){
            $this->userModel = new User($database);
        }


        //Lấy thông tin người dùng theo ID
        public function getUserbyId($userId){
            try{
                $user = $this->userModel->findById($userId);
                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'Người dùng không tồn tại'
                    ];
                }
                unset($user['password_hash']); // Xóa mật khẩu gốc khỏi kết quả trả về
                return [
                    'success' => true,
                    'user' => $user
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi khi lấy thông tin người dùng',    
                    'error' => $e->getMessage()
                ];
            }
        }

        //cập nhật thông tin người dùng
        //cập nhật thông tin người dùng
        public function updateUser($userId, $data)
        {
            try {
                // Kiểm tra xem người dùng có tồn tại hay không
                $existingUser = $this->userModel->findById($userId);
                if (!$existingUser) {
                    return [
                        'success' => false,
                        'message' => 'Người dùng không tồn tại'
                    ];
                }

                // Validate dữ liệu
                $validation = $this->validateUserData($data, $userId);
                if (!$validation['valid']) {
                    return [
                        'success' => false,
                        'message' => 'Dữ liệu không hợp lệ',
                        'error' => $validation['errors']
                    ];
                }

                // Kiểm tra tên đăng nhập: tìm các user khác (không phải user hiện tại) có username trùng
                if (isset($data['username'])) {
                    $sql = "SELECT user_id FROM users WHERE username = :username AND user_id != :current_user_id LIMIT 1";
                    $stmt = $this->userModel->getDatabase()->prepare($sql);
                    $stmt->bindParam(':username', $data['username']);
                    $stmt->bindParam(':current_user_id', $userId);
                    $stmt->execute();
                    
                    if ($stmt->fetch()) {
                        return [
                            'success' => false,
                            'message' => 'Tên đăng nhập đã tồn tại'
                        ];
                    }
                }

                // Kiểm tra email: tìm các user khác (không phải user hiện tại) có email trùng
                if (isset($data['email'])) {
                    $sql = "SELECT user_id FROM users WHERE email = :email AND user_id != :current_user_id LIMIT 1";
                    $stmt = $this->userModel->getDatabase()->prepare($sql);
                    $stmt->bindParam(':email', $data['email']);
                    $stmt->bindParam(':current_user_id', $userId);
                    $stmt->execute();
                    
                    if ($stmt->fetch()) {
                        return [
                            'success' => false,
                            'message' => 'Email đã được sử dụng'
                        ];
                    }
                }

                // Cập nhật thông tin người dùng (chỉ cập nhật các trường có trong request)
                $updateData = [];
                
                if (isset($data['username'])) {
                    $updateData['username'] = $data['username'];
                }
                if (isset($data['email'])) {
                    $updateData['email'] = $data['email'];
                }
                if (isset($data['full_name'])) {
                    $updateData['full_name'] = $data['full_name'];
                }
                if (isset($data['phone'])) {
                    $updateData['phone'] = $data['phone'];
                }
                if (isset($data['role'])) {
                    $updateData['role'] = $data['role'];
                }
                if (isset($data['status'])) {
                    $updateData['status'] = $data['status'];
                }

                // Thực hiện cập nhật
                if ($this->userModel->update($userId, $updateData)) {
                    return [
                        'success' => true,
                        'message' => 'Cập nhật thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Cập nhật thông tin người dùng không thành công, vui lòng thử lại sau'
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi khi cập nhật thông tin người dùng',
                    'error' => $e->getMessage()
                ];
            }
        }






        //xóa mềm người dùng
        public function deleteUser($userId){
            try{
                //kiểm tra xem người dùng có tồn tại hay không
                $existingUser = $this->userModel->findById($userId);
                if (!$existingUser) {
                    return [
                        'success' => false,
                        'message' => 'Người dùng không tồn tại'
                    ];
                }
                //xóa người dùng
                if($this->userModel->delete($userId)){
                    return [
                        'success' => true,
                        'message' => 'Xóa người dùng thành công'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Xóa người dùng không thành công, vui lòng thử lại sau'
                    ];
                }
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi khi xóa người dùng',    
                    'error' => $e->getMessage()
                ];
            }
        }

        //laasy danh sách người dùng với phân trang
        public function getUsers($page = 1, $limit = 10, $search = ''){
            try {
                $user = $this->userModel->getAll($page, $limit, $search);
                $total = $this->userModel->countAll($search);
                //xoas mật khẩu khỏi kết quả trả về
                foreach ($user as &$u) {
                    unset($u['password_hash']);
                }

                return [
                    'success' => true,
                    'users' => $user,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ];


            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                ];
            }
        }
        // thống kê người dùng theo vai trò
        public function getUserStatsByRole(){
            try {
                $stats = $this->userModel->countByRole();
                return [
                    'success' => true,
                    'stats' => $stats
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                ];
            }
        }

        //doi mật khẩu người dùng
        public function changePassword($userId,$oldPassword, $newPasseord){
            try{
                $user = $this->userModel->findById($userId);
                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'Người dùng không tồn tại'
                    ];
                }
                //kiểm tra mật khẩu cũ

                if (!password_verify($oldPassword, $user['password_hash'])) {
                    return [
                        'success' => false,
                        'message' => 'Mật khẩu cũ không đúng'
                    ];
                }
                //validate mật khẩu mới
                if(strlen($newPasseord) < 6){
                    return [
                        'success' => false,
                        'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'
                    ];
                }

                //mã hóa mật khẩu mới
                $newPasswordHash = password_hash($newPasseord, PASSWORD_BCRYPT);
                // Cập nhật password trực tiếp trong database
                // Giữ nguyên các thông tin khác, chỉ cập nhật password_hash
                $updateData = [
                    'username' => $user['username'],
                    'email' => $user['email'], 
                    'full_name' => $user['full_name'],
                    'phone' => $user['phone'],
                    'role' => $user['role'],
                    'status' => $user['status']
                ];

                // Sử dụng method update có sẵn nhưng cần thêm password_hash
                // Tạm thời xử lý trực tiếp SQL
                $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
                $stmt = $this->userModel->getDatabase()->prepare($sql);
                $stmt->bindParam(':id', $userId);
                $stmt->bindParam(':password_hash', $newPasswordHash);
                
                if ($stmt->execute()) {
                    return [
                        'success' => true,
                        'message' => 'Đổi mật khẩu thành công'
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Không thể cập nhật mật khẩu'
                ];


            }
            catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => 'Đã xảy ra lỗi khi đổi mật khẩu',    
                    'error' => $e->getMessage()
                ];
            }
        }


        // Hàm validate dữ liệu người dùng

        private function validateUserData($data, $userId = null)
        {
            $errors = [];

            // Validate username (chỉ khi có username trong data)
            if (isset($data['username'])) {
                if (empty($data['username'])) {
                    $errors['username'] = 'Tên đăng nhập không được để trống';
                } elseif (strlen($data['username']) < 3) {
                    $errors['username'] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                    $errors['username'] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
                }
                // Không kiểm tra trùng lặp ở đây, sẽ kiểm tra sau khi biết có thay đổi hay không
            }

            // Validate email (chỉ khi có email trong data)
            if (isset($data['email'])) {
                if (empty($data['email'])) {
                    $errors['email'] = 'Email không được để trống';
                } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Email không hợp lệ';
                }
                // Không kiểm tra trùng lặp ở đây, sẽ kiểm tra sau khi biết có thay đổi hay không
            }

            // Validate password (chỉ bắt buộc khi tạo user mới)
            if ($userId === null) {
                if (empty($data['password'])) {
                    $errors['password'] = 'Mật khẩu không được để trống';
                } elseif (strlen($data['password']) < 6) {
                    $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
                }
            } elseif (isset($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
            }

            // Validate full name (chỉ khi có trong data)
            if (isset($data['full_name'])) {
                if (empty($data['full_name'])) {
                    $errors['full_name'] = 'Họ và tên không được để trống';
                } elseif (strlen($data['full_name']) < 2) {
                    $errors['full_name'] = 'Họ và tên phải có ít nhất 2 ký tự';
                }
            }

            // Validate phone (chỉ khi có trong data)
            if (isset($data['phone']) && !empty($data['phone']) && !preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
                $errors['phone'] = 'Số điện thoại không hợp lệ (10-11 số)';
            }

            // Validate role
            $allowedRoles = ['admin', 'user', 'manager'];
            if (isset($data['role']) && !empty($data['role']) && !in_array($data['role'], $allowedRoles)) {
                $errors['role'] = 'Vai trò không hợp lệ. Chỉ chấp nhận: ' . implode(', ', $allowedRoles);
            }

            // Validate status
            $allowedStatuses = ['active', 'inactive', 'banned'];
            if (isset($data['status']) && !empty($data['status']) && !in_array($data['status'], $allowedStatuses)) {
                $errors['status'] = 'Trạng thái không hợp lệ. Chỉ chấp nhận: ' . implode(', ', $allowedStatuses);
            }

            return [
                'valid' => empty($errors),
                'errors' => $errors
            ];
        }

    }
?>