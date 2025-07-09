// ==================== CART UTILITIES ==================== //

// Storage utilities
const storage = {
    get: (key) => localStorage.getItem(key),
    set: (key, value) => localStorage.setItem(key, value),
    remove: (key) => localStorage.removeItem(key),
    clear: () => ['token', 'id', 'fullname'].forEach(k => localStorage.removeItem(k))
};

// API call utility
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
    const colors = { 
        success: '#28a745', 
        error: '#dc3545', 
        info: '#17a2b8', 
        warning: '#ffc107' 
    };
    
    notif.textContent = message;
    notif.style.cssText = `
        padding:12px 20px;margin:5px 0;border-radius:4px;color:white;
        font-weight:bold;transition:opacity 0.3s ease;
        background:${colors[type] || colors.info};
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease-out;
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
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
    `;
    
    const content = document.createElement('div');
    content.className = 'modal-content';
    content.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        position: relative;
    `;
    content.innerHTML = html;
    
    const closeBtn = document.createElement('span');
    closeBtn.className = 'modal-close';
    closeBtn.textContent = '×';
    closeBtn.style.cssText = `
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    `;
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
                <input type="password" name="currentPassword" placeholder="Mật khẩu hiện tại" required 
                       style="width:100%;margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:4px;">
                <input type="password" name="newPassword" placeholder="Mật khẩu mới" required 
                       style="width:100%;margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:4px;">
                <input type="password" name="confirmPassword" placeholder="Xác nhận mật khẩu mới" required 
                       style="width:100%;margin:5px 0;padding:8px;border:1px solid #ccc;border-radius:4px;">
                <button type="submit" 
                        style="margin-top:10px;padding:10px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;">
                    Xác nhận
                </button>
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
    setTimeout(() => {
        window.location.href = 'index.php';
    }, 1500);
};

// Add CSS for animations if not exists
if (!document.querySelector('style[data-cart-utils-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-cart-utils-styles', 'true');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-close:hover {
            color: #333;
        }
    `;
    document.head.appendChild(style);
}
