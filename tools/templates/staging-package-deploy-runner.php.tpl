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
$batchSize = max(1, (int) ($_GET['batch'] ?? 120));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$cleanupZip = isset($_GET['cleanup_zip']) ? (string) $_GET['cleanup_zip'] === '1' : true;
$cleanupRunner = isset($_GET['cleanup_runner']) ? (string) $_GET['cleanup_runner'] === '1' : true;
$selfFile = basename(__FILE__);

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

if ($batchSize > 250) {
    $batchSize = 250;
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

$totalFiles = $archive->numFiles;
if ($offset > $totalFiles) {
    $offset = $totalFiles;
}

$files = [];
$limit = min($totalFiles, $offset + $batchSize);
for ($index = $offset; $index < $limit; $index++) {
    $entry = $archive->getNameIndex($index);
    if ($entry === false || $entry === null || substr($entry, -1) === '/') {
        continue;
    }

    $files[] = $entry;
}

$importStartedAt = microtime(true);
$extractOk = true;
if ($files !== []) {
    $extractOk = $archive->extractTo($baseDir, $files);
}
$archive->close();

if (!$extractOk) {
    package_respond(500, [
        'success' => false,
        'message' => 'Extragerea pachetului ZIP a esuat.',
        'offset' => $offset,
        'batch' => $batchSize,
    ]);
}

clearstatcache(true);

$nextOffset = $offset + count($files);
$done = $nextOffset >= $totalFiles;

$result = [
    'success' => true,
    'message' => $done ? 'Pachetul a fost extras cu succes.' : 'Pachetul a fost extras partial.',
    'data' => [
        'zip_file' => $zipFileName,
        'import_seconds' => microtime(true) - $importStartedAt,
        'cleanup_zip' => $cleanupZip,
        'cleanup_runner' => $cleanupRunner,
        'offset' => $offset,
        'next_offset' => $nextOffset,
        'total_files' => $totalFiles,
        'processed_files' => count($files),
        'done' => $done,
    ],
];

if ($done && $cleanupZip) {
    @unlink($zipPath);
}

if ($done && $cleanupRunner) {
    @unlink(__FILE__);
}

package_respond(200, $result);
