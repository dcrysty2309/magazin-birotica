<?php
/* Template Name: Pagina legala (hexagon) */

defined('ABSPATH') || exit;

get_header();

$slug = get_post_field('post_name', get_the_ID());

$pap_legal_map = [
    'termeni-si-conditii'           => ['icon' => 'file-lines-outline', 'color' => '#0d2e61', 'deep' => '#09224a', 'eyebrow' => 'Informații legale'],
    'politica-de-confidentialitate' => ['icon' => 'shield',             'color' => '#8a32b0', 'deep' => '#5c1f76', 'eyebrow' => 'Informații legale'],
    'politica-de-retur'             => ['icon' => 'undo',               'color' => '#ff5b1f', 'deep' => '#f0440b', 'eyebrow' => 'Comenzi și retur'],
    'livrare'                       => ['icon' => 'truck-outline',      'color' => '#0d5e4a', 'deep' => '#083d30', 'eyebrow' => 'Comenzi și retur'],
    'intrebari-frecvente'           => ['icon' => 'help',               'color' => '#f3373d', 'deep' => '#c22029', 'eyebrow' => 'Ajutor'],
    'despre-noi'                    => ['icon' => 'heart-outline',      'color' => '#8a32b0', 'deep' => '#5c1f76', 'eyebrow' => 'Notix'],
    'garantie'                      => ['icon' => 'check-circle',       'color' => '#0d5e4a', 'deep' => '#083d30', 'eyebrow' => 'Comenzi și retur'],
    'politica-de-cookie-uri'        => ['icon' => 'cookie',             'color' => '#ff5b1f', 'deep' => '#f0440b', 'eyebrow' => 'Informații legale'],
    'contact'                       => ['icon' => 'headset-outline',    'color' => '#0d2e61', 'deep' => '#09224a', 'eyebrow' => 'Ajutor'],
];
$pap_legal_conf = $pap_legal_map[$slug] ?? ['icon' => 'file-lines-outline', 'color' => '#0d2e61', 'deep' => '#09224a', 'eyebrow' => 'Informații'];
?>

<style>
  .pap-legal-hero {
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, color-mix(in srgb, var(--hex-color) 55%, #123a72) 0%, var(--hex-color) 45%, var(--hex-deep) 100%);
    padding: 48px 0 42px;
  }
  .pap-legal-hero-hex { position: absolute; inset: 0; opacity: .14; }
  .pap-legal-hero-inner { display: flex; align-items: center; gap: 20px; position: relative; z-index: 2; }
  .pap-legal-hero-icon {
    flex-shrink: 0; width: 56px; height: 56px; border-radius: 50%;
    background: rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
  }
  .pap-legal-hero-icon svg { width: 26px; height: 26px; color: #fff; }
  .pap-legal-hero-eyebrow { font-family: var(--pap-font-sans); font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: rgba(255,255,255,.7); margin-bottom: 6px; }
  .pap-legal-hero-title { font-family: var(--pap-font-sans); font-size: 28px; font-weight: 900; color: #fff; margin: 0; }

  .pap-legal-body { display: flex; gap: 40px; padding-block: 44px 60px; align-items: flex-start; }
  .pap-legal-toc { width: 220px; flex-shrink: 0; position: sticky; top: 24px; }
  .pap-legal-toc:empty { display: none; }
  .pap-legal-toc-title { font-family: var(--pap-font-sans); font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #8a96a8; margin-bottom: 14px; }
  .pap-legal-toc a { display: block; font-family: var(--pap-font-sans); font-size: 13px; color: #3d4a63; text-decoration: none; padding: 7px 0 7px 14px; border-left: 2px solid #dde1e8; margin-bottom: 2px; line-height: 1.4; }
  .pap-legal-toc a:hover { color: var(--pap-navy); border-left-color: var(--hex-color); }

  .pap-legal-content { flex: 1; min-width: 0; max-width: 680px; font-family: var(--pap-font-sans); }
  .pap-legal-content p, .pap-legal-content li { font-size: 14px; line-height: 1.7; color: #3d4a63; }
  .pap-legal-content ul { padding-left: 20px; }
  .pap-legal-content h2 { margin: 30px 0 10px; font-size: 16.5px; font-weight: 800; color: var(--pap-navy); scroll-margin-top: 20px; }
  .pap-legal-content h2:first-of-type { margin-top: 0; }
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
  <div class="pap-legal-hero" style="--hex-color: <?php echo esc_attr($pap_legal_conf['color']); ?>; --hex-deep: <?php echo esc_attr($pap_legal_conf['deep']); ?>;">
    <svg class="pap-legal-hero-hex" viewBox="0 0 900 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <defs><pattern id="papLegalHex" width="42" height="72" patternUnits="userSpaceOnUse">
        <polygon points="21,0 42,12 42,36 21,48 0,36 0,12" fill="none" stroke="#ffffff" stroke-width="1"/>
        <polygon points="21,36 42,48 42,72 21,84 0,72 0,48" fill="none" stroke="#ffffff" stroke-width="1"/>
      </pattern></defs>
      <rect width="100%" height="100%" fill="url(#papLegalHex)"/>
    </svg>
    <div class="pap-shell pap-legal-hero-inner">
      <div class="pap-legal-hero-icon"><?php echo papetarie_storefront_icon($pap_legal_conf['icon']); ?></div>
      <div>
        <div class="pap-legal-hero-eyebrow"><?php echo esc_html($pap_legal_conf['eyebrow']); ?></div>
        <h1 class="pap-legal-hero-title"><?php the_title(); ?></h1>
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
      toc.appendChild(a);
    });
  })();
</script>

<?php get_footer(); ?>
