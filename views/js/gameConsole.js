document.addEventListener('DOMContentLoaded', () => {
    const gameContainer = document.getElementById("game-container");
    const token = localStorage.getItem('token');
    const role = localStorage.getItem("role");
    
    gameContainer.innerHTML = '<div class="loading">Đang tải dịch vụ...</div>';
    if(!token){
        clearCart();
    }
    loadCartFromStorage();
    updateCartDisplay();
    fetchGameConsoles();
});

// Cart management
let cart = [];

function loadCartFromStorage() {
    const savedCart = localStorage.getItem('gameCart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
        } catch (error) {
            console.error('Error loading cart from storage:', error);
            cart = [];
        }
    }
}

function saveCartToStorage() {
    localStorage.setItem('gameCart', JSON.stringify(cart));
}

function addToCart(consoleId, consoleName, price, imageUrl) {
    console.log('Adding to cart:', { consoleId, consoleName, price, imageUrl }); 
    
    // Đảm bảo consoleId là số
    const numericConsoleId = parseInt(consoleId);
    
    // Kiểm tra xem item đã có trong giỏ hàng chưa
    const existingItem = cart.find(item => parseInt(item.id) === numericConsoleId);
    
    if (existingItem) {
        showNotification('Máy này đã có trong giỏ hàng!', 'warning');
        return;
    }
    
    const cartItem = {
        id: parseInt(consoleId),
        name: consoleName,
        price: parseFloat(price),
        image: imageUrl || '../img/default.png'
    };
    
    cart.push(cartItem);
    saveCartToStorage();
    updateCartDisplay();
    
    // Cập nhật trạng thái nút cụ thể
    updateButtonState(numericConsoleId, true);
    
    showNotification('Đã thêm vào giỏ hàng thành công!', 'success');
}

function removeFromCart(consoleId) {
    const numericConsoleId = parseInt(consoleId);
    
    // Tìm index của item cần xóa
    const itemIndex = cart.findIndex(item => parseInt(item.id) === numericConsoleId);
    
    if (itemIndex === -1) {
        showNotification('Không tìm thấy sản phẩm trong giỏ hàng!', 'warning');
        return;
    }
    
    // Lưu tên sản phẩm để hiển thị thông báo
    const itemName = cart[itemIndex].name;
    
    // Xóa item khỏi giỏ hàng
    cart.splice(itemIndex, 1);
    
    // Lưu vào localStorage
    saveCartToStorage();
    
    // Cập nhật hiển thị giỏ hàng
    updateCartDisplay();
    
    // Cập nhật trạng thái nút "Thêm giỏ hàng" về trạng thái ban đầu
    updateButtonState(numericConsoleId, false);
    
    // Hiển thị thông báo thành công
    showNotification(`Đã xóa "${itemName}" khỏi giỏ hàng!`, 'info');
    
    // Log để debug (có thể xóa trong production)
    console.log('Removed from cart:', { consoleId: numericConsoleId, itemName });
    console.log('Current cart:', cart);
}

function clearCart() {
    if (cart.length === 0) {
        showNotification('Giỏ hàng đã trống!', 'info');
        return;
    }
    
    if (confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi giỏ hàng?')) {
        // Cập nhật trạng thái tất cả các nút về ban đầu
        cart.forEach(item => {
            updateButtonState(item.id, false);
        });
        
        // Xóa giỏ hàng
        cart = [];
        saveCartToStorage();
        updateCartDisplay();
        
        showNotification('Đã xóa tất cả sản phẩm khỏi giỏ hàng!', 'success');
    }
}


// Hàm cập nhật trạng thái nút cụ thể
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
    
    // Update cart count
    if (cart.length > 0) {
        cartCount.textContent = cart.length;
        cartCount.style.display = 'flex';
    } else {
        cartCount.style.display = 'none';
    }
    
    // Update cart dropdown content
    if (cart.length === 0) {
        cartDropdown.innerHTML = '<div class="cart-empty">Giỏ hàng trống</div>';
    } else {
        let cartHtml = '';
        let total = 0;
        
        cart.forEach(item => {
            total += item.price;
            cartHtml += `
                <div class="cart-item" data-console-id="${item.id}">
                    <img src="${item.image}" alt="${escapeHtml(item.name)}" onerror="this.src='../img/default.png'">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${escapeHtml(item.name)}</div>
                        <div class="cart-item-price">${formatPrice(item.price)} VND/giờ</div>
                    </div>
                    <button class="cart-item-remove" data-console-id="${item.id}" type="button">×</button>
                </div>
            `;
        });
        
        cartHtml += `
            <div class="cart-total">
                Tổng: ${formatPrice(total)} VND/giờ
            </div>
            <div class="cart-actions">
                <button class="btn-clear-cart" type="button">Xóa tất cả</button>
                <button class="btn-checkout" type="button">Thanh Toán</button>
            </div>

        `;
        
        cartDropdown.innerHTML = cartHtml;
        
        // Thêm event listeners sau khi cập nhật HTML
        attachCartRemoveListeners();
    }
}

// Hàm để gắn event listener cho các nút remove
function attachCartRemoveListeners() {
    // Nút remove từng item
    const removeButtons = document.querySelectorAll('.cart-item-remove');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const consoleId = this.getAttribute('data-console-id');
            removeFromCart(consoleId);
        });
    });

    // Nút clear all
    const clearButton = document.querySelector('.btn-clear-cart');
    if (clearButton) {
        clearButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            clearCart();
        });
    }

    // Nút thanh toán
    const checkoutButton = document.querySelector('.btn-checkout');
    if (checkoutButton) {
        checkoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Optionally: kiểm tra cart trống
            if (cart.length === 0) {
                showNotification('Giỏ hàng trống, không thể thanh toán.', 'warning');
                return;
            }

            // Lưu cart lại phòng khi có thay đổi
            saveCartToStorage();

            // Chuyển trang
            window.location.href = 'gameRent.php';
        });
    }
}

