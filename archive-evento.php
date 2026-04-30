<?php
/* Template for Agenda Cultural archive (CPT: evento) */
get_header();
?>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="mobile-logo">
        <?php if (function_exists('lanota_2026_render_logo')) { lanota_2026_render_logo('site-logo'); } ?>
    </div>
    <div class="mobile-controls">
        <button class="hamburger-menu" id="hamburger-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
</div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu">
    <div class="mobile-menu-header">
        <div class="mobile-logo">
            <?php if (function_exists('lanota_2026_render_logo')) { lanota_2026_render_logo('site-logo'); } else { ?>
                <?php if (has_custom_logo()) { the_custom_logo(); } else { ?>
                    <h2><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h2>
                <?php } ?>
            <?php } ?>
        </div>
        <button class="mobile-menu-close" id="mobile-menu-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav>
        <?php
        wp_nav_menu(array(
            'theme_location' => 'mobile',
            'menu_class' => 'mobile-menu-nav',
            'container' => false,
            'fallback_cb' => 'lanota_2026_mobile_fallback_menu'
        ));
        ?>
    </nav>
    <div class="mobile-menu-extras">
        <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
            <i class="fas fa-star"></i>
            Suscribite
        </a>
        <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
    </div>
    
</div>

<div class="container agenda-layout">
  <!-- Left Sidebar -->
  <aside class="left-sidebar">
      <div class="logo">
          <?php if (function_exists('lanota_2026_render_logo')) { lanota_2026_render_logo('site-logo'); } else { ?>
              <?php if (has_custom_logo()) { the_custom_logo(); } else { ?>
                  <h2><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h2>
              <?php } ?>
          <?php } ?>
      </div>
      
      <nav class="main-navigation">
          <?php
          wp_nav_menu(array(
              'theme_location' => 'primary',
              'menu_class' => 'nav-menu',
              'container' => false,
              'fallback_cb' => 'lanota_2026_fallback_menu'
          ));
          ?>
      </nav>
      <div class="sidebar-cta">
          <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
              <i class="fas fa-star"></i>
              Suscribite
          </a>
          <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
      </div>
  </aside>

  <!-- Main Content: Agenda feed (no right sidebar on desktop) -->
  <main class="main-content agenda-archive">
    <header class="agenda-header">
      <h1>Agenda Cultural</h1>
      <form class="agenda-filtros" method="get" action="<?php echo esc_url( get_post_type_archive_link('evento') ); ?>">
        <?php 
          $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'week';
          $from   = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
          $to     = isset($_GET['to']) ? sanitize_text_field($_GET['to']) : '';
        ?>
        <div class="filters-row">
          <div class="segmented">
            <label><input type="radio" name="period" value="week" <?php checked($period==='week'); ?>> <span>Semana</span></label>
            <label><input type="radio" name="period" value="month" <?php checked($period==='month'); ?>> <span>Mes</span></label>
            <label><input type="radio" name="period" value="all" <?php checked($period==='all'); ?>> <span>Todos</span></label>
          </div>
          <div class="range">
            <input type="date" name="from" value="<?php echo esc_attr($from); ?>" placeholder="Desde">
            <span class="tilde">–</span>
            <input type="date" name="to" value="<?php echo esc_attr($to); ?>" placeholder="Hasta">
            <button class="btn-apply" type="submit">Aplicar</button>
          </div>
        </div>
      </form>
    </header>

    <?php if ( have_posts() ) : ?>
      <div class="agenda-grid">
        <?php while ( have_posts() ) : the_post();
          $inicio = get_post_meta(get_the_ID(),'fecha_inicio',true);
          $fin    = get_post_meta(get_the_ID(),'fecha_fin',true);
          $lugar  = get_post_meta(get_the_ID(),'lugar',true);
          $precio = get_post_meta(get_the_ID(),'precio',true);
          $link   = get_post_meta(get_the_ID(),'enlace',true);
          $img    = get_the_post_thumbnail_url(get_the_ID(), 'featured-story-portrait');
        ?>
        <article class="agenda-card">
          <a class="card-media" href="<?php the_permalink(); ?>" aria-label="Ver evento: <?php the_title_attribute(); ?>">
            <?php if ($img): ?>
              <img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
            <?php else: ?>
              <div class="img-placeholder"></div>
            <?php endif; ?>
          </a>
          <div class="card-body">
            <h2 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <div class="card-meta">
              <?php if ($inicio): ?><span class="date"><?php echo esc_html( $inicio . ($fin? ' – '.$fin : '') ); ?></span><?php endif; ?>
              <?php if ($lugar): ?><span class="dot">•</span><span class="place"><?php echo esc_html($lugar); ?></span><?php endif; ?>
              <?php if ($precio): ?><span class="dot">•</span><span class="price"><?php echo esc_html($precio); ?></span><?php endif; ?>
            </div>
          </div>
        </article>
        <?php endwhile; ?>
      </div>

      <div class="pagination">
        <?php the_posts_pagination(); ?>
      </div>
    <?php else: ?>
      <p class="no-results">No hay eventos para el período seleccionado.</p>
    <?php endif; ?>
  </main>
</div>

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
