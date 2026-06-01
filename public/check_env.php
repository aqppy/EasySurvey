<?php
/**
 * Survey System Environment Checker
 */

define('SURVEY_SYSTEM', true);

// Attempt to load the configuration safely
@include_once __DIR__ . '/../app/config.php';

$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.0.0', '>=');

$requiredExtensions = [
    'pdo' => 'PDO 数据库驱动',
    'pdo_mysql' => 'PDO MySQL 数据库连接扩展',
    'session' => 'Session 会话支持',
    'gd' => 'GD 图像处理扩展',
    'mbstring' => '多字节字符串处理 mbstring'
];

$extensionResults = [];
foreach ($requiredExtensions as $ext => $desc) {
    $extensionResults[$ext] = [
        'name' => $desc,
        'ok' => extension_loaded($ext)
    ];
}

$writablePaths = [
    '/uploads' => '图片文件上传主目录',
    '/uploads/system' => '系统级别 Logo 目录',
    '/uploads/surveys' => '问卷背景、配图与 Logo 目录'
];

$writableResults = [];
foreach ($writablePaths as $path => $desc) {
    $fullPath = __DIR__ . $path;
    // Try to automatically create if it doesn't exist
    if (!is_dir($fullPath)) {
        @mkdir($fullPath, 0775, true);
    }
    $writableResults[$path] = [
        'desc' => $desc,
        'exists' => is_dir($fullPath),
        'writable' => is_writable($fullPath),
        'path' => 'public' . $path
    ];
}

$dbOk = false;
$dbError = null;
$configOk = defined('DB_HOST');

if ($configOk) {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $dbOk = true;
    } catch (PDOException $e) {
        $dbError = $e->getMessage();
    }
}

