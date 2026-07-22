<?php

defined('ABSPATH') || exit;

global $product;

if (!comments_open()) {
    return;
}

$review_count = $product->get_review_count();
$has_reviews = $review_count > 0 && wc_review_ratings_enabled();
$average_rating = (float) $product->get_average_rating();
$rating_counts = $has_reviews ? $product->get_rating_counts() : [];
$star_icon = papetarie_storefront_icon('star');

$product_id = $product->get_id();
$brand_name = '';
$brand_terms = get_the_terms($product_id, 'product_brand');
if (!is_wp_error($brand_terms) && !empty($brand_terms)) {
    $brand_name = $brand_terms[0]->name;
}
$review_modal_subtitle = trim($product->get_title() . ($brand_name ? ' · ' . $brand_name : ''));
$can_review = get_option('woocommerce_review_rating_verification_required') === 'no' || wc_customer_bought_product('', get_current_user_id(), $product->get_id());
?>
<div id="reviews" class="woocommerce-Reviews pap-reviews">
  <?php if ($has_reviews) : ?>
    <div class="pap-reviews-summary">
      <div class="pap-reviews-summary-score">
        <p class="pap-reviews-summary-score-value"><?php echo esc_html(number_format_i18n($average_rating, 1)); ?></p>
        <div class="pap-reviews-summary-stars" aria-hidden="true">
          <?php for ($i = 1; $i <= 5; $i++) : ?>
            <span class="pap-product-rating__star<?php echo $i <= round($average_rating) ? ' is-filled' : ''; ?>"><?php echo $star_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
          <?php endfor; ?>
        </div>
        <p class="pap-reviews-summary-count"><?php echo esc_html(sprintf(
            /* translators: %d: review count */
            _n('%d recenzie', '%d recenzii', $review_count, 'papetarie-storefront'),
            $review_count
        )); ?></p>
      </div>

      <div class="pap-reviews-summary-breakdown">
        <?php for ($stars = 5; $stars >= 1; $stars--) : ?>
          <?php
          $count_for_stars = (int) ($rating_counts[$stars] ?? 0);
          $percent = $review_count > 0 ? round(($count_for_stars / $review_count) * 100) : 0;
          ?>
          <div class="pap-reviews-breakdown-row">
            <span class="pap-reviews-breakdown-label"><?php echo esc_html(sprintf(
                /* translators: %d: star count */
                __('%d stele', 'papetarie-storefront'),
                $stars
            )); ?></span>
            <span class="pap-reviews-breakdown-track">
              <span class="pap-reviews-breakdown-fill" style="width:<?php echo esc_attr((string) $percent); ?>%"></span>
            </span>
            <span class="pap-reviews-breakdown-percent"><?php echo esc_html((string) $percent); ?>%</span>
          </div>
        <?php endfor; ?>
      </div>

      <div class="pap-reviews-summary-cta">
        <p><?php esc_html_e('Ai cumpărat produsul?', 'papetarie-storefront'); ?></p>
        <button type="button" class="pap-btn pap-btn--sm" data-open-review-modal>
          <span class="pap-btn-icon" aria-hidden="true"><?php echo papetarie_storefront_icon('star-outline'); ?></span>
          <?php esc_html_e('Scrie o recenzie', 'papetarie-storefront'); ?>
        </button>
      </div>
    </div>
  <?php endif; ?>

  <div id="comments">
    <?php if (have_comments()) : ?>
      <ol class="commentlist pap-reviews-list">
        <?php wp_list_comments(apply_filters('woocommerce_product_review_list_args', ['callback' => 'woocommerce_comments'])); ?>
      </ol>

      <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
        <?php
        $current_comment_page = max(1, (int) get_query_var('cpage'));
        $total_comment_pages = (int) get_comment_pages_count();
        $chevron_icon = papetarie_storefront_icon('chevron');
        ?>
        <nav class="pap-pagination-nav pap-reviews-pagination" aria-label="<?php esc_attr_e('Paginare recenzii', 'papetarie-storefront'); ?>">
          <?php if ($current_comment_page > 1) : ?>
            <a class="page-numbers prev" href="<?php echo esc_url(get_comments_pagenum_link($current_comment_page - 1)); ?>" aria-label="<?php esc_attr_e('Pagina anterioară', 'papetarie-storefront'); ?>">
              <span class="pap-pagination-icon pap-pagination-icon--prev" aria-hidden="true"><?php echo $chevron_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </a>
          <?php else : ?>
            <span class="page-numbers prev disabled" aria-hidden="true">
              <span class="pap-pagination-icon pap-pagination-icon--prev" aria-hidden="true"><?php echo $chevron_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </span>
          <?php endif; ?>

          <?php for ($page = 1; $page <= $total_comment_pages; $page++) : ?>
            <?php if ($page === $current_comment_page) : ?>
              <span class="page-numbers current" aria-current="page"><?php echo esc_html((string) $page); ?></span>
            <?php else : ?>
              <a class="page-numbers" href="<?php echo esc_url(get_comments_pagenum_link($page)); ?>"><?php echo esc_html((string) $page); ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($current_comment_page < $total_comment_pages) : ?>
            <a class="page-numbers next" href="<?php echo esc_url(get_comments_pagenum_link($current_comment_page + 1)); ?>" aria-label="<?php esc_attr_e('Pagina următoare', 'papetarie-storefront'); ?>">
              <span class="pap-pagination-icon" aria-hidden="true"><?php echo $chevron_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </a>
          <?php else : ?>
            <span class="page-numbers next disabled" aria-hidden="true">
              <span class="pap-pagination-icon" aria-hidden="true"><?php echo $chevron_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </span>
          <?php endif; ?>

          <span class="pap-reviews-pagination-summary">
            <?php
            $per_page = max(1, (int) get_option('comments_per_page'));
            $range_start = (($current_comment_page - 1) * $per_page) + 1;
            $range_end = min($review_count, $current_comment_page * $per_page);
            echo esc_html(sprintf(
                /* translators: 1: range start, 2: range end, 3: total review count */
                __('%1$d–%2$d din %3$d', 'papetarie-storefront'),
                $range_start,
                $range_end,
                $review_count
            ));
            ?>
          </span>
        </nav>
      <?php endif; ?>
    <?php else : ?>
      <p class="pap-reviews-empty"><?php esc_html_e('Nu există recenzii încă. Fii primul care lasă o recenzie.', 'papetarie-storefront'); ?></p>
    <?php endif; ?>
  </div>

  <div id="pap-review-modal" class="pap-review-modal" hidden aria-hidden="true">
    <div class="pap-review-modal__backdrop" data-review-modal-close></div>
    <div class="pap-review-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pap-review-modal-title">
      <div class="pap-review-modal__accent" aria-hidden="true"></div>
      <div class="pap-review-modal__header">
        <div class="pap-review-modal__heading">
          <h2 id="pap-review-modal-title" class="pap-review-modal__title"><?php esc_html_e('Scrie o recenzie', 'papetarie-storefront'); ?></h2>
          <?php if ($review_modal_subtitle !== '') : ?>
            <p class="pap-review-modal__subtitle"><?php echo esc_html($review_modal_subtitle); ?></p>
          <?php endif; ?>
        </div>
        <button type="button" class="pap-review-modal__close" data-review-modal-close aria-label="<?php esc_attr_e('Închide', 'papetarie-storefront'); ?>">&times;</button>
      </div>

      <div class="pap-review-modal__body">
        <?php if ($can_review) : ?>
          <div id="review_form_wrapper" class="pap-review-form-wrapper">
            <div id="review_form">
              <?php
              $commenter = wp_get_current_commenter();
              $comment_form = [
                  'title_reply' => '',
                  'title_reply_to' => esc_html__('Răspunde lui %s', 'papetarie-storefront'),
                  'comment_notes_after' => '',
                  'label_submit' => esc_html__('Trimite recenzia', 'papetarie-storefront'),
                  'submit_button' => '<button name="%1$s" type="submit" id="%2$s" class="%3$s pap-btn pap-review-modal__submit" disabled>%4$s</button>',
                  'submit_field' => '<p class="form-submit pap-review-modal__footer"><button type="button" class="pap-btn pap-btn--outline" data-review-modal-close>' . esc_html__('Anulează', 'papetarie-storefront') . '</button>%1$s%2$s</p>',
                  'logged_in_as' => '',
                  'comment_field' => '',
              ];

              $name_email_required = (bool) get_option('require_name_email', 1);
              $fields = [
                  'author' => [
                      'label' => __('Numele tău', 'papetarie-storefront'),
                      'type' => 'text',
                      'value' => $commenter['comment_author'],
                      'required' => $name_email_required,
                      'autocomplete' => 'name',
                      'order' => 2,
                  ],
                  'email' => [
                      'label' => __('Email', 'papetarie-storefront'),
                      'type' => 'email',
                      'value' => $commenter['comment_author_email'],
                      'required' => $name_email_required,
                      'autocomplete' => 'email',
                      'order' => 2,
                  ],
              ];

              $comment_form['fields'] = [];

              foreach ($fields as $key => $field) {
                  $field_html = '<p class="comment-form-' . esc_attr($key) . ' pap-review-form-field" style="order:' . (int) $field['order'] . '">';
                  $field_html .= '<label for="' . esc_attr($key) . '" class="pap-text-label">' . esc_html($field['label']);

                  if ($field['required']) {
                      $field_html .= '&nbsp;<span class="required">*</span>';
                  }

                  $field_html .= '</label><input id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" type="' . esc_attr($field['type']) . '" class="pap-input" autocomplete="' . esc_attr($field['autocomplete']) . '" value="' . esc_attr($field['value']) . '" size="30" ' . ($field['required'] ? 'required' : '') . ' /></p>';

                  $comment_form['fields'][$key] = $field_html;
              }

              $account_page_url = wc_get_page_permalink('myaccount');
              if ($account_page_url) {
                  /* translators: %1$s, %2$s: opening and closing link tags */
                  $comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf(esc_html__('Trebuie să fii %1$sautentificat%2$s pentru a lăsa o recenzie.', 'papetarie-storefront'), '<a href="' . esc_url($account_page_url) . '">', '</a>') . '</p>';
              }

              if (wc_review_ratings_enabled()) {
                  $comment_form['comment_field'] = '<div class="comment-form-rating pap-review-form-field" style="order:1"><label for="rating" id="comment-form-rating-label" class="pap-text-label">' . esc_html__('Nota ta', 'papetarie-storefront') . (wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '') . '</label><select name="rating" id="rating" class="pap-select" required>
                      <option value="">' . esc_html__('Alege…', 'papetarie-storefront') . '</option>
                      <option value="5">' . esc_html__('Excelent', 'papetarie-storefront') . '</option>
                      <option value="4">' . esc_html__('Bun', 'papetarie-storefront') . '</option>
                      <option value="3">' . esc_html__('Mediu', 'papetarie-storefront') . '</option>
                      <option value="2">' . esc_html__('Sub așteptări', 'papetarie-storefront') . '</option>
                      <option value="1">' . esc_html__('Foarte slab', 'papetarie-storefront') . '</option>
                  </select></div>';
              }

              $comment_form['comment_field'] .= '<p class="comment-form-title pap-review-form-field" style="order:3"><label for="review_title" class="pap-text-label">' . esc_html__('Titlu scurt', 'papetarie-storefront') . '&nbsp;<span class="required">*</span></label><input type="text" id="review_title" name="review_title" class="pap-input" maxlength="120" required /></p>';

              $comment_form['comment_field'] .= '<p class="comment-form-comment pap-review-form-field" style="order:4"><label for="comment" class="pap-text-label">' . esc_html__('Recenzia ta', 'papetarie-storefront') . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" class="pap-textarea" cols="45" rows="6" maxlength="500" required></textarea><span class="pap-review-modal__counter" data-review-char-counter>0 / 500</span></p>';

              comment_form(apply_filters('woocommerce_product_review_comment_form_args', $comment_form));
              ?>
            </div>
          </div>
        <?php else : ?>
          <p class="pap-reviews-empty"><?php esc_html_e('Doar clienții autentificați care au cumpărat acest produs pot lăsa o recenzie.', 'papetarie-storefront'); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="clear"></div>
