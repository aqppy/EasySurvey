<?php
/**
 * Survey System Database Backup Script (CLI)
 */

define('SURVEY_SYSTEM', true);

// Setup paths
define('ROOT_PATH', dirname(__DIR__));
$configFile = ROOT_PATH . '/app/config.php';

if (!file_exists($configFile)) {
    echo "【错误】配置文件 app/config.php 不存在，请先配置系统。\n";
    exit(1);
}

require_once $configFile;
require_once ROOT_PATH . '/app/database.php';

echo "=========================================\n";
echo "    Survey System Database Backup Utility\n";
echo "=========================================\n";

try {
    $db = getDB();
    echo "成功连接到数据库: " . DB_NAME . "\n";

    // Create backups directory if not exists
    $backupDir = ROOT_PATH . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }

    $fileName = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';
    $filePath = $backupDir . '/' . $fileName;

    echo "正在开始备份所有数据表...\n";

    // Get list of tables
    $tables = [];
    $result = $db->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlContent = "-- Survey System Database Backup\n";
    $sqlContent .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Host: " . DB_HOST . ":" . DB_PORT . "\n";
    $sqlContent .= "-- Database: " . DB_NAME . "\n\n";
    $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        echo "  - 备份表: {$table} ... ";
        
        // Add drop table structure
        $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        // Get create table structure
        $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch(PDO::FETCH_NUM);
        $sqlContent .= $createRow[1] . ";\n\n";

        // Get row insertions
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $sqlContent .= "INSERT INTO `{$table}` (";
            $columns = array_keys($rows[0]);
            $sqlContent .= implode(', ', array_map(function($c) { return "`$c`"; }, $columns)) . ") VALUES\n";
            
            $insertRows = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = $db->quote($val);
                    }
                }
                $insertRows[] = "(" . implode(', ', $values) . ")";
            }
            $sqlContent .= implode(",\n", $insertRows) . ";\n\n";
        }
        echo "【成功】(" . count($rows) . " 行)\n";
    }

    $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

    file_put_contents($filePath, $sqlContent);

    echo "-----------------------------------------\n";
    echo "【备份成功！】文件已保存至:\n";
    echo "{$filePath}\n";
    echo "=========================================\n";
} catch (Exception $e) {
    echo "【错误】备份失败: " . $e->getMessage() . "\n";
    exit(1);
}
