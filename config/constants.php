<?php
/**
 * Application Constants
 */

define('APP_NAME', 'OfferWall');
define('APP_VERSION', '1.0.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('API_URL', APP_URL . '/api');

// Security
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('API_KEY_LENGTH', 32);

// Reward settings
define('MIN_REWARD_AMOUNT', 0.01);
define('MAX_REWARD_AMOUNT', 10000);
define('WITHDRAWAL_MIN', 1);
define('WITHDRAWAL_MAX', 5000);

// Offer settings
define('OFFER_STATUS_ACTIVE', 'active');
define('OFFER_STATUS_INACTIVE', 'inactive');
define('OFFER_STATUS_EXPIRED', 'expired');

// User roles
define('ROLE_USER', 'user');
define('ROLE_ADMIN', 'admin');
define('ROLE_MODERATOR', 'moderator');

// Pagination
define('ITEMS_PER_PAGE', 20);

// File upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

?>
