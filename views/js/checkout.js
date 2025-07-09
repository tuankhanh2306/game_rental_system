// ==================== CHECKOUT MANAGER ==================== //
class CheckoutManager {
    constructor() {
        this.cart = [];
        this.total = 0;
        this.token = localStorage.getItem('token');
        this.userId = localStorage.getItem('id');
        this.apiBase = '/game_rental_system';
        
        this.init();
    }

    // Khởi tạo
    async init() {
        if (!this.checkAuth()) return;
        
        await this.loadCartData();
        this.bindEvents();
    }

    // Kiểm tra xác thực
    checkAuth() {
        if (!this.token || !this.userId) {
            this.showNotification('Vui lòng đăng nhập để tiếp tục', 'error');
            setTimeout(() => {
                window.location.href = "login.php";
            }, 2000);
            return false;
        }
        return true;
    }

    // Tải dữ liệu giỏ hàng
    async loadCartData() {
        try {
            const rawCart = localStorage.getItem('gameCart');
            
            if (!rawCart) {
                this.showEmptyCheckout();
                return;
            }

            this.cart = JSON.parse(rawCart);
            
            if (!Array.isArray(this.cart) || this.cart.length === 0) {
                this.showEmptyCheckout();
                return;
            }

            this.renderCheckoutItems();
            this.showNotification('Đã tải thông tin đơn hàng', 'success');
        } catch (error) {
            console.error("Lỗi khi tải dữ liệu:", error);
            this.showNotification('Có lỗi khi tải thông tin đơn hàng', 'error');
            this.showEmptyCheckout();
        }
    }

