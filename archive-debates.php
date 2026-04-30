<?php get_header(); ?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8 col-md-12">
            <div class="debates-header">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title">
                        <i class="fas fa-comments text-danger me-2"></i>
                        En Debate
                    </h1>
                    <button class="btn btn-danger btn-sm" id="new-debate-btn">
                        <i class="fas fa-plus me-1"></i>
                        Nuevo Debate
                    </button>
                </div>

                <!-- Search and Filters -->
                <div class="debates-controls mb-4">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="search-box">
                                <input type="text" id="debate-search" class="form-control" placeholder="Buscar debates...">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="debate-sort" class="form-select">
                                <option value="recent">Más Recientes</option>
                                <option value="commented">Más Comentados</option>
                                <option value="voted">Más Votados</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Algorithm Explanation -->
                <div class="algorithm-info mb-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Algoritmo Transparente:</strong> Los debates se ordenan por fecha de creación (recientes), número de comentarios o votos según tu selección. Sin algoritmos ocultos.
                    </div>
                </div>

                <!-- Tag Filters -->
                <div class="tag-filters mb-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="tag-filter active" data-tag="all">Todos</button>
                        <?php
                        $tags = get_terms(array(
                            'taxonomy' => 'debate_tags',
                            'hide_empty' => false,
                        ));
                        foreach ($tags as $tag) {
                            echo '<button class="tag-filter" data-tag="' . $tag->slug . '">' . $tag->name . '</button>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Debates Feed -->
            <div id="debates-feed" class="debates-feed">
                <?php
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $debates_query = new WP_Query(array(
                    'post_type' => 'debate_topic',
                    'posts_per_page' => 10,
                    'paged' => $paged,
                    'post_status' => 'publish'
                ));

                if ($debates_query->have_posts()) :
                    while ($debates_query->have_posts()) : $debates_query->the_post();
                        get_template_part('template-parts/debate-card');
                    endwhile;
                else :
                    echo '<div class="no-debates text-center py-5">';
                    echo '<i class="fas fa-comments fa-3x text-muted mb-3"></i>';
                    echo '<h3>No hay debates aún</h3>';
                    echo '<p class="text-muted">¡Sé el primero en abrir un debate!</p>';
                    echo '</div>';
                endif;
                wp_reset_postdata();
                ?>
            </div>

            <!-- Load More Button -->
            <?php if ($debates_query->max_num_pages > 1) : ?>
                <div class="text-center mt-4">
                    <button id="load-more-debates" class="btn btn-outline-primary" data-page="2" data-max="<?php echo $debates_query->max_num_pages; ?>">
                        <i class="fas fa-chevron-down me-2"></i>
                        Cargar Más Debates
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4 d-none d-lg-block">
            <div class="sidebar-content">
                <!-- Active Debates Widget -->
                <div class="widget debates-widget">
                    <h5 class="widget-title">
                        <i class="fas fa-fire text-danger me-2"></i>
                        Debates Activos
                    </h5>
                    <?php
                    $active_debates = new WP_Query(array(
                        'post_type' => 'debate_topic',
                        'posts_per_page' => 5,
                        'meta_key' => 'comment_count',
                        'orderby' => 'comment_count',
                        'order' => 'DESC'
                    ));
                    
                    if ($active_debates->have_posts()) :
                        echo '<ul class="list-unstyled">';
                        while ($active_debates->have_posts()) : $active_debates->the_post();
                            echo '<li class="mb-3">';
                            echo '<a href="' . get_permalink() . '" class="text-decoration-none">';
                            echo '<h6 class="mb-1">' . get_the_title() . '</h6>';
                            echo '<small class="text-muted">' . get_comments_number() . ' comentarios</small>';
                            echo '</a>';
                            echo '</li>';
                        endwhile;
                        echo '</ul>';
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>

                <!-- Popular Tags Widget -->
                <div class="widget tags-widget">
                    <h5 class="widget-title">
                        <i class="fas fa-tags text-primary me-2"></i>
                        Temas Populares
                    </h5>
                    <?php
                    $popular_tags = get_terms(array(
                        'taxonomy' => 'debate_tags',
                        'orderby' => 'count',
                        'order' => 'DESC',
                        'number' => 10
                    ));
                    
                    if ($popular_tags) :
                        echo '<div class="tag-cloud">';
                        foreach ($popular_tags as $tag) {
                            $tag_link = get_term_link($tag);
                            echo '<a href="' . $tag_link . '" class="tag-chip">' . $tag->name . ' (' . $tag->count . ')</a>';
                        }
                        echo '</div>';
                    endif;
                    ?>
                </div>

                <!-- Community Stats Widget -->
                <div class="widget stats-widget">
                    <h5 class="widget-title">
                        <i class="fas fa-chart-bar text-success me-2"></i>
                        Estadísticas
                    </h5>
                    <?php
                    $total_debates = wp_count_posts('debate_topic')->publish;
                    $total_comments = get_comments(array('post_type' => 'debate_topic', 'count' => true));
                    $active_users = count(get_users(array('meta_key' => '_debate_votes')));
                    ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_debates; ?></div>
                            <div class="stat-label">Debates Totales</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_comments; ?></div>
                            <div class="stat-label">Comentarios</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $active_users; ?></div>
                            <div class="stat-label">Participantes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Debate Modal -->
<div class="modal fade" id="newDebateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-danger me-2"></i>
                    Nuevo Debate
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="new-debate-form">
                    <div class="mb-3">
                        <label for="debate-title" class="form-label">Título del Debate</label>
                        <input type="text" class="form-control" id="debate-title" required>
                    </div>
                    <div class="mb-3">
                        <label for="debate-content" class="form-label">Contenido</label>
                        <textarea class="form-control" id="debate-content" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="debate-tags" class="form-label">Etiquetas</label>
                        <select class="form-select" id="debate-tags" multiple>
                            <?php
                            foreach ($tags as $tag) {
                                echo '<option value="' . $tag->term_id . '">' . $tag->name . '</option>';
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted">Mantén presionado Ctrl para seleccionar múltiples etiquetas</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="submit-debate">
                    <i class="fas fa-paper-plane me-1"></i>
                    Publicar Debate
                </button>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
