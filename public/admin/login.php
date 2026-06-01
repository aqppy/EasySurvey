<?php
/**
 * Admin login
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';

session_start();

$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$pageName = '管理员登录';
$pageTitle = buildPageTitle($pageName);
$error = '';

if (!checkLoginAttempts()) {
    $remaining = getLoginLockoutRemaining();
    $minutes = ceil($remaining / 60);
    $error = "登录尝试过多，请 {$minutes} 分钟后再试";
}

if (isLoggedIn()) {
    redirect('/admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrfToken)) {
        $error = '安全校验失败，请刷新页面后重试';
    } elseif ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        resetLoginAttempts();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        redirect('/admin/index.php');
    } else {
        recordLoginFailure();
        $attempts = $_SESSION['login_attempts']['count'] ?? 0;
        $remaining = LOGIN_MAX_ATTEMPTS - $attempts;
        if ($remaining > 0) {
            $error = "用户名或密码错误，还剩 {$remaining} 次尝试机会";
        } else {
            $error = '登录尝试过多，请稍后再试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        :root {
            --theme-color: <?php echo e(getAppThemeColor()); ?>;
        }
    </style>
</head>
<body data-admin-page="1" data-page-title-suffix="<?php echo e($pageName); ?>">
    <div class="login-container">
        <div class="login-card">
            <div class="admin-brand admin-brand-center">
                <img
                    data-app-logo
                    class="admin-brand-logo"
                    src="<?php echo e($appLogoUrl); ?>"
                    alt="<?php echo e($appName); ?>"
                    style="<?php echo $appLogoUrl === '' ? 'display:none;' : ''; ?>"
                >
                <h2 data-app-name><?php echo e($appName); ?></h2>
            </div>
            <p style="margin-bottom:16px; color:#666; text-align:center;">管理员登录</p>

            <?php if ($error): ?>
                <div class="login-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">登录</button>
            </form>
        </div>
    </div>
    <?php echo renderAppFooter('admin-footer admin-footer-login'); ?>
    <script src="/assets/js/main.js"></script>
</body>
</html>
