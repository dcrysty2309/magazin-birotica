<?php
declare(strict_types=1);

set_time_limit(0);

header('Content-Type: application/json; charset=utf-8');

$expectedToken = '__SYNC_TOKEN__';
$defaultSqlFile = '__SYNC_SQL_FILE__';
$defaultFromUrl = '__SYNC_FROM_URL__';
$defaultToUrl = '__SYNC_TO_URL__';

function sync_respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_GET['token']) || !hash_equals($expectedToken, (string) $_GET['token'])) {
    sync_respond(403, [
        'success' => false,
        'message' => 'Token invalid.',
    ]);
}

$baseDir = __DIR__;
$sqlFileName = basename((string) ($_GET['sql'] ?? $defaultSqlFile));
$fromUrl = (string) ($_GET['from'] ?? $defaultFromUrl);
$toUrl = (string) ($_GET['to'] ?? $defaultToUrl);
$cleanupSql = isset($_GET['cleanup_sql']) ? (string) $_GET['cleanup_sql'] === '1' : true;
$sqlPath = $baseDir . DIRECTORY_SEPARATOR . $sqlFileName;

if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'wp-load.php')) {
    sync_respond(500, [
        'success' => false,
        'message' => 'wp-load.php nu a fost gasit in public_html.',
    ]);
}

require_once $baseDir . DIRECTORY_SEPARATOR . 'wp-load.php';

if (!file_exists($sqlPath)) {
    sync_respond(404, [
        'success' => false,
        'message' => 'Fisierul SQL nu exista pe server.',
        'sql' => $sqlFileName,
    ]);
}

global $wpdb;

function sync_parse_db_host(string $dbHost): array
{
    $socket = null;
    $port = null;
    $host = $dbHost;

    if (strpos($dbHost, ':') !== false) {
        [$candidateHost, $candidatePort] = explode(':', $dbHost, 2);
        if ($candidatePort !== '' && ctype_digit($candidatePort)) {
            $host = $candidateHost;
            $port = (int) $candidatePort;
        } elseif ($candidatePort !== '') {
            $host = $candidateHost;
            $socket = $candidatePort;
        }
    }

    return [$host, $port, $socket];
}

function sync_recursive_replace($value, string $from, string $to)
{
    if (is_string($value)) {
        return str_replace($from, $to, $value);
    }

    if (is_array($value)) {
        foreach ($value as $key => $item) {
            $value[$key] = sync_recursive_replace($item, $from, $to);
        }

        return $value;
    }

    if (is_object($value)) {
        foreach (get_object_vars($value) as $property => $item) {
            $value->{$property} = sync_recursive_replace($item, $from, $to);
        }
    }

    return $value;
}

function sync_replace_cell_value($value, string $from, string $to): array
{
    if (!is_string($value) || $value === '') {
        return [false, $value];
    }

    $isSerialized = is_serialized($value);
    $workingValue = $isSerialized ? maybe_unserialize($value) : $value;
    $replacedValue = sync_recursive_replace($workingValue, $from, $to);
    $normalizedValue = $isSerialized ? maybe_serialize($replacedValue) : $replacedValue;

    return [$normalizedValue !== $value, $normalizedValue];
}

function sync_get_text_columns(array $columns): array
{
    $supportedTypes = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'json'];
    $textColumns = [];

    foreach ($columns as $column) {
        $type = strtolower((string) $column['Type']);
        foreach ($supportedTypes as $supportedType) {
            if (strpos($type, $supportedType) === 0) {
                $textColumns[] = (string) $column['Field'];
                break;
            }
        }
    }

    return $textColumns;
}

function sync_get_primary_keys(array $columns): array
{
    $keys = [];

    foreach ($columns as $column) {
        if (($column['Key'] ?? '') === 'PRI') {
            $keys[] = (string) $column['Field'];
        }
    }

    return $keys;
}

