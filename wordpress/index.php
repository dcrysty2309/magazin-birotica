<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
if (isset($_SERVER['HTTP_HOST'])) {
    $host = strtolower((string) $_SERVER['HTTP_HOST']);
    if ($host === 'memoreaza.ro' || str_ends_with($host, '.memoreaza.ro')) {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo '<!doctype html><html lang="ro"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title></title><style>html,body{margin:0;background:#fff;color:#111}</style></head><body></body></html>';
        exit;
    }
}

define( 'WP_USE_THEMES', true );

/** Loads the WordPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
