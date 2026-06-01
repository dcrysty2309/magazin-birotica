<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

function papetarie_storefront_register_category_ordering_page(): void
{
    add_submenu_page(
        'edit.php?post_type=product',
        __('Ordonează categorii', 'papetarie-storefront'),
        __('Ordonează categorii', 'papetarie-storefront'),
        'manage_product_terms',
        'papetarie-category-ordering',
        'papetarie_storefront_render_category_ordering_page'
    );
}
add_action('admin_menu', 'papetarie_storefront_register_category_ordering_page');

function papetarie_storefront_enqueue_category_ordering_assets(string $hook): void
{
    if ($hook !== 'product_page_papetarie-category-ordering') {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');
    add_action('admin_print_footer_scripts', 'papetarie_storefront_print_category_ordering_script', 20);
}
add_action('admin_enqueue_scripts', 'papetarie_storefront_enqueue_category_ordering_assets');

function papetarie_storefront_render_category_ordering_page(): void
{
    if (!current_user_can('manage_product_terms')) {
        wp_die(esc_html__('Nu ai permisiunea necesară pentru această pagină.', 'papetarie-storefront'));
    }

    $categories = papetarie_storefront_get_mega_menu_categories();
    ?>
    <div class="wrap pap-ordering-wrap">
      <h1><?php esc_html_e('Ordonează categoriile din mega menu', 'papetarie-storefront'); ?></h1>
      <p><?php esc_html_e('Aici rearanjezi doar categoriile folosite în „Toate categoriile”. Nu se creează duplicate și nu se schimbă altceva în structură.', 'papetarie-storefront'); ?></p>

      <div class="notice notice-info inline">
        <p><?php esc_html_e('Mută categoriile părinte în lista din stânga. Pentru fiecare categorie părinte, poți reordona separat și subcategoriile din dreapta.', 'papetarie-storefront'); ?></p>
      </div>

      <div id="pap-ordering-message" class="notice inline" hidden><p></p></div>

      <div class="pap-ordering-grid">
        <section class="pap-ordering-panel">
          <div class="pap-ordering-panel-head">
            <h2><?php esc_html_e('Categorii părinte', 'papetarie-storefront'); ?></h2>
            <p><?php esc_html_e('Ordinea de aici controlează coloana din stânga a mega menu-ului.', 'papetarie-storefront'); ?></p>
          </div>
          <ul id="pap-parent-ordering" class="pap-ordering-list pap-ordering-parents">
            <?php foreach ($categories as $category) : ?>
              <li class="pap-ordering-item" data-term-id="<?php echo esc_attr((string) $category['term_id']); ?>" data-slug="<?php echo esc_attr($category['slug']); ?>">
                <span class="pap-ordering-grip" aria-hidden="true">≡</span>
                <div class="pap-ordering-copy">
                  <strong><?php echo esc_html($category['name']); ?></strong>
                  <span><?php echo esc_html(sprintf(_n('%s subcategorie', '%s subcategorii', count($category['children']), 'papetarie-storefront'), count($category['children']))); ?></span>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="pap-ordering-panel">
          <div class="pap-ordering-panel-head">
            <h2><?php esc_html_e('Subcategorii', 'papetarie-storefront'); ?></h2>
            <p><?php esc_html_e('Selectează o categorie părinte din stânga și reordonează aici doar subcategoriile ei.', 'papetarie-storefront'); ?></p>
          </div>

          <div class="pap-ordering-children">
            <?php foreach ($categories as $index => $category) : ?>
              <div class="pap-ordering-child-group<?php echo $index === 0 ? ' is-active' : ''; ?>" data-parent-slug="<?php echo esc_attr($category['slug']); ?>" <?php echo $index === 0 ? '' : 'hidden'; ?>>
                <div class="pap-ordering-child-head">
                  <h3><?php echo esc_html($category['name']); ?></h3>
                  <span><?php esc_html_e('Trage subcategoriile în ordinea dorită', 'papetarie-storefront'); ?></span>
                </div>
                <ul class="pap-ordering-list pap-ordering-children-list" data-parent-id="<?php echo esc_attr((string) $category['term_id']); ?>">
                  <?php foreach ($category['children'] as $child) : ?>
                    <li class="pap-ordering-item" data-term-id="<?php echo esc_attr((string) $child['term_id']); ?>">
                      <span class="pap-ordering-grip" aria-hidden="true">≡</span>
                      <div class="pap-ordering-copy">
                        <strong><?php echo esc_html($child['name']); ?></strong>
                        <span><?php echo esc_html($child['description'] ?: __('Subcategorie WooCommerce', 'papetarie-storefront')); ?></span>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <p class="submit">
        <button id="pap-save-category-ordering" class="button button-primary button-large"><?php esc_html_e('Salvează ordinea', 'papetarie-storefront'); ?></button>
      </p>
    </div>

    <style>
      .pap-ordering-grid {
        display: grid;
        grid-template-columns: 360px minmax(0, 1fr);
        gap: 24px;
        margin-top: 20px;
      }

      .pap-ordering-panel {
        background: #fff;
        border: 1px solid #dcdcde;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
      }

      .pap-ordering-panel-head,
      .pap-ordering-child-head {
        padding: 18px 20px;
        border-bottom: 1px solid #eef1f4;
      }

      .pap-ordering-panel-head h2,
      .pap-ordering-child-head h3 {
        margin: 0 0 6px;
      }

      .pap-ordering-panel-head p,
      .pap-ordering-child-head span {
        margin: 0;
        color: #64748b;
      }

      .pap-ordering-list {
        list-style: none;
        margin: 0;
        padding: 12px;
      }

      .pap-ordering-item {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 0 0 10px;
        padding: 14px 16px;
        border: 1px solid #dbe3ed;
        background: #fff;
        cursor: default;
      }

      .pap-ordering-item:last-child {
        margin-bottom: 0;
      }

      .pap-ordering-parents .pap-ordering-item {
        cursor: pointer;
      }

      .pap-ordering-parents .pap-ordering-item.is-active {
        border-color: #f97316;
        background: #fff7ed;
      }

      .pap-ordering-copy {
        display: grid;
        gap: 4px;
        min-width: 0;
        flex: 1 1 auto;
      }

      .pap-ordering-copy strong {
        color: #16325c;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .pap-ordering-copy span {
        color: #667085;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .pap-ordering-grip {
        color: #98a2b3;
        font-size: 18px;
        line-height: 1;
        flex: 0 0 auto;
        cursor: move;
      }

      .pap-ordering-placeholder {
        height: 58px;
        border: 1px dashed #f97316;
        background: #fff7ed;
        margin-bottom: 10px;
      }

      .pap-ordering-children {
        display: grid;
        gap: 18px;
        padding: 12px;
      }

      .pap-ordering-child-group {
        border: 1px solid #edf2f7;
        background: #fbfcfe;
      }

      .pap-ordering-child-group[hidden] {
        display: none !important;
      }

      @media (max-width: 1100px) {
        .pap-ordering-grid {
          grid-template-columns: 1fr;
        }
      }
    </style>

    <?php
}

function papetarie_storefront_print_category_ordering_script(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || $screen->id !== 'product_page_papetarie-category-ordering') {
        return;
    }
    ?>
    <script>
      jQuery(function ($) {
        var $parentList = $('#pap-parent-ordering');
        var $childLists = $('.pap-ordering-children-list');
        var $parentItems = $parentList.children('.pap-ordering-item');
        var $childGroups = $('.pap-ordering-child-group');
        var $button = $('#pap-save-category-ordering');
        var $message = $('#pap-ordering-message');

        if (!$parentList.length || !$childLists.length || typeof $.fn.sortable !== 'function') {
          if ($message.length) {
            $message.removeClass('notice-success').addClass('notice-error').prop('hidden', false).find('p').text('<?php echo esc_js(__('Drag and drop nu s-a putut inițializa. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront')); ?>');
          }
          return;
        }

        function collectIds($items) {
          return $items.map(function () {
            return $(this).data('term-id');
          }).get();
        }

        function showMessage(type, text) {
          $message.removeClass('notice-success notice-error').addClass(type === 'success' ? 'notice-success' : 'notice-error');
          $message.find('p').text(text);
          $message.prop('hidden', false);
        }

        function setActiveParent(slug) {
          $parentItems.removeClass('is-active').filter('[data-slug="' + slug + '"]').addClass('is-active');
          $childGroups.removeClass('is-active').attr('hidden', true).filter('[data-parent-slug="' + slug + '"]').addClass('is-active').attr('hidden', false);
        }

        $parentList.sortable({
          axis: 'y',
          handle: '.pap-ordering-grip',
          placeholder: 'pap-ordering-placeholder'
        });

        $childLists.sortable({
          axis: 'y',
          handle: '.pap-ordering-grip',
          placeholder: 'pap-ordering-placeholder'
        });

        $parentItems.on('click', function (event) {
          if ($(event.target).closest('.pap-ordering-grip').length) {
            return;
          }

          setActiveParent($(this).data('slug'));
        });

        if ($parentItems.length) {
          setActiveParent($parentItems.first().data('slug'));
        }

        $button.on('click', function (event) {
          event.preventDefault();

          var payload = {
            action: 'pap_save_category_ordering',
            nonce: '<?php echo esc_js(wp_create_nonce('pap-save-category-ordering')); ?>',
            parents: collectIds($parentList.children('.pap-ordering-item')),
            children: {}
          };

          $childLists.each(function () {
            var $list = $(this);
            payload.children[$list.data('parent-id')] = collectIds($list.children('.pap-ordering-item'));
          });

          $button.prop('disabled', true).text('<?php echo esc_js(__('Se salvează...', 'papetarie-storefront')); ?>');
          $message.prop('hidden', true);

          $.post(ajaxurl, payload)
            .done(function (response) {
              if (response && response.success) {
                showMessage('success', response.data.message);
                return;
              }

              showMessage('error', (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('Nu am putut salva ordinea.', 'papetarie-storefront')); ?>');
            })
            .fail(function () {
              showMessage('error', '<?php echo esc_js(__('A apărut o eroare la salvare.', 'papetarie-storefront')); ?>');
            })
            .always(function () {
              $button.prop('disabled', false).text('<?php echo esc_js(__('Salvează ordinea', 'papetarie-storefront')); ?>');
            });
        });
      });
    </script>
    <?php
}

function papetarie_storefront_save_category_ordering(): void
{
    if (!current_user_can('manage_product_terms')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-save-category-ordering', 'nonce');

    $parents = isset($_POST['parents']) && is_array($_POST['parents']) ? array_map('intval', wp_unslash($_POST['parents'])) : [];
    $children = isset($_POST['children']) && is_array($_POST['children']) ? wp_unslash($_POST['children']) : [];

    if ($parents === []) {
        wp_send_json_error(['message' => __('Nu am primit categoriile părinte.', 'papetarie-storefront')], 400);
    }

    foreach (array_values($parents) as $index => $termId) {
        wc_set_term_order($termId, $index, 'product_cat', false);
        clean_term_cache($termId, 'product_cat');
    }

    foreach ($children as $parentId => $termIds) {
        if (!is_array($termIds)) {
            continue;
        }

        foreach (array_values(array_map('intval', $termIds)) as $index => $termId) {
            $term = get_term($termId, 'product_cat');

            if (!($term instanceof WP_Term) || (int) $term->parent !== (int) $parentId) {
                continue;
            }

            wc_set_term_order($termId, $index, 'product_cat', false);
            clean_term_cache($termId, 'product_cat');
        }
    }

    if (class_exists('WC_Cache_Helper')) {
        WC_Cache_Helper::invalidate_cache_group('product_cat');
    }

    wp_send_json_success(['message' => __('Ordinea categoriilor a fost salvată.', 'papetarie-storefront')]);
}
add_action('wp_ajax_pap_save_category_ordering', 'papetarie_storefront_save_category_ordering');