$allSystemOk = $phpOk && $dbOk && $configOk;
foreach ($extensionResults as $r) {
    if (!$r['ok']) $allSystemOk = false;
}
foreach ($writableResults as $r) {
    if (!$r['writable']) $allSystemOk = false;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>一键运行环境检测 - 问卷调查系统</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Noto+Sans+SC:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1677ff;
            --primary-hover: #4096ff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #0b0f19;
            --card-bg: rgba(22, 28, 45, 0.65);
            --text: #f8fafc;
            --text-secondary: #94a3b8;
            --border: rgba(255, 255, 255, 0.06);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Outfit', 'Noto Sans SC', sans-serif;
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(22, 119, 255, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            color: var(--text);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: 100%;
            max-width: 800px;
        }
        .main-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            padding-bottom: 24px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .brand h1 {
            font-size: 26px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .brand p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .overall-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 99px;
            font-size: 14px;
            font-weight: 600;
        }
        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }
        .badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #38bdf8;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-card {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }
        .check-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .check-item:first-child {
            padding-top: 0;
        }
        .item-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .item-info .title {
            font-weight: 600;
            font-size: 15px;
        }
        .item-info .desc {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .item-status {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-pass {
            color: var(--success);
        }
        .status-fail {
            color: var(--danger);
        }
        .status-warn {
            color: var(--warning);
        }
        .actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 32px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(22, 119, 255, 0.2);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(22, 119, 255, 0.3);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }
        .db-err-box {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.15);
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
            font-family: monospace;
            font-size: 12px;
            color: #fca5a5;
            word-break: break-all;
        }
        footer {
            text-align: center;
            font-size: 12px;
            color: #475569;
            margin-top: 24px;
        }
        svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <header>
                <div class="brand">
                    <h1>环境与连接一键诊断</h1>
                    <p>检测本地 PHP 运行环境、依赖项以及数据连通状态</p>
                </div>
                <div>
                    <?php if ($allSystemOk): ?>
                        <div class="overall-badge badge-success">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            就绪，可以上线
                        </div>
                    <?php else: ?>
                        <div class="overall-badge badge-danger">
                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            诊断异常，需手动修复
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- 1. PHP Base Check -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.73,8.87 c-0.11,0.2-0.06,0.47,0.12,0.61l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12c0,0.31,0.04,0.64,0.09,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.11-0.2,0.06-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>
                PHP 核心运行环境
            </div>
            <div class="section-card">
                <div class="check-item">
                    <div class="item-info">
                        <span class="title">PHP 版本</span>
                        <span class="desc">(要求 >= 8.0.0)</span>
                    </div>
                    <div class="item-status <?php echo $phpOk ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $phpVersion; ?>
                        <?php echo $phpOk ? ' (符合要求)' : ' (版本过低)'; ?>
                    </div>
                </div>
            </div>

            <!-- 2. PHP Extensions -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M4,17.2V20h2.8l8.5-8.5l-2.8-2.8L4,17.2z M20.7,7c0.3-0.3,0.3-0.8,0-1.1l-1.8-1.8c-0.3-0.3-0.8-0.3-1.1,0l-1.4,1.4 l2.8,2.8L20.7,7z"/></svg>
                关键 PHP 组件扩展
            </div>
            <div class="section-card">
                <?php foreach ($extensionResults as $ext => $r): ?>
                    <div class="check-item">
                        <div class="item-info">
                            <span class="title"><?php echo $ext; ?></span>
                            <span class="desc">(<?php echo $r['name']; ?>)</span>
                        </div>
                        <div class="item-status <?php echo $r['ok'] ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $r['ok'] ? '已开启 (Pass)' : '未开启 (Fail)'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 3. Write Permissions -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M20,6h-8l-2-2H4C2.9,4,2,4.9,2,6v12c0,1.1,0.9,2,2,2h16c1.1,0,2-0.9,2-2V8C22,6.9,21.1,6,20,6z M20,18H4V8h16V18z"/></svg>
                目录写权限检测
            </div>
            <div class="section-card">
                <?php foreach ($writableResults as $path => $r): ?>
                    <div class="check-item">
                        <div class="item-info">
                            <span class="title"><?php echo $r['path']; ?></span>
                            <span class="desc">(<?php echo $r['desc']; ?>)</span>
                        </div>
                        <div class="item-status <?php echo $r['writable'] ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $r['writable'] ? '可写 (Writable)' : '只读 (ReadOnly)'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 4. Database Link Check -->
            <div class="section-title">
                <svg viewBox="0 0 24 24"><path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2zm-1,17.93c-3.95-0.49-7-3.85-7-7.93c0-0.62,0.08-1.21,0.21-1.79L9,15v1c0,1.1,0.9,2,2,2v1.93zm6.9-2.54c-0.26-0.81-1-1.39-1.9-1.39h-1v-3c0-0.55-0.45-1-1-1H8v-2h2c0.55,0,1-0.45,1-1V7h2c1.1,0,2-0.9,2-2v-0.41c2.93,1.19,5,4.06,5,7.41c0,2.08-0.8,3.97-2.1,5.39z"/></svg>
                数据库配置与连通状态
            </div>
            <div class="section-card">
                <div class="check-item">
                    <div class="item-info">
                        <span class="title">配置文件 (app/config.php)</span>
                        <span class="desc">检测文件是否存在</span>
                    </div>
                    <div class="item-status <?php echo $configOk ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $configOk ? '已就绪 (Exist)' : '不存在 (Missing)'; ?>
                    </div>
                </div>
                
                <?php if ($configOk): ?>
                    <div class="check-item">
                        <div class="item-info">
                            <span class="title">MySQL 服务连接</span>
                            <span class="desc">(Dsn: mysql:host=<?php echo DB_HOST; ?>;port=<?php echo DB_PORT; ?>;dbname=<?php echo DB_NAME; ?>)</span>
                        </div>
                        <div class="item-status <?php echo $dbOk ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $dbOk ? '已连接 (Connected)' : '失败 (Failed)'; ?>
                        </div>
                    </div>
                    
                    <?php if (!$dbOk && $dbError): ?>
                        <div class="db-err-box">
                            <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="actions">
                <a href="" class="btn btn-secondary">重新检测</a>
                <?php if ($allSystemOk): ?>
                    <a href="/admin/login.php" class="btn btn-primary">前往后台管理 →</a>
                <?php endif; ?>
            </div>
        </div>

        <footer>
            Powered by Antigravity Survey Checker
        </footer>
    </div>
</body>
</html>
