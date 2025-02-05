<?php

$s = get_option('wp_buttondown_settings');

$api_key = $s['api_token'];
$key_is_regular = $s['regular_cookie'];
$key_is_premium = $s['premium_cookie'];

$routes = array(
  'login' => $s['login'],
  'success' => $s['success'],
  'error' => $s['error'],
  'no-subscription' => $s['no-subscription']
);

?>