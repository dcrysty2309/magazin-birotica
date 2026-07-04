<?php
declare(strict_types=1);

set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');

$expectedToken = '__PACKAGE_TOKEN__';
$defaultZipFile = '__PACKAGE_ZIP_FILE__';

function package_respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_GET['token']) || !hash_equals($expectedToken, (string) $_GET['token'])) {
    package_respond(403, [
        'success' => false,
        'message' => 'Token invalid.',
    ]);
}

$baseDir = __DIR__;
$zipFileName = basename((string) ($_GET['zip'] ?? $defaultZipFile));
$zipPath = $baseDir . DIRECTORY_SEPARATOR . $zipFileName;
$cleanupZip = isset($_GET['cleanup_zip']) ? (string) $_GET['cleanup_zip'] === '1' : true;
$cleanupRunner = isset($_GET['cleanup_runner']) ? (string) $_GET['cleanup_runner'] === '1' : true;
$selfFile = basename(__FILE__);

if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'wp-load.php')) {
    package_respond(500, [
        'success' => false,
        'message' => 'wp-load.php nu a fost gasit in public_html.',
    ]);
}

require_once $baseDir . DIRECTORY_SEPARATOR . 'wp-load.php';

if (!class_exists('ZipArchive')) {
    package_respond(500, [
        'success' => false,
        'message' => 'ZipArchive nu este disponibil pe server.',
    ]);
}

if (!file_exists($zipPath)) {
    package_respond(404, [
        'success' => false,
        'message' => 'Pachetul ZIP nu exista pe server.',
        'zip' => $zipFileName,
    ]);
}

$archive = new ZipArchive();
$openResult = $archive->open($zipPath);
if ($openResult !== true) {
    package_respond(500, [
        'success' => false,
        'message' => 'Nu am putut deschide pachetul ZIP.',
        'code' => $openResult,
        'zip' => $zipFileName,
    ]);
}

$importStartedAt = microtime(true);
$extractOk = $archive->extractTo($baseDir);
$archive->close();

if (!$extractOk) {
    package_respond(500, [
        'success' => false,
        'message' => 'Extragerea pachetului ZIP a esuat.',
    ]);
}

clearstatcache(true);

$result = [
    'success' => true,
    'message' => 'Pachetul a fost extras cu succes.',
    'data' => [
        'zip_file' => $zipFileName,
        'import_seconds' => microtime(true) - $importStartedAt,
        'siteurl' => function_exists('get_option') ? get_option('siteurl') : null,
        'home' => function_exists('get_option') ? get_option('home') : null,
        'cleanup_zip' => $cleanupZip,
        'cleanup_runner' => $cleanupRunner,
    ],
];

if ($cleanupZip) {
    @unlink($zipPath);
}

if ($cleanupRunner) {
    @unlink(__FILE__);
}

package_respond(200, $result);
