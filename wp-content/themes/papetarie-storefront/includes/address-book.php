<?php

defined('ABSPATH') || exit;

function papetarie_storefront_address_book_meta_key(): string
{
    return 'papetarie_address_book';
}

function papetarie_storefront_address_book_default_id_meta_key(): string
{
    return 'papetarie_default_address_id';
}

function papetarie_storefront_address_book_form_state_key(): string
{
    return 'papetarie_address_book_form_state';
}

function papetarie_storefront_address_book_fields(): array
{
    return [
        'first_name' => [
            'label' => __('Prenume', 'papetarie-storefront'),
            'placeholder' => __('Prenume', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
            'autocomplete' => 'given-name',
        ],
        'last_name' => [
            'label' => __('Nume', 'papetarie-storefront'),
            'placeholder' => __('Nume', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
            'autocomplete' => 'family-name',
        ],
        'phone' => [
            'label' => __('Telefon', 'papetarie-storefront'),
            'placeholder' => __('0712 345 678', 'papetarie-storefront'),
            'required' => true,
            'type' => 'tel',
            'autocomplete' => 'tel',
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
            'label' => __('Adresă (stradă și număr)', 'papetarie-storefront'),
            'placeholder' => __('Strada Exemplu 12', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
            'autocomplete' => 'address-line1',
        ],
        'address_2' => [
            'label' => __('Bloc / Scară / Etaj / Apartament', 'papetarie-storefront'),
            'placeholder' => __('Bloc A, scara 1, etaj 2, apartament 10', 'papetarie-storefront'),
            'required' => false,
            'type' => 'text',
            'autocomplete' => 'address-line2',
        ],
        'postcode' => [
            'label' => __('Cod poștal', 'papetarie-storefront'),
            'placeholder' => __('123456', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
            'autocomplete' => 'postal-code',
        ],
    ];
}

function papetarie_storefront_address_book_empty_entry(): array
{
    return [
        'id' => '',
        'label' => '',
        'first_name' => '',
        'last_name' => '',
        'phone' => '',
        'company' => '',
        'country' => 'RO',
        'state' => '',
        'city' => '',
        'postcode' => '',
        'address_1' => '',
        'address_2' => '',
        'created_at' => '',
        'updated_at' => '',
        'source' => '',
    ];
}

function papetarie_storefront_address_book_sanitize_entry(array $entry): array
{
    $normalized = papetarie_storefront_address_book_empty_entry();

    foreach (array_keys($normalized) as $key) {
        if (!array_key_exists($key, $entry)) {
            continue;
        }

        $value = $entry[$key];
        if (in_array($key, ['created_at', 'updated_at', 'source', 'id'], true)) {
            $normalized[$key] = sanitize_text_field((string) $value);
            continue;
        }

        $normalized[$key] = sanitize_text_field((string) $value);
    }

    $normalized['country'] = $normalized['country'] !== '' ? strtoupper($normalized['country']) : 'RO';
    $normalized['state'] = strtoupper(sanitize_key($normalized['state']));
    $normalized['phone'] = preg_replace('/\s+/', ' ', trim((string) $normalized['phone']));
    $normalized['postcode'] = preg_replace('/\s+/', '', trim((string) $normalized['postcode']));
    $normalized['address_1'] = trim((string) $normalized['address_1']);
    $normalized['address_2'] = trim((string) $normalized['address_2']);
    $normalized['city'] = trim((string) $normalized['city']);
    $normalized['label'] = trim((string) $normalized['label']);
    $normalized['first_name'] = trim((string) $normalized['first_name']);
    $normalized['last_name'] = trim((string) $normalized['last_name']);
    $normalized['company'] = trim((string) $normalized['company']);

    return $normalized;
}

function papetarie_storefront_address_book_label(array $address): string
{
    $label = trim((string) ($address['label'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    $company = trim((string) ($address['company'] ?? ''));
    if ($company !== '') {
        return $company;
    }

    $full_name = trim((string) ($address['first_name'] ?? '') . ' ' . (string) ($address['last_name'] ?? ''));
    if ($full_name !== '') {
        return $full_name;
    }

    return __('Adresă salvată', 'papetarie-storefront');
}

function papetarie_storefront_address_book_session()
{
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return null;
    }

    return WC()->session;
}

function papetarie_storefront_address_book_set_form_state(array $state): void
{
    $session = papetarie_storefront_address_book_session();
    if (!$session) {
        return;
    }

    $session->set(papetarie_storefront_address_book_form_state_key(), $state);
}

function papetarie_storefront_address_book_get_form_state(): array
{
    $session = papetarie_storefront_address_book_session();
    if (!$session) {
        return [];
    }

    $state = $session->get(papetarie_storefront_address_book_form_state_key(), []);
    return is_array($state) ? $state : [];
}

function papetarie_storefront_address_book_clear_form_state(): void
{
    $session = papetarie_storefront_address_book_session();
    if (!$session) {
        return;
    }

    $session->set(papetarie_storefront_address_book_form_state_key(), []);
}

function papetarie_storefront_address_book_legacy_source(string $prefix, int $user_id): array
{
    $entry = papetarie_storefront_address_book_empty_entry();
    $entry['source'] = $prefix;
    $entry['id'] = 'legacy-' . $prefix;
    $entry['label'] = $prefix === 'shipping'
        ? __('Livrare', 'papetarie-storefront')
        : __('Facturare', 'papetarie-storefront');
    $entry['first_name'] = trim((string) get_user_meta($user_id, $prefix . '_first_name', true));
    $entry['last_name'] = trim((string) get_user_meta($user_id, $prefix . '_last_name', true));
    $entry['company'] = trim((string) get_user_meta($user_id, $prefix . '_company', true));
    $entry['phone'] = trim((string) get_user_meta($user_id, $prefix . '_phone', true));
    $entry['country'] = strtoupper(trim((string) get_user_meta($user_id, $prefix . '_country', true))) ?: 'RO';
    $entry['state'] = strtoupper(sanitize_key((string) get_user_meta($user_id, $prefix . '_state', true)));
    $entry['city'] = trim((string) get_user_meta($user_id, $prefix . '_city', true));
    $entry['postcode'] = trim((string) get_user_meta($user_id, $prefix . '_postcode', true));
    $entry['address_1'] = trim((string) get_user_meta($user_id, $prefix . '_address_1', true));

    return papetarie_storefront_address_book_sanitize_entry($entry);
}

function papetarie_storefront_address_book_legacy_seed(int $user_id): array
{
    $entries = [];
    $billing = papetarie_storefront_address_book_legacy_source('billing', $user_id);
    $shipping = papetarie_storefront_address_book_legacy_source('shipping', $user_id);

    $billing_has_data = trim($billing['address_1'] ?? '') !== '' || trim($billing['city'] ?? '') !== '' || trim($billing['postcode'] ?? '') !== '';
    $shipping_has_data = trim($shipping['address_1'] ?? '') !== '' || trim($shipping['city'] ?? '') !== '' || trim($shipping['postcode'] ?? '') !== '';

    if ($billing_has_data) {
        $billing['created_at'] = gmdate('c');
        $billing['updated_at'] = gmdate('c');
        $entries[] = $billing;
    }

    if ($shipping_has_data) {
            $same_as_billing = $billing_has_data
            && wp_json_encode(array_intersect_key($shipping, array_flip(['first_name', 'last_name', 'phone', 'company', 'country', 'state', 'city', 'postcode', 'address_1']))) === wp_json_encode(array_intersect_key($billing, array_flip(['first_name', 'last_name', 'phone', 'company', 'country', 'state', 'city', 'postcode', 'address_1'])));

        if (!$same_as_billing) {
            $shipping['created_at'] = gmdate('c');
            $shipping['updated_at'] = gmdate('c');
            $entries[] = $shipping;
        } elseif (!$billing_has_data) {
            $shipping['created_at'] = gmdate('c');
            $shipping['updated_at'] = gmdate('c');
            $entries[] = $shipping;
        }
    }

    return $entries;
}

function papetarie_storefront_address_book_get_all(int $user_id, bool $migrate_legacy = true): array
{
    $stored = get_user_meta($user_id, papetarie_storefront_address_book_meta_key(), true);
    $addresses = [];

    if (is_array($stored)) {
        foreach ($stored as $address) {
            if (!is_array($address)) {
                continue;
            }

            $normalized = papetarie_storefront_address_book_sanitize_entry($address);
            if ($normalized['id'] === '') {
                $normalized['id'] = 'addr_' . wp_generate_uuid4();
            }

            $addresses[] = $normalized;
        }
    }

    if (empty($addresses) && $migrate_legacy) {
        $addresses = papetarie_storefront_address_book_legacy_seed($user_id);
        if (!empty($addresses)) {
            $default_id = '';
            foreach ($addresses as $address) {
                if (($address['source'] ?? '') === 'shipping') {
                    $default_id = $address['id'];
                    break;
                }
            }
            if ($default_id === '' && !empty($addresses[0]['id'])) {
                $default_id = (string) $addresses[0]['id'];
            }

            update_user_meta($user_id, papetarie_storefront_address_book_meta_key(), $addresses);
            update_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), $default_id);
        }
    }

    $default_id = trim((string) get_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), true));
    if ($default_id === '' && !empty($addresses)) {
        $default_id = (string) $addresses[0]['id'];
        update_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), $default_id);
    }

    foreach ($addresses as &$address) {
        $address['is_default'] = $default_id !== '' && (string) ($address['id'] ?? '') === $default_id;
    }
    unset($address);

    return $addresses;
}

