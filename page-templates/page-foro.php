<?php
/*
Template Name: En Debate
*/

if (!defined('ABSPATH')) { exit; }

get_header();
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
    <?php if (!nm_user_is_subscribed()): ?>
    <a class="subscribe-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>">
      <i class="fas fa-star"></i>
      <?php esc_html_e('Suscribite', 'lanota-theme-2026'); ?>
    </a>
    <?php endif; ?>
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
    <div class="sidebar-cta" style="display: flex; flex-direction: column; align-items: flex-start; gap: 12px;">
      <?php if (!nm_user_is_subscribed()): ?>
      <a class="subscribe-btn theme-aware-btn" href="<?php echo lanota_2026_get_subscription_url(); ?>" style="background: var(--surface-color); color: var(--text-color) !important; border: 1px solid var(--border-color);">
        <i class="fas fa-star"></i>
        <?php esc_html_e('Suscribite', 'lanota-theme-2026'); ?>
      </a>
      <?php endif; ?>
      <?php nm_render_mi_cuenta_button(); ?>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <section class="forum-wrap">
    <header class="forum-header">
      <h1 class="forum-title"><?php esc_html_e('En Debate', 'lanota-theme-2026'); ?></h1>
      <p class="forum-subtitle"><?php esc_html_e('Espacio para proponer y debatir temas de actualidad.', 'lanota-theme-2026'); ?></p>
    </header>

    <?php
      // View tabs: destacados | recientes
      $view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'destacados';
      if (!in_array($view, array('destacados','recientes'), true)) { $view = 'destacados'; }
      $base = get_permalink();
      $url_top = add_query_arg('view','destacados',$base);
      $url_new = add_query_arg('view','recientes',$base);
    ?>
    <nav class="community-tabs" aria-label="Cambiar vista del muro">
      <a class="tab<?php echo $view==='destacados'?' is-active':''; ?>" href="<?php echo esc_url($url_top); ?>"><i class="fas fa-fire"></i> <?php esc_html_e('Destacados', 'lanota-theme-2026'); ?></a>
      <a class="tab<?php echo $view==='recientes'?' is-active':''; ?>" href="<?php echo esc_url($url_new); ?>"><i class="fas fa-clock"></i> <?php esc_html_e('Recientes', 'lanota-theme-2026'); ?></a>
    </nav>

    <!-- Foro: Modal de políticas -->
    <div class="forum-modal" id="forumPolicyModal" aria-hidden="true" role="dialog" aria-labelledby="forumPolicyTitle" aria-describedby="forumPolicyBody">
      <div class="forum-modal__backdrop" data-close="modal"></div>
      <div class="forum-modal__dialog" role="document">
        <button class="forum-modal__close" type="button" aria-label="Cerrar" data-close="modal">×</button>
        <h2 id="forumPolicyTitle"><i class="fas fa-shield-alt"></i> <?php esc_html_e('Políticas del Foro', 'lanota-theme-2026'); ?></h2>
        <div id="forumPolicyBody" class="forum-modal__content">
          <p><?php esc_html_e('Este foro es moderado. Buscamos un intercambio respetuoso y constructivo.', 'lanota-theme-2026'); ?></p>
          <ul>
            <li><?php esc_html_e('No se permiten discursos de odio, amenazas ni incitación a la violencia.', 'lanota-theme-2026'); ?></li>
            <li><?php esc_html_e('No se tolera acoso, doxxing ni discriminación.', 'lanota-theme-2026'); ?></li>
            <li><?php esc_html_e('Argumentá con fuentes cuando sea posible. Evitá desinformación.', 'lanota-theme-2026'); ?></li>
            <li><?php esc_html_e('El muro es compartido por toda la comunidad: evitá spam y contenido fuera de tema.', 'lanota-theme-2026'); ?></li>
            <li><?php esc_html_e('Los moderadores pueden editar, ocultar o eliminar publicaciones que incumplan estas normas.', 'lanota-theme-2026'); ?></li>
          </ul>
          <p><?php esc_html_e('Al participar aceptás estas políticas y nuestra Política de Privacidad y Términos del Sitio.', 'lanota-theme-2026'); ?></p>
        </div>
        <div class="forum-modal__actions">
          <button id="forumPolicyAccept" class="btn btn-primary" type="button">
            <i class="fas fa-check"></i> <?php esc_html_e('Entendido', 'lanota-theme-2026'); ?>
          </button>
        </div>
      </div>
    </div>

    <?php
    // Notices
    $notice = isset($_GET['nm_forum']) ? sanitize_key($_GET['nm_forum']) : '';
    if ($notice) {
        $msg = '';
        if ($notice === 'created') {
            $msg = __('¡Tema creado correctamente!', 'lanota-theme-2026');
        } elseif ($notice === 'no_perm') {
            $msg = __('Tu suscripción no permite crear temas en el foro.', 'lanota-theme-2026');
        } elseif ($notice === 'invalid') {
            $msg = __('Solicitud inválida. Intenta nuevamente.', 'lanota-theme-2026');
        } elseif ($notice === 'short') {
            $msg = __('El título o el contenido son demasiado cortos.', 'lanota-theme-2026');
        } elseif ($notice === 'error') {
            $msg = __('Ocurrió un error al crear el tema.', 'lanota-theme-2026');
        }
        if ($msg) {
            echo '<div class="forum-notice" role="status">' . esc_html($msg) . '</div>';
        }
    }
    ?>

    <?php if (current_user_can('nm_forum_post')): ?>
      <section class="forum-new-topic is-collapsible" aria-labelledby="newTopicHeader" role="region">
        <div class="collapsible-header" id="newTopicHeader">
          <h2 class="forum-section-title">
            <?php esc_html_e('Proponer un tema', 'lanota-theme-2026'); ?>
          </h2>
          <button type="button" class="collapsible-toggle" aria-expanded="false" aria-controls="newTopicPanel" aria-label="<?php esc_attr_e('Mostrar formulario para crear nuevo tema', 'lanota-theme-2026'); ?>">
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
            <span class="sr-only"><?php esc_html_e('Mostrar/ocultar formulario', 'lanota-theme-2026'); ?></span>
          </button>
        </div>
        <form id="newTopicPanel" class="forum-form is-collapsed" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" hidden aria-labelledby="newTopicHeader">
          <input type="hidden" name="action" value="nm_create_debate_topic" />
          <?php wp_nonce_field('nm_forum_create', 'nm_forum_nonce'); ?>
          <div class="form-row">
            <label for="topic_title" class="required"><?php esc_html_e('Título del tema', 'lanota-theme-2026'); ?> <span aria-label="campo requerido">*</span></label>
            <input type="text" id="topic_title" name="topic_title" required minlength="6" maxlength="140" placeholder="<?php esc_attr_e('Escribe un título claro...', 'lanota-theme-2026'); ?>" aria-describedby="topic-title-help" />
            <div id="topic-title-help" class="form-help">Mínimo 6 caracteres, máximo 140</div>
          </div>
          <div class="form-row">
            <label for="topic_content" class="required"><?php esc_html_e('Descripción / Argumento', 'lanota-theme-2026'); ?> <span aria-label="campo requerido">*</span></label>
            <div class="debate-editor-container">
              <textarea id="topic_content" name="topic_content" rows="6" required minlength="20" placeholder="<?php esc_attr_e('Explicá de qué trata y por qué es relevante... Usá #hashtags y emojis 😊', 'lanota-theme-2026'); ?>" aria-describedby="topic-content-help"></textarea>
              <div id="topic-content-help" class="form-help">Mínimo 20 caracteres. Podés usar hashtags y emojis</div>
              
              <!-- Editor Toolbar -->
              <div class="editor-toolbar">
                <button type="button" class="toolbar-btn emoji-btn" id="emoji-picker-btn" aria-label="Agregar emoji" title="Agregar emoji">
                  <i class="fas fa-smile"></i>
                </button>
                <button type="button" class="toolbar-btn hashtag-btn" id="hashtag-helper-btn" aria-label="Ver hashtags populares" title="Hashtags populares">
                  <i class="fas fa-hashtag"></i>
                </button>
              </div>

              <!-- Emoji Picker -->
              <div class="emoji-picker" id="emoji-picker" style="display: none;">
                <div class="emoji-categories">
                  <button type="button" class="emoji-cat-btn active" data-category="smileys">😊</button>
                  <button type="button" class="emoji-cat-btn" data-category="people">👋</button>
                  <button type="button" class="emoji-cat-btn" data-category="nature">🌟</button>
                  <button type="button" class="emoji-cat-btn" data-category="objects">⚽</button>
                  <button type="button" class="emoji-cat-btn" data-category="symbols">❤️</button>
                  <button type="button" class="emoji-cat-btn" data-category="flags">🇦🇷</button>
                </div>
                <div class="emoji-grid" id="emoji-grid">
                  <!-- Emojis will be populated by JavaScript -->
                </div>
              </div>


              <!-- Hashtag Helper -->
              <div class="hashtag-helper" id="hashtag-helper" style="display: none;">
                <div class="hashtag-suggestions" id="hashtag-suggestions">
                  <!-- Popular hashtags will be populated -->
                </div>
              </div>
            </div>
          </div>
          <div class="form-row">
            <label for="topic_images"><?php esc_html_e('Imágenes (hasta 2) — formatos: WEBP, PNG o JPG', 'lanota-theme-2026'); ?></label>
            <input type="file" id="topic_images" name="topic_images[]" accept=".webp,.png,.jpg,.jpeg" multiple />
            <small><?php esc_html_e('Opcional. Máximo 2 archivos.', 'lanota-theme-2026'); ?></small>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary" aria-describedby="submit-help">
              <i class="fas fa-paper-plane" aria-hidden="true"></i> <?php esc_html_e('Publicar tema', 'lanota-theme-2026'); ?>
            </button>
          </div>
        </form>
      </section>
    <?php else: ?>
      <section class="forum-cta">
        <div class="forum-cta-card">
          <h2><i class="fas fa-lock"></i> <?php esc_html_e('Suscribite para publicar en el foro', 'lanota-theme-2026'); ?></h2>
          <p><?php esc_html_e('Los suscriptores de pago pueden proponer nuevos temas de debate y participar como autores.', 'lanota-theme-2026'); ?></p>
          <div class="cta-actions">
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/suscripcion/')); ?>"><?php esc_html_e('Ver planes', 'lanota-theme-2026'); ?></a>
            <?php if (!is_user_logged_in()): ?>
              <?php $forum_back = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/'); ?>
              <a class="btn" href="<?php echo esc_url( function_exists('nm_get_login_url') ? nm_get_login_url( $forum_back ) : wp_login_url( $forum_back ) ); ?>"><?php esc_html_e('Iniciar sesión', 'lanota-theme-2026'); ?></a>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="forum-topics">
      <h2 class="forum-section-title">
        <?php echo $view==='destacados' ? esc_html__('Temas destacados', 'lanota-theme-2026') : esc_html__('Temas recientes', 'lanota-theme-2026'); ?>
      </h2>
      <?php
      $paged = max(1, get_query_var('paged') ? get_query_var('paged') : get_query_var('page'));
      $args = array(
          'post_type' => 'debate_topic',
          'post_status' => 'publish',
          'posts_per_page' => 10,
          'paged' => $paged,
          'ignore_sticky_posts' => true,
      );
      // Base order by date DESC; we'll sort Destacados manually by score below
      $args['orderby'] = 'date';
      $args['order'] = 'DESC';
      $q = new WP_Query($args);
      if ($q->have_posts()):
        // Collect items and compute score if destacados
        $items = array();
        while ($q->have_posts()): $q->the_post();
            $pid = get_the_ID();
            $comments_count = get_comments_number($pid);
            $likes = function_exists('nm_get_debate_likes') ? nm_get_debate_likes($pid) : 0;
            $age_hours = max(1, ( current_time('timestamp') - get_post_time('U', true, $pid) ) / 3600);
            // freshness factor: newer posts slightly boosted
            $fresh = max(0, 72 - $age_hours) / 72; // 0..1
            $score = ($likes * 2) + ($comments_count * 1) + $fresh;
            $items[] = array('post_id' => $pid, 'score' => $score, 'comments' => $comments_count, 'likes' => $likes);
        endwhile;
        if ($view === 'destacados') {
            usort($items, function($a, $b){
                if ($a['score'] == $b['score']) return 0;
                return ($a['score'] > $b['score']) ? -1 : 1;
            });
        }
        echo '<div class="forum-topic-list">';
        foreach ($items as $it) {
              $pid = $it['post_id'];
              $comments_count = $it['comments'];
              $likes = $it['likes'];
              $liked = function_exists('nm_user_liked_debate') ? nm_user_liked_debate($pid) : false;
              $nonce = wp_create_nonce('nm_like_' . $pid);
              $thumb_html = '';
              if (has_post_thumbnail($pid)) {
                  $thumb_html = get_the_post_thumbnail($pid, 'large', array('class' => 'topic-thumb-img', 'alt' => esc_attr(get_the_title($pid))));
              }
              echo '<article class="forum-topic-card">';
              if ($thumb_html) {
                  echo '  <a class="topic-thumb" href="' . esc_url(get_permalink($pid)) . '" aria-label="' . esc_attr(get_the_title($pid)) . '">' . $thumb_html . '</a>';
              } else {
                  echo '  <a class="topic-thumb placeholder" href="' . esc_url(get_permalink($pid)) . '" aria-label="' . esc_attr(get_the_title($pid)) . '">'
                     . '    <span class="topic-thumb-placeholder" aria-hidden="true"><i class="fas fa-image"></i></span>'
                     . '  </a>';
              }
              echo '  <h3 class="topic-title"><a href="' . esc_url(get_permalink($pid)) . '">' . esc_html(get_the_title($pid)) . '</a></h3>';
              echo '  <div class="topic-meta">'
                 . '    <span class="author"><i class="fas fa-user"></i> ' . esc_html(get_the_author_meta('display_name', get_post_field('post_author', $pid))) . '</span>'
                 . '    <span class="date"><i class="fas fa-clock"></i> ' . esc_html(get_the_date('', $pid)) . '</span>'
                 . '    <span class="comments"><i class="fas fa-comments"></i> ' . intval($comments_count) . '</span>'
                 . '    <button class="like-btn' . ($liked ? ' is-liked' : '') . '" data-post-id="' . intval($pid) . '" data-nonce="' . esc_attr($nonce) . '" aria-pressed="' . ($liked ? 'true' : 'false') . '"><i class="fas fa-heart"></i> <span class="like-count">' . intval($likes) . '</span></button>'
                 . '  </div>';
              echo '  <p class="topic-excerpt">' . esc_html(wp_trim_words(get_the_excerpt($pid), 26)) . '</p>';
              // Hashtag chips for each topic
              $terms = get_the_terms($pid, 'hashtag');
              if ($terms && !is_wp_error($terms)) {
                  $forum_url = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/');
                  echo '<div class="debate-tags">';
                  foreach ($terms as $t) {
                      $url = add_query_arg('hashtag', $t->slug, $forum_url);
                      echo '<a class="tag-chip" href="' . esc_url($url) . '">#' . esc_html($t->name) . '</a>';
                  }
                  echo '</div>';
              }
              echo '</article>';
          }
          echo '</div>';
          // Pagination
          $big = 999999999;
          $pagination = paginate_links(array(
              'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
              'format' => '?paged=%#%',
              'current' => max(1, $paged),
              'total' => $q->max_num_pages,
              'type' => 'list',
          ));
          if ($pagination) {
              echo '<nav class="forum-pagination">' . $pagination . '</nav>';
          }
          wp_reset_postdata();
      else:
          echo '<p>' . esc_html__('No hay temas aún. ¡Sé el primero en proponer uno!', 'lanota-theme-2026') . '</p>';
      endif;
      ?>
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

    <!-- Social Media Links -->
    <?php if (function_exists('lanota_2026_render_social_links')) { ?>
    <div class="widget">
      <div class="widget-header"><?php esc_html_e('Seguinos', 'lanota-theme-2026'); ?></div>
      <div class="widget-content">
        <?php lanota_2026_render_social_links('social-row'); ?>
      </div>
    </div>
    <?php } ?>

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

    <!-- Hashtag Cloud Widget -->
    <div class="widget">
      <div class="widget-header"><?php esc_html_e('Hashtags populares', 'lanota-theme-2026'); ?></div>
      <div class="widget-content">
        <?php
        $popular_tags = get_terms(array(
          'taxonomy' => 'hashtag',
          'orderby' => 'count',
          'order' => 'DESC',
          'number' => 10,
          'hide_empty' => true,
        ));
        $forum_url = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/');
        if (!is_wp_error($popular_tags) && $popular_tags) : ?>
          <div class="tag-cloud">
            <?php foreach ($popular_tags as $t) :
              $url = add_query_arg('hashtag', $t->slug, $forum_url);
            ?>
              <a class="tag-chip" href="<?php echo esc_url($url); ?>">#<?php echo esc_html($t->name); ?></a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p><?php esc_html_e('No hay hashtags aún.', 'lanota-theme-2026'); ?></p>
        <?php endif; ?>
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

