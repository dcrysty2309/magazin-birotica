<?php
/* Template Name: Pagina legala (hexagon) */

defined('ABSPATH') || exit;

get_header();

$slug = get_post_field('post_name', get_the_ID());

$pap_legal_map = [
    'termeni-si-conditii'           => ['icon' => 'file-lines-outline', 'eyebrow' => 'Informații legale', 'desc' => 'Regulile de utilizare a site-ului și de plasare a comenzilor.'],
    'politica-de-confidentialitate' => ['icon' => 'shield',             'eyebrow' => 'Informații legale', 'desc' => 'Cum colectăm și protejăm datele tale personale.'],
    'politica-de-retur'             => ['icon' => 'undo',               'eyebrow' => 'Comenzi și retur',  'desc' => 'Cum returnezi un produs în 14 zile, fără complicații.'],
    'livrare'                       => ['icon' => 'truck-outline',      'eyebrow' => 'Comenzi și retur',  'desc' => 'Termene, costuri și zone de livrare prin curierul Cargus.'],
    'intrebari-frecvente'           => ['icon' => 'help',               'eyebrow' => 'Ajutor',            'desc' => 'Răspunsuri rapide la cele mai comune întrebări.'],
    'despre-noi'                    => ['icon' => 'heart-outline',      'eyebrow' => 'Notix',             'desc' => 'Cine suntem și cum lucrăm la Notix.'],
    'garantie'                      => ['icon' => 'shield',             'eyebrow' => 'Comenzi și retur',  'desc' => 'Informații despre garanția produselor și soluționarea reclamațiilor.'],
    'politica-de-cookie-uri'        => ['icon' => 'cookie',             'eyebrow' => 'Informații legale', 'desc' => 'Ce cookie-uri folosim și cum le poți controla.'],
    'contact'                       => ['icon' => 'headset-outline',    'eyebrow' => 'Ajutor',            'desc' => 'Cum ne poți contacta pentru orice întrebare.'],
];
$pap_legal_conf = $pap_legal_map[$slug] ?? ['icon' => 'file-lines-outline', 'eyebrow' => 'Informații', 'desc' => ''];
?>

