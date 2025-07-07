// Utility functions
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

const logout = () => {
    storage.clear();
    showNotification('Đăng xuất thành công!', 'success');
    updateAuthMenu();
    window.location.reload();
};

// Initialize application
document.addEventListener('DOMContentLoaded', () => {
    const gameContainer = document.getElementById("game-container");
    const token = storage.get('token');

    updateAuthMenu();
    loadInfoUser();

    gameContainer.innerHTML = '<div class="loading">Đang tải dịch vụ...</div>';

    if (!token) {
        clearCart();
    }

    // Event listeners
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

    loadCartFromStorage();
    updateCartDisplay();
    fetchGameConsoles();
});

// Profile functions
async function loadInfoUser() {
    const userId = storage.get('id');
    if (!userId) return;

    try {
        const data = await apiCall(`/users/${userId}`);

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

const profileActions = {
    viewProfile: async () => {
        const userId = storage.get('id');
        if (!userId) return;
        
        try {
            const data = await apiCall(`/users/${userId}`);
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
                const userId = storage.get('id');
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

// Cart management
let cart = [];

function loadCartFromStorage() {
    // Nếu chưa đăng nhập thì không load giỏ hàng
    if (!isLoggedIn()) {
        cart = [];
        return;
    }

    const savedCart = storage.get('gameCart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
        } catch (error) {
            console.error('Error loading cart from storage:', error);
            cart = [];
        }
    } else {
        cart = [];
    }
}


function saveCartToStorage() {
    storage.set('gameCart', JSON.stringify(cart));
}

function addToCart(consoleId, consoleName, price, imageUrl, hours, quantity) {
    if (!isLoggedIn()) {
        showNotification('Vui lòng đăng nhập để thêm sản phẩm.', 'warning');
        return;
    }

    const numericConsoleId = parseInt(consoleId);
    const existingItem = cart.find(item => item.id === numericConsoleId);

    if (existingItem) {
        showNotification('Máy này đã có trong giỏ hàng!', 'warning');
        return;
    }

    const cartItem = {
        id: parseInt(consoleId),
        name: consoleName,
        price: parseFloat(price),
        image: imageUrl || '../img/default.png',
        quantity: 1,        // mặc định 1
        hours: 1            // mặc định 1
    };

    cart.push(cartItem);
    saveCartToStorage();
    updateCartDisplay();
    updateButtonState(numericConsoleId, true);
    showNotification('Đã thêm vào giỏ hàng!', 'success');
}


function removeFromCart(consoleId) {
    if (consoleId === null || consoleId === undefined) {
        console.error('removeFromCart: consoleId is null or undefined', consoleId);
        showNotification('ID sản phẩm không hợp lệ!', 'error');
        return;
    }

    const numericConsoleId = parseInt(consoleId);
    if (isNaN(numericConsoleId)) {
        console.error('removeFromCart: consoleId is NaN', consoleId);
        showNotification('ID sản phẩm không hợp lệ!', 'error');
        return;
    }

    const itemIndex = cart.findIndex(item => item.id === numericConsoleId);

    if (itemIndex === -1) {
        console.warn('removeFromCart: Không tìm thấy sản phẩm', numericConsoleId, cart);
        showNotification('Không tìm thấy sản phẩm trong giỏ hàng!', 'warning');
        return;
    }

    const itemName = cart[itemIndex].name;
    cart.splice(itemIndex, 1);
    saveCartToStorage();
    updateCartDisplay();
    updateButtonState(numericConsoleId, false);
    showNotification(`Đã xóa "${itemName}" khỏi giỏ hàng!`, 'info');
}

function clearCart() {
    if (cart.length === 0) {
        
        return;
    }
    
    if (confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi giỏ hàng?')) {
        cart.forEach(item => {
            updateButtonState(item.id, false);
        });
        
        cart = [];
        saveCartToStorage();
        updateCartDisplay();
        showNotification('Đã xóa tất cả sản phẩm khỏi giỏ hàng!', 'success');
    }
}

function updateButtonState(consoleId, isInCart) {
    const consoleElement = document.getElementById(`console-${consoleId}`);
    if (!consoleElement) return;
    
    const addButton = consoleElement.querySelector('.btn-add-cart');
    if (!addButton) return;
    
    if (isInCart) {
        addButton.textContent = 'Đã thêm';
        addButton.classList.add('disabled');
        addButton.disabled = true;
    } else {
        addButton.textContent = 'Thêm giỏ hàng';
        addButton.classList.remove('disabled');
        addButton.disabled = false;
    }
}

function updateCartDisplay() {
    const cartCount = document.getElementById('cart-count');
    const cartDropdown = document.getElementById('cart-dropdown');

    if (!isLoggedIn()) {
        cartCount.style.display = 'none';
        cartDropdown.innerHTML = '<div class="cart-empty">Vui lòng đăng nhập để sử dụng giỏ hàng.</div>';
        return;
    }

    if (cart.length > 0) {
        cartCount.textContent = cart.length;
        cartCount.style.display = 'flex';
    } else {
        cartCount.style.display = 'none';
    }

    if (cart.length === 0) {
        cartDropdown.innerHTML = '<div class="cart-empty">Giỏ hàng trống</div>';
    } else {
        let cartHtml = '';
        let total = 0;

        cart.forEach((item, index) => {
            const itemTotal = item.price * item.quantity * item.hours;
            total += itemTotal;

            cartHtml += `
                <div class="cart-item" data-console-id="${item.id}">
                    <img src="${item.image}" alt="${escapeHtml(item.name)}" onerror="this.src='../img/default.png'">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${escapeHtml(item.name)}</div>
                        <div class="cart-item-controls">
                            <label>Số lượng:
                                <input type="number" class="quantity-input" data-index="${index}" min="1" value="${item.quantity}" style="width:50px;margin-left:5px;">
                            </label>
                            <label>Giờ thuê:
                                <input type="number" class="hours-input" data-index="${index}" min="1" value="${item.hours}" style="width:50px;margin-left:5px;">
                            </label>
                        </div>
                        <div class="cart-item-price">${formatPrice(itemTotal)} VND</div>
                    </div>
                    <button class="cart-item-remove" data-console-id="${item.id}" type="button">×</button>
                </div>
            `;
        });

        cartHtml += `
            <div class="cart-total">
                Tổng: <span id="cart-total-price">${formatPrice(total)} VND</span>
            </div>
            <div class="cart-actions">
                <button class="btn-clear-cart" type="button">Xóa tất cả</button>
                <button class="btn-checkout" type="button">Thanh Toán</button>
            </div>
        `;

        cartDropdown.innerHTML = cartHtml;

        attachCartRemoveListeners();
        attachCartQuantityHourListeners();
    }
}



function attachCartRemoveListeners() {
    const removeButtons = document.querySelectorAll('.cart-item-remove');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const consoleId = this.getAttribute('data-console-id');
            console.log('consoleId:', consoleId);
            removeFromCart(consoleId);
        });
    });

    const clearButton = document.querySelector('.btn-clear-cart');
    if (clearButton) {
        clearButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearCart();
        });
    }

    const checkoutButton = document.querySelector('.btn-checkout');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (cart.length === 0) {
                showNotification('Giỏ hàng trống, không thể thanh toán.', 'warning');
                return;
            }

            saveCartToStorage();
            window.location.href = 'gameRent.php';
        });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleCart() {
    const cartDropdown = document.getElementById('cart-dropdown');
    cartDropdown.style.display = cartDropdown.style.display === 'block' ? 'none' : 'block';
}

