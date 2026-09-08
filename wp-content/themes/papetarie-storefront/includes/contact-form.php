<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Formular de contact simplu, cu AJAX (acelasi tipar ca abonarea la
 * newsletter - vezi includes/newsletter.php): valideaza, trimite un email
 * catre magazin cu Reply-To pe adresa clientului, raspunde JSON. Camp
 * honeypot ascuns pentru spam de baza, fara sa complicam cu un captcha.
 */
function papetarie_storefront_contact_form_recipient(): string
{
    return (string) apply_filters('papetarie_storefront_contact_form_recipient', 'contact@notix.ro');
}

function papetarie_storefront_contact_form_subjects(): array
{
    return [
        'produse' => __('Întrebare despre produse', 'papetarie-storefront'),
        'comanda' => __('O comandă plasată', 'papetarie-storefront'),
        'b2b' => __('Comandă pentru firmă/instituție (B2B)', 'papetarie-storefront'),
        'altele' => __('Altceva', 'papetarie-storefront'),
    ];
}

function papetarie_storefront_handle_contact_form(): void
{
    check_ajax_referer('pap_contact_form', 'nonce');

    // Camp honeypot - un bot completeaza orice camp gaseste, un om nu vede
    // acest camp (ascuns vizual, dar prezent in DOM si tab-index -1).
    if (!empty($_POST['pap_contact_website'])) {
        wp_send_json_success(['message' => __('Mesajul a fost trimis. Îți mulțumim!', 'papetarie-storefront')]);
    }

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash((string) $_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash((string) $_POST['phone'])) : '';
    $subject_key = isset($_POST['subject']) ? sanitize_key((string) $_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash((string) $_POST['message'])) : '';

    $errors = [];
    if ($name === '') {
        $errors['name'] = __('Completează numele tău.', 'papetarie-storefront');
    }
    if ($email === '' || !is_email($email)) {
        $errors['email'] = __('Adresa de email nu este validă.', 'papetarie-storefront');
    }
    if ($message === '') {
        $errors['message'] = __('Scrie-ne câteva cuvinte despre ce ai nevoie.', 'papetarie-storefront');
    }

    if (!empty($errors)) {
        wp_send_json_error(['message' => __('Verifică datele completate.', 'papetarie-storefront'), 'errors' => $errors], 400);
    }

    $subjects = papetarie_storefront_contact_form_subjects();
    $subject_label = $subjects[$subject_key] ?? __('Mesaj de pe site', 'papetarie-storefront');

    $to = papetarie_storefront_contact_form_recipient();
    /* translators: %s: subiectul mesajului */
    $email_subject = sprintf(__('[Notix] %s', 'papetarie-storefront'), $subject_label);

    $body_lines = [
        sprintf(__('Nume: %s', 'papetarie-storefront'), $name),
        sprintf(__('Email: %s', 'papetarie-storefront'), $email),
    ];
    if ($phone !== '') {
        $body_lines[] = sprintf(__('Telefon: %s', 'papetarie-storefront'), $phone);
    }
    $body_lines[] = sprintf(__('Subiect: %s', 'papetarie-storefront'), $subject_label);
    $body_lines[] = '';
    $body_lines[] = $message;

    $headers = ['Content-Type: text/plain; charset=UTF-8'];
    if (is_email($email)) {
        $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    }

    $sent = wp_mail($to, $email_subject, implode("\n", $body_lines), $headers);

    if (!$sent) {
        wp_send_json_error(['message' => __('Mesajul nu a putut fi trimis. Încearcă din nou sau scrie-ne direct pe email.', 'papetarie-storefront')], 500);
    }

    wp_send_json_success(['message' => __('Mesajul a fost trimis. Îți răspundem cât mai curând posibil!', 'papetarie-storefront')]);
}
add_action('wp_ajax_pap_contact_form', 'papetarie_storefront_handle_contact_form');
add_action('wp_ajax_nopriv_pap_contact_form', 'papetarie_storefront_handle_contact_form');

function papetarie_storefront_enqueue_contact_form_script(): void
{
    if (!is_page_template('page-contact.php')) {
        return;
    }

    $script_path = get_stylesheet_directory() . '/assets/js/contact-form.js';
    $version = file_exists($script_path) ? (string) filemtime($script_path) : wp_get_theme()->get('Version');

    wp_enqueue_script(
        'papetarie-storefront-contact-form',
        get_stylesheet_directory_uri() . '/assets/js/contact-form.js',
        [],
        $version,
        true
    );

    wp_localize_script(
        'papetarie-storefront-contact-form',
        'papStorefrontContactForm',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pap_contact_form'),
            'genericError' => __('A apărut o eroare. Încearcă din nou.', 'papetarie-storefront'),
        ]
    );
}
add_action('wp_enqueue_scripts', 'papetarie_storefront_enqueue_contact_form_script');

/**
 * Randeaza formularul - functie separata (nu inline in template) ca sa
 * poata fi refolosita si in alta parte pe viitor (ex. un modal de contact)
 * fara sa duplicam markup-ul.
 */
function papetarie_storefront_render_contact_form(): void
{
    $subjects = papetarie_storefront_contact_form_subjects();
    ?>
    <form class="pap-contact-form" data-pap-contact-form novalidate>
      <div class="pap-contact-form-row">
        <div class="pap-contact-form-field">
          <label for="pap-contact-name"><?php esc_html_e('Nume', 'papetarie-storefront'); ?> <span aria-hidden="true">*</span></label>
          <input type="text" id="pap-contact-name" name="name" autocomplete="name" required>
          <span class="pap-contact-form-error" data-pap-contact-error="name"></span>
        </div>
        <div class="pap-contact-form-field">
          <label for="pap-contact-email"><?php esc_html_e('Email', 'papetarie-storefront'); ?> <span aria-hidden="true">*</span></label>
          <input type="email" id="pap-contact-email" name="email" autocomplete="email" required>
          <span class="pap-contact-form-error" data-pap-contact-error="email"></span>
        </div>
      </div>
      <div class="pap-contact-form-row">
        <div class="pap-contact-form-field">
          <label for="pap-contact-phone"><?php esc_html_e('Telefon', 'papetarie-storefront'); ?> <span class="pap-contact-form-optional">(<?php esc_html_e('opțional', 'papetarie-storefront'); ?>)</span></label>
          <input type="tel" id="pap-contact-phone" name="phone" autocomplete="tel">
        </div>
        <div class="pap-contact-form-field">
          <label for="pap-contact-subject"><?php esc_html_e('Subiect', 'papetarie-storefront'); ?></label>
          <select id="pap-contact-subject" name="subject">
            <?php foreach ($subjects as $key => $label) : ?>
              <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="pap-contact-form-field">
        <label for="pap-contact-message"><?php esc_html_e('Mesaj', 'papetarie-storefront'); ?> <span aria-hidden="true">*</span></label>
        <textarea id="pap-contact-message" name="message" rows="5" required></textarea>
        <span class="pap-contact-form-error" data-pap-contact-error="message"></span>
      </div>
      <div class="pap-contact-form-honeypot" aria-hidden="true">
        <label for="pap-contact-website"><?php esc_html_e('Website', 'papetarie-storefront'); ?></label>
        <input type="text" id="pap-contact-website" name="pap_contact_website" tabindex="-1" autocomplete="off">
      </div>
      <div class="pap-contact-form-footer">
        <button type="submit" class="pap-contact-form-submit"><?php esc_html_e('Trimite mesajul', 'papetarie-storefront'); ?></button>
        <p class="pap-contact-form-feedback" data-pap-contact-feedback hidden></p>
      </div>
    </form>
    <?php
}
