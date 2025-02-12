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

include 'utils.php';
include 'settings.php';

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
    
    if ( is_wp_error( $response ) ) {
        return array('error' => true);
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( !isset( $data['subscriber_type'] ) ) {
        return array('nosub' => true);
    }

    $is_regular = isset( $data['subscriber_type'] ) && ( $data['subscriber_type'] == 'regular');

    $is_premium = isset( $data['subscriber_type'] ) && ( $data['subscriber_type'] == 'premium' || ['subscriber_type'] == 'gifted');

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
        'permission_callback' => function () {
            $origin = get_http_origin();
            $expected_origin = 'https://' . $_SERVER['SERVER_NAME'];
            if ( $origin === $expected_origin ) {
                return true;
            }
            return false;
        }
    ));
}
add_action('rest_api_init', 'register_custom_api_endpoint');

function handle_wp_buttondown_request($request) {

    $s = wp_buttondown_get_settings();

    $email = sanitize_email($request->get_param('email'));
    
    if (empty($email)) {
        wp_redirect($s['error']);
        exit();
    }

    if (!isset($_POST['wp_buttondown_nonce']) || !wp_verify_nonce($_POST['wp_buttondown_nonce'])) {
        wp_redirect($s['error']);
        exit();
    }
    
    $result = fetch_api_data($email);

    if ( isset( $result["error"] )) {
        wp_redirect($s['error']);
        exit();
    }

    if ( isset( $result["nosub"] )) {
        wp_redirect($s['nosub']);
        exit();
    }

    $expires = time() + 60 * 60 * 24 * 30 * 6; // six months

    if ( $result['is_regular'] || $result['is_premium'] ) {
        setcookie($s['regular_cookie'], true, $expires, '/');
    }

    if ( $result['is_premium'] ) {
        setcookie($key_is_premium, true, $expires, '/');
    }
    
    wp_redirect($s['success']);
    exit();
}

// Create shortcodes to conditionally display content
function do_wp_buttondown_regular_shortcode($atts, $content = null) {

    $status = get_buttondown_subscription_status();

    if ( $status['is_regular'] ) {
        return do_shortcode($content);
    } else {
        return do_wp_buttondown_notice();
    }
}

function do_wp_buttondown_premium_shortcode($atts, $content = null) {
    $status = get_buttondown_subscription_status();

    if ( $status['is_premium'] ) {
        return do_shortcode($content);
    } else {
        return do_wp_buttondown_notice(true);
    }
}

function do_wp_buttondown_notice($isPremium = false) {
    $s = wp_buttondown_get_settings();
    $subscriber_status = ($isPremium == true) ? 'premium' : '';
    ob_start();
    ?>
    <div id="wp-buttondown-notice">
        <p>This content is for <?= $subscriber_status ?> subscribers only!<br /><a href="<?= $s['login'] ?>">Log in here.</a></p>
    </div>
    <?php
    return ob_get_clean();
}

function do_wp_buttondown_check_form() {
    ob_start();
    ?>
    <form class="wp-buttondown-check" method="post" action="/wp-json/buttondown/v1/lookup">
        <p>Enter your email to confirm your subscription.<br />Your email will not be recorded.</p>
        <?php wp_nonce_field(-1, 'wp_buttondown_nonce'); ?>
        <input type="email" name="email" required />
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

add_shortcode('wp_buttondown_regular', 'do_wp_buttondown_regular_shortcode');
add_shortcode('wp_buttondown_premium', 'do_wp_buttondown_premium_shortcode');
add_shortcode('wp_buttondown_check_form', 'do_wp_buttondown_check_form');

?>
