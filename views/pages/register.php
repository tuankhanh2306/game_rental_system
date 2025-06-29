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
    <h2 class="title">Tạo tài khoản</h2>
    <form id="register-form">
      <input type="text" id="userName" placeholder="Tên đăng nhập" required>
      <input type="text" id="fullName" placeholder="Họ và tên" required>
      <input type="tel" id="phone" placeholder="Số điện thoại" required>
      <input type="email" id="email" placeholder="Email" required>
      <input type="password" id="password" placeholder="Mật khẩu" required>
      <button type="submit">Đăng ký</button>
    </form>
  </main>

  <footer>
  &copy; 2025 Thuê Máy Nhanh
  </footer>
<script src="../js/register.js"></script>
</body>
</html>
