<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__register_for_plugin_lifetime_hooks() {
  ///////////////////////////////////////

  require_once 'database.php';

  require_once 'cron.php';

  ///////////////////////////////////////

  register_activation_hook(
    paperview_publisher__main_file(),
    function() {
      paperview_publisher__db__install();
      paperview_publisher__schedule_events();
    }
  );

  ///////////////////////////////

  register_deactivation_hook(
    paperview_publisher__main_file(),
    function() {
      paperview_publisher__unschedule_events();
    }
  );

  ///////////////////////////////

  add_action(
    'plugins_loaded',
    function() {
      if(!wp_doing_ajax() && !wp_is_json_request()) {
        paperview_publisher__db__install();
      }
    }
  );

  ///////////////////////////////

  paperview_publisher__schedule_events();

  ///////////////////////////////
}

////////////////////////////////////////////////////////////////////////////////
