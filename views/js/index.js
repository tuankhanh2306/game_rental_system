// Cấu hình
const CONFIG = {
    gameLimit: 10,
    featuredLimit: 4,
    notificationTimeout: 2700
};

let currentGamePage = 1;

// Utility functions
const storage = {
    get: (key) => localStorage.getItem(key),
    set: (key, value) => localStorage.setItem(key, value),
    remove: (key) => localStorage.removeItem(key),
    clear: () => ['token', 'id', 'fullname'].forEach(key => localStorage.removeItem(key))
};

const isLoggedIn = () => !!storage.get('token')?.trim();

const escapeHtml = (text) => text ? text.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m])) : '';

const showLoading = (show) => {
    let loading = document.getElementById('loadingOverlay');
    if (!loading) {
        loading = document.createElement('div');
        loading.id = 'loadingOverlay';
        loading.textContent = 'Đang tải...';
        document.body.appendChild(loading);
    }
    loading.style.display = show ? 'flex' : 'none';
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
    const colors = { success: '#28a745', error: '#dc3545', info: '#17a2b8' };
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
    }, CONFIG.notificationTimeout);
};

// API calls
const apiCall = async (url, options = {}) => {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${storage.get('token') || ''}`
        }
    };
    
    try {
        const response = await fetch(url, { ...defaultOptions, ...options });
        return await response.json();
    } catch (error) {
        throw new Error(`API call failed: ${error.message}`);
    }
};

// Auth functions
const updateAuthMenu = () => {
    const loginLinks = document.querySelectorAll('.login');
    const profileMenu = document.querySelector('.profile-menu');
    const loggedIn = isLoggedIn();
    
    loginLinks.forEach(link => link.style.display = loggedIn ? 'none' : 'inline-block');
    if (profileMenu) profileMenu.style.display = loggedIn ? 'inline-block' : 'none';
};

const logout = () => {
    storage.clear();
    showNotification('Đăng xuất thành công!', 'success');
    updateAuthMenu();
};

// Modal functions
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

// Game functions
const loadGameDetail = async (gameId) => {
    showLoading(true);
    try {
        const data = await apiCall(`/game_rental_system/gameConsoles/${gameId}`);
        if (data.success) {
            showGameDetailModal(data.data);
        } else {
            showNotification('Không thể tải thông tin chi tiết: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Lỗi khi tải chi tiết sản phẩm: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
};

const showGameDetailModal = (game) => {
    const modalHtml = `
        <div class="game-modal">
            <div class="game-modal-left">
                <img src="${escapeHtml(game.image_url)}" alt="${escapeHtml(game.console_name)}">
            </div>
            <div class="game-modal-right">
                <h2>${escapeHtml(game.console_name)}</h2>
                <p><strong>Loại:</strong> ${escapeHtml(game.console_type)}</p>
                <p><strong>Mô tả:</strong> ${escapeHtml(game.description)}</p>
                <p><strong>Giá thuê:</strong> ${escapeHtml(game.formatted_price)}</p>
                <p><strong>Trạng thái:</strong> ${escapeHtml(game.status_text)}</p>
                <div class="modal-actions">
                    <button class="rent-now-btn" data-id="${game.id}">Thuê ngay</button>
                </div>
            </div>
        </div>
    `;
    
    createModal(modalHtml);
    
    document.querySelector('.rent-now-btn')?.addEventListener('click', () => {
        window.location.href = 'gameConsole.php';
    });
};

const loadGamesData = async (page = 1) => {
    currentGamePage = page;
    showLoading(true);
    
    const url = `/game_rental_system/gameConsoles/index?page=${page}&limit=${CONFIG.gameLimit}`;
    
    try {
        const data = await apiCall(url);
        if (data.success) {
            displayGameProducts(data.data.consoles);
        } else {
            throw new Error(data.message || 'Lỗi khi tải dữ liệu');
        }
    } catch (error) {
        showNotification('Lỗi tải dữ liệu: ' + error.message, 'error');
        displayGameProducts([]);
    } finally {
        showLoading(false);
    }
};

const displayGameProducts = (games) => {
    const grid = document.getElementById('gameProductGrid');
    if (!grid) return;
    
    if (!games?.length) {
        grid.innerHTML = '<div class="empty-state">Không có sản phẩm để hiển thị.</div>';
        return;
    }
    
    grid.innerHTML = games.slice(0, CONFIG.featuredLimit).map(game => `
        <div class="product-card">
            <img src="${escapeHtml(game.image_url)}" alt="${escapeHtml(game.console_name)}">
            <h4>${escapeHtml(game.console_name)}</h4>
            <button data-id="${game.id}">Xem chi tiết</button>
        </div>
    `).join('');
    
    // Add event listeners
    grid.querySelectorAll('button').forEach(btn => {
        btn.onclick = async () => {
            const gameId = btn.dataset.id;
            if (isLoggedIn()) {
                await loadGameDetail(gameId);
            } else {
                showNotification('Vui lòng đăng nhập để xem chi tiết!', 'error');
            }
        };
    });
};

// User functions
const loadInfoUser = async () => {
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
            showNotification('Lỗi: ' + data.message, 'error');
        }
    } catch (error) {
        showNotification('Không thể tải thông tin.', 'error');
    }
};

// Profile functions
const profileActions = {
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

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    updateAuthMenu();
    loadGamesData();
    loadInfoUser();
    
    // Event delegation for profile actions
    const eventMap = {
        'logoutLink': logout,
        'viewProfile': profileActions.viewProfile,
        'changePassword': profileActions.changePassword
    };
    
    Object.entries(eventMap).forEach(([id, handler]) => {
        document.getElementById(id)?.addEventListener('click', (e) => {
            e.preventDefault();
            handler();
        });
    });
    
    // Legacy logout support
    document.querySelector('.logout')?.addEventListener('click', (e) => {
        e.preventDefault();
        logout();
    });
});