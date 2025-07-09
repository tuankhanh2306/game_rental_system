// ==================== QUẢN LÝ GIỎ HÀNG ==================== //

class CartManager {
    constructor() {
        this.cart = [];
        this.total = 0;
        this.token = localStorage.getItem('token');
        this.apiBase = '/game_rental_system';
        
        this.init();
    }

    // Khởi tạo
    async init() {
        if (!this.checkAuth()) return;
        
        await this.loadCart();
        this.bindEvents();
    }

    // Kiểm tra xác thực
    checkAuth() {
        if (!this.token) {
            this.showNotification('Vui lòng đăng nhập để tiếp tục', 'error');
            setTimeout(() => {
                window.location.href = "login.php";
            }, 2000);
            return false;
        }
        return true;
    }

    // Tải giỏ hàng
    async loadCart() {
        const params = new URLSearchParams(window.location.search);
        const consoleId = params.get("console_id");
        
        try {
            if (consoleId) {
                await this.addSingleConsole(consoleId);
            } else {
                await this.loadStoredCart();
            }
        } catch (error) {
            console.error("Lỗi khi tải giỏ hàng:", error);
            this.showNotification('Có lỗi khi tải giỏ hàng', 'error');
        }
    }

    // Thêm một máy chơi game từ URL
    async addSingleConsole(consoleId) {
        try {
            const response = await fetch(`${this.apiBase}/gameConsoles/${consoleId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Không tìm thấy máy chơi game');
            }

            const consoleData = result.data;
            
            // Tạo item cho giỏ hàng
            const cartItem = {
                id: consoleData.console_id,
                name: consoleData.console_name,
                price: parseFloat(consoleData.rental_price_per_hour),
                image: consoleData.image_url || '../images/default_console.png',
                quantity: 1,
                hours: 1,
                available: consoleData.availability_status === 'available'
            };

            this.cart = [cartItem];
            this.renderCart();
            this.showNotification('Đã thêm máy vào giỏ hàng', 'success');

        } catch (error) {
            console.error("Lỗi khi thêm máy:", error);
            this.showNotification(error.message || 'Có lỗi khi thêm máy vào giỏ hàng', 'error');
        }
    }

    // Tải giỏ hàng từ localStorage
    async loadStoredCart() {
        try {
            const rawCart = localStorage.getItem('gameCart');
            
            if (!rawCart) {
                this.showEmptyCart();
                return;
            }

            this.cart = JSON.parse(rawCart);
            
            if (!Array.isArray(this.cart) || this.cart.length === 0) {
                this.showEmptyCart();
                return;
            }

            // Validate và làm sạch dữ liệu
            this.cart = this.cart.filter(item => this.validateCartItem(item));
            
            if (this.cart.length === 0) {
                this.showEmptyCart();
                return;
            }

            this.renderCart();
            this.showNotification('Đã tải giỏ hàng thành công', 'success');

        } catch (error) {
            console.error("Lỗi khi tải giỏ hàng:", error);
            this.showNotification('Dữ liệu giỏ hàng không hợp lệ', 'error');
            localStorage.removeItem('gameCart');
            this.showEmptyCart();
        }
    }

    // Validate item trong giỏ hàng
    validateCartItem(item) {
        return item && 
               item.id && 
               item.name && 
               !isNaN(item.price) && 
               item.price > 0 &&
               !isNaN(item.quantity) && 
               item.quantity > 0 &&
               !isNaN(item.hours) && 
               item.hours > 0;
    }

    // Render giỏ hàng
    renderCart() {
        const cartItemsElement = document.getElementById("cart-items");
        const totalPriceElement = document.getElementById("total-price");
        
        if (!cartItemsElement || !totalPriceElement) {
            console.error("Không tìm thấy elements cần thiết");
            return;
        }

        // Clear existing content
        cartItemsElement.innerHTML = '';
        this.total = 0;

        if (this.cart.length === 0) {
            this.showEmptyCart();
            return;
        }

        // Render từng item
        this.cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity * item.hours;
            this.total += itemTotal;

            const tr = document.createElement("tr");
            tr.dataset.index = index;
            tr.innerHTML = `
                <td>
                    <img src="${this.escapeHtml(item.image)}" 
                         alt="${this.escapeHtml(item.name)}" 
                         class="cart-item-img"
                         onerror="this.src='../images/default_console.png'">
                </td>
                <td class="cart-item-name">${this.escapeHtml(item.name)}</td>
                <td class="cart-item-price">${this.formatPrice(item.price)} VND/giờ</td>
                <td>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="cartManager.changeQuantity(${index}, -1)">-</button>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" max="10" 
                               onchange="cartManager.updateQuantity(${index}, this.value)">
                        <button class="quantity-btn" onclick="cartManager.changeQuantity(${index}, 1)">+</button>
                    </div>
                </td>
                <td>
                    <input type="number" class="hours-input" value="${item.hours}" min="1" max="24" 
                           onchange="cartManager.updateHours(${index}, this.value)">
                </td>
                <td class="cart-item-price">${this.formatPrice(itemTotal)} VND</td>
                <td>
                    <button class="remove-btn" onclick="cartManager.removeItem(${index})">Xóa</button>
                </td>
            `;

            cartItemsElement.appendChild(tr);
        });

        // Cập nhật tổng tiền
        totalPriceElement.textContent = `${this.formatPrice(this.total)} VND`;
        
        // Lưu vào localStorage
        this.saveCart();
    }

    // Thay đổi số lượng
    changeQuantity(index, change) {
        if (index < 0 || index >= this.cart.length) return;
        
        const newQuantity = this.cart[index].quantity + change;
        
        if (newQuantity >= 1 && newQuantity <= 10) {
            this.cart[index].quantity = newQuantity;
            this.renderCart();
            this.showNotification('Đã cập nhật số lượng', 'info');
        }
    }

    // Cập nhật số lượng từ input
    updateQuantity(index, value) {
        const quantity = parseInt(value);
        
        if (isNaN(quantity) || quantity < 1 || quantity > 10) {
            this.showNotification('Số lượng phải từ 1 đến 10', 'error');
            this.renderCart();
            return;
        }
        
        this.cart[index].quantity = quantity;
        this.renderCart();
        this.showNotification('Đã cập nhật số lượng', 'info');
    }

    // Cập nhật số giờ
    updateHours(index, value) {
        const hours = parseInt(value);
        
        if (isNaN(hours) || hours < 1 || hours > 24) {
            this.showNotification('Số giờ phải từ 1 đến 24', 'error');
            this.renderCart();
            return;
        }
        
        this.cart[index].hours = hours;
        this.renderCart();
        this.showNotification('Đã cập nhật số giờ', 'info');
    }

    // Xóa item khỏi giỏ hàng
    removeItem(index) {
        if (index < 0 || index >= this.cart.length) return;
        
        const itemName = this.cart[index].name;
        
        if (confirm(`Bạn có chắc muốn xóa "${itemName}" khỏi giỏ hàng?`)) {
            this.cart.splice(index, 1);
            this.renderCart();
            this.showNotification('Đã xóa sản phẩm khỏi giỏ hàng', 'success');
            
            if (this.cart.length === 0) {
                this.showEmptyCart();
            }
        }
    }

    // Hiển thị giỏ hàng trống
    showEmptyCart() {
        const cartContainer = document.querySelector('.cart-container');
        if (cartContainer) {
            cartContainer.innerHTML = `
                <div class="empty-cart">
                    <h3>🛒 Giỏ hàng trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                    <a href="gameConsole.php" class="continue-shopping">Tiếp tục mua sắm</a>
                </div>
            `;
        }
    }

    // Lưu giỏ hàng vào localStorage
    saveCart() {
        try {
            localStorage.setItem('gameCart', JSON.stringify(this.cart));
        } catch (error) {
            console.error("Lỗi khi lưu giỏ hàng:", error);
            this.showNotification('Không thể lưu giỏ hàng', 'error');
        }
    }

    // Xóa toàn bộ giỏ hàng
    clearCart() {
        if (confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) {
            this.cart = [];
            localStorage.removeItem('gameCart');
            this.showEmptyCart();
            this.showNotification('Đã xóa toàn bộ giỏ hàng', 'success');
        }
    }

    // Bind events
    bindEvents() {
        // Nút xóa toàn bộ giỏ hàng
        const clearButton = document.getElementById('clear-cart');
        if (clearButton) {
            clearButton.addEventListener('click', () => this.clearCart());
        }

        // Nút tiếp tục mua sắm
        const continueButton = document.getElementById('continue-shopping');
        if (continueButton) {
            continueButton.addEventListener('click', () => {
                window.location.href = 'gameConsole.php';
            });
        }

        // Prevent form submission on Enter key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && (e.target.classList.contains('quantity-input') || e.target.classList.contains('hours-input'))) {
                e.preventDefault();
            }
        });
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

    // Hiển thị thông báo
    showNotification(message, type = 'info') {
        // Tạo container nếu chưa có
        let container = document.querySelector('.notification-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }

        // Tạo notification
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        container.appendChild(notification);

        // Tự động xóa sau 3 giây
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 3000);
    }

    // Lấy thông tin giỏ hàng để checkout
    getCartSummary() {
        return {
            items: this.cart,
            total: this.total,
            itemCount: this.cart.reduce((sum, item) => sum + item.quantity, 0),
            totalHours: this.cart.reduce((sum, item) => sum + (item.quantity * item.hours), 0)
        };
    }
}

// Utility functions for storage and API calls
const storage = {
    get: (key) => localStorage.getItem(key),
    set: (key, value) => localStorage.setItem(key, value),
    remove: (key) => localStorage.removeItem(key),
    clear: () => ['token', 'id', 'fullname'].forEach(k => localStorage.removeItem(k))
};

const apiCall = async (url, options = {}) => {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${storage.get('token') || ''}`
        }
    };
    const res = await fetch(url, { ...defaultOptions, ...options });
    return await res.json();
};

// Notification system
const showNotification = (message, type = 'success') => {
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:10000;';
        document.body.appendChild(container);
    }
    const notif = document.createElement('div');
    const colors = { success: '#28a745', error: '#dc3545', info: '#17a2b8', warning: '#ffc107' };
    notif.textContent = message;
    notif.style.cssText = `
        padding:12px 20px;margin:5px 0;border-radius:4px;color:white;
        font-weight:bold;transition:opacity 0.3s ease;
        background:${colors[type] || colors.info};
    `;
    container.appendChild(notif);
    setTimeout(() => {
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 300);
    }, 2700);
};

