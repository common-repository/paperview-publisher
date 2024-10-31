<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

add_filter(
  'paperview__plugins_info',
  function($paperview_plugins) {
    $paperview_plugins['paperview-publisher'] = [
      __('Name', 'paperview-publisher') => __('Paperview Publisher', 'paperview-publisher')
    ];
    return $paperview_plugins;
  }
);

////////////////////////////////////////////////////////////////////////////////

add_action(
  'paperview_plugin__system_status_info',
  function() {
    // TODO
  }
);

////////////////////////////////////////////////////////////////////////////////

add_action(
  'admin_menu',
  function() {
    ///////////////////////////////////////

    require_once 'paperview__top_level_admin_menu.php';

    ///////////////////////////////////////

    add_submenu_page(
      PAPERVIEW__TOP_LEVEL_ADMIN_MENU_NODE,
      __('Paperview Publisher settings', 'paperview-publisher'),
      __('Publisher settings', 'paperview-publisher'),
      'manage_options',
      'paperview_publisher__admin',
      'paperview_publisher__admin_general_settings_page'
    );

    add_submenu_page(
      PAPERVIEW__TOP_LEVEL_ADMIN_MENU_NODE,
      __('Paperview Publication packs', 'paperview-publisher'),
      __('Publication packs', 'paperview-publisher'),
      'manage_options',
      'paperview_publisher__packs',
      'paperview_publisher__packs_page'
    );
  }
);

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__admin_general_settings_page() {
  ///////////////////////////////////////

  require_once 'paperview__flash_notices.php';

  require_once 'plugin_options.php';

  require_once 'article_utils.php';

  ///////////////////////////////////////

  if(!current_user_can('manage_options')) {
    return '';
  }

  paperview_publisher__admin_general_settings_page__save_settings();

  $form_nonce =
    wp_create_nonce('paperview_publisher_admin_general_settings_form_nonce');

  $registered_post_types = get_post_types(
    [
      'public' => true
    ],
    'objects'
  );
  $supported_post_types =
    paperview_publisher__plugin_option__supported_post_types();

  $registered_post_statuses = get_post_stati(
    [
      'internal' => false
    ],
    'objects'
  );
  $supported_post_statuses =
    paperview_publisher__plugin_option__supported_post_statuses();

  $pack_url_slug = paperview_publisher__plugin_option__pack_url_slug();
  if(empty($pack_url_slug)) {
    $pack_url_slug = 'pack';
  }

  ?>
  <h1 class="wp-heading-inline"><?php _e('Paperview Publisher settings', 'paperview-publisher'); ?></h1>
  <hr class="wp-header-end">

  <?php settings_errors(); ?>
  <?php display_flash_notices(); ?>

  <form action="" method="post" id="paperview_publisher_plugin_options_form">
    <input type="hidden" name="_nonce" value="<?php echo $form_nonce; ?>">
    <input type="hidden" name="action" value="save_paperview_publisher_general_settings">

    <table class="form-table">
      <tr class="connect-to-sandbox">
        <th scope="row"><?php _e('Paperview environment', 'paperview-publisher'); ?></th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><?php _e('Paperview environment', 'paperview-publisher'); ?></legend>
            <input type="hidden" name="connect_to_sandbox" value="0">
            <label>
              <input type="checkbox" name="connect_to_sandbox" value="1" <?php checked(paperview_publisher__plugin_option__connect_to_sandbox()); ?> >
              <?php _e('Connect to Paperview sandbox', 'paperview-publisher'); ?>
            </label>
          </fieldset>
        </td>
      </tr>
      <tr class="publication-id">
        <th scope="row"><label for="publication_id"><?php _e('Publication ID', 'paperview-publisher'); ?></label></th>
        <td>
          <input type="text" name="publication_id" id="publication_id" value="<?php echo esc_attr(paperview_publisher__plugin_option__publication_id()); ?>" class="regular-text" />
          <p class="description"><?php _e('Please enter the unique ID for this Publication', 'paperview-publisher'); ?></p>
        </td>
      </tr>
      <tr class="publication-api-key">
        <th scope="row"><label for="publication_api_key"><?php _e('Publication API key', 'paperview-publisher'); ?></label></th>
        <td>
          <input type="text" name="publication_api_key" id="publication_api_key" value="<?php echo esc_attr(paperview_publisher__plugin_option__publication_api_key()); ?>" class="regular-text" />
        </td>
      </tr>
      <tr class="link-to-paperview-publication">
        <td></td>
        <td>
          <input type="hidden" id="_link_to_paperview" name="_link_to_paperview" value="">
          <a class="link-post-to-article" href="#"><?php echo esc_attr(__('Link to Publication on Paperview', 'paperview-publisher')); ?></a>
        </td>
      </tr>
    </table>

    <table class="form-table">
      <tr class="article--default-author-user-id">
        <th scope="row"><label for="article__default_author_user_id"><?php _e('Default User ID for article authors', 'paperview-publisher'); ?></label></th>
        <td>
          <input type="text" name="article__default_author_user_id" id="article__default_author_user_id" value="<?php echo esc_attr(paperview_publisher__plugin_option__article__default_author_user_id()); ?>" class="regular-text" />
          <p class="description"><?php _e('Please enter the Paperview User ID with which your articles should be associated by default.<br/>If the WordPress user (or the current user) has an associated Paperview User ID, that one will be used instead.<br/>If this is left blank, no default Paperview User ID will be assumed.', 'paperview-publisher'); ?></p>
        </td>
      </tr>
      <tr class="article--default-paywall-availability">
        <th scope="row"><label for="article__default_paywall_availability"><?php _e('Default transaction type', 'paperview-publisher'); ?></label></th>
        <td>
          <select name="article__default_paywall_availability" id="article__default_paywall_availability">
            <?php paperview_publisher__article_paywall_availability__values_as_select_options(
                    paperview_publisher__plugin_option__article__default_paywall_availability()
                  ); ?>
          </select>
        </td>
      </tr>
      <tr class="article-protection-options">
        <th scope="row"><?php _e('Article protection', 'paperview-publisher'); ?></th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><?php _e('Article protection', 'paperview-publisher'); ?></legend>
            <input type="hidden" name="send_article_to_paperview_default" value="0">
            <label>
              <input type="checkbox" name="send_article_to_paperview_default" value="1" <?php checked(paperview_publisher__plugin_option__send_article_to_paperview_default()); ?> >
              <?php _e('By default, activate Paperview in new articles', 'paperview-publisher'); ?>
            </label>
            <br />
            <input type="hidden" name="get_content_gibberish" value="0">
            <label>
              <input type="checkbox" name="get_content_gibberish" value="1" <?php checked(paperview_publisher__plugin_option__get_content_gibberish()); ?> >
              <?php _e('Save articles with content gibberish', 'paperview-publisher'); ?>
            </label>
            <br />
            <input type="hidden" name="get_paywall_invoker_gibberish" value="0">
            <label>
              <input type="checkbox" name="get_paywall_invoker_gibberish" value="1" <?php checked(paperview_publisher__plugin_option__get_paywall_invoker_gibberish()); ?> >
              <?php _e('Save articles with paywall invoker script', 'paperview-publisher'); ?>
            </label>
          </fieldset>
        </td>
      </tr>
    </table>

    <table class="form-table">
      <tr class="post-types">
        <th scope="row"><?php _e('Available for these post types', 'paperview-publisher'); ?></th>
        <td>
          <?php foreach($registered_post_types as $post_type) { ?>
            <div>
              <label>
                <input type="checkbox" name="supported_post_types[]" value="<?php echo $post_type->name; ?>" <?php checked(in_array($post_type->name, $supported_post_types)); ?> >
                <?php echo $post_type->label; ?>
              </label>
            </div>
          <?php } ?>
        </td>
      </tr>
    </table>

    <table class="form-table">
      <tr class="post-statuses">
        <th scope="row"><?php _e('Available for these post statuses', 'paperview-publisher'); ?></th>
        <td>
          <?php foreach($registered_post_statuses as $post_status) { ?>
            <div>
              <label>
                <input type="checkbox" name="supported_post_statuses[]" value="<?php echo $post_status->name; ?>" <?php checked(in_array($post_status->name, $supported_post_statuses)); ?> >
                <?php echo $post_status->label; ?>
              </label>
            </div>
          <?php } ?>
        </td>
      </tr>
    </table>

    <table class="form-table">
      <tr class="pack-url-slug">
        <th scope="row"><label for="pack_url_slug"><?php _e('Pack URL slug', 'paperview-publisher'); ?></label></th>
        <td>
          <input type="text" name="pack_url_slug" id="pack_url_slug" value="<?php echo esc_attr($pack_url_slug); ?>" class="regular-text" />
          <p class="description">
            <?php _e('Please enter the URL identifier slug to be used for Packs.', 'paperview-publisher'); ?>
            <br/>
            <?php printf(
              /* translators: 1: example URL */
              __('Example URL: %s', 'paperview-publisher'),
              "<span class=\"url\">https://example.com/<span class=\"relevant\">{$pack_url_slug}</span>/hello-world</span>"
            ); ?>
            <br/>
            <?php printf(
              /* translators: refresh permalinks link */
              _x('If you change this field, you may have to %s.', 'permalink refresh', 'paperview-publisher'),
              '<a href="'.get_site_url(null, 'wp-admin/options-permalink.php').'">'._x('refresh permalinks', 'permalink refresh', 'paperview-publisher').'</a>'
            ); ?>
          </p>
        </td>
      </tr>
    </table>

    <table class="form-table">
      <tr class="remove-plugin-data-on-uninstall">
        <th scope="row"><?php _e('Plugin uninstall', 'paperview-publisher'); ?></th>
        <td>
          <fieldset>
            <legend class="screen-reader-text"><?php _e('Plugin uninstall', 'paperview-publisher'); ?></legend>
            <input type="hidden" name="remove_plugin_data_on_uninstall" value="0">
            <label>
              <input type="checkbox" name="remove_plugin_data_on_uninstall" value="1" <?php checked(paperview_publisher__plugin_option__remove_plugin_data_on_uninstall()); ?> >
              <?php _e('Remove plugin data when uninstalling', 'paperview-publisher'); ?>
            </label>
            <p class="description">
              <?php _e('When checked, this plugin\'s data (including contents stored in clear text) will be removed if this plugin is uninstalled.', 'paperview-publisher'); ?>
              <br/>
              <span class="warning"><?php _e('Be aware that this removal is irreversible! Backup your data first!', 'paperview-publisher'); ?></span>
            </p>
          </fieldset>
        </td>
      </tr>
    </table>

    <p class="submit save-settings">
      <input type="submit" value="<?php echo esc_attr(__('Save settings', 'paperview-publisher')); ?>" class="button-primary">
    </p>
  </form>
  <?php
}

