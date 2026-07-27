<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Pagină de admin dedicată sincronizării Aperta - un rezumat simplu peste
 * Action Scheduler, filtrat doar pe grupul 'aperta-sync' (vezi
 * includes/aperta-sync.php), ca sa nu mai fie nevoie sa cauti prin ecranul
 * generic WooCommerce > Stare > Acțiuni programate.
 */

function papetarie_storefront_register_aperta_sync_page(): void
{
    add_menu_page(
        __('Sincronizare Aperta', 'papetarie-storefront'),
        __('Sincronizare Aperta', 'papetarie-storefront'),
        'manage_woocommerce',
        'papetarie-aperta-sync',
        'papetarie_storefront_render_aperta_sync_page',
        'dashicons-update',
        58
    );
}
add_action('admin_menu', 'papetarie_storefront_register_aperta_sync_page');

function papetarie_storefront_aperta_format_relative(?int $timestamp): string
{
    if (!$timestamp) {
        return __('niciodată', 'papetarie-storefront');
    }

    $diff = time() - $timestamp;
    $when = date_i18n('d.m.Y H:i', $timestamp);

    if ($diff < 0) {
        return sprintf(
            /* translators: %s: formatted date */
            __('programat pentru %s', 'papetarie-storefront'),
            $when
        );
    }

    return sprintf(
        /* translators: 1: human time diff, 2: formatted date */
        __('acum %1$s (%2$s)', 'papetarie-storefront'),
        human_time_diff($timestamp, time()),
        $when
    );
}

/**
 * Ca format_relative(), dar pentru o acțiune programată care încă nu a
 * rulat: dacă data e deja trecută, acțiunea e "restantă" (WP-Cron nu a
 * declanșat-o încă), nu "s-a întâmplat acum X ore".
 */
function papetarie_storefront_aperta_format_next_run(?int $timestamp): string
{
    if (!$timestamp) {
        return __('niciodată', 'papetarie-storefront');
    }

    $when = date_i18n('d.m.Y H:i', $timestamp);

    if ($timestamp > time()) {
        return sprintf(
            /* translators: %s: formatted date */
            __('programat pentru %s', 'papetarie-storefront'),
            $when
        );
    }

    $overdueBy = time() - $timestamp;

    // Restanta de cateva minute e normala (jitter), dar dupa un sfert de ora
    // e semn clar ca WP-Cron nu se declanseaza singur - spunem direct ce sa faca.
    if ($overdueBy > 15 * MINUTE_IN_SECONDS) {
        return sprintf(
            /* translators: 1: human time diff, 2: formatted date */
            __('restantă de %1$s (era programată la %2$s) — cronul automat nu s-a declanșat încă; apasă „Rulează acum” mai jos', 'papetarie-storefront'),
            human_time_diff($timestamp, time()),
            $when
        );
    }

    return sprintf(
        /* translators: %s: formatted date */
        __('pornește în curând (era programată la %s)', 'papetarie-storefront'),
        $when
    );
}

/**
 * Toate acțiunile "pending" pentru un hook, indiferent de argumente (relevant
 * pentru stoc, care are 10 sloturi/zi cu argumente diferite ['hour' => N]).
 *
 * @return array{count: int, next: ?int}
 */
function papetarie_storefront_aperta_pending_summary(string $hook): array
{
    if (!class_exists('ActionScheduler_Store')) {
        return ['count' => 0, 'next' => null];
    }

    $store = ActionScheduler_Store::instance();
    $ids = $store->query_actions([
        'hook' => $hook,
        'group' => 'aperta-sync',
        'status' => ActionScheduler_Store::STATUS_PENDING,
        'per_page' => 20,
        'orderby' => 'date',
        'order' => 'ASC',
    ]);

    if (empty($ids)) {
        return ['count' => 0, 'next' => null];
    }

    $action = $store->fetch_action((int) $ids[0]);
    $date = $action->get_schedule()->get_date();

    return [
        'count' => count($ids),
        'next' => $date ? $date->getTimestamp() : null,
    ];
}

