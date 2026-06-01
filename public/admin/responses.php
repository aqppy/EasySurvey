<?php
/**
 * Admin response viewer
 */

define('SURVEY_SYSTEM', true);

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();
$appName = getAppName();
$appLogoUrl = getAppLogoUrl();
$surveyList = getSurveyList();
$currentSurveyId = intval($_GET['survey_id'] ?? 0);

if (($_GET['action'] ?? '') === 'export' && $currentSurveyId > 0) {
    exportCSV($db, $currentSurveyId);
}

$currentSurvey = null;
$responses = [];
$questionStats = [];
$questions = [];
$totalCount = 0;
$totalPages = 0;
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

if ($currentSurveyId > 0) {
    $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$currentSurveyId]);
    $currentSurvey = $stmt->fetch();

    if ($currentSurvey) {
        $stmt = $db->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$currentSurveyId]);
        $questions = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT COUNT(*) AS count FROM responses WHERE survey_id = ?");
        $stmt->execute([$currentSurveyId]);
        $totalCount = intval($stmt->fetch()['count']);
        $totalPages = (int) ceil($totalCount / $perPage);
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare("SELECT * FROM responses WHERE survey_id = ? ORDER BY submitted_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$currentSurveyId, $perPage, $offset]);
        $responses = $stmt->fetchAll();

        if (!empty($responses)) {
            $responseIds = array_column($responses, 'id');
            $placeholders = implode(',', array_fill(0, count($responseIds), '?'));
            $stmt = $db->prepare("SELECT response_id, question_id, answer_value FROM answers WHERE response_id IN ($placeholders)");
            $stmt->execute($responseIds);
            $allAnswers = $stmt->fetchAll();

            $answersByResponse = [];
            foreach ($allAnswers as $answer) {
                $answersByResponse[$answer['response_id']][$answer['question_id']] = $answer['answer_value'];
            }

            foreach ($responses as &$response) {
                $response['answers'] = $answersByResponse[$response['id']] ?? [];
            }
            unset($response);
        }

        $questionIds = [];
        foreach ($questions as $question) {
            if ($question['type'] !== 'text' && json_decode($question['options'], true)) {
                $questionIds[] = intval($question['id']);
            }
        }

        $statsByQuestion = [];
        if (!empty($questionIds)) {
            $placeholders = implode(',', $questionIds);
            $stmt = $db->query("SELECT question_id, answer_value, COUNT(*) AS count FROM answers WHERE question_id IN ($placeholders) GROUP BY question_id, answer_value");
            $allStats = $stmt->fetchAll();
            foreach ($allStats as $row) {
                $statsByQuestion[$row['question_id']][$row['answer_value']] = intval($row['count']);
            }
        }

        foreach ($questions as $question) {
            if ($question['type'] === 'text') {
                continue;
            }

            $options = json_decode($question['options'], true);
            if (!$options) {
                continue;
            }

            $stats = [];
            foreach ($options as $option) {
                $stats[$option] = 0;
            }

            foreach (($statsByQuestion[$question['id']] ?? []) as $answerValue => $count) {
                if (strpos($answerValue, '[') === 0) {
                    $list = json_decode($answerValue, true);
                    if (is_array($list)) {
                        foreach ($list as $item) {
                            if (isset($stats[$item])) {
                                $stats[$item] += $count;
                            }
                        }
                    }
                } elseif (isset($stats[$answerValue])) {
                    $stats[$answerValue] += $count;
                }
            }

            $questionStats[$question['id']] = [
                'title' => $question['title'],
                'stats' => $stats,
                'total' => array_sum($stats)
            ];
        }
    }
}

