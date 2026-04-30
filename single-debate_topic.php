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
            <a class="debate-nav-btn" href="<?php echo get_post_type_archive_link('debate_topic'); ?>">
                <i class="fas fa-bullhorn"></i>
                En Debate
            </a>
        </div>
        <div class="sidebar-cta" style="display: flex; flex-direction: column; align-items: flex-start; gap: 12px;">
            <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>" style="align-self: flex-start;">
                <i class="fas fa-star"></i>
                Suscribite
            </a>
            <?php nm_render_mi_cuenta_button(); ?>
            <?php if (function_exists('lanota_2026_render_social_links')) { lanota_2026_render_social_links('social-row'); } ?>
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
                $debate_tags = get_the_terms($debate_id, 'hashtag');
                $author_id = get_the_author_meta('ID');
                
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

                
                <div class="single-post-content" style="padding: 20px 0;">
                    <h1 class="post-title" style="font-size: 32px; font-weight: 700; line-height: 1.3; margin-bottom: 16px; color: #333;"><?php the_title(); ?></h1>
                    
                    <div class="post-content" style="font-size: 16px; line-height: 1.6; color: #444; margin-bottom: 20px;">
                        <?php the_content(); ?>
                    </div>
                    
                    <!-- Debate Tags -->
                    <div class="post-categories" style="margin: 20px 0;">
                        <?php
                        $tags = get_the_terms(get_the_ID(), 'hashtag');
                        if ($tags && !is_wp_error($tags)) {
                            echo '<div class="categories-list" style="display: flex; flex-wrap: wrap; gap: 8px;">';
                            foreach ($tags as $tag) {
                                echo '<span class="category-tag" style="display: inline-block; background: #f0f0f0; color: #666; padding: 4px 12px; border-radius: 16px; font-size: 14px; text-decoration: none;">#' . esc_html($tag->name) . '</span>';
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
                <div class="post-actions" style="display: flex; gap: 12px; align-items: center; padding: 16px 0; border-top: 1px solid #eee; margin-top: 20px;">
                    <button class="post-action like-btn" data-debate-id="<?php echo get_the_ID(); ?>" data-action="like" style="display: flex; align-items: center; gap: 6px; background: none; border: 1px solid #ddd; padding: 8px 12px; border-radius: 20px; cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-heart" style="color: #e53935;"></i>
                        <span><?php echo $votes ? $votes : 0; ?></span>
                    </button>
                    <a class="post-action comments-link" href="#comments" style="display: flex; align-items: center; gap: 6px; text-decoration: none; color: #666; border: 1px solid #ddd; padding: 8px 12px; border-radius: 20px; transition: all 0.2s;">
                        <i class="far fa-comment"></i>
                        <span><?php echo get_comments_number(); ?></span>
                    </a>
                    <button class="post-action share-btn" onclick="navigator.share ? navigator.share({title: '<?php echo esc_js(get_the_title()); ?>', url: '<?php echo esc_url(get_permalink()); ?>'}) : copyToClipboard('<?php echo esc_url(get_permalink()); ?>')" style="display: flex; align-items: center; gap: 6px; background: none; border: 1px solid #ddd; padding: 8px 12px; border-radius: 20px; cursor: pointer; transition: all 0.2s;">
                        <i class="fas fa-share"></i>
                        <span>Compartir</span>
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
                        <?php
                        comment_form(array(
                            'title_reply' => 'Agregar una respuesta',
                            'label_submit' => 'Publicar respuesta',
                            'comment_field' => '<div class="form-group"><textarea id="comment" name="comment" class="form-control" rows="4" placeholder="Escribe tu respuesta..." required></textarea></div>',
                            'fields' => array(
                                'author' => '<div class="form-group"><input id="author" name="author" type="text" class="form-control" placeholder="Tu nombre" required /></div>',
                                'email' => '<div class="form-group"><input id="email" name="email" type="email" class="form-control" placeholder="Tu email (no será publicado)" required /></div>'
                            ),
                            'class_submit' => 'btn btn-primary'
                        ));
                        ?>
                    </div>
                <?php else : ?>
                    <div class="comments-closed">
                        <p>Los comentarios están cerrados para este debate.</p>
                    </div>
                <?php endif; ?>
            </section>
            <?php endwhile; ?>
        <?php endif; ?>
    </main>
</div>

<?php get_footer(); ?>
