<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

define('PAPERVIEW_PUBLISHER__DB_VERSION', '0.8.2');

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__install() {
  // Short-circuit check
  $existing_db_version = get_option('paperview_publisher__db_version');
  if($existing_db_version == PAPERVIEW_PUBLISHER__DB_VERSION) {
    return;
  }

  ///////////////////////////////////////

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  ///////////////////////////////////////

  // Adapted from
  // https://codex.wordpress.org/Creating_Tables_with_Plugins

  global $wpdb;

  ///////////////////

  $start_time = time();
  while(true) {
    $is_upgrading_db = get_transient('paperview_publisher__is_upgrading_db');
    if($is_upgrading_db === false) {
      break;
    }

    // sleep for 5 seconds
    sleep(5);
    // only wait for 5 minutes max
    if($start_time + (5 * 60) < time()) {
      return;
    }
  }

  set_transient(
    'paperview_publisher__is_upgrading_db',
    '-irrelevant-',
    10 * MINUTE_IN_SECONDS
  );

  ///////////////////

  $settle_db_changes = function() {
    // Just sleep for 1 second, as MySQL seems to need some time to settle
    // schema changes
    sleep(1);
  };

  ///////////////////

  $existing_db_version = get_option('paperview_publisher__db_version', '0.0.1');
  if($existing_db_version != PAPERVIEW_PUBLISHER__DB_VERSION) {
    ///////////////////

    $charset_collate = $wpdb->get_charset_collate() . ' engine=InnoDB';

    $publication_info_table_name =
      $wpdb->prefix . 'paperview_publisher_publication_info';
    $articles_table_name =
      $wpdb->prefix . 'paperview_publisher_articles';
    $packs_table_name =
      $wpdb->prefix . 'paperview_publisher_packs';
    $pack_articles_table_name =
      $wpdb->prefix . 'paperview_publisher_pack_articles';

    $wp_posts_table = $wpdb->posts;
    $wp_postmeta_table = $wpdb->postmeta;
    $wp_usermeta_table = $wpdb->usermeta;
    $wp_term_taxonomy_table = $wpdb->term_taxonomy;

    ///////////////////

    if(version_compare($existing_db_version, '0.8.0', '<')) {
      $editions_table_name =
        $wpdb->prefix . 'paperview_publisher_editions';
      $edition_articles_table_name =
        $wpdb->prefix . 'paperview_publisher_edition_articles';

      $has_publication_info_table =
        !empty($wpdb->get_var("SHOW TABLES LIKE '$publication_info_table_name'"));
      $has_articles_table =
        !empty($wpdb->get_var("SHOW TABLES LIKE '$articles_table_name'"));

      ///////////////////

      if($has_publication_info_table) {
        $wpdb->query("DROP TABLE IF EXISTS {$publication_info_table_name}__old");
        $wpdb->query("ALTER TABLE $publication_info_table_name RENAME {$publication_info_table_name}__old");
      }

      if($has_articles_table) {
        $wpdb->query("DROP TABLE IF EXISTS {$articles_table_name}__old");
        $wpdb->query("ALTER TABLE $articles_table_name RENAME {$articles_table_name}__old");
      }

      $wpdb->query("DROP TABLE IF EXISTS $editions_table_name");

      ///////////////////

      $wpdb->query(
        "
        CREATE TABLE $publication_info_table_name (
          id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          publication_id varchar(450) NOT NULL,
          last_data_fetch datetime NOT NULL,
          publication_currency varchar(5) NOT NULL,
          publication_currency_subunit_to_unit integer(11) NOT NULL,
          article_default_price_amount varchar(15) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate
        "
      );

      $wpdb->query(
        "
        CREATE TABLE $articles_table_name (
          id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          wp_post_id bigint(20) UNSIGNED NOT NULL,
          real_content longtext NOT NULL DEFAULT '',
          publication_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          article_id varchar(512) NOT NULL DEFAULT '',
          paywall_availability varchar(64) NOT NULL DEFAULT '',
          price varchar(16) NOT NULL DEFAULT '',
          last_sync bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          PRIMARY KEY  (id),
          UNIQUE KEY wp_post_id (wp_post_id)
        ) $charset_collate
        "
      );

      $wpdb->query(
        "
        CREATE TABLE $editions_table_name (
          id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          publication_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          edition_id varchar(512) NOT NULL DEFAULT '',
          available tinyint(1) NOT NULL DEFAULT 1,
          name varchar(512) NOT NULL DEFAULT '',
          description longtext NOT NULL DEFAULT '',
          price varchar(16) NOT NULL DEFAULT '',
          starts_on datetime,
          ends_on datetime,
          wp_term_id bigint(20) UNSIGNED,
          PRIMARY KEY  (id)
        ) $charset_collate
        "
      );

      $wpdb->query(
        "
        CREATE TABLE $edition_articles_table_name (
          id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          article_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          edition_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          explicit_assignment tinyint(1) NOT NULL DEFAULT 1,
          PRIMARY KEY  (id),
          UNIQUE KEY article_id_edition_id (article_id,edition_id)
        ) $charset_collate
        "
      );

      ///////////////////

      if($has_publication_info_table) {
        $wpdb->query(
          "
          INSERT INTO $publication_info_table_name (publication_id, last_data_fetch, publication_currency, publication_currency_subunit_to_unit, article_default_price_amount)
          SELECT publication_id, last_data_fetch, publication_currency, publication_currency_subunit_to_unit, article_default_price_amount
          FROM {$publication_info_table_name}__old
          "
        );
      }

      if($has_articles_table) {
        $wpdb->query(
          "
          INSERT INTO $articles_table_name (wp_post_id, real_content)
          SELECT post_id, real_content
          FROM {$articles_table_name}__old
          "
        );
      }

      ///////////////////

      $current_publication = paperview_publisher__db__get_publication_info();
      if(empty($current_publication)) {
        $current_publication =
          $wpdb->get_row(
            "
            SELECT *
            FROM $publication_info_table_name
            ORDER BY last_data_fetch DESC
            LIMIT 1
            ",
            ARRAY_A
          );
      }

      $wpdb->query(
        "
        DELETE FROM $articles_table_name
        WHERE wp_post_id NOT IN (SELECT id FROM $wp_posts_table)
        "
      );

      if(!empty($current_publication)) {
        $wpdb->update(
          $articles_table_name,
          ['publication_id' => $current_publication['id']],
          ['publication_id' => 0]
        );
      }

      $wpdb->query(
        "
        UPDATE $articles_table_name a
        INNER JOIN (
          SELECT post_id, meta_value
          FROM $wp_postmeta_table
          WHERE meta_key = 'paperview_publisher__article_id'
        ) _aid ON a.wp_post_id = _aid.post_id
        SET a.article_id = _aid.meta_value
        WHERE a.article_id = ''
        "
      );
      $wpdb->query(
        "
        UPDATE $articles_table_name a
        INNER JOIN (
          SELECT post_id, meta_value
          FROM $wp_postmeta_table
          WHERE meta_key = 'paperview_publisher__paywall_availability'
        ) _aid ON a.wp_post_id = _aid.post_id
        SET a.paywall_availability = _aid.meta_value
        WHERE a.paywall_availability = ''
        "
      );
      $wpdb->query(
        "
        UPDATE $articles_table_name a
        INNER JOIN (
          SELECT post_id, meta_value
          FROM $wp_postmeta_table
          WHERE meta_key = 'paperview_publisher__price'
        ) _aid ON a.wp_post_id = _aid.post_id
        SET a.price = _aid.meta_value
        WHERE a.price = ''
        "
      );
      $wpdb->query(
        "
        UPDATE $articles_table_name a
        INNER JOIN (
          SELECT post_id, meta_value
          FROM $wp_postmeta_table
          WHERE meta_key = 'paperview_publisher__last_sync'
        ) _aid ON a.wp_post_id = _aid.post_id
        SET a.last_sync = _aid.meta_value
        WHERE a.last_sync = 0
        "
      );

      ///////////////////

      $wpdb->query(
        "
        UPDATE $wp_usermeta_table
        SET meta_key = 'paperview_publisher__user_id'
        WHERE meta_key = 'paperview_user'
        "
      );

      ///////////////////

      $settle_db_changes();

      $post_meta_keys_to_delete = [
        'paperview_publisher__article_id',
        'paperview_publisher__paywall_availability',
        'paperview_publisher__price',
        'paperview_publisher__last_sync'
      ];
      foreach($post_meta_keys_to_delete as $key_to_delete) {
        delete_metadata('post', 0, $key_to_delete, null, true);
      }

      $wpdb->query("DROP TABLE IF EXISTS {$publication_info_table_name}__old");
      $wpdb->query("DROP TABLE IF EXISTS {$articles_table_name}__old");

      ///////////////////

      update_option('paperview_publisher__db_version', '0.8.0');
    }

    ///////////////////

    if(version_compare($existing_db_version, '0.8.2', '<')) {
      $editions_table_name =
        $wpdb->prefix . 'paperview_publisher_editions';
      $edition_articles_table_name =
        $wpdb->prefix . 'paperview_publisher_edition_articles';

      ///////////////////

      $wpdb->query(
        "
        UPDATE $wp_term_taxonomy_table
        SET taxonomy = 'paperview_publisher_pack'
        WHERE taxonomy = 'paperview_publisher_edition'
        "
      );

      ///////////////////

      $wpdb->query(
        "
        UPDATE $wp_usermeta_table
        SET meta_key = 'paperview_publisher__last_selected_packs'
        WHERE meta_key = 'paperview_publisher__last_selected_editions'
        "
      );

      ///////////////////

      $wpdb->query(
        "
        ALTER TABLE $edition_articles_table_name
        CHANGE COLUMN edition_id pack_id bigint(20) UNSIGNED NOT NULL DEFAULT 0
        "
      );
      $wpdb->query("ALTER TABLE $edition_articles_table_name RENAME $pack_articles_table_name");

      $wpdb->query(
        "
        ALTER TABLE $editions_table_name
        CHANGE COLUMN edition_id pack_id varchar(512) NOT NULL DEFAULT ''
        "
      );
      $wpdb->query("ALTER TABLE $editions_table_name RENAME $packs_table_name");

      $settle_db_changes();

      ///////////////////

      update_option('paperview_publisher__db_version', '0.8.2');
    }

    ///////////////////

    update_option(
      'paperview_publisher__db_version',
      PAPERVIEW_PUBLISHER__DB_VERSION
    );
  }

  ///////////////////

  delete_transient('paperview_publisher__is_upgrading_db');
}

function paperview_publisher__db__uninstall() {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  global $wpdb;

  $remove_plugin_data_on_uninstall =
    paperview_publisher__plugin_option__remove_plugin_data_on_uninstall();
  if($remove_plugin_data_on_uninstall === true) {
    $wpdb->query('START TRANSACTION');

    $option_keys_to_delete = [
      'paperview_publisher__db_version',
      'paperview_publisher__options',
      'paperview_publisher__last_up_check',
    ];
    foreach($option_keys_to_delete as $key_to_delete) {
      delete_option($key_to_delete);
    }

    $post_meta_keys_to_delete = [
      'paperview_publisher__paperview_article'
    ];
    foreach($post_meta_keys_to_delete as $key_to_delete) {
      delete_metadata('post', 0, $key_to_delete, null, true);
    }

    delete_metadata(
      'user',
      0,
      'paperview_publisher__user_id',
      null,
      true
    );
    delete_metadata(
      'user',
      0,
      'paperview_publisher__last_selected_packs',
      null,
      true
    );

    $publication_info_table_name =
      $wpdb->prefix . 'paperview_publisher_publication_info';
    $articles_table_name =
      $wpdb->prefix . 'paperview_publisher_articles';
    $packs_table_name =
      $wpdb->prefix . 'paperview_publisher_packs';
    $pack_articles_table_name =
      $wpdb->prefix . 'paperview_publisher_pack_articles';

    $wpdb->query("DROP TABLE IF EXISTS $publication_info_table_name");
    $wpdb->query("DROP TABLE IF EXISTS $articles_table_name");
    $wpdb->query("DROP TABLE IF EXISTS $packs_table_name");
    $wpdb->query("DROP TABLE IF EXISTS $pack_articles_table_name");

    $wpdb->query('COMMIT');
  }
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__store_publication_info(
  $publication_id,
  $last_data_fetch,
  $publication_currency,
  $publication_currency_subunit_to_unit,
  $article_default_price_amount
  ) {
  global $wpdb;

  $publication_info_table_name =
    $wpdb->prefix . 'paperview_publisher_publication_info';

  if(empty($last_data_fetch)) {
    $last_data_fetch = current_time('mysql', true);
  }

  $number_of_matching_rows =
    $wpdb->get_var(
      $wpdb->prepare(
        "
        SELECT COUNT(1)
        FROM $publication_info_table_name
        WHERE publication_id = %s
        ",
        $publication_id
      )
    );

  if($number_of_matching_rows == 0) {
    $wpdb->insert(
      $publication_info_table_name,
      [
        'publication_id' => $publication_id,
        'last_data_fetch' => $last_data_fetch,
        'publication_currency' => $publication_currency,
        'publication_currency_subunit_to_unit' =>
          $publication_currency_subunit_to_unit,
        'article_default_price_amount' => $article_default_price_amount,
      ]
    );

  } else {
    $wpdb->update(
      $publication_info_table_name,
      [
        'last_data_fetch' => $last_data_fetch,
        'publication_currency' => $publication_currency,
        'publication_currency_subunit_to_unit' =>
          $publication_currency_subunit_to_unit,
        'article_default_price_amount' => $article_default_price_amount,
      ],
      ['publication_id' => $publication_id]
    );
  }
}

function paperview_publisher__db__get_publication_info($publication_id = null) {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  global $wpdb;

  if(empty($publication_id)) {
    $publication_id = paperview_publisher__plugin_option__publication_id();
  }

  $publication_info_table_name =
    $wpdb->prefix . 'paperview_publisher_publication_info';

  $the_result =
    $wpdb->get_row(
      $wpdb->prepare(
        "
        SELECT *
        FROM $publication_info_table_name
        WHERE publication_id = %s
        ",
        $publication_id
      ),
      ARRAY_A
    );

  return $the_result;
}

function paperview_publisher__db__get_post_publication_info($post_id = null) {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  global $wpdb;

  $publication_info_table_name =
    $wpdb->prefix . 'paperview_publisher_publication_info';
  $articles_table_name =
    $wpdb->prefix . 'paperview_publisher_articles';

  $the_result =
    $wpdb->get_row(
      $wpdb->prepare(
        "
        SELECT *
        FROM $publication_info_table_name
        WHERE publication_id IN
          (
            SELECT publication_id
            FROM $articles_table_name
            WHERE wp_post_id = %d
          )
        ",
        $post_id
      ),
      ARRAY_A
    );

  return $the_result;
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__store_article_info(
  $post_id,
  $info_to_store = []
  ) {
  global $wpdb;

  $db_info_to_store = [];
  if(!empty($info_to_store)) {

    if(array_key_exists('publication_id', $info_to_store)) {
      $db_info_to_store['publication_id'] = $info_to_store['publication_id'];
    }

    if(array_key_exists('article_id', $info_to_store)) {
      $db_info_to_store['article_id'] = $info_to_store['article_id'];
    }

    if(array_key_exists('real_content', $info_to_store)) {
      $db_info_to_store['real_content'] = $info_to_store['real_content'];
    }

    if(array_key_exists('paywall_availability', $info_to_store)) {
      $db_info_to_store['paywall_availability'] =
        $info_to_store['paywall_availability'];
    }

    if(array_key_exists('price', $info_to_store)) {
      $db_info_to_store['price'] = $info_to_store['price'];
    }

    if(array_key_exists('last_sync', $info_to_store)) {
      $db_info_to_store['last_sync'] = $info_to_store['last_sync'];
    }
  }

  $articles_table_name = $wpdb->prefix . 'paperview_publisher_articles';

  $number_of_matching_rows =
    $wpdb->get_var(
      $wpdb->prepare(
        "
        SELECT COUNT(1)
        FROM $articles_table_name
        WHERE wp_post_id = %d
        ",
        $post_id
      )
    );

  if($number_of_matching_rows == 0) {
    $db_info_to_store['wp_post_id'] = $post_id;

    $wpdb->insert(
      $articles_table_name,
      $db_info_to_store
    );

  } else {
    $wpdb->update(
      $articles_table_name,
      $db_info_to_store,
      ['wp_post_id' => $post_id]
    );
  }
}

function paperview_publisher__db__get_article_info($post_id) {
  global $wpdb;

  $articles_table_name = $wpdb->prefix . 'paperview_publisher_articles';

  $the_result =
    $wpdb->get_row(
      $wpdb->prepare(
        "
        SELECT *
        FROM $articles_table_name
        WHERE wp_post_id = %d
        ",
        $post_id
      ),
      ARRAY_A
    );

  return $the_result;
}

function paperview_publisher__db__delete_article($post_id) {
  global $wpdb;

  $articles_table_name =
    $wpdb->prefix . 'paperview_publisher_articles';
  $pack_articles_table_name =
    $wpdb->prefix . 'paperview_publisher_pack_articles';

  $wpdb->query(
    $wpdb->prepare(
      "
      DELETE ea
      FROM
        $pack_articles_table_name ea
        INNER JOIN $articles_table_name a ON ea.article_id = a.id
      WHERE
        a.wp_post_id = %d
      ",
      $post_id
    )
  );

  $wpdb->delete(
    $articles_table_name,
    ['wp_post_id' => $post_id]
  );
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__store_post_data(
  $post_id,
  $content = null,
  $title = null,
  $teaser = null,
  $summary = null
  ) {
  global $wpdb;

  $the_data_to_save = [];

  if(!empty($content)) {
    $the_data_to_save['post_content'] = $content;
  }

  if(!empty($title)) {
    $the_data_to_save['post_title'] = $title;
  }

  if(!empty($teaser)) {
    $the_data_to_save['post_excerpt'] = $teaser;
  }

  if(!empty($summary)) {
    // $the_data_to_save['post_content'] = $summary;
  }

  if(!empty($the_data_to_save)) {
    $wpdb->update(
      $wpdb->posts,
      $the_data_to_save,
      ['ID' => $post_id]
    );
  }
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__correct_posts_terms_relations() {
  ///////////////////////////////////////

  require_once 'taxonomies.php';

  ///////////////////////////////////////

  global $wpdb;

  $articles_table_name =
    $wpdb->prefix . 'paperview_publisher_articles';
  $packs_table_name =
    $wpdb->prefix . 'paperview_publisher_packs';
  $pack_articles_table_name =
    $wpdb->prefix . 'paperview_publisher_pack_articles';

  $wp_term_relationships_table = $wpdb->term_relationships;
  $wp_term_taxonomy_table = $wpdb->term_taxonomy;
  $wp_terms_table = $wpdb->terms;

  // Remove non-desired article-term associations
  $wpdb->query(
    "
    DELETE wptr
    FROM
      $wp_term_relationships_table wptr
      INNER JOIN $wp_term_taxonomy_table wptt ON
        wptr.term_taxonomy_id = wptt.term_taxonomy_id AND
        wptt.taxonomy = '" . PAPERVIEW_PUBLISHER__PACK_TAXONOMY . "'
      INNER JOIN $wp_terms_table wpt ON wptt.term_id = wpt.term_id
      LEFT JOIN $articles_table_name a ON a.wp_post_id = wptr.object_id
      LEFT JOIN $packs_table_name e ON e.wp_term_id = wpt.term_id
      LEFT JOIN $pack_articles_table_name ea ON
        ea.article_id = a.id AND ea.pack_id = e.id
    WHERE
      e.id IS NULL OR
      a.id IS NULL OR
      ea.article_id IS NULL
    "
  );

  // Insert any mising article-term associations
  $wpdb->query(
    "
    INSERT INTO $wp_term_relationships_table (object_id, term_taxonomy_id)
    SELECT a.wp_post_id, wptt.term_taxonomy_id
    FROM
      $wp_term_taxonomy_table wptt
      INNER JOIN $wp_terms_table wpt ON wptt.term_id = wpt.term_id
      INNER JOIN $packs_table_name e ON e.wp_term_id = wpt.term_id
      INNER JOIN $pack_articles_table_name ea ON ea.pack_id = e.id
      INNER JOIN $articles_table_name a ON ea.article_id = a.id
      LEFT JOIN $wp_term_relationships_table wptr ON
        wptr.object_id = a.wp_post_id AND
        wptr.term_taxonomy_id = wptt.term_taxonomy_id
    WHERE
      wptt.taxonomy = '" . PAPERVIEW_PUBLISHER__PACK_TAXONOMY . "' AND
      wptr.object_id IS NULL
    "
  );
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__store_pack_info(
  $pack_id,
  $info_to_store = []
  ) {
  ///////////////////////////////////////

  require_once 'taxonomies.php';

  ///////////////////////////////////////

  global $wpdb;

  $db_info_to_store = [];
  if(!empty($info_to_store)) {

    if(array_key_exists('publication_id', $info_to_store)) {
      $db_info_to_store['publication_id'] = $info_to_store['publication_id'];
    }

    if(array_key_exists('name', $info_to_store)) {
      $db_info_to_store['name'] = $info_to_store['name'];
    }

    if(array_key_exists('description', $info_to_store)) {
      $db_info_to_store['description'] = $info_to_store['description'];
    }

    if(array_key_exists('price', $info_to_store)) {
      $db_info_to_store['price'] = $info_to_store['price'];
    }

    if(array_key_exists('starts_on', $info_to_store)) {
      $db_info_to_store['starts_on'] = $info_to_store['starts_on'];
    }

    if(array_key_exists('ends_on', $info_to_store)) {
      $db_info_to_store['ends_on'] = $info_to_store['ends_on'];
    }

    if(array_key_exists('available', $info_to_store)) {
      $db_info_to_store['available'] = ($info_to_store['available'] ? 1 : 0);
    }

    if(array_key_exists('available', $info_to_store)) {
      $db_info_to_store['available'] = ($info_to_store['available'] ? 1 : 0);
    }
  }

  $packs_table_name = $wpdb->prefix . 'paperview_publisher_packs';

  $number_of_matching_rows =
    $wpdb->get_var(
      $wpdb->prepare(
        "
        SELECT COUNT(1)
        FROM $packs_table_name
        WHERE pack_id = %s
        ",
        $pack_id
      )
    );

  if($number_of_matching_rows == 0) {
    $db_info_to_store['pack_id'] = $pack_id;

    $wpdb->insert(
      $packs_table_name,
      $db_info_to_store
    );

  } else {
    $wpdb->update(
      $packs_table_name,
      $db_info_to_store,
      ['pack_id' => $pack_id]
    );
  }

  if(array_key_exists('name', $info_to_store)) {

    $pack_info =
      $wpdb->get_row(
        $wpdb->prepare(
          "
          SELECT *
          FROM $packs_table_name
          WHERE pack_id = %s
          ",
          $pack_id
        ),
        ARRAY_A
      );

    $pack_name = $info_to_store['name'];
    $pack_slug = sanitize_title($pack_name);
    $pack_description = $pack_info['description'];

    if(!empty($pack_info['wp_term_id'])) {
      wp_update_term(
        $pack_info['wp_term_id'],
        PAPERVIEW_PUBLISHER__PACK_TAXONOMY,
        [
          'name'        => $pack_name,
          'description' => $pack_description,
          'slug'        => $pack_slug,
        ]
      );

    } else {
      $term_info =
        term_exists($pack_name, PAPERVIEW_PUBLISHER__PACK_TAXONOMY);

      if($term_info !== null) {
        $wpdb->update(
          $packs_table_name,
          [
            'wp_term_id' => $term_info['term_id']
          ],
          ['pack_id' => $pack_id]
        );
        wp_update_term(
          $term_info['term_id'],
          PAPERVIEW_PUBLISHER__PACK_TAXONOMY,
          [
            'description' => $pack_description,
            'slug'        => $pack_slug,
          ]
        );

      } else {
        $insert_result = wp_insert_term(
          $pack_name,
          PAPERVIEW_PUBLISHER__PACK_TAXONOMY,
          [
            'description' => $pack_description,
            'slug'        => $pack_slug,
          ]
        );

        if(!is_wp_error($insert_result)) {
          $wpdb->update(
            $packs_table_name,
            [
              'wp_term_id' => $insert_result['term_id']
            ],
            ['pack_id' => $pack_id]
          );
        }
      }
    }
  }
}

function paperview_publisher__db__get_packs(
  $publication_id = null,
  $available_packs = null,
  $pack_ids = null,
  $columns_to_fetch = null
  ) {
  global $wpdb;

  $publication_info =
    paperview_publisher__db__get_publication_info($publication_id);
  if(empty($publication_info)) {
    return [];
  }

  $packs_table_name =
    $wpdb->prefix . 'paperview_publisher_packs';

  if($available_packs === true) {
    $condition__available_packs = 'available = 1';
  } elseif($available_packs === false) {
    $condition__available_packs = 'available = 0';
  } else {
    $condition__available_packs = '1=1';
  }

  if(!empty($pack_ids)) {
    $pack_ids_str = implode(
      ',',
      array_map(
        function($pack_id) { return '\'' . esc_sql($pack_id) . '\''; },
        $pack_ids
      )
    );
    $condition__pack_ids = "pack_id IN ($pack_ids_str)";
  } else {
    $condition__pack_ids = '1=1';
  }

  if(empty($columns_to_fetch)) {
    $columns_to_fetch = [
      'pack_id',
      'name'
    ];
  }

  $select_columns_str = implode(',', $columns_to_fetch);

  $the_result =
    $wpdb->get_results(
      "
      SELECT $select_columns_str
      FROM $packs_table_name
      WHERE
        publication_id = {$publication_info['id']} AND
        $condition__available_packs AND
        $condition__pack_ids
      ",
      ARRAY_A
    );

  return $the_result;
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__db__get_article_packs(
  $post_id,
  $columns_to_fetch = null,
  $explicit_assignment = null
  ) {
  global $wpdb;

  if(empty($post_id)) {
    return [];
  }

  $articles_table_name =
    $wpdb->prefix . 'paperview_publisher_articles';
  $packs_table_name =
    $wpdb->prefix . 'paperview_publisher_packs';
  $pack_articles_table_name =
    $wpdb->prefix . 'paperview_publisher_pack_articles';

  if(empty($columns_to_fetch)) {
    $columns_to_fetch = [
      'pack_id',
      'name'
    ];
  }

  $scoped_columns_to_fetch = array_map(
    function($col) {
      switch($col) {
        case 'explicit_assignment':
          return 'ea.explicit_assignment';
        default:
          return "e.$col";
      }
    },
    $columns_to_fetch
  );
  $select_columns_str = implode(',', $scoped_columns_to_fetch);

  $where_conditions_str = '1=1';
  if($explicit_assignment === true) {
    $where_conditions_str .= ' AND ea.explicit_assignment = 1';
  } elseif($explicit_assignment === false) {
    $where_conditions_str .= ' AND ea.explicit_assignment = 0';
  }

  $the_result =
    $wpdb->get_results(
      $wpdb->prepare(
        "
        SELECT $select_columns_str
        FROM
          $pack_articles_table_name ea
          INNER JOIN $packs_table_name e ON e.id = ea.pack_id
          INNER JOIN $articles_table_name a ON a.id = ea.article_id
        WHERE
          a.wp_post_id = %d AND
          $where_conditions_str
        ",
        $post_id
      ),
      ARRAY_A
    );

  if(in_array('explicit_assignment', $columns_to_fetch)) {
    array_walk(
      $the_result,
      function(&$pack) {
        $pack['explicit_assignment'] = ($pack['explicit_assignment'] != '0');
      }
    );
  }

  return $the_result;
}

function paperview_publisher__db__set_article_packs(
  $post_id,
  $pack_ids_explicitly_assigned = null,
  $pack_ids_implicitly_assigned = null
  ) {
  global $wpdb;

  $articles_table_name =
    $wpdb->prefix . 'paperview_publisher_articles';
  $packs_table_name =
    $wpdb->prefix . 'paperview_publisher_packs';
  $pack_articles_table_name =
    $wpdb->prefix . 'paperview_publisher_pack_articles';

  if($pack_ids_explicitly_assigned == [] &&
     $pack_ids_implicitly_assigned == []
     ) {
    $wpdb->query(
      $wpdb->prepare(
        "
        DELETE ea
        FROM
          $pack_articles_table_name ea
          INNER JOIN $articles_table_name a ON ea.article_id = a.id
        WHERE a.wp_post_id = %d
        ",
        $post_id
      )
    );
    return;
  }

  $handle_assignments =
    function($pack_ids, $explicit_assignment_flag) use ($post_id, $articles_table_name, $packs_table_name, $pack_articles_table_name) {
      global $wpdb;

      if($pack_ids === null) {
        return;
      }

      if($pack_ids == []) {
        $wpdb->query(
          $wpdb->prepare(
            "
            DELETE ea
            FROM
              $pack_articles_table_name ea
              INNER JOIN $articles_table_name a ON ea.article_id = a.id
            WHERE
              ea.explicit_assignment = $explicit_assignment_flag AND
              a.wp_post_id = %d
            ",
            $post_id
          )
        );
        return;
      }

      $pack_ids__placeholders = implode(
        ',',
        array_fill(0, count($pack_ids), '%s')
      );

      $wpdb->query(
        $wpdb->prepare(
          "
          DELETE ea
          FROM
            $pack_articles_table_name ea
            INNER JOIN $articles_table_name a ON ea.article_id = a.id
            INNER JOIN $packs_table_name e ON ea.pack_id = e.id
          WHERE
            a.wp_post_id = %d AND
            (
              (
                e.pack_id NOT IN ($pack_ids__placeholders) AND
                ea.explicit_assignment = $explicit_assignment_flag
              ) OR
              (
                e.pack_id IN ($pack_ids__placeholders) AND
                ea.explicit_assignment <> $explicit_assignment_flag
              )
            )
          ",
          array_merge(
            array($post_id),
            $pack_ids,
            $pack_ids
          )
        )
      );

      $wpdb->query(
        $wpdb->prepare(
          "
          INSERT INTO $pack_articles_table_name (article_id, pack_id, explicit_assignment)
          SELECT a.id AS a_id, e.id AS e_id, $explicit_assignment_flag
          FROM
            $articles_table_name a
            INNER JOIN $packs_table_name e ON a.publication_id = e.publication_id
            LEFT JOIN $pack_articles_table_name ea ON ea.article_id = a.id AND ea.pack_id = e.id
          WHERE
            a.wp_post_id = %d AND
            e.pack_id IN ($pack_ids__placeholders) AND
            ea.id IS NULL
          ",
          array_merge(
            array($post_id),
            $pack_ids
          )
        )
      );
    };

  $handle_assignments($pack_ids_implicitly_assigned, 0);
  $handle_assignments($pack_ids_explicitly_assigned, 1);
}





// TODO: not being used yet
function paperview_publisher__db__get_pack_articles(
  $pack_id,
  $publication_id = null
  ) {
  global $wpdb;

  if(empty($pack_id)) {
    return [];
  }

  $publication_info =
    paperview_publisher__db__get_publication_info($publication_id);
  if(empty($publication_info)) {
    return [];
  }

  $articles_table_name =
    $wpdb->prefix . 'paperview_publisher_articles';
  $packs_table_name =
    $wpdb->prefix . 'paperview_publisher_packs';
  $pack_articles_table_name =
    $wpdb->prefix . 'paperview_publisher_pack_articles';

  $the_result =
    $wpdb->get_results(
      $wpdb->prepare(
        "
        SELECT a.article_id
        FROM
          $pack_articles_table_name ea
          INNER JOIN $packs_table_name e ON e.id = ea.pack_id
          INNER JOIN $articles_table_name a ON a.id = ea.article_id
        WHERE
          e.publication_id = %s AND
          e.pack_id = %s
        ",
        $publication_info['id'],
        $pack_id
      ),
      ARRAY_A
    );

  return $the_result;
}






////////////////////////////////////////////////////////////////////////////////
