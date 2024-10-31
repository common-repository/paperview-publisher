<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__sync_article($post_id, $force_sync = false) {
  ///////////////////////////////////////

  require_once 'database.php';

  require_once 'paperview__paperview_api_connection.php';

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  if(empty($post_id)) {
    return false;
  }

  $existing_article_info = paperview_publisher__db__get_article_info($post_id);
  if(empty($existing_article_info)) {
    return false;
  }

  $article_id = $existing_article_info['article_id'];
  if(empty($article_id)) {
    return false;
  }

  if(!$force_sync) {
    $last_sync = (int) $existing_article_info['last_sync'];

    // 60 * 60 = 60 minutes
    $sync_is_necessary = (time() - $last_sync > 60 * 60);
    if(!$sync_is_necessary) {
      return true;
    }
  }

  $publication_id = paperview_publisher__plugin_option__publication_id();
  $publication_api_key =
    paperview_publisher__plugin_option__publication_api_key();
  $publication_info =
    paperview_publisher__db__get_publication_info($publication_id);
  if(empty($publication_id) ||
     empty($publication_api_key) ||
     empty($publication_info)
     ) {
    return false;
  }

  $connect_to_sandbox =
    paperview_publisher__plugin_option__connect_to_sandbox();

  $get_content_gibberish =
    paperview_publisher__plugin_option__get_content_gibberish();
  $get_paywall_invoker_gibberish =
    paperview_publisher__plugin_option__get_paywall_invoker_gibberish();

  $article_info = paperview_api__get_publication_article(
    $connect_to_sandbox,
    $publication_id,
    $publication_api_key,
    $article_id,
    $get_content_gibberish,
    $get_paywall_invoker_gibberish,
    true
  );
  if(empty($article_info)) {
    return false;
  }

  paperview_publisher__db__store_article_info(
    $post_id,
    [
      'publication_id'       => $publication_info['id'],
      'article_id'           => $article_id,
      'real_content'         => json_encode($article_info['content']),
      'paywall_availability' => $article_info['paywall_availability'],
      'price'                => $article_info['price_amount'],
      'last_sync'            => time(),
    ]
  );

  paperview_publisher__db__store_post_data(
    $post_id,
    $article_info['result_content'],
    $article_info['title'],
    $article_info['teaser'],
    $article_info['summary']
  );

  $pack_ids_explicitly_assigned = [];
  $pack_ids_implicitly_assigned = [];
  foreach($article_info['packs'] as $pack_id => $pack_info) {
    if($pack_info['explicit_assignment'] == true) {
      $pack_ids_explicitly_assigned[] = $pack_id;
    } else {
      $pack_ids_implicitly_assigned[] = $pack_id;
    }
  }
  paperview_publisher__db__set_article_packs(
    $post_id,
    $pack_ids_explicitly_assigned,
    $pack_ids_implicitly_assigned
  );

  paperview_publisher__db__correct_posts_terms_relations();

  return true;
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__link_to_publication() {
  ///////////////////////////////////////

  require_once 'database.php';

  require_once 'paperview__paperview_api_connection.php';

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  $connect_to_sandbox =
    paperview_publisher__plugin_option__connect_to_sandbox();
  $publication_id = paperview_publisher__plugin_option__publication_id();
  $publication_api_key =
    paperview_publisher__plugin_option__publication_api_key();

  $can_link_to_publication =
    (
      !empty($publication_id) &&
      !empty($publication_api_key)
    );

  if(!$can_link_to_publication) {
    return false;
  }

  $publication_info = paperview_api__get_publication_info(
    $connect_to_sandbox,
    $publication_id,
    $publication_api_key
  );

  if(empty($publication_info)) {
    return false;
  }

  update_option(
    'paperview_publisher__last_up_check',
    current_time('mysql', true)
  );

  paperview_publisher__db__store_publication_info(
    $publication_id,
    null,
    $publication_info['currency'],
    $publication_info['currency_subunit_to_unit'],
    $publication_info['article_default_price_amount']
  );

  return true;
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__sync_packs() {
  ///////////////////////////////////////

  require_once 'database.php';

  require_once 'paperview__paperview_api_connection.php';

  require_once 'plugin_options.php';

  require_once 'taxonomies.php';

  ///////////////////////////////////////

  $publication_id = paperview_publisher__plugin_option__publication_id();
  $publication_api_key =
    paperview_publisher__plugin_option__publication_api_key();
  $publication_info =
    paperview_publisher__db__get_publication_info($publication_id);
  if(empty($publication_id) ||
     empty($publication_api_key) ||
     empty($publication_info)
     ) {
    return false;
  }

  $connect_to_sandbox =
    paperview_publisher__plugin_option__connect_to_sandbox();

  // TODO

  $fetched_packs = paperview_api__get_publication_packs(
    $connect_to_sandbox,
    $publication_id,
    $publication_api_key
  );

  if($fetched_packs === null) {
    return false;
  }

  if(empty($fetched_packs)) {
    return true;
  }

  foreach($fetched_packs as $pack) {
    paperview_publisher__db__store_pack_info(
      $pack['pack_id'],
      [
        'publication_id' => $publication_info['id'],
        'name'           => $pack['name'],
        'description'    => $pack['description'],
        'starts_on'      => $pack['starts_on'],
        'ends_on'        => $pack['ends_on'],
        'price'          => $pack['price'],
      ]
    );
  }

  return true;
}

////////////////////////////////////////////////////////////////////////////////
