<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__schedule_events() {
  $do_schedule = function($schedule_hook, $period) {
    if(!wp_next_scheduled($schedule_hook)) {
      wp_schedule_event(time(), $period, $schedule_hook);
    }
  };

  ///////////////////

  $do_schedule(
    'paperview_publisher__cron__link_publication',
    'weekly'
  );

  $do_schedule(
    'paperview_publisher__cron__sync_packs',
    'daily'
  );

  $do_schedule(
    'paperview_publisher__cron__correct_posts_terms_relations',
    'hourly'
  );
}

function paperview_publisher__unschedule_events() {
  $remove_schedule = function($schedule_hook) {
    if($timestamp = wp_next_scheduled($schedule_hook)) {
      wp_unschedule_event($timestamp, $schedule_hook);
    }
  };

  ///////////////////

  $remove_schedule('paperview_publisher__cron__link_publication');

  $remove_schedule('paperview_publisher__cron__sync_packs');

  $remove_schedule('paperview_publisher__cron__correct_posts_terms_relations');
}

////////////////////////////////////////////////////////////////////////////////

add_action(
  'paperview_publisher__cron__link_publication',
  function() {
    ///////////////////////////////////////

    require_once 'publisher_utils.php';

    ///////////////////////////////////////

    paperview_publisher__link_to_publication();
  }
);

////////////////////////////////////////////////////////////////////////////////

add_action(
  'paperview_publisher__cron__sync_packs',
  function() {
    ///////////////////////////////////////

    require_once 'publisher_utils.php';

    ///////////////////////////////////////

    paperview_publisher__sync_packs();
  }
);

////////////////////////////////////////////////////////////////////////////////

add_action(
  'paperview_publisher__cron__correct_posts_terms_relations',
  function() {
    ///////////////////////////////////////

    require_once 'database.php';

    ///////////////////////////////////////

    paperview_publisher__db__correct_posts_terms_relations();
  }
);

////////////////////////////////////////////////////////////////////////////////
