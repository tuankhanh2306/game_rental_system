<?php
// Tải biến môi trường từ file .env
$dotenv = parse_ini_file(__DIR__ . '/../.env');

// Trả về mảng cấu hình JWT
return [

    // Khóa bí mật để ký JWT (dùng cho thuật toán HMAC)
    'secret' => $_ENV['JWT_SECRET'] ?? $dotenv['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this-in-production',

    // Thuật toán dùng để ký token
    'algorithm' => $_ENV['JWT_ALGORITHM'] ?? $dotenv['JWT_ALGORITHM'] ?? 'HS256',

    // Thời gian sống (TTL) cho các loại token (tính bằng giây)
    'ttl' => [
        'access_token' => (int)($_ENV['JWT_ACCESS_TOKEN_EXPIRE'] ?? $dotenv['JWT_ACCESS_TOKEN_EXPIRE'] ?? 3600), // 1 giờ
        'refresh_token' => (int)($_ENV['JWT_REFRESH_TOKEN_EXPIRE'] ?? $dotenv['JWT_REFRESH_TOKEN_EXPIRE'] ?? 604800), // 7 ngày
        'password_reset' => (int)($_ENV['PASSWORD_RESET_EXPIRE'] ?? $dotenv['PASSWORD_RESET_EXPIRE'] ?? 3600), // 1 giờ
        'email_verification' => 86400, // 24 giờ
        'api_key' => (int)($_ENV['API_TOKEN_EXPIRE'] ?? $dotenv['API_TOKEN_EXPIRE'] ?? 86400), // 24 giờ
    ],

    // Các giá trị xác định người phát hành và người nhận token
    'issuer' => $_ENV['JWT_ISSUER'] ?? $dotenv['JWT_ISSUER'] ?? 'game-rental-system',
    'audience' => $_ENV['JWT_AUDIENCE'] ?? $dotenv['JWT_AUDIENCE'] ?? 'game-rental-users',

    // Header HTTP chứa token
    'headers' => [
        'authorization' => 'Authorization', // Bearer token ở đây
        'api_key' => 'X-API-Key',
        'api_secret' => 'X-API-Secret',
    ],

    // Tiền tố cho token (để phân biệt loại xác thực)
    'prefixes' => [
        'bearer' => 'Bearer',
        'basic' => 'Basic',
    ],

    // Cấu hình cho refresh token
    'refresh_token' => [
        'enabled' => true,           // Cho phép sử dụng refresh token
        'rotation' => true,          // Cấp refresh token mới mỗi lần sử dụng
        'single_use' => true,        // Invalidate token cũ sau khi dùng
        'max_lifetime' => 30 * 24 * 3600, // Tối đa 30 ngày
    ],

    // Cấu hình danh sách token bị thu hồi (blacklist)
    'blacklist' => [
        'enabled' => true,
        'grace_period' => 300, // 5 phút cho chênh lệch thời gian
        'storage' => 'database', // Có thể chọn: database, redis, file
        'cleanup_interval' => 86400, // 1 lần/ngày
    ],

    // Nếu dùng thuật toán RSxxx, cấu hình đường dẫn key
    'keys' => [
        'private' => __DIR__ . '/../storage/keys/private.key',
        'public' => __DIR__ . '/../storage/keys/public.key',
        'passphrase' => $_ENV['JWT_PASSPHRASE'] ?? '',
    ],

    // Các claim cơ bản cho JWT
    'claims' => [
        'iss' => $_ENV['JWT_ISSUER'] ?? $dotenv['JWT_ISSUER'] ?? 'game-rental-system',
        'aud' => $_ENV['JWT_AUDIENCE'] ?? $dotenv['JWT_AUDIENCE'] ?? 'game-rental-users',
        'sub' => null,     // Thường là ID người dùng
        'iat' => null,     // Thời điểm phát hành
        'exp' => null,     // Hạn dùng (tính từ iat)
        'nbf' => null,     // Not before (token chỉ có hiệu lực sau thời điểm này)
        'jti' => null,     // JWT ID - mã định danh duy nhất
    ],

    // Các claim tùy chỉnh thêm vào token
    'custom_claims' => [
        'user_type' => 'user_type',
        'permissions' => 'permissions',
        'session_id' => 'session_id',
        'ip_address' => 'ip_address',
        'user_agent' => 'user_agent',
        'role' => 'role',
    ],

    // Các quy tắc để xác thực token
    'validation' => [
        'verify_signature' => true,
        'verify_issuer' => true,
        'verify_audience' => true,
        'verify_expiration' => true,
        'verify_not_before' => true,
        'clock_skew' => 300, // Cho phép lệch thời gian 5 phút
    ],

    // Giới hạn tốc độ gọi các endpoint liên quan đến token
    'rate_limiting' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        'refresh' => [
            'max_attempts' => 10,
            'decay_minutes' => 60,
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
    ],

    // Cấu hình cho việc tạo API key
    'api_keys' => [
        'length' => (int)($_ENV['API_KEY_LENGTH'] ?? $dotenv['API_KEY_LENGTH'] ?? 32),
        'secret_length' => (int)($_ENV['API_SECRET_LENGTH'] ?? $dotenv['API_SECRET_LENGTH'] ?? 64),
        'prefix' => 'gr_', // Tiền tố cho API key
        'allowed_characters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'hash_algorithm' => 'sha256',
    ],

    // Cấu hình nơi lưu token
    'storage' => [
        'driver' => 'database', // Có thể là: database, redis, file
        'table' => 'access_tokens',
        'refresh_table' => 'refresh_tokens',
        'blacklist_table' => 'token_blacklist',
        'api_keys_table' => 'api_keys',
        'connection' => null, // Dùng kết nối DB mặc định
    ],

    // Cấu hình bảo mật
    'security' => [
        'require_https' => false, // Chỉ dùng HTTPS (true trong production)Fop
        'check_ip_address' => false, // Ràng buộc token với IP
        'check_user_agent' => false, // Ràng buộc token với trình duyệt
        'max_concurrent_sessions' => 5, // Số session tối đa mỗi user
        'invalidate_on_password_change' => true, // Hủy token cũ khi đổi mật khẩu
        'invalidate_on_role_change' => true,     // Hủy token nếu quyền thay đổi
    ],

    // Cấu hình ghi log các sự kiện JWT
    'logging' => [
        'enabled' => true,
        'log_file' => 'storage/logs/jwt.log',
        'log_level' => 'info', // Các mức: debug, info, warning, error
        'log_events' => [
            'token_issued' => true,
            'token_verified' => false,
            'token_expired' => true,
            'token_blacklisted' => true,
            'invalid_token' => true,
            'refresh_token_used' => true,
        ],
    ],

    // Cấu hình cho môi trường dev
    'development' => [
        'debug_mode' => $_ENV['APP_DEBUG'] ?? $dotenv['APP_DEBUG'] ?? false,
        'expose_token_in_response' => false, // Chỉ dùng khi test
        'log_token_payload' => false, // Log payload khi debug
    ],

    // Các scope quyền truy cập
    'scopes' => [
        'read' => 'Read access to resources',
        'write' => 'Write access to resources',
        'admin' => 'Administrative access',
        'consoles:read' => 'Read console information',
        'consoles:write' => 'Create and update consoles',
        'rentals:read' => 'Read rental information',
        'rentals:write' => 'Create and manage rentals',
        'users:read' => 'Read user information',
        'users:write' => 'Update user information',
        'reports:read' => 'Access to reports',
    ],

    // Scope mặc định cho từng loại người dùng
    'default_scopes' => [
        'user' => ['read', 'consoles:read', 'rentals:read', 'rentals:write', 'users:read'],
        'admin' => ['*'], // Toàn quyền
        'api' => ['read', 'consoles:read', 'rentals:read'],
    ],

    // Cấu hình tự động dọn dẹp token hết hạn
    'cleanup' => [
        'enabled' => true,
        'schedule' => 'daily', // Có thể là: daily, hourly, weekly
        'batch_size' => 1000,
        'keep_expired_for_days' => 7, // Giữ token hết hạn 7 ngày để kiểm tra
    ],
];
