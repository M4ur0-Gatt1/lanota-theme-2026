<?php
/*
Template Name: Mi Cuenta
*/
if (!defined('ABSPATH')) { exit; }

// Redirect non-logged users to masked login, back to this page.
if (!is_user_logged_in()) {
    $dest = function_exists('nm_get_login_url') ? nm_get_login_url( get_permalink() ) : wp_login_url( get_permalink() );
    wp_safe_redirect($dest);
    exit;
}

get_header();
$uid = get_current_user_id();
$saved_ids = function_exists('nm_get_saved_posts') ? nm_get_saved_posts($uid) : array();

// Build query of saved posts (latest first)
$paged = max(1, get_query_var('paged') ? get_query_var('paged') : get_query_var('page'));
$per_page = 10;
$total_saved = count($saved_ids);
$offset = ($paged - 1) * $per_page;
$ids_page = array_slice($saved_ids, $offset, $per_page);
$q = empty($ids_page) ? false : new WP_Query(array(
    'post_type' => 'post',
    'post__in' => $ids_page,
    'orderby' => 'post__in',
    'posts_per_page' => $per_page,
    'ignore_sticky_posts' => true,
));
?>

<!-- Mobile Header -->
<div class="mobile-header">
  <div class="mobile-logo">
    <?php if (function_exists('lanota_2026_render_logo')) { lanota_2026_render_logo('site-logo'); } else { ?>
      <?php if (has_custom_logo()) { the_custom_logo(); } else { ?>
        <h2><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h2>
      <?php } ?>
    <?php } ?>
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
      <?php esc_html_e('Suscribite', 'lanota-theme-2026'); ?>
    </a>
    <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
  </div>
</div>

