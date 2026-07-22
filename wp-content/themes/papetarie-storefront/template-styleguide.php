<?php

defined('ABSPATH') || exit;

$sg_colors = [
    ['name' => 'Navy', 'hex' => '#0d2e61', 'desc' => 'Primary / Text'],
    ['name' => 'Orange', 'hex' => '#ff5b1f', 'desc' => 'CTA / Accent'],
    ['name' => 'BG', 'hex' => '#f4f6f9', 'desc' => 'Page background'],
    ['name' => 'Border', 'hex' => '#dde1e8', 'desc' => 'Dividers'],
    ['name' => 'Muted', 'hex' => '#6b7a95', 'desc' => 'Secondary text'],
    ['name' => 'Success', 'hex' => '#16a34a', 'desc' => 'Confirmare'],
    ['name' => 'Danger', 'hex' => '#dc2626', 'desc' => 'Eroare / Reducere'],
    ['name' => 'Warning', 'hex' => '#f59e0b', 'desc' => 'Stele / Avertizare'],
    ['name' => 'White', 'hex' => '#ffffff', 'desc' => 'Card / Surface'],
    ['name' => 'Slate 50', 'hex' => '#f8fafc', 'desc' => 'Input background'],
];

$sg_ratings = [
    ['score' => 5, 'stars' => 5, 'count' => 118],
    ['score' => 4, 'stars' => 4, 'count' => 96],
    ['score' => 3, 'stars' => 3, 'count' => 74],
    ['score' => 2, 'stars' => 2, 'count' => 52],
];

$star_icon = papetarie_storefront_icon('star');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?php esc_html_e('Design System — Style Guide', 'papetarie-storefront'); ?></title>
  <?php wp_head(); ?>
</head>
<body <?php body_class('pap-sg-body'); ?>>
<?php wp_body_open(); ?>

<header class="pap-sg-header">
  <div class="pap-sg-header-inner">
    <div class="pap-sg-brand">
      <a class="pap-sg-logo" href="<?php echo esc_url(home_url('/')); ?>">
        <span class="pap-sg-logo-icon" aria-hidden="true">S</span>
        <span class="pap-sg-logo-text">SUPPLYHUB</span>
      </a>
      <span class="pap-sg-divider" aria-hidden="true"></span>
      <span class="pap-sg-label"><?php esc_html_e('Style Guide', 'papetarie-storefront'); ?></span>
    </div>
    <a class="pap-sg-back" href="<?php echo esc_url(home_url('/')); ?>">← <?php esc_html_e('Înapoi la site', 'papetarie-storefront'); ?></a>
  </div>
</header>

