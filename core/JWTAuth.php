<?php
namespace core;

use Exception;

class JWTAuth
{
    private $secretKey;
    private $algorithm;
    
    public function __construct()
    {
        // Có thể lấy từ config hoặc environment
        $this->secretKey = 'your-super-secret-key-here-should-be-very-long-and-random';
        $this->algorithm = 'HS256';
    }
    
    /**
     * Tạo JWT token
     */
    public function generateToken($payload, $type = 'access_token')
    {
        try {
            $header = [
                'typ' => 'JWT',
                'alg' => $this->algorithm
            ];
            
            // Thiết lập thời gian hết hạn
            $currentTime = time();
            if ($type === 'access_token') {
                $expirationTime = $currentTime + (60 * 60); // 1 giờ
            } else {
                $expirationTime = $currentTime + (60 * 60 * 24 * 7); // 7 ngày
            }
            
            $tokenPayload = array_merge($payload, [
                'iat' => $currentTime,     // Issued at
                'exp' => $expirationTime,  // Expiration time
                'type' => $type            // Token type
            ]);
            
            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($tokenPayload));
            
            $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secretKey, true);
            $signatureEncoded = $this->base64UrlEncode($signature);
            
            $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
            
            // Loại bỏ việc log vào database để tránh lỗi
            // $this->logToken($payload['user_id'] ?? null, $type, $token, $expirationTime);
            
            return $token;
            
        } catch (Exception $e) {
            throw new Exception('Không thể tạo token: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate JWT token
     */
    public function validateToken($authHeader)
    {
        try {
            // Kiểm tra format Bearer token
            if (strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
            } else {
                $token = $authHeader;
            }
            
            if (empty($token)) {
                return false;
            }
            
            // Tách các phần của token
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secretKey, true);
            $expectedSignatureEncoded = $this->base64UrlEncode($expectedSignature);
            
            if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
                return false;
            }
            
            // Decode payload
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
            if (!$payload) {
                return false;
            }
            
            // Kiểm tra thời gian hết hạn
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            // Loại bỏ việc kiểm tra blacklist để tránh lỗi database
            // if ($this->isTokenBlacklisted($token)) {
            //     return false;
            // }
            
            return $payload;
            
        } catch (Exception $e) {
            error_log('JWT validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revoke token (thêm vào blacklist)
     * Tạm thời disable để tránh lỗi database
     */
    public function revokeToken($token)
    {
        // TODO: Implement blacklist without database dependency
        return true;
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Lấy thông tin user từ token
     */
    public function getUserFromToken($authHeader)
    {
        $payload = $this->validateToken($authHeader);
        if (!$payload) {
            return false;
        }
        
        return [
            'user_id' => $payload['user_id'] ?? null,
            'role' => $payload['role'] ?? null,
            'type' => $payload['type'] ?? null
        ];
    }
    
    /**
     * Tạo access token và refresh token
     */
    public function generateTokenPair($payload)
    {
        return [
            'access_token' => $this->generateToken($payload, 'access_token'),
            'refresh_token' => $this->generateToken($payload, 'refresh_token'),
            'token_type' => 'Bearer',
            'expires_in' => 3600 // 1 giờ
        ];
    }
    
    /**
     * Log token vào database (disabled để tránh lỗi)
     */
    private function logToken($userId, $type, $token, $expirationTime)
    {
        // Tạm thời disable để tránh lỗi database
        // TODO: Implement sau khi tạo bảng token_logs
        return true;
    }
    
    /**
     * Kiểm tra token có trong blacklist không (disabled)
     */
    private function isTokenBlacklisted($token)
    {
        // Tạm thời disable để tránh lỗi database
        // TODO: Implement sau khi tạo bảng token_logs
        return false;
    }
}
?>