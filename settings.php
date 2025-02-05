<?php

/* Settings menu  */
  
function wp_buttondown_settings_init() {

  $opts = array(
      'api_token' => '',
      'regular_cookie' => '',
      'premium_cookie' => '',
      'login' => '/buttondown/login',
      'success' => '/buttondown/success',
      'error' => '/buttondown/error',
      'no-subscription' => '/buttondown/nosub',
      'pages_created' => false
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
      'Wordpress with Buttondown',
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
      <th>Regular subscriber cookie name</th>
      <td>
        <input type="text" name="regular_cookie" value="<?php echo isset( $setting['regular_cookie'] ) ? esc_attr( $setting['regular_cookie'] ) : ''; ?>" required />
      </td>
    </tr>
    <tr>
      <th>Premium subscriber cookie name</th>
      <td>
        <input type="text" name="premium_cookie" value="<?php echo isset( $setting['premium_cookie'] ) ? esc_attr( $setting['premium_cookie'] ) : ''; ?>" required />
      </td>
    </tr>
    <tr>
  </table>

  <h2>Landing page configuration</h2>
  <p>These are the pages that visitors will be redirected to for the login process. You must create these pages yourself.</p>

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
        <input type="text" name="no-subscription" value="<?php echo isset( $setting['no-subscription'] ) ? esc_attr( $setting['no-subscription'] ) : ''; ?>" required />
      </td>
    </tr>
  </table>
  <?php
}

function wp_buttondown_settings_page_html() {
  if ( !current_user_can( 'manage_options' ) ) {
        return;
  }
  if (isset($_POST['api_token'])) {

      $new_opts = array(
        'api_token' => sanitize_text_field($_POST['api_token']),
        'regular_cookie' => sanitize_text_field($_POST['regular_cookie']),
        'premium_cookie' => sanitize_text_field($_POST['premium_cookie']),
        'login' => sanitize_text_field($_POST['login']),
        'success' => sanitize_text_field($_POST['success']),
        'error' => sanitize_text_field($_POST['error']),
        'no-subscription' => sanitize_text_field($_POST['no-subscription'])
      );

      $success = update_option('wp_buttondown_settings', $new_opts);
      if ($success === true) {
          echo '<div class="updated"><p>Settings updated.</p></div>';
      } else {
        echo '<div class="error"><p>Update failed.</p></div>';
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
      </form>
  </div>
  <?php
}

?>