function papetarie_storefront_address_book_save_all(int $user_id, array $addresses, string $default_id = ''): void
{
    $clean_addresses = [];
    foreach ($addresses as $address) {
        if (!is_array($address)) {
            continue;
        }

        $normalized = papetarie_storefront_address_book_sanitize_entry($address);
        if ($normalized['id'] === '') {
            $normalized['id'] = 'addr_' . wp_generate_uuid4();
        }
        $clean_addresses[] = $normalized;
    }

    if ($default_id === '' && !empty($clean_addresses)) {
        $default_id = (string) $clean_addresses[0]['id'];
    }

    update_user_meta($user_id, papetarie_storefront_address_book_meta_key(), $clean_addresses);
    update_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), $default_id);
}

function papetarie_storefront_address_book_get(int $user_id, string $address_id): ?array
{
    $address_id = trim($address_id);
    if ($address_id === '') {
        return null;
    }

    foreach (papetarie_storefront_address_book_get_all($user_id) as $address) {
        if ((string) ($address['id'] ?? '') === $address_id) {
            return $address;
        }
    }

    return null;
}

function papetarie_storefront_address_book_save_entry(int $user_id, array $posted, string $address_id = '', ?string $default_id = null): array
{
    $addresses = papetarie_storefront_address_book_get_all($user_id);
    $existing = null;
    $existing_index = null;

    if ($address_id !== '') {
        foreach ($addresses as $index => $address) {
            if ((string) ($address['id'] ?? '') === $address_id) {
                $existing = $address;
                $existing_index = $index;
                break;
            }
        }
    }

    $entry = papetarie_storefront_address_book_empty_entry();
    if (is_array($existing)) {
        $entry = array_merge($entry, $existing);
    }

    $entry = array_merge($entry, papetarie_storefront_address_book_sanitize_entry($posted));
    $entry['id'] = $existing['id'] ?? ($address_id !== '' ? $address_id : 'addr_' . wp_generate_uuid4());
    $entry['created_at'] = $existing['created_at'] ?? gmdate('c');
    $entry['updated_at'] = gmdate('c');

    if ($existing_index !== null) {
        $addresses[$existing_index] = $entry;
    } else {
        $addresses[] = $entry;
    }

    $current_default_id = trim((string) get_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), true));
    if ($default_id === null) {
        $default_id = $current_default_id;
    }

    papetarie_storefront_address_book_save_all($user_id, $addresses, $default_id);

    return papetarie_storefront_address_book_get($user_id, (string) $entry['id']) ?? $entry;
}

function papetarie_storefront_address_book_delete_entry(int $user_id, string $address_id): bool
{
    $address_id = trim($address_id);
    if ($address_id === '') {
        return false;
    }

    $addresses = papetarie_storefront_address_book_get_all($user_id);
    $filtered = [];
    $removed = false;

    foreach ($addresses as $address) {
        if ((string) ($address['id'] ?? '') === $address_id) {
            $removed = true;
            continue;
        }

        $filtered[] = $address;
    }

    if (!$removed) {
        return false;
    }

    $default_id = trim((string) get_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), true));
    if ($default_id === $address_id) {
        $default_id = !empty($filtered) ? (string) $filtered[0]['id'] : '';
    }

    papetarie_storefront_address_book_save_all($user_id, $filtered, $default_id);

    return true;
}

function papetarie_storefront_address_book_has_items(int $user_id): bool
{
    return !empty(papetarie_storefront_address_book_get_all($user_id));
}

function papetarie_storefront_address_book_default_address(int $user_id): ?array
{
    $addresses = papetarie_storefront_address_book_get_all($user_id);
    if (empty($addresses)) {
        return null;
    }

    foreach ($addresses as $address) {
        if (!empty($address['is_default'])) {
            return $address;
        }
    }

    return $addresses[0];
}

function papetarie_storefront_address_book_checkout_selection_key(string $prefix): string
{
    return 'papetarie_checkout_selected_address_' . ('shipping' === $prefix ? 'shipping' : 'billing');
}

function papetarie_storefront_address_book_checkout_selected_address_id(string $prefix): string
{
    $session = papetarie_storefront_address_book_session();
    if (!$session) {
        return '';
    }

    $selected = trim((string) $session->get(papetarie_storefront_address_book_checkout_selection_key($prefix), ''));
    return sanitize_text_field($selected);
}

function papetarie_storefront_address_book_checkout_set_selected_address_id(string $prefix, string $address_id): void
{
    $session = papetarie_storefront_address_book_session();
    if (!$session) {
        return;
    }

    $session->set(papetarie_storefront_address_book_checkout_selection_key($prefix), sanitize_text_field(trim($address_id)));
}

function papetarie_storefront_address_book_checkout_clear_selected_address_id(string $prefix): void
{
    papetarie_storefront_address_book_checkout_set_selected_address_id($prefix, '');
}

