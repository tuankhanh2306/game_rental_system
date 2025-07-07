<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng nhập</title>
  <link rel="stylesheet" href="../css/login.css">
</head>
<body>
  <div class="particles"></div>

  <div class="main-container">
    <header>
      <h1 class="logo">THUÊ NHANH</h1>
    </header>
    
    <div class="container">
      <div class="login-card">
        <h2 class="login-title">Đăng nhập</h2>
        <form id="login-form" method="post">
          <div class="form-group">
            <label for="userName">Username</label>
            <input type="text" id="userName" placeholder="Nhập Username" required />
          </div>
          <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" id="password" placeholder="Nhập mật khẩu" required />
          </div>
          <button type="submit" class="login-button">Đăng nhập</button>
        </form>
        <div class="bottom-link">
          Chưa có tài khoản? <a href="register.php">Đăng ký</a>
        </div>
      </div>
    </div>
  </div>
  
  <script src="../js/login.js"></script>
</body>
</html>
