<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

define('PAPERVIEW__TOP_LEVEL_ADMIN_MENU_NODE', 'paperview');

////////////////////////////////////////////////////////////////////////////////

add_action(
  'admin_menu',
  function() {
    add_menu_page(
      __('Paperview', 'paperview-publisher'),
      __('Paperview', 'paperview-publisher'),
      'read',
      PAPERVIEW__TOP_LEVEL_ADMIN_MENU_NODE,
      '',
      'none',
      59
    );

    add_submenu_page(
      PAPERVIEW__TOP_LEVEL_ADMIN_MENU_NODE,
      __('Paperview status', 'paperview-publisher'),
      __('Status', 'paperview-publisher'),
      'read',
      PAPERVIEW__TOP_LEVEL_ADMIN_MENU_NODE,
      'paperview_plugin__system_status_page'
    );
  }
);

////////////////////////////////////////////////////////////////////////////////

function paperview_plugin__system_status_page() {
  ///////////////////////////////////////

  require_once 'paperview__paperview_api_connection.php';

  ///////////////////////////////////////

  $has_connection_to_paperview_api = paperview_api__test_connection(false);
  if($has_connection_to_paperview_api === true) {
    $connection_status_css_class = 'connection-ok';
    $connection_status_text = __('OK', 'paperview-publisher');
  } else {
    $connection_status_css_class = 'connection-error';
    $connection_status_text = __('not working', 'paperview-publisher');
  }
?>
  <h1 class="wp-heading-inline"><?php _e('Paperview status', 'paperview-publisher'); ?></h1>

  <h2 class="paperview-status"><?php _e('API', 'paperview-publisher'); ?></h2>
  <div class="api-connection-status <?php echo $connection_status_css_class; ?>">
    <?php
      printf(
        /* translators: 1: status of API connection */
        __('Connection to the Paperview API is %s.', 'paperview-publisher'),
        '<span class="status">' . $connection_status_text . '</span>'
      );
    ?>
  </div>

  <?php do_action('paperview_plugin__system_status_info'); ?>

<?php

  if(current_user_can('edit_plugins')) {
    $paperview_plugins = apply_filters('paperview__plugins_info', []);
    if(!empty($paperview_plugins)) {
?>
  <h2 class="paperview-plugins"><?php _e('Paperview plugins', 'paperview-publisher'); ?></h2>
  <?php foreach($paperview_plugins as $plugin_id => $plugin_info) { ?>
    <h3 class="plugin-id"><?php echo $plugin_id; ?></h3>
    <table class="plugin-info">
      <?php foreach($plugin_info as $info_key => $info_value) { ?>
        <tr>
          <td><?php echo $info_key; ?></td>
          <td><?php echo $info_value; ?></td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>
<?php
    }
  }
}

////////////////////////////////////////////////////////////////////////////////