function papetarie_storefront_address_book_checkout_selected_address(int $user_id, string $prefix): ?array
{
    $selected_id = papetarie_storefront_address_book_checkout_selected_address_id($prefix);
    $selected = $selected_id !== '' ? papetarie_storefront_address_book_get($user_id, $selected_id) : null;

    if (is_array($selected)) {
        return $selected;
    }

    if ($prefix === 'shipping') {
        $billing_selected_id = papetarie_storefront_address_book_checkout_selected_address_id('billing');
        $billing_selected = $billing_selected_id !== '' ? papetarie_storefront_address_book_get($user_id, $billing_selected_id) : null;
        if (is_array($billing_selected)) {
            return $billing_selected;
        }
    }

    return papetarie_storefront_address_book_default_address($user_id);
}

function papetarie_storefront_address_book_format_lines(array $address): array
{
    $lines = [];
    $full_name = trim((string) ($address['first_name'] ?? '') . ' ' . (string) ($address['last_name'] ?? ''));
    $company = trim((string) ($address['company'] ?? ''));
    $address_line = trim((string) ($address['address_1'] ?? ''));
    $address_line_2 = trim((string) ($address['address_2'] ?? ''));
    $state_code = sanitize_key((string) ($address['state'] ?? ''));
    $country_code = strtoupper(trim((string) ($address['country'] ?? '')));
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
    $countries = function_exists('papetarie_storefront_country_options') ? papetarie_storefront_country_options() : [];
    $state_label = $state_code !== '' && isset($counties[$state_code]) ? $counties[$state_code] : $state_code;
    $country_label = $country_code !== '' && isset($countries[$country_code]) ? $countries[$country_code] : $country_code;

    if ($company !== '') {
        $lines[] = $company;
    } elseif ($full_name !== '') {
        $lines[] = $full_name;
    }

    if ($address_line !== '') {
        $lines[] = $address_line;
    }

    if ($address_line_2 !== '') {
        $lines[] = $address_line_2;
    }

    $city_parts = array_filter([
        trim((string) ($address['city'] ?? '')),
        $state_label,
        trim((string) ($address['postcode'] ?? '')),
    ]);

    if (!empty($city_parts)) {
        $lines[] = implode(', ', $city_parts);
    }

    if ($country_code !== '' && $country_code !== 'RO' && $country_label !== '') {
        $lines[] = $country_label;
    }

    return $lines;
}

function papetarie_storefront_address_book_checkout_field_map(): array
{
    return [
        'billing_first_name' => 'first_name',
        'billing_last_name' => 'last_name',
        'billing_company' => 'company',
        'billing_phone' => 'phone',
        'billing_country' => 'country',
        'billing_state' => 'state',
        'billing_city' => 'city',
        'billing_postcode' => 'postcode',
        'billing_address_1' => 'address_1',
        'billing_address_2' => 'address_2',
        'shipping_first_name' => 'first_name',
        'shipping_last_name' => 'last_name',
        'shipping_company' => 'company',
        'shipping_phone' => 'phone',
        'shipping_country' => 'country',
        'shipping_state' => 'state',
        'shipping_city' => 'city',
        'shipping_postcode' => 'postcode',
        'shipping_address_1' => 'address_1',
        'shipping_address_2' => 'address_2',
    ];
}

function papetarie_storefront_address_book_checkout_field_value($value, string $input)
{
    if (!function_exists('is_checkout') || !is_checkout() || !is_user_logged_in()) {
        return $value;
    }

    $customer_id = get_current_user_id();
    $map = papetarie_storefront_address_book_checkout_field_map();
    $address = null;

    if (isset($map[$input])) {
        $prefix = str_starts_with($input, 'shipping_') ? 'shipping' : 'billing';
        $address = papetarie_storefront_address_book_checkout_selected_address($customer_id, $prefix);
    }

    if (!$address) {
        return $value;
    }

    if (!isset($map[$input])) {
        return $value;
    }

    $current_value = is_string($value) ? trim($value) : $value;
    $field_key = $map[$input];
    $address_value = trim((string) ($address[$field_key] ?? ''));

    if ($address_value === '') {
        if ($input === 'billing_phone' || $input === 'shipping_phone') {
            $address_value = trim((string) get_user_meta($customer_id, 'billing_phone', true));
        }
    }

    if ($address_value === '') {
        return $value;
    }

    $country_options = function_exists('papetarie_storefront_country_options') ? papetarie_storefront_country_options() : [];
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];

    if (($input === 'billing_country' || $input === 'shipping_country') && isset($country_options[$current_value])) {
        return $address_value;
    }

    if (($input === 'billing_state' || $input === 'shipping_state') && isset($counties[$address_value])) {
        return $address_value;
    }

    return $address_value;
}
add_filter('woocommerce_checkout_get_value', 'papetarie_storefront_address_book_checkout_field_value', 20, 2);

function papetarie_storefront_address_book_checkout_selection_options(int $user_id): array
{
    $options = [];

    foreach (papetarie_storefront_address_book_get_all($user_id) as $address) {
        $address_id = (string) ($address['id'] ?? '');
        if ($address_id === '') {
            continue;
        }

        $label = papetarie_storefront_address_book_label($address);
        $lines = array_filter(papetarie_storefront_address_book_format_lines($address));
        $summary = '';
        if (!empty($lines)) {
            $summary = implode(' • ', array_slice($lines, 1));
        }

        $options[$address_id] = trim($label . ($summary !== '' ? ' — ' . $summary : ''));
    }

    return $options;
}

