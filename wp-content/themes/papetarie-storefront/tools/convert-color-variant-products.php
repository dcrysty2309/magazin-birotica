<?php

declare(strict_types=1);

/**
 * Transforma in produse variabile (cu selector de culoare) produsele Aperta
 * care au poze multiple pe culori diferite in coloana "Imagine produs" (nume
 * de fisier ex. "-rosu.jpg", "-albastru.jpg"), dar pentru care Aperta NU
 * trimite coloana "Variant" completata in feed - deci sincronizarea obisnuita
 * (papetarie_storefront_aperta_image_urls, includes/aperta-sync.php:1083)
 * le trateaza corect ca "prima poza = principala, restul = galerie simpla",
 * nu ca variante. Nu e un bug de sincronizare - Aperta pur si simplu nu
 * expune aceste culori structurat, doar in numele fisierelor de poza.
 *
 * Verificat separat (11-12 august 2026): nu se suprapune cu
 * consolidate-color-variants.php (acela unifica produse SIMPLE deja separate
 * cu nume diferit de culoare) nici cu migrate-legacy-simple-to-variation.php
 * (acela migreaza familii cu coloana "Variant" completata in feed, dar
 * sarite de sincronizarea normala). Cazul de aici - un singur rand de feed,
 * cu mai multe poze, fara "Variant" - nu era acoperit de niciun tool existent.
 *
 * Lista celor 49 de produse (cod_produs Aperta, pret, culoare->URL poza) a
 * fost generata manual, prin analiza feed.csv - vezi constanta
 * PAP_COLOR_VARIANTS_DATA mai jos.
 *
 * Implicit ruleaza in mod DRY-RUN (doar raport, nicio modificare). Adauga
 * --apply ca sa aplice efectiv conversia. Suporta --limit=N (implicit: toate).
 *
 * Scriptul e idempotent - daca il rulezi de mai multe ori cu --apply,
 * produsele deja convertite (tip "variable") sunt sarite automat.
 *
 * Run with:
 * docker compose exec -T wordpress php /var/www/html/wp-content/themes/papetarie-storefront/tools/convert-color-variant-products.php [--apply] [--limit=1]
 */

require_once dirname(__DIR__, 4) . '/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

global $wpdb;

