<?php
if (defined('ADMIN_INIT')) {
    return;
}

define('ADMIN_INIT', true);
require_once __DIR__ . '/../includes/functions.php';
require_login();
require_admin();

if (empty($_SESSION['csrf_token'])) {
    get_csrf_token();
}

function admin_column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return (bool)$stmt->fetch();
}

function admin_ensure_schema(PDO $pdo): void {
    $columns = [
        ['table' => 'users', 'column' => 'is_active', 'definition' => 'TINYINT(1) NOT NULL DEFAULT 1'],
        ['table' => 'products', 'column' => 'is_enabled', 'definition' => 'TINYINT(1) NOT NULL DEFAULT 1'],
        ['table' => 'products', 'column' => 'sizes', 'definition' => 'VARCHAR(255) DEFAULT NULL'],
        ['table' => 'products', 'column' => 'colors', 'definition' => 'VARCHAR(255) DEFAULT NULL'],
    ];

    foreach ($columns as $columnDef) {
        if (!admin_column_exists($pdo, $columnDef['table'], $columnDef['column'])) {
            $pdo->exec(sprintf(
                'ALTER TABLE `%s` ADD COLUMN `%s` %s',
                $columnDef['table'],
                $columnDef['column'],
                $columnDef['definition']
            ));
        }
    }

    $stmt = $pdo->prepare("SHOW TABLES LIKE 'site_settings'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `site_settings` (' .
            '`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,' .
            '`setting_key` VARCHAR(100) NOT NULL UNIQUE,' .
            '`setting_value` TEXT DEFAULT NULL,' .
            '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,' .
            'PRIMARY KEY (`id`)' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }
}

admin_ensure_schema($pdo);
