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

    $is_regular = $_COOKIE[$key_is_regular];
    $is_premium = $_COOKIE[$key_is_premium];

    return array(
        'is_regular' => $is_regular,
        'is_premium' => $is_premium
    );
}

function fetch_api_data($email = '') {

    if (empty($email)) {
        return;
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
        return;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( !isset( $data['subscriber_type'] ) ) {
        return false;
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
        'callback' => 'handle_buttondown_request',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'register_custom_api_endpoint');

function handle_buttondown_request($request) {
    global $routes;
    global $key_is_regular;
    global $key_is_premium;

    $email = sanitize_email($request->get_param('email'));
    
    if (empty($email)) {
        return new WP_Error('missing_email', 'Email parameter is required', array('status' => 400));
    }
    
    $result = fetch_api_data($email);

    if ( !$result ) {
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
function do_buttondown_regular_shortcode($atts, $content = null) {

    $status = get_buttondown_subscription_status();

    if ( $status['is_regular'] ) {
        return do_shortcode($content);
    }
    return '';
}

function do_buttondown_premium_shortcode($atts, $content = null) {
    $status = get_buttondown_subscription_status();

    if ( $status['is_premium'] ) {
        return do_shortcode($content);
    }
    return '';
}

add_shortcode('buttondown_regular', 'do_buttondown_regular_shortcode');
add_shortcode('buttondown_premium', 'do_buttondown_premium_shortcode');

?>