// Hàm escape HTML để tránh lỗi XSS và ký tự đặc biệt
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleCart() {
    const cartDropdown = document.getElementById('cart-dropdown');
    cartDropdown.style.display = cartDropdown.style.display === 'block' ? 'none' : 'block';
}

function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification show ${type}`;
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Close cart when clicking outside
document.addEventListener('click', (e) => {
    const cartDropdown = document.getElementById('cart-dropdown');
    const cartIcon = document.querySelector('.cart-icon');
    
    // Không đóng cart nếu click vào nội dung cart dropdown
    if (!cartDropdown.contains(e.target) && !cartIcon.contains(e.target)) {
        cartDropdown.style.display = 'none';
    }
});

async function fetchGameConsoles() {
    const gameContainer = document.getElementById("game-container");
    
    try {
        // Gọi API để lấy danh sách máy chơi game
        const response = await fetch('/game_rental_system/gameConsoles/index', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });
        
        const result = await response.json();
        
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
        // Kiểm tra chính xác ID (đảm bảo cùng kiểu dữ liệu)
        const isInCart = cart.some(item => parseInt(item.id) === parseInt(console.console_id));
        
        // Escape các ký tự đặc biệt trong tên
        const escapedName = escapeHtml(console.console_name);
        const safeImageUrl = console.image_url || '../img/default.png';
        
        html += `
            <div class="game-item" id="console-${console.console_id}">
                <img src="${safeImageUrl}"
                     height="200px"
                     alt="${escapedName}"
                     onerror="this.src='../img/default.png'"/>
                <h3>
                    <a href="./console-detail.html?id=${console.console_id}">
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
                    ${console.status === 'available' ?
                        `<button class="btn-add-cart ${isInCart ? 'disabled' : ''}"
                                data-console-id="${console.id}"
                                data-console-name="${escapedName}"
                                data-price="${console.rental_price_per_hour}"
                                data-image="${safeImageUrl}"
                               ${isInCart ? 'disabled' : ''}>
                           ${isInCart ? 'Đã thêm' : 'Thêm giỏ hàng'}
                       </button>
                       <button class="btn-rent" onclick="rentConsole(${console.id})">
                           Thuê ngay
                       </button>` :
                       `<button class="btn-add-cart disabled" disabled>
                           Không khả dụng
                       </button>
                       <button class="btn-rent disabled" disabled>
                           Không khả dụng
                       </button>`
                    }
                </div>
            </div>
        `;
    });
    
    gameContainer.innerHTML = html;
    
    // Thêm event listener cho các nút "Thêm giỏ hàng" sau khi render
    attachCartButtonListeners();
    
    // Hiển thị thông tin phân trang nếu có
    if (data.pagination) {
        displayPagination(data.pagination);
    }
}

// Hàm mới để gắn event listener cho các nút thêm giỏ hàng
function attachCartButtonListeners() {
    const addToCartButtons = document.querySelectorAll('.btn-add-cart:not(.disabled)');
    
    addToCartButtons.forEach(button => {
        // Xóa event listener cũ nếu có
        button.removeEventListener('click', handleAddToCart);
        // Thêm event listener mới
        button.addEventListener('click', handleAddToCart);
    });
}

// Handler cho việc thêm vào giỏ hàng
function handleAddToCart(event) {
    const button = event.target;
    const consoleId = button.getAttribute('data-console-id');
    const consoleName = button.getAttribute('data-console-name');
    const price = button.getAttribute('data-price');
    const imageUrl = button.getAttribute('data-image');
    
    addToCart(consoleId, consoleName, price, imageUrl);
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
    
    // Nút Previous
    if (pagination.current_page > 1) {
        paginationHtml += `<button onclick="loadPage(${pagination.current_page - 1})">« Trước</button>`;
    }
    
    // Các số trang
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            paginationHtml += `<button class="active">${i}</button>`;
        } else {
            paginationHtml += `<button onclick="loadPage(${i})">${i}</button>`;
        }
    }
    
    // Nút Next
    if (pagination.current_page < pagination.total_pages) {
        paginationHtml += `<button onclick="loadPage(${pagination.current_page + 1})">Sau »</button>`;
    }
    
    paginationHtml += '</div>';
    
    gameContainer.innerHTML += paginationHtml;
}

function loadPage(page) {
    const gameContainer = document.getElementById("game-container");
    gameContainer.innerHTML = '<div class="loading">Đang tải dịch vụ...</div>';
    
    // Cập nhật URL với tham số page
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    window.history.pushState({}, '', url);
    
    fetchGameConsoles();
}

function logout(){
    localStorage.removeItem('token');
    alert("Bạn đã đăng xuất");
    window.location.href="login.php"
}

function rentConsole(consoleId) {
    const token = localStorage.getItem('token');
    
    if (!token) {
        alert('Vui lòng đăng nhập để thuê máy chơi game.');
        window.location.href = 'login.php';
        return;
    }
    
    // Chuyển đến trang đặt thuê với ID máy chơi game
    window.location.href = `gameRent.php?console_id=${consoleId}`;
}
