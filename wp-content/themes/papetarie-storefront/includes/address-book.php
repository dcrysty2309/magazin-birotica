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
        'label' => [
            'label' => __('Etichetă adresă', 'papetarie-storefront'),
            'placeholder' => __('Acasă, birou, depozit', 'papetarie-storefront'),
            'required' => false,
            'type' => 'text',
        ],
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
        'company' => [
            'label' => __('Firmă', 'papetarie-storefront'),
            'placeholder' => __('Denumire firmă', 'papetarie-storefront'),
            'required' => false,
            'type' => 'text',
        ],
        'country' => [
            'label' => __('Țară', 'papetarie-storefront'),
            'required' => true,
            'type' => 'select',
        ],
        'state' => [
            'label' => __('Județ / regiune', 'papetarie-storefront'),
            'placeholder' => __('Alege județul', 'papetarie-storefront'),
            'required' => true,
            'type' => 'select',
        ],
        'city' => [
            'label' => __('Oraș', 'papetarie-storefront'),
            'placeholder' => __('Alege localitatea', 'papetarie-storefront'),
            'required' => true,
            'type' => 'select',
        ],
        'postcode' => [
            'label' => __('Cod poștal', 'papetarie-storefront'),
            'placeholder' => __('123456', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
            'autocomplete' => 'postal-code',
        ],
        'address_1' => [
            'label' => __('Adresă 1', 'papetarie-storefront'),
            'placeholder' => __('Strada Exemplu 12', 'papetarie-storefront'),
            'required' => true,
            'type' => 'text',
            'autocomplete' => 'address-line1',
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
    $normalized['state'] = sanitize_key($normalized['state']);
    $normalized['phone'] = preg_replace('/\s+/', ' ', trim((string) $normalized['phone']));
    $normalized['postcode'] = preg_replace('/\s+/', '', trim((string) $normalized['postcode']));
    $normalized['address_1'] = trim((string) $normalized['address_1']);
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
    $entry['state'] = sanitize_key((string) get_user_meta($user_id, $prefix . '_state', true));
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

function papetarie_storefront_address_book_save_entry(int $user_id, array $posted, string $address_id = ''): array
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

    $default_id = trim((string) get_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), true));
    $make_default = !empty($posted['is_default']);
    if ($make_default) {
        $default_id = (string) $entry['id'];
    } elseif ($default_id === '' || ($existing !== null && $default_id === (string) $entry['id'])) {
        $fallback_default_id = '';
        foreach ($addresses as $candidate) {
            if ((string) ($candidate['id'] ?? '') !== (string) $entry['id']) {
                $fallback_default_id = (string) ($candidate['id'] ?? '');
                break;
            }
        }

        if ($fallback_default_id !== '') {
            $default_id = $fallback_default_id;
        } elseif ($default_id === '') {
            $default_id = (string) $entry['id'];
        }
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

function papetarie_storefront_address_book_set_default(int $user_id, string $address_id): bool
{
    $address_id = trim($address_id);
    if ($address_id === '') {
        return false;
    }

    $address = papetarie_storefront_address_book_get($user_id, $address_id);
    if (!$address) {
        return false;
    }

    update_user_meta($user_id, papetarie_storefront_address_book_default_id_meta_key(), $address_id);
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

function papetarie_storefront_address_book_format_lines(array $address): array
{
    $lines = [];
    $full_name = trim((string) ($address['first_name'] ?? '') . ' ' . (string) ($address['last_name'] ?? ''));
    $company = trim((string) ($address['company'] ?? ''));
    $address_line = trim((string) ($address['address_1'] ?? ''));
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
        'shipping_first_name' => 'first_name',
        'shipping_last_name' => 'last_name',
        'shipping_company' => 'company',
        'shipping_phone' => 'phone',
        'shipping_country' => 'country',
        'shipping_state' => 'state',
        'shipping_city' => 'city',
        'shipping_postcode' => 'postcode',
        'shipping_address_1' => 'address_1',
    ];
}

function papetarie_storefront_address_book_checkout_field_value($value, string $input)
{
    if (!function_exists('is_checkout') || !is_checkout() || !is_user_logged_in()) {
        return $value;
    }

    $customer_id = get_current_user_id();
    $address = papetarie_storefront_address_book_default_address($customer_id);
    if (!$address) {
        return $value;
    }

    $map = papetarie_storefront_address_book_checkout_field_map();
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

    if ($current_value !== '' && !in_array($input, ['billing_state', 'shipping_state', 'billing_country', 'shipping_country'], true)) {
        return $value;
    }

    return $address_value;
}
add_filter('woocommerce_checkout_get_value', 'papetarie_storefront_address_book_checkout_field_value', 20, 2);

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
        'class' => ['form-row-wide', 'pap-address-form-row', 'pap-address-form-row--' . $key],
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

    woocommerce_form_field($key, $args, $value);
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

function papetarie_storefront_address_book_render_form(array $address = [], string $mode = 'add'): void
{
    $fields = papetarie_storefront_address_book_fields();
    $address = array_merge(papetarie_storefront_address_book_empty_entry(), $address);
    $is_edit = $mode === 'edit' && !empty($address['id']);
    $action_url = papetarie_storefront_address_book_form_url([
        'pap_address_action' => $is_edit ? 'edit' : 'add',
        'pap_address_id' => $is_edit ? $address['id'] : null,
    ]);
    $is_default_checked = !empty($address['is_default']) || empty(papetarie_storefront_address_book_get_all(get_current_user_id()));
    ?>
    <section class="pap-account-panel pap-account-panel--form pap-account-address-form-shell">
      <div class="pap-account-panel-head">
        <div>
          <h2><?php echo esc_html($is_edit ? __('Editează adresa', 'papetarie-storefront') : __('Adaugă adresă nouă', 'papetarie-storefront')); ?></h2>
          <p><?php echo esc_html($is_edit ? __('Actualizează datele salvate pentru această adresă.', 'papetarie-storefront') : __('Completează o adresă nouă, apoi o poți seta ca implicită.', 'papetarie-storefront')); ?></p>
        </div>
        <a class="pap-account-row-action" href="<?php echo esc_url(papetarie_storefront_address_book_base_url()); ?>">
          <?php esc_html_e('Înapoi la listă', 'papetarie-storefront'); ?>
        </a>
      </div>

      <form class="pap-account-form pap-account-address-form" method="post" action="<?php echo esc_url($action_url); ?>" novalidate>
        <?php wp_nonce_field('pap_address_book_save', 'pap_address_book_nonce'); ?>
        <input type="hidden" name="pap_address_book_action" value="save">
        <input type="hidden" name="pap_address_id" value="<?php echo esc_attr((string) $address['id']); ?>">

        <div class="pap-account-form-grid pap-account-form-grid--address">
          <?php
          $form_state = papetarie_storefront_address_book_get_form_state();
          $source = !empty($form_state) ? array_merge($address, $form_state) : $address;

          foreach ($fields as $key => $field) :
              $value = (string) ($source[$key] ?? '');
              if ($key === 'state') {
                  $value = sanitize_key($value);
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
          <?php endforeach; ?>
        </div>

        <label class="pap-account-address-default-toggle">
          <input type="checkbox" name="is_default" value="1" <?php checked($is_default_checked); ?>>
          <span><?php esc_html_e('Setează ca adresă implicită de livrare', 'papetarie-storefront'); ?></span>
        </label>

        <div class="pap-account-form-actions">
          <button type="submit" class="pap-account-primary-button">
            <?php echo esc_html($is_edit ? __('Salvează adresa', 'papetarie-storefront') : __('Adaugă adresa', 'papetarie-storefront')); ?>
          </button>
          <a class="pap-account-row-action" href="<?php echo esc_url(papetarie_storefront_address_book_base_url()); ?>">
            <?php esc_html_e('Renunță', 'papetarie-storefront'); ?>
          </a>
        </div>
      </form>
    </section>
    <?php
}

function papetarie_storefront_address_book_render_list(array $addresses): void
{
    ?>
    <section class="pap-account-panel pap-account-panel--addresses">
      <div class="pap-account-panel-head">
        <div>
          <h2><?php esc_html_e('Adrese salvate', 'papetarie-storefront'); ?></h2>
          <p><?php esc_html_e('Aici gestionezi toate adresele folosite la checkout.', 'papetarie-storefront'); ?></p>
        </div>
        <a class="pap-account-primary-button pap-account-primary-button--link" href="<?php echo esc_url(papetarie_storefront_address_book_form_url(['pap_address_action' => 'add'])); ?>">
          <?php esc_html_e('Adaugă adresă', 'papetarie-storefront'); ?>
        </a>
      </div>

      <?php if (empty($addresses)) : ?>
        <div class="pap-account-empty-state pap-account-empty-state--addresses">
          <p><?php esc_html_e('Nu ai nicio adresă salvată încă.', 'papetarie-storefront'); ?></p>
          <a class="pap-account-row-action" href="<?php echo esc_url(papetarie_storefront_address_book_form_url(['pap_address_action' => 'add'])); ?>">
            <?php esc_html_e('Adaugă prima adresă', 'papetarie-storefront'); ?> <span aria-hidden="true">→</span>
          </a>
        </div>
      <?php else : ?>
        <div class="pap-account-address-grid">
          <?php foreach ($addresses as $address) : ?>
            <?php
            $lines = papetarie_storefront_address_book_format_lines($address);
            $is_default = !empty($address['is_default']);
            $address_id = (string) ($address['id'] ?? '');
            ?>
            <article class="pap-account-address-card<?php echo $is_default ? ' is-default' : ''; ?>">
              <div class="pap-account-address-card__head">
                <div class="pap-account-address-card__head-copy">
                  <h3><?php echo esc_html(papetarie_storefront_address_book_label($address)); ?></h3>
                  <?php if ($is_default) : ?>
                    <span class="pap-account-address-badge"><?php esc_html_e('Implicită pentru livrare', 'papetarie-storefront'); ?></span>
                  <?php endif; ?>
                </div>
                <a class="pap-account-row-action" href="<?php echo esc_url(papetarie_storefront_address_book_form_url(['pap_address_action' => 'edit', 'pap_address_id' => $address_id])); ?>">
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
                <?php if (!$is_default) : ?>
                  <form method="post" action="<?php echo esc_url(papetarie_storefront_address_book_base_url()); ?>">
                    <?php wp_nonce_field('pap_address_book_save', 'pap_address_book_nonce'); ?>
                    <input type="hidden" name="pap_address_book_action" value="set_default">
                    <input type="hidden" name="pap_address_id" value="<?php echo esc_attr($address_id); ?>">
                    <button type="submit" class="pap-account-row-action">
                      <?php esc_html_e('Setează ca implicită', 'papetarie-storefront'); ?>
                    </button>
                  </form>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(papetarie_storefront_address_book_base_url()); ?>" data-address-delete-form>
                  <?php wp_nonce_field('pap_address_book_save', 'pap_address_book_nonce'); ?>
                  <input type="hidden" name="pap_address_book_action" value="delete">
                  <input type="hidden" name="pap_address_id" value="<?php echo esc_attr($address_id); ?>">
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
      <?php endif; ?>
    </section>
    <?php
}

function papetarie_storefront_handle_address_book_request(): void
{
    if (!function_exists('is_account_page') || !is_account_page() || !is_wc_endpoint_url('edit-address')) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pap_address_book_action'])) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    $nonce = isset($_POST['pap_address_book_nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['pap_address_book_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'pap_address_book_save')) {
        wc_add_notice(__('Sesiunea a expirat. Reîncarcă pagina și încearcă din nou.', 'papetarie-storefront'), 'error');
        return;
    }

    $user_id = get_current_user_id();
    $action = sanitize_key(wp_unslash((string) $_POST['pap_address_book_action']));
    $address_id = isset($_POST['pap_address_id']) ? sanitize_text_field(wp_unslash((string) $_POST['pap_address_id'])) : '';

    if ($action === 'delete') {
        if (papetarie_storefront_address_book_delete_entry($user_id, $address_id)) {
            papetarie_storefront_address_book_clear_form_state();
            wc_add_notice(__('Adresa a fost ștearsă.', 'papetarie-storefront'), 'success');
        } else {
            wc_add_notice(__('Nu am putut șterge adresa selectată.', 'papetarie-storefront'), 'error');
        }

        wp_safe_redirect(papetarie_storefront_address_book_base_url());
        exit;
    }

    if ($action === 'set_default') {
        if (papetarie_storefront_address_book_set_default($user_id, $address_id)) {
            wc_add_notice(__('Adresa implicită a fost actualizată.', 'papetarie-storefront'), 'success');
        } else {
            wc_add_notice(__('Nu am putut seta adresa implicită.', 'papetarie-storefront'), 'error');
        }

        wp_safe_redirect(papetarie_storefront_address_book_base_url());
        exit;
    }

    if ($action !== 'save') {
        return;
    }

    $errors = new WP_Error();
    $clean = papetarie_storefront_address_book_validate($_POST, $errors);

    if ($errors->has_errors()) {
        papetarie_storefront_address_book_set_form_state($clean + [
            'is_default' => !empty($_POST['is_default']) ? '1' : '',
        ]);

        foreach ($errors->get_error_messages() as $message) {
            wc_add_notice($message, 'error');
        }

        wp_safe_redirect(papetarie_storefront_address_book_form_url([
            'pap_address_action' => $address_id !== '' ? 'edit' : 'add',
            'pap_address_id' => $address_id !== '' ? $address_id : null,
        ]));
        exit;
    }

    $saved = papetarie_storefront_address_book_save_entry($user_id, $clean + [
        'is_default' => !empty($_POST['is_default']),
    ], $address_id);

    papetarie_storefront_address_book_clear_form_state();

    if (!empty($saved['is_default'])) {
        wc_add_notice(__('Adresa implicită a fost actualizată.', 'papetarie-storefront'), 'success');
    }

    wc_add_notice($address_id !== '' ? __('Adresa a fost salvată.', 'papetarie-storefront') : __('Adresa a fost adăugată.', 'papetarie-storefront'), 'success');
    wp_safe_redirect(papetarie_storefront_address_book_base_url());
    exit;
}
add_action('template_redirect', 'papetarie_storefront_handle_address_book_request', 25);

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
            'citiesByCounty' => function_exists('papetarie_storefront_romania_cities_by_county') ? papetarie_storefront_romania_cities_by_county() : [],
            'cityPlaceholder' => __('Alege localitatea', 'papetarie-storefront'),
            'countyFirstPlaceholder' => __('Alege județul întâi', 'papetarie-storefront'),
            'deleteConfirm' => __('Sigur vrei să ștergi această adresă?', 'papetarie-storefront'),
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
        : null;
    $form_state = papetarie_storefront_address_book_get_form_state();

    if ($mode === 'add') {
        $active_address = !empty($form_state) ? $form_state : papetarie_storefront_address_book_empty_entry();
    } elseif ($mode === 'edit' && !$active_address && !empty($form_state)) {
        $active_address = $form_state;
    }
    ?>
    <div class="pap-account-page pap-account-page--addresses">
      <header class="pap-account-page-head">
        <h1><?php esc_html_e('Adrese', 'papetarie-storefront'); ?></h1>
        <p><?php esc_html_e('Gestionarea adreselor începe aici. Checkout-ul va prelua adresa implicită salvată în cont.', 'papetarie-storefront'); ?></p>
      </header>

      <?php if ($mode === 'add' || $mode === 'edit') : ?>
        <?php papetarie_storefront_address_book_render_form((array) $active_address, $mode); ?>
      <?php else : ?>
        <?php if (empty($addresses)) : ?>
          <section class="pap-account-panel pap-account-panel--addresses">
            <div class="pap-account-empty-state pap-account-empty-state--addresses">
              <p><?php esc_html_e('Nu ai nicio adresă salvată încă.', 'papetarie-storefront'); ?></p>
              <a class="pap-account-primary-button pap-account-primary-button--link" href="<?php echo esc_url(papetarie_storefront_address_book_form_url(['pap_address_action' => 'add'])); ?>">
                <?php esc_html_e('Adaugă prima adresă', 'papetarie-storefront'); ?>
              </a>
            </div>
          </section>
        <?php else : ?>
          <?php papetarie_storefront_address_book_render_list($addresses); ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
}
