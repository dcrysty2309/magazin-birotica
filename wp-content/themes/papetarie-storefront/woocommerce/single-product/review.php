<?php

defined('ABSPATH') || exit;

global $comment;

$rating = wc_review_ratings_enabled() ? intval(get_comment_meta($comment->comment_ID, 'rating', true)) : 0;
$is_verified = wc_review_is_from_verified_owner($comment->comment_ID);
$show_verified_label = get_option('woocommerce_review_rating_verification_label') === 'yes' && $is_verified;
$author_name = get_comment_author($comment);
$initial = function_exists('mb_substr') ? mb_substr(trim($author_name), 0, 1) : substr(trim($author_name), 0, 1);
$initial = $initial !== '' ? strtoupper($initial) : '?';
$star_icon = papetarie_storefront_icon('star');
$is_awaiting_approval = '0' === $comment->comment_approved;
$review_title = get_comment_meta($comment->comment_ID, 'review_title', true);
?>
<li <?php comment_class('pap-review-card'); ?> id="li-comment-<?php comment_ID(); ?>">
  <div id="comment-<?php comment_ID(); ?>" class="comment_container pap-review-card-inner">
    <div class="pap-review-card-side">
      <span class="pap-review-avatar" aria-hidden="true"><?php echo esc_html($initial); ?></span>
      <p class="pap-review-author"><?php echo esc_html($author_name); ?></p>
      <?php if ($rating > 0) : ?>
        <span class="pap-product-rating" aria-label="<?php echo esc_attr(sprintf(
            /* translators: %d: rating out of 5 */
            __('Rating %d din 5', 'papetarie-storefront'),
            $rating
        )); ?>">
          <?php for ($i = 1; $i <= 5; $i++) : ?>
            <span class="pap-product-rating__star pap-review-star<?php echo $i <= $rating ? ' is-filled' : ''; ?>"><?php echo $star_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
          <?php endfor; ?>
        </span>
      <?php endif; ?>
      <time class="pap-review-date" datetime="<?php echo esc_attr(get_comment_date('c')); ?>"><?php echo esc_html(get_comment_date(wc_date_format())); ?></time>
    </div>

    <div class="pap-review-card-body">
      <?php if ($is_awaiting_approval) : ?>
        <p class="pap-review-awaiting"><em><?php esc_html_e('Recenzia ta așteaptă aprobare', 'papetarie-storefront'); ?></em></p>
      <?php else : ?>
        <?php if ($review_title) : ?>
          <p class="pap-review-title"><?php echo esc_html($review_title); ?></p>
        <?php endif; ?>
        <div class="pap-review-text"><?php comment_text(); ?></div>
      <?php endif; ?>
    </div>

    <?php if ($show_verified_label && !$is_awaiting_approval) : ?>
      <div class="pap-review-card-badge">
        <span class="pap-pill pap-pill--success">✓ <?php esc_html_e('VERIFICAT', 'papetarie-storefront'); ?></span>
      </div>
    <?php endif; ?>
  </div>
