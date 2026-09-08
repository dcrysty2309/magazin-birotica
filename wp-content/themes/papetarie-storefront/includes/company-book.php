<?php

defined('ABSPATH') || exit;

function papetarie_storefront_company_book_meta_key(): string
{
    return 'papetarie_company_book';
}

function papetarie_storefront_company_book_default_id_meta_key(): string
{
    return 'papetarie_default_company_id';
}

function papetarie_storefront_company_book_form_state_key(): string
{
    return 'papetarie_company_book_form_state';
}

function papetarie_storefront_company_book_fields(): array
{
    return [
        'denumire' => [
            'label' => __('Nume firmă', 'papetarie-storefront'),
            'placeholder' => __('Denumire firmă', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
        ],
        'cui' => [
            'label' => __('CUI', 'papetarie-storefront'),
            'placeholder' => __('RO12345678', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
        ],
        'nr_reg_com' => [
            'label' => __('Nr. registrul comerțului', 'papetarie-storefront'),
            'placeholder' => __('J00/0000/2026', 'papetarie-storefront'),
            'required' => false,
            'type' => 'text',
        ],
        'state' => [
            'label' => __('Județ', 'papetarie-storefront'),
            'placeholder' => __('Alege județul', 'papetarie-storefront'),
            'required' => true,
            'type' => 'select',
        ],
        'city' => [
            'label' => __('Localitate', 'papetarie-storefront'),
            'placeholder' => __('Alege localitatea', 'papetarie-storefront'),
            'required' => true,
            'type' => 'select',
        ],
        'address_1' => [
            'label' => __('Adresă (sediu social)', 'papetarie-storefront'),
            'placeholder' => __('Strada Exemplu 12', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
        ],
    ];
}

function papetarie_storefront_company_book_empty_entry(): array
{
    return [
        'id' => '',
        'denumire' => '',
        'cui' => '',
        'nr_reg_com' => '',
        'vat_payer' => false,
        'country' => 'RO',
        'state' => '',
        'city' => '',
        'address_1' => '',
        'created_at' => '',
        'updated_at' => '',
    ];
}

function papetarie_storefront_company_book_sanitize_entry(array $entry): array
{
    $normalized = papetarie_storefront_company_book_empty_entry();

    foreach (array_keys($normalized) as $key) {
        if (!array_key_exists($key, $entry)) {
            continue;
        }

        if ($key === 'vat_payer') {
            $normalized[$key] = !empty($entry[$key]) && $entry[$key] !== '0';
            continue;
        }

        if (in_array($key, ['created_at', 'updated_at', 'id'], true)) {
            $normalized[$key] = sanitize_text_field((string) $entry[$key]);
            continue;
        }

        $normalized[$key] = sanitize_text_field((string) $entry[$key]);
    }

    $normalized['country'] = $normalized['country'] !== '' ? strtoupper($normalized['country']) : 'RO';
    $normalized['state'] = strtoupper(sanitize_key($normalized['state']));
    $normalized['address_1'] = trim((string) $normalized['address_1']);
    $normalized['city'] = trim((string) $normalized['city']);
    $normalized['denumire'] = trim((string) $normalized['denumire']);
    $normalized['nr_reg_com'] = trim((string) $normalized['nr_reg_com']);

    // CUI-ul poate fi tastat cu prefixul "RO" (asa cum apare pe majoritatea
    // documentelor firmei) sau doar cifre - il normalizam la doar cifre aici,
    // o singura data, ca sa nu se dubleze logica asta si in validare si in
    // payload-ul Oblio.
    $normalized['cui'] = strtoupper(preg_replace('/[^0-9A-Z]/', '', trim((string) $normalized['cui'])));
    if (str_starts_with($normalized['cui'], 'RO')) {
        $normalized['cui'] = substr($normalized['cui'], 2);
    }

    return $normalized;
}

function papetarie_storefront_company_book_label(array $company): string
{
    $denumire = trim((string) ($company['denumire'] ?? ''));
    return $denumire !== '' ? $denumire : __('Firmă salvată', 'papetarie-storefront');
}

function papetarie_storefront_company_book_session()
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return null;
    }

    return WC()->session;
}

function papetarie_storefront_company_book_set_form_state(array $state): void
{
    $session = papetarie_storefront_company_book_session();
    if (!$session) {
        return;
    }

    $session->set(papetarie_storefront_company_book_form_state_key(), $state);
}

function papetarie_storefront_company_book_get_form_state(): array
{
    $session = papetarie_storefront_company_book_session();
    if (!$session) {
        return [];
    }

    $state = $session->get(papetarie_storefront_company_book_form_state_key(), []);
    return is_array($state) ? $state : [];
}

function papetarie_storefront_company_book_clear_form_state(): void
{
    $session = papetarie_storefront_company_book_session();
    if (!$session) {
        return;
    }

    $session->set(papetarie_storefront_company_book_form_state_key(), []);
}

function papetarie_storefront_company_book_get_all(int $user_id): array
{
    $stored = get_user_meta($user_id, papetarie_storefront_company_book_meta_key(), true);
    $companies = [];

    if (is_array($stored)) {
        foreach ($stored as $company) {
            if (!is_array($company)) {
                continue;
            }

            $normalized = papetarie_storefront_company_book_sanitize_entry($company);
            if ($normalized['id'] === '') {
                $normalized['id'] = 'firm_' . wp_generate_uuid4();
            }

            $companies[] = $normalized;
        }
    }

    $default_id = trim((string) get_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), true));
    if ($default_id === '' && !empty($companies)) {
        $default_id = (string) $companies[0]['id'];
        update_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), $default_id);
    }

    foreach ($companies as &$company) {
        $company['is_default'] = $default_id !== '' && (string) ($company['id'] ?? '') === $default_id;
    }
    unset($company);

    return $companies;
}