const PAP_COLOR_VARIANTS_DATA = [
    [
        'cod_produs' => 'MIC223',
        'price' => '12.72',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/trusa-geometrie-s-cool-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/trusa-geometrie-s-cool-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/trusa-geometrie-s-cool-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/trusa-geometrie-s-cool-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'RAC577',
        'price' => '7.2',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/racleta-podea-55-cm-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/racleta-podea-55-cm-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/racleta-podea-55-cm-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/racleta-podea-55-cm-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '2687',
        'price' => '1.66',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-18-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-18-albastru.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-18-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '2688',
        'price' => '0.72',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-9-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-9-albastru.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-9-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '2686',
        'price' => '4.03',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-sina-metalica-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-sina-metalica-albastru.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/cutter-sina-metalica-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'AGE358',
        'price' => '67.34',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-72-file-coperta-tare-clairefontaine-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-72-file-coperta-tare-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-72-file-coperta-tare-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-72-file-coperta-tare-clairefontaine-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'AGE350',
        'price' => '47.28',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-clairefontaine-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-nedatata-a4-clairefontaine-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'ALT536',
        'price' => '48.43',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-electrica-dubla-albastru.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-electrica-dubla-portocaliu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-electrica-dubla-verde.jpg'
            ],
            [
                'color' => 'Roz',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-electrica-dubla-roz.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CRE043',
        'price' => '3.81',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-3-touch-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-3-touch-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-3-touch-albastru.jpg'
            ],
            [
                'color' => 'Gri',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-3-touch-gri.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CRE039',
        'price' => '1.8',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-city-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-city-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-city-albastru.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CRE040',
        'price' => '2.4',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-dubla-duo-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-dubla-duo-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-dubla-duo-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CRE038',
        'price' => '2.88',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-dubla-rezervor-wave-albastru.jpg'
            ],
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-dubla-rezervor-wave-negru.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CRE036',
        'price' => '2.26',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-smart-sharp-negru.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-smart-sharp-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-rezervor-erich-krause-smart-sharp-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'FAB034',
        'price' => '5.35',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-simpla-container-mini-grip-2001-faber-castell-albastru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-simpla-container-mini-grip-2001-faber-castell-rosu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'FAB028',
        'price' => '10.94',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-tripla-container-grip-2001-faber-castell-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/ascutitoare-plastic-tripla-container-grip-2001-faber-castell-albastru.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI114',
        'price' => '6.4',
        'variants' => [
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/bloc-desen-a4-plus-8-file-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/bloc-desen-a4-plus-8-file-clairefontaine-portocaliu.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/bloc-desen-a4-plus-8-file-clairefontaine-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'RAC220',
        'price' => '26.08',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/burete-ergonomic-confort-scotch-brite-rosu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/burete-ergonomic-confort-scotch-brite-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'AGE023',
        'price' => '20.12',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-scolara-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-scolara-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-scolara-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/agenda-scolara-clairefontaine-bleu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI054',
        'price' => '32.32',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blocnotes-clairefontaine-pupitre-a4-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blocnotes-clairefontaine-pupitre-a4-verde.jpg'
            ],
            [
                'color' => 'Mov',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blocnotes-clairefontaine-pupitre-a4-mov.jpg'
            ],
            [
                'color' => 'Gri',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blocnotes-clairefontaine-pupitre-a4-gri.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI118',
        'price' => '9.06',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-clairefontaine-portocaliu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI187',
        'price' => '39.09',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spirala-metric-clairefontaine-112-file-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spirala-metric-clairefontaine-112-file-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spirala-metric-clairefontaine-112-file-verde.jpg'
            ],
            [
                'color' => 'Mov',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spirala-metric-clairefontaine-112-file-mov.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI156',
        'price' => '21.38',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/notebook-17-22-cm-spira-4-4-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/notebook-17-22-cm-spira-4-4-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/notebook-17-22-cm-spira-4-4-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/notebook-17-22-cm-spira-4-4-clairefontaine-portocaliu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI151',
        'price' => '6.87',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-24-dictando-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-24-dictando-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-24-dictando-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-24-dictando-clairefontaine-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI152',
        'price' => '8.41',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-40-liniatura-franceza-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-40-liniatura-franceza-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-40-liniatura-franceza-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-40-liniatura-franceza-clairefontaine-galben.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-40-liniatura-franceza-clairefontaine-bleu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI154',
        'price' => '8.3',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-48-liniatura-franceza-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-48-liniatura-franceza-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-48-liniatura-franceza-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-48-liniatura-franceza-clairefontaine-galben.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-48-liniatura-franceza-clairefontaine-bleu.jpg'
            ],
            [
                'color' => 'Roz',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-48-liniatura-franceza-clairefontaine-roz.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI136',
        'price' => '13.67',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-clairefontaine-portocaliu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI111',
        'price' => '21.24',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-a4-capsat-48-file-velin-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-a4-capsat-48-file-velin-clairefontaine-rosu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI113',
        'price' => '17',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-24-32-capsat-48-metric-calligraphe-8000-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-24-32-capsat-48-metric-calligraphe-8000-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-24-32-capsat-48-metric-calligraphe-8000-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-24-32-capsat-48-metric-calligraphe-8000-clairefontaine-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI052',
        'price' => '38.08',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-clairefontaine-studium-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-clairefontaine-studium-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-clairefontaine-studium-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-clairefontaine-studium-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI182',
        'price' => '26.85',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-96-file-colectia-1951-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-96-file-colectia-1951-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-96-file-colectia-1951-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Roz',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-96-file-colectia-1951-clairefontaine-roz.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI135',
        'price' => '15.37',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-a5-spira-50-liniatura-franceza-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-a5-spira-50-liniatura-franceza-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-a5-spira-50-liniatura-franceza-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-a5-spira-50-liniatura-franceza-clairefontaine-bleu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI166',
        'price' => '2.36',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-16-liniatura-franceza-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-16-liniatura-franceza-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-16-liniatura-franceza-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-capsat-16-liniatura-franceza-clairefontaine-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI167',
        'price' => '13.98',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-a6-clairefontaine-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-a6-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-a6-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-spira-a6-clairefontaine-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI178',
        'price' => '7.07',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a7-clairefontaine-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a7-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a7-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a7-clairefontaine-portocaliu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI194',
        'price' => '7.62',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-17-22-cm-5-5-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-17-22-cm-5-5-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-17-22-cm-5-5-clairefontaine-galben.jpg'
            ],
            [
                'color' => 'Roz',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-17-22-cm-5-5-clairefontaine-roz.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI246',
        'price' => '9.06',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-48-file-colectia-mimesys-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Alb',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-48-file-colectia-mimesys-clairefontaine-alb.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-48-file-colectia-mimesys-clairefontaine-galben.jpg'
            ],
            [
                'color' => 'Mov',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-48-file-colectia-mimesys-clairefontaine-mov.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-48-file-colectia-mimesys-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-48-file-colectia-mimesys-clairefontaine-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI247',
        'price' => '7.28',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-plus-48-file-colectia-mimesys-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Alb',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-plus-48-file-colectia-mimesys-clairefontaine-alb.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-plus-48-file-colectia-mimesys-clairefontaine-galben.jpg'
            ],
            [
                'color' => 'Mov',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-plus-48-file-colectia-mimesys-clairefontaine-mov.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-plus-48-file-colectia-mimesys-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-capsat-a5-plus-48-file-colectia-mimesys-clairefontaine-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'CAI017',
        'price' => '28.93',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-cusut-coperta-tare-90-file-clairefontaine-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-cusut-coperta-tare-90-file-clairefontaine-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-cusut-coperta-tare-90-file-clairefontaine-verde.jpg'
            ],
            [
                'color' => 'Portocaliu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/caiet-17-22-cusut-coperta-tare-90-file-clairefontaine-portocaliu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'REG002',
        'price' => '14.18',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/repertoar-clairefontaine-metric-albastru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/repertoar-clairefontaine-metric-rosu.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/repertoar-clairefontaine-metric-verde.jpg'
            ],
            [
                'color' => 'Mov',
                'url' => 'https://www.aperta.ro/wp-content/uploads/repertoar-clairefontaine-metric-mov.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '2939',
        'price' => '3.25',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/pic-carioca-schneider-corry-albastru.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/pic-carioca-schneider-corry-galben.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/pic-carioca-schneider-corry-verde.jpg'
            ],
            [
                'color' => 'Roz',
                'url' => 'https://www.aperta.ro/wp-content/uploads/pic-carioca-schneider-corry-roz.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '6175',
        'price' => '14.61',
        'variants' => [
            [
                'color' => 'Maro',
                'url' => 'https://www.aperta.ro/wp-content/uploads/hartie-milimetrica-clairefontaine-maro.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/hartie-milimetrica-clairefontaine-bleu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '5783',
        'price' => '3.62',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/lipici-lichid-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/lipici-lichid-albastru.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/lipici-lichid-bleu.jpg'
            ],
            [
                'color' => 'Galben',
                'url' => 'https://www.aperta.ro/wp-content/uploads/lipici-lichid-galben.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '6387',
        'price' => '3.76',
        'variants' => [
            [
                'color' => 'Alb',
                'url' => 'https://www.aperta.ro/wp-content/uploads/pix-ico-olimpia-antibacterial-hu-alb.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/pix-ico-olimpia-antibacterial-hu-verde.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'ROG092',
        'price' => '7.91',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/roller-carioca-primary-blister-albastru.jpg'
            ],
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/roller-carioca-primary-blister-negru.jpg'
            ],
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/roller-carioca-primary-blister-rosu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => '2346',
        'price' => '22.26',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blister-roller-schneider-breeze-2022-rosu.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blister-roller-schneider-breeze-2022-albastru.jpg'
            ],
            [
                'color' => 'Verde',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blister-roller-schneider-breeze-2022-verde.jpg'
            ],
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/blister-roller-schneider-breeze-2025-negru.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'STI113',
        'price' => '7.48',
        'variants' => [
            [
                'color' => 'Roșu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-carioca-stilo-blister-rosu.jpg'
            ],
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-carioca-stilo-blister-negru.jpg'
            ],
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-carioca-stilo-blister-albastru.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'STI020',
        'price' => '60.08',
        'variants' => [
            [
                'color' => 'Albastru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-base-albastru.jpg'
            ],
            [
                'color' => 'Mov',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-base-mov.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'STI523',
        'price' => '45.43',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-ceod-shiny-2-rezerve-blister-negru.jpg'
            ],
            [
                'color' => 'Roz',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-ceod-shiny-2-rezerve-blister-roz.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-ceod-shiny-2-rezerve-blister-bleu.jpg'
            ]
        ]
    ],
    [
        'cod_produs' => 'STI522',
        'price' => '39.78',
        'variants' => [
            [
                'color' => 'Negru',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-tomo-corry-6-rezerve-blister-set-negru.jpg'
            ],
            [
                'color' => 'Bleu',
                'url' => 'https://www.aperta.ro/wp-content/uploads/stilou-schneider-tomo-corry-6-rezerve-blister-set-bleu.jpg'
            ]
        ]
    ]
];

$apply = false;
$limit = count(PAP_COLOR_VARIANTS_DATA);

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $apply = true;
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int) $m[1];
    }
}

