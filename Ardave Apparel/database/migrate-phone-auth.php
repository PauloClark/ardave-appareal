<?php
// Run only from CLI; never migrate implicitly during a login request.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/connection.php';
$sql = file_get_contents(__DIR__ . '/migrations/20260924_phone_auth.sql');
$pdo->exec(preg_replace('/^\xEF\xBB\xBF/', '', $sql));
echo "Phone authentication migration applied. No legacy phones were linked.\n";
