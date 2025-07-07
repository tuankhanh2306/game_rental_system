<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giỏ hàng</title>
  <link rel="stylesheet" href="../css/gameRent.css">
</head>
<body>
  <header>
    <h1 class="logo">THUÊ NHANH</h1>
  </header>

  <nav>
    <a href="index.php">Trang chủ</a>
    <a href="gameConsole.php">Danh sách máy</a>
    <a href="gameRent.php" class="active">Giỏ hàng</a>
    <a href="login.php" class="login">Đăng nhập</a>
    <a href="register.php" class="login">Đăng ký</a>
    <div class="profile-menu">
      <button class="profile-button" id="profileName">Tên người dùng ▼</button>
      <div class="profile-dropdown">
        <a href="#" id="viewProfile">👤 Thông tin cá nhân</a>
        <a href="#" id="changePassword">🔒 Đổi mật khẩu</a>
        <a href="#" id="logoutLink">🚪 Đăng xuất</a>
      </div>
    </div>
  </nav>

  <main>
    <h2>Giỏ hàng của bạn</h2>
    <div class="cart-container">
      <table class="cart-table">
        <thead>
          <tr>
            <th>Ảnh</th>
            <th>Tên máy</th>
            <th>Giá thuê</th>
            <th>Số lượng</th>
            <th>Số giờ</th>
            <th>Thành tiền</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody id="cart-items">
        </tbody>
      </table>

      <div class="cart-total">
        <div class="total-amount">
          Tổng cộng: <span id="total-price">235,000 VND</span>
        </div>
        <a href="thanhtoan.html" class="checkout-btn">Tiến hành thanh toán</a>
      </div>
    </div>
  </main>

  <footer>
    &copy; 2025 Thuê Máy Nhanh - Bài tập lớn Lập trình Web
  </footer>
  <script src="../js/gameRent.js"></script>
</body>
</html>
