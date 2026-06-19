<?php

defined('ABSPATH') || exit;

get_header();
?>
<main id="primary" class="site-main pap-styleguide-page">
  <section class="pap-shell pap-styleguide-tabs" aria-label="<?php esc_attr_e('Style guide sections', 'papetarie-storefront'); ?>" data-styleguide-tabs>
    <div class="pap-styleguide-tabs-nav" role="tablist" aria-label="<?php esc_attr_e('Style guide tabs', 'papetarie-storefront'); ?>">
      <button class="pap-styleguide-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="pap-styleguide-panel-typography" data-tab="typography">
        <?php esc_html_e('Typography', 'papetarie-storefront'); ?>
      </button>
      <button class="pap-styleguide-tab" type="button" role="tab" aria-selected="false" aria-controls="pap-styleguide-panel-buttons" data-tab="buttons">
        <?php esc_html_e('Buttons & Links', 'papetarie-storefront'); ?>
      </button>
      <button class="pap-styleguide-tab" type="button" role="tab" aria-selected="false" aria-controls="pap-styleguide-panel-inputs" data-tab="inputs">
        <?php esc_html_e('Inputs', 'papetarie-storefront'); ?>
      </button>
      <button class="pap-styleguide-tab" type="button" role="tab" aria-selected="false" aria-controls="pap-styleguide-panel-cards" data-tab="cards">
        <?php esc_html_e('Cards', 'papetarie-storefront'); ?>
      </button>
      <button class="pap-styleguide-tab" type="button" role="tab" aria-selected="false" aria-controls="pap-styleguide-panel-alerts" data-tab="alerts">
        <?php esc_html_e('Alerts', 'papetarie-storefront'); ?>
      </button>
      <button class="pap-styleguide-tab" type="button" role="tab" aria-selected="false" aria-controls="pap-styleguide-panel-tables" data-tab="tables">
        <?php esc_html_e('Tables', 'papetarie-storefront'); ?>
      </button>
      <button class="pap-styleguide-tab" type="button" role="tab" aria-selected="false" aria-controls="pap-styleguide-panel-components" data-tab="components">
        <?php esc_html_e('Components', 'papetarie-storefront'); ?>
      </button>
    </div>

    <div class="pap-styleguide-tab-panels">
      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--typography" id="pap-styleguide-panel-typography" role="tabpanel" aria-labelledby="pap-styleguide-tab-typography" data-panel="typography">
        <div class="pap-styleguide-table-wrap">
          <table class="pap-styleguide-table">
            <colgroup>
              <col class="pap-styleguide-col-type">
              <col class="pap-styleguide-col-weight">
              <col class="pap-styleguide-col-size">
              <col class="pap-styleguide-col-size">
              <col class="pap-styleguide-col-size">
              <col class="pap-styleguide-col-family">
              <col class="pap-styleguide-col-color">
              <col class="pap-styleguide-col-line">
              <col class="pap-styleguide-col-letter">
              <col class="pap-styleguide-col-use">
            </colgroup>
            <thead>
              <tr>
                <th><?php esc_html_e('Type', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Weight', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Desktop', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Tablet', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Mobile', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Font family', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Color', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Line-height', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Letter-spacing', 'papetarie-storefront'); ?></th>
                <th><?php esc_html_e('Use', 'papetarie-storefront'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('H1', 'papetarie-storefront'); ?></span>
                    <h1 class="pap-styleguide-sample pap-styleguide-sample--h1"><?php esc_html_e('Heading 1', 'papetarie-storefront'); ?></h1>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">700</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">32px / 40px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">28px / 36px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">24px / 32px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#17324D</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.25</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">-0.02em</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Titlu principal', 'papetarie-storefront'); ?></td>
              </tr>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('H2', 'papetarie-storefront'); ?></span>
                    <h2 class="pap-styleguide-sample pap-styleguide-sample--h2"><?php esc_html_e('Heading 2', 'papetarie-storefront'); ?></h2>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">700</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">24px / 32px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">22px / 30px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">20px / 28px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#17324D</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.33</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">-0.02em</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Titluri secțiuni', 'papetarie-storefront'); ?></td>
              </tr>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('H3', 'papetarie-storefront'); ?></span>
                    <h3 class="pap-styleguide-sample pap-styleguide-sample--h3"><?php esc_html_e('Heading 3', 'papetarie-storefront'); ?></h3>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">700</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">20px / 28px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">18px / 26px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">18px / 24px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#17324D</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.3</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">-0.02em</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Widget-uri și blocuri', 'papetarie-storefront'); ?></td>
              </tr>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('H4', 'papetarie-storefront'); ?></span>
                    <h4 class="pap-styleguide-sample pap-styleguide-sample--h4"><?php esc_html_e('Heading 4', 'papetarie-storefront'); ?></h4>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">600</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">18px / 24px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">17px / 24px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">16px / 22px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#17324D</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.33</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">-0.01em</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Subtitluri și carduri', 'papetarie-storefront'); ?></td>
              </tr>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('P', 'papetarie-storefront'); ?></span>
                    <p class="pap-styleguide-sample pap-styleguide-sample--p"><?php esc_html_e('Paragraph', 'papetarie-storefront'); ?></p>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">400</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">14px / 22px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">14px / 22px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">14px / 20px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#17324D</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.6</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">0</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Text și descrieri', 'papetarie-storefront'); ?></td>
              </tr>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('Small', 'papetarie-storefront'); ?></span>
                    <small class="pap-styleguide-sample pap-styleguide-sample--small"><?php esc_html_e('Small text', 'papetarie-storefront'); ?></small>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">400</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">13px / 20px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">13px / 20px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">12px / 18px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#5B6B80</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.54</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">0</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Texte secundare', 'papetarie-storefront'); ?></td>
              </tr>
              <tr>
                <td>
                  <div class="pap-styleguide-type-sample">
                    <span class="pap-styleguide-type-label"><?php esc_html_e('Label', 'papetarie-storefront'); ?></span>
                    <label class="pap-styleguide-sample pap-styleguide-sample--label"><?php esc_html_e('Label text', 'papetarie-storefront'); ?></label>
                  </div>
                </td>
                <td data-label="<?php esc_attr_e('Weight', 'papetarie-storefront'); ?>">600</td>
                <td data-label="<?php esc_attr_e('Desktop', 'papetarie-storefront'); ?>">12px / 16px</td>
                <td data-label="<?php esc_attr_e('Tablet', 'papetarie-storefront'); ?>">12px / 16px</td>
                <td data-label="<?php esc_attr_e('Mobile', 'papetarie-storefront'); ?>">12px / 16px</td>
                <td data-label="<?php esc_attr_e('Font family', 'papetarie-storefront'); ?>">Inter, sans-serif</td>
                <td data-label="<?php esc_attr_e('Color', 'papetarie-storefront'); ?>">#6D7788</td>
                <td data-label="<?php esc_attr_e('Line-height', 'papetarie-storefront'); ?>">1.33</td>
                <td data-label="<?php esc_attr_e('Letter-spacing', 'papetarie-storefront'); ?>">0.08em</td>
                <td data-label="<?php esc_attr_e('Use', 'papetarie-storefront'); ?>"><?php esc_html_e('Tabele, SKU, categorii', 'papetarie-storefront'); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--buttons" id="pap-styleguide-panel-buttons" role="tabpanel" aria-labelledby="pap-styleguide-tab-buttons" data-panel="buttons" hidden>
        <div class="pap-styleguide-empty-card">
          <p class="pap-styleguide-empty-card__eyebrow"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
          <h2><?php esc_html_e('Buttons & links', 'papetarie-storefront'); ?></h2>
          <p class="pap-styleguide-empty-card__soon"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
        </div>
      </section>

      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--inputs" id="pap-styleguide-panel-inputs" role="tabpanel" aria-labelledby="pap-styleguide-tab-inputs" data-panel="inputs" hidden>
        <div class="pap-styleguide-empty-card">
          <p class="pap-styleguide-empty-card__eyebrow"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
          <h2><?php esc_html_e('Inputs', 'papetarie-storefront'); ?></h2>
          <p class="pap-styleguide-empty-card__soon"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
        </div>
      </section>

      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--cards" id="pap-styleguide-panel-cards" role="tabpanel" aria-labelledby="pap-styleguide-tab-cards" data-panel="cards" hidden>
        <div class="pap-styleguide-empty-card">
          <p class="pap-styleguide-empty-card__eyebrow"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
          <h2><?php esc_html_e('Cards', 'papetarie-storefront'); ?></h2>
          <p class="pap-styleguide-empty-card__soon"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
        </div>
      </section>

      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--alerts" id="pap-styleguide-panel-alerts" role="tabpanel" aria-labelledby="pap-styleguide-tab-alerts" data-panel="alerts" hidden>
        <div class="pap-styleguide-empty-card">
          <p class="pap-styleguide-empty-card__eyebrow"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
          <h2><?php esc_html_e('Alerts', 'papetarie-storefront'); ?></h2>
          <p class="pap-styleguide-empty-card__soon"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
        </div>
      </section>

      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--tables" id="pap-styleguide-panel-tables" role="tabpanel" aria-labelledby="pap-styleguide-tab-tables" data-panel="tables" hidden>
        <div class="pap-styleguide-empty-card">
          <p class="pap-styleguide-empty-card__eyebrow"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
          <h2><?php esc_html_e('Tables', 'papetarie-storefront'); ?></h2>
          <p class="pap-styleguide-empty-card__soon"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
        </div>
      </section>

      <section class="pap-styleguide-tab-panel pap-styleguide-tab-panel--components" id="pap-styleguide-panel-components" role="tabpanel" aria-labelledby="pap-styleguide-tab-components" data-panel="components" hidden>
        <div class="pap-styleguide-empty-card">
          <p class="pap-styleguide-empty-card__eyebrow"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
          <h2><?php esc_html_e('Components', 'papetarie-storefront'); ?></h2>
          <p class="pap-styleguide-empty-card__soon"><?php esc_html_e('Coming soon', 'papetarie-storefront'); ?></p>
        </div>
      </section>
    </div>
  </section>
</main>
<script>
(function () {
  const root = document.querySelector('[data-styleguide-tabs]');
  if (!root) return;
  const tabs = Array.from(root.querySelectorAll('[role="tab"]'));
  const panels = Array.from(root.querySelectorAll('[role="tabpanel"]'));

  const activate = (name) => {
    tabs.forEach((tab) => {
      const active = tab.dataset.tab === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
    });

    panels.forEach((panel) => {
      const active = panel.dataset.panel === name;
      panel.hidden = !active;
    });
  };

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => activate(tab.dataset.tab));
  });

  activate((tabs.find((tab) => tab.classList.contains('is-active')) || tabs[0]).dataset.tab);
})();
</script>
<?php
get_footer();
