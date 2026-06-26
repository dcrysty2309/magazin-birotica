<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$expectedToken = '__CLEANUP_TOKEN__';
$files = [
    'staging-db-export.php',
    'staging-db-import.php',
    'staging-db-import-v2.php',
    'staging-db-import-v3.php',
    'staging-checkout-cases-inspect.php',
    'staging-opcache-reset.php',
    'staging-sync-db-runner.php',
    'staging-sync-db-runner-v2.php',
    'staging-sync-db.sql',
    'staging-sync-db-v2.sql',
];

if (!isset($_GET['token']) || !hash_equals($expectedToken, (string) $_GET['token'])) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'message' => 'Token invalid.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$deleted = [];
$missing = [];
$failed = [];

foreach ($files as $file) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($path)) {
        $missing[] = $file;
        continue;
    }

    if (@unlink($path)) {
        $deleted[] = $file;
        continue;
    }

    $failed[] = $file;
}

$self = basename(__FILE__);
if (@unlink(__FILE__)) {
    $deleted[] = $self;
} else {
    $failed[] = $self;
}

echo json_encode([
    'ok' => empty($failed),
    'deleted' => $deleted,
    'missing' => $missing,
    'failed' => $failed,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
