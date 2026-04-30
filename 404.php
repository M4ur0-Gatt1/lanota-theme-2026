<?php
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main id="primary" class="site-main">
  <section class="notfound-wrap container">
    <header class="notfound-header">
      <h1 class="notfound-title">Página no encontrada</h1>
      <p class="notfound-subtitle">La URL que intentaste abrir no existe o cambió de dirección.</p>
    </header>

    <div class="notfound-actions">
      <a class="btn btn-primary" href="<?php echo esc_url( home_url('/') ); ?>"><i class="fas fa-home"></i> Volver al Inicio</a>
      <a class="btn" href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>"><i class="fas fa-bullhorn"></i> Ir al Foro</a>
      <a class="btn" href="<?php echo esc_url( function_exists('lanota_2026_get_account_url') ? lanota_2026_get_account_url() : home_url('/mi-cuenta/') ); ?>"><i class="fas fa-user"></i> Mi Cuenta</a>
      <form class="notfound-search" role="search" method="get" action="<?php echo esc_url( home_url('/') ); ?>">
        <label class="screen-reader-text" for="s">Buscar:</label>
        <input type="search" id="s" name="s" placeholder="Buscar artículos o temas..." />
        <button type="submit" class="btn"><i class="fas fa-search"></i> Buscar</button>
      </form>
    </div>

    <div class="notfound-suggestions">
      <h2 class="notfound-section-title">Quizá te interese</h2>
      <?php
      $q = new WP_Query(array(
        'posts_per_page' => 6,
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
      ));
      if ($q->have_posts()): echo '<div class="notfound-grid">';
        while ($q->have_posts()): $q->the_post(); ?>
          <article class="nf-card">
            <a href="<?php the_permalink(); ?>" class="nf-thumb">
              <?php if (has_post_thumbnail()) { the_post_thumbnail('medium'); } else { echo '<div class="ph"></div>'; } ?>
            </a>
            <h3 class="nf-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
          </article>
        <?php endwhile; echo '</div>'; wp_reset_postdata();
      else:
        echo '<p>No encontramos sugerencias en este momento.</p>';
      endif; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
