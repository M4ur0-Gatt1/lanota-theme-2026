<?php get_header(); ?>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="mobile-logo">
        <?php if (function_exists('lanota_2026_render_logo')) { lanota_2026_render_logo('site-logo'); } ?>
    </div>
    <div class="mobile-controls">
        <button class="hamburger-menu" id="hamburger-toggle">
            <i class="fas fa-bars" aria-hidden="true"></i>
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
            <i class="fas fa-times" aria-hidden="true"></i>
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
        <a class="trending-link" href="<?php echo esc_url( add_query_arg(array('trending' => '1', 'sort' => 'views'), home_url('/')) ); ?>">
            <i class="fas fa-fire"></i>
            Tendencias
        </a>
        <?php if (!nm_user_is_subscribed()): ?>
        <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
            <i class="fas fa-star"></i>
            Suscribite
        </a>
        <?php endif; ?>
        <div class="account-cta">
            <div class="avatar-icon">
                <?php if ( is_user_logged_in() ) { echo get_avatar( get_current_user_id(), 44, '', 'Avatar' ); } else { ?>
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                <?php } ?>
            </div>
            <div class="account-actions">
                <?php if ( ! is_user_logged_in() ) : ?>
                    <a class="login-btn" href="<?php echo esc_url( nm_get_login_url( home_url( add_query_arg( array(), isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '/' ) ) ) ); ?>">
                        <i class="fas fa-sign-in-alt"></i>
                        Ingresar
                    </a>
                <?php else: ?>
                    <a class="login-btn" href="<?php echo esc_url( admin_url('profile.php') ); ?>">
                        <i class="fas fa-user"></i>
                        Mi cuenta
                    </a>
                <?php endif; ?>
            </div>
        </div>
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
        <div class="trending-below-nav">
            <a class="trending-nav-btn" href="<?php echo esc_url( add_query_arg(array('trending' => '1', 'sort' => 'views'), home_url('/')) ); ?>">
                <i class="fas fa-fire"></i>
                Tendencias
            </a>
        </div>
        <div class="debate-nav">
            <a class="debate-nav-btn" href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>">
                <i class="fas fa-bullhorn"></i>
                En Debate
            </a>
        </div>
        <div class="sidebar-cta">
            <?php if (!nm_user_is_subscribed()): ?>
            <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
                <i class="fas fa-star"></i>
                Suscribite
            </a>
            <?php endif; ?>
            <div class="account-cta">
                <div class="avatar-icon">
                    <?php if ( is_user_logged_in() ) { echo get_avatar( get_current_user_id(), 44, '', 'Avatar' ); } else { ?>
                        <i class="fas fa-user-circle" aria-hidden="true"></i>
                    <?php } ?>
                </div>
                <div class="account-actions">
                    <?php if ( ! is_user_logged_in() ) : ?>
                        <a class="login-btn" href="<?php echo esc_url( nm_get_login_url( home_url( add_query_arg( array(), isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '/' ) ) ) ); ?>">
                            <i class="fas fa-sign-in-alt"></i>
                            Ingresar
                        </a>
                    <?php else: ?>
                        <a class="login-btn" href="<?php echo esc_url( admin_url('profile.php') ); ?>">
                            <i class="fas fa-user"></i>
                            Mi cuenta
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php
        // Hero Lead Post (prefer category 'principal'; fallback to latest)
        $hero_q = null;
        $principal_term = get_category_by_slug('principal');
        if ($principal_term && isset($principal_term->term_id)) {
            $hero_q = new WP_Query(array(
                'posts_per_page' => 1,
                'cat' => intval($principal_term->term_id),
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array('key' => '_thumbnail_id', 'compare' => 'EXISTS')
                )
            ));
        }
        if (!$hero_q || !$hero_q->have_posts()) {
            // Fallback to latest post
            $hero_q = new WP_Query(array(
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => array(
                    array('key' => '_thumbnail_id', 'compare' => 'EXISTS')
                )
            ));
        }
        $lanota_2026_hero_id = 0;
        if ($hero_q->have_posts()) :
            while ($hero_q->have_posts()) : $hero_q->the_post();
                $lanota_2026_hero_id = get_the_ID();
                $categories = get_the_category();
                $category_name = !empty($categories) ? $categories[0]->name : 'General';
        ?>
        <section class="hero-lead">
            <a class="hero-card" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                <div class="hero-image-wrapper">
                    <?php if (has_post_thumbnail()) :
                        the_post_thumbnail('large', array(
                            'class' => 'hero-image',
                            'loading' => 'eager',
                            'fetchpriority' => 'high',
                            'decoding' => 'async',
                            'sizes' => '(min-width: 992px) 720px, 92vw'
                        ));
                    endif; ?>
                    <span class="hero-badge"><?php echo esc_html($category_name); ?></span>
                </div>
                <div class="hero-body">
                    <h2 class="hero-title"><?php the_title(); ?></h2>
                    <p class="hero-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30, '…' ) ); ?></p>
                </div>
            </a>
        </section>
        <?php
            endwhile; wp_reset_postdata();
        endif;
        ?>
        <div class="header">
            <div class="composer-card <?php echo is_user_logged_in() ? 'is-auth' : 'is-guest'; ?>" id="compose-card" role="button" tabindex="0" aria-label="Componer opinión" <?php if ( ! is_user_logged_in() ) { echo "onclick=\"window.location.href='https://prueba.lanotatucuman.com/suscripcion/';\""; } ?>>
                <div class="composer-avatar">
                    <?php echo get_avatar(get_current_user_id(), 36, '', 'Avatar'); ?>
                </div>
                <div class="composer-body">
                    <?php if ( ! is_user_logged_in() ) : ?>
                    <div class="composer-subscription-hint" role="note">
                        Para publicar tu opinión tenés que estar suscripto
                    </div>
                    <?php endif; ?>
                    <div class="composer-input" aria-hidden="true">Publicá tu opinión</div>
                    <div class="composer-actions">
                        <div class="composer-tools">
                            <i class="fas fa-image" title="Imagen"></i>
                            <i class="fas fa-chart-line" title="Encuesta"></i>
                            <i class="fas fa-smile" title="Emoji"></i>
                            <i class="fas fa-clock" title="Programar"></i>
                            <i class="fas fa-map-marker-alt" title="Ubicación"></i>
                        </div>
                        <a href="<?php echo is_user_logged_in() ? '#' : 'https://prueba.lanotatucuman.com/suscripcion/'; ?>" class="composer-post-btn" role="button">Publicar</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured News Section -->
        <section class="featured-news">
            <h2>Notas Destacadas</h2>
            <div class="featured-wrap">
                <div class="featured-carousel">
                <?php
                $exclude_ids = array();
                if (!empty($lanota_2026_hero_id)) { $exclude_ids[] = intval($lanota_2026_hero_id); }
                $featured_posts = new WP_Query(array(
                    'posts_per_page' => 8,
                    'meta_key' => 'featured_post',
                    'meta_value' => '1',
                    'post__not_in' => $exclude_ids
                ));
                
                if ($featured_posts->have_posts()) :
                    while ($featured_posts->have_posts()) : $featured_posts->the_post();
                        $categories = get_the_category();
                        $category_name = !empty($categories) ? $categories[0]->name : 'General';
                ?>
                    <?php 
                      $thumb_id = get_post_thumbnail_id();
                      $portrait_src = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'featured-story-portrait') : null;
                    ?>
                    <div class="featured-story" data-permalink="<?php the_permalink(); ?>" data-title="<?php echo esc_attr(wp_trim_words(get_the_title(), 12)); ?>" data-excerpt="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 20)); ?>" data-full="<?php echo $portrait_src ? esc_url($portrait_src[0]) : ''; ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php 
                              // Use portrait size primarily; sizes guides browser to pick optimal srcset
                              the_post_thumbnail('featured-story-portrait', array(
                                  'class' => 'featured-story-image',
                                  'loading' => 'lazy',
                                  'decoding' => 'async',
                                  'alt' => the_title_attribute(array('echo' => false)),
                                  'sizes' => '(min-width: 992px) 360px, (max-width: 480px) 82vw, 78vw'
                              ));
                            ?>
                        <?php endif; ?>
                        <div class="featured-story-overlay">
                            <div class="featured-story-category"><?php echo esc_html($category_name); ?></div>
                            <h3 class="featured-story-title"><?php echo wp_trim_words(get_the_title(), 8); ?></h3>
                        </div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    // Fallback to latest posts if no featured posts
                    $latest_posts = new WP_Query(array('posts_per_page' => 8));
                    while ($latest_posts->have_posts()) : $latest_posts->the_post();
                        $categories = get_the_category();
                        $category_name = !empty($categories) ? $categories[0]->name : 'General';
                ?>
                    <?php 
                      $thumb_id = get_post_thumbnail_id();
                      $portrait_src = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'featured-story-portrait') : null;
                    ?>
                    <div class="featured-story" data-permalink="<?php the_permalink(); ?>" data-title="<?php echo esc_attr(wp_trim_words(get_the_title(), 12)); ?>" data-excerpt="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 20)); ?>" data-full="<?php echo $portrait_src ? esc_url($portrait_src[0]) : ''; ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php echo get_the_post_thumbnail(get_the_ID(), 'featured-story-portrait', array(
                                'class' => 'featured-story-image',
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'alt' => the_title_attribute(array('echo' => false)),
                                'sizes' => '(min-width: 992px) 360px, (max-width: 480px) 82vw, 78vw'
                            )); ?>
                        <?php endif; ?>
                        <div class="featured-story-overlay">
                            <div class="featured-story-category"><?php echo esc_html($category_name); ?></div>
                            <h3 class="featured-story-title"><?php echo wp_trim_words(get_the_title(), 8); ?></h3>
                        </div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
                </div>
                <!-- Desktop navigation arrows inside the box -->
                <button class="featured-nav prev" type="button" aria-label="Anterior">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="featured-nav next" type="button" aria-label="Siguiente">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </section>
        

        <!-- Posts Feed (supports Trending mode) -->
        <div class="posts-feed" id="posts-feed">
            <?php 
            $is_trending = isset($_GET['trending']) && $_GET['trending'] == '1';
            if ($is_trending) {
                $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'views';
                $cat_ids = isset($_GET['cat_ids']) ? sanitize_text_field($_GET['cat_ids']) : '';
                $tag_slugs = isset($_GET['tag_slugs']) ? sanitize_text_field($_GET['tag_slugs']) : '';

                $args = array('posts_per_page' => get_option('posts_per_page'));
                if ($sort === 'latest') {
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
                } elseif ($sort === 'likes') {
                    $args['meta_key'] = 'post_likes';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                } else {
                    $args['meta_key'] = 'post_views';
                    $args['orderby'] = 'meta_value_num';
                    $args['order'] = 'DESC';
                }
                $tax_query = array('relation' => 'AND');
                if (!empty($cat_ids)) {
                    $ids = array_filter(array_map('intval', explode(',', $cat_ids)));
                    if (!empty($ids)) {
                        $tax_query[] = array(
                            'taxonomy' => 'category',
                            'field' => 'term_id',
                            'terms' => $ids,
                            'operator' => 'IN',
                        );
                    }
                }
                if (!empty($tag_slugs)) {
                    $slugs = array_filter(array_map('sanitize_title', array_map('trim', explode(',', $tag_slugs))));
                    if (!empty($slugs)) {
                        $tax_query[] = array(
                            'taxonomy' => 'post_tag',
                            'field' => 'slug',
                            'terms' => $slugs,
                            'operator' => 'IN',
                        );
                    }
                }
                if (count($tax_query) > 1) {
                    $args['tax_query'] = $tax_query;
                }

                $tr_q = new WP_Query($args);
                if ($tr_q->have_posts()) :
                    $nm_feed_i = 0;
                    while ($tr_q->have_posts()) : $tr_q->the_post();
                        $nm_feed_i++;
                        get_template_part('template-parts/content', 'post');
                        if ($nm_feed_i % 10 === 0 && function_exists('lanota_2026_render_infeed_ad_by_index')) { lanota_2026_render_infeed_ad_by_index(($nm_feed_i / 10) - 1); }
                    endwhile;
                    wp_reset_postdata();
                else: ?>
                    <div class="no-posts"><p>No hay publicaciones de tendencias.</p></div>
                <?php endif; 
            } else {
                if (have_posts()) :
                    $nm_feed_i = 0;
                    while (have_posts()) : the_post();
                        $nm_feed_i++;
                        get_template_part('template-parts/content', 'post');
                        if ($nm_feed_i % 10 === 0 && function_exists('lanota_2026_render_infeed_ad_by_index')) { lanota_2026_render_infeed_ad_by_index(($nm_feed_i / 10) - 1); }
                    endwhile;
                else : ?>
                    <div class="no-posts"><p>No hay publicaciones disponibles.</p></div>
                <?php endif; 
            } ?>
        </div>
        
        <div class="loading-spinner" id="loading-spinner" style="display: none;">
            <div class="spinner"></div>
        </div>
    </main>

    <!-- Right Sidebar -->
    <aside class="right-sidebar">
        <div class="sidebar-header">
            <div class="search-container">
                <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                    <input type="search" 
                           class="search-input" 
                           placeholder="Buscar noticias..." 
                           value="<?php echo get_search_query(); ?>" 
                           name="s">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span class="sr-only">Buscar</span>
                    </button>
                </form>
            </div>
            <button class="theme-toggle" id="desktop-theme-toggle" aria-label="Cambiar tema">
                <i class="fas fa-moon" id="desktop-theme-icon" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Social Media Links -->
        <?php if (function_exists('lanota_2026_render_social_links')) { ?>
        <div class="widget">
            <div class="widget-header">Seguinos</div>
            <div class="widget-content">
                <?php lanota_2026_render_social_links('social-row'); ?>
            </div>
        </div>
        <?php } ?>

        <!-- Trending Widget (Top of Right Sidebar) -->
        <?php if (is_active_sidebar('sidebar-right-top')): ?>
            <?php dynamic_sidebar('sidebar-right-top'); ?>
        <?php else: ?>
            <div class="widget">
                <div class="widget-header">Tendencias</div>
                <div class="widget-content">
                    <?php
                    $trending_posts = new WP_Query(array(
                        'posts_per_page' => 5,
                        'meta_key' => 'post_views',
                        'orderby' => 'meta_value_num',
                        'order' => 'DESC'
                    ));
                    
                    if ($trending_posts->have_posts()) :
                        while ($trending_posts->have_posts()) : $trending_posts->the_post();
                    ?>
                        <div class="trending-item">
                            <div class="trending-category"><?php echo get_the_category_list(', '); ?></div>
                            <div class="trending-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </div>
                            <div class="trending-posts"><?php echo get_comments_number(); ?> comentarios</div>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        // Fallback to recent posts
                        $recent_posts = new WP_Query(array('posts_per_page' => 5));
                        while ($recent_posts->have_posts()) : $recent_posts->the_post();
                    ?>
                        <div class="trending-item">
                            <div class="trending-category"><?php echo get_the_category_list(', '); ?></div>
                            <div class="trending-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </div>
                            <div class="trending-posts"><?php echo get_comments_number(); ?> comentarios</div>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Right Sidebar Widgets (below Top) -->
        <?php if (is_active_sidebar('sidebar-1')): ?>
            <?php dynamic_sidebar('sidebar-1'); ?>
        <?php endif; ?>

        <!-- Sidebar Ads Area -->
        <?php if (is_active_sidebar('sidebar-ads')): ?>
        <div class="widget">
            <div class="widget-header">Publicidad</div>
            <div class="widget-content">
                <?php dynamic_sidebar('sidebar-ads'); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php get_template_part('template-parts/sidebar-footer'); ?>
    </aside>
