<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

define('PAPERVIEW_AUTHOR_URI', 'https://paperview.net');

////////////////////////////////////////////////////////////////////////////////

function get_paperview_plugins($only_activated_plugins = true) {
  $paperview_plugins = [];
  $activated_plugins = get_option('active_plugins', []);
  $installed_plugins = get_plugins();

  foreach($installed_plugins as $plugin => $plugin_descriptor) {

    if($plugin_descriptor['AuthorURI'] !== PAPERVIEW_AUTHOR_URI) {
      continue;
    }

    if($only_activated_plugins && !isset($activated_plugins[$plugin])) {
      continue;
    }

    $paperview_plugins[]= $plugin;
  }

  return $paperview_plugins;
}

////////////////////////////////////////////////////////////////////////////////
