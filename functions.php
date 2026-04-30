<?php
// Ensure "Acceso" page exists and uses the Subscriber Login template
function nm_ensure_subscriber_login_page() {
    $page_title = 'Acceso';
    $page_slug  = 'acceso';
    $template   = 'page-templates/subscriber-login.php';

    $page = get_page_by_path( $page_slug );
    if ( ! $page ) {
        $page_id = wp_insert_post(array(
            'post_title'   => $page_title,
            'post_name'    => $page_slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));
        if ( ! is_wp_error( $page_id ) ) {
            update_post_meta( $page_id, '_wp_page_template', $template );
        }
    } else {
        // Ensure correct template if page already exists
        if ( get_page_template_slug( $page->ID ) !== $template ) {
            update_post_meta( $page->ID, '_wp_page_template', $template );
        }
    }
}
add_action('after_switch_theme', 'nm_ensure_subscriber_login_page');
// Also ensure on init (for existing installs not switching theme now)
add_action('init', function(){ if (!is_admin()) nm_ensure_subscriber_login_page(); });

// Helper: get masked login URL
function nm_get_login_url($redirect = '') {
    $url = home_url('/acceso/');
    if ( ! empty($redirect) ) {
        $url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
    }
    return $url;
}

// Force WordPress helpers to use masked login URL
add_filter('login_url', function($login_url, $redirect, $force_reauth){
    return nm_get_login_url( $redirect ?: '' );
}, 10, 3);

// Redirect direct access to wp-login.php to the masked login page (GET only)
function nm_mask_wp_login() {
    if ( ! defined('ABSPATH') ) { return; }
    $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
    if ( $script !== 'wp-login.php' ) { return; }

    // Allow core flows: POSTing credentials, lost password, reset, logout
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';
    $allowed_actions = array('logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass');
    if ( 'POST' === strtoupper($method) ) { return; }
    if ( in_array( $action, $allowed_actions, true ) ) { return; }

    // Build redirect
    $redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : home_url('/');
    $target = nm_get_login_url( $redirect_to );
    wp_safe_redirect( $target, 301 );
    exit;
}
add_action('login_init', 'nm_mask_wp_login');
// Redirect unauthenticated users hitting /wp-admin/ to masked login
function nm_admin_guard_redirect() {
    // Allow AJAX and cron
    if ( defined('DOING_AJAX') && DOING_AJAX ) { return; }
    if ( defined('DOING_CRON') && DOING_CRON ) { return; }

    if ( is_admin() ) {
        // Allow AJAX and admin-post handlers to run without redirection
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
        if ($script === 'admin-ajax.php' || $script === 'admin-post.php') {
            return;
        }
        if ( ! is_user_logged_in() ) {
            // Unauthenticated: send to masked login with redirect back
            $scheme   = is_ssl() ? 'https://' : 'http://';
            $host     = $_SERVER['HTTP_HOST'] ?? parse_url( home_url(), PHP_URL_HOST );
            $request  = $_SERVER['REQUEST_URI'] ?? '/wp-admin/';
            $dest     = $scheme . $host . $request;
            wp_safe_redirect( nm_get_login_url( $dest ) );
            exit;
        }
        // Logged-in: if user is not staff, redirect to themed My Account
        if ( ! current_user_can('edit_posts') && ! current_user_can('manage_options') ) {
            $account_url = function_exists('lanota_2026_get_account_url') ? lanota_2026_get_account_url() : home_url('/mi-cuenta/');
            wp_safe_redirect( $account_url );
            exit;
        }
    }
}
add_action('admin_init', 'nm_admin_guard_redirect');

// ---- Auth Security: rate limiting + logging ----
function nm_get_client_ip() {
    $keys = array('HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR');
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(current(explode(',', $_SERVER[$k])));
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
        }
    }
    return '0.0.0.0';
}

function nm_rate_defaults() {
    $d = array(
        'window_sec' => 900, // 15 min ventana de conteo
        'max_attempts_ip' => 8,
        'max_attempts_user' => 5,
        'lockout_sec' => 900, // 15 min bloqueo
        'log_keep' => 100,
    );
    return apply_filters('nm_rate_limit_defaults', $d);
}

function nm_rate_limit_should_block($user_login = '') {
    $cfg = nm_rate_defaults();
    $ip  = nm_get_client_ip();
    $now = time();
    $locks = array();
    $ip_lock = get_transient('nm_lock_ip_' . md5($ip));
    if ($ip_lock && is_numeric($ip_lock)) { $locks[] = intval($ip_lock) - $now; }
    if (!empty($user_login)) {
        $user_key = 'nm_lock_user_' . md5(strtolower($user_login));
        $u_lock = get_transient($user_key);
        if ($u_lock && is_numeric($u_lock)) { $locks[] = intval($u_lock) - $now; }
    }
    $locks = array_filter($locks, function($s){ return $s > 0; });
    if (!empty($locks)) { return max($locks); }
    return 0;
}

function nm_rate_limit_register_failure($user_login = '') {
    $cfg = nm_rate_defaults();
    $ip  = nm_get_client_ip();
    // IP counter
    $ip_key = 'nm_cnt_ip_' . md5($ip);
    $ip_cnt = intval(get_transient($ip_key));
    $ip_cnt++;
    set_transient($ip_key, $ip_cnt, $cfg['window_sec']);
    if ($ip_cnt >= $cfg['max_attempts_ip']) {
        set_transient('nm_lock_ip_' . md5($ip), time() + $cfg['lockout_sec'], $cfg['lockout_sec']);
    }
    // User counter
    if (!empty($user_login)) {
        $user_key = 'nm_cnt_user_' . md5(strtolower($user_login));
        $u_cnt = intval(get_transient($user_key));
        $u_cnt++;
        set_transient($user_key, $u_cnt, $cfg['window_sec']);
        if ($u_cnt >= $cfg['max_attempts_user']) {
            set_transient('nm_lock_user_' . md5(strtolower($user_login)), time() + $cfg['lockout_sec'], $cfg['lockout_sec']);
        }
    }
}

function nm_rate_limit_register_success($user_login = '') {
    $ip = nm_get_client_ip();
    delete_transient('nm_cnt_ip_' . md5($ip));
    delete_transient('nm_lock_ip_' . md5($ip));
    if (!empty($user_login)) {
        $h = md5(strtolower($user_login));
        delete_transient('nm_cnt_user_' . $h);
        delete_transient('nm_lock_user_' . $h);
    }
}

function nm_log_auth_attempt($status, $user_login = '', $meta = array()) {
    $cfg = nm_rate_defaults();
    $entry = array(
        't' => current_time('timestamp'),
        'ip' => nm_get_client_ip(),
        'user' => sanitize_text_field($user_login),
        'status' => sanitize_key($status),
        'meta' => $meta,
    );
    $log = get_option('nm_auth_log', array());
    if (!is_array($log)) { $log = array(); }
    array_unshift($log, $entry);
    if (count($log) > $cfg['log_keep']) { $log = array_slice($log, 0, $cfg['log_keep']); }
    update_option('nm_auth_log', $log, false);
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[nm_auth] ' . strtoupper($status) . ' user=' . $entry['user'] . ' ip=' . $entry['ip']);
    }
}

/**
 * Lanota Theme 2026 Theme Functions
 */

// Theme setup
function lanota_2026_theme_setup() {
    // i18n
    load_theme_textdomain('lanota-theme-2026', get_template_directory() . '/languages');
    // Add theme support for various features
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('title-tag');
    add_theme_support('custom-background');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary' => 'Primary Menu',
        'mobile' => 'Mobile Menu',
    ));

    // Add support for custom header
    add_theme_support('custom-header', array(
        'default-image' => '',
        'width' => 1200,
        'height' => 300,
        'flex-height' => true,
        'flex-width' => true,
    ));
}

// ---- Trending Posts Widget ----
class News_Media_Trending_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'lanota_2026_trending_widget',
            __('Tendencias (Lanota Theme 2026)', 'lanota-theme-2026'),
            array('description' => __('Lista de posts en tendencia por vistas.', 'lanota-theme-2026'))
        );
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : __('Tendencias', 'lanota-theme-2026');
        $count = isset($instance['count']) ? intval($instance['count']) : 5;
        $sort  = isset($instance['sort']) ? esc_attr($instance['sort']) : 'views'; // views|latest|likes
        $cat_ids = isset($instance['cat_ids']) ? esc_attr($instance['cat_ids']) : '';
        $tag_slugs = isset($instance['tag_slugs']) ? esc_attr($instance['tag_slugs']) : '';
        $selected_cat_ids = array_filter(array_map('intval', explode(',', $cat_ids)));
        $selected_tag_slugs = array_filter(array_map('trim', explode(',', $tag_slugs)));
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Título:', 'lanota-theme-2026'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('count')); ?>"><?php _e('Cantidad:', 'lanota-theme-2026'); ?></label>
            <input class="small-text" id="<?php echo esc_attr($this->get_field_id('count')); ?>" name="<?php echo esc_attr($this->get_field_name('count')); ?>" type="number" min="1" max="12" value="<?php echo $count; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('sort')); ?>"><?php _e('Criterio:', 'lanota-theme-2026'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('sort')); ?>" name="<?php echo esc_attr($this->get_field_name('sort')); ?>">
                <option value="views" <?php selected($sort, 'views'); ?>><?php _e('Por vistas', 'lanota-theme-2026'); ?></option>
                <option value="latest" <?php selected($sort, 'latest'); ?>><?php _e('Últimas noticias', 'lanota-theme-2026'); ?></option>
                <option value="likes" <?php selected($sort, 'likes'); ?>><?php _e('Por me gusta', 'lanota-theme-2026'); ?></option>
            </select>
        </p>
        <p><strong><?php _e('Categorías:', 'lanota-theme-2026'); ?></strong></p>
        <div style="max-height:180px;overflow:auto;border:1px solid #ddd;padding:6px;border-radius:4px;">
        <?php foreach (get_categories(array('hide_empty' => false)) as $cat): ?>
            <label style="display:block;margin-bottom:4px;">
                <input type="checkbox"
                    name="<?php echo esc_attr($this->get_field_name('cat_ids')); ?>[]"
                    value="<?php echo intval($cat->term_id); ?>"
                    <?php checked(in_array($cat->term_id, $selected_cat_ids, true)); ?> />
                <?php echo esc_html($cat->name . ' (ID ' . $cat->term_id . ')'); ?>
            </label>
        <?php endforeach; ?>
        </div>
        <p><strong><?php _e('Etiquetas:', 'lanota-theme-2026'); ?></strong></p>
        <div style="max-height:180px;overflow:auto;border:1px solid #ddd;padding:6px;border-radius:4px;">
        <?php foreach (get_tags(array('hide_empty' => false, 'number' => 100)) as $tag): ?>
            <label style="display:block;margin-bottom:4px;">
                <input type="checkbox"
                    name="<?php echo esc_attr($this->get_field_name('tag_slugs')); ?>[]"
                    value="<?php echo esc_attr($tag->slug); ?>"
                    <?php checked(in_array($tag->slug, $selected_tag_slugs, true)); ?> />
                <?php echo esc_html('#' . $tag->name); ?>
            </label>
        <?php endforeach; ?>
        </div>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? __('Tendencias', 'lanota-theme-2026'));
        $instance['count'] = max(1, min(12, intval($new_instance['count'] ?? 5)));
        $instance['sort']  = in_array(($new_instance['sort'] ?? 'views'), array('views','latest','likes'), true) ? $new_instance['sort'] : 'views';
        // Normalize checkbox arrays into CSV strings
        $cat_ids_in = $new_instance['cat_ids'] ?? array();
        if (is_array($cat_ids_in)) {
            $cat_ids_in = array_filter(array_map('intval', $cat_ids_in));
            $instance['cat_ids'] = implode(',', $cat_ids_in);
        } else {
            $instance['cat_ids'] = sanitize_text_field($cat_ids_in);
        }
        $tag_slugs_in = $new_instance['tag_slugs'] ?? array();
        if (is_array($tag_slugs_in)) {
            $tag_slugs_in = array_filter(array_map('sanitize_title', $tag_slugs_in));
            $instance['tag_slugs'] = implode(',', $tag_slugs_in);
        } else {
            $instance['tag_slugs'] = sanitize_text_field($tag_slugs_in);
        }
        return $instance;
    }

    public function widget($args, $instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Tendencias', 'lanota-theme-2026');
        $count = isset($instance['count']) ? intval($instance['count']) : 5;
        $sort  = isset($instance['sort']) ? $instance['sort'] : 'views';
        $cat_ids = isset($instance['cat_ids']) ? trim($instance['cat_ids']) : '';
        $tag_slugs = isset($instance['tag_slugs']) ? trim($instance['tag_slugs']) : '';

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
            echo '<div class="widget-content">';
        }

        $query_args = array(
            'posts_per_page' => $count,
        );

        // Sorting
        if ($sort === 'latest') {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        } elseif ($sort === 'likes') {
            $query_args['meta_key'] = 'post_likes';
            $query_args['orderby'] = 'meta_value_num';
            $query_args['order'] = 'DESC';
        } else { // views
            $query_args['meta_key'] = 'post_views';
            $query_args['orderby'] = 'meta_value_num';
            $query_args['order'] = 'DESC';
        }

        // Tax filters
        $tax_query = array('relation' => 'AND');
        if (!empty($cat_ids)) {
            $ids = array_filter(array_map('intval', explode(',', $cat_ids)));
            if (!empty($ids)) {
                $tax_query[] = array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $ids,
                    'operator' => 'IN'
                );
            }
        }
        if (!empty($tag_slugs)) {
            $slugs = array_filter(array_map('sanitize_title', array_map('trim', explode(',', $tag_slugs))));
            if (!empty($slugs)) {
                $tax_query[] = array(
                    'taxonomy' => 'post_tag',
                    'field'    => 'slug',
                    'terms'    => $slugs,
                    'operator' => 'IN'
                );
            }
        }
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }

        $trending_posts = new WP_Query($query_args);

        if ($trending_posts->have_posts()) {
            while ($trending_posts->have_posts()) { $trending_posts->the_post();
                echo '<div class="trending-item">';
                echo '<div class="trending-category">' . get_the_category_list(', ') . '</div>';
                echo '<div class="trending-title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></div>';
                // Optional: show a small metric depending on sort
                if ($sort === 'likes') {
                    $likes = intval(get_post_meta(get_the_ID(), 'post_likes', true));
                    echo '<div class="trending-posts">' . $likes . ' me gusta</div>';
                } elseif ($sort === 'views') {
                    $views = intval(get_post_meta(get_the_ID(), 'post_views', true));
                    echo '<div class="trending-posts">' . $views . ' vistas</div>';
                } else {
                    echo '<div class="trending-posts">' . get_the_date() . '</div>';
                }
                echo '</div>';
            }
            wp_reset_postdata();
            // More link to full trending feed (preserve config)
            $more_args = array('trending' => '1', 'sort' => $sort);
            if (!empty($cat_ids)) { $more_args['cat_ids'] = $cat_ids; }
            if (!empty($tag_slugs)) { $more_args['tag_slugs'] = $tag_slugs; }
            $more_url = esc_url( add_query_arg($more_args, home_url('/')) );
            echo '<div class="trending-more" style="margin-top:10px;">'
               . '<a class="trending-more-link" href="' . $more_url . '">'
               . '<i class="fas fa-fire" aria-hidden="true"></i> '
               . __('Ver más tendencias', 'lanota-theme-2026')
               . '</a>'
               . '</div>';
        } else {
            echo '<div class="trending-item">' . __('No hay publicaciones.', 'lanota-theme-2026') . '</div>';
        }

        if (!empty($title)) {
            echo '</div>';
        }
        echo $args['after_widget'];
    }
}
add_action('after_setup_theme', 'lanota_2026_theme_setup');