// Close cart when clicking outside
document.addEventListener('click', (e) => {
    const cartDropdown = document.getElementById('cart-dropdown');
    const cartIcon = document.querySelector('.cart-icon');
    
    if (!cartDropdown.contains(e.target) && !cartIcon.contains(e.target)) {
        cartDropdown.style.display = 'none';
    }
});

// Game consoles display
async function fetchGameConsoles() {
    const gameContainer = document.getElementById("game-container");
    
    try {
        const result = await apiCall('/game_rental_system/gameConsoles/index');
        
        if (result.success) {
            displayGameConsoles(result.data);
        } else {
            gameContainer.innerHTML = `<div class="error">Lỗi: ${result.message}</div>`;
        }
    } catch (error) {
        console.error('Lỗi khi tải dữ liệu:', error);
        gameContainer.innerHTML = '<div class="error">Không thể tải dữ liệu. Vui lòng thử lại sau.</div>';
    }
}

function displayGameConsoles(data) {
    const gameContainer = document.getElementById("game-container");

    if (!data.consoles || data.consoles.length === 0) {
        gameContainer.innerHTML = '<div class="no-data">Không có máy chơi game nào.</div>';
        return;
    }

    let html = '';

    data.consoles.forEach(console => {
        const consoleId = console.id;
        const isInCart = cart.some(item => parseInt(item.id) === parseInt(consoleId));
        const escapedName = escapeHtml(console.console_name);
        const safeImageUrl = console.image_url || '../img/default.png';

        html += `
            <div class="game-item" id="console-${consoleId}">
                <img src="${safeImageUrl}"
                     height="200px"
                     alt="${escapedName}"
                     onerror="this.src='../img/default.png'"/>
                <h3>
                    <a href="./console-detail.html?id=${consoleId}">
                        ${escapedName}
                    </a>
                </h3>
                <p class="price">${formatPrice(console.rental_price_per_hour)} VND/giờ</p>
                <p class="type">Loại: ${escapeHtml(console.console_type)}</p>
                <p class="status status-${console.status.toLowerCase()}">
                    Trạng thái: ${getStatusText(console.status)} </br>
                    Số lượng : ${console.available_quantity}
                </p>
                <div class="button-group">
                    ${
                        console.status === 'available'
                        ?
                        `
                        <button 
                            class="btn-add-cart ${isInCart ? 'disabled' : ''}"
                            type="button"
                            data-console-id="${consoleId}" 
                            data-console-name="${escapedName}" 
                            data-price="${console.rental_price_per_hour}" 
                            data-image="${safeImageUrl}"
                            data-quantity="${console.available_quantity}"
                            ${isInCart ? 'disabled' : ''}>
                            ${isInCart ? 'Đã thêm' : 'Thêm giỏ hàng'}
                        </button>
                        <button class="btn-rent" onclick="rentConsole(${consoleId})">
                            Thuê ngay
                        </button>
                        `
                        :
                        console.status === 'maintenance'
                        ?
                        `
                        <button class="btn-add-cart disabled" disabled>
                            Không khả dụng
                        </button>
                        <button class="btn-rent disabled" disabled>
                            Bảo trì
                        </button>
                        `
                        :
                        `
                        <button class="btn-add-cart disabled" disabled>
                            Không khả dụng
                        </button>
                        <button class="btn-rent disabled" disabled>
                            Không khả dụng
                        </button>
                        `
                    }
                </div>
            </div>
        `;
    });

    gameContainer.innerHTML = html;
    attachCartButtonListeners();

    if (data.pagination) {
        displayPagination(data.pagination);
    }
}


