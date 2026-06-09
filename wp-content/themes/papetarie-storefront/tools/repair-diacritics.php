<?php

$targets = [
    '/var/www/html/wp-content/themes/papetarie-storefront/functions.php' => [
        'Articole hârtie' => 'Articole hârtie',
        'Școlare' => 'Școlare',
        'Coșul nu este disponibil momentan.' => 'Coșul nu este disponibil momentan.',
        'Sesiunea a expirat. Reîncarcă pagina.' => 'Sesiunea a expirat. Reîncarcă pagina.',
        'Produsul nu a fost găsit.' => 'Produsul nu a fost găsit.',
        'Produsul nu poate fi adăugat în coș.' => 'Produsul nu poate fi adăugat în coș.',
        'Nu am putut adăuga produsul în coș.' => 'Nu am putut adăuga produsul în coș.',
        'Produsul a fost adăugat în coș' => 'Produsul a fost adăugat în coș',
    ],
    '/var/www/html/wp-content/themes/papetarie-storefront/style.css' => [
        'content: "→";' => 'content: "→";',
        'content: "·";' => 'content: "·";',
        'content: "·" !important;' => 'content: "·" !important;',
    ],
];

foreach ($targets as $path => $replacements) {
    $contents = file_get_contents($path);

    if ($contents === false) {
        fwrite(STDERR, "Could not read {$path}\n");
        continue;
    }

    $updated = strtr($contents, $replacements);
    file_put_contents($path, $updated);
    echo basename($path) . " updated\n";
}
