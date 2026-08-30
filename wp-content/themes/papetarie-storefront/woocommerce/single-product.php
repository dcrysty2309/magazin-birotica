<?php

defined('ABSPATH') || exit;

get_header();
?>
<main id="primary" class="site-main pap-product-page">
  <?php while (have_posts()) : the_post(); ?>
    <?php
    $product = wc_get_product(get_the_ID());

    if (!$product instanceof WC_Product) {
        continue;
    }

    if (post_password_required()) {
        echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        continue;
    }

    // woocommerce_before_single_product doar afiseaza notificarile (adaugat
    // in cos, erori) - implicit needvelite in nicio bordura de latime, ies
    // full-bleed (dincolo de .pap-shell folosit de restul paginii). Le
    // capturam aici (hook-ul trebuie sa ruleze la locul lui in flow-ul
    // WooCommerce), dar le afisam mai jos, DUPA breadcrumbs - inainte de ele
    // arata ca ies din pagina inaintea oricarui context (semnalat live
    // 2026-08-31 de user).
    ob_start();
    do_action('woocommerce_before_single_product');
    $pap_before_single_product_html = trim((string) ob_get_clean());

    $product_id = $product->get_id();

    // Thumbnail-ul parintelui (poza de pe categorie/cardul de produs) e
    // sursa de adevar - swatch-urile se reordoneaza la afisare ca sa
    // inceapa cu culoarea ei (vezi papetarie_storefront_thumbnail_matched_color
    // in color-swatches.php), iar aici gasim aceeasi variatie doar ca sa-i
    // preluam SKU-ul implicit mai jos - poza principala ramane oricum
    // $product->get_image_id() direct, deci mereu identica cu cea de pe
    // categorie (gasit live 2026-08-29: inainte porneam de la "prima
    // culoare din lista de atribute", care putea sa nu corespunda cu
    // thumbnail-ul parintelui).
    $default_color_variation = null;
    if ($product->is_type('variable')) {
        $thumbnail_id = (int) $product->get_image_id();
        if ($thumbnail_id !== 0) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                if ($child && $child->get_status() === 'publish' && (int) $child->get_image_id() === $thumbnail_id) {
                    $default_color_variation = $child;
                    break;
                }
            }
        }
    }

    $main_image_id = $product->get_image_id();
    $gallery_image_ids = $product->get_gallery_image_ids();
    $all_image_ids = array_values(array_filter(array_merge([$main_image_id], $gallery_image_ids)));
    $main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'large') : wc_placeholder_img_src('woocommerce_single');
    $product_name = $product->get_name();
    $sku = $product->get_sku();
    // Produsele variabile nu au SKU propriu (fiecare culoare are al ei) -
    // afisam implicit SKU-ul variatiei gasite mai sus (cea a carei imagine e
    // chiar thumbnail-ul), la fel cum pretul mare implicit e cel al variatiei
    // minime; JS-ul de mai jos il inlocuieste live cu SKU-ul culorii alese
    // (vezi found_variation mai jos).
    if ($sku === '' && $default_color_variation) {
        $sku = $default_color_variation->get_sku();
    } elseif ($sku === '' && $product->is_type('variable')) {
        foreach ($product->get_children() as $child_id) {
            $child_sku = get_post_meta($child_id, '_sku', true);
            if ($child_sku !== '') {
                $sku = $child_sku;
                break;
            }
        }
    }
    $brand_name = '';
    $brand_terms = get_the_terms($product_id, 'product_brand');
    if (!is_wp_error($brand_terms) && !empty($brand_terms)) {
        $brand_name = $brand_terms[0]->name;
    }
    $is_on_sale = $product->is_on_sale();
    $regular_price = (float) $product->get_regular_price();
    $sale_price = $product->is_on_sale() ? (float) $product->get_sale_price() : null;
    $discount_percent = ($is_on_sale && $regular_price > 0 && $sale_price !== null)
        ? (int) round((($regular_price - $sale_price) / $regular_price) * 100)
        : 0;
    $is_in_stock = $product->is_in_stock();
    $is_simple_purchasable = $product->is_type('simple') && $product->is_purchasable();
    // Static reference line under the main price ("Interval preț: min - max")
    // for variable products whose variations actually span a price range -
    // stays fixed regardless of which color is picked, unlike the big price
    // above it (swapped live to the selected variation's own price_html by
    // the inline script further down, via WooCommerce's found_variation/
    // reset_data events - same pattern already used there for the gallery
    // image swap).
    // The big price is always a single number, never WooCommerce's own
    // range HTML - before any color is picked that means "starting from"
    // (the lowest variation price); once one is picked, the inline script
    // further down swaps it to that variation's own price via
    // found_variation, and reset_data brings it back to this same
    // starting-from value (it's what $default_price_html renders here).
    $variation_price_range_html = '';
    $default_price_html = $product->get_price_html();
    if ($product->is_type('variable')) {
        $min_variation_price = $product->get_variation_price('min', true);
        $max_variation_price = $product->get_variation_price('max', true);
        if ($min_variation_price !== '' && $max_variation_price !== '' && $min_variation_price !== $max_variation_price) {
            $variation_price_range_html = wc_format_price_range($min_variation_price, $max_variation_price);
            $default_price_html = wc_price($min_variation_price);
        }
    }
    ?>

    <div class="pap-shell pap-page-breadcrumbs pap-product-breadcrumbs">
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

    <?php
    // woocommerce_output_all_notices() scoate mereu wrapper-ul
    // <div class="woocommerce-notices-wrapper">, chiar si fara nicio
    // notificare inauntru - un simplu "!== ''" nu prindea asta, lasand un
    // <div> gol (0 inaltime, dar tot prezent in DOM) dupa breadcrumbs pe
    // orice pagina fara notificare. Verificam continutul FARA tag-uri, nu
    // doar string-ul brut. Gasit live 2026-08-31, semnalat de user.
    if (trim(wp_strip_all_tags($pap_before_single_product_html)) !== '') :
        ?>
        <div class="pap-shell pap-product-notices">
          <?php echo $pap_before_single_product_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php
    endif;
    ?>

    <div id="product-<?php echo esc_attr((string) $product_id); ?>" <?php wc_product_class('pap-shell pap-product-summary', $product); ?>>
      <div class="pap-product-gallery" data-product-gallery>
        <div class="pap-product-gallery-main" data-gallery-lightbox-open aria-label="<?php esc_attr_e('Deschide galeria foto', 'papetarie-storefront'); ?>">
          <?php if ($is_on_sale && $discount_percent > 0) : ?>
            <span class="pap-product-gallery-badge">−<?php echo esc_html((string) $discount_percent); ?>%</span>
          <?php endif; ?>
          <img src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr($product_name); ?>" data-product-gallery-image>
          <?php if (count($all_image_ids) > 1) : ?>
            <button type="button" class="pap-product-gallery-next" data-product-gallery-next aria-label="<?php esc_attr_e('Imaginea următoare', 'papetarie-storefront'); ?>">
              <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
            </button>
          <?php endif; ?>
        </div>
        <div class="pap-product-gallery-thumbs-row">
          <?php if (count($all_image_ids) > 1) : ?>
            <button type="button" class="pap-product-gallery-thumbs-nav pap-product-gallery-thumbs-nav--prev" data-gallery-thumbs-prev aria-label="<?php esc_attr_e('Thumbnail-uri anterioare', 'papetarie-storefront'); ?>" hidden>
              <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
            </button>
          <?php endif; ?>
          <div class="pap-product-gallery-thumbs" data-gallery-thumbs-track>
            <?php foreach ($all_image_ids as $index => $image_id) : ?>
              <?php $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail'); ?>
              <button
                type="button"
                class="pap-product-gallery-thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                data-product-gallery-thumb
                data-index="<?php echo esc_attr((string) $index); ?>"
                data-full-src="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'large')); ?>"
              >
                <img src="<?php echo esc_url($thumb_url); ?>" alt="" loading="lazy">
              </button>
            <?php endforeach; ?>
          </div>
          <?php if (count($all_image_ids) > 1) : ?>
            <button type="button" class="pap-product-gallery-thumbs-nav pap-product-gallery-thumbs-nav--next" data-gallery-thumbs-next aria-label="<?php esc_attr_e('Thumbnail-uri următoare', 'papetarie-storefront'); ?>">
              <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <div id="pap-gallery-lightbox" class="pap-gallery-lightbox" hidden aria-hidden="true">
        <div class="pap-gallery-lightbox__backdrop" data-gallery-lightbox-close></div>
        <div class="pap-gallery-lightbox__dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Galerie foto produs', 'papetarie-storefront'); ?>">
          <button type="button" class="pap-gallery-lightbox__close" data-gallery-lightbox-close aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>">&times;</button>
          <div class="pap-gallery-lightbox__body">
            <div class="pap-gallery-lightbox__viewer">
              <?php if (count($all_image_ids) > 1) : ?>
                <button type="button" class="pap-gallery-lightbox__nav pap-gallery-lightbox__nav--prev" data-gallery-lightbox-prev aria-label="<?php esc_attr_e('Imaginea anterioară', 'papetarie-storefront'); ?>">
                  <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
                </button>
              <?php endif; ?>
              <div class="pap-gallery-lightbox__stage">
                <img src="" alt="<?php echo esc_attr($product_name); ?>" data-gallery-lightbox-image>
              </div>
              <?php if (count($all_image_ids) > 1) : ?>
                <button type="button" class="pap-gallery-lightbox__nav pap-gallery-lightbox__nav--next" data-gallery-lightbox-next aria-label="<?php esc_attr_e('Imaginea următoare', 'papetarie-storefront'); ?>">
                  <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
                </button>
              <?php endif; ?>
            </div>
            <?php if (count($all_image_ids) > 1) : ?>
              <div class="pap-gallery-lightbox__thumbs-row">
                <button type="button" class="pap-gallery-lightbox__thumbs-nav pap-gallery-lightbox__thumbs-nav--prev" data-lightbox-thumbs-prev aria-label="<?php esc_attr_e('Thumbnail-uri anterioare', 'papetarie-storefront'); ?>" hidden>
                  <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
                </button>
                <div class="pap-gallery-lightbox__thumbs" data-lightbox-thumbs-track>
                  <?php foreach ($all_image_ids as $index => $image_id) : ?>
                    <button
                      type="button"
                      class="pap-gallery-lightbox__thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                      data-lightbox-thumb
                      data-index="<?php echo esc_attr((string) $index); ?>"
                      data-full-src="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'full')); ?>"
                    >
                      <img src="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'thumbnail')); ?>" alt="" loading="lazy">
                    </button>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="pap-gallery-lightbox__thumbs-nav pap-gallery-lightbox__thumbs-nav--next" data-lightbox-thumbs-next aria-label="<?php esc_attr_e('Thumbnail-uri următoare', 'papetarie-storefront'); ?>">
                  <span aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span>
                </button>
              </div>
            <?php endif; ?>
          </div>
          <div class="pap-gallery-lightbox__toolbar">
            <button type="button" class="pap-gallery-lightbox__tool" data-gallery-lightbox-zoom-out aria-label="<?php esc_attr_e('Micșorează', 'papetarie-storefront'); ?>">
              <?php echo papetarie_storefront_icon('zoom-out'); ?>
            </button>
            <button type="button" class="pap-gallery-lightbox__tool" data-gallery-lightbox-zoom-in aria-label="<?php esc_attr_e('Mărește', 'papetarie-storefront'); ?>">
              <?php echo papetarie_storefront_icon('zoom-in'); ?>
            </button>
            <button type="button" class="pap-gallery-lightbox__tool" data-gallery-lightbox-rotate aria-label="<?php esc_attr_e('Rotește', 'papetarie-storefront'); ?>">
              <?php echo papetarie_storefront_icon('rotate'); ?>
            </button>
          </div>
        </div>
      </div>

      <div class="pap-product-right-col">
      <div class="pap-product-info">
        <?php if ($brand_name !== '' || $sku !== '') : ?>
          <div class="pap-product-info-top">
            <?php if ($brand_name !== '') : ?>
              <span class="pap-product-brand"><?php echo esc_html($brand_name); ?></span>
            <?php endif; ?>
            <?php if ($sku !== '') : ?>
              <span class="pap-product-sku"><?php esc_html_e('SKU:', 'papetarie-storefront'); ?> <span data-product-sku><?php echo esc_html($sku); ?></span></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <h1 class="pap-product-title"><?php echo esc_html($product_name); ?></h1>

        <div class="pap-product-price-block">
          <div class="pap-product-price-row">
            <span data-product-price-html><?php echo wp_kses_post($default_price_html); ?></span>
            <?php if ($is_on_sale && $discount_percent > 0) : ?>
              <span class="pap-product-price-discount">−<?php echo esc_html((string) $discount_percent); ?>%</span>
            <?php endif; ?>
          </div>
          <?php if ($variation_price_range_html !== '') : ?>
            <p class="pap-product-price-range">
              <?php esc_html_e('Interval preț:', 'papetarie-storefront'); ?>
              <?php echo wp_kses_post($variation_price_range_html); ?>
            </p>
          <?php endif; ?>
        </div>

        <?php if ($is_simple_purchasable && $is_in_stock) : ?>
          <form class="cart pap-product-actions-row" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype="multipart/form-data">
            <div class="pap-product-qty-stack" data-qty-stack>
              <div class="pap-product-qty-stepper" data-qty-stepper>
                <button type="button" class="pap-product-qty-btn" data-qty-decrease aria-label="<?php esc_attr_e('Scade cantitatea', 'papetarie-storefront'); ?>">
                  <?php echo papetarie_storefront_icon('minus'); ?>
                </button>
                <input
                  type="number"
                  name="quantity"
                  class="qty pap-product-qty-input"
                  value="<?php echo esc_attr((string) $product->get_min_purchase_quantity()); ?>"
                  min="<?php echo esc_attr((string) $product->get_min_purchase_quantity()); ?>"
                  <?php if ($product->get_max_purchase_quantity() > 0) : ?>max="<?php echo esc_attr((string) $product->get_max_purchase_quantity()); ?>"<?php endif; ?>
                  inputmode="numeric"
                  aria-label="<?php esc_attr_e('Cantitate', 'papetarie-storefront'); ?>"
                >
                <button type="button" class="pap-product-qty-btn" data-qty-increase aria-label="<?php esc_attr_e('Crește cantitatea', 'papetarie-storefront'); ?>">
                  <?php echo papetarie_storefront_icon('plus'); ?>
                </button>
              </div>
              <div class="pap-product-qty-tooltip" data-qty-tooltip hidden aria-hidden="true"></div>
            </div>
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr((string) $product_id); ?>" class="pap-product-add-to-cart single_add_to_cart_button">
              <span aria-hidden="true"><?php echo papetarie_storefront_icon('bag'); ?></span>
              <span><?php esc_html_e('Adaugă în coș', 'papetarie-storefront'); ?></span>
            </button>
          </form>
        <?php else : ?>
          <div class="pap-product-actions-row pap-product-actions-row--fallback">
            <?php woocommerce_template_single_add_to_cart(); ?>
          </div>
        <?php endif; ?>

      </div>

        <div class="pap-product-tabs-section">
          <?php woocommerce_output_product_data_tabs(); ?>
        </div>

        <ul class="pap-product-benefits">
          <li>
            <span class="pap-product-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('truck-outline'); ?></span>
            <span><?php esc_html_e('Livrare 24-48h', 'papetarie-storefront'); ?></span>
          </li>
          <li>
            <span class="pap-product-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('package'); ?></span>
            <span><?php esc_html_e('Ridicare din depozit', 'papetarie-storefront'); ?></span>
          </li>
          <li>
            <span class="pap-product-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('undo'); ?></span>
            <span><?php esc_html_e('Retur 30 zile', 'papetarie-storefront'); ?></span>
          </li>
          <li>
            <span class="pap-product-benefit-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('shield'); ?></span>
            <span><?php esc_html_e('Plată securizată', 'papetarie-storefront'); ?></span>
          </li>
        </ul>
      </div>
    </div>

    <div class="pap-shell pap-product-related-section">
      <?php woocommerce_output_related_products(); ?>
    </div>

    <?php do_action('woocommerce_after_single_product'); ?>
  <?php endwhile; ?>
