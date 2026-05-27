<?php

declare(strict_types=1);

$packages = [
    [
        'url' => 'https://downloads.wordpress.org/theme/blocksy.latest-stable.zip',
        'target' => '/var/www/html/wp-content/themes',
        'tmp' => '/tmp/blocksy.zip',
    ],
    [
        'url' => 'https://downloads.wordpress.org/plugin/blocksy-companion.latest-stable.zip',
        'target' => '/var/www/html/wp-content/plugins',
        'tmp' => '/tmp/blocksy-companion.zip',
    ],
];

foreach ($packages as $package) {
    $zipData = @file_get_contents($package['url']);

    if ($zipData === false) {
        throw new RuntimeException('Download failed: ' . $package['url']);
    }

    if (file_put_contents($package['tmp'], $zipData) === false) {
        throw new RuntimeException('Write failed: ' . $package['tmp']);
    }

    $zip = new ZipArchive();
    $result = $zip->open($package['tmp']);

    if ($result !== true) {
        throw new RuntimeException('Open failed: ' . $package['tmp'] . ' code=' . $result);
    }

    if (!$zip->extractTo($package['target'])) {
        throw new RuntimeException('Extract failed to: ' . $package['target']);
    }

    $zip->close();
}

echo "Blocksy packages installed.\n";
