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
        <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
            <i class="fas fa-star"></i>
            Suscribite
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
                En Debate
            </a>
        </div>
        <div class="sidebar-cta">
            <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
                <i class="fas fa-star"></i>
                Suscribite
            </a>
            <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>
                <?php printf('Resultados para: "%s"', get_search_query()); ?>
                <span style="font-size: 14px; color: var(--text-secondary); font-weight: normal;">
                    (<?php echo $wp_query->found_posts; ?> resultados)
                </span>
            </h1>
        </div>

        <div class="posts-feed" id="posts-feed">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'post'); ?>
                <?php endwhile; ?>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php
                    echo paginate_links(array(
                        'prev_text' => '← Anterior',
                        'next_text' => 'Siguiente →',
                        'type' => 'list'
                    ));
                    ?>
                </div>
                
            <?php else : ?>
                <div class="no-results">
                    <div class="post" style="text-align: center; padding: 40px;">
                        <h2>No se encontraron resultados</h2>
                        <p>No se encontraron publicaciones que coincidan con tu búsqueda "<strong><?php echo get_search_query(); ?></strong>".</p>
                        <p>Intenta con:</p>
                        <ul style="text-align: left; margin: 20px 0; color: var(--text-secondary);">
                            <li>Verificar la ortografía</li>
                            <li>Usar términos más generales</li>
                            <li>Usar menos palabras clave</li>
                        </ul>
                        <div style="margin-top: 30px;">
                            <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                                <input type="search" 
                                       class="search-input" 
                                       style="width: 300px; margin-right: 10px;"
                                       placeholder="Intentar nueva búsqueda..." 
                                       name="s">
                                <button type="submit" class="btn">Buscar</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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

        <!-- Related Posts Widget -->
        <div class="widget">
            <div class="widget-header">Artículos Relacionados</div>
            <div class="widget-content">
                <?php
                $related_posts = new WP_Query(array(
                    'posts_per_page' => 5,
                    'post__not_in' => array(get_the_ID()),
                    'category__in' => wp_get_post_categories(get_the_ID())
                ));
                
                if ($related_posts->have_posts()) :
                    while ($related_posts->have_posts()) : $related_posts->the_post();
                ?>
                    <div class="trending-item">
                        <div class="trending-category"><?php echo get_the_category_list(', '); ?></div>
                        <div class="trending-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </div>
                        <div class="trending-posts"><?php echo 'hace ' . human_time_diff(get_the_time('U'), current_time('timestamp')); ?></div>
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

<style>
.single-post {
    padding: 20px;
}

.single-post .post-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.post-title {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.3;
    margin-bottom: 20px;
}

.post-featured-image {
    margin: 20px 0;
    text-align: center;
}

.single-post-content .post-content {
    font-size: 16px;
    line-height: 1.7;
    margin-bottom: 30px;
}

.single-post-content .post-content p {
    margin-bottom: 16px;
}

.categories-list {
    margin: 20px 0;
}

.category-tag {
    display: inline-block;
    background-color: var(--primary-color);
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    margin-right: 8px;
    text-decoration: none;
}

.related-posts {
    margin-top: 40px;
    padding: 20px;
    border-top: 1px solid var(--border-color);
}

.related-posts h3 {
    margin-bottom: 20px;
    font-size: 20px;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.related-post {
    background-color: var(--surface-color);
    border-radius: 12px;
    padding: 15px;
    border: 1px solid var(--border-color);
}

.related-post-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.related-post h4 {
    margin-bottom: 8px;
    font-size: 14px;
}

.related-post p {
    font-size: 12px;
    color: var(--text-secondary);
}

.comments-section {
    margin-top: 40px;
    padding: 20px;
    border-top: 1px solid var(--border-color);
}

.pagination {
    padding: 20px;
    text-align: center;
}

.pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 4px;
    background-color: var(--surface-color);
    color: var(--text-color);
    text-decoration: none;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.pagination .page-numbers.current {
    background-color: var(--primary-color);
    color: white;
}

.search-result mark {
    background-color: yellow;
    color: black;
    padding: 2px 4px;
    border-radius: 3px;
}

@media (max-width: 768px) {
    .related-posts-grid {
        grid-template-columns: 1fr;
    }
    
    .post-title {
        font-size: 24px;
    }
}
</style>

<?php get_footer(); ?>