//model số giờ và số lượng
function showAddToCartModal(consoleId, consoleName, price, imageUrl, availableQuantity) {
    createModal(`
        <h3>Thuê máy "${escapeHtml(consoleName)}"</h3>
        <form id="addToCartForm">
            <label>Số giờ thuê:</label>
            <input type="number" name="hours" min="1" value="1" required style="width:100%;margin:5px 0;">
            <label>Số lượng máy:</label>
            <input type="number" name="quantity" min="1" max="${availableQuantity}" value="1" required style="width:100%;margin:5px 0;">
            <button type="submit" style="margin-top:10px;padding:10px 20px;background:#28a745;color:white;border:none;border-radius:4px;cursor:pointer;">Xác nhận thêm giỏ hàng</button>
        </form>
    `);

    document.getElementById('addToCartForm').onsubmit = (e) => {
        e.preventDefault();
        const form = e.target;
        const hours = parseInt(form.hours.value);
        const quantity = parseInt(form.quantity.value);

        if (isNaN(hours) || hours <= 0) {
            showNotification('Số giờ thuê không hợp lệ.', 'error');
            return;
        }

        if (isNaN(quantity) || quantity <= 0 || quantity > availableQuantity) {
            showNotification('Số lượng không hợp lệ.', 'error');
            return;
        }

        addToCart(consoleId, consoleName, price, imageUrl, hours, quantity);
        document.querySelector('.modal-overlay').remove();
    };
}