echo $apply ? "Mod APLICARE (conversie reala).\n\n" : "Mod DRY-RUN (niciun produs nu e modificat).\n\n";

$items = array_slice(PAP_COLOR_VARIANTS_DATA, 0, $limit);
echo "Procesez $limit din " . count(PAP_COLOR_VARIANTS_DATA) . " produse.\n\n";

$results = [];

foreach ($items as $item) {
    $codProdus = $item['cod_produs'];
    $price = $item['price'];
    $variants = $item['variants'];

    $productId = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_pap_aperta_cod_produs' AND meta_value=%s LIMIT 1",
        $codProdus
    ));

    if (!$productId) {
        $results[] = "$codProdus => NU S-A GASIT produsul local";
        continue;
    }

    $existingProduct = wc_get_product($productId);
    if (!$existingProduct) {
        $results[] = "$codProdus => produsul $productId nu se poate incarca ca WC_Product";
        continue;
    }
    if ($existingProduct->is_type('variable')) {
        $results[] = "$codProdus => (ID $productId) deja convertit anterior, sarit";
        continue;
    }

    $baseName = $existingProduct->get_name();
    $colorList = implode(', ', array_column($variants, 'color'));

    if (!$apply) {
        $results[] = "$codProdus => (ID $productId) \"$baseName\" — ar deveni variabil cu " . count($variants) . " culori: $colorList";
        continue;
    }

    // Descarca fiecare poza de culoare in biblioteca media
    $attachmentIdByColor = [];
    foreach ($variants as $v) {
        $attId = media_sideload_image($v['url'], $productId, $v['color'] . ' - ' . $baseName, 'id');
        if (is_wp_error($attId)) {
            $results[] = "$codProdus => eroare descarcare poza {$v['color']}: " . $attId->get_error_message();
            continue 2;
        }
        $attachmentIdByColor[$v['color']] = $attId;
    }

    // Marcheaza produsul ca "variable" (are variante)
    wp_set_object_terms($productId, 'variable', 'product_type');

    // Atribut local "Culoare", marcat ca folosit pentru variante
    $colorValues = array_keys($attachmentIdByColor);
    $attribute = new WC_Product_Attribute();
    $attribute->set_id(0);
    $attribute->set_name('Culoare');
    $attribute->set_options($colorValues);
    $attribute->set_position(0);
    $attribute->set_visible(true);
    $attribute->set_variation(true);

    $parent = new WC_Product_Variable($productId);
    $parent->set_attributes([$attribute]);
    $parent->set_image_id(reset($attachmentIdByColor));
    $parentId = $parent->save();

    // Curata variante existente (in caz de re-rulare) si le recreeaza
    $existingVariationIds = get_posts([
        'post_type' => 'product_variation',
        'post_parent' => $parentId,
        'numberposts' => -1,
        'fields' => 'ids',
        'post_status' => 'any',
    ]);
    foreach ($existingVariationIds as $vid) {
        wp_delete_post($vid, true);
    }

    foreach ($attachmentIdByColor as $color => $attId) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parentId);
        $variation->set_attributes(['culoare' => $color]);
        $variation->set_regular_price($price);
        $variation->set_status('publish');
        $variation->set_image_id($attId);
        $variation->set_manage_stock(false);
        $variation->set_stock_status('instock');
        $variation->save();
    }

    WC_Product_Variable::sync($parentId);

    $results[] = "$codProdus => (ID $parentId) OK, " . count($attachmentIdByColor) . " variante: " . implode(', ', $colorValues);
}

foreach ($results as $r) {
    echo $r . "\n";
}

echo "\nGata.\n";
