<?php

defined('ABSPATH') || exit;

get_header();

$pap_404_shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
$pap_404_account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$pap_404_categories = ['articole-scolare', 'accesorii-pentru-scris', 'organizare-arhivare-prezentare', 'arta', 'articole-pentru-birou', 'articole-din-hartie', 'creativitate', 'periferice', 'curatenie-si-sanitare', 'bagajerie', 'universul-copiilor'];
?>
<main id="primary" class="site-main pap-404-page">
  <section class="pap-404-hero">
    <div class="pap-404-hero__figure" aria-hidden="true">
      <span class="pap-404-hero__number">404</span>
      <svg class="pap-404-hero__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20 20.5 3.5a1.5 1.5 0 0 0-2-2L2 10l3 3 1.5-1.5"></path>
        <path d="m14 6 4 4"></path>
        <path d="M5 13 2 20l7-3"></path>
      </svg>
    </div>

    <h1><?php esc_html_e('Pagina nu a fost găsită', 'papetarie-storefront'); ?></h1>
    <p><?php esc_html_e('Adresa pe care ai accesat-o nu există sau a fost mutată. Încearcă să cauți produsul dorit sau întoarce-te pe pagina principală.', 'papetarie-storefront'); ?></p>

    <div class="pap-404-hero__actions">
      <a class="pap-404-hero__primary" href="<?php echo esc_url(home_url('/')); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.2 12 4l9 7.2"></path><path d="M5 10.5V21h14V10.5"></path></svg>
        <?php esc_html_e('Pagina principală', 'papetarie-storefront'); ?>
      </a>
      <a class="pap-404-hero__secondary" href="<?php echo esc_url($pap_404_account_url); ?>">
        <?php esc_html_e('Contul meu', 'papetarie-storefront'); ?>
      </a>
    </div>

    <div class="pap-404-hero__categories">
      <span><?php esc_html_e('Categorii populare', 'papetarie-storefront'); ?></span>
      <div class="pap-404-hero__chips">
        <?php foreach ($pap_404_categories as $pap_404_slug) :
            $pap_404_term = get_term_by('slug', $pap_404_slug, 'product_cat');
            if (!$pap_404_term instanceof WP_Term) {
                continue;
            }
            ?>
          <a class="pap-404-hero__chip" href="<?php echo esc_url(get_term_link($pap_404_term)); ?>"><?php echo esc_html($pap_404_term->name); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php
get_footer();
