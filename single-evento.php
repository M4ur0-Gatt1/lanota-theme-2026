<?php
/* Template for single Evento */
get_header();
?>
<main class="container single-evento">
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
    $inicio = get_post_meta(get_the_ID(),'fecha_inicio',true);
    $fin    = get_post_meta(get_the_ID(),'fecha_fin',true);
    $lugar  = get_post_meta(get_the_ID(),'lugar',true);
    $dir    = get_post_meta(get_the_ID(),'direccion',true);
    $precio = get_post_meta(get_the_ID(),'precio',true);
    $enlace = get_post_meta(get_the_ID(),'enlace',true);
    $coords = get_post_meta(get_the_ID(),'coordenadas',true);
    $mapq   = $dir ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($dir) : '';
    if (!$mapq && $coords) { $mapq = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($coords); }
  ?>
  <article <?php post_class('evento-article'); ?>>
    <header class="evento-header">
      <h1><?php the_title(); ?></h1>
      <div class="evento-meta">
        <?php if ($inicio): ?><span class="date"><?php echo esc_html( $inicio . ($fin? ' – '.$fin : '') ); ?></span><?php endif; ?>
        <?php if ($lugar): ?><span class="dot">•</span><span class="place"><?php echo esc_html($lugar); ?></span><?php endif; ?>
        <?php if ($precio): ?><span class="dot">•</span><span class="price"><?php echo esc_html($precio); ?></span><?php endif; ?>
      </div>
    </header>

    <figure class="evento-media">
      <?php if ( has_post_thumbnail() ) { the_post_thumbnail('large'); } ?>
    </figure>

    <div class="evento-content">
      <?php the_content(); ?>
    </div>

    <aside class="evento-info">
      <?php if ($dir): ?>
        <div class="info-row"><strong>Dirección:</strong> <span><?php echo esc_html($dir); ?></span></div>
      <?php endif; ?>
      <?php if ($lugar): ?>
        <div class="info-row"><strong>Lugar:</strong> <span><?php echo esc_html($lugar); ?></span></div>
      <?php endif; ?>
      <?php if ($precio): ?>
        <div class="info-row"><strong>Entrada:</strong> <span><?php echo esc_html($precio); ?></span></div>
      <?php endif; ?>
      <?php if ($enlace): ?>
        <div class="info-row"><a class="btn-primary" href="<?php echo esc_url($enlace); ?>" target="_blank" rel="noopener">Más info / Tickets</a></div>
      <?php endif; ?>
      <?php if ($mapq): ?>
        <div class="info-row"><a class="btn-secondary" href="<?php echo esc_url($mapq); ?>" target="_blank" rel="noopener">Cómo llegar</a></div>
      <?php endif; ?>
    </aside>
  </article>

  <section class="evento-relacionados">
    <h2>Otros eventos</h2>
    <div class="agenda-grid related">
      <?php
        $q = new WP_Query(array(
          'post_type' => 'evento',
          'posts_per_page' => 6,
          'post__not_in' => array(get_the_ID()),
          'orderby' => 'meta_value',
          'meta_key' => 'fecha_inicio',
          'order' => 'ASC',
        ));
        if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
          $img = get_the_post_thumbnail_url(get_the_ID(), 'featured-story-portrait');
      ?>
        <article class="agenda-card small">
          <a class="card-media" href="<?php the_permalink(); ?>">
            <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" /><?php endif; ?>
          </a>
          <div class="card-body"><h3 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3></div>
        </article>
      <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
  </section>

  <?php endwhile; endif; ?>
</main>
<!-- Mobile Footer Navigation -->
<div class="mobile-footer">
    <a href="<?php echo home_url(); ?>" class="mobile-nav-btn">
        <i class="fas fa-home"></i>
        <span>Inicio</span>
    </a>
    <a href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>" class="mobile-nav-btn">
        <i class="fas fa-bullhorn"></i>
        <span>En debate</span>
    </a>
    <a href="<?php echo home_url('/agenda/'); ?>" class="mobile-nav-btn">
        <i class="fas fa-calendar-alt"></i>
        <span>Agenda</span>
    </a>
    <button class="mobile-nav-btn theme-toggle" id="mobile-theme-toggle">
        <i class="fas fa-moon" id="mobile-theme-icon"></i>
        <span>Tema</span>
    </button>
    <button class="mobile-nav-btn" id="mobile-search-btn">
        <i class="fas fa-search"></i>
        <span>Buscar</span>
    </button>
 </div>

<?php get_footer(); ?>