// ---- Roles & Demo Users: Paid subscriber (forum author) vs commenters ----
function nm_register_paid_subscriber_role() {
    // Create or update role with custom capability
    $role = get_role('nm_paid_subscriber');
    if (!$role) {
        add_role('nm_paid_subscriber', __('Suscriptor de Pago', 'lanota-theme-2026'), array('read' => true));
        $role = get_role('nm_paid_subscriber');
    }
    if ($role && !$role->has_cap('nm_forum_post')) {
        $role->add_cap('nm_forum_post');
    }
}
add_action('init', 'nm_register_paid_subscriber_role');

// Forum subscription levels: roles and capabilities
function nm_register_forum_roles() {
    // Map of role => [label, caps]
    $defs = array(
        'nm_participant' => array(
            __('Suscriptor Participante', 'lanota-theme-2026'),
            array('read' => true, 'nm_forum_comment' => true)
        ),
        'nm_collaborator' => array(
            __('Suscriptor Colaborador', 'lanota-theme-2026'),
            array('read' => true, 'nm_forum_comment' => true, 'nm_forum_post' => true)
        ),
        'nm_premium' => array(
            __('Suscriptor Premium', 'lanota-theme-2026'),
            array('read' => true, 'nm_forum_comment' => true, 'nm_forum_post' => true)
        ),
        'nm_sponsor' => array(
            __('Sponsor Comunitario', 'lanota-theme-2026'),
            array('read' => true)
        ),
    );
    foreach ($defs as $role_key => $meta) {
        list($label, $caps) = $meta;
        $role = get_role($role_key);
        if (!$role) {
            add_role($role_key, $label, array('read' => !empty($caps['read'])));
            $role = get_role($role_key);
        }
        if ($role) {
            foreach ($caps as $cap => $grant) {
                if ($grant && !$role->has_cap($cap)) { $role->add_cap($cap); }
            }
        }
    }
    // Back-compat: ensure legacy paid role can post and comment
    $legacy = get_role('nm_paid_subscriber');
    if ($legacy) {
        if (!$legacy->has_cap('nm_forum_post')) { $legacy->add_cap('nm_forum_post'); }
        if (!$legacy->has_cap('nm_forum_comment')) { $legacy->add_cap('nm_forum_comment'); }
    }
}
add_action('init', 'nm_register_forum_roles');

// Helper function to check if user is subscribed (has any subscription role)
function nm_user_is_subscribed($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return false;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // Check for subscription roles
    $subscription_roles = array(
        'nm_participant',
        'nm_collaborator', 
        'nm_premium',
        'nm_sponsor',
        'nm_paid_subscriber' // legacy role
    );
    
    foreach ($subscription_roles as $role) {
        if (in_array($role, $user->roles)) {
            return true;
        }
    }
    
    return false;
}

// Server-side guard: only allowed roles can comment on debate topics
add_filter('preprocess_comment', function($commentdata) {
    $post_id = isset($commentdata['comment_post_ID']) ? intval($commentdata['comment_post_ID']) : 0;
    if ($post_id > 0) {
        $p = get_post($post_id);
        if ($p && $p->post_type === 'debate_topic') {
            if (!is_user_logged_in() || !current_user_can('nm_forum_comment')) {
                wp_die(wp_kses_post(__('Necesitás una suscripción participante para comentar en En Debate. <a href="/suscripcion/">Ver planes</a>', 'lanota-theme-2026')), 403);
            }
        }
    }
    return $commentdata;
});

// ===== Likes for debate_topic =====
function nm_get_debate_likes($post_id) {
    $c = get_post_meta($post_id, 'debate_likes', true);
    return max(0, intval($c));
}

function nm_user_liked_debate($post_id, $user_id = 0) {
    $uid = $user_id ? intval($user_id) : get_current_user_id();
    if (!$uid) return false;
    $liked = get_user_meta($uid, 'nm_likes_debate', true);
    if (!is_array($liked)) $liked = array();
    return in_array(intval($post_id), array_map('intval', $liked), true);
}

function nm_set_user_like_debate($post_id, $user_id, $like = true) {
    $uid = intval($user_id);
    $pid = intval($post_id);
    $arr = get_user_meta($uid, 'nm_likes_debate', true);
    if (!is_array($arr)) $arr = array();
    $arr = array_map('intval', $arr);
    $has = in_array($pid, $arr, true);
    if ($like && !$has) {
        $arr[] = $pid;
        update_user_meta($uid, 'nm_likes_debate', array_values(array_unique($arr)));
        $count = nm_get_debate_likes($pid) + 1;
        update_post_meta($pid, 'debate_likes', $count);
        return array(true, $count);
    } elseif (!$like && $has) {
        $arr = array_values(array_diff($arr, array($pid)));
        update_user_meta($uid, 'nm_likes_debate', $arr);
        $count = max(0, nm_get_debate_likes($pid) - 1);
        update_post_meta($pid, 'debate_likes', $count);
        return array(false, $count);
    }
    return array($has, nm_get_debate_likes($pid));
}

add_action('wp_ajax_nm_toggle_debate_like', function(){
    if (!isset($_POST['post_id'], $_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'nm_like_' . intval($_POST['post_id']))) {
        wp_send_json_error(array('message' => __('Nonce inválido', 'lanota-theme-2026')), 400);
    }
    if (!is_user_logged_in() || !current_user_can('nm_forum_comment')) {
        wp_send_json_error(array('message' => __('Necesitás una suscripción participante para dar Me Gusta.', 'lanota-theme-2026')), 403);
    }
    $pid = intval($_POST['post_id']);
    $post = get_post($pid);
    if (!$post || $post->post_type !== 'debate_topic' || $post->post_status !== 'publish') {
        wp_send_json_error(array('message' => __('Publicación no válida', 'lanota-theme-2026')), 404);
    }
    $liked = nm_user_liked_debate($pid);
    list($now_liked, $count) = nm_set_user_like_debate($pid, get_current_user_id(), !$liked);
    
    // Notify post author of new like
    if ($now_liked && $post->post_author != get_current_user_id()) {
        nm_add_user_notification(
            $post->post_author,
            'debate_like',
            sprintf(__('A alguien le gustó tu debate "%s"', 'lanota-theme-2026'), $post->post_title),
            array('post_id' => $pid)
        );
    }
    
    wp_send_json_success(array('liked' => (bool)$now_liked, 'count' => intval($count)));
});

// AJAX endpoint to get notifications
add_action('wp_ajax_nm_get_notifications', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $notifications = nm_get_user_notifications(get_current_user_id());
    wp_send_json_success($notifications);
});

// AJAX endpoint to mark notification as read
add_action('wp_ajax_nm_mark_notification_read', function() {
    if (!is_user_logged_in() || !isset($_POST['notification_id'])) {
        wp_send_json_error('Invalid request');
    }
    
    $result = nm_mark_notification_read(get_current_user_id(), sanitize_text_field($_POST['notification_id']));
    wp_send_json_success(array('marked' => $result));
});

function nm_create_demo_users_once() {
    if (get_option('nm_demo_users_created')) { return; }
    // Demo: paid subscriber (forum author)
    if (!username_exists('demo_premium') && !email_exists('demo_premium@example.com')) {
        $uid = wp_create_user('demo_premium', 'Demo#Premium2025', 'demo_premium@example.com');
        if (!is_wp_error($uid)) {
            $u = new WP_User($uid);
            $u->set_role('nm_paid_subscriber');
        }
    }
    // Demo: commenter-only (regular subscriber)
    if (!username_exists('demo_comentarios') && !email_exists('demo_comentarios@example.com')) {
        $uid2 = wp_create_user('demo_comentarios', 'Demo#Comentarios2025', 'demo_comentarios@example.com');
        if (!is_wp_error($uid2)) {
            $u2 = new WP_User($uid2);
            $u2->set_role('subscriber');
        }
    }
    update_option('nm_demo_users_created', 1, false);
}
add_action('after_switch_theme', 'nm_create_demo_users_once');

// Ensure the 'principal' category exists
function lanota_2026_ensure_principal_category() {
    $term = get_category_by_slug('principal');
    if (!$term) {
        wp_insert_term('Principal', 'category', array('slug' => 'principal', 'description' => 'Categoría para la noticia principal (hero).'));
    }
}
add_action('after_switch_theme', 'lanota_2026_ensure_principal_category');
// Also check on init in case the category was removed later
add_action('init', function(){
    // Run late to avoid interfering with admin category screens
    if (!is_admin()) { lanota_2026_ensure_principal_category(); }
});

// ---- Foro de Debate: CPT + Page + Post handler ----
function nm_register_debate_cpt() {
    $labels = array(
        'name' => __('Debate', 'lanota-theme-2026'),
        'singular_name' => __('Tema de debate', 'lanota-theme-2026'),
        'add_new' => __('Nuevo tema', 'lanota-theme-2026'),
        'add_new_item' => __('Agregar nuevo tema', 'lanota-theme-2026'),
        'edit_item' => __('Editar tema', 'lanota-theme-2026'),
        'new_item' => __('Nuevo tema', 'lanota-theme-2026'),
        'view_item' => __('Ver tema', 'lanota-theme-2026'),
        'search_items' => __('Buscar temas', 'lanota-theme-2026'),
        'not_found' => __('No se encontraron temas', 'lanota-theme-2026'),
        'not_found_in_trash' => __('No hay temas en la papelera', 'lanota-theme-2026'),
        'all_items' => __('Todos los temas', 'lanota-theme-2026'),
    );
    register_post_type('debate_topic', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'debate'),
        'supports' => array('title','editor','author','comments'),
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-megaphone',
    ));
}
add_action('init', 'nm_register_debate_cpt');

// Create Foro page once with template
function nm_ensure_foro_page() {
    // Canonical new slug
    $slug = 'en-debate';
    $existing = get_page_by_path($slug);
    if (!$existing) {
        $page_id = wp_insert_post(array(
            'post_title' => 'En Debate',
            'post_name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
        ));
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-templates/page-foro.php');
        }
    } else {
        // Ensure template
        $tpl = get_post_meta($existing->ID, '_wp_page_template', true);
        if ($tpl !== 'page-templates/page-foro.php') {
            update_post_meta($existing->ID, '_wp_page_template', 'page-templates/page-foro.php');
        }
        // Ensure published status and correct slug
        $needs_update = false;
        $update = array('ID' => $existing->ID);
        if ($existing->post_status !== 'publish') {
            $update['post_status'] = 'publish';
            $needs_update = true;
        }
        if ($existing->post_name !== $slug) {
            $update['post_name'] = $slug;
            $needs_update = true;
        }
        if ($needs_update) {
            wp_update_post($update);
        }
    }
}
add_action('after_switch_theme', 'nm_ensure_foro_page');
// Also ensure on init for existing installs not switching now
add_action('init', function(){ if (!is_admin()) nm_ensure_foro_page(); });

// Redirect legacy /foro URL to /en-debate/ (permanent), preserving query params
add_action('template_redirect', function() {
    if (is_admin()) { return; }
    $req = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (!$req) { return; }
    $path = wp_parse_url($req, PHP_URL_PATH);
    if ($path === '/foro' || $path === '/foro/') {
        $target = home_url('/en-debate/');
        if (!empty($_GET)) { $target = add_query_arg(wp_unslash($_GET), $target); }
        wp_safe_redirect($target, 301);
        exit;
    }
});

// Flush rewrite rules on theme switch to register CPT permalinks
add_action('after_switch_theme', function(){
    nm_register_debate_cpt();
    flush_rewrite_rules(false);
});

// One-time rewrite flush on init if not done yet (helps fix 404s without switching theme)
add_action('init', function(){
    if (get_option('nm_forum_rewrite_flushed')) { return; }
    // Only run in frontend to avoid admin perf hit
    if (!is_admin()) {
        flush_rewrite_rules(false);
        update_option('nm_forum_rewrite_flushed', 1, false);
    }
}, 20);