function papetarie_storefront_company_book_save_all(int $user_id, array $companies, string $default_id = ''): void
{
    $clean_companies = [];
    foreach ($companies as $company) {
        if (!is_array($company)) {
            continue;
        }

        $normalized = papetarie_storefront_company_book_sanitize_entry($company);
        if ($normalized['id'] === '') {
            $normalized['id'] = 'firm_' . wp_generate_uuid4();
        }
        $clean_companies[] = $normalized;
    }

    if ($default_id === '' && !empty($clean_companies)) {
        $default_id = (string) $clean_companies[0]['id'];
    }

    update_user_meta($user_id, papetarie_storefront_company_book_meta_key(), $clean_companies);
    update_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), $default_id);
}

function papetarie_storefront_company_book_get(int $user_id, string $company_id): ?array
{
    $company_id = trim($company_id);
    if ($company_id === '') {
        return null;
    }

    foreach (papetarie_storefront_company_book_get_all($user_id) as $company) {
        if ((string) ($company['id'] ?? '') === $company_id) {
            return $company;
        }
    }

    return null;
}

function papetarie_storefront_company_book_save_entry(int $user_id, array $posted, string $company_id = '', ?string $default_id = null): array
{
    $companies = papetarie_storefront_company_book_get_all($user_id);
    $existing = null;
    $existing_index = null;

    if ($company_id !== '') {
        foreach ($companies as $index => $company) {
            if ((string) ($company['id'] ?? '') === $company_id) {
                $existing = $company;
                $existing_index = $index;
                break;
            }
        }
    }

    $entry = papetarie_storefront_company_book_empty_entry();
    if (is_array($existing)) {
        $entry = array_merge($entry, $existing);
    }

    $entry = array_merge($entry, papetarie_storefront_company_book_sanitize_entry($posted));
    $entry['id'] = $existing['id'] ?? ($company_id !== '' ? $company_id : 'firm_' . wp_generate_uuid4());
    $entry['created_at'] = $existing['created_at'] ?? gmdate('c');
    $entry['updated_at'] = gmdate('c');

    if ($existing_index !== null) {
        $companies[$existing_index] = $entry;
    } else {
        $companies[] = $entry;
    }

    $current_default_id = trim((string) get_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), true));
    if ($default_id === null) {
        $default_id = $current_default_id;
    }

    papetarie_storefront_company_book_save_all($user_id, $companies, $default_id);

    return papetarie_storefront_company_book_get($user_id, (string) $entry['id']) ?? $entry;
}

function papetarie_storefront_company_book_delete_entry(int $user_id, string $company_id): bool
{
    $company_id = trim($company_id);
    if ($company_id === '') {
        return false;
    }

    $companies = papetarie_storefront_company_book_get_all($user_id);
    $filtered = [];
    $removed = false;

    foreach ($companies as $company) {
        if ((string) ($company['id'] ?? '') === $company_id) {
            $removed = true;
            continue;
        }

        $filtered[] = $company;
    }

    if (!$removed) {
        return false;
    }

    $default_id = trim((string) get_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), true));
    if ($default_id === $company_id) {
        $default_id = !empty($filtered) ? (string) $filtered[0]['id'] : '';
    }

    papetarie_storefront_company_book_save_all($user_id, $filtered, $default_id);

    return true;
}

function papetarie_storefront_company_book_default(int $user_id): ?array
{
    $companies = papetarie_storefront_company_book_get_all($user_id);
    if (empty($companies)) {
        return null;
    }

    foreach ($companies as $company) {
        if (!empty($company['is_default'])) {
            return $company;
        }
    }

    return $companies[0];
}

function papetarie_storefront_company_book_format_lines(array $company): array
{
    $lines = [];
    $denumire = trim((string) ($company['denumire'] ?? ''));
    $cui = trim((string) ($company['cui'] ?? ''));
    $address_line = trim((string) ($company['address_1'] ?? ''));
    $state_code = strtoupper(sanitize_key((string) ($company['state'] ?? '')));
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
    $state_label = $state_code !== '' && isset($counties[$state_code]) ? $counties[$state_code] : $state_code;

    if ($denumire !== '') {
        $lines[] = $denumire;
    }

    if ($cui !== '') {
        // "RO" doar daca firma e platitoare de TVA (asa vine si de la ANAF -
        // CUI-ul unui neplatitor nu are prefixul "RO"). Inainte se adauga
        // necondiționat, indiferent de "vat_payer" - afisa un CUI gresit
        // pentru orice firma neplatitoare de TVA. Semnalat live de user
        // 2026-08-31.
        $lines[] = 'CUI: ' . (!empty($company['vat_payer']) ? 'RO' : '') . $cui;
    }

    if ($address_line !== '') {
        $lines[] = $address_line;
    }

    $city_parts = array_filter([
        trim((string) ($company['city'] ?? '')),
        $state_label,
    ]);

    if (!empty($city_parts)) {
        $lines[] = implode(', ', $city_parts);
    }

    return $lines;
}

