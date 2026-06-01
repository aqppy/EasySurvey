<?php
/**
 * 数据库连接类
 */

if (!defined('SURVEY_SYSTEM')) {
    die('Access denied');
}

// 检查配置是否已载入，如果 config.php 缺失，则载入并渲染极致动感的配置引导页面
if (!defined('DB_HOST')) {
    header('HTTP/1.1 503 Service Unavailable');
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统配置引导 - 问卷调查系统</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Noto+Sans+SC:wght@300;400;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #1677ff;
                --primary-hover: #4096ff;
                --bg: #0f172a;
                --card-bg: rgba(30, 41, 59, 0.7);
                --text: #f8fafc;
                --text-secondary: #94a3b8;
                --border: rgba(255, 255, 255, 0.08);
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
                    radial-gradient(at 0% 0%, rgba(22, 119, 255, 0.15) 0px, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(13, 148, 136, 0.15) 0px, transparent 50%);
                background-attachment: fixed;
                color: var(--text);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .setup-card {
                background: var(--card-bg);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid var(--border);
                border-radius: 24px;
                padding: 40px;
                width: 100%;
                max-width: 650px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .logo-icon {
                width: 64px;
                height: 64px;
                background: linear-gradient(135deg, var(--primary), #0d9488);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 24px;
                box-shadow: 0 8px 16px rgba(22, 119, 255, 0.2);
            }
            .logo-icon svg {
                width: 32px;
                height: 32px;
                fill: white;
            }
            h1 {
                font-size: 28px;
                font-weight: 800;
                margin-bottom: 12px;
                background: linear-gradient(to right, #ffffff, #94a3b8);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            p.subtitle {
                font-size: 16px;
                color: var(--text-secondary);
                line-height: 1.6;
                margin-bottom: 32px;
            }
            .step-box {
                background: rgba(15, 23, 42, 0.4);
                border: 1px solid var(--border);
                border-radius: 16px;
                padding: 24px;
                margin-bottom: 24px;
            }
            h3 {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 12px;
                color: #38bdf8;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .code-block {
                background: #020617;
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 8px;
                padding: 14px;
                font-family: 'Courier New', Courier, monospace;
                font-size: 14px;
                color: #e2e8f0;
                overflow-x: auto;
                margin-bottom: 12px;
                position: relative;
            }
            .code-block::after {
                content: '命令';
                position: absolute;
                right: 12px;
                top: 8px;
                font-size: 10px;
                color: #475569;
                text-transform: uppercase;
            }
            ol {
                margin-left: 20px;
                color: var(--text-secondary);
                font-size: 14px;
                line-height: 1.8;
            }
            ol li {
                margin-bottom: 6px;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #475569;
                margin-top: 32px;
            }
            .btn-refresh {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 12px 24px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none;
                margin-top: 10px;
                box-shadow: 0 4px 12px rgba(22, 119, 255, 0.2);
            }
            .btn-refresh:hover {
                background: var(--primary-hover);
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(22, 119, 255, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="setup-card">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.73,8.87 c-0.11,0.2-0.06,0.47,0.12,0.61l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12c0,0.31,0.04,0.64,0.09,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.11-0.2,0.06-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
                </svg>
            </div>
            <h1>系统配置未完成</h1>
            <p class="subtitle">欢迎使用轻量级问卷系统。为了保证系统安全运行，请先配置您的数据库和系统凭证。</p>
            
            <div class="step-box">
                <h3>1. 复制配置文件</h3>
                <p style="font-size:14px; color:var(--text-secondary); margin-bottom:12px;">在项目根目录下执行以下命令，创建本地专属配置文件（该文件已加入 Git 忽略名单，可安全存放密码）：</p>
                <div class="code-block">cp app/config.example.php app/config.php</div>
            </div>

            <div class="step-box">
                <h3>2. 修改数据库及凭据</h3>
                <p style="font-size:14px; color:var(--text-secondary); margin-bottom:12px;">使用您喜爱的编辑器打开新创建的 <code style="color:#e2e8f0; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px;">app/config.php</code>，并修改以下关键项：</p>
                <ol>
                    <li>设置正确的数据库地址和端口（<code style="color:#38bdf8;">DB_HOST</code>, <code style="color:#38bdf8;">DB_PORT</code>）</li>
                    <li>设置数据库库名、用户名与密码（<code style="color:#38bdf8;">DB_NAME</code>, <code style="color:#38bdf8;">DB_USER</code>, <code style="color:#38bdf8;">DB_PASS</code>）</li>
                    <li>生成新的管理员强密码哈希替换原有的 <code style="color:#38bdf8;">ADMIN_PASSWORD_HASH</code></li>
                </ol>
            </div>

            <div style="text-align: center;">
                <a href="" class="btn-refresh">我已配置好，刷新页面</a>
            </div>
            
            <div class="footer">
                Powered by Antigravity Survey System
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            
            // 数据库连接失败时的极致动感排错页面
            header('HTTP/1.1 500 Internal Server Error');
            ?>
            <!DOCTYPE html>
            <html lang="zh-CN">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>数据库连接失败 - 问卷调查系统</title>
                <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Noto+Sans+SC:wght@300;400;700&display=swap" rel="stylesheet">
                <style>
                    :root {
                        --danger: #ef4444;
                        --danger-hover: #dc2626;
                        --bg: #0f172a;
                        --card-bg: rgba(30, 41, 59, 0.7);
                        --text: #f8fafc;
                        --text-secondary: #94a3b8;
                        --border: rgba(255, 255, 255, 0.08);
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
                            radial-gradient(at 0% 0%, rgba(239, 68, 68, 0.1) 0px, transparent 50%),
                            radial-gradient(at 100% 100%, rgba(13, 148, 136, 0.1) 0px, transparent 50%);
                        background-attachment: fixed;
                        color: var(--text);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                    }
                    .error-card {
                        background: var(--card-bg);
                        backdrop-filter: blur(16px);
                        -webkit-backdrop-filter: blur(16px);
                        border: 1px solid var(--border);
                        border-radius: 24px;
                        padding: 40px;
                        width: 100%;
                        max-width: 650px;
                        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                        animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
                    }
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(20px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    .logo-icon {
                        width: 64px;
                        height: 64px;
                        background: linear-gradient(135deg, var(--danger), #ea580c);
                        border-radius: 16px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 24px;
                        box-shadow: 0 8px 16px rgba(239, 68, 68, 0.2);
                    }
                    .logo-icon svg {
                        width: 32px;
                        height: 32px;
                        fill: white;
                    }
                    h1 {
                        font-size: 28px;
                        font-weight: 800;
                        margin-bottom: 12px;
                        background: linear-gradient(to right, #ffffff, #f8fafc);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                    }
                    p.subtitle {
                        font-size: 16px;
                        color: var(--text-secondary);
                        line-height: 1.6;
                        margin-bottom: 32px;
                    }
                    .info-box {
                        background: rgba(239, 68, 68, 0.05);
                        border: 1px solid rgba(239, 68, 68, 0.15);
                        border-radius: 16px;
                        padding: 24px;
                        margin-bottom: 24px;
                    }
                    h3 {
                        font-size: 16px;
                        font-weight: 600;
                        margin-bottom: 12px;
                        color: #f87171;
                    }
                    .error-detail {
                        background: #020617;
                        border: 1px solid rgba(255, 255, 255, 0.05);
                        border-radius: 8px;
                        padding: 14px;
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 14px;
                        color: #fca5a5;
                        overflow-x: auto;
                        margin-bottom: 12px;
                    }
                    ul {
                        margin-left: 20px;
                        color: var(--text-secondary);
                        font-size: 14px;
                        line-height: 1.8;
                    }
                    ul li {
                        margin-bottom: 6px;
                    }
                    .footer {
                        text-align: center;
                        font-size: 12px;
                        color: #475569;
                        margin-top: 32px;
                    }
                    .btn-retry {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        background: rgba(255, 255, 255, 0.1);
                        color: white;
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        border-radius: 12px;
                        padding: 12px 24px;
                        font-size: 14px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s ease;
                        text-decoration: none;
                        margin-top: 10px;
                    }
                    .btn-retry:hover {
                        background: rgba(255, 255, 255, 0.2);
                        transform: translateY(-1px);
                    }
                </style>
            </head>
            <body>
                <div class="error-card">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12,2C6.47,2,2,6.47,2,12s4.47,10,10,10s10-4.47,10-10S17.53,2,12,2z M13,17h-2v-2h2V17z M13,13h-2V7h2V13z"/>
                        </svg>
                    </div>
                    <h1>数据库连接失败</h1>
                    <p class="subtitle">无法连接到配置的 MySQL 数据库服务器。这可能是由于配置不正确或数据库服务未启动引起的。</p>
                    
                    <div class="info-box">
                        <h3>异常错误详情：</h3>
                        <div class="error-detail"><?php echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>

                    <div class="info-box" style="background: rgba(15, 23, 42, 0.4); border-color: var(--border);">
                        <h3 style="color: #38bdf8;">常见排查建议：</h3>
                        <ul>
                            <li>确认 MySQL 服务端已在运行中且监听对应端口。</li>
                            <li>核对 <code style="color:#e2e8f0; background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px;">app/config.php</code> 中的 <code style="color:#38bdf8;">DB_HOST</code>、<code style="color:#38bdf8;">DB_PORT</code> 是否正确。</li>
                            <li>检查您的数据库用户 <code style="color:#38bdf8;">DB_USER</code> 与密码 <code style="color:#38bdf8;">DB_PASS</code> 是否与服务端创建的用户一致。</li>
                            <li>确认已经创建了 <code style="color:#38bdf8;">DB_NAME</code> 中指定的数据库。若尚未初始化，请执行根目录的 <code style="color:#38bdf8;">database.sql</code>。</li>
                        </ul>
                    </div>

                    <div style="text-align: center;">
                        <a href="" class="btn-retry">重试连接</a>
                    </div>
                    
                    <div class="footer">
                        Powered by Antigravity Survey System
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo() {
        return $this->pdo;
    }

    // 防止克隆
    private function __clone() {}

    // 防止反序列化
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton');
    }
}

// 获取数据库连接的便捷函数
function getDB() {
    return Database::getInstance()->getPdo();
}
