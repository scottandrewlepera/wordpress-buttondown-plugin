<?php

function wp_buttondown_get_settings() {
  return get_option('wp_buttondown_settings');
}

function wp_buttondown_encrypt_token($token) {
  $key = AUTH_KEY;
  $salt = SECURE_AUTH_SALT;
  $iv = substr(hash('sha256', $salt), 0, 16);
  return base64_encode(openssl_encrypt($token, 'aes-256-cbc', $key, 0, $iv));
}

function wp_buttondown_decrypt_token($encrypted_token) {
  $key = AUTH_KEY;
  $salt = SECURE_AUTH_SALT;
  $iv = substr(hash('sha256', $salt), 0, 16);
  return openssl_decrypt(base64_decode($encrypted_token), 'aes-256-cbc', $key, 0, $iv);
}

function wp_buttondown_log( $data ) {
  if ( true === WP_DEBUG ) {
      if ( is_array( $data ) || is_object( $data ) ) {
          error_log( 'wp_buttondown' . print_r( $data, true ) );
      } else {
          error_log( 'wp_buttondown' . $data );
      }
  }
}

?>