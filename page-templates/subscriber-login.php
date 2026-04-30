<?php
/*
Template Name: Subscriber Login
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

// If already logged in, send to profile or redirect_to
if ( is_user_logged_in() ) {
    $redirect = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : admin_url('profile.php');
    wp_safe_redirect( $redirect );
    exit;
}

$login_error = '';
$site_key = get_option('nm_recaptcha_site_key');
$secret_key = get_option('nm_recaptcha_secret_key');
$lock_msg = '';

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['nm_login_nonce']) && wp_verify_nonce( $_POST['nm_login_nonce'], 'nm_login' ) ) {
    // Honeypot (should be empty)
    $hp = trim( (string) ($_POST['nm_hp'] ?? '') );
    if ( $hp !== '' ) {
        $login_error = __('Error de validación. Intenta nuevamente.', 'lanota-theme-2026');
    }

    // Minimum time on page (2s) to deter bots
    $ts = isset($_POST['nm_ts']) ? intval($_POST['nm_ts']) : 0;
    if ( empty($login_error) && ( time() - $ts < 2 ) ) {
        $login_error = __('Por favor, intenta de nuevo.', 'lanota-theme-2026');
    }

    // reCAPTCHA v2 verification (if configured)
    if ( empty($login_error) && !empty($secret_key) ) {
        $g_resp = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
        if ( empty($g_resp) ) {
            $login_error = __('Por favor, completa el reCAPTCHA.', 'lanota-theme-2026');
        } else {
            $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
                'timeout' => 10,
                'body' => array(
                    'secret' => $secret_key,
                    'response' => $g_resp,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                )
            ));
            if ( is_wp_error($resp) ) {
                $login_error = __('No se pudo verificar el reCAPTCHA. Intenta de nuevo.', 'lanota-theme-2026');
            } else {
                $body = json_decode( wp_remote_retrieve_body($resp), true );
                if ( empty($body['success']) ) {
                    $login_error = __('reCAPTCHA inválido. Intenta nuevamente.', 'lanota-theme-2026');
                }
            }
        }
    }
    $creds = array(
        'user_login'    => sanitize_text_field( $_POST['log'] ?? '' ),
        'user_password' => $_POST['pwd'] ?? '',
        'remember'      => ! empty( $_POST['rememberme'] ),
    );
    // Rate limit check (IP + user)
    $remain = 0;
    if ( empty($login_error) ) {
        $remain = function_exists('nm_rate_limit_should_block') ? nm_rate_limit_should_block($creds['user_login']) : 0;
        if ( $remain > 0 ) {
            $mins = ceil($remain / 60);
            $lock_msg = sprintf( __('Demasiados intentos. Intenta nuevamente en %d min.', 'lanota-theme-2026'), $mins );
            $login_error = $lock_msg;
            if ( function_exists('nm_log_auth_attempt') ) nm_log_auth_attempt('blocked', $creds['user_login']);
        }
    }

    if ( empty($login_error) ) {
        $user = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $user ) ) {
            if ( function_exists('nm_rate_limit_register_failure') ) nm_rate_limit_register_failure($creds['user_login']);
            if ( function_exists('nm_log_auth_attempt') ) nm_log_auth_attempt('fail', $creds['user_login']);
            $login_error = __('Credenciales inválidas. Verifica tus datos.', 'lanota-theme-2026');
        } else {
            if ( function_exists('nm_rate_limit_register_success') ) nm_rate_limit_register_success($creds['user_login']);
            if ( function_exists('nm_log_auth_attempt') ) nm_log_auth_attempt('success', $creds['user_login']);
            $redirect = isset($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : home_url('/');
            wp_safe_redirect( $redirect );
            exit;
        }
    }
}

get_header();
?>

<main class="main-content">
  <section class="subscriber-login-wrapper">
    <div class="subscriber-login-card" role="form" aria-labelledby="subscriber-login-title">
      <div class="login-header">
        <h1 id="subscriber-login-title">Ingresar</h1>
        <p class="login-subtitle">Accedé con tu cuenta de suscriptor</p>
      </div>

      <?php if ( ! empty( $login_error ) ) : ?>
        <div class="login-error" role="alert" aria-live="polite"><?php echo wp_kses_post( $login_error ); ?></div>
      <?php endif; ?>

      <form method="post" action="<?php echo esc_url( get_permalink() ); ?>">
        <label class="login-field">
          <span>Usuario o Email</span>
          <input type="text" name="log" autocomplete="username" required />
        </label>

        <label class="login-field">
          <span>Contraseña</span>
          <input type="password" name="pwd" autocomplete="current-password" required />
        </label>

        <label class="remember-field">
          <input type="checkbox" name="rememberme" value="forever" />
          <span>Recordarme</span>
        </label>

        <?php wp_nonce_field( 'nm_login', 'nm_login_nonce' ); ?>
        <input type="text" name="nm_hp" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true" />
        <input type="hidden" name="nm_ts" value="<?php echo esc_attr( time() ); ?>" />
        <?php if ( ! empty($site_key) ) : ?>
          <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
          <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <?php endif; ?>
        <?php if ( isset($_GET['redirect_to']) ) : ?>
          <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $_GET['redirect_to'] ); ?>" />
        <?php endif; ?>

        <button type="submit" class="login-submit">
          <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
          Ingresar
        </button>
      </form>

      <div class="login-links">
        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">¿Olvidaste tu contraseña?</a>
        <a href="<?php echo esc_url( home_url('/') ); ?>">Volver al inicio</a>
      </div>
    </div>
  </section>
</main>

<?php get_footer(); ?>
