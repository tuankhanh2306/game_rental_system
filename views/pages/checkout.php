<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Thanh toán - Thuê Máy Nhanh</title>
  <link rel="stylesheet" href="../css/gameRent.css">
  <style>
    body { font-family: sans-serif; }
    .checkout-container { max-width: 800px; margin: 30px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; background: #f9f9f9; }
    .checkout-item { border-bottom: 1px solid #ddd; padding: 15px 0; }
    .checkout-item:last-child { border-bottom: none; }
    .checkout-item p { margin: 5px 0; }
    .checkout-actions { text-align: right; margin-top: 20px; }
    .checkout-btn  { padding: 12px 25px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .checkout-actions a#back {
      display: inline-block;
      margin-right: 10px;
      padding: 12px 25px;
      background: #6c757d;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      transition: background 0.3s ease;
    }

    .checkout-actions a#back:hover {
      background: #5a6268;
    }

    .payment-select { width: 100%; padding: 8px; margin-top: 10px; }
  </style>
</head>
<body>
  <header>
    <h1 class="logo">THANH TOÁN</h1>
  </header>

  <main class="checkout-container">
    <h2>Xác nhận đơn thuê</h2>
    <div id="checkout-items"></div>

    <div>
      <label><strong>Hình thức thanh toán:</strong></label>
      <select id="payment-method" class="payment-select">
        <option value="">-- Chọn phương thức --</option>
        <option value="cash">Tiền mặt khi nhận máy</option>
        <option value="bank_transfer">Chuyển khoản ngân hàng</option>
      </select>
    </div>

    <div class="checkout-actions">
      <a href="gameConsole.php" id="back">Quay lại danh sách máy</a>
      <button id="confirmCheckoutBtn" class="checkout-btn">Xác nhận thanh toán</button>
    </div>
  </main>

  <footer>
    &copy; 2025 Thuê Máy Nhanh - Bài tập lớn Lập trình Web
  </footer>

  <script src="../js/cartUntils.js"></script>
  <script src="../js/checkout.js"></script>
</body>
</html>