/**
 * Randurile pentru tabelul cheie-valoare din cardul de firma (design Figma
 * node 191:2) - spre deosebire de format_lines() (folosita ca fallback text
 * simplu), aici fiecare rand e o pereche [label, value] separata, ca sa
 * poata fi randata ca grid cu 2 coloane (eticheta / valoare).
 */
function papetarie_storefront_company_book_table_rows(array $company): array
{
    $rows = [];
    $denumire = trim((string) ($company['denumire'] ?? ''));
    $cui = trim((string) ($company['cui'] ?? ''));
    $nr_reg_com = trim((string) ($company['nr_reg_com'] ?? ''));
    $address_line = trim((string) ($company['address_1'] ?? ''));
    $city = trim((string) ($company['city'] ?? ''));
    $state_code = strtoupper(sanitize_key((string) ($company['state'] ?? '')));
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
    $state_label = $state_code !== '' && isset($counties[$state_code]) ? $counties[$state_code] : $state_code;

    if ($denumire !== '') {
        $rows[] = ['label' => __('Denumire', 'papetarie-storefront'), 'value' => $denumire];
    }

    if ($cui !== '') {
        // "RO" doar daca firma e platitoare de TVA - vezi comentariul din
        // format_lines() de mai sus, acelasi bug, acelasi fix.
        $rows[] = ['label' => __('CUI / CIF', 'papetarie-storefront'), 'value' => (!empty($company['vat_payer']) ? 'RO' : '') . $cui];
    }

    if ($nr_reg_com !== '') {
        $rows[] = ['label' => __('Reg. Com.', 'papetarie-storefront'), 'value' => $nr_reg_com];
    }

    if ($address_line !== '') {
        // Firmele salvate inainte de fix-ul din lookup-ul ANAF (sau completate
        // manual prin copy-paste dintr-un document oficial) pot inca avea
        // judetul/localitatea duplicate in address_1 - le curatam si aici, la
        // afisare, nu doar la salvare, ca sa nu conteze cand anume a fost
        // adaugata firma.
        $address_display = function_exists('papetarie_storefront_company_book_strip_address_admin_segments')
            ? papetarie_storefront_company_book_strip_address_admin_segments($address_line)
            : $address_line;
        $rows[] = ['label' => __('Adresă', 'papetarie-storefront'), 'value' => $address_display];
    }

    if ($city !== '') {
        $rows[] = ['label' => __('Localitate', 'papetarie-storefront'), 'value' => $city];
    }

    if ($state_label !== '') {
        $rows[] = ['label' => __('Județ', 'papetarie-storefront'), 'value' => $state_label];
    }

    return $rows;
}

/**
 * Validare CUI foarte permisiva la nivel de format (doar cifre, 2-10) -
 * scopul e sa prindem greseli evidente de tastare, nu sa validam impotriva
 * ANAF (lookup-ul e strict optional, vezi papetarie_storefront_company_book_lookup_cui
 * - un CUI valid ca format dar negasit in ANAF tot trebuie sa poata fi
 * salvat manual, firma poate fi foarte noua sau ANAF poate fi indisponibil).
 */
function papetarie_storefront_company_book_cui_is_valid_format(string $cui): bool
{
    $digits = preg_replace('/[^0-9]/', '', strtoupper(str_starts_with(strtoupper($cui), 'RO') ? substr($cui, 2) : $cui));
    return $digits !== '' && strlen($digits) >= 2 && strlen($digits) <= 10;
}

/**
 * Doar cifrele CUI-ului, fara prefixul "RO" - acelasi CUI se poate salva in
 * doua randuri diferite ("49485790" vs "RO49485790" vs " 49485790 "), fara
 * normalizare ar trece de orice comparatie directa de string.
 */
function papetarie_storefront_company_book_cui_digits(string $cui): string
{
    return preg_replace('/[^0-9]/', '', strtoupper(str_starts_with(strtoupper($cui), 'RO') ? substr($cui, 2) : $cui));
}