// Modal creation utility
const createModal = (html) => {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    const content = document.createElement('div');
    content.className = 'modal-content';
    content.innerHTML = html;
    const closeBtn = document.createElement('span');
    closeBtn.className = 'modal-close';
    closeBtn.textContent = '×';
    closeBtn.onclick = () => overlay.remove();
    content.appendChild(closeBtn);
    overlay.appendChild(content);
    document.body.appendChild(overlay);
    overlay.onclick = (e) => {
        if (e.target === overlay) overlay.remove();
    };
};

// HTML escape utility
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Check if user is logged in
function isLoggedIn() {
    const token = storage.get('token');
    return !!token && token.trim() !== '';
}

// Update authentication menu visibility
function updateAuthMenu() {
    const loginLinks = document.querySelectorAll('.login');
    const profileMenu = document.querySelector('.profile-menu');

    if (isLoggedIn()) {
        loginLinks.forEach(link => link.style.display = 'none');
        if (profileMenu) profileMenu.style.display = 'inline-block';
    } else {
        loginLinks.forEach(link => link.style.display = 'inline-block');
        if (profileMenu) profileMenu.style.display = 'none';
    }
}

// Load user information
async function loadInfoUser() {
    const userId = storage.get('id');
    if (!userId) return;

    try {
        const data = await apiCall(`/game_rental_system/users/${userId}`);

        if (data.success) {
            const user = data.data;
            storage.set('fullname', user.full_name);

            const profileName = document.getElementById('profileName');
            if (profileName) profileName.textContent = user.full_name + ' ▼';

            updateAuthMenu();
        } else {
            showNotification('Lỗi tải thông tin người dùng: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Không thể tải thông tin người dùng.', 'error');
    }
}

// Profile actions object
const profileActions = {
    // View user profile
    viewProfile: async () => {
        const userId = storage.get('id');
        if (!userId) return;
        
        try {
            const data = await apiCall(`/game_rental_system/users/${userId}`);
            if (data.success) {
                const user = data.data;
                createModal(`
                    <h3>Thông tin người dùng</h3>
                    <p><strong>Tên:</strong> ${escapeHtml(user.full_name)}</p>
                    <p><strong>Tên đăng nhập:</strong> ${escapeHtml(user.username)}</p>
                    <p><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                    <p><strong>Điện thoại:</strong> ${escapeHtml(user.phone)}</p>
                `);
            } else {
                showNotification('Lỗi: ' + data.message, 'error');
            }
        } catch (error) {
            showNotification('Không thể tải thông tin.', 'error');
        }
    },
    
    // Change password
    changePassword: () => {
        createModal(`
            <h3>Đổi mật khẩu</h3>
            <form id="changePasswordForm">
                <input type="password" name="currentPassword" placeholder="Mật khẩu hiện tại" required style="width:100%;margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:4px;">
                <input type="password" name="newPassword" placeholder="Mật khẩu mới" required style="width:100%;margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:4px;">
                <input type="password" name="confirmPassword" placeholder="Xác nhận mật khẩu mới" required style="width:100%;margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:4px;">
                <button type="submit" style="margin-top:10px;padding:10px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;">Xác nhận</button>
            </form>
        `);
        
        document.getElementById('changePasswordForm').onsubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const [current, newPass, confirm] = ['currentPassword', 'newPassword', 'confirmPassword']
                .map(name => form[name].value.trim());
            
            if (newPass !== confirm) {
                showNotification('Mật khẩu mới không khớp.', 'error');
                return;
            }
            
            try {
                const data = await apiCall(`/game_rental_system/users/change-password`, {
                    method: 'POST',
                    body: JSON.stringify({ old_password: current, new_password: newPass }),
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${storage.get('token')}`
                    }
                });
                
                if (data.success) {
                    showNotification('Đổi mật khẩu thành công.', 'success');
                    document.querySelector('.modal-overlay').remove();
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                showNotification('Đổi mật khẩu thất bại.', 'error');
            }
        };
    }
};

// Logout function
const logout = () => {
    storage.clear();
    localStorage.removeItem('gameCart');
    showNotification('Đăng xuất thành công!', 'success');
    updateAuthMenu();
    
};

// ==================== KHỞI TẠO ==================== //

// Khởi tạo cart manager khi DOM ready
let cartManager;

document.addEventListener("DOMContentLoaded", function() {
    cartManager = new CartManager();
    updateAuthMenu();
    loadInfoUser();

    // Event listeners for profile actions
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            logout();
        });
    }

    const viewProfileLink = document.getElementById('viewProfile');
    if (viewProfileLink) {
        viewProfileLink.addEventListener('click', (e) => {
            e.preventDefault();
            profileActions.viewProfile();
        });
    }

    const changePasswordLink = document.getElementById('changePassword');
    if (changePasswordLink) {
        changePasswordLink.addEventListener('click', (e) => {
            e.preventDefault();
            profileActions.changePassword();
        });
    }
});

// Export cho sử dụng ở nơi khác
window.cartManager = cartManager;

// ==================== ADDITIONAL FEATURES ==================== //

// Thêm CSS cho notification nếu chưa có
if (!document.querySelector('style[data-notification-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-notification-styles', 'true');
    style.textContent = `
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        }
        
        .notification {
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
            animation: slideInRight 0.3s ease-out;
            pointer-events: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.info { background: #17a2b8; }
        .notification.warning { background: #ffc107; color: #212529; }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}