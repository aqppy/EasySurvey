<?php
/**
 * Survey submit API
 */

error_reporting(0);
ini_set('display_errors', '0');
set_error_handler(function () {
    return true;
});

set_exception_handler(function ($e) {
    error_log('问卷提交异常: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => 1, 'message' => '系统错误'], JSON_UNESCAPED_UNICODE);
    exit;
});

define('SURVEY_SYSTEM', true);

while (ob_get_level() > 0) {
    ob_end_clean();
}

@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(1, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['survey_id']) || !isset($input['answers'])) {
    jsonResponse(1, '参数错误');
}

$surveyId = intval($input['survey_id']);
$answers = $input['answers'];
$db = getDB();

$stmt = $db->prepare("SELECT id, status FROM surveys WHERE id = ?");
$stmt->execute([$surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    jsonResponse(1, '问卷不存在');
}

if (intval($survey['status']) !== 1) {
    jsonResponse(1, '当前问卷已停用');
}

$settings = getSurveySettings($surveyId);

$stmt = $db->prepare("SELECT id, type, options, required FROM questions WHERE survey_id = ?");
$stmt->execute([$surveyId]);
$questionsDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

$questions = [];
foreach ($questionsDb as $q) {
    $questions[intval($q['id'])] = [
        'type' => $q['type'],
        'options' => $q['options'] ? json_decode($q['options'], true) : null,
        'required' => !empty($q['required'])
    ];
}

// 校验提交的题目 ID 是否均属于该问卷
foreach ($answers as $qId => $val) {
    $qId = intval($qId);
    if (!isset($questions[$qId])) {
        jsonResponse(1, '参数错误');
    }
}

// 校验每一个问题的必填性及选项值合法性
foreach ($questions as $qId => $question) {
    $val = $answers[$qId] ?? null;
    $required = $question['required'];
    $type = $question['type'];
    $options = $question['options'];

    $isEmpty = false;
    if ($val === null) {
        $isEmpty = true;
    } elseif (is_array($val)) {
        $isEmpty = (count($val) === 0);
    } else {
        $isEmpty = (trim((string) $val) === '');
    }

    if ($required && $isEmpty) {
        jsonResponse(1, '请填写所有必填项');
    }

    if (!$isEmpty) {
        if ($type === 'radio') {
            if (is_array($val)) {
                jsonResponse(1, '参数错误');
            }
            if (is_array($options) && !in_array((string) $val, $options, true)) {
                jsonResponse(1, '参数错误');
            }
        } elseif ($type === 'checkbox') {
            if (!is_array($val)) {
                jsonResponse(1, '参数错误');
            }
            if (is_array($options)) {
                foreach ($val as $item) {
                    if (!in_array((string) $item, $options, true)) {
                        jsonResponse(1, '参数错误');
                      }
                }
            }
        } elseif ($type === 'text') {
            if (is_array($val)) {
                jsonResponse(1, '参数错误');
            }
            if (mb_strlen((string) $val, 'UTF-8') > MAX_ANSWER_LENGTH) {
                jsonResponse(1, '答案内容过长，请精简后重试');
            }
        }
    }
}

$ip = getClientIP();
$allowRepeatSubmit = !empty($settings['allow_repeat_submit']);
$cookieName = "survey_submitted_{$surveyId}";

if (!$allowRepeatSubmit && isset($_COOKIE[$cookieName])) {
    jsonResponse(1, '您已提交过此问卷，请勿重复提交');
}

if (!checkIPSubmissionLimit($ip, $surveyId)) {
    jsonResponse(1, '提交过于频繁，请稍后再试');
}

$db->beginTransaction();

try {
    $stmt = $db->prepare("INSERT INTO responses (survey_id, ip_address) VALUES (?, ?)");
    $stmt->execute([$surveyId, $ip]);
    $responseId = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO answers (response_id, question_id, answer_value) VALUES (?, ?, ?)");

    foreach ($answers as $questionId => $answerValue) {
        if (is_array($answerValue)) {
            $answerValue = json_encode(array_values($answerValue), JSON_UNESCAPED_UNICODE);
        }

        $stmt->execute([$responseId, intval($questionId), (string) $answerValue]);
    }

    $db->commit();

    if (!$allowRepeatSubmit) {
        $cookieSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $cookieSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $cookieSecure = true;
        }

        setcookie($cookieName, '1', time() + SUBMIT_COOKIE_EXPIRE, '/', '', $cookieSecure, true);
    }

    jsonResponse(0, '提交成功');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('问卷提交失败: ' . $e->getMessage());
    jsonResponse(1, '提交失败，请重试');
}
