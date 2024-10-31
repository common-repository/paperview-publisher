<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

require_once 'paperview__remote_request.php';

////////////////////////////////////////////////////////////////////////////////

function __paperview_api__base_url($connect_to_sandbox) {
  if(defined('PAPERVIEW_API_BASE_URL')) {
    return PAPERVIEW_API_BASE_URL;
  }

  if($connect_to_sandbox) {
    return "https://blackmask-sandbox-backoffice.herokuapp.com/api/public-api-handler/";
  }

  return "https://api.paperview.tech/v1/";
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__get_publication_article(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key,
  $article_id,
  $get_content_gibberish = true,
  $get_paywall_invoker_gibberish = true,
  $get_packs = true
  ) {
  $result = paperview__remote_get(
    __paperview_api__base_url($connect_to_sandbox) . 'publication/article',
    [
      'publication_id'                => $publication_id,
      'publication_api_key'           => $publication_api_key,
      'article'                       => $article_id,
      'return_content_format'         => 'string',
      'return_gibberish'              => $get_content_gibberish,
      'return_paywall_invoker_script' => $get_paywall_invoker_gibberish,
      'return_packs'                  => $get_packs
    ]
  );

  if(!$result['success'] || $result['http_code'] != 200) {
    return null;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__store_publication_article(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key,
  $user_id,
  $article_id = null,
  $article_content = [],
  $article_title = null,
  $article_teaser = null,
  $article_summary = null,
  $paywall_availability = '',
  $price = '',
  $packs = null,
  $get_content_gibberish = true,
  $get_paywall_invoker_gibberish = true
  ) {
  $result = paperview__remote_post(
    __paperview_api__base_url($connect_to_sandbox) . 'publication/article',
    [
      'publication_id'      => $publication_id,
      'publication_api_key' => $publication_api_key,
      'user_id'             => $user_id,
      'article'             => [
        'id'                   => $article_id,
        'content'              => $article_content,
        'title'                => $article_title,
        'teaser'               => $article_teaser,
        'summary'              => $article_summary,
        'paywall_availability' => $paywall_availability,
        'price'                => ((float) $price),
        'packs'                => $packs,
      ],
      'return_gibberish'              => $get_content_gibberish,
      'return_paywall_invoker_script' => $get_paywall_invoker_gibberish
    ]
  );

  if(!$result['success'] || $result['http_code'] != 200) {
    return null;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__set_publication_article_packs(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key,
  $article_id,
  $pack_ids
  ) {
  if($pack_ids === null) {
    $pack_ids = [];
  }

  $result = paperview__remote_post(
    __paperview_api__base_url($connect_to_sandbox) . 'publication/article/packs',
    [
      'publication_id'      => $publication_id,
      'publication_api_key' => $publication_api_key,
      'article'             => $article_id,
      'packs'               => $pack_ids
    ]
  );

  if(!$result['success'] || $result['http_code'] != 200) {
    return false;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__get_publication_info(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key
  ) {
  $result = paperview__remote_get(
    __paperview_api__base_url($connect_to_sandbox) . 'publication',
    [
      'publication_id'      => $publication_id,
      'publication_api_key' => $publication_api_key
    ]
  );

  if(!$result['success'] ||
     $result['http_code'] != 200
     ) {
    return null;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__get_publication_packs(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key
  ) {
  $result = paperview__remote_get(
    __paperview_api__base_url($connect_to_sandbox) . 'publication/packs',
    [
      'publication_id'      => $publication_id,
      'publication_api_key' => $publication_api_key
    ]
  );

  if(!$result['success'] || $result['http_code'] != 200) {
    return null;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__get_publication_editors(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key
  ) {
  $result = paperview__remote_get(
    __paperview_api__base_url($connect_to_sandbox) . 'publication/members/editors',
    [
      'publication_id'      => $publication_id,
      'publication_api_key' => $publication_api_key
    ]
  );

  if(!$result['success'] || $result['http_code'] != 200) {
    return null;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__get_publication_administrators(
  $connect_to_sandbox,
  $publication_id,
  $publication_api_key
  ) {
  $result = paperview__remote_get(
    __paperview_api__base_url($connect_to_sandbox) . 'publication/members/administrators',
    [
      'publication_id'      => $publication_id,
      'publication_api_key' => $publication_api_key
    ]
  );

  if(!$result['success'] || $result['http_code'] != 200) {
    return null;
  }

  $response_body = wp_remote_retrieve_body($result['response']);
  return json_decode($response_body, true);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_api__test_connection(
  $connect_to_sandbox,
  $test_data_server = false
  ) {
  $request_params = [];
  if($test_data_server) {
    $request_params['check_data_server'] = '1';
  }

  $result = paperview__remote_head(
    __paperview_api__base_url($connect_to_sandbox) . 'connection/test',
    $request_params
  );

  return $result['success'] && $result['http_code'] == 200;
}

////////////////////////////////////////////////////////////////////////////////
