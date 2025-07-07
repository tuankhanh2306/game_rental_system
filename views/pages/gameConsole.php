<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách máy</title>
    <link rel="stylesheet" href="../css/gameConsole.css"/>
    
</head>
<body>
    <!-- Notification -->
    <div id="notification" class="notification"></div>
    
    <!-- Cart Icon -->
    <button class="cart-icon" onclick="toggleCart()">
        🛒
        <span id="cart-count" class="cart-count" style="display: none;">0</span>
    </button>
    
    <!-- Cart Dropdown -->
    <div id="cart-dropdown" class="cart-dropdown"></div>
    
    <header>
        <h1>Danh sách máy</h1>
    </header>
    <nav>
        <a href="index.php">Trang chủ</a>
        <a href="gameConsole.php">Danh sách máy</a>
        <a href="gameRent.php">Giỏ hàng</a>
        <a href="login.php" class="login">Đăng nhập</a>
        <a href="register.php" class="login">Đăng ký</a>
        <div class="profile-menu">
        <button class="profile-button" id="profileName">Tên người dùng ▼</button>
        <div class="profile-dropdown">
            <a href="#" id="viewProfile"><i class="fas fa-user"></i> 👤 Thông tin cá nhân</a>
            <a href="#" id="changePassword"><i class="fas fa-lock"></i> 🔒 Đổi mật khẩu</a>
            <a href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> 🚪 Đăng xuất</a>
        </div>
        </div>
    </nav>
    <hr />
    <main>
        <h2>Danh sách máy cho thuê</h2>
        <div id="game-container">
            <div class="loading">Đang tải dịch vụ...</div>
        </div>
    </main>
    <script src="../js/gameConsole.js"></script>
    <hr />
    <footer>
        &copy; 2025 Thuê Máy Nhanh - Bài tập lớn Lập trình Web
    </footer>
</body>
</html>
