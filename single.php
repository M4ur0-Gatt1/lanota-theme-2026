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
        <div class="header">
            <h1>
                <a href="<?php echo home_url(); ?>" style="color: var(--text-secondary); text-decoration: none;">← Volver</a>
            </h1>
        </div>

        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article class="single-post">
                    <div class="post-header">
                        <img src="<?php echo get_avatar_url(get_the_author_meta('ID'), array('size' => 50)); ?>" 
                             alt="<?php the_author(); ?>" class="post-avatar" style="width: 50px; height: 50px;">
                        <div class="post-meta">
                            <div>
                                <span class="post-author"><?php the_author(); ?></span>
                                <?php
                                    $time_diff = human_time_diff(get_the_time('U'), current_time('timestamp'));
                                    $content = get_post_field('post_content', get_the_ID());
                                    $word_count = str_word_count( wp_strip_all_tags( $content ) );
                                    $reading_minutes = max(1, ceil($word_count / 200));
                                ?>
                                <div class="post-date">hace <?php echo esc_html($time_diff); ?> • <?php echo esc_html($reading_minutes); ?> min de lectura</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="single-post-content">
                        <h1 class="post-title"><?php the_title(); ?></h1>
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="post-featured-image">
                                <?php the_post_thumbnail('large', array('class' => 'post-image')); ?>
                                <!-- More Actions (three dots) positioned on image -->
                                <div class="post-more-actions">
                                    <button class="more-actions-btn" type="button">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="more-actions-menu">
                                        <button class="menu-item create-debate-from-post" data-post-id="<?php the_ID(); ?>">
                                            <i class="fas fa-comments"></i>
                                            Generar debate a partir de este post
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <?php the_content(); ?>
                        </div>
                        
                        <div class="post-categories">
                            <?php
                            $categories = get_the_category();
                            if (!empty($categories)) {
                                echo '<div class="categories-list">';
                                foreach ($categories as $category) {
                                    echo '<span class="category-tag">' . esc_html($category->name) . '</span>';
                                }
                                echo '</div>';
                            }
                            ?>
                        </div>
                        
                        <!-- Social Share Buttons -->
                        <div class="social-share">
                            <button class="social-share-btn facebook" onclick="shareOnFacebook('<?php echo esc_url(get_permalink()); ?>')">
                                <i class="fab fa-facebook-f"></i>
                            </button>
                            <button class="social-share-btn twitter" onclick="shareOnTwitter('<?php echo esc_js(get_the_title()); ?>', '<?php echo esc_url(get_permalink()); ?>')">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button class="social-share-btn whatsapp" onclick="shareOnWhatsApp('<?php echo esc_js(get_the_title()); ?>', '<?php echo esc_url(get_permalink()); ?>')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                            <button class="social-share-btn telegram" onclick="shareOnTelegram('<?php echo esc_js(get_the_title()); ?>', '<?php echo esc_url(get_permalink()); ?>')">
                                <i class="fab fa-telegram-plane"></i>
                            </button>
                            <button class="social-share-btn copy" onclick="copyToClipboard('<?php echo esc_url(get_permalink()); ?>')">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                        
                        <!-- Open Debate Button -->
                        <div class="open-debate-section">
                            <button class="open-debate-btn" data-post-id="<?php the_ID(); ?>">
                                <i class="fas fa-comments"></i>
                                <span>Abrir Debate</span>
                            </button>
                            <small class="text-muted">Inicia una discusión sobre esta noticia</small>
                        </div>
                        
                        <div class="post-actions">
                            <a class="post-action comments-link" href="<?php echo esc_url( get_comments_link() ); ?>" aria-label="Ver o agregar comentarios en <?php the_title_attribute(); ?>">
                                <i class="far fa-comment"></i>
                                <span><?php comments_number('0 comentarios', '1 comentario', '% comentarios'); ?></span>
                            </a>
                            <?php $likes = intval(get_post_meta(get_the_ID(), 'likes_count', true)); ?>
                            <button class="post-action like-btn" data-post-id="<?php the_ID(); ?>" aria-label="Me gusta">
                                <i class="far fa-heart"></i>
                                <span class="like-count"><?php echo $likes; ?></span>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Minimal Comment Box -->
                <?php if (comments_open()) : ?>
                <section class="minimal-comment" aria-labelledby="minimal-comment-title">
                    <h3 id="minimal-comment-title" class="minimal-comment-title">Deja tu comentario</h3>
                    <div class="minimal-comment-card">
                        <?php
                        // Minimal args for comment_form
                        $comment_args = array(
                            'title_reply' => '',
                            'comment_notes_before' => '',
                            'comment_notes_after' => '',
                            'label_submit' => 'Publicar',
                            'class_submit' => 'btn',
                            'fields' => array(
                                'author' => '<input id="author" name="author" type="text" class="input" placeholder="Nombre" />',
                                'email'  => '<input id="email" name="email" type="email" class="input" placeholder="Email" />',
                            ),
                            'comment_field' => '<textarea id="comment" name="comment" class="textarea" rows="3" placeholder="Escribe tu comentario..."></textarea>',
                        );
                        comment_form($comment_args);
                        ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Related Posts -->
                <section class="related-posts">
                    <h3>Te puede interesar</h3>
                    <div class="related-posts-grid">
                        <?php
                        $current_id = get_the_ID();
                        $cats = wp_get_post_categories($current_id); // array of IDs
                        $tags = wp_get_post_terms($current_id, 'post_tag', array('fields' => 'ids')); // array of IDs

                        $args = array(
                            'posts_per_page' => 3,
                            'post__not_in' => array($current_id),
                            'ignore_sticky_posts' => 1,
                        );

                        $tax_query = array('relation' => 'OR');
                        if (!empty($cats)) {
                            $tax_query[] = array(
                                'taxonomy' => 'category',
                                'field' => 'term_id',
                                'terms' => $cats,
                            );
                        }
                        if (!empty($tags)) {
                            $tax_query[] = array(
                                'taxonomy' => 'post_tag',
                                'field' => 'term_id',
                                'terms' => $tags,
                            );
                        }

                        if (count($tax_query) > 1) {
                            $args['tax_query'] = $tax_query;
                        }

                        $related_posts = new WP_Query($args);
                        
                        if ($related_posts->have_posts()) :
                            while ($related_posts->have_posts()) : $related_posts->the_post();
                        ?>
                            <article class="related-post">
                                <?php if (has_post_thumbnail()) : ?>
                                    <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title(); ?>" class="related-post-image">
                                <?php endif; ?>
                                <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                <p><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                            </article>
                        <?php
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                </section>

                <!-- Más artículos (infinite scroll) -->
                <section class="more-posts" style="margin-top: 30px;">
                    <h3 style="margin-bottom: 16px;">Más artículos</h3>
                    <div class="posts-feed" id="posts-feed">
                        <?php
                        // Cargar algunos artículos iniciales (excluye el actual)
                        $ppp = get_option('posts_per_page');
                        $more_posts = new WP_Query(array(
                            'posts_per_page' => $ppp,
                            'post__not_in' => array(get_the_ID()),
                        ));
                        if ($more_posts->have_posts()) :
                            while ($more_posts->have_posts()) : $more_posts->the_post();
                                get_template_part('template-parts/content', 'post');
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                    <div class="loading-spinner" id="loading-spinner" style="display: none;">
                        <div class="spinner"></div>
                    </div>
                </section>

                

                <!-- Comments Section -->
                <?php if (comments_open() || get_comments_number()) : ?>
                    <section class="comments-section">
                        <?php comments_template(); ?>
                    </section>
                <?php endif; ?>

            <?php endwhile; ?>
        <?php endif; ?>
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

        <!-- Trending Widget -->
        <div class="widget">
            <div class="widget-header">Más Leídos</div>
            <div class="widget-content">
                <?php
                $popular_posts = new WP_Query(array(
                    'posts_per_page' => 5,
                    'meta_key' => 'post_views',
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                    'post__not_in' => array(get_the_ID())
                ));
                
                if ($popular_posts->have_posts()) :
                    while ($popular_posts->have_posts()) : $popular_posts->the_post();
                ?>
                    <div class="trending-item">
                        <div class="trending-category"><?php echo get_the_category_list(', '); ?></div>
                        <div class="trending-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </div>
                        <div class="trending-posts"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ago'; ?></div>
                    </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
                ?>
            </div>
        </div>
        
        <?php get_template_part('template-parts/sidebar-footer'); ?>
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
