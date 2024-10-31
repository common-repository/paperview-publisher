<?php

////////////////////////////////////////////////////////////////////////////////

if(!defined('ABSPATH')) exit; // Exit if accessed directly

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__user__get_user_id($user_id) {
  return get_user_meta($user_id, 'paperview_publisher__user_id', true);
}

function paperview_publisher__current_user__get_user_id() {
  $user_id = get_current_user_id();
  if(empty($user_id)) {
    return null;
  }
  return paperview_publisher__user__get_user_id($user_id);
}

////////////////////////////////////////////////////////////////////////////////

function paperview_publisher__user__get_last_article_selected_packs($user_id) {
  return get_user_meta(
    $user_id,
    'paperview_publisher__last_article_selected_packs',
    true
  );
}

function paperview_publisher__current_user__get_last_article_selected_packs() {
  $user_id = get_current_user_id();
  if(empty($user_id)) {
    return [];
  }
  return paperview_publisher__user__get_last_article_selected_packs($user_id);
}

function paperview_publisher__user__set_last_article_selected_packs(
  $user_id,
  $pack_ids
  ) {
  if(current_user_can('edit_user', $user_id)) {
    update_user_meta(
      $user_id,
      'paperview_publisher__last_article_selected_packs',
      $pack_ids
    );
  }
}

function paperview_publisher__current_user__set_last_article_selected_packs(
  $pack_ids
  ) {
  $user_id = get_current_user_id();
  if(!empty($user_id)) {
    paperview_publisher__user__set_last_article_selected_packs(
      $user_id,
      $pack_ids
    );
  }
}

////////////////////////////////////////////////////////////////////////////////

add_action(
  'show_user_profile',
  'paperview_publisher__add_user_profile_fields'
);
add_action(
  'edit_user_profile',
  'paperview_publisher__add_user_profile_fields'
);

function paperview_publisher__add_user_profile_fields($user) {
?>
<h2><?php _e('Paperview User settings', 'paperview-publisher'); ?></h2>
<table class="form-table" role="presentation">
  <tr>
    <th scope="row"><label for="paperview_publisher__user_id"><?php _e('Paperview User ID', 'paperview-publisher'); ?></label></th>
    <td>
      <input type="text" name="paperview_publisher__user_id" id="paperview_publisher__user_id" aria-describedby="paperview_publisher__user_id__description" value="<?php echo esc_attr(paperview_publisher__user__get_user_id($user->ID)); ?>" class="regular-text" />
      <p class="description" id="paperview_publisher__user_id__description"><?php _e('Please enter the Paperview User ID', 'paperview-publisher'); ?></p>
    </td>
  </tr>
</table>
<?php
}

////////////////////////////////////////////////////////////////////////////////

add_action(
  'personal_options_update',
  'paperview_publisher__save_user_profile_fields'
);
add_action(
  'edit_user_profile_update',
  'paperview_publisher__save_user_profile_fields'
);

function paperview_publisher__save_user_profile_fields($user_id) {
  if(current_user_can('edit_user', $user_id)) {
    update_user_meta(
      $user_id,
      'paperview_publisher__user_id',
      sanitize_text_field($_POST['paperview_publisher__user_id'])
    );
  }
}

////////////////////////////////////////////////////////////////////////////////
