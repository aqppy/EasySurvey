<?php
/**
 * 退出登录
 */

define('SURVEY_SYSTEM', true);
@include_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/database.php';
require_once __DIR__ . '/../../app/functions.php';

session_start();
session_destroy();

redirect('/admin/login.php');
