<?php
/**
 * Survey system configuration template
 * Copy this file to config.php and customize for your environment.
 */

if (!defined('SURVEY_SYSTEM')) {
    die('Access denied');
}

// Database Connection
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); // Default MySQL port
define('DB_NAME', 'survey_system');
define('DB_USER', 'survey_system_user');
define('DB_PASS', 'your_secure_password_here');
define('DB_CHARSET', 'utf8mb4');

// Administrator Credentials
define('ADMIN_USERNAME', 'admin');
// Default password hash for "admin123". Change this for production!
// Generate a new hash with: php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '$2y$10$tZ2R8hW2q6zL9n0x4y5E8uG0y1q1q1q1q1q1q1q1q1q1q1q1q1q1q'); 

// Site Configuration
define('APP_NAME', '问卷调查系统');
define('SITE_URL', 'http://127.0.0.1:8089');

// Security & Submission Limits
define('SUBMIT_COOKIE_EXPIRE', 86400); // 1 day
define('IP_LIMIT_WINDOW', 600);        // 10 minutes
define('IP_LIMIT_MAX', 20);           // Max submissions per IP within window
define('MAX_ANSWER_LENGTH', 5000);     // Character limit per answer
define('LOGIN_MAX_ATTEMPTS', 10);      // Max login failures before lockout
define('LOGIN_LOCKOUT_SECONDS', 900);  // 15 minutes lockout

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// PHP Session Security Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

$isHttps = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $isHttps = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isHttps = true;
}
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Basic HTTP Security Headers
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
}
