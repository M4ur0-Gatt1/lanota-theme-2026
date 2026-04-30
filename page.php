<?php get_header(); ?>

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
        <?php if (!nm_user_is_subscribed()): ?>
        <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
            <i class="fas fa-star"></i>
            Suscribite
        </a>
        <?php endif; ?>
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
            <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
                <header class="post-header">
                    <h1 class="post-title"><?php the_title(); ?></h1>
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="post-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('large'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </header>

                <div class="post-content">
                    <?php the_content(); ?>
                    <?php
                        wp_link_pages(array(
                            'before' => '<div class="page-links">Páginas:',
                            'after'  => '</div>',
                        ));
                    ?>
                </div>

                <?php if (current_user_can('edit_post', get_the_ID())) : ?>
                    <div class="post-actions">
                        <span class="edit-link"><?php edit_post_link('Editar'); ?></span>
                    </div>
                <?php endif; ?>
            </article>

            <?php
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
            ?>
        <?php endwhile; else : ?>
            <div class="no-posts">
                <div class="post" style="text-align: center; padding: 40px;">
                    <h2>La página no existe</h2>
                    <p>No encontramos contenido para esta página.</p>
                    <a href="<?php echo home_url(); ?>" class="btn">Volver al inicio</a>
                </div>
            </div>
        <?php endif; ?>

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
                        <i class="fas fa-search"></i>
                        <span class="sr-only">Buscar</span>
                    </button>
                </form>
            </div>
            <button class="theme-toggle" id="theme-toggle" aria-label="Cambiar tema">
                <i class="fas fa-moon" id="theme-icon"></i>
            </button>
        </div>

        <!-- Recent Posts Widget -->
        <div class="widget">
            <div class="widget-header">Recientes</div>
            <div class="widget-content">
                <?php
                $recent_posts = new WP_Query(array(
                    'posts_per_page' => 5
                ));
                
                if ($recent_posts->have_posts()) :
                    while ($recent_posts->have_posts()) : $recent_posts->the_post();
                ?>
                    <div class="trending-item">
                        <div class="trending-category"><?php echo get_the_category_list(', '); ?></div>
                        <div class="trending-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </div>
                        <?php
                            $time_diff = human_time_diff(get_the_time('U'), current_time('timestamp'));
                            $content = get_post_field('post_content', get_the_ID());
                            $word_count = str_word_count( wp_strip_all_tags( $content ) );
                            $reading_minutes = max(1, ceil($word_count / 200));
                        ?>
                        <div class="trending-posts">hace <?php echo esc_html($time_diff); ?> • <?php echo esc_html($reading_minutes); ?> min</div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>

        <!-- Categories Widget -->
        <div class="widget">
            <div class="widget-header">Categorías</div>
            <div class="widget-content">
                <?php
                $categories = get_categories(array('number' => 8));
                foreach ($categories as $category) :
                ?>
                    <div class="trending-item">
                        <div class="trending-title">
                            <a href="<?php echo get_category_link($category->term_id); ?>">
                                <?php echo $category->name; ?>
                            </a>
                        </div>
                        <div class="trending-posts"><?php echo $category->count; ?> publicaciones</div>
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
        <span>Inicio</span>
    </a>
    <a href="<?php echo esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ); ?>" class="mobile-nav-btn">
        <i class="fas fa-bullhorn"></i>
        <span>En debate</span>
    </a>
    <a href="<?php echo esc_url( function_exists('lanota_2026_get_agenda_url') ? lanota_2026_get_agenda_url() : home_url('/agenda/') ); ?>" class="mobile-nav-btn">
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
