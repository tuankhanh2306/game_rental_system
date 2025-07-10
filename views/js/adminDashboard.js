// Admin Dashboard JavaScript
// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Dashboard loaded');
    const role = localStorage.getItem('role');
    if (!role || role !== 'admin') {
        alert('Bạn không có quyền truy cập vào trang này.');
        localStorage.removeItem('token');
        window.location.href = 'login.php';
        return;
    }
    if (!localStorage.getItem('token')) {
        alert('Bạn cần đăng nhập để truy cập trang này.');
        window.location.href = 'login.php';
        return;
    }
    // Khởi tạo navigation
    initNavigation();

    // Load dashboard mặc định
    showSection('dashboard');
    
});

document.getElementById('userForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    submitUserForm();
});
///user

document.getElementById('prevPageBtn').addEventListener('click', () => {
    if (currentPage > 1) {
        loadUsersData(currentPage - 1);
    }
});
document.getElementById('nextPageBtn').addEventListener('click', () => {
    loadUsersData(currentPage + 1);
});
//game

document.getElementById('nextPageBtnGame').addEventListener('click', () => {
    loadGamesData(currentGamePage + 1);
});
document.getElementById('prevPageBtnGame').addEventListener('click', () => {
    if (currentGamePage > 1) {
        loadGamesData(currentGamePage - 1);
    }
});
//rental
document.getElementById('nextPageBtnRental').addEventListener('click', () => {
    loadRentalsData(currentRentalPage + 1);
});
document.getElementById('prevPageBtnRental').addEventListener('click', () => {
    if (currentRentalPage > 1) {
        loadRentalsData(currentRentalPage - 1);
    }
});





