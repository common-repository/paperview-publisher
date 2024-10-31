<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

require_once 'plugin_options.php';

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__check_if_current_screen_is_editor() {
  if(!function_exists('get_current_screen')) {
    return false;
  }

  $paperview_publisher__post_types =
    paperview_publisher__plugin_option__supported_post_types();

  $screen = get_current_screen();

  if(!in_array($screen->post_type, $paperview_publisher__post_types)) {
    return false;
  }

  $is_in_add_screen =
    $screen->base == 'post' &&
    $screen->action == 'add';
  $is_in_edit_screen =
    $screen->base == 'post' &&
    empty($screen->action);

  return $is_in_add_screen || $is_in_edit_screen;
}

function paperview_publisher__check_if_current_screen_is_block_editor() {
  if(!function_exists('get_current_screen')) {
    return false;
  }
  return get_current_screen()->is_block_editor();
}

function paperview_publisher__check_if_current_screen_is_new_post() {
  if(!function_exists('get_current_screen')) {
    return false;
  }

  $paperview_publisher__post_types =
    paperview_publisher__plugin_option__supported_post_types();

  $screen = get_current_screen();

  if(!in_array($screen->post_type, $paperview_publisher__post_types)) {
    return false;
  }

  $is_in_add_screen =
    $screen->base == 'post' &&
    $screen->action == 'add';

  return $is_in_add_screen;
}

////////////////////////////////////////////////////////////////////////////////

add_action(
  'the_post',
  'paperview_publisher__handle_admin_post_fetch'
);

function paperview_publisher__handle_admin_post_fetch($post_object) {
  ///////////////////////////////////////

  require_once 'article_utils.php';

  ///////////////////////////////////////

  if(!is_admin()) {
    return;
  }

  if(paperview_publisher__check_if_current_screen_is_editor()) {
    $post_id = $post_object->ID;

    $the_content = paperview_publisher__get_article_content($post_id);
    if($the_content !== null) {
      $post_object->post_content = $the_content;
    }
  }
}

////////////////////////////////////////////////////////////////////////////////

add_action(
  'delete_post',
  function($post_id) {
    ///////////////////////////////////////

    require_once 'database.php';

    ///////////////////////////////////////

    paperview_publisher__db__delete_article($post_id);
  }
);

////////////////////////////////////////////////////////////////////////////////

add_action(
  'wp_ajax_paperview_publisher__sync_with_paperview_article',
  'paperview_publisher__sync_with_paperview_article__ajax_handler'
);
add_action(
  'add_meta_boxes',
  'paperview_publisher__add_post_config_box'
);
foreach(paperview_publisher__plugin_option__supported_post_types() as $post_type) {
  add_action(
    'save_post_' . $post_type,
    'paperview_publisher__save_post_config',
    10,
    2
  );
}

function paperview_publisher__add_post_config_box() {
  foreach(paperview_publisher__plugin_option__supported_post_types() as $post_type) {
    add_meta_box(
      'paperview_post_config',
      __('Paperview options', 'paperview-publisher'),
      'paperview_publisher__post_config_box',
      $post_type,
      'normal',
      'high'
    );
  }
}

