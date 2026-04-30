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
            <a class="debate-nav-btn" href="<?php echo get_post_type_archive_link('debate_topic'); ?>">
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

    <!-- Right Sidebar -->
    <aside class="right-sidebar">
        <!-- Search Box -->
        <div class="search-widget">
            <form role="search" method="get" class="search-form" action="<?php echo home_url('/'); ?>">
                <div class="search-input-group">
                    <input type="search" class="search-field" placeholder="Buscar noticias..." value="<?php echo get_search_query(); ?>" name="s" />
                    <button type="submit" class="search-submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Theme Toggle -->
        <div class="theme-toggle-widget">
            <button class="theme-toggle-btn" id="theme-toggle" title="Cambiar tema">
                <i class="fas fa-moon" id="theme-icon"></i>
                <span id="theme-text">Modo Oscuro</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>
                <a href="<?php echo get_post_type_archive_link('debate_topic'); ?>" style="color: var(--text-secondary); text-decoration: none;">← Volver</a>
            </h1>
        </div>

        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                    <?php
                    $debate_id = get_the_ID();
                    $votes = get_debate_votes($debate_id);
                    $user_voted = user_voted_debate($debate_id);
                    $debate_tags = get_the_terms($debate_id, 'debate_tags');
                    $author_id = get_the_author_meta('ID');
                    $parent_debate = get_post_meta($debate_id, '_parent_debate', true);
                    $source_post = get_post_meta($debate_id, '_source_post', true);
                    
                    // Increment view count
                    $views = get_post_meta($debate_id, '_views_count', true);
                    $views = $views ? intval($views) + 1 : 1;
                    update_post_meta($debate_id, '_views_count', $views);
                    ?>

                    <!-- Debate Header -->
                    <article class="single-post">
                        <div class="post-header">
                            <img src="<?php echo get_avatar_url(get_the_author_meta('ID'), array('size' => 40)); ?>" 
                                 alt="<?php the_author(); ?>" class="post-avatar">
                            <div class="post-meta">
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

                    
                    <div class="single-post-content">
                        <h1 class="post-title"><?php the_title(); ?></h1>
                        
                        <div class="post-content">
                            <?php the_content(); ?>
                        </div>
                        
                        <!-- Debate Tags -->
                        <div class="post-categories">
                            <?php
                            $tags = get_the_terms(get_the_ID(), 'debate_tags');
                            if ($tags && !is_wp_error($tags)) {
                                echo '<div class="categories-list">';
                                foreach ($tags as $tag) {
                                    echo '<span class="category-tag">' . esc_html($tag->name) . '</span>';
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
                    </div>
                    
                    <!-- Debate Actions -->
                    <div class="post-actions">
                        <button class="post-action like-btn" data-debate-id="<?php echo get_the_ID(); ?>" data-action="like">
                            <i class="fas fa-heart"></i>
                            <span><?php echo get_debate_votes(get_the_ID()); ?></span>
                        </button>
                        <a class="post-action comments-link" href="#comments">
                            <i class="far fa-comment"></i>
                            <span><?php echo get_comments_number(); ?></span>
                        </a>
                        <button class="post-action child-debate-btn" data-bs-toggle="modal" data-bs-target="#childDebateModal">
                            <i class="fas fa-plus"></i>
                            <span>Crear Debate Hijo</span>
                        </button>
                    </div>
                </article>

                <!-- Comments/Responses Feed -->
                <section class="debate-responses" id="comments">
                    <h3 class="responses-title">
                        <i class="fas fa-comments"></i>
                        Respuestas al debate (<?php echo get_comments_number(); ?>)
                    </h3>
                    
                    <?php if (comments_open() || get_comments_number()) : ?>
                        <div class="responses-feed">
                            <?php
                            // Custom comment walker for debate responses
                            $comments = get_comments(array(
                                'post_id' => get_the_ID(),
                                'status' => 'approve',
                                'order' => 'ASC'
                            ));
                            
                            if ($comments) :
                                foreach ($comments as $comment) :
                            ?>
                                <div class="response-item" id="comment-<?php echo $comment->comment_ID; ?>">
                                    <div class="response-header">
                                        <img src="<?php echo get_avatar_url($comment->user_id, array('size' => 40)); ?>" 
                                             alt="<?php echo $comment->comment_author; ?>" class="response-avatar">
                                        <div class="response-meta">
                                            <span class="response-author"><?php echo $comment->comment_author; ?></span>
                                            <div class="response-date">
                                                hace <?php echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="response-content">
                                        <?php echo wpautop($comment->comment_content); ?>
                                    </div>
                                    <div class="response-actions">
                                        <button class="response-action like-btn" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                            <i class="far fa-heart"></i>
                                            <span>0</span>
                                        </button>
                                        <button class="response-action reply-btn" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                            <i class="far fa-comment"></i>
                                            <span>Responder</span>
                                        </button>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <div class="no-responses">
                                    <p>Aún no hay respuestas a este debate. ¡Sé el primero en participar!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comment Form -->
                        <div class="response-form-container">
                            <?php if (is_user_logged_in()) : ?>
                                <?php
                                comment_form(array(
                                    'title_reply' => 'Publicar tu opinión',
                                    'label_submit' => 'Publicar opinión',
                                    'comment_field' => '<div class="form-group"><textarea id="comment" name="comment" class="form-control" rows="4" placeholder="Escribe tu opinión sobre este debate..." required></textarea></div>',
                                    'logged_in_as' => '',
                                    'class_submit' => 'btn btn-primary'
                                ));
                                ?>
                            <?php else : ?>
                                <div class="login-to-comment">
                                    <h4>Publicar tu opinión</h4>
                                    <p>Para participar en este debate necesitas estar registrado.</p>
                                    <div class="comment-cta-actions">
                                        <a class="btn btn-primary" href="<?php echo wp_login_url(get_permalink()); ?>">Iniciar sesión</a>
                                        <a class="btn btn-outline" href="<?php echo home_url('/suscripcion/'); ?>">Registrarse</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="comments-closed">
                            <p>Los comentarios están cerrados para este debate.</p>
                        </div>
                    <?php endif; ?>
                </section><!-- Create Child Debate Button -->
                    <div class="create-child-debate mt-4">
                        <button class="btn btn-outline-danger" id="create-child-debate" data-parent-id="<?php echo $debate_id; ?>">
                            <i class="fas fa-plus me-1"></i>
                            Crear Debate Derivado
                        </button>
                    </div>

                    <!-- Child Debates Section -->
                    <?php
                    $child_debates = new WP_Query(array(
                        'post_type' => 'debate_topic',
                        'meta_key' => '_parent_debate',
                        'meta_value' => $debate_id,
                        'posts_per_page' => -1,
                        'post_status' => 'publish'
                    ));
                    
                    if ($child_debates->have_posts()) :
                    ?>
                        <section class="child-debates mt-5" id="child-debates">
                            <h3 class="section-title">
                                <i class="fas fa-code-branch text-muted me-2"></i>
                                Debates Derivados (<?php echo $child_debates->found_posts; ?>)
                            </h3>
                            <div class="child-debates-grid">
                                <?php while ($child_debates->have_posts()) : $child_debates->the_post(); ?>
                                    <div class="child-debate-card">
                                        <h5 class="child-debate-title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h5>
                                        <div class="child-debate-meta">
                                            <span class="author">por <?php the_author(); ?></span>
                                            <span class="date"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' atrás'; ?></span>
                                            <span class="comments"><?php echo get_comments_number(); ?> comentarios</span>
                                        </div>
                                        <div class="child-debate-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </section>
                    <?php 
                    endif;
                    wp_reset_postdata();
                    ?>

                    <!-- Live Debate Section -->
                    <?php if (get_post_meta($debate_id, '_live_debate_enabled', true)) : ?>
                        <section class="live-debate mt-5" id="live-debate">
                            <div class="live-debate-header">
                                <h3 class="section-title">
                                    <i class="fas fa-broadcast-tower text-danger me-2"></i>
                                    Debate en Vivo
                                    <span class="live-indicator">EN VIVO</span>
                                </h3>
                                <div class="live-debate-info">
                                    <small class="text-muted">
                                        Este debate está activo por tiempo limitado. Participa en tiempo real.
                                    </small>
                                </div>
                            </div>
                            <div class="live-chat-container">
                                <div id="live-chat-messages" class="live-chat-messages"></div>
                                <div class="live-chat-input">
                                    <div class="input-group">
                                        <input type="text" id="live-message-input" class="form-control" placeholder="Escribe tu mensaje...">
                                        <button class="btn btn-danger" id="send-live-message">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>


                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>
