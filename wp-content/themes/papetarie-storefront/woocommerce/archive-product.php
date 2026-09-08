<?php

defined('ABSPATH') || exit;

$queried_object = get_queried_object();
$is_product_category = is_tax('product_cat') && $queried_object instanceof WP_Term;
$current_term = $is_product_category ? $queried_object : null;
$term_id = $current_term ? (int) $current_term->term_id : 0;
$term_slug = $current_term ? (string) $current_term->slug : '';
$archive_title = $current_term ? $current_term->name : (function_exists('woocommerce_page_title') ? woocommerce_page_title(false) : __('Produse', 'papetarie-storefront'));
$archive_description = $current_term ? wp_strip_all_tags((string) term_description($term_id, 'product_cat')) : '';
$query = $GLOBALS['wp_query'] ?? null;
$product_count = $query instanceof WP_Query ? (int) $query->found_posts : 0;
$is_empty_archive = $product_count === 0;
// WooCommerce ruteaza si rezultatele de cautare de produse prin acest
// template (is_post_type_archive('product') e adevarat si pentru
// /?s=...&post_type=product), nu doar arhivele reale de categorie - de-aia
// starile goale de mai jos trebuie sa distinga explicit intre "cautare fara
// rezultate" si "categorie fara produse", altfel mesajul de categorie
// ("in aceasta categorie...") apare gresit pe o cautare. Semnalat de user
// 2026-09-01.
$is_search_archive = is_search();
$archive_action_url = $current_term ? get_term_link($current_term, 'product_cat') : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/'));

if (is_wp_error($archive_action_url) || !$archive_action_url) {
    $archive_action_url = home_url('/');
}

$current_stock_status = isset($_GET['stock_status']) ? sanitize_key(wp_unslash($_GET['stock_status'])) : 'all';
$stock_status_options = function_exists('papetarie_storefront_stock_status_options') ? papetarie_storefront_stock_status_options() : [];
$current_orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
$sort_options = [
    'price' => __('Preț crescător', 'papetarie-storefront'),
    'price-desc' => __('Preț descrescător', 'papetarie-storefront'),
];
$active_orderby = isset($sort_options[$current_orderby]) ? $current_orderby : 'price';
$price_ranges = function_exists('papetarie_storefront_price_ranges') ? papetarie_storefront_price_ranges($current_term) : [];
$selected_price_ranges = function_exists('papetarie_storefront_get_selected_price_range_keys') ? papetarie_storefront_get_selected_price_range_keys($current_term) : [];
$price_range_counts = function_exists('papetarie_storefront_get_price_range_counts') ? papetarie_storefront_get_price_range_counts($current_term) : [];
$has_meaningful_price_filter = count(array_filter($price_range_counts, static fn (int $count): bool => $count > 0)) >= 2;
$stock_status_counts = function_exists('papetarie_storefront_get_stock_status_counts') ? papetarie_storefront_get_stock_status_counts($current_term) : [];
$selected_subcategories = isset($_GET['product_cat_child']) ? array_map('sanitize_key', (array) wp_unslash($_GET['product_cat_child'])) : [];
$attribute_filter_groups = function_exists('papetarie_storefront_get_category_attribute_filters') ? papetarie_storefront_get_category_attribute_filters($current_term) : [];
$selected_attributes = function_exists('papetarie_storefront_get_selected_attribute_terms') ? papetarie_storefront_get_selected_attribute_terms() : [];

$has_active_filters = !empty($selected_subcategories)
    || $current_stock_status !== 'all'
    || !empty($selected_price_ranges)
    || !empty($selected_attributes);

$subcategory_terms = [];

if ($term_id > 0) {
    $direct_children = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => $term_id,
    ]);

    if (!is_wp_error($direct_children)) {
        foreach (papetarie_storefront_sort_terms($direct_children) as $child_term) {
            $subcategory_terms[] = [
                'term_id' => $child_term->term_id,
                'slug' => $child_term->slug,
                'name' => $child_term->name,
                'count' => (int) $child_term->count,
            ];
        }
    }
}

$active_filter_chips = [];

foreach ($selected_subcategories as $selected_slug) {
    foreach ($subcategory_terms as $subcategory_term) {
        if ($subcategory_term['slug'] === $selected_slug) {
            $active_filter_chips[] = [
                'label' => $subcategory_term['name'],
                'url' => papetarie_storefront_filter_removal_url($archive_action_url, ['product_cat_child' => $selected_slug]),
            ];
            break;
        }
    }
}

