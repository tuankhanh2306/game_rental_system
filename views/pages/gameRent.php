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
    <a href="giohang.html" class="active">Giỏ hàng</a>
  </nav>

  <main>
    <h2>Giỏ hàng của bạn</h2>
    <div class="cart-container">
      <table>
        <thead>
          <tr>
            <th>Hình ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Giá thuê</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
          </tr>
        </thead>
        <tbody id="cart-items">
          <!-- Dữ liệu sẽ được chèn ở đây bằng JS -->
        </tbody>
      </table>

      <div class="cart-total">
        <p><strong>Tổng cộng:</strong> <span id="total-price">0 VND</span></p>
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
