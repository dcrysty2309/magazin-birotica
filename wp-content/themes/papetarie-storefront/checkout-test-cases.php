<?php
/*
Template Name: Checkout Test Cases
Template Post Type: page
*/

defined('ABSPATH') || exit;

get_header();

$cases = [
    [
        'id' => '1.1',
        'scenario' => 'Guest - stare inițială',
        'user_type' => 'Guest',
        'addresses' => '0',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Verifică faptul că Pasul 1 - Adresă de livrare este deschis și afișează formularul gol, înainte să completezi orice câmp.',
        ],
        'expected' => [
            'Formularul este deschis.',
            'Nu există summary card.',
            'Nu există border de selecție.',
            'Nu există label de selecție.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '1.2',
        'scenario' => 'Guest - formular completat și salvat',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Completează formularul cu date clare și verificabile: Prenume Ion, Nume Popescu, Email ion.popescu@test.local, Telefon 0712223333, Județ Cluj, Localitate Cluj-Napoca, Adresă Strada Test 12, Cod poștal 405400.',
            'Apasă "Continuă".',
            'Așteaptă să se închidă formularul și să apară summary card.',
        ],
        'expected' => [
            'Formularul dispare complet.',
            'Apare summary card-ul.',
            'Nu există badge de selecție.',
            'Butonul "Modifică" este vizibil.',
            'Pasul 2 devine activ.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '1.3',
        'scenario' => 'User logat - adresa din cont în summary',
        'user_type' => 'User logat',
        'addresses' => '1',
        'reproduce' => [
            'Autentifică-te cu `checkout.oneaddress@test.local`.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Verifică faptul că Pasul 1 afișează cardul de summary pentru adresa din cont, fără formular deschis dedesubt.',
        ],
        'expected' => [
            'Apare summary-ul adresei standard din My Account.',
            'Formularul este ascuns până la apăsarea "Modifică".',
            'Nu există listă de adrese multiple.',
            'Nu există badge de selecție.',
        ],
        'user_test' => [
            'User: checkout.oneaddress@test.local',
            'Parolă: Steauab23.',
            'Login state: logat',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '1.4',
        'scenario' => 'User logat - modifică adresa și salvează în cont',
        'user_type' => 'User logat',
        'addresses' => '1',
        'reproduce' => [
            'Autentifică-te cu `checkout.oneaddress@test.local`.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Apasă „Modifică” și schimbă explicit telefonul, strada și codul poștal, de exemplu: Telefon 0740000000, Adresă Strada Nouă 44, Cod poștal 400000.',
            'Continuă fără bifă și verifică faptul că My Account nu se schimbă.',
            'Reintră în „Modifică”, păstrează Adresa B în formular și bifează „Actualizează adresa din contul meu cu aceste date”.',
            'Continuă din nou și verifică faptul că My Account se actualizează.',
        ],
        'expected' => [
            'currentOrderAddress rămâne Adresa B după primul „Continuă”.',
            'La redeschiderea formularului, câmpurile sunt precompletate cu Adresa B.',
            'My Account se actualizează doar după ce checkbox-ul este bifat.',
            'Adresa A nu mai reapare în formular sau în summary.',
        ],
        'user_test' => [
            'User: checkout.oneaddress@test.local',
            'Parolă: Steauab23.',
            'Login state: logat',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '2.1',
        'scenario' => 'Guest - adresă incompletă, transport indisponibil',
        'user_type' => 'Guest',
        'addresses' => '0',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs fizic în coș.',
            'Deschide pagina `/checkout/`.',
            'Lasă formularul de adresă necompletat și verifică Pasul 2 - Tip de livrare.',
        ],
        'expected' => [
            'Pasul 2 rămâne blocat până când adresa este completată.',
            'Nu apare tarif inventat.',
            'Este afișat un mesaj clar care spune că trebuie completată adresa pentru a calcula transportul.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '2.2',
        'scenario' => 'Guest - adresă completă, transport calculat',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs fizic în coș.',
            'Deschide pagina `/checkout/`.',
            'Completează formularul cu date clare și verificabile: Prenume Ion, Nume Popescu, Email ion.popescu@test.local, Telefon 0712223333, Județ Cluj, Localitate Cluj-Napoca, Adresă Strada Test 12, Cod poștal 405400.',
            'Continuă până ajungi la Pasul 2 - Tip de livrare.',
        ],
        'expected' => [
            'Transportul afișează metoda și costul venite din WooCommerce.',
            'Nu există `STANDARD` sau ETA inventat.',
            'Dacă există rate multiple, apar doar ratele reale din WooCommerce.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '2.3',
        'scenario' => 'Guest - transport gratuit la pragul de 150 lei',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Asigură-te că în WooCommerce există o zonă de shipping pentru România cu Free Shipping activ la pragul de 150 lei.',
            'Deschide checkout ca guest cu o adresă completă.',
            'Verifică Pasul 2 - Tip de livrare după ce adresa este validă.',
        ],
        'expected' => [
            'Metoda gratuită apare doar dacă este configurată în WooCommerce.',
            'Costul de livrare reflectă exact regula administrată în WooCommerce și apare ca text, fără HTML vizibil.',
            'Nu apare fallback vizual cu `0.00 lei` inventat.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '2.4',
        'scenario' => 'Guest - ramburs activ, comandă test și thank-you page',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Verifică în WooCommerce că metoda Cash on Delivery este activă.',
            'Deschide checkout ca guest cu o adresă completă.',
            'Continuă până când vezi summary card-ul adresei, shipping calculat și Pasul 4 - Metodă de plată activ.',
            'Selectează ramburs și plasează comanda test.',
        ],
        'expected' => [
            'Ramburs apare doar dacă este activ în WooCommerce.',
            'Comanda se poate plasa cap-coadă.',
            'După submit, checkout-ul ajunge pe pagina de confirmare / thank-you page.',
            'În WooCommerce Admin, metoda de plată și datele comenzii sunt corecte.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '2.5',
        'scenario' => 'Guest - nicio metodă de plată activă',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'În WooCommerce dezactivează temporar metodele de plată disponibile.',
            'Deschide checkout ca guest cu o adresă completă.',
            'Continuă până la Pasul 4 - Metodă de plată.',
        ],
        'expected' => [
            'Nu se afișează metode inactive.',
            'Se afișează un mesaj clar de business, nu o eroare tehnică.',
            'Nu se poate plasa comanda fără metodă de plată selectată.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '2.6',
        'scenario' => 'Guest - ramburs și meta corecte în Admin',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Verifică în WooCommerce că metoda Cash on Delivery este activă.',
            'Deschide checkout ca guest cu o adresă completă.',
            'Selectează ramburs și plasează comanda test.',
            'Deschide comanda în WooCommerce Admin și verifică adresa, shipping-ul, plata și totalul.',
        ],
        'expected' => [
            'Adresa, shipping-ul, metoda de plată și totalul sunt identice cu ce s-a ales în checkout.',
            'Ramburs apare doar dacă este activ în WooCommerce.',
            'Comanda rămâne corectă și după refresh în Admin.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
];

$comment_index = function_exists('papetarie_storefront_get_checkout_test_comment_index') ? papetarie_storefront_get_checkout_test_comment_index() : [];
$commented_cases = array_values(array_filter($cases, static function (array $case) use ($comment_index): bool {
    return isset($comment_index[$case['id']]) && !empty($comment_index[$case['id']]['comments']);
}));

function pap_checkout_cases_join_lines(array $lines): string
{
    return implode("\n", array_map('trim', $lines));
}

$recommended_case_ids = ['1.1', '1.2', '1.3', '1.4'];
$recommended_cases = array_values(array_filter($cases, static function (array $case) use ($recommended_case_ids): bool {
    return in_array($case['id'], $recommended_case_ids, true);
}));
?>
<main id="primary" class="site-main pap-checkout-cases-page">
  <section class="pap-shell pap-checkout-cases-header">
    <div class="pap-checkout-cases-header__copy">
      <h4><?php esc_html_e('Teste Checkout — Phase 1: Adresă, transport și plată', 'papetarie-storefront'); ?></h4>
      <p class="pap-checkout-cases-lead"><?php esc_html_e('Scenarii recomandate pentru guest și user logat, de la fluxul de adresă la transport real și ramburs în WooCommerce.', 'papetarie-storefront'); ?></p>
    </div>
  </section>

  <section class="pap-shell pap-checkout-cases-table-shell">
    <div class="pap-checkout-cases-commented" aria-label="<?php esc_attr_e('Cazuri cu observații', 'papetarie-storefront'); ?>">
      <div class="pap-checkout-cases-commented__head">
        <span><?php esc_html_e('Cazuri cu observații', 'papetarie-storefront'); ?></span>
        <div class="pap-checkout-cases-commented__head-actions">
          <strong data-comments-count><?php echo esc_html((string) count($commented_cases)); ?></strong>
          <button type="button" class="pap-checkout-cases-button pap-checkout-cases-button--secondary pap-checkout-cases-button--small" data-comments-clear-all>
            <?php esc_html_e('Șterge toate', 'papetarie-storefront'); ?>
          </button>
        </div>
      </div>
      <div class="pap-checkout-cases-commented__list" data-comments-list>
        <?php if (!empty($commented_cases)) : ?>
          <?php foreach ($commented_cases as $comment_case) : ?>
            <?php $comment_entry = $comment_index[$comment_case['id']] ?? []; ?>
            <a href="#" class="pap-checkout-cases-commented__chip<?php echo !empty($comment_entry['has_open_comment']) ? ' pap-checkout-cases-commented__chip--open' : ''; ?>" data-comment-jump="<?php echo esc_attr($comment_case['id']); ?>" title="<?php echo esc_attr((string) ($comment_entry['latest_comment_text'] ?? '')); ?>">
              <span><?php echo esc_html($comment_case['id']); ?></span>
              <small><?php echo esc_html(wp_trim_words((string) ($comment_entry['latest_comment_text'] ?: $comment_case['scenario']), 8, '…')); ?></small>
              <?php if (!empty($comment_entry['has_open_comment'])) : ?>
                <em><?php echo esc_html(('in_progress' === (string) ($comment_entry['latest_status'] ?? '') ? __('În lucru', 'papetarie-storefront') : __('Deschis', 'papetarie-storefront'))); ?></em>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php else : ?>
          <span class="pap-checkout-cases-commented__empty"><?php esc_html_e('Nu există comentarii salvate încă.', 'papetarie-storefront'); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <details class="pap-checkout-cases-group" open>
      <summary><?php esc_html_e('Cazuri adresă', 'papetarie-storefront'); ?></summary>
      <p class="pap-checkout-cases-group__lead"><?php esc_html_e('Folosește-le pentru verificarea rapidă a Pasului 1 - Adresa de livrare. Acoperă starea inițială, salvarea, editarea și regresiile de cod poștal.', 'papetarie-storefront'); ?></p>
      <div class="pap-checkout-cases-table-wrap">
        <table class="pap-checkout-cases-table">
          <thead>
            <tr>
              <th><?php esc_html_e('ID', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Scenariu', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Tip utilizator', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Nr. adrese', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Preview', 'papetarie-storefront'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recommended_cases as $case) : ?>
              <?php
              $comment_entry = $comment_index[$case['id']] ?? [];
              $row_classes = [];
              if (!empty($comment_entry)) {
                  $row_classes[] = 'pap-checkout-cases-row--has-comments';
              }
              if (!empty($comment_entry['has_open_comment'])) {
                  $row_classes[] = 'pap-checkout-cases-row--open-comments';
              }
              ?>
              <tr class="<?php echo esc_attr(implode(' ', $row_classes)); ?>" data-case-id="<?php echo esc_attr($case['id']); ?>" data-case-title="<?php echo esc_attr($case['scenario']); ?>" data-case-comment-count="<?php echo esc_attr((string) ($comment_entry['total_count'] ?? 0)); ?>" data-case-open-comment-count="<?php echo esc_attr((string) ($comment_entry['open_count'] ?? 0)); ?>">
                <td><strong><?php echo esc_html($case['id']); ?></strong></td>
                <td><?php echo esc_html($case['scenario']); ?></td>
                <td><span class="pap-checkout-cases-badge pap-checkout-cases-badge--<?php echo esc_attr('Guest' === $case['user_type'] ? 'guest' : 'user'); ?>"><?php echo esc_html($case['user_type']); ?></span></td>
                <td><?php echo esc_html($case['addresses']); ?></td>
                <td>
                  <button
                    type="button"
                    class="pap-checkout-cases-button pap-checkout-cases-button--secondary"
                    data-case-preview
                    data-case-id="<?php echo esc_attr($case['id']); ?>"
                    data-case-user-type="<?php echo esc_attr($case['user_type']); ?>"
                    data-case-addresses="<?php echo esc_attr($case['addresses']); ?>"
                    data-case-title="<?php echo esc_attr($case['scenario']); ?>"
                    data-case-reproduce="<?php echo esc_attr(pap_checkout_cases_join_lines($case['reproduce'])); ?>"
                    data-case-expected="<?php echo esc_attr(pap_checkout_cases_join_lines($case['expected'])); ?>"
                    data-case-user="<?php echo esc_attr(pap_checkout_cases_join_lines($case['user_test'])); ?>"
                    data-case-screenshot="<?php echo esc_attr($case['screenshot']); ?>"
                  >
                    <?php esc_html_e('Preview', 'papetarie-storefront'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>

    <details class="pap-checkout-cases-group">
      <summary><?php esc_html_e('Cazuri Phase 1 - shipping și plată', 'papetarie-storefront'); ?></summary>
      <p class="pap-checkout-cases-group__lead"><?php esc_html_e('Folosește-le pentru verificarea transportului real din WooCommerce, a rambursului și a comenzilor test cap-coadă.', 'papetarie-storefront'); ?></p>
      <div class="pap-checkout-cases-table-wrap">
        <table class="pap-checkout-cases-table">
          <thead>
            <tr>
              <th><?php esc_html_e('ID', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Scenariu', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Tip utilizator', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Nr. adrese', 'papetarie-storefront'); ?></th>
              <th><?php esc_html_e('Preview', 'papetarie-storefront'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_values(array_filter($cases, static function (array $case): bool {
                return str_starts_with((string) $case['id'], '2.');
            })) as $case) : ?>
              <?php
              $comment_entry = $comment_index[$case['id']] ?? [];
              $row_classes = [];
              if (!empty($comment_entry)) {
                  $row_classes[] = 'pap-checkout-cases-row--has-comments';
              }
              if (!empty($comment_entry['has_open_comment'])) {
                  $row_classes[] = 'pap-checkout-cases-row--open-comments';
              }
              ?>
              <tr class="<?php echo esc_attr(implode(' ', $row_classes)); ?>" data-case-id="<?php echo esc_attr($case['id']); ?>" data-case-title="<?php echo esc_attr($case['scenario']); ?>" data-case-comment-count="<?php echo esc_attr((string) ($comment_entry['total_count'] ?? 0)); ?>" data-case-open-comment-count="<?php echo esc_attr((string) ($comment_entry['open_count'] ?? 0)); ?>">
                <td><strong><?php echo esc_html($case['id']); ?></strong></td>
                <td><?php echo esc_html($case['scenario']); ?></td>
                <td><span class="pap-checkout-cases-badge pap-checkout-cases-badge--<?php echo esc_attr('Guest' === $case['user_type'] ? 'guest' : 'user'); ?>"><?php echo esc_html($case['user_type']); ?></span></td>
                <td><?php echo esc_html($case['addresses']); ?></td>
                <td>
                  <button
                    type="button"
                    class="pap-checkout-cases-button pap-checkout-cases-button--secondary"
                    data-case-preview
                    data-case-id="<?php echo esc_attr($case['id']); ?>"
                    data-case-user-type="<?php echo esc_attr($case['user_type']); ?>"
                    data-case-addresses="<?php echo esc_attr($case['addresses']); ?>"
                    data-case-title="<?php echo esc_attr($case['scenario']); ?>"
                    data-case-reproduce="<?php echo esc_attr(pap_checkout_cases_join_lines($case['reproduce'])); ?>"
                    data-case-expected="<?php echo esc_attr(pap_checkout_cases_join_lines($case['expected'])); ?>"
                    data-case-user="<?php echo esc_attr(pap_checkout_cases_join_lines($case['user_test'])); ?>"
                    data-case-screenshot="<?php echo esc_attr($case['screenshot']); ?>"
                  >
                    <?php esc_html_e('Preview', 'papetarie-storefront'); ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>

  </section>

  <div class="pap-checkout-cases-preview" hidden aria-hidden="true">
    <div class="pap-checkout-cases-preview__backdrop" data-preview-close></div>
    <aside class="pap-checkout-cases-preview__panel" role="dialog" aria-modal="true" aria-labelledby="pap-checkout-cases-preview-title">
      <div class="pap-checkout-cases-preview__header">
        <div>
          <p class="pap-checkout-cases-eyebrow"><?php esc_html_e('Preview caz', 'papetarie-storefront'); ?></p>
          <h2 id="pap-checkout-cases-preview-title"><?php esc_html_e('Preview', 'papetarie-storefront'); ?></h2>
        </div>
        <button type="button" class="pap-checkout-cases-preview__close" data-preview-close aria-label="<?php esc_attr_e('Închide preview', 'papetarie-storefront'); ?>">×</button>
      </div>

      <div class="pap-checkout-cases-preview__body">
        <section class="pap-checkout-cases-preview__block">
          <h3><?php esc_html_e('Tip cont', 'papetarie-storefront'); ?></h3>
          <p data-preview-user-type></p>
        </section>

        <section class="pap-checkout-cases-preview__block">
          <h3><?php esc_html_e('User / parolă', 'papetarie-storefront'); ?></h3>
          <ul data-preview-user></ul>
        </section>

        <section class="pap-checkout-cases-preview__block">
          <h3><?php esc_html_e('Cum se reproduce', 'papetarie-storefront'); ?></h3>
          <ol data-preview-reproduce></ol>
        </section>

        <section class="pap-checkout-cases-preview__block">
          <h3><?php esc_html_e('Expected result', 'papetarie-storefront'); ?></h3>
          <ul data-preview-expected></ul>
        </section>

        <section class="pap-checkout-cases-preview__block">
          <h3><?php esc_html_e('Comentarii salvate', 'papetarie-storefront'); ?></h3>
          <div class="pap-checkout-cases-preview__history" data-preview-comment-history></div>
        </section>

        <section class="pap-checkout-cases-preview__block">
          <div class="pap-checkout-cases-preview__block-head">
            <h3 data-preview-comment-form-title><?php esc_html_e('Comentariu nou', 'papetarie-storefront'); ?></h3>
            <p data-preview-comment-edit-hint hidden></p>
          </div>
          <input type="hidden" data-preview-comment-id value="">
          <label class="pap-checkout-cases-preview__field">
            <span><?php esc_html_e('Stare', 'papetarie-storefront'); ?></span>
            <select class="pap-checkout-cases-preview__select" data-preview-comment-status>
              <option value="open"><?php esc_html_e('Deschis', 'papetarie-storefront'); ?></option>
              <option value="in_progress"><?php esc_html_e('În lucru', 'papetarie-storefront'); ?></option>
              <option value="fixed"><?php esc_html_e('Rezolvat', 'papetarie-storefront'); ?></option>
              <option value="ignored"><?php esc_html_e('Ignorat', 'papetarie-storefront'); ?></option>
            </select>
          </label>
          <textarea
            class="pap-checkout-cases-preview__textarea"
            data-preview-comment
            placeholder="<?php esc_attr_e('Scrie aici problema observată în timpul testării...', 'papetarie-storefront'); ?>"
          ></textarea>
          <div class="pap-checkout-cases-preview__actions">
            <button type="button" class="pap-checkout-cases-button pap-checkout-cases-button--secondary" data-preview-comment-save>
              <?php esc_html_e('Salvează comentariul', 'papetarie-storefront'); ?>
            </button>
            <p class="pap-checkout-cases-preview__status" data-preview-comment-save-status hidden></p>
          </div>
        </section>
      </div>
    </aside>
  </div>
</main>

<script>
window.papCheckoutCaseCommentIndex = <?php echo wp_json_encode($comment_index); ?>;
window.papCheckoutCaseCommentNonce = <?php echo wp_json_encode(wp_create_nonce('pap_checkout_case_comments')); ?>;
window.papCheckoutCaseCommentEndpoint = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
</script>

<script>
(function () {
  const preview = document.querySelector('.pap-checkout-cases-preview');
  if (!preview) {
    return;
  }

  const title = preview.querySelector('#pap-checkout-cases-preview-title');
  let commentIndex = window.papCheckoutCaseCommentIndex || {};
  const fields = {
    commentId: preview.querySelector('[data-preview-comment-id]'),
    comment: preview.querySelector('[data-preview-comment]'),
    commentState: preview.querySelector('[data-preview-comment-status]'),
    commentHistory: preview.querySelector('[data-preview-comment-history]'),
    commentFormTitle: preview.querySelector('[data-preview-comment-form-title]'),
    commentEditHint: preview.querySelector('[data-preview-comment-edit-hint]'),
    saveStatus: preview.querySelector('[data-preview-comment-save-status]'),
    userType: preview.querySelector('[data-preview-user-type]'),
    reproduce: preview.querySelector('[data-preview-reproduce]'),
    expected: preview.querySelector('[data-preview-expected]'),
    user: preview.querySelector('[data-preview-user]'),
  };

  const commentsList = document.querySelector('[data-comments-list]');
  const commentsCount = document.querySelector('[data-comments-count]');
      const saveButtonEl = preview.querySelector('[data-preview-comment-save]');
      const clearAllButtonEl = document.querySelector('[data-comments-clear-all]');
  const caseIndex = <?php echo wp_json_encode(array_map(static function ($case) { return ['id' => $case['id'], 'scenario' => $case['scenario']]; }, $cases)); ?>;
  const statusLabels = {
    open: '<?php echo esc_js(__('Deschis', 'papetarie-storefront')); ?>',
    in_progress: '<?php echo esc_js(__('În lucru', 'papetarie-storefront')); ?>',
    fixed: '<?php echo esc_js(__('Rezolvat', 'papetarie-storefront')); ?>',
    ignored: '<?php echo esc_js(__('Ignorat', 'papetarie-storefront')); ?>',
  };
  const emptyCommentsLabel = '<?php echo esc_js(__('Nu există comentarii salvate încă.', 'papetarie-storefront')); ?>';
  let activeCaseId = '';
  let isSavingComment = false;

  const getStorageKey = (caseId) => `pap_checkout_case_comment_${caseId}`;
  const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const escapeSelector = (value) => {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }

    return String(value).replace(/"/g, '\\"');
  };

  const parseCaseLines = (value) => (value || '')
    .split(/\n+/)
    .map((line) => line.trim())
    .filter(Boolean);

  const getCaseLineValue = (lines, prefix) => {
    const normalizedPrefix = String(prefix || '').toLowerCase();
    const match = (lines || []).find((line) => line.toLowerCase().startsWith(normalizedPrefix));
    return match ? match.slice(prefix.length).trim() : '';
  };

  const formatTimestamp = (value) => {
    if (!value) {
      return '';
    }

    const parsed = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) {
      return String(value);
    }

    return parsed.toLocaleString('ro-RO', {
      dateStyle: 'medium',
      timeStyle: 'short',
    });
  };

  const getLegacyComments = () => {
    const comments = {};
    try {
      if (typeof window.localStorage === 'undefined') {
        return comments;
      }

      caseIndex.forEach((item) => {
        const value = window.localStorage.getItem(getStorageKey(item.id));
        if (value && value.trim().length > 0) {
          comments[item.id] = value.trim();
        }
      });
    } catch (error) {
      return {};
    }

    return comments;
  };

  const getCaseComments = (caseId) => {
    const entry = commentIndex[caseId];
    if (!entry || !Array.isArray(entry.comments)) {
      return [];
    }

    return entry.comments;
  };

  const getLatestComment = (caseId) => {
    const comments = getCaseComments(caseId);
    if (!comments.length) {
      return null;
    }

    return comments[comments.length - 1];
  };

  const renderCommentList = () => {
    if (!commentsList || !commentsCount) {
      return;
    }

    const items = caseIndex.filter((item) => getCaseComments(item.id).length > 0);
    commentsCount.textContent = String(items.length);

    if (!items.length) {
      commentsList.innerHTML = `<span class="pap-checkout-cases-commented__empty">${emptyCommentsLabel}</span>`;
      return;
    }

    commentsList.innerHTML = items.map((item) => {
      const latestComment = getLatestComment(item.id);
      const commentText = latestComment ? String(latestComment.comment_text || '') : '';
      const status = latestComment ? String(latestComment.status || 'open') : 'open';
      const words = commentText ? commentText.split(/\s+/) : [];
      const snippet = words.length ? words.slice(0, 8).join(' ') : '';
      const statusLabel = statusLabels[status] || statusLabels.open;
      return `<a href="#" class="pap-checkout-cases-commented__chip${status === 'open' || status === 'in_progress' ? ' pap-checkout-cases-commented__chip--open' : ''}" data-comment-jump="${escapeHtml(item.id)}" title="${escapeHtml(commentText)}"><span>${escapeHtml(item.id)}</span>${snippet ? `<small>${escapeHtml(snippet)}${words.length > 8 ? '…' : ''}</small>` : ''}<em>${escapeHtml(statusLabel)}</em></a>`;
    }).join('');
  };

  const renderCommentHistory = (caseId) => {
    if (!fields.commentHistory) {
      return;
    }

    const comments = getCaseComments(caseId);

    if (!comments.length) {
      fields.commentHistory.innerHTML = `<p class="pap-checkout-cases-preview__history-empty">${emptyCommentsLabel}</p>`;
      return;
    }

    fields.commentHistory.innerHTML = comments.map((comment) => {
      const commentId = String(comment.id || '');
      const status = String(comment.status || 'open');
      const statusLabel = statusLabels[status] || statusLabels.open;
      const dateLabel = formatTimestamp(comment.updated_at || comment.created_at || '');
      const authorLabel = comment.author_name || (comment.user_id ? `ID ${escapeHtml(comment.user_id)}` : 'Guest');
      return `
        <article class="pap-checkout-cases-comment-item" data-comment-id="${escapeHtml(commentId)}">
          <div class="pap-checkout-cases-comment-item__meta">
            <strong>${escapeHtml(statusLabel)}</strong>
            <span>${escapeHtml(dateLabel)}${authorLabel ? ` • ${escapeHtml(authorLabel)}` : ''}${comment.environment ? ` • ${escapeHtml(comment.environment)}` : ''}</span>
          </div>
          <p>${escapeHtml(String(comment.comment_text || ''))}</p>
          <div class="pap-checkout-cases-comment-item__actions">
            <button type="button" class="pap-checkout-cases-button pap-checkout-cases-button--secondary" data-comment-edit data-comment-id="${escapeHtml(commentId)}" data-comment-status="${escapeHtml(status)}" data-comment-title="${escapeHtml(String(comment.test_case_title || ''))}">
              <?php echo esc_js(__('Editează', 'papetarie-storefront')); ?>
            </button>
            <button type="button" class="pap-checkout-cases-button pap-checkout-cases-button--secondary" data-comment-delete data-comment-id="${escapeHtml(commentId)}">
              <?php echo esc_js(__('Șterge', 'papetarie-storefront')); ?>
            </button>
          </div>
        </article>
      `;
    }).join('');
  };

  const updateComposerMode = (mode, comment = null) => {
    if (fields.commentFormTitle) {
      fields.commentFormTitle.textContent = mode === 'edit'
        ? '<?php echo esc_js(__('Editează comentariul', 'papetarie-storefront')); ?>'
        : '<?php echo esc_js(__('Comentariu nou', 'papetarie-storefront')); ?>';
    }

    if (fields.commentEditHint) {
      if (mode === 'edit' && comment) {
        fields.commentEditHint.hidden = false;
        fields.commentEditHint.textContent = `#${comment.id} · ${statusLabels[comment.status] || comment.status}`;
      } else {
        fields.commentEditHint.hidden = true;
        fields.commentEditHint.textContent = '';
      }
    }
  };

  const resetComposer = () => {
    if (fields.commentId) {
      fields.commentId.value = '';
    }
    if (fields.comment) {
      fields.comment.value = '';
    }
    if (fields.commentState) {
      fields.commentState.value = 'open';
    }
    updateComposerMode('new');
  };

  const loadCommentIntoComposer = (commentId) => {
    const comments = getCaseComments(activeCaseId);
    const comment = comments.find((item) => String(item.id) === String(commentId));
    if (!comment) {
      return;
    }

    if (fields.commentId) {
      fields.commentId.value = String(comment.id || '');
    }
    if (fields.comment) {
      fields.comment.value = String(comment.comment_text || '');
      fields.comment.focus();
    }
    if (fields.commentState) {
      fields.commentState.value = String(comment.status || 'open');
    }
    updateComposerMode('edit', comment);
  };

  const saveCommentToServer = (caseId, comment, commentId, status) => {
    const payload = new URLSearchParams();
    payload.set('action', 'pap_checkout_case_save_comment');
    payload.set('nonce', window.papCheckoutCaseCommentNonce || '');
    payload.set('case_id', caseId);
    payload.set('comment', comment);
    payload.set('comment_id', commentId || '');
    payload.set('status', status || 'open');
    payload.set('test_case_title', (caseIndex.find((item) => item.id === caseId) || {}).scenario || '');
    payload.set('page_url', window.location.href);

    return fetch(window.papCheckoutCaseCommentEndpoint || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: payload.toString(),
    }).then((response) => response.json());
  };

  const migrateLegacyComments = () => {
    const legacyComments = getLegacyComments();
    const serverComments = window.papCheckoutCaseCommentIndex || {};
    const legacyIds = Object.keys(legacyComments);

    if (!legacyIds.length) {
      return;
    }

    const needsMigration = legacyIds.some((caseId) => !serverComments[caseId] || !Array.isArray(serverComments[caseId].comments) || !serverComments[caseId].comments.length);
    if (!needsMigration) {
      return;
    }

    Promise.all(legacyIds.map((caseId) => saveCommentToServer(caseId, legacyComments[caseId], '', 'open')))
      .then((responses) => {
        const merged = { ...(window.papCheckoutCaseCommentIndex || {}) };
        responses.forEach((response) => {
          if (response && response.success && response.data && response.data.comments) {
            Object.assign(merged, response.data.comments);
          }
        });
        window.papCheckoutCaseCommentIndex = merged;
        commentIndex = merged;
        renderCommentList();
        renderCommentHistory(activeCaseId);
        try {
          if (typeof window.localStorage !== 'undefined') {
            legacyIds.forEach((caseId) => {
              window.localStorage.removeItem(getStorageKey(caseId));
            });
          }
        } catch (error) {
          // Ignore fallback cleanup failures.
        }
      })
      .catch(() => {
        // Keep the local fallback visible if the migration request fails.
    });
  };

  const deleteCommentFromServer = (commentId) => {
    const payload = new URLSearchParams();
    payload.set('action', 'pap_checkout_case_delete_comment');
    payload.set('nonce', window.papCheckoutCaseCommentNonce || '');
    payload.set('comment_id', String(commentId || ''));

    return fetch(window.papCheckoutCaseCommentEndpoint || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: payload.toString(),
    }).then((response) => response.json());
  };

  const deleteAllCommentsFromServer = () => {
    const payload = new URLSearchParams();
    payload.set('action', 'pap_checkout_case_delete_comment');
    payload.set('nonce', window.papCheckoutCaseCommentNonce || '');
    payload.set('delete_all', '1');

    return fetch(window.papCheckoutCaseCommentEndpoint || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: payload.toString(),
    }).then((response) => response.json());
  };

  const closePreview = () => {
    preview.hidden = true;
    preview.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('pap-checkout-cases-preview-open');
    activeCaseId = '';
  };

  const renderList = (target, value, listType = 'ul') => {
    if (!target) {
      return;
    }

    const lines = parseCaseLines(value);
    if (!lines.length) {
      target.innerHTML = '<li><?php echo esc_js(__('Nu sunt disponibile date pentru acest câmp.', 'papetarie-storefront')); ?></li>';
      return;
    }

    target.innerHTML = lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('');
  };

  const openPreview = (button) => {
    const isGuest = (button.dataset.caseUserType || '').toLowerCase() === 'guest';
    const caseUserLines = parseCaseLines(button.dataset.caseUser || '');
    const caseUser = getCaseLineValue(caseUserLines, 'User:') || caseUserLines[0] || '';
    const casePassword = getCaseLineValue(caseUserLines, 'Parolă:');
    activeCaseId = button.dataset.caseId || '';

    fields.userType.textContent = button.dataset.caseUserType || '';
    fields.user.innerHTML = isGuest
      ? '<li><strong><?php echo esc_js(__('User:', 'papetarie-storefront')); ?></strong> Guest</li><li><strong><?php echo esc_js(__('Parolă:', 'papetarie-storefront')); ?></strong> Nu se aplică</li><li><strong><?php echo esc_js(__('Notă:', 'papetarie-storefront')); ?></strong> testează delogat sau în incognito</li>'
      : '<li><strong><?php echo esc_js(__('User:', 'papetarie-storefront')); ?></strong> ' + escapeHtml(caseUser) + '</li><li><strong><?php echo esc_js(__('Parolă:', 'papetarie-storefront')); ?></strong> ' + escapeHtml(casePassword || '<?php echo esc_js(__('Nu se aplică', 'papetarie-storefront')); ?>') + '</li>';
    renderList(fields.reproduce, button.dataset.caseReproduce);
    renderList(fields.expected, button.dataset.caseExpected);
    renderCommentHistory(activeCaseId);
    resetComposer();
    if (fields.saveStatus) {
      fields.saveStatus.hidden = true;
      fields.saveStatus.textContent = '';
    }

    preview.hidden = false;
    preview.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pap-checkout-cases-preview-open');
  };

  const saveComment = () => {
    if (!activeCaseId || !fields.comment || !fields.commentState || isSavingComment) {
      return;
    }

    const value = fields.comment.value.trim();
    const commentId = fields.commentId ? fields.commentId.value.trim() : '';
    const status = fields.commentState.value || 'open';

    if (!value) {
      if (fields.saveStatus) {
        fields.saveStatus.textContent = '<?php echo esc_js(__('Comentariul nu poate fi gol.', 'papetarie-storefront')); ?>';
        fields.saveStatus.hidden = false;
      }
      return;
    }

    isSavingComment = true;
    if (saveButtonEl) {
      saveButtonEl.disabled = true;
    }

    saveCommentToServer(activeCaseId, value, commentId, status)
      .then((data) => {
        if (!data || !data.success) {
          throw new Error((data && data.data && data.data.message) || '<?php echo esc_js(__('Nu am putut salva comentariul.', 'papetarie-storefront')); ?>');
        }

        window.papCheckoutCaseCommentIndex = data.data.comments || {};
        commentIndex = window.papCheckoutCaseCommentIndex;

        renderCommentList();
        renderCommentHistory(activeCaseId);
        resetComposer();

        if (fields.saveStatus) {
          fields.saveStatus.textContent = '<?php echo esc_js(__('Comentariul a fost salvat.', 'papetarie-storefront')); ?>';
          fields.saveStatus.hidden = false;
        }
      })
      .catch((error) => {
        if (fields.saveStatus) {
          fields.saveStatus.textContent = error && error.message ? error.message : '<?php echo esc_js(__('Nu am putut salva comentariul.', 'papetarie-storefront')); ?>';
          fields.saveStatus.hidden = false;
        }
      })
      .finally(() => {
        isSavingComment = false;
        if (saveButtonEl) {
          saveButtonEl.disabled = false;
        }
      });
  };

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-case-preview]');
    if (button) {
      openPreview(button);
      return;
    }

    const saveButton = event.target.closest('[data-preview-comment-save]');
    if (saveButton) {
      saveComment();
      return;
    }

    const editButton = event.target.closest('[data-comment-edit]');
    if (editButton) {
      event.preventDefault();
      loadCommentIntoComposer(editButton.getAttribute('data-comment-id') || '');
      return;
    }

    const deleteButton = event.target.closest('[data-comment-delete]');
    if (deleteButton) {
      event.preventDefault();
      const commentId = deleteButton.getAttribute('data-comment-id') || '';
      if (!commentId) {
        return;
      }

      if (!window.confirm('<?php echo esc_js(__('Sigur vrei să ștergi acest comentariu?', 'papetarie-storefront')); ?>')) {
        return;
      }

      deleteCommentFromServer(commentId)
        .then((data) => {
          if (!data || !data.success) {
            throw new Error((data && data.data && data.data.message) || '<?php echo esc_js(__('Nu am putut șterge comentariul.', 'papetarie-storefront')); ?>');
          }

          window.papCheckoutCaseCommentIndex = data.data.comments || {};
          commentIndex = window.papCheckoutCaseCommentIndex;
          renderCommentList();
          renderCommentHistory(activeCaseId);
          resetComposer();
        })
        .catch((error) => {
          if (fields.saveStatus) {
            fields.saveStatus.textContent = error && error.message ? error.message : '<?php echo esc_js(__('Nu am putut șterge comentariul.', 'papetarie-storefront')); ?>';
            fields.saveStatus.hidden = false;
          }
        });
      return;
    }

    const jumpButton = event.target.closest('[data-comment-jump]');
    if (jumpButton) {
      event.preventDefault();
      const targetId = jumpButton.getAttribute('data-comment-jump');
      const targetButton = document.querySelector(`[data-case-preview][data-case-id="${escapeSelector(targetId)}"]`);
      if (targetButton) {
        targetButton.click();
      }
      return;
    }

    if (event.target.closest('[data-preview-close]')) {
      closePreview();
      return;
    }

    if (event.target.closest('[data-comments-clear-all]')) {
      if (!window.confirm('<?php echo esc_js(__('Sigur vrei să ștergi toate comentariile QA?', 'papetarie-storefront')); ?>')) {
        return;
      }

      deleteAllCommentsFromServer()
        .then((data) => {
          if (!data || !data.success) {
            throw new Error((data && data.data && data.data.message) || '<?php echo esc_js(__('Nu am putut șterge comentariile.', 'papetarie-storefront')); ?>');
          }

          window.papCheckoutCaseCommentIndex = data.data.comments || {};
          commentIndex = window.papCheckoutCaseCommentIndex;
          renderCommentList();
          if (activeCaseId) {
            renderCommentHistory(activeCaseId);
          }
          resetComposer();
        })
        .catch((error) => {
          if (fields.saveStatus) {
            fields.saveStatus.textContent = error && error.message ? error.message : '<?php echo esc_js(__('Nu am putut șterge comentariile.', 'papetarie-storefront')); ?>';
            fields.saveStatus.hidden = false;
          }
        });
    }
  });

  if (fields.comment) {
    fields.comment.addEventListener('input', () => {
      if (fields.saveStatus) {
        fields.saveStatus.hidden = true;
      }
    });
  }

  if (fields.commentState) {
    fields.commentState.addEventListener('change', () => {
      if (fields.saveStatus) {
        fields.saveStatus.hidden = true;
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !preview.hidden) {
      closePreview();
    }
  });

  renderCommentList();
  migrateLegacyComments();
  renderCommentHistory(activeCaseId);
})();
</script>

<?php
get_footer();
