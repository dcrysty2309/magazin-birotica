<?php
/*
Template Name: Checkout Test Cases
Template Post Type: page
*/

defined('ABSPATH') || exit;

get_header();

$flows = [
    [
        'badge' => 'guest',
        'badge_label' => __('Guest', 'papetarie-storefront'),
        'title' => __('Guest — fără cont', 'papetarie-storefront'),
        'credentials' => null,
        'steps' => [
            __('Deschide site-ul într-o fereastră incognito (sau delogează-te).', 'papetarie-storefront'),
            __('Adaugă un produs în coș.', 'papetarie-storefront'),
            __('Mergi la pagina de checkout.', 'papetarie-storefront'),
            __('Completează adresa de livrare cu date reale și apasă „Continuă”.', 'papetarie-storefront'),
            __('Alege metoda de livrare, apoi „Ramburs” ca metodă de plată.', 'papetarie-storefront'),
            __('Plasează comanda.', 'papetarie-storefront'),
        ],
        'expect' => __('Ajungi pe pagina de confirmare, iar comanda apare corect în WooCommerce → Comenzi, cu adresa, transportul și plata alese.', 'papetarie-storefront'),
    ],
    [
        'badge' => 'account',
        'badge_label' => __('Cont logat', 'papetarie-storefront'),
        'title' => __('Cont cu adresă salvată', 'papetarie-storefront'),
        'credentials' => [
            'email' => 'checkout.oneaddress@test.local',
            'password' => 'TestCheckout123!',
        ],
        'steps' => [
            __('Loghează-te cu contul de mai jos.', 'papetarie-storefront'),
            __('Adaugă un produs în coș și mergi la checkout.', 'papetarie-storefront'),
            __('Verifică că adresa salvată apare direct, fără să o completezi din nou.', 'papetarie-storefront'),
            __('Alege metoda de livrare, apoi „Ramburs” ca metodă de plată.', 'papetarie-storefront'),
            __('Plasează comanda.', 'papetarie-storefront'),
        ],
        'expect' => __('Adresa din cont apare precompletată de la primul pas. Comanda se plasează fără să retastezi nimic.', 'papetarie-storefront'),
    ],
    [
        'badge' => 'account',
        'badge_label' => __('Cont logat', 'papetarie-storefront'),
        'title' => __('Cont fără adresă salvată', 'papetarie-storefront'),
        'credentials' => [
            'email' => 'checkout.cleannoaddress@test.local',
            'password' => 'TestCheckout123!',
        ],
        'steps' => [
            __('Loghează-te cu contul de mai jos.', 'papetarie-storefront'),
            __('Adaugă un produs în coș și mergi la checkout.', 'papetarie-storefront'),
            __('Verifică că formularul de adresă apare gol, gata de completat.', 'papetarie-storefront'),
            __('Completează adresa și apasă „Continuă”.', 'papetarie-storefront'),
            __('Alege metoda de livrare, apoi „Ramburs” ca metodă de plată.', 'papetarie-storefront'),
            __('Plasează comanda.', 'papetarie-storefront'),
        ],
        'expect' => __('Formularul e gol de la început — fără date vechi și fără mesaj de eroare. După completare, comanda se plasează normal.', 'papetarie-storefront'),
    ],
];
?>
<main id="primary" class="site-main pap-checkout-flows-page">
  <section class="pap-shell pap-checkout-flows-header">
    <h1><?php esc_html_e('Teste checkout — flow-uri principale', 'papetarie-storefront'); ?></h1>
    <p><?php esc_html_e('Trei scenarii simple, de la coș până la comanda plasată. Le rulezi pe rând, cap-coadă.', 'papetarie-storefront'); ?></p>
  </section>

  <section class="pap-shell pap-checkout-flows-grid">
    <?php foreach ($flows as $flow) : ?>
      <article class="pap-checkout-flow-card">
        <div class="pap-checkout-flow-card__head">
          <span class="pap-checkout-flow-card__badge pap-checkout-flow-card__badge--<?php echo esc_attr($flow['badge']); ?>">
            <?php echo esc_html($flow['badge_label']); ?>
          </span>
          <h2><?php echo esc_html($flow['title']); ?></h2>
        </div>

        <?php if ($flow['credentials']) : ?>
          <dl class="pap-checkout-flow-card__credentials">
            <div>
              <dt><?php esc_html_e('Email', 'papetarie-storefront'); ?></dt>
              <dd><code><?php echo esc_html($flow['credentials']['email']); ?></code></dd>
            </div>
            <div>
              <dt><?php esc_html_e('Parolă', 'papetarie-storefront'); ?></dt>
              <dd><code><?php echo esc_html($flow['credentials']['password']); ?></code></dd>
            </div>
          </dl>
        <?php endif; ?>

        <ol class="pap-checkout-flow-card__steps">
          <?php foreach ($flow['steps'] as $step) : ?>
            <li><?php echo esc_html($step); ?></li>
          <?php endforeach; ?>
        </ol>

        <p class="pap-checkout-flow-card__expect">
          <strong><?php esc_html_e('Ce ar trebui să vezi:', 'papetarie-storefront'); ?></strong>
          <?php echo esc_html($flow['expect']); ?>
        </p>
      </article>
    <?php endforeach; ?>
  </section>
</main>

<?php
get_footer();