// Handle create topic submission
function nm_handle_create_debate_topic() {
    // Only logged in allowed here
    if (!is_user_logged_in()) {
        wp_safe_redirect( nm_get_login_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/') ) );
        exit;
    }
    if (!isset($_POST['nm_forum_nonce']) || !wp_verify_nonce($_POST['nm_forum_nonce'], 'nm_forum_create')) {
        $base = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/');
        wp_safe_redirect( add_query_arg('nm_forum', 'invalid', $base) );
        exit;
    }
    if (!current_user_can('nm_forum_post')) {
        $base = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/');
        wp_safe_redirect( add_query_arg('nm_forum', 'no_perm', $base) );
        exit;
    }
    $title = sanitize_text_field( $_POST['topic_title'] ?? '' );
    $content = wp_kses_post( $_POST['topic_content'] ?? '' );
    if (strlen($title) < 6 || strlen($content) < 20) {
        $base = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/');
        wp_safe_redirect( add_query_arg('nm_forum', 'short', $base) );
        exit;
    }
    // Handle up to two images (webp, png, jpg)
    $allowed_mimes = array('image/webp','image/png','image/jpeg');
    $attached_ids = array();
    $img_too_many = false; $img_too_big = false; $img_bad_type = false;
    if (!empty($_FILES['topic_images']) && is_array($_FILES['topic_images']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $names = $_FILES['topic_images']['name'];
        $types = $_FILES['topic_images']['type'];
        $tmps  = $_FILES['topic_images']['tmp_name'];
        $errs  = $_FILES['topic_images']['error'];
        $sizes = $_FILES['topic_images']['size'];
        $max_size = 2 * 1024 * 1024; // 2 MB per image
        $orig_count = 0;
        foreach ($names as $nm) { if (!empty($nm)) { $orig_count++; } }
        if ($orig_count > 2) { $img_too_many = true; }
        $max = min(2, count($names));
        for ($i=0; $i<$max; $i++) {
            if (!isset($names[$i]) || !$names[$i] || !isset($tmps[$i]) || !$tmps[$i]) continue;
            if (!empty($errs[$i])) continue;
            if (isset($sizes[$i]) && intval($sizes[$i]) > $max_size) { $img_too_big = true; continue; } // too big
            // Validate mime from file info after upload attempt
            $file_array = array(
                'name' => sanitize_file_name($names[$i]),
                'type' => $types[$i],
                'tmp_name' => $tmps[$i],
                'error' => 0,
                'size' => intval($sizes[$i]),
            );
            $overrides = array('test_form' => false, 'mimes' => array(
                'webp' => 'image/webp',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
            ));
            $movefile = wp_handle_upload($file_array, $overrides);
            if ($movefile && !isset($movefile['error'])) {
                // Double-check mime
                if (!in_array($movefile['type'], $allowed_mimes, true)) {
                    $img_bad_type = true;
                    continue;
                }
                $attachment = array(
                    'post_mime_type' => $movefile['type'],
                    'post_title'     => sanitize_text_field(pathinfo($file_array['name'], PATHINFO_FILENAME)),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $movefile['file']);
                if (!is_wp_error($attach_id)) {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    $attached_ids[] = $attach_id;
                }
            }
        }
    }

    $post_id = wp_insert_post(array(
        'post_type' => 'debate_topic',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_content' => $content,
        'post_author' => get_current_user_id(),
        'comment_status' => 'open',
    ));
    if (is_wp_error($post_id)) {
        $base = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/en-debate/');
        wp_safe_redirect( add_query_arg('nm_forum', 'error', $base) );
        exit;
    }
    // If we have images, set first as featured and append both to content
    if (!empty($attached_ids)) {
        set_post_thumbnail($post_id, $attached_ids[0]);
        $imgs_html = '';
        foreach ($attached_ids as $aid) {
            $imgs_html .= wp_get_attachment_image($aid, 'large', false, array('class'=>'forum-uploaded-img')) . "\n";
        }
        if ($imgs_html) {
            $new_content = $content . "\n\n" . $imgs_html;
            wp_update_post(array('ID'=>$post_id,'post_content'=>$new_content));
        }
    }
    $qs = array('nm_forum' => 'created', 'topic' => $post_id);
    if ($img_too_many) { $qs['img_many'] = 1; }
    if ($img_too_big)  { $qs['img_big']  = 1; }
    if ($img_bad_type) { $qs['img_type'] = 1; }
    wp_safe_redirect( add_query_arg($qs, get_permalink($post_id)) );
    exit;
}
add_action('admin_post_nm_create_debate_topic', 'nm_handle_create_debate_topic');
add_action('admin_post_nopriv_nm_create_debate_topic', 'nm_handle_create_debate_topic');

// Enqueue debate editor script
function lanota_2026_enqueue_debate_editor() {
    // Only load where needed to reduce JS on non-forum pages
    if (is_post_type_archive('debate_topic') || is_singular('debate_topic') || is_page_template('page-templates/page-foro.php')) {
        wp_enqueue_script('debate-editor', get_template_directory_uri() . '/js/debate-editor.js', array('jquery'), filemtime(get_template_directory() . '/js/debate-editor.js'), true);
        wp_enqueue_script('debate-likes', get_template_directory_uri() . '/js/debate-likes.js', array('jquery'), filemtime(get_template_directory() . '/js/debate-likes.js'), true);
        wp_localize_script('debate-editor', 'debateEditor', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lanota_2026_nonce')
        ));
        wp_localize_script('debate-likes', 'ajaxurl', admin_url('admin-ajax.php'));
    }
}
add_action('wp_enqueue_scripts', 'lanota_2026_enqueue_debate_editor');

// AJAX handler for debate likes
function ajax_toggle_debate_like() {
    check_ajax_referer('lanota_2026_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Usuario no autenticado');
    }
    
    $debate_id = intval($_POST['debate_id']);
    $user_id = get_current_user_id();
    
    if (!$debate_id) {
        wp_send_json_error('ID de debate inválido');
    }
    
    // Get current likes
    $likes = get_post_meta($debate_id, '_debate_likes', true);
    if (!is_array($likes)) $likes = array();
    
    $user_liked = in_array($user_id, $likes);
    
    if ($user_liked) {
        // Remove like
        $likes = array_diff($likes, array($user_id));
        $action = 'unliked';
    } else {
        // Add like
        $likes[] = $user_id;
        $action = 'liked';
        
        // Save to user's liked posts
        $user_likes = get_user_meta($user_id, '_liked_debates', true);
        if (!is_array($user_likes)) $user_likes = array();
        if (!in_array($debate_id, $user_likes)) {
            $user_likes[] = $debate_id;
            update_user_meta($user_id, '_liked_debates', $user_likes);
        }
    }
    
    update_post_meta($debate_id, '_debate_likes', $likes);
    
    wp_send_json_success(array(
        'action' => $action,
        'count' => count($likes),
        'user_liked' => !$user_liked
    ));
}
add_action('wp_ajax_toggle_debate_like', 'ajax_toggle_debate_like');
add_action('wp_ajax_nopriv_toggle_debate_like', 'ajax_toggle_debate_like');

// AJAX handler to check if user liked a debate
function ajax_check_debate_like() {
    check_ajax_referer('lanota_2026_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_success(array('user_liked' => false));
    }
    
    $debate_id = intval($_POST['debate_id']);
    $user_id = get_current_user_id();
    
    if (!$debate_id) {
        wp_send_json_error('ID de debate inválido');
    }
    
    $likes = get_post_meta($debate_id, '_debate_likes', true);
    if (!is_array($likes)) $likes = array();
    
    $user_liked = in_array($user_id, $likes);
    
    wp_send_json_success(array('user_liked' => $user_liked));
}
add_action('wp_ajax_check_debate_like', 'ajax_check_debate_like');
add_action('wp_ajax_nopriv_check_debate_like', 'ajax_check_debate_like');

// Enqueue scripts and styles
function lanota_2026_enqueue_scripts() {
    // Version assets by file modification time for optimal long-term caching
    $style_path = get_stylesheet_directory() . '/style.css';
    $main_js_path = get_template_directory() . '/js/main.js';
    $style_ver = file_exists($style_path) ? filemtime($style_path) : null;
    $main_js_ver = file_exists($main_js_path) ? filemtime($main_js_path) : null;

    wp_enqueue_style('lanota-2026-style', get_stylesheet_uri(), array(), $style_ver);
    // Font Awesome 5.15.4 local
    $fa_local_css = get_template_directory() . '/assets/fontawesome/css/all.min.css';
    if (file_exists($fa_local_css)) {
        wp_enqueue_style('font-awesome', get_template_directory_uri() . '/assets/fontawesome/css/all.min.css', array(), '5.15.4');
    }
    // Enqueue scripts and styles with optimization
    
    // Only load main script on frontend
    if (!is_admin()) {
        wp_enqueue_script('lanota-2026-script', get_template_directory_uri() . '/js/main.js', array('jquery'), filemtime(get_template_directory() . '/js/main.js'), true);
        // Defer main bundle to improve first paint
        if (wp_script_is('lanota-2026-script', 'enqueued')) {
            wp_script_add_data('lanota-2026-script', 'defer', true);
        }
    }
    
    // Localize script for basic AJAX endpoints (nm_ajax)
    wp_localize_script('lanota-2026-script', 'nm_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nm_ajax_nonce')
    ));
    
    // Remove external Google Fonts; use self-hosted @font-face defined in style.css
    
    // Defer handled above after enqueue
    
    // Localize script for main bundle runtime (ajax_object used in main.js)
    $saved_ids = array();
    if (is_user_logged_in()) {
        $saved_ids = get_user_meta(get_current_user_id(), 'nm_saved_posts', true);
        if (!is_array($saved_ids)) { $saved_ids = array(); }
        $saved_ids = array_map('intval', $saved_ids);
    }
    wp_localize_script('lanota-2026-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ajax_nonce'),
        'current_post_id' => (is_single() ? get_queried_object_id() : 0),
        'is_logged_in' => is_user_logged_in(),
        'can_post_forum' => current_user_can('nm_forum_post') ? 1 : 0,
        'current_user_name' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
        'saved_post_ids' => $saved_ids,
        'compose_url' => admin_url('post-new.php'),
        'login_url' => nm_get_login_url(),
        'paywall_url' => (function() {
            $opt = get_option('lanota_2026_paywall_url');
            if ($opt && filter_var($opt, FILTER_VALIDATE_URL)) {
                return $opt;
            }
            return home_url('/suscripcion/');
        })(),
        'forum_url' => (function() {
            // 1) Customizer option if it's a valid URL
            $opt = get_option('lanota_2026_forum_url');
            if ($opt && filter_var($opt, FILTER_VALIDATE_URL)) {
                return $opt;
            }
            // 2) Auto-detect a page using the template
            $pages = get_pages(array(
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'page-foro.php',
                'number'     => 1,
            ));
            if (!empty($pages) && isset($pages[0]->ID)) {
                return get_permalink($pages[0]->ID);
            }
            // 3) Try by common slug (new first)
            $by_path = get_page_by_path('en-debate');
            if ($by_path) {
                return get_permalink($by_path->ID);
            }
            // 4) Legacy slug
            $legacy = get_page_by_path('foro');
            if ($legacy) {
                return get_permalink($legacy->ID);
            }
            // 5) Final fallback
            return home_url('/en-debate/');
        })(),
        'debate_max_len' => 400,
        'reply_max_len' => 240,
        // Infinite scroll enablement on home/front-page/archive or trending mode
        'infinite_enabled' => (is_home() || is_front_page() || is_archive() || (isset($_GET['trending']) && $_GET['trending'] === '1')) ? 1 : 0,
        // Pass trending params if present to persist through AJAX
        'trending' => array(
            'enabled' => (isset($_GET['trending']) && $_GET['trending'] === '1') ? 1 : 0,
            'sort' => isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'views',
            'cat_ids' => isset($_GET['cat_ids']) ? sanitize_text_field($_GET['cat_ids']) : '',
            'tag_slugs' => isset($_GET['tag_slugs']) ? sanitize_text_field($_GET['tag_slugs']) : ''
        )
    ));
}
add_action('wp_enqueue_scripts', 'lanota_2026_enqueue_scripts');

// Add resource hints for external CDN used (Font Awesome via cdnjs)
add_filter('wp_resource_hints', function($urls, $relation_type) {
    if ('preconnect' === $relation_type) {
        $urls[] = 'https://cdnjs.cloudflare.com';
    }
    if ('dns-prefetch' === $relation_type) {
        $urls[] = '//cdnjs.cloudflare.com';
    }
    return array_values(array_unique($urls));
}, 10, 2);

// Preload Font Awesome when served from CDN to improve first paint
add_filter('style_loader_tag', function($html, $handle, $href, $media) {
    // Preload external Font Awesome
    if ($handle === 'font-awesome' && strpos($href, 'cdnjs.cloudflare.com') !== false) {
        $html = sprintf('<link rel="preload" as="style" href="%s" onload="this.onload=null;this.rel=\'stylesheet\'">', esc_url($href));
        $html .= sprintf('<noscript><link rel="stylesheet" href="%s"></noscript>', esc_url($href));
    }
    // Preload main theme stylesheet for faster first paint
    if ($handle === 'lanota-2026-style') {
        $html = sprintf('<link rel="preload" as="style" href="%s" onload="this.onload=null;this.rel=\'stylesheet\'">', esc_url($href));
        $html .= sprintf('<noscript><link rel="stylesheet" href="%s"></noscript>', esc_url($href));
    }
    return $html;
}, 10, 4);

// Disable emojis and oEmbed to reduce front-end bloat
add_action('init', function() {
    // Emojis
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');

    // oEmbed
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    add_filter('embed_oembed_discover', '__return_false');
});

// Helper: get Forum / Debate URL (used across templates)
function lanota_2026_get_forum_url() {
    // 1) Customizer option if it's a valid URL
    $opt = get_option('lanota_2026_forum_url');
    if ($opt && filter_var($opt, FILTER_VALIDATE_URL)) {
        return $opt;
    }
    // 2) Auto-detect a page using the template
    $pages = get_pages(array(
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'page-templates/page-foro.php',
        'number'     => 1,
    ));
    if (!empty($pages) && isset($pages[0]->ID)) {
        return get_permalink($pages[0]->ID);
    }
    // 3) Try by common slug (new first)
    $by_path = get_page_by_path('en-debate');
    if ($by_path) {
        return get_permalink($by_path->ID);
    }
    // 4) Legacy slug
    $legacy = get_page_by_path('foro');
    if ($legacy) {
        return get_permalink($legacy->ID);
    }
    // 5) Final fallback
    return home_url('/en-debate/');
}

// Helper: get Agenda URL (always /agenda/)
function lanota_2026_get_agenda_url() {
    return home_url('/agenda/');
}

// Helper: get My Account URL (always the themed front-end page)
function lanota_2026_get_account_url() {
    // 1) Try by canonical slug
    $by_path = get_page_by_path('mi-cuenta');
    if ($by_path) {
        return get_permalink($by_path->ID);
    }
    // 2) Try by template assignment
    $by_tpl = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'meta_key' => '_wp_page_template',
        'meta_value' => 'page-templates/page-cuenta.php',
        'posts_per_page' => 1,
        'fields' => 'ids',
    ));
    if (!empty($by_tpl)) {
        return get_permalink($by_tpl[0]);
    }
    // 3) Fallback to expected path
    return home_url('/mi-cuenta/');
}

// Hide admin bar for non-staff users to keep the front-end fully themed
add_filter('show_admin_bar', function($show) {
    if (is_user_logged_in() && !current_user_can('edit_posts') && !current_user_can('manage_options')) {
        return false;
    }
    return $show;
}, 20);

// After login, send non-staff users to the themed My Account page if no specific redirect is requested
add_filter('login_redirect', function($redirect_to, $requested, $user){
    if (is_wp_error($user) || !$user) { return $redirect_to; }
    if (!user_can($user, 'edit_posts') && !user_can($user, 'manage_options')) {
        return function_exists('lanota_2026_get_account_url') ? lanota_2026_get_account_url() : home_url('/mi-cuenta/');
    }
    return $redirect_to;
}, 10, 3);

// ====== Save for Later (Leer Después) ======
function nm_get_saved_posts($user_id = 0) {
    $uid = $user_id ? intval($user_id) : get_current_user_id();
    if (!$uid) return array();
    $ids = get_user_meta($uid, 'nm_saved_posts', true);
    if (!is_array($ids)) $ids = array();
    return array_values(array_unique(array_map('intval', $ids)));
}

function nm_is_saved_post($post_id, $user_id = 0) {
    return in_array(intval($post_id), nm_get_saved_posts($user_id), true);
}

function nm_toggle_save_post($user_id, $post_id) {
    $ids = nm_get_saved_posts($user_id);
    $post_id = intval($post_id);
    if (in_array($post_id, $ids, true)) {
        $ids = array_values(array_diff($ids, array($post_id)));
        update_user_meta($user_id, 'nm_saved_posts', $ids);
        return 'removed';
    } else {
        $ids[] = $post_id;
        $ids = array_values(array_unique($ids));
        update_user_meta($user_id, 'nm_saved_posts', $ids);
        return 'saved';
    }
}

// Non-AJAX handler (works without JS)
function nm_handle_toggle_save() {
    $redirect = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : wp_get_referer();
    if (!is_user_logged_in()) {
        wp_safe_redirect( nm_get_login_url($redirect ? $redirect : home_url('/')) );
        exit;
    }
    if (!isset($_POST['nm_save_nonce']) || !wp_verify_nonce($_POST['nm_save_nonce'], 'nm_toggle_save')) {
        wp_safe_redirect( add_query_arg('saved', 'invalid', $redirect ?: home_url('/')) );
        exit;
    }
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || get_post_status($post_id) !== 'publish') {
        wp_safe_redirect( add_query_arg('saved', 'invalid', $redirect ?: home_url('/')) );
        exit;
    }
    $result = nm_toggle_save_post(get_current_user_id(), $post_id);
    wp_safe_redirect( add_query_arg('saved', $result, $redirect ?: get_permalink($post_id)) );
    exit;
}
add_action('admin_post_nm_toggle_save', 'nm_handle_toggle_save');
add_action('admin_post_nopriv_nm_toggle_save', 'nm_handle_toggle_save');