function paperview_publisher__post_config_box($post) {
  ///////////////////////////////////////

  require_once 'database.php';

  require_once 'article_utils.php';

  require_once 'plugin_options.php';

  ///////////////////////////////////////

  if(paperview_publisher__check_if_current_screen_is_new_post()) {
    $post_id = null;
  } else {
    $post_id = $post->ID;
  }

  $publication_info = null;
  if($post_id !== null) {
    $publication_info =
      paperview_publisher__db__get_post_publication_info($post_id);
  }
  if(empty($publication_info)) {
    $publication_info = paperview_publisher__db__get_publication_info();
  }
  if(empty($publication_info)) { ?>
    <?php _e('You need to configure the Paperview Publisher plugin first.', 'paperview-publisher'); ?>
    <br/>
    <?php printf(
            /* translators: 1: link to config page */
            __('You can find the configuration page %s.', 'paperview-publisher'),
            '<a class="button-link" href="' .
              menu_page_url('paperview_publisher__admin', false) .
              '">' . __('here', 'paperview-publisher') . '</a>'
          ); ?>
    <?php
    return;
  }

  if($post_id === null) {
    $paperview_article = '';
    $article_info = null;

  } else {
    $paperview_article = get_post_meta(
      $post_id,
      'paperview_publisher__paperview_article',
      true
    );
    $article_info = paperview_publisher__db__get_article_info($post_id);
  }

  if($paperview_article === '') {
    $paperview_article =
      paperview_publisher__plugin_option__send_article_to_paperview_default();
    if($paperview_article) {
      $paperview_article = '1';
    } else {
      $paperview_article = '';
    }
  }

  if(!empty($article_info)) {
    $article_id = $article_info['article_id'];
    $paywall_availability = $article_info['paywall_availability'];
    $price = $article_info['price'];

  } else {
    $article_id = '';
    $paywall_availability = '';
    $price = '';
  }

  if(empty($paywall_availability)) {
    $paywall_availability =
      paperview_publisher__plugin_option__article__default_paywall_availability();
  }

  if(empty($price)) {
    $price = $publication_info['article_default_price_amount'];
  }

  $data_attr_article_content = '';
  if($post_id !== null) {
    if(!paperview_publisher__check_if_current_screen_is_block_editor()) {
      $article_content = paperview_publisher__get_article_content($post_id);
      if($article_content !== null) {
        $data_attr_article_content =
          'data-article-content="' . htmlspecialchars($article_content) . '"';
      }
    }
  }

  $available_packs = paperview_publisher__db__get_packs(
    $publication_info['publication_id'],
    true,  // $available_packs
    null,
    [
      'pack_id',
      'name',
    ]
  );

  if($post_id !== null) {
    $article_assigned_packs = paperview_publisher__db__get_article_packs(
      $post_id,
      [
        'pack_id',
        'name',
        'explicit_assignment'
      ]
    );
  } else {
    $user_last_article_packs =
      paperview_publisher__current_user__get_last_article_selected_packs();
    if(!empty($user_last_article_packs)) {
      $article_assigned_packs = array_map(
        function($pack) {
          $pack['explicit_assignment'] = true;
          return $pack;
        },
        paperview_publisher__db__get_packs(
          $publication_info['publication_id'],
          true,
          $user_last_article_packs
        )
      );
    } else {
      $article_assigned_packs = [];
    }
  }

  ?>
  <table class="form-table paperview-fields" <?php echo $data_attr_article_content; ?>>
    <tr class="paperview-article">
      <th scope="row"><label for="paperview_publisher__paperview_article"><?php _e('Activate Paperview', 'paperview-publisher'); ?></label></th>
      <td>
        <fieldset>
          <legend class="screen-reader-text"><?php _e('Activate Paperview', 'paperview-publisher'); ?></legend>
          <input type="hidden" name="paperview_publisher__paperview_article" value="0">
          <label>
            <input type="checkbox" name="paperview_publisher__paperview_article" value="1" <?php checked($paperview_article, '1'); ?> >
            <span class="description">
              <?php _e('When checked, this content will be processed by Paperview when saving.', 'paperview-publisher'); ?>
            </span>
          </label>
        </fieldset>
      </td>
    </tr>
    <?php if(!empty($available_packs) || !empty($article_assigned_packs)) { ?>
    <tr class="packs">
      <th scope="row"><label for="paperview_publisher__select_pack"><?php _e('Packs', 'paperview-publisher'); ?></label></th>
      <td>
        <input type="hidden" name="paperview_publisher__packs">
        <div class="assigned-packs" data-assigned-packs="<?php echo htmlspecialchars(json_encode($article_assigned_packs)); ?>">
          <div class="assigned-packs-list"></div>
        </div>
        <div class="available-packs" data-available-packs="<?php echo htmlspecialchars(json_encode($available_packs)); ?>">
          <input id="paperview_publisher__select_pack" type="text">
          <div class="available-packs-list">
            <div class="no-available-packs"><?php _e('No available packs.', 'paperview-publisher'); ?></div>
          </div>
        </div>
      </td>
    </tr>
    <?php } ?>
    <tr class="price">
      <th scope="row"><label for="paperview_publisher__price"><?php _e('Price', 'paperview-publisher'); ?></label></th>
      <td>
        <div>
          <input min="0.0" step="<?php echo (1 / $publication_info['publication_currency_subunit_to_unit']); ?>" value="<?php echo $price; ?>" type="number" name="paperview_publisher__price" id="paperview_publisher__price">
          <span class="currency"><?php echo $publication_info['publication_currency']; ?></span>
        </div>
      </td>
    </tr>
    <tr class="paywall-availability">
      <th scope="row"><label for="paperview_publisher__paywall_availability"><?php _e('Transaction type', 'paperview-publisher'); ?></label></th>
      <td>
        <select name="paperview_publisher__paywall_availability" id="paperview_publisher__paywall_availability">
          <?php paperview_publisher__article_paywall_availability__values_as_select_options($paywall_availability); ?>
        </select>
      </td>
    </tr>
    <tr class="article-id">
      <th scope="row"><label for="paperview_publisher__article_id"><?php _e('Article ID', 'paperview-publisher'); ?></label></th>
      <td>
        <?php if(empty($article_id)) { ?>
        <input type="text" name="paperview_publisher__article_id" id="paperview_publisher__article_id">
        <br/>
        <span class="description">
          <?php _e('Enter only if you want to link this post with a Paperview article that already exists.', 'paperview-publisher'); ?>
          <br/>
          <?php _e('Otherwise, leave this field blank, and it will be automatically filled out for you.', 'paperview-publisher'); ?>
          <br/>
          <span class="relevant"><?php _e('If you fill this field with a Paperview article ID, this post will reflect that article; any text you have entered will be lost.', 'paperview-publisher'); ?></span>
        </span>
        <?php } else { ?>
        <input value="<?php echo $article_id; ?>" type="text" id="paperview_publisher__article_id" disabled="disabled" readonly="readonly">
        <p style="display: none;">
          <a class="sync-post-with-article" data-nonce="<?php echo wp_create_nonce('paperview_publisher__sync_with_paperview_article'); ?>" data-post-id="<?php echo $post_id; ?>" href="#"><?php _e('Sync with Paperview', 'paperview-publisher'); ?></a>
        </p>
        <?php } ?>
      </td>
    </tr>
  </table>
  <?php
}

