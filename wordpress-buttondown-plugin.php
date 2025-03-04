<?php
/**
 * Plugin Name: WP Buttondown (Beta)
 * Description: Create subscriber-only content on your Wordpress site for your Buttondown list subscribers.
 * Version: 0.1.0   
 * Author: Scott Andrew LePera
 * Author URI: https://scottandrew.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('WP_BUTTONDOWN_COOKIE_EXPIRY', 60 * 60 * 24 * 30 * 6);
define('WP_BUTTONDOWN_IS_SECURE', !empty($_SERVER['HTTPS']));

include 'utils.php';
include 'settings.php';

function wp_buttondown_query_vars( $qvars ) {
    $qvars[] = 'wp_btndwn_r';
    return $qvars;
}
add_filter( 'query_vars', 'wp_buttondown_query_vars' );

function get_buttondown_subscription_status() {

    $s = wp_buttondown_get_settings();

    $is_regular = isset( $_COOKIE[$s['regular_cookie']] );
    $is_premium = isset( $_COOKIE[$s['premium_cookie']] );

    return array(
        'is_regular' => $is_regular,
        'is_premium' => $is_premium
    );
}

function fetch_api_data($email = '') {

    if (empty($email)) {
        wp_buttondown_log('no email');
        return array('error' => true);
    }

    $s = wp_buttondown_get_settings();

    $api_url = 'https://api.buttondown.com/v1/subscribers/' . urlencode($email);

    $api_token = wp_buttondown_decrypt_token($s['api_token']);
    
    $args = array(
        'headers' => array(
            'Authorization' => $api_token,
            'Accept' => 'application/json',
        )
    );

    $response = wp_remote_get($api_url, $args);

    wp_buttondown_log($response);
    
    if ( is_wp_error( $response ) ) {
        wp_buttondown_log('is_wp_error');
        return array('error' => true);
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( !isset( $data['subscriber_type'] ) ) {
        wp_buttondown_log('no subscriber type');
        return array('nosub' => true);
    }

    $is_regular = isset( $data['subscriber_type'] ) && ( $data['subscriber_type'] == 'regular');

    $is_premium = isset( $data['subscriber_type'] ) && ( $data['subscriber_type'] == 'premium' || $data['subscriber_type'] == 'gifted');

    return array(
        'is_regular' => $is_regular,
        'is_premium' => $is_premium
    );
}   

// Register REST API route
function register_custom_api_endpoint() {
    register_rest_route('buttondown/v1', '/lookup/', array(
        'methods' => 'POST',
        'callback' => 'handle_wp_buttondown_request',
        'permission_callback' => function ($request) {
            $origin = get_http_origin();
            $expected_origin = 'http' . (WP_BUTTONDOWN_IS_SECURE ? 's' : '') . '://' . $_SERVER['SERVER_NAME'];
            $nonce = $request->get_param('_wpnonce');
            return (
                $origin === $expected_origin &&
                wp_verify_nonce($nonce, 'wp_rest')
            );
        }
    ));
}
add_action('rest_api_init', 'register_custom_api_endpoint');

function wp_buttondown_handle_redirect($route, $return_to = false) {
    $dest = $route . ((($return_to) && (is_numeric($return_to))) ? "?wp_btndwn_r=$return_to" : '');
    wp_redirect($dest);
    exit();
}

function handle_wp_buttondown_request($request) {

    $s = wp_buttondown_get_settings();

    $email = sanitize_email($request->get_param('email'));
    $return_to = $request->get_param('return_to');
    
    if (empty($email)) {
        wp_buttondown_handle_redirect($s['error'], $return_to);
    }
    
    $result = fetch_api_data($email);

    if ( isset( $result["error"] )) {
        wp_buttondown_handle_redirect($s['error'], $return_to);
    }

    if ( isset( $result["nosub"] )) {
        wp_buttondown_handle_redirect($s['nosub'], $return_to);
    }

    $expires = time() + WP_BUTTONDOWN_COOKIE_EXPIRY; // six months

    if ( $result['is_regular'] || $result['is_premium'] ) {
        setcookie($s['regular_cookie'], true, [
            'expires' => $expires,
            'path' => '/',
            'secure' => WP_BUTTONDOWN_IS_SECURE,
            'httponly' => WP_BUTTONDOWN_IS_SECURE,
            'samesite' => 'Strict'
        ]);
    }

    if ( $result['is_premium'] ) {
        setcookie($s['premium_cookie'], true, [
            'expires' => $expires,
            'path' => '/',
            'secure' => WP_BUTTONDOWN_IS_SECURE,
            'httponly' => WP_BUTTONDOWN_IS_SECURE,
            'samesite' => 'Strict'
        ]);
    }
    
    wp_buttondown_handle_redirect($s['success'], $return_to);
}

function do_wp_buttondown_regular_shortcode($atts, $content = null) {

    $status = get_buttondown_subscription_status();

    if ( $status['is_regular'] ) {
        return do_shortcode($content);
    } else {
        return do_wp_buttondown_login_message();
    }
}

function do_wp_buttondown_premium_shortcode($atts, $content = null) {
    $status = get_buttondown_subscription_status();

    if ( $status['is_premium'] ) {
        return do_shortcode($content);
    } else {
        return do_wp_buttondown_login_message(true);
    }
}

function do_wp_buttondown_login_message($isPremium = false) {
    $s = wp_buttondown_get_settings();
    $subscriber_status = ($isPremium == true) ? 'premium' : '';
    $return_to = get_the_ID();
    ob_start();
    ?>
    <div id="wp-buttondown-notice" class="wp-buttondown-login">
        <p>This content is for <?= $subscriber_status ?> subscribers only! <a href="<?= $s['login'] ?>?wp_btndwn_r=<?= $return_to ?>">Log in.</a></p>
    </div>
    <?php
    return ob_get_clean();
}

function do_wp_buttondown_success_message() {
    $return_to = get_query_var('wp_btndwn_r');
    $status = get_buttondown_subscription_status();
    ob_start();
    ?>
    <div id="wp-buttondown-notice" class="wp-buttondown-success">
        <?php if ($status['is_regular'] || $status['is_premium']) { ?>
            <p>Confirmed! You’re a subscriber!</p>
            <p>You now have access to free subscriber-only content.</p>
        <?php } ?>
        <?php if ($status['is_premium']) { ?>
            <p>You also now have access to premium subscriber-only content.</p>
        <?php } ?>
        <?php if (!empty($return_to)) { ?>
            <p><a href="<?= get_permalink($return_to) ?>">Continue</a></p>
        <?php } ?>
    </div>
    <?php
    return ob_get_clean();
}

function wp_buttondown_recognized_message($return_to) {
    ob_start();
    ?>
    <div id="wp-buttondown-notice" class="wp-buttondown-recognized">
        <p>You're already logged in.</p>
        <?php if (!empty($return_to)) { ?>
            <p><a href="<?= get_permalink($return_to) ?>">Go back</a></p>
        <?php } ?>
    </div>
    <?php
    return ob_get_clean();
}

function wp_buttondown_is_logged_in() {
    $status = get_buttondown_subscription_status();
    $is_logged_in = ($status['is_regular'] || $status['is_premium']);
    return $is_logged_in;
}

function wp_buttondown_render_login_form($return_to) {
    ob_start();
    ?>
    <form class="wp-buttondown-check" method="post" action="/wp-json/buttondown/v1/lookup">
        <p>Enter your email to confirm your subscription.<br />Your email will not be recorded.</p>
        <input type="email" name="email" required />
        <?php if (!empty($return_to)) { ?>
            <input type="hidden" name="return_to" value="<?= $return_to ?>" />
        <?php } 
        wp_nonce_field('wp_rest');
        ?>
        <button>Submit</button>
    </form>
    <?php
    $s = wp_buttondown_get_settings();
    $subscribe_page = $s['subscribe_page'];
    if ( isset($subscribe_page) && $subscribe_page != '' ) {
        echo( "<p>Not a subscriber? <a href=\"$subscribe_page\">Subscribe here!</a></p>" );
    }
    return ob_get_clean();
}

function do_wp_buttondown_login_form() {
    $return_to = get_query_var('wp_btndwn_r');
    if (wp_buttondown_is_logged_in()) {
        return wp_buttondown_recognized_message($return_to);
    } else {
        return wp_buttondown_render_login_form($return_to);
    }
    
}

add_shortcode('wp_buttondown_regular', 'do_wp_buttondown_regular_shortcode');
add_shortcode('wp_buttondown_premium', 'do_wp_buttondown_premium_shortcode');
add_shortcode('wp_buttondown_login_form', 'do_wp_buttondown_login_form');
add_shortcode('wp_buttondown_success_message', 'do_wp_buttondown_success_message');

?>
