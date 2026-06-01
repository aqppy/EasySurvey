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
        jsonResponse(1, __('op_failed') . ': CSRF Token Invalid');
    }

    $appName = trim($_POST['app_name'] ?? '');
    $currentLogoUrl = trim($_POST['current_app_logo_url'] ?? '');
    $browserTitleTemplate = trim($_POST['browser_title_template'] ?? '');
    $webBaseUrl = trim($_POST['web_base_url'] ?? '');
    $openWxMpLogin = !empty($_POST['open_wx_mp_login']) ? '1' : '0';
    $copyright = trim($_POST['copyright'] ?? '');
    $removeLogo = !empty($_POST['remove_app_logo']);
    $themeColor = trim($_POST['theme_color'] ?? '');
    $defaultLanguage = trim($_POST['default_language'] ?? 'zh');

    if ($appName === '') {
        jsonResponse(1, __('sys_app_name') . ' ' . __('survey_empty_title'));
    }

    if ($browserTitleTemplate === '') {
        $browserTitleTemplate = '{page} - {app}';
    }

    if ($webBaseUrl === '') {
        $webBaseUrl = SITE_URL;
    }

    if (!in_array($defaultLanguage, ['zh', 'en'], true)) {
        $defaultLanguage = 'zh';
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
        'theme_color' => $themeColor === '' ? '#1677ff' : $themeColor,
        'default_language' => $defaultLanguage
    ]);

    jsonResponse(0, __('save_success'), [
        'app_name' => $appName,
        'app_logo_url' => $appLogoUrl,
        'browser_title_template' => $browserTitleTemplate,
        'web_base_url' => $webBaseUrl,
        'open_wx_mp_login' => $openWxMpLogin,
        'copyright' => $copyright,
        'theme_color' => $themeColor === '' ? '#1677ff' : $themeColor,
        'default_language' => $defaultLanguage
    ]);
}

$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$browserTitleTemplate = getBrowserTitleTemplate();
$webBaseUrl = getWebBaseUrl();
$openWxMpLogin = isWxMpLoginEnabled();
$copyright = getAppCopyright();
$pageName = __('nav_system');
$pageTitle = buildPageTitle($pageName);
$currentDefaultLang = getAppSettings()['default_language'] ?? 'zh';
?>
<!DOCTYPE html>
<html lang="<?php echo getSystemLanguage() === 'zh' ? 'zh-CN' : 'en'; ?>">
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
                <a href="/admin/index.php"><?php echo __('nav_dashboard'); ?></a>
                <a href="/admin/surveys.php"><?php echo __('nav_surveys'); ?></a>
                <a href="/admin/responses.php"><?php echo __('nav_responses'); ?></a>
                <a href="/admin/qrcode.php"><?php echo __('nav_qrcode'); ?></a>
                <a href="/admin/system.php" class="active"><?php echo __('nav_system'); ?></a>
                <a href="?lang_toggle=1" class="lang-toggle" style="margin-left:auto; color:var(--theme-color); font-weight:600;"><?php echo __('lang_toggle'); ?></a>
                <a href="/admin/logout.php" class="logout"><?php echo __('nav_logout'); ?></a>
            </nav>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><?php echo __('sys_title'); ?></h2>
            </div>

            <form id="systemForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="current_app_logo_url" id="currentAppLogoUrl" value="<?php echo e($appLogoUrl); ?>">
                <input type="hidden" name="remove_app_logo" id="removeAppLogo" value="0">

                <div class="form-section">
                    <h3><?php echo __('sys_section_brand'); ?></h3>

                    <div class="form-group">
                        <label><?php echo __('sys_app_name'); ?></label>
                        <input type="text" name="app_name" class="form-control" value="<?php echo e($appName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('sys_theme_color'); ?></label>
                        <input type="color" name="theme_color" class="color-input" value="<?php echo e(getAppThemeColor()); ?>">
                        <div class="form-help"><?php echo __('sys_theme_color_help'); ?></div>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('sys_logo'); ?></label>
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
                                <button type="button" class="btn" id="removeLogoBtn"><?php echo __('sys_logo_remove'); ?></button>
                                <div class="form-help"><?php echo __('sys_logo_help'); ?></div>
                                <div class="upload-status-text" id="systemLogoStatus" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('sys_title_template'); ?></label>
                        <input type="text" name="browser_title_template" class="form-control" value="<?php echo e($browserTitleTemplate); ?>" placeholder="{page} - {app}">
                        <div class="form-help"><?php echo __('sys_title_template_help'); ?></div>
                    </div>
                </div>

                <hr class="section-divider">

                <div class="form-section">
                    <h3><?php echo __('sys_section_access'); ?></h3>

                    <div class="form-group">
                        <label><?php echo __('sys_base_url'); ?></label>
                        <input type="text" name="web_base_url" class="form-control" value="<?php echo e($webBaseUrl); ?>" placeholder="https://example.com">
                        <div class="form-help"><?php echo __('sys_base_url_help'); ?></div>
                    </div>

                    <div class="form-group">
                        <label><?php echo __('sys_default_language'); ?></label>
                        <select name="default_language" class="form-control">
                            <option value="zh" <?php echo $currentDefaultLang === 'zh' ? 'selected' : ''; ?>>简体中文</option>
                            <option value="en" <?php echo $currentDefaultLang === 'en' ? 'selected' : ''; ?>>English</option>
                        </select>
                        <div class="form-help"><?php echo __('sys_default_language_help'); ?></div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-row">
                            <input type="checkbox" name="open_wx_mp_login" value="1" <?php echo $openWxMpLogin ? 'checked' : ''; ?>>
                            <?php echo __('sys_wx_mp'); ?>
                        </label>
                        <div class="form-help"><?php echo __('sys_wx_mp_help'); ?></div>
                    </div>
                </div>

                <hr class="section-divider">

                <div class="form-section">
                    <h3><?php echo __('sys_section_footer'); ?></h3>

                    <div class="form-group">
                        <label><?php echo __('sys_copyright'); ?></label>
                        <textarea name="copyright" class="form-control" rows="3" placeholder="例如：Copyright © 2026 XXX"><?php echo e($copyright); ?></textarea>
                        <div class="form-help"><?php echo __('sys_copyright_help'); ?></div>
                    </div>
                </div>

                <button type="button" class="btn btn-primary" id="saveSystemBtn"><?php echo __('save'); ?></button>
            </form>
        </div>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>

    <?php echo getJsLangBridgeHtml(); ?>
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
        saveSystemBtn.textContent = "<?php echo __('saving'); ?>";

        fetch('/admin/system.php', {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.code !== 0) {
                    throw new Error(data.message || "<?php echo __('save_failed'); ?>");
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

                alert("<?php echo __('save_success'); ?>");
                location.reload(); // Reload to refresh language settings instantly
            })
            .catch(function (error) {
                alert(error.message || "<?php echo __('save_failed'); ?>");
            })
            .finally(function () {
                saveSystemBtn.disabled = false;
                saveSystemBtn.textContent = "<?php echo __('save'); ?>";
            });
    });
    </script>
</body>
</html>