function exportCSV($db, $surveyId) {
    $stmt = $db->prepare("SELECT title FROM surveys WHERE id = ?");
    $stmt->execute([$surveyId]);
    $survey = $stmt->fetch();

    if (!$survey) {
        die('Survey not found');
    }

    $stmt = $db->prepare("SELECT id, title FROM questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$surveyId]);
    $questions = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM responses WHERE survey_id = ? ORDER BY submitted_at DESC");
    $stmt->execute([$surveyId]);
    $responses = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . urlencode($survey['title']) . '_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    $headers = [__('dash_resp_id', '回答 ID'), __('dash_submit_ip', 'IP 地址'), __('dash_submit_time', '提交时间')];
    foreach ($questions as $question) {
        $headers[] = $question['title'];
    }
    fputcsv($output, $headers);

    $answersMap = [];
    $responseIds = array_column($responses, 'id');
    if (!empty($responseIds)) {
        $placeholders = implode(',', array_fill(0, count($responseIds), '?'));
        $stmt = $db->prepare("SELECT response_id, question_id, answer_value FROM answers WHERE response_id IN ($placeholders)");
        $stmt->execute($responseIds);
        foreach ($stmt->fetchAll() as $answer) {
            $answersMap[$answer['response_id']][$answer['question_id']] = $answer['answer_value'];
        }
    }

    foreach ($responses as $response) {
        $row = [$response['id'], $response['ip_address'], $response['submitted_at']];
        foreach ($questions as $question) {
            $row[] = $answersMap[$response['id']][$question['id']] ?? '';
        }
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

$pageName = __('nav_responses');
$pageTitle = buildPageTitle($pageName);
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
    <script src="/assets/js/chart.umd.min.js"></script>
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
                <a href="/admin/responses.php" class="active"><?php echo __('nav_responses'); ?></a>
                <a href="/admin/qrcode.php"><?php echo __('nav_qrcode'); ?></a>
                <a href="/admin/system.php"><?php echo __('nav_system'); ?></a>
                <a href="?lang_toggle=1" class="lang-toggle" style="margin-left:auto; color:var(--theme-color); font-weight:600;"><?php echo __('lang_toggle'); ?></a>
                <a href="/admin/logout.php" class="logout"><?php echo __('nav_logout'); ?></a>
            </nav>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><?php echo __('resp_select_survey'); ?></h2>
            </div>
            <form method="GET" action="" style="display:flex; gap:10px; align-items:center;">
                <select name="survey_id" class="form-control" style="flex:1;" onchange="this.form.submit()">
                    <option value="">-- <?php echo __('resp_select_survey'); ?> --</option>
                    <?php foreach ($surveyList as $survey): ?>
                        <option value="<?php echo $survey['id']; ?>" <?php echo $currentSurveyId === intval($survey['id']) ? 'selected' : ''; ?>>
                            <?php echo e($survey['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($currentSurvey): ?>
                    <button type="button" class="btn btn-success" onclick="window.location.href='?survey_id=<?php echo $currentSurvey['id']; ?>&action=export'"><?php echo __('resp_export_btn'); ?></button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($currentSurvey): ?>
            <div class="card">
                <div class="card-header">
                    <h2><?php echo __('resp_chart_tab'); ?></h2>
                </div>
                <p><?php echo __('resp_total_count'); ?>：<strong><?php echo $totalCount; ?></strong></p>

                <?php if (!empty($questionStats)): ?>
                    <div class="chart-container">
                        <?php foreach ($questionStats as $questionId => $stat): ?>
                            <div style="margin-bottom:30px;">
                                <h4 style="margin-bottom:10px;"><?php echo e($stat['title']); ?></h4>
                                <canvas id="chart_<?php echo $questionId; ?>" height="100"></canvas>
                                <table class="table" style="margin-top:10px; max-width:400px;">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('resp_opt_name'); ?></th>
                                            <th><?php echo __('resp_opt_count'); ?></th>
                                            <th><?php echo __('resp_opt_ratio'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stat['stats'] as $option => $count): ?>
                                            <tr>
                                                <td><?php echo e($option); ?></td>
                                                <td><?php echo $count; ?></td>
                                                <td><?php echo $stat['total'] > 0 ? round($count / $stat['total'] * 100, 1) : 0; ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color:#999;"><?php echo __('resp_no_data'); ?></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><?php echo __('resp_list_tab'); ?></h2>
                </div>

                <?php if (empty($responses)): ?>
                    <p style="color:#999; text-align:center; padding:20px;"><?php echo __('resp_no_data'); ?></p>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo __('dash_resp_id'); ?></th>
                                    <th><?php echo __('dash_submit_ip'); ?></th>
                                    <th><?php echo __('dash_submit_time'); ?></th>
                                    <?php foreach ($questions as $question): ?>
                                        <th><?php echo e($question['title']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($responses as $response): ?>
                                    <tr>
                                        <td><?php echo $response['id']; ?></td>
                                        <td><?php echo e($response['ip_address']); ?></td>
                                        <td><?php echo e($response['submitted_at']); ?></td>
                                        <?php foreach ($questions as $question): ?>
                                            <?php $value = $response['answers'][$question['id']] ?? ''; ?>
                                            <td><?php echo e($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div style="margin-top:20px; display:flex; gap:8px; flex-wrap:wrap;">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="/admin/responses.php?survey_id=<?php echo $currentSurveyId; ?>&page=<?php echo $i; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php echo renderAppFooter('admin-footer'); ?>

    <?php echo getJsLangBridgeHtml(); ?>
    <script src="/assets/js/main.js"></script>
    <script>
    <?php foreach ($questionStats as $questionId => $stat): ?>
    new Chart(document.getElementById('chart_<?php echo $questionId; ?>'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($stat['stats']), JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: "<?php echo e(__('resp_opt_count')); ?>",
                data: <?php echo json_encode(array_values($stat['stats'])); ?>,
                backgroundColor: '<?php echo e(getAppThemeColor()); ?>'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, precision: 0 } }
        }
    });
    <?php endforeach; ?>
    </script>
</body>
</html>
