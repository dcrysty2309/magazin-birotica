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

$packageDir = __DIR__;
$baseDir = dirname(__DIR__, 4);
$zipFileName = basename((string) ($_GET['zip'] ?? $defaultZipFile));
$zipPath = $packageDir . DIRECTORY_SEPARATOR . $zipFileName;
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
$filesystemChecks = [];
$zipChecks = [];

if (is_readable($zipPath)) {
    $zipChecks['package_zip'] = [
        'path' => $zipPath,
        'exists' => true,
        'sha256' => strtoupper(hash_file('sha256', $zipPath)),
        'size' => filesize($zipPath),
        'mtime' => filemtime($zipPath),
    ];
}
else {
    $zipChecks['package_zip'] = [
        'path' => $zipPath,
        'exists' => false,
    ];
}

if ($done) {
    $stylePath = $baseDir . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'papetarie-storefront' . DIRECTORY_SEPARATOR . 'style.css';
    $filesystemChecks['theme_style_css'] = [
        'path' => $stylePath,
        'exists' => file_exists($stylePath),
    ];

    if (is_readable($stylePath)) {
        $filesystemChecks['theme_style_css']['sha256'] = strtoupper(hash_file('sha256', $stylePath));
        $filesystemChecks['theme_style_css']['size'] = filesize($stylePath);
        $filesystemChecks['theme_style_css']['mtime'] = filemtime($stylePath);
    }
}

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
        'zip_checks' => $zipChecks,
        'filesystem_checks' => $filesystemChecks,
    ],
];

if ($done && $cleanupZip) {
    @unlink($zipPath);
}

if ($done && $cleanupRunner) {
    @unlink(__FILE__);
}

package_respond(200, $result);
