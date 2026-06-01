<?php
/**
 * Frontend survey entry
 * Route: /survey/{id} or /index.php?id={id}
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';
require_once __DIR__ . '/../app/functions.php';

$surveyId = intval($_GET['id'] ?? 0);

if (!$surveyId) {
    die('无效的问卷 ID');
}

$survey = getSurveyWithQuestions($surveyId);
if (!$survey) {
    die('问卷不存在');
}

if (intval($survey['status']) !== 1) {
    die('当前问卷已停用');
}

$settings = getSurveySettings($surveyId);
$theme = getSurveyTheme($surveyId);

$showDescription = !empty($settings['show_description']) && !empty($theme['show_description']);
$showNumber = !empty($settings['show_number']) && !empty($theme['show_number']);
$showTitle = !empty($theme['show_title']);
$allowRepeatSubmit = !empty($settings['allow_repeat_submit']);

$cookieName = "survey_submitted_{$surveyId}";
$showThankYou = !$allowRepeatSubmit && isset($_COOKIE[$cookieName]);
$pageTitle = $showThankYou ? $settings['thank_you_title'] : $survey['title'];

$bodyStyleParts = [];
if (!empty($theme['background_color'])) {
    $bodyStyleParts[] = '--theme-color: ' . $theme['theme_color'];
    $bodyStyleParts[] = '--page-bg: ' . $theme['background_color'];
}
if (!empty($theme['background_image_url'])) {
    $bodyStyleParts[] = '--page-bg-image: url(' . htmlspecialchars($theme['background_image_url'], ENT_QUOTES, 'UTF-8') . ')';
}

$pageStyles = '<style>
    :root {
        --theme-color: ' . e($theme['theme_color']) . ';
        --page-bg: ' . e($theme['background_color']) . ';
        --page-bg-image: none;
    }
    body.survey-theme {
        background-color: var(--page-bg);
        background-image: var(--page-bg-image);
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    }
</style>';

require_once INCLUDES_PATH . '/header.php';
?>

<div class="survey-wrapper">
    <?php if (!empty($theme['header_image_url'])): ?>
        <div class="survey-hero">
            <img src="<?php echo e($theme['header_image_url']); ?>" alt="问卷头图">
        </div>
    <?php endif; ?>

    <?php if (!empty($theme['logo_url'])): ?>
        <div class="survey-logo-wrap">
            <img class="survey-logo" src="<?php echo e($theme['logo_url']); ?>" alt="问卷 Logo">
        </div>
    <?php endif; ?>

    <?php if ($showThankYou): ?>
        <div class="thank-you">
            <h2><?php echo e($settings['thank_you_title']); ?></h2>
            <p><?php echo e($settings['thank_you_message']); ?></p>
            <button class="close-btn" onclick="window.close()">关闭页面</button>
        </div>
    <?php else: ?>
        <div class="survey-header">
            <?php if ($showTitle): ?>
                <h1><?php echo e($survey['title']); ?></h1>
            <?php endif; ?>
            <?php if ($showDescription && !empty($survey['description'])): ?>
                <p><?php echo e($survey['description']); ?></p>
            <?php endif; ?>
        </div>

        <form id="surveyForm">
            <?php foreach ($survey['questions'] as $index => $question): ?>
                <div class="question-block" data-question-id="<?php echo intval($question['id']); ?>" data-required="<?php echo intval($question['required']); ?>">
                    <div class="question-title">
                        <?php if ($showNumber): ?>
                            <span class="question-number"><?php echo $index + 1; ?>.</span>
                        <?php endif; ?>
                        <?php if (!empty($question['required'])): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                        <?php echo e($question['title']); ?>
                    </div>

                    <?php if ($question['type'] === 'radio'): ?>
                        <ul class="options-list">
                            <?php foreach ((array) $question['options'] as $option): ?>
                                <li>
                                    <label>
                                        <input type="radio" name="q_<?php echo intval($question['id']); ?>" value="<?php echo e($option); ?>">
                                        <?php echo e($option); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($question['type'] === 'checkbox'): ?>
                        <ul class="options-list options-inline">
                            <?php foreach ((array) $question['options'] as $option): ?>
                                <li>
                                    <label>
                                        <input type="checkbox" name="q_<?php echo intval($question['id']); ?>[]" value="<?php echo e($option); ?>">
                                        <?php echo e($option); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($question['type'] === 'text'): ?>
                        <textarea class="text-input" name="q_<?php echo intval($question['id']); ?>" placeholder="请输入您的回答"></textarea>
                    <?php endif; ?>

                    <div class="error-msg">此题为必填项，请填写后再提交</div>
                </div>
            <?php endforeach; ?>

            <button
                type="button"
                id="submitBtn"
                class="submit-btn"
                data-default-text="<?php echo e($theme['submit_button_text']); ?>"
                onclick="submitSurvey(<?php echo $surveyId; ?>)"
            ><?php echo e($theme['submit_button_text']); ?></button>
        </form>

        <div id="thankYou" class="thank-you" style="display:none;">
            <h2><?php echo e($settings['thank_you_title']); ?></h2>
            <p><?php echo e($settings['thank_you_message']); ?></p>
            <button class="close-btn" onclick="window.close()">关闭页面</button>
        </div>
    <?php endif; ?>
</div>

<script>
document.body.classList.add('survey-theme');
<?php if (!empty($bodyStyleParts)): ?>
document.body.style.cssText += ';<?php echo implode(';', $bodyStyleParts); ?>';
<?php endif; ?>
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
