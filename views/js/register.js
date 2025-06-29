document.addEventListener('DOMContentLoaded', function(){
    const registerForm = document.getElementById('register-form');
    registerForm.addEventListener('submit', async function(e){
        e.preventDefault();
        
        // Lấy giá trị từ input fields
        const userName = document.getElementById('userName').value;
        const fullName = document.getElementById('fullName').value;
        const phone = document.getElementById('phone').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        // Validate dữ liệu
        if (!userName || !password || !fullName || !phone || !email) {
            alert('Vui lòng nhập đầy đủ thông tin đăng nhập');
            return;
        }
        
        try{
            const response = await fetch('/game_rental_system/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', 
                },
                body: JSON.stringify({
                    username: userName, 
                    email: email,
                    password: password,
                    full_name: fullName, 
                    phone: phone
                })
            });
            
            // Kiểm tra response có phải JSON không
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Server response is not JSON:', text);
                alert('Server trả về dữ liệu không hợp lệ. Vui lòng kiểm tra lại.');
                return;
            }
            
            const result = await response.json();
            
            if(result.success){
                alert("Đăng ký thành công");
                window.location.href = "/game_rental_system/views/pages/login.php";
            } else {
                // Hiển thị lỗi cụ thể
                if(result.errors) {
                    let errorMessage = 'Lỗi đăng ký:\n';
                    for(let field in result.errors) {
                        errorMessage += `- ${result.errors[field]}\n`;
                    }
                    alert(errorMessage);
                } else {
                    alert('Đăng ký thất bại. Vui lòng thử lại.');
                }
            }
        }
        catch(error){
            console.error('Register error:', error);
            alert('Có lỗi xảy ra khi đăng ký. Vui lòng thử lại.');
        }
    });
});
