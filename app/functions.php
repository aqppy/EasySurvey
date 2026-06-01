<?php
/**
 * Shared helpers
 */

if (!defined('SURVEY_SYSTEM')) {
    die('Access denied');
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// 语言切换请求拦截
if (isset($_GET['lang_toggle'])) {
    $currentLang = (!empty($_SESSION['admin_lang']) ? $_SESSION['admin_lang'] : 'zh');
    if (!empty($_COOKIE['admin_lang']) && empty($_SESSION['admin_lang'])) {
        $currentLang = $_COOKIE['admin_lang'];
    }
    $newLang = ($currentLang === 'zh' ? 'en' : 'zh');
    
    $_SESSION['admin_lang'] = $newLang;
    setcookie('admin_lang', $newLang, time() + 31536000, '/', '', false, true);
    
    $uri = $_SERVER['REQUEST_URI'];
    $cleanUri = preg_replace('/[?&]lang_toggle=[^&]+/', '', $uri);
    $cleanUri = preg_replace('/[?&]$/', '', $cleanUri);
    if ($cleanUri === '') {
        $cleanUri = '/';
    }
    
    header('Location: ' . $cleanUri);
    exit;
}

// 针对旧版本 config.php 兼容性提供的安全默认配置兜底
if (!defined('APP_NAME')) {
    define('APP_NAME', '问卷调查系统');
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}
if (!defined('LOGIN_LOCKOUT_SECONDS')) {
    define('LOGIN_LOCKOUT_SECONDS', 900);
}
if (!defined('LOGIN_MAX_ATTEMPTS')) {
    define('LOGIN_MAX_ATTEMPTS', 10);
}
if (!defined('IP_LIMIT_WINDOW')) {
    define('IP_LIMIT_WINDOW', 600);
}
if (!defined('IP_LIMIT_MAX')) {
    define('IP_LIMIT_MAX', 20);
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . '/public');
}

function jsonResponse($code, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function e($string) {
    return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
}

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function checkLoginAttempts() {
    if (!isset($_SESSION['login_attempts'])) {
        return true;
    }

    $attempts = $_SESSION['login_attempts'];
    if (time() - $attempts['last_time'] >= LOGIN_LOCKOUT_SECONDS) {
        unset($_SESSION['login_attempts']);
        return true;
    }

    return $attempts['count'] < LOGIN_MAX_ATTEMPTS;
}

function recordLoginFailure() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = ['count' => 0, 'last_time' => time()];
    }

    $_SESSION['login_attempts']['count']++;
    $_SESSION['login_attempts']['last_time'] = time();
}

function getLoginLockoutRemaining() {
    if (!isset($_SESSION['login_attempts'])) {
        return 0;
    }

    $remaining = LOGIN_LOCKOUT_SECONDS - (time() - $_SESSION['login_attempts']['last_time']);
    return max(0, $remaining);
}

function resetLoginAttempts() {
    unset($_SESSION['login_attempts']);
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/admin/login.php');
    }
}

function getSurveyList() {
    $db = getDB();
    return $db->query("SELECT id, title FROM surveys ORDER BY id DESC")->fetchAll();
}

function getSurveyWithQuestions($surveyId) {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch();

    if (!$survey) {
        return null;
    }

    $stmt = $db->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$surveyId]);
    $questions = $stmt->fetchAll();

    foreach ($questions as &$question) {
        if ($question['options']) {
            $question['options'] = json_decode($question['options'], true);
        }
    }
    unset($question);

    $survey['questions'] = $questions;
    return $survey;
}

function getDefaultSurveySettings() {
    return [
        'allow_repeat_submit' => false,
        'thank_you_title' => '感谢您的填写',
        'thank_you_message' => '您的意见对我们非常重要。',
        'show_description' => true,
        'show_number' => true
    ];
}

function getDefaultSurveyTheme() {
    return [
        'logo_url' => '',
        'header_image_url' => '',
        'theme_color' => '#1677ff',
        'background_color' => '#f5f7fa',
        'background_image_url' => '',
        'submit_button_text' => '提交',
        'show_title' => true,
        'show_description' => true,
        'show_number' => true
    ];
}

