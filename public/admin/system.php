<?php
/**
 * Admin system settings
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        jsonResponse(1, '安全校验失败，请刷新后重试');
    }

    $appName = trim($_POST['app_name'] ?? '');
    $currentLogoUrl = trim($_POST['current_app_logo_url'] ?? '');
    $browserTitleTemplate = trim($_POST['browser_title_template'] ?? '');
    $webBaseUrl = trim($_POST['web_base_url'] ?? '');
    $openWxMpLogin = !empty($_POST['open_wx_mp_login']) ? '1' : '0';
    $copyright = trim($_POST['copyright'] ?? '');
    $removeLogo = !empty($_POST['remove_app_logo']);
    $themeColor = trim($_POST['theme_color'] ?? '');

    if ($appName === '') {
        jsonResponse(1, '系统名称不能为空');
    }

    if ($browserTitleTemplate === '') {
        $browserTitleTemplate = '{page} - {app}';
    }

    if ($webBaseUrl === '') {
        $webBaseUrl = SITE_URL;
    }

    $appLogoUrl = $removeLogo ? '' : $currentLogoUrl;

    if ($removeLogo && $currentLogoUrl !== '') {
        deleteUploadedFile($currentLogoUrl);
    }

    try {
        $uploadedLogoUrl = handleUploadedImage($_FILES['app_logo_file'] ?? null, 'system', 'logo');
        if ($uploadedLogoUrl !== null) {
            $appLogoUrl = $uploadedLogoUrl;
            if ($currentLogoUrl !== '') {
                deleteUploadedFile($currentLogoUrl);
            }
        }
    } catch (RuntimeException $e) {
        jsonResponse(1, $e->getMessage());
    }

    saveAppSettings([
        'app_name' => $appName,
        'app_logo_url' => $appLogoUrl,
        'browser_title_template' => $browserTitleTemplate,
        'web_base_url' => $webBaseUrl,
        'open_wx_mp_login' => $openWxMpLogin,
        'copyright' => $copyright,
        'theme_color' => $themeColor === '' ? '#1677ff' : $themeColor
    ]);

    jsonResponse(0, '保存成功', [
        'app_name' => $appName,
        'app_logo_url' => $appLogoUrl,
        'browser_title_template' => $browserTitleTemplate,
        'web_base_url' => $webBaseUrl,
        'open_wx_mp_login' => $openWxMpLogin,
        'copyright' => $copyright,
        'theme_color' => $themeColor === '' ? '#1677ff' : $themeColor
    ]);
}

$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$browserTitleTemplate = getBrowserTitleTemplate();
$webBaseUrl = getWebBaseUrl();
$openWxMpLogin = isWxMpLoginEnabled();
$copyright = getAppCopyright();
$pageName = '系统设置';
$pageTitle = buildPageTitle($pageName);
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
                <a href="/admin/index.php">仪表盘</a>
                <a href="/admin/surveys.php">问卷管理</a>
                <a href="/admin/responses.php">数据查看</a>
                <a href="/admin/qrcode.php">二维码生成</a>
                <a href="/admin/system.php" class="active">系统设置</a>
                <a href="/admin/logout.php" class="logout">退出登录</a>
            </nav>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>系统信息</h2>
            </div>

            <form id="systemForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="current_app_logo_url" id="currentAppLogoUrl" value="<?php echo e($appLogoUrl); ?>">
                <input type="hidden" name="remove_app_logo" id="removeAppLogo" value="0">

                <div class="form-section">
                    <h3>系统品牌</h3>

                    <div class="form-group">
                        <label>系统名称</label>
                        <input type="text" name="app_name" class="form-control" value="<?php echo e($appName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>系统主题色</label>
                        <input type="color" name="theme_color" class="color-input" value="<?php echo e(getAppThemeColor()); ?>">
                        <div class="form-help">控制后台管理页面的全局品牌主题色。</div>
                    </div>

                    <div class="form-group">
                        <label>系统 Logo</label>
                        <div class="logo-upload-panel">
                            <div class="logo-upload-preview-wrap">
                                <img
                                    id="systemLogoPreview"
                                    class="logo-upload-preview"
                                    src="<?php echo e($appLogoUrl); ?>"
                                    alt="<?php echo e($appName); ?>"
                                    style="<?php echo $appLogoUrl === '' ? 'display:none;' : ''; ?>"
                                >
                                <div
                                    id="systemLogoPlaceholder"
                                    class="logo-upload-placeholder"
                                    style="<?php echo $appLogoUrl === '' ? '' : 'display:none;'; ?>"
                                >暂无 Logo</div>
                            </div>
                            <div class="logo-upload-actions">
                                <input type="file" name="app_logo_file" id="appLogoFile" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,image/*">
                                <button type="button" class="btn" id="removeLogoBtn">移除 Logo</button>
                                <div class="form-help">支持 PNG、JPG、GIF、WEBP，大小不超过 2MB。</div>
                                <div class="upload-status-text" id="systemLogoStatus" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>浏览器标题模板</label>
                        <input type="text" name="browser_title_template" class="form-control" value="<?php echo e($browserTitleTemplate); ?>" placeholder="{page} - {app}">
                        <div class="form-help">{page} 表示当前页面名，{app} 表示系统名称。</div>
                    </div>
                </div>

                <hr class="section-divider">

                <div class="form-section">
                    <h3>访问配置</h3>

                    <div class="form-group">
                        <label>站点基础地址</label>
                        <input type="text" name="web_base_url" class="form-control" value="<?php echo e($webBaseUrl); ?>" placeholder="https://example.com">
                        <div class="form-help">二维码和问卷预览链接会优先使用这里的地址生成。</div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-row">
                            <input type="checkbox" name="open_wx_mp_login" value="1" <?php echo $openWxMpLogin ? 'checked' : ''; ?>>
                            开启微信公众号登录
                        </label>
                        <div class="form-help">这项开关先保留占位，当前版本暂不继续接入公众号登录流程。</div>
                    </div>
                </div>

                <hr class="section-divider">

                <div class="form-section">
                    <h3>页脚信息</h3>

                    <div class="form-group">
                        <label>版权信息</label>
                        <textarea name="copyright" class="form-control" rows="3" placeholder="例如：Copyright © 2026 XXX"><?php echo e($copyright); ?></textarea>
                        <div class="form-help">留空则不显示，前台和后台页脚会同步使用这里的内容。</div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary" id="saveSystemBtn">保存设置</button>
            </form>
        </div>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>

    <script src="/assets/js/main.js"></script>
    <script>
    const systemLogoUploader = setupImageUploadField({
        fileInputId: 'appLogoFile',
        currentInputId: 'currentAppLogoUrl',
        removeInputId: 'removeAppLogo',
        previewId: 'systemLogoPreview',
        placeholderId: 'systemLogoPlaceholder',
        removeButtonId: 'removeLogoBtn',
        statusId: 'systemLogoStatus',
        label: '系统 Logo'
    });

    const saveSystemBtn = document.getElementById('saveSystemBtn');
    saveSystemBtn.addEventListener('click', function () {
        const form = document.getElementById('systemForm');
        const formData = new FormData(form);

        saveSystemBtn.disabled = true;
        saveSystemBtn.textContent = '保存中...';

        fetch('/admin/system.php', {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.code !== 0) {
                    throw new Error(data.message || '保存失败，请重试');
                }

                localStorage.setItem('app_name', data.data.app_name);
                localStorage.setItem('app_logo_url', data.data.app_logo_url);
                localStorage.setItem('browser_title_template', data.data.browser_title_template);
                localStorage.setItem('copyright', data.data.copyright);
                localStorage.setItem('theme_color', data.data.theme_color);

                document.documentElement.style.setProperty('--theme-color', data.data.theme_color);

                if (typeof applyAdminBranding === 'function') {
                    applyAdminBranding(
                        data.data.app_name,
                        data.data.app_logo_url,
                        data.data.browser_title_template,
                        data.data.copyright
                    );
                }

                if (systemLogoUploader) {
                    systemLogoUploader.setCurrentUrl(data.data.app_logo_url || '');
                }

                alert('保存成功');
            })
            .catch(function (error) {
                alert(error.message || '保存失败，请重试');
            })
            .finally(function () {
                saveSystemBtn.disabled = false;
                saveSystemBtn.textContent = '保存设置';
            });
    });
    </script>
</body>
</html>
