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
                    <button class="btn-logout" onclick="logout()"> 
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </button>
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
                        <button class="btn btn-secondary" onclick="loadUsersData()">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
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
                    <div class="pagination" style="text-align:center; margin-top:1rem;">
                        <button id="prevPageBtn" class="btn btn-secondary">Trang trước</button>
                        <span id="currentPageDisplay">Trang 1</span>
                        <button id="nextPageBtn" class="btn btn-secondary">Trang sau</button>
                    </div>
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
                        <button class="btn btn-secondary" onclick="loadGamesData()">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
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
                    <div class="pagination" style="text-align:center; margin-top:1rem;">
                        <button id="prevPageBtnGame" class="btn btn-secondary">Trang trước</button>
                        <span id="currentPageDisplayGame">Trang 1</span>
                        <button id="nextPageBtnGame" class="btn btn-secondary">Trang sau</button>
                    </div>
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
                    <table id="rentalsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên máy</th>
                                <th>Người thuê</th>
                                <th>Thời gian bắt đầu</th>
                                <th>Thời gian kết thúc</th>
                                <th>Số lượng</th>
                                <th>Tổng giờ</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dữ liệu sẽ được đổ vào đây -->
                        </tbody>
                    </table>
                    <button id="prevPageBtnRental" class="btn btn-secondary">Trang trước</button>
                    <span id="currentPageDisplayRental">Trang 1</span>
                    <button id="nextPageBtnRental" class="btn btn-secondary">Trang sau</button>
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
                    <label>Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" required>
                </div>
                <div class="form-group">
                    <label>Tên Người dùng</label>
                    <input type="text" class="form-control" id="fullName" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" id="email" required>
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
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select class="form-control" id="role" required>
                        <option value="acive">Hoạt động</option>
                        <option value="inactive">Khóa</option>
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
                        <option value="Sony PlayStation">PlayStation </option>
                        <option value="Console Handheld">Console Handheld</option>
                        <option value="PC Handheld">PC Handheld (Windows Gaming Handheld) </option>
                        <option value="Home Console">Home Console (Microsoft Console, Digital Only)</option>
                        <option value="Console Hybrid">Console Hybrid</option>
                        <option value="Android Handheld">Android Handheld</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hình ảnh</label>
                    <input type="file" class="form-control" id="imageFile" accept="image/*">
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
                <div class="form-group">
                    <label>Số lượng tồn kho</label>
                    <input type="number" class="form-control" id="quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label>Số lượng còn sẵn sàng</label>
                    <input type="number" class="form-control" id="availableQuantity" min="0" required>
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