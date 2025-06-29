document.addEventListener("DOMContentLoaded", async function() {
    const params = new URLSearchParams(window.location.search);
    const consoleId = params.get("console_id");
    const token = localStorage.getItem('token');
    if(!token){
        alert("Vui lòng đăng nhập để tiếp tục");
        window.location.href ="login.php"
    }
  // Nếu có console_id => Thuê ngay 1 máy
    if (consoleId) {
        try {
            const response = await fetch(`/game_rental_system/gameConsoles/${consoleId}`, {
                method: 'GET',
                headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
            });

            const result = await response.json();

            if (!result.success) {
                alert("Không tìm thấy máy chơi game!");
                return;
            }

            const consoleData = result.data;

            // Tạo HTML cho giỏ hàng
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><img src="${consoleData.image_url || '../images/default_console.png'}" alt="${consoleData.console_name}" width="100"></td>
                <td>${consoleData.console_name}</td>
                <td>${consoleData.rental_price_per_hour.toLocaleString()} VND / giờ</td>
                <td>1</td>
                <td>${consoleData.rental_price_per_hour.toLocaleString()} VND</td>
            `;

            document.getElementById("cart-items").appendChild(tr);

            // Hiển thị tổng cộng
            document.getElementById("total-price").textContent = `${consoleData.rental_price_per_hour.toLocaleString()} VND`;

        } catch (error) {
        console.error("Lỗi khi gọi API:", error);
        alert("Có lỗi khi lấy dữ liệu.");
        }
    } 
     // Nếu không có console_id => Hiển thị giỏ hàng từ localStorage
    else {
        const rawCart = localStorage.getItem('gameCart');
        if (!rawCart) {
            alert("Giỏ hàng trống!");
            return;
        }

        let cart;
        try {
            cart = JSON.parse(rawCart);
        } catch (error) {
            console.error("Lỗi parse giỏ hàng:", error);
            alert("Dữ liệu giỏ hàng không hợp lệ.");
            return;
        }

        if (cart.length === 0) {
            alert("Giỏ hàng trống!");
            return;
        }

        let total = 0;

        cart.forEach(item => {
            total += item.price;

            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><img src="${item.image || '../images/default_console.png'}" alt="${escapeHtml(item.name)}" width="100"></td>
                <td>${escapeHtml(item.name)}</td>
                <td>${formatPrice(item.price)} VND / giờ</td>
                <td>1</td>
                <td>${formatPrice(item.price)} VND</td>
            `;

            document.getElementById("cart-items").appendChild(tr);
        });

        document.getElementById("total-price").textContent = `${formatPrice(total)} VND`;
    }
});

// Hàm escape tránh XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Hàm format giá tiền
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}
