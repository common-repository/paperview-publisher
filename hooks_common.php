<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

add_action(
  'init',
  function() {
    load_plugin_textdomain(
      'paperview-publisher',
      FALSE,
      basename(dirname(__FILE__)) . '/languages'
    );
  }
);

////////////////////////////////////////////////////////////////////////////////

/*
// TODO: when replacing metaboxes with blocks, revisit this

add_action('init', 'paperview_publisher__register_block_types');

function paperview_publisher__register_block_types() {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  require_once 'database.php';

  require_once 'article_utils.php';

  ///////////////////////////////////////

  wp_register_script(
    'paperview_publisher__article_settings_block_script',
    plugin_dir_url(__FILE__) . '/assets/article_settings_block.js',
    [
      'wp-data',
      'wp-components',
      'wp-blocks',
      'wp-element',
      'wp-i18n',
    ],
    '1.0.0'
  );

  wp_set_script_translations(
    'paperview_publisher__article_settings_block_script',
    'paperview-publisher',
    plugin_dir_url(__FILE__) . '/languages',
  );

  $publication_info = paperview_publisher__db__get_publication_info();
  if(empty($publication_info)) {
    $need_to_configure_plugin = '1';
    $publication_currency =
    $publication_currency_subunit_to_unit =
    $publication_article_default_price_amount = '';
  } else {
    $need_to_configure_plugin = '0';
    $publication_currency = $publication_info['publication_currency'];
    $publication_currency_subunit_to_unit =
      (string) $publication_info['publication_currency_subunit_to_unit'];
    $publication_article_default_price_amount =
      (string) $publication_info['article_default_price_amount'];
  }

  register_block_type(
    'paperview-publisher/article-settings-block',
    [
      'attributes' => [
        'need_to_configure_plugin' => [
          'type' => 'string',
          'default' => $need_to_configure_plugin,
        ],
        'publication_currency' => [
          'type' => 'string',
          'default' => $publication_currency,
        ],
        'publication_currency_subunit_to_unit' => [
          'type' => 'string',
          'default' => $publication_currency_subunit_to_unit,
        ],
        'default__send_to_paperview' => [
          'type' => 'string',
          'default' => '1',
        ],
        'default__price' => [
          'type' => 'string',
          'default' => $publication_article_default_price_amount,
        ],
        'default__paywall_availability' => [
          'type' => 'string',
          'default' => paperview_publisher__article_paywall_availability__default(),
        ],
        'send_to_paperview' => [
          'type' => 'string',
          'source' => 'meta',
          'meta' => 'paperview_publisher__paperview_article',
        ],
        'article_id' => [
          'type' => 'string',
          'source' => 'meta',
          'meta' => 'paperview_publisher__article_id',
        ],
        'price' => [
          'type' => 'string',
          'source' => 'meta',
          'meta' => 'paperview_publisher__price',
        ],
        'paywall_availability' => [
          'type' => 'string',
          'source' => 'meta',
          'meta' => 'paperview_publisher__paywall_availability',
        ],
        'sync_with_paperview_article_url' => [
          'type' => 'string',
          'default' => plugin_dir_url(__FILE__) . 'link_post_to_article.php?post_id=${post_id}&return_url=${return_url}',
        ],
      ],
      'editor_script' => 'paperview_publisher__article_settings_block_script',
      'render_callback' => function($attributes, $content) { return ''; },
    ]
  );

  $meta_keys = [
    'paperview_publisher__paperview_article',
    'paperview_publisher__article_id',
    'paperview_publisher__price',
    'paperview_publisher__paywall_availability',
  ];

  foreach(paperview_publisher__plugin_option__supported_post_types() as $post_type) {
    foreach($meta_keys as $meta_key) {
      register_post_meta(
        $post_type,
        $meta_key,
        [
          'show_in_rest' => true,
          'single' => true,
          'type' => 'string',
        ]
      );
    }
  }
}

*/

////////////////////////////////////////////////////////////////////////////////

/*
// TODO: when replacing metaboxes with blocks, revisit this

add_action('rest_api_init', 'paperview_publisher__rest_api__add_user_id');

function paperview_publisher__rest_api__add_user_id() {
  register_rest_field(
    'user',
    'paperview_publisher__user_id',
    [
      'get_callback' =>
        function($user) {
          if(empty($user)) {
            return null;
          }
          return get_user_meta($user['id'], 'paperview_publisher__user_id', true);
        }
    ]
  );
}

*/

////////////////////////////////////////////////////////////////////////////////
