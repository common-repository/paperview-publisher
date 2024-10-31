<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__plugin_options($default = null) {
  ///////////////////////////////////////

  require_once 'article_utils.php';

  ///////////////////////////////////////

  if($default === null) {
    $plugin_options =
      get_option('paperview_publisher__options', []) +
      [
        'connect_to_sandbox'                    => false,
        'publication_id'                        => '',
        'publication_api_key'                   => '',
        'article__default_author_user_id'       => '',
        'article__default_paywall_availability' =>
          paperview_publisher__article_paywall_availability__default(),
        'send_article_to_paperview_default'     => true,
        'get_content_gibberish'                 => true,
        'get_paywall_invoker_gibberish'         => true,
        'supported_post_types'                  =>
          [
            'page',
            'post'
          ],
        'supported_post_statuses'               =>
          [
            'publish',
            'future'
          ],
        'pack_url_slug'                         => 'pack',
        'remove_plugin_data_on_uninstall'       => false,
      ];
    return $plugin_options;
  }
  return get_option('paperview_publisher__options', $default);
}

function paperview_publisher__save_plugin_options(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key,
  $article__default_author_user_id,
  $article__default_paywall_availability,
  $send_article_to_paperview_default,
  $get_content_gibberish,
  $get_paywall_invoker_gibberish,
  $supported_post_types,
  $supported_post_statuses,
  $pack_url_slug,
  $remove_plugin_data_on_uninstall
  ) {
  if(is_array($supported_post_types)) {
    $supported_post_types = json_encode($supported_post_types);
  }
  if(is_array($supported_post_statuses)) {
    $supported_post_statuses = json_encode($supported_post_statuses);
  }

  $new_options = [
    'connect_to_sandbox'                    => $connect_to_sandbox,
    'publication_id'                        => $publication_id,
    'publication_api_key'                   => $publication_api_key,
    'article__default_author_user_id'       => $article__default_author_user_id,
    'article__default_paywall_availability' =>
      $article__default_paywall_availability,
    'send_article_to_paperview_default'     =>
      $send_article_to_paperview_default,
    'get_content_gibberish'                 => $get_content_gibberish,
    'get_paywall_invoker_gibberish'         => $get_paywall_invoker_gibberish,
    'supported_post_types'                  => $supported_post_types,
    'supported_post_statuses'               => $supported_post_statuses,
    'pack_url_slug'                         => $pack_url_slug,
    'remove_plugin_data_on_uninstall'       => $remove_plugin_data_on_uninstall
  ];
  return update_option('paperview_publisher__options', $new_options);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__plugin_option__connect_to_sandbox() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['connect_to_sandbox'];
}

function paperview_publisher__plugin_option__publication_id() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['publication_id'];
}

function paperview_publisher__plugin_option__publication_api_key() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['publication_api_key'];
}

function paperview_publisher__plugin_option__article__default_author_user_id() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['article__default_author_user_id'];
}

function paperview_publisher__plugin_option__article__default_paywall_availability() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['article__default_paywall_availability'];
}

function paperview_publisher__plugin_option__send_article_to_paperview_default() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['send_article_to_paperview_default'];
}

function paperview_publisher__plugin_option__get_content_gibberish() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['get_content_gibberish'];
}

function paperview_publisher__plugin_option__get_paywall_invoker_gibberish() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['get_paywall_invoker_gibberish'];
}

function paperview_publisher__plugin_option__supported_post_types() {
  $plugin_options = paperview_publisher__plugin_options();

  $supported_post_types = $plugin_options['supported_post_types'];

  if(empty($supported_post_types)) {
    $supported_post_types = [
      'page',
      'post'
    ];
  } elseif(!is_array($supported_post_types)) {
    $supported_post_types = json_decode($supported_post_types);
  }

  return $supported_post_types;
}

function paperview_publisher__plugin_option__supported_post_statuses() {
  $plugin_options = paperview_publisher__plugin_options();

  $supported_post_statuses = $plugin_options['supported_post_statuses'];

  if(empty($supported_post_statuses)) {
    $supported_post_statuses = [
      'publish',
      'future'
    ];
  } elseif(!is_array($supported_post_statuses)) {
    $supported_post_statuses = json_decode($supported_post_statuses);
  }

  return $supported_post_statuses;
}

function paperview_publisher__plugin_option__pack_url_slug() {
  $plugin_options = paperview_publisher__plugin_options();
  if(!empty($plugin_options['edition_url_slug']))
  {
    return $plugin_options['edition_url_slug'];
  }
  return $plugin_options['pack_url_slug'];
}

function paperview_publisher__plugin_option__remove_plugin_data_on_uninstall() {
  $plugin_options = paperview_publisher__plugin_options();
  return $plugin_options['remove_plugin_data_on_uninstall'];
}

////////////////////////////////////////////////////////////////////////////////
