<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thuê Máy Nhanh</title>
  <link rel="stylesheet" href="../css/index.css">
</head>
<body>
  <header>
    <h1>Thuê Máy Nhanh</h1>
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
        <a href="#" id="viewProfile"><i class="fas fa-user"></i> Thông tin cá nhân</a>
        <a href="#" id="changePassword"><i class="fas fa-lock"></i> Đổi mật khẩu</a>
        <a href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
      </div>
    </div>
  </nav>




  <div class="intro">
    <img src="../img/index.png" alt="Banner trang chu"/>
  </div>

  <main>
    <section class="intro">
      <h2>Chào mừng bạn đến với Thuê Máy Nhanh!</h2>
      <p>Chúng tôi cung cấp dịch vụ cho thuê máy chơi game hiện đại với quy trình nhanh chóng, đơn giản và giao hàng tận nơi.</p>
    </section>

    <section class="featured-products">
      <h2>Máy chơi game nổi bật</h2>
      <div class="product-grid" id="gameProductGrid">
      <!-- Sẽ đổ dữ liệu động vào đây -->
      </div>
    </section>

    <section class="why-choose">
      <h2>Tại sao chọn chúng tôi?</h2>
      <ul>
      <li>✔ Thủ tục thuê máy cực đơn giản</li>
      <li>✔ Giao hàng nhanh chóng toàn quốc</li>
      <li>✔ Máy mới, chất lượng, đa dạng lựa chọn</li>
      <li>✔ Hỗ trợ kỹ thuật 24/7</li>
      </ul>
    </section>
  </main>

<footer>
&copy; 2025 Thuê Máy Nhanh - Bài tập lớn Lập trình Web
</footer>
<script src="../js/index.js"></script>
</body>
</html>
