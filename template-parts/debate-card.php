<?php
$debate_id = get_the_ID();
$votes = get_debate_votes($debate_id);
$user_voted = user_voted_debate($debate_id);
$comment_count = get_comments_number($debate_id);
$debate_tags = get_the_terms($debate_id, 'debate_tags');
$author_id = get_the_author_meta('ID');
$parent_debate = get_post_meta($debate_id, '_parent_debate', true);
$source_post = get_post_meta($debate_id, '_source_post', true);
?>

<article class="debate-card" data-debate-id="<?php echo $debate_id; ?>">
    <div class="debate-card-header">
        <div class="author-info">
            <div class="author-avatar">
                <?php echo get_avatar($author_id, 40); ?>
            </div>
            <div class="author-details">
                <div class="author-name">
                    <?php the_author(); ?>
                    <?php
                    // Show community roles and badges
                    $user_role = get_user_meta($author_id, '_community_role', true);
                    $user_badges = get_user_meta($author_id, '_user_badges', true);
                    
                    if ($user_role) {
                        echo '<span class="community-role">' . esc_html($user_role) . '</span>';
                    }
                    
                    if ($user_badges && is_array($user_badges)) {
                        foreach ($user_badges as $badge) {
                            echo '<span class="user-badge badge-' . sanitize_html_class($badge) . '">' . esc_html($badge) . '</span>';
                        }
                    }
                    ?>
                </div>
                <div class="debate-meta">
                    <time datetime="<?php echo get_the_date('c'); ?>"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' atrás'; ?></time>
                    <?php if ($parent_debate) : ?>
                        <span class="parent-indicator">
                            <i class="fas fa-reply text-muted"></i>
                            Debate derivado
                        </span>
                    <?php endif; ?>
                    <?php if ($source_post) : ?>
                        <span class="source-indicator">
                            <i class="fas fa-newspaper text-muted"></i>
                            Desde noticia
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- More Actions Menu -->
        <div class="more-actions">
            <button class="more-actions-btn" type="button">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="more-actions-menu">
                <button class="menu-item report-post" data-post-id="<?php echo $debate_id; ?>">
                    <i class="fas fa-flag"></i>
                    Denunciar debate
                </button>
                <button class="menu-item share-link" data-post-id="<?php echo $debate_id; ?>">
                    <i class="fas fa-share"></i>
                    Compartir enlace
                </button>
                <button class="menu-item start-debate" data-post-id="<?php echo $debate_id; ?>">
                    <i class="fas fa-comments"></i>
                    Debate derivado
                </button>
            </div>
        </div>
    </div>

    <div class="debate-content">
        <h3 class="debate-title">
            <a href="<?php the_permalink(); ?>" class="text-decoration-none">
                <?php the_title(); ?>
            </a>
        </h3>
        
        <div class="debate-excerpt">
            <?php 
            $excerpt = get_the_excerpt();
            echo wp_trim_words($excerpt, 30, '...');
            ?>
        </div>

        <?php if ($debate_tags && !is_wp_error($debate_tags)) : ?>
            <div class="debate-tags">
                <?php foreach ($debate_tags as $tag) : ?>
                    <a href="<?php echo get_term_link($tag); ?>" class="debate-tag">
                        <?php echo $tag->name; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="debate-actions">
        <div class="action-buttons">
            <!-- Vote Button -->
            <button class="action-btn vote-btn <?php echo $user_voted ? 'voted' : ''; ?>" 
                    data-debate-id="<?php echo $debate_id; ?>">
                <i class="fas fa-arrow-up"></i>
                <span class="vote-count"><?php echo $votes; ?></span>
            </button>

            <!-- Comments Button -->
            <a href="<?php the_permalink(); ?>#comments" class="action-btn comment-btn">
                <i class="fas fa-comment"></i>
                <span class="comment-count"><?php echo $comment_count; ?></span>
            </a>

            <!-- Share Button -->
            <button class="action-btn share-btn" data-url="<?php the_permalink(); ?>">
                <i class="fas fa-share-alt"></i>
                Compartir
            </button>

            <!-- Live Debate Button (if enabled) -->
            <?php if (get_post_meta($debate_id, '_live_debate_enabled', true)) : ?>
                <button class="action-btn live-btn" data-debate-id="<?php echo $debate_id; ?>">
                    <i class="fas fa-broadcast-tower text-danger"></i>
                    En Vivo
                </button>
            <?php endif; ?>
        </div>

        <div class="debate-stats">
            <span class="stat-item">
                <i class="fas fa-eye text-muted"></i>
                <?php echo get_post_meta($debate_id, '_views_count', true) ?: 0; ?> vistas
            </span>
        </div>
    </div>

    <!-- Child Debates Preview (if any) -->
    <?php
    $child_debates = new WP_Query(array(
        'post_type' => 'debate_topic',
        'meta_key' => '_parent_debate',
        'meta_value' => $debate_id,
        'posts_per_page' => 3,
        'post_status' => 'publish'
    ));
    
    if ($child_debates->have_posts()) :
    ?>
        <div class="child-debates">
            <div class="child-debates-header">
                <i class="fas fa-code-branch text-muted me-1"></i>
                <small class="text-muted"><?php echo $child_debates->found_posts; ?> debates derivados</small>
            </div>
            <div class="child-debates-list">
                <?php while ($child_debates->have_posts()) : $child_debates->the_post(); ?>
                    <a href="<?php the_permalink(); ?>" class="child-debate-link">
                        <i class="fas fa-arrow-right text-muted me-1"></i>
                        <?php echo wp_trim_words(get_the_title(), 8); ?>
                    </a>
                <?php endwhile; ?>
                <?php if ($child_debates->found_posts > 3) : ?>
                    <a href="<?php echo get_permalink($debate_id); ?>#child-debates" class="view-all-children">
                        Ver todos los debates derivados
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php 
    endif;
    wp_reset_postdata();
    ?>
</article>