</main>

<script>
  (function () {
    var gallery = document.querySelector('[data-product-gallery]');
    if (!gallery) {
      return;
    }

    var mainImage = gallery.querySelector('[data-product-gallery-image]');
    var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-product-gallery-thumb]'));
    var nextButton = gallery.querySelector('[data-product-gallery-next]');
    var activeIndex = 0;
    var defaultImageSrc = mainImage ? mainImage.src : '';

    // The big price always starts as the product's own range (server-
    // rendered) - swapped to the selected variation's own price_html (which
    // WooCommerce already formats, sale strike-through included) while one
    // is picked, restored verbatim on reset_data. The small "Interval preț"
    // line underneath is server-rendered once and never touched here - it's
    // meant to stay the fixed overall range regardless of selection.
    var priceEl = document.querySelector('[data-product-price-html]');
    var defaultPriceHTML = priceEl ? priceEl.innerHTML : '';
    var skuEl = document.querySelector('[data-product-sku]');
    var defaultSkuText = skuEl ? skuEl.textContent : '';

    function activate(index) {
      var thumb = thumbs[index];
      if (!thumb || !mainImage) {
        return;
      }

      activeIndex = index;
      thumbs.forEach(function (item) {
        item.classList.remove('is-active');
      });
      thumb.classList.add('is-active');
      mainImage.src = thumb.getAttribute('data-full-src');
    }

    var variationsForm = document.querySelector('.variations_form');
    if (variationsForm && window.jQuery) {
      window.jQuery(variationsForm).on('found_variation', function (event, variation) {
        if (priceEl && variation && variation.price_html) {
          priceEl.innerHTML = variation.price_html;
        }

        if (skuEl && variation && variation.sku) {
          skuEl.textContent = variation.sku;
        }

        if (!mainImage || !variation || !variation.image || !variation.image.src) {
          return;
        }

        mainImage.src = variation.image.src;
        thumbs.forEach(function (item) {
          item.classList.remove('is-active');
        });

        var matchingThumb = thumbs.find(function (thumb) {
          return thumb.getAttribute('data-full-src') === variation.image.src
            || thumb.getAttribute('data-full-src') === variation.image.full_src;
        });

        if (matchingThumb) {
          matchingThumb.classList.add('is-active');
          activeIndex = thumbs.indexOf(matchingThumb);
        }
      });

      window.jQuery(variationsForm).on('reset_data', function () {
        if (priceEl) {
          priceEl.innerHTML = defaultPriceHTML;
        }

        if (skuEl) {
          skuEl.textContent = defaultSkuText;
        }

        if (mainImage) {
          mainImage.src = defaultImageSrc;
        }
        activate(0);
      });
    }

    thumbs.forEach(function (thumb, index) {
      thumb.addEventListener('click', function () {
        activate(index);
      });
    });

    if (nextButton && thumbs.length > 1) {
      nextButton.addEventListener('click', function () {
        activate((activeIndex + 1) % thumbs.length);
      });
    }

    function initThumbSlider(track, prevBtn, nextBtn) {
      if (!track) {
        return;
      }

      // Toleranta mare intentionat (jumatate de thumbnail) - track.scrollWidth
      // vs track.clientWidth (folosit inainte) da rezultate usor diferite
      // intre motoare de randare (subpixel/gap in flexbox), lasand sageata
      // "urmatoare" vizibila cu 1-2px in plus desi vizual toate pozele
      // incapeau deja - semnalat live 2026-08-31 (Rhodia, 5 poze). Comparam
      // direct marginea DREAPTA a ultimului thumbnail cu marginea vizibila
      // a track-ului - mai robust decat scrollWidth, si oricum o depasire
      // REALA (nevoie efectiva de scroll) e intotdeauna mult mai mare decat
      // toleranta asta (cel putin latimea unui thumbnail intreg).
      var OVERFLOW_TOLERANCE = 34;

      function hasOverflow() {
        var lastThumb = track.lastElementChild;
        if (!lastThumb) {
          return false;
        }
        var trackRight = track.getBoundingClientRect().right;
        var lastThumbRight = lastThumb.getBoundingClientRect().right;
        return lastThumbRight > trackRight + OVERFLOW_TOLERANCE;
      }

      function refresh() {
        var maxScroll = track.scrollWidth - track.clientWidth;
        var overflowing = hasOverflow();
        if (prevBtn) {
          prevBtn.hidden = track.scrollLeft <= 4;
        }
        if (nextBtn) {
          nextBtn.hidden = !overflowing || track.scrollLeft >= maxScroll - 4;
        }
      }

      function scrollByPage(direction) {
        track.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' });
      }

      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          scrollByPage(-1);
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          scrollByPage(1);
        });
      }

      track.addEventListener('scroll', refresh);
      window.addEventListener('resize', refresh);
      refresh();

      // refresh() ruleaza sincron, la parsarea scriptului - daca layout-ul
      // real (fonturi web incarcate, orice reflow ulterior) se aseaza DUPA
      // acest moment, latimea masurata atunci poate fi usor diferita de
      // cea finala, lasand sageata "urmatoare" vizibila desi thumbnail-
      // urile incap deja toate intr-un singur rand - fara alt trigger
      // (scroll/resize), starea gresita ramanea permanenta. Reverificam
      // dupa incarcarea completa a paginii si dupa ce fonturile web s-au
      // asezat. Gasit live 2026-08-31, semnalat de user (Rhodia, 5 poze
      // care incap toate, dar sageata "urmatoare" tot aparea).
      window.addEventListener('load', refresh);
      if (window.document.fonts && window.document.fonts.ready && typeof window.document.fonts.ready.then === 'function') {
        window.document.fonts.ready.then(refresh);
      }
    }

    initThumbSlider(
      gallery.querySelector('[data-gallery-thumbs-track]'),
      gallery.querySelector('[data-gallery-thumbs-prev]'),
      gallery.querySelector('[data-gallery-thumbs-next]')
    );

    var lightbox = document.getElementById('pap-gallery-lightbox');

    if (lightbox) {
      var lightboxOpenTrigger = gallery.querySelector('[data-gallery-lightbox-open]');
      var lightboxCloseTriggers = lightbox.querySelectorAll('[data-gallery-lightbox-close]');
      var lightboxStageImage = lightbox.querySelector('[data-gallery-lightbox-image]');
      var lightboxThumbs = Array.prototype.slice.call(lightbox.querySelectorAll('[data-lightbox-thumb]'));
      var lightboxPrev = lightbox.querySelector('[data-gallery-lightbox-prev]');
      var lightboxNext = lightbox.querySelector('[data-gallery-lightbox-next]');
      var zoomInBtn = lightbox.querySelector('[data-gallery-lightbox-zoom-in]');
      var zoomOutBtn = lightbox.querySelector('[data-gallery-lightbox-zoom-out]');
      var rotateBtn = lightbox.querySelector('[data-gallery-lightbox-rotate]');
      var lightboxIndex = 0;
      var zoomScale = 1;
      var rotateDeg = 0;
      var lastFocusedTrigger = null;

      function applyTransform() {
        if (lightboxStageImage) {
          lightboxStageImage.style.transform = 'scale(' + zoomScale + ') rotate(' + rotateDeg + 'deg)';
        }
      }

      function showLightboxImage(index) {
        var thumb = lightboxThumbs[index];
        if (!lightboxStageImage) {
          return;
        }

        zoomScale = 1;
        rotateDeg = 0;
        applyTransform();

        if (!thumb) {
          // Produsele cu o singura poza nu au niciun thumb in lightbox (PHP
          // randeaza rândul de thumbs doar cand sunt 2+ poze) - fara acest
          // fallback, imaginea din lightbox ramanea cu src="" din markup-ul
          // initial (poza "sparta" la deschidere - gasit live 2026-08-12).
          lightboxStageImage.src = mainImage ? mainImage.src : '';
          return;
        }

        lightboxIndex = index;
        lightboxThumbs.forEach(function (item) {
          item.classList.remove('is-active');
        });
        thumb.classList.add('is-active');
        lightboxStageImage.src = thumb.getAttribute('data-full-src');
      }

      function closeLightbox() {
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');

        if (window.papModalManager) {
          window.papModalManager.close(lightbox);
        }

        window.setTimeout(function () {
          lightbox.hidden = true;
        }, 180);

        if (lastFocusedTrigger && typeof lastFocusedTrigger.focus === 'function') {
          window.setTimeout(function () {
            lastFocusedTrigger.focus({ preventScroll: true });
          }, 200);
        }
      }

      function openLightbox(index) {
        lastFocusedTrigger = document.activeElement;
        lightbox.hidden = false;
        lightbox.setAttribute('aria-hidden', 'false');
        showLightboxImage(index || 0);

        if (window.papModalManager) {
          window.papModalManager.open(lightbox, closeLightbox, {});
        }

        window.requestAnimationFrame(function () {
          lightbox.classList.add('is-open');
        });
      }

      if (lightboxOpenTrigger) {
        lightboxOpenTrigger.addEventListener('click', function () {
          openLightbox(activeIndex);
        });
      }

      Array.prototype.forEach.call(lightboxCloseTriggers, function (trigger) {
        trigger.addEventListener('click', closeLightbox);
      });

      lightboxThumbs.forEach(function (thumb, index) {
        thumb.addEventListener('click', function () {
          showLightboxImage(index);
        });
      });

      if (lightboxPrev) {
        lightboxPrev.addEventListener('click', function () {
          showLightboxImage((lightboxIndex - 1 + lightboxThumbs.length) % lightboxThumbs.length);
        });
      }

      if (lightboxNext) {
        lightboxNext.addEventListener('click', function () {
          showLightboxImage((lightboxIndex + 1) % lightboxThumbs.length);
        });
      }

      if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function () {
          zoomScale = Math.min(3, zoomScale + 0.25);
          applyTransform();
        });
      }

      if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function () {
          zoomScale = Math.max(1, zoomScale - 0.25);
          applyTransform();
        });
      }

      if (rotateBtn) {
        rotateBtn.addEventListener('click', function () {
          rotateDeg = (rotateDeg + 90) % 360;
          applyTransform();
        });
      }

      document.addEventListener('keydown', function (event) {
        if (lightbox.hidden) {
          return;
        }

        if (event.key === 'ArrowLeft' && lightboxPrev) {
          lightboxPrev.click();
        } else if (event.key === 'ArrowRight' && lightboxNext) {
          lightboxNext.click();
        }
      });

      initThumbSlider(
        lightbox.querySelector('[data-lightbox-thumbs-track]'),
        lightbox.querySelector('[data-lightbox-thumbs-prev]'),
        lightbox.querySelector('[data-lightbox-thumbs-next]')
      );
    }

    var stepper = document.querySelector('[data-qty-stepper]');
    if (stepper) {
      var input = stepper.querySelector('.pap-product-qty-input');
      var decrease = stepper.querySelector('[data-qty-decrease]');
      var increase = stepper.querySelector('[data-qty-increase]');
      var qtyStack = stepper.closest('[data-qty-stack]');
      var qtyTooltip = qtyStack ? qtyStack.querySelector('[data-qty-tooltip]') : null;

      function clamp(value) {
        var min = parseInt(input.getAttribute('min'), 10) || 1;
        var max = input.getAttribute('max') ? parseInt(input.getAttribute('max'), 10) : null;
        var next = Math.max(min, value);
        if (max !== null) {
          next = Math.min(max, next);
        }
        return next;
      }

      function hideQtyTooltip() {
        if (!qtyTooltip) {
          return;
        }
        qtyTooltip.hidden = true;
        qtyTooltip.setAttribute('aria-hidden', 'true');
      }

      // Limita min/max se comunica printr-un tooltip la hover, nu printr-un
      // stil vizual "stins" pe buton (butonul disabled arata identic cu cel
      // activ - vezi .pap-product-qty-btn:disabled in CSS). Cerut explicit
      // 2026-08-31.
      function maybeShowQtyTooltip(button) {
        if (!qtyTooltip || !button || !button.disabled) {
          return;
        }

        var min = parseInt(input.getAttribute('min'), 10) || 1;
        var max = input.getAttribute('max') ? parseInt(input.getAttribute('max'), 10) : null;
        var text = '';

        if (button === decrease) {
          text = '<?php echo esc_js(__('Cantitatea minimă este', 'papetarie-storefront')); ?> ' + min + '.';
        } else if (button === increase && max !== null) {
          text = '<?php echo esc_js(__('Ai atins limita maximă disponibilă', 'papetarie-storefront')); ?> (' + max + ' <?php echo esc_js(__('bucăți', 'papetarie-storefront')); ?>).';
        }

        if (!text) {
          return;
        }

        qtyTooltip.textContent = text;
        qtyTooltip.hidden = false;
        qtyTooltip.setAttribute('aria-hidden', 'false');
      }

      // Dezactiveaza (functional, nu doar vizual) - / + cand valoarea
      // curenta e deja la limita (min/max) - altfel butoanele raman
      // "clicabile" fara niciun efect.
      function updateDisabledState() {
        var min = parseInt(input.getAttribute('min'), 10) || 1;
        var max = input.getAttribute('max') ? parseInt(input.getAttribute('max'), 10) : null;
        var current = parseInt(input.value, 10) || min;
        if (decrease) {
          decrease.disabled = current <= min;
        }
        if (increase) {
          increase.disabled = max !== null && current >= max;
        }
        hideQtyTooltip();
      }

      [decrease, increase].forEach(function (button) {
        if (!button) {
          return;
        }
        button.addEventListener('mouseenter', function () {
          maybeShowQtyTooltip(button);
        });
        button.addEventListener('mouseleave', hideQtyTooltip);
        button.addEventListener('focus', function () {
          maybeShowQtyTooltip(button);
        });
        button.addEventListener('blur', hideQtyTooltip);
      });

      if (decrease) {
        decrease.addEventListener('click', function () {
          input.value = clamp((parseInt(input.value, 10) || 1) - 1);
          updateDisabledState();
        });
      }

      if (increase) {
        increase.addEventListener('click', function () {
          input.value = clamp((parseInt(input.value, 10) || 1) + 1);
          updateDisabledState();
        });
      }

      updateDisabledState();
    }

    var shareButton = document.querySelector('[data-product-share]');
    if (shareButton) {
      shareButton.addEventListener('click', function () {
        var shareData = {
          title: shareButton.getAttribute('data-share-title') || document.title,
          url: window.location.href
        };

        if (navigator.share) {
          navigator.share(shareData).catch(function () {});
          return;
        }

        if (navigator.clipboard) {
          navigator.clipboard.writeText(shareData.url).catch(function () {});
        }
      });
    }

    var readMoreButton = document.querySelector('[data-read-more]');
    var descriptionBox = document.querySelector('[data-description-box]');
    if (readMoreButton && descriptionBox) {
      // "Citește mai mult" pe baza inaltimii REALE randate a continutului,
      // nu a unui numar fix de randuri - un numar fix (incercat inainte:
      // 3 randuri/78px) fie taia descrieri scurte fara niciun rost (un
      // singur rand deja incape, dar tot arata butonul), fie e prea putin
      // pentru descrieri cu paragrafe/liste. Recalculat la fiecare
      // resize, fiindca acelasi text randeaza la inaltimi total diferite
      // pe desktop vs mobil (rewrapping). Cerut explicit 2026-08-31.
      //
      // COLLAPSE_THRESHOLD: sub-asta descrierea se arata mereu integral.
      // GRACE: daca depaseste pragul cu mai putin de-atat (~2 randuri),
      // tot o aratam integral - nu are sens un buton "Citește mai mult"
      // care ar mai descoperi doar o bucatica de rand.
      var COLLAPSE_THRESHOLD = 280;
      var GRACE = 60;
      var isUserExpanded = false;
      var resizeTimer = null;

      function evaluateDescriptionCollapse() {
        var naturalHeight = descriptionBox.scrollHeight;
        var needsCollapse = naturalHeight > COLLAPSE_THRESHOLD + GRACE;

        if (!needsCollapse) {
          // Incape deja integral - fara buton, si is-expanded ca sa
          // stinga fade-ul (regula CSS existenta il ascunde doar cand
          // clasa asta e prezenta, indiferent de motiv).
          readMoreButton.style.display = 'none';
          descriptionBox.classList.add('is-expanded');
          descriptionBox.style.maxHeight = naturalHeight + 'px';
          return;
        }

        readMoreButton.style.display = '';

        if (isUserExpanded) {
          descriptionBox.classList.add('is-expanded');
          descriptionBox.style.maxHeight = naturalHeight + 'px';
        } else {
          descriptionBox.classList.remove('is-expanded');
          descriptionBox.style.maxHeight = COLLAPSE_THRESHOLD + 'px';
        }
      }

      evaluateDescriptionCollapse();

      window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(evaluateDescriptionCollapse, 150);
      });

      readMoreButton.addEventListener('click', function () {
        isUserExpanded = !isUserExpanded;
        readMoreButton.classList.toggle('is-expanded', isUserExpanded);
        readMoreButton.querySelector('span').textContent = isUserExpanded
          ? '<?php echo esc_js(__('Arată mai puțin', 'papetarie-storefront')); ?>'
          : '<?php echo esc_js(__('Citește mai mult', 'papetarie-storefront')); ?>';
        evaluateDescriptionCollapse();
      });
    }
  })();
</script>

<?php get_footer(); ?>