if ($current_stock_status !== 'all') {
    $active_filter_chips[] = [
        'label' => $stock_status_options[$current_stock_status] ?? $current_stock_status,
        'url' => papetarie_storefront_filter_removal_url($archive_action_url, ['stock_status' => null]),
    ];
}

foreach ($selected_price_ranges as $selected_range_key) {
    foreach ($price_ranges as $range) {
        if ($range['key'] === $selected_range_key) {
            $active_filter_chips[] = [
                'label' => $range['label'],
                'url' => papetarie_storefront_filter_removal_url($archive_action_url, ['price_range' => $selected_range_key]),
            ];
            break;
        }
    }
}

foreach ($selected_attributes as $selected_attr_slug) {
    foreach ($attribute_filter_groups as $attr_values) {
        foreach ($attr_values as $attr_value) {
            if ($attr_value['slug'] === $selected_attr_slug) {
                $active_filter_chips[] = [
                    'label' => $attr_value['name'],
                    'url' => papetarie_storefront_filter_removal_url($archive_action_url, ['attr' => $selected_attr_slug]),
                ];
                break 2;
            }
        }
    }
}

get_header();
?>
<main id="primary" class="site-main pap-archive-page">
  <div class="pap-shell pap-page-breadcrumbs pap-archive-breadcrumbs">
    <?php
    if (function_exists('woocommerce_breadcrumb')) {
        woocommerce_breadcrumb([
            'delimiter' => '<span class="pap-breadcrumb-delimiter" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg></span>',
            'wrap_before' => '<nav class="woocommerce-breadcrumb pap-breadcrumbs-nav">',
            'wrap_after' => '</nav>',
            'home' => __('Acasă', 'papetarie-storefront'),
        ]);
    }
    ?>
  </div>

  <section class="pap-shell pap-archive-titlebar">
    <div class="pap-archive-titlebar-inner">
      <div class="pap-archive-titlebar-head">
        <div class="pap-archive-titlebar-copy">
          <h1><?php echo esc_html($archive_title); ?></h1>
          <p><?php echo esc_html(sprintf(_n('%d produs', '%d produse', $product_count, 'papetarie-storefront'), $product_count)); ?></p>
        </div>
      </div>

      <?php if (!$is_empty_archive) : ?>
        <div class="pap-archive-titlebar-controls">
          <button type="button" class="pap-archive-filter-toggle" data-archive-filter-toggle aria-expanded="false" aria-controls="pap-archive-sidebar">
            <span class="pap-archive-filter-toggle-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('filter'); ?></span>
            <?php esc_html_e('Filtrează', 'papetarie-storefront'); ?>
          </button>

          <div class="pap-archive-toolbar-ordering" data-sort-dropdown>
            <button type="button" class="pap-archive-sort-trigger" data-sort-trigger aria-haspopup="true" aria-expanded="false">
              <span class="pap-archive-sort-static-label"><?php esc_html_e('Sortează:', 'papetarie-storefront'); ?></span>
              <span class="pap-archive-sort-value"><?php echo esc_html($sort_options[$active_orderby]); ?></span>
              <span class="pap-archive-sort-chevron" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron-down'); ?></span>
            </button>
            <div class="pap-archive-sort-menu" data-sort-menu hidden>
              <?php foreach ($sort_options as $sort_key => $sort_label) : ?>
                <a
                  href="<?php echo esc_url(papetarie_storefront_filter_sort_url($archive_action_url, $sort_key)); ?>"
                  class="pap-archive-sort-option<?php echo $sort_key === $active_orderby ? ' is-active' : ''; ?>"
                >
                  <?php echo esc_html($sort_label); ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!$is_empty_archive && $active_filter_chips) : ?>
    <section class="pap-shell pap-active-filters-bar-wrap">
      <div class="pap-active-filters-bar">
        <span class="pap-active-filters-label"><?php esc_html_e('Filtre active', 'papetarie-storefront'); ?></span>
        <span class="pap-active-filters-badge"><?php echo esc_html((string) count($active_filter_chips)); ?></span>
        <span class="pap-active-filters-divider" aria-hidden="true"></span>
        <?php foreach ($active_filter_chips as $chip_index => $chip) : ?>
          <a
            href="<?php echo esc_url($chip['url']); ?>"
            class="pap-filter-chip<?php echo $chip_index >= 5 ? ' pap-filter-chip--overflow' : ''; ?>"
            <?php echo $chip_index >= 5 ? 'hidden' : ''; ?>
          >
            <?php echo esc_html($chip['label']); ?>
            <span class="pap-filter-chip-remove" aria-hidden="true">&times;</span>
          </a>
        <?php endforeach; ?>
        <?php if (count($active_filter_chips) > 5) : ?>
          <button type="button" class="pap-active-filters-more" data-filters-more>
            <?php echo esc_html(sprintf(
                /* translators: %d: number of hidden active filter chips */
                __('+%d mai multe', 'papetarie-storefront'),
                count($active_filter_chips) - 5
            )); ?>
          </button>
        <?php endif; ?>
        <span class="pap-active-filters-spacer"></span>
        <a href="<?php echo esc_url($archive_action_url); ?>" class="pap-active-filters-clear">
          <span aria-hidden="true">&times;</span>
          <?php esc_html_e('Șterge toate', 'papetarie-storefront'); ?>
        </a>
      </div>
    </section>
  <?php endif; ?>

  <section class="pap-shell pap-archive-layout<?php echo $is_empty_archive ? ' pap-archive-layout--empty' : ''; ?>">
    <?php if (!$is_empty_archive || !$is_search_archive) : ?>
    <aside class="pap-archive-sidebar" id="pap-archive-sidebar">
      <?php if (!$is_empty_archive) : ?>
        <?php if ($has_active_filters) : ?>
          <a href="<?php echo esc_url($archive_action_url); ?>" class="pap-sidebar-clear-all">
            <span><?php esc_html_e('Șterge toate filtrele', 'papetarie-storefront'); ?></span>
            <span class="pap-sidebar-clear-all-icon" aria-hidden="true">&times;</span>
          </a>
        <?php endif; ?>
        <form class="pap-archive-filter-form" method="get" action="<?php echo esc_url($archive_action_url); ?>">
          <?php if ($current_orderby !== '') : ?>
            <input type="hidden" name="orderby" value="<?php echo esc_attr($current_orderby); ?>">
          <?php endif; ?>
          <input type="hidden" name="paged" value="1">

          <?php if ($subcategory_terms) : ?>
            <div class="pap-filter-card">
              <div class="pap-archive-filter-section-title">
                <span><?php esc_html_e('Subcategorie', 'papetarie-storefront'); ?></span>
              </div>
              <div class="pap-archive-filter-body pap-archive-filter-checklist">
                <?php foreach ($subcategory_terms as $subcategory_term) : ?>
                  <?php $is_checked = in_array($subcategory_term['slug'], $selected_subcategories, true); ?>
                  <label class="pap-archive-check-option">
                    <input type="checkbox" class="pap-checkbox-input" name="product_cat_child[]" value="<?php echo esc_attr($subcategory_term['slug']); ?>" <?php checked($is_checked); ?>>
                    <span class="pap-archive-check-label"><?php echo esc_html($subcategory_term['name']); ?></span>
                    <span class="pap-archive-check-count">(<?php echo esc_html((string) $subcategory_term['count']); ?>)</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($has_meaningful_price_filter) : ?>
            <div class="pap-filter-card">
              <div class="pap-archive-filter-section-title">
                <span><?php esc_html_e('Preț', 'papetarie-storefront'); ?></span>
              </div>
              <div class="pap-archive-filter-body pap-archive-filter-checklist">
                <?php foreach ($price_ranges as $range) : ?>
                  <?php
                    $is_checked = in_array($range['key'], $selected_price_ranges, true);
                    // O optiune fara niciun produs (posibila la intervale generate
                    // algoritmic, nu neaparat pline toate) n-are ce sa filtreze -
                    // ascundem randul, nu doar bageta cu numarul. Pastram optiunea
                    // deja selectata vizibila chiar daca numaratoarea curenta e 0
                    // (ex. dupa combinarea cu alt filtru), ca sa nu dispara de sub
                    // click-ul utilizatorului fara explicatie.
                    if (empty($price_range_counts[$range['key']]) && !$is_checked) {
                        continue;
                    }
                  ?>
                  <label class="pap-archive-check-option">
                    <input type="checkbox" class="pap-checkbox-input" name="price_range[]" value="<?php echo esc_attr($range['key']); ?>" <?php checked($is_checked); ?>>
                    <span class="pap-archive-check-label"><?php echo esc_html($range['label']); ?></span>
                    <?php if (!empty($price_range_counts[$range['key']])) : ?>
                      <span class="pap-archive-check-count">(<?php echo esc_html((string) $price_range_counts[$range['key']]); ?>)</span>
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="pap-filter-card">
            <div class="pap-archive-filter-section-title">
              <span><?php esc_html_e('Disponibilitate', 'papetarie-storefront'); ?></span>
            </div>
            <div class="pap-archive-filter-body pap-archive-filter-checklist pap-archive-filter-checklist--radio">
              <label class="pap-archive-radio-option">
                <input type="radio" class="pap-radio-input" name="stock_status" value="all" <?php checked($current_stock_status, 'all'); ?>>
                <span class="pap-archive-check-label"><?php esc_html_e('Toate produsele', 'papetarie-storefront'); ?></span>
              </label>
              <?php foreach ($stock_status_options as $stock_status_key => $stock_status_label) : ?>
                <label class="pap-archive-radio-option">
                  <input type="radio" class="pap-radio-input" name="stock_status" value="<?php echo esc_attr($stock_status_key); ?>" <?php checked($current_stock_status, $stock_status_key); ?>>
                  <span class="pap-archive-check-label"><?php echo esc_html($stock_status_label); ?></span>
                  <span class="pap-archive-check-count"><?php echo esc_html((string) ($stock_status_counts[$stock_status_key] ?? 0)); ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <?php foreach ($attribute_filter_groups as $attr_group_name => $attr_values) : ?>
            <div class="pap-filter-card">
              <div class="pap-archive-filter-section-title">
                <span><?php echo esc_html($attr_group_name); ?></span>
              </div>
              <div class="pap-archive-filter-body pap-archive-filter-checklist">
                <?php foreach ($attr_values as $attr_value) : ?>
                  <?php $is_checked = in_array($attr_value['slug'], $selected_attributes, true); ?>
                  <label class="pap-archive-check-option">
                    <input type="checkbox" class="pap-checkbox-input" name="attr[]" value="<?php echo esc_attr($attr_value['slug']); ?>" <?php checked($is_checked); ?>>
                    <span class="pap-archive-check-label"><?php echo esc_html($attr_value['name']); ?></span>
                    <span class="pap-archive-check-count">(<?php echo esc_html((string) $attr_value['count']); ?>)</span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </form>

        <div class="pap-sidebar-promo">
          <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/')); ?>">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/sidebar-oferta-zilei.png'); ?>" alt="<?php esc_attr_e('Oferta zilei — Papetărie & birou până la -20% extra', 'papetarie-storefront'); ?>" loading="lazy">
          </a>
        </div>
      <?php else : ?>
        <div class="pap-archive-sidebar-box">
          <div class="pap-archive-sidebar-empty">
            <div class="pap-archive-sidebar-empty-panel">
              <span class="pap-archive-sidebar-empty-icon" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
                  <circle cx="24" cy="24" r="24" fill="#EEF5FF"/>
                  <path d="M13 16.5C13 15.67 13.67 15 14.5 15H33.5C34.33 15 35 15.67 35 16.5C35 16.86 34.87 17.2 34.64 17.47L27 26.5V34.2C27 34.72 26.7 35.2 26.23 35.43L22.23 37.43C21.57 37.76 20.8 37.28 20.8 36.55V26.5L13.36 17.47C13.13 17.2 13 16.86 13 16.5Z" stroke="#0B3A78" stroke-width="2" stroke-linejoin="round"/>
                </svg>
              </span>
              <h2><?php esc_html_e('Produse în pregătire', 'papetarie-storefront'); ?></h2>
              <p><?php esc_html_e('În această categorie urmează să adăugăm produse potrivite. Revino curând pentru selecția completă.', 'papetarie-storefront'); ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </aside>
    <?php endif; ?>

    <div class="pap-archive-content" id="pap-archive-products">
      <?php if (woocommerce_product_loop()) : ?>
        <div class="pap-archive-grid" data-archive-grid>
          <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <?php
            global $product;

            if (!$product instanceof WC_Product) {
                $product = wc_get_product(get_the_ID());
            }

            if (!$product instanceof WC_Product) {
                continue;
            }

            papetarie_storefront_render_product_card($product);
            ?>
          <?php endwhile; ?>
        </div>

        <?php woocommerce_pagination(); ?>

      <?php else : ?>
        <div class="pap-archive-empty">
          <span class="pap-archive-empty-icon" aria-hidden="true">
            <svg width="96" height="96" viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
              <circle cx="48" cy="48" r="48" fill="#EDF4FB"/>
              <path d="M25 39.5L48 27L71 39.5L48 52L25 39.5Z" stroke="#0B3A75" stroke-width="3" stroke-linejoin="round"/>
              <path d="M25 39.5V60.5L48 73L71 60.5V39.5" stroke="#0B3A75" stroke-width="3" stroke-linejoin="round"/>
              <path d="M48 52V73" stroke="#0B3A75" stroke-width="3" stroke-linecap="round"/>
              <path d="M35.5 33L58.5 45.5" stroke="#0B3A75" stroke-width="3" stroke-linecap="round"/>
              <path d="M60.5 33L37.5 45.5" stroke="#0B3A75" stroke-width="3" stroke-linecap="round"/>
              <path d="M48 16V21" stroke="#2D7DD2" stroke-width="3" stroke-linecap="round"/>
              <path d="M36 20L38.5 24.5" stroke="#2D7DD2" stroke-width="3" stroke-linecap="round"/>
              <path d="M60 20L57.5 24.5" stroke="#2D7DD2" stroke-width="3" stroke-linecap="round"/>
            </svg>
          </span>
          <?php if ($is_search_archive) : ?>
            <h2>
              <?php
              echo esc_html(sprintf(
                  /* translators: %s: search term */
                  __('Nu am găsit rezultate pentru „%s”', 'papetarie-storefront'),
                  get_search_query(false)
              ));
              ?>
            </h2>
            <p><?php esc_html_e('Încearcă alți termeni de căutare sau răsfoiește categoriile din meniu.', 'papetarie-storefront'); ?></p>
          <?php elseif ($current_term) : ?>
            <h2><?php esc_html_e('Nu există produse în această categorie încă', 'papetarie-storefront'); ?></h2>
            <p><?php esc_html_e('În această categorie urmează să adăugăm produse potrivite. Revino curând pentru selecția completă.', 'papetarie-storefront'); ?></p>
          <?php else : ?>
            <h2><?php esc_html_e('Nu există produse disponibile momentan', 'papetarie-storefront'); ?></h2>
            <p><?php esc_html_e('Revino curând, actualizăm constant catalogul.', 'papetarie-storefront'); ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<script>
  (function () {
    var filterToggle = document.querySelector('[data-archive-filter-toggle]');
    var sidebar = document.getElementById('pap-archive-sidebar');

    if (filterToggle && sidebar) {
      filterToggle.addEventListener('click', function () {
        var isOpen = sidebar.classList.toggle('is-open');
        filterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    }

    var sortDropdown = document.querySelector('[data-sort-dropdown]');

    if (sortDropdown) {
      var sortTrigger = sortDropdown.querySelector('[data-sort-trigger]');
      var sortMenu = sortDropdown.querySelector('[data-sort-menu]');

      function closeSortMenu() {
        sortMenu.hidden = true;
        sortTrigger.setAttribute('aria-expanded', 'false');
        sortDropdown.removeAttribute('data-open');
      }

      function openSortMenu() {
        sortMenu.hidden = false;
        sortTrigger.setAttribute('aria-expanded', 'true');
        sortDropdown.setAttribute('data-open', '');
      }

      sortTrigger.addEventListener('click', function (event) {
        event.stopPropagation();

        if (sortMenu.hidden) {
          openSortMenu();
        } else {
          closeSortMenu();
        }
      });

      document.addEventListener('click', function (event) {
        if (!sortDropdown.contains(event.target)) {
          closeSortMenu();
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          closeSortMenu();
        }
      });
    }

    var filterForm = document.querySelector('.pap-archive-filter-form');

    function submitFilter() {
      if (!filterForm) {
        return;
      }
      if (filterForm.requestSubmit) {
        filterForm.requestSubmit();
      } else {
        filterForm.submit();
      }
    }

    if (filterForm) {
      var autoSubmitInputs = Array.prototype.slice.call(
        filterForm.querySelectorAll('input[type="checkbox"], input[type="radio"]')
      );

      autoSubmitInputs.forEach(function (input) {
        input.addEventListener('change', submitFilter);
      });
    }

    var moreFiltersButton = document.querySelector('[data-filters-more]');

    if (moreFiltersButton) {
      moreFiltersButton.addEventListener('click', function () {
        var hiddenChips = Array.prototype.slice.call(
          document.querySelectorAll('.pap-filter-chip--overflow[hidden]')
        );
        hiddenChips.forEach(function (chip) {
          chip.removeAttribute('hidden');
        });
        moreFiltersButton.remove();
      });
    }

  })();
</script>
<?php
get_footer();