function nm_render_save_button($post_id = 0) {
    if (!$post_id) $post_id = get_the_ID();
    if (!$post_id) return '';
    $is_saved = is_user_logged_in() ? nm_is_saved_post($post_id) : false;
    $label = $is_saved ? __('Guardado', 'lanota-theme-2026') : __('Guardar', 'lanota-theme-2026');
    $icon  = $is_saved ? 'fas fa-bookmark' : 'far fa-bookmark';
    $redirect_to = (is_singular() ? get_permalink($post_id) : (is_home() || is_archive() ? home_url(add_query_arg(null, null)) : get_permalink($post_id)));
    $out  = '<form class="nm-save-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    $out .= '<input type="hidden" name="action" value="nm_toggle_save" />';
    $out .= wp_nonce_field('nm_toggle_save', 'nm_save_nonce', true, false);
    $out .= '<input type="hidden" name="post_id" value="' . intval($post_id) . '" />';
    $out .= '<input type="hidden" name="redirect_to" value="' . esc_attr($redirect_to) . '" />';
    $out .= '<button type="submit" class="nm-save-btn" aria-pressed="' . ($is_saved ? 'true' : 'false') . '">';
    $out .= '<i class="' . esc_attr($icon) . '" aria-hidden="true"></i> ' . esc_html($label);
    $out .= '</button>';
    $out .= '</form>';
    return $out;
}

// Inject save button below content on single posts and below excerpt on archives
function nm_append_save_button_to_content($content) {
    if (is_singular('post') && in_the_loop() && is_main_query()) {
        return $content . '<div class="nm-save-wrap">' . nm_render_save_button() . '</div>';
    }
    return $content;
}
add_filter('the_content', 'nm_append_save_button_to_content');

function nm_append_save_button_to_excerpt($excerpt) {
    if ((is_home() || is_archive()) && in_the_loop() && is_main_query() && get_post_type() === 'post') {
        return $excerpt . ' <div class="nm-save-wrap">' . nm_render_save_button() . '</div>';
    }
    return $excerpt;
}
add_filter('the_excerpt', 'nm_append_save_button_to_excerpt');

// Auto-create "Mi Cuenta" page with template
function nm_ensure_mi_cuenta_page() {
    $slug = 'mi-cuenta';
    $existing = get_page_by_path($slug);
    if (!$existing) {
        $page_id = wp_insert_post(array(
            'post_title' => 'Mi Cuenta',
            'post_name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
        ));
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-templates/page-cuenta.php');
        }
    } else {
        $tpl = get_post_meta($existing->ID, '_wp_page_template', true);
        if ($tpl !== 'page-templates/page-cuenta.php') {
            update_post_meta($existing->ID, '_wp_page_template', 'page-templates/page-cuenta.php');
        }
    }
}
add_action('after_switch_theme', 'nm_ensure_mi_cuenta_page');

// Also ensure on init (for sites donde no se ejecutó after_switch_theme)
function nm_ensure_mi_cuenta_page_on_init() {
    $slug = 'mi-cuenta';
    $existing = get_page_by_path($slug);
    if (!$existing && !get_option('nm_mi_cuenta_created')) {
        $page_id = wp_insert_post(array(
            'post_title' => 'Mi Cuenta',
            'post_name' => $slug,
            'post_type' => 'page',
            'post_status' => 'publish',
        ));
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'page-templates/page-cuenta.php');
            update_option('nm_mi_cuenta_created', 1);
            // Ensure permalinks include the new page path
            if (!get_option('nm_flush_after_account')) {
                flush_rewrite_rules(false);
                update_option('nm_flush_after_account', 1);
            }
        }
    } elseif ($existing) {
        $tpl = get_post_meta($existing->ID, '_wp_page_template', true);
        if ($tpl !== 'page-templates/page-cuenta.php') {
            update_post_meta($existing->ID, '_wp_page_template', 'page-templates/page-cuenta.php');
        }
    }
}
add_action('init', 'nm_ensure_mi_cuenta_page_on_init');

// Resource hints for third-party CDNs (performance)
function lanota_2026_resource_hints($urls, $relation_type) {
    if ('preconnect' === $relation_type || 'dns-prefetch' === $relation_type) {
        $hints = array(
            'https://cdnjs.cloudflare.com',
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
        );
        foreach ($hints as $h) {
            $urls[] = $h;
        }
    }
    return $urls;
}
add_filter('wp_resource_hints', 'lanota_2026_resource_hints', 10, 2);

// --- Images: convert generated sizes to WebP when possible ---
function la_nota_image_output_format($formats) {
    // Prefer AVIF if supported; else fallback to WebP
    if (function_exists('wp_image_editor_supports')) {
        if (wp_image_editor_supports(array('mime_type' => 'image/avif'))) {
            $formats['image/jpeg'] = 'image/avif';
            $formats['image/png']  = 'image/avif';
        } elseif (wp_image_editor_supports(array('mime_type' => 'image/webp'))) {
            $formats['image/jpeg'] = 'image/webp';
            $formats['image/png']  = 'image/webp';
        }
    }
    return $formats;
}
add_filter('image_editor_output_format', 'la_nota_image_output_format');

// Set reasonable compression quality for generated images (WebP + JPEG)
add_filter('wp_editor_set_quality', function($quality){ return 82; });

// Ensure lazy-loading is enabled globally (WordPress defaults to true since 5.5)
add_filter('wp_lazy_loading_enabled', '__return_true');

// Allow uploads of WebP/AVIF
add_filter('upload_mimes', function($mimes){
    $mimes['webp'] = 'image/webp';
    $mimes['avif'] = 'image/avif';
    return $mimes;
});

// Fallback menu function
function lanota_2026_fallback_menu() {
    echo '<ul class="nav-menu">';
    echo '<li><a href="' . home_url() . '"><i class="fas fa-home icon"></i><span class="nav-text">Inicio</span></a></li>';
    echo '<li><a href="' . esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ) . '"><i class="fas fa-bullhorn icon"></i><span class="nav-text">En debate</span></a></li>';
    echo '<li><a href="' . home_url('/category/noticias/') . '"><i class="fas fa-newspaper icon"></i><span class="nav-text">Noticias</span></a></li>';
    echo '<li><a href="' . home_url('/category/deportes/') . '"><i class="fas fa-futbol icon"></i><span class="nav-text">Deportes</span></a></li>';
    echo '<li><a href="' . home_url('/category/tecnologia/') . '"><i class="fas fa-laptop icon"></i><span class="nav-text">Tecnología</span></a></li>';
    echo '<li><a href="' . home_url('/category/entretenimiento/') . '"><i class="fas fa-film icon"></i><span class="nav-text">Entretenimiento</span></a></li>';
    echo '</ul>';
}

// ---------- Featured Video Meta Box ----------
function lanota_2026_add_featured_video_metabox() {
    add_meta_box(
        'lanota_2026_featured_video',
        'Video destacado (feed)',
        'lanota_2026_featured_video_metabox_cb',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'lanota_2026_add_featured_video_metabox');

function lanota_2026_featured_video_metabox_cb($post) {
    wp_nonce_field('lanota_2026_featured_video_save', 'lanota_2026_featured_video_nonce');
    $url = get_post_meta($post->ID, '_featured_video_url', true);
    echo '<p>Seleccioná o pegá un video (MP4/WebM). Se mostrará en el feed en lugar de la imagen destacada.</p>';
    echo '<input type="url" id="featured-video-url" name="featured_video_url" value="' . esc_attr($url) . '" style="width:100%" placeholder="https://.../video.mp4" />';
    echo '<p><button type="button" class="button" id="featured-video-upload">Subir/Seleccionar video</button></p>';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){var btn=document.getElementById("featured-video-upload");if(!btn||!wp||!wp.media)return;btn.addEventListener("click",function(e){e.preventDefault();var frame=wp.media({title:"Seleccionar video",library:{type:["video"]},multiple:false});frame.on("select",function(){var a=frame.state().get("selection").first().toJSON();var input=document.getElementById("featured-video-url");if(input&&a&&a.url){input.value=a.url;}});frame.open();});});</script>';
}

function lanota_2026_save_featured_video($post_id) {
    if (!isset($_POST['lanota_2026_featured_video_nonce']) || !wp_verify_nonce($_POST['lanota_2026_featured_video_nonce'], 'lanota_2026_featured_video_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $url = isset($_POST['featured_video_url']) ? esc_url_raw($_POST['featured_video_url']) : '';
    if ($url) update_post_meta($post_id, '_featured_video_url', $url); else delete_post_meta($post_id, '_featured_video_url');
}
add_action('save_post_post', 'lanota_2026_save_featured_video');

// Allow WebM uploads
function lanota_2026_mimes($mimes){ $mimes['webm'] = 'video/webm'; return $mimes; }
add_filter('upload_mimes', 'lanota_2026_mimes');

// Mobile menu fallback function
function lanota_2026_mobile_fallback_menu() {
    echo '<ul class="mobile-menu-nav">';
    echo '<li><a href="' . home_url() . '"><i class="fas fa-home"></i>Inicio</a></li>';
    echo '<li><a href="' . esc_url( function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/') ) . '"><i class="fas fa-bullhorn"></i>En debate</a></li>';
    echo '<li><a href="' . home_url('/category/deportes/') . '"><i class="fas fa-futbol"></i>Deportes</a></li>';
    echo '<li><a href="' . home_url('/category/tecnologia/') . '"><i class="fas fa-laptop"></i>Tecnología</a></li>';
    echo '<li><a href="' . home_url('/category/entretenimiento/') . '"><i class="fas fa-film"></i>Entretenimiento</a></li>';
    echo '</ul>';
}

// AJAX handler for infinite scroll
function load_more_posts() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_die('Security check failed');
    }
    
    $page = intval($_POST['page']);
    $exclude = isset($_POST['exclude']) ? array_map('intval', (array) $_POST['exclude']) : array();
    $posts_per_page = get_option('posts_per_page');
    
    $query_args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $page
    );

    if (!empty($exclude)) {
        $query_args['post__not_in'] = $exclude;
    }

    // Trending mode support
    $is_trending = isset($_POST['trending']) && $_POST['trending'] === '1';
    if ($is_trending) {
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'views';
        $cat_ids = isset($_POST['cat_ids']) ? sanitize_text_field($_POST['cat_ids']) : '';
        $tag_slugs = isset($_POST['tag_slugs']) ? sanitize_text_field($_POST['tag_slugs']) : '';

        if ($sort === 'latest') {
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        } elseif ($sort === 'likes') {
            $query_args['meta_key'] = 'post_likes';
            $query_args['orderby'] = 'meta_value_num';
            $query_args['order'] = 'DESC';
        } else {
            $query_args['meta_key'] = 'post_views';
            $query_args['orderby'] = 'meta_value_num';
            $query_args['order'] = 'DESC';
        }

        $tax_query = array('relation' => 'AND');
        if (!empty($cat_ids)) {
            $ids = array_filter(array_map('intval', explode(',', $cat_ids)));
            if (!empty($ids)) {
                $tax_query[] = array(
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $ids,
                    'operator' => 'IN',
                );
            }
        }
        if (!empty($tag_slugs)) {
            $slugs = array_filter(array_map('sanitize_title', array_map('trim', explode(',', $tag_slugs))));
            if (!empty($slugs)) {
                $tax_query[] = array(
                    'taxonomy' => 'post_tag',
                    'field' => 'slug',
                    'terms' => $slugs,
                    'operator' => 'IN',
                );
            }
        }
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }
    }

    // Add caching for saved posts query
    $cache_key = 'saved_posts_' . md5(serialize($query_args));
    $posts = wp_cache_get($cache_key, 'nm_cache');
    if (false === $posts) {
        $posts = new WP_Query($query_args);
        wp_cache_set($cache_key, $posts, 'nm_cache', 180);
    }
    
    if ($posts->have_posts()) :
        $i = 0;
        while ($posts->have_posts()) : $posts->the_post();
            $i++;
            // Use the same template part as archives/feed to keep markup and share icons consistent
            get_template_part('template-parts/content', 'post');
            if ($i % 10 === 0) {
                lanota_2026_render_infeed_ad_by_index($i / 10 - 1);
            }
        endwhile;
    endif;
    
    wp_reset_postdata();
    wp_die();
}
add_action('wp_ajax_load_more_posts', 'load_more_posts');
add_action('wp_ajax_nopriv_load_more_posts', 'load_more_posts');

// Custom post meta for featured posts
function add_featured_post_meta_box() {
    add_meta_box(
        'featured_post',
        'Noticia Destacada',
        'featured_post_meta_box_callback',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_featured_post_meta_box');

function featured_post_meta_box_callback($post) {
    wp_nonce_field('save_featured_post_meta', 'featured_post_nonce');
    $value = get_post_meta($post->ID, 'featured_post', true);
    echo '<label for="featured_post_field">';
    echo '<input type="checkbox" id="featured_post_field" name="featured_post_field" value="1" ' . checked($value, '1', false) . '>';
    echo ' Marcar como noticia destacada</label>';
}

function save_featured_post_meta($post_id) {
    if (!isset($_POST['featured_post_nonce']) || !wp_verify_nonce($_POST['featured_post_nonce'], 'save_featured_post_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['featured_post_field'])) {
        update_post_meta($post_id, 'featured_post', '1');
    } else {
        delete_post_meta($post_id, 'featured_post');
    }
}
add_action('save_post', 'save_featured_post_meta');

// Post view counter
function set_post_views($post_id) {
    $key = 'post_views';
    $count = get_post_meta($post_id, $key, true);
    if ($count == '') {
        $count = 0;
        delete_post_meta($post_id, $key);
        add_post_meta($post_id, $key, '0');
    } else {
        $count++;
        update_post_meta($post_id, $key, $count);
    }
}

function track_post_views($post_id) {
    if (!is_single()) return;
    if (empty($post_id)) {
        global $post;
        $post_id = $post->ID;
    }
    set_post_views($post_id);
}
add_action('wp_head', 'track_post_views');

// Customizer settings
function lanota_2026_theme_customize_register($wp_customize) {
    // Logo section
    $wp_customize->add_section('logo_section', array(
        'title' => 'Logo Settings',
        'priority' => 30,
    ));

    // Default logo (used for light + fullcolor)
    $wp_customize->add_setting('lanota_2026_logo_default', array(
        'type' => 'theme_mod',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'lanota_2026_logo_default', array(
        'label' => 'Logo (Claro / Full Color)',
        'section' => 'logo_section',
    )));

    // Dark logo
    $wp_customize->add_setting('lanota_2026_logo_dark', array(
        'type' => 'theme_mod',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'lanota_2026_logo_dark', array(
        'label' => 'Logo (Oscuro)',
        'section' => 'logo_section',
    )));

    // Theme colors
    $wp_customize->add_section('theme_colors', array(
        'title' => 'Theme Colors',
        'priority' => 40,
    ));

    $wp_customize->add_setting('primary_color', array(
        'default' => '#e53935',
        'sanitize_callback' => 'sanitize_hex_color',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_color', array(
        'label' => 'Primary Color',
        'section' => 'theme_colors',
    )));

    // Monetization / Paywall settings
    $wp_customize->add_section('monetization_settings', array(
        'title' => 'Monetización',
        'priority' => 45,
    ));

    $wp_customize->add_setting('lanota_2026_paywall_url', array(
        'default' => home_url('/suscripcion/'),
        'sanitize_callback' => 'esc_url_raw',
        'type' => 'option'
    ));

    $wp_customize->add_control('lanota_2026_paywall_url', array(
        'label' => 'URL del Paywall / Suscripción',
        'type' => 'url',
        'section' => 'monetization_settings',
        'description' => 'Página a la que se redirige cuando un usuario no logueado intenta publicar.'
    ));

    // Community / Foro settings
    $wp_customize->add_section('community_settings', array(
        'title' => 'Comunidad / Foro',
        'priority' => 46,
    ));

    $wp_customize->add_setting('lanota_2026_forum_url', array(
        'default' => home_url('/en-debate/'),
        'sanitize_callback' => 'esc_url_raw',
        'type' => 'option',
    ));

    $wp_customize->add_control('lanota_2026_forum_url', array(
        'label' => 'URL de En Debate',
        'type' => 'url',
        'section' => 'community_settings',
        'description' => 'Página que usa la plantilla "En Debate".',
    ));

    // Security: reCAPTCHA
    $wp_customize->add_section('security_settings', array(
        'title' => 'Seguridad',
        'priority' => 48,
    ));

    $wp_customize->add_setting('nm_recaptcha_site_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
        'type' => 'option',
    ));
    $wp_customize->add_control('nm_recaptcha_site_key', array(
        'label' => 'reCAPTCHA Site Key (v2 Checkbox)',
        'type' => 'text',
        'section' => 'security_settings',
        'description' => 'Clave pública para el formulario de acceso de suscriptores.'
    ));

    $wp_customize->add_setting('nm_recaptcha_secret_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
        'type' => 'option',
    ));
    $wp_customize->add_control('nm_recaptcha_secret_key', array(
        'label' => 'reCAPTCHA Secret Key',
        'type' => 'text',
        'section' => 'security_settings',
        'description' => 'Clave secreta para la verificación en el servidor.'
    ));

    // Social links
    $wp_customize->add_section('social_settings', array(
        'title' => 'Redes Sociales',
        'priority' => 47,
    ));

    $social_controls = array(
        'lanota_2026_facebook_url' => array('label' => 'Facebook URL'),
        'lanota_2026_twitter_url'  => array('label' => 'X / Twitter URL'),
        'lanota_2026_instagram_url'=> array('label' => 'Instagram URL'),
        'lanota_2026_youtube_url'  => array('label' => 'YouTube URL'),
    );
    foreach ($social_controls as $key => $meta) {
        $wp_customize->add_setting($key, array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
            'type' => 'option',
        ));
        $wp_customize->add_control($key, array(
            'label' => $meta['label'],
            'type' => 'url',
            'section' => 'social_settings',
        ));
    }
}
add_action('customize_register', 'lanota_2026_theme_customize_register');

