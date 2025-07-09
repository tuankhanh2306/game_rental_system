<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Tạo tài khoản</title>
  <link rel="stylesheet" href="../css/register.css">
</head>
<body>
  <header>
    <h1 class="logo">THUÊ NHANH</h1>
  </header>

  <main class="main-container">
    <div class="register-card">
      <h2 class="title">Tạo tài khoản</h2>
      <form id="register-form">
        <div class="form-group">
          <input type="text" id="userName" placeholder="Tên đăng nhập" required>
        </div>
        <div class="form-group">
          <input type="text" id="fullName" placeholder="Họ và tên" required>
        </div>
        <div class="form-group">
          <input type="tel" id="phone" placeholder="Số điện thoại" required>
        </div>
        <div class="form-group">
          <input type="email" id="email" placeholder="Email" required>
        </div>
        <div class="form-group">
          <input type="password" id="password" placeholder="Mật khẩu" required>
        </div>
        <button type="submit" class="register-button">Đăng ký</button>
      </form>
      <div class="bottom-link">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
      </div>
    </div>
  </main>


  <footer>
  &copy; 2025 Thuê Máy Nhanh
  </footer>
<script src="../js/register.js"></script>
</body>
</html>
