<?php
/**
 * Integrare Oblio: generare factura/proforma pe o comanda + trimitere unui
 * singur email catre client (AWB + document PDF atasat), in loc de doua
 * emailuri separate (nota WooCommerce + emailul automat Oblio).
 */

if (!defined('ABSPATH')) {
    exit;
}

const PAP_OBLIO_API_BASE = 'https://www.oblio.eu/api';
const PAP_OBLIO_TOKEN_TRANSIENT = 'pap_oblio_access_token';

function papetarie_storefront_oblio_settings(): array
{
    return get_option('pap_oblio_settings', [
        'email' => '',
        'secret' => '',
        'cif' => '',
        'series' => '',
        'doc_type' => 'proforma',
    ]);
}

function papetarie_storefront_oblio_is_configured(): bool
{
    $s = papetarie_storefront_oblio_settings();

    return $s['email'] !== '' && $s['secret'] !== '' && $s['cif'] !== '' && $s['series'] !== '';
}

/* ---------------------------------------------------------------------
 * Pagina de setari (Oblio email + secret + CIF + serie + tip document)
 * ------------------------------------------------------------------- */

function papetarie_storefront_oblio_admin_menu(): void
{
    add_submenu_page(
        'woocommerce',
        'Integrare Oblio',
        'Oblio',
        'manage_woocommerce',
        'pap-oblio-settings',
        'papetarie_storefront_oblio_settings_page'
    );
}
add_action('admin_menu', 'papetarie_storefront_oblio_admin_menu');

function papetarie_storefront_oblio_settings_page(): void
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    if (isset($_POST['pap_oblio_save']) && check_admin_referer('pap_oblio_settings')) {
        $settings = [
            'email' => sanitize_email(wp_unslash($_POST['pap_oblio_email'] ?? '')),
            'secret' => sanitize_text_field(wp_unslash($_POST['pap_oblio_secret'] ?? '')),
            'cif' => sanitize_text_field(wp_unslash($_POST['pap_oblio_cif'] ?? '')),
            'series' => sanitize_text_field(wp_unslash($_POST['pap_oblio_series'] ?? '')),
            'doc_type' => in_array($_POST['pap_oblio_doc_type'] ?? '', ['proforma', 'invoice'], true)
                ? $_POST['pap_oblio_doc_type']
                : 'proforma',
        ];
        // Daca secretul e lasat gol la re-salvare, pastram valoarea existenta
        // (campul e afisat mascat, nu re-trimitem valoarea reala in formular).
        if ($settings['secret'] === '') {
            $existing = papetarie_storefront_oblio_settings();
            $settings['secret'] = $existing['secret'];
        }
        update_option('pap_oblio_settings', $settings);
        delete_transient(PAP_OBLIO_TOKEN_TRANSIENT);
        echo '<div class="notice notice-success"><p>Setari salvate.</p></div>';
    }

    $s = papetarie_storefront_oblio_settings();
    $secretMasked = $s['secret'] !== '' ? str_repeat('•', 20) . substr($s['secret'], -6) : '';
    ?>
    <div class="wrap">
        <h1>Integrare Oblio</h1>
        <p>Conectare cont Oblio pentru generarea automata de facturi/proforme la comenzi, cu AWB inclus intr-un singur email catre client.</p>
        <form method="post">
            <?php wp_nonce_field('pap_oblio_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="pap_oblio_email">Email cont Oblio</label></th>
                    <td><input type="email" id="pap_oblio_email" name="pap_oblio_email" class="regular-text" value="<?php echo esc_attr($s['email']); ?>" placeholder="ex: contact@artflex.ro"></td>
                </tr>
                <tr>
                    <th><label for="pap_oblio_secret">API Secret</label></th>
                    <td>
                        <input type="password" id="pap_oblio_secret" name="pap_oblio_secret" class="regular-text" placeholder="<?php echo $secretMasked !== '' ? esc_attr($secretMasked) . ' (lasa gol ca sa pastrezi valoarea curenta)' : 'Setari > Date generale > Date Cont, in Oblio'; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="pap_oblio_cif">CIF firma</label></th>
                    <td><input type="text" id="pap_oblio_cif" name="pap_oblio_cif" class="regular-text" value="<?php echo esc_attr($s['cif']); ?>" placeholder="ex: RO49485790"></td>
                </tr>
                <tr>
                    <th><label for="pap_oblio_series">Serie document</label></th>
                    <td>
                        <input type="text" id="pap_oblio_series" name="pap_oblio_series" class="regular-text" value="<?php echo esc_attr($s['series']); ?>" placeholder="ex: TESTNOTIX">
                        <p class="description">Trebuie sa existe deja in Oblio (Setari &gt; Serii documente), cu tipul potrivit (Proforma sau Factura) pentru ce alegi mai jos.</p>
                    </td>
                </tr>
                <tr>
                    <th>Tip document implicit</th>
                    <td>
                        <label><input type="radio" name="pap_oblio_doc_type" value="proforma" <?php checked($s['doc_type'], 'proforma'); ?>> Proforma (recomandat pentru testare — nu-i document fiscal)</label><br>
                        <label><input type="radio" name="pap_oblio_doc_type" value="invoice" <?php checked($s['doc_type'], 'invoice'); ?>> Factura (document fiscal real)</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Salveaza', 'primary', 'pap_oblio_save'); ?>
        </form>
        <?php if (papetarie_storefront_oblio_is_configured()) : ?>
            <p style="color: #0a7d3f;">✓ Configurat — butonul de generare apare acum pe pagina fiecarei comenzi.</p>
        <?php else : ?>
            <p style="color: #a33;">Nu-i complet configurat inca — completeaza toate campurile de mai sus.</p>
        <?php endif; ?>
    </div>
    <?php
}

