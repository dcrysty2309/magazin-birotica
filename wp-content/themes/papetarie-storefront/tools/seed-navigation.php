<?php

declare(strict_types=1);

require '/var/www/html/wp-load.php';

if (!taxonomy_exists('product_cat')) {
    throw new RuntimeException('WooCommerce taxonomy product_cat is not available.');
}

function pap_seed_page(string $title, string $slug, string $content = ''): int
{
    $existing = get_page_by_path($slug, OBJECT, 'page');

    if ($existing instanceof WP_Post) {
        wp_update_post(
            [
                'ID' => $existing->ID,
                'post_title' => $title,
                'post_content' => $content ?: $existing->post_content,
                'post_status' => 'publish',
            ]
        );

        return (int) $existing->ID;
    }

    return (int) wp_insert_post(
        [
            'post_title' => $title,
            'post_name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => $content,
        ]
    );
}

function pap_seed_category(string $name, string $slug, int $parentId = 0, string $description = ''): int
{
    $existing = get_term_by('slug', $slug, 'product_cat');

    if ($existing instanceof WP_Term) {
        wp_update_term(
            $existing->term_id,
            'product_cat',
            [
                'name' => $name,
                'parent' => $parentId,
                'description' => $description,
                'slug' => $slug,
            ]
        );

        return (int) $existing->term_id;
    }

    $created = wp_insert_term(
        $name,
        'product_cat',
        [
            'slug' => $slug,
            'parent' => $parentId,
            'description' => $description,
        ]
    );

    if (is_wp_error($created)) {
        throw new RuntimeException('Could not create category ' . $name . ': ' . $created->get_error_message());
    }

    return (int) $created['term_id'];
}

function pap_delete_category_if_empty(string $slug): void
{
    $term = get_term_by('slug', $slug, 'product_cat');

    if (!($term instanceof WP_Term)) {
        return;
    }

    if ((int) $term->count === 0) {
        wp_delete_term($term->term_id, 'product_cat');
    }
}

