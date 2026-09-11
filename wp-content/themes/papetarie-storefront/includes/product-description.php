<?php

defined('ABSPATH') || exit;

/**
 * Randare uniformă a descrierii de produs, la nivel de afișare - nu se
 * modifică niciodată post_content în baza de date.
 *
 * Feed-ul Aperta trimite text simplu, hard-wrapped la o anumită lățime (linii
 * noi în mijlocul unei propoziții/paragraf), amestecat uneori cu liste -
 * "<li>" HTML deja valid, sau "- element" pe fiecare rând. wpautop() simplu
 * ar transforma FIECARE linie nouă într-un <br>, dând un "zid" de fragmente
 * rupte în loc de paragrafe curate.
 *
 * Regulă simplă și stabilă (2026-08-30, după ce euristica anterioară -
 * promovare selectivă doar a rândurilor "Etichetă: valoare" - s-a dovedit
 * fragilă și inconsistentă): tab-ul Descriere arată STRICT proza (proza
 * hard-wrapped se recompune într-un paragraf normal). Orice element de
 * listă, fără excepție - "<li>", "- element", indiferent dacă arată sau nu
 * ca o pereche etichetă:valoare - e extras separat, la sincronizare
 * (vezi includes/aperta-sync.php,
 * papetarie_storefront_aperta_extract_description_attributes() /
 * extract_plain_list_items()) și afișat DOAR în tab-ul Specificații, ca
 * atribut real de produs. Aici, la randare, orice bloc care conține o listă
 * e pur și simplu omis - nu mai există nicio logică de "arată ca o
 * specificație, deci o promovăm/ascundem selectiv". Funcționează identic
 * pentru orice produs, vechi sau nou, fără nicio intervenție manuală după
 * import.
 */
function papetarie_storefront_format_description_content(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }

    // Descrierile Aperta contin uneori link-uri catre propriile site-uri
    // (aperta.ro - catre produsul "sot" dintr-un set; aperta.shop - pagina
    // proiectului "Caietele care spun povesti") - functionale, dar ar
    // trimite clientul direct pe site-ul furnizorului/concurentei, adesea
    // fara sa arate vizual ca link. Pastram textul ancorei, eliminam doar
    // link-ul. Gasit 2026-09-08, 19 produse afectate pe Descriere cu
    // aperta.ro (alte 8 aveau link-ul intr-o lista, care oricum e mutata in
    // Specificatii si deja iese ca text simplu acolo) + ~30 produse "Caiet
    // Aperta" cu aperta.shop. Decizie user.
    $raw = preg_replace('/<a\b[^>]*\bhref=(["\'])https?:\/\/(?:www\.)?aperta\.(?:ro|shop)[^"\']*\1[^>]*>(.*?)<\/a>/is', '$2', $raw);

    // Produse adăugate manual din editorul cu blocuri (Gutenberg) - au deja
    // structură reală, lăsăm WordPress să le randeze nativ în loc să aplicăm
    // euristica de mai jos, gândită pentru text simplu venit din feed.
    if (function_exists('has_blocks') && has_blocks($raw)) {
        return apply_filters('the_content', $raw);
    }

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
            $html .= papetarie_storefront_render_description_run($run);
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

    // O eticheta simpla, fara nicio valoare pe acelasi rand ("Detalii:",
    // "Conținut:", "Suprafețe:", "Caracteristici:") - apare des in feedul
    // Kreul, ca heading deasupra unei liste HTML de pe randul urmator. Lista
    // e oricum mutata separat in Specificatii (randare de tip 'html' mai
    // jos), deci eticheta ramane orfana si fara sens dac-o lasam ca proza -
    // acelasi tratament ca la heading-urile HTML izolate de mai jos
    // ("<strong>Caracteristici:</strong>" fara lista). Gasit 2026-09-08 la
    // categoriile Kreul din Arta (91 de produse, 229 de aparitii ale acestui
    // tipar). Decizie user: eticheta se elimina, nu se afiseaza goala.
    if (preg_match('/^[\p{L}][\p{L}\s\/\-]{1,60}:\s*$/u', $line)) {
        return 'empty-label';
    }

    // O pereche "Eticheta: valoare" pe rand simplu (fara "-"/<li>) e tot o
    // lista, cu un singur element - extract_description_attributes() din
    // aperta-sync.php o prinde si o promoveaza in Specificatii (acelasi
    // criteriu exact: eticheta 2-40 caractere, valoare pana in 40 caractere)
    // - trebuie sa dispara si de aici, altfel ramane duplicata in Descriere.
    // Gasit 2026-08-30 la rucsacul Exacompta ("Dimensiuni: L 32 x h 41 x l
    // 15 cm." ramanea in proza desi era deja promovat ca atribut).
    if (preg_match('/^([\p{L}][\p{L}\s\/\-]{1,39}):\s*(\S.*)$/u', $line, $m) && mb_strlen(trim($m[2])) <= 40) {
        return 'bullet';
    }

    return 'prose';
}

/**
 * @param array{type: string, lines: string[]} $run
 */