function getSurveySettings($surveyId) {
    ensureAppSettingsTable();
    $db = getDB();
    $defaults = getDefaultSurveySettings();

    $stmt = $db->prepare("SELECT settings_json FROM survey_settings WHERE survey_id = ?");
    $stmt->execute([$surveyId]);
    $row = $stmt->fetch();

    if (!$row || empty($row['settings_json'])) {
        return $defaults;
    }

    $settings = json_decode($row['settings_json'], true);
    return is_array($settings) ? array_merge($defaults, $settings) : $defaults;
}

function getSurveyTheme($surveyId) {
    ensureAppSettingsTable();
    $db = getDB();
    $defaults = getDefaultSurveyTheme();

    $stmt = $db->prepare("
        SELECT logo_url, header_image_url, theme_color, background_color,
               background_image_url, submit_button_text, show_title,
               show_description, show_number
        FROM survey_themes
        WHERE survey_id = ?
    ");
    $stmt->execute([$surveyId]);
    $row = $stmt->fetch();

    if (!$row) {
        return $defaults;
    }

    $theme = array_merge($defaults, $row);
    $theme['show_title'] = isset($row['show_title']) && $row['show_title'] !== null ? (bool)$row['show_title'] : $defaults['show_title'];
    $theme['show_description'] = isset($row['show_description']) && $row['show_description'] !== null ? (bool)$row['show_description'] : $defaults['show_description'];
    $theme['show_number'] = isset($row['show_number']) && $row['show_number'] !== null ? (bool)$row['show_number'] : $defaults['show_number'];

    return $theme;
}

function saveSurveySettings($surveyId, array $settings) {
    ensureAppSettingsTable();
    $db = getDB();
    $payload = json_encode(array_merge(getDefaultSurveySettings(), $settings), JSON_UNESCAPED_UNICODE);

    $stmt = $db->prepare("
        INSERT INTO survey_settings (survey_id, settings_json)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE settings_json = VALUES(settings_json)
    ");

    return $stmt->execute([$surveyId, $payload]);
}

function saveSurveyTheme($surveyId, array $theme) {
    ensureAppSettingsTable();
    $db = getDB();
    $theme = array_merge(getDefaultSurveyTheme(), $theme);

    $stmt = $db->prepare("
        INSERT INTO survey_themes (
            survey_id, logo_url, header_image_url, theme_color, background_color,
            background_image_url, submit_button_text, show_title, show_description, show_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            logo_url = VALUES(logo_url),
            header_image_url = VALUES(header_image_url),
            theme_color = VALUES(theme_color),
            background_color = VALUES(background_color),
            background_image_url = VALUES(background_image_url),
            submit_button_text = VALUES(submit_button_text),
            show_title = VALUES(show_title),
            show_description = VALUES(show_description),
            show_number = VALUES(show_number)
    ");

    return $stmt->execute([
        $surveyId,
        $theme['logo_url'],
        $theme['header_image_url'],
        $theme['theme_color'],
        $theme['background_color'],
        $theme['background_image_url'],
        $theme['submit_button_text'],
        !empty($theme['show_title']) ? 1 : 0,
        !empty($theme['show_description']) ? 1 : 0,
        !empty($theme['show_number']) ? 1 : 0
    ]);
}

function handleUploadedImage($file, $subDir, $prefix = 'image') {
    if (!isset($file) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('图片上传失败，请重试');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('图片大小不能超过 2MB');
    }

    $tmpName = $file['tmp_name'] ?? '';
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('未识别到有效的上传文件');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpName) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedTypes = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('仅支持 PNG、JPG、GIF 或 WEBP 图片');
    }

    $subDir = trim(str_replace('\\', '/', $subDir), '/');
    $uploadDir = PUBLIC_PATH . '/uploads/' . $subDir;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('无法创建上传目录');
    }

    $fileName = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$mimeType];
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('图片保存失败，请检查目录权限');
    }

    return '/uploads/' . $subDir . '/' . $fileName;
}

