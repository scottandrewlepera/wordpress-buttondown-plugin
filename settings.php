<?php

/* Settings menu  */

include 'create-pages.php';
  
function wp_buttondown_settings_init() {

  $opts = array(
    'api_token'      => '',
    'subscribe_page' => '',
    'regular_cookie' => wp_buttondown_generate_cookie_name(),
    'premium_cookie' => wp_buttondown_generate_cookie_name(),
    'login'          => '/buttondown-login',
    'success'        => '/buttondown-success',
    'error'          => '/buttondown-error',
    'nosub'          => '/buttondown-nosub',
  );

  add_option('wp_buttondown_settings', $opts);

  register_setting(
    'wp_buttondown',
    'wp_buttondown_settings'
  );

  add_settings_section(
      'wp_buttondown_settings_section',
      'General settings',
      'wp_buttondown_settings_section_callback',
      'wp_buttondown'
  );

  add_settings_field(
    'wp_buttondown_api_token',
    'WP Buttondown settings',
    'wp_buttondown_settings_field_callback',
    'wp_buttondown',
    'wp_buttondown_settings_section'
  );
}

add_action( 'admin_init', 'wp_buttondown_settings_init' );

// register the page and add it as a Settings submenu
function wp_buttondown_settings_page() {
  $hookname = add_options_page(
      'WP Buttondown (Beta)',
      'WP Buttondown',
      'manage_options',
      'wp_buttondown',
      'wp_buttondown_settings_page_html'
  );
}

add_action( 'admin_menu', 'wp_buttondown_settings_page' );

function wp_buttondown_settings_section_callback($args) {
  // not used
}

function wp_buttondown_settings_field_callback($args) {

  $setting = get_option('wp_buttondown_settings');
  ?>
  
  <h2>Buttondown and cookie settings</h2>

  <p>These settings allow the plugin to query your Buttondown mailing list and set the appropriate cookies based on subscription status.</p>
  <p>Cookie names can be anything valid. To revoke access, change the cookie names.</p>

  <table class="form-table">
    <tr>
      <th>Buttondown API Token</th>
      <td>
        <input type="text" name="api_token" value="<?php echo isset( $setting['api_token'] ) ? esc_attr( $setting['api_token'] ) : ''; ?>" required />
      </td>
    <tr>
    <tr>
      <th>Buttondown subscription page (optional)</th>
      <td>
        <input type="text" name="subscribe_page" value="<?php echo isset( $setting['subscribe_page'] ) ? esc_attr( $setting['subscribe_page'] ) : ''; ?>" placeholder="https://buttondown.com/your-cool-newsletter/" />
      </td>
    <tr>
    <tr>
      <th>Regular subscriber cookie name</th>
      <td>
      <?php echo isset( $setting['regular_cookie'] ) ? esc_attr( $setting['regular_cookie'] ) : 'Not set'; ?>
      </td>
    </tr>
    <tr>
      <th>Premium subscriber cookie name</th>
      <td>
        <?php echo isset( $setting['premium_cookie'] ) ? esc_attr( $setting['premium_cookie'] ) : 'Not set'; ?>
      </td>
    </tr>
    <tr>
      <th>Generate new cookies</th>
      <td>
        <input type="checkbox" name="regen_cookies" />
        <p class="description">This will log out all subscribers, forcing them to log in again.</p>
      </td>
    </tr>
  </table>

  <h2>Landing page configuration</h2>
  <p>These are the pages that visitors will be redirected to for the login process. You must create these pages yourself, or the plugin can create them for you.</p>

  <table class="form-table">
    <tr>
      <th>Login page</th>
      <td>
        <input type="text" name="login" value="<?php echo isset( $setting['login'] ) ? esc_attr( $setting['login'] ) : ''; ?>" required />
      </td>
    </tr>
    <tr>
      <th>Success landing page</th>
      <td>
        <input type="text" name="success" value="<?php echo isset( $setting['success'] ) ? esc_attr( $setting['success'] ) : ''; ?>" required />
      </td>
    </tr>
    <tr>
      <th>Error landing page</th>
      <td>
        <input type="text" name="error" value="<?php echo isset( $setting['error'] ) ? esc_attr( $setting['error'] ) : ''; ?>" required />
      </td>
    </tr>
    <tr>
      <th>No subscription landing page</th>
      <td>
        <input type="text" name="nosub" value="<?php echo isset( $setting['nosub'] ) ? esc_attr( $setting['nosub'] ) : ''; ?>" required />
      </td>
    </tr>
    <tr>
      <th>Create pages on update</th>
      <td>
        <input type="checkbox" name="create_pages" />
        <p class="description">This will create all the pages listed above if they don't already exist.</p>
      </td>
    </tr>
  </table>
  <?php
}

function wp_buttondown_generate_cookie_name() {
  $valid = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ0129456789';
  return substr(str_shuffle($valid), 0, 10);
}

function wp_buttondown_sanitize_cookie_name($name) {
  // Allowed characters
  $allowed_chars = "/[^!#$%&'*+\-.^_`|~A-Za-z0-9]/";
  // Remove any disallowed characters
  return preg_replace($allowed_chars, '', $name);
}

function wp_buttondown_settings_page_html() {
  if ( !current_user_can( 'manage_options' ) ) {
    return;
  }

  $s = wp_buttondown_get_settings();

  $new_opts = array();
  
  if (isset($_POST['api_token'])) {

    $new_opts['api_token'] = sanitize_text_field($_POST['api_token']);
    $new_opts['subscribe_page'] = sanitize_url($_POST['subscribe_page']);
    $new_opts['login'] = sanitize_text_field($_POST['login']);
    $new_opts['success'] = sanitize_text_field($_POST['success']);
    $new_opts['error'] = sanitize_text_field($_POST['error']);
    $new_opts['nosub'] = sanitize_text_field($_POST['nosub']);

    if (isset($_POST['regen_cookies'])) {
      $new_opts['regular_cookie'] = wp_buttondown_generate_cookie_name();
      $new_opts['premium_cookie'] = wp_buttondown_generate_cookie_name();
    } else {
      $new_opts['regular_cookie'] = $s['regular_cookie'];
      $new_opts['premium_cookie'] = $s['premium_cookie'];
    }
  }

  if ( count($new_opts) !== 0 ) {

    $success = update_option('wp_buttondown_settings', $new_opts);

    if ($success === true) {
        echo '<div class="updated"><p>Settings updated.</p></div>';
        if (isset($_POST['regen_cookies'])) {
          echo '<div class="updated"><p>New cookies generated.</p></div>';
        }
    } else {
      echo '<div class="error"><p>Update failed.</p></div>';
    }

  }

  if (isset($_POST['create_pages'])) {
    $page_results = wp_buttondown_settings_create_pages();
    if ($page_results['code'] == 0) {
      echo '<div class="updated"><p>Pages created.</p></div>';
    } else {
      $page_errors = join('<br />', $page_results['errors']);
      echo "<div class=\"error\">$page_errors</div>";
    }
  }

  ?>
  <div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options-general.php?page=wp_buttondown" method="post">
      <?php
      settings_fields( 'wp_buttondown' );
      do_settings_fields( 'wp_buttondown', 'wp_buttondown_settings_section' );
      submit_button( 'Save settings' );
      ?>
  </div>
  <?php
}

?>