<div class="container">
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
    <div class="debate-nav">
      <a class="debate-nav-btn" href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>">
        <i class="fas fa-bullhorn"></i>
        <?php esc_html_e('En Debate', 'lanota-theme-2026'); ?>
      </a>
    </div>
    <div class="sidebar-cta">
      <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
        <i class="fas fa-star"></i>
        <?php esc_html_e('Suscribite', 'lanota-theme-2026'); ?>
      </a>
      <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <section class="account-wrap">
      <header class="account-header">
        <h1 class="account-title"><?php esc_html_e('Mi Cuenta', 'lanota-theme-2026'); ?></h1>
        <div class="account-meta">
          <?php $u = wp_get_current_user(); ?>
          <span class="account-user"><i class="fas fa-user-circle"></i> <?php echo esc_html($u->display_name ?: $u->user_login); ?></span>
          <?php if (current_user_can('nm_forum_post')): ?>
            <span class="account-badge paid"><i class="fas fa-crown"></i> <?php esc_html_e('Suscriptor de Pago', 'lanota-theme-2026'); ?></span>
          <?php else: ?>
            <span class="account-badge free"><i class="fas fa-user"></i> <?php esc_html_e('Miembro', 'lanota-theme-2026'); ?></span>
          <?php endif; ?>
        </div>
        <div class="account-cta">
          <a class="btn btn-primary" href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>">
            <i class="fas fa-bullhorn" aria-hidden="true"></i> <?php esc_html_e('Ver En Debate', 'lanota-theme-2026'); ?>
          </a>
        </div>
      </header>

      <?php
        // Build notifications: recent comments on user's debate topics by others
        $my_topics = get_posts(array(
          'author' => $uid,
          'post_type' => 'debate_topic',
          'post_status' => 'publish',
          'fields' => 'ids',
          'posts_per_page' => 200,
        ));
        $notifications = array();
        if (!empty($my_topics)) {
          $comments = get_comments(array(
            'post__in' => $my_topics,
            'status' => 'approve',
            'number' => 10,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
          ));
          if ($comments) {
            foreach ($comments as $c) {
              if (intval($c->user_id) === intval($uid)) { continue; } // skip own comments
              $notifications[] = $c;
            }
          }
        }
      ?>
      <section class="account-section">
        <h2 class="account-section-title"><i class="fas fa-bell"></i> <?php esc_html_e('Notificaciones', 'lanota-theme-2026'); ?></h2>
        <?php if (empty($notifications)) : ?>
          <p><?php esc_html_e('Sin novedades por ahora. Cuando otros comenten tus temas de debate, te aparecerán acá.', 'lanota-theme-2026'); ?></p>
        <?php else: ?>
          <ul class="account-notifications">
            <?php foreach ($notifications as $note):
                $plink = get_comment_link($note);
                $post  = get_post($note->comment_post_ID);
                $when  = sprintf(_x('%s atrás', 'human time diff', 'lanota-theme-2026'), human_time_diff( strtotime($note->comment_date_gmt), current_time('timestamp', true) ));
            ?>
              <li class="notification-item">
                <a class="notification-link" href="<?php echo esc_url($plink); ?>">
                  <strong><?php echo esc_html($note->comment_author ?: __('Usuario', 'lanota-theme-2026')); ?></strong>
                  <span><?php esc_html_e('comentó en tu debate', 'lanota-theme-2026'); ?></span>
                  <em>“<?php echo esc_html( wp_trim_words( $post ? $post->post_title : '', 12 ) ); ?>”</em>
                  <span class="when">· <?php echo esc_html($when); ?></span>
                </a>
                <div class="notification-excerpt">“<?php echo esc_html( wp_trim_words( $note->comment_content, 20 ) ); ?>”</div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>

      <section class="account-section">
        <h2 class="account-section-title"><i class="fas fa-heart"></i> <?php esc_html_e('Debates que me gustaron', 'lanota-theme-2026'); ?></h2>
        <?php
        $user_likes = get_user_meta($uid, '_liked_debates', true);
        if (!is_array($user_likes)) $user_likes = array();
        $total_likes = count($user_likes);
        ?>
        <?php if ($total_likes === 0): ?>
          <p><?php esc_html_e('Todavía no diste me gusta a ningún debate. Tocá el corazón en los debates para sumarlos a tu lista.', 'lanota-theme-2026'); ?></p>
        <?php else: ?>
          <div class="liked-list">
            <?php
            $liked_debates = new WP_Query(array(
              'post_type' => 'debate_topic',
              'post__in' => array_slice($user_likes, 0, 10),
              'orderby' => 'post__in',
              'posts_per_page' => 10
            ));
            if ($liked_debates->have_posts()): while ($liked_debates->have_posts()): $liked_debates->the_post(); ?>
              <article class="liked-item">
                <div class="liked-body">
                  <h3 class="liked-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                  <div class="liked-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo esc_html(get_the_date()); ?></span>
                    <span><i class="fas fa-user"></i> <?php echo get_the_author(); ?></span>
                  </div>
                  <p class="liked-excerpt"><?php echo esc_html( wp_trim_words( get_the_content(), 24 ) ); ?></p>
                </div>
              </article>
            <?php endwhile; wp_reset_postdata(); endif; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="account-section">
        <h2 class="account-section-title"><i class="fas fa-bookmark"></i> <?php esc_html_e('Mis artículos guardados', 'lanota-theme-2026'); ?></h2>
        <?php if ($total_saved === 0): ?>
          <p><?php esc_html_e('Todavía no guardaste artículos. Tocá el ícono de marcador en las notas para sumarlas a tu lista.', 'lanota-theme-2026'); ?></p>
        <?php else: ?>
          <div class="saved-list">
            <?php if ($q && $q->have_posts()): while ($q->have_posts()): $q->the_post(); ?>
              <article class="saved-item">
                <div class="saved-thumb">
                  <a href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()) { the_post_thumbnail('medium'); } else { echo '<div class="ph"></div>'; } ?>
                  </a>
                </div>
                <div class="saved-body">
                  <h3 class="saved-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                  <div class="saved-meta">
                    <span><i class="fas fa-calendar"></i> <?php echo esc_html(get_the_date()); ?></span>
                    <span><i class="fas fa-folder"></i> <?php echo get_the_category_list(', '); ?></span>
                  </div>
                  <p class="saved-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
                  <div class="saved-actions">
                    <?php if (function_exists('nm_render_save_button')) echo nm_render_save_button(get_the_ID()); ?>
                  </div>
                </div>
              </article>
            <?php endwhile; wp_reset_postdata(); endif; ?>
          </div>
          <?php
          // Pagination for saved list
          $total_pages = ceil( $total_saved / $per_page );
          if ($total_pages > 1) {
              $big = 999999999;
              $pagination = paginate_links(array(
                  'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                  'format' => '?paged=%#%',
                  'current' => $paged,
                  'total' => $total_pages,
                  'type' => 'list',
              ));
              if ($pagination) echo '<nav class="account-pagination">' . $pagination . '</nav>';
          }
          ?>
        <?php endif; ?>
      </section>

      <section class="account-links">
        <a class="btn" href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>"><i class="fas fa-bullhorn"></i> <?php esc_html_e('Ir a En Debate', 'lanota-theme-2026'); ?></a>
        <a class="btn" href="<?php echo esc_url( home_url('/') ); ?>"><i class="fas fa-home"></i> <?php esc_html_e('Inicio', 'lanota-theme-2026'); ?></a>
        <a class="btn" href="<?php echo esc_url( wp_logout_url( home_url('/') ) ); ?>"><i class="fas fa-right-from-bracket"></i> <?php esc_html_e('Cerrar sesión', 'lanota-theme-2026'); ?></a>
      </section>
    </section>
  </main>

  <!-- Right Sidebar -->
  <aside class="right-sidebar">
    <div class="sidebar-header">
      <div class="search-container">
        <form role="search" method="get" action="<?php echo home_url('/'); ?>">
          <input type="search" class="search-input" placeholder="<?php esc_attr_e('Buscar noticias...', 'lanota-theme-2026'); ?>" value="<?php echo get_search_query(); ?>" name="s">
          <button type="submit" class="search-btn">
            <i class="fas fa-search"></i>
            <span class="sr-only"><?php esc_html_e('Buscar', 'lanota-theme-2026'); ?></span>
          </button>
        </form>
      </div>
      <button class="theme-toggle" id="theme-toggle" aria-label="<?php esc_attr_e('Cambiar tema', 'lanota-theme-2026'); ?>">
        <i class="fas fa-moon" id="theme-icon"></i>
      </button>
    </div>

    <!-- Recent Posts Widget -->
    <div class="widget">
      <div class="widget-header"><?php esc_html_e('Recientes', 'lanota-theme-2026'); ?></div>
      <div class="widget-content">
        <?php
        $recent_posts = new WP_Query(array('posts_per_page' => 5));
        if ($recent_posts->have_posts()) :
          while ($recent_posts->have_posts()) : $recent_posts->the_post(); ?>
            <div class="trending-item">
              <div class="trending-category"><?php echo get_the_category_list(', '); ?></div>
              <div class="trending-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
              <?php
                $time_diff = human_time_diff(get_the_time('U'), current_time('timestamp'));
                $content = get_post_field('post_content', get_the_ID());
                $word_count = str_word_count( wp_strip_all_tags( $content ) );
                $reading_minutes = max(1, ceil($word_count / 200));
              ?>
              <div class="trending-posts"><?php echo esc_html(sprintf(__('hace %s • %d min', 'lanota-theme-2026'), $time_diff, $reading_minutes)); ?></div>
            </div>
          <?php endwhile; wp_reset_postdata(); endif; ?>
      </div>
    </div>

    <!-- Categories Widget -->
    <div class="widget">
      <div class="widget-header"><?php esc_html_e('Categorías', 'lanota-theme-2026'); ?></div>
      <div class="widget-content">
        <?php $categories = get_categories(array('number' => 8)); foreach ($categories as $category) : ?>
          <div class="trending-item">
            <div class="trending-title">
              <a href="<?php echo get_category_link($category->term_id); ?>"><?php echo esc_html($category->name); ?></a>
            </div>
            <div class="trending-posts"><?php echo intval($category->count); ?> <?php esc_html_e('publicaciones', 'lanota-theme-2026'); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
