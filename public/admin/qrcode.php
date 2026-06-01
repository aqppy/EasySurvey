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
$pageName = __('nav_qrcode');
$pageTitle = buildPageTitle($pageName);
?>
<!DOCTYPE html>
<html lang="<?php echo getSystemLanguage() === 'zh' ? 'zh-CN' : 'en'; ?>">
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
                <a href="/admin/index.php"><?php echo __('nav_dashboard'); ?></a>
                <a href="/admin/surveys.php"><?php echo __('nav_surveys'); ?></a>
                <a href="/admin/responses.php"><?php echo __('nav_responses'); ?></a>
                <a href="/admin/qrcode.php" class="active"><?php echo __('nav_qrcode'); ?></a>
                <a href="/admin/system.php"><?php echo __('nav_system'); ?></a>
                <a href="?lang_toggle=1" class="lang-toggle" style="margin-left:auto; color:var(--theme-color); font-weight:600;"><?php echo __('lang_toggle'); ?></a>
                <a href="/admin/logout.php" class="logout"><?php echo __('nav_logout'); ?></a>
            </nav>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><?php echo __('qr_title'); ?></h2>
            </div>

            <p style="margin-bottom:15px; color:#666;"><?php echo __('qr_help'); ?></p>
            <p style="margin-bottom:15px; color:#666;"><?php echo __('sys_base_url'); ?>: <code><?php echo e($webBaseUrl); ?></code></p>

            <div style="display:flex; gap:10px; align-items:center; margin-bottom:20px;">
                <select id="qrcodeSurveySelect" class="form-control" style="flex:1;">
                    <option value="">-- <?php echo __('resp_select_survey'); ?> --</option>
                    <?php foreach ($surveyList as $survey): ?>
                        <option value="<?php echo $survey['id']; ?>" data-url="<?php echo e(buildSurveyUrl($survey['id'])); ?>">
                            <?php echo e($survey['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-primary" id="generateBtn"><?php echo __('qr_title'); ?></button>
            </div>

            <div id="qrcodeResult" style="display:none;">
                <div class="qrcode-display">
                    <h3 id="qrTitleText" style="margin-bottom:15px;"></h3>
                    <canvas id="qrCanvas" width="290" height="290"></canvas>
                    <canvas id="qrDownloadCanvas" width="1024" height="1024" style="display:none;"></canvas>
                    <p style="margin-top:15px;">
                        <button type="button" class="btn btn-success" id="downloadBtn"><?php echo __('qr_download_btn'); ?></button>
                    </p>
                    <p style="margin-top:10px; color:#666; font-size:0.9rem;">
                        <?php echo __('qr_preview_text'); ?>: <a id="qrLink" href="#" target="_blank"></a>
                    </p>
                </div>
            </div>

            <div id="qrcodePlaceholder" class="qrcode-placeholder"><?php echo __('qr_help'); ?></div>
        </div>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>

    <script src="/assets/js/qrcode.js"></script>
    <script>
    (function () {
        document.getElementById('generateBtn').addEventListener('click', function () {
            const select = document.getElementById('qrcodeSurveySelect');
            if (!select.value) {
                alert("<?php echo e(__('resp_select_survey')); ?>");
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
                    alert('QR code generation failed: ' + error);
                    return;
                }

                // 2. 同步生成 1024x1024 高清规格的下载用离线二维码
                QRCode.toCanvas(downloadCanvas, surveyUrl, { width: 1024, margin: 2 }, function (err) {
                    if (err) {
                        console.error('HD QR code generation failed:', err);
                    }
                });

                document.getElementById('qrTitleText').textContent = title;
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
    <?php echo getJsLangBridgeHtml(); ?>
    <script src="/assets/js/main.js"></script>
</body>
</html>
