<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) : '问卷调查系统'; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($pageStyles)): ?>
        <?php echo $pageStyles; ?>
    <?php endif; ?>
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body>
    <div class="container">