function papetarie_storefront_company_book_validate(array $posted, \WP_Error $errors, int $user_id = 0, string $exclude_id = ''): array
{
    $fields = papetarie_storefront_company_book_fields();
    $clean = [];
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];

    foreach (array_keys($fields) as $field_key) {
        $raw = isset($posted[$field_key]) ? wp_unslash((string) $posted[$field_key]) : '';
        $clean[$field_key] = sanitize_text_field($raw);
    }
    $clean['vat_payer'] = !empty($posted['vat_payer']);
    $clean['country'] = 'RO';

    if (($clean['denumire'] ?? '') === '') {
        $errors->add('company_denumire_required', __('Completează denumirea firmei.', 'papetarie-storefront'));
    }

    if (($clean['cui'] ?? '') === '') {
        $errors->add('company_cui_required', __('Completează CUI-ul firmei.', 'papetarie-storefront'));
    } elseif (!papetarie_storefront_company_book_cui_is_valid_format((string) $clean['cui'])) {
        $errors->add('company_cui_invalid', __('CUI-ul nu pare valid — verifică cifrele.', 'papetarie-storefront'));
    } elseif ($user_id > 0) {
        // O firma cu acelasi CUI, salvata deja - fara asta userul putea
        // adauga aceeasi firma de mai multe ori (aceleasi date, intrari
        // separate in lista). "$exclude_id" e firma curent editata, ca sa
        // nu se compare cu ea insasi. Semnalat live de user 2026-08-31.
        $new_digits = papetarie_storefront_company_book_cui_digits((string) $clean['cui']);
        foreach (papetarie_storefront_company_book_get_all($user_id) as $existing_company) {
            $existing_id = (string) ($existing_company['id'] ?? '');
            if ($existing_id === $exclude_id) {
                continue;
            }

            if ($new_digits !== '' && papetarie_storefront_company_book_cui_digits((string) ($existing_company['cui'] ?? '')) === $new_digits) {
                $errors->add('company_cui_duplicate', __('Ai deja o firmă salvată cu acest CUI.', 'papetarie-storefront'));
                break;
            }
        }
    }

    if (($clean['state'] ?? '') === '') {
        $errors->add('company_state_required', __('Alege județul.', 'papetarie-storefront'));
    } elseif (!isset($counties[$clean['state']])) {
        $errors->add('company_state_invalid', __('Județul selectat nu este valid.', 'papetarie-storefront'));
    }

    if (($clean['city'] ?? '') === '') {
        $errors->add('company_city_required', __('Alege localitatea.', 'papetarie-storefront'));
    }

    if (($clean['address_1'] ?? '') === '') {
        $errors->add('company_address_required', __('Completează adresa sediului social.', 'papetarie-storefront'));
    }

    return $clean;
}

function papetarie_storefront_company_book_base_url(): string
{
    return function_exists('wc_get_endpoint_url')
        ? wc_get_endpoint_url('firmele-mele')
        : home_url('/my-account/firmele-mele/');
}

function papetarie_storefront_company_book_form_url(array $query_args = []): string
{
    $url = papetarie_storefront_company_book_base_url();
    return !empty($query_args) ? add_query_arg($query_args, $url) : $url;
}

function papetarie_storefront_company_book_current_mode(): string
{
    $mode = isset($_GET['pap_company_action']) ? sanitize_key(wp_unslash((string) $_GET['pap_company_action'])) : '';
    return in_array($mode, ['add', 'edit'], true) ? $mode : 'list';
}

function papetarie_storefront_company_book_current_id(): string
{
    return isset($_GET['pap_company_id']) ? sanitize_text_field(wp_unslash((string) $_GET['pap_company_id'])) : '';
}

function papetarie_storefront_company_book_render_form_notice(): void
{
    $notices = function_exists('wc_print_notices') ? wc_print_notices(true) : '';
    if (trim((string) $notices) === '') {
        return;
    }
    ?>
    <div class="pap-account-address-modal__notices">
      <?php echo $notices; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <?php
}

/**
 * Aceeasi structura ".pap-float-field" ca la adrese/detalii cont (vezi
 * papetarie_storefront_address_book_render_float_field) - dublata aici
 * intentionat, nu partajata, ca sa poata purta clasa
 * "pap-company-form-field--{cheie}" fara sa se amestece cu selectoarele CSS
 * ale formularului de adresa.
 */
function papetarie_storefront_company_book_render_float_field(string $key, array $field, string $value, array $extra_attrs = [], array $options = []): void
{
    $type = (string) ($field['type'] ?? 'text');
    $required = !empty($field['required']);
    $label = (string) ($field['label'] ?? $key);
    $field_id = 'company-book-' . $key;

    if ($required) {
        $extra_attrs['required'] = 'required';
    }

    $attr_pairs = [];
    foreach ($extra_attrs as $attr_name => $attr_value) {
        if ($attr_value === null || $attr_value === false || $attr_value === '') {
            continue;
        }
        $attr_pairs[] = sprintf('%1$s="%2$s"', esc_attr((string) $attr_name), esc_attr((string) $attr_value));
    }
    $attrs_html = implode(' ', $attr_pairs);

    $wrapper_class = 'pap-float-field pap-company-form-field pap-company-form-field--' . $key;
    if ($type === 'select') {
        $wrapper_class .= ' pap-float-field--select';
    }
    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>">
      <?php if ($type === 'select') : ?>
        <select name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($field_id); ?>" class="woocommerce-Input woocommerce-Input--select" <?php echo $attrs_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
          <?php foreach ($options as $option_value => $option_label) : ?>
            <option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected($value, (string) $option_value); ?>><?php echo esc_html((string) $option_label); ?></option>
          <?php endforeach; ?>
        </select>
      <?php else : ?>
        <input type="<?php echo esc_attr($type); ?>" class="woocommerce-Input woocommerce-Input--<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($field_id); ?>" placeholder=" " value="<?php echo esc_attr($value); ?>" <?php echo $attrs_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
      <?php endif; ?>
      <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?></label>
      <small class="pap-field-error" aria-hidden="true"></small>
    </div>
    <?php
}