function paperview_publisher__save_post_config($post_id, $post) {
  ///////////////////////////////////////

  require_once 'database.php';

  require_once 'user_info.php';

  require_once 'article_utils.php';

  ///////////////////////////////////////

  if(!array_key_exists('paperview_publisher__paperview_article', $_POST)) {
    return;
  }

  $send_article_to_paperview =
    sanitize_text_field($_POST['paperview_publisher__paperview_article']);
  if(!in_array($send_article_to_paperview, [ '0', '1' ])) {
    $send_article_to_paperview = '0';
  }
  update_post_meta(
    $post_id,
    'paperview_publisher__paperview_article',
    $send_article_to_paperview
  );

  $article_id_provided_by_user = false;

  if($send_article_to_paperview === '1') {

    $publication_info = paperview_publisher__db__get_publication_info();
    if(empty($publication_info)) {
      add_flash_notice(
        __('Paperview Publisher plugin not configured. Article not saved.', 'paperview-publisher'),
        'error'
      );
      return;
    }

    $info_to_save = [
      'publication_id' => $publication_info['id']
    ];

    if(array_key_exists('paperview_publisher__paywall_availability', $_POST)) {
      $paywall_availability =
        sanitize_text_field($_POST['paperview_publisher__paywall_availability']);
      if(!in_array($paywall_availability,
                   paperview_publisher__article_paywall_availability__allowed_values()
                   )
         ) {
        $paywall_availability =
          paperview_publisher__article_paywall_availability__default();
      }

      $info_to_save['paywall_availability'] = $paywall_availability;
    }

    if(array_key_exists('paperview_publisher__price', $_POST)) {
      $price = sanitize_text_field($_POST['paperview_publisher__price']);

      $info_to_save['price'] = $price;
    }

    if(array_key_exists('paperview_publisher__article_id', $_POST)) {
      $article_id =
        sanitize_text_field($_POST['paperview_publisher__article_id']);
      if(!empty($article_id)) {
        $info_to_save['article_id'] = $article_id;

        $article_id_provided_by_user = true;
      }
    }

    paperview_publisher__db__store_article_info($post_id, $info_to_save);

    if(array_key_exists('paperview_publisher__packs', $_POST)) {
      $packs_to_assign =
        sanitize_text_field($_POST['paperview_publisher__packs']);
      $packs_to_assign = base64_decode($packs_to_assign);
      if(!empty($packs_to_assign) && $packs_to_assign !== false) {
        $packs_to_assign = json_decode($packs_to_assign, true);
        if($packs_to_assign !== null) {

          paperview_publisher__current_user__set_last_article_selected_packs(
            $packs_to_assign
          );

          paperview_publisher__db__set_article_packs(
            $post_id,
            $packs_to_assign
          );

        }
      }
    }
  }

  paperview_publisher__handle_paperview_article_saving(
    $post_id,
    $post,
    $article_id_provided_by_user
  );
}