function papetarie_storefront_address_book_checkout_render_selector(string $prefix): string
{
    if (!is_user_logged_in()) {
        return '';
    }

    $user_id = get_current_user_id();
    $options = papetarie_storefront_address_book_checkout_selection_options($user_id);
    if (empty($options)) {
        return '';
    }

    $selected_id = papetarie_storefront_address_book_checkout_selected_address_id($prefix);
    $selected_address = $selected_id !== '' ? papetarie_storefront_address_book_get($user_id, $selected_id) : null;
    if (!$selected_address && $prefix === 'shipping') {
        $selected_address = papetarie_storefront_address_book_checkout_selected_address($user_id, $prefix);
        $selected_id = (string) ($selected_address['id'] ?? '');
    } elseif (!$selected_address) {
        $selected_address = papetarie_storefront_address_book_default_address($user_id);
        $selected_id = (string) ($selected_address['id'] ?? '');
    }

    $label = $prefix === 'shipping'
        ? __('Alege adresa de livrare salvată', 'papetarie-storefront')
        : __('Alege adresa de facturare salvată', 'papetarie-storefront');
    $help = $prefix === 'shipping'
        ? __('Alege rapid o adresă salvată pentru livrare. Când livrarea diferă, selectorul devine activ.', 'papetarie-storefront')
        : __('Alege rapid o adresă salvată pentru facturare.', 'papetarie-storefront');
    $field_id = 'pap-checkout-saved-address-' . $prefix;
    $selected_id = isset($options[$selected_id]) ? $selected_id : array_key_first($options);

    ob_start();
    ?>
    <div class="pap-checkout-address-selector" data-checkout-address-selector-shell="<?php echo esc_attr($prefix); ?>">
      <label class="pap-checkout-address-selector__label" for="<?php echo esc_attr($field_id); ?>">
        <?php echo esc_html($label); ?>
      </label>
      <select
        id="<?php echo esc_attr($field_id); ?>"
        class="pap-checkout-address-selector__select"
        name="<?php echo esc_attr(papetarie_storefront_address_book_checkout_selection_key($prefix)); ?>"
        data-checkout-address-selector
        data-checkout-address-prefix="<?php echo esc_attr($prefix); ?>"
      >
        <?php foreach ($options as $option_id => $option_label) : ?>
          <option value="<?php echo esc_attr($option_id); ?>" <?php selected($selected_id, $option_id); ?>>
            <?php echo esc_html($option_label); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <p class="pap-checkout-address-selector__help"><?php echo esc_html($help); ?></p>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_address_book_checkout_selection_data(): array
{
    if (!is_user_logged_in()) {
        return [];
    }

    $user_id = get_current_user_id();
    $addresses = [];

    foreach (papetarie_storefront_address_book_get_all($user_id) as $address) {
        $address_id = (string) ($address['id'] ?? '');
        if ($address_id === '') {
            continue;
        }

        $addresses[$address_id] = [
            'id' => $address_id,
            'label' => papetarie_storefront_address_book_label($address),
            'first_name' => (string) ($address['first_name'] ?? ''),
            'last_name' => (string) ($address['last_name'] ?? ''),
            'phone' => (string) ($address['phone'] ?? ''),
            'company' => (string) ($address['company'] ?? ''),
            'country' => (string) ($address['country'] ?? ''),
            'state' => (string) ($address['state'] ?? ''),
            'city' => (string) ($address['city'] ?? ''),
            'postcode' => (string) ($address['postcode'] ?? ''),
            'address_1' => (string) ($address['address_1'] ?? ''),
            'address_2' => (string) ($address['address_2'] ?? ''),
            'is_default' => !empty($address['is_default']),
        ];
    }

    return $addresses;
}

function papetarie_storefront_address_book_checkout_email(int $user_id): string
{
    $billing_email = sanitize_email((string) get_user_meta($user_id, 'billing_email', true));
    if ($billing_email !== '') {
        return $billing_email;
    }

    $user = get_userdata($user_id);
    return $user instanceof WP_User ? sanitize_email((string) $user->user_email) : '';
}

function papetarie_storefront_address_book_sync_customer(int $user_id, array $address, string $email = ''): void
{
    $field_map = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'company' => 'company',
        'phone' => 'phone',
        'country' => 'country',
        'state' => 'state',
        'city' => 'city',
        'postcode' => 'postcode',
        'address_1' => 'address_1',
        'address_2' => 'address_2',
    ];

    foreach ($field_map as $meta_key => $address_key) {
        $value = sanitize_text_field((string) ($address[$address_key] ?? ''));
        update_user_meta($user_id, 'shipping_' . $meta_key, $value);

        if (in_array($meta_key, ['first_name', 'last_name', 'company', 'phone'], true)) {
            update_user_meta($user_id, 'billing_' . $meta_key, $value);
        }
    }

    $email = sanitize_email($email);
    if ($email !== '') {
        update_user_meta($user_id, 'billing_email', $email);
    }

    if (!function_exists('WC') || !WC() || !WC()->customer || (int) WC()->customer->get_id() !== $user_id) {
        return;
    }

    $customer = WC()->customer;
    foreach ($field_map as $meta_key => $address_key) {
        $setter = 'set_shipping_' . $meta_key;
        if (method_exists($customer, $setter)) {
            $customer->{$setter}((string) ($address[$address_key] ?? ''));
        }
    }

    if ($email !== '' && method_exists($customer, 'set_billing_email')) {
        $customer->set_billing_email($email);
    }

    $customer->save();
}

function papetarie_storefront_address_book_sync_checkout_selection_from_request(array $data): void
{
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();
    $has_shipping_difference = !empty($data['ship_to_different_address']);
    $default_address = papetarie_storefront_address_book_default_address($user_id);
    $default_id = (string) ($default_address['id'] ?? '');

    $billing_id = isset($data[papetarie_storefront_address_book_checkout_selection_key('billing')])
        ? sanitize_text_field((string) $data[papetarie_storefront_address_book_checkout_selection_key('billing')])
        : '';
    $shipping_id = isset($data[papetarie_storefront_address_book_checkout_selection_key('shipping')])
        ? sanitize_text_field((string) $data[papetarie_storefront_address_book_checkout_selection_key('shipping')])
        : '';

    $billing_address = $billing_id !== '' ? papetarie_storefront_address_book_get($user_id, $billing_id) : null;
    if (!$billing_address && $default_id !== '') {
        $billing_id = $default_id;
    }

    if (!$has_shipping_difference) {
        $shipping_id = $billing_id;
    } else {
        $shipping_address = $shipping_id !== '' ? papetarie_storefront_address_book_get($user_id, $shipping_id) : null;
        if (!$shipping_address) {
            $shipping_id = $billing_id !== '' ? $billing_id : $default_id;
        }
    }

    if ($billing_id !== '') {
        papetarie_storefront_address_book_checkout_set_selected_address_id('billing', $billing_id);
    } else {
        papetarie_storefront_address_book_checkout_clear_selected_address_id('billing');
    }

    if ($shipping_id !== '') {
        papetarie_storefront_address_book_checkout_set_selected_address_id('shipping', $shipping_id);
    } else {
        papetarie_storefront_address_book_checkout_clear_selected_address_id('shipping');
    }
}

function papetarie_storefront_address_book_sync_checkout_selection_review(string $posted_data): void
{
    if ($posted_data === '') {
        return;
    }

    $data = [];
    parse_str($posted_data, $data);

    if (is_array($data)) {
        papetarie_storefront_address_book_sync_checkout_selection_from_request($data);
    }
}
add_action('woocommerce_checkout_update_order_review', 'papetarie_storefront_address_book_sync_checkout_selection_review', 20, 1);

function papetarie_storefront_address_book_sync_checkout_selection_validation(array $data, WP_Error $errors): void
{
    papetarie_storefront_address_book_sync_checkout_selection_from_request($data);
}
add_action('woocommerce_after_checkout_validation', 'papetarie_storefront_address_book_sync_checkout_selection_validation', 1, 2);