// Helpers: get logo URLs with fallbacks to custom_logo if set
function lanota_2026_get_custom_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $img = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($img && !empty($img[0])) return $img[0];
    }
    return '';
}

function lanota_2026_get_logo_default_url() {
    $url = get_theme_mod('lanota_2026_logo_default');
    if ($url) return esc_url($url);
    // Fallback to general custom logo
    $fallback = lanota_2026_get_custom_logo_url();
    return $fallback ? esc_url($fallback) : '';
}

function lanota_2026_get_logo_dark_url() {
    $url = get_theme_mod('lanota_2026_logo_dark');
    if ($url) return esc_url($url);
    // If not provided, fallback to default
    $fallback = lanota_2026_get_logo_default_url();
    return $fallback ? esc_url($fallback) : '';
}

/**
 * Echo logo markup with default/dark variants. Wraps in home link.
 */
function lanota_2026_render_logo($class = 'site-logo') {
    $logo_default = lanota_2026_get_logo_default_url();
    $logo_dark = lanota_2026_get_logo_dark_url();
    $home = esc_url(home_url('/'));
    $alt = esc_attr(get_bloginfo('name'));
    echo '<a class="' . esc_attr($class) . '" href="' . $home . '" aria-label="' . $alt . '">';
    if ($logo_default) {
        echo '<img class="logo-default" src="' . esc_url($logo_default) . '" alt="' . $alt . '" loading="lazy" />';
    }
    if ($logo_dark) {
        echo '<img class="logo-dark" src="' . esc_url($logo_dark) . '" alt="' . $alt . '" loading="lazy" />';
    }
    if (!$logo_default && !$logo_dark) {
        echo '<span class="site-title-text">' . get_bloginfo('name') . '</span>';
    }
    echo '</a>';
}

// Output custom colors
function lanota_2026_theme_custom_colors() {
    $primary_color = get_theme_mod('primary_color', '#e53935');
    ?>
    <style type="text/css">
        :root {
            --primary-color: <?php echo esc_attr($primary_color); ?>;
        }
    </style>
    <?php
}
add_action('wp_head', 'lanota_2026_theme_custom_colors');

// Widget areas
function lanota_2026_theme_widgets_init() {
    register_sidebar(array(
        'name' => 'Right Sidebar',
        'id' => 'sidebar-1',
        'description' => 'Add widgets here to appear in your sidebar.',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<div class="widget-header">',
        'after_title' => '</div><div class="widget-content">',
    ));

    // Top area of the right sidebar (place Trending widget here to pin it at the top)
    register_sidebar(array(
        'name' => 'Right Sidebar Top',
        'id' => 'sidebar-right-top',
        'description' => 'Zona superior del sidebar derecho. Ideal para Tendencias.',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<div class="widget-header">',
        'after_title' => '</div><div class="widget-content">',
    ));

    // Ads: in-feed widgets pool
    register_sidebar(array(
        'name' => 'In-Feed Ads',
        'id' => 'infeed-ads',
        'description' => 'Colocá acá anuncios que aparecerán cada 10 posts en el feed.',
        'before_widget' => '<div class="infeed-ad">',
        'after_widget' => '</div>',
        'before_title' => '<div class="sr-only">', // hide titles visually
        'after_title' => '</div>',
    ));

    // Ads: right sidebar zone
    register_sidebar(array(
        'name' => 'Sidebar Ads',
        'id' => 'sidebar-ads',
        'description' => 'Zona de anuncios para el sidebar derecho (desktop).',
        'before_widget' => '<div class="widget ad-widget">',
        'after_widget' => '</div>',
        'before_title' => '<div class="widget-header">',
        'after_title' => '</div><div class="widget-content">',
    ));

    // Forum-specific sidebar widgets
    register_sidebar(array(
        'name' => 'Forum Sidebar Top',
        'id' => 'forum-sidebar-top',
        'description' => 'Zona superior del sidebar en páginas del foro. Ideal para publicidades destacadas.',
        'before_widget' => '<div class="widget forum-widget">',
        'after_widget' => '</div>',
        'before_title' => '<div class="widget-header">',
        'after_title' => '</div><div class="widget-content">',
    ));

    register_sidebar(array(
        'name' => 'Forum Sidebar Middle',
        'id' => 'forum-sidebar-middle',
        'description' => 'Zona media del sidebar en páginas del foro. Para publicidades y contenido relacionado.',
        'before_widget' => '<div class="widget forum-widget">',
        'after_widget' => '</div>',
        'before_title' => '<div class="widget-header">',
        'after_title' => '</div><div class="widget-content">',
    ));

    register_sidebar(array(
        'name' => 'Forum Sidebar Bottom',
        'id' => 'forum-sidebar-bottom',
        'description' => 'Zona inferior del sidebar en páginas del foro. Para publicidades adicionales.',
        'before_widget' => '<div class="widget forum-widget">',
        'after_widget' => '</div>',
        'before_title' => '<div class="widget-header">',
        'after_title' => '</div><div class="widget-content">',
    ));
}
add_action('widgets_init', 'lanota_2026_theme_widgets_init');

// ---- Configurable Ad Widget ----
class News_Media_Ad_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'lanota_2026_ad_widget',
            __('Anuncio (Lanota Theme 2026)', 'lanota-theme-2026'),
            array('description' => __('Widget de anuncio: imagen/GIF con enlace o código HTML.', 'lanota-theme-2026'))
        );
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $image_url = isset($instance['image_url']) ? esc_url($instance['image_url']) : '';
        $link_url = isset($instance['link_url']) ? esc_url($instance['link_url']) : '';
        $code = isset($instance['code']) ? $instance['code'] : '';
        $new_tab = !empty($instance['new_tab']);
        $nofollow = !empty($instance['nofollow']);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Título (opcional):</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('image_url')); ?>">URL de imagen/GIF:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('image_url')); ?>" name="<?php echo esc_attr($this->get_field_name('image_url')); ?>" type="url" value="<?php echo $image_url; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('link_url')); ?>">Link (si hay imagen):</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('link_url')); ?>" name="<?php echo esc_attr($this->get_field_name('link_url')); ?>" type="url" value="<?php echo $link_url; ?>" />
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('new_tab')); ?>" name="<?php echo esc_attr($this->get_field_name('new_tab')); ?>" <?php checked($new_tab); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('new_tab')); ?>">Abrir en nueva pestaña</label>
        </p>
        <p>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('nofollow')); ?>" name="<?php echo esc_attr($this->get_field_name('nofollow')); ?>" <?php checked($nofollow); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('nofollow')); ?>">Agregar rel="nofollow sponsored"</label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('code')); ?>">Código HTML (alternativa a imagen):</label>
            <textarea class="widefat" rows="6" id="<?php echo esc_attr($this->get_field_id('code')); ?>" name="<?php echo esc_attr($this->get_field_name('code')); ?>"><?php echo esc_textarea($code); ?></textarea>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['image_url'] = esc_url_raw($new_instance['image_url'] ?? '');
        $instance['link_url'] = esc_url_raw($new_instance['link_url'] ?? '');
        $instance['code'] = $new_instance['code'] ?? '';
        $instance['new_tab'] = !empty($new_instance['new_tab']) ? 1 : 0;
        $instance['nofollow'] = !empty($new_instance['nofollow']) ? 1 : 0;
        return $instance;
    }

    public function widget($args, $instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $image_url = isset($instance['image_url']) ? $instance['image_url'] : '';
        $link_url = isset($instance['link_url']) ? $instance['link_url'] : '';
        $code = isset($instance['code']) ? $instance['code'] : '';
        $new_tab = !empty($instance['new_tab']);
        $nofollow = !empty($instance['nofollow']);

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
            echo '<div class="widget-content">';
        }

        if (!empty($code)) {
            // Allow safe HTML (ad code usually needs scripts, but we keep minimal for theme safety)
            echo $code; // trusted site admins place code
        } elseif (!empty($image_url)) {
            $attrs = '';
            if ($new_tab) $attrs .= ' target="_blank"';
            if ($nofollow) $attrs .= ' rel="nofollow sponsored"';
            if (!empty($link_url)) echo '<a href="' . esc_url($link_url) . '"' . $attrs . '>';
            echo '<img src="' . esc_url($image_url) . '" alt="Ad" loading="lazy" style="max-width:100%;height:auto;border-radius:12px;" />';
            if (!empty($link_url)) echo '</a>';
        } else {
            echo '<div class="ad-placeholder">Configurá este anuncio en el widget.</div>';
        }

        if (!empty($title)) {
            echo '</div>';
        }
        echo $args['after_widget'];
    }
}

// Forum Advertisement Widget
class News_Media_Forum_Ad_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'lanota_2026_forum_ad_widget',
            __('Publicidad del Foro', 'lanota-theme-2026'),
            array('description' => __('Widget de publicidad específico para páginas del foro.', 'lanota-theme-2026'))
        );
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $image_url = isset($instance['image_url']) ? esc_url($instance['image_url']) : '';
        $link_url = isset($instance['link_url']) ? esc_url($instance['link_url']) : '';
        $code = isset($instance['code']) ? $instance['code'] : '';
        $ad_type = isset($instance['ad_type']) ? $instance['ad_type'] : 'banner';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Título (opcional):</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('ad_type')); ?>">Tipo de anuncio:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('ad_type')); ?>" name="<?php echo esc_attr($this->get_field_name('ad_type')); ?>">
                <option value="banner" <?php selected($ad_type, 'banner'); ?>>Banner (300x250)</option>
                <option value="skyscraper" <?php selected($ad_type, 'skyscraper'); ?>>Rascacielos (160x600)</option>
                <option value="square" <?php selected($ad_type, 'square'); ?>>Cuadrado (250x250)</option>
                <option value="custom" <?php selected($ad_type, 'custom'); ?>>Personalizado</option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('image_url')); ?>">URL de imagen:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('image_url')); ?>" name="<?php echo esc_attr($this->get_field_name('image_url')); ?>" type="url" value="<?php echo $image_url; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('link_url')); ?>">URL de destino:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('link_url')); ?>" name="<?php echo esc_attr($this->get_field_name('link_url')); ?>" type="url" value="<?php echo $link_url; ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('code')); ?>">Código HTML (Google Ads, etc.):</label>
            <textarea class="widefat" rows="6" id="<?php echo esc_attr($this->get_field_id('code')); ?>" name="<?php echo esc_attr($this->get_field_name('code')); ?>"><?php echo esc_textarea($code); ?></textarea>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['image_url'] = esc_url_raw($new_instance['image_url'] ?? '');
        $instance['link_url'] = esc_url_raw($new_instance['link_url'] ?? '');
        $instance['code'] = $new_instance['code'] ?? '';
        $instance['ad_type'] = sanitize_text_field($new_instance['ad_type'] ?? 'banner');
        return $instance;
    }

    public function widget($args, $instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        $image_url = isset($instance['image_url']) ? $instance['image_url'] : '';
        $link_url = isset($instance['link_url']) ? $instance['link_url'] : '';
        $code = isset($instance['code']) ? $instance['code'] : '';
        $ad_type = isset($instance['ad_type']) ? $instance['ad_type'] : 'banner';

        echo $args['before_widget'];
        
        echo '<div class="ad-label">PUBLICIDAD</div>';
        
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        echo '<div class="widget-content forum-ad-content ad-type-' . esc_attr($ad_type) . '">';

        if (!empty($code)) {
            echo $code;
        } elseif (!empty($image_url)) {
            if (!empty($link_url)) {
                echo '<a href="' . esc_url($link_url) . '" target="_blank" rel="nofollow sponsored">';
            }
            echo '<img src="' . esc_url($image_url) . '" alt="Publicidad" loading="lazy" class="forum-ad-image" />';
            if (!empty($link_url)) {
                echo '</a>';
            }
        } else {
            echo '<div class="ad-placeholder">Configurá tu publicidad aquí</div>';
        }

        echo '</div>';
        echo $args['after_widget'];
    }
}

add_action('widgets_init', function(){
    register_widget('News_Media_Ad_Widget');
    register_widget('News_Media_Forum_Ad_Widget');
    register_widget('News_Media_Trending_Widget');
});

// Helpers to render in-feed ads pool without repetition per request
function lanota_2026_get_sidebar_widget_instances($sidebar_id) {
    $sidebars = wp_get_sidebars_widgets();
    if (empty($sidebars[$sidebar_id])) return array();
    $widgets = $sidebars[$sidebar_id]; // e.g. array( 'lanota_2026_ad_widget-2', ... )
    $out = array();
    foreach ($widgets as $widget_id) {
        // Extract id_base and number
        if (!preg_match('/^(.*)-(\d+)$/', $widget_id, $m)) continue;
        $id_base = $m[1]; $number = intval($m[2]);
        $all_opts = get_option('widget_' . $id_base);
        if (isset($all_opts[$number])) {
            $out[] = array('id_base' => $id_base, 'number' => $number, 'instance' => $all_opts[$number]);
        }
    }
    return $out;
}

function lanota_2026_render_infeed_ad_by_index($i) {
    $pool = lanota_2026_get_sidebar_widget_instances('infeed-ads');
    if (empty($pool)) return; // nothing configured
    $idx = $i % count($pool);
    $widget = $pool[$idx];
    // Render the widget instance manually
    $args = array(
        'before_widget' => '<div class="infeed-ad">',
        'after_widget'  => '</div>',
        'before_title'  => '<div class="sr-only">',
        'after_title'   => '</div>',
        'widget_id'     => $widget['id_base'] . '-' . $widget['number'],
        'widget_name'   => 'Ad',
    );
    $wobj = new News_Media_Ad_Widget();
    ob_start();
    $wobj->widget($args, $widget['instance']);
    echo ob_get_clean();
}

