<?php
/**
 * Admin dashboard
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();
$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$pageName = '仪表盘';
$pageTitle = buildPageTitle($pageName);

$stats = [];
$stats['survey_count'] = $db->query("SELECT COUNT(*) AS count FROM surveys")->fetch()['count'];
$stats['active_survey_count'] = $db->query("SELECT COUNT(*) AS count FROM surveys WHERE status = 1")->fetch()['count'];
$stats['response_count'] = $db->query("SELECT COUNT(*) AS count FROM responses")->fetch()['count'];
$stats['today_response_count'] = $db->query("SELECT COUNT(*) AS count FROM responses WHERE DATE(submitted_at) = CURDATE()")->fetch()['count'];
$recentSurveys = $db->query("SELECT id, title, status, created_at FROM surveys ORDER BY id DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        :root {
            --theme-color: <?php echo e(getAppThemeColor()); ?>;
        }
    </style>
</head>
<body data-admin-page="1" data-page-title-suffix="<?php echo e($pageName); ?>">
    <div class="container">
        <div class="admin-header">
            <div class="admin-brand">
                <img
                    data-app-logo
                    class="admin-brand-logo"
                    src="<?php echo e($appLogoUrl); ?>"
                    alt="<?php echo e($appName); ?>"
                    style="<?php echo $appLogoUrl === '' ? 'display:none;' : ''; ?>"
                >
                <h1 data-app-name><?php echo e($appName); ?></h1>
            </div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="active">仪表盘</a>
                <a href="/admin/surveys.php">问卷管理</a>
                <a href="/admin/responses.php">数据查看</a>
                <a href="/admin/qrcode.php">二维码生成</a>
                <a href="/admin/system.php">系统设置</a>
                <a href="/admin/logout.php" class="logout">退出登录</a>
            </nav>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom:20px;">
            <div class="card" style="text-align:center;">
                <div style="font-size:2rem; color:#1890ff; font-weight:bold;"><?php echo $stats['survey_count']; ?></div>
                <div style="color:#666;">问卷总数</div>
            </div>
            <div class="card" style="text-align:center;">
                <div style="font-size:2rem; color:#52c41a; font-weight:bold;"><?php echo $stats['active_survey_count']; ?></div>
                <div style="color:#666;">启用问卷</div>
            </div>
            <div class="card" style="text-align:center;">
                <div style="font-size:2rem; color:#722ed1; font-weight:bold;"><?php echo $stats['response_count']; ?></div>
                <div style="color:#666;">总回答数</div>
            </div>
            <div class="card" style="text-align:center;">
                <div style="font-size:2rem; color:#fa8c16; font-weight:bold;"><?php echo $stats['today_response_count']; ?></div>
                <div style="color:#666;">今日回答</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>最近创建的问卷</h2>
                <a href="/admin/surveys.php" class="btn btn-primary">查看全部</a>
            </div>

            <?php if (empty($recentSurveys)): ?>
                <p style="color:#999; text-align:center; padding:20px;">还没有问卷，先去创建第一份吧。</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>标题</th>
                            <th>状态</th>
                            <th>创建时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSurveys as $survey): ?>
                            <tr>
                                <td><?php echo $survey['id']; ?></td>
                                <td><?php echo e($survey['title']); ?></td>
                                <td>
                                    <span class="status <?php echo $survey['status'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $survey['status'] ? '启用' : '停用'; ?>
                                    </span>
                                </td>
                                <td><?php echo formatDateTime($survey['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>
    <script src="/assets/js/main.js"></script>
</body>
</html>