function papetarie_storefront_address_book_validate(array $posted, \WP_Error $errors): array
{
    $fields = papetarie_storefront_address_book_fields();
    $clean = [];
    $counties = function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : [];
    $countries = function_exists('papetarie_storefront_country_options') ? papetarie_storefront_country_options() : ['RO' => 'Romania'];

    foreach (array_keys($fields) as $field_key) {
        $raw = isset($posted[$field_key]) ? wp_unslash((string) $posted[$field_key]) : '';

        $clean[$field_key] = sanitize_text_field($raw);
    }
    $clean['country'] = isset($posted['country'])
        ? strtoupper(sanitize_text_field(wp_unslash((string) $posted['country'])))
        : 'RO';

    if (($clean['first_name'] ?? '') === '') {
        $errors->add('address_first_name_required', __('Completează prenumele.', 'papetarie-storefront'));
    }

    if (($clean['last_name'] ?? '') === '') {
        $errors->add('address_last_name_required', __('Completează numele.', 'papetarie-storefront'));
    }

    if (($clean['phone'] ?? '') === '') {
        $errors->add('address_phone_required', __('Completează numărul de telefon.', 'papetarie-storefront'));
    } else {
        $phone_digits = preg_replace('/\D+/', '', $clean['phone']);
        if (strlen($phone_digits) < 8) {
            $errors->add('address_phone_invalid', __('Numărul de telefon nu pare valid.', 'papetarie-storefront'));
        }
    }

    if (($clean['country'] ?? 'RO') === '') {
        $errors->add('address_country_required', __('Selectează țara.', 'papetarie-storefront'));
    } elseif (!isset($countries[$clean['country']])) {
        $errors->add('address_country_invalid', __('Țara selectată nu este validă.', 'papetarie-storefront'));
    }

    if (($clean['state'] ?? '') === '') {
        $errors->add('address_state_required', __('Selectează județul / regiunea.', 'papetarie-storefront'));
    } elseif (($clean['country'] ?? 'RO') === 'RO' && !isset($counties[$clean['state']])) {
        $errors->add('address_state_invalid', __('Județul selectat nu este valid.', 'papetarie-storefront'));
    }

    if (($clean['city'] ?? '') === '') {
        $errors->add('address_city_required', __('Completează orașul.', 'papetarie-storefront'));
    } elseif (($clean['country'] ?? 'RO') === 'RO' && ($clean['state'] ?? '') !== '') {
        $county_cities = function_exists('papetarie_storefront_checkout_city_options_for_county')
            ? papetarie_storefront_checkout_city_options_for_county((string) $clean['state'])
            : [];

        if (!empty($county_cities)) {
            $normalized_city = function_exists('papetarie_storefront_normalize_city_key')
                ? papetarie_storefront_normalize_city_key((string) $clean['city'])
                : strtolower(trim((string) $clean['city']));

            $city_matches = false;
            foreach (array_keys($county_cities) as $city_option) {
                $normalized_option = function_exists('papetarie_storefront_normalize_city_key')
                    ? papetarie_storefront_normalize_city_key((string) $city_option)
                    : strtolower(trim((string) $city_option));

                if ($normalized_option === $normalized_city) {
                    $clean['city'] = (string) $city_option;
                    $city_matches = true;
                    break;
                }
            }

            if (!$city_matches) {
                $errors->add('address_city_invalid', __('Orașul selectat nu aparține județului ales.', 'papetarie-storefront'));
            }
        }
    }

    if (($clean['postcode'] ?? '') === '') {
        $errors->add('address_postcode_required', __('Completează codul poștal.', 'papetarie-storefront'));
    } elseif (!preg_match('/^[0-9]{6}$/', preg_replace('/\s+/', '', (string) $clean['postcode']))) {
        $errors->add('address_postcode_invalid', __('Codul poștal trebuie să aibă 6 cifre.', 'papetarie-storefront'));
    } else {
        $clean['postcode'] = preg_replace('/\s+/', '', (string) $clean['postcode']);
    }

    if (($clean['address_1'] ?? '') === '') {
        $errors->add('address_line_1_required', __('Completează prima linie a adresei.', 'papetarie-storefront'));
    }

    return $clean;
}

function papetarie_storefront_address_book_render_input(string $key, array $field, string $value, array $context = []): void
{
    $required = !empty($field['required']);
    $args = [
        'label' => $field['label'] ?? $key,
        'required' => $required,
        'class' => ['form-row-wide', 'pap-address-form-field', 'pap-address-form-field--' . $key],
        'input_class' => ['input-text'],
        'custom_attributes' => [],
        'type' => $field['type'] ?? 'text',
    ];

    if (!empty($field['placeholder'])) {
        $args['placeholder'] = $field['placeholder'];
    }

    if (!empty($field['autocomplete'])) {
        $args['custom_attributes']['autocomplete'] = $field['autocomplete'];
    }

    if ($key === 'country') {
        $args['type'] = 'select';
        $args['options'] = function_exists('papetarie_storefront_country_options') ? papetarie_storefront_country_options() : ['RO' => __('România', 'papetarie-storefront')];
    } elseif ($key === 'state') {
        $args['type'] = 'select';
        $args['options'] = ['' => $field['placeholder'] ?? __('Alege județul', 'papetarie-storefront')] + (function_exists('papetarie_storefront_romania_counties') ? papetarie_storefront_romania_counties() : []);
        $args['custom_attributes']['data-address-book-state'] = '1';
    } elseif ($key === 'city') {
        $args['type'] = 'select';
        $args['custom_attributes']['data-address-book-city'] = '1';
        $state = trim((string) ($context['state'] ?? ''));
        if ($state !== '') {
            $args['options'] = ['' => $field['placeholder'] ?? __('Alege localitatea', 'papetarie-storefront')]
                + (function_exists('papetarie_storefront_checkout_city_options_for_county') ? papetarie_storefront_checkout_city_options_for_county($state) : []);
        } else {
            $args['options'] = ['' => __('Alege județul întâi', 'papetarie-storefront')];
            $args['custom_attributes']['disabled'] = 'disabled';
            $args['custom_attributes']['aria-disabled'] = 'true';
        }
    }

    if ($key === 'country') {
        $args['custom_attributes']['data-address-book-country'] = '1';
    }

    $args['return'] = true;
    $field_html = woocommerce_form_field($key, $args, $value);

    printf(
        '<div class="pap-address-form-row pap-address-form-row--%1$s">%2$s</div>',
        esc_attr($key),
        $field_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    );
}

function papetarie_storefront_address_book_base_url(): string
{
    if (function_exists('wc_get_endpoint_url')) {
        return wc_get_endpoint_url('edit-address');
    }

    return home_url('/my-account/edit-address/');
}

function papetarie_storefront_address_book_form_url(array $query_args = []): string
{
    $url = papetarie_storefront_address_book_base_url();
    return !empty($query_args) ? add_query_arg($query_args, $url) : $url;
}

function papetarie_storefront_address_book_current_mode(): string
{
    $mode = isset($_GET['pap_address_action']) ? sanitize_key(wp_unslash((string) $_GET['pap_address_action'])) : '';
    return in_array($mode, ['add', 'edit'], true) ? $mode : 'list';
}

function papetarie_storefront_address_book_current_id(): string
{
    return isset($_GET['pap_address_id']) ? sanitize_text_field(wp_unslash((string) $_GET['pap_address_id'])) : '';
}

function papetarie_storefront_address_book_tab_url(string $tab, array $query_args = []): string
{
    $query_args['pap_address_type'] = 'shipping';
    return papetarie_storefront_address_book_form_url($query_args);
}

