<article class="post" id="post-<?php the_ID(); ?>">
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
            <span class="post-date">hace <?php echo esc_html($time_diff); ?></span>
            <span class="post-reading-time">• <?php echo esc_html($reading_minutes); ?> min de lectura</span>
        </div>
    </div>
    
    <div class="post-content">
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo wp_trim_words(get_the_content(), 30); ?></p>
        <?php 
            $video_url = get_post_meta(get_the_ID(), '_featured_video_url', true);
            $thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : '';
        ?>
        <?php if ($video_url) : ?>
            <?php 
                $is_youtube = false; $is_vimeo = false; $yt_id = ''; $vm_id = '';
                if (preg_match('~(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})~i', $video_url, $m)) { $is_youtube = true; $yt_id = $m[1]; }
                if (!$is_youtube && preg_match('~vimeo\.com\/(?:video\/)?(\d+)~i', $video_url, $m2)) { $is_vimeo = true; $vm_id = $m2[1]; }
            ?>
            <?php if ($is_youtube) : ?>
                <?php $poster = $thumb_url ? $thumb_url : 'https://i.ytimg.com/vi/' . esc_attr($yt_id) . '/hqdefault.jpg'; ?>
                <div class="post-media embed-video" data-provider="youtube" data-id="<?php echo esc_attr($yt_id); ?>" data-src="https://www.youtube.com/embed/<?php echo esc_attr($yt_id); ?>?autoplay=1&mute=1&rel=0&playsinline=1&modestbranding=1&controls=0&fs=0&iv_load_policy=3">
                    <img class="embed-thumb" src="<?php echo esc_url($poster); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy"/>
                    <button class="mute-toggle" aria-label="Activar sonido" title="Activar sonido" data-muted="1">
                        <i class="fas fa-volume-mute"></i>
                    </button>
                    <!-- More Actions (three dots) -->
                    <div class="more-actions" aria-label="Más acciones" role="group">
                        <button class="more-actions__btn" aria-haspopup="true" aria-expanded="false" title="Más acciones">
                            <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                            <span class="sr-only">Más acciones</span>
                        </button>
                        <div class="more-actions__menu" role="menu" aria-hidden="true">
                            <button class="more-actions__item js-start-debate"
                                 data-post-id="<?php the_ID(); ?>"
                                 data-title="<?php echo esc_attr( get_the_title() ); ?>"
                                 data-url="<?php echo esc_url( get_permalink() ); ?>">
                                <i class="fas fa-comments" aria-hidden="true"></i>
                                Iniciar debate a partir de este posteo
                            </button>
                        </div>
                    </div>
                </div>
            <?php elseif ($is_vimeo) : ?>
                <div class="post-media embed-video" data-provider="vimeo" data-id="<?php echo esc_attr($vm_id); ?>" data-src="https://player.vimeo.com/video/<?php echo esc_attr($vm_id); ?>?autoplay=1&muted=1&title=0&byline=0&portrait=0">
                    <?php if ($thumb_url): ?>
                        <img class="embed-thumb" src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy"/>
                    <?php else: ?>
                        <div class="embed-poster"></div>
                    <?php endif; ?>
                    <button class="mute-toggle" aria-label="Activar sonido" title="Activar sonido" data-muted="1">
                        <i class="fas fa-volume-mute"></i>
                    </button>
                    <!-- More Actions (three dots) -->
                    <div class="more-actions" aria-label="Más acciones" role="group">
                        <button class="more-actions__btn" aria-haspopup="true" aria-expanded="false" title="Más acciones">
                            <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                            <span class="sr-only">Más acciones</span>
                        </button>
                        <div class="more-actions__menu" role="menu" aria-hidden="true">
                            <button class="more-actions__item js-start-debate"
                                 data-post-id="<?php the_ID(); ?>"
                                 data-title="<?php echo esc_attr( get_the_title() ); ?>"
                                 data-url="<?php echo esc_url( get_permalink() ); ?>">
                                <i class="fas fa-comments" aria-hidden="true"></i>
                                Iniciar debate a partir de este posteo
                            </button>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="post-media">
                    <video class="post-video" playsinline muted preload="metadata" poster="<?php echo esc_url($thumb_url); ?>" data-src="<?php echo esc_url($video_url); ?>"></video>
                    <button class="mute-toggle" aria-label="Activar sonido" title="Activar sonido" data-muted="1">
                        <i class="fas fa-volume-mute"></i>
                    </button>
                    <!-- More Actions (three dots) -->
                    <div class="more-actions" aria-label="Más acciones" role="group">
                        <button class="more-actions__btn" aria-haspopup="true" aria-expanded="false" title="Más acciones">
                            <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                            <span class="sr-only">Más acciones</span>
                        </button>
                        <div class="more-actions__menu" role="menu" aria-hidden="true">
                            <button class="more-actions__item js-start-debate"
                                 data-post-id="<?php the_ID(); ?>"
                                 data-title="<?php echo esc_attr( get_the_title() ); ?>"
                                 data-url="<?php echo esc_url( get_permalink() ); ?>">
                                <i class="fas fa-comments" aria-hidden="true"></i>
                                Iniciar debate a partir de este posteo
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
        <?php else : ?>
            <?php if (has_post_thumbnail()) : ?>
            <div class="post-image-wrapper">
                <a href="<?php the_permalink(); ?>" class="post-image-link">
                    <img src="<?php the_post_thumbnail_url('medium_large'); ?>" 
                         alt="<?php the_title(); ?>" 
                         class="post-image"
                         loading="lazy">
                </a>
                <!-- More Actions (three dots) -->
                <div class="more-actions" aria-label="Más acciones" role="group">
                    <button class="more-actions__btn" aria-haspopup="true" aria-expanded="false" title="Más acciones">
                        <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                        <span class="sr-only">Más acciones</span>
                    </button>
                    <div class="more-actions__menu" role="menu" aria-hidden="true">
                        <button class="more-actions__item js-start-debate"
                             data-post-id="<?php the_ID(); ?>"
                             data-title="<?php echo esc_attr( get_the_title() ); ?>"
                             data-url="<?php echo esc_url( get_permalink() ); ?>">
                            <i class="fas fa-comments" aria-hidden="true"></i>
                            Iniciar debate a partir de este posteo
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
        <?php endif; ?>
    </div>
    
    <div class="post-actions">
        <a class="post-action comments-link" href="<?php echo esc_url( get_comments_link() ); ?>" aria-label="Ver o agregar comentarios en <?php the_title_attribute(); ?>">
            <i class="far fa-comment"></i>
            <span><?php comments_number('0', '1', '%'); ?></span>
        </a>
        <?php $likes = intval(get_post_meta(get_the_ID(), 'likes_count', true)); ?>
        <button class="post-action like-btn" data-post-id="<?php the_ID(); ?>" aria-label="Me gusta">
            <i class="far fa-heart"></i>
            <span class="like-count"><?php echo $likes; ?></span>
        </button>
        <button class="post-action save-btn" data-post-id="<?php the_ID(); ?>" aria-label="Guardar" title="Guardar">
            <i class="far fa-bookmark"></i>
        </button>
    </div>
</article>
