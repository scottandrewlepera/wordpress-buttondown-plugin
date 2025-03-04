<?php

define('WP_BUTTONDOWN_CIPHER', 'aes-256-cbc');
define('WP_BUTTONDOWN_IV_LENGTH', 16);

function wp_buttondown_get_settings() {
  $settings = get_option('wp_buttondown_settings');
  if (!is_array($settings)) {
      wp_buttondown_log('Invalid settings format');
      return array();
  }
  return $settings;
}

function wp_buttondown_encrypt_token($token) {
  if (empty($token)) {
      return '';
  }

  $key = wp_buttondown_get_encryption_key();

  // Generate a random initialization vector
  $iv = openssl_random_pseudo_bytes(WP_BUTTONDOWN_IV_LENGTH);
  
  // Encrypt the token
  $encrypted = openssl_encrypt($token, WP_BUTTONDOWN_CIPHER, $key, 0, $iv);
  
  if ($encrypted === false) {
      wp_buttondown_log('Encryption failed');
      return '';
  }

  // Return base64-encoded IV + encrypted data
  return base64_encode($iv . $encrypted);
}


function wp_buttondown_decrypt_token($encryptedToken) {
  if (empty($encryptedToken)) {
      return '';
  }

  $key = wp_buttondown_get_encryption_key();

  // Decode base64-encoded string
  $data = base64_decode($encryptedToken);
  
  if ($data === false || strlen($data) < WP_BUTTONDOWN_IV_LENGTH) {
      wp_buttondown_log('Invalid encrypted data');
      return '';
  }

  // Extract IV and encrypted token
  $iv = substr($data, 0, WP_BUTTONDOWN_IV_LENGTH);
  $encryptedData = substr($data, WP_BUTTONDOWN_IV_LENGTH);

  // Decrypt
  $decrypted = openssl_decrypt($encryptedData, WP_BUTTONDOWN_CIPHER, $key, 0, $iv);
  
  if ($decrypted === false) {
      wp_buttondown_log('Decryption failed');
      return '';
  }

  return $decrypted;
}

function wp_buttondown_log( $data ) {
  if ( true === WP_DEBUG ) {
      if ( is_array( $data ) || is_object( $data ) ) {
          error_log( 'wp_buttondown: ' . print_r( $data, true ) );
      } else {
          error_log( 'wp_buttondown: ' . $data );
      }
  }
}

function wp_buttondown_get_encryption_key() {
  $key_material = implode('|', [
      gethostname(),
      realpath(ABSPATH . 'wp-config.php'),
      get_option('siteurl'),
      get_option('admin_email'),
  ]);
  // return key is 32 bytes (256-bit) for AES-256
  return hash('sha256', $key_material, true);
}

?>