function papetarie_storefront_render_info_alert(string $message, array $args = []): string
{
    $title = isset($args['title']) ? trim((string) $args['title']) : '';
    $icon_class = isset($args['icon_class']) ? trim((string) $args['icon_class']) : 'fa-regular fa-circle-info';

    ob_start();
    ?>
    <div class="pap-info-alert" role="status" aria-live="polite">
      <i class="pap-info-alert__icon <?php echo esc_attr($icon_class); ?>" aria-hidden="true"></i>
      <div class="pap-info-alert__copy">
        <?php if ($title !== '') : ?>
          <strong class="pap-info-alert__title"><?php echo esc_html($title); ?></strong>
        <?php endif; ?>
        <p class="pap-info-alert__text"><?php echo wp_kses_post($message); ?></p>
      </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_address_book_render_form_notice(): void
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

function papetarie_storefront_address_book_render_form_fields(array $source): void
{
    $fields = papetarie_storefront_address_book_fields();
    foreach ($fields as $key => $field) :
        $value = (string) ($source[$key] ?? '');
        if ($key === 'state') {
            $value = strtoupper(sanitize_key($value));
        } elseif ($key === 'country') {
            $value = strtoupper($value ?: 'RO');
        } elseif ($key === 'city' && $value !== '') {
            $state_value = sanitize_key((string) ($source['state'] ?? ''));
            if ($state_value !== '' && function_exists('papetarie_storefront_checkout_city_options_for_county')) {
                $city_options = papetarie_storefront_checkout_city_options_for_county($state_value);
                foreach (array_keys($city_options) as $city_option) {
                    if (function_exists('papetarie_storefront_normalize_city_key') && papetarie_storefront_normalize_city_key((string) $city_option) === papetarie_storefront_normalize_city_key($value)) {
                        $value = (string) $city_option;
                        break;
                    }
                }
            }
        }
        ?>
        <?php papetarie_storefront_address_book_render_input($key, $field, $value, ['state' => (string) ($source['state'] ?? '')]); ?>
    <?php endforeach;
}

function papetarie_storefront_address_book_render_form(array $address = [], string $mode = 'add'): void
{
    $address = array_merge(papetarie_storefront_address_book_empty_entry(), $address);
    $is_edit = $mode === 'edit' && !empty($address['id']);
    $action_url = papetarie_storefront_address_book_form_url([
        'pap_address_action' => $is_edit ? 'edit' : 'add',
        'pap_address_id' => $is_edit ? $address['id'] : null,
        'pap_address_type' => 'shipping',
    ]);
    ?>
    <form class="pap-account-form pap-account-address-form" method="post" action="<?php echo esc_url($action_url); ?>" novalidate data-address-book-modal-form>
        <?php wp_nonce_field('pap_address_book_save', 'pap_address_book_nonce'); ?>
        <input type="hidden" name="pap_address_book_action" value="save">
        <input type="hidden" name="pap_address_id" value="<?php echo esc_attr((string) $address['id']); ?>">
        <input type="hidden" name="country" value="<?php echo esc_attr((string) ($address['country'] ?? 'RO')); ?>">
        <input type="hidden" name="pap_address_type" value="shipping">
        <input type="hidden" name="pap_address_is_default" value="0">

        <?php $form_state = papetarie_storefront_address_book_get_form_state(); ?>
        <?php $source = !empty($form_state) ? array_merge($address, $form_state) : $address; ?>

        <div class="pap-account-address-modal__notice-wrap" data-address-book-form-notice></div>

        <div class="pap-account-address-form-grid pap-account-address-form-grid--modal">
          <?php papetarie_storefront_address_book_render_form_fields($source); ?>
        </div>

        <div class="pap-account-address-form-options">
          <label class="pap-account-address-default-toggle">
            <input type="checkbox" name="pap_address_is_default" value="1" <?php checked(!empty($source['is_default'])); ?>>
            <span class="pap-account-address-default-toggle__box" aria-hidden="true"></span>
            <span class="pap-account-address-default-toggle__copy">
              <strong><?php esc_html_e('Setează ca adresă implicită', 'papetarie-storefront'); ?></strong>
              <span><?php esc_html_e('Această adresă va fi selectată automat la checkout.', 'papetarie-storefront'); ?></span>
            </span>
          </label>
        </div>

        <div class="pap-account-address-form-actions">
          <button type="button" class="pap-account-secondary-button" data-address-book-modal-close>
            <?php esc_html_e('Anulează', 'papetarie-storefront'); ?>
          </button>
          <button type="submit" class="pap-account-primary-button">
            <?php echo esc_html($is_edit ? __('Salvează adresa', 'papetarie-storefront') : __('Salvează adresa', 'papetarie-storefront')); ?>
          </button>
        </div>
      </form>
    <?php
}

function papetarie_storefront_address_book_render_list(array $addresses): void
{
    if (empty($addresses)) {
        return;
    }
    ?>
    <div class="pap-account-address-grid">
      <?php foreach ($addresses as $address) : ?>
        <?php
        $lines = papetarie_storefront_address_book_format_lines($address);
        $address_id = (string) ($address['id'] ?? '');
        $address_json = wp_json_encode($address, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ?>
        <article class="pap-account-address-card">
          <div class="pap-account-address-card__head">
            <div class="pap-account-address-card__head-copy">
              <h3><?php echo esc_html(papetarie_storefront_address_book_label($address)); ?></h3>
              <?php if (!empty($address['is_default'])) : ?>
                <span class="pap-account-address-card__badge"><?php esc_html_e('Implicită', 'papetarie-storefront'); ?></span>
              <?php endif; ?>
            </div>
            <a
              class="pap-account-row-action"
              href="<?php echo esc_url(papetarie_storefront_address_book_form_url(['pap_address_action' => 'edit', 'pap_address_id' => $address_id, 'pap_address_type' => 'shipping'])); ?>"
              data-address-book-open-modal
              data-address-book-mode="edit"
              data-address-book-id="<?php echo esc_attr($address_id); ?>"
              data-address-book-entry="<?php echo esc_attr($address_json ?: '{}'); ?>"
            >
              <?php esc_html_e('Editează', 'papetarie-storefront'); ?>
            </a>
          </div>

          <div class="pap-account-address-card__content">
            <?php if (!empty($lines)) : ?>
              <?php foreach ($lines as $line) : ?>
                <p><?php echo esc_html($line); ?></p>
              <?php endforeach; ?>
            <?php else : ?>
              <p><?php esc_html_e('Adresa este goală.', 'papetarie-storefront'); ?></p>
            <?php endif; ?>
          </div>

          <div class="pap-account-address-card__actions">
            <form method="post" action="<?php echo esc_url(papetarie_storefront_address_book_base_url()); ?>" data-address-delete-form>
              <?php wp_nonce_field('pap_address_book_save', 'pap_address_book_nonce'); ?>
              <input type="hidden" name="pap_address_book_action" value="delete">
              <input type="hidden" name="pap_address_id" value="<?php echo esc_attr($address_id); ?>">
              <input type="hidden" name="pap_address_type" value="shipping">
              <button
                type="submit"
                class="pap-account-row-action pap-account-row-action--danger"
                onclick="return confirm('<?php echo esc_js(__('Sigur vrei să ștergi această adresă?', 'papetarie-storefront')); ?>');"
              >
                <?php esc_html_e('Șterge', 'papetarie-storefront'); ?>
              </button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
  </div>
  <?php
}

function papetarie_storefront_address_book_render_empty_section(): void
{
    $message = __('Nu ai adăugat încă nicio adresă de livrare. Apasă pe „Adaugă adresă” pentru a salva prima adresă.', 'papetarie-storefront');
    papetarie_storefront_render_account_tab_section(
        'pap-account-address-section',
        static function () use ($message): void {
            echo papetarie_storefront_render_info_alert($message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    );
}

function papetarie_storefront_address_book_render_active_section(array $addresses): void
{
    papetarie_storefront_render_account_tab_section(
        'pap-account-address-section',
        static function () use ($addresses): void {
            papetarie_storefront_address_book_render_list($addresses);
        }
    );
}

function papetarie_storefront_address_book_render_panel_content(array $addresses): void
{
    if (empty($addresses)) {
        papetarie_storefront_address_book_render_empty_section();
        return;
    }

    papetarie_storefront_address_book_render_active_section($addresses);
}

function papetarie_storefront_address_book_render_panel_html(array $addresses): string
{
    ob_start();
    if (empty($addresses)) {
        echo papetarie_storefront_render_info_alert(
            __('Nu ai adăugat încă nicio adresă de livrare. Apasă pe „Adaugă adresă” pentru a salva prima adresă.', 'papetarie-storefront')
        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        papetarie_storefront_address_book_render_list($addresses);
    }
    return (string) ob_get_clean();
}

function papetarie_storefront_address_book_render_modal_html(array $address = [], string $mode = 'add', bool $is_open = false): string
{
    $address = array_merge(papetarie_storefront_address_book_empty_entry(), $address);
    $mode = in_array($mode, ['add', 'edit'], true) ? $mode : 'add';
    $should_open = (bool) $is_open;
    $title = $mode === 'edit' ? __('Editează adresă', 'papetarie-storefront') : __('Adaugă adresă', 'papetarie-storefront');
    $subtitle = __('Completează datele pentru livrare.', 'papetarie-storefront');
    $action_url = papetarie_storefront_address_book_form_url([
        'pap_address_action' => $mode,
        'pap_address_id' => $mode === 'edit' ? (string) $address['id'] : null,
        'pap_address_type' => 'shipping',
    ]);

    ob_start();
    ?>
    <div
      class="pap-account-address-modal<?php echo $should_open ? ' is-open' : ''; ?>"
      id="pap-account-address-modal"
      data-address-book-modal
      <?php echo $should_open ? '' : 'hidden'; ?>
      aria-hidden="<?php echo $should_open ? 'false' : 'true'; ?>"
      data-address-book-open-on-load="<?php echo $should_open ? '1' : '0'; ?>"
    >
      <div class="pap-account-address-modal__backdrop" data-address-book-modal-close aria-hidden="true"></div>
      <div class="pap-account-address-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pap-account-address-modal-title">
        <div class="pap-account-address-modal__head">
          <div class="pap-account-address-modal__head-copy">
            <h2 id="pap-account-address-modal-title"><?php echo esc_html($title); ?></h2>
            <p><?php echo esc_html($subtitle); ?></p>
          </div>
          <button type="button" class="pap-account-address-modal__close" aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>" data-address-book-modal-close>×</button>
        </div>
        <div class="pap-account-address-modal__body">
          <?php papetarie_storefront_address_book_render_form_notice(); ?>
          <?php papetarie_storefront_address_book_render_form($address, $mode); ?>
        </div>
      </div>
    </div>
    <?php

    return (string) ob_get_clean();
}

function papetarie_storefront_address_book_process_request(array $request): array|WP_Error
{
    if (!is_user_logged_in()) {
        return new WP_Error('address_not_logged_in', __('Trebuie să fii autentificat pentru a gestiona adresele.', 'papetarie-storefront'));
    }

    $nonce = isset($request['pap_address_book_nonce']) ? sanitize_text_field(wp_unslash((string) $request['pap_address_book_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_address_book_save')) {
        return new WP_Error('address_nonce_invalid', __('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'));
    }

    $user_id = get_current_user_id();
    $action = sanitize_key(wp_unslash((string) ($request['pap_address_book_action'] ?? '')));
    $address_id = isset($request['pap_address_id']) ? sanitize_text_field(wp_unslash((string) $request['pap_address_id'])) : '';

    if ($address_id !== '' && !papetarie_storefront_address_book_get($user_id, $address_id)) {
        return new WP_Error('address_id_invalid', __('Adresa selectată nu există sau nu îți aparține.', 'papetarie-storefront'));
    }

    if ($action === 'delete') {
        if (!papetarie_storefront_address_book_delete_entry($user_id, $address_id)) {
            return new WP_Error('address_delete_failed', __('Nu am putut șterge adresa selectată.', 'papetarie-storefront'));
        }

        papetarie_storefront_address_book_clear_form_state();

        return [
            'message' => __('Adresa a fost ștearsă.', 'papetarie-storefront'),
            'addresses_html' => papetarie_storefront_address_book_render_panel_html(papetarie_storefront_address_book_get_all($user_id)),
        ];
    }

    if ($action !== 'save') {
        return new WP_Error('address_action_invalid', __('Acțiunea selectată nu este validă.', 'papetarie-storefront'));
    }

    $errors = new WP_Error();
    $clean = papetarie_storefront_address_book_validate($request, $errors);
    $email = isset($request['email']) ? sanitize_email(wp_unslash((string) $request['email'])) : '';

    if (isset($request['email']) && $email === '') {
        $errors->add('address_email_invalid', __('Introdu o adresă de email validă.', 'papetarie-storefront'));
    }

    if ($errors->has_errors()) {
        $clean['is_default'] = !empty($request['pap_address_is_default']) && (string) $request['pap_address_is_default'] === '1';
        papetarie_storefront_address_book_set_form_state($clean);
        return $errors;
    }

    $current_default_id = trim((string) get_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), true));
    $is_checkout_request = !empty($request['pap_address_checkout']);
    $make_default = (!empty($request['pap_address_is_default']) && (string) $request['pap_address_is_default'] === '1')
        || ($is_checkout_request && $current_default_id === '');
    $saved = papetarie_storefront_address_book_save_entry($user_id, $clean, $address_id);

    if ($make_default) {
        update_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), (string) ($saved['id'] ?? ''));
    } elseif (!$is_checkout_request && $address_id !== '' && $current_default_id === $address_id) {
        $next_default = '';
        foreach (papetarie_storefront_address_book_get_all($user_id, false) as $candidate) {
            $candidate_id = (string) ($candidate['id'] ?? '');
            if ($candidate_id !== '' && $candidate_id !== (string) ($saved['id'] ?? '')) {
                $next_default = $candidate_id;
                break;
            }
        }

        if ($next_default === '') {
            $next_default = (string) ($saved['id'] ?? '');
        }

        update_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), $next_default);
    }

    if (!empty($request['pap_address_checkout'])) {
        $saved_id = (string) ($saved['id'] ?? '');
        $requested_selected_id = isset($request['pap_checkout_selected_address_id'])
            ? sanitize_text_field(wp_unslash((string) $request['pap_checkout_selected_address_id']))
            : '';
        $requested_selected = $requested_selected_id !== ''
            ? papetarie_storefront_address_book_get($user_id, $requested_selected_id)
            : null;
        $selected_id = $address_id === '' || $address_id === $requested_selected_id || !$requested_selected
            ? $saved_id
            : $requested_selected_id;
        $selected_address = $selected_id === $saved_id
            ? $saved
            : papetarie_storefront_address_book_get($user_id, $selected_id);

        papetarie_storefront_address_book_checkout_set_selected_address_id('shipping', $selected_id);
        papetarie_storefront_address_book_checkout_set_selected_address_id('billing', $selected_id);
        if ($selected_address) {
            papetarie_storefront_address_book_sync_customer($user_id, $selected_address, $email);
        }
    }

    papetarie_storefront_address_book_clear_form_state();

    return [
        'message' => $address_id !== '' ? __('Adresa a fost salvată.', 'papetarie-storefront') : __('Adresa a fost adăugată.', 'papetarie-storefront'),
        'addresses_html' => papetarie_storefront_address_book_render_panel_html(papetarie_storefront_address_book_get_all($user_id)),
        'saved_address' => $saved,
        'selected_address_id' => !empty($request['pap_address_checkout']) ? $selected_id : '',
        'email' => $email !== '' ? $email : papetarie_storefront_address_book_checkout_email($user_id),
    ];
}

