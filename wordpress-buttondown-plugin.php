<?php
/**
 * Plugin Name: Wordpress Buttondown test plugin
 * Description: Allows conditional content based on Buttondown subscription status.
 * Version: 0.1.0
 * Author: Scott Andrew LePera scottandrew.com
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include 'config.php';

function get_buttondown_subscription_status() {
    global $key_is_regular;
    global $key_is_premium;

    $is_regular = isset( $_COOKIE[$key_is_regular] );
    $is_premium = isset( $_COOKIE[$key_is_premium] );

    return array(
        'is_regular' => $is_regular,
        'is_premium' => $is_premium
    );
}

function fetch_api_data($email = '') {

    if (empty($email)) {
        return array('error' => true);
    }

    global $api_key;
    global $key_is_regular;
    global $key_is_premium;
    
    $api_url = 'https://api.buttondown.com/v1/subscribers/' . urlencode($email);
    
    $args = array(
        'headers' => array(
            'Authorization' => $api_key,
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
    global $routes;
    global $key_is_regular;
    global $key_is_premium;

    $email = sanitize_email($request->get_param('email'));
    
    if (empty($email)) {
        wp_redirect($routes['error']);
        exit();
    }
    
    $result = fetch_api_data($email);

    if ( isset( $result["error"] )) {
        wp_redirect($routes['error']);
        exit();
    }

    if ( isset( $result["nosub"] )) {
        wp_redirect($routes['no-subscription']);
        exit();
    }

    $expires = time() + 60 * 60 * 24 * 30; // one month

    if ( $result['is_regular'] ) {
        setcookie($key_is_regular, true, $expires, '/');
    }

    if ( $result['is_premium'] ) {
        setcookie($key_is_premium, true, $expires, '/');
    }
    
    wp_redirect($routes['success']);
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
    global $routes;
    $subscriber_status = ($isPremium == true) ? 'premium' : '';
    ob_start();
    ?>
    <div id="wp-buttondown-notice">
        <p>This content is for <?= $subscriber_status ?> subscribers only!<br /><a href="<?= $routes['login'] ?>">Log in here.</a></p>
    </div>
    <?php
    return ob_get_clean();
}

function do_wp_buttondown_check_form() {
    ob_start();
    ?>
    <form class="wp-buttondown-check" method="post" action="/wp-json/buttondown/v1/lookup">
        <p>Enter your email to confirm your subscription.<br />Your email will not be recorded.</p>
        <input type="email" name="email" required />
        <button>Submit</button>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('wp_buttondown_regular', 'do_wp_buttondown_regular_shortcode');
add_shortcode('wp_buttondown_premium', 'do_wp_buttondown_premium_shortcode');
add_shortcode('wp_buttondown_check_form', 'do_wp_buttondown_check_form');

?>