</div>

<!-- Mobile Footer Navigation -->
<div class="mobile-footer">
  <a href="<?php echo home_url(); ?>" class="mobile-nav-btn">
    <i class="fas fa-home"></i>
    <span><?php esc_html_e('Inicio', 'lanota-theme-2026'); ?></span>
  </a>
  <a href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/') ); ?>" class="mobile-nav-btn">
    <i class="fas fa-bullhorn"></i>
    <span><?php esc_html_e('En debate', 'lanota-theme-2026'); ?></span>
  </a>
  <a href="<?php echo esc_url( function_exists('lanota_2026_get_agenda_url') ? lanota_2026_get_agenda_url() : home_url('/agenda/') ); ?>" class="mobile-nav-btn">
    <i class="fas fa-calendar-alt"></i>
    <span><?php esc_html_e('Agenda', 'lanota-theme-2026'); ?></span>
  </a>
  <button class="mobile-nav-btn theme-toggle" id="mobile-theme-toggle">
    <i class="fas fa-moon" id="mobile-theme-icon"></i>
    <span><?php esc_html_e('Tema', 'lanota-theme-2026'); ?></span>
  </button>
  <button class="mobile-nav-btn" id="mobile-search-btn">
    <i class="fas fa-search"></i>
    <span><?php esc_html_e('Buscar', 'lanota-theme-2026'); ?></span>
  </button>
</div>

<?php get_footer(); ?>
