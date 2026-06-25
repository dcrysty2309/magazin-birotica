<?php

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
        'scenario' => 'Guest - validare formular',
        'user_type' => 'Guest',
        'addresses' => '0',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Lasă formularul gol și apasă "Continuă".',
        ],
        'expected' => [
            'Apar erori doar după submit.',
            'Toate câmpurile obligatorii sunt marcate invalid.',
            'Focusul merge pe primul câmp invalid.',
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
        'scenario' => 'Guest - după salvare',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Completează formularul guest cu date valide.',
            'Apasă "Continuă".',
            'Așteaptă să se închidă formularul și să apară summary card.',
        ],
        'expected' => [
            'Formularul dispare complet.',
            'Apare summary card-ul.',
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
        'id' => '1.4',
        'scenario' => 'Guest - modifică adresă',
        'user_type' => 'Guest',
        'addresses' => '1',
        'reproduce' => [
            'Asigură-te că ești delogat sau folosește o fereastră incognito.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Completează și salvează adresa.',
            'După salvarea adresei, apasă "Modifică".',
            'Reia editarea aceleiași adrese.',
        ],
        'expected' => [
            'Formularul revine în edit mode.',
            'Summary card-ul dispare.',
            'Datele rămân completate.',
        ],
        'user_test' => [
            'User: Guest',
            'Parolă: Nu se aplică',
            'Login state: guest / incognito',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '4.1',
        'scenario' => 'User logat - o adresă',
        'user_type' => 'User logat',
        'addresses' => '1',
        'reproduce' => [
            'Autentifică-te cu `checkout.oneaddress@test.local`.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Verifică primul card disponibil.',
        ],
        'expected' => [
            'Apare un singur card neutru.',
            'Nu există label de selecție.',
            'Nu există border de selecție.',
            'Poate exista "Adaugă adresă nouă" dacă logica o permite.',
        ],
        'user_test' => [
            'User: checkout.oneaddress@test.local',
            'Parolă: Steaaub23',
            'Login state: logat',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '4.2',
        'scenario' => 'User logat - două adrese',
        'user_type' => 'User logat',
        'addresses' => '2+',
        'reproduce' => [
            'Autentifică-te cu `checkout.multiaddress@test.local`.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/`.',
            'Verifică lista de adrese.',
            'Schimbă selecția pe alt card.',
        ],
        'expected' => [
            'Cardul activ are border selectat.',
            'Apare labelul "Selectată pentru livrare".',
            'Dacă adresa activă este implicită în cont, apare și "Adresa implicită din cont".',
            'Click pe alt card mută selecția instant.',
        ],
        'user_test' => [
            'User: checkout.multiaddress@test.local',
            'Parolă: Steaaub23',
            'Login state: logat',
        ],
        'screenshot' => '',
    ],
    [
        'id' => '5.1',
        'scenario' => 'User logat - fără adresă',
        'user_type' => 'User logat',
        'addresses' => '0',
        'reproduce' => [
            'Autentifică-te cu `checkout.noaddress@test.local`.',
            'Adaugă un produs în coș.',
            'Deschide pagina `/checkout/` fără adresă salvată.',
        ],
        'expected' => [
            'Se afișează formularul complet, identic cu guest.',
            'După salvare apare summary card.',
            'Nu se modifică automat My Account.',
        ],
        'user_test' => [
            'User: checkout.noaddress@test.local',
            'Parolă: Steaaub23',
            'Login state: logat',
        ],
        'screenshot' => '',
    ],
];

function pap_checkout_cases_join_lines(array $lines): string
{
    return implode("\n", array_map('trim', $lines));
}
?>
<main id="primary" class="site-main pap-checkout-cases-page">
  <section class="pap-shell pap-checkout-cases-header">
    <div class="pap-checkout-cases-header__copy">
      <h4><?php esc_html_e('Teste Checkout — Pasul 1: Adresa de livrare', 'papetarie-storefront'); ?></h4>
      <p class="pap-checkout-cases-lead"><?php esc_html_e('Scenarii pentru guest și user logat: formular, summary card, selecție adresă și persistență în sesiune.', 'papetarie-storefront'); ?></p>
    </div>
  </section>

  <section class="pap-shell pap-checkout-cases-table-shell">
    <div class="pap-checkout-cases-commented" aria-label="<?php esc_attr_e('Cazuri cu comentarii', 'papetarie-storefront'); ?>">
      <div class="pap-checkout-cases-commented__head">
        <span><?php esc_html_e('Cazuri cu comentarii', 'papetarie-storefront'); ?></span>
        <strong data-comments-count>0</strong>
      </div>
      <div class="pap-checkout-cases-commented__list" data-comments-list></div>
    </div>

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
          <?php foreach ($cases as $case) : ?>
            <tr>
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
          <h3><?php esc_html_e('Comentarii testare', 'papetarie-storefront'); ?></h3>
          <textarea
            class="pap-checkout-cases-preview__textarea"
            data-preview-comment
            placeholder="<?php esc_attr_e('Scrie aici problema observată în timpul testării...', 'papetarie-storefront'); ?>"
          ></textarea>
          <div class="pap-checkout-cases-preview__actions">
            <button type="button" class="pap-checkout-cases-button pap-checkout-cases-button--secondary" data-preview-comment-save>
              <?php esc_html_e('Salvează comentariul', 'papetarie-storefront'); ?>
            </button>
            <p class="pap-checkout-cases-preview__status" data-preview-comment-status hidden></p>
          </div>
        </section>
      </div>
    </aside>
  </div>
</main>

<script>
(function () {
  const preview = document.querySelector('.pap-checkout-cases-preview');
  if (!preview) {
    return;
  }

  const title = preview.querySelector('#pap-checkout-cases-preview-title');
  const fields = {
    comment: preview.querySelector('[data-preview-comment]'),
    commentStatus: preview.querySelector('[data-preview-comment-status]'),
    userType: preview.querySelector('[data-preview-user-type]'),
    reproduce: preview.querySelector('[data-preview-reproduce]'),
    expected: preview.querySelector('[data-preview-expected]'),
    user: preview.querySelector('[data-preview-user]'),
  };

  const commentsList = document.querySelector('[data-comments-list]');
  const commentsCount = document.querySelector('[data-comments-count]');
  const caseIndex = <?php echo wp_json_encode(array_map(static function ($case) { return ['id' => $case['id'], 'scenario' => $case['scenario']]; }, $cases)); ?>;
  let activeCaseId = '';

  const getStorageKey = (caseId) => `pap_checkout_case_comment_${caseId}`;

  const readComment = (caseId) => {
    try {
      return window.localStorage.getItem(getStorageKey(caseId)) || '';
    } catch (error) {
      return '';
    }
  };

  const renderCommentList = () => {
    if (!commentsList || !commentsCount) {
      return;
    }

    const items = caseIndex.filter((item) => readComment(item.id).trim().length > 0);
    commentsCount.textContent = String(items.length);

    if (!items.length) {
      commentsList.innerHTML = '<span class="pap-checkout-cases-commented__empty"><?php echo esc_js(__('Nu există comentarii salvate încă.', 'papetarie-storefront')); ?></span>';
      return;
    }

    commentsList.innerHTML = items.map((item) => `<a href="#" class="pap-checkout-cases-commented__chip" data-comment-jump="${item.id}">${item.id}</a>`).join('');
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

    const lines = (value || '').split(/\n+/).map((line) => line.trim()).filter(Boolean);
    if (!lines.length) {
      target.innerHTML = '<li><?php echo esc_js(__('Nu sunt disponibile date pentru acest câmp.', 'papetarie-storefront')); ?></li>';
      return;
    }

    target.innerHTML = lines.map((line) => `<li>${line}</li>`).join('');
  };

  const openPreview = (button) => {
    const isGuest = (button.dataset.caseUserType || '').toLowerCase() === 'guest';
    activeCaseId = button.dataset.caseId || '';

    fields.userType.textContent = button.dataset.caseUserType || '';
    fields.user.innerHTML = isGuest
      ? '<li><strong><?php echo esc_js(__('User:', 'papetarie-storefront')); ?></strong> Guest</li><li><strong><?php echo esc_js(__('Parolă:', 'papetarie-storefront')); ?></strong> Nu se aplică</li><li><strong><?php echo esc_js(__('Notă:', 'papetarie-storefront')); ?></strong> testează delogat sau în incognito</li>'
      : '<li><strong><?php echo esc_js(__('User:', 'papetarie-storefront')); ?></strong> ' + (button.dataset.caseUser?.split('\n')[0]?.replace('User: ', '') || '') + '</li><li><strong><?php echo esc_js(__('Parolă:', 'papetarie-storefront')); ?></strong> Steaaub23</li>';
    renderList(fields.reproduce, button.dataset.caseReproduce);
    renderList(fields.expected, button.dataset.caseExpected);
    if (fields.comment) {
      fields.comment.value = readComment(activeCaseId);
    }
    if (fields.commentStatus) {
      fields.commentStatus.hidden = true;
      fields.commentStatus.textContent = '';
    }

    preview.hidden = false;
    preview.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pap-checkout-cases-preview-open');
  };

  const saveComment = () => {
    if (!activeCaseId || !fields.comment) {
      return;
    }

    const value = fields.comment.value.trim();

    try {
      if (value) {
        window.localStorage.setItem(getStorageKey(activeCaseId), value);
      } else {
        window.localStorage.removeItem(getStorageKey(activeCaseId));
      }

      renderCommentList();

      if (fields.commentStatus) {
        fields.commentStatus.textContent = value
          ? '<?php echo esc_js(__('Comentariul a fost salvat.', 'papetarie-storefront')); ?>'
          : '<?php echo esc_js(__('Comentariul a fost șters.', 'papetarie-storefront')); ?>';
        fields.commentStatus.hidden = false;
      }
    } catch (error) {
      if (fields.commentStatus) {
        fields.commentStatus.textContent = '<?php echo esc_js(__('Nu am putut salva comentariul local.', 'papetarie-storefront')); ?>';
        fields.commentStatus.hidden = false;
      }
    }
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

    const jumpButton = event.target.closest('[data-comment-jump]');
    if (jumpButton) {
      event.preventDefault();
      const targetId = jumpButton.getAttribute('data-comment-jump');
      const targetButton = document.querySelector(`[data-case-preview][data-case-id="${CSS.escape(targetId)}"]`);
      if (targetButton) {
        targetButton.click();
      }
      return;
    }

    if (event.target.closest('[data-preview-close]')) {
      closePreview();
    }
  });

  if (fields.comment) {
    fields.comment.addEventListener('input', () => {
      if (fields.commentStatus) {
        fields.commentStatus.hidden = true;
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !preview.hidden) {
      closePreview();
    }
  });

  renderCommentList();
})();
</script>

<?php
get_footer();
