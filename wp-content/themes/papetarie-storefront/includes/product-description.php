<?php

defined('ABSPATH') || exit;

/**
 * Randare uniformă a descrierii de produs, la nivel de afișare - nu se
 * modifică niciodată post_content în baza de date.
 *
 * Feed-ul Aperta trimite text simplu, hard-wrapped la o anumită lățime (linii
 * noi în mijlocul unei propoziții/paragraf), amestecat uneori cu: rânduri
 * scurte "Etichetă: valoare" (Dimensiuni, Format, Greutate etc.), liste cu
 * "- element" pe fiecare rând, sau HTML deja valid (<ul><li>, <strong>).
 * wpautop() simplu ar transforma FIECARE linie nouă într-un <br>, dând un
 * "zid" de fragmente rupte în loc de paragrafe curate.
 *
 * Funcțiile de mai jos clasifică fiecare rând (proză / specificație tehnică /
 * element de listă / HTML deja valid) și grupează rândurile consecutive de
 * același tip, ca să obținem automat: proza hard-wrapped se recompune într-un
 * paragraf normal, rândurile "Etichetă: valoare" rămân separate și grupate
 * vizual, listele "- element" devin <ul><li>, iar HTML-ul existent trece
 * neatins. Funcționează identic pentru orice produs, vechi sau nou, fără nicio
 * intervenție manuală după import.
 */
function papetarie_storefront_format_description_content(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    // Produse adăugate manual din editorul cu blocuri (Gutenberg) - au deja
    // structură reală, lăsăm WordPress să le randeze nativ în loc să aplicăm
    // euristica de mai jos, gândită pentru text simplu venit din feed.
    if (function_exists('has_blocks') && has_blocks($raw)) {
        return apply_filters('the_content', $raw);
    }

    // Randurile "<li>Eticheta: valoare</li>" care se regasesc si ca atribute
    // WooCommerce reale (vezi includes/aperta-sync.php,
    // papetarie_storefront_aperta_extract_description_attributes() - aceeasi
    // sursa, folosita si la sincronizare) apar automat in tab-ul
    // Specificatii - le scoatem de aici ca sa nu se vada duplicat in ambele
    // tab-uri, gasit 2026-08-30 (geanta Tellur: Material/Dimensiuni/
    // Greutate aparea si in Descriere, si in Specificatii).
    $promotedAttrs = function_exists('papetarie_storefront_aperta_extract_description_attributes')
        ? papetarie_storefront_aperta_extract_description_attributes($raw)
        : [];

    $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
    // Blocuri reale de paragraf = linii goale in sursa (\n\n) - singura
    // ruptura pe care o respectam necondiționat, neatinsă mai jos.
    $blocks = preg_split('/\n{2,}/', $normalized);
    $html = '';

    foreach ($blocks as $block) {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $block)),
            static fn (string $line): bool => $line !== ''
        ));

        if (empty($lines)) {
            continue;
        }

        foreach (papetarie_storefront_group_description_lines($lines) as $run) {
            $html .= papetarie_storefront_render_description_run($run, $promotedAttrs);
        }
    }

    // wp_kses_post ca plasă de siguranță pe HTML-ul din feed (permite tag-uri
    // uzuale de continut, taie orice ar fi periculos) - wptexturize pentru
    // tipografie normala (ghilimele, liniuțe), la fel ca la restul site-ului.
    return wptexturize(wp_kses_post($html));
}

/**
 * @param string[] $lines
 * @return array<int, array{type: string, lines: string[]}>
 */
function papetarie_storefront_group_description_lines(array $lines): array
{
    $runs = [];
    $current = null;

    foreach ($lines as $line) {
        $type = papetarie_storefront_classify_description_line($line);

        if ($current !== null && $current['type'] === $type) {
            $current['lines'][] = $line;
            continue;
        }

        if ($current !== null) {
            $runs[] = $current;
        }
        $current = ['type' => $type, 'lines' => [$line]];
    }

    if ($current !== null) {
        $runs[] = $current;
    }

    return $runs;
}

