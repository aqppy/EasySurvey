<?php
/**
 * Admin survey management
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        jsonResponse(1, '安全校验失败，请刷新页面后重试');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status') {
        $surveyId = intval($_POST['survey_id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        $stmt = $db->prepare('UPDATE surveys SET status = ? WHERE id = ?');
        $stmt->execute([$status, $surveyId]);

        jsonResponse(0, '操作成功');
    }

    if ($action === 'delete') {
        $surveyId = intval($_POST['survey_id'] ?? 0);

        $stmt = $db->prepare('DELETE FROM surveys WHERE id = ?');
        $stmt->execute([$surveyId]);

        jsonResponse(0, '删除成功');
    }

    if ($action === 'parse_csv') {
        if (empty($_FILES['csv_file']['tmp_name'])) {
            jsonResponse(1, '未找到上传的文件');
        }

        $file = $_FILES['csv_file']['tmp_name'];
        
        $contents = file_get_contents($file);
        if ($contents === false) {
            jsonResponse(1, '文件读取失败');
        }

        if (substr($contents, 0, 3) === "\xEF\xBB\xBF") {
            $contents = substr($contents, 3);
        }

        $encoding = mb_detect_encoding($contents, ['UTF-8', 'GBK', 'GB2312', 'BIG5', 'ASCII']);
        if ($encoding !== 'UTF-8') {
            $contents = mb_convert_encoding($contents, 'UTF-8', $encoding ?: 'GBK');
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $questions = [];
        $header = fgetcsv($stream); // skip header

        while (($row = fgetcsv($stream)) !== false) {
            if (count($row) < 1 || trim($row[0]) === '') {
                continue;
            }

            $title = trim($row[0]);
            $typeStr = isset($row[1]) ? trim($row[1]) : '单选题';
            $requiredStr = isset($row[2]) ? trim($row[2]) : '否';
            $optionsStr = isset($row[3]) ? trim($row[3]) : '';

            $type = 'radio';
            if (strpos($typeStr, '多选') !== false || strtolower($typeStr) === 'checkbox') {
                $type = 'checkbox';
            } elseif (strpos($typeStr, '文本') !== false || strpos($typeStr, '问答') !== false || strtolower($typeStr) === 'text') {
                $type = 'text';
            }

            $required = 0;
            if (strpos($requiredStr, '是') !== false || $requiredStr === '1' || strpos($requiredStr, '必填') !== false) {
                $required = 1;
            }

            $options = [];
            if ($type !== 'text' && $optionsStr !== '') {
                $options = array_filter(array_map('trim', explode('|', $optionsStr)), function ($val) {
                    return $val !== '';
                });
            }

            $questions[] = [
                'id' => 0,
                'title' => $title,
                'type' => $type,
                'required' => $required,
                'options' => array_values($options)
            ];
        }

        fclose($stream);

        if (empty($questions)) {
            jsonResponse(1, '未找到有效的题目数据，请检查 CSV 格式');
        }

        jsonResponse(0, '解析成功', ['questions' => $questions]);
    }

    if ($action === 'save_survey') {
        ensureAppSettingsTable();
        $surveyId = intval($_POST['survey_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $questions = $_POST['questions'] ?? [];
        $settingsDefaults = getDefaultSurveySettings();
        $themeDefaults = getDefaultSurveyTheme();

        $settings = [
            'allow_repeat_submit' => !empty($_POST['settings']['allow_repeat_submit']),
            'thank_you_title' => trim($_POST['settings']['thank_you_title'] ?? ''),
            'thank_you_message' => trim($_POST['settings']['thank_you_message'] ?? ''),
            'show_description' => !empty($_POST['settings']['show_description']),
            'show_number' => !empty($_POST['settings']['show_number'])
        ];
        $theme = [
            'logo_url' => trim($_POST['theme']['current_logo_url'] ?? ''),
            'header_image_url' => trim($_POST['theme']['current_header_image_url'] ?? ''),
            'theme_color' => trim($_POST['theme']['theme_color'] ?? ''),
            'background_color' => trim($_POST['theme']['background_color'] ?? ''),
            'background_image_url' => trim($_POST['theme']['current_background_image_url'] ?? ''),
            'submit_button_text' => trim($_POST['theme']['submit_button_text'] ?? ''),
            'show_title' => !empty($_POST['theme']['show_title']),
            'show_description' => !empty($_POST['theme']['show_description']),
            'show_number' => !empty($_POST['theme']['show_number'])
        ];

        $oldLogoUrl = trim($_POST['theme']['current_logo_url'] ?? '');
        $oldHeaderUrl = trim($_POST['theme']['current_header_image_url'] ?? '');
        $oldBackgroundUrl = trim($_POST['theme']['current_background_image_url'] ?? '');

        if (!empty($_POST['theme']['remove_logo_url'])) {
            $theme['logo_url'] = '';
            if ($oldLogoUrl !== '') {
                deleteUploadedFile($oldLogoUrl);
            }
        }
        if (!empty($_POST['theme']['remove_header_image_url'])) {
            $theme['header_image_url'] = '';
            if ($oldHeaderUrl !== '') {
                deleteUploadedFile($oldHeaderUrl);
            }
        }
        if (!empty($_POST['theme']['remove_background_image_url'])) {
            $theme['background_image_url'] = '';
            if ($oldBackgroundUrl !== '') {
                deleteUploadedFile($oldBackgroundUrl);
            }
        }

        if ($title === '') {
            jsonResponse(1, '请输入问卷标题');
        }

        if ($settings['thank_you_title'] === '') {
            $settings['thank_you_title'] = $settingsDefaults['thank_you_title'];
        }
        if ($settings['thank_you_message'] === '') {
            $settings['thank_you_message'] = $settingsDefaults['thank_you_message'];
        }
        if ($theme['submit_button_text'] === '') {
            $theme['submit_button_text'] = $themeDefaults['submit_button_text'];
        }

        try {
            $uploadedLogoUrl = handleUploadedImage($_FILES['theme_logo_file'] ?? null, 'surveys', 'survey_logo');
            if ($uploadedLogoUrl !== null) {
                $theme['logo_url'] = $uploadedLogoUrl;
                if ($oldLogoUrl !== '') {
                    deleteUploadedFile($oldLogoUrl);
                }
            }

            $uploadedHeaderUrl = handleUploadedImage($_FILES['theme_header_file'] ?? null, 'surveys', 'survey_header');
            if ($uploadedHeaderUrl !== null) {
                $theme['header_image_url'] = $uploadedHeaderUrl;
                if ($oldHeaderUrl !== '') {
                    deleteUploadedFile($oldHeaderUrl);
                }
            }

            $uploadedBackgroundUrl = handleUploadedImage($_FILES['theme_background_file'] ?? null, 'surveys', 'survey_bg');
            if ($uploadedBackgroundUrl !== null) {
                $theme['background_image_url'] = $uploadedBackgroundUrl;
                if ($oldBackgroundUrl !== '') {
                    deleteUploadedFile($oldBackgroundUrl);
                }
            }
        } catch (RuntimeException $e) {
            jsonResponse(1, $e->getMessage());
        }

        $db->beginTransaction();

        try {
            $existingQuestionIds = [];
            if ($surveyId > 0) {
                $stmt = $db->prepare('UPDATE surveys SET title = ?, description = ? WHERE id = ?');
                $stmt->execute([$title, $description, $surveyId]);

                $stmt = $db->prepare('SELECT id FROM questions WHERE survey_id = ?');
                $stmt->execute([$surveyId]);
                $existingQuestionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $stmt = $db->prepare('INSERT INTO surveys (title, description) VALUES (?, ?)');
                $stmt->execute([$title, $description]);
                $surveyId = intval($db->lastInsertId());
            }

            $keptQuestionIds = [];

            if (!empty($questions)) {
                usort($questions, function ($a, $b) {
                    $sortA = intval($a['sort_order'] ?? 0);
                    $sortB = intval($b['sort_order'] ?? 0);
                    return $sortA <=> $sortB;
                });

                $insertStmt = $db->prepare('
                    INSERT INTO questions (survey_id, title, type, options, required, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');

                $updateStmt = $db->prepare('
                    UPDATE questions 
                    SET title = ?, type = ?, options = ?, required = ?, sort_order = ?
                    WHERE id = ? AND survey_id = ?
                ');

                foreach ($questions as $index => $question) {
                    $qId = intval($question['id'] ?? 0);
                    $questionTitle = trim($question['title'] ?? '');
                    $questionType = $question['type'] ?? 'radio';
                    $questionRequired = !empty($question['required']) ? 1 : 0;
                    $questionOptions = null;
                    $sortOrder = intval($question['sort_order'] ?? ($index + 1));

                    if ($questionTitle === '') {
                        continue;
                    }

                    if ($questionType !== 'text') {
                        $options = array_filter($question['options'] ?? [], function ($option) {
                            return trim((string) $option) !== '';
                        });
                        $questionOptions = json_encode(array_values($options), JSON_UNESCAPED_UNICODE);
                    }

                    if ($qId > 0 && in_array($qId, $existingQuestionIds)) {
                        $updateStmt->execute([
                            $questionTitle,
                            $questionType,
                            $questionOptions,
                            $questionRequired,
                            $sortOrder,
                            $qId,
                            $surveyId
                        ]);
                        $keptQuestionIds[] = $qId;
                    } else {
                        $insertStmt->execute([
                            $surveyId,
                            $questionTitle,
                            $questionType,
                            $questionOptions,
                            $questionRequired,
                            $sortOrder
                        ]);
                        $newId = intval($db->lastInsertId());
                        if ($newId > 0) {
                            $keptQuestionIds[] = $newId;
                        }
                    }
                }
            }

            // Delete questions that were not kept
            $deletedQuestionIds = array_diff($existingQuestionIds, $keptQuestionIds);
            if (!empty($deletedQuestionIds)) {
                $placeholders = implode(',', array_fill(0, count($deletedQuestionIds), '?'));
                $deleteStmt = $db->prepare("DELETE FROM questions WHERE id IN ($placeholders) AND survey_id = ?");
                $params = array_merge(array_values($deletedQuestionIds), [$surveyId]);
                $deleteStmt->execute($params);
            }

            saveSurveySettings($surveyId, $settings);
            saveSurveyTheme($surveyId, $theme);

            $db->commit();
            jsonResponse(0, '保存成功', ['survey_id' => $surveyId]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('问卷保存失败: ' . $e->getMessage());
            jsonResponse(1, '保存失败：' . $e->getMessage());
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $surveyId = intval($_GET['survey_id'] ?? 0);
    $survey = getSurveyWithQuestions($surveyId);
    if (!$survey) {
        die('问卷不存在');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . rawurlencode($survey['title']) . '_问卷结构.csv"');
    
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    fputcsv($output, ['题目内容', '题目类型', '是否必填', '选项']);

    foreach ($survey['questions'] as $question) {
        $typeStr = '单选题';
        if ($question['type'] === 'checkbox') {
            $typeStr = '多选题';
        } elseif ($question['type'] === 'text') {
            $typeStr = '文本题';
        }

        $requiredStr = $question['required'] ? '是' : '否';

        $optionsStr = '';
        if ($question['type'] !== 'text' && !empty($question['options'])) {
            $optionsStr = implode('|', (array)$question['options']);
        }

        fputcsv($output, [
            $question['title'],
            $typeStr,
            $requiredStr,
            $optionsStr
        ]);
    }

    fclose($output);
    exit;
}

$stmt = $db->query('
    SELECT s.*, (SELECT COUNT(*) FROM questions WHERE survey_id = s.id) AS question_count
    FROM surveys s
    ORDER BY s.id DESC
');
$surveys = $stmt->fetchAll();

$editSurvey = null;
$editQuestions = [];
$editSettings = getDefaultSurveySettings();
$editTheme = getDefaultSurveyTheme();
$responseCount = 0;

if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    if ($_GET['edit'] === '0' || $_GET['edit'] === 'new') {
        $editSurvey = ['id' => 0, 'title' => '', 'description' => ''];
    } else {
        $editId = intval($_GET['edit']);
        $editSurvey = getSurveyWithQuestions($editId);
        if ($editSurvey) {
            $editQuestions = $editSurvey['questions'];
            $editSettings = getSurveySettings($editId);
            $editTheme = getSurveyTheme($editId);
            
            $stmt = $db->prepare('SELECT COUNT(*) FROM responses WHERE survey_id = ?');
            $stmt->execute([$editId]);
            $responseCount = intval($stmt->fetchColumn());
        }
    }
}

$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$pageName = '问卷管理';
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
                <a href="/admin/surveys.php" class="active">问卷管理</a>
                <a href="/admin/responses.php">数据查看</a>
                <a href="/admin/qrcode.php">二维码生成</a>
                <a href="/admin/system.php">系统设置</a>
                <a href="/admin/logout.php" class="logout">退出登录</a>
            </nav>
        </div>

        <?php if ($editSurvey): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $editSurvey['id'] ? '编辑问卷' : '创建问卷'; ?></h2>
                    <a href="/admin/surveys.php" class="btn">返回列表</a>
                </div>

                <form id="surveyForm" method="POST" action="" enctype="multipart/form-data" data-has-responses="<?php echo $responseCount > 0 ? 'true' : 'false'; ?>" data-response-count="<?php echo $responseCount; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="save_survey">
                    <input type="hidden" name="survey_id" value="<?php echo intval($editSurvey['id']); ?>">

                    <div class="form-group">
                        <label>问卷标题</label>
                        <input type="text" name="title" class="form-control" value="<?php echo e($editSurvey['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>问卷描述</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo e($editSurvey['description']); ?></textarea>
                    </div>

                    <hr class="section-divider">

                    <div class="form-section">
                        <h3>基础设置</h3>
                        <div class="grid-two">
                            <label class="checkbox-row">
                                <input type="checkbox" name="settings[allow_repeat_submit]" value="1" <?php echo !empty($editSettings['allow_repeat_submit']) ? 'checked' : ''; ?>>
                                允许重复提交
                            </label>
                            <label class="checkbox-row">
                                <input type="checkbox" name="settings[show_description]" value="1" <?php echo !empty($editSettings['show_description']) ? 'checked' : ''; ?>>
                                前台显示描述
                            </label>
                            <label class="checkbox-row">
                                <input type="checkbox" name="settings[show_number]" value="1" <?php echo !empty($editSettings['show_number']) ? 'checked' : ''; ?>>
                                前台显示题号
                            </label>
                        </div>

                        <div class="form-group">
                            <label>感谢页标题</label>
                            <input type="text" name="settings[thank_you_title]" class="form-control" value="<?php echo e($editSettings['thank_you_title']); ?>">
                        </div>

                        <div class="form-group">
                            <label>感谢页文案</label>
                            <textarea name="settings[thank_you_message]" class="form-control" rows="3"><?php echo e($editSettings['thank_you_message']); ?></textarea>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div class="form-section">
                        <h3>主题设置</h3>
                        <div class="grid-two">
                            <div class="form-group">
                                <label>问卷 Logo</label>
                                <input type="hidden" name="theme[current_logo_url]" id="currentThemeLogoUrl" value="<?php echo e($editTheme['logo_url']); ?>">
                                <input type="hidden" name="theme[remove_logo_url]" id="removeThemeLogoUrl" value="0">
                                <div class="logo-upload-panel">
                                    <div class="logo-upload-preview-wrap">
                                        <img id="themeLogoPreview" class="logo-upload-preview" src="<?php echo e($editTheme['logo_url']); ?>" alt="问卷 Logo" style="<?php echo $editTheme['logo_url'] === '' ? 'display:none;' : ''; ?>">
                                        <div id="themeLogoPlaceholder" class="logo-upload-placeholder" style="<?php echo $editTheme['logo_url'] === '' ? '' : 'display:none;'; ?>">暂无 Logo</div>
                                    </div>
                                    <div class="logo-upload-actions">
                                        <input type="file" name="theme_logo_file" id="themeLogoFile" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,image/*">
                                        <button type="button" class="btn" id="removeThemeLogoBtn">移除 Logo</button>
                                        <div class="upload-status-text" id="themeLogoStatus" aria-live="polite"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>问卷头图</label>
                                <input type="hidden" name="theme[current_header_image_url]" id="currentThemeHeaderUrl" value="<?php echo e($editTheme['header_image_url']); ?>">
                                <input type="hidden" name="theme[remove_header_image_url]" id="removeThemeHeaderUrl" value="0">
                                <div class="logo-upload-panel">
                                    <div class="logo-upload-preview-wrap">
                                        <img id="themeHeaderPreview" class="logo-upload-preview" src="<?php echo e($editTheme['header_image_url']); ?>" alt="问卷头图" style="<?php echo $editTheme['header_image_url'] === '' ? 'display:none;' : ''; ?>">
                                        <div id="themeHeaderPlaceholder" class="logo-upload-placeholder" style="<?php echo $editTheme['header_image_url'] === '' ? '' : 'display:none;'; ?>">暂无头图</div>
                                    </div>
                                    <div class="logo-upload-actions">
                                        <input type="file" name="theme_header_file" id="themeHeaderFile" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,image/*">
                                        <button type="button" class="btn" id="removeThemeHeaderBtn">移除头图</button>
                                        <div class="upload-status-text" id="themeHeaderStatus" aria-live="polite"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>主题色</label>
                                <input type="color" name="theme[theme_color]" class="color-input" value="<?php echo e($editTheme['theme_color']); ?>">
                            </div>

                            <div class="form-group">
                                <label>背景色</label>
                                <input type="color" name="theme[background_color]" class="color-input" value="<?php echo e($editTheme['background_color']); ?>">
                            </div>

                            <div class="form-group">
                                <label>背景图</label>
                                <input type="hidden" name="theme[current_background_image_url]" id="currentThemeBackgroundUrl" value="<?php echo e($editTheme['background_image_url']); ?>">
                                <input type="hidden" name="theme[remove_background_image_url]" id="removeThemeBackgroundUrl" value="0">
                                <div class="logo-upload-panel">
                                    <div class="logo-upload-preview-wrap">
                                        <img id="themeBackgroundPreview" class="logo-upload-preview" src="<?php echo e($editTheme['background_image_url']); ?>" alt="背景图" style="<?php echo $editTheme['background_image_url'] === '' ? 'display:none;' : ''; ?>">
                                        <div id="themeBackgroundPlaceholder" class="logo-upload-placeholder" style="<?php echo $editTheme['background_image_url'] === '' ? '' : 'display:none;'; ?>">暂无背景图</div>
                                    </div>
                                    <div class="logo-upload-actions">
                                        <input type="file" name="theme_background_file" id="themeBackgroundFile" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,image/*">
                                        <button type="button" class="btn" id="removeThemeBackgroundBtn">移除背景图</button>
                                        <div class="upload-status-text" id="themeBackgroundStatus" aria-live="polite"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>提交按钮文字</label>
                                <input type="text" name="theme[submit_button_text]" class="form-control" value="<?php echo e($editTheme['submit_button_text']); ?>">
                            </div>
                        </div>

                        <div class="grid-two">
                            <label class="checkbox-row">
                                <input type="checkbox" name="theme[show_title]" value="1" <?php echo !empty($editTheme['show_title']) ? 'checked' : ''; ?>>
                                显示标题
                            </label>
                            <label class="checkbox-row">
                                <input type="checkbox" name="theme[show_description]" value="1" <?php echo !empty($editTheme['show_description']) ? 'checked' : ''; ?>>
                                显示描述
                            </label>
                            <label class="checkbox-row">
                                <input type="checkbox" name="theme[show_number]" value="1" <?php echo !empty($editTheme['show_number']) ? 'checked' : ''; ?>>
                                显示题号
                            </label>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div class="form-section">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                            <h3 style="margin-bottom:0;">题目列表</h3>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <?php if ($editSurvey['id'] > 0): ?>
                                    <a href="/admin/surveys.php?action=export_csv&survey_id=<?php echo $editSurvey['id']; ?>" class="btn btn-sm" style="background:#52c41a; color:#fff;">导出 CSV 问卷</a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm" style="background:#fa8c16; color:#fff;" onclick="triggerCSVImport()">导入 CSV 问卷</button>
                                <input type="file" id="csvImportFile" style="display:none;" accept=".csv" onchange="handleCSVImport(this)">
                            </div>
                        </div>
                        <div id="questionsContainer">
                            <?php foreach ($editQuestions as $index => $question): ?>
                                <div class="question-editor" data-index="<?php echo $index; ?>">
                                    <input type="hidden" name="questions[<?php echo $index; ?>][id]" value="<?php echo intval($question['id']); ?>">
                                    <div class="question-editor-header">
                                        <h4>题目 <?php echo $index + 1; ?></h4>
                                        <div class="question-actions">
                                            <button type="button" class="btn btn-sm" onclick="moveQuestion(this, -1)" title="上移">↑</button>
                                            <button type="button" class="btn btn-sm" onclick="moveQuestion(this, 1)" title="下移">↓</button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(this)" title="删除">×</button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>题目内容</label>
                                        <input type="text" name="questions[<?php echo $index; ?>][title]" class="form-control" value="<?php echo e($question['title']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>题目类型</label>
                                        <select name="questions[<?php echo $index; ?>][type]" class="form-control" onchange="toggleOptionsInput(this)">
                                            <option value="radio" <?php echo $question['type'] === 'radio' ? 'selected' : ''; ?>>单选题</option>
                                            <option value="checkbox" <?php echo $question['type'] === 'checkbox' ? 'selected' : ''; ?>>多选题</option>
                                            <option value="text" <?php echo $question['type'] === 'text' ? 'selected' : ''; ?>>文本题</option>
                                        </select>
                                    </div>
                                    <div class="form-group options-group" style="<?php echo $question['type'] === 'text' ? 'display:none;' : ''; ?>">
                                        <label>选项</label>
                                        <div class="option-inputs">
                                            <?php foreach ((array) $question['options'] as $option): ?>
                                                <div class="option-input-row">
                                                    <input type="text" name="questions[<?php echo $index; ?>][options][]" class="form-control" value="<?php echo e($option); ?>" placeholder="请输入选项">
                                                    <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="btn btn-sm" onclick="addOption(this)" style="margin-top:8px;">+ 添加选项</button>
                                    </div>
                                    <div class="form-group">
                                        <label class="checkbox-row">
                                            <input type="checkbox" name="questions[<?php echo $index; ?>][required]" value="1" <?php echo !empty($question['required']) ? 'checked' : ''; ?>>
                                            必填
                                        </label>
                                    </div>
                                    <input type="hidden" name="questions[<?php echo $index; ?>][sort_order]" value="<?php echo $index + 1; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="btn" onclick="addQuestion()" style="margin:15px 0;">+ 添加题目</button>
                    </div>

                    <div style="margin-top:20px;">
                        <button type="button" class="btn btn-primary" id="saveSurveyBtn">保存问卷</button>
                        <a href="/admin/surveys.php" class="btn">取消</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2>问卷列表</h2>
                    <a href="/admin/surveys.php?edit=new" class="btn btn-primary">+ 创建问卷</a>
                </div>

                <?php if (empty($surveys)): ?>
                    <p style="color:#999; text-align:center; padding:40px;">还没有问卷，点击上方按钮创建第一份问卷。</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>题目数</th>
                                <th>状态</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($surveys as $survey): ?>
                                <tr>
                                    <td><?php echo $survey['id']; ?></td>
                                    <td><?php echo e($survey['title']); ?></td>
                                    <td><?php echo $survey['question_count']; ?></td>
                                    <td>
                                        <span class="status <?php echo $survey['status'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $survey['status'] ? '启用' : '停用'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($survey['created_at']); ?></td>
                                    <td>
                                        <a href="/admin/surveys.php?edit=<?php echo $survey['id']; ?>" class="btn btn-sm">编辑</a>
                                        <a href="<?php echo e(buildSurveyUrl($survey['id'])); ?>" target="_blank" class="btn btn-sm">预览</a>
                                        <button class="btn btn-sm" onclick="toggleSurveyStatus(<?php echo $survey['id']; ?>, <?php echo $survey['status']; ?>)">
                                            <?php echo $survey['status'] ? '停用' : '启用'; ?>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteSurvey(<?php echo $survey['id']; ?>)">删除</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>

    <script src="/assets/js/main.js"></script>
    <script>
    setupImageUploadField({
        fileInputId: 'themeLogoFile',
        currentInputId: 'currentThemeLogoUrl',
        removeInputId: 'removeThemeLogoUrl',
        previewId: 'themeLogoPreview',
        placeholderId: 'themeLogoPlaceholder',
        removeButtonId: 'removeThemeLogoBtn',
        statusId: 'themeLogoStatus',
        label: '问卷 Logo'
    });
    setupImageUploadField({
        fileInputId: 'themeHeaderFile',
        currentInputId: 'currentThemeHeaderUrl',
        removeInputId: 'removeThemeHeaderUrl',
        previewId: 'themeHeaderPreview',
        placeholderId: 'themeHeaderPlaceholder',
        removeButtonId: 'removeThemeHeaderBtn',
        statusId: 'themeHeaderStatus',
        label: '问卷头图'
    });
    setupImageUploadField({
        fileInputId: 'themeBackgroundFile',
        currentInputId: 'currentThemeBackgroundUrl',
        removeInputId: 'removeThemeBackgroundUrl',
        previewId: 'themeBackgroundPreview',
        placeholderId: 'themeBackgroundPlaceholder',
        removeButtonId: 'removeThemeBackgroundBtn',
        statusId: 'themeBackgroundStatus',
        label: '背景图'
    });

    const saveBtn = document.getElementById('saveSurveyBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            const form = document.getElementById('surveyForm');
            const formData = new FormData(form);

            saveBtn.disabled = true;
            saveBtn.textContent = '保存中...';

            fetch('/admin/surveys.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(function (text) {
                            throw new Error('服务端返回了非 JSON 响应: ' + text.substring(0, 200));
                        });
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (data.code !== 0) {
                        throw new Error(data.message || '保存失败，请重试');
                    }

                    alert('保存成功');
                    window.location.href = '/admin/surveys.php?edit=' + data.data.survey_id;
                })
                .catch(function (error) {
                    alert(error.message || '提交出错，请重试');
                    saveBtn.disabled = false;
                    saveBtn.textContent = '保存问卷';
                });
        });
    }
    </script>
</body>
</html>