</div>

<script>
  (function () {
    var modal = document.getElementById('pap-review-modal');
    var openTriggers = document.querySelectorAll('[data-open-review-modal]');
    if (!modal || !openTriggers.length) {
      return;
    }

    var closeTriggers = modal.querySelectorAll('[data-review-modal-close]');
    var lastFocusedTrigger = null;

    function closeReviewModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');

      if (window.papModalManager) {
        window.papModalManager.close(modal);
      }

      window.setTimeout(function () {
        modal.hidden = true;
      }, 180);

      if (lastFocusedTrigger && typeof lastFocusedTrigger.focus === 'function') {
        window.setTimeout(function () {
          lastFocusedTrigger.focus({ preventScroll: true });
        }, 200);
      }
    }

    function openReviewModal(trigger) {
      lastFocusedTrigger = trigger || null;

      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');

      if (window.papModalManager) {
        window.papModalManager.open(modal, function (info) {
          closeReviewModal();
          if (info && info.focusTarget && typeof info.focusTarget.focus === 'function') {
            info.focusTarget.focus({ preventScroll: true });
          }
        }, {
          focusTarget: trigger
        });
      }

      window.requestAnimationFrame(function () {
        modal.classList.add('is-open');
        var firstField = modal.querySelector('#rating, #author, #review_title');
        if (firstField) {
          firstField.focus({ preventScroll: true });
        }
      });
    }

    Array.prototype.forEach.call(openTriggers, function (trigger) {
      trigger.addEventListener('click', function () {
        openReviewModal(trigger);
      });
    });

    Array.prototype.forEach.call(closeTriggers, function (trigger) {
      trigger.addEventListener('click', closeReviewModal);
    });

    var textarea = modal.querySelector('#comment');
    var counter = modal.querySelector('[data-review-char-counter]');
    if (textarea && counter) {
      textarea.addEventListener('input', function () {
        counter.textContent = textarea.value.length + ' / 500';
      });
    }

    var submitButton = modal.querySelector('.pap-review-modal__submit');
    var requiredFields = modal.querySelectorAll('#rating, #author, #review_title, #comment');
    if (submitButton && requiredFields.length) {
      var checkValidity = function () {
        var allFilled = true;
        Array.prototype.forEach.call(requiredFields, function (field) {
          if (!field.value || !field.value.trim()) {
            allFilled = false;
          }
        });
        submitButton.disabled = !allFilled;
      };

      Array.prototype.forEach.call(requiredFields, function (field) {
        field.addEventListener('input', checkValidity);
        field.addEventListener('change', checkValidity);
      });

      checkValidity();
    }
  })();
</script>
