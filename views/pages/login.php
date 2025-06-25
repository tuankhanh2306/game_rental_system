<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng nhập</title>
  <link rel="stylesheet" href="../css/login.css">
</head>
<body>
  <header>
    <h1 class="logo">THUÊ NHANH</h1>
  </header>
  <div class="container">
    <h2 style="text-align: center;">Đăng nhập</h2>
    <form id="login-form" method="post">
      <div class="form-group">
        <label>Username</label>
        <input type="name" id="userName" placeholder="Nhập Username" required />
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input type="password" id="password" placeholder="Nhập mật khẩu" required />
      </div>
      <button type="submit">Đăng nhập</button>
    </form>
    <div class="bottom-link">
      Chưa có tài khoản? <a href="dangky.html">Đăng ký</a>
    </div>
  </div>
  
  <script src="../js/login.js"></script>
</body>
</html>