function papetarie_storefront_company_book_render_form_fields(array $source): void
{
    $fields = papetarie_storefront_company_book_fields();

    $state_value = strtoupper(sanitize_key((string) ($source['state'] ?? '')));
    $state_options = ['' => $fields['state']['placeholder'] ?? __('Alege județul', 'papetarie-storefront')]
        + (function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : []);

    $city_value = (string) ($source['city'] ?? '');
    $city_attrs = ['data-company-book-city' => '1'];
    if ($state_value !== '' && function_exists('papetarie_storefront_checkout_city_options_for_county')) {
        $city_options = papetarie_storefront_checkout_city_options_for_county($state_value);
        $city_select_options = ['' => $fields['city']['placeholder'] ?? __('Alege localitatea', 'papetarie-storefront')] + $city_options;
        foreach (array_keys($city_options) as $city_option) {
            if (function_exists('papetarie_storefront_normalize_city_key') && papetarie_storefront_normalize_city_key((string) $city_option) === papetarie_storefront_normalize_city_key($city_value)) {
                $city_value = (string) $city_option;
                break;
            }
        }
    } else {
        $city_select_options = ['' => __('Alege județul întâi', 'papetarie-storefront')];
        $city_attrs['disabled'] = 'disabled';
        $city_attrs['aria-disabled'] = 'true';
    }
    // Modul "editare" (firma exista deja, are date reale) porneste cu
    // sectiunea completa desfasurata - doar la "adaugare" incepe stransa,
    // cu un singur camp + buton, ca in Figma-ul cerut de user.
    $has_existing_data = trim((string) ($source['denumire'] ?? '')) !== '';
    ?>
    <div class="pap-company-cui-row">
      <?php papetarie_storefront_company_book_render_float_field('cui', $fields['cui'], (string) ($source['cui'] ?? ''), ['data-company-book-cui' => '1', 'autocomplete' => 'off']); ?>
      <button type="button" class="pap-account-secondary-button pap-company-autocomplete-btn" data-company-book-autocomplete>
        <?php esc_html_e('Completează automat', 'papetarie-storefront'); ?>
      </button>
    </div>
    <p class="pap-company-lookup-status" data-company-book-lookup-status aria-live="polite"></p>

    <?php
    $manual_label_closed = __('Nu găsești firma? Completează datele manual', 'papetarie-storefront');
    $manual_label_open = __('← Completează automat după CUI', 'papetarie-storefront');
    ?>
    <button
      type="button"
      class="pap-manual-toggle-link"
      data-company-book-manual-toggle
      data-label-closed="<?php echo esc_attr($manual_label_closed); ?>"
      data-label-open="<?php echo esc_attr($manual_label_open); ?>"
    ><?php echo esc_html($has_existing_data ? $manual_label_open : $manual_label_closed); ?></button>

    <div class="pap-company-manual-fields" data-company-book-manual-fields <?php echo $has_existing_data ? '' : 'hidden'; ?>>
      <?php papetarie_storefront_company_book_render_float_field('denumire', $fields['denumire'], (string) ($source['denumire'] ?? ''), ['data-company-book-denumire' => '1']); ?>

      <div class="pap-address-form-grid-2col">
        <?php papetarie_storefront_company_book_render_float_field('nr_reg_com', $fields['nr_reg_com'], (string) ($source['nr_reg_com'] ?? ''), ['data-company-book-nr-reg-com' => '1']); ?>
        <label class="pap-company-vat-payer">
          <input type="checkbox" class="pap-checkbox-input" name="vat_payer" value="1" data-company-book-vat-payer="1" <?php checked(!empty($source['vat_payer'])); ?>>
          <?php esc_html_e('Plătitor de TVA', 'papetarie-storefront'); ?>
        </label>
      </div>

      <div class="pap-address-form-grid-2col">
        <?php papetarie_storefront_company_book_render_float_field('state', $fields['state'], $state_value, ['data-company-book-state' => '1'], $state_options); ?>
        <?php papetarie_storefront_company_book_render_float_field('city', $fields['city'], $city_value, $city_attrs, $city_select_options); ?>
      </div>

      <?php papetarie_storefront_company_book_render_float_field('address_1', $fields['address_1'], (string) ($source['address_1'] ?? ''), ['data-company-book-address-1' => '1']); ?>
    </div>
    <?php
}

