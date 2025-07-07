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

// Profile actions object
const profileActions = {
    // View user profile
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
    showNotification('Đăng xuất thành công!', 'success');
    updateAuthMenu();
    window.location.reload();
};

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