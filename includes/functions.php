<?php
/**
 * Utility Functions
 */

/**
 * Generate JWT Token
 */
function generateToken($user_id, $expire_time = 86400) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'iat' => time(),
        'exp' => time() + $expire_time
    ]);
    
    $header_encoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $payload_encoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_encoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

/**
 * Verify JWT Token
 */
function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) != 3) return false;
    
    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
    
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", JWT_SECRET, true);
    $signature_verified = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=') === $signature_encoded;
    
    if (!$signature_verified) return false;
    
    $payload = json_decode(base64_decode(strtr($payload_encoded, '-_', '+/')), true);
    if ($payload['exp'] < time()) return false;
    
    return $payload;
}

/**
 * Generate unique referral code
 */
function generateReferralCode() {
    return strtoupper(bin2hex(random_bytes(10)));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate API key
 */
function generateApiKey() {
    return bin2hex(random_bytes(16));
}

/**
 * Hash API key
 */
function hashApiKey($key) {
    return hash('sha256', $key);
}

/**
 * Get user balance
 */
function getUserBalance($conn, $user_id) {
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['balance'] : 0;
}

/**
 * Update user balance
 */
function updateBalance($conn, $user_id, $amount, $type = 'earn', $description = '') {
    $balance_before = getUserBalance($conn, $user_id);
    $balance_after = $balance_before + $amount;
    
    // Update user balance
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();
    
    // Log transaction
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isidds", $user_id, $type, $amount, $balance_before, $balance_after, $description);
    $stmt->execute();
    
    return $balance_after;
}

/**
 * Get current user from token
 */
function getCurrentUser($conn) {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
        $token = $matches[1];
        $payload = verifyToken($token);
        
        if ($payload) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $payload['user_id']);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
    }
    
    return null;
}

/**
 * Log audit action
 */
function logAudit($conn, $user_id, $action, $entity_type, $entity_id = null, $changes = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $changes_json = $changes ? json_encode($changes) : null;
    
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, changes, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiss", $user_id, $action, $entity_type, $entity_id, $changes_json, $ip, $user_agent);
    $stmt->execute();
}

/**
 * Send JSON response
 */
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send error response
 */
function sendError($message, $status = 400) {
    sendResponse(['success' => false, 'error' => $message], $status);
}

/**
 * Send success response
 */
function sendSuccess($message, $data = null, $status = 200) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    sendResponse($response, $status);
}

/**
 * Validate API key
 */
function validateApiKey($conn, $api_key) {
    $key_hash = hashApiKey($api_key);
    $stmt = $conn->prepare("SELECT user_id FROM api_keys WHERE key_hash = ?");
    $stmt->bind_param("s", $key_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Calculate pagination
 */
function getPagination($page = 1, $per_page = ITEMS_PER_PAGE) {
    $page = max(1, intval($page));
    $per_page = min(100, max(1, intval($per_page)));
    $offset = ($page - 1) * $per_page;
    
    return compact('page', 'per_page', 'offset');
}

/**
 * Check if user is admin
 */
function isAdmin($user) {
    return $user && $user['role'] === 'admin';
}

/**
 * Check if user is moderator
 */
function isModerator($user) {
    return $user && in_array($user['role'], ['admin', 'moderator']);
}

?>