<style>
  .pap-legal-hero {
    position: relative; overflow: hidden;
    background: #F6F8FB;
    min-height: 260px;
    display: flex; align-items: center;
  }

  /* --- partea stanga: text --- */
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

  /* --- partea dreapta: compozitie hexagoane --- */
  /* toate pozitiile sunt procente din LATIMEA TOTALA a coverului, nu dintr-un sub-container */
  .pap-legal-hero-illustration { display: none; position: absolute; inset: 0; pointer-events: none; }
  @media (min-width: 900px) { .pap-legal-hero-illustration { display: block; } }

  .pap-legal-hex-deco { position: absolute; }
  /* stanga hexagonului principal: tranzitie graduala, mai apropiata/suprapusa acum */
  .pap-legal-hex-deco--bgL { width: 140px; height: 140px; left: 51%; top: 0%; opacity: .22; z-index: 0; }
  .pap-legal-hex-deco--grayL { width: 84px; height: 84px; left: 62%; bottom: 2%; }
  .pap-legal-hex-deco--lineL { width: 112px; height: 112px; left: 54%; top: 34%; opacity: .23; }
  .pap-legal-hex-deco--smallL { width: 45px; height: 45px; left: 66%; top: 62%; opacity: .34; }
  .pap-legal-hex-deco--orange { width: 65px; height: 65px; left: calc(60% + 10px); bottom: 8%; }
  /* zona principala: forme pale suprapuse in jurul hexagonului alb */
  .pap-legal-hex-deco--bgMain { width: 155px; height: 155px; left: 66%; top: -8%; opacity: .28; }
  .pap-legal-hex-deco--bgMain2 { width: 100px; height: 100px; left: 60%; top: 12%; opacity: .2; }
  .pap-legal-hex-deco--bgMain3 { width: 90px; height: 90px; left: 76%; top: 62%; opacity: .22; }
  .pap-legal-hex-deco--grayR { width: 78px; height: 78px; left: 82%; top: 52%; }
  /* dreapta hexagonului principal: apropiat de cluster, densitate redusa spre margine */
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
    .pap-legal-hex-deco--line20, .pap-legal-hex-deco--line55, .pap-legal-hex-deco--micro1,
    .pap-legal-hex-deco--micro2, .pap-legal-hero-dotgrid, .pap-legal-hex-deco--bg3 { display: none; }
  }

  .pap-legal-body { display: flex; gap: 40px; padding-block: 44px 60px; align-items: flex-start; }
  .pap-legal-toc { width: 220px; flex-shrink: 0; position: sticky; top: 24px; }
  .pap-legal-toc:empty { display: none; }
  .pap-legal-toc-title { font-family: var(--pap-font-sans); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #8a96a8; margin-bottom: 14px; }
  .pap-legal-toc a { display: block; font-family: var(--pap-font-sans); font-size: 13px; color: #3d4a63; text-decoration: none; padding: 7px 0 7px 14px; border-left: 2px solid #dde1e8; margin-bottom: 2px; line-height: 1.4; }
  .pap-legal-toc a:hover { color: var(--pap-navy); border-left-color: #f2600c; }
  .pap-legal-toc a.is-active { color: #f2600c; font-weight: 700; border-left-color: #f2600c; }

  .pap-legal-content { flex: 1; min-width: 0; font-family: var(--pap-font-sans); }
  .pap-legal-content p, .pap-legal-content li { font-size: 14px; line-height: 1.7; color: #3d4a63; text-align: justify; hyphens: auto; }
  .pap-legal-content ul { padding-left: 20px; }
  .pap-legal-content h2 {
    margin: 0; padding: 22px 0 10px; font-size: 16.5px; font-weight: 800; color: #f2600c; scroll-margin-top: 20px;
    border-top: 1px solid #e5e8ec;
  }
  .pap-legal-content h2:first-of-type { border-top: 0; padding-top: 0; }
  .pap-legal-content h3 { font-family: var(--pap-font-sans); font-size: 14.5px; font-weight: 800; color: var(--pap-navy); margin: 16px 0 8px; }

  .pap-legal-note { display: flex; gap: 12px; align-items: flex-start; background: #fff8ec; border: 1px solid #f5e0ae; border-radius: 12px; padding: 14px 18px; margin: 0 0 30px; }
  .pap-legal-note-icon { flex-shrink: 0; margin-top: 1px; color: #e0a512; font-weight: 900; font-size: 15px; font-family: var(--pap-font-sans); }
  .pap-legal-note-icon::after { content: '!'; }
  .pap-legal-note-text { margin: 0 !important; font-size: 13px !important; color: #7a5c1f !important; line-height: 1.55 !important; }

  @media (max-width: 860px) {
    .pap-legal-toc { display: none; }
    .pap-legal-hero-title { font-size: 23px; }
  }
</style>

<main id="primary" class="site-main pap-legal-page">
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
      <div class="pap-legal-hero-showcase"><?php echo papetarie_storefront_icon($pap_legal_conf['icon']); ?></div>
    </div>
    <div class="pap-shell pap-legal-hero-inner">
      <div class="pap-legal-hero-text">
        <div class="pap-legal-hero-badge-row">
          <div class="pap-legal-hero-icon"><?php echo papetarie_storefront_icon($pap_legal_conf['icon']); ?></div>
          <div class="pap-legal-hero-eyebrow"><?php echo esc_html($pap_legal_conf['eyebrow']); ?></div>
        </div>
        <div class="pap-legal-hero-accent-line"></div>
        <h1 class="pap-legal-hero-title"><?php the_title(); ?></h1>
        <?php if (!empty($pap_legal_conf['desc'])) : ?>
          <p class="pap-legal-hero-desc"><?php echo esc_html($pap_legal_conf['desc']); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="pap-shell pap-legal-body">
    <nav class="pap-legal-toc" id="pap-legal-toc" aria-label="<?php esc_attr_e('Cuprins', 'papetarie-storefront'); ?>">
      <div class="pap-legal-toc-title"><?php esc_html_e('Pe pagina asta', 'papetarie-storefront'); ?></div>
    </nav>
    <div class="pap-legal-content" id="pap-legal-content">
      <?php
      while (have_posts()) :
          the_post();
          the_content();
      endwhile;
      ?>
    </div>
  </div>
</main>

<script>
  (function () {
    var content = document.getElementById('pap-legal-content');
    var toc = document.getElementById('pap-legal-toc');
    if (!content || !toc) { return; }
    var headings = content.querySelectorAll('h2');
    if (headings.length < 2) { toc.remove(); return; }
    headings.forEach(function (h, i) {
      var id = 'sec-' + (i + 1);
      h.id = id;
      var a = document.createElement('a');
      a.href = '#' + id;
      a.textContent = h.textContent;
      if (i === 0) { a.classList.add('is-active'); }
      toc.appendChild(a);
    });
    var links = toc.querySelectorAll('a');
    links.forEach(function (link) {
      link.addEventListener('click', function () {
        links.forEach(function (l) { l.classList.remove('is-active'); });
        link.classList.add('is-active');
      });
    });
  })();
</script>

<?php get_footer(); ?>