// Excerpt length
function lanota_2026_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'lanota_2026_excerpt_length');

// Search functionality enhancement
function lanota_2026_search_filter($query) {
    if (!is_admin() && $query->is_main_query()) {
        if ($query->is_search()) {
            $query->set('post_type', array('post'));
        }
    }
}
add_action('pre_get_posts', 'lanota_2026_search_filter');

// Enhanced AJAX search with multiple content types
function ajax_search_suggestions() {
    if (!wp_verify_nonce($_POST['nonce'], 'nm_ajax_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $search_term = sanitize_text_field($_POST['search_term']);
    if (strlen($search_term) < 2) {
        wp_send_json_success(array());
    }
    
    // Cache search suggestions
    $cache_key = 'search_suggestions_' . md5($search_term);
    $cached_results = wp_cache_get($cache_key, 'nm_cache');
    if (false !== $cached_results) {
        wp_send_json_success($cached_results);
        return;
    }
    
    // Search in posts and debate topics
    $posts = new WP_Query(array(
        's' => $search_term,
        'posts_per_page' => 3,
        'post_status' => 'publish',
        'post_type' => array('post')
    ));
    
    $debates = new WP_Query(array(
        's' => $search_term,
        'posts_per_page' => 2,
        'post_status' => 'publish',
        'post_type' => array('debate_topic')
    ));
    
    $suggestions = array();
    
    // Add posts to suggestions
    if ($posts->have_posts()) {
        while ($posts->have_posts()) {
            $posts->the_post();
            $suggestions[] = array(
                'title' => get_the_title(),
                'url' => get_permalink(),
                'excerpt' => wp_trim_words(get_the_excerpt(), 10),
                'type' => 'post'
            );
        }
    }
    
    // Add debates to suggestions
    if ($debates->have_posts()) {
        while ($debates->have_posts()) {
            $debates->the_post();
            $suggestions[] = array(
                'title' => get_the_title(),
                'url' => get_permalink(),
                'excerpt' => wp_trim_words(get_the_excerpt(), 10),
                'type' => 'debate'
            );
        }
    }
    
    wp_reset_postdata();
    
    // Cache search results
    wp_cache_set($cache_key, $suggestions, 'nm_cache', 120);
    
    wp_send_json_success($suggestions);
}
add_action('wp_ajax_search_suggestions', 'ajax_search_suggestions');
add_action('wp_ajax_nopriv_search_suggestions', 'ajax_search_suggestions');

// Auto-moderation: check for spam patterns
function nm_auto_moderate_content($content, $author_id = 0) {
    $spam_patterns = array(
        '/\b(viagra|casino|poker|lottery|winner|congratulations)\b/i',
        '/\b(click here|visit now|buy now|limited time)\b/i',
        '/https?:\/\/[^\s]+\.(tk|ml|ga|cf)\b/i', // Suspicious domains
    );
    
    foreach ($spam_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return 'spam';
        }
    }
    
    // Check for excessive caps
    $caps_ratio = strlen(preg_replace('/[^A-Z]/', '', $content)) / max(1, strlen($content));
    if ($caps_ratio > 0.7 && strlen($content) > 20) {
        return 'excessive_caps';
    }
    
    // Check for repeated characters
    if (preg_match('/([a-zA-Z])\1{4,}/', $content)) {
        return 'repeated_chars';
    }
    
    return 'approved';
}

// Apply auto-moderation to comments
add_filter('preprocess_comment', function($commentdata) {
    $moderation_result = nm_auto_moderate_content($commentdata['comment_content'], $commentdata['user_id']);
    
    if ($moderation_result !== 'approved') {
        $commentdata['comment_approved'] = 0; // Hold for moderation
        
        // Log the auto-moderation action
        nm_flag_content(
            0, // Will be set after comment is created
            'comment',
            'Auto-moderated: ' . $moderation_result,
            0 // System flag
        );
    }
    
    return $commentdata;
});

// Remove admin bar for better mobile experience
function remove_admin_bar() {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar');

// Custom comment callback function
function lanota_2026_comment_callback($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    ?>
    <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <div class="comment">
            <div class="comment-author">
                <?php echo get_avatar($comment, 32); ?>
                <div>
                    <strong><?php comment_author(); ?></strong>
                    <div class="comment-meta">
                        <a href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>">
                            <?php printf('%1$s a las %2$s', get_comment_date(), get_comment_time()); ?>
                        </a>
                        <?php edit_comment_link('(Editar)', ' ', ''); ?>
                    </div>
                </div>
            </div>
            
            <div class="comment-content">
                <?php if ($comment->comment_approved == '0') : ?>
                    <em>Tu comentario está esperando moderación.</em>
                <?php endif; ?>
                <?php comment_text(); ?>
            </div>
            
            <div class="comment-reply">
                <?php comment_reply_link(array_merge($args, array(
                    'depth' => $depth,
                    'max_depth' => $args['max_depth']
                ))); ?>
            </div>
        </div>
    <?php
}

// Add theme support for block editor
function lanota_2026_theme_editor_support() {
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style('editor-style.css');
}
add_action('after_setup_theme', 'lanota_2026_theme_editor_support');

// Custom image sizes
function lanota_2026_custom_image_sizes() {
    add_image_size('featured-large', 800, 400, true);
    add_image_size('post-thumbnail', 400, 250, true);
    // Stories carousel: provide high-res crops for portrait and landscape to avoid pixelation
    // Portrait used on mobile (aspect ~9:16); Landscape on desktop (~16:9)
    add_image_size('featured-story-portrait', 1200, 1800, true);
    add_image_size('featured-story-landscape', 1600, 900, true);
}
add_action('after_setup_theme', 'lanota_2026_custom_image_sizes');

// Security enhancements
function lanota_2026_security_headers() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
add_action('send_headers', 'lanota_2026_security_headers');

// Performance optimizations
function lanota_2026_performance_optimizations() {
    // Remove unnecessary WordPress features
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Keep emojis enabled for debate functionality
    // remove_action('wp_head', 'print_emoji_detection_script', 7);
    // remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('init', 'lanota_2026_performance_optimizations');

// Register Taxonomy: Hashtag for debate topics (attach to CPT 'debate_topic')
function lanota_2026_register_debate_hashtags() {
    // Hashtag taxonomy
    $tlabels = array(
        'name' => 'Hashtags',
        'singular_name' => 'Hashtag',
        'search_items' => 'Buscar hashtags',
        'all_items' => 'Todos los hashtags',
        'edit_item' => 'Editar hashtag',
        'update_item' => 'Actualizar hashtag',
        'add_new_item' => 'Agregar nuevo hashtag',
        'new_item_name' => 'Nuevo hashtag',
        'menu_name' => 'Hashtags',
    );

    register_taxonomy('hashtag', array('debate_topic'), array(
        'hierarchical' => false,
        'labels' => $tlabels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'hashtag'),
        'show_in_rest' => true,
    ));
}
add_action('init', 'lanota_2026_register_debate_hashtags');

// AJAX: Create Debate (frontend editor)
function lanota_2026_create_debate() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Necesitás iniciar sesión'));
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }

    $content = isset($_POST['content']) ? wp_strip_all_tags(wp_unslash($_POST['content'])) : '';
    $link_url = isset($_POST['link_url']) ? esc_url_raw(wp_unslash($_POST['link_url'])) : '';

    $content = trim($content);
    if ($content === '' || mb_strlen($content) > 400) {
        wp_send_json_error(array('message' => 'El contenido es requerido (máx 400 caracteres)'));
    }

    // Determine status by role (Moderación A)
    $user = wp_get_current_user();
    $status = in_array('author', (array) $user->roles, true) || current_user_can('publish_posts') ? 'publish' : 'pending';

    // Ensure link is internal (optional)
    if ($link_url) {
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $link_host = wp_parse_url($link_url, PHP_URL_HOST);
        if (!$link_host || strtolower($link_host) !== strtolower($site_host)) {
            // If external, drop it for now
            $link_url = '';
        }
    }

    // Create post
    $post_id = wp_insert_post(array(
        'post_type' => 'debate_topic',
        'post_title' => wp_trim_words($content, 12, '…'),
        'post_content' => $content,
        'post_status' => $status,
        'post_author' => get_current_user_id(),
        'comment_status' => 'open',
    ));
    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => $post_id->get_error_message()));
    }

    if ($link_url) {
        update_post_meta($post_id, 'debate_link_url', $link_url);
    }

    // Extract hashtags
    $tags = array();
    if (preg_match_all('/#([\p{L}0-9_\-\.]{1,50})/u', $content, $m)) {
        $tags = array_unique(array_map('sanitize_title', $m[1]));
    }
    if (!empty($tags)) {
        wp_set_object_terms($post_id, $tags, 'hashtag', false);
    }

    wp_send_json_success(array('post_id' => $post_id, 'status' => $status));
}
add_action('wp_ajax_create_debate', 'lanota_2026_create_debate');
add_action('wp_ajax_nopriv_create_debate', function(){ wp_send_json_error(array('message' => 'Login requerido')); });

// AJAX: Create Reply (comment) on Debate
function lanota_2026_create_debate_reply() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Necesitás iniciar sesión'));
    }
    // Only subscribers (nm_forum_post) can post respuestas
    if (!current_user_can('nm_forum_post')) {
        wp_send_json_error(array('message' => 'Necesitás una suscripción activa para responder'));
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $content = isset($_POST['content']) ? wp_strip_all_tags(wp_unslash($_POST['content'])) : '';
    $content = trim($content);
    if (!$post_id || get_post_type($post_id) !== 'debate_topic') {
        wp_send_json_error(array('message' => 'Debate inválido'));
    }
    if ($content === '' || mb_strlen($content) > 240) {
        wp_send_json_error(array('message' => 'Respuesta inválida (máx 240 caracteres)'));
    }

    $commentdata = array(
        'comment_post_ID' => $post_id,
        'comment_content' => $content,
        'user_id' => get_current_user_id(),
        'comment_approved' => 1,
    );
    $cid = wp_insert_comment(wp_slash($commentdata));
    if (is_wp_error($cid) || !$cid) {
        wp_send_json_error(array('message' => 'No se pudo publicar la respuesta'));
    }
    // Mark as respuesta for custom listings
    add_comment_meta($cid, 'is_reply', 1, true);
    wp_send_json_success(array('comment_id' => $cid));
}
add_action('wp_ajax_create_debate_reply', 'lanota_2026_create_debate_reply');

// AJAX: Toggle like on a reply (comment)
function lanota_2026_toggle_reply_like() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Necesitás iniciar sesión'));
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
    if (!$comment_id || !get_comment($comment_id)) {
        wp_send_json_error(array('message' => 'Comentario inválido'));
    }
    $user_id = get_current_user_id();
    $liked = get_user_meta($user_id, 'nm_liked_replies', true);
    if (!is_array($liked)) $liked = array();
    $liked = array_map('intval', $liked);
    $is_liked = in_array($comment_id, $liked, true);

    $count = intval(get_comment_meta($comment_id, 'like_count', true));
    if ($is_liked) {
        // Unlike
        $liked = array_values(array_diff($liked, array($comment_id)));
        update_user_meta($user_id, 'nm_liked_replies', $liked);
        $count = max(0, $count - 1);
        update_comment_meta($comment_id, 'like_count', $count);
        wp_send_json_success(array('liked' => false, 'count' => $count));
    } else {
        // Like
        $liked[] = $comment_id;
        $liked = array_values(array_unique($liked));
        update_user_meta($user_id, 'nm_liked_replies', $liked);
        $count = $count + 1;
        update_comment_meta($comment_id, 'like_count', $count);
        wp_send_json_success(array('liked' => true, 'count' => $count));
    }
}
add_action('wp_ajax_nm_toggle_reply_like', 'lanota_2026_toggle_reply_like');