<script>
(function(){
  const KEY = 'nm_forum_policies_ack_v1';
  const modal = document.getElementById('forumPolicyModal');
  if(!modal) return;
  let submitPending = false;
  const show = () => {
    modal.setAttribute('aria-hidden','false');
    document.documentElement.classList.add('forum-modal-open');
  };
  const hide = () => {
    modal.setAttribute('aria-hidden','true');
    document.documentElement.classList.remove('forum-modal-open');
  };
  if (!localStorage.getItem(KEY)) {
    // Only show on forum pages
    show();
  }
  modal.addEventListener('click', function(e){
    if (e.target && e.target.getAttribute('data-close') === 'modal') hide();
  });
  const closeBtn = modal.querySelector('.forum-modal__close');
  if (closeBtn) closeBtn.addEventListener('click', hide);
  const accept = document.getElementById('forumPolicyAccept');
  if (accept) accept.addEventListener('click', function(){
    localStorage.setItem(KEY, '1');
    hide();
    if (submitPending) {
      submitPending = false;
      const form = document.querySelector('.forum-form');
      if (form) form.submit();
    }
  });

  // Intercept new-topic form submit to enforce acknowledgement
  const form = document.querySelector('.forum-form');
  if (form) {
    form.addEventListener('submit', function(e){
      // Policies gate
      if (!localStorage.getItem(KEY)) {
        e.preventDefault();
        submitPending = true;
        show();
        return;
      }
      // Client-side image validation: max 2, accepted types, size <= 2MB
      const f = document.getElementById('topic_images');
      if (f && f.files && f.files.length) {
        if (f.files.length > 2) {
          e.preventDefault();
          alert('Podés adjuntar hasta 2 imágenes.');
          return;
        }
        for (let i=0;i<f.files.length;i++) {
          const t = f.files[i].type;
          if (!['image/webp','image/png','image/jpeg'].includes(t)) {
            e.preventDefault();
            alert('Formato no permitido. Usá WEBP, PNG o JPG.');
            return;
          }
          if (f.files[i].size > 2 * 1024 * 1024) {
            e.preventDefault();
            alert('Cada imagen debe pesar como máximo 2 MB.');
            return;
          }
        }
      }
    });
  }
})();
// Collapsible: new topic
(function(){
  const wrap = document.querySelector('.forum-new-topic.is-collapsible');
  if (!wrap) return;
  const btn = wrap.querySelector('.collapsible-toggle');
  const header = wrap.querySelector('.collapsible-header');
  const panel = document.getElementById('newTopicPanel');
  if (!btn || !panel) return;
  btn.addEventListener('click', function(){
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    const next = !expanded;
    btn.setAttribute('aria-expanded', next ? 'true' : 'false');
    btn.title = next ? '<?php echo esc_js(__('Ocultar formulario', 'lanota-theme-2026')); ?>' : '<?php echo esc_js(__('Mostrar formulario', 'lanota-theme-2026')); ?>';
    if (next) {
      panel.hidden = false;
      panel.classList.remove('is-collapsed');
      panel.classList.add('is-open');
    } else {
      panel.classList.remove('is-open');
      panel.classList.add('is-collapsed');
      panel.hidden = true;
    }
    // rotate icon
    const icon = btn.querySelector('i');
    if (icon) icon.style.transform = next ? 'rotate(180deg)' : 'rotate(0deg)';
  });
  if (header) {
    header.addEventListener('click', function(e){
      // Avoid double toggle if the click originated on the button itself
      if (e.target.closest && e.target.closest('.collapsible-toggle')) return;
      btn.click();
    });
  }
})();
// Likes toggle
(function(){
  function sendToggle(btn){
    const pid = btn.getAttribute('data-post-id');
    const nonce = btn.getAttribute('data-nonce');
    if (!pid || !nonce) return;
    btn.disabled = true;
    const form = new FormData();
    form.append('action','nm_toggle_debate_like');
    form.append('post_id', pid);
    form.append('_wpnonce', nonce);
    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', { method: 'POST', credentials: 'same-origin', body: form })
      .then(r => r.json())
      .then(data => {
        if (data && data.success && data.data) {
          const liked = !!data.data.liked;
          const count = parseInt(data.data.count || 0, 10);
          btn.classList.toggle('is-liked', liked);
          btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
          const span = btn.querySelector('.like-count');
          if (span) span.textContent = String(count);
        } else if (data && data.data && data.data.message) {
          alert(data.data.message);
        }
      })
      .catch(()=>{})
      .finally(()=>{ btn.disabled = false; });
  }
  document.addEventListener('click', function(e){
    const btn = e.target.closest && e.target.closest('.like-btn');
    if (!btn) return;
    e.preventDefault();
    sendToggle(btn);
  });
})();
</script>
