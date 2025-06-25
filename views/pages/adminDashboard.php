<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quản Lý Thuê Máy Chơi Game</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/adminDashboard.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-gamepad"></i> GameRent</h1>
            </div>
            <ul class="sidebar-nav">
                <li><a href="#" class="nav-link active" data-section="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="#" class="nav-link" data-section="users">
                    <i class="fas fa-users"></i> Người dùng
                </a></li>
                <li><a href="#" class="nav-link" data-section="games">
                    <i class="fas fa-gamepad"></i> Máy chơi game
                </a></li>
                <li><a href="#" class="nav-link" data-section="rentals">
                    <i class="fas fa-calendar-alt"></i> Đơn thuê
                </a></li>
                <li><a href="#" class="nav-link" data-section="history">
                    <i class="fas fa-history"></i> Lịch sử
                </a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="welcome-text">
                    <h2>Chào mừng, Admin!</h2>
                    <p>Quản lý hệ thống thuê máy chơi game</p>
                </div>
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div>
                        <div><strong>Admin</strong></div>
                        <div style="font-size: 0.9rem; color: #666;">Quản trị viên</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Section -->
            <div id="dashboard" class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="totalUsers">0</div>
                                <div class="stat-label">Tổng người dùng</div>
                            </div>
                            <div class="stat-icon users">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="totalGames">0</div>
                                <div class="stat-label">Tổng máy chơi game</div>
                            </div>
                            <div class="stat-icon games">
                                <i class="fas fa-gamepad"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="totalRentals">0</div>
                                <div class="stat-label">Tổng đơn thuê</div>
                            </div>
                            <div class="stat-icon rentals">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value" id="totalRevenue">0đ</div>
                                <div class="stat-label">Tổng doanh thu</div>
                            </div>
                            <div class="stat-icon revenue">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Section -->
            <div id="users" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Quản lý người dùng</h2>
                    <button class="btn btn-primary" onclick="openModal('userModal')">
                        <i class="fas fa-plus"></i> Thêm người dùng
                    </button>
                </div>
                
                <div class="search-filter-bar">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Tìm kiếm người dùng..." id="userSearch">
                    </div>
                </div>

                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên người dùng</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Games Section -->
            <div id="games" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Quản lý máy chơi game</h2>
                    <button class="btn btn-primary" onclick="openModal('gameModal')">
                        <i class="fas fa-plus"></i> Thêm máy mới
                    </button>
                </div>
                
                <div class="search-filter-bar">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Tìm kiếm máy chơi game..." id="gameSearch">
                    </div>
                    <div class="filter-group">
                        <label>Loại:</label>
                        <select class="form-control" id="gameTypeFilter">
                            <option value="">Tất cả</option>
                            <option value="PS5">PS5</option>
                            <option value="Xbox">Xbox</option>
                            <option value="Nintendo">Nintendo</option>
                            <option value="PC">PC</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table id="gamesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên máy</th>
                                <th>Loại</th>
                                <th>Giá thuê/giờ</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Rentals Section -->
            <div id="rentals" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Quản lý đơn thuê</h2>
                    <button class="btn btn-primary" onclick="openModal('rentalModal')">
                        <i class="fas fa-plus"></i> Tạo đơn thuê
                    </button>
                </div>
                
                <div class="search-filter-bar">
                    <div class="search-box">
                        <input type="text" class="form-control" placeholder="Tìm kiếm đơn thuê..." id="rentalSearch">
                    </div>
                    <div class="filter-group">
                        <label>Trạng thái:</label>
                        <select class="form-control" id="rentalStatusFilter">
                            <option value="">Tất cả</option>
                            <option value="pending">Chờ xử lý</option>
                            <option value="active">Đang thuê</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table id="rentalsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Người thuê</th>
                                <th>Máy chơi game</th>
                                <th>Thời gian bắt đầu</th>
                                <th>Thời gian kết thúc</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- History Section -->
            <div id="history" class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Lịch sử hoạt động</h2>
                </div>
                
                <div class="search-filter-bar">
                    <div class="filter-group">
                        <label>Hành động:</label>
                        <select class="form-control" id="historyActionFilter">
                            <option value="">Tất cả</option>
                            <option value="create">Tạo mới</option>
                            <option value="update">Cập nhật</option>
                            <option value="delete">Xóa</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Từ ngày:</label>
                        <input type="date" class="form-control" id="historyFromDate">
                    </div>
                    <div class="filter-group">
                        <label>Đến ngày:</label>
                        <input type="date" class="form-control" id="historyToDate">
                    </div>
                </div>

                <div class="table-container">
                    <table id="historyTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Đơn thuê</th>
                                <th>Hành động</th>
                                <th>Người thực hiện</th>
                                <th>Thời gian</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">Thêm người dùng mới</h3>
                <span class="close" onclick="closeModal('userModal')">&times;</span>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label>Tên người dùng</label>
                    <input type="text" class="form-control" id="username" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" id="email" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" class="form-control" id="password">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" class="form-control" id="phone">
                </div>
                <div class="form-group">
                    <label>Vai trò</label>
                    <select class="form-control" id="role" required>
                        <option value="user">Người dùng</option>
                        <option value="admin">Quản trị viên</option>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Game Modal -->
    <div id="gameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="gameModalTitle">Thêm máy chơi game mới</h3>
                <span class="close" onclick="closeModal('gameModal')">&times;</span>
            </div>
            <form id="gameForm">
                <input type="hidden" id="gameId">
                <div class="form-group">
                    <label>Tên máy</label>
                    <input type="text" class="form-control" id="consoleName" required>
                </div>
                <div class="form-group">
                    <label>Loại máy</label>
                    <select class="form-control" id="consoleType" required>
                        <option value="">Chọn loại máy</option>
                        <option value="PS5">PlayStation 5</option>
                        <option value="Xbox">Xbox Series X/S</option>
                        <option value="Nintendo">Nintendo Switch</option>
                        <option value="PC">PC Gaming</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giá thuê/giờ (VNĐ)</label>
                    <input type="number" class="form-control" id="rentalPrice" required min="0">
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select class="form-control" id="gameStatus" required>
                        <option value="available">Có sẵn</option>
                        <option value="rented">Đang được thuê</option>
                        <option value="maintenance">Bảo trì</option>
                        <option value="inactive">Không hoạt động</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea class="form-control" id="description" rows="3"></textarea>
                </div>
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('gameModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rental Modal -->
    <div id="rentalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="rentalModalTitle">Tạo đơn thuê mới</h3>
                <span class="close" onclick="closeModal('rentalModal')">&times;</span>
            </div>
            <form id="rentalForm">
                <input type="hidden" id="rentalId">
                <div class="form-group">
                    <label>Người thuê</label>
            
                    <select class="form-control" id="rentalUserId" required>
                        <option value="">Chọn người thuê</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Máy chơi game</label>
                    <select class="form-control" id="rentalGameId" required>
                        <option value="">Chọn máy chơi game</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Thời gian bắt đầu</label>
                    <input type="datetime-local" class="form-control" id="startTime" required>
                </div>
                <div class="form-group">
                    <label>Số giờ thuê</label>
                    <input type="number" class="form-control" id="rentHours" required min="1" max="24">
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea class="form-control" id="rentalNotes" rows="3"></textarea>
                </div>
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('rentalModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">Tạo đơn thuê</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading -->
    <div id="loading" class="loading">
        <div class="spinner"></div>
        <p>Đang tải dữ liệu...</p>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification">
        <span id="notificationText"></span>
    </div>
    <script src="../js/adminDashboard.js"></script>
</body>
</html>