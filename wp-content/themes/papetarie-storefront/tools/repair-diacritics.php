<?php

$targets = [
    '/var/www/html/wp-content/themes/papetarie-storefront/functions.php' => [
        'Articole hÃ¢rtie' => 'Articole hârtie',
        'È˜colare' => 'Școlare',
        'CoÈ™ul nu este disponibil momentan.' => 'Coșul nu este disponibil momentan.',
        'Sesiunea a expirat. ReÃ®ncarcÄƒ pagina.' => 'Sesiunea a expirat. Reîncarcă pagina.',
        'Produsul nu a fost gÄƒsit.' => 'Produsul nu a fost găsit.',
        'Produsul nu poate fi adÄƒugat Ã®n coÈ™.' => 'Produsul nu poate fi adăugat în coș.',
        'Nu am putut adÄƒuga produsul Ã®n coÈ™.' => 'Nu am putut adăuga produsul în coș.',
        'Produsul a fost adÄƒugat Ã®n coÈ™' => 'Produsul a fost adăugat în coș',
    ],
    '/var/www/html/wp-content/themes/papetarie-storefront/style.css' => [
        'content: "Ã¢â€ â€™";' => 'content: "→";',
        'content: "Â·";' => 'content: "·";',
        'content: "Â·" !important;' => 'content: "·" !important;',
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