function papetarie_storefront_handle_checkout_address_selection(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Trebuie să fii autentificat.', 'papetarie-storefront')], 403);
    }

    check_ajax_referer('pap_checkout_address', 'nonce');

    $user_id = get_current_user_id();
    $address_id = isset($_POST['address_id']) ? sanitize_text_field(wp_unslash((string) $_POST['address_id'])) : '';
    $address = $address_id !== '' ? papetarie_storefront_address_book_get($user_id, $address_id) : null;

    if (!$address) {
        wp_send_json_error(['message' => __('Adresa selectată nu există sau nu îți aparține.', 'papetarie-storefront')], 400);
    }

    papetarie_storefront_address_book_checkout_set_selected_address_id('shipping', $address_id);
    papetarie_storefront_address_book_checkout_set_selected_address_id('billing', $address_id);
    papetarie_storefront_address_book_sync_customer(
        $user_id,
        $address,
        papetarie_storefront_address_book_checkout_email($user_id)
    );

    wp_send_json_success([
        'selected_address_id' => $address_id,
    ]);
}
add_action('wp_ajax_papetarie_storefront_checkout_select_address', 'papetarie_storefront_handle_checkout_address_selection');

function papetarie_storefront_handle_address_book_request(): void
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_wc_endpoint_url('edit-address')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pap_address_book_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash((string) ($_POST['pap_address_book_action'] ?? '')));
    $address_id = isset($_POST['pap_address_id']) ? sanitize_text_field(wp_unslash((string) $_POST['pap_address_id'])) : '';
    $result = papetarie_storefront_address_book_process_request($_POST);
    if (is_wp_error($result)) {
        foreach ($result->get_error_messages() as $message) {
            wc_add_notice($message, 'error');
        }

        if ($action === 'save') {
            wp_safe_redirect(papetarie_storefront_address_book_form_url([
                'pap_address_action' => $address_id !== '' ? 'edit' : 'add',
                'pap_address_id' => $address_id !== '' ? $address_id : null,
                'pap_address_type' => 'shipping',
            ]));
        } else {
            wp_safe_redirect(papetarie_storefront_address_book_tab_url('shipping'));
        }
        exit;
    }

    if (!empty($result['message'])) {
        wc_add_notice((string) $result['message'], 'success');
    }

    wp_safe_redirect(papetarie_storefront_address_book_tab_url('shipping'));
    exit;
}
add_action('template_redirect', 'papetarie_storefront_handle_address_book_request', 25);

