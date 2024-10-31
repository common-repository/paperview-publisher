<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

// Adapted from
// https://webprogramo.com/admin-notices-after-a-page-refresh-on-wordpress/1183/

////////////////////////////////////////////////////////////////////////////////

/**
 * Add a flash notice to {prefix}options table until a full page refresh is
 * done.
 *
 * @param string $notice our notice message
 * @param string $type This can be 'info', 'warning', 'error' or 'success', 'warning' as default
 * @param boolean $dismissible set this to true to add is-dismissible functionality to your notice
 * @return void
 */
function add_flash_notice(
  $notice,
  $type = 'warning',
  $dismissible = true
  ) {
  // Here we return the notices saved on our option.
  // If there are no notices, then an empty array is returned.
  $notices = get_option('paperview_plugin_flash_notices', []);

  $dismissible_text = $dismissible ? 'is-dismissible' : '';

  // We add our new notice.
  $notices[] = [
    'notice'      => $notice,
    'type'        => $type,
    'dismissible' => $dismissible_text
  ];

  // Then we update the option with our notices array
  update_option('paperview_plugin_flash_notices', $notices);
}

////////////////////////////////////////////////////////////////////////////////

// We add our display_flash_notices function to the admin_notices
add_action('admin_notices', 'display_flash_notices', 12);

/**
 * Function executed when the 'admin_notices' action is called. Here we check if
 * there are notices on our database and display them. After that, we remove the
 * option to prevent notices being displayed forever.
 * @return void
 */
function display_flash_notices() {
  $notices = get_option('paperview_plugin_flash_notices', []);

  // Iterate through our notices to be displayed and print them.
  foreach($notices as $notice) {
    printf(
      '<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
      $notice['type'],
      $notice['dismissible'],
      $notice['notice']
    );
  }

  // Now we reset our options to prevent notices being displayed forever.
  if(!empty($notices)) {
    delete_option('paperview_plugin_flash_notices');
  }
}

////////////////////////////////////////////////////////////////////////////////
