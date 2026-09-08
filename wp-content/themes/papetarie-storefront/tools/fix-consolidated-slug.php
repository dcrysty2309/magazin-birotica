<?php

declare(strict_types=1);

/**
 * Fix punctual: cand consolidate-single-variant-wrappers.php a re-publicat
 * un parinte care fusese in "trash", WordPress ii pastreaza slug-ul
 * "__trashed-N" primit automat cand a fost trecut la gunoi (nu-l reface
 * singur la slug-ul curat original) - gasit live 2026-09-04 pe produsul
 * consolidat "Marker metalic Schneider Paint-It 011 2 mm" (#24232),
 * ajuns la /product/__trashed-2/ in loc de un link curat.
 *
 * Seteaza slug-ul corect DOAR daca e liber (nu suprascrie alt produs).
 *
 * Deschide in browser cu ?t=<token>&post_id=<id>&slug=<slug-dorit>&apply=1
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

const PAP_FIX_SLUG_TOKEN = 'fix-slug-2026-09-04-q9k2';

if (($_GET['t'] ?? '') !== PAP_FIX_SLUG_TOKEN) {
    http_response_code(404);
    exit;
}

$postId = (int) ($_GET['post_id'] ?? 0);
$desiredSlug = sanitize_title((string) ($_GET['slug'] ?? ''));
$apply = ($_GET['apply'] ?? '') === '1';

if ($postId <= 0 || $desiredSlug === '') {
    echo "Lipseste post_id sau slug.\n";
    exit;
}

$post = get_post($postId);
if (!$post) {
    echo "Nu exista postarea #$postId.\n";
    exit;
}

echo "Post #$postId: slug curent = {$post->post_name}, titlu = {$post->post_title}\n";
echo "Slug dorit: $desiredSlug\n\n";

$conflict = get_page_by_path($desiredSlug, OBJECT, $post->post_type);
if ($conflict && (int) $conflict->ID !== $postId) {
    echo "EROARE: slug-ul e deja ocupat de post #{$conflict->ID} ({$conflict->post_title}) - nu ating nimic.\n";
    exit;
}

if (!$apply) {
    echo "DRY-RUN - adauga &apply=1 ca sa aplici.\n";
    exit;
}

$result = wp_update_post([
    'ID' => $postId,
    'post_name' => $desiredSlug,
], true);

if (is_wp_error($result)) {
    echo "EROARE: " . $result->get_error_message() . "\n";
    exit;
}

$after = get_post($postId);
echo "Slug nou: {$after->post_name}\n";
echo "Permalink nou: " . get_permalink($postId) . "\n";