function papetarie_storefront_handle_address_book_ajax_request(): void
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Trebuie să fii autentificat.', 'papetarie-storefront')], 403);
    }

    $result = papetarie_storefront_address_book_process_request($_POST);
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => $result->get_error_message(),
            'messages' => $result->get_error_messages(),
        ], 400);
    }

    wp_send_json_success($result);
}
add_action('wp_ajax_papetarie_storefront_address_book', 'papetarie_storefront_handle_address_book_ajax_request');

function papetarie_storefront_enqueue_address_book_script(): void
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_wc_endpoint_url('edit-address')) {
        return;
    }

    $script_path = get_stylesheet_directory() . '/assets/js/address-book.js';
    $script_version = file_exists($script_path) ? (string) filemtime($script_path) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-address-book',
        get_stylesheet_directory_uri() . '/assets/js/address-book.js',
        ['jquery'],
        $script_version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-address-book',
        'papAddressBookData',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxAction' => 'papetarie_storefront_address_book',
            'ajaxNonce' => wp_create_nonce('pap_address_book_save'),
            'citiesByCounty' => function_exists('papetarie_storefront_romania_cities_by_county') ? papetarie_storefront_romania_cities_by_county() : [],
            'cityPlaceholder' => __('Alege localitatea', 'papetarie-storefront'),
            'countyFirstPlaceholder' => __('Alege județul întâi', 'papetarie-storefront'),
            'deleteConfirm' => __('Sigur vrei să ștergi această adresă?', 'papetarie-storefront'),
            'currentMode' => papetarie_storefront_address_book_current_mode(),
            'currentAddressId' => papetarie_storefront_address_book_current_id(),
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_address_book_script', 25);

function papetarie_storefront_render_account_addresses_page(): void
{
    if (!is_user_logged_in()) {
        echo '<p>' . esc_html__('Trebuie să fii autentificat pentru a vedea adresele salvate.', 'papetarie-storefront') . '</p>';
        return;
    }

    $addresses = papetarie_storefront_address_book_get_all(get_current_user_id());
    $mode = papetarie_storefront_address_book_current_mode();
    $edit_id = papetarie_storefront_address_book_current_id();
    $active_address = $mode === 'edit' && $edit_id !== ''
        ? papetarie_storefront_address_book_get(get_current_user_id(), $edit_id)
        : papetarie_storefront_address_book_empty_entry();
    $form_state = papetarie_storefront_address_book_get_form_state();
    if (!empty($form_state)) {
        $active_address = array_merge($active_address, $form_state);
    }
    ?>
    <div class="pap-account-page pap-account-page--addresses">
      <?php papetarie_storefront_render_account_page_head(
          __('Adrese', 'papetarie-storefront'),
          __('Gestionează adresele tale de livrare.', 'papetarie-storefront'),
          '<a class="pap-account-primary-button" href="' . esc_url(papetarie_storefront_address_book_form_url(['pap_address_action' => 'add', 'pap_address_type' => 'shipping'])) . '" data-address-book-open-modal data-address-book-mode="add">' . esc_html__('Adaugă adresă', 'papetarie-storefront') . '</a>'
      ); ?>
      <section class="pap-account-panel pap-account-panel--addresses pap-account-address-panel">
        <div class="pap-account-address-panel__content" data-address-book-list>
          <?php echo papetarie_storefront_address_book_render_panel_html($addresses); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
      </section>
      <?php echo papetarie_storefront_address_book_render_modal_html($active_address, $mode, in_array($mode, ['add', 'edit'], true)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <?php
}