</div>


<!-- Mobile Footer Navigation -->
<div class="mobile-footer">
    <a href="<?php echo home_url(); ?>" class="mobile-nav-btn">
        <i class="fas fa-home" aria-hidden="true"></i>
        <span>Inicio</span>
    </a>
    <a href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>" class="mobile-nav-btn">
        <i class="fas fa-bullhorn" aria-hidden="true"></i>
        <span>En debate</span>
    </a>
    <a href="<?php echo esc_url( function_exists('lanota_2026_get_agenda_url') ? lanota_2026_get_agenda_url() : home_url('/agenda/') ); ?>" class="mobile-nav-btn">
        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
        <span>Agenda</span>
    </a>
    <button class="mobile-nav-btn theme-toggle" id="mobile-theme-toggle">
        <i class="fas fa-moon" id="mobile-theme-icon" aria-hidden="true"></i>
        <span>Tema</span>
    </button>
    <button class="mobile-nav-btn" id="mobile-search-btn">
        <i class="fas fa-search" aria-hidden="true"></i>
        <span>Buscar</span>
    </button>
</div>

<!-- Image Lightbox Modal -->
<div class="image-lightbox" id="image-lightbox" style="display:none" aria-hidden="true">
    <div class="lightbox-content">
      <div class="lightbox-bars" id="lightbox-bars"></div>
      <button class="lightbox-close" id="lightbox-close">
        <i class="fas fa-times" aria-hidden="true"></i>
      </button>
      <button class="lightbox-nav lightbox-prev" id="lightbox-prev" aria-label="Anterior">
        <i class="fas fa-chevron-left" aria-hidden="true"></i>
      </button>
      <button class="lightbox-nav lightbox-next" id="lightbox-next" aria-label="Siguiente">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
      </button>
      <div class="lightbox-image-container">
        <img id="lightbox-image" src="" alt="">
      </div>
      <div class="lightbox-info">
        <h3 id="lightbox-title"></h3>
            <p id="lightbox-excerpt"></p>
            <button class="lightbox-read-more" id="lightbox-read-more">
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                Leer Artículo
            </button>
      </div>
    </div>
</div>

<?php get_footer(); ?>
