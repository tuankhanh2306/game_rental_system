document.addEventListener('DOMContentLoaded', () => {
    const gameContainer = document.getElementById("game-container");
    const token = localStorage.getItem('token');
    const role = localStorage.getItem("role");
    
    gameContainer.innerHTML = '<div class="loading">Đang tải dịch vụ...</div>';
    
    // Gọi API để lấy danh sách máy chơi game
    fetchGameConsoles();
});

async function fetchGameConsoles() {
    const gameContainer = document.getElementById("game-container");
    
    try {
        // Gọi API để lấy danh sách máy chơi game
        const response = await fetch('/gameConsoles/index', {
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
        html += `
            <div class="game-item" id="console-${console.console_id}">
                <img src="${console.image_url || '../img/default.png'}" 
                     height="200px" 
                     alt="${console.console_name}" 
                     onerror="this.src='../img/default.png'"/>
                <h3>
                    <a href="./console-detail.html?id=${console.console_id}">
                        ${console.console_name}
                    </a>
                </h3>
                <p class="price">${formatPrice(console.rental_price_per_hour)} VND/giờ</p>
                <p class="type">Loại: ${console.console_type}</p>
                <p class="status status-${console.status.toLowerCase()}">
                    Trạng thái: ${getStatusText(console.status)}
                </p>
                ${console.status === 'available' ? 
                    `<button class="btn-rent" onclick="rentConsole(${console.console_id})">
                        Thuê ngay
                    </button>` : 
                    `<button class="btn-rent disabled" disabled>
                        Không khả dụng
                    </button>`
                }
            </div>
        `;
    });
    
    gameContainer.innerHTML = html;
    
    // Hiển thị thông tin phân trang nếu có
    if (data.pagination) {
        displayPagination(data.pagination);
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

function rentConsole(consoleId) {
    const token = localStorage.getItem('token');
    
    if (!token) {
        alert('Vui lòng đăng nhập để thuê máy chơi game.');
        window.location.href = 'login.html';
        return;
    }
    
    // Chuyển đến trang đặt thuê với ID máy chơi game
    window.location.href = `datthue.html?console_id=${consoleId}`;
}
