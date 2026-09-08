<?php
/* Template Name: Pagina de contact */

defined('ABSPATH') || exit;

get_header();

$pap_contact_support = papetarie_storefront_get_checkout_support_details();
$pap_contact_phone = $pap_contact_support['phone'];
$pap_contact_email = papetarie_storefront_contact_form_recipient();
?>

<style>
  /* Hero identic cu cel din page-legal.php (Despre noi, Termeni, Livrare
     etc.) - fundal deschis cu compozitie de hexagoane decorative, nu un
     banner navy plin. Contact avea deja o intrare pregatita in
     $pap_legal_map de acolo, dar pagina foloseste propriul template -
     copiat aici (nu reutilizat prin page-legal.php) ca sa nu cuplam
     formularul de contact de sablonul generic de pagina legala, folosit de
     8 alte pagini. Semnalat de user 2026-08-31: "nu imi place poza aia de
     cover... albastru lal" (bannerul navy plin, versiunea anterioara). */
  .pap-legal-hero {
    position: relative; overflow: hidden;
    background: #F6F8FB;
    min-height: 260px;
    display: flex; align-items: center;
  }
  .pap-legal-hero-inner { position: relative; z-index: 3; width: 100%; padding-block: 32px; }
  .pap-legal-hero-text { max-width: 45%; }
  .pap-legal-hero-badge-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
  .pap-legal-hero-icon {
    flex-shrink: 0; width: 46px; height: 46px; border-radius: 50%;
    background: #E8EDF5;
    display: flex; align-items: center; justify-content: center;
  }
  .pap-legal-hero-icon svg { width: 20px; height: 20px; color: var(--pap-navy); }
  .pap-legal-hero-eyebrow { font-family: var(--pap-font-sans); font-size: 11px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #5b6b85; }
  .pap-legal-hero-accent-line { width: 34px; height: 3px; border-radius: 2px; background: #F2600C; margin: 8px 0 12px; }
  .pap-legal-hero-title { font-family: var(--pap-font-sans); font-size: 30px; font-weight: 900; color: var(--pap-navy); margin: 0 0 8px; }
  .pap-legal-hero-desc { font-family: var(--pap-font-sans); font-size: 14px; line-height: 1.6; color: #5b6b85; margin: 0; }

  .pap-legal-hero-illustration { display: none; position: absolute; inset: 0; pointer-events: none; }
  @media (min-width: 900px) { .pap-legal-hero-illustration { display: block; } }

  .pap-legal-hex-deco { position: absolute; }
  .pap-legal-hex-deco--bgL { width: 140px; height: 140px; left: 51%; top: 0%; opacity: .22; z-index: 0; }
  .pap-legal-hex-deco--grayL { width: 84px; height: 84px; left: 62%; bottom: 2%; }
  .pap-legal-hex-deco--lineL { width: 112px; height: 112px; left: 54%; top: 34%; opacity: .23; }
  .pap-legal-hex-deco--smallL { width: 45px; height: 45px; left: 66%; top: 62%; opacity: .34; }
  .pap-legal-hex-deco--orange { width: 65px; height: 65px; left: calc(60% + 10px); bottom: 8%; }
  .pap-legal-hex-deco--bgMain { width: 155px; height: 155px; left: 66%; top: -8%; opacity: .28; }
  .pap-legal-hex-deco--bgMain2 { width: 100px; height: 100px; left: 60%; top: 12%; opacity: .2; }
  .pap-legal-hex-deco--bgMain3 { width: 90px; height: 90px; left: 76%; top: 62%; opacity: .22; }
  .pap-legal-hex-deco--grayR { width: 78px; height: 78px; left: 82%; top: 52%; }
  .pap-legal-hex-deco--lineR1 { width: 118px; height: 118px; left: 81%; top: 2%; opacity: .28; }
  .pap-legal-hex-deco--lineR2 { width: 54px; height: 54px; left: 90%; top: 46%; opacity: .22; }
  .pap-legal-hex-deco--microR { width: 20px; height: 20px; left: 96%; top: 22%; opacity: .18; }
  .pap-legal-hex-deco--tinyR { width: 24px; height: 24px; left: 95%; top: 76%; opacity: .14; }
  .pap-legal-hero-dotgrid {
    position: absolute; left: 88%; bottom: 10%; z-index: 1; opacity: .3;
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; width: 40px;
  }
  .pap-legal-hero-dotgrid span { width: 3px; height: 3px; border-radius: 50%; background: #9AAAC9; }

  .pap-legal-hero-showcase {
    position: absolute; left: 73%; top: calc(50% + 12px); transform: translate(-50%, -50%);
    width: 158px; height: 172px; z-index: 2;
    background: #ffffff;
    box-shadow: 0 12px 28px rgba(20, 40, 80, .1);
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
    display: flex; align-items: center; justify-content: center;
  }
  .pap-legal-hero-showcase svg { width: 60px; height: 60px; color: var(--pap-navy); }

  @media (min-width: 900px) and (max-width: 1199px) {
    .pap-legal-hero-showcase { width: 118px; height: 130px; }
    .pap-legal-hero-showcase svg { width: 46px; height: 46px; }
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
    .pap-legal-hero-title {
      font-size: 23px;
    }
  }
</style>

<main id="primary" class="site-main pap-contact-page">
  <div class="pap-legal-hero">
    <div class="pap-legal-hero-illustration" aria-hidden="true">
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--bgL" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#E8EDF5"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--grayL" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#C7D3E8"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--lineL" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="none" stroke="#0d2e61" stroke-width="1.3"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--smallL" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#D6E0F0"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--bgMain" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#DFE6F0"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--bgMain2" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#E8EDF5"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--bgMain3" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#D5DEEA"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--grayR" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="#D5DEEA"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--lineR1" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="none" stroke="#0d2e61" stroke-width="1.2"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--lineR2" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="none" stroke="#0d2e61" stroke-width="1.4"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--microR" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="none" stroke="#0d2e61" stroke-width="1.5"/></svg>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--tinyR" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="none" stroke="#9AAAC9" stroke-width="1.3"/></svg>
      <div class="pap-legal-hero-dotgrid">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
      </div>
      <svg class="pap-legal-hex-deco pap-legal-hex-deco--orange" viewBox="0 0 100 100"><polygon points="50,2 98,26 98,74 50,98 2,74 2,26" fill="none" stroke="#F2600C" stroke-width="1.6"/></svg>
      <div class="pap-legal-hero-showcase"><?php echo papetarie_storefront_icon('headset-outline'); ?></div>
    </div>
    <div class="pap-shell pap-legal-hero-inner">
      <div class="pap-legal-hero-text">
        <div class="pap-legal-hero-badge-row">
          <div class="pap-legal-hero-icon"><?php echo papetarie_storefront_icon('headset-outline'); ?></div>
          <div class="pap-legal-hero-eyebrow"><?php esc_html_e('Ajutor', 'papetarie-storefront'); ?></div>
        </div>
        <div class="pap-legal-hero-accent-line"></div>
        <h1 class="pap-legal-hero-title"><?php the_title(); ?></h1>
        <p class="pap-legal-hero-desc"><?php esc_html_e('Scrie-ne despre produse, comenzi sau orice altă nelămurire — îți răspundem cât mai rapid posibil.', 'papetarie-storefront'); ?></p>
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