/* ---------------------------------------------------------------------
 * Client API Oblio (auth + emitere document + descarcare PDF)
 * ------------------------------------------------------------------- */

function papetarie_storefront_oblio_get_access_token(): string|WP_Error
{
    $cached = get_transient(PAP_OBLIO_TOKEN_TRANSIENT);
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }

    $s = papetarie_storefront_oblio_settings();

    $response = wp_remote_post(PAP_OBLIO_API_BASE . '/authorize/token', [
        'timeout' => 20,
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'body' => [
            'client_id' => $s['email'],
            'client_secret' => $s['secret'],
        ],
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($body['access_token'])) {
        return new WP_Error('pap_oblio_auth_failed', 'Autentificare Oblio esuata: ' . wp_remote_retrieve_body($response));
    }

    $ttl = isset($body['expires_in']) ? max(60, (int) $body['expires_in'] - 60) : 3000;
    set_transient(PAP_OBLIO_TOKEN_TRANSIENT, $body['access_token'], $ttl);

    return $body['access_token'];
}

function papetarie_storefront_oblio_default_vat(): array
{
    $cached = get_transient('pap_oblio_vat_default');
    if (is_array($cached)) {
        return $cached;
    }

    $token = papetarie_storefront_oblio_get_access_token();
    $s = papetarie_storefront_oblio_settings();
    if (is_wp_error($token)) {
        return ['name' => 'Scutit', 'percentage' => 0];
    }

    $response = wp_remote_get(PAP_OBLIO_API_BASE . '/nomenclature/vat_rates?cif=' . urlencode($s['cif']), [
        'timeout' => 15,
        'headers' => ['Authorization' => 'Bearer ' . $token],
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $rates = $body['data'] ?? [];
    foreach ($rates as $rate) {
        if (!empty($rate['default'])) {
            $result = ['name' => $rate['name'], 'percentage' => (float) $rate['percent']];
            set_transient('pap_oblio_vat_default', $result, DAY_IN_SECONDS);

            return $result;
        }
    }

    return ['name' => 'Scutit', 'percentage' => 0];
}

/**
 * @return array{number: string, series: string, link: string}|WP_Error
 */
function papetarie_storefront_oblio_issue_document(WC_Order $order, string $awbText): array|WP_Error
{
    $token = papetarie_storefront_oblio_get_access_token();
    if (is_wp_error($token)) {
        return $token;
    }

    $s = papetarie_storefront_oblio_settings();
    $vat = papetarie_storefront_oblio_default_vat();

    $products = [];
    foreach ($order->get_items() as $item) {
        $products[] = [
            'name' => $item->get_name(),
            'price' => (float) $order->get_item_total($item, false, false),
            'measuringUnit' => 'buc',
            'quantity' => (int) $item->get_quantity(),
            'vatName' => $vat['name'],
            'vatPercentage' => $vat['percentage'],
            'vatIncluded' => 0,
        ];
    }

    $client = [
        'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        'address' => trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
        'state' => $order->get_billing_state(),
        'city' => $order->get_billing_city(),
        'country' => $order->get_billing_country() ?: 'Romania',
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
        'save' => 0,
    ];

    // "Doresc factura pe firma" (vezi papetarie_storefront_render_checkout_company_block()
    // in functions.php) - cand comanda are un CUI completat, factura se emite
    // pe firma (denumire + CUI), nu pe persoana care a plasat comanda.
    // Adresa sediului social e separata de adresa de livrare a comenzii
    // (_billing_company_state/_city/_address, nu _billing_state/_city/_address_1
    // - vezi campurile billing_company_* inregistrate in
    // papetarie_storefront_checkout_fields()), fiindca sediul unei firme
    // poate diferi de punctul de livrare.
    // "_billing_company" e camp NATIV WooCommerce (are propriul getter/
    // setter), nu meta ad-hoc - WC_Data::get_meta_data() il exclude explicit
    // din lista de meta accesibila prin get_meta() (e in
    // $internal_meta_keys), indiferent de storage (HPOS sau legacy postmeta).
    // get_meta('_billing_company') intoarce gol INTOTDEAUNA, deci conditia de
    // mai jos nu se activa niciodata - orice comanda facturata pe firma
    // genera documentul Oblio cu numele persoanei, nu al firmei. Trebuie
    // folosit getter-ul dedicat. Semnalat de user 2026-09-01 (proforma de
    // test aparuta cu numele in loc de firma).
    $company_name = trim((string) $order->get_billing_company());
    $company_cui = trim((string) $order->get_meta('_billing_cui'));
    if ($company_name !== '' && $company_cui !== '') {
        $cui_digits = strtoupper(preg_replace('/[^0-9A-Z]/', '', $company_cui));
        if (str_starts_with($cui_digits, 'RO')) {
            $cui_digits = substr($cui_digits, 2);
        }

        // "RO" doar daca firma e chiar platitoare de TVA (asa vine si de la
        // ANAF - un neplatitor nu are prefixul "RO" pe CUI). Inainte se
        // adauga necondiționat, indiferent de statusul real - factura ar fi
        // iesit cu un CIF gresit pentru orice firma neplatitoare. Meta
        // "_billing_vat_payer" vine din campul ascuns "billing_vat_payer" de
        // la checkout (papetarie_storefront_checkout_save_company_meta_to_order()),
        // populat din raspunsul ANAF sau din firma salvata aleasa. Lipsa ei
        // (comenzi vechi, dinainte de acest fix) inseamna "necunoscut" - nu
        // adaugam "RO" fara sa stim sigur, CUI-ul trebuie sa ramana exact
        // cum e. Semnalat live de user 2026-08-31.
        $company_is_vat_payer = trim((string) $order->get_meta('_billing_vat_payer')) === '1';

        $client['name'] = $company_name;
        $client['cif'] = ($company_is_vat_payer ? 'RO' : '') . $cui_digits;

        $company_state = trim((string) $order->get_meta('_billing_company_state'));
        if ($company_state !== '') {
            $client['state'] = $company_state;
        }

        $company_city = trim((string) $order->get_meta('_billing_company_city'));
        if ($company_city !== '') {
            $client['city'] = $company_city;
        }

        $company_address = trim((string) $order->get_meta('_billing_company_address'));
        if ($company_address !== '') {
            $client['address'] = $company_address;
        }

        $reg_no = trim((string) $order->get_meta('_billing_reg_no'));
        if ($reg_no !== '') {
            $client['rc'] = $reg_no;
        }
    }

    $payload = [
        'cif' => $s['cif'],
        'client' => $client,
        'seriesName' => $s['series'],
        'language' => 'RO',
        'precision' => 2,
        'currency' => 'RON',
        'products' => $products,
        'mentions' => $awbText,
        'sendEmail' => 0,
        'idempotencyKey' => 'notix-order-' . $order->get_id(),
    ];

    $endpoint = $s['doc_type'] === 'invoice' ? '/docs/invoice' : '/docs/proforma';

    $response = wp_remote_post(PAP_OBLIO_API_BASE . $endpoint, [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (($body['status'] ?? 0) !== 200 || empty($body['data']['link'])) {
        return new WP_Error('pap_oblio_issue_failed', 'Oblio a raspuns cu eroare: ' . wp_remote_retrieve_body($response));
    }

    return [
        'number' => $body['data']['number'],
        'series' => $body['data']['seriesName'],
        'link' => $body['data']['link'],
    ];
}

/* ---------------------------------------------------------------------
 * Meta box pe pagina comenzii
 * ------------------------------------------------------------------- */

function papetarie_storefront_oblio_add_meta_box(): void
{
    if (!papetarie_storefront_oblio_is_configured()) {
        return;
    }

    $screen = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
        && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
        ? wc_get_page_screen_id('shop-order')
        : 'shop_order';

    add_meta_box(
        'pap_oblio_box',
        'AWB + document Oblio',
        'papetarie_storefront_oblio_render_meta_box',
        $screen,
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'papetarie_storefront_oblio_add_meta_box');

function papetarie_storefront_oblio_render_meta_box($post_or_order): void
{
    $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order($post_or_order->ID);
    if (!$order) {
        return;
    }

    $docNumber = $order->get_meta('_pap_oblio_doc_number');
    $docSeries = $order->get_meta('_pap_oblio_doc_series');
    $docLink = $order->get_meta('_pap_oblio_doc_link');
    $awbSaved = $order->get_meta('_pap_oblio_awb');

    wp_nonce_field('pap_oblio_generate_' . $order->get_id(), 'pap_oblio_nonce');
    ?>
    <p>
        <label for="pap_oblio_awb"><strong>AWB</strong></label>
        <input type="text" id="pap_oblio_awb" style="width:100%;" value="<?php echo esc_attr($awbSaved); ?>" placeholder="ex: 1234567890">
    </p>
    <?php if ($docNumber) : ?>
        <p style="color:#0a7d3f;">
            Document generat: <?php echo esc_html($docSeries . ' ' . $docNumber); ?><br>
            <a href="<?php echo esc_url($docLink); ?>" target="_blank">Vezi documentul</a>
        </p>
        <button type="button" class="button" id="pap_oblio_generate_btn" data-order="<?php echo esc_attr($order->get_id()); ?>" data-force="1">Regenereaza + retrimite</button>
    <?php else : ?>
        <button type="button" class="button button-primary" id="pap_oblio_generate_btn" data-order="<?php echo esc_attr($order->get_id()); ?>" data-force="0">Genereaza document si trimite</button>
    <?php endif; ?>
    <p id="pap_oblio_status" style="margin-top:8px;"></p>
    <script>
    (function() {
        var btn = document.getElementById('pap_oblio_generate_btn');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var status = document.getElementById('pap_oblio_status');
            var awb = document.getElementById('pap_oblio_awb').value.trim();
            if (!awb) {
                status.textContent = 'Completeaza AWB-ul intai.';
                status.style.color = '#a33';
                return;
            }
            btn.disabled = true;
            status.textContent = 'Se genereaza...';
            status.style.color = '';
            var data = new FormData();
            data.append('action', 'pap_oblio_generate');
            data.append('order_id', btn.dataset.order);
            data.append('awb', awb);
            data.append('force', btn.dataset.force);
            data.append('nonce', document.getElementById('pap_oblio_nonce').value);
            fetch(ajaxurl, { method: 'POST', credentials: 'same-origin', body: data })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btn.disabled = false;
                    if (res.success) {
                        status.textContent = 'Gata — document + email trimise.';
                        status.style.color = '#0a7d3f';
                        setTimeout(function() { window.location.reload(); }, 1200);
                    } else {
                        status.textContent = 'Eroare: ' + (res.data && res.data.message ? res.data.message : 'necunoscuta');
                        status.style.color = '#a33';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    status.textContent = 'Eroare de retea.';
                    status.style.color = '#a33';
                });
        });
    })();
    </script>
    <?php
}

/* ---------------------------------------------------------------------
 * AJAX: genereaza documentul + trimite emailul combinat
 * ------------------------------------------------------------------- */

function papetarie_storefront_oblio_handle_generate_ajax(): void
{
    $orderId = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    $awb = isset($_POST['awb']) ? sanitize_text_field(wp_unslash($_POST['awb'])) : '';
    $force = !empty($_POST['force']);

    if (!$orderId || !check_ajax_referer('pap_oblio_generate_' . $orderId, 'nonce', false)) {
        wp_send_json_error(['message' => 'Cerere invalida.'], 400);
    }

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Nu ai permisiune.'], 403);
    }

    $order = wc_get_order($orderId);
    if (!$order) {
        wp_send_json_error(['message' => 'Comanda nu exista.'], 404);
    }

    if ($awb === '') {
        wp_send_json_error(['message' => 'AWB-ul e gol.'], 400);
    }

    if (!$force && $order->get_meta('_pap_oblio_doc_number')) {
        wp_send_json_error(['message' => 'Documentul a fost deja generat pentru comanda asta.'], 409);
    }

    $mentions = "AWB: {$awb}";

    $result = papetarie_storefront_oblio_issue_document($order, $mentions);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()], 500);
    }

    $order->update_meta_data('_pap_oblio_awb', $awb);
    $order->update_meta_data('_pap_oblio_doc_number', $result['number']);
    $order->update_meta_data('_pap_oblio_doc_series', $result['series']);
    $order->update_meta_data('_pap_oblio_doc_link', $result['link']);
    $order->save();

    // Trecere automata "Processing" -> "Expediata": odata AWB-ul generat si
    // trimis clientului, comanda nu mai e doar "in procesare" - a fost
    // predata curierului. Conditionat strict pe statusul curent fiind
    // "processing" - daca a fost deja marcata "Completed" (livrare
    // confirmata) sau pusa manual "On hold" si documentul se regenereaza
    // mai tarziu (butonul de "force"), nu vrem sa retrogradam o stare
    // aleasa deliberat de admin. Cerut de user 2026-09-01.
    if ($order->get_status() === 'processing') {
        $order->update_status('expediat', __('AWB generat și trimis clientului prin email.', 'papetarie-storefront'));
    }

    $pdfResponse = wp_remote_get($result['link'], ['timeout' => 30]);
    $attachmentPath = '';
    if (!is_wp_error($pdfResponse) && wp_remote_retrieve_response_code($pdfResponse) === 200) {
        $upload = wp_upload_dir();
        $tmpDir = trailingslashit($upload['basedir']) . 'oblio-tmp';
        wp_mkdir_p($tmpDir);
        $attachmentPath = $tmpDir . '/' . $result['series'] . '-' . $result['number'] . '-comanda-' . $orderId . '.pdf';
        file_put_contents($attachmentPath, wp_remote_retrieve_body($pdfResponse));
    }

    $customerEmail = $order->get_billing_email();
    $subject = sprintf('Comanda #%s a fost expediată', $order->get_order_number());
    $heading = sprintf('Comanda ta #%s a fost expediată', $order->get_order_number());
    // Deep-link cu AWB-ul precompletat - clientul da click si vede direct
    // statusul coletului, fara sa mai copieze/lipeasca AWB-ul manual pe
    // cargus.ro. Parametrul "tracking_number" e cel folosit chiar de
    // formularul de tracking al Cargus (GET, actiunea paginii
    // /personal/urmareste-coletul) - verificat live 2026-09-01.
    $trackingUrl = 'https://www.cargus.ro/personal/urmareste-coletul/?tracking_number=' . rawurlencode($awb);
    $docTypeLabel = papetarie_storefront_oblio_settings()['doc_type'] === 'invoice' ? 'Factura' : 'Proforma';
    $body = sprintf(
        '<p>Bună ziua,</p>
        <p>Comanda ta a fost predată curierului Cargus și este în drum spre tine.</p>
        <p><strong>AWB: %1$s</strong></p>
        <p style="text-align:center;margin:24px 0 4px;"><a class="button" href="%2$s" style="display:inline-block;background-color:#ff5b1f;color:#ffffff;padding:10px 24px;font-size:14px;font-weight:700;text-decoration:none;border-radius:2px;">Urmărește coletul</a></p>
        <p style="font-size:13px;color:#6b7280;margin-top:24px;">Atașat găsești %3$s ta pentru această comandă (%4$s %5$s).</p>',
        esc_html($awb),
        esc_url($trackingUrl),
        esc_html(mb_strtolower($docTypeLabel)),
        esc_html($result['series']),
        esc_html($result['number'])
    );
    $message = function_exists('papetarie_storefront_wrap_email_html') ? papetarie_storefront_wrap_email_html($heading, $body) : $body;

    $attachments = $attachmentPath !== '' ? [$attachmentPath] : [];
    $headers = ['Content-Type: text/html; charset=UTF-8', 'From: Notix <noreply@notix.ro>'];
    wp_mail($customerEmail, $subject, $message, $headers, $attachments);

    if ($attachmentPath !== '' && file_exists($attachmentPath)) {
        unlink($attachmentPath);
    }

    wp_send_json_success([
        'document' => $result['series'] . ' ' . $result['number'],
        'link' => $result['link'],
    ]);
}
add_action('wp_ajax_pap_oblio_generate', 'papetarie_storefront_oblio_handle_generate_ajax');