function sync_replace_urls_in_database(wpdb $wpdb, string $from, string $to): array
{
    $report = [
        'tables_scanned' => 0,
        'rows_updated' => 0,
        'cells_updated' => 0,
        'table_updates' => [],
    ];

    $tables = $wpdb->get_col('SHOW TABLES');
    if (!is_array($tables)) {
        return $report;
    }

    foreach ($tables as $table) {
        if (strpos((string) $table, $wpdb->prefix) !== 0) {
            continue;
        }

        $columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table}`", ARRAY_A);
        if (!is_array($columns) || $columns === []) {
            continue;
        }

        $primaryKeys = sync_get_primary_keys($columns);
        $textColumns = sync_get_text_columns($columns);

        if ($primaryKeys === [] || $textColumns === []) {
            continue;
        }

        $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
        if (!is_array($rows) || $rows === []) {
            $report['tables_scanned']++;
            continue;
        }

        $tableRowsUpdated = 0;
        $tableCellsUpdated = 0;

        foreach ($rows as $row) {
            $updateData = [];
            foreach ($textColumns as $columnName) {
                if (!array_key_exists($columnName, $row)) {
                    continue;
                }

                [$changed, $newValue] = sync_replace_cell_value($row[$columnName], $from, $to);
                if ($changed) {
                    $updateData[$columnName] = $newValue;
                    $tableCellsUpdated++;
                    $report['cells_updated']++;
                }
            }

            if ($updateData === []) {
                continue;
            }

            $where = [];
            foreach ($primaryKeys as $primaryKey) {
                $where[$primaryKey] = $row[$primaryKey];
            }

            $updated = $wpdb->update($table, $updateData, $where);
            if ($updated !== false) {
                $tableRowsUpdated++;
                $report['rows_updated']++;
            }
        }

        $report['tables_scanned']++;

        if ($tableRowsUpdated > 0) {
            $report['table_updates'][$table] = [
                'rows_updated' => $tableRowsUpdated,
                'cells_updated' => $tableCellsUpdated,
            ];
        }
    }

    return $report;
}

[$dbHost, $dbPort, $dbSocket] = sync_parse_db_host(DB_HOST);

$mysqli = mysqli_init();
$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);

$connected = $mysqli->real_connect(
    $dbHost,
    DB_USER,
    DB_PASSWORD,
    DB_NAME,
    $dbPort ?? 3306,
    $dbSocket
);

if (!$connected) {
    sync_respond(500, [
        'success' => false,
        'message' => 'Conectarea mysqli a esuat.',
        'error' => mysqli_connect_error(),
    ]);
}

$sqlContents = file_get_contents($sqlPath);
if ($sqlContents === false || trim($sqlContents) === '') {
    sync_respond(500, [
        'success' => false,
        'message' => 'Fisierul SQL este gol sau nu poate fi citit.',
        'sql' => $sqlFileName,
    ]);
}

$sqlContents = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContents);

$importStartedAt = microtime(true);
$importOk = $mysqli->multi_query($sqlContents);

if (!$importOk) {
    sync_respond(500, [
        'success' => false,
        'message' => 'Importul SQL a esuat la pornire.',
        'error' => $mysqli->error,
    ]);
}

do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

if ($mysqli->errno) {
    sync_respond(500, [
        'success' => false,
        'message' => 'Importul SQL a esuat in timpul executiei.',
        'error' => $mysqli->error,
    ]);
}

$importDuration = round(microtime(true) - $importStartedAt, 3);

update_option('home', $toUrl);
update_option('siteurl', $toUrl);

$replaceReport = sync_replace_urls_in_database($wpdb, $fromUrl, $toUrl);

update_option('home', $toUrl);
update_option('siteurl', $toUrl);

if (function_exists('flush_rewrite_rules')) {
    flush_rewrite_rules(true);
}

if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

if ($cleanupSql && file_exists($sqlPath)) {
    @unlink($sqlPath);
}

sync_respond(200, [
    'success' => true,
    'message' => 'Sincronizarea DB s-a terminat cu succes.',
    'data' => [
        'sql_file' => $sqlFileName,
        'import_seconds' => $importDuration,
        'from_url' => $fromUrl,
        'to_url' => $toUrl,
        'siteurl' => get_option('siteurl'),
        'home' => get_option('home'),
        'replace_report' => $replaceReport,
        'cleanup_sql' => $cleanupSql,
    ],
]);
