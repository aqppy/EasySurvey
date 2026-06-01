<?php
/**
 * QR code generator
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$surveyList = getSurveyList();
$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$webBaseUrl = getWebBaseUrl();
$pageName = '二维码生成';
$pageTitle = buildPageTitle($pageName);
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
    <style>
        .qrcode-display { text-align: center; padding: 20px; }
        .qrcode-display canvas { max-width: 300px; border: 1px solid #eee; padding: 10px; }
        .qrcode-placeholder { color: #999; text-align: center; padding: 40px; }
        #qrCanvas { display: none; margin: 0 auto 15px auto; border: 1px solid #eee; padding: 10px; }
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
                <a href="/admin/index.php">仪表盘</a>
                <a href="/admin/surveys.php">问卷管理</a>
                <a href="/admin/responses.php">数据查看</a>
                <a href="/admin/qrcode.php" class="active">二维码生成</a>
                <a href="/admin/system.php">系统设置</a>
                <a href="/admin/logout.php" class="logout">退出登录</a>
            </nav>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>生成问卷二维码</h2>
            </div>

            <p style="margin-bottom:15px; color:#666;">选择问卷后生成二维码，可下载打印给用户扫码填写。</p>
            <p style="margin-bottom:15px; color:#666;">当前生成链接基于：<code><?php echo e($webBaseUrl); ?></code></p>

            <div style="display:flex; gap:10px; align-items:center; margin-bottom:20px;">
                <select id="qrcodeSurveySelect" class="form-control" style="flex:1;">
                    <option value="">-- 请选择问卷 --</option>
                    <?php foreach ($surveyList as $survey): ?>
                        <option value="<?php echo $survey['id']; ?>" data-url="<?php echo e(buildSurveyUrl($survey['id'])); ?>">
                            <?php echo e($survey['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary" id="generateBtn">生成二维码</button>
            </div>

            <div id="qrcodeResult" style="display:none;">
                <div class="qrcode-display">
                    <h3 id="qrTitle" style="margin-bottom:15px;"></h3>
                    <canvas id="qrCanvas" width="290" height="290"></canvas>
                    <canvas id="qrDownloadCanvas" width="1024" height="1024" style="display:none;"></canvas>
                    <p style="margin-top:15px;">
                        <button type="button" class="btn btn-success" id="downloadBtn">下载二维码</button>
                    </p>
                    <p style="margin-top:10px; color:#666; font-size:0.9rem;">
                        问卷链接：<a id="qrLink" href="#" target="_blank"></a>
                    </p>
                </div>
            </div>

            <div id="qrcodePlaceholder" class="qrcode-placeholder">请选择问卷并点击“生成二维码”。</div>
        </div>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>

    <script src="/assets/js/qrcode.js"></script>
    <script>
    (function () {
        document.getElementById('generateBtn').addEventListener('click', function () {
            const select = document.getElementById('qrcodeSurveySelect');
            if (!select.value) {
                alert('请先选择问卷');
                return;
            }

            const surveyUrl = select.options[select.selectedIndex].getAttribute('data-url');
            const title = select.options[select.selectedIndex].textContent;
            const canvas = document.getElementById('qrCanvas');
            const downloadCanvas = document.getElementById('qrDownloadCanvas');
            
            canvas.style.display = 'block';

            // 1. 生成前台展示用的 290x290 规格预览二维码
            QRCode.toCanvas(canvas, surveyUrl, { width: 290, margin: 2 }, function (error) {
                if (error) {
                    alert('预览二维码生成失败: ' + error);
                    return;
                }

                // 2. 同步生成 1024x1024 高清规格的下载用离线二维码
                QRCode.toCanvas(downloadCanvas, surveyUrl, { width: 1024, margin: 2 }, function (err) {
                    if (err) {
                        console.error('高清二维码生成失败:', err);
                    }
                });

                document.getElementById('qrTitle').textContent = title;
                document.getElementById('qrLink').href = surveyUrl;
                document.getElementById('qrLink').textContent = surveyUrl;
                document.getElementById('qrcodeResult').style.display = 'block';
                document.getElementById('qrcodePlaceholder').style.display = 'none';
            });
        });

        document.getElementById('downloadBtn').addEventListener('click', function () {
            const downloadCanvas = document.getElementById('qrDownloadCanvas');
            const select = document.getElementById('qrcodeSurveySelect');
            const title = select.options[select.selectedIndex].textContent.trim();
            const link = document.createElement('a');
            link.download = 'qrcode_' + title + '.png';
            link.href = downloadCanvas.toDataURL('image/png');
            link.click();
        });
    })();
    </script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