function paperview_publisher__handle_paperview_article_saving(
  $post_id,
  $post,
  $article_id_provided_by_user
  ) {
  ///////////////////////////////////////

  require_once 'plugin_options.php';

  require_once 'paperview__flash_notices.php';

  require_once 'paperview__paperview_api_connection.php';

  require_once 'user_info.php';

  require_once 'database.php';

  ///////////////////////////////////////

  $paperview_publisher__post_statuses =
    paperview_publisher__plugin_option__supported_post_statuses();
  if(!in_array($post->post_status, $paperview_publisher__post_statuses)) {
    return;
  }

  $send_article_to_paperview = get_post_meta(
    $post_id,
    'paperview_publisher__paperview_article',
    true
  );

  $article_info = paperview_publisher__db__get_article_info($post_id);

  if($send_article_to_paperview === '1') {

    if(empty($article_info)) {
      // Should NEVER happen!
      wp_die();
    }

    $publication_id = paperview_publisher__plugin_option__publication_id();
    $publication_api_key =
      paperview_publisher__plugin_option__publication_api_key();
    if($publication_id === '' || $publication_api_key === '') {
      add_flash_notice(
        __('Paperview Publisher plugin not configured. Article not saved.', 'paperview-publisher'),
        'error'
      );
      return;
    }

    if($article_id_provided_by_user) {
      // Sync with article, instead of creating it.
      paperview_publisher__sync_article($post_id);

    } else {
      $user_id = paperview_publisher__user__get_user_id($post->post_author);
      if($user_id === '') {
        $user_id = paperview_publisher__current_user__get_user_id();
      }
      if($user_id === '') {
        $user_id =
          paperview_publisher__plugin_option__article__default_author_user_id();
      }
      if($user_id === '') {
        add_flash_notice(
          __('Paperview User ID not configured. Article not saved.', 'paperview-publisher'),
          'error'
        );
        return;
      }

      $article_title = $post->post_title;
      $article_teaser = $post->post_excerpt;
      $article_summary = null;

      //////////////////////////////////////////////////

      // Need to send the article content as it was in the DB (as the user typed
      // it!).
      $original_article_content =
      $article_content_to_protect = $post->post_content;
      if(empty($article_content_to_protect)) {
        $original_article_content = $article_content_to_protect = '';
        // Now we know that $original_article_content and
        // $article_content_to_protect are NOT null.
      }

      // Allow filters to adjust the content to be sent to Paperview.
      $article_content_to_protect = apply_filters(
        'paperview_publisher__content_to_save',
        $article_content_to_protect,
        $post_id,
        $post
      );
      $article_content_to_protect = apply_filters(
        'paperview_publisher__' . $post->post_type . '_content_to_save',
        $article_content_to_protect,
        $post_id,
        $post
      );

      // Normalize the value, just in case.
      if($article_content_to_protect == null) {
        $article_content_to_protect = '';
      }

      // Get the processed content (after applying shortcodes, etc).
      $article_content_to_protect =
        apply_filters('the_content', $article_content_to_protect);

      // Determine if we will need to store the original content too.
      $need_to_keep_original_article_content =
        ($article_content_to_protect != $original_article_content);

      // Now format the resulting content (string or array) into a Paperview
      // content array.
      if(is_array($article_content_to_protect)) {
        // Is already an array. May be a Paperview content array already!

        if(!is_array($article_content_to_protect[0])) {
          // The first element is not an array, and so the content is not in the
          // Paperview content array format.
          // Assume that the content is an array of strings.
          $article_content_to_protect = array_map(
            function($value_string, $index) {
              $index_str = ($index === 0 ? 'Content' : "Content $index");
              return
                [
                  'tag'  => $index_str,
                  'text' => ((string) $value_string)
                ];
            },
            $article_content_to_protect,
            array_keys($article_content_to_protect)
          );
        }

      } else {
        $article_content_to_protect = [
          [
            'tag'  => 'Content',
            'text' => ((string) $article_content_to_protect)
          ]
        ];
      }

      if($need_to_keep_original_article_content) {
        $article_content_to_protect[] =
          [
            'tag'  => '___WordPress_Content___',
            'text' => ((string) $original_article_content)
          ];
      }

      //////////////////////////////////////////////////

      $paywall_availability = $article_info['paywall_availability'];

      $price = $article_info['price'];

      $article_id = $article_info['article_id'];

      $connect_to_sandbox =
        paperview_publisher__plugin_option__connect_to_sandbox();

      $get_content_gibberish =
        paperview_publisher__plugin_option__get_content_gibberish();
      $get_paywall_invoker_gibberish =
        paperview_publisher__plugin_option__get_paywall_invoker_gibberish();

      $article_pack_ids = array_map(
        function($pack_row) { return $pack_row['pack_id']; },
        paperview_publisher__db__get_article_packs(
          $post_id,
          [
            'pack_id'
          ],
          true
        )
      );

      $storing_result = paperview_api__store_publication_article(
        $connect_to_sandbox,
        $publication_id,
        $publication_api_key,
        $user_id,
        $article_id,
        $article_content_to_protect,
        $article_title,
        $article_teaser,
        $article_summary,
        $paywall_availability,
        $price,
        $article_pack_ids,
        $get_content_gibberish,
        $get_paywall_invoker_gibberish
      );

      if(empty($storing_result)) {
        add_flash_notice(
          __('Error saving Paperview article.', 'paperview-publisher'),
          'error'
        );

      } else {
        $article_id = $storing_result['article'];

        paperview_publisher__db__store_article_info(
          $post_id,
          [
            'article_id'           => $article_id,
            'real_content'         => json_encode($article_content_to_protect),
            'paywall_availability' => $storing_result['paywall_availability'],
            'price'                => $storing_result['price_amount'],
            'last_sync'            => time(),
          ]
        );

        $result_content = $storing_result['result_content'];
        if(!empty($result_content)) {
          paperview_publisher__db__store_post_data(
            $post_id,
            $result_content[0]['text']
          );
        }

        $pack_ids_explicitly_assigned = [];
        $pack_ids_implicitly_assigned = [];
        foreach($storing_result['packs'] as $pack_id => $pack_info) {
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

      }
    }

  } else {
    // Restore the original clear text content, if possible.
    if(!empty($article_info)) {
      $the_content = $article_info['real_content'];
      if(!empty($the_content)) {
        $the_content = json_decode($the_content, true);
        if(!empty($the_content)) {
          paperview_publisher__db__store_post_data(
            $post_id,
            $the_content[0]['text']
          );
        }
      }
    }
  }
}

function paperview_publisher__sync_with_paperview_article__ajax_handler() {
  ///////////////////////////////////////

  require_once 'publisher_utils.php';

  ///////////////////////////////////////

  $nonce_ok = check_ajax_referer(
    'paperview_publisher__sync_with_paperview_article',
    false,
    false
  );
  if(!$nonce_ok) {
    wp_send_json_error();
  }

  ///////////////////////////////////////

  $post_id = sanitize_text_field($_POST['post_id']);
  if(empty($post_id)) {
    wp_send_json_error();
  }
  $post_id = (int) $post_id;

  ///////////////////////////////////////

  paperview_publisher__sync_article($post_id, true);

  ///////////////////////////////////////

  wp_send_json_success();
}

////////////////////////////////////////////////////////////////////////////////

/*
// TODO: replace metaboxes with blocks
add_action(
  'init',
  function() {
    foreach(paperview_publisher__plugin_option__supported_post_types() as $post_type) {

      $post_type_object = get_post_type_object($post_type);
      $post_type_object->template = [
        ['paperview-publisher/article-settings-block'],
      ];

    }
  }
);
*/

////////////////////////////////////////////////////////////////////////////////

add_filter(
  'admin_body_class',
  function($classes) {
    if(paperview_publisher__check_if_current_screen_is_editor()) {
      $classes .= ' paperview-article-editor';
    }
    return $classes;
  }
);

////////////////////////////////////////////////////////////////////////////////

add_filter(
  'plugin_action_links_' . paperview_publisher__main_file(),
  function($actions) {
    $settings_action =
      '<a href="' . menu_page_url('paperview_publisher__admin', false) .'">' .
        __('Settings', 'paperview-publisher') . '</a>';
    array_unshift($actions, $settings_action);
    return $actions;
  }
);

////////////////////////////////////////////////////////////////////////////////

add_action(
  'admin_enqueue_scripts',
  function() {
    wp_enqueue_style(
      'paperview_publisher__admin_css',
      plugin_dir_url(__FILE__) . '/assets/plugin_admin.css',
      false,
      '1.0.0'
    );

    wp_enqueue_script(
      'paperview_publisher__admin_script',
      plugin_dir_url(__FILE__) . '/assets/plugin_admin.js',
      [],
      '1.0.0'
    );
  }
);

////////////////////////////////////////////////////////////////////////////////