function papetarie_storefront_company_book_render_form(array $company = [], string $mode = 'add'): void
{
    $company = array_merge(papetarie_storefront_company_book_empty_entry(), $company);
    $is_edit = $mode === 'edit' && !empty($company['id']);
    $action_url = papetarie_storefront_company_book_form_url([
        'pap_company_action' => $is_edit ? 'edit' : 'add',
        'pap_company_id' => $is_edit ? $company['id'] : null,
    ]);
    ?>
    <form class="pap-account-form pap-account-address-form" method="post" action="<?php echo esc_url($action_url); ?>" novalidate data-company-book-modal-form>
        <?php wp_nonce_field('pap_company_book_save', 'pap_company_book_nonce'); ?>
        <input type="hidden" name="pap_company_book_action" value="save">
        <input type="hidden" name="pap_company_id" value="<?php echo esc_attr((string) $company['id']); ?>">

        <?php $form_state = papetarie_storefront_company_book_get_form_state(); ?>
        <?php $source = !empty($form_state) ? array_merge($company, $form_state) : $company; ?>

        <div class="pap-account-address-modal__notice-wrap" data-company-book-form-notice></div>

        <div class="pap-address-form-stack">
          <?php papetarie_storefront_company_book_render_form_fields($source); ?>
        </div>

        <div class="pap-account-address-form-actions">
          <button type="button" class="pap-account-secondary-button" data-company-book-modal-close>
            <?php esc_html_e('Anulați', 'papetarie-storefront'); ?>
          </button>
          <button type="submit" class="pap-account-primary-button">
            <?php esc_html_e('Salvare', 'papetarie-storefront'); ?>
          </button>
        </div>
      </form>
    <?php
}

function papetarie_storefront_company_book_render_modal_html(array $company = [], string $mode = 'add', bool $is_open = false): string
{
    $company = array_merge(papetarie_storefront_company_book_empty_entry(), $company);
    $mode = in_array($mode, ['add', 'edit'], true) ? $mode : 'add';
    $should_open = (bool) $is_open;
    $title = $mode === 'edit' ? __('Editează firma', 'papetarie-storefront') : __('Adaugă firmă', 'papetarie-storefront');
    $subtitle = __('Datele astea apar pe factură — completează-le corect.', 'papetarie-storefront');

    ob_start();
    ?>
    <div
      class="pap-account-address-modal<?php echo $should_open ? ' is-open' : ''; ?>"
      id="pap-account-company-modal"
      data-company-book-modal
      <?php echo $should_open ? '' : 'hidden'; ?>
      aria-hidden="<?php echo $should_open ? 'false' : 'true'; ?>"
      data-company-book-open-on-load="<?php echo $should_open ? '1' : '0'; ?>"
    >
      <div class="pap-account-address-modal__backdrop" data-company-book-modal-close aria-hidden="true"></div>
      <div class="pap-account-address-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pap-account-company-modal-title">
        <div class="pap-account-address-modal__head">
          <div class="pap-account-address-modal__head-copy">
            <h2 id="pap-account-company-modal-title"><?php echo esc_html($title); ?></h2>
            <p><?php echo esc_html($subtitle); ?></p>
          </div>
        </div>
        <div class="pap-account-address-modal__body">
          <?php papetarie_storefront_company_book_render_form_notice(); ?>
          <?php papetarie_storefront_company_book_render_form($company, $mode); ?>
        </div>
      </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_company_book_process_request(array $request): array|WP_Error
{
    if (!is_user_logged_in()) {
        return new WP_Error('company_not_logged_in', __('Trebuie să fii autentificat pentru a gestiona firmele.', 'papetarie-storefront'));
    }

    $nonce = isset($request['pap_company_book_nonce']) ? sanitize_text_field(wp_unslash((string) $request['pap_company_book_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_company_book_save')) {
        return new WP_Error('company_nonce_invalid', __('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'));
    }

    $user_id = get_current_user_id();
    $action = sanitize_key(wp_unslash((string) ($request['pap_company_book_action'] ?? '')));
    $company_id = isset($request['pap_company_id']) ? sanitize_text_field(wp_unslash((string) $request['pap_company_id'])) : '';

    if ($company_id !== '' && !papetarie_storefront_company_book_get($user_id, $company_id)) {
        return new WP_Error('company_id_invalid', __('Firma selectată nu există sau nu îți aparține.', 'papetarie-storefront'));
    }

    if ($action === 'delete') {
        if (!papetarie_storefront_company_book_delete_entry($user_id, $company_id)) {
            return new WP_Error('company_delete_failed', __('Nu am putut șterge firma selectată.', 'papetarie-storefront'));
        }

        papetarie_storefront_company_book_clear_form_state();

        return ['message' => __('Firma a fost ștearsă.', 'papetarie-storefront')];
    }

    if ($action === 'set_default') {
        if ($company_id === '') {
            return new WP_Error('company_id_invalid', __('Firma selectată nu există sau nu îți aparține.', 'papetarie-storefront'));
        }

        update_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), $company_id);

        return ['message' => __('Firma implicită a fost actualizată.', 'papetarie-storefront')];
    }

    if ($action !== 'save') {
        return new WP_Error('company_action_invalid', __('Acțiunea selectată nu este validă.', 'papetarie-storefront'));
    }

    $errors = new WP_Error();
    $clean = papetarie_storefront_company_book_validate($request, $errors, $user_id, $company_id);

    if ($errors->has_errors()) {
        papetarie_storefront_company_book_set_form_state($clean);
        return $errors;
    }

    $current_default_id = trim((string) get_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), true));
    $make_default = (!empty($request['pap_company_is_default']) && (string) $request['pap_company_is_default'] === '1')
        || $current_default_id === '';
    $saved = papetarie_storefront_company_book_save_entry($user_id, $clean, $company_id);

    if ($make_default) {
        update_user_meta($user_id, papetarie_storefront_company_book_default_id_meta_key(), (string) ($saved['id'] ?? ''));
    }

    papetarie_storefront_company_book_clear_form_state();

    return [
        'message' => $company_id !== '' ? __('Firma a fost salvată.', 'papetarie-storefront') : __('Firma a fost adăugată.', 'papetarie-storefront'),
        'company' => $saved,
    ];
}