    // Render danh sách items cần thanh toán
    renderCheckoutItems() {
        const checkoutItemsElement = document.getElementById("checkout-items");
        
        if (!checkoutItemsElement) {
            console.error("Không tìm thấy element checkout-items");
            return;
        }

        checkoutItemsElement.innerHTML = '';
        this.total = 0;

        this.cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity * item.hours;
            this.total += itemTotal;

            const itemDiv = document.createElement("div");
            itemDiv.className = "checkout-item";
            itemDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="${this.escapeHtml(item.image)}" 
                         alt="${this.escapeHtml(item.name)}" 
                         style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;"
                         onerror="this.src='../images/default_console.png'">
                    <div style="flex: 1;">
                        <p><strong>Tên máy:</strong> ${this.escapeHtml(item.name)}</p>
                        <p><strong>Giá thuê:</strong> ${this.formatPrice(item.price)} VND/giờ</p>
                        <p><strong>Số lượng:</strong> ${item.quantity}</p>
                        <p><strong>Số giờ thuê:</strong> ${item.hours} giờ</p>
                        <p><strong>Thành tiền:</strong> <span style="color: #28a745; font-weight: bold;">${this.formatPrice(itemTotal)} VND</span></p>
                    </div>
                </div>
            `;
            checkoutItemsElement.appendChild(itemDiv);
        });

        // Thêm tổng cộng
        const totalDiv = document.createElement("div");
        totalDiv.className = "checkout-total";
        totalDiv.innerHTML = `
            <div style="text-align: right; padding: 20px 0; border-top: 2px solid #28a745; margin-top: 20px;">
                <h3 style="color: #28a745; margin: 0;">
                    Tổng cộng: ${this.formatPrice(this.total)} VND
                </h3>
            </div>
        `;
        checkoutItemsElement.appendChild(totalDiv);
    }

    // Hiển thị trang trống khi không có items
    showEmptyCheckout() {
        const checkoutContainer = document.querySelector('.checkout-container');
        if (checkoutContainer) {
            checkoutContainer.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <h3>🛒 Không có sản phẩm để thanh toán</h3>
                    <p>Giỏ hàng của bạn đang trống</p>
                    <a href="gameConsole.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;">
                        Quay lại mua sắm
                    </a>
                </div>
            `;
        }
    }

    // Xử lý thanh toán
    async processCheckout() {
        const paymentMethod = document.getElementById('payment-method').value;
        
        if (!paymentMethod) {
            this.showNotification('Vui lòng chọn phương thức thanh toán', 'error');
            return;
        }

        if (this.cart.length === 0) {
            this.showNotification('Giỏ hàng trống', 'error');
            return;
        }

        // Hiển thị loading
        const confirmBtn = document.getElementById('confirmCheckoutBtn');
        const originalText = confirmBtn.textContent;
        confirmBtn.textContent = 'Đang xử lý...';
        confirmBtn.disabled = true;

        try {
            

            // Chuẩn bị dữ liệu để gửi
            const checkoutData = this.prepareCheckoutData(paymentMethod);
            
            // Gửi request tạo đơn thuê
            const response = await fetch(`/game_rental_system/rentals`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(checkoutData)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Thanh toán thành công
                this.showNotification('Đặt thuê thành công!', 'success');
                
                // Xóa giỏ hàng
                localStorage.removeItem('gameCart');
                
                // Hiển thị thông tin đơn hàng
                this.showOrderSuccess(result.data);
                setTimeout(() => {
                    window.location.href = 'gameConsole.php';
                }, 1000);
                
            } else {
                throw new Error(result.message || 'Có lỗi xảy ra khi tạo đơn thuê');
            }

        } catch (error) {
            console.error('Lỗi thanh toán:', error);
            this.showNotification(error.message || 'Có lỗi xảy ra khi thanh toán', 'error');
        } finally {
            // Khôi phục button
            confirmBtn.textContent = originalText;
            confirmBtn.disabled = false;
        }
    }

    // Chuẩn bị dữ liệu checkout
    prepareCheckoutData(paymentMethod) {
        const now = new Date();
        const items = this.cart.map(item => {
            const rentalStart = new Date(now.getTime() + 60 * 60 * 1000);
            const rentalEnd = new Date(rentalStart.getTime() + item.hours * 60 * 60 * 1000);

            return {
                console_id: item.id,
                rental_start: this.formatDateTime(rentalStart),
                rental_end: this.formatDateTime(rentalEnd),     
                quantity: item.quantity,
                notes: `Thuê ${item.quantity} máy ${item.name} trong ${item.hours} giờ`
            };
        });

        return {
            payment_method: paymentMethod,
            items: items
        };
    }




    // Format datetime cho MySQL
    formatDateTime(date) {
        // Format yyyy-mm-dd hh:mm:ss theo local time
        const pad = (n) => n.toString().padStart(2, '0');
        return (
            date.getFullYear() +
            '-' +
            pad(date.getMonth() + 1) +
            '-' +
            pad(date.getDate()) +
            ' ' +
            pad(date.getHours()) +
            ':' +
            pad(date.getMinutes()) +
            ':' +
            pad(date.getSeconds())
        );
    }


    // Hiển thị thông báo thành công
    showOrderSuccess(orderData) {
        const checkoutContainer = document.querySelector('.checkout-container');
        if (checkoutContainer) {
            checkoutContainer.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <div style="color: #28a745; font-size: 60px; margin-bottom: 20px;">✅</div>
                    <h2 style="color: #28a745;">Đặt thuê thành công!</h2>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">
                        <h4>Thông tin đơn thuê:</h4>
                        <p><strong>Mã thanh toán:</strong> #${orderData.payment_id}</p>
                        <p><strong>Tổng tiền:</strong> ${this.formatPrice(orderData.total_amount)} VND</p>
                        <p><strong>Số lượng máy:</strong> ${orderData.rentals.length}</p>
                        <p><strong>Trạng thái:</strong> <span style="color: #ffc107;">Chờ xác nhận</span></p>
                    </div>
                    <div style="margin-top: 30px;">
                        <a href="gameConsole.php" style="display: inline-block; padding: 12px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 0 10px;">
                            Tiếp tục thuê máy
                        </a>
                        <a href="index.php" style="display: inline-block; padding: 12px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin: 0 10px;">
                            Về trang chủ
                        </a>
                    </div>
                </div>
            `;
        }
    }

    // Bind events
    bindEvents() {
        const confirmBtn = document.getElementById('confirmCheckoutBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.processCheckout());
        }

        // Xử lý thay đổi phương thức thanh toán
        const paymentSelect = document.getElementById('payment-method');
        if (paymentSelect) {
            paymentSelect.addEventListener('change', (e) => {
                const method = e.target.value;
                if (method === 'bank_transfer') {
                    this.showBankInfo();
                }
            });
        }
    }

    // Hiển thị thông tin chuyển khoản
    showBankInfo() {
        let bankInfoDiv = document.getElementById('bank-info');
        if (!bankInfoDiv) {
            bankInfoDiv = document.createElement('div');
            bankInfoDiv.id = 'bank-info';
            document.getElementById('payment-method').parentNode.appendChild(bankInfoDiv);
        }

        bankInfoDiv.innerHTML = `
            <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; margin-top: 10px; border-left: 4px solid #007bff;">
                <h4 style="margin-top: 0; color: #007bff;">Thông tin chuyển khoản:</h4>
                <p><strong>Ngân hàng:</strong> MBBank</p>
                <p><strong>Số tài khoản:</strong> 0859120166</p>
                <p><strong>Chủ tài khoản:</strong> CONG TY THUE MAY NHANH</p>
                <p><strong>Số tiền:</strong> ${this.formatPrice(this.total)} VND</p>
                <p><strong>Nội dung:</strong> Thanh toan don thue [Mã đơn sẽ được tạo sau khi xác nhận]</p>
                <p style="color: #dc3545; font-style: italic;">* Vui lòng chuyển khoản đúng số tiền và nội dung để đơn hàng được xử lý nhanh chóng</p>
            </div>
        `;
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price || 0);
    }

    showNotification(message, type = 'info') {
        // Sử dụng hàm showNotification từ cartUtils.js
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            alert(message);
        }
    }
}

// Khởi tạo khi DOM ready
let checkoutManager;
document.addEventListener("DOMContentLoaded", function() {
    checkoutManager = new CheckoutManager();
    window.checkoutManager = checkoutManager;
});

