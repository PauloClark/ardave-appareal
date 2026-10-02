<?php
// Secure PDO connection for Ardave Apparel (XAMPP default uses root / no password)
// Update DB_HOST, DB_NAME, DB_USER and DB_PASS in .env or the server environment.

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

define('DB_HOST', get_config('DB_HOST', '127.0.0.1'));
define('DB_NAME', get_config('DB_NAME', 'ardave_apparel'));
define('DB_USER', get_config('DB_USER', 'root'));
define('DB_PASS', get_config('DB_PASS', ''));
define('DB_CHARSET', get_config('DB_CHARSET', 'utf8mb4'));

function getPDO(): PDO
{
	static $pdo = null;
	if ($pdo instanceof PDO) {
		return $pdo;
	}

	$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES => false,
	];

	try {
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
		return $pdo;
	} catch (PDOException $e) {
		// For security, do not reveal sensitive details in production.
		http_response_code(500);
		echo 'Database connection failed. Check configuration.';
		error_log('PDO connection error: ' . $e->getMessage());
		exit;
	}
}

$pdo = getPDO();

/**
 * One-time migration: deduplicate products and add UNIQUE index on sku.
 * The seed SQL used INSERT IGNORE without a UNIQUE constraint on sku,
 * so running database.sql multiple times created duplicate product rows.
 */
try {
    // Check if UNIQUE index on sku already exists
    $checkStmt = $pdo->query("SHOW INDEX FROM products WHERE Column_name = 'sku' AND Non_unique = 0");
    $hasUnique = (bool)$checkStmt->fetch();

    if (!$hasUnique) {
        // First: delete duplicate products (keep the row with the lowest id for each name).
        // This is safe because duplicates were accidentally created by repeated seed SQL runs.
        $pdo->exec("
            DELETE p1 FROM products p1
            INNER JOIN products p2
            WHERE p1.name = p2.name AND p1.id > p2.id
        ");
        error_log('Deduplication: removed duplicate product rows.');

        // Add UNIQUE constraint on sku to prevent future duplicates.
        // First set any NULL skus to a unique placeholder to avoid constraint violations.
        $pdo->exec("UPDATE products SET sku = CONCAT('AUTO-', id) WHERE sku IS NULL");
        $pdo->exec("ALTER TABLE products ADD UNIQUE INDEX idx_products_sku (sku)");
        error_log('Migration: added UNIQUE index on products.sku.');
    }
} catch (Exception $e) {
    // Non-fatal: log and continue. The product query will still work,
    // though duplicates may appear until the migration runs successfully.
    error_log('Product dedup migration skipped: ' . $e->getMessage());
}