async function submitUserForm() {
    const userId = document.getElementById('userId').value.trim();

    const userData = {
        username: document.getElementById('username').value.trim(),
        full_name: document.getElementById('fullName').value.trim(),
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        role: document.getElementById('role').value
    };

    // Nếu là thêm mới, bạn có thể yêu cầu password:
    if (!userId) {
        const password = prompt("Nhập mật khẩu cho người dùng mới:");
        if (!password) {
            alert("Bạn phải nhập mật khẩu!");
            return;
        }
        userData.password = password;
    }

    showLoading(true);

    try {
        const response = await fetch(
            userId
                ? `/game_rental_system/users/${userId}` // Update
                : `/game_rental_system/register`,         // Create
            {
                method: userId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
                },
                body: JSON.stringify(userData)
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            showNotification(
                userId ? 'Cập nhật người dùng thành công' : 'Thêm người dùng thành công',
                'success'
            );
            closeModal('userModal');
            loadUsersData();
        } else {
            throw new Error(result.message || 'Thao tác thất bại');
        }

    } catch (error) {
        console.error('Lỗi khi xử lý người dùng:', error);
        showNotification('Lỗi: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

async function submitGameForm() {
    const formData = new FormData();

    formData.append('console_name', document.getElementById('consoleName').value.trim());
    formData.append('console_type', document.getElementById('consoleType').value.trim());
    formData.append('rental_price_per_hour', parseInt(document.getElementById('rentalPrice').value));
    formData.append('status', document.getElementById('gameStatus').value);
    formData.append('description', document.getElementById('description').value.trim());
    formData.append('quantity', parseInt(document.getElementById('quantity').value));
    formData.append('available_quantity', parseInt(document.getElementById('availableQuantity').value));

    const imageFileInput = document.getElementById('imageFile');
    if (imageFileInput.files.length > 0) {
        formData.append('image', imageFileInput.files[0]);
    }

    // Lấy gameId để xác định Thêm mới hay Cập nhật
    const gameId = document.getElementById('gameId').value;

    // URL và method tùy theo trạng thái
    const isUpdate = !!gameId;
    const url = isUpdate
        ? `/game_rental_system/gameConsoles/${gameId}`
        : `/game_rental_system/gameConsoles/create`;
    const method = 'POST'; // Luôn POST

    if (isUpdate) {
        formData.append('_method', 'PUT');
    }

    showLoading(true);

    try {
        const response = await fetch(
            url,
            {
                method: 'POST', // Luôn POST
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
                },
                body: formData
            }
        );


        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            showNotification(
                isUpdate ? 'Cập nhật máy chơi game thành công' : 'Thêm máy chơi game thành công',
                'success'
            );
            closeModal('gameModal');
            loadGamesData();
        } else {
            throw new Error(result.message || 'Thao tác thất bại');
        }

    } catch (error) {
        console.error('Lỗi khi xử lý máy chơi game:', error);
        showNotification('Lỗi: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}


function fillGameForm(game) {
    document.getElementById('gameId').value = game.id || '';
    document.getElementById('consoleName').value = game.console_name || '';
    document.getElementById('consoleType').value = game.console_type || '';
    document.getElementById('rentalPrice').value = game.rental_price_per_hour || 0;
    document.getElementById('gameStatus').value = game.status || 'available';
    document.getElementById('description').value = game.description || '';
    
    // Nếu bạn có thêm hình ảnh
    if (document.getElementById('imageUrl')) {
        document.getElementById('imageUrl').value = game.image_url || '';
    }
    
    if (document.getElementById('quantity')) {
        document.getElementById('quantity').value = game.quantity || 0;
    }
    if (document.getElementById('availableQuantity')) {
        document.getElementById('availableQuantity').value = game.available_quantity || 0;
    }
}



async function editGame(gameId) {
    showLoading(true);
    try {
        const response = await fetch(`/game_rental_system/gameConsoles/${gameId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            fillGameForm(data.data);
            document.getElementById('gameModalTitle').textContent = 'Chỉnh sửa máy chơi game';
            openModal('gameModal');
        } else {
            throw new Error(data.message || 'Lỗi khi tải thông tin máy chơi game');
        }
    } catch (error) {
        console.error('Error loading game for edit:', error);
        showNotification('Lỗi khi tải thông tin máy: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

async function viewGame(gameId) {
    showLoading(true);
    try {
        const response = await fetch(`/game_rental_system/gameConsoles/${gameId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            const game = data.data;
            alert(`Thông tin máy:\nID: ${game.id}\nTên: ${game.console_name}\nLoại: ${game.console_type}\nGiá/giờ: ${formatCurrency(game.rental_price_per_hour)}\nTrạng thái: ${game.status}\nMô tả: ${game.description}`);
        } else {
            throw new Error(data.message || 'Lỗi khi tải thông tin máy chơi game');
        }
    } catch (error) {
        console.error('Error loading game details:', error);
        showNotification('Lỗi khi tải thông tin máy: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}


async function deleteGame(gameId) {
    if (!confirm('Bạn có chắc chắn muốn xóa máy chơi game này?')) {
        return;
    }

    showLoading(true);
    try {
        const response = await fetch(`/game_rental_system/gameConsoles/${gameId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            showNotification('Xóa máy chơi game thành công', 'success');
            loadGamesData();
        } else {
            throw new Error(data.message || 'Lỗi khi xóa máy chơi game');
        }
    } catch (error) {
        console.error('Error deleting game:', error);
        showNotification('Lỗi khi xóa máy: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}


function openAddGameModal() {
    document.getElementById('gameModalTitle').textContent = 'Thêm máy chơi game mới';
    document.getElementById('gameForm').reset();
    document.getElementById('gameId').value = '';
    openModal('gameModal');
}


document.getElementById('gameForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    submitGameForm();
});



/**
 * Khởi tạo navigation system
 */
function initNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Lấy section từ data-section
            const sectionName = this.getAttribute('data-section');
            
            // Xóa active class từ tất cả nav links
            navLinks.forEach(nav => nav.classList.remove('active'));
            
            // Thêm active class cho nav link hiện tại
            this.classList.add('active');
            
            // Hiển thị section tương ứng
            showSection(sectionName);
        });
    });
}

/**
 * Hiển thị section được chọn và ẩn các section khác
 * @param {string} sectionName - Tên của section cần hiển thị
 */
function showSection(sectionName) {
    // Ẩn tất cả các section
    const allSections = document.querySelectorAll('.content-section');
    allSections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Hiển thị section được chọn
    const targetSection = document.getElementById(sectionName);
    if (targetSection) {
        targetSection.classList.add('active');
        
        // Gọi hàm load data tương ứng cho từng section
        loadSectionData(sectionName);
    } else {
        console.error(`Section "${sectionName}" not found`);
    }
}

/**
 * Load dữ liệu cho từng section
 * @param {string} sectionName - Tên section
 */
function loadSectionData(sectionName) {
    switch(sectionName) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'users':
            loadUsersData();
            break;
        case 'games':
            loadGamesData();
            break;
        case 'rentals':
            loadRentalsData();
            break;
        case 'history':
            loadHistoryData();
            break;
        default:
            console.log(`No data loader for section: ${sectionName}`);
    }
}


async function loadTotalRevenue() {
    try {
        const response = await fetch('/api/rentals/total-revenue');
        const result = await response.json();
        if (result.success) {
            const revenue = result.data.totalRevenue || 0;
            const formatted = revenue.toLocaleString('vi-VN') + ' đ';
            document.getElementById('totalRevenue').textContent = formatted;
        } else {
            console.warn('Không lấy được tổng doanh thu:', result.message);
            document.getElementById('totalRevenue').textContent = '0 đ';
        }
    } catch (error) {
        console.error('Lỗi khi gọi API tổng doanh thu:', error);
        document.getElementById('totalRevenue').textContent = '0 đ';
    }
}


async function loadUserStats() {
    if(!localStorage.getItem('token')) {
        alert('Bạn cần đăng nhập để truy cập trang này.');
        window.location.href = 'login.php';
        return;
    }
    try {
        const response = await fetch('/game_rental_system/users/stats', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('API result:', result);

        if (result.success) {
            const statsArray = result.data;

            // Tính tổng người dùng từ danh sách role
            let totalUsers = 0;
            for (const item of statsArray) {
                totalUsers += item.total;
            }

            // Tạo object định dạng đúng
            const statsObject = {
                totalUsers: totalUsers
                // Nếu bạn có thêm tổng game, đơn thuê, doanh thu => thêm ở đây
            };

            // Gửi vào hàm update
            updateDashboardStats(statsObject);
        } else {
            throw new Error(result.message || 'Lỗi khi tải thống kê');
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showNotification('Lỗi khi tải thống kê: ' + error.message, 'error');
    } 
}

async function loadGameStats() {
    if(!localStorage.getItem('token')) {
        alert('Bạn cần đăng nhập để truy cập trang này.');
        window.location.href = 'login.php';
        return;
    }
    try {
        const response = await fetch('/game_rental_system/gameConsoles/stats', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('API result (games):', result);

        if (result.success) {
            const data = result.data;

            const statsObject = {
                totalGames: data.total_consoles || 0
            };

            updateDashboardStats(statsObject);
        } else {
            throw new Error(result.message || 'Lỗi khi tải thống kê máy chơi game');
        }
    } catch (error) {
        console.error('Error loading game stats:', error);
        showNotification('Lỗi khi tải thống kê máy chơi game: ' + error.message, 'error');
    }
}


async function loadRentalStats() {
    if(!localStorage.getItem('token')) {
        alert('Bạn cần đăng nhập để truy cập trang này.');
        window.location.href = 'login.php';
        return;
    }
    const response = await fetch('/game_rental_system/rentals/stats', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
        }
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const rawText = await response.text();

    let result;
    try {
        result = JSON.parse(rawText);
    } catch(parseError) {
        throw new Error('Server trả về dữ liệu không phải JSON. Có thể là lỗi PHP hoặc chưa đăng nhập.');
    }

    if (result.success) {
        // Lấy tổng rentals và revenue
        const totalRentals = result.data.total_rentals ?? 0;
        const totalRevenue = result.data.total_revenue ?? 0;

        return {
            totalRentals,
            totalRevenue
        };
    } else {
        throw new Error(result.message || 'Lỗi khi tải thống kê đơn thuê');
    }
}



async function loadDashboardData() {
    console.log('Loading dashboard data...');
    showLoading(true);

    const [totalUsers, totalGames, rentalStats] = await Promise.all([
        loadUserStats(),
        loadGameStats(),
        loadRentalStats()
    ]);

    const statsObject = {
        totalUsers,
        totalGames,
        totalRentals: rentalStats.totalRentals,
        totalRevenue: rentalStats.totalRevenue
    };

    updateDashboardStats(statsObject);
    showLoading(false);
}


// Khai báo biến phân trang ở đầu file (chỉ cần khai báo 1 lần)
let currentPage = 1;
let limit = 10;

async function loadUsersData(page = 1) {
    currentPage = page; // Cập nhật currentPage
    console.log(`Loading users data... Trang: ${currentPage}`);

    showLoading(true);

    const searchValue = document.getElementById('userSearch')?.value.trim() || '';

    // Tạo URL với search + page + limit
    const params = new URLSearchParams();
    
    params.append('page', currentPage);
    params.append('limit', limit);
    if (searchValue) {
            params.append('search', searchValue);
        }
    const url = `/game_rental_system/users?${params.toString()}`;

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            displayUsersTable(data.data.users);

            // Cập nhật số trang hiển thị
            const pageDisplay = document.getElementById('currentPageDisplay');
            if (pageDisplay) {
                pageDisplay.textContent = `Trang ${currentPage}`;
            }

            showNotification('Tải dữ liệu người dùng thành công', 'success');
        } else {
            throw new Error(data.message || 'Lỗi khi tải dữ liệu người dùng');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showNotification('Lỗi khi tải dữ liệu người dùng: ' + error.message, 'error');
        displayUsersTable([]);
    } finally {
        showLoading(false); // Sửa thành false để ẩn loading
    }
}



function displayUsersTable(users){
    const tbody = document.querySelector('#usersTable tbody');
    if(!tbody){
        console.error('Khong tim thay bang tbody ');
        return;
    }

    //remove old data 
    tbody.innerHTML='';
    if(!users || users.length === 0){
        body.innerHTML = 
        `
            <tr>
                <td colspan = "7" style ="text-align :center; padding : 2rem ; color :#666; ">
                    Không có dữ liệu người dùng
                </td>
            </tr>
        `;
        return;
    }

    users.forEach(user =>{
        const row = document.createElement('tr');
        row.innerHTML= `
            <td>${user.user_id}</td>
            <td>${escapeHtml(user.username)}</td>
            <td>${escapeHtml(user.full_name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>
                <span class "badge ${user.role === 'admin' ? 'badge-admin' : 'baadge-user'}">
                    ${user.role === 'admin' ? 'admin' : 'Người dùng'}
                </span>
            </td>
            <td>
                <span class = "status ${user.status} ">
                    ${getStatusText(user.status)}
                </span>
            </td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-info" onclick="viewUser(${user.user_id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editUser(${user.user_id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.user_id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>

        `;
        tbody.appendChild(row);
    })
}


/**
 * Cập nhật thống kê dashboard
 * @param {Object} stats - Dữ liệu thống kê
 */
function updateDashboardStats(stats) {
    // Cập nhật tổng số người dùng
    const totalUsersElement = document.getElementById('totalUsers');
    if (totalUsersElement && stats.totalUsers !== undefined) {
        totalUsersElement.textContent = stats.totalUsers;
    }
    
    // Cập nhật các thống kê khác nếu có
    if (stats.totalGames !== undefined) {
        const totalGamesElement = document.getElementById('totalGames');
        if (totalGamesElement) {
            totalGamesElement.textContent = stats.totalGames;
        }
    }
    
    if (stats.totalRentals !== undefined) {
        const totalRentalsElement = document.getElementById('totalRentals');
        if (totalRentalsElement) {
            totalRentalsElement.textContent = stats.totalRentals;
        }
    }
    
    if (stats.totalRevenue !== undefined) {
        const totalRevenueElement = document.getElementById('totalRevenue');
        if (totalRevenueElement) {
            totalRevenueElement.textContent = formatCurrency(stats.totalRevenue);
        }
    }
}

/**
 * Xem thông tin chi tiết user
 * @param {number} userId - ID của user
 */
async function viewUser(userId) {
    showLoading(true);
    try {
        const response = await fetch(`/game_rental_system/users/${userId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            showUserDetails(data.data);
        } else {
            throw new Error(data.message || 'Lỗi khi tải thông tin người dùng');
        }
    } catch (error) {
        console.error('Error loading user details:', error);
        showNotification('Lỗi khi tải thông tin người dùng: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}


/**
 * Chỉnh sửa user
 * @param {number} userId - ID của user
 */
async function editUser(userId) {
    showLoading(true);
    try {
        const response = await fetch(`/game_rental_system/users/${userId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            fillUserForm(data.data);
            document.getElementById('userModalTitle').textContent = 'Chỉnh sửa người dùng';
            openModal('userModal');
        } else {
            throw new Error(data.message || 'Lỗi khi tải thông tin người dùng');
        }
    } catch (error) {
        console.error('Error loading user for edit:', error);
        showNotification('Lỗi khi tải thông tin người dùng: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}


/**
 * Xóa user
 * @param {number} userId - ID của user
 */
async function deleteUser(userId) {
    if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
        return;
    }

    showLoading(true);
    try{
        const response = await fetch(`/game_rental_system/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (data.success) {
            showNotification('Xóa người dùng thành công', 'success');
            loadUsersData(); // Reload danh sách người dùng
        } else {
            throw new Error(data.message || 'Lỗi khi xóa người dùng');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showNotification('Lỗi khi xóa người dùng: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}



/**
 * Load dữ liệu Games
 */
let currentGamePage = 1;
let gameLimit = 10;

async function loadGamesData(page = 1) {
    if(!localStorage.getItem('token')) {
        alert('Bạn cần đăng nhập để truy cập trang này.');
        window.location.href = 'login.php';
        return;
    }
    currentGamePage = page;
    console.log(`Loading games data... Trang: ${currentGamePage}`);
    showLoading(true);

    const searchValue = document.getElementById('gameSearch')?.value.trim() || '';

    const params = new URLSearchParams();
    params.append('page', currentGamePage);
    params.append('limit', gameLimit);
    if (searchValue) {
        params.append('search', searchValue);
    }

    const url = `/game_rental_system/gameConsoles/index?${params.toString()}`;

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // Hiển thị bảng máy chơi game
            displayGameTable(data.data.consoles);

            // Cập nhật hiển thị số trang nếu cần
            const pageDisplay = document.getElementById('currentPageDisplayGame');
            if (pageDisplay) {
                pageDisplay.textContent = `Trang ${currentGamePage}`;
            }

            showNotification('Tải dữ liệu máy thành công', 'success');
        } else {
            throw new Error(data.message || 'Lỗi khi tải dữ liệu máy');
        }
    } catch (error) {
        console.error('Error loading games:', error);
        showNotification('Lỗi khi tải dữ liệu máy: ' + error.message, 'error');
        displayGameTable([]);
    } finally {
        showLoading(false);
    }
}



function displayGameTable(games) {
    const tbody = document.querySelector('#gamesTable tbody');
    if (!tbody) {
        console.error('Không tìm thấy bảng tbody');
        return;
    }

    // Clear old data
    tbody.innerHTML = '';

    if (!games || games.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                    Không có dữ liệu máy chơi game
                </td>
            </tr>
        `;
        return;
    }

    games.forEach(game => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${game.id}</td>
            <td>
                <div style="display: flex; align-items: center;">
                    <img src="${escapeHtml(game.image_url)}" alt="${escapeHtml(game.console_name)}" style="width: 50px; height: auto; margin-right: 8px; border-radius: 4px;">
                    ${escapeHtml(game.console_name)}
                </div>
            </td>
            <td>${escapeHtml(game.console_type)}</td>
            <td>${escapeHtml(game.formatted_price)}</td>
            <td>${game.status_text}</td>
            <td>${formatDate(game.created_at)}</td>
            
            
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-info" onclick="viewGame(${game.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editGame(${game.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteGame(${game.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Load dữ liệu Rentals
 */
let currentRentalPage = 1;
let rentalLimit = 10;

async function loadRentalsData(page = 1) {
    if(!localStorage.getItem('token')) {
        alert('Bạn cần đăng nhập để truy cập trang này.');
        window.location.href = 'login.php';
        return;
    }
    currentRentalPage = page;
    console.log(`Loading rentals data... Trang: ${currentRentalPage}`);
    showLoading(true);

    const params = new URLSearchParams();
    params.append('page', currentRentalPage);
    params.append('limit', rentalLimit);

    const url = `/game_rental_system/rentals?${params.toString()}`;

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Lấy toàn bộ text trả về
        const result = await response.json();



        if (result.success) {
            displayRentalTable(result.data.rentals);

            // Cập nhật trang hiện tại
            const pageDisplay = document.getElementById('currentPageDisplayRental');
            if (pageDisplay) {
                pageDisplay.textContent = `Trang ${currentRentalPage}`;
            }

            showNotification('Tải dữ liệu đơn thuê thành công', 'success');
        } else {
            throw new Error(result.message || 'Lỗi khi tải dữ liệu đơn thuê');
        }

    } catch (error) {
        console.error('Error loading rentals:', error);
        showNotification('Lỗi khi tải dữ liệu đơn thuê: ' + error.message, 'error');
        displayRentalTable([]);
    } finally {
        showLoading(false);
    }
}
function displayRentalTable(rentals) {
    const tbody = document.querySelector('#rentalsTable tbody');
    if (!tbody) {
        console.error('Không tìm thấy tbody bảng đơn thuê');
        return;
    }

    tbody.innerHTML = '';

    if (!rentals || rentals.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 2rem; color: #888;">
                    Không có đơn thuê nào
                </td>
            </tr>
        `;
        return;
    }

    rentals.forEach(rental => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${rental.rental_id}</td>
            <td>${escapeHtml(rental.console_name || 'N/A')}</td>
            <td>${escapeHtml(rental.full_name || 'N/A')}</td>
            <td>${formatDate(rental.rental_start)}</td>
            <td>${formatDate(rental.rental_end)}</td>
            <td>${rental.quantity}</td>
            <td>${rental.total_hours} giờ</td>
            <td>${formatCurrency(rental.total_amount)}</td>
            <td>${rental.status}</td>
        `;
        tbody.appendChild(row);
    });
}



/**
 * Load dữ liệu History
 */
function loadHistoryData() {
    console.log('Loading history data...');
    // TODO: Implement history data loading
    // Ví dụ:
    // - Load lịch sử hoạt động
    // - Hiển thị trong bảng
}

/**
 * Utility Functions
 */

/**
 * Mở modal
 * @param {string} modalId - ID của modal cần mở
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Ngăn scroll body khi modal mở
    }
}

/**
 * Đóng modal
 * @param {string} modalId - ID của modal cần đóng
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Cho phép scroll body lại
        
        // Reset form nếu có
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

/**
 * Đóng modal khi click bên ngoài
 */
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
});

/**
 * Hiển thị thông báo
 * @param {string} message - Nội dung thông báo
 * @param {string} type - Loại thông báo (success, error, info, warning)
 */
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notificationText');
    
    if (notification && notificationText) {
        notificationText.textContent = message;
        notification.className = `notification ${type} show`;
        
        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
}


/**
 * Hiển thị/ẩn loading
 * @param {boolean} show - true để hiển thị, false để ẩn
 */
function showLoading(show = true) {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = show ? 'flex' : 'none';
    }
}

/**
 * Format số tiền VNĐ
 * @param {number} amount - Số tiền
 * @returns {string} - Số tiền đã format
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format ngày tháng
 * @param {string|Date} date - Ngày cần format
 * @returns {string} - Ngày đã format
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Log khi script được load
console.log('Admin Dashboard JS loaded successfully');
function showUserDetails(user) {
    // TODO: Implement user details modal
    alert(`Thông tin người dùng:\nID: ${user.user_id}\nTên: ${user.username}\nEmail: ${user.email}`);
}

/**
 * Điền dữ liệu vào form user
 * @param {Object} user - Thông tin user
 */
function fillUserForm(user) {
    document.getElementById('userId').value = user.user_id;
    document.getElementById('username').value = user.username;
    document.getElementById('fullName').value = user.full_name;
    document.getElementById('email').value = user.email;
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role').value = user.role;
}

/**
 * Lấy text trạng thái
 * @param {string} status - Trạng thái
 * @returns {string} - Text trạng thái
 */
function getStatusText(status) {
    const statusMap = {
        'active': 'Hoạt động',
        'inactive': 'Không hoạt động',
        'suspended': 'Bị đình chỉ',
        'deleted': 'Đã xóa'
    };
    return statusMap[status] || status;
}

/**
 * Escape HTML để tránh XSS
 * @param {string} text - Text cần escape
 * @returns {string} - Text đã escape
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


function logout() {
    // Xóa token hoặc thông tin đăng nhập
    localStorage.removeItem('token');
    alert('Bạn đã đăng xuất thành công.');

    // Chuyển hướng về trang đăng nhập
    window.location.href = 'login.php'; // hoặc đường dẫn đăng nhập của bạn

    return;
}