function paperview_publisher__admin_general_settings_page__save_settings() {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  require_once 'article_utils.php';

  require_once 'paperview__flash_notices.php';

  require_once 'publisher_utils.php';

  ///////////////////////////////////////

  if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
  }

  $nonce_is_valid = wp_verify_nonce(
    $_POST['_nonce'],
    'paperview_publisher_admin_general_settings_form_nonce'
  );
  if(!$nonce_is_valid) {
    wp_die(
      __('Invalid nonce specified', 'paperview-publisher'),
      __('Error', 'paperview-publisher'),
      [
        'response'  => 403,
        'back_link' => menu_page_url('paperview_publisher__admin', false),
      ]
    );
    return;
  }

  $old_connect_to_sandbox =
    paperview_publisher__plugin_option__connect_to_sandbox();
  $old_publication_id = paperview_publisher__plugin_option__publication_id();
  $old_publication_api_key =
    paperview_publisher__plugin_option__publication_api_key();

  $remove_plugin_data_on_uninstall =
    $_POST['remove_plugin_data_on_uninstall'] === '1';

  $connect_to_sandbox = $_POST['connect_to_sandbox'] === '1';

  $publication_id = sanitize_text_field($_POST['publication_id']);

  $publication_api_key = sanitize_text_field($_POST['publication_api_key']);

  $article__default_author_user_id =
    sanitize_text_field($_POST['article__default_author_user_id']);

  $article__default_paywall_availability =
    sanitize_text_field($_POST['article__default_paywall_availability']);
  if(!in_array($article__default_paywall_availability,
               paperview_publisher__article_paywall_availability__allowed_values()
               )
     ) {
    $article__default_paywall_availability =
      paperview_publisher__article_paywall_availability__default();
  }

  $send_article_to_paperview_default =
    $_POST['send_article_to_paperview_default'] === '1';

  $get_content_gibberish = $_POST['get_content_gibberish'] === '1';

  $get_paywall_invoker_gibberish =
    $_POST['get_paywall_invoker_gibberish'] === '1';

  if(isset($_POST['supported_post_types'])) {
    $supported_post_types = $_POST['supported_post_types'];
    if(empty($supported_post_types)) {
      $supported_post_types = [];
    } else {
      $supported_post_types =
        array_map('sanitize_text_field', $supported_post_types);
      $supported_post_types = array_filter($supported_post_types);
    }
  } else {
    $supported_post_types = [];
  }

  if(isset($_POST['supported_post_statuses'])) {
    $supported_post_statuses = $_POST['supported_post_statuses'];
    if(empty($supported_post_statuses)) {
      $supported_post_statuses = [];
    } else {
      $supported_post_statuses =
        array_map('sanitize_text_field', $supported_post_statuses);
      $supported_post_statuses = array_filter($supported_post_statuses);
    }
  } else {
    $supported_post_statuses = [];
  }

  $pack_url_slug = sanitize_title($_POST['pack_url_slug']);

  paperview_publisher__save_plugin_options(
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
  );

  $requested_relink_to_publication = $_POST['_link_to_paperview'] === '1';
  $need_to_relink_to_publication =
    $requested_relink_to_publication ||
    (
      !empty($publication_id) &&
      !empty($publication_api_key) &&
      (
        $old_connect_to_sandbox != $connect_to_sandbox ||
        $old_publication_id != $publication_id ||
        $old_publication_api_key != $publication_api_key
      )
    );

  if($need_to_relink_to_publication) {

    if($requested_relink_to_publication &&
       (empty($publication_id) || empty($publication_api_key))
       ) {
      add_flash_notice(
        __('Publication ID or API key are missing.', 'paperview-publisher'),
        'error'
      );

    } else {
      $linking_result = paperview_publisher__link_to_publication();
      if($linking_result) {
        add_flash_notice(
          __('Successfully linked to Paperview Publication.', 'paperview-publisher'),
          'info'
        );
      } else {
        add_flash_notice(
          __('Publication not found.', 'paperview-publisher'),
          'error'
        );
      }
    }

  } else {
    add_flash_notice(__('Changes saved.', 'paperview-publisher'), 'info');
  }
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__packs_page() {
  ///////////////////////////////////////

  require_once 'paperview__flash_notices.php';

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  if(!current_user_can('manage_options')) {
    return '';
  }

  paperview_publisher__packs_page__save_settings();

  $form_nonce = wp_create_nonce('paperview_publisher_packs_form_nonce');

  $publication_packs = paperview_publisher__db__get_packs(
    null,
    null,
    null,
    [
      'pack_id',
      'name',
      'available'
    ]
  );

  ?>
  <h1 class="wp-heading-inline"><?php _e('Packs', 'paperview-publisher'); ?></h1>
  <hr class="wp-header-end">

  <?php settings_errors(); ?>
  <?php display_flash_notices(); ?>

  <h2 class="wp-heading-inline"><?php _e('Active packs', 'paperview-publisher'); ?></h2>
  <table class="active-packs">
    <?php
    foreach($publication_packs as $pack) {
      if($pack['available'] == 1) {
    ?>
    <tr data-pack-id="<?php echo $pack['pack_id']; ?>">
      <td class="name">
        <?php echo $pack['name']; ?>
      </td>
    </tr>
    <?php
      }
    }
    ?>
  </table>

  <h2 class="wp-heading-inline"><?php _e('Previous packs', 'paperview-publisher'); ?></h2>
  <table class="previous-packs">
    <?php
    foreach($publication_packs as $pack) {
      if($pack['available'] == 0) {
    ?>
    <tr data-pack-id="<?php echo $pack['pack_id']; ?>">
      <td class="name">
        <?php echo $pack['name']; ?>
      </td>
    </tr>
    <?php
      }
    }
    ?>
  </table>

  <?php
}

function paperview_publisher__packs_page__save_settings() {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  require_once 'paperview__flash_notices.php';

  ///////////////////////////////////////

  if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
  }

  $nonce_is_valid = wp_verify_nonce(
    $_POST['_nonce'],
    'paperview_publisher_packs_form_nonce'
  );
  if(!$nonce_is_valid) {
    wp_die(
      __('Invalid nonce specified', 'paperview-publisher'),
      __('Error', 'paperview-publisher'),
      [
        'response'  => 403,
        'back_link' => menu_page_url('paperview_publisher__packs', false),
      ]
    );
    return;
  }






}

////////////////////////////////////////////////////////////////////////////////
