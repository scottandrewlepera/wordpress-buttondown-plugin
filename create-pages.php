<?php

function wp_buttondown_settings_create_pages() {

  if (!current_user_can('manage_options')) {
    return array(
      'errors' => array('Insufficient permissions'),
      'code' => 2
    );
  }

  $page_titles = array(
    'login' => 'Log in',
    'error' => 'Error',
    'success' => 'Success!',
    'nosub' => 'No Subscription',
  );
  
  $page_content = array(
    'login' => '[wp_buttondown_login_form]',
    'error' => 'Something went wrong. Try again? [wp_buttondown_login_form]',
    'success' => '[wp_buttondown_success_message]',
    'nosub' => 'Sorry, there\'s no subscription for that email. Try again? [wp_buttondown_login_form]',
  );

  $s = get_option('wp_buttondown_settings');

  $routes = array(
    'login' => $s['login'],
    'error' => $s['error'],
    'success' => $s['success'],
    'nosub' => $s['nosub'],
  );

  $status = array(
    'errors' => array(),
    'code' => 0,
  );

  $return_wp_error_on_failure = true;
  $run_after_insert_hooks = true;

  foreach ($routes as $key => $path) {
    if ($path[0] != '/') {
      $path = "/$path";
    }
    $existing_page = get_page_by_path($path);
    if (!$existing_page) {
        $page_data = array(
          'post_title'   => $page_titles[$key],
          'post_content' => $page_content[$key],
          'post_status'  => 'publish',
          'post_type'    => 'page',
          'comment_status' => 'closed',
          'post_name'    => $path
        );
        $result = wp_insert_post(
          $page_data,
          $return_wp_error_on_failure,
          $run_after_insert_hooks
        );
        if (is_wp_error($result)) {
          $status['errors'][$key] = "Error creating $path: " . 
          join(" | ", $result->get_error_messages());
        }
    } else {
      $status['errors'][$key] = "Error creating $path: page already exists.";
    }
  } // end foreach

  if (sizeof($status['errors']) > 0 ) {
    $status['code'] = 1;
  }

  return $status;
}

?>