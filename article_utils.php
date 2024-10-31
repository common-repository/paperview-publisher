<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__article_paywall_availability__default() {
  return 'traditional_purchase';
}

function paperview_publisher__article_paywall_availability__allowed_values() {
  return [
    'free',
    'optional_purchase',
    'traditional_purchase',
    'subscription_only'
  ];
}

function paperview_publisher__article_paywall_availability__values_as_select_options(
  $selected_paywall_availability = null
  ) {
  $allowed_values = [
    'free'                 => __('Free (logged in)', 'paperview-publisher'),
    'optional_purchase'    => __('Optional payment', 'paperview-publisher'),
    'traditional_purchase' => __('Purchase', 'paperview-publisher'),
    'subscription_only'    => __('Subscribers only', 'paperview-publisher')
  ];
  foreach($allowed_values as $key => $text) {
  ?>
    <option value="<?php echo $key; ?>" <?php selected($selected_paywall_availability, $key); ?>><?php echo $text; ?></option>
  <?php
  }
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__get_article_content(
  $post_id,
  $sync_article = false
  ) {
  ///////////////////////////////////////

  require_once 'publisher_utils.php';

  require_once 'database.php';

  ///////////////////////////////////////

  $send_to_paperview = get_post_meta(
    $post_id,
    'paperview_publisher__paperview_article',
    true
  );
  if($send_to_paperview === '' || $send_to_paperview === '0') {
    return null;
  }

  if($sync_article) {
    paperview_publisher__sync_article($post_id);
  }

  $article_info = paperview_publisher__db__get_article_info($post_id);
  if(empty($article_info)) {

    // Don't resync if sync was already done.
    if(!$sync_article) {
      paperview_publisher__sync_article($post_id);
    }

    if(empty($article_info)) {
      return null;
    }
  }

  $the_content = $article_info['real_content'];
  if(!empty($the_content)) {
    $the_content = json_decode($the_content, true);
  }
  if(empty($the_content)) {
    return '';
  }

  foreach($the_content as $content_entry) {
    $is_hidden_wp_content_entry =
      !empty($content_entry) &&
      $content_entry['tag'] === '___WordPress_Content___';
    if($is_hidden_wp_content_entry) {
      return $content_entry['text'];
    }
  }

  return $the_content[0]['text'];
}

////////////////////////////////////////////////////////////////////////////////