function pap_seed_menu(string $menuName, string $location, array $items): int
{
    $menu = wp_get_nav_menu_object($menuName);
    $menuId = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($menuName);

    $existingItems = wp_get_nav_menu_items($menuId) ?: [];

    foreach ($existingItems as $item) {
        wp_delete_post((int) $item->ID, true);
    }

    foreach ($items as $index => $item) {
        $args = [
            'menu-item-title' => $item['title'],
            'menu-item-status' => 'publish',
            'menu-item-position' => $index + 1,
        ];

        if (!empty($item['object_id'])) {
            $args['menu-item-object-id'] = (int) $item['object_id'];
            $args['menu-item-object'] = 'page';
            $args['menu-item-type'] = 'post_type';
        } else {
            $args['menu-item-type'] = 'custom';
            $args['menu-item-url'] = $item['url'] ?? home_url('/');
        }

        wp_update_nav_menu_item($menuId, 0, $args);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $menuId;
    set_theme_mod('nav_menu_locations', $locations);

    return $menuId;
}

$pages = [
    'despre-noi' => pap_seed_page('Despre noi', 'despre-noi', '<!-- wp:paragraph --><p>Suntem un magazin local de papetărie și birotică pentru persoane fizice, companii și instituții.</p><!-- /wp:paragraph -->'),
    'produse-promotionale' => pap_seed_page('Produse promoționale', 'produse-promotionale', '<!-- wp:paragraph --><p>Pregătim colecții personalizate pentru companii, evenimente și campanii promoționale.</p><!-- /wp:paragraph -->'),
    'seap' => pap_seed_page('SEAP', 'seap', '<!-- wp:paragraph --><p>Solicită ofertă sau informații pentru achiziții prin SEAP.</p><!-- /wp:paragraph -->'),
    'contact' => pap_seed_page('Contact', 'contact', '<!-- wp:paragraph --><p>Scrie-ne pentru oferte, disponibilitate și comenzi B2B.</p><!-- /wp:paragraph -->'),
    'solicita-oferta' => pap_seed_page('Solicită ofertă', 'solicita-oferta', '<!-- wp:paragraph --><p>Trimite-ne necesarul tău și revenim cu ofertă personalizată.</p><!-- /wp:paragraph -->'),
];

$categoryTree = [
    [
        'name' => 'Instrumente de scris și corectură',
        'slug' => 'instrumente-de-scris-si-corectura',
        'description' => 'Pixuri, creioane, markere și produse de corectură pentru birou și școală.',
        'children' => [
            ['name' => 'Pixuri de unică folosință', 'slug' => 'pixuri-de-unica-folosinta'],
            ['name' => 'Pixuri cu gel', 'slug' => 'pixuri-cu-gel'],
            ['name' => 'Pixuri cu mecanism', 'slug' => 'pixuri-cu-mecanism'],
            ['name' => 'Creioane și mine', 'slug' => 'creioane-si-mine'],
            ['name' => 'Markere și evidențiatoare', 'slug' => 'markere-si-evidentiatoare'],
            ['name' => 'Corectoare', 'slug' => 'corectoare'],
        ],
    ],
    [
        'name' => 'Capsatoare și perforatoare',
        'slug' => 'capsatoare-si-perforatoare',
        'description' => 'Capsatoare, perforatoare și consumabile pentru arhivare și birou.',
        'children' => [
            ['name' => 'Capsatoare de birou', 'slug' => 'capsatoare-de-birou'],
            ['name' => 'Capsatoare profesionale', 'slug' => 'capsatoare-profesionale'],
            ['name' => 'Capsatoare tip clește', 'slug' => 'capsatoare-tip-cleste'],
            ['name' => 'Capsatoare cu braț lung', 'slug' => 'capsatoare-cu-brat-lung'],
            ['name' => 'Perforatoare', 'slug' => 'perforatoare'],
            ['name' => 'Capse și accesorii', 'slug' => 'capse-si-accesorii'],
        ],
    ],
    [
        'name' => 'Accesorii pentru birou',
        'slug' => 'accesorii-pentru-birou',
        'description' => 'Accesorii esențiale de organizare pentru biroul de zi cu zi.',
        'children' => [
            ['name' => 'Agrafe și ace cu gămălie', 'slug' => 'agrafe-si-ace-cu-gamalie'],
            ['name' => 'Pioneze', 'slug' => 'pioneze'],
            ['name' => 'Dispensere pentru agrafe', 'slug' => 'dispensere-pentru-agrafe'],
            ['name' => 'Accesorii mărunte birou', 'slug' => 'accesorii-marunte-birou'],
        ],
    ],
    [
        'name' => 'Arhivare',
        'slug' => 'arhivare',
        'description' => 'Bibliorafturi, dosare și soluții pentru păstrarea documentelor.',
        'children' => [
            ['name' => 'Folii protecție documente', 'slug' => 'folii-protectie-documente'],
            ['name' => 'Bibliorafturi', 'slug' => 'bibliorafturi'],
            ['name' => 'Dosare și mape', 'slug' => 'dosare-si-mape'],
            ['name' => 'Separatoare și index', 'slug' => 'separatoare-si-index'],
        ],
    ],
    [
        'name' => 'Organizare',
        'slug' => 'organizare',
        'description' => 'Soluții de organizare pentru birou, documente și spații de lucru.',
        'children' => [
            ['name' => 'Suporturi pentru cub hârtie', 'slug' => 'suporturi-pentru-cub-hartie'],
            ['name' => 'Organizatoare birou', 'slug' => 'organizatoare-birou'],
            ['name' => 'Seturi birou', 'slug' => 'seturi-birou'],
            ['name' => 'Suporturi documente', 'slug' => 'suporturi-documente'],
            ['name' => 'Copy holder', 'slug' => 'copy-holder'],
        ],
    ],
    [
        'name' => 'Articole din hârtie',
        'slug' => 'articole-din-hartie',
        'description' => 'Agende, caiete, notebook-uri și articole esențiale din hârtie.',
        'children' => [
            ['name' => 'Registre și repertoare', 'slug' => 'registre-si-repertoare'],
            ['name' => 'Agende', 'slug' => 'agende'],
            ['name' => 'Organizere', 'slug' => 'organizere'],
            ['name' => 'Notebook-uri', 'slug' => 'notebook-uri'],
            ['name' => 'Calendare', 'slug' => 'calendare'],
            ['name' => 'Rezerve organizere', 'slug' => 'rezerve-organizere'],
        ],
    ],
    [
        'name' => 'Sisteme de prezentare și afișare',
        'slug' => 'sisteme-de-prezentare-si-afisare',
        'description' => 'Flipchart-uri, whiteboard-uri și accesorii pentru prezentare.',
        'children' => [
            ['name' => 'Flipchart-uri', 'slug' => 'flipchart-uri'],
            ['name' => 'Whiteboard-uri', 'slug' => 'whiteboard-uri'],
            ['name' => 'Whiteboard-uri rotative', 'slug' => 'whiteboard-uri-rotative'],
            ['name' => 'Table din sticlă', 'slug' => 'table-din-sticla'],
            ['name' => 'Plannere magnetice', 'slug' => 'plannere-magnetice'],
            ['name' => 'Accesorii whiteboard', 'slug' => 'accesorii-whiteboard'],
        ],
    ],
    [
        'name' => 'Consumabile și îndosariere',
        'slug' => 'consumabile-si-indosariere',
        'description' => 'Consumabile pentru laminare, îndosariere și finisare documente.',
        'children' => [
            ['name' => 'Folii pentru laminat', 'slug' => 'folii-pentru-laminat'],
            ['name' => 'Folii autolaminante', 'slug' => 'folii-autolaminante'],
            ['name' => 'Coperți pentru îndosariere', 'slug' => 'coperti-pentru-indosariere'],
            ['name' => 'Coperți termice', 'slug' => 'coperti-termice'],
            ['name' => 'Inele plastic', 'slug' => 'inele-plastic'],
            ['name' => 'Inele metal', 'slug' => 'inele-metal'],
        ],
    ],
    [
        'name' => 'Accesorii IT',
        'slug' => 'accesorii-it',
        'description' => 'Media, carcase și accesorii IT pentru arhivare și prezentare.',
        'children' => [
            ['name' => 'CD-uri', 'slug' => 'cd-uri'],
            ['name' => 'DVD-uri', 'slug' => 'dvd-uri'],
            ['name' => 'Carcase CD/DVD', 'slug' => 'carcase-cd-dvd'],
            ['name' => 'Plicuri CD', 'slug' => 'plicuri-cd'],
            ['name' => 'Suporturi CD/DVD', 'slug' => 'suporturi-cd-dvd'],
            ['name' => 'Kituri curățare', 'slug' => 'kituri-curatare'],
        ],
    ],
    [
        'name' => 'Articole școlare',
        'slug' => 'articole-scolare',
        'description' => 'Rechizite și materiale creative pentru elevi și profesori.',
        'children' => [
            ['name' => 'Creioane colorate', 'slug' => 'creioane-colorate'],
            ['name' => 'Creioane cerate', 'slug' => 'creioane-cerate'],
            ['name' => 'Carioci', 'slug' => 'carioci'],
            ['name' => 'Rechizite creative', 'slug' => 'rechizite-creative'],
        ],
    ],
    [
        'name' => 'Echipamente birou',
        'slug' => 'echipamente-birou',
        'description' => 'Laminatoare, distrugătoare și echipamente profesionale pentru birou.',
        'children' => [
            ['name' => 'Laminatoare', 'slug' => 'laminatoare'],
            ['name' => 'Distrugătoare documente', 'slug' => 'distrugatoare-documente'],
            ['name' => 'Ghilotine și trimmere', 'slug' => 'ghilotine-si-trimmere'],
            ['name' => 'Mașini de îndosariat', 'slug' => 'masini-de-indosariat'],
            ['name' => 'Consumabile echipamente', 'slug' => 'consumabile-echipamente'],
        ],
    ],
    [
        'name' => 'Test',
        'slug' => 'test',
        'description' => 'Categorie de lucru pentru produsele aflate în dezvoltare.',
        'children' => [],
    ],
];

$parentOrder = 0;
$childTargetId = 0;

foreach ($categoryTree as $parent) {
    $parentId = pap_seed_category($parent['name'], $parent['slug'], 0, $parent['description']);
    update_term_meta($parentId, 'order', $parentOrder++);

    $childOrder = 0;

    foreach ($parent['children'] as $child) {
        $childId = pap_seed_category($child['name'], $child['slug'], $parentId, $child['description'] ?? '');
        update_term_meta($childId, 'order', $childOrder++);

        if ($child['slug'] === 'notebook-uri') {
            $childTargetId = $childId;
        }
    }
}

pap_delete_category_if_empty('casual');
pap_delete_category_if_empty('travel');

if ($childTargetId > 0) {
    $products = get_posts(
        [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]
    );

    if ($products) {
        wp_set_object_terms((int) $products[0], [$childTargetId], 'product_cat', false);
    }
}

pap_seed_menu(
    'Meniu principal',
    'primary',
    [
        ['title' => 'Despre noi', 'object_id' => $pages['despre-noi']],
        ['title' => 'Produse promoționale', 'object_id' => $pages['produse-promotionale']],
        ['title' => 'SEAP', 'object_id' => $pages['seap']],
    ]
);

pap_seed_menu(
    'Meniu ajutor',
    'utility',
    [
        ['title' => 'Ai nevoie de ajutor?', 'object_id' => $pages['contact']],
    ]
);

$admin = get_user_by('login', 'admin');

if ($admin instanceof WP_User) {
    wp_set_password('Papetarie2026!Admin', $admin->ID);
}

echo 'Seed completed.' . PHP_EOL;
