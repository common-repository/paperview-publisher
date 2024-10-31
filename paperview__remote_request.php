<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

/**
 * Send a HEAD request
 * @param string $url to request
 * @param array $head values to send
 * @param array $options for wp_remote_request
 * @return array
 */
function paperview__remote_head($url, $head = [], $options = []) {
  $options += [
    'method' => 'HEAD',
  ];

  return paperview__remote_get($url, $head, $options);
}

////////////////////////////////////////////////////////////////////////////////

/**
 * Send a GET request
 * @param string $url to request
 * @param array $get values to send
 * @param array $options for wp_remote_request
 * @return array
 */
function paperview__remote_get($url, $get = [], $options = []) {
  $url .= (strpos($url, '?') === false ? '?' : '') . http_build_query($get);

  $options += [
    'method'  => 'GET',
    'timeout' => 30,
  ];

  $remote_response = wp_remote_request($url, $options);

  if(is_wp_error($remote_response)) {
    $result = [
      'success' => false,
      'error' => $remote_response->get_error_message(),
    ];
  } else {
    $result = [
      'success' => true,
      'http_code' => wp_remote_retrieve_response_code($remote_response),
      'response' => $remote_response,
    ];
  }

  return $result;
}

////////////////////////////////////////////////////////////////////////////////

/**
 * Send a POST request
 * @param string $url to request
 * @param array $post values to send
 * @param array $options for wp_remote_request
 * @return array
 */
function paperview__remote_post(
  $url,
  $post = [],
  $options = [],
  $json_body = true
  ) {
  $options += [
    'method' => 'POST',
    'timeout' => 30,
  ];

  if(!array_key_exists('body', $options)) {
    if($json_body) {

      if(array_key_exists('headers', $options)) {
        $headers = $options['headers'];
      } else {
        $headers = [];
      }

      $headers['Content-Type'] = 'application/json';
      $options['headers'] = $headers;

      $options['body'] = json_encode($post);

    } else {
      $options['body'] = http_build_query($post);
    }
  }

  $remote_response = wp_remote_request($url, $options);

  if(is_wp_error($remote_response)) {
    $result = [
      'success' => false,
      'error' => $remote_response->get_error_message(),
    ];
  } else {
    $result = [
      'success' => true,
      'http_code' => wp_remote_retrieve_response_code($remote_response),
      'response' => $remote_response,
    ];
  }

  return $result;
}

////////////////////////////////////////////////////////////////////////////////