function papetarie_storefront_classify_description_line(string $line): string
{
    if ($line[0] === '<') {
        return 'html';
    }

    if (preg_match('/^(?:[-*•]|\d+[.\)])\s+\S/u', $line)) {
        return 'bullet';
    }

    // Eticheta scurta (cel mult 5 cuvinte, sub 40 caractere) urmata de ":" =
    // specificatie tehnica (ex. "Dimensiuni: L 38 x h 30,5 cm") - NU o
    // propozitie normala care intampla sa contina ":" mai departe in text
    // (ex. "...articolele esentiale de afaceri: telefon, pixuri..."), unde
    // partea dinaintea lui ":" e mult mai lunga decat o eticheta reala.
    if (preg_match('/^([\p{L}][\p{L}\s\/\-]{0,39}):(\s*(.*))?$/u', $line, $m)) {
        $label = trim($m[1]);
        $value = isset($m[3]) ? trim($m[3]) : '';
        if (str_word_count($label) <= 5 && mb_strlen($value) <= 100) {
            return 'spec';
        }
    }

    return 'prose';
}

/**
 * Scoate din blocul HTML (o lista <ul>/<ol> deja valida din feed) orice
 * <li>Eticheta: valoare</li> care se regaseste identic printre atributele
 * deja promovate (vezi $promotedAttrs in
 * papetarie_storefront_format_description_content()) - aceleasi
 * label+valoare, extrase cu exact acelasi regex ca la sincronizare, deci
 * garantat sincronizate intre ce se promoveaza si ce se ascunde aici.
 *
 * @param array<string, string> $promotedAttrs
 */
function papetarie_storefront_strip_promoted_li_items(string $html, array $promotedAttrs): string
{
    if (empty($promotedAttrs)) {
        return $html;
    }

    return (string) preg_replace_callback(
        '/<li>\s*([^:<]{2,40}):\s*([^<]+?)\s*<\/li>/iu',
        static function (array $m) use ($promotedAttrs): string {
            $group = mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8');
            $value = trim($m[2]);
            if (isset($promotedAttrs[$group]) && $promotedAttrs[$group] === $value) {
                return '';
            }
            return $m[0];
        },
        $html
    );
}

/**
 * @param array{type: string, lines: string[]} $run
 * @param array<string, string> $promotedAttrs
 */
function papetarie_storefront_render_description_run(array $run, array $promotedAttrs = []): string
{
    switch ($run['type']) {
        case 'html':
            $joined = implode("\n", $run['lines']);

            if (str_contains($joined, '<li>')) {
                $joined = papetarie_storefront_strip_promoted_li_items($joined, $promotedAttrs);
                // Daca au ramas doar o lista goala (toate <li> promovate in
                // Specificatii) sau doar spatii/tag-uri de invelis, nu
                // afisam nimic in loc de un <ul></ul> gol.
                if (trim(wp_strip_all_tags($joined)) === '') {
                    return '';
                }
            }

            // O linie HTML izolata care e doar text ingrosat/emfazat (heading
            // scurt gen "<strong>Caracteristici:</strong>", fara <p> propriu
            // in sursa) - o incadram intr-un <p> ca sa primeasca acelasi
            // ritm de spatiere ca restul continutului, nu sa stea lipita de
            // blocul urmator (ex. lista de dedesubt).
            if (count($run['lines']) === 1 && preg_match('/^<(strong|b|em|i)>.*<\/(strong|b|em|i)>$/iu', $joined)) {
                return '<p>' . $joined . '</p>';
            }
            // Deja HTML valid (din feed sau adaugat manual) - trece neatins.
            return $joined . "\n";

        case 'bullet':
            $items = '';
            foreach ($run['lines'] as $line) {
                $text = (string) preg_replace('/^(?:[-*•]|\d+[.\)])\s+/u', '', $line);
                $items .= '<li>' . $text . '</li>';
            }
            return '<ul>' . $items . '</ul>';

        case 'spec':
            $items = '';
            foreach ($run['lines'] as $line) {
                preg_match('/^([\p{L}][\p{L}\s\/\-]{0,39}):(\s*(.*))?$/u', $line, $m);
                $label = trim($m[1]);
                $value = isset($m[3]) ? trim($m[3]) : '';
                if ($value === '') {
                    $items .= '<p class="pap-product-description-spec-heading">' . $label . ':</p>';
                } else {
                    $items .= '<p class="pap-product-description-spec-line"><strong>' . $label . ':</strong> ' . $value . '</p>';
                }
            }
            return '<div class="pap-product-description-specs">' . $items . '</div>';

        case 'prose':
        default:
            return '<p>' . implode(' ', $run['lines']) . '</p>';
    }
}