function papetarie_storefront_render_aperta_sync_page(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_die(esc_html__('Nu ai permisiunea necesară pentru această pagină.', 'papetarie-storefront'));
    }

    $hasActionScheduler = class_exists('ActionScheduler_Store') && class_exists('ActionScheduler_Logger');

    $lastFullSync = (int) get_option('pap_aperta_last_full_sync', 0) ?: null;
    $lastStockSync = (int) get_option('pap_aperta_last_stock_sync', 0) ?: null;
    $productsSchedule = papetarie_storefront_aperta_pending_summary('pap_aperta_sync_products_start');
    $stockSchedule = papetarie_storefront_aperta_pending_summary('pap_aperta_sync_stock_start');

    global $wpdb;
    // Doar produsele-parinte (nu si variatiile individuale de culoare/marime,
    // care sunt post-uri separate cu propriul lor SKU) - numarul pe care il
    // recunoaste un administrator ca "un produs".
    $productCount = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_pap_aperta_cod_produs' AND p.post_type = 'product'"
    );
    $skuCount = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_pap_aperta_sku'"
    );
    // Produse din importul vechi (JSON static, fara SKU) - nu se pot reconcilia
    // fiabil cu feed-ul Aperta si de-asta e recomandat sa fie curatate inainte
    // de primul sync real, ca sa nu ramana duplicate pe site.
    $legacyCount = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_pap_import_key' AND p.post_type = 'product'
         AND NOT EXISTS (
             SELECT 1 FROM {$wpdb->postmeta} pm2
             WHERE pm2.post_id = p.ID AND pm2.meta_key IN ('_pap_aperta_cod_produs', '_pap_aperta_sku')
         )"
    );

    // Istoric permanent al rulărilor COMPLETE (nu bucăți individuale) - scris
    // de papetarie_storefront_aperta_record_history(), independent de
    // Action Scheduler (care își șterge singur acțiunile vechi după o vreme).
    $history = get_option('pap_aperta_sync_history', []);
    $history = is_array($history) ? array_reverse($history) : [];
    $history = array_slice($history, 0, 30);
    $retainedRunLogIds = get_option('pap_aperta_run_log_ids', []);
    $retainedRunLogIds = is_array($retainedRunLogIds) ? array_flip($retainedRunLogIds) : [];
    $productsProgress = papetarie_storefront_aperta_progress_get('products');
    $stockProgress = papetarie_storefront_aperta_progress_get('stock');
    ?>
    <div class="wrap pap-aperta-wrap">
      <h1><?php esc_html_e('Sincronizare Aperta', 'papetarie-storefront'); ?></h1>
      <p><?php esc_html_e('Rezumat simplu al sincronizării automate cu feed-urile Aperta (produse zilnic, stoc orar). Nu ține totul separat — e doar o vedere filtrată peste Action Scheduler.', 'papetarie-storefront'); ?></p>

      <?php if (!$hasActionScheduler) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('Action Scheduler (parte din WooCommerce) nu pare disponibil.', 'papetarie-storefront'); ?></p></div>
      <?php endif; ?>

      <div id="pap-aperta-message" class="notice inline" hidden><p></p></div>

      <div class="pap-aperta-cards">
        <div class="pap-aperta-card">
          <span class="pap-aperta-card-label"><?php esc_html_e('Ultima sincronizare completă de produse', 'papetarie-storefront'); ?></span>
          <strong><?php echo esc_html(papetarie_storefront_aperta_format_relative($lastFullSync)); ?></strong>
        </div>
        <div class="pap-aperta-card">
          <span class="pap-aperta-card-label"><?php esc_html_e('Ultima sincronizare de stoc', 'papetarie-storefront'); ?></span>
          <strong><?php echo esc_html(papetarie_storefront_aperta_format_relative($lastStockSync)); ?></strong>
        </div>
        <div class="pap-aperta-card">
          <span class="pap-aperta-card-label"><?php esc_html_e('Produse din Aperta pe site', 'papetarie-storefront'); ?></span>
          <strong><?php echo esc_html((string) $productCount); ?></strong>
          <span class="pap-aperta-card-note"><?php echo esc_html(sprintf(
              /* translators: %d: total SKU/variant count */
              __('%d variante individuale în total — dacă un produs are, de ex., 5 culori diferite, fiecare culoare are propriul cod (SKU) și e numărată separat aici', 'papetarie-storefront'),
              $skuCount
          )); ?></span>
        </div>
      </div>

      <?php if ($legacyCount > 0) : ?>
        <div class="notice notice-warning inline pap-aperta-legacy-notice">
          <p>
            <?php echo esc_html(sprintf(
                /* translators: %d: number of legacy products found */
                _n(
                    'Am găsit %d produs din importul vechi (fără SKU, nu poate fi reconciliat automat cu Aperta).',
                    'Am găsit %d produse din importul vechi (fără SKU, nu pot fi reconciliate automat cu Aperta).',
                    $legacyCount,
                    'papetarie-storefront'
                ),
                $legacyCount
            )); ?>
            <?php esc_html_e('Recomandat: mută-le în coșul de gunoi înainte de prima sincronizare Aperta, ca să nu rămână duplicate pe site.', 'papetarie-storefront'); ?>
          </p>
          <p>
            <button type="button" class="button button-secondary" id="pap-aperta-purge-legacy"><?php esc_html_e('Curăță produsele vechi', 'papetarie-storefront'); ?></button>
          </p>
        </div>
      <?php endif; ?>

      <h2><?php esc_html_e('Program de sincronizare', 'papetarie-storefront'); ?></h2>
      <table class="widefat striped pap-aperta-table">
        <thead>
          <tr>
            <th><?php esc_html_e('Flux', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Frecvență', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Durată estimată', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Afectează site-ul live?', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Următoarea rulare', 'papetarie-storefront'); ?></th>
            <th><?php esc_html_e('Acțiune', 'papetarie-storefront'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <strong><?php esc_html_e('Produse (catalog complet)', 'papetarie-storefront'); ?></strong>
              <details>
                <summary><?php esc_html_e('ce face', 'papetarie-storefront'); ?></summary>
                <ul class="pap-aperta-flow-facts">
                  <li><?php esc_html_e('Descarcă feed.csv (tot catalogul: nume, descriere, preț, categorie, brand, poze) și actualizează fiecare produs, cu prețul recalculat cu discountul din contract.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('Poze: NU redescarcă pozele deja existente — verifică după link-ul sursă și descarcă doar ce e nou sau schimbat.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('Stoc: nu este atins de acest job (asta face fluxul de Stoc, separat).', 'papetarie-storefront'); ?></li>
                </ul>
              </details>
            </td>
            <td><?php esc_html_e('1x/zi, ~3:10', 'papetarie-storefront'); ?></td>
            <td><?php esc_html_e('Variabil — mai lent la primele rulări (~3–5 ore), apoi mult mai rapid odată ce majoritatea produselor rămân neschimbate de la o noapte la alta (sar peste procesare dacă nu s-a schimbat nimic)', 'papetarie-storefront'); ?></td>
            <td><?php esc_html_e('Nu — site-ul rămâne funcțional tot timpul, produsele se actualizează treptat, unul câte unul, iar rularea e programată noaptea, în afara orelor cu trafic.', 'papetarie-storefront'); ?></td>
            <td><?php echo esc_html(papetarie_storefront_aperta_format_next_run($productsSchedule['next'])); ?></td>
            <td><button type="button" class="button button-primary" data-pap-run="products"><?php esc_html_e('Rulează acum', 'papetarie-storefront'); ?></button></td>
          </tr>
          <tr>
            <td>
              <strong><?php esc_html_e('Stoc', 'papetarie-storefront'); ?></strong>
              <details>
                <summary><?php esc_html_e('ce face', 'papetarie-storefront'); ?></summary>
                <ul class="pap-aperta-flow-facts">
                  <li><?php esc_html_e('Descarcă feed-stoc.csv (câte un cod/SKU pentru fiecare variantă de produs — ex. fiecare culoare are codul ei — și cantitatea aferentă) și actualizează STRICT cantitatea de stoc și starea „în stoc / fără stoc”.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('Nu atinge preț, descriere, categorie sau poze — doar cantitatea.', 'papetarie-storefront'); ?></li>
                  <li><?php esc_html_e('De ce de 10x/zi: copiază exact orele la care Aperta își actualizează propriul stoc.', 'papetarie-storefront'); ?></li>
                </ul>
              </details>
            </td>
            <td><?php echo esc_html(sprintf(
                /* translators: %d: number of daily runs */
                __('%d x/zi (1:30 și din oră în oră 9:30–17:30)', 'papetarie-storefront'),
                $stockSchedule['count'] ?: 10
            )); ?></td>
            <td><?php esc_html_e('Variabil — mai lent prima dată, apoi rapid (sare peste stocurile neschimbate)', 'papetarie-storefront'); ?></td>
            <td><?php esc_html_e('Nu — actualizare rapidă, fără impact vizibil.', 'papetarie-storefront'); ?></td>
            <td><?php echo esc_html(papetarie_storefront_aperta_format_next_run($stockSchedule['next'])); ?></td>
            <td><button type="button" class="button button-primary" data-pap-run="stock"><?php esc_html_e('Rulează acum', 'papetarie-storefront'); ?></button></td>
          </tr>
        </tbody>
      </table>
      <p class="description"><?php esc_html_e('„Rulează acum” pornește sincronizarea imediat, fără să aștepte programul (util pentru testare).', 'papetarie-storefront'); ?></p>

      <div class="pap-aperta-card" style="margin: 16px 0 24px;">
        <span class="pap-aperta-card-label"><?php esc_html_e('Actualizare rapidă filtre', 'papetarie-storefront'); ?></span>
        <p class="description" style="margin: 4px 0 10px;"><?php esc_html_e('Aplică pe produsele deja sincronizate cele mai noi reguli de extragere a filtrelor (culoare, format, material etc.), fără să refacă toată sincronizarea (fără poze, fără prețuri) — durează câteva secunde, nu ore. Folosește asta după ce urci o versiune nouă de cod cu reguli de filtre schimbate.', 'papetarie-storefront'); ?></p>
        <button type="button" class="button button-secondary" id="pap-aperta-backfill-attrs"><?php esc_html_e('Actualizează filtrele acum', 'papetarie-storefront'); ?></button>
        <p class="description" id="pap-aperta-backfill-status" style="margin-top: 8px;"></p>
      </div>

      <div class="pap-aperta-card" style="margin: 16px 0 24px;">
        <span class="pap-aperta-card-label"><?php esc_html_e('Ordinea meniului', 'papetarie-storefront'); ?></span>
        <p class="description" style="margin: 4px 0 10px;"><?php esc_html_e('Repară ordinea subcategoriilor din mega-meniu (coloanele grupate logic) și curăță categoriile create greșit de sincronizare dintr-o cale de feed coruptă. Rulează instant. Folosește asta după ce sincronizarea a creat subcategorii noi sau ordinea din meniu nu se potrivește cu local.', 'papetarie-storefront'); ?></p>
        <button type="button" class="button button-secondary" id="pap-aperta-fix-menu-order"><?php esc_html_e('Repară ordinea meniului', 'papetarie-storefront'); ?></button>
        <p class="description" id="pap-aperta-fix-menu-order-status" style="margin-top: 8px;"></p>
      </div>

      <h2><?php esc_html_e('Progres live', 'papetarie-storefront'); ?></h2>
      <div class="pap-aperta-progress-grid">
        <?php foreach (['products' => ['label' => __('Produse', 'papetarie-storefront'), 'data' => $productsProgress], 'stock' => ['label' => __('Stoc', 'papetarie-storefront'), 'data' => $stockProgress]] as $flow => $info) : ?>
          <div class="pap-aperta-progress-card" data-pap-progress="<?php echo esc_attr($flow); ?>">
            <div class="pap-aperta-progress-head">
              <strong><?php echo esc_html($info['label']); ?></strong>
              <span data-field="status-label">—</span>
            </div>
            <div class="pap-aperta-progress-bar">
              <div class="pap-aperta-progress-bar-fill" data-field="bar" style="width:0%"></div>
            </div>
            <p class="pap-aperta-progress-meta" data-field="meta">&nbsp;</p>
            <p class="pap-aperta-progress-summary" data-field="summary" hidden></p>
            <ul class="pap-aperta-progress-log" data-field="log"></ul>
          </div>
        <?php endforeach; ?>
      </div>

      <h2><?php esc_html_e('Istoric sincronizări', 'papetarie-storefront'); ?></h2>
      <p class="description"><?php esc_html_e('O rulare completă per rând (nu bucăți individuale) - rămâne aici chiar și după ce Action Scheduler își șterge singur acțiunile vechi.', 'papetarie-storefront'); ?></p>
      <?php if (empty($history)) : ?>
        <p><?php esc_html_e('Nicio sincronizare nu s-a finalizat încă.', 'papetarie-storefront'); ?></p>
      <?php else : ?>
        <table class="widefat striped pap-aperta-table">
          <thead>
            <tr>
              <th><?php esc_html_e('Data', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Flux', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Pornit', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Verificate', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Găsite pe site', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Schimbate', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Neschimbate', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Durată', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Detalii', 'papetarie-storefront'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($history as $row) :
                $runId = $row['run_id'] ?? '';
                $hasLog = $runId !== '' && isset($retainedRunLogIds[$runId]);
            ?>
              <tr>
                <td><?php echo esc_html(date_i18n('d.m.Y H:i', $row['finished_at'])); ?></td>
                <td><?php echo esc_html($row['flow'] === 'stock' ? __('Stoc', 'papetarie-storefront') : __('Produse', 'papetarie-storefront')); ?></td>
                <td><?php echo esc_html(($row['trigger'] ?? 'auto') === 'manual' ? __('Manual („Rulează acum”)', 'papetarie-storefront') : __('Automat (program)', 'papetarie-storefront')); ?></td>
                <td><?php echo esc_html((string) $row['total']); ?></td>
                <td><?php echo esc_html((string) $row['matched']); ?></td>
                <td><?php echo esc_html((string) $row['changed']); ?></td>
                <td><?php echo esc_html((string) $row['unchanged']); ?></td>
                <td><?php echo $row['duration'] !== null ? esc_html(floor($row['duration'] / 60) . 'm ' . ($row['duration'] % 60) . 's') : '—'; ?></td>
                <td>
                  <?php if ($hasLog) : ?>
                    <button type="button" class="button button-small" data-pap-view-log="<?php echo esc_attr($runId); ?>"><?php esc_html_e('Vezi loguri', 'papetarie-storefront'); ?></button>
                  <?php else : ?>
                    <span class="description">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php if ($hasLog) : ?>
                <tr class="pap-aperta-log-row" data-run-log-row="<?php echo esc_attr($runId); ?>" hidden>
                  <td colspan="9">
                    <ul class="pap-aperta-progress-log" data-run-log-content></ul>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="description"><?php esc_html_e('Log-ul detaliat (SKU + produs + ce s-a schimbat) e păstrat doar pentru ultimele 20 de rulări, ca să nu îngreuiem baza de date — rulările mai vechi rămân în tabel doar cu rezumatul.', 'papetarie-storefront'); ?></p>
      <?php endif; ?>
    </div>

    <style>
      .pap-aperta-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 14px;
        margin: 20px 0;
      }

      .pap-aperta-card {
        background: #fff;
        border: 1px solid #dcdcde;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        padding: 16px 18px;
        display: grid;
        gap: 6px;
      }

      .pap-aperta-card-label {
        color: #646970;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .02em;
      }

      .pap-aperta-card strong {
        font-size: 15px;
        color: #1d2327;
      }

      .pap-aperta-card-note {
        color: #8c8f94;
        font-size: 11px;
      }

      .pap-aperta-flow-facts {
        margin: 8px 0 0;
        padding-left: 18px;
        color: #50575e;
      }

      .pap-aperta-flow-facts li {
        margin-bottom: 6px;
      }

      .pap-aperta-table details summary {
        cursor: pointer;
        color: #2271b1;
        font-size: 12px;
      }

      .pap-aperta-table {
        margin-top: 12px;
      }

      .pap-aperta-progress-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 14px;
        margin: 16px 0 24px;
      }

      .pap-aperta-progress-card {
        background: #fff;
        border: 1px solid #dcdcde;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        padding: 16px 18px;
        min-width: 0;
      }

      @media (max-width: 782px) {
        .pap-aperta-progress-grid {
          grid-template-columns: 1fr;
        }
      }

      .pap-aperta-progress-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
      }

      .pap-aperta-progress-bar {
        background: #f0f0f1;
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
      }

      .pap-aperta-progress-bar-fill {
        background: #2271b1;
        height: 100%;
        width: 0;
        transition: width .4s ease;
      }

      .pap-aperta-progress-card[data-status="complete"] .pap-aperta-progress-bar-fill {
        background: #1a7a35;
      }

      .pap-aperta-progress-meta {
        margin: 8px 0 0;
        color: #50575e;
        font-size: 12px;
      }

      .pap-aperta-progress-summary {
        margin: 10px 0 0;
        padding: 8px 10px;
        background: #edfaef;
        border: 1px solid #d5eedb;
        border-radius: 3px;
        color: #1a7a35;
        font-size: 12px;
        font-weight: 600;
      }

      .pap-aperta-info-wrap {
        position: relative;
        display: inline-block;
      }

      .pap-aperta-info-icon {
        display: inline-block;
        cursor: pointer;
        font-weight: 700;
        color: #1a7a35;
        background: none;
        border: none;
        border-bottom: 1px dotted currentColor;
        padding: 0;
        font-size: 12px;
        line-height: 1;
      }

      .pap-aperta-info-box {
        position: absolute;
        z-index: 10;
        top: 100%;
        right: 0;
        margin-top: 6px;
        width: 260px;
        max-width: min(260px, 85vw);
        background: #1d2327;
        color: #fff;
        font-weight: 400;
        padding: 10px 12px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
        white-space: pre-line;
        line-height: 1.5;
      }

      @media (max-width: 480px) {
        .pap-aperta-info-box {
          right: auto;
          left: 50%;
          transform: translateX(-50%);
        }
      }

      .pap-aperta-progress-log {
        margin: 10px 0 0;
        padding: 0;
        list-style: none;
        max-height: 480px;
        overflow-y: auto;
        border-top: 1px solid #f0f0f1;
      }

      .pap-aperta-progress-log:empty {
        border-top: none;
      }

      .pap-aperta-progress-log li {
        padding: 5px 0;
        border-bottom: 1px solid #f6f7f7;
        font-size: 12px;
        color: #1d2327;
        overflow-wrap: break-word;
        word-break: break-word;
      }

      .pap-aperta-progress-log li span {
        color: #8c8f94;
        margin-right: 6px;
      }

      .pap-aperta-log-row td {
        background: #f6f7f7;
        padding: 12px 16px 16px;
      }

      .pap-aperta-log-row .pap-aperta-progress-log {
        border-top: none;
        max-height: 360px;
      }
    </style>

    <script>
      jQuery(function ($) {
        var $message = $('#pap-aperta-message');
        var nonce = '<?php echo esc_js(wp_create_nonce('pap-aperta-run-now')); ?>';
        var statusLabels = {
          idle: '<?php echo esc_js(__('Inactiv', 'papetarie-storefront')); ?>',
          starting: '<?php echo esc_js(__('Pornește…', 'papetarie-storefront')); ?>',
          running: '<?php echo esc_js(__('Rulează…', 'papetarie-storefront')); ?>',
          complete: '<?php echo esc_js(__('Finalizat', 'papetarie-storefront')); ?>'
        };
        var pollTimer = null;
        var siteProductCount = <?php echo (int) $productCount; ?>;
        var batchSizes = { products: 10, stock: 100 };
        var unitLabels = {
          products: '<?php echo esc_js(__('produse (din feed.csv)', 'papetarie-storefront')); ?>',
          stock: '<?php echo esc_js(__('variante/SKU-uri verificate (din feed-stoc.csv)', 'papetarie-storefront')); ?>'
        };

        function showMessage(type, text) {
          $message.removeClass('notice-success notice-error').addClass(type === 'success' ? 'notice-success' : 'notice-error');
          $message.find('p').text(text);
          $message.prop('hidden', false);
        }

        function timeAgo(startedAt) {
          if (!startedAt) {
            return '';
          }
          var seconds = Math.max(0, Math.floor(Date.now() / 1000) - startedAt);
          if (seconds < 60) {
            return seconds + 's';
          }
          var minutes = Math.floor(seconds / 60);
          var restSeconds = seconds % 60;
          return minutes + 'm ' + restSeconds + 's';
        }

        function renderProgress(flow, data) {
          var $card = $('[data-pap-progress="' + flow + '"]');
          if (!$card.length || !data) {
            return;
          }

          $card.attr('data-status', data.status);

          var percent = data.total > 0 ? Math.min(100, Math.round((data.processed / data.total) * 100)) : 0;
          $card.find('[data-field="bar"]').css('width', percent + '%');
          $card.find('[data-field="status-label"]').text(statusLabels[data.status] || data.status);

          var meta = '';
          if (data.status === 'idle') {
            meta = '<?php echo esc_js(__('Nicio rulare încă.', 'papetarie-storefront')); ?>';
          } else if (data.status === 'starting') {
            meta = '<?php echo esc_js(__('Se descarcă feed-ul…', 'papetarie-storefront')); ?>';
          } else if (data.status === 'complete') {
            var duration = (data.finished_at && data.started_at) ? (data.finished_at - data.started_at) : null;
            meta = data.processed + ' / ' + data.total + ' ' + (unitLabels[flow] || '');
            if (duration !== null) {
              meta += ' — <?php echo esc_js(__('finalizat în', 'papetarie-storefront')); ?> ' + Math.floor(duration / 60) + 'm ' + (duration % 60) + 's';
            }
          } else {
            meta = data.processed + ' / ' + data.total + ' ' + (unitLabels[flow] || '') + ' (' + percent + '%) — <?php echo esc_js(__('pornit acum', 'papetarie-storefront')); ?> ' + timeAgo(data.started_at)
              + ' — <?php echo esc_js(__('procesează în calupuri de', 'papetarie-storefront')); ?> ' + (batchSizes[flow] || '?') + ' <?php echo esc_js(__('simultan, o dată la ~5 secunde', 'papetarie-storefront')); ?>';
          }
          $card.find('[data-field="meta"]').text(meta);

          var $summary = $card.find('[data-field="summary"]');
          if (data.status === 'complete') {
            var summary;
            var tooltipText = '';
            if (flow === 'stock') {
              summary = '<?php echo esc_js(__('Am verificat', 'papetarie-storefront')); ?> ' + data.total + ' <?php echo esc_js(__('variante/SKU-uri (', 'papetarie-storefront')); ?>' + siteProductCount + '<?php echo esc_js(__(' produse) — actualizate:', 'papetarie-storefront')); ?> ' + data.changed
                + ', <?php echo esc_js(__('neschimbate:', 'papetarie-storefront')); ?> ' + data.unchanged + '.';
              tooltipText = '<?php echo esc_js(__('Din cele verificate,', 'papetarie-storefront')); ?> ' + data.matched + ' <?php echo esc_js(__('au fost găsite pe site.', 'papetarie-storefront')); ?>\n\n'
                + '<?php echo esc_js(__('Dacă un produs are mai multe culori sau mărimi, se numește produs variabil: e un singur produs pe site, dar fiecare culoare/mărime are propriul cod (SKU) și stoc — verificate separat.', 'papetarie-storefront')); ?>';
            } else {
              summary = '<?php echo esc_js(__('Am verificat', 'papetarie-storefront')); ?> ' + data.total + ' <?php echo esc_js(__('produse din feed.csv — create/actualizate cu modificări:', 'papetarie-storefront')); ?> ' + data.changed
                + ', <?php echo esc_js(__('neschimbate:', 'papetarie-storefront')); ?> ' + data.unchanged + '.';
              tooltipText = '<?php echo esc_js(__('„Actualizate cu modificări” înseamnă: fie e produs nou, fie prețul s-a schimbat.', 'papetarie-storefront')); ?>\n\n'
                + '<?php echo esc_js(__('Pentru un produs cu variante (culori/mărimi), e suficient ca o singură variantă să aibă preț nou ca tot produsul să conteze „schimbat”.', 'papetarie-storefront')); ?>';
            }
            $summary.empty().text(summary + ' ').append(
              $('<span class="pap-aperta-info-wrap">').append(
                $('<button type="button" class="pap-aperta-info-icon">ⓘ</button>'),
                $('<span class="pap-aperta-info-box" hidden>').text(tooltipText)
              )
            );
            $summary.prop('hidden', false);
          } else {
            $summary.prop('hidden', true);
          }

          var $log = $card.find('[data-field="log"]');
          $log.empty();
          var recent = (data.recent || []).slice().reverse();
          recent.forEach(function (item) {
            $log.append($('<li>').append($('<span>').text(item.sku)).append(document.createTextNode(item.name)));
          });
        }

        function isActive(data) {
          return data && (data.status === 'starting' || data.status === 'running');
        }

        function poll() {
          $.post(ajaxurl, {
            action: 'pap_aperta_get_progress',
            nonce: nonce
          }).done(function (response) {
            if (!response || !response.success) {
              return;
            }
            renderProgress('products', response.data.products);
            renderProgress('stock', response.data.stock);

            if (isActive(response.data.products) || isActive(response.data.stock)) {
              pollTimer = setTimeout(poll, 2500);
            } else {
              pollTimer = null;
            }
          });
        }

        function ensurePolling() {
          if (!pollTimer) {
            poll();
          }
        }

        $(document).on('click', '.pap-aperta-info-icon', function (event) {
          event.preventDefault();
          event.stopPropagation();
          var $box = $(this).siblings('.pap-aperta-info-box');
          var wasHidden = $box.prop('hidden');
          $('.pap-aperta-info-box').prop('hidden', true);
          $box.prop('hidden', !wasHidden);
        });

        $(document).on('click', function (event) {
          if (!$(event.target).closest('.pap-aperta-info-wrap').length) {
            $('.pap-aperta-info-box').prop('hidden', true);
          }
        });

        $(document).on('click', '[data-pap-view-log]', function () {
          var $button = $(this);
          var runId = $button.data('pap-view-log');
          var $row = $('[data-run-log-row="' + runId + '"]');
          var $content = $row.find('[data-run-log-content]');

          if (!$row.prop('hidden')) {
            $row.prop('hidden', true);
            $button.text('<?php echo esc_js(__('Vezi loguri', 'papetarie-storefront')); ?>');
            return;
          }

          $row.prop('hidden', false);
          $button.text('<?php echo esc_js(__('Ascunde loguri', 'papetarie-storefront')); ?>');

          if ($content.data('loaded')) {
            return;
          }

          $content.empty().append($('<li>').text('<?php echo esc_js(__('Se încarcă…', 'papetarie-storefront')); ?>'));

          $.post(ajaxurl, {
            action: 'pap_aperta_get_run_log',
            nonce: nonce,
            run_id: runId
          }).done(function (response) {
            $content.empty();
            var items = (response && response.success && response.data.items) ? response.data.items : [];
            if (!items.length) {
              $content.append($('<li>').text('<?php echo esc_js(__('Niciun detaliu salvat pentru această rulare.', 'papetarie-storefront')); ?>'));
              return;
            }
            items.forEach(function (item) {
              $content.append($('<li>').append($('<span>').text(item.sku)).append(document.createTextNode(item.name)));
            });
            $content.data('loaded', true);
          }).fail(function () {
            $content.empty().append($('<li>').text('<?php echo esc_js(__('Eroare la încărcare.', 'papetarie-storefront')); ?>'));
          });
        });

        function resetProgressCard(flow) {
          var $card = $('[data-pap-progress="' + flow + '"]');
          $card.attr('data-status', 'starting');
          $card.find('[data-field="bar"]').css('width', '0%');
          $card.find('[data-field="status-label"]').text(statusLabels.starting);
          $card.find('[data-field="meta"]').text('<?php echo esc_js(__('Se descarcă feed-ul…', 'papetarie-storefront')); ?>');
          $card.find('[data-field="summary"]').prop('hidden', true).empty();
          $card.find('[data-field="log"]').empty();
        }

        $('[data-pap-run]').on('click', function () {
          var $button = $(this);
          var flow = $button.data('pap-run');

          $button.prop('disabled', true);
          $message.prop('hidden', true);
          // Curatam imediat cardul (fara sa asteptam raspunsul serverului),
          // ca lista veche de SKU-uri sa nu ramana pe ecran in timp ce noua
          // rulare porneste - serverul oricum reseteaza "recent" la [] chiar
          // acum, dar UI-ul altfel ar arata lista veche pana la primul poll.
          resetProgressCard(flow);

          $.post(ajaxurl, {
            action: 'pap_aperta_run_now',
            nonce: nonce,
            flow: flow
          }).done(function (response) {
            if (response && response.success) {
              showMessage('success', response.data.message);
              ensurePolling();
              return;
            }
            showMessage('error', (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
          }).fail(function () {
            showMessage('error', '<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
          }).always(function () {
            $button.prop('disabled', false);
          });
        });

        $('#pap-aperta-purge-legacy').on('click', function () {
          var $button = $(this);
          if (!window.confirm('<?php echo esc_js(__('Sigur muți produsele vechi (fără SKU) în coșul de gunoi? Cele deja migrate prin Aperta nu sunt atinse.', 'papetarie-storefront')); ?>')) {
            return;
          }

          $button.prop('disabled', true);
          $message.prop('hidden', true);

          $.post(ajaxurl, {
            action: 'pap_aperta_purge_legacy',
            nonce: nonce
          }).done(function (response) {
            if (response && response.success) {
              showMessage('success', response.data.message);
              setTimeout(function () { window.location.reload(); }, 1500);
              return;
            }
            showMessage('error', (response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          }).fail(function () {
            showMessage('error', '<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          });
        });

        $('#pap-aperta-backfill-attrs').on('click', function () {
          var $button = $(this);
          var $status = $('#pap-aperta-backfill-status');

          $button.prop('disabled', true);
          $status.text('<?php echo esc_js(__('Pornit…', 'papetarie-storefront')); ?>');

          function processChunk(offset) {
            $.post(ajaxurl, {
              action: 'pap_aperta_backfill_attrs',
              nonce: nonce,
              offset: offset
            }).done(function (response) {
              if (!response || !response.success) {
                $status.text((response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
                $button.prop('disabled', false);
                return;
              }

              var data = response.data;
              var nextOffset = offset + data.processed;
              $status.text(nextOffset + ' / ' + data.total + ' <?php echo esc_js(__('produse verificate…', 'papetarie-storefront')); ?>');

              if (nextOffset < data.total && data.processed > 0) {
                processChunk(nextOffset);
              } else {
                $status.text('<?php echo esc_js(__('Gata —', 'papetarie-storefront')); ?> ' + data.total + ' <?php echo esc_js(__('produse verificate.', 'papetarie-storefront')); ?>');
                $button.prop('disabled', false);
              }
            }).fail(function () {
              $status.text('<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
              $button.prop('disabled', false);
            });
          }

          processChunk(0);
        });

        $('#pap-aperta-fix-menu-order').on('click', function () {
          var $button = $(this);
          var $status = $('#pap-aperta-fix-menu-order-status');

          $button.prop('disabled', true);
          $status.text('<?php echo esc_js(__('Se repară…', 'papetarie-storefront')); ?>');

          $.post(ajaxurl, {
            action: 'pap_aperta_fix_menu_order',
            nonce: nonce
          }).done(function (response) {
            if (response && response.success) {
              $status.text(response.data.message);
            } else {
              $status.text((response && response.data && response.data.message) ? response.data.message : '<?php echo esc_js(__('A apărut o eroare.', 'papetarie-storefront')); ?>');
            }
            $button.prop('disabled', false);
          }).fail(function () {
            $status.text('<?php echo esc_js(__('A apărut o eroare de conexiune.', 'papetarie-storefront')); ?>');
            $button.prop('disabled', false);
          });
        });

        // O verificare imediată la deschiderea paginii, ca să reflecte o
        // rulare deja în curs (pornită din altă filă sau automat).
        poll();
      });
    </script>
    <?php
}

function papetarie_storefront_aperta_ajax_run_now(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $flow = isset($_POST['flow']) ? sanitize_key(wp_unslash($_POST['flow'])) : '';

    if (!in_array($flow, ['products', 'stock'], true) || !function_exists('as_enqueue_async_action')) {
        wp_send_json_error(['message' => __('Flux necunoscut.', 'papetarie-storefront')], 400);
    }

    if (!papetarie_storefront_aperta_progress_mark_starting($flow)) {
        wp_send_json_error(['message' => __('Rulează deja o sincronizare de acest tip — așteaptă să termine (vezi progresul mai jos).', 'papetarie-storefront')], 409);
    }

    $hook = $flow === 'products' ? 'pap_aperta_sync_products_start' : 'pap_aperta_sync_stock_start';
    as_enqueue_async_action($hook, ['trigger' => 'manual'], 'aperta-sync');

    wp_send_json_success(['message' => __('Pornit — vezi progresul mai jos.', 'papetarie-storefront')]);
}
add_action('wp_ajax_pap_aperta_run_now', 'papetarie_storefront_aperta_ajax_run_now');

function papetarie_storefront_aperta_ajax_get_progress(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    // In acest mediu local, WP-Cron nu se declanseaza singur (vezi nota din
    // pagina) - fiecare verificare de progres "impinge" manual coada Action
    // Scheduler mai departe, ca sincronizarea sa avanseze real cat timp
    // pagina e deschisa. Pe serverul real (cu WP-Cron functional), acest
    // apel e redundant/inofensiv - Action Scheduler isi gestioneaza singur
    // procesarea, iar aici doar o "grabeste" un pic.
    if (class_exists('ActionScheduler_QueueRunner')) {
        ActionScheduler_QueueRunner::instance()->run();
    }

    wp_send_json_success([
        'products' => papetarie_storefront_aperta_progress_get('products'),
        'stock' => papetarie_storefront_aperta_progress_get('stock'),
    ]);
}
add_action('wp_ajax_pap_aperta_get_progress', 'papetarie_storefront_aperta_ajax_get_progress');

function papetarie_storefront_aperta_ajax_get_run_log(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $runId = isset($_POST['run_id']) ? sanitize_text_field(wp_unslash($_POST['run_id'])) : '';

    if ($runId === '') {
        wp_send_json_error(['message' => __('Rulare necunoscută.', 'papetarie-storefront')], 400);
    }

    wp_send_json_success(['items' => papetarie_storefront_aperta_get_run_log($runId)]);
}
add_action('wp_ajax_pap_aperta_get_run_log', 'papetarie_storefront_aperta_ajax_get_run_log');

/**
 * Muta in cosul de gunoi produsele importate din vechiul JSON static
 * (_pap_import_key, fara SKU) - varianta din admin a tools/purge-legacy-import-products.php,
 * declansabila cu un click, fara acces la baza de date sau linie de comanda.
 *
 * Notă: pe unele instalări wp_delete_post($id, false) s-a comportat ca ștergere
 * definitivă în loc de coș de gunoi (observat local) - dacă asta se întâmplă,
 * comportamentul WordPress-ului de bază e responsabil, nu acest cod.
 */
function papetarie_storefront_aperta_ajax_purge_legacy(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    if (!function_exists('wc_get_product')) {
        wp_send_json_error(['message' => __('WooCommerce nu pare încărcat.', 'papetarie-storefront')], 400);
    }

    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'any',
        'meta_key' => '_pap_import_key',
        'fields' => 'ids',
        'posts_per_page' => -1,
    ]);

    $trashed = 0;
    foreach ($ids as $id) {
        if (get_post_meta($id, '_pap_aperta_cod_produs', true) || get_post_meta($id, '_pap_aperta_sku', true)) {
            continue;
        }

        wp_delete_post($id, false);
        $trashed++;
    }

    wp_send_json_success([
        'message' => sprintf(
            /* translators: %d: number of products moved to trash */
            __('%d produse mutate în coșul de gunoi.', 'papetarie-storefront'),
            $trashed
        ),
    ]);
}
add_action('wp_ajax_pap_aperta_purge_legacy', 'papetarie_storefront_aperta_ajax_purge_legacy');

function papetarie_storefront_aperta_ajax_backfill_attrs(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    if (!function_exists('papetarie_storefront_aperta_backfill_attributes_chunk')) {
        wp_send_json_error(['message' => __('Funcția de actualizare nu e disponibilă.', 'papetarie-storefront')], 400);
    }

    $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;

    wp_send_json_success(papetarie_storefront_aperta_backfill_attributes_chunk($offset, 300));
}
add_action('wp_ajax_pap_aperta_backfill_attrs', 'papetarie_storefront_aperta_ajax_backfill_attrs');

function papetarie_storefront_aperta_ajax_fix_menu_order(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    if (!function_exists('papetarie_storefront_aperta_fix_menu_order')) {
        wp_send_json_error(['message' => __('Funcția de reparare nu e disponibilă.', 'papetarie-storefront')], 400);
    }

    $result = papetarie_storefront_aperta_fix_menu_order();

    $message = sprintf(
        /* translators: %d: number of ordered subcategories */
        __('%d subcategorii reordonate.', 'papetarie-storefront'),
        $result['ordered']
    );

    if (!empty($result['reparented'])) {
        $message .= ' ' . sprintf(
            /* translators: %s: comma-separated slug list */
            __('Reparentate din categoria coruptă: %s.', 'papetarie-storefront'),
            implode(', ', $result['reparented'])
        );
    }

    if (!empty($result['missing'])) {
        $message .= ' ' . sprintf(
            /* translators: %d: number of slugs not found */
            __('%d slug-uri din lista de ordonare nu au fost găsite (posibil redenumite de sincronizare) — verifică manual.', 'papetarie-storefront'),
            count($result['missing'])
        );
    }

    wp_send_json_success(['message' => $message, 'result' => $result]);
}
add_action('wp_ajax_pap_aperta_fix_menu_order', 'papetarie_storefront_aperta_ajax_fix_menu_order');

/**
 * Curatenie unica: muta in cosul de gunoi produsele care nu se regasesc in
 * lista curatata manual (Excel Lavinia) - identificate prin cod_produs/sku,
 * NU prin post ID (ID-urile difera intre local si staging). Nu e un
 * mecanism permanent, doar un instrument de rulat o data prin AJAX direct
 * (fara buton in UI), la fel ca celelalte actiuni din acest fisier.
 */
function papetarie_storefront_aperta_ajax_trash_by_code(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $itemsJson = isset($_POST['items']) ? wp_unslash((string) $_POST['items']) : '';
    $items = json_decode($itemsJson, true);

    if (!is_array($items)) {
        wp_send_json_error(['message' => __('Listă invalidă.', 'papetarie-storefront')], 400);
    }

    $trashed = 0;
    $notFound = 0;
    $alreadyTrashed = 0;

    foreach ($items as $item) {
        $codProdus = trim((string) ($item['cod_produs'] ?? ''));
        $sku = trim((string) ($item['sku'] ?? ''));

        $productId = null;

        if ($codProdus !== '') {
            $productId = papetarie_storefront_aperta_find_parent_by_cod_produs($codProdus);
        }

        if ($productId === null && $sku !== '') {
            $foundId = papetarie_storefront_aperta_find_by_sku_meta($sku);
            if ($foundId !== null) {
                $post = get_post($foundId);
                $productId = ($post && $post->post_parent) ? (int) $post->post_parent : $foundId;
            }
        }

        if ($productId === null) {
            $notFound++;
            continue;
        }

        $post = get_post($productId);
        if (!$post || $post->post_status === 'trash') {
            $alreadyTrashed++;
            continue;
        }

        wp_trash_post($productId);
        $trashed++;
    }

    wp_send_json_success([
        'trashed' => $trashed,
        'not_found' => $notFound,
        'already_trashed' => $alreadyTrashed,
        'total' => count($items),
    ]);
}
add_action('wp_ajax_pap_aperta_trash_by_code', 'papetarie_storefront_aperta_ajax_trash_by_code');

/**
 * Restaureaza din cosul de gunoi produse identificate prin cod_produs -
 * folosit pentru cazurile 100% sigure (nume identic cu un produs deja
 * pastrat = variantа de culoare a aceleiasi linii), nu pentru revizuire
 * manuala pe scor.
 */
function papetarie_storefront_aperta_ajax_restore_by_code(): void
{
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Nu ai permisiunea necesară.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap-aperta-run-now', 'nonce');

    $codesJson = isset($_POST['codes']) ? wp_unslash((string) $_POST['codes']) : '';
    $codes = json_decode($codesJson, true);

    if (!is_array($codes)) {
        wp_send_json_error(['message' => __('Listă invalidă.', 'papetarie-storefront')], 400);
    }

    $restored = 0;
    $notFound = 0;

    foreach ($codes as $codProdus) {
        $codProdus = trim((string) $codProdus);
        if ($codProdus === '') {
            continue;
        }

        // NU folosim papetarie_storefront_aperta_find_parent_by_cod_produs()
        // aici - foloseste post_status => 'any', care EXCLUDE 'trash' (o
        // particularitate WP_Query binecunoscuta). Cautam explicit inclusiv
        // in cosul de gunoi, fiindca exact acolo se afla produsele de restaurat.
        $ids = get_posts([
            'post_type' => 'product',
            'post_status' => ['trash', 'publish', 'draft', 'pending', 'private'],
            'meta_key' => '_pap_aperta_cod_produs',
            'meta_value' => $codProdus,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]);
        $productId = isset($ids[0]) ? (int) $ids[0] : null;

        if ($productId === null) {
            $notFound++;
            continue;
        }

        $post = get_post($productId);
        if ($post && $post->post_status === 'trash') {
            wp_untrash_post($productId);
            wp_update_post(['ID' => $productId, 'post_status' => 'publish']);
        }

        // Produsele variabile (cu variatii - ex. culoare/liniatura) au nevoie
        // ca TOATE variatiile lor sa fie restaurate odata cu parintele, altfel
        // produsul apare pe site fara nicio varianta cumparabila. Variatiile
        // sunt post_type separat (product_variation), nu se restaureaza
        // automat cand restauram parintele.
        global $wpdb;
        $variationIds = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation' AND post_parent = %d AND post_status = 'trash'",
            $productId
        ));
        foreach ($variationIds as $variationId) {
            wp_untrash_post((int) $variationId);
            wp_update_post(['ID' => (int) $variationId, 'post_status' => 'publish']);
        }

        $restored++;
    }

    wp_send_json_success([
        'restored' => $restored,
        'not_found' => $notFound,
        'total' => count($codes),
    ]);
}
add_action('wp_ajax_pap_aperta_restore_by_code', 'papetarie_storefront_aperta_ajax_restore_by_code');
