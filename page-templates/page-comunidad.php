<?php
/*
Template Name: Comunidad
*/
if (!defined('ABSPATH')) { exit; }
get_header();

// Params: view = destacados|recientes
$view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'destacados';
if (!in_array($view, array('destacados','recientes'), true)) { $view = 'destacados'; }

$paged = max(1, get_query_var('paged') ? get_query_var('paged') : get_query_var('page'));
$per_page = 10;

$q_args = array(
  'post_type' => 'debate_topic',
  'post_status' => 'publish',
  'posts_per_page' => $per_page,
  'paged' => $paged,
  'ignore_sticky_posts' => true,
);
if ($view === 'destacados') {
  // Aproximación de relevancia: más comentados primero, luego recientes
  $q_args['orderby'] = 'comment_count';
  $q_args['order'] = 'DESC';
} else {
  $q_args['orderby'] = 'date';
  $q_args['order'] = 'DESC';
}
$q = new WP_Query($q_args);
?>
<main id="primary" class="site-main">
  <section class="community-wrap container">
    <header class="community-header">
      <h1 class="community-title">Comunidad</h1>
      <nav class="community-tabs" aria-label="Cambiar vista del muro">
        <?php
        $base = get_permalink();
        $url_top = add_query_arg('view','destacados',$base);
        $url_new = add_query_arg('view','recientes',$base);
        ?>
        <a class="tab<?php echo $view==='destacados'?' is-active':''; ?>" href="<?php echo esc_url($url_top); ?>"><i class="fas fa-fire"></i> Destacados</a>
        <a class="tab<?php echo $view==='recientes'?' is-active':''; ?>" href="<?php echo esc_url($url_new); ?>"><i class="fas fa-clock"></i> Recientes</a>
      </nav>
    </header>

    <?php if (is_user_logged_in()) : ?>
      <?php
        // Notificaciones: últimos comentarios de otros en mis temas
        $uid = get_current_user_id();
        $my_topics = get_posts(array(
          'author' => $uid,
          'post_type' => 'debate_topic',
          'post_status' => 'publish',
          'fields' => 'ids',
          'posts_per_page' => 200,
        ));
        $notifications = array();
        if (!empty($my_topics)) {
          $comments = get_comments(array(
            'post__in' => $my_topics,
            'status' => 'approve',
            'number' => 8,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
          ));
          if ($comments) {
            foreach ($comments as $c) {
              if (intval($c->user_id) === intval($uid)) { continue; }
              $notifications[] = $c;
            }
          }
        }
      ?>
      <section class="community-notifs">
        <h2 class="section-title"><i class="fas fa-bell"></i> Notificaciones</h2>
        <?php if (empty($notifications)) : ?>
          <p class="muted">Sin novedades por ahora.</p>
        <?php else: ?>
          <ul class="notif-list">
            <?php foreach ($notifications as $note):
              $plink = get_comment_link($note);
              $post  = get_post($note->comment_post_ID);
              $when  = sprintf(_x('%s atrás', 'human time diff', 'lanota-theme-2026'), human_time_diff( strtotime($note->comment_date_gmt), current_time('timestamp', true) ));
            ?>
              <li class="notif-item">
                <a class="notification-link" href="<?php echo esc_url($plink); ?>">
                  <strong><?php echo esc_html($note->comment_author ?: __('Usuario', 'lanota-theme-2026')); ?></strong>
                  <span>comentó en tu debate</span>
                  <em>“<?php echo esc_html( wp_trim_words( $post ? $post->post_title : '', 12 ) ); ?>”</em>
                  <span class="when">· <?php echo esc_html($when); ?></span>
                </a>
                <div class="notification-excerpt">“<?php echo esc_html( wp_trim_words( $note->comment_content, 18 ) ); ?>”</div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="community-feed">
      <?php if ($q->have_posts()) : ?>
        <div class="feed-grid">
          <?php while ($q->have_posts()) : $q->the_post(); ?>
            <article class="feed-card">
              <header class="feed-card__head">
                <h3 class="feed-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="feed-card__meta">
                  <span><i class="fas fa-user"></i> <?php echo esc_html(get_the_author()); ?></span>
                  <span><i class="fas fa-clock"></i> <?php echo esc_html(get_the_date()); ?></span>
                  <span><i class="fas fa-comments"></i> <?php echo intval(get_comments_number()); ?></span>
                </div>
              </header>
              <div class="feed-card__excerpt">
                <?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?>
              </div>
              <?php
                $terms = get_the_terms(get_the_ID(), 'hashtag');
                if ($terms && !is_wp_error($terms)) :
                  $forum_url = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/');
              ?>
              <div class="debate-tags">
                <?php foreach ($terms as $t) :
                  $url = add_query_arg('hashtag', $t->slug, $forum_url);
                ?>
                  <a class="tag-chip" href="<?php echo esc_url($url); ?>">#<?php echo esc_html($t->name); ?></a>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </article>
          <?php endwhile; ?>
        </div>
        <?php
          $big = 999999999;
          $pagination = paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $q->max_num_pages,
            'type' => 'list',
          ));
          if ($pagination) echo '<nav class="community-pagination">' . $pagination . '</nav>';
          wp_reset_postdata();
        ?>
      <?php else: ?>
        <p>No hay contenidos aún.</p>
      <?php endif; ?>
    </section>
  </section>
</main>
<?php get_footer(); ?>