<main class="pap-sg-main">
  <h1 class="pap-sg-title"><?php esc_html_e('Design System', 'papetarie-storefront'); ?></h1>
  <p class="pap-sg-subtitle"><?php esc_html_e('Componente, culori și tipografie folosite pe platformă.', 'papetarie-storefront'); ?></p>

  <nav class="pap-sg-tabs" role="tablist">
    <button type="button" class="pap-sg-tab is-active" data-sg-tab="culori" role="tab" aria-selected="true"><?php esc_html_e('Culori', 'papetarie-storefront'); ?> <span class="pap-sg-tab-count">10</span></button>
    <button type="button" class="pap-sg-tab" data-sg-tab="tipografie" role="tab" aria-selected="false"><?php esc_html_e('Tipografie', 'papetarie-storefront'); ?> <span class="pap-sg-tab-count">6</span></button>
    <button type="button" class="pap-sg-tab" data-sg-tab="butoane" role="tab" aria-selected="false"><?php esc_html_e('Butoane', 'papetarie-storefront'); ?> <span class="pap-sg-tab-count">10</span></button>
    <button type="button" class="pap-sg-tab" data-sg-tab="formulare" role="tab" aria-selected="false"><?php esc_html_e('Formulare', 'papetarie-storefront'); ?> <span class="pap-sg-tab-count">8</span></button>
    <button type="button" class="pap-sg-tab" data-sg-tab="componente" role="tab" aria-selected="false"><?php esc_html_e('Componente', 'papetarie-storefront'); ?> <span class="pap-sg-tab-count">12</span></button>
  </nav>

  <!-- ===================== CULORI ===================== -->
  <div class="pap-sg-panel is-active" data-sg-panel="culori">
    <div class="pap-sg-colors-grid">
      <?php foreach ($sg_colors as $sg_color) : ?>
        <div class="pap-sg-color-card">
          <div class="pap-sg-color-swatch" style="background:<?php echo esc_attr($sg_color['hex']); ?>;<?php echo $sg_color['hex'] === '#ffffff' ? 'border-bottom:1px solid #dde1e8;' : ''; ?>"></div>
          <div class="pap-sg-color-info">
            <p class="pap-sg-color-name"><?php echo esc_html($sg_color['name']); ?></p>
            <p class="pap-sg-color-hex"><?php echo esc_html($sg_color['hex']); ?></p>
            <p class="pap-sg-color-desc"><?php echo esc_html($sg_color['desc']); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ===================== TIPOGRAFIE ===================== -->
  <div class="pap-sg-panel" data-sg-panel="tipografie">
    <div class="pap-sg-card">
      <p class="pap-sg-card-title"><?php esc_html_e('Headings', 'papetarie-storefront'); ?></p>

      <div class="pap-sg-type-row">
        <span class="pap-sg-type-tag">H1</span>
        <h1 class="pap-heading-h1 pap-sg-type-sample"><?php esc_html_e('Titlu exemplu H1', 'papetarie-storefront'); ?></h1>
        <span class="pap-sg-type-meta">28px / 800</span>
      </div>
      <div class="pap-sg-type-row">
        <span class="pap-sg-type-tag">H2</span>
        <h2 class="pap-heading-h2 pap-sg-type-sample"><?php esc_html_e('Titlu exemplu H2', 'papetarie-storefront'); ?></h2>
        <span class="pap-sg-type-meta">22px / 700</span>
      </div>
      <div class="pap-sg-type-row">
        <span class="pap-sg-type-tag">H3</span>
        <h3 class="pap-heading-h3 pap-sg-type-sample"><?php esc_html_e('Titlu exemplu H3', 'papetarie-storefront'); ?></h3>
        <span class="pap-sg-type-meta">18px / 700</span>
      </div>
      <div class="pap-sg-type-row">
        <span class="pap-sg-type-tag">H4</span>
        <h4 class="pap-heading-h4 pap-sg-type-sample"><?php esc_html_e('Titlu exemplu H4', 'papetarie-storefront'); ?></h4>
        <span class="pap-sg-type-meta">15px / 600</span>
      </div>

      <?php
      papetarie_storefront_render_code_toggle(
          "<h1 class=\"pap-heading-h1\">…</h1>\n<h2 class=\"pap-heading-h2\">…</h2>\n<h3 class=\"pap-heading-h3\">…</h3>\n<h4 class=\"pap-heading-h4\">…</h4>"
      );
      ?>
    </div>

    <div class="pap-sg-grid" style="margin-top:12px;">
      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Body text', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <p class="pap-text-body"><?php esc_html_e('Text normal — 14px / #374151', 'papetarie-storefront'); ?></p>
          <p class="pap-text-secondary"><?php esc_html_e('Text secundar — 12px / muted', 'papetarie-storefront'); ?></p>
          <p class="pap-text-meta"><?php esc_html_e('Text mic / meta — 11px', 'papetarie-storefront'); ?></p>
        </div>
        <?php papetarie_storefront_render_code_toggle("<p class=\"pap-text-body\">…</p>\n<p class=\"pap-text-secondary\">…</p>\n<p class=\"pap-text-meta\">…</p>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Labels & badges text', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <span class="pap-text-label"><?php esc_html_e('LABEL UPPERCASE', 'papetarie-storefront'); ?></span>
          <a class="pap-text-link" href="#"><?php esc_html_e('Link / accent text', 'papetarie-storefront'); ?></a>
          <span class="pap-text-strike">32.00 lei — <?php esc_html_e('preț tăiat', 'papetarie-storefront'); ?></span>
        </div>
        <?php papetarie_storefront_render_code_toggle("<span class=\"pap-text-label\">…</span>\n<a class=\"pap-text-link\" href=\"#\">…</a>\n<span class=\"pap-text-strike\">32.00 lei</span>"); ?>
      </div>
    </div>
  </div>

  <!-- ===================== BUTOANE ===================== -->
  <div class="pap-sg-panel" data-sg-panel="butoane">
    <div class="pap-sg-grid">
      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Butoane principale', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row">
          <button type="button" class="pap-btn"><?php esc_html_e('Acțiune principală', 'papetarie-storefront'); ?></button>
          <button type="button" class="pap-btn pap-btn--secondary"><?php esc_html_e('Acțiune secundară', 'papetarie-storefront'); ?></button>
        </div>
        <?php papetarie_storefront_render_code_toggle("<button class=\"pap-btn\">Acțiune principală</button>\n<button class=\"pap-btn pap-btn--secondary\">Acțiune secundară</button>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Butoane outline & ghost', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row">
          <button type="button" class="pap-btn pap-btn--outline"><?php esc_html_e('Outline', 'papetarie-storefront'); ?></button>
          <button type="button" class="pap-btn pap-btn--ghost"><?php esc_html_e('Ghost', 'papetarie-storefront'); ?></button>
        </div>
        <?php papetarie_storefront_render_code_toggle("<button class=\"pap-btn pap-btn--outline\">Outline</button>\n<button class=\"pap-btn pap-btn--ghost\">Ghost</button>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Add to cart — stări', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row">
          <button type="button" class="pap-btn">
            <span class="pap-btn-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('bag'); ?></span>
            <?php esc_html_e('Adaugă în coș', 'papetarie-storefront'); ?>
          </button>
          <span class="pap-text-meta">← <?php esc_html_e('dă click', 'papetarie-storefront'); ?></span>
        </div>
        <?php papetarie_storefront_render_code_toggle("<button class=\"pap-btn\">\n  <span class=\"pap-btn-icon\">…</span>\n  Adaugă în coș\n</button>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Butoane disabled & mici', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row">
          <button type="button" class="pap-btn pap-btn--disabled" disabled><?php esc_html_e('Indisponibil', 'papetarie-storefront'); ?></button>
          <button type="button" class="pap-btn pap-btn--sm"><?php esc_html_e('Mic', 'papetarie-storefront'); ?></button>
          <button type="button" class="pap-btn pap-btn--sm-outline"><?php esc_html_e('Mic outline', 'papetarie-storefront'); ?></button>
        </div>
        <?php papetarie_storefront_render_code_toggle("<button class=\"pap-btn pap-btn--disabled\" disabled>Indisponibil</button>\n<button class=\"pap-btn pap-btn--sm\">Mic</button>\n<button class=\"pap-btn pap-btn--sm-outline\">Mic outline</button>"); ?>
      </div>
    </div>

    <div class="pap-sg-card" style="margin-top:12px;">
      <p class="pap-sg-card-title"><?php esc_html_e('Variante cu iconiță', 'papetarie-storefront'); ?></p>
      <div class="pap-sg-row">
        <button type="button" class="pap-btn pap-btn--secondary">
          <span class="pap-btn-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('search'); ?></span>
          <?php esc_html_e('Caută', 'papetarie-storefront'); ?>
        </button>
        <button type="button" class="pap-btn">
          <span class="pap-btn-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('bag'); ?></span>
          <?php esc_html_e('Coș (3)', 'papetarie-storefront'); ?>
        </button>
        <button type="button" class="pap-btn pap-btn--outline">
          <?php esc_html_e('Sortează', 'papetarie-storefront'); ?>
          <span class="pap-btn-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron-down'); ?></span>
        </button>
        <button type="button" class="pap-btn pap-btn--danger-outline">
          <span class="pap-btn-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('trash-2'); ?></span>
          <?php esc_html_e('Șterge', 'papetarie-storefront'); ?>
        </button>
      </div>
      <?php papetarie_storefront_render_code_toggle("<button class=\"pap-btn pap-btn--secondary\"><span class=\"pap-btn-icon\">…</span>Caută</button>\n<button class=\"pap-btn\"><span class=\"pap-btn-icon\">…</span>Coș (3)</button>\n<button class=\"pap-btn pap-btn--outline\">Sortează<span class=\"pap-btn-icon\">…</span></button>\n<button class=\"pap-btn pap-btn--danger-outline\"><span class=\"pap-btn-icon\">…</span>Șterge</button>"); ?>
    </div>
  </div>

  <!-- ===================== FORMULARE ===================== -->
  <div class="pap-sg-panel" data-sg-panel="formulare">
    <div class="pap-sg-grid">
      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Input text', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <input type="text" class="pap-input" placeholder="<?php esc_attr_e('Caută produs...', 'papetarie-storefront'); ?>">
          <div class="pap-input-group">
            <span class="pap-input-group-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('search'); ?></span>
            <input type="text" class="pap-input" placeholder="<?php esc_attr_e('Cu iconiță stânga', 'papetarie-storefront'); ?>">
          </div>
          <div>
            <input type="text" class="pap-input pap-input--error" placeholder="<?php esc_attr_e('Câmp cu eroare', 'papetarie-storefront'); ?>">
            <p class="pap-input-error-text"><?php esc_html_e('Câmpul este obligatoriu', 'papetarie-storefront'); ?></p>
          </div>
          <input type="text" class="pap-input" placeholder="<?php esc_attr_e('Input dezactivat', 'papetarie-storefront'); ?>" disabled>
        </div>
        <?php papetarie_storefront_render_code_toggle("<input class=\"pap-input\">\n<div class=\"pap-input-group\">\n  <span class=\"pap-input-group-icon\">…</span>\n  <input class=\"pap-input\">\n</div>\n<input class=\"pap-input pap-input--error\">\n<input class=\"pap-input\" disabled>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Select & Textarea', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <select class="pap-select">
            <option><?php esc_html_e('Alege o opțiune', 'papetarie-storefront'); ?></option>
          </select>
          <textarea class="pap-textarea" placeholder="<?php esc_attr_e('Scrie recenzia ta...', 'papetarie-storefront'); ?>"></textarea>
        </div>
        <?php papetarie_storefront_render_code_toggle("<select class=\"pap-select\">…</select>\n<textarea class=\"pap-textarea\"></textarea>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Checkbox & Radio', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row" style="align-items:flex-start;gap:32px;">
          <div class="pap-sg-col">
            <span class="pap-text-label"><?php esc_html_e('Checkbox', 'papetarie-storefront'); ?></span>
            <label class="pap-form-check-label"><input type="checkbox" class="pap-checkbox-input" checked><?php esc_html_e('Opțiune A', 'papetarie-storefront'); ?></label>
            <label class="pap-form-check-label"><input type="checkbox" class="pap-checkbox-input"><?php esc_html_e('Opțiune B', 'papetarie-storefront'); ?></label>
            <label class="pap-form-check-label"><input type="checkbox" class="pap-checkbox-input" disabled><?php esc_html_e('Dezactivat', 'papetarie-storefront'); ?></label>
          </div>
          <div class="pap-sg-col">
            <span class="pap-text-label"><?php esc_html_e('Radio', 'papetarie-storefront'); ?></span>
            <label class="pap-form-check-label"><input type="radio" name="pap-sg-radio-demo" class="pap-radio-input" checked><?php esc_html_e('Opțiune A', 'papetarie-storefront'); ?></label>
            <label class="pap-form-check-label"><input type="radio" name="pap-sg-radio-demo" class="pap-radio-input"><?php esc_html_e('Opțiune B', 'papetarie-storefront'); ?></label>
            <label class="pap-form-check-label"><input type="radio" name="pap-sg-radio-demo" class="pap-radio-input"><?php esc_html_e('Opțiune C', 'papetarie-storefront'); ?></label>
          </div>
        </div>
        <?php papetarie_storefront_render_code_toggle("<label class=\"pap-form-check-label\">\n  <input type=\"checkbox\" class=\"pap-checkbox-input\">\n  Opțiune A\n</label>\n<label class=\"pap-form-check-label\">\n  <input type=\"radio\" name=\"grup\" class=\"pap-radio-input\">\n  Opțiune A\n</label>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Range & Search bar', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <div>
            <div class="pap-range-row">
              <span class="pap-text-meta"><?php esc_html_e('Preț', 'papetarie-storefront'); ?></span>
              <span class="pap-text-secondary" style="color:#0d2e61;font-weight:600;">0 — 200 lei</span>
            </div>
            <input type="range" class="pap-range" min="0" max="200" value="120">
          </div>
          <div class="pap-search-bar">
            <input type="text" class="pap-input" placeholder="<?php esc_attr_e('Caută produs, SKU...', 'papetarie-storefront'); ?>">
            <button type="button" class="pap-search-bar-submit" aria-label="<?php esc_attr_e('Caută', 'papetarie-storefront'); ?>">
              <?php echo papetarie_storefront_icon('search'); ?>
            </button>
          </div>
        </div>
        <?php papetarie_storefront_render_code_toggle("<input type=\"range\" class=\"pap-range\">\n<div class=\"pap-search-bar\">\n  <input class=\"pap-input\">\n  <button class=\"pap-search-bar-submit\">…</button>\n</div>"); ?>
      </div>
    </div>
  </div>

  <!-- ===================== COMPONENTE ===================== -->
  <div class="pap-sg-panel" data-sg-panel="componente">
    <div class="pap-sg-grid">
      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Badges & Pills', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row">
          <span class="pap-badge pap-badge--danger">−20%</span>
          <span class="pap-badge pap-badge--navy"><?php esc_html_e('Nou', 'papetarie-storefront'); ?></span>
          <span class="pap-badge pap-badge--orange"><?php esc_html_e('Ofertă', 'papetarie-storefront'); ?></span>
          <span class="pap-badge pap-badge--warning"><?php esc_html_e('Reducere', 'papetarie-storefront'); ?></span>
          <span class="pap-pill pap-pill--success">✓ <?php esc_html_e('Verificat', 'papetarie-storefront'); ?></span>
          <span class="pap-pill pap-pill--indigo"><?php esc_html_e('Arhivare', 'papetarie-storefront'); ?></span>
          <span class="pap-pill pap-pill--orange"><?php esc_html_e('Dosare', 'papetarie-storefront'); ?></span>
        </div>
        <?php papetarie_storefront_render_code_toggle("<span class=\"pap-badge pap-badge--danger\">−20%</span>\n<span class=\"pap-pill pap-pill--success\">✓ Verificat</span>\n<span class=\"pap-pill pap-pill--indigo\">Arhivare</span>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Stele rating', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <?php foreach ($sg_ratings as $sg_rating) : ?>
            <div class="pap-sg-row" style="gap:10px;">
              <span class="pap-product-rating" aria-hidden="true">
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                  <span class="pap-product-rating__star<?php echo $i <= $sg_rating['stars'] ? ' is-filled' : ''; ?>"><?php echo $star_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <?php endfor; ?>
              </span>
              <span style="color:#0d2e61;font-size:12px;font-weight:600;"><?php echo esc_html(number_format_i18n($sg_rating['score'], 1)); ?></span>
              <span class="pap-text-meta"><?php echo esc_html($sg_rating['count']); ?> <?php esc_html_e('recenzii', 'papetarie-storefront'); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php papetarie_storefront_render_code_toggle("<span class=\"pap-product-rating\">\n  <span class=\"pap-product-rating__star is-filled\">…</span>\n  …\n</span>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Alert / Toast messages', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-col">
          <div class="pap-alert pap-alert--success">
            <span class="pap-alert-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('check-circle'); ?></span>
            <?php esc_html_e('Produsul a fost adăugat în coș.', 'papetarie-storefront'); ?>
          </div>
          <div class="pap-alert pap-alert--info">
            <span class="pap-alert-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('info-circle'); ?></span>
            <?php esc_html_e('Prețurile includ TVA 19%.', 'papetarie-storefront'); ?>
          </div>
          <div class="pap-alert pap-alert--warning">
            <span class="pap-alert-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('alert-triangle'); ?></span>
            <?php esc_html_e('Stoc limitat — mai sunt 3 bucăți.', 'papetarie-storefront'); ?>
          </div>
          <div class="pap-alert pap-alert--danger">
            <span class="pap-alert-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('alert-circle'); ?></span>
            <?php esc_html_e('Eroare la procesarea comenzii.', 'papetarie-storefront'); ?>
          </div>
        </div>
        <?php papetarie_storefront_render_code_toggle("<div class=\"pap-alert pap-alert--success\">\n  <span class=\"pap-alert-icon\">…</span>\n  Produsul a fost adăugat în coș.\n</div>"); ?>
      </div>

      <div class="pap-sg-card">
        <p class="pap-sg-card-title"><?php esc_html_e('Preț produs', 'papetarie-storefront'); ?></p>
        <div class="pap-sg-row" style="align-items:flex-start;gap:32px;">
          <div class="pap-sg-price-block">
            <p class="pap-sg-price-label"><?php esc_html_e('Cu reducere', 'papetarie-storefront'); ?></p>
            <p class="pap-sg-price-value">12.99 <small>lei</small></p>
            <div class="pap-sg-price-old-row">
              <span class="pap-text-strike" style="font-size:12px;">16.50 lei</span>
              <span class="pap-badge pap-badge--danger" style="font-size:9px;padding:2px 5px;">−21%</span>
            </div>
          </div>
          <div class="pap-sg-price-block">
            <p class="pap-sg-price-label"><?php esc_html_e('Fără reducere', 'papetarie-storefront'); ?></p>
            <p class="pap-sg-price-value">24.99 <small>lei</small></p>
            <p class="pap-sg-price-stock">✓ <?php esc_html_e('În stoc', 'papetarie-storefront'); ?></p>
          </div>
        </div>
        <?php papetarie_storefront_render_code_toggle("<p class=\"pap-sg-price-value\">12.99 <small>lei</small></p>\n<span class=\"pap-text-strike\">16.50 lei</span>\n<span class=\"pap-badge pap-badge--danger\">−21%</span>"); ?>
      </div>
    </div>

    <div class="pap-sg-card" style="margin-top:12px;">
      <p class="pap-sg-card-title"><?php esc_html_e('Paginație', 'papetarie-storefront'); ?></p>
      <nav class="pap-pagination-nav" aria-label="<?php esc_attr_e('Exemplu paginație', 'papetarie-storefront'); ?>">
        <span class="page-numbers prev"><span class="pap-pagination-icon pap-pagination-icon--prev" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span></span>
        <a href="#" class="page-numbers">1</a>
        <span class="page-numbers current">2</span>
        <a href="#" class="page-numbers">3</a>
        <a href="#" class="page-numbers">4</a>
        <a href="#" class="page-numbers">5</a>
        <a href="#" class="page-numbers">6</a>
        <span class="page-numbers next"><span class="pap-pagination-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('chevron'); ?></span></span>
        <span class="pap-text-secondary" style="margin-left:8px;"><?php esc_html_e('Pagina 2 din 6', 'papetarie-storefront'); ?></span>
      </nav>
      <?php papetarie_storefront_render_code_toggle("<nav class=\"pap-pagination-nav\">\n  <span class=\"page-numbers prev disabled\">…</span>\n  <a class=\"page-numbers\" href=\"#\">1</a>\n  <span class=\"page-numbers current\">2</span>\n  …\n</nav>"); ?>
    </div>
  </div>
</main>

<script>
  (function () {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-sg-tab]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-sg-panel]'));

    function activate(name) {
      tabs.forEach(function (tab) {
        var isActive = tab.getAttribute('data-sg-tab') === name;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-sg-panel') === name);
      });
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var name = tab.getAttribute('data-sg-tab');
        activate(name);
        if (window.history && window.history.replaceState) {
          window.history.replaceState(null, '', '#' + name);
        }
      });
    });

    var initialHash = window.location.hash.replace('#', '');
    if (initialHash && tabs.some(function (tab) { return tab.getAttribute('data-sg-tab') === initialHash; })) {
      activate(initialHash);
    }
  })();
</script>

<?php wp_footer(); ?>
</body>
</html>