function attachCartQuantityHourListeners() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    const hourInputs = document.querySelectorAll('.hours-input');

    quantityInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.getAttribute('data-index'));
            let val = parseInt(e.target.value);
            if (isNaN(val) || val < 1) val = 1;
            cart[index].quantity = val;
            saveCartToStorage();
            updateCartDisplay();
        });
    });

    hourInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const index = parseInt(e.target.getAttribute('data-index'));
            let val = parseInt(e.target.value);
            if (isNaN(val) || val < 1) val = 1;
            cart[index].hours = val;
            saveCartToStorage();
            updateCartDisplay();
        });
    });
}


function attachCartButtonListeners() {
    const gameContainer = document.getElementById("game-container");
    gameContainer.removeEventListener('click', handleCartButtonClick);
    gameContainer.addEventListener('click', handleCartButtonClick);
}

function handleCartButtonClick(event) {
    if (
        event.target.classList.contains('btn-add-cart') &&
        !event.target.classList.contains('disabled') &&
        !event.target.disabled
    ) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.target;
        const consoleId = button.getAttribute('data-console-id');
        const consoleName = button.getAttribute('data-console-name');
        const price = button.getAttribute('data-price');
        const imageUrl = button.getAttribute('data-image');

        addToCart(consoleId, consoleName, price, imageUrl);
    }
}



function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

function getStatusText(status) {
    const statusMap = {
        'available': 'Có sẵn',
        'rented': 'Đã thuê',
        'maintenance': 'Bảo trì',
        'unavailable': 'Không khả dụng'
    };
    return statusMap[status] || status;
}

function displayPagination(pagination) {
    const gameContainer = document.getElementById("game-container");
    
    let paginationHtml = '<div class="pagination">';
    
    if (pagination.current_page > 1) {
        paginationHtml += `<button onclick="loadPage(${pagination.current_page - 1})">« Trước</button>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            paginationHtml += `<button class="active">${i}</button>`;
        } else {
            paginationHtml += `<button onclick="loadPage(${i})">${i}</button>`;
        }
    }
    
    if (pagination.current_page < pagination.total_pages) {
        paginationHtml += `<button onclick="loadPage(${pagination.current_page + 1})">Sau »</button>`;
    }
    
    paginationHtml += '</div>';
    gameContainer.innerHTML += paginationHtml;
}

function loadPage(page) {
    const gameContainer = document.getElementById("game-container");
    gameContainer.innerHTML = '<div class="loading">Đang tải dịch vụ...</div>';
    
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.history.pushState({}, '', url);
    
    fetchGameConsoles();
}

function isLoggedIn() {
    const token = storage.get('token');
    return !!token && token.trim() !== '';
}

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

function rentConsole(consoleId) {
    const token = storage.get('token');
    
    if (!token) {
        alert('Vui lòng đăng nhập để thuê máy chơi game.');
        window.location.href = 'login.php';
        return;
    }
    
    window.location.href = `gameRent.php?console_id=${consoleId}`;
}