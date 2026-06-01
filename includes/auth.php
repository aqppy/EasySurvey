<?php
/**
 * 认证检查
 * 在后台页面开头引入此文件
 */

if (!defined('SURVEY_SYSTEM')) {
    die('Access denied');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
requireLogin();