// AJAX: Load more debates (infinite scroll)
function lanota_2026_load_more_debates() {
    // Basic sanitize inputs
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $hashtag = isset($_POST['hashtag']) ? sanitize_title(wp_unslash($_POST['hashtag'])) : '';

    $args = array(
        'post_type' => 'debate_topic',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        'paged' => $page,
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

    // Cache debates query
    $cache_key = 'debates_' . md5(serialize($args));
    $debates = wp_cache_get($cache_key, 'nm_cache');
    if (false === $debates) {
        $debates = new WP_Query($args);
        wp_cache_set($cache_key, $debates, 'nm_cache', 300);
    }
    if ($debates->have_posts()) {
        ob_start();
        while ($debates->have_posts()) { $debates->the_post();
            ?>
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
                    $forum_url = function_exists('lanota_2026_get_forum_url') ? lanota_2026_get_forum_url() : home_url('/foro/');
                    if ($terms && !is_wp_error($terms)) {
                      foreach ($terms as $t) {
                        $url = add_query_arg('hashtag', $t->slug, $forum_url);
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
            <?php
        }
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }

    wp_send_json_success(array('html' => ''));
}
add_action('wp_ajax_nm_load_more_debates', 'lanota_2026_load_more_debates');

// AJAX: Report post
function lanota_2026_report_post() {
    if (!wp_verify_nonce($_POST['nonce'], 'lanota_2026_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || !get_post($post_id)) {
        wp_send_json_error(array('message' => 'Post inválido'));
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Debes estar logueado para denunciar'));
    }
    
    // Get existing reports
    $reports = get_post_meta($post_id, '_reports', true);
    if (!is_array($reports)) $reports = array();
    
    // Check if user already reported this post
    $user_reported = false;
    foreach ($reports as $report) {
        if ($report['user_id'] == $user_id) {
            $user_reported = true;
            break;
        }
    }
    
    if ($user_reported) {
        wp_send_json_error(array('message' => 'Ya has denunciado este post'));
    }
    
    // Add new report
    $reports[] = array(
        'user_id' => $user_id,
        'date' => current_time('mysql'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    );
    
    update_post_meta($post_id, '_reports', $reports);
    
    wp_send_json_success(array('message' => 'Tu denuncia fue registrada'));
}
add_action('wp_ajax_nm_report_post', 'lanota_2026_report_post');

// AJAX: Create new debate from existing post
function lanota_2026_create_debate_from_post() {
    if (!wp_verify_nonce($_POST['nonce'], 'lanota_2026_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    
    $parent_post_id = isset($_POST['parent_post_id']) ? intval($_POST['parent_post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    
    if (!$parent_post_id || !get_post($parent_post_id)) {
        wp_send_json_error(array('message' => 'Post padre inválido'));
    }
    
    if (empty($title) || empty($content)) {
        wp_send_json_error(array('message' => 'Título y contenido son requeridos'));
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Debes estar logueado'));
    }
    
    // Create new debate post
    $new_post_id = wp_insert_post(array(
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'debate_topic',
        'post_author' => $user_id
    ));
    
    if (is_wp_error($new_post_id)) {
        wp_send_json_error(array('message' => 'Error al crear el debate'));
    }
    
    // Add parent relationship
    update_post_meta($new_post_id, '_parent_debate', $parent_post_id);
    
    wp_send_json_success(array(
        'message' => 'Debate creado exitosamente',
        'post_id' => $new_post_id,
        'permalink' => get_permalink($new_post_id)
    ));
}
add_action('wp_ajax_nm_create_debate_from_post', 'lanota_2026_create_debate_from_post');

// ===== EN DEBATE SYSTEM =====
// Note: Using debate_topic post type (registered above) with megaphone icon

// Register Custom Taxonomy: debate_tags
function register_debate_tags_taxonomy() {
    $labels = array(
        'name'                       => 'Etiquetas de Debate',
        'singular_name'              => 'Etiqueta de Debate',
        'menu_name'                  => 'Etiquetas',
        'all_items'                  => 'Todas las Etiquetas',
        'parent_item'                => 'Etiqueta Padre',
        'parent_item_colon'          => 'Etiqueta Padre:',
        'new_item_name'              => 'Nueva Etiqueta',
        'add_new_item'               => 'Agregar Nueva Etiqueta',
        'edit_item'                  => 'Editar Etiqueta',
        'update_item'                => 'Actualizar Etiqueta',
        'view_item'                  => 'Ver Etiqueta',
        'separate_items_with_commas' => 'Separar etiquetas con comas',
        'add_or_remove_items'        => 'Agregar o remover etiquetas',
        'choose_from_most_used'      => 'Elegir de las más usadas',
        'popular_items'              => 'Etiquetas Populares',
        'search_items'               => 'Buscar Etiquetas',
        'not_found'                  => 'No encontrado',
    );
    
    $args = array(
        'labels'                     => $labels,
        'hierarchical'               => false,
        'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        'show_tagcloud'              => true,
        'show_in_rest'               => true,
        'rewrite'                    => array('slug' => 'tema'),
    );
    
    register_taxonomy('debate_tags', array('debates'), $args);
}
add_action('init', 'register_debate_tags_taxonomy', 0);

// Create default debate tags
function create_default_debate_tags() {
    $default_tags = array(
        'Tucumán' => 'Temas relacionados con la provincia de Tucumán',
        'Universidad' => 'Debates sobre educación universitaria',
        'Cultura Popular' => 'Expresiones culturales del pueblo',
        'Política' => 'Temas de política local y nacional',
        'Economía' => 'Asuntos económicos y laborales'
    );
    
    foreach ($default_tags as $tag_name => $description) {
        if (!term_exists($tag_name, 'debate_tags')) {
            wp_insert_term($tag_name, 'debate_tags', array(
                'description' => $description,
                'slug' => sanitize_title($tag_name)
            ));
        }
    }
}
add_action('init', 'create_default_debate_tags');

// Auto-assign tags based on content
function auto_assign_debate_tags($post_id) {
    if (get_post_type($post_id) !== 'debate_topic') return;
    
    $post = get_post($post_id);
    $content = strtolower($post->post_title . ' ' . $post->post_content);
    
    $tag_keywords = array(
        'Tucumán' => array('tucumán', 'tucuman', 'provincia', 'norte', 'noa'),
        'Universidad' => array('universidad', 'estudiante', 'unt', 'facultad', 'carrera', 'educación'),
        'Cultura Popular' => array('cultura', 'folklore', 'tradición', 'música', 'arte', 'popular'),
        'Política' => array('política', 'gobierno', 'elecciones', 'partido', 'candidato', 'votación'),
        'Economía' => array('economía', 'trabajo', 'empleo', 'salario', 'inflación', 'precio')
    );
    
    $assigned_tags = array();
    
    foreach ($tag_keywords as $tag_name => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($content, $keyword) !== false) {
                $assigned_tags[] = $tag_name;
                break;
            }
        }
    }
    
    if (!empty($assigned_tags)) {
        wp_set_post_terms($post_id, $assigned_tags, 'debate_tags');
    }
}
add_action('save_post', 'auto_assign_debate_tags');

// Add voting system
function add_debate_vote_meta_boxes() {
    add_meta_box(
        'debate_votes',
        'Votos del Debate',
        'debate_votes_meta_box_callback',
        'debates',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_debate_vote_meta_boxes');

function debate_votes_meta_box_callback($post) {
    $votes = get_post_meta($post->ID, '_debate_votes', true);
    $votes = $votes ? $votes : 0;
    echo '<p><strong>Total de votos:</strong> ' . $votes . '</p>';
    echo '<p><em>Los votos se gestionan desde el frontend.</em></p>';
}

// AJAX: Vote on debate
function ajax_vote_debate() {
    if (!wp_verify_nonce($_POST['nonce'], 'lanota_2026_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'debate_topic') {
        wp_send_json_error(array('message' => 'Debate inválido'));
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Debes estar logueado para votar'));
    }
    
    // Check if user already voted
    $user_votes = get_user_meta($user_id, '_debate_votes', true);
    if (!is_array($user_votes)) $user_votes = array();
    
    $has_voted = in_array($post_id, $user_votes);
    $current_votes = get_post_meta($post_id, '_debate_votes', true);
    $current_votes = $current_votes ? intval($current_votes) : 0;
    
    if ($has_voted) {
        // Remove vote
        $user_votes = array_values(array_diff($user_votes, array($post_id)));
        $current_votes = max(0, $current_votes - 1);
        update_user_meta($user_id, '_debate_votes', $user_votes);
        update_post_meta($post_id, '_debate_votes', $current_votes);
        wp_send_json_success(array('voted' => false, 'votes' => $current_votes));
    } else {
        // Add vote
        $user_votes[] = $post_id;
        $user_votes = array_values(array_unique($user_votes));
        update_user_meta($user_id, '_debate_votes', $user_votes);
        $current_votes = $current_votes + 1;
        update_post_meta($post_id, '_debate_votes', $current_votes);
        wp_send_json_success(array('voted' => true, 'votes' => $current_votes));
    }
}
add_action('wp_ajax_vote_debate', 'ajax_vote_debate');

// Get debate vote count
function get_debate_votes($post_id) {
    $votes = get_post_meta($post_id, '_debate_votes', true);
    return $votes ? intval($votes) : 0;
}

// Check if user voted on debate
function user_voted_debate($post_id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    $user_votes = get_user_meta($user_id, '_debate_votes', true);
    if (!is_array($user_votes)) return false;
    
    return in_array($post_id, $user_votes);
}

// AJAX: Create debate from news post
function ajax_create_debate_from_news() {
    if (!wp_verify_nonce($_POST['nonce'], 'lanota_2026_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'post') {
        wp_send_json_error(array('message' => 'Post inválido'));
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Debes estar logueado'));
    }
    
    // Check if debate already exists for this post
    $existing_debate = get_posts(array(
        'post_type' => 'debate_topic',
        'meta_key' => '_source_post',
        'meta_value' => $post_id,
        'posts_per_page' => 1
    ));
    
    if ($existing_debate) {
        wp_send_json_success(array(
            'exists' => true,
            'permalink' => get_permalink($existing_debate[0]->ID),
            'message' => 'Ya existe un debate para esta noticia'
        ));
        return;
    }
    
    // Get original post data
    $original_post = get_post($post_id);
    
    // Create new debate
    $debate_data = array(
        'post_title' => 'Debate: ' . $original_post->post_title,
        'post_content' => get_the_excerpt($post_id) ?: wp_trim_words($original_post->post_content, 50),
        'post_status' => 'publish',
        'post_type' => 'debate_topic',
        'post_author' => $user_id
    );
    
    $debate_id = wp_insert_post($debate_data);
    
    if (is_wp_error($debate_id)) {
        wp_send_json_error(array('message' => 'Error al crear el debate'));
    }
    
    // Link to source post
    update_post_meta($debate_id, '_source_post', $post_id);
    
    // Auto-assign tags
    auto_assign_debate_tags($debate_id);
    
    wp_send_json_success(array(
        'exists' => false,
        'debate_id' => $debate_id,
        'permalink' => get_permalink($debate_id),
        'message' => 'Debate creado exitosamente'
    ));
}
add_action('wp_ajax_create_debate_from_news', 'ajax_create_debate_from_news');

// AJAX: Load more debates
function ajax_load_more_debates() {
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'recent';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $tag = isset($_POST['tag']) ? sanitize_text_field($_POST['tag']) : '';
    
    $args = array(
        'post_type' => 'debate_topic',
        'posts_per_page' => 10,
        'paged' => $page,
        'post_status' => 'publish'
    );
    
    // Apply sorting
    switch ($sort) {
        case 'commented':
            $args['orderby'] = 'comment_count';
            $args['order'] = 'DESC';
            break;
        case 'voted':
            $args['meta_key'] = '_debate_votes';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        default: // recent
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }
    
    // Apply search
    if ($search) {
        $args['s'] = $search;
    }
    
    // Apply tag filter
    if ($tag && $tag !== 'all') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'debate_tags',
                'field' => 'slug',
                'terms' => $tag
            )
        );
    }
    
    $debates_query = new WP_Query($args);
    
    ob_start();
    if ($debates_query->have_posts()) {
        while ($debates_query->have_posts()) {
            $debates_query->the_post();
            get_template_part('template-parts/debate-card');
        }
    }
    $html = ob_get_clean();
    wp_reset_postdata();
    
    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $page < $debates_query->max_num_pages
    ));
}
add_action('wp_ajax_load_more_debates', 'ajax_load_more_debates');
add_action('wp_ajax_nopriv_load_more_debates', 'ajax_load_more_debates');

// AJAX: Create new debate with emoji and hashtag support
function ajax_submit_new_debate() {
    if (!wp_verify_nonce($_POST['nonce'], 'lanota_2026_nonce')) {
        wp_send_json_error(array('message' => 'Nonce inválido'));
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'Debes estar logueado'));
    }
    
    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    
    // Process content to preserve emojis and extract hashtags
    $content = nm_process_debate_content($content);
    $hashtags = nm_extract_hashtags($content);
    
    $tags = isset($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : array();
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    if (empty($title) || empty($content)) {
        wp_send_json_error(array('message' => 'Título y contenido son obligatorios'));
    }
    
    $debate_data = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_type' => 'debate_topic',
        'post_author' => $user_id
    );
    
    $debate_id = wp_insert_post($debate_data);
    
    if (is_wp_error($debate_id)) {
        wp_send_json_error(array('message' => 'Error al crear el debate'));
    }
    
    // Set hashtags as terms
    if (!empty($hashtags)) {
        wp_set_post_terms($debate_id, $hashtags, 'hashtag');
    }
    
    // Set additional tags
    if (!empty($tags)) {
        wp_set_post_terms($debate_id, $tags, 'debate_tags');
    }
    
    // Set parent if it's a child debate
    if ($parent_id) {
        update_post_meta($debate_id, '_parent_debate', $parent_id);
    }
    
    wp_send_json_success(array(
        'debate_id' => $debate_id,
        'permalink' => get_permalink($debate_id),
        'message' => 'Debate creado exitosamente'
    ));
}
add_action('wp_ajax_submit_new_debate', 'ajax_submit_new_debate');

// Process debate content to handle emojis and stickers
function nm_process_debate_content($content) {
    // Convert sticker placeholders to actual emojis
    $sticker_map = array(
        '[👍]' => '👍',
        '[😍]' => '😍', 
        '[🔥]' => '🔥',
        '[👏]' => '👏',
        '[🤔]' => '🤔',
        '[😱]' => '😱',
        '[🇦🇷]' => '🇦🇷',
        '[🧉]' => '🧉',
        '[💃]' => '💃',
        '[⚽]' => '⚽',
        '[🥩]' => '🥩',
        '[🥟]' => '🥟',
        '[📢]' => '📢',
        '[💬]' => '💬',
        '[💡]' => '💡',
        '[❓]' => '❓',
        '[❗]' => '❗',
        '[⚖️]' => '⚖️'
    );
    
    return str_replace(array_keys($sticker_map), array_values($sticker_map), $content);
}

// Extract hashtags from content
function nm_extract_hashtags($content) {
    preg_match_all('/#([a-zA-ZÀ-ÿ\u00f1\u00d10-9_]+)/', $content, $matches);
    return array_unique($matches[1]);
}

// AJAX: Get popular hashtags
function ajax_get_popular_hashtags() {
    $hashtags = get_terms(array(
        'taxonomy' => 'hashtag',
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => 15,
        'hide_empty' => true
    ));
    
    $result = array();
    if ($hashtags && !is_wp_error($hashtags)) {
        foreach ($hashtags as $hashtag) {
            $result[] = array(
                'name' => '#' . $hashtag->name,
                'count' => $hashtag->count
            );
        }
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_get_popular_hashtags', 'ajax_get_popular_hashtags');
add_action('wp_ajax_nopriv_get_popular_hashtags', 'ajax_get_popular_hashtags');

// Enqueue debates JavaScript
function enqueue_debates_scripts() {
    if (is_post_type_archive('debate_topic') || is_singular('debate_topic') || is_singular('post')) {
        wp_enqueue_script('debates-js', get_template_directory_uri() . '/js/debates.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_debates_scripts');

// Add gamification system
function init_user_badges_system() {
    // Check for "Sembrador de ideas" badge (10 debates created)
    add_action('save_post', function($post_id) {
        if (get_post_type($post_id) !== 'debate_topic') return;
        
        $author_id = get_post_field('post_author', $post_id);
        $user_debates = new WP_Query(array(
            'post_type' => 'debate_topic',
            'author' => $author_id,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        if ($user_debates->found_posts >= 10) {
            $badges = get_user_meta($author_id, '_user_badges', true);
            if (!is_array($badges)) $badges = array();
            
            if (!in_array('Sembrador de ideas', $badges)) {
                $badges[] = 'Sembrador de ideas';
                update_user_meta($author_id, '_user_badges', $badges);
            }
        }
    });
    
    // Check for "Fogonero" badge (debate with 50+ comments)
    add_action('comment_post', function($comment_id) {
        $comment = get_comment($comment_id);
        $post_id = $comment->comment_post_ID;
        
        if (get_post_type($post_id) !== 'debate_topic') return;
        
        $comment_count = get_comments_number($post_id);
        if ($comment_count >= 50) {
            $author_id = get_post_field('post_author', $post_id);
            $badges = get_user_meta($author_id, '_user_badges', true);
            if (!is_array($badges)) $badges = array();
            
            if (!in_array('Fogonero', $badges)) {
                $badges[] = 'Fogonero';
                update_user_meta($author_id, '_user_badges', $badges);
            }
        }
    });
    
    // Check for "Tejedor" badge (created child debate)
    add_action('save_post', function($post_id) {
        if (get_post_type($post_id) !== 'debate_topic') return;
        
        $parent_debate = get_post_meta($post_id, '_parent_debate', true);
        if ($parent_debate) {
            $author_id = get_post_field('post_author', $post_id);
            $badges = get_user_meta($author_id, '_user_badges', true);
            if (!is_array($badges)) $badges = array();
            
            if (!in_array('Tejedor', $badges)) {
                $badges[] = 'Tejedor';
                update_user_meta($author_id, '_user_badges', $badges);
            }
        }
    });
}
add_action('init', 'init_user_badges_system');

// Add community roles to user profile
function add_community_role_field($user) {
    ?>
    <h3>Rol Comunitario</h3>
    <table class="form-table">
        <tr>
            <th><label for="community_role">Rol en la Comunidad</label></th>
            <td>
                <select name="community_role" id="community_role">
                    <option value="">Ninguno</option>
                    <option value="Moderador Barrial" <?php selected(get_user_meta($user->ID, '_community_role', true), 'Moderador Barrial'); ?>>Moderador Barrial</option>
                    <option value="Referente" <?php selected(get_user_meta($user->ID, '_community_role', true), 'Referente'); ?>>Referente</option>
                    <option value="Estudiante" <?php selected(get_user_meta($user->ID, '_community_role', true), 'Estudiante'); ?>>Estudiante</option>
                    <option value="Docente" <?php selected(get_user_meta($user->ID, '_community_role', true), 'Docente'); ?>>Docente</option>
                    <option value="Trabajador" <?php selected(get_user_meta($user->ID, '_community_role', true), 'Trabajador'); ?>>Trabajador</option>
                    <option value="Organización" <?php selected(get_user_meta($user->ID, '_community_role', true), 'Organización'); ?>>Organización</option>
                </select>
                <p class="description">Selecciona el rol que representa al usuario en la comunidad.</p>
            </td>
        </tr>
        <tr>
            <th><label for="organization_verified">Organización Verificada</label></th>
            <td>
                <input type="checkbox" name="organization_verified" id="organization_verified" value="1" <?php checked(get_user_meta($user->ID, '_organization_verified', true), '1'); ?> />
                <label for="organization_verified">Marcar como organización verificada</label>
                <p class="description">Las organizaciones verificadas aparecen con insignia especial.</p>
            </td>
        </tr>
    </table>
    
    <h3>Insignias Obtenidas</h3>
    <table class="form-table">
        <tr>
            <th>Insignias Actuales</th>
            <td>
                <?php
                $badges = get_user_meta($user->ID, '_user_badges', true);
                if (is_array($badges) && !empty($badges)) {
                    foreach ($badges as $badge) {
                        echo '<span class="badge badge-' . sanitize_html_class($badge) . '">' . esc_html($badge) . '</span> ';
                    }
                } else {
                    echo '<em>Sin insignias aún</em>';
                }
                ?>
                <p class="description">Las insignias se otorgan automáticamente por actividad en debates.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_community_role_field');
add_action('edit_user_profile', 'add_community_role_field');

// Save community role field
function save_community_role_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;
    
    if (isset($_POST['community_role'])) {
        update_user_meta($user_id, '_community_role', sanitize_text_field($_POST['community_role']));
    }
    
    if (isset($_POST['organization_verified'])) {
        update_user_meta($user_id, '_organization_verified', '1');
    } else {
        delete_user_meta($user_id, '_organization_verified');
    }
}
add_action('personal_options_update', 'save_community_role_field');
add_action('edit_user_profile_update', 'save_community_role_field');
add_action('wp_ajax_nopriv_load_more_debates', 'lanota_2026_load_more_debates');

// AJAX: Toggle like on posts
function lanota_2026_toggle_like() {
    // Basic validation
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post'));
    }

    $do = isset($_POST['do']) ? sanitize_text_field($_POST['do']) : 'toggle';
    $key = 'likes_count';
    $count = intval(get_post_meta($post_id, $key, true));

    if ($do === 'like') {
        $count++;
        update_post_meta($post_id, $key, $count);
    } elseif ($do === 'unlike' && $count > 0) {
        $count--;
        update_post_meta($post_id, $key, $count);
    }

    wp_send_json_success(array('count' => $count));
}
add_action('wp_ajax_toggle_like', 'lanota_2026_toggle_like');
add_action('wp_ajax_nopriv_toggle_like', 'lanota_2026_toggle_like');

// Helper: get subscription/paywall URL from option with fallback
function lanota_2026_get_subscription_url() {
    $opt = get_option('lanota_2026_paywall_url');
    if ($opt && filter_var($opt, FILTER_VALIDATE_URL)) {
        return esc_url($opt);
    }
    return esc_url(home_url('/suscripcion/'));
}

// Helper: render social icons list based on Customizer options
function lanota_2026_render_social_links($class = 'social-row') {
    $links = array(
        'facebook'  => get_option('lanota_2026_facebook_url'),
        'twitter'   => get_option('lanota_2026_twitter_url'),
        'instagram' => get_option('lanota_2026_instagram_url'),
        'youtube'   => get_option('lanota_2026_youtube_url'),
    );
    echo '<div class="' . esc_attr($class) . '">';
    foreach ($links as $name => $url) {
        if ($url) {
            $icon = 'fa-globe';
            if ($name === 'facebook') $icon = 'fa-facebook-f';
            if ($name === 'twitter') $icon = 'fa-twitter';
            if ($name === 'instagram') $icon = 'fa-instagram';
            if ($name === 'youtube') $icon = 'fa-youtube';
            echo '<a class="social-link s-' . esc_attr($name) . '" href="' . esc_url($url) . '" target="_blank" rel="noopener nofollow" aria-label="' . esc_attr(ucfirst($name)) . '"><i class="fab ' . esc_attr($icon) . '"></i></a>';
        }
    }
    echo '</div>';
}

// ================= Agenda Cultural: CPT Evento =================
// Register CPT 'evento'
function nm_register_evento_cpt() {
    $labels = array(
        'name' => 'Agenda',
        'singular_name' => 'Evento',
        'add_new' => 'Nuevo evento',
        'add_new_item' => 'Agregar nuevo evento',
        'edit_item' => 'Editar evento',
        'new_item' => 'Nuevo evento',
        'view_item' => 'Ver evento',
        'search_items' => 'Buscar eventos',
        'not_found' => 'No se encontraron eventos',
        'menu_name' => 'Agenda Cultural',
    );

    register_post_type('evento', array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'agenda'),
        'supports' => array('title', 'editor', 'thumbnail', 'author'),
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'capability_type' => array('evento', 'eventos'),
        'map_meta_cap' => true,
    ));
}
add_action('init', 'nm_register_evento_cpt');

// Create role for cultural centers with limited caps
function nm_setup_agenda_role() {
    if (!get_role('gestor_agenda')) {
        add_role('gestor_agenda', 'Gestor Agenda', array('read' => true));
    }
    // Ensure caps exist for CPT
    $caps = array(
        'read',
        'edit_evento', 'read_evento', 'delete_evento',
        'edit_eventos', 'edit_others_eventos', 'publish_eventos', 'read_private_eventos', 'delete_eventos', 'delete_others_eventos', 'edit_published_eventos', 'delete_published_eventos'
    );
    foreach (array('administrator','editor','author','gestor_agenda') as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($caps as $c) { $role->add_cap($c); }
        }
    }
}
add_action('after_setup_theme', 'nm_setup_agenda_role');

// Meta boxes for Evento
function nm_evento_add_metaboxes() {
    add_meta_box('nm_evento_datos', 'Datos del evento', 'nm_evento_metabox_cb', 'evento', 'normal', 'high');
}
add_action('add_meta_boxes', 'nm_evento_add_metaboxes');

function nm_evento_metabox_cb($post) {
    wp_nonce_field('nm_evento_save', 'nm_evento_nonce');
    $vals = array(
        'fecha_inicio' => get_post_meta($post->ID, 'fecha_inicio', true),
        'fecha_fin'    => get_post_meta($post->ID, 'fecha_fin', true),
        'lugar'        => get_post_meta($post->ID, 'lugar', true),
        'direccion'    => get_post_meta($post->ID, 'direccion', true),
        'precio'       => get_post_meta($post->ID, 'precio', true),
        'enlace'       => get_post_meta($post->ID, 'enlace', true),
        'coordenadas'  => get_post_meta($post->ID, 'coordenadas', true),
    );
    ?>
    <style>.nm-evento-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.nm-evento-grid .full{grid-column:1/-1}</style>
    <div class="nm-evento-grid">
        <p>
            <label><strong>Fecha inicio</strong><br>
            <input type="date" name="fecha_inicio" value="<?php echo esc_attr($vals['fecha_inicio']); ?>"></label>
        </p>
        <p>
            <label><strong>Fecha fin</strong><br>
            <input type="date" name="fecha_fin" value="<?php echo esc_attr($vals['fecha_fin']); ?>"></label>
        </p>
        <p class="full">
            <label><strong>Lugar</strong><br>
            <input type="text" name="lugar" value="<?php echo esc_attr($vals['lugar']); ?>" class="widefat"></label>
        </p>
        <p class="full">
            <label><strong>Dirección</strong><br>
            <input type="text" name="direccion" value="<?php echo esc_attr($vals['direccion']); ?>" class="widefat"></label>
        </p>
        <p>
            <label><strong>Precio</strong><br>
            <input type="text" name="precio" value="<?php echo esc_attr($vals['precio']); ?>" class="widefat"></label>
        </p>
        <p>
            <label><strong>Enlace</strong><br>
            <input type="url" name="enlace" value="<?php echo esc_attr($vals['enlace']); ?>" class="widefat" placeholder="https://..."></label>
        </p>
        <p class="full">
            <label><strong>Coordenadas (lat,lng)</strong><br>
            <input type="text" name="coordenadas" value="<?php echo esc_attr($vals['coordenadas']); ?>" class="widefat" placeholder="-26.83,-65.22"></label>
        </p>
    </div>
    <?php
}

function nm_evento_save_post($post_id) {
    if (!isset($_POST['nm_evento_nonce']) || !wp_verify_nonce($_POST['nm_evento_nonce'], 'nm_evento_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $fields = array('fecha_inicio','fecha_fin','lugar','direccion','precio','enlace','coordenadas');
    foreach ($fields as $f) {
        $val = isset($_POST[$f]) ? sanitize_text_field(wp_unslash($_POST[$f])) : '';
        if ($f === 'enlace') $val = esc_url_raw($val);
        update_post_meta($post_id, $f, $val);
    }
}
add_action('save_post_evento', 'nm_evento_save_post');

// Archive filters: period=week|month|all or from/to (YYYY-MM-DD)
function nm_evento_archive_filter($query) {
    if (is_admin() || !$query->is_main_query()) return;
    if ($query->is_post_type_archive('evento')) {
        $today = current_time('Y-m-d');
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'week';
        $from = isset($_GET['from']) ? sanitize_text_field($_GET['from']) : '';
        $to   = isset($_GET['to'])   ? sanitize_text_field($_GET['to'])   : '';

        if ($period === 'all') {
            // Show all from today forward by default
            $from = $from ?: $today;
        } elseif ($period === 'month') {
            $first = date('Y-m-01', strtotime($today));
            $last  = date('Y-m-t', strtotime($today));
            $from = $from ?: $first; $to = $to ?: $last;
        } else { // week
            $ts = strtotime($today);
            $dow = date('N', $ts); // 1..7 (Mon..Sun)
            $monday = date('Y-m-d', strtotime('-' . ($dow-1) . ' days', $ts));
            $sunday = date('Y-m-d', strtotime('+' . (7-$dow) . ' days', $ts));
            $from = $from ?: $monday; $to = $to ?: $sunday;
        }

        // Intersect event [inicio, fin] with [from, to] if to provided.
        $meta = array('relation' => 'AND');
        if ($from) {
            $meta[] = array(
                'key' => 'fecha_fin',
                'value' => $from,
                'compare' => '>=',
                'type' => 'DATE',
            );
        }
        if ($to) {
            $meta[] = array(
                'key' => 'fecha_inicio',
                'value' => $to,
                'compare' => '<=',
                'type' => 'DATE',
            );
        }
        $query->set('meta_query', $meta);
        $query->set('meta_key', 'fecha_inicio');
        $query->set('orderby', array('meta_value' => 'ASC'));
        $query->set('posts_per_page', 18);
    }
}
add_action('pre_get_posts', 'nm_evento_archive_filter');

// Rate limiting for content creation
function nm_check_rate_limit($user_id, $action, $limit_per_hour = 5) {
    $key = 'nm_rate_limit_' . $action . '_' . $user_id;
    $attempts = get_transient($key);
    
    if ($attempts === false) {
        set_transient($key, 1, HOUR_IN_SECONDS);
        return true;
    }
    
    if ($attempts >= $limit_per_hour) {
        return false;
    }
    
    set_transient($key, $attempts + 1, HOUR_IN_SECONDS);
    return true;
}

// Apply rate limiting to debate creation
add_action('wp_ajax_nm_create_debate_topic', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    if (!nm_check_rate_limit(get_current_user_id(), 'create_debate', 3)) {
        wp_send_json_error(__('Has alcanzado el límite de debates por hora. Intenta más tarde.', 'lanota-theme-2026'));
    }
    
    // Continue with normal debate creation logic...
}, 5); // High priority to run before other handlers

// ---- Unified Mi Cuenta Button Helper ----
if (!function_exists('nm_render_mi_cuenta_button')) {
    function nm_render_mi_cuenta_button($class = 'account-btn') {
        $account_url = function_exists('lanota_2026_get_account_url') ? lanota_2026_get_account_url() : home_url('/mi-cuenta/');
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $avatar_url = get_avatar_url($user->ID, array('size' => 32));
            echo '<a class="' . esc_attr($class) . ' theme-aware-btn" href="' . esc_url($account_url) . '">';
            echo '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($user->display_name) . '" class="account-avatar">';
            echo '<span>' . esc_html__('Mi cuenta', 'lanota-theme-2026') . '</span>';
            echo '</a>';
        } else {
            $login_url = function_exists('nm_get_login_url') ? nm_get_login_url($account_url) : wp_login_url($account_url);
            echo '<a class="' . esc_attr($class) . ' theme-aware-btn" href="' . esc_url($login_url) . '">';
            echo '<i class="fas fa-sign-in-alt"></i>';
            echo '<span>' . esc_html__('Mi cuenta', 'lanota-theme-2026') . '</span>';
            echo '</a>';
        }
    }
}

// ===== Migrate options from old theme (news_media_*) =====
function lanota_2026_migrate_old_options() {
    if (get_option('lanota_2026_options_migrated')) return;

    $map = array(
        'news_media_paywall_url'   => 'lanota_2026_paywall_url',
        'news_media_facebook_url'  => 'lanota_2026_facebook_url',
        'news_media_twitter_url'   => 'lanota_2026_twitter_url',
        'news_media_instagram_url' => 'lanota_2026_instagram_url',
        'news_media_youtube_url'   => 'lanota_2026_youtube_url',
        'news_media_tiktok_url'    => 'lanota_2026_tiktok_url',
        'news_media_logo_default'  => 'lanota_2026_logo_default',
        'news_media_logo_dark'     => 'lanota_2026_logo_dark',
        'news_media_primary_color' => 'lanota_2026_primary_color',
    );

    foreach ($map as $old => $new) {
        $val = get_option($old);
        if ($val && !get_option($new)) {
            update_option($new, $val);
        }
    }

    update_option('lanota_2026_options_migrated', true);
}
add_action('after_setup_theme', 'lanota_2026_migrate_old_options');

// ===== Subscribe with Google (SwG) — disable auto-popup =====

// Remove the Reader Revenue Manager plugin's auto-popup script.
function nm_swg_disable_auto_prompt() {
    wp_dequeue_script('google_swgjs');
    wp_deregister_script('google_swgjs');
}
add_action('wp_enqueue_scripts', 'nm_swg_disable_auto_prompt', 999);

// Load SWG Basic (same library the RRM plugin uses) with auto-prompt disabled.
function nm_swg_manual_init() {
    ?>
    <script async
            src="https://news.google.com/swg/js/v1/swg-basic.js"></script>
    <script>
    var _nmSwgBasic = null;
    (self.SWG_BASIC = self.SWG_BASIC || []).push(function(basicSubscriptions) {
        basicSubscriptions.init({
            type: "NewsArticle",
            isPartOfType: ["Product"],
            isPartOfProductId: "CAoiEGy6YkUcqDvzWHARFduvqcQ:openaccess",
            autoPromptType: "none",
            clientOptions: {theme: "light", lang: "es-AR"}
        });
        _nmSwgBasic = basicSubscriptions;
    });
    </script>
    <?php
}
add_action('wp_head', 'nm_swg_manual_init', 99);

// Open SWG subscription prompt when clicking any .subscribe-btn
function nm_swg_subscribe_click_handler() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.subscribe-btn, .btn-subscribe-swg').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (_nmSwgBasic) {
                    _nmSwgBasic.setupAndShowAutoPrompt({
                        autoPromptType: 'subscription_large',
                        alwaysShow: true
                    });
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'nm_swg_subscribe_click_handler', 99);
