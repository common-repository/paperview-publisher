<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

define('PAPERVIEW_PUBLISHER__PACK_TAXONOMY', 'paperview_publisher_pack');

////////////////////////////////////////////////////////////////////////////////

add_action(
  'init',
  function() {
    ///////////////////////////////////////

    require_once 'plugin_options.php';

    ///////////////////////////////////////

    $pack_url_slug = paperview_publisher__plugin_option__pack_url_slug();
    if(empty($pack_url_slug)) {
      $pack_url_slug = 'pack';
    }

    register_taxonomy(
      PAPERVIEW_PUBLISHER__PACK_TAXONOMY,
      paperview_publisher__plugin_option__supported_post_types(),
      [
        'labels' =>
          [
            'menu_name'                  => __('Paperview Packs', 'paperview-publisher'),
            'name'                       => _x('Paperview Packs', 'taxonomy general name', 'paperview-publisher'),
            'singular_name'              => _x('Pack', 'taxonomy singular name', 'paperview-publisher'),
            'search_items'               => __('Search Packs', 'paperview-publisher'),
            'popular_items'              => __('Popular Packs', 'paperview-publisher'),
            'all_items'                  => __('All Packs', 'paperview-publisher'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Pack', 'paperview-publisher'),
            'view_item'                  => __('View Pack', 'paperview-publisher'),
            'update_item'                => __('Update Pack', 'paperview-publisher'),
            'add_new_item'               => __('Add New Pack', 'paperview-publisher'),
            'new_item_name'              => __('New Pack Name', 'paperview-publisher'),
            'separate_items_with_commas' => __('Separate packs with commas', 'paperview-publisher'),
            'add_or_remove_items'        => __('Add or remove packs', 'paperview-publisher'),
            'choose_from_most_used'      => __('Choose from the most used packs', 'paperview-publisher'),
            'not_found'                  => __('No packs found', 'paperview-publisher'),
            'no_terms'                   => __('No packs', 'paperview-publisher'),
            'filter_by_item'             => null,
          ],
        'hierarchical'       => false,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => false,
        'show_tagcloud'      => true,
        'show_in_rest'       => false,
        'show_admin_column'  => true,
        'meta_box_cb'        => false,
        'sort'               => false,
        'query_var'          => 'pack',
        'rewrite' =>
          [
            'slug' => $pack_url_slug,
          ],
      ]
    );
  }
);

////////////////////////////////////////////////////////////////////////////////