/**
 * Cauta o firma dupa CUI la ANAF (API-ul public gratuit, fara cheie) - doar
 * pentru autocompletare in formular, niciodata blocant. Orice esec (CUI
 * negasit, ANAF jos, raspuns intr-un format neasteptat) intoarce pur si
 * simplu null - formularul ramane complet functional manual.
 */
function papetarie_storefront_company_book_lookup_cui(string $cui): ?array
{
    $digits = preg_replace('/[^0-9]/', '', $cui);
    if ($digits === '') {
        return null;
    }

    // Verificat live 2026-08-17: vechiul path
    // ".../PlatitorTvaRest/api/v9/ws/tva" da 404 (ANAF l-a mutat) - path-ul
    // corect actual e ".../api/PlatitorTvaRest/v9/tva" ("api" primul, fara
    // "/ws/").
    $response = wp_remote_post('https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva', [
        'timeout' => 8,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => wp_json_encode([[
            'cui' => (int) $digits,
            'data' => wp_date('Y-m-d'),
        ]]),
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $found = $body['found'][0] ?? null;
    if (!is_array($found)) {
        return null;
    }

    $general = $found['date_generale'] ?? [];
    $sediu = $found['adresa_sediu_social'] ?? [];
    $tva = $found['inregistrare_scop_Tva']['scpTVA'] ?? null;

    // Comparatie exacta (doar lowercase) - ANAF intoarce "MUNICIPIUL
    // BUCUREȘTI" pentru judetul Bucuresti (lista noastra are doar
    // "București"), asa ca NICIUN CUI cu sediul in Bucuresti nu se
    // potrivea vreodata, indiferent de firma - judetul/localitatea ramaneau
    // goale dupa autocompletare. papetarie_storefront_normalize_locality_key()
    // (deja folosita pentru potrivirea localitatii) elimina exact acest tip
    // de prefix ("municipiul ", "judetul " etc.) si diacriticele, inainte de
    // comparatie. Gasit live 2026-08-31, testand fluxul cu o firma reala din
    // Bucuresti.
    $county_name = trim((string) ($sediu['sdenumire_Judet'] ?? ''));
    $county_code = '';
    if ($county_name !== '' && function_exists('papetarie_storefront_romania_counties') && function_exists('papetarie_storefront_normalize_locality_key')) {
        $normalized_county_name = papetarie_storefront_normalize_locality_key($county_name);
        foreach (papetarie_storefront_romania_counties() as $code => $name) {
            if (papetarie_storefront_normalize_locality_key($name) === $normalized_county_name) {
                $county_code = $code;
                break;
            }
        }

        // Plasa de siguranta: daca ANAF foloseste vreodata alt format
        // neanticipat pentru un judet (in afara de prefixul "municipiul"
        // deja tratat mai sus), incercam o potrivire "contine" - numele
        // normalizat al judetului trebuie sa apara ca sub-secventa in
        // textul normalizat primit de la ANAF. Nu se substituie
        // comparatiei exacte de mai sus (ramane prioritara, mai precisa),
        // doar completeaza cazuri neprevazute - acelasi principiu ca la
        // potrivirea localitatii in extractLocalityCandidates() (JS).
        if ($county_code === '') {
            foreach (papetarie_storefront_romania_counties() as $code => $name) {
                $normalized_name = papetarie_storefront_normalize_locality_key($name);
                if ($normalized_name !== '' && str_contains($normalized_county_name, $normalized_name)) {
                    $county_code = $code;
                    break;
                }
            }
        }
    }

    return [
        'denumire' => sanitize_text_field((string) ($general['denumire'] ?? '')),
        'nr_reg_com' => sanitize_text_field((string) ($general['nrRegCom'] ?? '')),
        'vat_payer' => (bool) $tva,
        'address_1' => papetarie_storefront_company_book_strip_address_admin_segments(sanitize_text_field((string) ($general['adresa'] ?? ''))),
        'state' => $county_code,
        'city' => sanitize_text_field((string) ($sediu['sdenumire_Localitate'] ?? '')),
    ];
}

/**
 * ANAF intoarce adresa sediului social ca un singur string care repeta
 * judetul si localitatea inaintea strazii (ex. "JUD. CLUJ, SAT FLOREŞTI
 * COM. FLOREŞTI, STR. LACULUI, NR.2, AP.15") - date deja acoperite separat
 * de campurile Judet/Localitate din formular. Pastram doar segmentele de
 * nivel strada (Str./Nr./Bl./Sc./Et./Ap. etc), ca in campul "Adresa" de pe
 * ipb.ro (ex. "Str Lacului, 2, Ap:15"), nu tot lantul administrativ.
 */
function papetarie_storefront_company_book_strip_address_admin_segments(string $raw): string
{
    $segments = array_map('trim', explode(',', $raw));
    $admin_prefix_pattern = '/^(JUD\.?|JUDEȚUL|JUDETUL|MUN\.?|MUNICIPIUL|ORAȘ|ORAS|SAT|COM\.?|COMUNA|SECTOR)\b/iu';

    $kept = array_filter($segments, static function (string $segment) use ($admin_prefix_pattern): bool {
        return $segment !== '' && !preg_match($admin_prefix_pattern, $segment);
    });

    $result = implode(', ', $kept);

    // Daca dupa filtrare n-a mai ramas nimic (adresa era doar diviziuni
    // administrative, fara strada explicita), mai bine pastram string-ul
    // original decat sa trimitem campul complet gol.
    return $result !== '' ? $result : $raw;
}

function papetarie_storefront_handle_company_cui_lookup_ajax(): void
{
    check_ajax_referer('pap_lookup_cui', 'nonce');

    $cui = isset($_POST['cui']) ? sanitize_text_field(wp_unslash((string) $_POST['cui'])) : '';
    if (!papetarie_storefront_company_book_cui_is_valid_format($cui)) {
        wp_send_json_error(['message' => __('CUI invalid.', 'papetarie-storefront')], 400);
    }

    $data = papetarie_storefront_company_book_lookup_cui($cui);
    if ($data === null) {
        wp_send_json_error(['message' => __('Nu am găsit firma — completează manual.', 'papetarie-storefront')], 404);
    }

    wp_send_json_success($data);
}
add_action('wp_ajax_pap_lookup_cui', 'papetarie_storefront_handle_company_cui_lookup_ajax');
add_action('wp_ajax_nopriv_pap_lookup_cui', 'papetarie_storefront_handle_company_cui_lookup_ajax');

function papetarie_storefront_handle_company_book_request(): void
{
    // "firmele-mele" e un endpoint WP simplu (add_rewrite_endpoint), nu unul
    // inregistrat in WC()->query->get_query_vars() - is_wc_endpoint_url()
    // cauta doar in acea lista, deci intoarce mereu false aici. Acelasi bug
    // a fost gasit si reparat in functions.php, papetarie_storefront_edit_account_notice_hooks().
    // Verificam direct query var-ul brut pus de WP pentru acest endpoint.
    global $wp;
    $is_firmele_mele = function_exists('is_account_page') && is_account_page() && isset($wp->query_vars['firmele-mele']);
    if (!$is_firmele_mele) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pap_company_book_action'])) {
        return;
    }

    $result = papetarie_storefront_company_book_process_request($_POST);
    if (is_wp_error($result)) {
        foreach ($result->get_error_messages() as $message) {
            wc_add_notice($message, 'error');
        }

        $company_id = isset($_POST['pap_company_id']) ? sanitize_text_field(wp_unslash((string) $_POST['pap_company_id'])) : '';
        wp_safe_redirect(papetarie_storefront_company_book_form_url([
            'pap_company_action' => $company_id !== '' ? 'edit' : 'add',
            'pap_company_id' => $company_id !== '' ? $company_id : null,
        ]));
        exit;
    }

    if (!empty($result['message'])) {
        wc_add_notice((string) $result['message'], 'success');
    }

    wp_safe_redirect(papetarie_storefront_company_book_base_url());
    exit;
}
add_action('template_redirect', 'papetarie_storefront_handle_company_book_request', 25);

function papetarie_storefront_handle_company_book_ajax_request(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Trebuie să fii autentificat.', 'papetarie-storefront')], 403);
    }

    $result = papetarie_storefront_company_book_process_request($_POST);
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => $result->get_error_message(),
            'messages' => $result->get_error_messages(),
        ], 400);
    }

    if (!empty($result['message'])) {
        wc_add_notice((string) $result['message'], 'success');
    }

    wp_send_json_success($result);
}
add_action('wp_ajax_papetarie_storefront_company_book', 'papetarie_storefront_handle_company_book_ajax_request');

