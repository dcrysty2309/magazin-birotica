<?php get_header(); ?>

<main class="content-section">
  <div class="section-panel">
    <?php if (have_posts()) : ?>
      <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
          <h1><?php the_title(); ?></h1>
          <?php the_content(); ?>
        </article>
      <?php endwhile; ?>
    <?php else : ?>
      <p><?php esc_html_e('Nu exista continut inca.', 'papetarie-store'); ?></p>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