function ensureAppSettingsTable() {
    static $initialized = false;

    if ($initialized) {
        return;
    }

    try {
        $db = getDB();
        
        // 1. app_settings Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT DEFAULT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 2. survey_settings Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS survey_settings (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                survey_id INT UNSIGNED NOT NULL,
                settings_json JSON NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_survey_settings_survey_id (survey_id),
                CONSTRAINT fk_survey_settings_survey FOREIGN KEY (survey_id) REFERENCES surveys (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. survey_themes Table
        $db->exec("
            CREATE TABLE IF NOT EXISTS survey_themes (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                survey_id INT UNSIGNED NOT NULL,
                logo_url VARCHAR(500) DEFAULT NULL,
                header_image_url VARCHAR(500) DEFAULT NULL,
                theme_color VARCHAR(20) DEFAULT NULL,
                background_color VARCHAR(20) DEFAULT NULL,
                background_image_url VARCHAR(500) DEFAULT NULL,
                submit_button_text VARCHAR(100) DEFAULT NULL,
                show_title TINYINT NOT NULL DEFAULT 1,
                show_description TINYINT NOT NULL DEFAULT 1,
                show_number TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_survey_themes_survey_id (survey_id),
                CONSTRAINT fk_survey_themes_survey FOREIGN KEY (survey_id) REFERENCES surveys (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $initialized = true;
    } catch (Throwable $e) {
        error_log('Database schema initialization failed: ' . $e->getMessage());
    }
}

function getDefaultAppSettings() {
    return [
        'app_name' => APP_NAME,
        'app_logo_url' => '',
        'browser_title_template' => '{page} - {app}',
        'web_base_url' => SITE_URL,
        'open_wx_mp_login' => '0',
        'copyright' => '',
        'theme_color' => '#1677ff',
        'default_language' => 'zh'
    ];
}

function getAppSettings() {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $defaults = getDefaultAppSettings();

    try {
        ensureAppSettingsTable();
        $db = getDB();
        $rows = $db->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll();
        $settings = $defaults;

        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $cached = $settings;
        return $cached;
    } catch (Throwable $e) {
        return $defaults;
    }
}

function getSystemLanguage() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['admin_lang'])) {
        return $_SESSION['admin_lang'];
    }
    if (!empty($_COOKIE['admin_lang'])) {
        return $_COOKIE['admin_lang'];
    }
    $settings = getAppSettings();
    $default = trim((string)($settings['default_language'] ?? 'zh'));
    return $default !== '' ? $default : 'zh';
}

function __($key, $default = '') {
    static $dictionary = null;
    if ($dictionary === null) {
        $lang = getSystemLanguage();
        $file = __DIR__ . "/languages/{$lang}.php";
        $dictionary = is_file($file) ? include $file : [];
    }
    return $dictionary[$key] ?? ($default !== '' ? $default : $key);
}

function getJsLangBridgeHtml() {
    $lang = getSystemLanguage();
    $file = __DIR__ . "/languages/{$lang}.php";
    $dictionary = is_file($file) ? include $file : [];
    
    $jsTrans = [];
    $extraKeys = [
        'survey_q_title',
        'survey_q_title_placeholder',
        'survey_q_type',
        'survey_type_radio',
        'survey_type_checkbox',
        'survey_type_text',
        'survey_options_label',
        'survey_option_placeholder',
        'survey_add_option',
        'required_field',
        'edit',
        'delete',
        'resp_detail_q'
    ];
    
    foreach ($dictionary as $key => $value) {
        if (strpos($key, 'js_') === 0 || in_array($key, $extraKeys, true)) {
            $jsTrans[$key] = $value;
        }
    }
    
    return '<script>window.SurveyLang = ' . json_encode($jsTrans, JSON_UNESCAPED_UNICODE) . ';</script>';
}

function getAppName() {
    $settings = getAppSettings();
    return trim((string) ($settings['app_name'] ?? APP_NAME)) ?: APP_NAME;
}

function getAppLogoUrl() {
    $settings = getAppSettings();
    return trim((string) ($settings['app_logo_url'] ?? ''));
}

function getBrowserTitleTemplate() {
    $settings = getAppSettings();
    $template = trim((string) ($settings['browser_title_template'] ?? ''));
    return $template !== '' ? $template : '{page} - {app}';
}

function getWebBaseUrl() {
    // 自动适配本地化部署及动态域名/IP
    if (isset($_SERVER['HTTP_HOST'])) {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }

    $settings = getAppSettings();
    $url = trim((string) ($settings['web_base_url'] ?? ''));
    return $url !== '' ? $url : SITE_URL;
}

function getAppThemeColor() {
    $settings = getAppSettings();
    $color = trim((string) ($settings['theme_color'] ?? ''));
    return $color !== '' ? $color : '#1677ff';
}

function normalizeBaseUrl($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    return rtrim($url, '/');
}

function buildSurveyUrl($surveyId) {
    $baseUrl = normalizeBaseUrl(getWebBaseUrl());
    $surveyId = intval($surveyId);
    return $baseUrl . '/index.php?id=' . $surveyId;
}

function isWxMpLoginEnabled() {
    $settings = getAppSettings();
    return !empty($settings['open_wx_mp_login']) && $settings['open_wx_mp_login'] !== '0';
}

function getAppCopyright() {
    $settings = getAppSettings();
    return trim((string) ($settings['copyright'] ?? ''));
}

function renderAppFooter($extraClass = '') {
    $copyright = getAppCopyright();
    $className = trim('app-footer ' . $extraClass);
    $style = $copyright === '' ? ' style="display:none;"' : '';
    return '<footer class="' . e($className) . '"' . $style . '><div class="app-footer-text" data-app-copyright>' . e($copyright) . '</div></footer>';
}

function buildPageTitle($pageTitle = '') {
    $appName = getAppName();
    $template = getBrowserTitleTemplate();
    $page = trim((string) $pageTitle);

    if ($page === '') {
        $page = $appName;
    }

    $result = str_replace(
        ['{page}', '{app}'],
        [$page, $appName],
        $template
    );

    return trim($result) !== '' ? $result : ($page ? $page . ' - ' . $appName : $appName);
}

function saveAppSettings(array $settings) {
    ensureAppSettingsTable();
    $db = getDB();
    $merged = array_merge(getDefaultAppSettings(), $settings);
    $stmt = $db->prepare("
        INSERT INTO app_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    foreach ($merged as $key => $value) {
        $stmt->execute([$key, (string) $value]);
    }

    return true;
}

function checkIPSubmissionLimit($ip, $surveyId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) AS count
        FROM responses
        WHERE ip_address = ? AND survey_id = ?
          AND submitted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$ip, $surveyId, IP_LIMIT_WINDOW]);
    $result = $stmt->fetch();

    return intval($result['count']) < IP_LIMIT_MAX;
}

function formatDateTime($datetime) {
    if (!$datetime) {
        return '-';
    }
    return date('Y-m-d H:i:s', strtotime($datetime));
}

/**
 * 安全删除已上传的物理图片文件
 * @param string $fileUrl
 * @return bool
 */
function deleteUploadedFile($fileUrl) {
    $fileUrl = trim((string) $fileUrl);
    if ($fileUrl === '') {
        return false;
    }

    // 只允许删除以 /uploads/ 开头的相对路径文件
    if (strpos($fileUrl, '/uploads/') !== 0) {
        return false;
    }

    // 防止目录遍历
    if (strpos($fileUrl, '..') !== false) {
        return false;
    }

    // 获取物理路径
    $filePath = PUBLIC_PATH . $fileUrl;

    // 确认物理路径在 public/uploads/ 目录下且文件存在
    if (is_file($filePath)) {
        $realUploadsDir = realpath(PUBLIC_PATH . '/uploads/');
        $realFilePath = realpath($filePath);
        
        if ($realUploadsDir && $realFilePath && strpos($realFilePath, $realUploadsDir) === 0) {
            return unlink($realFilePath);
        }
    }

    return false;
}
