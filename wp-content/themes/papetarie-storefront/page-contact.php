<?php
/* Template Name: Pagina de contact */

defined('ABSPATH') || exit;

get_header();

$pap_contact_support = papetarie_storefront_get_checkout_support_details();
$pap_contact_phone = $pap_contact_support['phone'];
$pap_contact_email = papetarie_storefront_contact_form_recipient();
?>

<style>
  .pap-contact-hero {
    background: var(--pap-navy);
    color: #fff;
    padding: 40px 0;
  }
  .pap-contact-hero-inner {
    display: flex;
    align-items: center;
    gap: 16px;
  }
  .pap-contact-hero-icon {
    flex: 0 0 auto;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .pap-contact-hero-icon svg {
    width: 24px;
    height: 24px;
    color: #fff;
  }
  .pap-contact-hero-eyebrow {
    font-family: var(--pap-font-sans);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.65);
  }
  .pap-contact-hero-title {
    font-family: var(--pap-font-sans);
    font-size: 28px;
    font-weight: 900;
    margin: 4px 0 0;
    color: #fff;
  }
  .pap-contact-hero-desc {
    font-family: var(--pap-font-sans);
    font-size: 14px;
    color: rgba(255, 255, 255, 0.75);
    margin: 6px 0 0;
    max-width: 52ch;
  }

  .pap-contact-body {
    padding: 40px 0 64px;
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
    gap: 28px;
    align-items: start;
  }

  .pap-contact-card {
    background: #fff;
    border: 1px solid var(--pap-border, #dde1e8);
    border-radius: 10px;
    padding: 28px;
  }

  .pap-contact-card h2 {
    font-family: var(--pap-font-sans);
    font-size: 18px;
    font-weight: 800;
    color: var(--pap-navy);
    margin: 0 0 4px;
  }
  .pap-contact-card-intro {
    font-family: var(--pap-font-sans);
    font-size: 13.5px;
    color: var(--text-secondary, #66758a);
    margin: 0 0 22px;
  }

  .pap-contact-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .pap-contact-form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
  }
  .pap-contact-form-field label {
    font-family: var(--pap-font-sans);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--pap-navy);
  }
  .pap-contact-form-field label span[aria-hidden] {
    color: var(--pap-orange);
  }
  .pap-contact-form-optional {
    font-weight: 400;
    color: var(--text-secondary, #66758a);
  }
  .pap-contact-form-field input,
  .pap-contact-form-field select,
  .pap-contact-form-field textarea {
    font-family: var(--pap-font-sans);
    font-size: 14px;
    padding: 10px 12px;
    border: 1px solid var(--pap-border, #dde1e8);
    border-radius: 6px;
    color: var(--text-primary, #17324d);
    background: #fff;
    width: 100%;
  }
  .pap-contact-form-field input:focus,
  .pap-contact-form-field select:focus,
  .pap-contact-form-field textarea:focus {
    outline: 2px solid var(--pap-navy);
    outline-offset: 1px;
  }
  .pap-contact-form-field textarea {
    resize: vertical;
    min-height: 110px;
  }
  .pap-contact-form-field.has-error input,
  .pap-contact-form-field.has-error textarea {
    border-color: #f43f5e;
  }
  .pap-contact-form-error {
    font-size: 12px;
    color: #e11d48;
    min-height: 1em;
  }
  .pap-contact-form-honeypot {
    position: absolute;
    left: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
  }
  .pap-contact-form-footer {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
  }
  .pap-contact-form-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    padding: 0 26px;
    border: 0;
    border-radius: 3px;
    background: var(--pap-orange);
    color: #fff;
    font-family: var(--pap-font-sans);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
  }
  .pap-contact-form-submit:hover {
    background: var(--pap-orange-deep);
  }
  .pap-contact-form-submit:disabled {
    opacity: 0.7;
    cursor: wait;
  }
  .pap-contact-form-feedback {
    margin: 0;
    font-family: var(--pap-font-sans);
    font-size: 13.5px;
    font-weight: 600;
  }
  .pap-contact-form-feedback[data-state="success"] {
    color: #2f8f6b;
  }
  .pap-contact-form-feedback[data-state="error"] {
    color: #e11d48;
  }

  .pap-contact-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  .pap-contact-info-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 4px;
  }
  .pap-contact-info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: var(--text-primary, #17324d);
    font-family: var(--pap-font-sans);
    font-size: 14.5px;
    font-weight: 600;
  }
  .pap-contact-info-item:hover {
    color: var(--pap-orange);
  }
  .pap-contact-info-icon {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--pap-soft, #f4f7fb);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--pap-navy);
  }
  .pap-contact-info-icon svg {
    width: 17px;
    height: 17px;
  }

  .pap-contact-faq-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
  .pap-contact-faq-list a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 0;
    border-bottom: 1px solid var(--pap-soft, #f4f7fb);
    text-decoration: none;
    color: var(--text-primary, #17324d);
    font-family: var(--pap-font-sans);
    font-size: 14px;
    font-weight: 600;
  }
  .pap-contact-faq-list li:last-child a {
    border-bottom: 0;
  }
  .pap-contact-faq-list a:hover {
    color: var(--pap-orange);
  }
  .pap-contact-faq-list a svg {
    width: 14px;
    height: 14px;
    flex: 0 0 auto;
    color: var(--text-secondary, #66758a);
  }

  @media (max-width: 860px) {
    .pap-contact-body {
      grid-template-columns: 1fr;
    }
    .pap-contact-form-row {
      grid-template-columns: 1fr;
    }
  }
</style>

<main id="primary" class="site-main pap-contact-page">
  <div class="pap-contact-hero">
    <div class="pap-shell pap-contact-hero-inner">
      <div class="pap-contact-hero-icon"><?php echo papetarie_storefront_icon('headset-outline'); ?></div>
      <div>
        <div class="pap-contact-hero-eyebrow"><?php esc_html_e('Ajutor', 'papetarie-storefront'); ?></div>
        <h1 class="pap-contact-hero-title"><?php the_title(); ?></h1>
        <p class="pap-contact-hero-desc"><?php esc_html_e('Scrie-ne despre produse, comenzi sau orice altă nelămurire — îți răspundem cât mai rapid posibil.', 'papetarie-storefront'); ?></p>
      </div>
    </div>
  </div>

  <div class="pap-shell pap-contact-body">
    <section class="pap-contact-card">
      <h2><?php esc_html_e('Trimite-ne un mesaj', 'papetarie-storefront'); ?></h2>
      <p class="pap-contact-card-intro"><?php esc_html_e('Completează formularul de mai jos și îți răspundem pe email.', 'papetarie-storefront'); ?></p>
      <?php papetarie_storefront_render_contact_form(); ?>
    </section>

    <aside class="pap-contact-sidebar">
      <section class="pap-contact-card">
        <h2><?php esc_html_e('Scrie-ne direct', 'papetarie-storefront'); ?></h2>
        <p class="pap-contact-card-intro"><?php esc_html_e('Pentru întrebări despre produse, disponibilitate sau comenzi B2B (firme, școli, instituții).', 'papetarie-storefront'); ?></p>
        <div class="pap-contact-info-list">
          <?php if ($pap_contact_phone !== '') : ?>
            <a class="pap-contact-info-item" href="<?php echo esc_attr('tel:+4' . preg_replace('/\s+/', '', $pap_contact_phone)); ?>">
              <span class="pap-contact-info-icon"><?php echo papetarie_storefront_checkout_address_card_icon_svg('phone'); ?></span>
              <span><?php echo esc_html($pap_contact_phone); ?></span>
            </a>
          <?php endif; ?>
          <a class="pap-contact-info-item" href="mailto:<?php echo esc_attr($pap_contact_email); ?>">
            <span class="pap-contact-info-icon"><?php echo papetarie_storefront_icon('mail'); ?></span>
            <span><?php echo esc_html($pap_contact_email); ?></span>
          </a>
        </div>
      </section>

      <section class="pap-contact-card">
        <h2><?php esc_html_e('Alte întrebări frecvente', 'papetarie-storefront'); ?></h2>
        <p class="pap-contact-card-intro"><?php esc_html_e('Poate găsești răspunsul mai rapid pe paginile dedicate:', 'papetarie-storefront'); ?></p>
        <ul class="pap-contact-faq-list">
          <li><a href="<?php echo esc_url(home_url('/livrare/')); ?>"><?php esc_html_e('Livrare', 'papetarie-storefront'); ?><?php echo papetarie_storefront_icon('chevron'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/politica-de-retur/')); ?>"><?php esc_html_e('Politica de retur', 'papetarie-storefront'); ?><?php echo papetarie_storefront_icon('chevron'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/garantie/')); ?>"><?php esc_html_e('Garanție', 'papetarie-storefront'); ?><?php echo papetarie_storefront_icon('chevron'); ?></a></li>
          <li><a href="<?php echo esc_url(home_url('/intrebari-frecvente/')); ?>"><?php esc_html_e('Întrebări frecvente', 'papetarie-storefront'); ?><?php echo papetarie_storefront_icon('chevron'); ?></a></li>
        </ul>
      </section>
    </aside>
  </div>
</main>

<?php get_footer(); ?>
