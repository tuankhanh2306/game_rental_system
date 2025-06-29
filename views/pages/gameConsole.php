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
        <a href="index.html">Trang chủ</a>
        <a href="login.html">Đăng nhập / Đăng ký</a>
        <a href="datthue.html">Đặt thuê</a>
        <a href="#" onclick="logout()">Đăng xuất </a>
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
