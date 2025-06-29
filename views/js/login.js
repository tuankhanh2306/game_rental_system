document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Lấy dữ liệu từ form
        const userName = document.getElementById('userName').value.trim();
        const password = document.getElementById('password').value.trim();
        
        // Validate dữ liệu
        if (!userName || !password) {
            alert('Vui lòng nhập đầy đủ thông tin đăng nhập');
            return;
        }
        
        // Disable nút submit để tránh spam
        const submitButton = loginForm.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Đang đăng nhập...';
        
        try {
            // Gửi request đến API
            const response = await fetch('/game_rental_system/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    identifier: userName,
                    password: password
                })
            });
            
            // Parse response
            const result = await response.json();
            
            console.log('Login response:', result);
            
            if (result.success) {
                
                // Đăng nhập thành công
                alert('Đăng nhập thành công!');
                
                // Lưu token nếu có
                if (result.token) {
                    localStorage.setItem('token', result.token);
                }
                if(result.user.role){
                    localStorage.setItem('role', result.user.role);
                }
                
                // Lưu thông tin user nếu có
                if (result.user) {
                    localStorage.setItem('user_info', JSON.stringify(result.user));
                }
                if(result.user.role == "admin"){
                    window.location.href = 'adminDashboard.php';
                }
                else{
                    window.location.href = 'index.php';
                }
                // Chuyển hướng đến trang chủ hoặc dashboard
                // Thay đổi URL theo ứng dụng của bạn
                 // hoặc '/'
                
            } else {
                // Đăng nhập thất bại
                alert('Đăng nhập thất bại: ' + (result.message || 'Lỗi không xác định'));
            }
            
        } catch (error) {
            console.error('Login error:', error);
            alert('Có lỗi xảy ra khi đăng nhập. Vui lòng thử lại.');
        } finally {
            // Khôi phục nút submit
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
    
    // Xử lý Enter key trong các input
    const inputs = loginForm.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loginForm.dispatchEvent(new Event('submit'));
            }
        });
    });
});

// Hàm kiểm tra trạng thái đăng nhập
function isLoggedIn() {
    return localStorage.getItem('auth_token') !== null;
}

// Hàm đăng xuất
function logout() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_info');
    window.location.href = '/login.html';
}