function papetarie_storefront_render_description_run(array $run): string
{
    switch ($run['type']) {
        case 'html':
            $joined = implode("\n", $run['lines']);

            // Orice bloc de lista (<ul>/<ol>, sau <li> izolate) se muta
            // integral in Specificatii - fara exceptie, vezi comentariul de
            // la inceputul fisierului. Nu mai ramane deloc in Descriere.
            if (preg_match('/<(ul|ol|li)[\s>]/i', $joined)) {
                return '';
            }

            // Heading-uri HTML izolate (ex. "<strong>Caracteristici:</strong>",
            // fara <p> propriu in sursa) existau aproape mereu doar ca sa
            // introduca o lista de dedesubt - fara lista (mutata mai sus),
            // ar ramane orfane, deci le scoatem si pe ele.
            if (count($run['lines']) === 1 && preg_match('/^<(strong|b|em|i)>[^<]*<\/(strong|b|em|i)>$/iu', trim($joined))) {
                return '';
            }

            // Deja HTML valid (din feed sau adaugat manual) - trece neatins.
            return $joined . "\n";

        case 'bullet':
            // Fara exceptie, orice bullet se muta in Specificatii - vezi
            // comentariul de la inceputul fisierului.
            return '';

        case 'empty-label':
            // Eticheta fara valoare - vezi comentariul din
            // classify_description_line().
            return '';

        case 'prose':
        default:
            return papetarie_storefront_render_prose_paragraphs($run['lines']);
    }
}

/**
 * Comentariul de la inceputul fisierului explica de ce liniile dintr-un
 * bloc "prose" erau unite cu un singur spatiu, fara sa se tina cont de
 * rupturile simple de linie (\n) - feed-ul Aperta uneori taie o propozitie
 * la mijloc doar din cauza latimii fixe de export, nu pt ca ar fi un gand
 * nou. DAR aceeasi regula unea si propozitii COMPLETE, punctate corect,
 * fiecare pe randul ei, intr-un singur paragraf-zid, greu de citit -
 * semnalat de user cu un exemplu concret (husa laptop NeoDeco) 2026-09-11.
 *
 * Distinctie: daca randul CURENT se termina cu punctuatie de final de
 * propozitie (. ! ?, optional urmata de ghilimele/paranteza), urmatorul
 * rand e cu siguranta un gand nou - devine paragraf separat. Daca NU se
 * termina asa, e cu siguranta continuarea aceleiasi propozitii taiate la
 * mijloc de latimea de export - ramane unit cu un spatiu, ca pana acum.
 * Regula generica, doar la randare (post_content ramane neatins), se
 * aplica automat retroactiv la orice produs afectat, fara migrare.
 *
 * A doua fata a aceleiasi probleme, gasita separat: uneori Aperta trimite
 * TOATE propozitiile unui produs pe UN SINGUR rand brut, fara niciun \n
 * intre ele (ex. "Grund de umplere... Se usuca rapid... Aerosol extrem de
 * inflamabil!..." tot pe acelasi rand) - regula de mai sus, care lucreaza
 * doar cu rupturile de linie deja existente, nu are ce sa desparta in
 * cazul asta, ramanand tot un singur paragraf-zid. Semnalat de user cu un
 * al doilea exemplu concret (spray Schneider Primer) 2026-09-11, imediat
 * dupa primul fix. Rezolvat desfacand FIECARE rand in propozitii separate
 * INAINTE de gruparea de mai jos (vezi papetarie_storefront_split_into_sentences()),
 * care foloseste acelasi semnal (majuscula dupa punct/semn de exclamare/
 * intrebare) ca sa gaseasca limitele reale de propozitie chiar si intr-un
 * rand brut nedespartit deloc.
 *
 * @param string[] $lines
 */
function papetarie_storefront_render_prose_paragraphs(array $lines): string
{
    $sentences = [];
    foreach ($lines as $line) {
        foreach (papetarie_storefront_split_into_sentences($line) as $sentence) {
            $sentences[] = $sentence;
        }
    }

    $paragraphs = [];
    $current = '';

    foreach ($sentences as $sentence) {
        $current = $current === '' ? $sentence : $current . ' ' . $sentence;

        if (preg_match('/[.!?]["\')]*$/u', $sentence)) {
            $paragraphs[] = $current;
            $current = '';
        }
    }

    if ($current !== '') {
        $paragraphs[] = $current;
    }

    $html = '';
    foreach ($paragraphs as $paragraph) {
        $html .= '<p>' . $paragraph . '</p>';
    }

    return $html;
}

/**
 * Desparte un rand brut in propozitii, folosind acelasi semnal folosit si
 * de vecina ei de mai sus (papetarie_storefront_render_prose_paragraphs()):
 * o majuscula imediat dupa un semn de final de propozitie (. ! ?) urmat de
 * spatiu e aproape sigur un gand nou, nu o prescurtare/numar zecimal -
 * "200 ml. Compatibil cu..." se desparte corect (Compatibil = majuscula),
 * "20.5 cm este..." NU se desparte (e = minuscula, deci nu e o propozitie
 * noua, ramane "20.5 cm este..." intreg).
 *
 * @return string[]
 */
function papetarie_storefront_split_into_sentences(string $line): array
{
    $line = trim($line);
    if ($line === '') {
        return [];
    }

    $parts = preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', $line);
    if ($parts === false) {
        return [$line];
    }

    return array_values(array_filter(
        array_map('trim', $parts),
        static fn(string $part): bool => $part !== ''
    ));
}
