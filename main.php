<?php
/**
 * Plugin Name: Paperview Publisher
 * Plugin URI: https://paperview.net/paperview-publisher-plugin
 * Description: A plugin to automate the tasks for a Publisher.
 * Version: 0.8.6
 * Author: Paperview Systems
 * Author URI: https://paperview.net
 * Text Domain: paperview-publisher
 * Domain Path: /languages
 */

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__main_file() {
  static $main_file;
  if(!isset($main_file)) {
    $main_file = plugin_basename(__FILE__);
  }
  return $main_file;
}

////////////////////////////////////////////////////////////////////////////////

require_once 'plugin_lifetime.php';
paperview_publisher__register_for_plugin_lifetime_hooks();

////////////////////////////////////////////////////////////////////////////////

if(is_admin()) {
  require_once 'paperview__top_level_admin_menu.php';

  require_once 'user_info.php';

  require_once 'admin_menu.php';
}

////////////////////////////////////////////////////////////////////////////////

require_once 'hooks_common.php';

require_once 'taxonomies.php';

if(is_admin()) {
  require_once 'hooks_admin.php';
} else {
  require_once 'hooks_frontend.php';
}

////////////////////////////////////////////////////////////////////////////////