function papetarie_storefront_enqueue_company_book_script(): void
{
    $is_account_company_page = is_user_logged_in() && function_exists('is_account_page') && is_account_page();
    $is_checkout_page = function_exists('is_checkout') && is_checkout();
    if (!$is_account_company_page && !$is_checkout_page) {
        return;
    }

    $script_path = get_stylesheet_directory() . '/assets/js/company-book.js';
    $script_version = file_exists($script_path) ? (string) filemtime($script_path) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-company-book',
        get_stylesheet_directory_uri() . '/assets/js/company-book.js',
        ['jquery', 'papetarie-storefront-confirm-modal'],
        $script_version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-company-book',
        'papCompanyBookData',
        [
            'cityPlaceholder' => __('Alege localitatea', 'papetarie-storefront'),
            'countyFirstPlaceholder' => __('Alege județul întâi', 'papetarie-storefront'),
            'deleteConfirm' => __('Sigur vrei să ștergi această firmă?', 'papetarie-storefront'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxAction' => 'papetarie_storefront_company_book',
            'ajaxNonce' => wp_create_nonce('pap_company_book_save'),
            'lookupNonce' => wp_create_nonce('pap_lookup_cui'),
            'currentMode' => is_user_logged_in() ? papetarie_storefront_company_book_current_mode() : '',
            'currentCompanyId' => is_user_logged_in() ? papetarie_storefront_company_book_current_id() : '',
            'citiesByCounty' => new stdClass(),
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_company_book_script');
