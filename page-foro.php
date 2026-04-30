<?php
/*
Template Name: En Debate
*/
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
          <?php if (!is_user_logged_in()) : ?>
              <a class="login-btn" href="<?php echo wp_login_url(get_permalink()); ?>">
                  <i class="fas fa-sign-in-alt"></i>
                  Iniciar Sesión
              </a>
              <a class="register-btn" href="<?php echo wp_registration_url(); ?>">
                  <i class="fas fa-user-plus"></i>
                  Registrarse
              </a>
          <?php else : ?>
              <a class="btn btn-primary" href="<?php echo esc_url(home_url('/mi-cuenta/')); ?>" style="display: block; text-align: center; margin-bottom: 10px;">
                  <i class="fas fa-user-circle"></i>
                  Mi Cuenta
              </a>
          <?php endif; ?>
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
      <h1 style="font-size:20px; font-weight:900;">En Debate</h1>
    </div>
    <div class="content-area">
      <article class="post">
        <header class="post-header">
          <p class="post-excerpt">Publicá debates (máx 400 caracteres), respondé y usá #hashtags. Los más activos suben primero.</p>
        </header>

        <div class="post-content">
          <?php if ( is_user_logged_in() ) : ?>
            <form id="debate-form" class="debate-form">
              <div class="debate-editor-container">
                <textarea id="debate-content" name="content" rows="3" maxlength="400" placeholder="¿Qué querés debatir? Usá #hashtags y emojis 😊"></textarea>
                
                <!-- Editor Toolbar -->
                <div class="editor-toolbar">
                  <button type="button" class="toolbar-btn emoji-btn" id="emoji-picker-btn" title="Agregar emoji">
                    <i class="fas fa-smile"></i>
                  </button>
                  <button type="button" class="toolbar-btn sticker-btn" id="sticker-picker-btn" title="Agregar sticker">
                    <i class="fas fa-images"></i>
                  </button>
                  <button type="button" class="toolbar-btn hashtag-btn" id="hashtag-helper-btn" title="Hashtags populares">
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

                <!-- Sticker Picker -->
                <div class="sticker-picker" id="sticker-picker" style="display: none;">
                  <div class="sticker-categories">
                    <button type="button" class="sticker-cat-btn active" data-category="reactions">Reacciones</button>
                    <button type="button" class="sticker-cat-btn" data-category="argentina">Argentina</button>
                    <button type="button" class="sticker-cat-btn" data-category="debate">Debate</button>
                  </div>
                  <div class="sticker-grid" id="sticker-grid">
                    <!-- Stickers will be populated by JavaScript -->
                  </div>
                </div>

                <!-- Hashtag Helper -->
                <div class="hashtag-helper" id="hashtag-helper" style="display: none;">
                  <div class="hashtag-suggestions" id="hashtag-suggestions">
                    <!-- Popular hashtags will be populated -->
                  </div>
                </div>
              </div>

              <div class="debate-form-row">
                <input type="url" id="debate-link" name="link_url" placeholder="Enlace a una nota interna (opcional)">
                <span class="char-counter" id="debate-counter">0 / 400</span>
              </div>

              <div class="debate-loading" id="debate-loading" style="display:none;">
                Cargando debates...
              </div>
              <button type="submit" class="btn">Publicar debate</button>
              <div class="form-feedback" id="debate-feedback" aria-live="polite"></div>
              
              <!-- Force load debate editor script -->
              <script>
                document.addEventListener('DOMContentLoaded', function() {
                  // Initialize editor immediately if class exists
                  if (typeof DebateEditor !== 'undefined') {
                    new DebateEditor();
                  } else {
                    // Load script if not available
                    var script = document.createElement('script');
                    script.src = '<?php echo get_template_directory_uri(); ?>/js/debate-editor.js';
                    script.onload = function() {
                      if (typeof DebateEditor !== 'undefined') {
                        new DebateEditor();
                      }
                    };
                    document.head.appendChild(script);
                  }
                  
                  // Debug: Force create emoji picker if missing
                  setTimeout(function() {
                    console.log('Checking for emoji elements...');
                    
                    var toolbar = document.querySelector('.editor-toolbar');
                    if (!toolbar) {
                      console.log('Toolbar not found');
                      return;
                    }
                    
                    // Create emoji picker if missing
                    if (!document.getElementById('emoji-picker')) {
                      console.log('Creating emoji picker...');
                      var emojiPicker = document.createElement('div');
                      emojiPicker.id = 'emoji-picker';
                      emojiPicker.className = 'picker-dropdown emoji-picker';
                      emojiPicker.innerHTML = '<div class="picker-tabs"><button class="picker-tab active" data-category="smileys">😊</button><button class="picker-tab" data-category="people">👋</button><button class="picker-tab" data-category="nature">🌟</button></div><div class="picker-content"><div class="emoji-grid">😊😂🤣😍🥰😘😉😋😎🤗🤔😐😑🙄😏😣😥😮🤐😯😪😫🥱😴😌😛😜🤪😝🤑</div></div>';
                      toolbar.appendChild(emojiPicker);
                    }
                    
                    // Show all pickers
                    var emojiPicker = document.getElementById('emoji-picker');
                    var stickerPicker = document.getElementById('sticker-picker');
                    var hashtagHelper = document.getElementById('hashtag-helper');
                    
                    if (emojiPicker) {
                      emojiPicker.style.display = 'block';
                      emojiPicker.style.opacity = '1';
                      emojiPicker.style.visibility = 'visible';
                      console.log('Emoji picker shown');
                    }
                    if (stickerPicker) {
                      stickerPicker.style.display = 'block';
                      console.log('Sticker picker shown');
                    }
                    if (hashtagHelper) {
                      hashtagHelper.style.display = 'block';
                      console.log('Hashtag helper shown');
                    }
                  }, 2000);
                });
              </script>
            </form>
          <?php else: ?>
            <p>Necesitás iniciar sesión para participar.</p>
          <?php endif; ?>

          <?php
            $hashtag = isset($_GET['hashtag']) ? sanitize_title( wp_unslash($_GET['hashtag']) ) : '';
            $paged = max(1, get_query_var('paged') ? get_query_var('paged') : (isset($_GET['paged']) ? intval($_GET['paged']) : 1));
            $args = array(
              'post_type' => 'debate',
              'post_status' => 'publish',
              'posts_per_page' => 10,
              'paged' => $paged,
              'orderby' => 'comment_count',
              'order' => 'DESC',
            );
            if ($hashtag) {
              $args['tax_query'] = array(array(
                'taxonomy' => 'hashtag',
                'field' => 'slug',
                'terms' => $hashtag
              ));
            }
            $debates = new WP_Query($args);
          ?>

          <div class="debate-filters">
            <?php if ($hashtag): ?>
              <div class="hashtag-chip">#<?php echo esc_html($hashtag); ?> <a href="<?php echo esc_url( get_permalink() ); ?>" class="clear-filter">×</a></div>
            <?php endif; ?>
          </div>

          <div id="debate-feed" class="debate-feed" data-page="<?php echo esc_attr($paged); ?>" data-max="<?php echo esc_attr($debates->max_num_pages); ?>" data-hashtag="<?php echo esc_attr($hashtag); ?>">
            <?php if ($debates->have_posts()): while ($debates->have_posts()): $debates->the_post(); ?>
              <div class="debate-card" data-id="<?php the_ID(); ?>">
                <div class="more-actions">
                  <button class="more-actions__btn" aria-expanded="false" aria-label="Más acciones">
                    <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                    <span class="sr-only">Más acciones</span>
                  </button>
                  <div class="more-actions__menu" role="menu" aria-hidden="true">
                    <button class="more-actions__item js-report-post"
                         data-post-id="<?php the_ID(); ?>"
                         role="menuitem">
                      <i class="fas fa-flag" aria-hidden="true"></i>
                      Denunciar post
                    </button>
                    <button class="more-actions__item js-share-link"
                         data-post-id="<?php the_ID(); ?>"
                         data-permalink="<?php echo esc_attr( get_permalink() ); ?>"
                         role="menuitem">
                      <i class="fas fa-share" aria-hidden="true"></i>
                      Compartir enlace
                    </button>
                    <button class="more-actions__item js-start-debate"
                         data-post-id="<?php the_ID(); ?>"
                         data-title="<?php echo esc_attr( get_the_title() ); ?>"
                         role="menuitem">
                      <i class="fas fa-comments" aria-hidden="true"></i>
                      Iniciar debate a partir de este
                    </button>
                  </div>
                </div>
                <div class="debate-meta">
                  <strong><?php the_author(); ?></strong>
                  <span>·</span>
                  <time datetime="<?php echo esc_attr( get_the_date('c') ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                </div>
                <div class="debate-content">
                  <p><?php echo nl2br( esc_html( get_the_content() ) ); ?></p>
                  <?php $link = get_post_meta(get_the_ID(), 'debate_link_url', true); if ($link): ?>
                    <p class="debate-link"><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($link); ?></a></p>
                  <?php endif; ?>
                  <div class="debate-tags">
                    <?php
                      $terms = get_the_terms(get_the_ID(), 'hashtag');
                      if ($terms && !is_wp_error($terms)) {
                        foreach ($terms as $t) {
                          $url = add_query_arg('hashtag', $t->slug, get_permalink());
                          echo '<a class="tag-chip" href="' . esc_url($url) . '">#' . esc_html($t->name) . '</a>';
                        }
                      }
                    ?>
                  </div>
                </div>
                <div class="debate-actions">
                  <span class="comments-count"><i class="fa fa-comment"></i> <?php echo get_comments_number(); ?></span>
                </div>
                <div class="debate-replies">
                  <?php
                    $comments = get_comments(array('post_id' => get_the_ID(), 'status' => 'approve', 'number' => 3, 'order' => 'DESC'));
                    if ($comments) {
                      echo '<ul class="reply-list">';
                      foreach ($comments as $c) {
                        echo '<li><strong>' . esc_html($c->comment_author) . ':</strong> ' . esc_html($c->comment_content) . '</li>';
                      }
                      echo '</ul>';
                    }
                  ?>
                </div>
                <?php if ( is_user_logged_in() ) : ?>
                <form class="reply-form">
                  <input type="text" name="content" maxlength="240" placeholder="Responder (máx 240)">
                  <button type="submit" class="btn btn-reply">Responder</button>
                </form>
                <?php endif; ?>
              </div>
            <?php endwhile; else: ?>
              <p>No hay debates aún.</p>
            <?php endif; wp_reset_postdata(); ?>
          </div>

          <?php if ( $debates->max_num_pages > 1 ) : ?>
            <nav class="pagination">
              <?php
                echo paginate_links(array(
                  'total' => $debates->max_num_pages,
                  'current' => $paged,
                ));
              ?>
            </nav>
          <?php endif; ?>
        </div>
      </article>
    </div>
  </main>

  <!-- Right Sidebar -->
  <aside class="right-sidebar">
      <div class="sidebar-header">
          <div class="search-container">
              <form role="search" method="get" action="<?php echo home_url('/'); ?>">
                  <input type="search" 
                         class="search-input" 
                         placeholder="Buscar debates..." 
                         value="<?php echo get_search_query(); ?>" 
                         name="s">
                  <button type="submit" class="search-btn">
                      <i class="fas fa-search"></i>
                      <span class="sr-only">Buscar</span>
                  </button>
              </form>
          </div>
          <button class="theme-toggle" id="desktop-theme-toggle" aria-label="Cambiar tema">
              <i class="fas fa-moon" id="desktop-theme-icon"></i>
          </button>
      </div>

      <!-- Forum Sidebar Top - Para publicidades destacadas -->
      <?php if (is_active_sidebar('forum-sidebar-top')) : ?>
          <?php dynamic_sidebar('forum-sidebar-top'); ?>
      <?php endif; ?>

      <!-- Forum Sidebar Middle - Para contenido relacionado y publicidades -->
      <?php if (is_active_sidebar('forum-sidebar-middle')) : ?>
          <?php dynamic_sidebar('forum-sidebar-middle'); ?>
      <?php else : ?>
          <!-- Fallback: Trending debates widget -->
          <div class="widget">
              <div class="widget-header">Debates Populares</div>
              <div class="widget-content">
                  <?php
                  $trending_debates = new WP_Query(array(
                      'post_type' => 'debate',
                      'posts_per_page' => 5,
                      'orderby' => 'comment_count',
                      'order' => 'DESC'
                  ));
                  if ($trending_debates->have_posts()) :
                      while ($trending_debates->have_posts()) : $trending_debates->the_post();
                  ?>
                      <div class="trending-item">
                          <div class="trending-title">
                              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                          </div>
                          <div class="trending-posts"><?php echo get_comments_number(); ?> respuestas</div>
                      </div>
                  <?php
                      endwhile;
                      wp_reset_postdata();
                  endif;
                  ?>
              </div>
          </div>
      <?php endif; ?>

      <!-- Forum Sidebar Bottom - Para publicidades adicionales -->
      <?php if (is_active_sidebar('forum-sidebar-bottom')) : ?>
          <?php dynamic_sidebar('forum-sidebar-bottom'); ?>
      <?php else : ?>
          <!-- Fallback: Popular hashtags widget -->
          <div class="widget">
              <div class="widget-header">Hashtags Populares</div>
              <div class="widget-content">
                  <?php
                  $popular_hashtags = get_terms(array(
                      'taxonomy' => 'hashtag',
                      'orderby' => 'count',
                      'order' => 'DESC',
                      'number' => 10,
                      'hide_empty' => true
                  ));
                  if ($popular_hashtags && !is_wp_error($popular_hashtags)) :
                      foreach ($popular_hashtags as $hashtag) :
                          $hashtag_url = add_query_arg('hashtag', $hashtag->slug, get_permalink());
                  ?>
                      <div class="trending-item">
                          <div class="trending-title">
                              <a href="<?php echo esc_url($hashtag_url); ?>">#<?php echo esc_html($hashtag->name); ?></a>
                          </div>
                          <div class="trending-posts"><?php echo $hashtag->count; ?> debates</div>
                      </div>
                  <?php
                      endforeach;
                  endif;
                  ?>
              </div>
          </div>
      <?php endif; ?>
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
