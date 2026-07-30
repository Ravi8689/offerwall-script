<?php
/**
 * Authentication Middleware
 */

/**
 * Check if user is authenticated
 */
function checkAuth($conn) {
    $user = getCurrentUser($conn);
    if (!$user) {
        sendError('Unauthorized: Invalid or missing token', 401);
    }
    return $user;
}

/**
 * Check if user is admin
 */
function checkAdmin($conn) {
    $user = checkAuth($conn);
    if ($user['role'] !== 'admin') {
        sendError('Forbidden: Admin access required', 403);
    }
    return $user;
}

/**
 * Check if user is moderator
 */
function checkModerator($conn) {
    $user = checkAuth($conn);
    if (!in_array($user['role'], ['admin', 'moderator'])) {
        sendError('Forbidden: Moderator access required', 403);
    }
    return $user;
}

/**
 * Check CORS and set headers
 */
function handleCORS() {
    $allowed_origins = explode(',', getenv('ALLOWED_ORIGINS') ?: '*');
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array('*', $allowed_origins) || in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
    }
    
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");
    header("Access-Control-Allow-Credentials: true");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Validate request method
 */
function validateMethod($allowed_methods) {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, (array)$allowed_methods)) {
        sendError("Method {$method} not allowed", 405);
    }
}

/**
 * Get JSON body
 */
function getJsonBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

/**
 * Rate limiting
 */
function checkRateLimit($conn, $user_id, $limit = 100, $window = 3600) {
    $cache_key = "rate_limit:{$user_id}";
    $count = 0;
    
    // Simple rate limiting using database
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM audit_logs WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param("ii", $user_id, $window);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] >= $limit) {
        sendError('Rate limit exceeded', 429);
    }
}

?>
